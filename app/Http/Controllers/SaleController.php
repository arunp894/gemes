<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSalePaymentRequest;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\UpdateSaleRequest;
use App\Models\Barcode;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseProduct;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\User;
use App\Repositories\SaleRepository;
use App\Services\SaleService;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class SaleController extends Controller
{
    public function __construct(
        private SaleService    $service,
        private SaleRepository $repo,
        private StockService   $stock,
    ) {}

    /* ─── List ─────────────────────────────────────────────── */

    public function index(): View
    {
        return view('sales.index');
    }

    public function data(Request $request): JsonResponse
    {
        $q = $this->repo->query();

        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }
        if ($payStatus = $request->query('payment_status')) {
            $q->where('payment_status', $payStatus);
        }
        if ($from = $request->query('date_from')) {
            $q->whereDate('sale_date', '>=', $from);
        }
        if ($to = $request->query('date_to')) {
            $q->whereDate('sale_date', '<=', $to);
        }
        if ($customerId = $request->query('customer_id')) {
            $q->where('customer_id', $customerId);
        }
        if ($locationId = $request->query('location_id')) {
            $q->where('location_id', $locationId);
        }

        return DataTables::eloquent($q)
            ->editColumn(
                'sale_number',
                fn(Sale $s) =>
                '<a href="' . route('sales.show', $s) . '" class="link-reset"><code>' . e($s->sale_number) . '</code></a>'
            )
            ->editColumn('sale_date', fn(Sale $s) => optional($s->sale_date)->format('d M Y'))
            ->addColumn(
                'customer_label',
                fn(Sale $s) =>
                $s->customer ? e($s->customer->display_name) : '<span class="text-muted">—</span>'
            )
            ->addColumn(
                'location_label',
                fn(Sale $s) =>
                $s->location ? e($s->location->name) : '<span class="text-muted">—</span>'
            )
            ->editColumn(
                'grand_total',
                fn(Sale $s) =>
                '<span class="fw-semibold">' . number_format((float) $s->grand_total, 2) . '</span>'
            )
            ->editColumn('balance_due', fn(Sale $s) => number_format((float) $s->balance_due, 2))
            ->addColumn(
                'payment_badge',
                fn(Sale $s) =>
                '<span class="badge ' . $s->paymentStatusBadgeClass() . ' fs-xxs">' . e($s->paymentStatusLabel()) . '</span>'
            )
            ->addColumn(
                'status_badge',
                fn(Sale $s) =>
                '<span class="badge ' . $s->statusBadgeClass() . ' fs-xxs">' . e($s->statusLabel()) . '</span>'
            )
            ->addColumn('actions', function (Sale $s) {
                $canEdit   = auth()->user()?->hasPermission('sales.edit')   ?? false;
                $canDelete = auth()->user()?->hasPermission('sales.delete') ?? false;
                $canPost   = auth()->user()?->hasPermission('sales.post')   ?? false;

                $html  = '<div class="d-flex gap-1 justify-content-center">';
                $html .= '<a href="' . route('sales.show', $s) . '" class="btn btn-default btn-icon btn-sm" title="View"><i class="ti ti-eye fs-lg"></i></a>';
                if ($canEdit && $s->isEditable()) {
                    $html .= '<a href="' . route('sales.edit', $s) . '" class="btn btn-default btn-icon btn-sm" title="Edit"><i class="ti ti-edit fs-lg"></i></a>';
                }
                if ($canPost && $s->isDraft()) {
                    $html .= '<button type="button" class="btn btn-default btn-icon btn-sm js-post-sale text-success" data-url="' . route('sales.post', $s) . '" title="Post"><i class="ti ti-check fs-lg"></i></button>';
                }
                if ($canDelete) {
                    $html .= '<button type="button" class="btn btn-default btn-icon btn-sm js-delete-sale text-danger" data-url="' . route('sales.destroy', $s) . '" data-number="' . e($s->sale_number) . '" title="Delete"><i class="ti ti-trash fs-lg"></i></button>';
                }
                $html .= '</div>';
                return $html;
            })
            ->filterColumn('customer_label', function ($query, $keyword) {
                $like = "%{$keyword}%";
                $query->whereHas('customer', function ($qq) use ($like) {
                    $qq->where('name', 'like', $like)
                        ->orWhere('company_name', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('customer_code', 'like', $like);
                });
            })
            ->rawColumns(['sale_number', 'customer_label', 'location_label', 'grand_total', 'payment_badge', 'status_badge', 'actions'])
            ->toJson();
    }

    /* ─── Terminal (create) ───────────────────────────────── */

    public function create(): View
    {
        /** @var User $user */
        $user          = auth()->user();
        $userLocations = $user->locations()->active()->ordered()->get(['locations.id', 'location_code', 'name', 'type', 'is_default']);

        [$locationMode, $defaultLocation] = $this->resolveLocationContext($userLocations);

        return view('sales.create', [
            'userLocations'        => $userLocations,
            'locationMode'         => $locationMode,   // 'none' | 'single' | 'multiple'
            'salespeople'          => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'paymentMethods'       => SalePayment::METHODS,
            'taxTypes'             => Sale::TAX_TYPES,
            'defaultLocation'      => $defaultLocation,
            'defaultSalespersonId' => auth()->id(),
        ]);
    }

    public function store(StoreSaleRequest $request): JsonResponse
    {
        try {
            $sale = $this->service->create($request->validated());

            return response()->json([
                'ok'       => true,
                'message'  => 'Sale saved successfully.',
                'sale'     => $sale,
                'redirect' => route('sales.show', $sale),
            ], 201);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'ok'      => false,
                'message' => 'Could not save sale: ' . $e->getMessage(),
            ], 422);
        }
    }

    /* ─── Show / Edit / Update ────────────────────────────── */

    public function show(Sale $sale): View
    {
        return view('sales.show', [
            'sale'           => $this->repo->find($sale->id),
            'paymentMethods' => SalePayment::METHODS,
        ]);
    }

    public function edit(Sale $sale): View
    {
        abort_unless($sale->isEditable(), 403, 'Only draft sales can be edited.');

        /** @var User $user */
        $user          = auth()->user();
        $userLocations = $user->locations()->active()->ordered()->get(['locations.id', 'location_code', 'name', 'type', 'is_default']);

        [$locationMode, $defaultLocation] = $this->resolveLocationContext($userLocations, $sale->location);

        return view('sales.edit', [
            'sale'                 => $this->repo->find($sale->id),
            'userLocations'        => $userLocations,
            'locationMode'         => $locationMode,
            'salespeople'          => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'paymentMethods'       => SalePayment::METHODS,
            'taxTypes'             => Sale::TAX_TYPES,
            'defaultLocation'      => $defaultLocation,
            'defaultSalespersonId' => $sale->salesperson_id,
        ]);
    }

    public function update(UpdateSaleRequest $request, Sale $sale): JsonResponse
    {
        try {
            $sale = $this->service->update($sale, $request->validated());

            return response()->json([
                'ok'       => true,
                'message'  => 'Sale updated successfully.',
                'sale'     => $sale,
                'redirect' => route('sales.show', $sale),
            ]);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'ok'      => false,
                'message' => 'Could not update sale: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function destroy(Sale $sale): JsonResponse
    {
        $this->service->delete($sale);
        return response()->json(['ok' => true, 'message' => 'Sale deleted.']);
    }

    /* ─── Status transitions ──────────────────────────────── */

    public function post(Sale $sale): JsonResponse
    {
        try {
            $sale = $this->service->post($sale);
            return response()->json(['ok' => true, 'message' => 'Sale posted.', 'sale' => $sale]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function complete(Sale $sale): JsonResponse
    {
        try {
            $sale = $this->service->complete($sale);
            return response()->json(['ok' => true, 'message' => 'Sale completed.', 'sale' => $sale]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function refund(Sale $sale): JsonResponse
    {
        try {
            $sale = $this->service->refund($sale);
            return response()->json(['ok' => true, 'message' => 'Sale refunded.', 'sale' => $sale]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function cancel(Sale $sale): JsonResponse
    {
        try {
            $sale = $this->service->cancel($sale);
            return response()->json(['ok' => true, 'message' => 'Sale cancelled.', 'sale' => $sale]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /* ─── Payments ────────────────────────────────────────── */

    public function addPayment(StoreSalePaymentRequest $request, Sale $sale): JsonResponse
    {
        if ($sale->isCancelled() || $sale->isDraft()) {
            return response()->json([
                'ok'      => false,
                'message' => 'Payments can only be added to posted, completed, or refunded sales.',
            ], 422);
        }

        $payment = $this->service->addPayment($sale, $request->validated());

        return response()->json([
            'ok'      => true,
            'message' => 'Payment recorded.',
            'payment' => $payment,
            'sale'    => $sale->fresh(),
        ], 201);
    }

    public function removePayment(Sale $sale, SalePayment $payment): JsonResponse
    {
        abort_unless($payment->sale_id === $sale->id, 404);
        $this->service->removePayment($payment);
        return response()->json([
            'ok'      => true,
            'message' => 'Payment removed.',
            'sale'    => $sale->fresh(),
        ]);
    }

    /* ─── Terminal helpers (lookup + search) ──────────────── */

    /**
     * Resolve a scanned barcode to (product + the exact PurchaseProduct
     * with remaining stock, if any). Sales prefer matching the specific
     * inventory row so cost + margin reporting are accurate.
     *
     * Accepts optional ?location_id= so the terminal can show the
     * live on-hand balance at the sale's location.
     */
    public function lookupByBarcode(Request $request): JsonResponse
    {
        $value      = trim((string) $request->query('barcode', ''));
        $locationId = (int) $request->query('location_id', 0);
        if ($value === '') {
            return response()->json(['ok' => false, 'message' => 'No barcode provided.'], 422);
        }

        // Strategy 1: exact match in purchase_products (the inventory row).
        $pp = PurchaseProduct::with([
            'line.product:id,title,sku',
            'line.purchase:id,status',
        ])
            ->where('barcode', $value)
            ->whereNull('deleted_at')
            ->first();

        if ($pp && $pp->line && $pp->line->product) {
            // Live on-hand from the ledger, summed across all locations.
            // Stock is one global pool; the sale's location is recorded on
            // the sale but does not gate availability.
            $onHand = $this->stock->onHandForPieceGlobal((int) $pp->id);

            return response()->json([
                'ok'       => true,
                'source'   => 'inventory',
                'product'  => [
                    'id'    => $pp->line->product->id,
                    'title' => $pp->line->product->title,
                    'sku'   => $pp->line->product->sku,
                ],
                'inventory' => [
                    'purchase_product_id' => $pp->id,
                    'qty_on_record'       => (int) $pp->qty,
                    'on_hand'             => $onHand,
                    'cost_price'          => (float) $pp->price,
                    'rack_id'             => $pp->rack_id,
                    'expiry_date'         => optional($pp->expiry_date)->toDateString(),
                ],
                'barcode'  => $value,
            ]);
        }

        // Strategy 2: registered barcode that hasn't been linked to a
        // specific purchase row — still useful, just no cost / qty info.
        $bc = Barcode::with('product:id,title,sku')->where('barcode_value', $value)->first();
        if ($bc && $bc->product) {
            $onHand = $this->stock->onHandForProductGlobal((int) $bc->product->id);

            return response()->json([
                'ok'      => true,
                'source'  => 'product_barcode',
                'product' => [
                    'id'    => $bc->product->id,
                    'title' => $bc->product->title,
                    'sku'   => $bc->product->sku,
                ],
                'inventory' => [
                    'purchase_product_id' => null,
                    'on_hand'             => $onHand,
                ],
                'barcode'   => $value,
            ]);
        }

        return response()->json([
            'ok'      => false,
            'message' => "No product found for barcode '{$value}'.",
        ], 404);
    }

    /**
     * Quick product search for the terminal picker. Mirrors the purchase
     * search but doesn't surface inventory rows in the results.
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        $q = Product::query()
            ->select(['id', 'title', 'sku'])
            ->limit(15);

        if ($term !== '') {
            $like = "%{$term}%";
            $q->where(function ($qq) use ($like) {
                $qq->where('title', 'like', $like)->orWhere('sku', 'like', $like);
            });
        } else {
            $q->orderBy('title');
        }

        return response()->json([
            'ok'    => true,
            'items' => $q->get()->map(fn(Product $p) => [
                'id'    => $p->id,
                'title' => $p->title,
                'sku'   => $p->sku,
            ]),
        ]);
    }

    /**
     * Preview next sale number for the chosen date. Read-only — the real
     * number is regenerated inside the save transaction.
     */
    public function previewSaleNumber(Request $request): JsonResponse
    {
        $date = $request->query('date', now()->toDateString());
        $next = Sale::generateSaleNumber(\Carbon\Carbon::parse($date));
        return response()->json(['ok' => true, 'sale_number' => $next]);
    }

    /* ─── Private helpers ─────────────────────────────────── */

    /**
     * Determine the location UI mode and default selection for the sale terminal.
     *
     * Rules:
     *   - 0 locations assigned → mode 'none'  : block entry with an alert.
     *   - 1 location  assigned → mode 'single': auto-select, show readonly badge.
     *   - 2+ locations assigned → mode 'multiple': show a dropdown of the user's
     *     assigned locations only, defaulting to their is_default location or the
     *     first in the list.
     *
     * When $preferredLocation is given (edit flow) it overrides the default
     * selection, but only if that location is in the user's allowed set.
     *
     * @param  \Illuminate\Support\Collection  $userLocations
     * @param  Location|null                   $preferredLocation
     * @return array{0: string, 1: Location|null}  [mode, defaultLocation]
     */
    private function resolveLocationContext(
        \Illuminate\Support\Collection $userLocations,
        ?Location $preferredLocation = null
    ): array {
        $count = $userLocations->count();

        if ($count === 0) {
            return ['none', null];
        }

        if ($count === 1) {
            return ['single', $userLocations->first()];
        }

        // Multiple — pick the preferred location if it's in the user's set,
        // else fall back to their default-flagged one, else first in list.
        if ($preferredLocation && $userLocations->contains('id', $preferredLocation->id)) {
            return ['multiple', $preferredLocation];
        }

        $default = $userLocations->firstWhere('is_default', true) ?? $userLocations->first();
        return ['multiple', $default];
    }
}
