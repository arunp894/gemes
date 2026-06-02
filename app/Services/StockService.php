<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Purchase;
use App\Models\PurchaseProduct;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferLine;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * StockService — central authority over the stock_movements ledger.
 *
 * Every IN, OUT, transfer, and adjustment goes through this class so:
 *   1. The ledger semantics (append-only, signed math) stay consistent.
 *   2. Posting/cancelling a Purchase or Sale produces exactly the right
 *      movements without duplicating logic across the codebase.
 *   3. Balance queries have one canonical implementation.
 *
 * All write methods are idempotent against repeated calls when paired
 * with the `hasMovementsFromSource()` guard — callers should check
 * before recording so a re-post doesn't double-book stock.
 */
class StockService
{
    /* ─────────────────────────────────────────────────────────
     |  Read API — balance queries
     | ─────────────────────────────────────────────────────────
     */

    /**
     * Current on-hand quantity for a specific piece at a specific
     * location. Per-piece granularity — this is THE canonical question
     * the system asks before allowing a sale.
     */
    public function onHandForPiece(int $purchaseProductId, int $locationId): int
    {
        $in  = (int) StockMovement::query()
            ->where('purchase_product_id', $purchaseProductId)
            ->where('location_id', $locationId)
            ->where('direction', StockMovement::DIRECTION_IN)
            ->sum('qty');

        $out = (int) StockMovement::query()
            ->where('purchase_product_id', $purchaseProductId)
            ->where('location_id', $locationId)
            ->where('direction', StockMovement::DIRECTION_OUT)
            ->sum('qty');

        return $in - $out;
    }

    /**
     * On-hand across every location for a piece. Useful for "where is
     * this piece" reports.
     *
     * Returns: [location_id => balance, ...] including only locations
     * with non-zero balances.
     */
    public function onHandForPieceByLocation(int $purchaseProductId): array
    {
        $rows = StockMovement::query()
            ->selectRaw('location_id, '
                . 'SUM(CASE WHEN direction = ? THEN qty ELSE 0 END) as in_qty, '
                . 'SUM(CASE WHEN direction = ? THEN qty ELSE 0 END) as out_qty',
                [StockMovement::DIRECTION_IN, StockMovement::DIRECTION_OUT])
            ->where('purchase_product_id', $purchaseProductId)
            ->groupBy('location_id')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $bal = (int) $r->in_qty - (int) $r->out_qty;
            if ($bal !== 0) {
                $out[(int) $r->location_id] = $bal;
            }
        }
        return $out;
    }

    /**
     * On-hand for a product at a location, summed across all pieces.
     * Used by the stock-report card and the sale-search fallback.
     */
    public function onHandForProduct(int $productId, int $locationId): int
    {
        $in  = (int) StockMovement::query()
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->where('direction', StockMovement::DIRECTION_IN)
            ->sum('qty');

        $out = (int) StockMovement::query()
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->where('direction', StockMovement::DIRECTION_OUT)
            ->sum('qty');

        return $in - $out;
    }

    /**
     * Pieces of a given product that have positive balance at the given
     * location. Used when a sale line references a product but no
     * specific piece — FIFO pick.
     *
     * Returns an array of [purchase_product_id => balance], ordered by
     * the piece's purchase date (oldest first).
     */
    public function availablePiecesForProduct(int $productId, int $locationId): array
    {
        // First, find all pieces for this product (via purchase_line)
        // that have any IN movement at this location. Then compute the
        // balance for each and filter positives.
        $pieceIds = StockMovement::query()
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->pluck('purchase_product_id')
            ->unique()
            ->values();

        if ($pieceIds->isEmpty()) {
            return [];
        }

        // Compute balance per piece in one query.
        $rows = StockMovement::query()
            ->selectRaw('purchase_product_id, '
                . 'SUM(CASE WHEN direction = ? THEN qty ELSE 0 END) as in_qty, '
                . 'SUM(CASE WHEN direction = ? THEN qty ELSE 0 END) as out_qty',
                [StockMovement::DIRECTION_IN, StockMovement::DIRECTION_OUT])
            ->whereIn('purchase_product_id', $pieceIds)
            ->where('location_id', $locationId)
            ->groupBy('purchase_product_id')
            ->get()
            ->keyBy('purchase_product_id');

        $available = [];
        foreach ($rows as $ppId => $r) {
            $bal = (int) $r->in_qty - (int) $r->out_qty;
            if ($bal > 0) {
                $available[(int) $ppId] = $bal;
            }
        }

        if (empty($available)) {
            return [];
        }

        // Sort by oldest piece first (FIFO). PurchaseProduct.created_at
        // is good enough since the inventory row is created when the
        // purchase is built.
        $order = PurchaseProduct::whereIn('id', array_keys($available))
            ->orderBy('created_at')
            ->orderBy('id')
            ->pluck('id')
            ->toArray();

        $ordered = [];
        foreach ($order as $id) {
            if (isset($available[$id])) {
                $ordered[$id] = $available[$id];
            }
        }

        return $ordered;
    }

    /**
     * Have we already recorded movements for this source document?
     * Idempotency guard — call before re-posting to avoid double-booking.
     */
    public function hasMovementsFromSource(string $sourceType, int $sourceId, ?string $reason = null): bool
    {
        $q = StockMovement::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId);

        if ($reason !== null) {
            $q->where('reason', $reason);
        }

        return $q->exists();
    }

    /* ─────────────────────────────────────────────────────────
     |  Write API — record a single movement
     | ─────────────────────────────────────────────────────────
     */

    /**
     * The one and only INSERT entry point. All higher-level methods
     * funnel through here so the schema invariants (positive qty,
     * direction-reason coherence, etc.) live in one place.
     */
    public function record(array $data): StockMovement
    {
        // Schema invariants enforced here, not in DB constraints, so
        // we get clean PHP errors with context.
        if (! isset($data['purchase_product_id'], $data['product_id'], $data['location_id'])) {
            throw new InvalidArgumentException('record(): purchase_product_id, product_id, and location_id are required.');
        }
        if (! isset($data['direction']) || ! in_array($data['direction'], StockMovement::DIRECTIONS, true)) {
            throw new InvalidArgumentException("record(): direction must be 'in' or 'out'.");
        }
        $qty = (int) ($data['qty'] ?? 0);
        if ($qty <= 0) {
            throw new InvalidArgumentException('record(): qty must be a positive integer.');
        }
        if (empty($data['reason']) || ! array_key_exists($data['reason'], StockMovement::REASONS)) {
            throw new InvalidArgumentException("record(): reason '{$data['reason']}' is not a recognised reason.");
        }

        return StockMovement::create([
            'purchase_product_id' => $data['purchase_product_id'],
            'product_id'          => $data['product_id'],
            'location_id'         => $data['location_id'],
            'direction'           => $data['direction'],
            'qty'                 => $qty,
            'reason'              => $data['reason'],
            'source_type'         => $data['source_type']    ?? null,
            'source_id'           => $data['source_id']      ?? null,
            'source_line_id'      => $data['source_line_id'] ?? null,
            'rack_id'             => $data['rack_id']        ?? null,
            'movement_date'       => $data['movement_date']  ?? now()->toDateString(),
            'notes'               => $data['notes']          ?? null,
        ]);
    }

    /* ─────────────────────────────────────────────────────────
     |  Domain integration — Purchase
     | ─────────────────────────────────────────────────────────
     */

    /**
     * Record all IN movements for a purchase being posted. One movement
     * per purchase_product row.
     *
     * Idempotent — if movements already exist for this purchase with
     * reason=purchase, the call is a no-op.
     */
    public function recordPurchasePosting(Purchase $purchase): void
    {
        if ($this->hasMovementsFromSource(StockMovement::SOURCE_PURCHASE, $purchase->id, StockMovement::REASON_PURCHASE)) {
            return;
        }

        $locationId = $purchase->location_id ?: $this->defaultLocationId();
        if (! $locationId) {
            throw new RuntimeException('Cannot post purchase: no location set and no default location available.');
        }

        DB::transaction(function () use ($purchase, $locationId) {
            $purchase->load('lines.rows');

            foreach ($purchase->lines as $line) {
                foreach ($line->rows as $row) {
                    if ((int) $row->qty <= 0) {
                        continue;
                    }
                    $this->record([
                        'purchase_product_id' => $row->id,
                        'product_id'          => $line->product_id,
                        'location_id'         => $locationId,
                        'direction'           => StockMovement::DIRECTION_IN,
                        'qty'                 => (int) $row->qty,
                        'reason'              => StockMovement::REASON_PURCHASE,
                        'source_type'         => StockMovement::SOURCE_PURCHASE,
                        'source_id'           => $purchase->id,
                        'source_line_id'      => $line->id,
                        'rack_id'             => $row->rack_id,
                        'movement_date'       => optional($purchase->purchase_date)->toDateString() ?? now()->toDateString(),
                    ]);
                }
            }
        });
    }

    /**
     * Reverse a previously-posted purchase by emitting OUT counter-
     * movements. Used when a posted purchase is cancelled.
     */
    public function reversePurchasePosting(Purchase $purchase): void
    {
        // Idempotency — already reversed?
        if ($this->hasMovementsFromSource(StockMovement::SOURCE_PURCHASE, $purchase->id, StockMovement::REASON_PURCHASE_CANCEL)) {
            return;
        }

        // Find the original IN rows by source so we know exactly what to
        // counter. Don't re-derive from purchase_products — if the rows
        // were edited between post and cancel, we'd corrupt the ledger.
        $originals = StockMovement::query()
            ->where('source_type', StockMovement::SOURCE_PURCHASE)
            ->where('source_id', $purchase->id)
            ->where('reason', StockMovement::REASON_PURCHASE)
            ->get();

        if ($originals->isEmpty()) {
            return; // Nothing to reverse.
        }

        DB::transaction(function () use ($originals, $purchase) {
            foreach ($originals as $orig) {
                // Safety: never reverse stock that's already been sold/
                // transferred out. Refuse if any piece's balance would
                // go negative at its location.
                $balance = $this->onHandForPiece((int) $orig->purchase_product_id, (int) $orig->location_id);
                if ($balance < (int) $orig->qty) {
                    throw new RuntimeException(
                        "Cannot cancel purchase {$purchase->invoice_number}: piece #{$orig->purchase_product_id} "
                        . "at location #{$orig->location_id} has on-hand {$balance}, which is less than the "
                        . "original IN of {$orig->qty}. Reverse downstream sales/transfers first."
                    );
                }
            }

            foreach ($originals as $orig) {
                $this->record([
                    'purchase_product_id' => $orig->purchase_product_id,
                    'product_id'          => $orig->product_id,
                    'location_id'         => $orig->location_id,
                    'direction'           => StockMovement::DIRECTION_OUT,
                    'qty'                 => $orig->qty,
                    'reason'              => StockMovement::REASON_PURCHASE_CANCEL,
                    'source_type'         => StockMovement::SOURCE_PURCHASE,
                    'source_id'           => $purchase->id,
                    'source_line_id'      => $orig->source_line_id,
                    'rack_id'             => $orig->rack_id,
                    'notes'               => 'Reversal of original IN movement #' . $orig->id,
                ]);
            }
        });
    }

    /* ─────────────────────────────────────────────────────────
     |  Domain integration — Sale
     | ─────────────────────────────────────────────────────────
     */

    /**
     * Hard availability check used before posting a sale. Returns a
     * list of error messages — empty array means "OK to post".
     *
     * Each sale line is checked individually: the specified piece must
     * have ≥ qty on hand at the sale's location. If no specific piece
     * is set, the sum across all available pieces of that product at
     * that location must be ≥ qty.
     */
    public function checkSaleAvailability(Sale $sale): array
    {
        $errors = [];
        $locationId = (int) $sale->location_id;
        if (! $locationId) {
            return ['Sale has no location — cannot validate stock.'];
        }

        // Roll the lines up so multiple lines hitting the same piece are
        // aggregated for the check. Otherwise two lines of qty=2 each on
        // a piece with on-hand=3 would both pass individually.
        $byPiece   = [];
        $byProduct = [];

        $sale->load('lines');

        foreach ($sale->lines as $line) {
            $qty = (int) $line->qty;
            if ($qty <= 0) {
                continue;
            }

            if ($line->purchase_product_id) {
                $key = (int) $line->purchase_product_id;
                $byPiece[$key] = ($byPiece[$key] ?? 0) + $qty;
            } else {
                $key = (int) $line->product_id;
                $byProduct[$key] = ($byProduct[$key] ?? 0) + $qty;
            }
        }

        foreach ($byPiece as $ppId => $needed) {
            $onHand = $this->onHandForPiece($ppId, $locationId);
            if ($onHand < $needed) {
                $errors[] = "Insufficient stock for piece #{$ppId} at location: need {$needed}, on hand {$onHand}.";
            }
        }

        foreach ($byProduct as $productId => $needed) {
            $onHand = $this->onHandForProduct($productId, $locationId);
            if ($onHand < $needed) {
                $errors[] = "Insufficient stock for product #{$productId} at location: need {$needed}, on hand {$onHand}.";
            }
        }

        return $errors;
    }

    /**
     * Record OUT movements for a sale being posted. For lines with an
     * explicit purchase_product_id we consume that exact piece; for
     * lines without one we FIFO-allocate across available pieces.
     *
     * Throws RuntimeException if availability fails. The caller should
     * call checkSaleAvailability() first for a clean error UX, but the
     * service still guards here so direct callers can't bypass it.
     */
    public function recordSalePosting(Sale $sale): void
    {
        if ($this->hasMovementsFromSource(StockMovement::SOURCE_SALE, $sale->id, StockMovement::REASON_SALE)) {
            return;
        }

        $errors = $this->checkSaleAvailability($sale);
        if (! empty($errors)) {
            throw new RuntimeException('Cannot post sale: ' . implode(' ', $errors));
        }

        $locationId = (int) $sale->location_id;

        DB::transaction(function () use ($sale, $locationId) {
            $sale->load('lines');

            foreach ($sale->lines as $line) {
                $qty = (int) $line->qty;
                if ($qty <= 0) {
                    continue;
                }

                if ($line->purchase_product_id) {
                    // Exact piece specified — consume directly.
                    $this->record([
                        'purchase_product_id' => (int) $line->purchase_product_id,
                        'product_id'          => (int) $line->product_id,
                        'location_id'         => $locationId,
                        'direction'           => StockMovement::DIRECTION_OUT,
                        'qty'                 => $qty,
                        'reason'              => StockMovement::REASON_SALE,
                        'source_type'         => StockMovement::SOURCE_SALE,
                        'source_id'           => $sale->id,
                        'source_line_id'      => $line->id,
                        'movement_date'       => optional($sale->sale_date)->toDateString() ?? now()->toDateString(),
                    ]);
                    continue;
                }

                // No specific piece — FIFO-allocate across available
                // pieces of this product at the sale's location. Snapshot
                // the chosen piece(s) back onto the SaleLine so future
                // refunds reverse the correct rows.
                $available = $this->availablePiecesForProduct((int) $line->product_id, $locationId);
                $remaining = $qty;
                $firstPpId = null;

                foreach ($available as $ppId => $bal) {
                    if ($remaining <= 0) break;
                    $take = min($bal, $remaining);
                    if ($take <= 0) continue;

                    $this->record([
                        'purchase_product_id' => $ppId,
                        'product_id'          => (int) $line->product_id,
                        'location_id'         => $locationId,
                        'direction'           => StockMovement::DIRECTION_OUT,
                        'qty'                 => $take,
                        'reason'              => StockMovement::REASON_SALE,
                        'source_type'         => StockMovement::SOURCE_SALE,
                        'source_id'           => $sale->id,
                        'source_line_id'      => $line->id,
                        'movement_date'       => optional($sale->sale_date)->toDateString() ?? now()->toDateString(),
                        'notes'               => 'FIFO-allocated from piece #' . $ppId,
                    ]);

                    if ($firstPpId === null) {
                        $firstPpId = $ppId;
                    }
                    $remaining -= $take;
                }

                if ($remaining > 0) {
                    // Should be unreachable given the availability check
                    // above; bail loudly if math drifts.
                    throw new RuntimeException(
                        "FIFO allocation underflow on sale line #{$line->id}: {$remaining} units unfilled."
                    );
                }

                // Tag the line with the first piece consumed so the show
                // page has something useful to display and the reversal
                // can find a starting point. Cost snapshot from the piece.
                if ($firstPpId !== null && ! $line->purchase_product_id) {
                    $firstPiece = PurchaseProduct::find($firstPpId);
                    SaleLine::where('id', $line->id)->update([
                        'purchase_product_id' => $firstPpId,
                        'cost_price'          => $firstPiece ? $firstPiece->price : $line->cost_price,
                    ]);
                }
            }
        });
    }

    /**
     * Reverse a posted sale's OUT movements by inserting matching INs.
     * Reason indicates whether the trigger was a refund or a cancellation.
     */
    public function reverseSalePosting(Sale $sale, string $reason): void
    {
        if (! in_array($reason, [StockMovement::REASON_SALE_RETURN, StockMovement::REASON_SALE_CANCEL], true)) {
            throw new InvalidArgumentException("reverseSalePosting(): unsupported reason '{$reason}'.");
        }

        // Idempotency — if a counter-row already exists with the same
        // reason, skip.
        if ($this->hasMovementsFromSource(StockMovement::SOURCE_SALE, $sale->id, $reason)) {
            return;
        }

        $originals = StockMovement::query()
            ->where('source_type', StockMovement::SOURCE_SALE)
            ->where('source_id', $sale->id)
            ->where('reason', StockMovement::REASON_SALE)
            ->get();

        if ($originals->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($originals, $reason, $sale) {
            foreach ($originals as $orig) {
                $this->record([
                    'purchase_product_id' => $orig->purchase_product_id,
                    'product_id'          => $orig->product_id,
                    'location_id'         => $orig->location_id,
                    'direction'           => StockMovement::DIRECTION_IN,
                    'qty'                 => $orig->qty,
                    'reason'              => $reason,
                    'source_type'         => StockMovement::SOURCE_SALE,
                    'source_id'           => $sale->id,
                    'source_line_id'      => $orig->source_line_id,
                    'notes'               => 'Reversal of original OUT movement #' . $orig->id,
                ]);
            }
        });
    }

    /* ─────────────────────────────────────────────────────────
     |  Domain integration — Stock Transfer
     | ─────────────────────────────────────────────────────────
     */

    /**
     * Post a stock transfer: emit OUT movements at from_location.
     * Pieces are now "in transit" — gone from source, not yet at dest.
     */
    public function recordTransferPosting(StockTransfer $transfer): void
    {
        if ($this->hasMovementsFromSource(StockMovement::SOURCE_STOCK_TRANSFER, $transfer->id, StockMovement::REASON_TRANSFER_OUT)) {
            return;
        }

        $transfer->load('lines');

        // Pre-flight availability check at source — same per-piece rules.
        $errors = [];
        $byPiece = [];
        foreach ($transfer->lines as $line) {
            if ((int) $line->qty <= 0) continue;
            $key = (int) $line->purchase_product_id;
            $byPiece[$key] = ($byPiece[$key] ?? 0) + (int) $line->qty;
        }
        foreach ($byPiece as $ppId => $needed) {
            $onHand = $this->onHandForPiece($ppId, (int) $transfer->from_location_id);
            if ($onHand < $needed) {
                $errors[] = "Insufficient stock for piece #{$ppId} at source: need {$needed}, on hand {$onHand}.";
            }
        }
        if (! empty($errors)) {
            throw new RuntimeException('Cannot post transfer: ' . implode(' ', $errors));
        }

        DB::transaction(function () use ($transfer) {
            foreach ($transfer->lines as $line) {
                if ((int) $line->qty <= 0) continue;

                $this->record([
                    'purchase_product_id' => (int) $line->purchase_product_id,
                    'product_id'          => (int) $line->product_id,
                    'location_id'         => (int) $transfer->from_location_id,
                    'direction'           => StockMovement::DIRECTION_OUT,
                    'qty'                 => (int) $line->qty,
                    'reason'              => StockMovement::REASON_TRANSFER_OUT,
                    'source_type'         => StockMovement::SOURCE_STOCK_TRANSFER,
                    'source_id'           => $transfer->id,
                    'source_line_id'      => $line->id,
                    'movement_date'       => optional($transfer->transfer_date)->toDateString() ?? now()->toDateString(),
                ]);
            }
        });
    }

    /**
     * Receive a transfer: emit IN movements at to_location.
     */
    public function recordTransferReceipt(StockTransfer $transfer): void
    {
        if ($this->hasMovementsFromSource(StockMovement::SOURCE_STOCK_TRANSFER, $transfer->id, StockMovement::REASON_TRANSFER_IN)) {
            return;
        }

        $transfer->load('lines');

        DB::transaction(function () use ($transfer) {
            foreach ($transfer->lines as $line) {
                if ((int) $line->qty <= 0) continue;

                $this->record([
                    'purchase_product_id' => (int) $line->purchase_product_id,
                    'product_id'          => (int) $line->product_id,
                    'location_id'         => (int) $transfer->to_location_id,
                    'direction'           => StockMovement::DIRECTION_IN,
                    'qty'                 => (int) $line->qty,
                    'reason'              => StockMovement::REASON_TRANSFER_IN,
                    'source_type'         => StockMovement::SOURCE_STOCK_TRANSFER,
                    'source_id'           => $transfer->id,
                    'source_line_id'      => $line->id,
                    'rack_id'             => $line->to_rack_id,
                    'movement_date'       => now()->toDateString(),
                ]);
            }
        });
    }

    /**
     * Cancel an in-transit transfer: return pieces to from_location via
     * compensating IN movements.
     */
    public function reverseTransferPosting(StockTransfer $transfer): void
    {
        if ($this->hasMovementsFromSource(StockMovement::SOURCE_STOCK_TRANSFER, $transfer->id, StockMovement::REASON_TRANSFER_CANCEL_OUT)) {
            return;
        }

        $originals = StockMovement::query()
            ->where('source_type', StockMovement::SOURCE_STOCK_TRANSFER)
            ->where('source_id', $transfer->id)
            ->where('reason', StockMovement::REASON_TRANSFER_OUT)
            ->get();

        if ($originals->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($originals, $transfer) {
            foreach ($originals as $orig) {
                $this->record([
                    'purchase_product_id' => $orig->purchase_product_id,
                    'product_id'          => $orig->product_id,
                    'location_id'         => $orig->location_id,
                    'direction'           => StockMovement::DIRECTION_IN,
                    'qty'                 => $orig->qty,
                    'reason'              => StockMovement::REASON_TRANSFER_CANCEL_OUT,
                    'source_type'         => StockMovement::SOURCE_STOCK_TRANSFER,
                    'source_id'           => $transfer->id,
                    'source_line_id'      => $orig->source_line_id,
                    'notes'               => 'Cancellation of in-transit transfer; restoring to source.',
                ]);
            }
        });
    }

    /* ─────────────────────────────────────────────────────────
     |  Manual adjustments
     | ─────────────────────────────────────────────────────────
     */

    /**
     * Manual stock adjustment. delta > 0 → IN; delta < 0 → OUT.
     * Used for stock-take corrections, breakage, etc.
     */
    public function adjust(
        int $purchaseProductId,
        int $productId,
        int $locationId,
        int $delta,
        string $notes = ''
    ): StockMovement {
        if ($delta === 0) {
            throw new InvalidArgumentException('adjust(): delta must be non-zero.');
        }

        $direction = $delta > 0 ? StockMovement::DIRECTION_IN : StockMovement::DIRECTION_OUT;
        $reason    = $delta > 0 ? StockMovement::REASON_ADJUSTMENT_IN : StockMovement::REASON_ADJUSTMENT_OUT;

        if ($delta < 0) {
            $onHand = $this->onHandForPiece($purchaseProductId, $locationId);
            if ($onHand < abs($delta)) {
                throw new RuntimeException(
                    "Cannot adjust down by " . abs($delta) . ": piece #{$purchaseProductId} only has on-hand {$onHand}."
                );
            }
        }

        return $this->record([
            'purchase_product_id' => $purchaseProductId,
            'product_id'          => $productId,
            'location_id'         => $locationId,
            'direction'           => $direction,
            'qty'                 => abs($delta),
            'reason'              => $reason,
            'source_type'         => StockMovement::SOURCE_STOCK_ADJUSTMENT,
            'source_id'           => null,
            'notes'               => $notes ?: null,
        ]);
    }

    /* ─────────────────────────────────────────────────────────
     |  Helpers
     | ─────────────────────────────────────────────────────────
     */

    public function defaultLocationId(): ?int
    {
        $id = Location::where('is_default', true)->value('id');
        if ($id) return (int) $id;

        $id = Location::orderBy('id')->value('id');
        return $id ? (int) $id : null;
    }
}
