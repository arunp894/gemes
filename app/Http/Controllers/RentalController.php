<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Rental;
use App\Models\RentalLine;
use App\Models\SalePayment;
use App\Models\User;
use App\Services\RentalService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

/** Stone Memo: list (with an Overdue tab), create, return, cancel, extend, print. */
class RentalController extends Controller
{
    public function __construct(private RentalService $service, private SettingService $settings) {}

    public function index(): View
    {
        $c = Rental::selectRaw(
            "COUNT(*) as total,
             SUM(status = 'active') as active,
             SUM(status = 'active' AND due_at < ?) as overdue,
             SUM(status = 'returned') as returned",
            [Rental::wallNow()]
        )->first();

        return view('rentals.index', ['stats' => [
            'total'    => (int) $c->total,
            'active'   => (int) $c->active,
            'overdue'  => (int) $c->overdue,
            'returned' => (int) $c->returned,
        ]]);
    }

    public function data(Request $request): JsonResponse
    {
        $q = Rental::query()->with(['customer:id,name,company_name', 'location:id,name'])->withCount('lines')
            ->withCount(['lines as out_count' => fn ($l) => $l->where('status', RentalLine::STATUS_OUT)]);

        match ($request->query('tab')) {
            'active'   => $q->where('status', Rental::STATUS_ACTIVE),
            'overdue'  => $q->overdue(),
            'returned' => $q->whereIn('status', [Rental::STATUS_RETURNED, Rental::STATUS_CANCELLED]),
            default    => null,
        };
        if ($cid = $request->query('customer_id')) {
            $q->where('customer_id', $cid);
        }

        $money = fn ($v) => $this->settings->formatMoney($v);

        return DataTables::eloquent($q)
            ->addIndexColumn()
            ->editColumn('rent_number', fn (Rental $r) => '<a href="' . route('rentals.show', $r) . '" class="link-reset"><code>' . e($r->rent_number) . '</code></a>')
            ->addColumn('customer_label', fn (Rental $r) => e($r->customer?->display_name ?? '—'))
            ->addColumn('location_label', fn (Rental $r) => e($r->location?->name ?? '—'))
            ->editColumn('start_at', fn (Rental $r) => $r->start_at->format('d M Y H:i'))
            ->editColumn('due_at', fn (Rental $r) => $r->due_at->format('d M Y H:i')
                . ($r->isOverdue() ? '<div class="text-danger fs-xxs">' . $r->overdueDays() . ' day(s) late</div>' : ''))
            ->addColumn('stones', fn (Rental $r) => $r->out_count . ' / ' . $r->lines_count . ' out')
            ->editColumn('rent_total', fn (Rental $r) => $money($r->rent_total))
            ->addColumn('deposit_held', fn (Rental $r) => $money(max(0, $r->depositHeld())))
            ->addColumn('status_badge', fn (Rental $r) => '<span class="badge ' . $r->statusBadgeClass() . ' fs-xxs">' . e($r->statusLabel()) . '</span>')
            ->addColumn('actions', fn (Rental $r) => '<a href="' . route('rentals.show', $r) . '" class="action-btn action-view" title="View"><i class="ti ti-eye"></i></a>')
            ->filterColumn('customer_label', fn ($query, $kw) => $query->whereHas('customer',
                fn ($c) => $c->where('name', 'like', "%{$kw}%")->orWhere('company_name', 'like', "%{$kw}%")))
            ->rawColumns(['rent_number', 'due_at', 'status_badge', 'actions'])
            ->toJson();
    }

    public function create(): View
    {
        /** @var User $user */
        $user      = auth()->user();
        $locations = $user->locations()->active()->ordered()->get(['locations.id', 'name', 'is_default']);

        return view('rentals.create', [
            'locations'      => $locations,
            'defaultLocId'   => ($locations->firstWhere('is_default', true) ?? $locations->first())?->id,
            'paymentMethods' => SalePayment::METHODS,
            'currency'       => $this->settings->get('currency_symbol', '₹'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_id'                    => ['required', 'integer', 'exists:customers,id'],
            'location_id'                    => ['required', 'integer', 'exists:locations,id'],
            'start_at'                       => ['required', 'date'],
            'due_at'                         => ['required', 'date', 'after:start_at'],
            'note'                           => ['nullable', 'string', 'max:2000'],
            'payment_method'                 => ['nullable', 'string', 'max:30'],
            'rent_paid'                      => ['nullable', 'numeric', 'min:0'],
            'deposit_paid'                   => ['nullable', 'numeric', 'min:0'],
            'lines'                          => ['required', 'array', 'min:1'],
            'lines.*.purchase_product_id'    => ['required', 'integer', 'exists:purchase_products,id'],
            'lines.*.qty'                    => ['required', 'integer', 'min:1'],
            'lines.*.carat_weight'           => ['nullable', 'numeric', 'min:0'],
            'lines.*.rent_rate'              => ['nullable', 'numeric', 'min:0'],
            'lines.*.deposit_amount'         => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $rental = $this->service->create($data);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'message' => 'Memo saved.', 'redirect' => route('rentals.show', $rental)], 201);
    }

    public function show(Rental $rental): View
    {
        $rental->load(['customer', 'location', 'creator:id,name', 'payments', 'logs.user:id,name',
            'lines.product:id,title,sku', 'lines.purchaseProduct:id,lot_code']);

        return view('rentals.show', [
            'rental'         => $rental,
            'paymentMethods' => SalePayment::METHODS,
            'settings'       => $this->settings,
        ]);
    }

    public function print(Rental $rental): View
    {
        $rental->load(['customer', 'location', 'lines.product:id,title,sku', 'lines.purchaseProduct:id,lot_code']);

        return view('rentals.print', ['rental' => $rental, 'settings' => $this->settings]);
    }

    public function returnLines(Request $request, Rental $rental): JsonResponse
    {
        $d = $request->validate([
            'return_ids'     => ['nullable', 'array'],
            'return_ids.*'   => ['integer'],
            'lost_ids'       => ['nullable', 'array'],
            'lost_ids.*'     => ['integer'],
            'deduction'      => ['nullable', 'numeric', 'min:0'],
            'refund'         => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:30'],
            'note'           => ['nullable', 'string', 'max:255'],
        ]);

        return $this->run(fn () => $this->service->returnLines(
            $rental, $d['return_ids'] ?? [], $d['lost_ids'] ?? [],
            (float) ($d['deduction'] ?? 0), (float) ($d['refund'] ?? 0), $d['payment_method'] ?? 'cash', $d['note'] ?? null
        ), 'Return saved.');
    }

    public function cancel(Request $request, Rental $rental): JsonResponse
    {
        return $this->run(fn () => $this->service->cancel($rental, $request->input('payment_method', 'cash')), 'Memo cancelled.');
    }

    public function extend(Request $request, Rental $rental): JsonResponse
    {
        $d = $request->validate(['due_at' => ['required', 'date']]);

        return $this->run(fn () => $this->service->extend($rental, $d['due_at']), 'Due date extended.');
    }

    private function run(callable $fn, string $ok): JsonResponse
    {
        try {
            $fn();
        } catch (Throwable $e) {
            report($e);
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'message' => $ok]);
    }
}
