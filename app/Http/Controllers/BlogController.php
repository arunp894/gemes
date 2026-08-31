<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBlogRequest;
use App\Http\Requests\UpdateBlogRequest;
use App\Models\Blog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        // Single aggregate query instead of separate COUNT() round-trips per card.
        $counts = Blog::selectRaw('COUNT(*) as total, SUM(status = 1) as published, SUM(status = 0) as draft')
            ->first();

        $stats = [
            'blogs_total'     => (int) $counts->total,
            'blogs_published' => (int) $counts->published,
            'blogs_draft'     => (int) $counts->draft,
        ];

        return view('blogs.index', compact('stats'));
    }

    public function data(Request $request): JsonResponse
    {
        $query = Blog::query()->with('media');

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('title', function (Blog $blog) {
                $thumb = $blog->image_thumb_url
                    ? '<img src="' . e($blog->image_thumb_url) . '" alt="' . e($blog->title) . '" class="blog-thumb-img" />'
                    : '<span class="blog-thumb-placeholder"><i class="ti ti-article"></i></span>';

                return '
                    <div class="blog-title-cell">
                        <div class="blog-thumb">' . $thumb . '</div>
                        <div>
                            <a href="' . route('blogs.show', $blog) . '" class="blog-title-link">' . e($blog->title) . '</a>
                            <div class="blog-slug-text">/blog/' . e($blog->slug) . '</div>
                        </div>
                    </div>
                ';
            })
            ->addColumn('status_badge', function (Blog $blog) {
                $class = $blog->isActive() ? 'status-pill status-active' : 'status-pill status-inactive';
                return '<span class="' . $class . '"><span class="status-dot"></span>' . $blog->statusLabel() . '</span>';
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
                            data-url="' . $destroy . '" data-name="' . e($blog->title) . '" title="Delete">
                            <i class="ti ti-trash"></i>
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
        $data['content'] = clean($data['content']);

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
        $data['content'] = clean($data['content']);

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

    /* -----------------------------------------------------------------
     |  Inline content image upload — consumed by the Quill editor's
     |  image toolbar button on the Add/Edit forms. Stored as a plain
     |  public file rather than through Spatie MediaLibrary: these images
     |  are free-form content assets referenced by URL from inside the
     |  post body, not a structured "one blog has one X" relationship
     |  like the featured image.
     | -----------------------------------------------------------------
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
        ]);

        $path = $request->file('image')->store('blog-content', 'public');

        return response()->json([
            'success' => true,
            'url'     => Storage::disk('public')->url($path),
        ]);
    }
}
