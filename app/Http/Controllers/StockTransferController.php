<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockTransferRequest;
use App\Http\Requests\UpdateStockTransferRequest;
use App\Models\Barcode;
use App\Models\Location;
use App\Models\PurchaseProduct;
use App\Models\Rack;
use App\Models\StockMovement;
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
        return view('stock-transfers.index');
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
            ->addColumn('line_count', fn (StockTransfer $t) => $t->lines()->count())
            ->addColumn('status_badge', fn (StockTransfer $t) =>
                '<span class="badge ' . $t->statusBadgeClass() . ' fs-xxs">' . e($t->statusLabel()) . '</span>'
            )
            ->addColumn('actions', function (StockTransfer $t) {
                $canEdit = auth()->user()?->hasPermission('stock-transfers.edit') ?? false;
                $canPost = auth()->user()?->hasPermission('stock-transfers.post') ?? false;
                $canDel  = auth()->user()?->hasPermission('stock-transfers.delete') ?? false;

                $html = '<div class="d-flex gap-1 justify-content-center">';
                $html .= '<a href="' . route('stock-transfers.show', $t) . '" class="btn btn-default btn-icon btn-sm" title="View"><i class="ti ti-eye fs-lg"></i></a>';
                if ($canEdit && $t->isEditable()) {
                    $html .= '<a href="' . route('stock-transfers.edit', $t) . '" class="btn btn-default btn-icon btn-sm" title="Edit"><i class="ti ti-edit fs-lg"></i></a>';
                }
                if ($canPost && $t->isDraft()) {
                    $html .= '<button type="button" class="btn btn-default btn-icon btn-sm js-status-action text-info" data-url="' . route('stock-transfers.post', $t) . '" data-confirm="Post transfer? Stock will leave the source location." title="Post"><i class="ti ti-send fs-lg"></i></button>';
                }
                if ($canPost && $t->isInTransit()) {
                    $html .= '<button type="button" class="btn btn-default btn-icon btn-sm js-status-action text-success" data-url="' . route('stock-transfers.receive', $t) . '" data-confirm="Mark transfer as received? Stock will arrive at the destination." title="Receive"><i class="ti ti-check fs-lg"></i></button>';
                }
                if ($canDel && ($t->isDraft() || $t->isInTransit())) {
                    $html .= '<button type="button" class="btn btn-default btn-icon btn-sm js-status-action text-danger" data-url="' . route('stock-transfers.cancel', $t) . '" data-confirm="Cancel this transfer? In-transit stock will return to the source." title="Cancel"><i class="ti ti-ban fs-lg"></i></button>';
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

        return view('stock-transfers.edit', [
            'transfer'  => $this->repo->find($stockTransfer->id),
            'locations' => Location::active()->orderBy('name')->get(['id', 'location_code', 'name', 'is_default']),
            'racks'     => Rack::query()->orderBy('name')->get(['id', 'code', 'name']),
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
     * location". Returns the piece + its on-hand balance so the UI can
     * cap the qty input and refuse zero-stock scans.
     */
    public function lookupByBarcode(Request $request): JsonResponse
    {
        $value          = trim((string) $request->query('barcode', ''));
        $fromLocationId = (int) $request->query('from_location_id', 0);

        if ($value === '' || $fromLocationId <= 0) {
            return response()->json(['ok' => false, 'message' => 'Barcode and source location are required.'], 422);
        }

        $pp = PurchaseProduct::with(['line.product:id,title,sku'])
            ->where('barcode', $value)
            ->first();

        if (! $pp || ! $pp->line || ! $pp->line->product) {
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

        $onHand = $this->stock->onHandForPiece((int) $pp->id, $fromLocationId);
        if ($onHand <= 0) {
            return response()->json([
                'ok'      => false,
                'message' => "{$pp->line->product->title} has no stock at the selected source location.",
            ], 404);
        }

        return response()->json([
            'ok'       => true,
            'piece'    => [
                'purchase_product_id' => $pp->id,
                'product_id'          => $pp->line->product->id,
                'product_title'       => $pp->line->product->title,
                'product_sku'         => $pp->line->product->sku,
                'barcode'             => $pp->barcode,
                'on_hand'             => $onHand,
            ],
        ]);
    }
}
