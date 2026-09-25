<?php

namespace App\Http\Controllers;

use App\Models\Color;
use App\Http\Requests\StoreColorRequest;
use App\Http\Requests\UpdateColorRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ColorController extends Controller
{
    /* ─── List ─────────────────────────────────────────────── */

    public function index(): View
    {
        $counts = Color::selectRaw('COUNT(*) as total, SUM(status = 1) as active, SUM(status = 0) as inactive')
            ->first();

        $stats = [
            'colors_total'    => (int) $counts->total,
            'colors_active'   => (int) $counts->active,
            'colors_inactive' => (int) $counts->inactive,
        ];

        return view('colors.index', compact('stats'));
    }

    public function data(Request $request): JsonResponse
    {
        $q = Color::query();

        if ($request->filled('status') && $request->query('status') !== '') {
            $q->where('status', (bool) $request->query('status'));
        }

        return DataTables::eloquent($q)
            ->addIndexColumn()
            ->editColumn('name', fn(Color $c) =>
                '<span class="color-name">' . e($c->name) . '</span>'
            )
            ->editColumn('status', fn(Color $c) =>
                '<span class="status-pill ' . ($c->isActive() ? 'status-active' : 'status-inactive') . '">'
                . '<span class="status-dot"></span>' . e($c->statusLabel()) . '</span>'
            )
            ->addColumn('actions', function (Color $c) {
                $canEdit   = auth()->user()?->hasPermission('colors.edit')   ?? false;
                $canDelete = auth()->user()?->hasPermission('colors.delete') ?? false;

                $html  = '<div class="d-flex gap-1 justify-content-center">';
                $html .= '<a href="' . route('colors.show', $c) . '" class="action-btn action-view" title="View"><i class="ti ti-eye"></i></a>';
                if ($canEdit) {
                    $html .= '<a href="' . route('colors.edit', $c) . '" class="action-btn action-edit" title="Edit"><i class="ti ti-edit"></i></a>';
                }
                if ($canEdit) {
                    $toggle = $c->isActive() ? 'Deactivate' : 'Activate';
                    $html .= '<button type="button" class="action-btn action-toggle js-toggle-color"'
                        . ' data-url="' . route('colors.toggle-status', $c) . '"'
                        . ' title="' . $toggle . '">'
                        . '<i class="ti ti-' . ($c->isActive() ? 'eye-off' : 'eye') . '"></i>'
                        . '</button>';
                }
                if ($canDelete) {
                    $html .= '<button type="button" class="action-btn action-delete js-delete-color"'
                        . ' data-url="' . route('colors.destroy', $c) . '"'
                        . ' data-name="' . e($c->name) . '"'
                        . ' title="Delete"><i class="ti ti-trash"></i></button>';
                }
                $html .= '</div>';
                return $html;
            })
            ->rawColumns(['name', 'status', 'actions'])
            ->toJson();
    }

    /* ─── Create / Store ──────────────────────────────────── */

    public function create(): View
    {
        return view('colors.create');
    }

    public function store(StoreColorRequest $request): JsonResponse
    {
        Color::create($request->validated());

        return response()->json([
            'ok'       => true,
            'message'  => 'Color created.',
            'redirect' => route('colors.index'),
        ], 201);
    }

    /* ─── Show ─────────────────────────────────────────────── */

    public function show(Color $color): View
    {
        return view('colors.show', compact('color'));
    }

    /* ─── Edit / Update ────────────────────────────────────── */

    public function edit(Color $color): View
    {
        return view('colors.edit', compact('color'));
    }

    public function update(UpdateColorRequest $request, Color $color): JsonResponse
    {
        $color->update($request->validated());

        return response()->json([
            'ok'       => true,
            'message'  => 'Color updated.',
            'redirect' => route('colors.index'),
        ]);
    }

    /* ─── Delete ────────────────────────────────────────────── */

    public function destroy(Color $color): JsonResponse
    {
        $color->delete(); // soft delete
        return response()->json(['ok' => true, 'message' => 'Color deleted.']);
    }

    /* ─── Toggle Status ─────────────────────────────────────── */

    public function toggleStatus(Color $color): JsonResponse
    {
        $color->update(['status' => ! $color->status]);
        return response()->json([
            'ok'     => true,
            'status' => $color->fresh()->status,
            'label'  => $color->fresh()->statusLabel(),
        ]);
    }
}
