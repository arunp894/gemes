<?php

namespace App\Services;

use App\Models\Location;
use App\Models\StockAudit;
use App\Models\StockAuditItem;
use App\Models\StockAuditScan;
use App\Repositories\StockAuditRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * StockAudit orchestration — physical stock-take against the ledger.
 *
 *   start()    Snapshots every piece with positive on-hand at the chosen
 *              location into stock_audit_items. StockService owns the
 *              "what's on hand here" query — see
 *              StockService::onHandPiecesForLocation().
 *   scan()     Resolves one scanned value against the frozen snapshot.
 *              Primary key is lot_code (what the printed shelf label
 *              actually encodes — see purchases.labels); the
 *              receiving-dock `barcode` is a fallback since it can
 *              repeat across a box's rows.
 *   complete() / cancel()   Close the audit out. Neither writes to the
 *              stock ledger by itself — see writeOffMissing() for the
 *              explicit, separate action that does.
 *
 * All mutating methods run inside DB transactions and take row locks
 * on the snapshot row being touched, so two staff scanning the same
 * audit from different devices can't double-book a single piece.
 */
class StockAuditService
{
    public function __construct(
        private StockAuditRepository $repo,
        private StockService         $stock,
    ) {}

    /* ─── Start ────────────────────────────────────────────── */

    /**
     * Expected payload: ['location_id' => int, 'audit_date' => ?string, 'note' => ?string]
     */
    public function start(array $data): StockAudit
    {
        $locationId = (int) ($data['location_id'] ?? 0);
        if ($locationId <= 0) {
            throw new InvalidArgumentException('A location is required to start a stock audit.');
        }
        if (! Location::whereKey($locationId)->exists()) {
            throw new InvalidArgumentException('Selected location was not found.');
        }
        if (StockAudit::where('location_id', $locationId)->inProgress()->exists()) {
            throw new InvalidArgumentException(
                'An audit is already in progress for this location. Complete or cancel it before starting another.'
            );
        }

        return DB::transaction(function () use ($locationId, $data) {
            $date = Carbon::parse($data['audit_date'] ?? now()->toDateString());

            $audit = new StockAudit([
                'audit_number' => StockAudit::generateAuditNumber($date),
                'audit_date'   => $date->toDateString(),
                'location_id'  => $locationId,
                'status'       => StockAudit::STATUS_IN_PROGRESS,
                'started_at'   => now(),
                'note'         => $data['note'] ?? null,
            ]);
            $audit->save();

            // Frozen snapshot — everything the ledger says is on hand at
            // this location right now. Scanning below matches against
            // this list only, never a live re-query, so a sale rung up
            // on the floor mid-count can't move the goalposts.
            $pieces = $this->stock->onHandPiecesForLocation($locationId);

            $now  = now();
            $rows = $pieces->map(fn ($p) => [
                'stock_audit_id'      => $audit->id,
                'purchase_product_id' => $p->purchase_product_id,
                'product_id'          => $p->product_id,
                'lot_code'            => $p->lot_code,
                'barcode'             => $p->barcode,
                'created_at'          => $now,
                'updated_at'          => $now,
            ])->all();

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('stock_audit_items')->insert($chunk);
            }

            $audit->expected_total = $pieces->count();
            $audit->save();

            return $this->repo->refresh($audit);
        });
    }

    /* ─── Scan ─────────────────────────────────────────────── */

    public function scan(StockAudit $audit, string $rawValue, ?int $userId = null): StockAuditScan
    {
        if (! $audit->isInProgress()) {
            throw new InvalidArgumentException('This audit is not in progress.');
        }

        $value = trim($rawValue);
        if ($value === '') {
            throw new InvalidArgumentException('Enter or scan a code.');
        }

        return DB::transaction(function () use ($audit, $value, $userId) {
            // Primary: exact lot_code match. Unique per physical piece,
            // and what the printed shelf label actually encodes as a
            // barcode. `matched_at IS NOT NULL` sorts unmatched rows
            // first so a rare lot_code collision (see
            // PurchaseProduct::generateLotCode()) still prefers an
            // unmatched sibling over a duplicate report.
            $item = StockAuditItem::where('stock_audit_id', $audit->id)
                ->where('lot_code', $value)
                ->orderByRaw('matched_at IS NOT NULL')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            // Fallback: the receiving-dock barcode, which can repeat
            // across several rows from the same box — same FIFO
            // convention as StockService::availablePiecesForBarcode().
            if (! $item) {
                $item = StockAuditItem::where('stock_audit_id', $audit->id)
                    ->where('barcode', $value)
                    ->orderByRaw('matched_at IS NOT NULL')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first();
            }

            if (! $item) {
                return $this->logScan($audit, $value, null, StockAuditScan::RESULT_UNEXPECTED, $userId);
            }

            if ($item->matched_at !== null) {
                return $this->logScan($audit, $value, $item->id, StockAuditScan::RESULT_DUPLICATE, $userId);
            }

            $item->matched_at = now();
            $item->matched_by = $userId;
            $item->save();

            $audit->increment('matched_total');

            return $this->logScan($audit, $value, $item->id, StockAuditScan::RESULT_MATCHED, $userId);
        });
    }

    private function logScan(StockAudit $audit, string $value, ?int $itemId, string $result, ?int $userId): StockAuditScan
    {
        return StockAuditScan::create([
            'stock_audit_id'      => $audit->id,
            'stock_audit_item_id' => $itemId,
            'scanned_value'       => $value,
            'result'              => $result,
            'scanned_by'          => $userId,
            'scanned_at'          => now(),
        ]);
    }

    /**
     * Undo the current user's own most recent scan on this audit.
     * Scoped per-user so two staff scanning the same audit on different
     * devices can never undo each other's work.
     */
    public function undoLastScan(StockAudit $audit, int $userId): ?StockAuditScan
    {
        if (! $audit->isInProgress()) {
            throw new InvalidArgumentException('This audit is not in progress.');
        }

        return DB::transaction(function () use ($audit, $userId) {
            $scan = StockAuditScan::where('stock_audit_id', $audit->id)
                ->where('scanned_by', $userId)
                ->orderByDesc('scanned_at')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (! $scan) {
                return null;
            }

            if ($scan->result === StockAuditScan::RESULT_MATCHED && $scan->stock_audit_item_id) {
                $item = StockAuditItem::whereKey($scan->stock_audit_item_id)->lockForUpdate()->first();
                if ($item && $item->matched_at !== null) {
                    $item->matched_at = null;
                    $item->matched_by = null;
                    $item->save();
                    $audit->decrement('matched_total');
                }
            }

            $scan->delete();

            return $scan;
        });
    }

    /* ─── Close out ────────────────────────────────────────── */

    public function complete(StockAudit $audit): StockAudit
    {
        if (! $audit->isInProgress()) {
            throw new InvalidArgumentException('Only an in-progress audit can be completed.');
        }

        return DB::transaction(function () use ($audit) {
            $audit->status       = StockAudit::STATUS_COMPLETED;
            $audit->completed_at = now();
            $audit->save();

            return $this->repo->refresh($audit);
        });
    }

    public function cancel(StockAudit $audit): StockAudit
    {
        if (! $audit->isInProgress()) {
            throw new InvalidArgumentException('Only an in-progress audit can be cancelled.');
        }

        return DB::transaction(function () use ($audit) {
            $audit->status       = StockAudit::STATUS_CANCELLED;
            $audit->cancelled_at = now();
            $audit->save();

            return $this->repo->refresh($audit);
        });
    }

    /* ─── Reporting ────────────────────────────────────────── */

    /**
     * Every unmatched snapshot row, joined through to purchase + supplier
     * for the "missing stock" report. Callers add ->get() (exports) or
     * hand the Builder to DataTables (the on-screen table) as needed.
     */
    public function missingItemsQuery(StockAudit $audit): Builder
    {
        return StockAuditItem::query()
            ->where('stock_audit_id', $audit->id)
            ->whereNull('matched_at')
            ->with([
                'product:id,title,sku,category_id',
                'product.category:id,name',
                'purchaseProduct:id,purchase_line_id,price,carat_weight',
                'purchaseProduct.line:id,purchase_id,category_id',
                'purchaseProduct.line.purchase:id,invoice_number,purchase_date,supplier_id',
                'purchaseProduct.line.purchase.supplier:id,name,company_name',
            ])
            ->orderBy('id');
    }

    public function scanResultCounts(StockAudit $audit): array
    {
        $rows = StockAuditScan::where('stock_audit_id', $audit->id)
            ->selectRaw('result, COUNT(*) as c')
            ->groupBy('result')
            ->pluck('c', 'result');

        return [
            'matched'    => (int) ($rows[StockAuditScan::RESULT_MATCHED]    ?? 0),
            'duplicate'  => (int) ($rows[StockAuditScan::RESULT_DUPLICATE]  ?? 0),
            'unexpected' => (int) ($rows[StockAuditScan::RESULT_UNEXPECTED] ?? 0),
        ];
    }

    /* ─── Write-off (optional reconciliation action) ──────── */

    /**
     * Book a stock_movements OUT adjustment (via StockService::adjust(),
     * the same entry point stock-take corrections already use) for every
     * still-missing, not-yet-written-off row. Only valid once the audit
     * is completed, so a still-in-progress count — partial by definition
     * — can never be written off by mistake. Returns how many rows were
     * actually adjusted.
     */
    public function writeOffMissing(StockAudit $audit, int $userId): int
    {
        if (! $audit->isCompleted()) {
            throw new InvalidArgumentException('Only a completed audit can be written off.');
        }

        $items = StockAuditItem::where('stock_audit_id', $audit->id)
            ->whereNull('matched_at')
            ->whereNull('written_off_at')
            ->get();

        if ($items->isEmpty()) {
            return 0;
        }

        $count = 0;

        DB::transaction(function () use ($items, $audit, $userId, &$count) {
            foreach ($items as $item) {
                $onHand = $this->stock->onHandForPiece((int) $item->purchase_product_id, (int) $audit->location_id);

                if ($onHand > 0) {
                    $this->stock->adjust(
                        (int) $item->purchase_product_id,
                        (int) $item->product_id,
                        (int) $audit->location_id,
                        -$onHand,
                        "Stock audit {$audit->audit_number}: not found during physical count."
                    );
                    $count++;
                }
                // onHand <= 0 means something else (a sale, a transfer)
                // already zeroed this piece out since the audit
                // completed — nothing left to write off, but still mark
                // it so a second pass doesn't re-check it.

                $item->written_off_at = now();
                $item->written_off_by = $userId;
                $item->save();
            }
        });

        return $count;
    }
}
