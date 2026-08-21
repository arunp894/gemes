<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePackingRequest;
use App\Http\Requests\UpdatePackingRequest;
use App\Models\Location;
use App\Models\Packing;
use App\Models\PurchaseProduct;
use App\Models\Rack;
use App\Repositories\PackingRepository;
use App\Services\PackingService;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class PackingController extends Controller
{
    public function __construct(
        private PackingService    $service,
        private PackingRepository $repo,
        private StockService      $stock,
    ) {}

    /* ─── List ─────────────────────────────────────────────── */

    public function index(): View
    {
        return view('packings.index');
    }

    public function data(Request $request): JsonResponse
    {
        $q = $this->repo->query();

        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }
        if ($from = $request->query('date_from')) {
            $q->whereDate('packing_date', '>=', $from);
        }
        if ($to = $request->query('date_to')) {
            $q->whereDate('packing_date', '<=', $to);
        }

        return DataTables::eloquent($q)
            ->addIndexColumn()
            ->editColumn('packing_number', fn (Packing $p) =>
                '<a href="' . route('packings.show', $p) . '" class="link-reset"><code>' . e($p->packing_number) . '</code></a>'
            )
            ->editColumn('packing_date', fn (Packing $p) => optional($p->packing_date)->format('d M Y'))
            ->addColumn('location_label', fn (Packing $p) => $p->location ? e($p->location->name) : '—')
            ->addColumn('output_count', fn (Packing $p) => $p->outputs()->count())
            ->addColumn('status_badge', fn (Packing $p) =>
                '<span class="badge ' . $p->statusBadgeClass() . ' fs-xxs">' . e($p->statusLabel()) . '</span>'
            )
            ->addColumn('actions', function (Packing $p) {
                $canEdit = auth()->user()?->hasPermission('packings.edit')   ?? false;
                $canPost = auth()->user()?->hasPermission('packings.post')  ?? false;
                $canDel  = auth()->user()?->hasPermission('packings.delete') ?? false;

                $html = '<div class="d-flex gap-1 justify-content-center">';
                $html .= '<a href="' . route('packings.show', $p) . '" class="btn btn-default btn-icon btn-sm" title="View"><i class="ti ti-eye fs-lg"></i></a>';
                if ($canEdit && $p->isEditable()) {
                    $html .= '<a href="' . route('packings.edit', $p) . '" class="btn btn-default btn-icon btn-sm" title="Edit"><i class="ti ti-edit fs-lg"></i></a>';
                }
                if ($canPost && $p->isDraft()) {
                    $html .= '<button type="button" class="btn btn-default btn-icon btn-sm js-status-action text-success" data-url="' . route('packings.post', $p) . '" data-confirm="Post this packing? The raw stock will be consumed and the new products credited." title="Post"><i class="ti ti-send fs-lg"></i></button>';
                }
                if ($canDel && ! $p->isCancelled()) {
                    $label   = $p->isDraft() ? 'Delete' : 'Cancel';
                    $confirm = $p->isDraft()
                        ? 'Delete this draft packing?'
                        : 'Cancel this packing? Raw stock will be restored and the packed products removed from stock.';
                    $html .= '<button type="button" class="btn btn-default btn-icon btn-sm js-status-action text-danger" data-url="' . route('packings.cancel', $p) . '" data-confirm="' . e($confirm) . '" title="' . $label . '"><i class="ti ti-ban fs-lg"></i></button>';
                }
                $html .= '</div>';
                return $html;
            })
            ->rawColumns(['packing_number', 'status_badge', 'actions'])
            ->toJson();
    }

    /* ─── Create / Store ──────────────────────────────────── */

    public function create(): View
    {
        return view('packings.create', [
            'locations' => Location::active()->orderBy('name')->get(['id', 'location_code', 'name', 'is_default']),
        ]);
    }

    public function store(StorePackingRequest $request): JsonResponse
    {
        try {
            $packing = $this->service->create($request->validated());
            return response()->json([
                'ok'       => true,
                'message'  => 'Packing saved.',
                'packing'  => $packing,
                'redirect' => route('packings.show', $packing),
            ], 201);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* ─── Show / Edit / Update ────────────────────────────── */

    public function show(Packing $packing): View
    {
        return view('packings.show', [
            'packing' => $this->repo->find($packing->id),
        ]);
    }

    public function edit(Packing $packing): View|RedirectResponse
    {
        if (! $packing->isEditable()) {
            return redirect()->route('packings.show', $packing)->with('error', 'Only draft packings can be edited.');
        }

        $packing = $this->repo->find($packing->id);

        // Pre-shape sources into exactly the JSON the create screen's Vue
        // form already knows how to consume -- on_hand is computed fresh
        // per row rather than trusted from any stored value, since a
        // draft packing's own qty_taken was never posted to the ledger
        // (see PackingService::post()), so the true current balance is
        // whatever StockService says right now.
        $sourcesPayload = $packing->sources->map(function ($s) use ($packing) {
            $pp   = $s->purchaseProduct;
            $line = $pp?->line;
            return [
                'purchase_product_id' => $s->purchase_product_id,
                'qty_taken'           => (int) $s->qty_taken,
                'lot_code'            => $pp?->lot_code,
                'category'            => $line?->category?->name,
                'category_id'         => $line?->category_id,
                'stone_type'          => $line?->stone_type,
                'colour_grade'        => $line?->colour_grade,
                'clarity_grade'       => $line?->clarity_grade,
                'cut_shape'           => $line?->cut_shape,
                'treatment'           => $line?->treatment,
                'carat_weight'        => $pp?->carat_weight,
                'price'               => $pp?->price,
                // This packing's OWN saved per-row choices -- not
                // re-defaulted from the raw piece, since the user may
                // have already customised them here.
                'website_enabled'     => (bool) $s->website_enabled,
                'website_price'       => $s->website_price,
                'on_hand'             => $pp ? $this->stock->onHandForPiece($pp->id, (int) $packing->location_id) : 0,
            ];
        })->values();

        return view('packings.edit', [
            'packing'        => $packing,
            'sourcesPayload' => $sourcesPayload,
            'locations'      => Location::active()->orderBy('name')->get(['id', 'location_code', 'name', 'is_default']),
        ]);
    }

    public function update(UpdatePackingRequest $request, Packing $packing): JsonResponse
    {
        try {
            $packing = $this->service->update($packing, $request->validated());
            return response()->json([
                'ok'       => true,
                'message'  => 'Packing updated.',
                'packing'  => $packing,
                'redirect' => route('packings.show', $packing),
            ]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy(Packing $packing): JsonResponse
    {
        try {
            $this->service->delete($packing);
            return response()->json(['ok' => true, 'message' => 'Packing deleted.']);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* ─── Status transitions ──────────────────────────────── */

    public function post(Packing $packing): JsonResponse
    {
        try {
            $packing = $this->service->post($packing);
            return response()->json(['ok' => true, 'message' => 'Packing posted.', 'packing' => $packing]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Draft -> delete outright. Posted -> reverse the ledger movements
     * and mark cancelled. Kept as one endpoint since the show/index
     * screens only ever need one "undo" button regardless of state.
     */
    public function cancel(Packing $packing): JsonResponse
    {
        try {
            if ($packing->isDraft()) {
                $this->service->delete($packing);
                return response()->json(['ok' => true, 'message' => 'Packing deleted.', 'deleted' => true]);
            }
            $packing = $this->service->cancel($packing);
            return response()->json(['ok' => true, 'message' => 'Packing cancelled.', 'packing' => $packing]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* ─── Helpers used by the create/edit form ────────────── */

    /**
     * Raw (unpacked) pieces on hand at a location, optionally filtered by
     * lot code or the dock-scan barcode, for the source picker. Only
     * ever returns rows with product_id null -- an already-packed piece
     * isn't raw material anymore.
     */
    public function availableSources(Request $request): JsonResponse
    {
        $locationId = (int) $request->query('location_id', 0);
        $search     = trim((string) $request->query('search', ''));

        if ($locationId <= 0) {
            return response()->json(['ok' => false, 'message' => 'Location is required.'], 422);
        }

        $q = PurchaseProduct::query()
            ->whereNull('product_id')
            ->with(['line.category', 'line.purchase.supplier', 'rack']);

        if ($search !== '') {
            $q->where(function ($qq) use ($search) {
                $qq->where('lot_code', 'like', "%{$search}%")
                   ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $rows = $q->orderBy('created_at')->limit(50)->get();

        $items = $rows->map(function (PurchaseProduct $row) use ($locationId) {
            $onHand = $this->stock->onHandForPiece($row->id, $locationId);
            if ($onHand <= 0) {
                return null;
            }
            return [
                'purchase_product_id' => $row->id,
                'lot_code'            => $row->lot_code,
                'barcode'             => $row->barcode,
                'category_id'         => $row->line?->category_id,
                'category'            => $row->line?->category?->name,
                'stone_type'          => $row->line?->stone_type,
                'colour_grade'        => $row->line?->colour_grade,
                'clarity_grade'       => $row->line?->clarity_grade,
                'cut_shape'           => $row->line?->cut_shape,
                'treatment'           => $row->line?->treatment,
                'carat_weight'        => $row->carat_weight,
                'price'               => $row->price,
                // Defaults for the per-row "show on website" + Selling
                // Price columns on the picker -- see
                // PackingService::syncSources().
                'website_price'       => $row->website_price,
                'website_enabled'     => (bool) ($row->line?->website_enabled ?? false),
                'supplier'            => $row->line?->purchase?->supplier?->name,
                'rack'                => $row->rack?->name,
                'on_hand'             => $onHand,
            ];
        })->filter()->values();

        return response()->json(['ok' => true, 'items' => $items]);
    }

    /* ─── Label printing ───────────────────────────────────── */

    /**
     * Printable barcode-label sheet for this packing's output rows. Same
     * shape as PurchaseController::printLabels() -- each label encodes
     * the output row's own lot_code + cipher-coded cost, but can safely
     * show the real product title/stone type since a packed row always
     * has a real Product by the time it exists.
     */
    public function printLabels(Request $request, Packing $packing): View
    {
        $validIds = $packing->outputs()->pluck('id');

        $selections = collect($request->query('items', []))
            ->filter(fn ($item) => isset($item['id']) && $validIds->contains((int) $item['id']))
            ->mapWithKeys(fn ($item) => [
                (int) $item['id'] => max(1, min(100, (int) ($item['copies'] ?? 1))),
            ]);

        $rows = PurchaseProduct::with(['product'])
            ->whereIn('id', $selections->keys()->all())
            ->get()
            ->keyBy('id');

        $labels = $selections->flatMap(
            fn ($copies, $id) => $rows->has($id) ? array_fill(0, $copies, $rows[$id]) : []
        )->values();

        return view('packings.labels', [
            'packing' => $packing,
            'labels'  => $labels,
        ]);
    }
}
