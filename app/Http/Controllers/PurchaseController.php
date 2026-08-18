<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequest;
use App\Http\Requests\UpdatePurchaseRequest;
use App\Models\Barcode;
use App\Models\Category;
use App\Models\CountryOfOrigin;
use App\Models\Location;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseProduct;
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
            'suppliers'  => Supplier::active()->ordered()->get(['id', 'supplier_code', 'name', 'company_name', 'invoice_prefix', 'gst_number']),
            'locations'  => Location::active()->ordered()->get(['id', 'location_code', 'name', 'type']),
            'racks'      => Rack::active()->ordered()->get(['id', 'code', 'name']),
            'categories' => Category::active()->ordered()->get(['id', 'name', 'code', 'is_gemstone']),
            'countriesOfOrigin' => CountryOfOrigin::active()->ordered()->get(['id', 'name']),
            'taxTypes'   => Purchase::TAX_TYPES,
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
            'purchase'   => $this->repo->find($purchase->id),
            'suppliers'  => Supplier::active()->ordered()->get(['id', 'supplier_code', 'name', 'company_name', 'invoice_prefix', 'gst_number']),
            'locations'  => Location::active()->ordered()->get(['id', 'location_code', 'name', 'type']),
            'racks'      => Rack::active()->ordered()->get(['id', 'code', 'name']),
            'categories' => Category::active()->ordered()->get(['id', 'name', 'code', 'is_gemstone']),
            'countriesOfOrigin' => CountryOfOrigin::active()->ordered()->get(['id', 'name']),
            'taxTypes'   => Purchase::TAX_TYPES,
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
     *
     * NOTE: no longer called from the create/edit purchase screens — a
     * new-style line creates its own product instead of picking an
     * existing one, so there's nothing to look up when adding a line.
     * Left in place (route still registered) rather than removed, in
     * case restocking a genuinely non-unique existing product ever needs
     * this path back.
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
     *
     * NOTE: no longer called from the create/edit purchase screens — see
     * lookupByBarcode() above.
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

    /**
     * Preview the next lot code(s) for a supplier + category, optionally
     * $count in a row (a line fans out into one inventory row per
     * product). Read-only — the real codes are (re)generated inside the
     * save transaction, same pattern as previewInvoiceNumber(). Keyed on
     * category rather than product: a purchase line no longer points at
     * a pre-existing product to key the preview on — see
     * PurchaseProduct::generateLotCode() for why category is the right
     * stable key now.
     */
    public function previewLotCode(Request $request): JsonResponse
    {
        $supplier = Supplier::find((int) $request->query('supplier_id'));
        $category = Category::find((int) $request->query('category_id'));
        $count    = max(1, (int) $request->query('count', 1));

        if (! $supplier || ! $category) {
            return response()->json(['ok' => false, 'message' => 'Supplier or category not found.'], 404);
        }

        return response()->json([
            'ok'    => true,
            'codes' => PurchaseProduct::previewLotCodes($supplier, $category, $count),
        ]);
    }

    /* ─── Label printing ───────────────────────────── */

    /**
     * Printable barcode-label sheet for selected inventory rows on this
     * purchase. Each label encodes the row's lot_code — always present
     * and unique per physical unit, unlike `barcode`, which can repeat
     * across a box of identical pieces (it identifies the product, not
     * the piece). Read-only; writes nothing.
     *
     * Expects (GET, built client-side from the show page's row
     * checkboxes + copies inputs):
     *   items[N][id]     = purchase_product id
     *   items[N][copies] = how many copies of that label to print
     */
    public function printLabels(Request $request, Purchase $purchase): View
    {
        $validIds = collect($purchase->purchaseProductIds());

        $selections = collect($request->query('items', []))
            ->filter(fn ($item) => isset($item['id']) && $validIds->contains((int) $item['id']))
            ->mapWithKeys(fn ($item) => [
                (int) $item['id'] => max(1, min(100, (int) ($item['copies'] ?? 1))),
            ]);

        $rows = PurchaseProduct::with(['product', 'line.product'])
            ->whereIn('id', $selections->keys()->all())
            ->get()
            ->keyBy('id');

        $labels = $selections->flatMap(
            fn ($copies, $id) => $rows->has($id) ? array_fill(0, $copies, $rows[$id]) : []
        )->values();

        return view('purchases.labels', [
            'purchase' => $purchase,
            'labels'   => $labels,
        ]);
    }
}
