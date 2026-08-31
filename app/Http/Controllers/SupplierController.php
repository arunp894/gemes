<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Category;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * Suppliers CRUD.
 *
 * Pattern mirrors UserController and CategoryController:
 *   - DataTables AJAX listing at /suppliers/data
 *   - PATCH /suppliers/{supplier}/toggle-status for the inline toggle
 *   - JSON responses for AJAX / FormData submissions; classic redirects otherwise
 */
class SupplierController extends Controller
{
    public function __construct(private SettingService $settings) {}

    /**
     * Listing page.
     */
    public function index(): View
    {
        // Single aggregate query instead of separate COUNT() round-trips per card.
        $counts = Supplier::selectRaw('COUNT(*) as total, SUM(status = 1) as active, SUM(status = 0) as inactive')
            ->first();

        $stats = [
            'suppliers_total'    => (int) $counts->total,
            'suppliers_active'   => (int) $counts->active,
            'suppliers_inactive' => (int) $counts->inactive,
        ];

        return view('suppliers.index', compact('stats'));
    }

    /**
     * DataTables AJAX endpoint.
     */
    public function data(Request $request): JsonResponse
    {
        $query = Supplier::query()
            ->withSum('purchases as purchase_payment_sum', 'grand_total')
            ->withSum('purchases as paid_payment_sum', 'paid_amount')
            ->withSum('purchases as pending_payment_sum', 'due_amount');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('checkbox', function (Supplier $supplier) {
                return '<input class="form-check-input form-check-input-light fs-14 product-item-check mt-0" '
                    . 'type="checkbox" value="' . $supplier->id . '" />';
            })
            ->editColumn('name', function (Supplier $supplier) {
                $sub = $supplier->company_name && $supplier->name !== $supplier->company_name
                    ? e($supplier->name)
                    : ($supplier->email ? e($supplier->email) : e($supplier->phone));

                return '
                    <div class="supplier-name-cell">
                        <a href="' . route('suppliers.show', $supplier) . '" class="supplier-name-link">'
                    . e($supplier->display_name) .
                    '</a>
                        <small class="d-block text-muted">' . $sub . '</small>
                    </div>
                ';
            })
            ->addColumn('contact', function (Supplier $supplier) {
                $phone = e($supplier->phone);
                $email = $supplier->email ? '<small class="d-block text-muted">' . e($supplier->email) . '</small>' : '';
                return '<div><span>' . $phone . '</span>' . $email . '</div>';
            })
            ->addColumn('location', function (Supplier $supplier) {
                $parts = array_filter([$supplier->city, $supplier->state, $supplier->country]);
                if (empty($parts)) {
                    return '<span class="text-muted fs-xs">—</span>';
                }
                return e(implode(', ', $parts));
            })
            ->editColumn('credit_limit', function (Supplier $supplier) {
                return '<span class="fw-medium">' . $this->settings->formatMoney($supplier->credit_limit) . '</span>';
            })
            ->addColumn('purchase_payment', function (Supplier $supplier) {
                return '<span class="fw-medium">' . $this->settings->formatMoney($supplier->purchase_payment_sum ?? 0) . '</span>';
            })
            ->addColumn('paid_payment', function (Supplier $supplier) {
                return '<span class="text-success fw-medium">' . $this->settings->formatMoney($supplier->paid_payment_sum ?? 0) . '</span>';
            })
            ->addColumn('pending_payment', function (Supplier $supplier) {
                $due = (float) ($supplier->pending_payment_sum ?? 0);
                $class = $due > 0 ? 'text-danger fw-medium' : 'text-muted';
                return '<span class="' . $class . '">' . $this->settings->formatMoney($due) . '</span>';
            })
            ->addColumn('status_badge', function (Supplier $supplier) {
                $class = $supplier->isActive() ? 'status-pill status-active' : 'status-pill status-inactive';
                $label = $supplier->statusLabel();
                return '<span class="' . $class . '"><span class="status-dot"></span>' . $label . '</span>';
            })
            ->editColumn('created_at', function (Supplier $supplier) {
                return optional($supplier->created_at)->format('d M, Y') ?? '—';
            })
            ->addColumn('action', function (Supplier $supplier) {
                $show    = route('suppliers.show', $supplier);
                $edit    = route('suppliers.edit', $supplier);
                $toggle  = route('suppliers.toggle-status', $supplier);
                $destroy = route('suppliers.destroy', $supplier);

                $toggleIcon = $supplier->isActive() ? 'ti-toggle-right' : 'ti-toggle-left';

                return '
                    <div class="d-flex justify-content-center gap-1">
                        <a href="' . $show . '" class="action-btn action-view" title="View">
                            <i class="ti ti-eye"></i>
                        </a>
                        <a href="' . $edit . '" class="action-btn action-edit" title="Edit">
                            <i class="ti ti-edit"></i>
                        </a>
                        <button type="button" class="action-btn action-toggle js-toggle-status"
                            data-url="' . $toggle . '" title="Toggle Status">
                            <i class="ti ' . $toggleIcon . '"></i>
                        </button>
                        <button type="button" class="action-btn action-delete js-delete"
                            data-url="' . $destroy . '" data-name="' . e($supplier->display_name) . '" title="Delete">
                            <i class="ti ti-trash"></i>
                        </button>
                    </div>
                ';
            })
            ->filterColumn('name', function ($query, $keyword) {
                $like = "%{$keyword}%";
                $query->where(function ($q) use ($like) {
                    $q->where('suppliers.name', 'like', $like)
                        ->orWhere('suppliers.company_name', 'like', $like)
                        ->orWhere('suppliers.email', 'like', $like)
                        ->orWhere('suppliers.phone', 'like', $like)
                        ->orWhere('suppliers.supplier_code', 'like', $like);
                });
            })
            ->filterColumn('status_badge', function ($query, $keyword) {
                if ($keyword === '1' || $keyword === '0') {
                    $query->where('suppliers.status', (int) $keyword);
                }
            })
            ->rawColumns(['checkbox', 'name', 'contact', 'location', 'credit_limit', 'purchase_payment', 'paid_payment', 'pending_payment', 'status_badge', 'action'])
            ->make(true);
    }

    /**
     * Show the create form.
     */
    public function create(): View
    {
        $nextCode   = Supplier::generateNextCode();
        $categories = Category::active()->ordered()->get(['id', 'name']);
        return view('suppliers.create', compact('nextCode', 'categories'));
    }

    /**
     * Store a new supplier.
     */
    public function store(StoreSupplierRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        $supplier = DB::transaction(function () use ($data) {
            $supplier = Supplier::create([
                'supplier_code'   => $data['supplier_code'] ?? null, // model auto-generates if null
                'name'            => $data['name'],
                'company_name'    => $data['company_name'] ?? null,
                'email'           => $data['email'] ?? null,
                'phone'           => $data['phone'],
                'alternate_phone' => $data['alternate_phone'] ?? null,
                'gst_number'      => $data['gst_number'] ?? null,
                'tax_number'      => $data['tax_number'] ?? null,
                'website'         => $data['website'] ?? null,
                'country'         => $data['country'] ?? null,
                'state'           => $data['state'] ?? null,
                'city'            => $data['city'] ?? null,
                'zip_code'        => $data['zip_code'] ?? null,
                'address'         => $data['address'] ?? null,
                'opening_balance' => $data['opening_balance'] ?? 0,
                'credit_limit'    => $data['credit_limit'] ?? 0,
                'status'          => (bool) $data['status'],
            ]);

            $supplier->categories()->sync($data['category_ids'] ?? []);

            return $supplier;
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Supplier created successfully.',
                'redirect' => route('suppliers.index'),
                'data'     => $supplier,
            ]);
        }

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier created successfully.');
    }

    /**
     * Display a single supplier.
     */
    public function show(Supplier $supplier): View
    {
        $supplier->load(['creator', 'updater']);

        $row = $supplier->purchases()
            ->selectRaw("COUNT(*) as count, SUM(status = 'posted') as posted, COALESCE(SUM(grand_total),0) as total, COALESCE(SUM(due_amount),0) as due")
            ->first();

        $purchaseStats = [
            'count'  => (int) $row->count,
            'posted' => (int) $row->posted,
            'total'  => (float) $row->total,
            'due'    => (float) $row->due,
        ];

        return view('suppliers.show', compact('supplier', 'purchaseStats'));
    }

    /**
     * DataTables AJAX endpoint — purchases belonging to this supplier.
     * Powers the "Purchases" tab on the supplier profile page.
     */
    public function purchasesData(Request $request, Supplier $supplier): JsonResponse
    {
        $query = Purchase::query()
            ->where('supplier_id', $supplier->id)
            ->with(['location:id,name,location_code,type']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('purchase_date', '>=', $dateFrom);
        }
        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('purchase_date', '<=', $dateTo);
        }

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('invoice_link', function (Purchase $p) {
                return '<a href="' . route('purchases.show', $p) . '" class="link-reset fw-medium">'
                    . e($p->invoice_number) . '</a>';
            })
            ->addColumn('location_label', function (Purchase $p) {
                return $p->location
                    ? '<span title="' . e($p->location->location_code) . '">' . e($p->location->name) . '</span>'
                    : '<span class="text-muted">—</span>';
            })
            ->editColumn('purchase_date', fn (Purchase $p) => optional($p->purchase_date)->format('d M Y'))
            ->editColumn('grand_total', fn (Purchase $p) => $this->settings->formatMoney($p->grand_total))
            ->editColumn('paid_amount', fn (Purchase $p) => $this->settings->formatMoney($p->paid_amount))
            ->editColumn('due_amount', fn (Purchase $p) => $this->settings->formatMoney($p->due_amount))
            ->addColumn('status_badge', function (Purchase $p) {
                $class = match ($p->status) {
                    'posted'    => 'status-pill status-posted',
                    'cancelled' => 'status-pill status-cancelled',
                    default     => 'status-pill status-draft',
                };
                return '<span class="' . $class . '"><span class="status-dot"></span>' . $p->statusLabel() . '</span>';
            })
            ->addColumn('actions', function (Purchase $p) {
                return '<div class="d-flex justify-content-center">'
                    . '<a href="' . route('purchases.show', $p) . '" class="action-btn action-view" title="View"><i class="ti ti-eye"></i></a>'
                    . '</div>';
            })
            ->rawColumns(['invoice_link', 'location_label', 'status_badge', 'actions'])
            ->toJson();
    }

    /**
     * Show the edit form.
     */
    public function edit(Supplier $supplier): View
    {
        $categories = Category::active()->ordered()->get(['id', 'name']);
        $selectedCategoryIds = $supplier->categories()->pluck('categories.id')->all();

        return view('suppliers.edit', compact('supplier', 'categories', 'selectedCategoryIds'));
    }

    /**
     * Update an existing supplier.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($supplier, $data) {
            $supplier->fill([
                // supplier_code is intentionally not updated here — it's
                // immutable in the UI. Posted values are ignored on update.
                'name'            => $data['name'],
                'company_name'    => $data['company_name'] ?? null,
                'email'           => $data['email'] ?? null,
                'phone'           => $data['phone'],
                'alternate_phone' => $data['alternate_phone'] ?? null,
                'gst_number'      => $data['gst_number'] ?? null,
                'tax_number'      => $data['tax_number'] ?? null,
                'website'         => $data['website'] ?? null,
                'country'         => $data['country'] ?? null,
                'state'           => $data['state'] ?? null,
                'city'            => $data['city'] ?? null,
                'zip_code'        => $data['zip_code'] ?? null,
                'address'         => $data['address'] ?? null,
                'opening_balance' => $data['opening_balance'] ?? 0,
                'credit_limit'    => $data['credit_limit'] ?? 0,
                'status'          => (bool) $data['status'],
            ])->save();

            $supplier->categories()->sync($data['category_ids'] ?? []);
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Supplier updated successfully.',
                'redirect' => route('suppliers.index'),
                'data'     => $supplier->fresh(),
            ]);
        }

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

    /**
     * Soft-delete a supplier.
     */
    public function destroy(Supplier $supplier, Request $request): JsonResponse|RedirectResponse
    {
        $supplier->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Supplier deleted successfully.',
            ]);
        }

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier deleted successfully.');
    }

    /**
     * Categories mapped to this supplier — AJAX endpoint consumed by the
     * purchase create/edit screen. Once a supplier is chosen, the "Add
     * Item" category dropdown is filtered to this list instead of every
     * active category. A supplier with no categories mapped yet returns
     * an empty list; the Vue form falls back to the full category list
     * in that case so unmapped (typically older) suppliers keep working.
     */
    public function categories(Supplier $supplier): JsonResponse
    {
        $categories = $supplier->categories()
            ->active()
            ->ordered()
            ->get(['categories.id', 'categories.name', 'categories.is_gemstone']);

        return response()->json([
            'success'    => true,
            'categories' => $categories,
        ]);
    }

    /**
     * Toggle Active / Inactive.
     */
    public function toggleStatus(Supplier $supplier): JsonResponse
    {
        $supplier->status = ! $supplier->status;
        $supplier->save();

        return response()->json([
            'success' => true,
            'status'  => (bool) $supplier->status,
            'label'   => $supplier->statusLabel(),
            'message' => 'Status updated.',
        ]);
    }
}
