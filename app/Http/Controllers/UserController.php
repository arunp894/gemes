<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * Users CRUD.
 *
 * Users may hold multiple roles (BelongsToMany). The effective permission
 * set is the union of every assigned role's permissions, with super-role
 * holders bypassing every gate (see User::isSuperAdmin).
 *
 * Safety guards:
 *   - You cannot delete or deactivate yourself.
 *   - You cannot delete the last remaining super-admin user.
 */
class UserController extends Controller
{
    /**
     * Listing page + the create-modal role dropdown.
     */
    public function index(): View
    {
        $roles = Role::orderBy('name')->get(['id', 'name', 'slug', 'is_super']);
        return view('users.index', compact('roles'));
    }

    /**
     * DataTables AJAX endpoint.
     * Eager-loads roles so the badge column doesn't N+1.
     */
    public function data(Request $request): JsonResponse
    {
        $query = User::query()->with('roles:id,name,slug,is_super');

        return DataTables::of($query)
            ->addColumn('checkbox', function (User $user) {
                return '<input class="form-check-input form-check-input-light fs-14 product-item-check mt-0" '
                    . 'type="checkbox" value="' . $user->id . '" />';
            })
            ->editColumn('name', function (User $user) {
                $initial = strtoupper(mb_substr($user->name, 0, 1));
                return '
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm me-2">
                            <span class="avatar-title bg-primary-subtle text-primary rounded-circle fw-bold">'
                                . e($initial) .
                            '</span>
                        </div>
                        <div>
                            <h5 class="mb-0 fs-base">
                                <a href="' . route('users.show', $user) . '" class="link-reset">'
                                    . e($user->name) .
                                '</a>
                            </h5>
                            <small class="text-muted">' . e($user->email) . '</small>
                        </div>
                    </div>
                ';
            })
            ->addColumn('roles_badges', function (User $user) {
                if ($user->roles->isEmpty()) {
                    return '<span class="text-muted fs-xs">—</span>';
                }
                return $user->roles->map(function (Role $role) {
                    $class = $role->is_super ? 'badge-soft-danger' : 'badge-soft-info';
                    return '<span class="badge ' . $class . ' fs-xxs me-1">' . e($role->name) . '</span>';
                })->implode('');
            })
            ->addColumn('status_badge', function (User $user) {
                $class = $user->is_active ? 'badge-soft-success' : 'badge-soft-danger';
                $label = $user->is_active ? 'Active' : 'Inactive';
                return '<span class="badge ' . $class . ' fs-xxs">' . $label . '</span>';
            })
            ->editColumn('created_at', function (User $user) {
                return optional($user->created_at)->format('d M, Y') ?? '—';
            })
            ->addColumn('action', function (User $user) {
                $isSelf  = auth()->id() === $user->id;
                $show    = route('users.show', $user);
                $edit    = route('users.edit', $user);
                $toggle  = route('users.toggle-status', $user);
                $destroy = route('users.destroy', $user);

                $toggleIcon = $user->is_active ? 'ti-toggle-right' : 'ti-toggle-left';
                $disabled   = $isSelf ? 'disabled title="Cannot act on your own account"' : '';

                return '
                    <div class="d-flex justify-content-center gap-1">
                        <a href="' . $show . '" class="btn btn-default btn-icon btn-sm" title="View">
                            <i class="ti ti-eye fs-lg"></i>
                        </a>
                        <a href="' . $edit . '" class="btn btn-default btn-icon btn-sm" title="Edit">
                            <i class="ti ti-edit fs-lg"></i>
                        </a>
                        <button type="button" class="btn btn-default btn-icon btn-sm js-toggle-status" ' . $disabled . '
                            data-url="' . $toggle . '" title="Toggle Status">
                            <i class="ti ' . $toggleIcon . ' fs-lg"></i>
                        </button>
                        <button type="button" class="btn btn-default btn-icon btn-sm js-delete text-danger" ' . $disabled . '
                            data-url="' . $destroy . '" data-name="' . e($user->name) . '" title="Delete">
                            <i class="ti ti-trash fs-lg"></i>
                        </button>
                    </div>
                ';
            })
            ->filterColumn('name', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('users.name', 'like', "%{$keyword}%")
                      ->orWhere('users.email', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('status_badge', function ($query, $keyword) {
                if ($keyword === '1' || $keyword === '0') {
                    $query->where('users.is_active', (int) $keyword);
                }
            })
            ->rawColumns(['checkbox', 'name', 'roles_badges', 'status_badge', 'action'])
            ->make(true);
    }

    /**
     * Show the create form.
     */
    public function create(): View
    {
        $roles = Role::orderBy('name')->get(['id', 'name', 'slug', 'description', 'is_super']);
        return view('users.create', compact('roles'));
    }

    /**
     * Store a new user with role assignments.
     */
    public function store(StoreUserRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => Hash::make($data['password']),
                'is_active' => (bool) $data['is_active'],
            ]);

            $user->roles()->sync($data['role_ids']);

            return $user;
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => 'User created successfully.',
                'redirect' => route('users.index'),
                'data'     => $user->load('roles'),
            ]);
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display a single user with roles and computed permissions.
     */
    public function show(User $user): View
    {
        $user->load(['roles.permissions']);
        $permissionSlugs = $user->permissionSlugs();
        return view('users.show', compact('user', 'permissionSlugs'));
    }

    /**
     * Show the edit form.
     */
    public function edit(User $user): View
    {
        $user->load('roles:id');
        $roles = Role::orderBy('name')->get(['id', 'name', 'slug', 'description', 'is_super']);
        $assignedRoleIds = $user->roles->pluck('id')->all();
        return view('users.edit', compact('user', 'roles', 'assignedRoleIds'));
    }

    /**
     * Update an existing user.
     * Password update is optional — only re-hashes when a new one is supplied.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        // Self-edit safety: prevent the current user from removing their own
        // last super role (which would lock them out of the admin area).
        if (auth()->id() === $user->id && $user->isSuperAdmin()) {
            $newRoleIds = $data['role_ids'] ?? [];
            $stillSuper = Role::whereIn('id', $newRoleIds)->where('is_super', true)->exists();
            if (! $stillSuper) {
                $message = 'You cannot remove your own super-admin access.';
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }
                return back()->withInput()->with('error', $message);
            }
        }

        DB::transaction(function () use ($user, $data) {
            $payload = [
                'name'      => $data['name'],
                'email'     => $data['email'],
                'is_active' => (bool) $data['is_active'],
            ];

            if (! empty($data['password'])) {
                $payload['password'] = Hash::make($data['password']);
            }

            $user->fill($payload)->save();
            $user->roles()->sync($data['role_ids']);
            $user->flushPermissionCache();
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => 'User updated successfully.',
                'redirect' => route('users.index'),
                'data'     => $user->fresh()->load('roles'),
            ]);
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Soft-delete a user.
     * Guards:
     *   - cannot delete yourself
     *   - cannot delete the last remaining super-admin
     */
    public function destroy(User $user, Request $request): JsonResponse|RedirectResponse
    {
        if (auth()->id() === $user->id) {
            return $this->failure($request, 'You cannot delete your own account.');
        }

        if ($user->isSuperAdmin()) {
            $remainingSupers = User::query()
                ->whereHas('roles', fn ($q) => $q->where('is_super', true))
                ->where('id', '!=', $user->id)
                ->count();

            if ($remainingSupers === 0) {
                return $this->failure(
                    $request,
                    'Cannot delete the last super-admin. Assign super-admin to another user first.',
                );
            }
        }

        DB::transaction(function () use ($user) {
            $user->roles()->detach();
            $user->delete();
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully.',
            ]);
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Toggle the user's is_active flag.
     * Cannot deactivate yourself.
     */
    public function toggleStatus(User $user, Request $request): JsonResponse
    {
        if (auth()->id() === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot deactivate your own account.',
            ], 422);
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        return response()->json([
            'success' => true,
            'active'  => (bool) $user->is_active,
            'label'   => $user->is_active ? 'Active' : 'Inactive',
            'message' => 'Status updated.',
        ]);
    }

    /**
     * Uniform JSON/redirect failure helper.
     */
    private function failure(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }
        return back()->with('error', $message);
    }
}
