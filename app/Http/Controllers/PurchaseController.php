<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequest;
use App\Http\Requests\UpdatePurchaseRequest;
use App\Models\Barcode;
use App\Models\Location;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Rack;
use App\Models\Supplier;
use App\Repositories\PurchaseRepository;
use App\Services\PurchaseService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class PurchaseController extends Controller
{
    public function __construct(
        private PurchaseService    $service,
        private PurchaseRepository $repo,
        private SettingService     $settings,
    ) {}

    /**
     * Number of days after the purchase date during which a purchase
     * remains editable. Configurable via Settings → Purchases.
     */
    private function purchaseEditDays(): int
    {
        return (int) $this->settings->get('purchase_edit_days', 10);
    }

    /* ─── List ─────────────────────────────────────────────── */

    public function index(): View
    {
        return view('purchases.index');
    }

    public function data(Request $request)
    {
        $q = $this->repo->query();

        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }
        if ($from = $request->query('date_from')) {
            $q->whereDate('purchase_date', '>=', $from);
        }
        if ($to = $request->query('date_to')) {
            $q->whereDate('purchase_date', '<=', $to);
        }
        if ($supplierId = $request->query('supplier_id')) {
            $q->where('supplier_id', $supplierId);
        }

        return DataTables::eloquent($q)
            ->addColumn(
                'supplier_label',
                fn(Purchase $p) =>
                $p->supplier ? ($p->supplier->company_name ?: $p->supplier->name) : '—'
            )
            ->addColumn(
                'location_label',
                fn(Purchase $p) =>
                $p->location
                    ? '<span title="' . e($p->location->location_code) . '">' . e($p->location->name) . '</span>'
                    : '<span class="text-muted">—</span>'
            )
            ->editColumn(
                'purchase_date',
                fn(Purchase $p) =>
                optional($p->purchase_date)->format('d M Y')
            )
            ->editColumn(
                'grand_total',
                fn(Purchase $p) =>
                number_format((float) $p->grand_total, 2)
            )
            ->editColumn(
                'due_amount',
                fn(Purchase $p) =>
                number_format((float) $p->due_amount, 2)
            )
            ->addColumn(
                'status_badge',
                fn(Purchase $p) =>
                '<span class="badge ' . $p->statusBadgeClass() . '">' . $p->statusLabel() . '</span>'
            )
            ->addColumn('actions', function (Purchase $p) {
                $canEdit   = auth()->user()?->hasPermission('purchases.edit')   ?? false;
                $canDelete = auth()->user()?->hasPermission('purchases.delete') ?? false;
                $canPost   = auth()->user()?->hasPermission('purchases.post')   ?? false;

                $html = '<div class="d-flex gap-1 justify-content-center">';
                $html .= '<a href="' . route('purchases.show', $p) . '" class="btn btn-sm btn-soft-secondary" title="View"><i class="ti ti-eye"></i></a>';
                if ($canEdit && $p->isDraft()) {
                    $html .= '<a href="' . route('purchases.edit', $p) . '" class="btn btn-sm btn-soft-primary" title="Edit"><i class="ti ti-edit"></i></a>';
                }
                if ($canPost && $p->isDraft()) {
                    $html .= '<button type="button" class="btn btn-sm btn-soft-success js-post-purchase" data-id="' . $p->id . '" title="Post"><i class="ti ti-check"></i></button>';
                }
                if ($canDelete) {
                    $html .= '<button type="button" class="btn btn-sm btn-soft-danger js-delete-purchase" data-id="' . $p->id . '" title="Delete"><i class="ti ti-trash"></i></button>';
                }
                $html .= '</div>';
                return $html;
            })
            ->rawColumns(['status_badge', 'actions', 'location_label'])
            ->toJson();
    }

    /* ─── Create / Store ──────────────────────────────────── */

    public function create(): View
    {
        return view('purchases.create', [
            'suppliers' => Supplier::active()->ordered()->get(['id', 'supplier_code', 'name', 'company_name', 'invoice_prefix', 'gst_number']),
            'locations' => Location::active()->ordered()->get(['id', 'location_code', 'name', 'type']),
            'racks'     => Rack::active()->ordered()->get(['id', 'code', 'name']),
            'taxTypes'  => Purchase::TAX_TYPES,
        ]);
    }

    public function store(StorePurchaseRequest $request): JsonResponse
    {
        $purchase = $this->service->create($request->validated());

        return response()->json([
            'message'  => 'Purchase saved successfully.',
            'purchase' => $purchase,
            'redirect' => route('purchases.show', $purchase),
        ], 201);
    }

    /* ─── Show / Edit / Update ────────────────────────────── */

    public function show(Purchase $purchase): View
    {
        $purchase = $this->repo->find($purchase->id);

        return view('purchases.show', [
            'purchase'        => $purchase,
            'editBlockReason' => $purchase->editBlockReason($this->purchaseEditDays()),
        ]);
    }

    public function edit(Purchase $purchase): View|RedirectResponse
    {
        if ($reason = $purchase->editBlockReason($this->purchaseEditDays())) {
            return redirect()->route('purchases.show', $purchase)->with('error', $reason);
        }

        return view('purchases.edit', [
            'purchase'  => $this->repo->find($purchase->id),
            'suppliers' => Supplier::active()->ordered()->get(['id', 'supplier_code', 'name', 'company_name', 'invoice_prefix', 'gst_number']),
            'locations' => Location::active()->ordered()->get(['id', 'location_code', 'name', 'type']),
            'racks'     => Rack::active()->ordered()->get(['id', 'code', 'name']),
            'taxTypes'  => Purchase::TAX_TYPES,
        ]);
    }

    public function update(UpdatePurchaseRequest $request, Purchase $purchase): JsonResponse
    {
        if ($reason = $purchase->editBlockReason($this->purchaseEditDays())) {
            return response()->json(['message' => $reason], 422);
        }

        try {
            $purchase = $this->service->update($purchase, $request->validated());
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message'  => 'Purchase updated successfully.',
            'purchase' => $purchase,
            'redirect' => route('purchases.show', $purchase),
        ]);
    }

    public function destroy(Purchase $purchase): JsonResponse
    {
        $this->service->delete($purchase);
        return response()->json(['message' => 'Purchase deleted.']);
    }

    /* ─── Status transitions ──────────────────────────────── */

    public function post(Purchase $purchase): JsonResponse
    {
        $purchase = $this->service->post($purchase);
        return response()->json([
            'message'  => 'Purchase posted.',
            'purchase' => $purchase,
        ]);
    }

    public function cancel(Purchase $purchase): JsonResponse
    {
        $purchase = $this->service->cancel($purchase);
        return response()->json([
            'message'  => 'Purchase cancelled.',
            'purchase' => $purchase,
        ]);
    }

    /* ─── Barcode lookup (scanner support) ────────────────── */

    /**
     * Resolve a scanned barcode value to a product. Returns the product
     * with its full packaging payload so the Vue form can decide whether
     * to insert one piece-row or expand into N inner-pack rows.
     */
    public function lookupByBarcode(Request $request): JsonResponse
    {
        $value = trim((string) $request->query('barcode', ''));

        if ($value === '') {
            return response()->json(['ok' => false, 'message' => 'No barcode provided.'], 422);
        }

        $barcode = Barcode::with(['product' => function ($q) {
            $q->select([
                'id',
                'title',
                'sku',
                'status',
                'carat_weight',
                'pack_type',
                'outer_pack_name',
                'outer_pack_contains',
                'inner_pack_name',
                'inner_pack_contains',
            ]);
        }])->where('barcode_value', $value)->first();

        if (! $barcode || ! $barcode->product) {
            return response()->json([
                'ok'      => false,
                'message' => "No product found for barcode '{$value}'.",
            ], 404);
        }

        $product = $barcode->product;

        return response()->json([
            'ok'      => true,
            'product' => [
                'id'        => $product->id,
                'title'     => $product->title,
                'sku'       => $product->sku,
                'carat_weight' => $product->carat_weight,
                'packaging' => $product->packagingPayload(),
            ],
            'barcode' => [
                'value'      => $barcode->barcode_value,
                'format'     => $barcode->barcode_format,
                'is_primary' => (bool) $barcode->is_primary,
            ],
        ]);
    }

    /**
     * Quick product search for the secondary picker (when the user
     * doesn't have a scanner / barcode isn't registered yet).
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        $q = Product::query()
            ->select([
                'id',
                'title',
                'sku',
                'carat_weight',
                'pack_type',
                'outer_pack_name',
                'outer_pack_contains',
                'inner_pack_name',
                'inner_pack_contains',
            ])
            ->with(['primaryBarcode:id,product_id,barcode_value,barcode_format,is_primary'])
            ->limit(15);

        if ($term !== '') {
            $q->where(function ($qq) use ($term) {
                $qq->where('title', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhereHas('barcodes', function ($qb) use ($term) {
                        $qb->where('barcode_value', 'like', "%{$term}%");
                    });
            });

            // Bubble exact barcode matches to the top, then exact SKU, then the rest.
            $q->orderByRaw(
                'CASE
                    WHEN EXISTS (
                        SELECT 1 FROM barcodes b
                        WHERE b.product_id = products.id
                          AND b.deleted_at IS NULL
                          AND b.barcode_value = ?
                    ) THEN 0
                    WHEN sku = ? THEN 1
                    ELSE 2
                END',
                [$term, $term]
            );
        }

        $items = $q->get()->map(fn(Product $p) => [
            'id'        => $p->id,
            'title'     => $p->title,
            'sku'       => $p->sku,
            'carat_weight' => $p->carat_weight,
            'packaging' => $p->packagingPayload(),
            'barcode'   => $p->primaryBarcode ? [
                'value'      => $p->primaryBarcode->barcode_value,
                'format'     => $p->primaryBarcode->barcode_format,
                'is_primary' => true,
            ] : null,
        ]);

        return response()->json(['ok' => true, 'items' => $items]);
    }

    /**
     * Preview the next invoice number for a chosen supplier + date.
     * Used to show "Next: ACME-202605-0007" on the create screen.
     * NOTE: this is a read-only preview — the real number is regenerated
     * inside the save transaction to stay collision-safe.
     */
    public function previewInvoiceNumber(Request $request): JsonResponse
    {
        $supplierId = (int) $request->query('supplier_id');
        $date       = $request->query('date', now()->toDateString());

        $supplier = Supplier::find($supplierId);
        if (! $supplier) {
            return response()->json(['ok' => false, 'message' => 'Supplier not found.'], 404);
        }

        $next = Purchase::generateInvoiceNumber($supplier, \Carbon\Carbon::parse($date));

        return response()->json(['ok' => true, 'invoice_number' => $next]);
    }
}
