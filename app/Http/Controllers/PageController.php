<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePageRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Static page configuration (About Us, Terms & Conditions, ...).
 * Kept deliberately lighter than Blog/Banner: no media, no status flag,
 * no DataTables — this is a short, admin-curated list, a plain table is
 * enough. Public rendering lives in WebsiteController::pageShow().
 */
class PageController extends Controller
{
    public function index(): View
    {
        $pages = Page::ordered()->get();

        // Derived from the already-loaded collection (list is short/admin-curated,
        // so a second query isn't worth it). No status field on this model, so
        // these are genuinely-computed metrics rather than an Active/Inactive split.
        $stats = [
            'pages_total'    => $pages->count(),
            'pages_recent'   => $pages->filter(
                fn (Page $page) => $page->updated_at && $page->updated_at->gte(now()->subDays(7))
            )->count(),
            'pages_with_seo' => $pages->filter(fn (Page $page) => filled($page->meta_title))->count(),
        ];

        return view('pages.index', compact('pages', 'stats'));
    }

    public function create(): View
    {
        return view('pages.create');
    }

    public function store(StorePageRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $data['content'] = clean($data['content']);

        $page = Page::create([
            'slug'             => $data['slug'] ?? null, // model auto-generates if blank
            'title'            => $data['title'],
            'content'          => $data['content'],
            'meta_title'       => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Page created successfully.',
                'redirect' => route('pages.index'),
                'data'     => $page,
            ]);
        }

        return redirect()->route('pages.index')->with('success', 'Page created successfully.');
    }

    public function edit(Page $page): View
    {
        return view('pages.edit', compact('page'));
    }

    public function update(UpdatePageRequest $request, Page $page): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $data['content'] = clean($data['content']);

        $page->fill([
            'slug'             => $data['slug'] ?? $page->slug,
            'title'            => $data['title'],
            'content'          => $data['content'],
            'meta_title'       => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
        ])->save();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Page updated successfully.',
                'redirect' => route('pages.index'),
                'data'     => $page->fresh(),
            ]);
        }

        return redirect()->route('pages.index')->with('success', 'Page updated successfully.');
    }

    public function destroy(Page $page): JsonResponse
    {
        $page->delete();

        return response()->json([
            'success' => true,
            'message' => 'Page deleted successfully.',
        ]);
    }

    /**
     * Inline content image upload — consumed by the Quill editor's image
     * toolbar button on the Add/Edit forms. Same pattern as
     * BlogController::uploadImage(): a plain public file, not Spatie
     * MediaLibrary, since it's a free-form content asset referenced by URL
     * from inside the page body rather than a structured model relationship.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
        ]);

        $path = $request->file('image')->store('page-content', 'public');

        return response()->json([
            'success' => true,
            'url'     => Storage::disk('public')->url($path),
        ]);
    }
}
