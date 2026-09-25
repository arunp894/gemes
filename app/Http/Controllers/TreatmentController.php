<?php

namespace App\Http\Controllers;

use App\Models\Treatment;
use App\Http\Requests\StoreTreatmentRequest;
use App\Http\Requests\UpdateTreatmentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class TreatmentController extends Controller
{
    /* ─── List ─────────────────────────────────────────────── */

    public function index(): View
    {
        $counts = Treatment::selectRaw('COUNT(*) as total, SUM(status = 1) as active, SUM(status = 0) as inactive')
            ->first();

        $stats = [
            'treatments_total'    => (int) $counts->total,
            'treatments_active'   => (int) $counts->active,
            'treatments_inactive' => (int) $counts->inactive,
        ];

        return view('treatments.index', compact('stats'));
    }

    public function data(Request $request): JsonResponse
    {
        $q = Treatment::query();

        if ($request->filled('status') && $request->query('status') !== '') {
            $q->where('status', (bool) $request->query('status'));
        }

        return DataTables::eloquent($q)
            ->addIndexColumn()
            ->editColumn('name', fn(Treatment $t) =>
                '<span class="treatment-name">' . e($t->name) . '</span>'
            )
            ->editColumn('status', fn(Treatment $t) =>
                '<span class="status-pill ' . ($t->isActive() ? 'status-active' : 'status-inactive') . '">'
                . '<span class="status-dot"></span>' . e($t->statusLabel()) . '</span>'
            )
            ->addColumn('actions', function (Treatment $t) {
                $canEdit   = auth()->user()?->hasPermission('treatments.edit')   ?? false;
                $canDelete = auth()->user()?->hasPermission('treatments.delete') ?? false;

                $html  = '<div class="d-flex gap-1 justify-content-center">';
                $html .= '<a href="' . route('treatments.show', $t) . '" class="action-btn action-view" title="View"><i class="ti ti-eye"></i></a>';
                if ($canEdit) {
                    $html .= '<a href="' . route('treatments.edit', $t) . '" class="action-btn action-edit" title="Edit"><i class="ti ti-edit"></i></a>';
                }
                if ($canEdit) {
                    $toggle = $t->isActive() ? 'Deactivate' : 'Activate';
                    $html .= '<button type="button" class="action-btn action-toggle js-toggle-treatment"'
                        . ' data-url="' . route('treatments.toggle-status', $t) . '"'
                        . ' title="' . $toggle . '">'
                        . '<i class="ti ti-' . ($t->isActive() ? 'eye-off' : 'eye') . '"></i>'
                        . '</button>';
                }
                if ($canDelete) {
                    $html .= '<button type="button" class="action-btn action-delete js-delete-treatment"'
                        . ' data-url="' . route('treatments.destroy', $t) . '"'
                        . ' data-name="' . e($t->name) . '"'
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
        return view('treatments.create');
    }

    public function store(StoreTreatmentRequest $request): JsonResponse
    {
        Treatment::create($request->validated());

        return response()->json([
            'ok'       => true,
            'message'  => 'Treatment created.',
            'redirect' => route('treatments.index'),
        ], 201);
    }

    /* ─── Show ─────────────────────────────────────────────── */

    public function show(Treatment $treatment): View
    {
        return view('treatments.show', compact('treatment'));
    }

    /* ─── Edit / Update ────────────────────────────────────── */

    public function edit(Treatment $treatment): View
    {
        return view('treatments.edit', compact('treatment'));
    }

    public function update(UpdateTreatmentRequest $request, Treatment $treatment): JsonResponse
    {
        $treatment->update($request->validated());

        return response()->json([
            'ok'       => true,
            'message'  => 'Treatment updated.',
            'redirect' => route('treatments.index'),
        ]);
    }

    /* ─── Delete ────────────────────────────────────────────── */

    public function destroy(Treatment $treatment): JsonResponse
    {
        $treatment->delete(); // soft delete
        return response()->json(['ok' => true, 'message' => 'Treatment deleted.']);
    }

    /* ─── Toggle Status ─────────────────────────────────────── */

    public function toggleStatus(Treatment $treatment): JsonResponse
    {
        $treatment->update(['status' => ! $treatment->status]);
        return response()->json([
            'ok'     => true,
            'status' => $treatment->fresh()->status,
            'label'  => $treatment->fresh()->statusLabel(),
        ]);
    }
}
