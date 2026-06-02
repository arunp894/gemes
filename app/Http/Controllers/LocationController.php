<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * Locations CRUD.
 *
 * Mirrors SupplierController:
 *   - DataTables AJAX listing at /locations/data
 *   - PATCH /locations/{location}/toggle-status   — active flip
 *   - PATCH /locations/{location}/set-default     — promote to default
 *   - JSON responses for AJAX / FormData submissions; classic redirects otherwise
 */
class LocationController extends Controller
{
    /**
     * Listing page.
     */
    public function index(): View
    {
        return view('locations.index');
    }

    /**
     * DataTables AJAX endpoint.
     */
    public function data(Request $request): JsonResponse
    {
        $query = Location::query()->with('manager:id,name');

        return DataTables::of($query)
            ->addColumn('checkbox', function (Location $location) {
                return '<input class="form-check-input form-check-input-light fs-14 product-item-check mt-0" '
                    . 'type="checkbox" value="' . $location->id . '" />';
            })
            ->editColumn('location_code', function (Location $location) {
                $defaultBadge = $location->is_default
                    ? ' <span class="badge badge-soft-warning fs-xxs ms-1" title="Default location">DEFAULT</span>'
                    : '';
                return '<code class="text-muted">' . e($location->location_code) . '</code>' . $defaultBadge;
            })
            ->editColumn('name', function (Location $location) {
                $initial = strtoupper(mb_substr($location->name, 0, 1) ?: '?');
                $sub = $location->short_address ?: ($location->phone ?: '');

                return '
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm me-2">
                            <span class="avatar-title bg-primary-subtle text-primary rounded-circle fw-bold">'
                    . e($initial) .
                    '</span>
                        </div>
                        <div>
                            <h5 class="mb-0 fs-base">
                                <a href="' . route('locations.show', $location) . '" class="link-reset">'
                    . e($location->name) .
                    '</a>
                            </h5>
                            <small class="text-muted">' . e($sub) . '</small>
                        </div>
                    </div>
                ';
            })
            ->addColumn('type_badge', function (Location $location) {
                return '<span class="badge ' . $location->typeBadgeClass() . ' fs-xxs">'
                    . e($location->typeLabel())
                    . '</span>';
            })
            ->addColumn('manager', function (Location $location) {
                if (! $location->manager) {
                    return '<span class="text-muted fs-xs">—</span>';
                }
                return '<span>' . e($location->manager->name) . '</span>';
            })
            ->addColumn('contact', function (Location $location) {
                $phone = $location->phone ? e($location->phone) : '';
                $email = $location->email ? '<small class="d-block text-muted">' . e($location->email) . '</small>' : '';
                if (! $phone && ! $email) {
                    return '<span class="text-muted fs-xs">—</span>';
                }
                return '<div><span>' . $phone . '</span>' . $email . '</div>';
            })
            ->addColumn('status_badge', function (Location $location) {
                return '<span class="badge ' . $location->statusBadgeClass() . ' fs-xxs">'
                    . e($location->statusLabel())
                    . '</span>';
            })
            ->editColumn('created_at', function (Location $location) {
                return optional($location->created_at)->format('d M, Y') ?? '—';
            })
            ->addColumn('action', function (Location $location) {
                $show       = route('locations.show', $location);
                $edit       = route('locations.edit', $location);
                $toggle     = route('locations.toggle-status', $location);
                $setDefault = route('locations.set-default', $location);
                $destroy    = route('locations.destroy', $location);

                $toggleIcon = $location->isActive() ? 'ti-toggle-right' : 'ti-toggle-left';

                // Default-promote button is shown only when this row isn't
                // already the default. Defaults can be transferred but not
                // unset directly from the list (must pick a new default).
                $defaultBtn = $location->is_default
                    ? '<button type="button" class="btn btn-default btn-icon btn-sm" disabled title="Already default">
                        <i class="ti ti-star-filled fs-lg text-warning"></i>
                       </button>'
                    : '<button type="button" class="btn btn-default btn-icon btn-sm js-set-default"
                        data-url="' . $setDefault . '" data-name="' . e($location->name) . '" title="Set as default">
                        <i class="ti ti-star fs-lg"></i>
                       </button>';

                return '
                    <div class="d-flex justify-content-center gap-1">
                        <a href="' . $show . '" class="btn btn-default btn-icon btn-sm" title="View">
                            <i class="ti ti-eye fs-lg"></i>
                        </a>
                        <a href="' . $edit . '" class="btn btn-default btn-icon btn-sm" title="Edit">
                            <i class="ti ti-edit fs-lg"></i>
                        </a>
                        ' . $defaultBtn . '
                        <button type="button" class="btn btn-default btn-icon btn-sm js-toggle-status"
                            data-url="' . $toggle . '" title="Toggle Status">
                            <i class="ti ' . $toggleIcon . ' fs-lg"></i>
                        </button>
                        <button type="button" class="btn btn-default btn-icon btn-sm js-delete text-danger"
                            data-url="' . $destroy . '" data-name="' . e($location->name) . '" title="Delete">
                            <i class="ti ti-trash fs-lg"></i>
                        </button>
                    </div>
                ';
            })
            ->filterColumn('name', function ($query, $keyword) {
                $like = "%{$keyword}%";
                $query->where(function ($q) use ($like) {
                    $q->where('locations.name', 'like', $like)
                        ->orWhere('locations.location_code', 'like', $like)
                        ->orWhere('locations.city', 'like', $like)
                        ->orWhere('locations.state', 'like', $like)
                        ->orWhere('locations.country', 'like', $like)
                        ->orWhere('locations.phone', 'like', $like)
                        ->orWhere('locations.email', 'like', $like);
                });
            })
            ->filterColumn('type_badge', function ($query, $keyword) {
                if ($keyword !== '' && $keyword !== null) {
                    $query->where('locations.type', $keyword);
                }
            })
            ->filterColumn('status_badge', function ($query, $keyword) {
                if ($keyword === '1' || $keyword === '0') {
                    $query->where('locations.status', (int) $keyword);
                }
            })
            ->rawColumns(['checkbox', 'location_code', 'name', 'type_badge', 'manager', 'contact', 'status_badge', 'action'])
            ->make(true);
    }

    /**
     * Show the create form.
     */
    public function create(): View
    {
        $nextCode = Location::generateNextCode();
        $managers = $this->managerOptions();
        $types    = Location::TYPES;

        return view('locations.create', compact('nextCode', 'managers', 'types'));
    }

    /**
     * Store a new location.
     */
    public function store(StoreLocationRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        $location = DB::transaction(function () use ($data) {
            return Location::create([
                'location_code' => $data['location_code'] ?? null, // model auto-generates if null
                'name'          => $data['name'],
                'type'          => $data['type'],
                'description'   => $data['description'] ?? null,
                'manager_id'    => $data['manager_id'] ?? null,
                'address_line1' => $data['address_line1'] ?? null,
                'address_line2' => $data['address_line2'] ?? null,
                'city'          => $data['city'] ?? null,
                'state'         => $data['state'] ?? null,
                'country'       => $data['country'] ?? null,
                'zip_code'      => $data['zip_code'] ?? null,
                'phone'         => $data['phone'] ?? null,
                'email'         => $data['email'] ?? null,
                'latitude'      => $data['latitude'] ?? null,
                'longitude'     => $data['longitude'] ?? null,
                'is_default'    => (bool) $data['is_default'],
                'status'        => (bool) $data['status'],
                'notes'         => $data['notes'] ?? null,
            ]);
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Location created successfully.',
                'redirect' => route('locations.index'),
                'data'     => $location,
            ]);
        }

        return redirect()
            ->route('locations.index')
            ->with('success', 'Location created successfully.');
    }

    /**
     * Display a single location.
     */
    public function show(Location $location): View
    {
        $location->load(['manager', 'creator', 'updater']);
        return view('locations.show', compact('location'));
    }

    /**
     * Show the edit form.
     */
    public function edit(Location $location): View
    {
        $managers = $this->managerOptions();
        $types    = Location::TYPES;
        return view('locations.edit', compact('location', 'managers', 'types'));
    }

    /**
     * Update an existing location.
     */
    public function update(UpdateLocationRequest $request, Location $location): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($location, $data) {
            $location->fill([
                // location_code is intentionally not updated — immutable in UI.
                'name'          => $data['name'],
                'type'          => $data['type'],
                'description'   => $data['description'] ?? null,
                'manager_id'    => $data['manager_id'] ?? null,
                'address_line1' => $data['address_line1'] ?? null,
                'address_line2' => $data['address_line2'] ?? null,
                'city'          => $data['city'] ?? null,
                'state'         => $data['state'] ?? null,
                'country'       => $data['country'] ?? null,
                'zip_code'      => $data['zip_code'] ?? null,
                'phone'         => $data['phone'] ?? null,
                'email'         => $data['email'] ?? null,
                'latitude'      => $data['latitude'] ?? null,
                'longitude'     => $data['longitude'] ?? null,
                'is_default'    => (bool) $data['is_default'],
                'status'        => (bool) $data['status'],
                'notes'         => $data['notes'] ?? null,
            ])->save();
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Location updated successfully.',
                'redirect' => route('locations.index'),
                'data'     => $location->fresh(),
            ]);
        }

        return redirect()
            ->route('locations.index')
            ->with('success', 'Location updated successfully.');
    }

    /**
     * Soft-delete a location.
     *
     * Refuses to delete the default location — there must always be one,
     * and demoting it should be an explicit user action.
     */
    public function destroy(Location $location, Request $request): JsonResponse|RedirectResponse
    {
        if ($location->is_default) {
            $msg = 'Cannot delete the default location. Promote another location to default first.';

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->route('locations.index')->with('error', $msg);
        }

        $location->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Location deleted successfully.',
            ]);
        }

        return redirect()
            ->route('locations.index')
            ->with('success', 'Location deleted successfully.');
    }

    /**
     * Toggle Active / Inactive.
     */
    public function toggleStatus(Location $location): JsonResponse
    {
        // Don't allow deactivating the default location — sales need a
        // sane fallback to point at.
        if ($location->is_default && $location->status) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot deactivate the default location.',
            ], 422);
        }

        $location->status = ! $location->status;
        $location->save();

        return response()->json([
            'success' => true,
            'status'  => (bool) $location->status,
            'label'   => $location->statusLabel(),
            'message' => 'Status updated.',
        ]);
    }

    /**
     * Promote this location to be the default. The saving() event on
     * Location demotes any other default in the same transaction.
     */
    public function setDefault(Location $location): JsonResponse
    {
        if (! $location->status) {
            return response()->json([
                'success' => false,
                'message' => 'An inactive location cannot be set as default.',
            ], 422);
        }

        $location->is_default = true;
        $location->save();

        return response()->json([
            'success' => true,
            'message' => 'Default location updated.',
        ]);
    }

    /* -----------------------------------------------------------------
     |  Helpers
     | -----------------------------------------------------------------
     */

    /**
     * Slim user list for the manager dropdown. Only active users — the
     * UI shouldn't surface soft-deleted or inactive staff as assignable.
     * SoftDeletes on User already excludes deleted rows.
     */
    protected function managerOptions()
    {
        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }
}
