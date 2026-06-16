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

            $intendedStatus = $data['status'] ?? Purchase::STATUS_DRAFT;

            $purchase = new Purchase();
            $purchase->supplier_id    = $supplier->id;
            $purchase->purchase_date  = $date->toDateString();
            $purchase->invoice_number = Purchase::generateInvoiceNumber($supplier, $date);
            $purchase->location_id    = $data['location_id'] ?? null;
            $purchase->tax_type       = $data['tax_type'] ?? Purchase::TAX_NONE;
            $purchase->note           = $data['note'] ?? null;
            // Always build as DRAFT first, then promote via post() so the
            // stock IN movements are written. Setting status='posted'
            // directly here would leave the ledger empty.
            $purchase->status         = Purchase::STATUS_DRAFT;
            $purchase->paid_amount    = (float) ($data['paid_amount'] ?? 0);
            $purchase->save();

            $this->syncLines($purchase, $data['lines'] ?? []);
            $this->recalculate($purchase);

            if ($intendedStatus === Purchase::STATUS_POSTED) {
                $this->post($purchase);
            }

            return $this->repo->refresh($purchase);
        });
    }

    /**
     * Update an existing purchase. Behaviour depends on status:
     *
     *   - Draft:   full line-item replace (existing behaviour).
     *   - Posted:  full line-item replace too, but the inventory ledger
     *              must be kept in sync — see updatePostedLines(). Only
     *              reachable when Purchase::editBlockReason() is null
     *              (no sales against this purchase's stock yet, and
     *              within the configurable edit window).
     *   - Other (cancelled): lightweight note/paid_amount only, as a
     *              defensive fallback — the controller's editBlockReason()
     *              gate normally prevents reaching here at all.
     */
    public function update(Purchase $purchase, array $data): Purchase
    {
        return DB::transaction(function () use ($purchase, $data) {

            if ($purchase->isPosted()) {
                return $this->updatePostedLines($purchase, $data);
            }

            if (! $purchase->isDraft()) {
                $purchase->note        = $data['note']        ?? $purchase->note;
                $purchase->paid_amount = $data['paid_amount'] ?? $purchase->paid_amount;
                $purchase->due_amount  = max(0, (float) $purchase->grand_total - (float) $purchase->paid_amount);
                $purchase->save();
                return $this->repo->refresh($purchase);
            }

            $purchase->purchase_date = $data['purchase_date'] ?? $purchase->purchase_date;
            $purchase->location_id   = $data['location_id']   ?? $purchase->location_id;
            $purchase->tax_type      = $data['tax_type']      ?? $purchase->tax_type;
            $purchase->note          = $data['note']          ?? $purchase->note;
            $purchase->paid_amount   = (float) ($data['paid_amount'] ?? 0);

            // Hard reset of children — cheapest correct strategy. Safe for
            // drafts: no stock movements exist yet to reference these rows.
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
     * Edit a POSTED purchase's lines while keeping the stock ledger in
     * sync. Only reachable when editBlockReason() is null, which already
     * guarantees no sale has consumed any of this purchase's stock — so
     * every currently-posted piece's on-hand balance equals exactly its
     * original IN quantity (modulo transfers, checked below).
     *
     * Strategy (ledger is append-only, ON DELETE RESTRICT on
     * stock_movements.purchase_product_id, so old rows can't be hard
     * deleted):
     *   1. Verify none of the currently-posted pieces have moved away
     *      from the purchase's location (e.g. via a stock transfer).
     *   2. Emit OUT "purchase_cancel" movements reversing every current
     *      piece at the OLD location.
     *   3. Soft-delete the old lines/rows (kept for ledger history —
     *      they remain valid FK targets for the movements above).
     *   4. Update header fields and rebuild lines/rows from the new
     *      payload via syncLines() + recalculate().
     *   5. Emit fresh IN "purchase" movements for the new rows at the
     *      (possibly updated) location.
     */
    private function updatePostedLines(Purchase $purchase, array $data): Purchase
    {
        $oldLocationId = (int) $purchase->location_id;
        if (! $oldLocationId) {
            throw new InvalidArgumentException('Cannot edit this purchase: it has no location set.');
        }

        $purchase->load('lines.rows');

        // 1. Pre-flight — none of this purchase's pieces may have moved
        //    away from the posting location (e.g. a stock transfer).
        foreach ($purchase->lines as $line) {
            foreach ($line->rows as $row) {
                $qty = (int) $row->qty;
                if ($qty <= 0) {
                    continue;
                }

                $onHand = $this->stock->onHandForPiece($row->id, $oldLocationId);
                if ($onHand < $qty) {
                    throw new InvalidArgumentException(
                        "Cannot edit this purchase: stock for one of its items has already moved "
                        . "(on hand {$onHand}, expected {$qty}). Reverse the downstream movement first."
                    );
                }
            }
        }

        // 2. Reverse every current piece at the old location.
        foreach ($purchase->lines as $line) {
            foreach ($line->rows as $row) {
                $qty = (int) $row->qty;
                if ($qty <= 0) {
                    continue;
                }

                $this->stock->record([
                    'purchase_product_id' => $row->id,
                    'product_id'          => $line->product_id,
                    'location_id'         => $oldLocationId,
                    'direction'           => StockMovement::DIRECTION_OUT,
                    'qty'                 => $qty,
                    'reason'              => StockMovement::REASON_PURCHASE_CANCEL,
                    'source_type'         => StockMovement::SOURCE_PURCHASE,
                    'source_id'           => $purchase->id,
                    'source_line_id'      => $line->id,
                    'rack_id'             => $row->rack_id,
                    'notes'               => 'Reversed for edit of purchase ' . $purchase->invoice_number,
                ]);
            }
        }

        // 3. Soft-delete the old lines/rows — kept for ledger history, but
        //    excluded from $purchase->lines() / recalculate() going forward.
        foreach ($purchase->lines as $line) {
            $line->rows()->delete();
            $line->delete();
        }

        // 4. Header fields + rebuild lines from the new payload.
        $purchase->purchase_date = $data['purchase_date'] ?? $purchase->purchase_date;
        $purchase->location_id   = $data['location_id']   ?? $purchase->location_id;
        $purchase->tax_type      = $data['tax_type']      ?? $purchase->tax_type;
        $purchase->note          = $data['note']          ?? $purchase->note;
        $purchase->paid_amount   = (float) ($data['paid_amount'] ?? $purchase->paid_amount);
        $purchase->save();

        $this->syncLines($purchase, $data['lines'] ?? []);
        $this->recalculate($purchase);

        // 5. Post fresh IN movements for the new rows at the (possibly
        //    updated) location.
        $newLocationId = (int) ($purchase->location_id ?: $oldLocationId);
        $purchase->load('lines.rows');

        foreach ($purchase->lines as $line) {
            foreach ($line->rows as $row) {
                $qty = (int) $row->qty;
                if ($qty <= 0) {
                    continue;
                }

                $this->stock->record([
                    'purchase_product_id' => $row->id,
                    'product_id'          => $line->product_id,
                    'location_id'         => $newLocationId,
                    'direction'           => StockMovement::DIRECTION_IN,
                    'qty'                 => $qty,
                    'reason'              => StockMovement::REASON_PURCHASE,
                    'source_type'         => StockMovement::SOURCE_PURCHASE,
                    'source_id'           => $purchase->id,
                    'source_line_id'      => $line->id,
                    'rack_id'             => $row->rack_id,
                    'movement_date'       => optional($purchase->purchase_date)->toDateString() ?? now()->toDateString(),
                ]);
            }
        }

        return $this->repo->refresh($purchase);
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
                'carat_weight'     => null,
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
                // Net = carat_weight × price (tax and discount not used).
                $qty             = max(0, (int) ($r['qty'] ?? ($unitContains ?? 1)));
                $caratWeight     = isset($r['carat_weight']) && $r['carat_weight'] !== '' ? (float) $r['carat_weight'] : null;
                $price           = (float) ($r['price'] ?? 0);

                $row = new PurchaseProduct([
                    'qty'              => $qty,
                    'carat_weight'     => $caratWeight,
                    'barcode'          => $r['barcode']          ?? null,
                    'rack_id'          => $r['rack_id']          ?? null,
                    'serial_number'    => $r['serial_number']    ?? null,
                    'price'            => $price,
                    'tax_percent'      => 0,
                    'tax_amount'       => 0,
                    'discount_percent' => 0,
                    'discount_amount'  => 0,
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
     * Net = carat_weight × price; tax and discount are not used.
     */
    private function recalculate(Purchase $purchase): void
    {
        $invoiceTotal = 0.0;

        foreach ($purchase->lines()->with('rows')->get() as $line) {
            $lineTotal = 0.0;

            foreach ($line->rows as $row) {
                $net        = (float) $row->carat_weight * (float) $row->price;
                $lineTotal += $net;
            }

            $line->subtotal = round($lineTotal, 2);
            $line->total    = round($lineTotal, 2);
            $line->save();

            $invoiceTotal += $lineTotal;
        }

        $purchase->subtotal       = round($invoiceTotal, 2);
        $purchase->discount_total = 0;
        $purchase->tax_total      = 0;
        $purchase->grand_total    = round($invoiceTotal, 2);
        $purchase->due_amount     = round(max(0, $invoiceTotal - (float) $purchase->paid_amount), 2);
        $purchase->save();
    }
}
