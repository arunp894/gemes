<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
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
    /**
     * Listing page.
     */
    public function index(): View
    {
        return view('suppliers.index');
    }

    /**
     * DataTables AJAX endpoint.
     */
    public function data(Request $request): JsonResponse
    {
        $query = Supplier::query();

        return DataTables::of($query)
            ->addColumn('checkbox', function (Supplier $supplier) {
                return '<input class="form-check-input form-check-input-light fs-14 product-item-check mt-0" '
                    . 'type="checkbox" value="' . $supplier->id . '" />';
            })
            ->editColumn('supplier_code', function (Supplier $supplier) {
                return '<code class="text-muted">' . e($supplier->supplier_code) . '</code>';
            })
            ->editColumn('name', function (Supplier $supplier) {
                $initial = strtoupper(mb_substr($supplier->display_name, 0, 1) ?: '?');
                $sub     = $supplier->company_name && $supplier->name !== $supplier->company_name
                    ? e($supplier->name)
                    : ($supplier->email ? e($supplier->email) : e($supplier->phone));

                return '
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm me-2">
                            <span class="avatar-title bg-primary-subtle text-primary rounded-circle fw-bold">'
                    . e($initial) .
                    '</span>
                        </div>
                        <div>
                            <h5 class="mb-0 fs-base">
                                <a href="' . route('suppliers.show', $supplier) . '" class="link-reset">'
                    . e($supplier->display_name) .
                    '</a>
                            </h5>
                            <small class="text-muted">' . $sub . '</small>
                        </div>
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
                return '<span class="fw-medium">' . number_format((float) $supplier->credit_limit, 2) . '</span>';
            })
            ->addColumn('status_badge', function (Supplier $supplier) {
                $class = $supplier->statusBadgeClass();
                $label = $supplier->statusLabel();
                return '<span class="badge ' . $class . ' fs-xxs">' . $label . '</span>';
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
                        <a href="' . $show . '" class="btn btn-default btn-icon btn-sm" title="View">
                            <i class="ti ti-eye fs-lg"></i>
                        </a>
                        <a href="' . $edit . '" class="btn btn-default btn-icon btn-sm" title="Edit">
                            <i class="ti ti-edit fs-lg"></i>
                        </a>
                        <button type="button" class="btn btn-default btn-icon btn-sm js-toggle-status"
                            data-url="' . $toggle . '" title="Toggle Status">
                            <i class="ti ' . $toggleIcon . ' fs-lg"></i>
                        </button>
                        <button type="button" class="btn btn-default btn-icon btn-sm js-delete text-danger"
                            data-url="' . $destroy . '" data-name="' . e($supplier->display_name) . '" title="Delete">
                            <i class="ti ti-trash fs-lg"></i>
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
            ->rawColumns(['checkbox', 'supplier_code', 'name', 'contact', 'location', 'credit_limit', 'status_badge', 'action'])
            ->make(true);
    }

    /**
     * Show the create form.
     */
    public function create(): View
    {
        $nextCode = Supplier::generateNextCode();
        return view('suppliers.create', compact('nextCode'));
    }

    /**
     * Store a new supplier.
     */
    public function store(StoreSupplierRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        $supplier = DB::transaction(function () use ($data) {
            return Supplier::create([
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
        return view('suppliers.show', compact('supplier'));
    }

    /**
     * Show the edit form.
     */
    public function edit(Supplier $supplier): View
    {
        return view('suppliers.edit', compact('supplier'));
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
