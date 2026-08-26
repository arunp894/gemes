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
        // Single aggregate query instead of separate COUNT() round-trips per card.
        $counts = Channel::selectRaw('COUNT(*) as total, SUM(status = 1) as active, SUM(status = 0) as inactive')
            ->first();

        $stats = [
            'channels_total'    => (int) $counts->total,
            'channels_active'   => (int) $counts->active,
            'channels_inactive' => (int) $counts->inactive,
        ];

        return view('channels.index', compact('stats'));
    }

    public function data(Request $request): JsonResponse
    {
        $q = Channel::query();

        if ($request->filled('status') && $request->query('status') !== '') {
            $q->where('status', (bool) $request->query('status'));
        }

        return DataTables::eloquent($q)
            ->addIndexColumn()
            ->addColumn('icon_preview', fn(Channel $c) =>
                $c->icon
                    ? '<span class="channel-icon-badge"><i class="' . e($c->icon) . '"></i></span>'
                    : '<span class="channel-icon-badge channel-icon-empty"><i class="ti ti-broadcast"></i></span>'
            )
            ->editColumn('name', fn(Channel $c) =>
                '<span class="channel-name">' . e($c->name) . '</span>'
            )
            ->editColumn('status', fn(Channel $c) =>
                '<span class="status-pill ' . ($c->isActive() ? 'status-active' : 'status-inactive') . '">'
                . '<span class="status-dot"></span>' . e($c->statusLabel()) . '</span>'
            )
            ->addColumn('sales_count', fn(Channel $c) =>
                $c->sales()->withTrashed()->count()
            )
            ->addColumn('actions', function (Channel $c) {
                $canEdit   = auth()->user()?->hasPermission('channels.edit')   ?? false;
                $canDelete = auth()->user()?->hasPermission('channels.delete') ?? false;

                $html  = '<div class="d-flex gap-1 justify-content-center">';
                $html .= '<a href="' . route('channels.show', $c) . '" class="action-btn action-view" title="View"><i class="ti ti-eye"></i></a>';
                if ($canEdit) {
                    $html .= '<a href="' . route('channels.edit', $c) . '" class="action-btn action-edit" title="Edit"><i class="ti ti-edit"></i></a>';
                }
                if ($canEdit) {
                    $toggle = $c->isActive() ? 'Deactivate' : 'Activate';
                    $html .= '<button type="button" class="action-btn action-toggle js-toggle-channel"'
                        . ' data-url="' . route('channels.toggle-status', $c) . '"'
                        . ' title="' . $toggle . '">'
                        . '<i class="ti ti-' . ($c->isActive() ? 'eye-off' : 'eye') . '"></i>'
                        . '</button>';
                }
                if ($canDelete) {
                    $html .= '<button type="button" class="action-btn action-delete js-delete-channel"'
                        . ' data-url="' . route('channels.destroy', $c) . '"'
                        . ' data-name="' . e($c->name) . '"'
                        . ($c->hasSales() ? ' data-has-sales="1"' : '')
                        . ' title="Delete"><i class="ti ti-trash"></i></button>';
                }
                $html .= '</div>';
                return $html;
            })
            ->rawColumns(['icon_preview', 'name', 'status', 'actions'])
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
