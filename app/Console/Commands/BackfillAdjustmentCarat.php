<?php

namespace App\Console\Commands;

use App\Models\CaratMovement;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-time fix: StockService::adjust() used to book pieces without any carat,
 * so old stock adjustments (audit write-offs) left their carat on the ledger.
 * This books the missing carat OUT, dated like the original adjustment.
 *
 * Only adjustment-outs that left 0 pieces at that location are fixed — the
 * carat removed is everything the location held at that moment (same as
 * adjust() does when it empties a piece). Anything else is listed for review.
 *
 * Safe to re-run: an adjustment that already has a carat row is skipped.
 */
class BackfillAdjustmentCarat extends Command
{
    protected $signature = 'carat:backfill-adjustments {--apply : Actually write the carat rows (default is a dry-run preview)}';

    protected $description = 'Book the missing carat OUT for old stock adjustments that moved pieces but no carat.';

    public function handle(StockService $stock): int
    {
        $apply = (bool) $this->option('apply');

        $adjustments = DB::table('stock_movements')
            ->whereNull('deleted_at')
            ->where('source_type', StockMovement::SOURCE_STOCK_ADJUSTMENT)
            ->whereIn('reason', [StockMovement::REASON_ADJUSTMENT_IN, StockMovement::REASON_ADJUSTMENT_OUT])
            ->orderBy('id')
            ->get();

        $this->info(($apply ? '' : '[DRY RUN] ') . "Scanning {$adjustments->count()} stock adjustments...");

        $booked = $hasCarat = $nothingToBook = 0;
        $totalCarat = 0.0;
        $review = [];

        foreach ($adjustments as $m) {
            $reason = $m->reason === StockMovement::REASON_ADJUSTMENT_IN
                ? CaratMovement::REASON_ADJUSTMENT_IN
                : CaratMovement::REASON_ADJUSTMENT_OUT;

            $already = DB::table('carat_movements')
                ->whereNull('deleted_at')
                ->where('purchase_product_id', $m->purchase_product_id)
                ->where('location_id', $m->location_id)
                ->where('source_type', CaratMovement::SOURCE_STOCK_ADJUSTMENT)
                ->where('reason', $reason)
                ->exists();
            if ($already) {
                $hasCarat++;
                continue;
            }

            if ($m->reason === StockMovement::REASON_ADJUSTMENT_IN) {
                $review[] = [$m->id, $m->purchase_product_id, $m->location_id, 'adjustment_in — carat can\'t be reconstructed safely'];
                continue;
            }

            // Pieces left at this location right after the adjustment.
            $qtyAfter = (int) DB::table('stock_movements')
                ->whereNull('deleted_at')
                ->where('purchase_product_id', $m->purchase_product_id)
                ->where('location_id', $m->location_id)
                ->where(fn ($q) => $q->where('created_at', '<', $m->created_at)
                    ->orWhere(fn ($q2) => $q2->where('created_at', $m->created_at)->where('id', '<=', $m->id)))
                ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN qty ELSE -qty END), 0) as bal")
                ->value('bal');
            if ($qtyAfter !== 0) {
                $review[] = [$m->id, $m->purchase_product_id, $m->location_id, "left {$qtyAfter} pieces at the location (not a full write-off)"];
                continue;
            }

            // Carat the location held at that moment.
            $remaining = round((float) DB::table('carat_movements')
                ->whereNull('deleted_at')
                ->where('purchase_product_id', $m->purchase_product_id)
                ->where('location_id', $m->location_id)
                ->where('created_at', '<=', $m->created_at)
                ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN carat ELSE -carat END), 0) as bal")
                ->value('bal'), 3);
            if ($remaining <= 0.0005) {
                $nothingToBook++;
                continue;
            }

            if ($apply) {
                $stock->recordCarat([
                    'purchase_product_id' => $m->purchase_product_id,
                    'product_id'          => $m->product_id,
                    'location_id'         => $m->location_id,
                    'direction'           => CaratMovement::DIRECTION_OUT,
                    'carat'               => $remaining,
                    'reason'              => CaratMovement::REASON_ADJUSTMENT_OUT,
                    'source_type'         => CaratMovement::SOURCE_STOCK_ADJUSTMENT,
                    'movement_date'       => $m->movement_date,
                    'notes'               => 'Carat backfill for adjustment #' . $m->id,
                ]);
            }

            $booked++;
            $totalCarat += $remaining;
        }

        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['Adjustments scanned', $adjustments->count()],
            ['Carat OUT ' . ($apply ? 'booked' : '(would book)'), $booked],
            ['Total carat ' . ($apply ? 'booked' : '(would book)'), round($totalCarat, 3)],
            ['Skipped — already has a carat row', $hasCarat],
            ['Skipped — no carat on the ledger to remove', $nothingToBook],
            ['Needs manual review', count($review)],
        ]);

        if ($review) {
            $this->table(['Movement #', 'Piece #', 'Location #', 'Why'], $review);
        }

        if (! $apply) {
            $this->comment('Dry run only — nothing was written. Re-run with --apply to book the carat.');
        }

        return self::SUCCESS;
    }
}
