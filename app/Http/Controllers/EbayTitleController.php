<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\EbayTitle;
use App\Http\Requests\StoreEbayTitleRequest;
use App\Http\Requests\UpdateEbayTitleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class EbayTitleController extends Controller
{
    /* ─── List ─────────────────────────────────────────────── */

    public function index(): View
    {
        $counts = EbayTitle::selectRaw('COUNT(*) as total, SUM(status = 1) as active, SUM(status = 0) as inactive')
            ->first();

        $stats = [
            'ebay_titles_total'    => (int) $counts->total,
            'ebay_titles_active'   => (int) $counts->active,
            'ebay_titles_inactive' => (int) $counts->inactive,
        ];

        return view('ebay-titles.index', compact('stats'));
    }

    public function data(Request $request): JsonResponse
    {
        $q = EbayTitle::query()->with('category:id,name');

        if ($request->filled('status') && $request->query('status') !== '') {
            $q->where('status', (bool) $request->query('status'));
        }

        return DataTables::eloquent($q)
            ->addIndexColumn()
            ->addColumn('stone', fn(EbayTitle $e) => e($e->category?->name ?? '—'))
            ->editColumn('title', fn(EbayTitle $e) =>
                '<span class="ebay-title-text">' . e($e->title) . '</span>'
            )
            ->editColumn('status', fn(EbayTitle $e) =>
                '<span class="status-pill ' . ($e->isActive() ? 'status-active' : 'status-inactive') . '">'
                . '<span class="status-dot"></span>' . e($e->statusLabel()) . '</span>'
            )
            ->addColumn('actions', function (EbayTitle $e) {
                $canEdit   = auth()->user()?->hasPermission('ebay-titles.edit')   ?? false;
                $canDelete = auth()->user()?->hasPermission('ebay-titles.delete') ?? false;

                $html  = '<div class="d-flex gap-1 justify-content-center">';
                $html .= '<a href="' . route('ebay-titles.show', $e) . '" class="action-btn action-view" title="View"><i class="ti ti-eye"></i></a>';
                if ($canEdit) {
                    $html .= '<a href="' . route('ebay-titles.edit', $e) . '" class="action-btn action-edit" title="Edit"><i class="ti ti-edit"></i></a>';
                }
                if ($canEdit) {
                    $toggle = $e->isActive() ? 'Deactivate' : 'Activate';
                    $html .= '<button type="button" class="action-btn action-toggle js-toggle-ebay-title"'
                        . ' data-url="' . route('ebay-titles.toggle-status', $e) . '"'
                        . ' title="' . $toggle . '">'
                        . '<i class="ti ti-' . ($e->isActive() ? 'eye-off' : 'eye') . '"></i>'
                        . '</button>';
                }
                if ($canDelete) {
                    $html .= '<button type="button" class="action-btn action-delete js-delete-ebay-title"'
                        . ' data-url="' . route('ebay-titles.destroy', $e) . '"'
                        . ' data-name="' . e($e->title) . '"'
                        . ' title="Delete"><i class="ti ti-trash"></i></button>';
                }
                $html .= '</div>';
                return $html;
            })
            ->rawColumns(['title', 'status', 'actions'])
            ->toJson();
    }

    /* ─── Create / Store ──────────────────────────────────── */

    public function create(): View
    {
        $categories = Category::ordered()->get(['id', 'name']);
        return view('ebay-titles.create', compact('categories'));
    }

    public function store(StoreEbayTitleRequest $request): JsonResponse
    {
        EbayTitle::create($request->validated());

        return response()->json([
            'ok'       => true,
            'message'  => 'eBay Title created.',
            'redirect' => route('ebay-titles.index'),
        ], 201);
    }

    /* ─── Show ─────────────────────────────────────────────── */

    public function show(EbayTitle $ebay_title): View
    {
        $ebay_title->load('category:id,name');
        return view('ebay-titles.show', ['ebayTitle' => $ebay_title]);
    }

    /* ─── Edit / Update ────────────────────────────────────── */

    public function edit(EbayTitle $ebay_title): View
    {
        $categories = Category::ordered()->get(['id', 'name']);
        return view('ebay-titles.edit', ['ebayTitle' => $ebay_title, 'categories' => $categories]);
    }

    public function update(UpdateEbayTitleRequest $request, EbayTitle $ebay_title): JsonResponse
    {
        $ebay_title->update($request->validated());

        return response()->json([
            'ok'       => true,
            'message'  => 'eBay Title updated.',
            'redirect' => route('ebay-titles.index'),
        ]);
    }

    /* ─── Delete ────────────────────────────────────────────── */

    public function destroy(EbayTitle $ebay_title): JsonResponse
    {
        $ebay_title->delete(); // soft delete
        return response()->json(['ok' => true, 'message' => 'eBay Title deleted.']);
    }

    /* ─── Toggle Status ─────────────────────────────────────── */

    public function toggleStatus(EbayTitle $ebay_title): JsonResponse
    {
        $ebay_title->update(['status' => ! $ebay_title->status]);
        return response()->json([
            'ok'     => true,
            'status' => $ebay_title->fresh()->status,
            'label'  => $ebay_title->fresh()->statusLabel(),
        ]);
    }
}
