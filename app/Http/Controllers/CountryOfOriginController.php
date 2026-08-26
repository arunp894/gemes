<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCountryOfOriginRequest;
use App\Http\Requests\UpdateCountryOfOriginRequest;
use App\Models\CountryOfOrigin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * Countries of Origin CRUD — the gemstone origin lookup list.
 * Pattern mirrors ChannelController: simple lookup, no media.
 */
class CountryOfOriginController extends Controller
{
    /* ─── List ─────────────────────────────────────────────── */

    public function index(): View
    {
        // Single aggregate query instead of separate COUNT() round-trips per card.
        $counts = CountryOfOrigin::selectRaw('COUNT(*) as total, SUM(status = 1) as active, SUM(status = 0) as inactive')
            ->first();

        $stats = [
            'origins_total'    => (int) $counts->total,
            'origins_active'   => (int) $counts->active,
            'origins_inactive' => (int) $counts->inactive,
        ];

        return view('country-origins.index', compact('stats'));
    }

    public function data(Request $request): JsonResponse
    {
        $q = CountryOfOrigin::query();

        if ($request->filled('status') && $request->query('status') !== '') {
            $q->where('status', (bool) $request->query('status'));
        }

        return DataTables::of($q)
            ->addIndexColumn()
            ->editColumn('name', fn (CountryOfOrigin $o) =>
                '<a href="' . route('country-origins.show', $o) . '" class="origin-name-link">' . e($o->name) . '</a>'
            )
            ->editColumn('status', function (CountryOfOrigin $o) {
                $class = $o->isActive() ? 'status-pill status-active' : 'status-pill status-inactive';
                return '<span class="' . $class . '"><span class="status-dot"></span>' . e($o->statusLabel()) . '</span>';
            })
            ->editColumn('display_order', fn (CountryOfOrigin $o) =>
                '<span class="badge bg-light text-dark">' . $o->display_order . '</span>'
            )
            ->addColumn('action', function (CountryOfOrigin $o) {
                $show    = route('country-origins.show', $o);
                $edit    = route('country-origins.edit', $o);
                $toggle  = route('country-origins.toggle-status', $o);
                $destroy = route('country-origins.destroy', $o);
                $toggleIcon = $o->isActive() ? 'ti-toggle-right' : 'ti-toggle-left';

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
                            data-url="' . $destroy . '" data-name="' . e($o->name) . '" title="Delete">
                            <i class="ti ti-trash"></i>
                        </button>
                    </div>
                ';
            })
            ->filterColumn('name', function ($query, $keyword) {
                $query->where('countries_of_origin.name', 'like', "%{$keyword}%");
            })
            ->filterColumn('status', function ($query, $keyword) {
                if ($keyword === '1' || $keyword === '0') {
                    $query->where('countries_of_origin.status', (int) $keyword);
                }
            })
            ->rawColumns(['name', 'status', 'display_order', 'action'])
            ->make(true);
    }

    /* ─── Create / Store ──────────────────────────────────── */

    public function create(): View
    {
        return view('country-origins.create');
    }

    public function store(StoreCountryOfOriginRequest $request): JsonResponse
    {
        $origin = CountryOfOrigin::create($request->validated());

        return response()->json([
            'success'  => true,
            'message'  => 'Country of origin created successfully.',
            'redirect' => route('country-origins.index'),
            'data'     => $origin,
        ], 201);
    }

    /* ─── Show ─────────────────────────────────────────────── */

    public function show(CountryOfOrigin $countryOrigin): View
    {
        $countryOrigin->load(['creator', 'updater']);
        return view('country-origins.show', ['origin' => $countryOrigin]);
    }

    /* ─── Edit / Update ────────────────────────────────────── */

    public function edit(CountryOfOrigin $countryOrigin): View
    {
        return view('country-origins.edit', ['origin' => $countryOrigin]);
    }

    public function update(UpdateCountryOfOriginRequest $request, CountryOfOrigin $countryOrigin): JsonResponse
    {
        $countryOrigin->update($request->validated());

        return response()->json([
            'success'  => true,
            'message'  => 'Country of origin updated successfully.',
            'redirect' => route('country-origins.index'),
            'data'     => $countryOrigin->fresh(),
        ]);
    }

    /* ─── Destroy ───────────────────────────────────────────── */

    public function destroy(CountryOfOrigin $countryOrigin): JsonResponse
    {
        $countryOrigin->delete();

        return response()->json([
            'success' => true,
            'message' => 'Country of origin deleted successfully.',
        ]);
    }

    /* ─── Toggle Status ─────────────────────────────────────── */

    public function toggleStatus(CountryOfOrigin $countryOrigin): JsonResponse
    {
        $countryOrigin->status = ! $countryOrigin->status;
        $countryOrigin->save();

        return response()->json([
            'success' => true,
            'status'  => (bool) $countryOrigin->status,
            'label'   => $countryOrigin->statusLabel(),
            'message' => 'Status updated.',
        ]);
    }
}
