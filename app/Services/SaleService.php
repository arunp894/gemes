<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseProduct;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\SalePayment;
use App\Models\StockMovement;
use App\Repositories\SaleRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Sale orchestration. Owns:
 *
 *   - transactional create / update / status transitions
 *   - sale number generation (global per-month sequence)
 *   - line + payment money math (server is source of truth)
 *   - payment status / balance recalculation
 *
 * The lines table IS the outgoing-stock ledger:
 *   on_hand = SUM(purchase_products.qty WHERE posted)
 *           − SUM(sale_lines.qty WHERE sale.status IN (posted, completed))
 *
 * That query lives elsewhere (a future Stock reporter); this service
 * just makes sure the data shape stays correct.
 */
class SaleService
{
    public function __construct(
        private SaleRepository $repo,
        private StockService   $stock,
    ) {}

    /* ─── Public API ───────────────────────────────────────── */

    /**
     * Create a new sale from the validated request payload.
     *
     * Expected payload shape (see StoreSaleRequest):
     * [
     *   'sale_date'        => 'YYYY-MM-DD',
     *   'customer_id'      => int,
     *   'location_id'      => int,
     *   'salesperson_id'   => int|null,
     *   'tax_type'         => 'none'|'cgst_sgst'|'igst',
     *   'shipping_charge'  => float,
     *   'note'             => string|null,
     *   'status'           => 'draft'|'posted'|'completed',
     *   'lines' => [
     *     [
     *       'product_id'          => int,
     *       'purchase_product_id' => int|null,
     *       'barcode'             => string|null,
     *       'qty'                 => int,
     *       'unit_price'          => float,
     *       'tax_percent'         => float,
     *       'discount_percent'    => float,
     *       'notes'               => string|null,
     *     ],
     *     ...
     *   ],
     *   'payments' => [
     *     [
     *       'payment_date'     => 'YYYY-MM-DD',
     *       'amount'           => float,
     *       'payment_method'   => string,
     *       'reference_number' => string|null,
     *       'notes'            => string|null,
     *     ],
     *     ...
     *   ],
     * ]
     */
    public function create(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $customer = Customer::findOrFail($data['customer_id']);
            $location = Location::findOrFail($data['location_id']);
            $date     = Carbon::parse($data['sale_date'] ?? now()->toDateString());

            $sale = new Sale();
            $sale->sale_number     = Sale::generateSaleNumber($date);
            $sale->sale_date       = $date->toDateString();
            $sale->customer_id     = $customer->id;
            $sale->location_id     = $location->id;
            $sale->channel_id      = $data['channel_id']      ?? null;
            $sale->salesperson_id  = $data['salesperson_id']  ?? auth()->id();
            $sale->tax_type        = $data['tax_type']        ?? Sale::TAX_NONE;
            $sale->shipping_charge = (float) ($data['shipping_charge'] ?? 0);
            $sale->note            = $data['note']            ?? null;

            // Start as DRAFT so we can build lines + payments first,
            // then run the stock check, THEN transition to the intended
            // status. This keeps the availability error path clean
            // (we never have a half-posted sale with no movements).
            $intendedStatus = $data['status'] ?? Sale::STATUS_DRAFT;
            $sale->status   = Sale::STATUS_DRAFT;
            $sale->save();

            $this->syncLines($sale, $data['lines'] ?? []);
            $this->syncPayments($sale, $data['payments'] ?? [], replace: true);
            $this->recalculate($sale);

            // Apply the real status. post() and complete() both call
            // recordSalePosting() which runs the availability check.
            if ($intendedStatus === Sale::STATUS_POSTED) {
                $this->post($sale);
            } elseif ($intendedStatus === Sale::STATUS_COMPLETED) {
                $this->post($sale);
                $sale->refresh();
                $this->complete($sale);
            }

            return $this->repo->refresh($sale);
        });
    }

    /**
     * Update an existing sale. Drafts can be fully re-saved; non-drafts
     * only let through `note` and `shipping_charge` changes (the request
     * validator should already enforce this).
     */
    public function update(Sale $sale, array $data): Sale
    {
        return DB::transaction(function () use ($sale, $data) {

            if (! $sale->isEditable()) {
                $sale->note            = $data['note']            ?? $sale->note;
                $sale->shipping_charge = (float) ($data['shipping_charge'] ?? $sale->shipping_charge);
                $sale->save();
                $this->recalculate($sale);
                return $this->repo->refresh($sale);
            }

            $sale->sale_date       = $data['sale_date']       ?? $sale->sale_date;
            $sale->customer_id     = $data['customer_id']     ?? $sale->customer_id;
            $sale->location_id     = $data['location_id']     ?? $sale->location_id;
            $sale->channel_id      = array_key_exists('channel_id', $data) ? $data['channel_id'] : $sale->channel_id;
            $sale->salesperson_id  = $data['salesperson_id']  ?? $sale->salesperson_id;
            $sale->tax_type        = $data['tax_type']        ?? $sale->tax_type;
            $sale->shipping_charge = (float) ($data['shipping_charge'] ?? 0);
            $sale->note            = $data['note']            ?? null;

            // Hard reset of children — simplest correct strategy.
            $sale->lines()->each(function (SaleLine $l) {
                $l->forceDelete();
            });

            $sale->save();

            $this->syncLines($sale, $data['lines'] ?? []);
            $this->syncPayments($sale, $data['payments'] ?? [], replace: true);
            $this->recalculate($sale);

            return $this->repo->refresh($sale);
        });
    }

    /* ─── Status transitions ──────────────────────────────── */

    public function post(Sale $sale): Sale
    {
        if ($sale->isPosted() || $sale->isCompleted()) {
            return $sale;
        }
        if ($sale->isCancelled() || $sale->isRefunded()) {
            throw new InvalidArgumentException('Cannot post a cancelled or refunded sale.');
        }
        if ($sale->lines()->count() === 0) {
            throw new InvalidArgumentException('Cannot post an empty sale.');
        }

        return DB::transaction(function () use ($sale) {
            // Hard stock check + ledger writes. Throws RuntimeException
            // if any line can't be filled — the surrounding transaction
            // ensures we never end up with status=posted but no movements.
            $this->stock->recordSalePosting($sale);

            $sale->status = Sale::STATUS_POSTED;
            $sale->save();

            return $this->repo->refresh($sale);
        });
    }

    /**
     * Mark a posted sale as completed. Reached when the customer has
     * been fully paid AND delivered (the UI calls this).
     */
    public function complete(Sale $sale): Sale
    {
        if (! $sale->isPosted()) {
            throw new InvalidArgumentException('Only posted sales can be completed.');
        }
        if ((float) $sale->balance_due > 0.0001) {
            throw new InvalidArgumentException('Cannot complete a sale with an outstanding balance.');
        }

        $sale->status = Sale::STATUS_COMPLETED;
        $sale->save();

        return $this->repo->refresh($sale);
    }

    public function refund(Sale $sale): Sale
    {
        if ($sale->isDraft() || $sale->isCancelled()) {
            throw new InvalidArgumentException('Only posted or completed sales can be refunded.');
        }

        return DB::transaction(function () use ($sale) {
            // Restore stock first, then flip status.
            $this->stock->reverseSalePosting($sale, StockMovement::REASON_SALE_RETURN);
            $sale->status = Sale::STATUS_REFUNDED;
            $sale->save();
            return $this->repo->refresh($sale);
        });
    }

    public function cancel(Sale $sale): Sale
    {
        if ($sale->isCompleted() || $sale->isRefunded()) {
            throw new InvalidArgumentException('Completed or refunded sales cannot be cancelled.');
        }

        return DB::transaction(function () use ($sale) {
            // If the sale had been posted, return stock with a 'sale_cancel'
            // reason. Draft sales never wrote movements — nothing to reverse.
            if ($sale->isPosted()) {
                $this->stock->reverseSalePosting($sale, StockMovement::REASON_SALE_CANCEL);
            }
            $sale->status = Sale::STATUS_CANCELLED;
            $sale->save();
            return $this->repo->refresh($sale);
        });
    }

    public function delete(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            // SoftDeletes doesn't cascade automatically — walk the tree.
            $sale->lines()->each(fn (SaleLine $l) => $l->delete());
            $sale->payments()->each(fn (SalePayment $p) => $p->delete());
            $sale->delete();
        });
    }

    /* ─── Payment helpers (public for the show page) ──────── */

    /**
     * Append a single payment to an existing sale and refresh totals.
     */
    public function addPayment(Sale $sale, array $data): SalePayment
    {
        return DB::transaction(function () use ($sale, $data) {
            $payment = new SalePayment([
                'payment_date'     => $data['payment_date']     ?? now()->toDateString(),
                'amount'           => (float) ($data['amount']  ?? 0),
                'payment_method'   => $data['payment_method']   ?? SalePayment::METHOD_CASH,
                'reference_number' => $data['reference_number'] ?? null,
                'notes'            => $data['notes']            ?? null,
            ]);
            $sale->payments()->save($payment);

            $this->recalculatePayments($sale);

            return $payment->refresh();
        });
    }

    public function removePayment(SalePayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $sale = $payment->sale;
            $payment->delete();
            if ($sale) {
                $this->recalculatePayments($sale);
            }
        });
    }

    /* ─── Internals ────────────────────────────────────────── */

    /**
     * Persist all sale lines. Server recomputes money math from the raw
     * inputs (qty, unit_price, *percent fields) so client tampering can't
     * change recorded totals.
     */
    private function syncLines(Sale $sale, array $lines): void
    {
        foreach ($lines as $row) {
            /** @var Product $product */
            $product = Product::findOrFail($row['product_id']);

            $qty             = max(1, (int) ($row['qty'] ?? 1));
            $unitPrice       = (float) ($row['unit_price'] ?? 0);
            $taxPercent      = (float) ($row['tax_percent'] ?? 0);
            $discountPercent = (float) ($row['discount_percent'] ?? 0);

            $gross          = $qty * $unitPrice;
            $discountAmount = round($gross * $discountPercent / 100, 2);
            $taxableBase    = $gross - $discountAmount;
            $taxAmount      = round($taxableBase * $taxPercent / 100, 2);
            $total          = round($taxableBase + $taxAmount, 2);

            // Snapshot cost from the linked PurchaseProduct (if any) so
            // historical margin reports stay accurate forever.
            $costPrice = 0.0;
            $purchaseProductId = $row['purchase_product_id'] ?? null;
            if ($purchaseProductId) {
                $pp = PurchaseProduct::find($purchaseProductId);
                if ($pp) {
                    $costPrice = (float) $pp->price;
                }
            }

            $line = new SaleLine([
                'product_id'          => $product->id,
                'purchase_product_id' => $purchaseProductId,
                'barcode'             => $row['barcode'] ?? null,
                'qty'                 => $qty,
                'unit_price'          => $unitPrice,
                'tax_percent'         => $taxPercent,
                'tax_amount'          => $taxAmount,
                'discount_percent'    => $discountPercent,
                'discount_amount'     => $discountAmount,
                'subtotal'            => round($gross, 2),
                'total'               => $total,
                'cost_price'          => $costPrice,
                'notes'               => $row['notes'] ?? null,
            ]);

            $sale->lines()->save($line);
        }
    }

    /**
     * Persist payment rows. When $replace is true (the standard create/
     * update flow) all existing payments on the sale are wiped first.
     */
    private function syncPayments(Sale $sale, array $payments, bool $replace = false): void
    {
        if ($replace) {
            $sale->payments()->each(fn (SalePayment $p) => $p->forceDelete());
        }

        foreach ($payments as $row) {
            $amount = (float) ($row['amount'] ?? 0);
            // Skip zero-amount entries entirely — they'd just clutter audit.
            if (abs($amount) < 0.001) {
                continue;
            }

            $sale->payments()->save(new SalePayment([
                'payment_date'     => $row['payment_date']     ?? $sale->sale_date->toDateString(),
                'amount'           => $amount,
                'payment_method'   => $row['payment_method']   ?? SalePayment::METHOD_CASH,
                'reference_number' => $row['reference_number'] ?? null,
                'notes'            => $row['notes']            ?? null,
            ]));
        }
    }

    /**
     * Recompute header totals from line rows + payment rows. The ONLY
     * place subtotal/tax/discount/grand/paid/balance are written.
     */
    private function recalculate(Sale $sale): void
    {
        $subtotal = 0.0;
        $tax      = 0.0;
        $discount = 0.0;

        foreach ($sale->lines()->get() as $line) {
            $subtotal += (float) $line->subtotal;
            $tax      += (float) $line->tax_amount;
            $discount += (float) $line->discount_amount;
        }

        $shipping = (float) $sale->shipping_charge;
        $grand    = $subtotal - $discount + $tax + $shipping;

        $sale->subtotal       = round($subtotal, 2);
        $sale->discount_total = round($discount, 2);
        $sale->tax_total      = round($tax, 2);
        $sale->grand_total    = round($grand, 2);
        $sale->save();

        $this->recalculatePayments($sale);
    }

    /**
     * Re-derive paid_amount + balance_due + payment_status from
     * sale_payments. Safe to call any time payments change.
     */
    private function recalculatePayments(Sale $sale): void
    {
        $paid  = (float) $sale->payments()->sum('amount');
        $grand = (float) $sale->grand_total;

        $balance = round(max(0, $grand - $paid), 2);

        // 0.01 tolerance — float math.
        if ($paid <= 0.0001) {
            $status = Sale::PAY_UNPAID;
        } elseif ($paid + 0.0001 >= $grand) {
            $status = Sale::PAY_PAID;
        } else {
            $status = Sale::PAY_PARTIAL;
        }

        $sale->paid_amount    = round($paid, 2);
        $sale->balance_due    = $balance;
        $sale->payment_status = $status;
        $sale->save();
    }
}
