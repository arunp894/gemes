<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class CategoryController extends Controller
{
    /**
     * Display the listing page (top-level categories only).
     * Also passes the parent dropdown options for the quick-add modal.
     */
    public function index(): View
    {
        $parents = Category::active()->topLevel()->ordered()->get(['id', 'name']);
        return view('categories.index', compact('parents'));
    }

    /**
     * DataTables AJAX endpoint.
     * Returns ONLY top-level categories (parent_id IS NULL).
     * Each row includes subcategories_count from the self-referential relation.
     */
    public function data(Request $request): JsonResponse
    {
        $query = Category::query()
            ->topLevel()
            ->withCount('subcategories');

        return DataTables::of($query)
            ->addColumn('checkbox', function (Category $category) {
                return '<input class="form-check-input form-check-input-light fs-14 product-item-check mt-0" '
                    . 'type="checkbox" value="' . $category->id . '" />';
            })
            ->editColumn('name', function (Category $category) {
                $thumb = $category->thumb_url;
                $imgHtml = $thumb
                    ? '<img src="' . e($thumb) . '" alt="' . e($category->name) . '" class="img-fluid rounded" />'
                    : '<span class="d-inline-flex align-items-center justify-content-center bg-light text-muted rounded w-100 h-100"><i class="ti ti-photo"></i></span>';

                return '
                    <div class="d-flex align-items-center">
                        <div class="avatar-md me-3">' . $imgHtml . '</div>
                        <div>
                            <h5 class="mb-0">
                                <a href="' . route('categories.show', $category) . '" class="link-reset">'
                                    . e($category->name) .
                                '</a>
                            </h5>
                        </div>
                    </div>
                ';
            })
            ->editColumn('code', function (Category $category) {
                return '<code class="text-muted">' . e($category->code) . '</code>';
            })
            ->addColumn('status_badge', function (Category $category) {
                $class = $category->isActive() ? 'badge-soft-success' : 'badge-soft-danger';
                $label = $category->statusLabel();
                return '<span class="badge ' . $class . ' fs-xxs">' . $label . '</span>';
            })
            ->editColumn('updated_at', function (Category $category) {
                $dt = $category->updated_at;
                if (!$dt) {
                    return '—';
                }
                return $dt->format('d M, Y') . ' <small class="text-muted">' . $dt->format('h:i A') . '</small>';
            })
            ->addColumn('action', function (Category $category) {
                $show    = route('categories.show', $category);
                $edit    = route('categories.edit', $category);
                $toggle  = route('categories.toggle-status', $category);
                $destroy = route('categories.destroy', $category);

                $toggleIcon = $category->isActive() ? 'ti-toggle-right' : 'ti-toggle-left';

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
                            data-url="' . $destroy . '" data-name="' . e($category->name) . '" title="Delete">
                            <i class="ti ti-trash fs-lg"></i>
                        </button>
                    </div>
                ';
            })
            // Custom filter for status dropdown — exact match on 0/1
            ->filterColumn('status_badge', function ($query, $keyword) {
                if ($keyword === '1' || $keyword === '0') {
                    $query->where('categories.status', (int) $keyword);
                }
            })
            ->rawColumns(['checkbox', 'name', 'code', 'status_badge', 'updated_at', 'action'])
            ->make(true);
    }

    /**
     * Show the full create form.
     */
    public function create(): View
    {
        $parents = Category::active()->topLevel()->ordered()->get(['id', 'name']);
        return view('categories.create', compact('parents'));
    }

    /**
     * Store a newly created category. Returns JSON for AJAX modal/form submission.
     */
    public function store(StoreCategoryRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        $category = DB::transaction(function () use ($request, $data) {
            $category = Category::create([
                'name'          => $data['name'],
                'code'          => strtoupper($data['code']),
                'description'   => $data['description'] ?? null,
                'parent_id'     => $data['parent_id'] ?? null,
                'display_order' => $data['display_order'] ?? 0,
                'status'        => (bool) $data['status'],
                // is_gemstone is only meaningful for top-level categories,
                // but storing it uniformly keeps the column predictable and
                // lets the UI hide/show the checkbox without server gymnastics.
                'is_gemstone'   => empty($data['parent_id']) ? (bool) ($data['is_gemstone'] ?? false) : false,
            ]);

            if ($request->hasFile('image')) {
                $category->addMediaFromRequest('image')
                    ->toMediaCollection(Category::MEDIA_COLLECTION_IMAGE);
            }

            return $category;
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Category created successfully.',
                'redirect' => route('categories.index'),
                'data'     => $category,
            ]);
        }

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Display a single category.
     */
    public function show(Category $category): View
    {
        $category->load(['creator', 'updater', 'parent']);
        $category->loadCount('subcategories');
        return view('categories.show', compact('category'));
    }

    /**
     * Show the edit form.
     */
    public function edit(Category $category): View
    {
        // Available parents = active top-level categories EXCLUDING self.
        $parents = Category::active()
            ->topLevel()
            ->where('id', '!=', $category->id)
            ->ordered()
            ->get(['id', 'name']);

        // If this category has children, it cannot become a child itself.
        $hasChildren = $category->children()->exists();

        return view('categories.edit', compact('category', 'parents', 'hasChildren'));
    }

    /**
     * Update an existing category.
     */
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $category, $data) {
            $payload = [
                'name'          => $data['name'],
                'description'   => $data['description'] ?? null,
                'display_order' => $data['display_order'] ?? 0,
                'status'        => (bool) $data['status'],
            ];

            // Only update parent_id if the request actually sent it.
            if (array_key_exists('parent_id', $data)) {
                $payload['parent_id'] = $data['parent_id'] ?: null;
            }

            // is_gemstone only applies to top-level categories. If this
            // category currently has a parent OR is being moved under a
            // parent, force the flag false. Otherwise persist the form value.
            $effectiveParentId = array_key_exists('parent_id', $payload)
                ? $payload['parent_id']
                : $category->parent_id;

            $payload['is_gemstone'] = empty($effectiveParentId)
                ? (bool) ($data['is_gemstone'] ?? false)
                : false;

            $category->fill($payload)->save();

            if ($request->boolean('remove_image')) {
                $category->clearMediaCollection(Category::MEDIA_COLLECTION_IMAGE);
            }

            if ($request->hasFile('image')) {
                $category->clearMediaCollection(Category::MEDIA_COLLECTION_IMAGE);
                $category->addMediaFromRequest('image')
                    ->toMediaCollection(Category::MEDIA_COLLECTION_IMAGE);
            }
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Category updated successfully.',
                'redirect' => route('categories.index'),
                'data'     => $category->fresh(),
            ]);
        }

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Soft-delete a category.
     * Per spec: a category with linked children (subcategories) or products
     * cannot be deleted — it must be deactivated instead.
     */
    public function destroy(Category $category, Request $request): JsonResponse|RedirectResponse
    {
        $childCount = $category->children()->count();

        if ($childCount > 0) {
            $message = 'Cannot delete: this category has ' . $childCount . ' subcategor'
                . ($childCount === 1 ? 'y' : 'ies') . '. Deactivate it instead.';

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return back()->with('error', $message);
        }

        $category->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully.',
            ]);
        }

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    /**
     * Toggle Active / Inactive status.
     */
    public function toggleStatus(Category $category): JsonResponse
    {
        $category->status = ! $category->status;
        $category->save();

        return response()->json([
            'success' => true,
            'status'  => (bool) $category->status,
            'label'   => $category->statusLabel(),
            'message' => 'Status updated.',
        ]);
    }
}
