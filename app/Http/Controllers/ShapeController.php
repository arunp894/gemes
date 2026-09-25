<?php

namespace App\Http\Controllers;

use App\Models\Shape;
use App\Http\Requests\StoreShapeRequest;
use App\Http\Requests\UpdateShapeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ShapeController extends Controller
{
    /* ─── List ─────────────────────────────────────────────── */

    public function index(): View
    {
        $counts = Shape::selectRaw('COUNT(*) as total, SUM(status = 1) as active, SUM(status = 0) as inactive')
            ->first();

        $stats = [
            'shapes_total'    => (int) $counts->total,
            'shapes_active'   => (int) $counts->active,
            'shapes_inactive' => (int) $counts->inactive,
        ];

        return view('shapes.index', compact('stats'));
    }

    public function data(Request $request): JsonResponse
    {
        $q = Shape::query();

        if ($request->filled('status') && $request->query('status') !== '') {
            $q->where('status', (bool) $request->query('status'));
        }

        return DataTables::eloquent($q)
            ->addIndexColumn()
            ->editColumn('name', fn(Shape $s) =>
                '<span class="shape-name">' . e($s->name) . '</span>'
            )
            ->editColumn('status', fn(Shape $s) =>
                '<span class="status-pill ' . ($s->isActive() ? 'status-active' : 'status-inactive') . '">'
                . '<span class="status-dot"></span>' . e($s->statusLabel()) . '</span>'
            )
            ->addColumn('actions', function (Shape $s) {
                $canEdit   = auth()->user()?->hasPermission('shapes.edit')   ?? false;
                $canDelete = auth()->user()?->hasPermission('shapes.delete') ?? false;

                $html  = '<div class="d-flex gap-1 justify-content-center">';
                $html .= '<a href="' . route('shapes.show', $s) . '" class="action-btn action-view" title="View"><i class="ti ti-eye"></i></a>';
                if ($canEdit) {
                    $html .= '<a href="' . route('shapes.edit', $s) . '" class="action-btn action-edit" title="Edit"><i class="ti ti-edit"></i></a>';
                }
                if ($canEdit) {
                    $toggle = $s->isActive() ? 'Deactivate' : 'Activate';
                    $html .= '<button type="button" class="action-btn action-toggle js-toggle-shape"'
                        . ' data-url="' . route('shapes.toggle-status', $s) . '"'
                        . ' title="' . $toggle . '">'
                        . '<i class="ti ti-' . ($s->isActive() ? 'eye-off' : 'eye') . '"></i>'
                        . '</button>';
                }
                if ($canDelete) {
                    $html .= '<button type="button" class="action-btn action-delete js-delete-shape"'
                        . ' data-url="' . route('shapes.destroy', $s) . '"'
                        . ' data-name="' . e($s->name) . '"'
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
        return view('shapes.create');
    }

    public function store(StoreShapeRequest $request): JsonResponse
    {
        Shape::create($request->validated());

        return response()->json([
            'ok'       => true,
            'message'  => 'Shape created.',
            'redirect' => route('shapes.index'),
        ], 201);
    }

    /* ─── Show ─────────────────────────────────────────────── */

    public function show(Shape $shape): View
    {
        return view('shapes.show', compact('shape'));
    }

    /* ─── Edit / Update ────────────────────────────────────── */

    public function edit(Shape $shape): View
    {
        return view('shapes.edit', compact('shape'));
    }

    public function update(UpdateShapeRequest $request, Shape $shape): JsonResponse
    {
        $shape->update($request->validated());

        return response()->json([
            'ok'       => true,
            'message'  => 'Shape updated.',
            'redirect' => route('shapes.index'),
        ]);
    }

    /* ─── Delete ────────────────────────────────────────────── */

    public function destroy(Shape $shape): JsonResponse
    {
        $shape->delete(); // soft delete
        return response()->json(['ok' => true, 'message' => 'Shape deleted.']);
    }

    /* ─── Toggle Status ─────────────────────────────────────── */

    public function toggleStatus(Shape $shape): JsonResponse
    {
        $shape->update(['status' => ! $shape->status]);
        return response()->json([
            'ok'     => true,
            'status' => $shape->fresh()->status,
            'label'  => $shape->fresh()->statusLabel(),
        ]);
    }
}
