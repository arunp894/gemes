<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBlogRequest;
use App\Http\Requests\UpdateBlogRequest;
use App\Models\Blog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * Blog posts CRUD (admin). Pattern mirrors BannerController:
 *   - DataTables AJAX listing  GET  /blogs/data
 *   - PATCH /blogs/{blog}/toggle-status
 *   - JSON responses for AJAX; classic redirects otherwise
 *   - Featured image via Spatie MediaLibrary (blog_featured_image collection)
 *
 * Public display lives in WebsiteController::blogIndex()/blogShow().
 */
class BlogController extends Controller
{
    /* -----------------------------------------------------------------
     |  Listing
     | -----------------------------------------------------------------
     */
    public function index(): View
    {
        return view('blogs.index');
    }

    public function data(Request $request): JsonResponse
    {
        $query = Blog::query();

        return DataTables::of($query)
            ->editColumn('title', function (Blog $blog) {
                $thumb = $blog->image_thumb_url
                    ? '<img src="' . e($blog->image_thumb_url) . '" alt="" class="rounded me-2" width="48" height="32" style="object-fit:cover;">'
                    : '<span class="avatar-sm me-2"><span class="avatar-title bg-light text-muted rounded fs-xl"><i class="ti ti-article"></i></span></span>';

                return '
                    <div class="d-flex align-items-center">
                        ' . $thumb . '
                        <div>
                            <h5 class="mb-0 fs-base">
                                <a href="' . route('blogs.show', $blog) . '" class="link-reset">' . e($blog->title) . '</a>
                            </h5>
                            <small class="text-muted">/blog/' . e($blog->slug) . '</small>
                        </div>
                    </div>
                ';
            })
            ->addColumn('status_badge', function (Blog $blog) {
                return '<span class="badge ' . $blog->statusBadgeClass() . ' fs-xxs">' . e($blog->statusLabel()) . '</span>';
            })
            ->editColumn('published_at', function (Blog $blog) {
                return $blog->published_at ? $blog->published_at->format('d M, Y') : '<span class="text-muted">—</span>';
            })
            ->editColumn('created_at', function (Blog $blog) {
                return optional($blog->created_at)->format('d M, Y') ?? '—';
            })
            ->addColumn('action', function (Blog $blog) {
                $show    = route('blogs.show', $blog);
                $edit    = route('blogs.edit', $blog);
                $toggle  = route('blogs.toggle-status', $blog);
                $destroy = route('blogs.destroy', $blog);

                $toggleIcon = $blog->isActive() ? 'ti-toggle-right' : 'ti-toggle-left';

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
                            data-url="' . $destroy . '" data-name="' . e($blog->title) . '" title="Delete">
                            <i class="ti ti-trash fs-lg"></i>
                        </button>
                    </div>
                ';
            })
            ->filterColumn('title', function ($query, $keyword) {
                $like = "%{$keyword}%";
                $query->where(function ($q) use ($like) {
                    $q->where('blogs.title', 'like', $like)
                      ->orWhere('blogs.slug', 'like', $like);
                });
            })
            ->filterColumn('status_badge', function ($query, $keyword) {
                if ($keyword === '1' || $keyword === '0') {
                    $query->where('blogs.status', (int) $keyword);
                }
            })
            ->rawColumns(['title', 'status_badge', 'published_at', 'action'])
            ->make(true);
    }

    /* -----------------------------------------------------------------
     |  Create
     | -----------------------------------------------------------------
     */
    public function create(): View
    {
        return view('blogs.create');
    }

    public function store(StoreBlogRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        $blog = DB::transaction(function () use ($data, $request) {
            $blog = Blog::create([
                'title'             => $data['title'],
                'slug'              => $data['slug'] ?? null, // model auto-generates if blank
                'excerpt'           => $data['excerpt'] ?? null,
                'content'           => $data['content'],
                'meta_title'        => $data['meta_title'] ?? null,
                'meta_description'  => $data['meta_description'] ?? null,
                'status'            => (bool) $data['status'],
            ]);

            if ($request->hasFile('image')) {
                $blog->addMediaFromRequest('image')
                    ->toMediaCollection(Blog::MEDIA_COLLECTION_IMAGE);
            }

            return $blog;
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Blog post created successfully.',
                'redirect' => route('blogs.index'),
                'data'     => $blog,
            ]);
        }

        return redirect()->route('blogs.index')->with('success', 'Blog post created successfully.');
    }

    /* -----------------------------------------------------------------
     |  Show
     | -----------------------------------------------------------------
     */
    public function show(Blog $blog): View
    {
        $blog->load(['creator', 'updater']);
        return view('blogs.show', compact('blog'));
    }

    /* -----------------------------------------------------------------
     |  Edit / Update
     | -----------------------------------------------------------------
     */
    public function edit(Blog $blog): View
    {
        return view('blogs.edit', compact('blog'));
    }

    public function update(UpdateBlogRequest $request, Blog $blog): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($blog, $data, $request) {
            $blog->fill([
                'title'            => $data['title'],
                'slug'             => $data['slug'] ?? $blog->slug,
                'excerpt'          => $data['excerpt'] ?? null,
                'content'          => $data['content'],
                'meta_title'       => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'status'           => (bool) $data['status'],
            ])->save();

            if (! empty($data['remove_image'])) {
                $blog->clearMediaCollection(Blog::MEDIA_COLLECTION_IMAGE);
            }

            if ($request->hasFile('image')) {
                $blog->clearMediaCollection(Blog::MEDIA_COLLECTION_IMAGE);
                $blog->addMediaFromRequest('image')
                    ->toMediaCollection(Blog::MEDIA_COLLECTION_IMAGE);
            }
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Blog post updated successfully.',
                'redirect' => route('blogs.index'),
                'data'     => $blog->fresh(),
            ]);
        }

        return redirect()->route('blogs.index')->with('success', 'Blog post updated successfully.');
    }

    /* -----------------------------------------------------------------
     |  Destroy
     | -----------------------------------------------------------------
     */
    public function destroy(Blog $blog, Request $request): JsonResponse|RedirectResponse
    {
        $blog->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Blog post deleted successfully.']);
        }

        return redirect()->route('blogs.index')->with('success', 'Blog post deleted successfully.');
    }

    /* -----------------------------------------------------------------
     |  Toggle Status
     | -----------------------------------------------------------------
     */
    public function toggleStatus(Blog $blog): JsonResponse
    {
        $blog->status = ! $blog->status;
        $blog->save();

        return response()->json([
            'success' => true,
            'status'  => (bool) $blog->status,
            'label'   => $blog->statusLabel(),
            'message' => 'Status updated.',
        ]);
    }
}
