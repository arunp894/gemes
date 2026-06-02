<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Models\PurchaseProduct;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Repositories\PurchaseRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Purchase orchestration. The controller hands off the whole request
 * payload here; this class owns:
 *
 *   - transactional save (purchase + lines + inventory rows)
 *   - invoice number generation (per-supplier-per-month sequence)
 *   - line/row total reconciliation (server is source of truth)
 *   - status transitions (draft -> posted -> cancelled)
 *
 * Stock is NOT a separate table — purchase_products IS the stock ledger.
 * Posting only flips Purchase::status, which lets stock queries filter
 * via `whereHas('line.purchase', fn($q) => $q->posted())`.
 */
class PurchaseService
{
    public function __construct(
        private PurchaseRepository $repo,
        private StockService       $stock,
    ) {}

    /* ─── Public API ───────────────────────────────────────── */

    /**
     * Create a new purchase from validated request data.
     *
     * Expected payload shape (see StorePurchaseRequest):
     * [
     *   'supplier_id'    => int,
     *   'purchase_date'  => 'YYYY-MM-DD',
     *   'tax_type'       => 'none'|'cgst_sgst'|'igst',
     *   'note'           => string|null,
     *   'paid_amount'    => float,
     *   'status'         => 'draft'|'posted',
     *   'lines' => [
     *     [
     *       'product_id'    => int,
     *       'type'          => 'piece'|'unit'|'carton',
     *       'package_name'  => string|null,
     *       'package_qty'   => int,
     *       'unit_contains' => int|null,
     *       'remarks'       => string|null,
     *       'rows' => [
     *         [
     *           'qty'              => int,
     *           'barcode'          => string|null,
     *           'rack_id'          => int|null,
     *           'serial_number'    => string|null,
     *           'price'            => float,
     *           'tax_percent'      => float,
     *           'discount_percent' => float,
     *           'expiry_date'      => 'YYYY-MM-DD'|null,
     *           'manufacture_date' => 'YYYY-MM-DD'|null,
     *           'remarks'          => string|null,
     *         ],
     *         ...
     *       ],
     *     ],
     *     ...
     *   ],
     * ]
     */
    public function create(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $supplier = Supplier::findOrFail($data['supplier_id']);
            $date     = Carbon::parse($data['purchase_date']);

            $purchase = new Purchase();
            $purchase->supplier_id    = $supplier->id;
            $purchase->purchase_date  = $date->toDateString();
            $purchase->invoice_number = Purchase::generateInvoiceNumber($supplier, $date);
            $purchase->tax_type       = $data['tax_type'] ?? Purchase::TAX_NONE;
            $purchase->note           = $data['note'] ?? null;
            $purchase->status         = $data['status'] ?? Purchase::STATUS_DRAFT;
            $purchase->paid_amount    = (float) ($data['paid_amount'] ?? 0);
            $purchase->save();

            $this->syncLines($purchase, $data['lines'] ?? []);
            $this->recalculate($purchase);

            return $this->repo->refresh($purchase);
        });
    }

    /**
     * Update an existing purchase. Lines are reconciled by full replace
     * (simpler than diffing, safe because purchase_products has no FK
     * dependents). Posted purchases are immutable except for `note` and
     * `paid_amount`; the request validator already enforces this.
     */
    public function update(Purchase $purchase, array $data): Purchase
    {
        return DB::transaction(function () use ($purchase, $data) {

            // Allow editing only when the purchase is still a draft.
            if (! $purchase->isDraft()) {
                $purchase->note        = $data['note']        ?? $purchase->note;
                $purchase->paid_amount = $data['paid_amount'] ?? $purchase->paid_amount;
                $purchase->due_amount  = max(0, (float) $purchase->grand_total - (float) $purchase->paid_amount);
                $purchase->save();
                return $this->repo->refresh($purchase);
            }

            $purchase->purchase_date = $data['purchase_date'] ?? $purchase->purchase_date;
            $purchase->tax_type      = $data['tax_type']      ?? $purchase->tax_type;
            $purchase->note          = $data['note']          ?? $purchase->note;
            $purchase->paid_amount   = (float) ($data['paid_amount'] ?? 0);

            // Hard reset of children — cheapest correct strategy.
            $purchase->lines()->each(function (PurchaseLine $l) {
                $l->rows()->forceDelete();
                $l->forceDelete();
            });

            $purchase->save();

            $this->syncLines($purchase, $data['lines'] ?? []);
            $this->recalculate($purchase);

            return $this->repo->refresh($purchase);
        });
    }

    /**
     * Post a draft purchase. Once posted the inventory rows are live.
     *
     * Wires into StockService: emits IN movements per purchase_product
     * row at the purchase's location. The movement creation runs in the
     * SAME transaction as the status flip so a failure rolls everything
     * back cleanly.
     */
    public function post(Purchase $purchase): Purchase
    {
        if ($purchase->isPosted()) {
            return $purchase;
        }
        if ($purchase->isCancelled()) {
            throw new InvalidArgumentException('Cannot post a cancelled purchase.');
        }
        if ($purchase->lines()->count() === 0) {
            throw new InvalidArgumentException('Cannot post an empty purchase.');
        }

        return DB::transaction(function () use ($purchase) {
            $purchase->status = Purchase::STATUS_POSTED;
            $purchase->save();

            // Emit IN movements. StockService guards against double-post
            // via hasMovementsFromSource() so a retry is safe.
            $this->stock->recordPurchasePosting($purchase);

            return $this->repo->refresh($purchase);
        });
    }

    public function cancel(Purchase $purchase): Purchase
    {
        return DB::transaction(function () use ($purchase) {
            // If the purchase was posted, reverse its stock movements
            // BEFORE flipping status — that way the safety guard in
            // StockService::reversePurchasePosting() can still see the
            // 'posted' history when checking downstream consumption.
            if ($purchase->isPosted()) {
                $this->stock->reversePurchasePosting($purchase);
            }

            $purchase->status = Purchase::STATUS_CANCELLED;
            $purchase->save();

            return $this->repo->refresh($purchase);
        });
    }

    public function delete(Purchase $purchase): void
    {
        DB::transaction(function () use ($purchase) {
            // Cascading soft-deletes don't propagate automatically;
            // walk the tree.
            $purchase->lines()->each(function (PurchaseLine $l) {
                $l->rows()->delete();
                $l->delete();
            });
            $purchase->delete();
        });
    }

    /* ─── Internal helpers ─────────────────────────────────── */

    /**
     * Persist all lines + their inventory rows. Server expands carton
     * quantities into one inventory row per inner pack even if the
     * client somehow under-populated `rows[]`.
     */
    private function syncLines(Purchase $purchase, array $lines): void
    {
        foreach ($lines as $lineData) {
            /** @var Product $product */
            $product = Product::findOrFail($lineData['product_id']);

            $type        = $lineData['type']        ?? PurchaseLine::TYPE_PIECE;
            $packageQty  = max(1, (int) ($lineData['package_qty'] ?? 1));
            $packageName = $lineData['package_name']
                ?? ($type === PurchaseLine::TYPE_PIECE ? 'Piece' : 'Box');

            $unitContains = ($type === PurchaseLine::TYPE_BOX)
                ? (int) ($product->inner_pack_contains ?? 1)
                : null;

            // Total pieces: for box lines, multiply package_qty × unit_contains.
            $totalQty = match ($type) {
                PurchaseLine::TYPE_BOX   => $packageQty * (int) ($product->inner_pack_contains ?? 1),
                default                  => $packageQty,
            };

            $line = new PurchaseLine([
                'product_id'    => $product->id,
                'type'          => $type,
                'package_name'  => $packageName,
                'package_qty'   => $packageQty,
                'total_qty'     => $totalQty,
                'unit_contains' => $unitContains,
                'remarks'       => $lineData['remarks'] ?? null,
            ]);
            $purchase->lines()->save($line);

            $rows = $lineData['rows'] ?? [];

            // Guard: if the client didn't send enough rows for a box line,
            // fabricate the missing ones with the row[0] template.
            $expectedRows = match ($type) {
                PurchaseLine::TYPE_BOX => $packageQty,
                default                => max(1, count($rows) ?: 1),
            };

            $template = $rows[0] ?? [
                'qty'              => $unitContains ?? 1,
                'barcode'          => null,
                'rack_id'          => null,
                'serial_number'    => null,
                'price'            => 0,
                'tax_percent'      => 0,
                'discount_percent' => 0,
                'expiry_date'      => null,
                'manufacture_date' => null,
                'remarks'          => null,
            ];

            for ($i = 0; $i < $expectedRows; $i++) {
                $r = $rows[$i] ?? $template;

                // Per-row money math — server is the source of truth.
                $qty             = max(0, (int) ($r['qty'] ?? ($unitContains ?? 1)));
                $price           = (float) ($r['price'] ?? 0);
                $taxPercent      = (float) ($r['tax_percent'] ?? 0);
                $discountPercent = (float) ($r['discount_percent'] ?? 0);

                $gross           = $qty * $price;
                $discountAmount  = round($gross * $discountPercent / 100, 2);
                $taxableBase     = $gross - $discountAmount;
                $taxAmount       = round($taxableBase * $taxPercent / 100, 2);

                $row = new PurchaseProduct([
                    'qty'              => $qty,
                    'barcode'          => $r['barcode']          ?? null,
                    'rack_id'          => $r['rack_id']          ?? null,
                    'serial_number'    => $r['serial_number']    ?? null,
                    'price'            => $price,
                    'tax_percent'      => $taxPercent,
                    'tax_amount'       => $taxAmount,
                    'discount_percent' => $discountPercent,
                    'discount_amount'  => $discountAmount,
                    'expiry_date'      => $r['expiry_date']      ?? null,
                    'manufacture_date' => $r['manufacture_date'] ?? null,
                    'remarks'          => $r['remarks']          ?? null,
                ]);

                $line->rows()->save($row);
            }
        }
    }

    /**
     * Recompute line and invoice totals from the persisted rows.
     * This is the ONLY place subtotal/tax/discount/grand totals are set.
     */
    private function recalculate(Purchase $purchase): void
    {
        $invoiceSubtotal = 0.0;
        $invoiceTax      = 0.0;
        $invoiceDiscount = 0.0;

        foreach ($purchase->lines()->with('rows')->get() as $line) {
            $lineSubtotal = 0.0;
            $lineTotal    = 0.0;

            foreach ($line->rows as $row) {
                $gross = (float) $row->qty * (float) $row->price;
                $net   = $gross - (float) $row->discount_amount + (float) $row->tax_amount;

                $lineSubtotal     += $gross;
                $lineTotal        += $net;
                $invoiceSubtotal  += $gross;
                $invoiceDiscount  += (float) $row->discount_amount;
                $invoiceTax       += (float) $row->tax_amount;
            }

            $line->subtotal = round($lineSubtotal, 2);
            $line->total    = round($lineTotal, 2);
            $line->save();
        }

        $grand = $invoiceSubtotal - $invoiceDiscount + $invoiceTax;

        $purchase->subtotal       = round($invoiceSubtotal, 2);
        $purchase->discount_total = round($invoiceDiscount, 2);
        $purchase->tax_total      = round($invoiceTax, 2);
        $purchase->grand_total    = round($grand, 2);
        $purchase->due_amount     = round(max(0, $grand - (float) $purchase->paid_amount), 2);
        $purchase->save();
    }
}
