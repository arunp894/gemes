<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseProduct;
use App\Models\StockTransfer;
use App\Models\StockTransferLine;
use App\Repositories\StockTransferRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * StockTransfer orchestration.
 *
 * Lifecycle:
 *   draft → in_transit  (post:    StockService::recordTransferPosting)
 *   in_transit → received (receive: StockService::recordTransferReceipt)
 *   draft / in_transit → cancelled (compensating moves where needed)
 *   received → (terminal — must do a reverse transfer to undo)
 *
 * All status transitions are wrapped in DB transactions so the document
 * status and the ledger writes never get out of sync.
 */
class StockTransferService
{
    public function __construct(
        private StockTransferRepository $repo,
        private StockService            $stock,
    ) {}

    /* ─── CRUD ─────────────────────────────────────────────── */

    /**
     * Expected payload:
     * [
     *   'transfer_date'    => 'YYYY-MM-DD',
     *   'from_location_id' => int,
     *   'to_location_id'   => int,
     *   'status'           => 'draft' | 'in_transit'  (start state)
     *   'note'             => string|null,
     *   'lines' => [
     *     ['purchase_product_id' => int, 'qty' => int, 'to_rack_id' => int|null, 'notes' => string|null],
     *     ...
     *   ],
     * ]
     */
    public function create(array $data): StockTransfer
    {
        $this->assertDistinctLocations($data['from_location_id'] ?? null, $data['to_location_id'] ?? null);

        return DB::transaction(function () use ($data) {
            $date = Carbon::parse($data['transfer_date'] ?? now()->toDateString());

            $transfer = new StockTransfer();
            $transfer->transfer_number  = StockTransfer::generateTransferNumber($date);
            $transfer->transfer_date    = $date->toDateString();
            $transfer->from_location_id = $data['from_location_id'];
            $transfer->to_location_id   = $data['to_location_id'];
            $transfer->note             = $data['note'] ?? null;
            // Start as draft regardless of intended state; transitions go
            // through the status methods so ledger writes always run.
            $transfer->status           = StockTransfer::STATUS_DRAFT;
            $transfer->save();

            $this->syncLines($transfer, $data['lines'] ?? []);

            $intended = $data['status'] ?? StockTransfer::STATUS_DRAFT;
            if ($intended === StockTransfer::STATUS_IN_TRANSIT) {
                $this->post($transfer);
            }

            return $this->repo->refresh($transfer);
        });
    }

    public function update(StockTransfer $transfer, array $data): StockTransfer
    {
        if (! $transfer->isEditable()) {
            throw new InvalidArgumentException('Only draft transfers can be edited.');
        }

        $this->assertDistinctLocations(
            $data['from_location_id'] ?? $transfer->from_location_id,
            $data['to_location_id']   ?? $transfer->to_location_id,
        );

        return DB::transaction(function () use ($transfer, $data) {
            $transfer->transfer_date    = $data['transfer_date']    ?? $transfer->transfer_date;
            $transfer->from_location_id = $data['from_location_id'] ?? $transfer->from_location_id;
            $transfer->to_location_id   = $data['to_location_id']   ?? $transfer->to_location_id;
            $transfer->note             = $data['note']             ?? null;

            // Hard reset of children — simplest correct strategy.
            $transfer->lines()->each(fn (StockTransferLine $l) => $l->forceDelete());
            $transfer->save();

            $this->syncLines($transfer, $data['lines'] ?? []);

            return $this->repo->refresh($transfer);
        });
    }

    public function delete(StockTransfer $transfer): void
    {
        if ($transfer->isInTransit() || $transfer->isReceived()) {
            throw new InvalidArgumentException(
                'Cannot delete a transfer that has moved stock. Cancel it first.'
            );
        }

        DB::transaction(function () use ($transfer) {
            $transfer->lines()->each(fn (StockTransferLine $l) => $l->delete());
            $transfer->delete();
        });
    }

    /* ─── Status transitions ──────────────────────────────── */

    /**
     * Post a draft transfer: OUT movements at from_location. Pieces are
     * now "in transit" — gone from source, not yet at destination.
     */
    public function post(StockTransfer $transfer): StockTransfer
    {
        if ($transfer->isInTransit() || $transfer->isReceived()) {
            return $transfer;
        }
        if ($transfer->isCancelled()) {
            throw new InvalidArgumentException('Cannot post a cancelled transfer.');
        }
        if ($transfer->lines()->count() === 0) {
            throw new InvalidArgumentException('Cannot post an empty transfer.');
        }

        return DB::transaction(function () use ($transfer) {
            // Movement write FIRST — its availability check is the bouncer
            // at the door. If it throws, we never flip status.
            $this->stock->recordTransferPosting($transfer);

            $transfer->status    = StockTransfer::STATUS_IN_TRANSIT;
            $transfer->posted_at = now();
            $transfer->save();

            return $this->repo->refresh($transfer);
        });
    }

    /**
     * Receive an in-transit transfer: IN movements at to_location.
     */
    public function receive(StockTransfer $transfer): StockTransfer
    {
        if ($transfer->isReceived()) {
            return $transfer;
        }
        if (! $transfer->isInTransit()) {
            throw new InvalidArgumentException('Only in-transit transfers can be received.');
        }

        return DB::transaction(function () use ($transfer) {
            $this->stock->recordTransferReceipt($transfer);
            $transfer->status      = StockTransfer::STATUS_RECEIVED;
            $transfer->received_at = now();
            $transfer->save();
            return $this->repo->refresh($transfer);
        });
    }

    /**
     * Cancel a transfer. Allowed from draft or in_transit.
     * - draft     → no stock impact.
     * - in_transit → compensating IN at from_location.
     * - received  → blocked (caller must initiate a reverse transfer).
     */
    public function cancel(StockTransfer $transfer): StockTransfer
    {
        if ($transfer->isReceived()) {
            throw new InvalidArgumentException(
                'A received transfer cannot be cancelled. Create a reverse transfer instead.'
            );
        }
        if ($transfer->isCancelled()) {
            return $transfer;
        }

        return DB::transaction(function () use ($transfer) {
            if ($transfer->isInTransit()) {
                // Restore source stock.
                $this->stock->reverseTransferPosting($transfer);
            }
            $transfer->status       = StockTransfer::STATUS_CANCELLED;
            $transfer->cancelled_at = now();
            $transfer->save();
            return $this->repo->refresh($transfer);
        });
    }

    /* ─── Helpers ─────────────────────────────────────────── */

    private function syncLines(StockTransfer $transfer, array $lines): void
    {
        foreach ($lines as $row) {
            $ppId = (int) ($row['purchase_product_id'] ?? 0);
            if ($ppId <= 0) {
                continue;
            }

            $pp = PurchaseProduct::find($ppId);
            if (! $pp) {
                throw new InvalidArgumentException("Piece #{$ppId} not found.");
            }
            $productId = (int) optional($pp->line)->product_id;
            if (! $productId) {
                throw new InvalidArgumentException("Piece #{$ppId} is missing a product reference.");
            }

            $transfer->lines()->save(new StockTransferLine([
                'purchase_product_id' => $ppId,
                'product_id'          => $productId,
                'qty'                 => max(1, (int) ($row['qty'] ?? 1)),
                'to_rack_id'          => $row['to_rack_id'] ?? null,
                'notes'               => $row['notes']      ?? null,
            ]));
        }
    }

    private function assertDistinctLocations($from, $to): void
    {
        if (! $from || ! $to) {
            throw new InvalidArgumentException('Both source and destination locations are required.');
        }
        if ((int) $from === (int) $to) {
            throw new InvalidArgumentException('Source and destination must be different locations.');
        }
    }
}
