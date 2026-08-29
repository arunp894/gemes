<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseProduct;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use Illuminate\Support\Collection;
use App\Services\SettingService;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * Read-only views over the stock ledger.
 *
 *   /stock                — on-hand summary (per product, per location)
 *   /stock/pieces/{pp}    — piece detail (where it is + full movement log)
 *   /stock/ledger         — full movement history with filters
 */
class StockController extends Controller
{
    /**
     * Products at or below this on-hand quantity are flagged "Low Stock"
     * on the dashboard. There's no per-product reorder-level field yet,
     * so this is a single fixed default rather than something configurable
     * per product — revisit if that becomes a real requirement.
     */
    private const LOW_STOCK_THRESHOLD = 5;

    /**
     * A stone (category) is flagged "Low Stock" on the Stones & Carat
     * table when its total on-hand pieces, summed across every product
     * under it, falls at or below this. Deliberately much higher than
     * LOW_STOCK_THRESHOLD above (which flags a single product) — a
     * category aggregates many products, so the same small number would
     * flag almost every stone as low.
     */
    private const STONE_LOW_STOCK_THRESHOLD = 10;

    public function __construct(
        private StockService $stock,
        private SettingService $settings,
    ) {}

    /* ─── Dashboard ────────────────────────────────────────── */

    public function index(Request $request): View
    {
        $today = now()->toDateString();

        $canStockTransfers = (bool) auth()->user()?->hasPermission('stock-transfers.view');

        // ── On-hand per (purchase_product, product) — the base every
        // other KPI here is derived from, so it's computed once. ────────
        $onHandPieces = DB::table('stock_movements')
            ->whereNull('deleted_at')
            ->groupBy('purchase_product_id', 'product_id')
            ->havingRaw("SUM(CASE WHEN direction = 'in' THEN qty ELSE -qty END) > 0")
            ->select([
                'purchase_product_id',
                'product_id',
                DB::raw("SUM(CASE WHEN direction = 'in' THEN qty ELSE -qty END) as on_hand"),
            ]);

        $totalCurrentStock = (int) DB::query()->fromSub($onHandPieces, 'op')->sum('on_hand');

        $totalStockValue = (float) DB::query()
            ->fromSub($onHandPieces, 'op')
            ->join('purchase_products', 'purchase_products.id', '=', 'op.purchase_product_id')
            ->sum(DB::raw('op.on_hand * purchase_products.price'));

        // Same ledger-first, carat_weight-fallback approach as
        // categoryData() below — a piece with no carat_movements history
        // (demo/seed data inserted directly into stock_movements) would
        // otherwise silently contribute 0 to this total despite having a
        // real recorded carat_weight.
        $caratLedgerByPiece = DB::table('carat_movements')
            ->whereNull('deleted_at')
            ->groupBy('purchase_product_id')
            ->selectRaw("purchase_product_id, SUM(CASE WHEN direction = 'in' THEN carat ELSE -carat END) as remaining_carat");

        $totalCurrentStockCt = (float) DB::query()
            ->fromSub($onHandPieces, 'op')
            ->join('purchase_products', 'purchase_products.id', '=', 'op.purchase_product_id')
            ->leftJoinSub($caratLedgerByPiece, 'cl', 'cl.purchase_product_id', '=', 'op.purchase_product_id')
            ->selectRaw('COALESCE(cl.remaining_carat, purchase_products.carat_weight * op.on_hand) as ct')
            ->get()
            ->sum('ct');

        // ── Today's received / removed (qty + ct) ───────────────────────
        $todayQty = DB::table('stock_movements')
            ->whereNull('deleted_at')
            ->whereDate('movement_date', $today)
            ->selectRaw("SUM(CASE WHEN direction = 'in' THEN qty ELSE 0 END) as received")
            ->selectRaw("SUM(CASE WHEN direction = 'out' THEN qty ELSE 0 END) as removed")
            ->first();

        $todayCt = DB::table('carat_movements')
            ->whereNull('deleted_at')
            ->whereDate('movement_date', $today)
            ->selectRaw("SUM(CASE WHEN direction = 'in' THEN carat ELSE 0 END) as received")
            ->selectRaw("SUM(CASE WHEN direction = 'out' THEN carat ELSE 0 END) as removed")
            ->first();

        // ── Bottom summary strip: today's activity by category ──────────
        $todayByReason = DB::table('stock_movements')
            ->whereNull('deleted_at')
            ->whereDate('movement_date', $today)
            ->selectRaw("SUM(CASE WHEN reason IN ('transfer_in','transfer_out') THEN qty ELSE 0 END) as transfers")
            ->selectRaw("SUM(CASE WHEN reason = 'sale' THEN qty ELSE 0 END) as sales")
            ->selectRaw("SUM(CASE WHEN reason = 'sale_return' THEN qty ELSE 0 END) as returns")
            ->selectRaw("SUM(CASE WHEN reason IN ('adjustment_in','adjustment_out') THEN qty ELSE 0 END) as adjustments")
            ->first();

        // ── Low stock: on-hand per product (across all locations), at
        // or below the threshold but still tracked (> 0). ──────────────
        $onHandByProduct = DB::query()->fromSub($onHandPieces, 'op')
            ->groupBy('op.product_id')
            ->select('op.product_id', DB::raw('SUM(op.on_hand) as on_hand'))
            ->havingRaw('SUM(op.on_hand) > 0 and SUM(op.on_hand) <= ?', [self::LOW_STOCK_THRESHOLD]);

        $lowStockCount = (int) DB::query()->fromSub($onHandByProduct, 'lp')->count();

        $lowStockItems = DB::query()->fromSub($onHandByProduct, 'lp')
            ->join('products', 'products.id', '=', 'lp.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->orderBy('lp.on_hand')
            ->limit(5)
            ->select(['products.id', 'products.title', 'products.sku', 'categories.name as category_name', 'lp.on_hand'])
            ->get();

        // ── Stock by location (for the donut) ───────────────────────────
        $byLocation = DB::table('stock_movements')
            ->join('locations', 'locations.id', '=', 'stock_movements.location_id')
            ->whereNull('stock_movements.deleted_at')
            ->groupBy('locations.id', 'locations.name')
            ->havingRaw("SUM(CASE WHEN stock_movements.direction = 'in' THEN stock_movements.qty ELSE -stock_movements.qty END) > 0")
            ->select([
                'locations.name',
                DB::raw("SUM(CASE WHEN stock_movements.direction = 'in' THEN stock_movements.qty ELSE -stock_movements.qty END) as on_hand"),
            ])
            ->orderByDesc('on_hand')
            ->get()
            // PDO returns SUM() as a numeric string, not an int — ApexCharts'
            // pie/donut series-format detection silently rejects a string
            // array and renders nothing, so this must be a real number
            // before it ever reaches @json() in the view.
            ->map(fn ($row) => tap($row, fn ($r) => $r->on_hand = (int) $r->on_hand));

        // ── Recent transfers (only if the user can see that module) ────
        $recentTransfers = collect();
        if ($canStockTransfers) {
            $recentTransfers = StockTransfer::with(['fromLocation:id,name', 'toLocation:id,name'])
                ->withCount('lines')
                ->withSum('lines', 'qty')
                ->latest('created_at')
                ->limit(5)
                ->get();
        }

        return view('stock.index', [
            'locations'  => Location::active()->orderBy('name')->get(['id', 'location_code', 'name', 'is_default']),
            'categories' => Category::active()->ordered()->get(['id', 'name']),

            'totalCurrentStock'   => $totalCurrentStock,
            'totalCurrentStockCt' => $totalCurrentStockCt,
            'totalStockValue'     => $totalStockValue,
            'todayReceivedQty'    => (int) ($todayQty->received ?? 0),
            'todayRemovedQty'     => (int) ($todayQty->removed ?? 0),
            'todayReceivedCt'     => (float) ($todayCt->received ?? 0),
            'todayRemovedCt'      => (float) ($todayCt->removed ?? 0),
            'todayTransfersQty'   => (int) ($todayByReason->transfers ?? 0),
            'todaySalesQty'       => (int) ($todayByReason->sales ?? 0),
            'todayReturnsQty'     => (int) ($todayByReason->returns ?? 0),
            'todayAdjustmentsQty' => (int) ($todayByReason->adjustments ?? 0),
            'lowStockCount'       => $lowStockCount,
            'lowStockItems'       => $lowStockItems,
            'lowStockThreshold'   => self::LOW_STOCK_THRESHOLD,
            'byLocation'          => $byLocation,
            'canStockTransfers'   => $canStockTransfers,
            'recentTransfers'     => $recentTransfers,
        ]);
    }

    /**
     * DataTables-friendly on-hand-by-product-per-location feed.
     *
     * Materialized from a single grouped query against stock_movements.
     * Could be cached if a future report screen needs it; for now the
     * direct query is fast enough thanks to the (product_id, location_id)
     * composite index.
     */
    public function data(Request $request): JsonResponse
    {
        $locationId = (int) $request->query('location_id', 0);
        $categoryId = (int) $request->query('category_id', 0);

        // Subquery aggregating in/out by product+location; then join to
        // products and locations for labels.
        $signedSql = "SUM(CASE WHEN stock_movements.direction = 'in' THEN stock_movements.qty "
            . "ELSE -stock_movements.qty END)";

        // Remaining CT per (product, location), pre-aggregated from the
        // real CT ledger — NOT qty × per-unit carat_weight (a row's
        // units can carry different individual weights, see
        // CaratMovement). Pre-aggregating to exactly one row per
        // (product_id, location_id) before joining is required: joining
        // carat_movements directly against stock_movements would fan out
        // (many historical rows on each side) and wildly over-count.
        $caratAgg = DB::table('carat_movements')
            ->whereNull('deleted_at')
            ->groupBy('product_id', 'location_id')
            ->selectRaw('product_id, location_id, '
                . 'SUM(CASE WHEN direction = \'in\' THEN carat ELSE -carat END) as remaining_carat');

        $base = DB::table('stock_movements')
            ->join('products',      'products.id',      '=', 'stock_movements.product_id')
            ->join('locations',     'locations.id',      '=', 'stock_movements.location_id')
            ->leftJoin('categories', 'categories.id',     '=', 'products.category_id')
            ->leftJoinSub($caratAgg, 'cm_agg', function ($join) {
                $join->on('cm_agg.product_id', '=', 'stock_movements.product_id')
                     ->on('cm_agg.location_id', '=', 'stock_movements.location_id');
            })
            ->whereNull('stock_movements.deleted_at')
            ->groupBy(
                'stock_movements.product_id', 'stock_movements.location_id',
                'products.title', 'products.sku', 'products.category_id',
                'products.carat_weight',
                'categories.name', 'locations.name', 'locations.location_code'
            )
            ->select([
                'stock_movements.product_id',
                'stock_movements.location_id',
                'products.title       as product_title',
                'products.sku         as product_sku',
                'products.category_id as category_id',
                'products.carat_weight as carat_weight',
                'categories.name      as category_name',
                'locations.name       as location_name',
                'locations.location_code',
                DB::raw($signedSql . ' as on_hand'),
                // cm_agg joins to exactly one row per (product_id,
                // location_id) — MAX() here just picks up that constant
                // value per group, not a real ambiguity.
                DB::raw('MAX(cm_agg.remaining_carat) as remaining_carat'),
            ])
            ->havingRaw($signedSql . ' <> 0');

        if ($locationId) {
            $base->where('stock_movements.location_id', $locationId);
        }
        if ($categoryId) {
            $base->where('products.category_id', $categoryId);
        }

        return DataTables::query($base)
            ->addIndexColumn()
            ->editColumn('on_hand', fn ($row) =>
                '<span class="fw-semibold ' . ((int) $row->on_hand <= 0 ? 'text-danger' : '') . '">'
                . (int) $row->on_hand . '</span>'
            )
            ->addColumn('remaining_ct', fn ($row) =>
                $row->carat_weight !== null && $row->remaining_carat !== null
                    ? number_format((float) $row->remaining_carat, 3) . ' ct'
                    : '—'
            )
            ->addColumn('product_label', fn ($row) =>
                '<div class="fw-semibold">' . e($row->product_title) . '</div>'
                . '<small class="text-muted">SKU: ' . e($row->product_sku)
                . ($row->category_name ? ' &middot; ' . e($row->category_name) : '') . '</small>'
            )
            ->addColumn('location_label', fn ($row) =>
                e($row->location_name) . ' <small class="text-muted">(' . e($row->location_code) . ')</small>'
            )
            ->addColumn('action', function ($row) {
                $url = route('stock.product', ['product' => $row->product_id]);
                $urlLoc = $url . '?location_id=' . (int) $row->location_id;
                return '<a href="' . $urlLoc . '" class="action-link action-link-view" title="View Ledger">'
                    . '<i class="ti ti-history fs-sm"></i> Ledger</a>';
            })
            ->filterColumn('product_label', function ($q, $keyword) {
                $like = "%{$keyword}%";
                $q->where(function ($qq) use ($like) {
                    $qq->where('products.title', 'like', $like)
                        ->orWhere('products.sku', 'like', $like);
                });
            })
            ->rawColumns(['on_hand', 'product_label', 'location_label', 'action'])
            ->toJson();
    }

    /* ─── Category rollup ──────────────────────────────────── */

    /**
     * Total on-hand + distinct product count per category, respecting
     * the same location filter as the main table. Every purchase now
     * mints its own Product (1:1 with the physical piece/box), so the
     * per-product table above lists one row per item rather than one
     * per style -- this is the "how much of X do I have" answer that
     * gives back, grouped at the category level instead.
     *
     * Not DataTables-paginated: there are only ever a handful of
     * categories, so one small query on page load (and again on location
     * change) is simpler than standing up a second server-side table.
     */
    public function categoryData(Request $request): JsonResponse
    {
        $locationId = (int) $request->query('location_id', 0);

        $signedSql = "SUM(CASE WHEN stock_movements.direction = 'in' THEN stock_movements.qty "
            . "ELSE -stock_movements.qty END)";

        // Remaining CT per product, pre-aggregated from the real CT
        // ledger (respecting the same optional location filter as the
        // qty side below) — NOT qty × per-unit carat_weight, since a
        // product's units can carry different individual weights.
        $caratAgg = DB::table('carat_movements')
            ->whereNull('deleted_at')
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(CASE WHEN direction = \'in\' THEN carat ELSE -carat END) as remaining_carat');

        // Per-product balance first -- matches the on-hand table's own
        // math -- then rolled up to category, so a product sitting at
        // exactly zero doesn't count toward that category's product count.
        $perProduct = DB::table('stock_movements')
            ->join('products', 'products.id', '=', 'stock_movements.product_id')
            ->leftJoinSub($caratAgg, 'cm_agg', 'cm_agg.product_id', '=', 'stock_movements.product_id')
            ->whereNull('stock_movements.deleted_at')
            ->when($locationId, fn ($q) => $q->where('stock_movements.location_id', $locationId))
            ->groupBy('products.id', 'products.category_id')
            ->select([
                'products.id          as product_id',
                'products.category_id as category_id',
                DB::raw($signedSql . ' as on_hand'),
                // Prefer the precise CT ledger; fall back to on_hand qty ×
                // the product's static carat_weight when the ledger has no
                // rows at all for this product (e.g. demo/seed data
                // inserted directly into stock_movements, bypassing
                // PurchaseService — see SeedDemoTransactions.php). Without
                // this, a product with a real carat_weight but no ledger
                // history would silently show no carat anywhere.
                DB::raw('COALESCE(MAX(cm_agg.remaining_carat), MAX(products.carat_weight) * ' . $signedSql . ') as remaining_carat'),
            ])
            ->havingRaw($signedSql . ' <> 0');

        $rows = DB::query()
            ->fromSub($perProduct, 'p')
            ->join('categories', 'categories.id', '=', 'p.category_id')
            ->groupBy('categories.id', 'categories.name')
            ->select([
                'categories.id   as category_id',
                'categories.name as category_name',
                DB::raw('SUM(p.on_hand) as on_hand'),
                DB::raw('COUNT(*) as product_count'),
                DB::raw('SUM(COALESCE(p.remaining_carat, 0)) as on_hand_carats'),
            ])
            ->orderByDesc('on_hand')
            ->get();

        return response()->json(['ok' => true, 'categories' => $rows]);
    }

    /**
     * DataTables feed for the "Stones & Carat" tab — the same per-category
     * rollup as categoryData() above, but paginated/searchable and carrying
     * stock value + average rate-per-carat, for a dedicated stone-wise
     * report table rather than the compact sidebar list.
     *
     * Value/rate are built from the piece-level (purchase_product_id,
     * product_id) grain — same as index()'s totalStockValue KPI — rather
     * than joining purchase_products directly onto the product-level
     * rollup, since a product can in principle be fed by more than one
     * purchase_product and a naive join would fan out and over-count.
     */
    public function byStoneData(Request $request): JsonResponse
    {
        $locationId = (int) $request->query('location_id', 0);
        $categoryId = (int) $request->query('category_id', 0);

        $onHandPieces = DB::table('stock_movements')
            ->whereNull('deleted_at')
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->groupBy('purchase_product_id', 'product_id')
            ->havingRaw("SUM(CASE WHEN direction = 'in' THEN qty ELSE -qty END) > 0")
            ->select([
                'purchase_product_id',
                'product_id',
                DB::raw("SUM(CASE WHEN direction = 'in' THEN qty ELSE -qty END) as on_hand"),
            ]);

        $caratLedgerByPiece = DB::table('carat_movements')
            ->whereNull('deleted_at')
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->groupBy('purchase_product_id')
            ->selectRaw("purchase_product_id, SUM(CASE WHEN direction = 'in' THEN carat ELSE -carat END) as remaining_carat");

        $perPiece = DB::query()
            ->fromSub($onHandPieces, 'op')
            ->join('purchase_products', 'purchase_products.id', '=', 'op.purchase_product_id')
            ->join('products', 'products.id', '=', 'op.product_id')
            ->leftJoinSub($caratLedgerByPiece, 'cl', 'cl.purchase_product_id', '=', 'op.purchase_product_id')
            ->select([
                'products.category_id as category_id',
                'op.product_id        as product_id',
                'op.on_hand           as on_hand',
                DB::raw('op.on_hand * purchase_products.price as piece_value'),
                DB::raw('COALESCE(cl.remaining_carat, purchase_products.carat_weight * op.on_hand) as piece_carat'),
            ]);

        $query = DB::query()
            ->fromSub($perPiece, 'pp')
            ->join('categories', 'categories.id', '=', 'pp.category_id')
            ->when($categoryId, fn ($q) => $q->where('categories.id', $categoryId))
            ->groupBy('categories.id', 'categories.name')
            ->select([
                'categories.id   as category_id',
                'categories.name as category_name',
                DB::raw('COUNT(DISTINCT pp.product_id) as product_count'),
                DB::raw('SUM(pp.on_hand) as pieces'),
                DB::raw('SUM(COALESCE(pp.piece_carat, 0)) as carat_weight'),
                DB::raw('SUM(COALESCE(pp.piece_value, 0)) as stock_value'),
            ]);

        return DataTables::query($query)
            ->addIndexColumn()
            ->addColumn('stone_label', fn ($row) =>
                '<div class="d-flex align-items-center gap-2">'
                . '<span class="movement-thumb movement-thumb-sm"><i class="ti ti-diamond"></i></span>'
                . '<div class="min-w-0">'
                . '<div class="fw-semibold text-truncate">' . e($row->category_name) . '</div>'
                . '<small class="text-muted">' . (int) $row->product_count . ' product' . ((int) $row->product_count === 1 ? '' : 's') . '</small>'
                . '</div></div>'
            )
            ->editColumn('pieces', fn ($row) => number_format((int) $row->pieces))
            ->addColumn('carat_label', fn ($row) => number_format((float) $row->carat_weight, 2) . ' ct')
            ->addColumn('rate_label', function ($row) {
                $carat = (float) $row->carat_weight;

                return $carat > 0
                    ? $this->settings->formatMoney($row->stock_value / $carat) . ' / ct'
                    : '—';
            })
            ->addColumn('value_label', fn ($row) => $this->settings->formatMoney((float) $row->stock_value))
            ->addColumn('status_label', fn ($row) =>
                (int) $row->pieces <= self::STONE_LOW_STOCK_THRESHOLD
                    ? '<span class="badge badge-soft-warning">Low Stock</span>'
                    : '<span class="badge badge-soft-success">In Stock</span>'
            )
            ->addColumn('action', fn ($row) =>
                '<a href="javascript:void(0)" class="action-link action-link-view js-view-stone" '
                . 'data-category-id="' . $row->category_id . '" title="View products in Current Stock">'
                . '<i class="ti ti-eye fs-sm"></i></a>'
            )
            ->filterColumn('stone_label', fn ($q, $keyword) => $q->where('categories.name', 'like', "%{$keyword}%"))
            ->rawColumns(['stone_label', 'status_label', 'action'])
            ->toJson();
    }

    /* ─── Stock Movement (unified ledger, all sources) ─────── */

    /**
     * The single source of truth for "what happened to stock" — every
     * purchase, sale, transfer, and adjustment shown as one plain-English
     * ledger instead of scattered across separate report screens. Sales
     * appear here as a movement only; deeper sales analytics (revenue,
     * margins, top customers, etc.) stay in the Sales module where they
     * belong.
     *
     * Reference numbers are resolved via SQL joins (not per-row lookups)
     * so this stays fast even with thousands of rows: at most one of the
     * three conditional joins can match a given movement, since each is
     * gated on source_type in its own ON clause.
     */
    public function movementsData(Request $request): JsonResponse
    {
        $locationId = (int) $request->query('location_id', 0);
        $productId  = (int) $request->query('product_id', 0);
        $type       = (string) $request->query('type', '');
        $dateFrom   = $request->query('date_from');
        $dateTo     = $request->query('date_to');

        $query = DB::table('stock_movements')
            ->join('products', 'products.id', '=', 'stock_movements.product_id')
            ->join('locations', 'locations.id', '=', 'stock_movements.location_id')
            ->leftJoin('purchases', function ($j) {
                $j->on('purchases.id', '=', 'stock_movements.source_id')
                    ->where('stock_movements.source_type', '=', StockMovement::SOURCE_PURCHASE);
            })
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->leftJoin('sales', function ($j) {
                $j->on('sales.id', '=', 'stock_movements.source_id')
                    ->where('stock_movements.source_type', '=', StockMovement::SOURCE_SALE);
            })
            ->leftJoin('customers', 'customers.id', '=', 'sales.customer_id')
            ->leftJoin('stock_transfers', function ($j) {
                $j->on('stock_transfers.id', '=', 'stock_movements.source_id')
                    ->where('stock_movements.source_type', '=', StockMovement::SOURCE_STOCK_TRANSFER);
            })
            ->leftJoin('locations as from_loc', 'from_loc.id', '=', 'stock_transfers.from_location_id')
            ->leftJoin('locations as to_loc', 'to_loc.id', '=', 'stock_transfers.to_location_id')
            ->leftJoin('users as creators', 'creators.id', '=', 'stock_movements.created_by')
            ->whereNull('stock_movements.deleted_at')
            ->select([
                'stock_movements.id',
                'stock_movements.movement_date',
                'stock_movements.created_at',
                'stock_movements.direction',
                'stock_movements.reason',
                'stock_movements.qty',
                'stock_movements.source_type',
                'stock_movements.source_id',
                'stock_movements.notes',
                'products.id as product_id',
                'products.title as product_title',
                'products.sku as product_sku',
                'locations.name as location_name',
                'creators.name as created_by_name',
                'suppliers.name as supplier_name',
                'suppliers.company_name as supplier_company_name',
                'customers.name as customer_name',
                'from_loc.name as from_location_name',
                'to_loc.name as to_location_name',
                DB::raw('COALESCE(purchases.invoice_number, sales.sale_number, stock_transfers.transfer_number) as reference_number'),
            ]);

        if ($locationId) {
            $query->where('stock_movements.location_id', $locationId);
        }
        if ($productId) {
            $query->where('stock_movements.product_id', $productId);
        }
        if ($dateFrom) {
            $query->whereDate('stock_movements.movement_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('stock_movements.movement_date', '<=', $dateTo);
        }

        match ($type) {
            'in'         => $query->where('stock_movements.direction', StockMovement::DIRECTION_IN),
            'out'        => $query->where('stock_movements.direction', StockMovement::DIRECTION_OUT),
            'purchase'   => $query->whereIn('stock_movements.reason', [
                StockMovement::REASON_PURCHASE, StockMovement::REASON_PURCHASE_CANCEL,
            ]),
            'sale'       => $query->whereIn('stock_movements.reason', [
                StockMovement::REASON_SALE, StockMovement::REASON_SALE_RETURN,
                StockMovement::REASON_SALE_CANCEL, StockMovement::REASON_SALE_EDIT_REVERSE,
            ]),
            'transfer'   => $query->whereIn('stock_movements.reason', [
                StockMovement::REASON_TRANSFER_IN, StockMovement::REASON_TRANSFER_OUT,
                StockMovement::REASON_TRANSFER_CANCEL_OUT,
            ]),
            'return'     => $query->where('stock_movements.reason', StockMovement::REASON_SALE_RETURN),
            'adjustment' => $query->whereIn('stock_movements.reason', [
                StockMovement::REASON_ADJUSTMENT_IN, StockMovement::REASON_ADJUSTMENT_OUT,
                StockMovement::REASON_OPENING,
            ]),
            default      => null,
        };

        $query->orderByDesc('stock_movements.created_at')->orderByDesc('stock_movements.id');

        return DataTables::query($query)
            ->addIndexColumn()
            ->addColumn('when_label', fn ($row) =>
                '<div class="fw-semibold movement-date">' . date('d M Y', strtotime($row->movement_date)) . '</div>'
                . '<small class="text-muted movement-time">' . date('h:i A', strtotime($row->created_at)) . '</small>'
            )
            ->addColumn('product_label', fn ($row) =>
                '<div class="d-flex align-items-center gap-2">'
                . '<span class="movement-thumb"><i class="ti ti-diamond"></i></span>'
                . '<div><div class="fw-semibold">' . e($row->product_title) . '</div>'
                . '<small class="text-muted">' . e($row->product_sku) . '</small></div>'
                . '</div>'
            )
            ->addColumn('movement_label', function ($row) {
                [$label, $badge, $icon] = $this->movementTypeMeta($row->reason);

                return '<span class="badge ' . $badge . ' d-inline-flex align-items-center gap-1"><i class="ti ' . $icon . ' fs-xs"></i>' . $label . '</span>';
            })
            ->addColumn('source_label', function ($row) {
                if ($row->source_type === StockMovement::SOURCE_PURCHASE) {
                    return e($row->supplier_company_name ?: ($row->supplier_name ?? '—'));
                }
                if ($row->source_type === StockMovement::SOURCE_SALE) {
                    return e($row->customer_name ?? 'Walk-in Customer');
                }
                if ($row->source_type === StockMovement::SOURCE_STOCK_TRANSFER) {
                    return e($row->from_location_name ?? '—') . ' <i class="ti ti-arrow-right fs-xs text-muted"></i> ' . e($row->to_location_name ?? '—');
                }

                return $row->notes
                    ? '<span title="' . e($row->notes) . '">' . e(Str::limit($row->notes, 35)) . '</span>'
                    : '<span class="text-muted">—</span>';
            })
            ->addColumn('reference_label', function ($row) {
                if ($row->reference_number) {
                    $url = match ($row->source_type) {
                        StockMovement::SOURCE_PURCHASE       => route('purchases.show', $row->source_id),
                        StockMovement::SOURCE_SALE           => route('sales.show', $row->source_id),
                        StockMovement::SOURCE_STOCK_TRANSFER => route('stock-transfers.show', $row->source_id),
                        default                               => null,
                    };
                    $icon = match ($row->source_type) {
                        StockMovement::SOURCE_PURCHASE       => 'ti-shopping-cart',
                        StockMovement::SOURCE_SALE           => 'ti-receipt',
                        StockMovement::SOURCE_STOCK_TRANSFER => 'ti-transfer',
                        default                               => 'ti-file',
                    };
                    if ($url) {
                        return '<a href="' . $url . '" class="d-inline-flex align-items-center gap-1 text-decoration-none">'
                            . '<i class="ti ' . $icon . ' fs-xs text-muted"></i>' . e($row->reference_number) . '</a>';
                    }
                    return e($row->reference_number);
                }

                return $row->notes
                    ? '<span class="text-muted" title="' . e($row->notes) . '">' . e(Str::limit($row->notes, 40)) . '</span>'
                    : '<span class="text-muted">—</span>';
            })
            ->addColumn('location_label', fn ($row) => e($row->location_name))
            ->addColumn('stock_in_label', fn ($row) =>
                $row->direction === StockMovement::DIRECTION_IN
                    ? '<span class="fw-semibold text-success movement-qty">+' . (int) $row->qty . '</span>'
                    : '<span class="text-muted movement-qty">—</span>'
            )
            ->addColumn('stock_out_label', fn ($row) =>
                $row->direction === StockMovement::DIRECTION_OUT
                    ? '<span class="fw-semibold text-danger movement-qty">−' . (int) $row->qty . '</span>'
                    : '<span class="text-muted movement-qty">—</span>'
            )
            ->addColumn('by_label', fn ($row) => e($row->created_by_name ?? '—'))
            ->filterColumn('product_label', function ($q, $keyword) {
                $like = "%{$keyword}%";
                $q->where(function ($qq) use ($like) {
                    $qq->where('products.title', 'like', $like)
                        ->orWhere('products.sku', 'like', $like)
                        ->orWhere('purchases.invoice_number', 'like', $like)
                        ->orWhere('sales.sale_number', 'like', $like)
                        ->orWhere('stock_transfers.transfer_number', 'like', $like);
                });
            })
            ->rawColumns([
                'when_label', 'product_label', 'movement_label', 'source_label',
                'reference_label', 'location_label', 'stock_in_label', 'stock_out_label',
            ])
            ->toJson();
    }

    /**
     * [label, badge class, icon] for a movement's specific reason — the
     * plain-English category shown in the Movement Type column. Distinct
     * from direction (Stock In/Out), which gets its own pair of columns.
     */
    private function movementTypeMeta(string $reason): array
    {
        return match ($reason) {
            StockMovement::REASON_PURCHASE            => ['Received', 'badge-soft-success', 'ti-package-import'],
            StockMovement::REASON_PURCHASE_CANCEL     => ['Removed', 'badge-soft-danger', 'ti-package-export'],
            StockMovement::REASON_SALE                => ['Sale', 'badge-soft-purple', 'ti-shopping-cart'],
            StockMovement::REASON_SALE_RETURN         => ['Return', 'badge-soft-success', 'ti-arrow-back-up'],
            StockMovement::REASON_SALE_CANCEL         => ['Sale Cancelled', 'badge-soft-success', 'ti-rotate'],
            StockMovement::REASON_SALE_EDIT_REVERSE   => ['Sale Edited', 'badge-soft-info', 'ti-edit'],
            StockMovement::REASON_TRANSFER_IN,
            StockMovement::REASON_TRANSFER_OUT        => ['Transfer', 'badge-soft-info', 'ti-transfer'],
            StockMovement::REASON_TRANSFER_CANCEL_OUT => ['Transfer Cancelled', 'badge-soft-warning', 'ti-transfer'],
            StockMovement::REASON_ADJUSTMENT_IN,
            StockMovement::REASON_ADJUSTMENT_OUT      => ['Adjustment', 'badge-soft-warning', 'ti-adjustments'],
            StockMovement::REASON_OPENING             => ['Opening Stock', 'badge-soft-secondary', 'ti-flag'],
            default                                    => [ucfirst(str_replace('_', ' ', $reason)), 'badge-soft-secondary', 'ti-dots'],
        };
    }

    /**
     * Lightweight product search for the Stock Movement product filter —
     * a plain Select2 AJAX lookup so the filter stays fast with large
     * catalogues instead of rendering a 500+ option <select>.
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        $q = Product::query()->select(['id', 'title', 'sku'])->orderBy('title')->limit(20);

        if ($term !== '') {
            $like = "%{$term}%";
            $q->where(function ($qq) use ($like) {
                $qq->where('title', 'like', $like)->orWhere('sku', 'like', $like);
            });
        }

        return response()->json([
            'ok'    => true,
            'items' => $q->get(),
        ]);
    }

    /* ─── Per-product ledger ──────────────────────────────── */

    public function product(Product $product, Request $request): View
    {
        $locationId = (int) $request->query('location_id', 0) ?: null;

        $movements = StockMovement::query()
            ->where('product_id', $product->id)
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->with(['location:id,name,location_code', 'purchaseProduct:id,barcode,carat_weight', 'creator:id,name'])
            ->orderBy('movement_date')
            ->orderBy('id')
            ->get();

        // Real per-movement CT — see StockService::caratForMovements() for
        // why this can't just be purchaseProduct->carat_weight (that's the
        // piece's static original weight, constant across every row).
        $caratByMovementId = $this->stock->caratForMovements($movements);

        // Compute running balance per piece+location so the ledger reads
        // naturally — each row shows the balance immediately after it.
        $balances = [];
        $rows     = [];
        foreach ($movements as $m) {
            $key = $m->purchase_product_id . ':' . $m->location_id;
            $balances[$key] = ($balances[$key] ?? 0) + $m->signedQty();

            $rows[] = [
                'movement'      => $m,
                'balance_after' => $balances[$key],
                'carat'         => $caratByMovementId[$m->id] ?? null,
            ];
        }

        // KPI summary computed from the (optionally location-filtered) movements.
        $summary = [
            'total_in'    => (int) $movements->where('direction', 'in')->sum('qty'),
            'total_out'   => (int) $movements->where('direction', 'out')->sum('qty'),
            'count'       => $movements->count(),
            'sold_qty'    => (int) $movements->where('reason', StockMovement::REASON_SALE)->sum('qty'),
            'purchased_qty' => (int) $movements->where('reason', StockMovement::REASON_PURCHASE)->sum('qty'),
        ];
        $summary['balance'] = $summary['total_in'] - $summary['total_out'];

        // Counts per category for filter tab badges.
        $summary['cat_purchase']   = $movements->whereIn('reason', [
            StockMovement::REASON_PURCHASE, StockMovement::REASON_PURCHASE_CANCEL,
        ])->count();
        $summary['cat_sale']       = $movements->whereIn('reason', [
            StockMovement::REASON_SALE, StockMovement::REASON_SALE_RETURN,
            StockMovement::REASON_SALE_CANCEL, StockMovement::REASON_SALE_EDIT_REVERSE,
        ])->count();
        $summary['cat_transfer']   = $movements->whereIn('reason', [
            StockMovement::REASON_TRANSFER_OUT, StockMovement::REASON_TRANSFER_IN,
            StockMovement::REASON_TRANSFER_CANCEL_OUT,
        ])->count();
        $summary['cat_adjustment'] = $movements->whereIn('reason', [
            StockMovement::REASON_ADJUSTMENT_IN, StockMovement::REASON_ADJUSTMENT_OUT,
            StockMovement::REASON_OPENING,
        ])->count();

        // Total on-hand (across all pieces) for the header.
        $onHand = $locationId
            ? $this->stock->onHandForProduct($product->id, $locationId)
            : $this->stock->onHandForProductGlobal($product->id);

        // Actual remaining CT for this product, from the CT ledger — NOT
        // on-hand qty × per-unit carat, since different units can carry
        // different individual weights. Null (not 0) when carat isn't
        // tracked at all.
        $onHandCt = $product->carat_weight !== null
            ? ($locationId
                ? $this->stock->remainingCaratForProduct($product->id, $locationId)
                : $this->stock->remainingCaratForProductGlobal($product->id))
            : null;

        $locations    = Location::active()->orderBy('name')->get(['id', 'name', 'location_code']);
        $sourceLabels = $this->buildSourceLabels($movements);

        return view('stock.product', compact('product', 'rows', 'summary', 'onHand', 'onHandCt', 'locations', 'locationId', 'sourceLabels'));
    }

    /* ─── Per-piece ledger ────────────────────────────────── */

    public function piece(PurchaseProduct $purchaseProduct): View
    {
        $purchaseProduct->load(['product', 'line.product', 'line.purchase']);

        $movements = StockMovement::query()
            ->where('purchase_product_id', $purchaseProduct->id)
            ->with(['location:id,name,location_code', 'creator:id,name'])
            ->orderBy('movement_date')
            ->orderBy('id')
            ->get();

        // Running balance per location for this piece.
        $balances = [];
        $rows     = [];
        foreach ($movements as $m) {
            $key = (int) $m->location_id;
            $balances[$key] = ($balances[$key] ?? 0) + $m->signedQty();
            $rows[] = ['movement' => $m, 'balance_after' => $balances[$key]];
        }

        $byLocation   = $this->stock->onHandForPieceByLocation($purchaseProduct->id);
        // Real per-location CT balance from the ledger — NOT
        // balance × carat_weight, since this piece's own recorded
        // weight may not be what's individually left after partial
        // sales/transfers.
        $caratByLocation      = $this->stock->remainingCaratForPieceByLocation($purchaseProduct->id);
        $totalRemainingCarat  = $this->stock->remainingCaratForPieceGlobal($purchaseProduct->id);
        $sourceLabels = $this->buildSourceLabels($movements);

        return view('stock.piece', compact('purchaseProduct', 'rows', 'byLocation', 'caratByLocation', 'totalRemainingCarat', 'sourceLabels'));
    }

    /* ─── Helpers ─────────────────────────────────────────── */

    /**
     * Build lookup arrays [source_type => [id => document_number]] so
     * ledger views can render clickable links without N+1 queries.
     */
    private function buildSourceLabels(Collection $movements): array
    {
        $purchaseIds = $movements
            ->where('source_type', StockMovement::SOURCE_PURCHASE)
            ->pluck('source_id')->unique()->filter()->values();

        $saleIds = $movements
            ->where('source_type', StockMovement::SOURCE_SALE)
            ->pluck('source_id')->unique()->filter()->values();

        $transferIds = $movements
            ->where('source_type', StockMovement::SOURCE_STOCK_TRANSFER)
            ->pluck('source_id')->unique()->filter()->values();

        return [
            'purchase'       => $purchaseIds->isNotEmpty()
                ? Purchase::withTrashed()->whereIn('id', $purchaseIds)->pluck('invoice_number', 'id')
                : collect(),
            'sale'           => $saleIds->isNotEmpty()
                ? Sale::withTrashed()->whereIn('id', $saleIds)->pluck('sale_number', 'id')
                : collect(),
            'stock_transfer' => $transferIds->isNotEmpty()
                ? StockTransfer::withTrashed()->whereIn('id', $transferIds)->pluck('transfer_number', 'id')
                : collect(),
        ];
    }
}
