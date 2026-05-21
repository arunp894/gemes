<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePermissionRequest;
use App\Http\Requests\UpdatePermissionRequest;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * Permissions CRUD.
 *
 * Permissions are atomic capabilities ("module.action") attached to roles.
 * Users never receive permissions directly — only through their roles.
 *
 * Safety guard: a permission still attached to any role cannot be deleted
 * (the destroy endpoint detaches and deletes inside a transaction, but the
 * UI confirms the impact first).
 */
class PermissionController extends Controller
{
    /**
     * Listing page. Module dropdown lets admins filter by module.
     */
    public function index(): View
    {
        $modules = Permission::query()
            ->select('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module');

        return view('permissions.index', compact('modules'));
    }

    /**
     * DataTables AJAX endpoint.
     */
    public function data(Request $request): JsonResponse
    {
        $query = Permission::query()->withCount('roles');

        return DataTables::of($query)
            ->editColumn('name', function (Permission $permission) {
                return '
                    <h5 class="mb-0 fs-base">
                        <a href="' . route('permissions.show', $permission) . '" class="link-reset">'
                            . e($permission->name) .
                        '</a>
                    </h5>
                    <small class="text-muted">' . e($permission->description ?? '—') . '</small>
                ';
            })
            ->editColumn('slug', function (Permission $permission) {
                return '<code class="text-muted">' . e($permission->slug) . '</code>';
            })
            ->editColumn('module', function (Permission $permission) {
                return '<span class="badge badge-soft-primary fs-xxs text-uppercase">'
                    . e($permission->module) .
                '</span>';
            })
            ->addColumn('roles_count_badge', function (Permission $permission) {
                $class = $permission->roles_count > 0 ? 'badge-soft-info' : 'badge-soft-secondary';
                return '<span class="badge ' . $class . ' fs-xxs">' . (int) $permission->roles_count . '</span>';
            })
            ->editColumn('updated_at', function (Permission $permission) {
                $dt = $permission->updated_at;
                if (! $dt) return '—';
                return $dt->format('d M, Y') . ' <small class="text-muted">' . $dt->format('h:i A') . '</small>';
            })
            ->addColumn('action', function (Permission $permission) {
                $show    = route('permissions.show', $permission);
                $edit    = route('permissions.edit', $permission);
                $destroy = route('permissions.destroy', $permission);

                return '
                    <div class="d-flex justify-content-center gap-1">
                        <a href="' . $show . '" class="btn btn-default btn-icon btn-sm" title="View">
                            <i class="ti ti-eye fs-lg"></i>
                        </a>
                        <a href="' . $edit . '" class="btn btn-default btn-icon btn-sm" title="Edit">
                            <i class="ti ti-edit fs-lg"></i>
                        </a>
                        <button type="button" class="btn btn-default btn-icon btn-sm js-delete text-danger"
                            data-url="' . $destroy . '" data-name="' . e($permission->name) . '" title="Delete">
                            <i class="ti ti-trash fs-lg"></i>
                        </button>
                    </div>
                ';
            })
            ->filterColumn('name', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('permissions.name', 'like', "%{$keyword}%")
                      ->orWhere('permissions.slug', 'like', "%{$keyword}%")
                      ->orWhere('permissions.description', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('module', function ($query, $keyword) {
                $query->where('permissions.module', $keyword);
            })
            ->rawColumns(['name', 'slug', 'module', 'roles_count_badge', 'updated_at', 'action'])
            ->make(true);
    }

    /**
     * Show the create form. Existing modules become datalist suggestions.
     */
    public function create(): View
    {
        $modules = Permission::query()->distinct()->orderBy('module')->pluck('module');
        return view('permissions.create', compact('modules'));
    }

    /**
     * Store a new permission.
     */
    public function store(StorePermissionRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        $permission = Permission::create([
            'name'        => $data['name'],
            'slug'        => $data['slug'],
            'module'      => $data['module'],
            'description' => $data['description'] ?? null,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Permission created successfully.',
                'redirect' => route('permissions.index'),
                'data'     => $permission,
            ]);
        }

        return redirect()
            ->route('permissions.index')
            ->with('success', 'Permission created successfully.');
    }

    /**
     * Display a single permission with the roles that hold it.
     */
    public function show(Permission $permission): View
    {
        $permission->load('roles:id,name,slug,is_super');
        return view('permissions.show', compact('permission'));
    }

    /**
     * Show the edit form.
     */
    public function edit(Permission $permission): View
    {
        $modules = Permission::query()->distinct()->orderBy('module')->pluck('module');
        return view('permissions.edit', compact('permission', 'modules'));
    }

    /**
     * Update an existing permission.
     */
    public function update(UpdatePermissionRequest $request, Permission $permission): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        $payload = [
            'name'        => $data['name'],
            'module'      => $data['module'],
            'description' => $data['description'] ?? null,
        ];

        if (array_key_exists('slug', $data)) {
            $payload['slug'] = $data['slug'];
        }

        $permission->fill($payload)->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Permission updated successfully.',
                'redirect' => route('permissions.index'),
                'data'     => $permission->fresh(),
            ]);
        }

        return redirect()
            ->route('permissions.index')
            ->with('success', 'Permission updated successfully.');
    }

    /**
     * Soft-delete a permission. Detaches from any roles first.
     * The UI surfaces the impact (role count) before submission.
     */
    public function destroy(Permission $permission, Request $request): JsonResponse|RedirectResponse
    {
        DB::transaction(function () use ($permission) {
            $permission->roles()->detach();
            $permission->delete();
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Permission deleted successfully.',
            ]);
        }

        return redirect()
            ->route('permissions.index')
            ->with('success', 'Permission deleted successfully.');
    }
}
