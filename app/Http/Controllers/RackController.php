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
        return view('racks.index', [
            'suggestedCode' => Rack::generateNextCode(),
        ]);
    }

    public function data(Request $request)
    {
        $q = Rack::query();

        if ($search = trim((string) $request->query('search.value', $request->query('search', '')))) {
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
            ->addColumn('status_badge', fn (Rack $r) =>
                '<span class="badge ' . $r->statusBadgeClass() . '">' . $r->statusLabel() . '</span>'
            )
            ->addColumn('actions', function (Rack $r) {
                $canEdit   = auth()->user()?->hasPermission('racks.edit')   ?? false;
                $canDelete = auth()->user()?->hasPermission('racks.delete') ?? false;

                $html = '<div class="d-flex gap-1 justify-content-center">';
                if ($canEdit) {
                    $html .= '<a href="' . route('racks.edit', $r) . '" class="btn btn-sm btn-soft-primary"><i class="ti ti-edit"></i></a>';
                    $html .= '<button type="button" class="btn btn-sm btn-soft-secondary js-toggle-rack" data-id="' . $r->id . '"><i class="ti ti-toggle-' . ($r->isActive() ? 'right' : 'left') . '"></i></button>';
                }
                if ($canDelete) {
                    $html .= '<button type="button" class="btn btn-sm btn-soft-danger js-delete-rack" data-id="' . $r->id . '"><i class="ti ti-trash"></i></button>';
                }
                $html .= '</div>';
                return $html;
            })
            ->rawColumns(['status_badge', 'actions'])
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
