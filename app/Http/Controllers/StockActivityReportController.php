<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockAuditScan;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Stock Activity Report — Daily / Weekly / Monthly rollup of Stock In,
 * Stock Out, Transfers, and per-user activity, built for a quick visual
 * read rather than a row-by-row ledger (the Stock Dashboard's ledger tabs
 * already cover that). Every number on this page can be re-derived from
 * stock_movements + stock_transfers + the audit-trail created_by/scanned_by
 * columns already on every table — nothing new is stored here.
 */
class StockActivityReportController extends Controller
{
    private const PERIODS = ['daily', 'weekly', 'monthly', 'quarterly', 'halfyearly', 'yearly', 'custom'];

    public function index(Request $request): View
    {
        $period = in_array($request->query('period'), self::PERIODS, true)
            ? $request->query('period')
            : 'daily';

        $anchor = $request->query('date')
            ? Carbon::parse($request->query('date'))->startOfDay()
            : Carbon::today();

        if ($period === 'custom') {
            $customFrom = $request->query('from') ? Carbon::parse($request->query('from')) : Carbon::today()->subDays(6);
            $customTo   = $request->query('to') ? Carbon::parse($request->query('to')) : Carbon::today();
            [$start, $end, $buckets, $bucketLabels, $bucketFormat] = $this->resolveCustomRange($customFrom, $customTo);
        } else {
            [$start, $end, $buckets, $bucketLabels, $bucketFormat] = $this->resolveRange($period, $anchor);
        }

        /* ── KPIs ─────────────────────────────────────────────── */
        $movementTotals = StockMovement::whereNull('deleted_at')
            ->whereBetween('movement_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN qty ELSE 0 END),0) as in_qty")
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'out' THEN qty ELSE 0 END),0) as out_qty")
            ->first();
        $stockInQty  = (int) $movementTotals->in_qty;
        $stockOutQty = (int) $movementTotals->out_qty;

        // Carat counterpart — carat_movements is its own parallel ledger
        // (same reason/direction/movement_date shape as stock_movements,
        // see StockAuditService::caratProgress() for the same pattern),
        // not derivable from stock_movements itself since that table has
        // no carat column at all.
        $caratTotals = DB::table('carat_movements')
            ->whereNull('deleted_at')
            ->whereBetween('movement_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN carat ELSE 0 END),0) as in_carat")
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'out' THEN carat ELSE 0 END),0) as out_carat")
            ->first();
        $stockInCarat  = (float) $caratTotals->in_carat;
        $stockOutCarat = (float) $caratTotals->out_carat;

        $transfersCount = StockTransfer::whereBetween('transfer_date', [$start->toDateString(), $end->toDateString()])->count();

        $websiteOrdersCount = Sale::whereHas('channel', fn ($q) => $q->where('code', Channel::CODE_WEBSITE))
            ->whereBetween('sale_date', [$start->toDateString(), $end->toDateString()])
            ->count();

        $activeUsers = $this->activeUserIds($start, $end)->count();

        /* ── Time-series: Stock In vs Stock Out per bucket ───────── */
        // Granularity comes from resolveRange()/resolveCustomRange() (hour
        // for a single day, day for a week/month/short custom range, month
        // for a quarter/half-year/year/long custom range) — never derived
        // from $period directly, since 'custom' can resolve to any of the
        // three depending on how wide a range the user picked.
        $dateExpr = match ($bucketFormat) {
            'hour'  => 'HOUR(created_at)',
            'month' => "DATE_FORMAT(movement_date, '%Y-%m')",
            default => 'movement_date',
        };
        $rows = StockMovement::whereNull('deleted_at')
            ->whereBetween('movement_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw("$dateExpr as bucket")
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN qty ELSE 0 END),0) as in_qty")
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'out' THEN qty ELSE 0 END),0) as out_qty")
            ->groupBy('bucket')
            ->get()
            ->keyBy(fn ($r) => $bucketFormat === 'hour' ? (int) $r->bucket : (string) $r->bucket);

        $seriesIn  = [];
        $seriesOut = [];
        foreach ($buckets as $b) {
            $row = $rows->get($b);
            $seriesIn[]  = $row ? (int) $row->in_qty : 0;
            $seriesOut[] = $row ? -1 * (int) $row->out_qty : 0; // negative so it plots below the axis
        }

        // Carat counterpart of the same per-bucket series, read from
        // carat_movements the same way the KPI carat totals are above.
        $caratRows = DB::table('carat_movements')
            ->whereNull('deleted_at')
            ->whereBetween('movement_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw("$dateExpr as bucket")
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN carat ELSE 0 END),0) as in_carat")
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'out' THEN carat ELSE 0 END),0) as out_carat")
            ->groupBy('bucket')
            ->get()
            ->keyBy(fn ($r) => $bucketFormat === 'hour' ? (int) $r->bucket : (string) $r->bucket);

        $seriesInCarat  = [];
        $seriesOutCarat = [];
        foreach ($buckets as $b) {
            $row = $caratRows->get($b);
            $seriesInCarat[]  = $row ? round((float) $row->in_carat, 2) : 0;
            $seriesOutCarat[] = $row ? round((float) $row->out_carat, 2) : 0;
        }

        /* ── Movement type breakdown (donut) ─────────────────────── */
        $reasonRows = StockMovement::whereNull('deleted_at')
            ->whereBetween('movement_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('reason, COUNT(*) as c, COALESCE(SUM(qty),0) as qty')
            ->groupBy('reason')
            ->get();

        // Same reason strings on both ledgers (CaratMovement::REASON_* mirrors
        // StockMovement::REASON_* exactly), so the carat side can be bucketed
        // with the same reason lists.
        $caratReasonRows = DB::table('carat_movements')
            ->whereNull('deleted_at')
            ->whereBetween('movement_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('reason, COALESCE(SUM(carat),0) as carat')
            ->groupBy('reason')
            ->get();

        $reasonBuckets = [
            'Purchases'   => [StockMovement::REASON_PURCHASE],
            'Sales'       => [StockMovement::REASON_SALE],
            'Transfers'   => [StockMovement::REASON_TRANSFER_IN, StockMovement::REASON_TRANSFER_OUT, StockMovement::REASON_TRANSFER_CANCEL_OUT],
            'Returns'     => [StockMovement::REASON_SALE_RETURN, StockMovement::REASON_SALE_CANCEL, StockMovement::REASON_SALE_EDIT_REVERSE],
            'Adjustments' => [StockMovement::REASON_ADJUSTMENT_IN, StockMovement::REASON_ADJUSTMENT_OUT, StockMovement::REASON_OPENING, StockMovement::REASON_PURCHASE_CANCEL],
        ];
        $movementTypeLabels = [];
        $movementTypeQty    = [];
        $movementTypeCarat  = [];
        foreach ($reasonBuckets as $label => $reasons) {
            $qty = (int) $reasonRows->whereIn('reason', $reasons)->sum('qty');
            if ($qty > 0) {
                $movementTypeLabels[] = $label;
                $movementTypeQty[]    = $qty;
                $movementTypeCarat[]  = round((float) $caratReasonRows->whereIn('reason', $reasons)->sum('carat'), 2);
            }
        }

        /* ── Top stones by carat — sold vs purchased ─────────────── */
        $topSellingByCarat   = $this->topStonesByCarat($start, $end, 'sale');
        $topPurchasedByCarat = $this->topStonesByCarat($start, $end, 'purchase');

        /* ── Transfers by status ──────────────────────────────────── */
        $transfersByStatus = StockTransfer::whereBetween('transfer_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $recentTransfers = StockTransfer::with(['fromLocation:id,name', 'toLocation:id,name'])
            ->withCount('lines')
            ->whereBetween('transfer_date', [$start->toDateString(), $end->toDateString()])
            ->latest('created_at')
            ->limit(6)
            ->get();

        /* ── User activity ────────────────────────────────────────── */
        $userActivity = $this->userActivity($start, $end);

        return view('reports.stock-activity', [
            'period'             => $period,
            'anchor'             => $anchor,
            'rangeStart'         => $start,
            'rangeEnd'           => $end,
            'rangeLabel'         => $this->rangeLabel($period, $start, $end),
            'prevDate'           => $this->shift($period === 'custom' ? 'daily' : $period, $anchor, -1)->toDateString(),
            'nextDate'           => $this->shift($period === 'custom' ? 'daily' : $period, $anchor, 1)->toDateString(),
            'todayDate'          => Carbon::today()->toDateString(),
            'customFrom'         => $period === 'custom' ? $start->toDateString() : Carbon::today()->subDays(6)->toDateString(),
            'customTo'           => $period === 'custom' ? $end->toDateString() : Carbon::today()->toDateString(),
            'websiteOrdersCount' => $websiteOrdersCount,
            'stockInQty'          => $stockInQty,
            'stockOutQty'         => $stockOutQty,
            'stockInCarat'        => $stockInCarat,
            'stockOutCarat'       => $stockOutCarat,
            'netChangeQty'        => $stockInQty - $stockOutQty,
            'netChangeCarat'      => $stockInCarat - $stockOutCarat,
            'transfersCount'      => $transfersCount,
            'activeUsers'         => $activeUsers,
            'bucketLabels'        => $bucketLabels,
            'seriesIn'            => $seriesIn,
            'seriesOut'           => $seriesOut,
            'seriesInCarat'       => $seriesInCarat,
            'seriesOutCarat'      => $seriesOutCarat,
            'movementTypeLabels'  => $movementTypeLabels,
            'movementTypeQty'     => $movementTypeQty,
            'movementTypeCarat'   => $movementTypeCarat,
            'topSellingByCarat'   => $topSellingByCarat,
            'topPurchasedByCarat' => $topPurchasedByCarat,
            'transfersByStatus'   => $transfersByStatus,
            'recentTransfers'     => $recentTransfers,
            'userActivity'        => $userActivity,
        ]);
    }

    /**
     * Every non-custom period is resolved from a single anchor date; a
     * custom range is resolved from explicit from/to dates instead (see
     * resolveCustomRange()). Bucket granularity scales with the span so a
     * year view doesn't try to plot 365 daily bars: hour → day → month.
     *
     * @return array{0: Carbon, 1: Carbon, 2: array, 3: array, 4: string}
     */
    private function resolveRange(string $period, Carbon $anchor): array
    {
        return match ($period) {
            'weekly' => $this->dailyBuckets(
                $anchor->copy()->startOfWeek(Carbon::MONDAY),
                $anchor->copy()->endOfWeek(Carbon::SUNDAY),
                'D j'
            ),
            'monthly' => $this->dailyBuckets(
                $anchor->copy()->startOfMonth(),
                $anchor->copy()->endOfMonth(),
                'j'
            ),
            'quarterly' => $this->monthlyBuckets(
                $anchor->copy()->firstOfQuarter(),
                $anchor->copy()->lastOfQuarter()
            ),
            'halfyearly' => (function () use ($anchor) {
                $half  = $anchor->month <= 6 ? 1 : 2;
                $start = $anchor->copy()->month($half === 1 ? 1 : 7)->startOfMonth();
                $end   = $start->copy()->addMonthsNoOverflow(5)->endOfMonth();
                return $this->monthlyBuckets($start, $end);
            })(),
            'yearly' => $this->monthlyBuckets(
                $anchor->copy()->startOfYear(),
                $anchor->copy()->endOfYear()
            ),
            default => (function () use ($anchor) {
                $start = $anchor->copy()->startOfDay();
                $end   = $anchor->copy()->endOfDay();
                $buckets = range(0, 23);
                $labels  = array_map(fn ($h) => sprintf('%02d:00', $h), $buckets);
                return [$start, $end, $buckets, $labels, 'hour'];
            })(),
        };
    }

    /**
     * A user-picked from/to range — granularity scales with the span
     * since there's no fixed shape to lean on like the named periods.
     *
     * @return array{0: Carbon, 1: Carbon, 2: array, 3: array, 4: string}
     */
    private function resolveCustomRange(Carbon $from, Carbon $to): array
    {
        $start = $from->copy()->startOfDay();
        $end   = $to->copy()->endOfDay();
        if ($end->lt($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        $days = $start->diffInDays($end) + 1;

        if ($days <= 1) {
            $buckets = range(0, 23);
            $labels  = array_map(fn ($h) => sprintf('%02d:00', $h), $buckets);
            return [$start, $end, $buckets, $labels, 'hour'];
        }
        if ($days <= 62) {
            return $this->dailyBuckets($start, $end, $days > 31 ? 'j M' : 'D j');
        }

        return $this->monthlyBuckets($start->copy()->startOfMonth(), $end->copy()->endOfMonth());
    }

    /** One bucket per calendar day between $start and $end, inclusive. */
    private function dailyBuckets(Carbon $start, Carbon $end, string $labelFormat): array
    {
        $buckets = [];
        $labels  = [];
        foreach (CarbonPeriod::create($start, $end) as $d) {
            $buckets[] = $d->toDateString();
            $labels[]  = $d->format($labelFormat);
        }
        return [$start, $end, $buckets, $labels, 'day'];
    }

    /** One bucket per calendar month between $start and $end, inclusive. */
    private function monthlyBuckets(Carbon $start, Carbon $end): array
    {
        $buckets = [];
        $labels  = [];
        foreach (CarbonPeriod::create($start->copy()->startOfMonth(), '1 month', $end->copy()->startOfMonth()) as $d) {
            $buckets[] = $d->format('Y-m');
            $labels[]  = $d->format('M Y');
        }
        return [$start, $end, $buckets, $labels, 'month'];
    }

    private function shift(string $period, Carbon $anchor, int $direction): Carbon
    {
        return match ($period) {
            'weekly'     => $anchor->copy()->addWeeks($direction),
            'monthly'    => $anchor->copy()->addMonthNoOverflow($direction),
            'quarterly'  => $anchor->copy()->addMonthsNoOverflow(3 * $direction),
            'halfyearly' => $anchor->copy()->addMonthsNoOverflow(6 * $direction),
            'yearly'     => $anchor->copy()->addYears($direction),
            default      => $anchor->copy()->addDays($direction),
        };
    }

    private function rangeLabel(string $period, Carbon $start, Carbon $end): string
    {
        return match ($period) {
            'weekly'     => $start->format('d M') . ' – ' . $end->format('d M Y'),
            'monthly'    => $start->format('F Y'),
            'quarterly'  => 'Q' . $start->quarter . ' ' . $start->format('Y') . ' (' . $start->format('M') . ' – ' . $end->format('M Y') . ')',
            'halfyearly' => ($start->month === 1 ? 'H1' : 'H2') . ' ' . $start->format('Y') . ' (' . $start->format('M') . ' – ' . $end->format('M Y') . ')',
            'yearly'     => $start->format('Y'),
            'custom'     => $start->format('d M Y') . ' – ' . $end->format('d M Y'),
            default      => $start->format('l, d M Y'),
        };
    }

    /**
     * Top stones (categories) by carat weight for a given reason —
     * 'sale' for "Top Selling", 'purchase' for "Top Purchased". Reads
     * carat_movements directly rather than stock_movements: that table
     * has no carat column at all, and carat_movements already carries the
     * same reason/movement_date shape needed to filter it the same way.
     */
    private function topStonesByCarat(Carbon $start, Carbon $end, string $reason, int $limit = 6): \Illuminate\Support\Collection
    {
        return DB::table('carat_movements')
            ->join('products', 'products.id', '=', 'carat_movements.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->whereNull('carat_movements.deleted_at')
            ->where('carat_movements.reason', $reason)
            ->whereBetween('carat_movements.movement_date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('categories.id', 'categories.name')
            ->select([
                'categories.id   as category_id',
                'categories.name as category_name',
                DB::raw('SUM(carat_movements.carat) as carat'),
                DB::raw('COUNT(DISTINCT carat_movements.purchase_product_id) as pieces'),
            ])
            ->orderByDesc('carat')
            ->limit($limit)
            ->get();
    }

    /**
     * Distinct users who touched anything (purchases, sales, transfers,
     * stock movements, or audit scans) inside the range — the app has no
     * dedicated activity-log table, so "active" is derived from the
     * created_by/scanned_by columns every real action already stamps.
     */
    private function activeUserIds(Carbon $start, Carbon $end): \Illuminate\Support\Collection
    {
        $ids = collect();
        $ids = $ids->merge(
            Purchase::whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()])->pluck('created_by')
        );
        $ids = $ids->merge(
            Sale::whereBetween('sale_date', [$start->toDateString(), $end->toDateString()])->pluck('created_by')
        );
        $ids = $ids->merge(
            StockTransfer::whereBetween('transfer_date', [$start->toDateString(), $end->toDateString()])->pluck('created_by')
        );
        $ids = $ids->merge(
            StockAuditScan::whereBetween('scanned_at', [$start, $end])->pluck('scanned_by')
        );

        return $ids->filter()->unique();
    }

    /**
     * Per-user breakdown feeding both the "Top Active Users" chart and
     * the activity table — one row per user who did anything at all in
     * the range, ranked by total actions.
     */
    private function userActivity(Carbon $start, Carbon $end): \Illuminate\Support\Collection
    {
        $purchases = Purchase::whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('created_by as user_id, COUNT(*) as c')->groupBy('created_by')->pluck('c', 'user_id');
        $sales = Sale::whereBetween('sale_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('created_by as user_id, COUNT(*) as c')->groupBy('created_by')->pluck('c', 'user_id');
        $transfers = StockTransfer::whereBetween('transfer_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('created_by as user_id, COUNT(*) as c')->groupBy('created_by')->pluck('c', 'user_id');
        $scans = StockAuditScan::whereBetween('scanned_at', [$start, $end])
            ->selectRaw('scanned_by as user_id, COUNT(*) as c')->groupBy('scanned_by')->pluck('c', 'user_id');

        $userIds = collect()
            ->merge($purchases->keys())
            ->merge($sales->keys())
            ->merge($transfers->keys())
            ->merge($scans->keys())
            ->filter()
            ->unique();

        if ($userIds->isEmpty()) {
            return collect();
        }

        $names = User::whereIn('id', $userIds)->pluck('name', 'id');

        return $userIds->map(function ($id) use ($purchases, $sales, $transfers, $scans, $names) {
            $p = (int) ($purchases[$id] ?? 0);
            $s = (int) ($sales[$id] ?? 0);
            $t = (int) ($transfers[$id] ?? 0);
            $sc = (int) ($scans[$id] ?? 0);

            return (object) [
                'user_id'   => $id,
                'name'      => $names[$id] ?? 'Unknown User',
                'purchases' => $p,
                'sales'     => $s,
                'transfers' => $t,
                'scans'     => $sc,
                'total'     => $p + $s + $t + $sc,
            ];
        })
        ->sortByDesc('total')
        ->values();
    }
}
