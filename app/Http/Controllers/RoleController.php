<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * Roles CRUD.
 *
 * A role aggregates permissions and is assigned to users. A role flagged
 * `is_super` bypasses every permission check at runtime.
 *
 * Safety guards:
 *   - You cannot delete a role that still has users assigned to it.
 *   - You cannot delete the last `is_super` role (would leave the system
 *     without any super-admin).
 *   - You cannot demote (set is_super=false on) the last super role.
 */
class RoleController extends Controller
{
    /**
     * Listing page.
     */
    public function index(): View
    {
        return view('roles.index');
    }

    /**
     * DataTables AJAX endpoint.
     */
    public function data(Request $request): JsonResponse
    {
        $query = Role::query()
            ->withCount(['users', 'permissions']);

        return DataTables::of($query)
            ->editColumn('name', function (Role $role) {
                $superBadge = $role->is_super
                    ? ' <span class="badge badge-soft-danger fs-xxs ms-1">Super</span>'
                    : '';
                return '
                    <h5 class="mb-0 fs-base">
                        <a href="' . route('roles.show', $role) . '" class="link-reset">'
                            . e($role->name) .
                        '</a>' . $superBadge . '
                    </h5>
                    <small class="text-muted">' . e($role->description ?? '—') . '</small>
                ';
            })
            ->editColumn('slug', function (Role $role) {
                return '<code class="text-muted">' . e($role->slug) . '</code>';
            })
            ->addColumn('users_count_badge', function (Role $role) {
                return '<span class="badge badge-soft-info fs-xxs">' . (int) $role->users_count . '</span>';
            })
            ->addColumn('permissions_count_badge', function (Role $role) {
                if ($role->is_super) {
                    return '<span class="badge badge-soft-danger fs-xxs">All (super)</span>';
                }
                return '<span class="badge badge-soft-secondary fs-xxs">' . (int) $role->permissions_count . '</span>';
            })
            ->editColumn('updated_at', function (Role $role) {
                $dt = $role->updated_at;
                if (! $dt) return '—';
                return $dt->format('d M, Y') . ' <small class="text-muted">' . $dt->format('h:i A') . '</small>';
            })
            ->addColumn('action', function (Role $role) {
                $show    = route('roles.show', $role);
                $edit    = route('roles.edit', $role);
                $destroy = route('roles.destroy', $role);

                return '
                    <div class="d-flex justify-content-center gap-1">
                        <a href="' . $show . '" class="btn btn-default btn-icon btn-sm" title="View">
                            <i class="ti ti-eye fs-lg"></i>
                        </a>
                        <a href="' . $edit . '" class="btn btn-default btn-icon btn-sm" title="Edit">
                            <i class="ti ti-edit fs-lg"></i>
                        </a>
                        <button type="button" class="btn btn-default btn-icon btn-sm js-delete text-danger"
                            data-url="' . $destroy . '" data-name="' . e($role->name) . '" title="Delete">
                            <i class="ti ti-trash fs-lg"></i>
                        </button>
                    </div>
                ';
            })
            ->filterColumn('name', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('roles.name', 'like', "%{$keyword}%")
                      ->orWhere('roles.slug', 'like', "%{$keyword}%")
                      ->orWhere('roles.description', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['name', 'slug', 'users_count_badge', 'permissions_count_badge', 'updated_at', 'action'])
            ->make(true);
    }

    /**
     * Show the create form with permissions grouped by module.
     */
    public function create(): View
    {
        $permissions = Permission::orderBy('module')->orderBy('name')->get();
        $groupedPermissions = $permissions->groupBy('module');
        return view('roles.create', compact('groupedPermissions'));
    }

    /**
     * Store a new role.
     */
    public function store(StoreRoleRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        $role = DB::transaction(function () use ($data) {
            $role = Role::create([
                'name'        => $data['name'],
                'slug'        => $data['slug'],
                'description' => $data['description'] ?? null,
                'is_super'    => (bool) $data['is_super'],
            ]);

            // Super roles bypass permission checks at runtime, but we still
            // attach the full permission set for display consistency.
            if ($role->is_super) {
                $role->permissions()->sync(Permission::pluck('id'));
            } else {
                $role->permissions()->sync($data['permission_ids'] ?? []);
            }

            return $role;
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Role created successfully.',
                'redirect' => route('roles.index'),
                'data'     => $role,
            ]);
        }

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role created successfully.');
    }

    /**
     * Display a single role with its permissions and users.
     */
    public function show(Role $role): View
    {
        $role->load(['permissions', 'users:id,name,email']);
        $groupedPermissions = $role->permissions->groupBy('module');
        return view('roles.show', compact('role', 'groupedPermissions'));
    }

    /**
     * Show the edit form.
     */
    public function edit(Role $role): View
    {
        $role->load('permissions:id');
        $permissions = Permission::orderBy('module')->orderBy('name')->get();
        $groupedPermissions = $permissions->groupBy('module');
        $assignedPermissionIds = $role->permissions->pluck('id')->all();
        return view('roles.edit', compact('role', 'groupedPermissions', 'assignedPermissionIds'));
    }

    /**
     * Update an existing role.
     * Demotion safety: prevents removing is_super from the last super role.
     */
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $becomingNonSuper = $role->is_super && empty($data['is_super']);

        if ($becomingNonSuper) {
            $otherSupers = Role::where('is_super', true)
                ->where('id', '!=', $role->id)
                ->count();
            if ($otherSupers === 0) {
                $message = 'Cannot remove super status from the last super role.';
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }
                return back()->withInput()->with('error', $message);
            }
        }

        DB::transaction(function () use ($role, $data) {
            $payload = [
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'is_super'    => (bool) $data['is_super'],
            ];

            // slug is read-only in the form, but accept it if posted.
            if (array_key_exists('slug', $data)) {
                $payload['slug'] = $data['slug'];
            }

            $role->fill($payload)->save();

            if ($role->is_super) {
                $role->permissions()->sync(Permission::pluck('id'));
            } else {
                $role->permissions()->sync($data['permission_ids'] ?? []);
            }
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Role updated successfully.',
                'redirect' => route('roles.index'),
                'data'     => $role->fresh()->load('permissions'),
            ]);
        }

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role updated successfully.');
    }

    /**
     * Soft-delete a role.
     * Guards:
     *   - role must have zero users attached
     *   - cannot delete the last super role
     */
    public function destroy(Role $role, Request $request): JsonResponse|RedirectResponse
    {
        $userCount = $role->users()->count();
        if ($userCount > 0) {
            return $this->failure(
                $request,
                "Cannot delete: this role is still assigned to {$userCount} user"
                . ($userCount === 1 ? '' : 's') . '. Reassign them first.',
            );
        }

        if ($role->is_super) {
            $otherSupers = Role::where('is_super', true)
                ->where('id', '!=', $role->id)
                ->count();
            if ($otherSupers === 0) {
                return $this->failure(
                    $request,
                    'Cannot delete the last super role.',
                );
            }
        }

        DB::transaction(function () use ($role) {
            $role->permissions()->detach();
            $role->delete();
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully.',
            ]);
        }

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    private function failure(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }
        return back()->with('error', $message);
    }
}
