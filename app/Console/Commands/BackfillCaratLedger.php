<?php

namespace App\Console\Commands;

use App\Models\CaratMovement;
use App\Models\PurchaseProduct;
use App\Services\StockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-time backfill: seeds opening carat_movements rows for
 * purchase_products that existed before the CT ledger did. Every row
 * created from now on gets its CT posted for real, at the exact entered
 * amount, by StockService — this command only fills the gap for history
 * the ledger never saw.
 *
 * A row that's already partially sold/transferred predates any per-unit
 * CT tracking, so there's no way to know which specific unit's weight
 * was already consumed — the opening balance for those rows is a
 * proportional estimate (carat_weight × on_hand / qty), clearly reasoned
 * about below. This is NOT the same thing as the averaging the ledger
 * itself is forbidden from doing going forward: forward postings always
 * use the real entered CT, never a derived split.
 *
 * Safe to re-run: any purchase_product that already has CaratMovement
 * rows (from a previous run of this command, or from real activity
 * posted by the live ledger since it went in) is left untouched.
 */
class BackfillCaratLedger extends Command
{
    protected $signature = 'carat:backfill-ledger {--apply : Actually write the opening balances (default is a dry-run preview)}';

    protected $description = 'Seed opening CaratMovement balances for purchase_products rows that predate the CT ledger.';

    public function handle(StockService $stock): int
    {
        $apply = (bool) $this->option('apply');

        $query = PurchaseProduct::query()
            ->with(['product', 'line.product'])
            ->whereNotNull('carat_weight')
            ->where('carat_weight', '>', 0);

        $total = (clone $query)->count();
        $this->info(($apply ? '' : '[DRY RUN] ') . "Scanning {$total} purchase_products rows with a recorded carat_weight...");

        $seeded            = 0;
        $skippedHasLedger  = 0;
        $skippedZeroOnHand = 0;
        $skippedNoProduct  = 0;
        $skippedAnomaly    = 0;
        $totalCaratSeeded  = 0.0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunkById(500, function ($rows) use (
            $stock, $apply, &$seeded, &$skippedHasLedger, &$skippedZeroOnHand,
            &$skippedNoProduct, &$skippedAnomaly, &$totalCaratSeeded, $bar
        ) {
            foreach ($rows as $pp) {
                $bar->advance();

                // Already has ledger activity — either backfilled by an
                // earlier run of this same command, or genuinely posted
                // by a purchase/sale/transfer made since the ledger went
                // live. Never touch it again either way.
                if (CaratMovement::where('purchase_product_id', $pp->id)->exists()) {
                    $skippedHasLedger++;
                    continue;
                }

                $productId = $pp->resolved_product?->id;
                if (! $productId) {
                    $skippedNoProduct++;
                    continue;
                }

                $byLoc       = $stock->onHandForPieceByLocation($pp->id);
                $onHandTotal = array_sum($byLoc);

                if ($onHandTotal <= 0) {
                    // Nothing left of this piece — remainingCarat*() is
                    // already correctly 0 with zero movement rows, so
                    // there's nothing to seed.
                    $skippedZeroOnHand++;
                    continue;
                }

                $qty = (int) $pp->qty;
                if ($qty <= 0 || $onHandTotal > $qty) {
                    // On-hand exceeding recorded qty (or a non-positive
                    // qty) is a data problem, not something to paper over
                    // with a guess — flag it for a human to look at.
                    $skippedAnomaly++;
                    $this->newLine();
                    $this->warn("  Anomaly: purchase_product #{$pp->id} — qty={$qty}, on_hand_total={$onHandTotal}. Skipped, needs manual review.");
                    continue;
                }

                $openingTotal = round((float) $pp->carat_weight * $onHandTotal / $qty, 3);
                if ($openingTotal <= 0) {
                    $skippedZeroOnHand++;
                    continue;
                }

                if ($apply) {
                    DB::transaction(function () use ($stock, $pp, $productId, $byLoc, $openingTotal) {
                        ksort($byLoc);
                        $locIds         = array_keys($byLoc);
                        $qtySum         = array_sum($byLoc);
                        $lastIdx        = count($locIds) - 1;
                        $remainingCarat = $openingTotal;

                        foreach ($locIds as $i => $locId) {
                            // Last location (by id) absorbs whatever's left
                            // of the total after proportional rounding, so
                            // the per-location shares always sum to exactly
                            // openingTotal — same "last one absorbs the
                            // remainder" rule used when booking a sale/
                            // transfer that spans multiple locations.
                            $share = ($i === $lastIdx)
                                ? $remainingCarat
                                : round($openingTotal * $byLoc[$locId] / $qtySum, 3);
                            $remainingCarat = round($remainingCarat - $share, 3);

                            if ($share <= 0) {
                                continue;
                            }

                            $stock->recordCarat([
                                'purchase_product_id' => $pp->id,
                                'product_id'          => $productId,
                                'location_id'         => $locId,
                                'direction'           => CaratMovement::DIRECTION_IN,
                                'carat'               => $share,
                                'reason'              => CaratMovement::REASON_OPENING,
                                'notes'               => 'CT ledger backfill (pre-ledger opening balance)',
                            ]);
                        }
                    });
                }

                $seeded++;
                $totalCaratSeeded += $openingTotal;
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Rows scanned', $total],
                ['Seeded' . ($apply ? '' : ' (would seed)'), $seeded],
                ['Skipped — already has ledger rows', $skippedHasLedger],
                ['Skipped — zero on-hand', $skippedZeroOnHand],
                ['Skipped — no resolvable product', $skippedNoProduct],
                ['Skipped — anomaly (needs review)', $skippedAnomaly],
                ['Total CT seeded' . ($apply ? '' : ' (would seed)'), round($totalCaratSeeded, 3)],
            ]
        );

        if (! $apply) {
            $this->comment('Dry run only — no rows were written. Re-run with --apply to write the opening balances.');
        }

        return self::SUCCESS;
    }
}
