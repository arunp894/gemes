<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockTransferRequest;
use App\Http\Requests\UpdateStockTransferRequest;
use App\Models\Barcode;
use App\Models\Location;
use App\Models\PurchaseLine;
use App\Models\PurchaseProduct;
use App\Models\Rack;
use App\Models\StockTransfer;
use App\Repositories\StockTransferRepository;
use App\Services\StockService;
use App\Services\StockTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class StockTransferController extends Controller
{
    public function __construct(
        private StockTransferService    $service,
        private StockTransferRepository $repo,
        private StockService            $stock,
    ) {}

    /* ─── List ─────────────────────────────────────────────── */

    public function index(): View
    {
        // Single aggregate query instead of separate COUNT() round-trips per card.
        $counts = StockTransfer::selectRaw(
            "COUNT(*) as total,
             SUM(status = 'draft') as draft,
             SUM(status = 'in_transit') as in_transit,
             SUM(status = 'received') as received,
             SUM(status = 'cancelled') as cancelled"
        )->first();

        $stats = [
            'total'      => (int) $counts->total,
            'draft'      => (int) $counts->draft,
            'in_transit' => (int) $counts->in_transit,
            'received'   => (int) $counts->received,
            'cancelled'  => (int) $counts->cancelled,
        ];

        return view('stock-transfers.index', compact('stats'));
    }

    public function data(Request $request): JsonResponse
    {
        $q = $this->repo->query();

        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }
        if ($from = $request->query('date_from')) {
            $q->whereDate('transfer_date', '>=', $from);
        }
        if ($to = $request->query('date_to')) {
            $q->whereDate('transfer_date', '<=', $to);
        }

        return DataTables::eloquent($q)
            ->addIndexColumn()
            ->editColumn('transfer_number', fn (StockTransfer $t) =>
                '<a href="' . route('stock-transfers.show', $t) . '" class="link-reset"><code>' . e($t->transfer_number) . '</code></a>'
            )
            ->editColumn('transfer_date', fn (StockTransfer $t) => optional($t->transfer_date)->format('d M Y'))
            ->addColumn('from_label', fn (StockTransfer $t) =>
                $t->fromLocation ? e($t->fromLocation->name) : '—'
            )
            ->addColumn('to_label', fn (StockTransfer $t) =>
                $t->toLocation ? e($t->toLocation->name) : '—'
            )
            ->addColumn('line_count', fn (StockTransfer $t) => $t->lines_count)
            ->addColumn('status_badge', function (StockTransfer $t) {
                $map = [
                    StockTransfer::STATUS_DRAFT      => 'status-draft',
                    StockTransfer::STATUS_IN_TRANSIT => 'status-transit',
                    StockTransfer::STATUS_RECEIVED   => 'status-received',
                    StockTransfer::STATUS_CANCELLED  => 'status-cancelled',
                ];
                $class = 'status-pill ' . ($map[$t->status] ?? 'status-draft');
                return '<span class="' . $class . '"><span class="status-dot"></span>' . e($t->statusLabel()) . '</span>';
            })
            ->addColumn('actions', function (StockTransfer $t) {
                $canEdit = auth()->user()?->hasPermission('stock-transfers.edit') ?? false;
                $canPost = auth()->user()?->hasPermission('stock-transfers.post') ?? false;
                $canDel  = auth()->user()?->hasPermission('stock-transfers.delete') ?? false;

                $html = '<div class="d-flex justify-content-center gap-1">';
                $html .= '<a href="' . route('stock-transfers.show', $t) . '" class="action-btn action-view" title="View"><i class="ti ti-eye"></i></a>';
                if ($canEdit && $t->isEditable()) {
                    $html .= '<a href="' . route('stock-transfers.edit', $t) . '" class="action-btn action-edit" title="Edit"><i class="ti ti-edit"></i></a>';
                }
                if ($canPost && $t->isDraft()) {
                    $html .= '<button type="button" class="action-btn action-post js-status-action" data-kind="post" data-url="' . route('stock-transfers.post', $t) . '" data-confirm="Post transfer? Stock will leave the source location." title="Post"><i class="ti ti-send"></i></button>';
                }
                if ($canPost && $t->isInTransit()) {
                    $html .= '<button type="button" class="action-btn action-receive js-status-action" data-kind="receive" data-url="' . route('stock-transfers.receive', $t) . '" data-confirm="Mark transfer as received? Stock will arrive at the destination." title="Receive"><i class="ti ti-check"></i></button>';
                }
                if ($canDel && ($t->isDraft() || $t->isInTransit())) {
                    $html .= '<button type="button" class="action-btn action-cancel js-status-action" data-kind="cancel" data-url="' . route('stock-transfers.cancel', $t) . '" data-confirm="Cancel this transfer? In-transit stock will return to the source." title="Cancel"><i class="ti ti-ban"></i></button>';
                }
                $html .= '</div>';
                return $html;
            })
            ->rawColumns(['transfer_number', 'from_label', 'to_label', 'status_badge', 'actions'])
            ->toJson();
    }

    /* ─── Create / Store ──────────────────────────────────── */

    public function create(): View
    {
        return view('stock-transfers.create', [
            'locations' => Location::active()->orderBy('name')->get(['id', 'location_code', 'name', 'is_default']),
            'racks'     => Rack::query()->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(StoreStockTransferRequest $request): JsonResponse
    {
        try {
            $transfer = $this->service->create($request->validated());
            return response()->json([
                'ok'       => true,
                'message'  => 'Transfer saved.',
                'transfer' => $transfer,
                'redirect' => route('stock-transfers.show', $transfer),
            ], 201);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* ─── Show / Edit / Update ────────────────────────────── */

    public function show(StockTransfer $stockTransfer): View
    {
        $transfer = $this->repo->find($stockTransfer->id);
        return view('stock-transfers.show', compact('transfer'));
    }

    public function edit(StockTransfer $stockTransfer): View
    {
        abort_unless($stockTransfer->isEditable(), 403, 'Only draft transfers can be edited.');

        $transfer = $this->repo->find($stockTransfer->id);

        // Pre-shape lines into what the create screen's Vue form already
        // knows how to consume. on_hand is a placeholder here; the Vue
        // app refreshes it for real once mounted (see
        // refreshOnHandForExistingLines()).
        $linesPayload = $transfer->lines->map(function ($l) {
            return [
                'is_group'             => false,
                'purchase_product_id'  => $l->purchase_product_id,
                'product_id'           => $l->product_id,
                'product_title'        => optional($l->product)->title,
                'product_sku'          => optional($l->product)->sku,
                'barcode'              => $l->purchaseProduct?->barcode,
                'lot_code'             => $l->purchaseProduct?->lot_code,
                'qty'                  => (int) $l->qty,
                'carat_weight'         => $l->carat_weight ?? optional($l->purchaseProduct)->carat_weight,
                'piece_carat_weight'   => optional($l->purchaseProduct)->carat_weight,
                'to_rack_id'           => $l->to_rack_id,
                'notes'                => $l->notes,
                'on_hand'              => 9999,
                // Placeholder, same as on_hand above — the Vue app
                // refreshes this for real once mounted (see
                // refreshOnHandForExistingLines()), scoped to the
                // transfer's actual from_location_id.
                'remaining_carat_before' => null,
            ];
        })->values();

        return view('stock-transfers.edit', [
            'transfer'     => $transfer,
            'linesPayload' => $linesPayload,
            'locations'    => Location::active()->orderBy('name')->get(['id', 'location_code', 'name', 'is_default']),
            'racks'        => Rack::query()->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function update(UpdateStockTransferRequest $request, StockTransfer $stockTransfer): JsonResponse
    {
        try {
            $transfer = $this->service->update($stockTransfer, $request->validated());
            return response()->json([
                'ok'       => true,
                'message'  => 'Transfer updated.',
                'transfer' => $transfer,
                'redirect' => route('stock-transfers.show', $transfer),
            ]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy(StockTransfer $stockTransfer): JsonResponse
    {
        try {
            $this->service->delete($stockTransfer);
            return response()->json(['ok' => true, 'message' => 'Transfer deleted.']);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* ─── Status transitions ──────────────────────────────── */

    public function post(StockTransfer $stockTransfer): JsonResponse
    {
        try {
            $transfer = $this->service->post($stockTransfer);
            return response()->json(['ok' => true, 'message' => 'Transfer posted.', 'transfer' => $transfer]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function receive(StockTransfer $stockTransfer): JsonResponse
    {
        try {
            $transfer = $this->service->receive($stockTransfer);
            return response()->json(['ok' => true, 'message' => 'Transfer received.', 'transfer' => $transfer]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function cancel(StockTransfer $stockTransfer): JsonResponse
    {
        try {
            $transfer = $this->service->cancel($stockTransfer);
            return response()->json(['ok' => true, 'message' => 'Transfer cancelled.', 'transfer' => $transfer]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* ─── Helpers used by the transfer create form ────────── */

    /**
     * Barcode lookup constrained to "what's currently at this source
     * location". A barcode can match MULTIPLE purchase_products rows --
     * it's the receiving-dock scan value captured at purchase entry, not
     * the unique per-product retail barcode, so every row generated from
     * the same box (or piece) line commonly shares one. Returns the
     * aggregate on-hand across every matching row plus the line's type,
     * so the create/edit form can offer a quantity for box groups instead
     * of always adding a single unit.
     */
    public function lookupByBarcode(Request $request): JsonResponse
    {
        $value          = trim((string) $request->query('barcode', ''));
        $fromLocationId = (int) $request->query('from_location_id', 0);

        if ($value === '' || $fromLocationId <= 0) {
            return response()->json(['ok' => false, 'message' => 'Barcode and source location are required.'], 422);
        }

        $pieces = PurchaseProduct::with([
                'product:id,title,sku,website_enabled',
                'line:id,title,type,product_id',
                'line.product:id,title,sku,website_enabled',
            ])
            ->where('barcode', $value)
            ->get()
            ->filter(fn (PurchaseProduct $pp) => $pp->resolved_product !== null)
            ->values();

        if ($pieces->isEmpty()) {
            // Fallback to the product-level barcode mapping. This won't
            // know the specific piece, so transfer can't proceed via this
            // path alone (transfers require per-piece). Surface the issue.
            $bc = Barcode::with('product:id,title,sku')->where('barcode_value', $value)->first();
            if ($bc && $bc->product) {
                return response()->json([
                    'ok'      => false,
                    'message' => "Barcode '{$value}' is registered to {$bc->product->title} but no specific inventory unit matches. Use a piece-level barcode.",
                ], 404);
            }
            return response()->json(['ok' => false, 'message' => "No piece found for barcode '{$value}'."], 404);
        }

        $available = $this->stock->availablePiecesForBarcode($value, $fromLocationId);
        $onHand    = array_sum($available);
        $first     = $pieces->first();

        // Website-listed pieces are locked to their current location —
        // transferring them would drift from what the site implies is
        // available there. Disable the listing first to move it.
        if ($first->resolved_product->website_enabled) {
            return response()->json([
                'ok'      => false,
                'message' => "{$first->resolved_product->title} is enabled for website sale and can't be transferred. Disable it first.",
            ], 404);
        }

        if ($onHand <= 0) {
            return response()->json([
                'ok'      => false,
                'message' => "{$first->resolved_product->title} has no stock at the selected source location.",
            ], 404);
        }

        return response()->json([
            'ok'    => true,
            'piece' => [
                'purchase_product_id' => $first->id,
                'product_id'          => $first->resolved_product->id,
                'product_title'       => $first->resolved_product->title,
                'product_sku'         => $first->resolved_product->sku,
                'barcode'             => $value,
                'type'                => $first->line->type ?? PurchaseLine::TYPE_PIECE,
                'carat_weight'        => $first->carat_weight,
                'remaining_carat'     => $first->carat_weight !== null
                    ? $this->stock->remainingCaratForPiece((int) $first->id, $fromLocationId)
                    : null,
                'on_hand'             => $onHand,
                // FIFO-ordered breakdown of every distinct row sharing this
                // barcode with stock at the source location. The create/
                // edit form walks this client-side once the user picks how
                // many to take -- the server re-validates per-row on-hand
                // again at post() regardless, so this list is a UX
                // convenience, not the safety boundary.
                'pieces' => collect($available)->map(fn ($bal, $ppId) => [
                    'purchase_product_id' => (int) $ppId,
                    'balance'             => (int) $bal,
                ])->values(),
            ],
        ]);
    }

    /**
     * Text search for the create/edit form's picker: matches lot_code,
     * barcode, or product title/SKU.
     */
    public function searchPieces(Request $request): JsonResponse
    {
        $locationId = (int) $request->query('from_location_id', 0);
        $search     = trim((string) $request->query('search', ''));

        if ($locationId <= 0) {
            return response()->json(['ok' => false, 'message' => 'Source location is required.'], 422);
        }

        $q = PurchaseProduct::query()
            ->with(['product:id,title,sku,website_enabled', 'line.category', 'line.product:id,title,sku,website_enabled']);

        if ($search !== '') {
            $q->where(function ($qq) use ($search) {
                $qq->where('lot_code', 'like', "%{$search}%")
                   ->orWhere('barcode', 'like', "%{$search}%")
                   ->orWhereHas('product', function ($pq) use ($search) {
                       $pq->where('title', 'like', "%{$search}%")
                          ->orWhere('sku', 'like', "%{$search}%");
                   });
            });
        }

        $rows = $q->orderByDesc('id')->limit(50)->get();

        $items = $rows->map(function (PurchaseProduct $row) use ($locationId) {
            $onHand = $this->stock->onHandForPiece($row->id, $locationId);
            if ($onHand <= 0) {
                return null;
            }

            $product = $row->resolved_product;

            // Website-listed pieces are locked to their current location —
            // transferring them would drift from what the site implies is
            // available there. Disable the listing first to move it.
            if ($product?->website_enabled) {
                return null;
            }

            return [
                'purchase_product_id' => $row->id,
                'lot_code'            => $row->lot_code,
                'barcode'             => $row->barcode,
                'product_id'          => $product?->id,
                'product_title'       => $product?->title,
                'product_sku'         => $product?->sku,
                'category'            => $row->line?->category?->name,
                'stone_type'          => $row->line?->stone_type,
                'carat_weight'        => $row->carat_weight,
                // Actual remaining CT at this location, from the ledger —
                // NOT on_hand × carat_weight (units can carry different
                // individual weights).
                'remaining_carat'     => $row->carat_weight !== null
                    ? $this->stock->remainingCaratForPiece($row->id, $locationId)
                    : null,
                'on_hand'             => $onHand,
            ];
        })->filter()->values();

        return response()->json(['ok' => true, 'items' => $items]);
    }
}
