<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * Customers CRUD. Mirrors SupplierController structure exactly.
 *
 * Includes a /customers/search endpoint used by the Sales terminal so
 * the cashier can pick a customer without leaving the create page.
 */
class CustomerController extends Controller
{
    public function index(): View
    {
        // Single aggregate query instead of separate COUNT() round-trips per card.
        $counts = Customer::selectRaw('COUNT(*) as total, SUM(status = 1) as active, SUM(status = 0) as inactive')
            ->first();

        $stats = [
            'customers_total'    => (int) $counts->total,
            'customers_active'   => (int) $counts->active,
            'customers_inactive' => (int) $counts->inactive,
        ];

        return view('customers.index', compact('stats'));
    }

    public function data(Request $request): JsonResponse
    {
        $query = Customer::query();

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('checkbox', function (Customer $c) {
                return '<input class="form-check-input form-check-input-light fs-14 product-item-check mt-0" '
                    . 'type="checkbox" value="' . $c->id . '" />';
            })
            ->editColumn('customer_code', function (Customer $c) {
                return '<code class="text-muted">' . e($c->customer_code) . '</code>';
            })
            ->editColumn('name', function (Customer $c) {
                $sub = $c->company_name && $c->name !== $c->company_name
                    ? e($c->name)
                    : ($c->phone ? e($c->phone) : e($c->email));

                return '
                    <div class="customer-name-cell">
                        <a href="' . route('customers.show', $c) . '" class="customer-name-link">'
                    . e($c->display_name) .
                    '</a>
                        <small class="d-block text-muted">' . $sub . '</small>
                    </div>
                ';
            })
            ->addColumn('type_badge', function (Customer $c) {
                return '<span class="badge ' . $c->typeBadgeClass() . ' fs-xxs">' . e($c->typeLabel()) . '</span>';
            })
            ->addColumn('contact', function (Customer $c) {
                $phone = $c->phone ? e($c->phone) : '';
                $email = $c->email ? '<small class="d-block text-muted">' . e($c->email) . '</small>' : '';
                if (! $phone && ! $email) {
                    return '<span class="text-muted fs-xs">—</span>';
                }
                return '<div><span>' . $phone . '</span>' . $email . '</div>';
            })
            ->addColumn('location', function (Customer $c) {
                $parts = array_filter([$c->city, $c->state, $c->country]);
                return $parts ? e(implode(', ', $parts)) : '<span class="text-muted fs-xs">—</span>';
            })
            ->addColumn('status_badge', function (Customer $c) {
                $class = $c->isActive() ? 'status-pill status-active' : 'status-pill status-inactive';
                $label = $c->statusLabel();
                return '<span class="' . $class . '"><span class="status-dot"></span>' . $label . '</span>';
            })
            ->editColumn('created_at', function (Customer $c) {
                return optional($c->created_at)->format('d M, Y') ?? '—';
            })
            ->addColumn('action', function (Customer $c) {
                $show    = route('customers.show', $c);
                $edit    = route('customers.edit', $c);
                $toggle  = route('customers.toggle-status', $c);
                $destroy = route('customers.destroy', $c);
                $icon    = $c->isActive() ? 'ti-toggle-right' : 'ti-toggle-left';

                return '
                    <div class="d-flex justify-content-center gap-1">
                        <a href="' . $show . '" class="action-btn action-view" title="View"><i class="ti ti-eye"></i></a>
                        <a href="' . $edit . '" class="action-btn action-edit" title="Edit"><i class="ti ti-edit"></i></a>
                        <button type="button" class="action-btn action-toggle js-toggle-status" data-url="' . $toggle . '" title="Toggle Status">
                            <i class="ti ' . $icon . '"></i>
                        </button>
                        <button type="button" class="action-btn action-delete js-delete" data-url="' . $destroy . '" data-name="' . e($c->display_name) . '" title="Delete">
                            <i class="ti ti-trash"></i>
                        </button>
                    </div>
                ';
            })
            ->filterColumn('name', function ($query, $keyword) {
                $like = "%{$keyword}%";
                $query->where(function ($q) use ($like) {
                    $q->where('customers.name', 'like', $like)
                        ->orWhere('customers.company_name', 'like', $like)
                        ->orWhere('customers.email', 'like', $like)
                        ->orWhere('customers.phone', 'like', $like)
                        ->orWhere('customers.customer_code', 'like', $like);
                });
            })
            ->filterColumn('type_badge', function ($query, $keyword) {
                if ($keyword !== '' && $keyword !== null) {
                    $query->where('customers.customer_type', $keyword);
                }
            })
            ->filterColumn('status_badge', function ($query, $keyword) {
                if ($keyword === '1' || $keyword === '0') {
                    $query->where('customers.status', (int) $keyword);
                }
            })
            ->rawColumns(['checkbox', 'customer_code', 'name', 'type_badge', 'contact', 'location', 'status_badge', 'action'])
            ->make(true);
    }

    public function create(): View
    {
        $nextCode = Customer::generateNextCode();
        $types    = Customer::TYPES;
        return view('customers.create', compact('nextCode', 'types'));
    }

    public function store(StoreCustomerRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        $customer = DB::transaction(function () use ($data) {
            return Customer::create([
                'customer_code'   => $data['customer_code'] ?? null,
                'name'            => $data['name'],
                'company_name'    => $data['company_name'] ?? null,
                'customer_type'   => $data['customer_type'],
                'email'           => $data['email'] ?? null,
                'phone'           => $data['phone'] ?? null,
                'alternate_phone' => $data['alternate_phone'] ?? null,
                'gst_number'      => $data['gst_number'] ?? null,
                'pan_number'      => $data['pan_number'] ?? null,
                'address_line1'   => $data['address_line1'] ?? null,
                'address_line2'   => $data['address_line2'] ?? null,
                'city'            => $data['city'] ?? null,
                'state'           => $data['state'] ?? null,
                'country'         => $data['country'] ?? null,
                'zip_code'        => $data['zip_code'] ?? null,
                'status'          => (bool) $data['status'],
                'notes'           => $data['notes'] ?? null,
            ]);
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Customer created successfully.',
                'redirect' => route('customers.index'),
                'data'     => $customer,
            ]);
        }

        return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
    }

    public function show(Customer $customer): View
    {
        $customer->load(['creator', 'updater']);
        // Last 10 sales for quick history view.
        $recentSales = $customer->sales()
            ->latest('sale_date')
            ->limit(10)
            ->get();
        return view('customers.show', compact('customer', 'recentSales'));
    }

    public function edit(Customer $customer): View
    {
        $types = Customer::TYPES;
        return view('customers.edit', compact('customer', 'types'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($customer, $data) {
            $customer->fill([
                'name'            => $data['name'],
                'company_name'    => $data['company_name'] ?? null,
                'customer_type'   => $data['customer_type'],
                'email'           => $data['email'] ?? null,
                'phone'           => $data['phone'] ?? null,
                'alternate_phone' => $data['alternate_phone'] ?? null,
                'gst_number'      => $data['gst_number'] ?? null,
                'pan_number'      => $data['pan_number'] ?? null,
                'address_line1'   => $data['address_line1'] ?? null,
                'address_line2'   => $data['address_line2'] ?? null,
                'city'            => $data['city'] ?? null,
                'state'           => $data['state'] ?? null,
                'country'         => $data['country'] ?? null,
                'zip_code'        => $data['zip_code'] ?? null,
                'status'          => (bool) $data['status'],
                'notes'           => $data['notes'] ?? null,
            ])->save();
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Customer updated successfully.',
                'redirect' => route('customers.index'),
                'data'     => $customer->fresh(),
            ]);
        }

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer, Request $request): JsonResponse|RedirectResponse
    {
        $customer->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Customer deleted successfully.']);
        }

        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }

    public function toggleStatus(Customer $customer): JsonResponse
    {
        $customer->status = ! $customer->status;
        $customer->save();

        return response()->json([
            'success' => true,
            'status'  => (bool) $customer->status,
            'label'   => $customer->statusLabel(),
            'message' => 'Status updated.',
        ]);
    }

    /**
     * Lightweight search endpoint for the Sales terminal autocomplete.
     * Returns top 15 matches by name/phone/email/code.
     */
    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        $q = Customer::query()->active()->limit(15);

        if ($term !== '') {
            $like = "%{$term}%";
            $q->where(function ($qq) use ($like) {
                $qq->where('name', 'like', $like)
                    ->orWhere('company_name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('customer_code', 'like', $like);
            });
        } else {
            $q->orderBy('name')->limit(20);
        }

        $items = $q->get(['id', 'customer_code', 'name', 'company_name', 'customer_type', 'phone', 'email', 'gst_number'])
            ->map(fn (Customer $c) => [
                'id'            => $c->id,
                'customer_code' => $c->customer_code,
                'name'          => $c->name,
                'company_name'  => $c->company_name,
                'display_name'  => $c->display_name,
                'customer_type' => $c->customer_type,
                'phone'         => $c->phone,
                'email'         => $c->email,
                'gst_number'    => $c->gst_number,
            ]);

        return response()->json(['ok' => true, 'items' => $items]);
    }
}
