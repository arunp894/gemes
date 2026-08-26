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
     * Display the listing page.
     */
    public function index(): View
    {
        // Single aggregate query instead of separate COUNT() round-trips per card.
        $counts = Category::selectRaw('COUNT(*) as total, SUM(status = 1) as active, SUM(status = 0) as inactive')
            ->first();

        $stats = [
            'stones_total'    => (int) $counts->total,
            'stones_active'   => (int) $counts->active,
            'stones_inactive' => (int) $counts->inactive,
        ];

        return view('categories.index', compact('stats'));
    }

    /**
     * DataTables AJAX endpoint.
     * Each row includes children_count from the self-referential relation.
     */
    public function data(Request $request): JsonResponse
    {
        // Eager-load media so the thumbnail column doesn't issue one query per row.
        $query = Category::query()->with('media');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('checkbox', function (Category $category) {
                return '<input class="form-check-input form-check-input-light fs-14 product-item-check mt-0" '
                    . 'type="checkbox" value="' . $category->id . '" />';
            })
            ->editColumn('name', function (Category $category) {
                $thumb = $category->thumb_url;
                $imgHtml = $thumb
                    ? '<img src="' . e($thumb) . '" alt="' . e($category->name) . '" class="stone-thumb-img" />'
                    : '<span class="stone-thumb-placeholder"><i class="ti ti-photo"></i></span>';

                return '
                    <div class="stone-name-cell">
                        <div class="stone-thumb">' . $imgHtml . '</div>
                        <div>
                            <a href="' . route('categories.show', $category) . '" class="stone-name-link">'
                                . e($category->name) .
                            '</a>
                        </div>
                    </div>
                ';
            })
            ->editColumn('code', function (Category $category) {
                return '<code class="text-muted">' . e($category->code) . '</code>';
            })
            ->addColumn('status_badge', function (Category $category) {
                $class = $category->isActive() ? 'status-pill status-active' : 'status-pill status-inactive';
                $label = $category->statusLabel();
                return '<span class="' . $class . '"><span class="status-dot"></span>' . $label . '</span>';
            })
            ->editColumn('updated_at', function (Category $category) {
                $dt = $category->updated_at;
                return $dt ? $dt->format('d M, Y') : '—';
            })
            ->addColumn('action', function (Category $category) {
                $show    = route('categories.show', $category);
                $edit    = route('categories.edit', $category);
                $toggle  = route('categories.toggle-status', $category);
                $destroy = route('categories.destroy', $category);

                $toggleIcon = $category->isActive() ? 'ti-toggle-right' : 'ti-toggle-left';

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
                            data-url="' . $destroy . '" data-name="' . e($category->name) . '" title="Delete">
                            <i class="ti ti-trash"></i>
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
        return view('categories.create');
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
                'display_order' => $data['display_order'] ?? 0,
                'status'        => (bool) $data['status'],
                'is_gemstone'   => (bool) ($data['is_gemstone'] ?? false),
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
                'message'  => 'Stone created successfully.',
                'redirect' => route('categories.index'),
                'data'     => $category,
            ]);
        }

        return redirect()
            ->route('categories.index')
            ->with('success', 'Stone created successfully.');
    }

    /**
     * Display a single category.
     */
    public function show(Category $category): View
    {
        $category->load(['creator', 'updater']);
        return view('categories.show', compact('category'));
    }

    /**
     * Show the edit form.
     */
    public function edit(Category $category): View
    {
        return view('categories.edit', compact('category'));
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
                'is_gemstone'   => (bool) ($data['is_gemstone'] ?? false),
            ];

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
                'message'  => 'Stone updated successfully.',
                'redirect' => route('categories.index'),
                'data'     => $category->fresh(),
            ]);
        }

        return redirect()
            ->route('categories.index')
            ->with('success', 'Stone updated successfully.');
    }

    /**
     * Soft-delete a category.
     */
    public function destroy(Category $category, Request $request): JsonResponse|RedirectResponse
    {
        $category->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Stone deleted successfully.',
            ]);
        }

        return redirect()
            ->route('categories.index')
            ->with('success', 'Stone deleted successfully.');
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
