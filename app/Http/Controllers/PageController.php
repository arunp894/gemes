<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePageRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
        return view('pages.index', compact('pages'));
    }

    public function create(): View
    {
        return view('pages.create');
    }

    public function store(StorePageRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

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
}
