<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseProduct;
use App\Models\StockMovement;
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
            'locations' => Location::active()->orderBy('name')->get(['id', 'location_code', 'name', 'is_default']),
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

        // Subquery aggregating in/out by product+location; then join to
        // products and locations for labels.
        $signedSql = "SUM(CASE WHEN stock_movements.direction = 'in' THEN stock_movements.qty "
            . "ELSE -stock_movements.qty END)";

        $base = DB::table('stock_movements')
            ->join('products',  'products.id',  '=', 'stock_movements.product_id')
            ->join('locations', 'locations.id', '=', 'stock_movements.location_id')
            ->whereNull('stock_movements.deleted_at')
            ->groupBy('stock_movements.product_id', 'stock_movements.location_id', 'products.title', 'products.sku', 'locations.name', 'locations.location_code')
            ->select([
                'stock_movements.product_id',
                'stock_movements.location_id',
                'products.title  as product_title',
                'products.sku    as product_sku',
                'locations.name  as location_name',
                'locations.location_code',
                DB::raw($signedSql . ' as on_hand'),
            ])
            ->havingRaw($signedSql . ' <> 0');

        if ($locationId) {
            $base->where('stock_movements.location_id', $locationId);
        }

        return DataTables::query($base)
            ->editColumn('on_hand', fn ($row) =>
                '<span class="fw-semibold ' . ((int) $row->on_hand <= 0 ? 'text-danger' : '') . '">'
                . (int) $row->on_hand . '</span>'
            )
            ->addColumn('product_label', fn ($row) =>
                '<div class="fw-semibold">' . e($row->product_title) . '</div>'
                . '<small class="text-muted">SKU: ' . e($row->product_sku) . '</small>'
            )
            ->addColumn('location_label', fn ($row) =>
                e($row->location_name) . ' <small class="text-muted">(' . e($row->location_code) . ')</small>'
            )
            ->addColumn('action', function ($row) {
                $url = route('stock.product', ['product' => $row->product_id]);
                return '<a href="' . $url . '?location_id=' . (int) $row->location_id . '" class="btn btn-default btn-icon btn-sm" title="Ledger"><i class="ti ti-history fs-lg"></i></a>';
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

    /* ─── Per-product ledger ──────────────────────────────── */

    public function product(Product $product, Request $request): View
    {
        $locationId = (int) $request->query('location_id', 0) ?: null;

        $movements = StockMovement::query()
            ->where('product_id', $product->id)
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->with(['location:id,name,location_code', 'purchaseProduct:id,barcode', 'creator:id,name'])
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

        // Total on-hand (across all pieces) for the header.
        $onHand = $locationId
            ? $this->stock->onHandForProduct($product->id, $locationId)
            : null;

        $locations = Location::active()->orderBy('name')->get(['id', 'name', 'location_code']);

        return view('stock.product', compact('product', 'rows', 'onHand', 'locations', 'locationId'));
    }

    /* ─── Per-piece ledger ────────────────────────────────── */

    public function piece(PurchaseProduct $purchaseProduct): View
    {
        $purchaseProduct->load(['line.product', 'line.purchase']);

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

        $byLocation = $this->stock->onHandForPieceByLocation($purchaseProduct->id);

        return view('stock.piece', compact('purchaseProduct', 'rows', 'byLocation'));
    }
}
