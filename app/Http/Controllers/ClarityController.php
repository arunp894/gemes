<?php

namespace App\Http\Controllers;

use App\Models\Clarity;
use App\Http\Requests\StoreClarityRequest;
use App\Http\Requests\UpdateClarityRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ClarityController extends Controller
{
    /* ─── List ─────────────────────────────────────────────── */

    public function index(): View
    {
        $counts = Clarity::selectRaw('COUNT(*) as total, SUM(status = 1) as active, SUM(status = 0) as inactive')
            ->first();

        $stats = [
            'clarities_total'    => (int) $counts->total,
            'clarities_active'   => (int) $counts->active,
            'clarities_inactive' => (int) $counts->inactive,
        ];

        return view('clarities.index', compact('stats'));
    }

    public function data(Request $request): JsonResponse
    {
        $q = Clarity::query();

        if ($request->filled('status') && $request->query('status') !== '') {
            $q->where('status', (bool) $request->query('status'));
        }

        return DataTables::eloquent($q)
            ->addIndexColumn()
            ->editColumn('name', fn(Clarity $c) =>
                '<span class="clarity-name">' . e($c->name) . '</span>'
            )
            ->editColumn('status', fn(Clarity $c) =>
                '<span class="status-pill ' . ($c->isActive() ? 'status-active' : 'status-inactive') . '">'
                . '<span class="status-dot"></span>' . e($c->statusLabel()) . '</span>'
            )
            ->addColumn('actions', function (Clarity $c) {
                $canEdit   = auth()->user()?->hasPermission('clarities.edit')   ?? false;
                $canDelete = auth()->user()?->hasPermission('clarities.delete') ?? false;

                $html  = '<div class="d-flex gap-1 justify-content-center">';
                $html .= '<a href="' . route('clarities.show', $c) . '" class="action-btn action-view" title="View"><i class="ti ti-eye"></i></a>';
                if ($canEdit) {
                    $html .= '<a href="' . route('clarities.edit', $c) . '" class="action-btn action-edit" title="Edit"><i class="ti ti-edit"></i></a>';
                }
                if ($canEdit) {
                    $toggle = $c->isActive() ? 'Deactivate' : 'Activate';
                    $html .= '<button type="button" class="action-btn action-toggle js-toggle-clarity"'
                        . ' data-url="' . route('clarities.toggle-status', $c) . '"'
                        . ' title="' . $toggle . '">'
                        . '<i class="ti ti-' . ($c->isActive() ? 'eye-off' : 'eye') . '"></i>'
                        . '</button>';
                }
                if ($canDelete) {
                    $html .= '<button type="button" class="action-btn action-delete js-delete-clarity"'
                        . ' data-url="' . route('clarities.destroy', $c) . '"'
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
        return view('clarities.create');
    }

    public function store(StoreClarityRequest $request): JsonResponse
    {
        Clarity::create($request->validated());

        return response()->json([
            'ok'       => true,
            'message'  => 'Clarity created.',
            'redirect' => route('clarities.index'),
        ], 201);
    }

    /* ─── Show ─────────────────────────────────────────────── */

    public function show(Clarity $clarity): View
    {
        return view('clarities.show', compact('clarity'));
    }

    /* ─── Edit / Update ────────────────────────────────────── */

    public function edit(Clarity $clarity): View
    {
        return view('clarities.edit', compact('clarity'));
    }

    public function update(UpdateClarityRequest $request, Clarity $clarity): JsonResponse
    {
        $clarity->update($request->validated());

        return response()->json([
            'ok'       => true,
            'message'  => 'Clarity updated.',
            'redirect' => route('clarities.index'),
        ]);
    }

    /* ─── Delete ────────────────────────────────────────────── */

    public function destroy(Clarity $clarity): JsonResponse
    {
        $clarity->delete(); // soft delete
        return response()->json(['ok' => true, 'message' => 'Clarity deleted.']);
    }

    /* ─── Toggle Status ─────────────────────────────────────── */

    public function toggleStatus(Clarity $clarity): JsonResponse
    {
        $clarity->update(['status' => ! $clarity->status]);
        return response()->json([
            'ok'     => true,
            'status' => $clarity->fresh()->status,
            'label'  => $clarity->fresh()->statusLabel(),
        ]);
    }
}
