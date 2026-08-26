<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBannerRequest;
use App\Http\Requests\UpdateBannerRequest;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

/**
 * Banners CRUD — website promotional banners.
 *
 * Follows the Location/Supplier controller pattern:
 *   - DataTables AJAX listing  GET  /banners/data
 *   - PATCH /banners/{banner}/toggle-status
 *   - JSON responses for AJAX; classic redirects otherwise
 *   - Images handled via Spatie MediaLibrary (banner_image collection)
 */
class BannerController extends Controller
{
    /* -----------------------------------------------------------------
     |  Listing
     | -----------------------------------------------------------------
     */
    public function index(): View
    {
        // Single aggregate query instead of separate COUNT() round-trips per card.
        $counts = Banner::selectRaw('COUNT(*) as total, SUM(status = 1) as active, SUM(status = 0) as inactive')
            ->first();

        $stats = [
            'banners_total'    => (int) $counts->total,
            'banners_active'   => (int) $counts->active,
            'banners_inactive' => (int) $counts->inactive,
        ];

        return view('banners.index', compact('stats'));
    }

    public function data(Request $request): JsonResponse
    {
        $query = Banner::query()->withoutTrashed();

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('checkbox', function (Banner $banner) {
                return '<input class="form-check-input form-check-input-light fs-14 banner-item-check mt-0"
                    type="checkbox" value="' . $banner->id . '" />';
            })
            ->editColumn('title', function (Banner $banner) {
                $thumb = $banner->image_thumb_url
                    ? '<img src="' . e($banner->image_thumb_url) . '" alt="' . e($banner->title) . '" class="banner-thumb-img" />'
                    : '<span class="banner-thumb-placeholder"><i class="ti ti-photo"></i></span>';

                $sub = $banner->subtitle ? '<small class="d-block text-muted">' . e($banner->subtitle) . '</small>' : '';

                return '
                    <div class="banner-name-cell">
                        <div class="banner-thumb">' . $thumb . '</div>
                        <div>
                            <a href="' . route('banners.show', $banner) . '" class="banner-name-link">' . e($banner->title) . '</a>
                            ' . $sub . '
                        </div>
                    </div>
                ';
            })
            ->addColumn('position_badge', function (Banner $banner) {
                return '<span class="badge ' . $banner->positionBadgeClass() . ' fs-xxs">'
                    . e($banner->positionLabel()) . '</span>';
            })
            ->addColumn('live_badge', function (Banner $banner) {
                return $banner->liveBadge();
            })
            ->addColumn('status_badge', function (Banner $banner) {
                $class = $banner->isActive() ? 'status-pill status-active' : 'status-pill status-inactive';
                return '<span class="' . $class . '"><span class="status-dot"></span>' . e($banner->statusLabel()) . '</span>';
            })
            ->addColumn('date_range', function (Banner $banner) {
                $from = $banner->starts_at ? $banner->starts_at->format('d M Y') : '<span class="text-muted">—</span>';
                $to   = $banner->ends_at   ? $banner->ends_at->format('d M Y')   : '<span class="text-muted">—</span>';
                return $from . ' → ' . $to;
            })
            ->editColumn('sort_order', function (Banner $banner) {
                return '<span class="badge bg-light text-dark">' . $banner->sort_order . '</span>';
            })
            ->editColumn('created_at', function (Banner $banner) {
                return optional($banner->created_at)->format('d M, Y') ?? '—';
            })
            ->addColumn('action', function (Banner $banner) {
                $show    = route('banners.show', $banner);
                $edit    = route('banners.edit', $banner);
                $toggle  = route('banners.toggle-status', $banner);
                $destroy = route('banners.destroy', $banner);

                $toggleIcon = $banner->isActive() ? 'ti-toggle-right' : 'ti-toggle-left';

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
                            data-url="' . $destroy . '" data-name="' . e($banner->title) . '" title="Delete">
                            <i class="ti ti-trash"></i>
                        </button>
                    </div>
                ';
            })
            ->filterColumn('title', function ($query, $keyword) {
                $like = "%{$keyword}%";
                $query->where(function ($q) use ($like) {
                    $q->where('banners.title', 'like', $like)
                      ->orWhere('banners.subtitle', 'like', $like)
                      ->orWhere('banners.link_url', 'like', $like);
                });
            })
            ->filterColumn('position_badge', function ($query, $keyword) {
                if ($keyword !== '' && $keyword !== null) {
                    $query->where('banners.position', $keyword);
                }
            })
            ->filterColumn('status_badge', function ($query, $keyword) {
                if ($keyword === '1' || $keyword === '0') {
                    $query->where('banners.status', (int) $keyword);
                }
            })
            ->rawColumns(['checkbox', 'title', 'position_badge', 'live_badge', 'status_badge', 'date_range', 'sort_order', 'action'])
            ->make(true);
    }

    /* -----------------------------------------------------------------
     |  Create
     | -----------------------------------------------------------------
     */
    public function create(): View
    {
        $positions = Banner::POSITIONS;
        return view('banners.create', compact('positions'));
    }

    public function store(StoreBannerRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        $banner = DB::transaction(function () use ($data, $request) {
            $banner = Banner::create([
                'title'      => $data['title'],
                'subtitle'   => $data['subtitle']   ?? null,
                'link_url'   => $data['link_url']   ?? null,
                'link_text'  => $data['link_text']  ?? null,
                'position'   => $data['position'],
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'starts_at'  => $data['starts_at']  ?? null,
                'ends_at'    => $data['ends_at']    ?? null,
                'status'     => (bool) $data['status'],
                'notes'      => $data['notes']      ?? null,
            ]);

            if ($request->hasFile('image')) {
                $banner->addMediaFromRequest('image')
                    ->toMediaCollection(Banner::MEDIA_COLLECTION_IMAGE);
            }

            return $banner;
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Banner created successfully.',
                'redirect' => route('banners.index'),
                'data'     => $banner,
            ]);
        }

        return redirect()->route('banners.index')->with('success', 'Banner created successfully.');
    }

    /* -----------------------------------------------------------------
     |  Show
     | -----------------------------------------------------------------
     */
    public function show(Banner $banner): View
    {
        $banner->load(['creator', 'updater']);
        return view('banners.show', compact('banner'));
    }

    /* -----------------------------------------------------------------
     |  Edit / Update
     | -----------------------------------------------------------------
     */
    public function edit(Banner $banner): View
    {
        $positions = Banner::POSITIONS;
        return view('banners.edit', compact('banner', 'positions'));
    }

    public function update(UpdateBannerRequest $request, Banner $banner): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($banner, $data, $request) {
            $banner->fill([
                'title'      => $data['title'],
                'subtitle'   => $data['subtitle']   ?? null,
                'link_url'   => $data['link_url']   ?? null,
                'link_text'  => $data['link_text']  ?? null,
                'position'   => $data['position'],
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'starts_at'  => $data['starts_at']  ?? null,
                'ends_at'    => $data['ends_at']    ?? null,
                'status'     => (bool) $data['status'],
                'notes'      => $data['notes']      ?? null,
            ])->save();

            // Remove existing image if flagged.
            if (! empty($data['remove_image'])) {
                $banner->clearMediaCollection(Banner::MEDIA_COLLECTION_IMAGE);
            }

            // Upload new image (replaces existing because collection is singleFile).
            if ($request->hasFile('image')) {
                $banner->addMediaFromRequest('image')
                    ->toMediaCollection(Banner::MEDIA_COLLECTION_IMAGE);
            }
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Banner updated successfully.',
                'redirect' => route('banners.index'),
                'data'     => $banner->fresh(),
            ]);
        }

        return redirect()->route('banners.index')->with('success', 'Banner updated successfully.');
    }

    /* -----------------------------------------------------------------
     |  Destroy
     | -----------------------------------------------------------------
     */
    public function destroy(Banner $banner, Request $request): JsonResponse|RedirectResponse
    {
        $banner->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Banner deleted successfully.']);
        }

        return redirect()->route('banners.index')->with('success', 'Banner deleted successfully.');
    }

    /* -----------------------------------------------------------------
     |  Toggle Status
     | -----------------------------------------------------------------
     */
    public function toggleStatus(Banner $banner): JsonResponse
    {
        $banner->status = ! $banner->status;
        $banner->save();

        return response()->json([
            'success' => true,
            'status'  => (bool) $banner->status,
            'label'   => $banner->statusLabel(),
            'message' => 'Status updated.',
        ]);
    }
}
