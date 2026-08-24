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
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    public function __construct(private StockService $stock) {}

    /* ─── On-hand summary ─────────────────────────────────── */

    public function index(Request $request): View
    {
        return view('stock.index', [
            'locations'  => Location::active()->orderBy('name')->get(['id', 'location_code', 'name', 'is_default']),
            'categories' => Category::active()->ordered()->get(['id', 'name']),
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

        $base = DB::table('stock_movements')
            ->join('products',      'products.id',      '=', 'stock_movements.product_id')
            ->join('locations',     'locations.id',      '=', 'stock_movements.location_id')
            ->leftJoin('categories', 'categories.id',     '=', 'products.category_id')
            ->whereNull('stock_movements.deleted_at')
            ->groupBy(
                'stock_movements.product_id', 'stock_movements.location_id',
                'products.title', 'products.sku', 'products.category_id',
                'categories.name', 'locations.name', 'locations.location_code'
            )
            ->select([
                'stock_movements.product_id',
                'stock_movements.location_id',
                'products.title       as product_title',
                'products.sku         as product_sku',
                'products.category_id as category_id',
                'categories.name      as category_name',
                'locations.name       as location_name',
                'locations.location_code',
                DB::raw($signedSql . ' as on_hand'),
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
                return '<a href="' . $urlLoc . '" class="btn btn-soft-primary btn-sm d-inline-flex align-items-center gap-1" title="View Ledger">'
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

        // Per-product balance first -- matches the on-hand table's own
        // math -- then rolled up to category, so a product sitting at
        // exactly zero doesn't count toward that category's product count.
        $perProduct = DB::table('stock_movements')
            ->join('products', 'products.id', '=', 'stock_movements.product_id')
            ->whereNull('stock_movements.deleted_at')
            ->when($locationId, fn ($q) => $q->where('stock_movements.location_id', $locationId))
            ->groupBy('products.id', 'products.category_id')
            ->select([
                'products.id          as product_id',
                'products.category_id as category_id',
                DB::raw($signedSql . ' as on_hand'),
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
            ])
            ->orderByDesc('on_hand')
            ->get();

        return response()->json(['ok' => true, 'categories' => $rows]);
    }

    /* ─── Sales report ────────────────────────────── */

    /**
     * KPI strip for the Sales Report tab: total qty sold, distinct
     * products sold, and distinct sale invoices touched — respecting
     * the same location/category filters as salesData(). Uses the same
     * reason=sale definition as the "sold_qty" KPI already shown on the
     * per-product ledger (product(), above) so the two screens agree.
     */
    public function salesSummary(Request $request): JsonResponse
    {
        $locationId = (int) $request->query('location_id', 0);
        $categoryId = (int) $request->query('category_id', 0);

        $q = DB::table('stock_movements')
            ->join('products', 'products.id', '=', 'stock_movements.product_id')
            ->where('stock_movements.reason', StockMovement::REASON_SALE)
            ->whereNull('stock_movements.deleted_at');

        if ($locationId) {
            $q->where('stock_movements.location_id', $locationId);
        }
        if ($categoryId) {
            $q->where('products.category_id', $categoryId);
        }

        $row = $q->selectRaw(
            'SUM(stock_movements.qty) as qty_sold, '
            . 'COUNT(DISTINCT stock_movements.product_id) as products_sold, '
            . 'COUNT(DISTINCT stock_movements.source_id) as sales_count'
        )->first();

        return response()->json([
            'ok'            => true,
            'qty_sold'      => (int) ($row->qty_sold ?? 0),
            'products_sold' => (int) ($row->products_sold ?? 0),
            'sales_count'   => (int) ($row->sales_count ?? 0),
        ]);
    }

    /**
     * DataTables-friendly sold-qty-by-product-per-location feed — the
     * Sales Report counterpart to data(). Same grouping shape as the
     * on-hand table, but summed over OUT movements with reason=sale
     * instead of the running in/out balance.
     */
    public function salesData(Request $request): JsonResponse
    {
        $locationId = (int) $request->query('location_id', 0);
        $categoryId = (int) $request->query('category_id', 0);

        $base = DB::table('stock_movements')
            ->join('products',      'products.id',      '=', 'stock_movements.product_id')
            ->join('locations',     'locations.id',      '=', 'stock_movements.location_id')
            ->leftJoin('categories', 'categories.id',     '=', 'products.category_id')
            ->where('stock_movements.reason', StockMovement::REASON_SALE)
            ->whereNull('stock_movements.deleted_at')
            ->groupBy(
                'stock_movements.product_id', 'stock_movements.location_id',
                'products.title', 'products.sku', 'products.category_id',
                'categories.name', 'locations.name', 'locations.location_code'
            )
            ->select([
                'stock_movements.product_id',
                'stock_movements.location_id',
                'products.title       as product_title',
                'products.sku         as product_sku',
                'products.category_id as category_id',
                'categories.name      as category_name',
                'locations.name       as location_name',
                'locations.location_code',
                DB::raw('SUM(stock_movements.qty) as qty_sold'),
                DB::raw('COUNT(DISTINCT stock_movements.source_id) as sales_count'),
                DB::raw('MAX(stock_movements.movement_date) as last_sale_date'),
            ]);

        if ($locationId) {
            $base->where('stock_movements.location_id', $locationId);
        }
        if ($categoryId) {
            $base->where('products.category_id', $categoryId);
        }

        return DataTables::query($base)
            ->addIndexColumn()
            ->editColumn('qty_sold', fn ($row) =>
                '<span class="fw-semibold text-danger">' . (int) $row->qty_sold . '</span>'
            )
            ->editColumn('last_sale_date', fn ($row) =>
                $row->last_sale_date ? date('d M Y', strtotime($row->last_sale_date)) : '—'
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
                return '<a href="' . $urlLoc . '" class="btn btn-soft-danger btn-sm d-inline-flex align-items-center gap-1" title="View Sales in Ledger">'
                    . '<i class="ti ti-receipt fs-sm"></i> Ledger</a>';
            })
            ->filterColumn('product_label', function ($q, $keyword) {
                $like = "%{$keyword}%";
                $q->where(function ($qq) use ($like) {
                    $qq->where('products.title', 'like', $like)
                        ->orWhere('products.sku', 'like', $like);
                });
            })
            ->rawColumns(['qty_sold', 'product_label', 'location_label', 'action'])
            ->toJson();
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

        // Compute running balance per piece+location so the ledger reads
        // naturally — each row shows the balance immediately after it.
        $balances = [];
        $rows     = [];
        foreach ($movements as $m) {
            $key = $m->purchase_product_id . ':' . $m->location_id;
            $balances[$key] = ($balances[$key] ?? 0) + $m->signedQty();
            $rows[] = ['movement' => $m, 'balance_after' => $balances[$key]];
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

        $locations    = Location::active()->orderBy('name')->get(['id', 'name', 'location_code']);
        $sourceLabels = $this->buildSourceLabels($movements);

        return view('stock.product', compact('product', 'rows', 'summary', 'onHand', 'locations', 'locationId', 'sourceLabels'));
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
        $sourceLabels = $this->buildSourceLabels($movements);

        return view('stock.piece', compact('purchaseProduct', 'rows', 'byLocation', 'sourceLabels'));
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
