<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRackRequest;
use App\Http\Requests\UpdateRackRequest;
use App\Models\Rack;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class RackController extends Controller
{
    public function index(): View
    {
        // Single aggregate query instead of separate COUNT() round-trips per card.
        $counts = Rack::selectRaw('COUNT(*) as total, SUM(status = 1) as active, SUM(status = 0) as inactive')
            ->first();

        $stats = [
            'racks_total'    => (int) $counts->total,
            'racks_active'   => (int) $counts->active,
            'racks_inactive' => (int) $counts->inactive,
        ];

        return view('racks.index', [
            'suggestedCode' => Rack::generateNextCode(),
            'stats'         => $stats,
        ]);
    }

    public function data(Request $request)
    {
        $q = Rack::query();

        $searchParam = $request->query('search');
        $searchValue = is_array($searchParam) ? ($searchParam['value'] ?? '') : ($searchParam ?? '');
        if ($search = trim((string) $searchValue)) {
            $q->where(function ($qq) use ($search) {
                $qq->where('code', 'like', "%{$search}%")
                   ->orWhere('name', 'like', "%{$search}%")
                   ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if (($status = $request->query('status')) !== null && $status !== '') {
            $q->where('status', $status);
        }

        return DataTables::eloquent($q)
            ->addIndexColumn()
            ->editColumn('code', fn (Rack $r) =>
                '<code class="text-muted">' . e($r->code) . '</code>'
            )
            ->editColumn('name', fn (Rack $r) =>
                '<span class="rack-name">' . e($r->name) . '</span>'
            )
            ->addColumn('status_badge', fn (Rack $r) =>
                '<span class="status-pill ' . ($r->isActive() ? 'status-active' : 'status-inactive') . '">'
                    . '<span class="status-dot"></span>' . $r->statusLabel() . '</span>'
            )
            ->editColumn('created_at', fn (Rack $r) =>
                $r->created_at ? $r->created_at->format('d M, Y') : '—'
            )
            ->addColumn('actions', function (Rack $r) {
                $canEdit   = auth()->user()?->hasPermission('racks.edit')   ?? false;
                $canDelete = auth()->user()?->hasPermission('racks.delete') ?? false;

                $html = '<div class="d-flex gap-1 justify-content-center">';
                if ($canEdit) {
                    $html .= '<a href="' . route('racks.edit', $r) . '" class="action-btn action-edit" title="Edit"><i class="ti ti-edit"></i></a>';
                    $html .= '<button type="button" class="action-btn action-toggle js-toggle-rack" data-id="' . $r->id . '" data-url="' . route('racks.toggle-status', $r) . '" title="Toggle Status"><i class="ti ti-toggle-' . ($r->isActive() ? 'right' : 'left') . '"></i></button>';
                }
                if ($canDelete) {
                    $html .= '<button type="button" class="action-btn action-delete js-delete-rack" data-id="' . $r->id . '" data-url="' . route('racks.destroy', $r) . '" data-name="' . e($r->name) . '" title="Delete"><i class="ti ti-trash"></i></button>';
                }
                $html .= '</div>';
                return $html;
            })
            ->rawColumns(['code', 'name', 'status_badge', 'actions'])
            ->toJson();
    }

    public function create(): View
    {
        return view('racks.create', [
            'suggestedCode' => Rack::generateNextCode(),
        ]);
    }

    public function store(StoreRackRequest $request): JsonResponse
    {
        $rack = Rack::create($request->validated());

        return response()->json([
            'message'  => 'Rack created.',
            'rack'     => $rack,
            'redirect' => route('racks.index'),
        ], 201);
    }

    public function edit(Rack $rack): View
    {
        return view('racks.edit', ['rack' => $rack]);
    }

    public function update(UpdateRackRequest $request, Rack $rack): JsonResponse
    {
        $rack->update($request->validated());

        return response()->json([
            'message'  => 'Rack updated.',
            'rack'     => $rack->fresh(),
            'redirect' => route('racks.index'),
        ]);
    }

    public function destroy(Rack $rack): JsonResponse
    {
        $rack->delete();
        return response()->json(['message' => 'Rack deleted.']);
    }

    public function toggleStatus(Rack $rack): JsonResponse
    {
        $rack->status = ! $rack->status;
        $rack->save();
        return response()->json(['message' => 'Status updated.', 'status' => $rack->status]);
    }
}
