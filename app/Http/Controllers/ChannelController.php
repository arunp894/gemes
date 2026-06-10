<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Http\Requests\StoreChannelRequest;
use App\Http\Requests\UpdateChannelRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ChannelController extends Controller
{
    /* ─── List ─────────────────────────────────────────────── */

    public function index(): View
    {
        return view('channels.index');
    }

    public function data(Request $request): JsonResponse
    {
        $q = Channel::query();

        if ($request->filled('status') && $request->query('status') !== '') {
            $q->where('status', (bool) $request->query('status'));
        }

        return DataTables::eloquent($q)
            ->addColumn('icon_preview', fn(Channel $c) =>
                $c->icon ? '<i class="' . e($c->icon) . ' fs-lg me-1"></i>' : ''
            )
            ->editColumn('status', fn(Channel $c) =>
                '<span class="badge ' . ($c->isActive() ? 'badge-soft-success' : 'badge-soft-secondary') . ' fs-xxs">'
                . e($c->statusLabel()) . '</span>'
            )
            ->addColumn('sales_count', fn(Channel $c) =>
                $c->sales()->withTrashed()->count()
            )
            ->addColumn('actions', function (Channel $c) {
                $canEdit   = auth()->user()?->hasPermission('channels.edit')   ?? false;
                $canDelete = auth()->user()?->hasPermission('channels.delete') ?? false;

                $html  = '<div class="d-flex gap-1 justify-content-center">';
                $html .= '<a href="' . route('channels.show', $c) . '" class="btn btn-default btn-icon btn-sm" title="View"><i class="ti ti-eye fs-lg"></i></a>';
                if ($canEdit) {
                    $html .= '<a href="' . route('channels.edit', $c) . '" class="btn btn-default btn-icon btn-sm" title="Edit"><i class="ti ti-edit fs-lg"></i></a>';
                }
                if ($canEdit) {
                    $toggle = $c->isActive() ? 'Deactivate' : 'Activate';
                    $html .= '<button type="button" class="btn btn-default btn-icon btn-sm js-toggle-channel text-' . ($c->isActive() ? 'warning' : 'success') . '"'
                        . ' data-url="' . route('channels.toggle-status', $c) . '"'
                        . ' title="' . $toggle . '">'
                        . '<i class="ti ti-' . ($c->isActive() ? 'eye-off' : 'eye') . ' fs-lg"></i>'
                        . '</button>';
                }
                if ($canDelete) {
                    $html .= '<button type="button" class="btn btn-default btn-icon btn-sm js-delete-channel text-danger"'
                        . ' data-url="' . route('channels.destroy', $c) . '"'
                        . ' data-name="' . e($c->name) . '"'
                        . ($c->hasSales() ? ' data-has-sales="1"' : '')
                        . ' title="Delete"><i class="ti ti-trash fs-lg"></i></button>';
                }
                $html .= '</div>';
                return $html;
            })
            ->rawColumns(['icon_preview', 'status', 'actions'])
            ->toJson();
    }

    /* ─── Create / Store ──────────────────────────────────── */

    public function create(): View
    {
        return view('channels.create');
    }

    public function store(StoreChannelRequest $request): JsonResponse
    {
        $channel = Channel::create($request->validated());

        return response()->json([
            'ok'       => true,
            'message'  => 'Channel created.',
            'redirect' => route('channels.index'),
        ], 201);
    }

    /* ─── Show ─────────────────────────────────────────────── */

    public function show(Channel $channel): View
    {
        $channel->loadCount(['sales']);
        return view('channels.show', compact('channel'));
    }

    /* ─── Edit / Update ────────────────────────────────────── */

    public function edit(Channel $channel): View
    {
        return view('channels.edit', compact('channel'));
    }

    public function update(UpdateChannelRequest $request, Channel $channel): JsonResponse
    {
        $channel->update($request->validated());

        return response()->json([
            'ok'       => true,
            'message'  => 'Channel updated.',
            'redirect' => route('channels.index'),
        ]);
    }

    /* ─── Delete ────────────────────────────────────────────── */

    public function destroy(Channel $channel): JsonResponse
    {
        // Hard block: if any sale (even soft-deleted) belongs to this channel,
        // refuse deletion to preserve referential audit trail.
        if ($channel->hasSales()) {
            $count = $channel->sales()->withTrashed()->count();
            return response()->json([
                'ok'      => false,
                'message' => 'Cannot delete "' . $channel->name . '" — it has ' . $count . ' sale(s) recorded against it. Deactivate it instead.',
            ], 422);
        }

        $channel->delete(); // soft delete
        return response()->json(['ok' => true, 'message' => 'Channel deleted.']);
    }

    /* ─── Toggle Status ─────────────────────────────────────── */

    public function toggleStatus(Channel $channel): JsonResponse
    {
        $channel->update(['status' => ! $channel->status]);
        return response()->json([
            'ok'     => true,
            'status' => $channel->fresh()->status,
            'label'  => $channel->fresh()->statusLabel(),
        ]);
    }
}
