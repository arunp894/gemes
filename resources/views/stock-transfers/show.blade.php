@extends('layout.app')

@section('title', $transfer->transfer_number)

@section('content')
<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">
                Transfer {{ $transfer->transfer_number }}
                <span class="badge {{ $transfer->statusBadgeClass() }} ms-2">{{ $transfer->statusLabel() }}</span>
            </h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('stock-transfers.index') }}">Transfers</a></li>
                <li class="breadcrumb-item active">{{ $transfer->transfer_number }}</li>
            </ol>
        </div>
    </div>

    {{-- Actions --}}
    <div class="card">
        <div class="card-body d-flex flex-wrap gap-2">
            @permission('stock-transfers.edit')
                @if ($transfer->isEditable())
                    <a href="{{ route('stock-transfers.edit', $transfer) }}" class="btn btn-primary">
                        <i class="ti ti-edit me-1"></i> Edit
                    </a>
                @endif
            @endpermission

            @permission('stock-transfers.post')
                @if ($transfer->isDraft())
                    <button class="btn btn-info js-status-action"
                        data-url="{{ route('stock-transfers.post', $transfer) }}"
                        data-confirm="Post this transfer? Stock will leave the source location.">
                        <i class="ti ti-send me-1"></i> Post
                    </button>
                @endif
                @if ($transfer->isInTransit())
                    <button class="btn btn-success js-status-action"
                        data-url="{{ route('stock-transfers.receive', $transfer) }}"
                        data-confirm="Mark this transfer as received? Stock will arrive at the destination.">
                        <i class="ti ti-check me-1"></i> Receive
                    </button>
                @endif
                @if ($transfer->isDraft() || $transfer->isInTransit())
                    <button class="btn btn-soft-danger js-status-action"
                        data-url="{{ route('stock-transfers.cancel', $transfer) }}"
                        data-confirm="Cancel this transfer? In-transit stock will return to the source.">
                        <i class="ti ti-ban me-1"></i> Cancel
                    </button>
                @endif
            @endpermission

            <button class="btn btn-light ms-auto" onclick="window.print()">
                <i class="ti ti-printer me-1"></i> Print
            </button>
        </div>
    </div>

    <div class="row">
        {{-- Lines --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted text-uppercase small">From</h6>
                            @if ($transfer->fromLocation)
                                <div class="fw-semibold">{{ $transfer->fromLocation->name }}</div>
                                <small class="text-muted">{{ $transfer->fromLocation->location_code }}</small>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted text-uppercase small">To</h6>
                            @if ($transfer->toLocation)
                                <div class="fw-semibold">{{ $transfer->toLocation->name }}</div>
                                <small class="text-muted">{{ $transfer->toLocation->location_code }}</small>
                            @endif
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-custom align-middle mb-0">
                            <thead class="bg-light bg-opacity-25 thead-sm">
                                <tr class="text-uppercase fs-xxs">
                                    <th>#</th>
                                    <th>Product</th>
                                    <th>Barcode</th>
                                    <th class="text-end">Qty</th>
                                    <th>To Rack</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($transfer->lines as $line)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ optional($line->product)->title ?? '—' }}</div>
                                            <small class="text-muted">SKU: {{ optional($line->product)->sku ?? '—' }}</small>
                                        </td>
                                        <td>
                                            <code class="small">
                                                <a href="{{ route('stock.piece', $line->purchase_product_id) }}">
                                                    {{ optional($line->purchaseProduct)->barcode ?? '#' . $line->purchase_product_id }}
                                                </a>
                                            </code>
                                        </td>
                                        <td class="text-end fw-semibold">{{ (int) $line->qty }}</td>
                                        <td>
                                            @if ($line->toRack)
                                                {{ $line->toRack->code }} — {{ $line->toRack->name }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td><small>{{ $line->notes }}</small></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">No lines.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if ($transfer->note)
                <div class="card">
                    <div class="card-header border-light"><h5 class="card-title mb-0">Note</h5></div>
                    <div class="card-body">
                        <p class="mb-0" style="white-space: pre-wrap;">{{ $transfer->note }}</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header border-light"><h5 class="card-title mb-0">Timeline</h5></div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Transfer Date</dt>
                        <dd class="col-7">{{ optional($transfer->transfer_date)->format('d M Y') }}</dd>

                        <dt class="col-5 text-muted">Created</dt>
                        <dd class="col-7">{{ optional($transfer->created_at)->format('d M Y, h:i A') }}</dd>

                        @if ($transfer->posted_at)
                            <dt class="col-5 text-muted">Posted</dt>
                            <dd class="col-7">{{ $transfer->posted_at->format('d M Y, h:i A') }}</dd>
                        @endif

                        @if ($transfer->received_at)
                            <dt class="col-5 text-muted">Received</dt>
                            <dd class="col-7">{{ $transfer->received_at->format('d M Y, h:i A') }}</dd>
                        @endif

                        @if ($transfer->cancelled_at)
                            <dt class="col-5 text-muted">Cancelled</dt>
                            <dd class="col-7">{{ $transfer->cancelled_at->format('d M Y, h:i A') }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-light"><h5 class="card-title mb-0">Summary</h5></div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-7 text-muted">Lines</dt>
                        <dd class="col-5 text-end">{{ $transfer->lines->count() }}</dd>
                        <dt class="col-7 text-muted">Total pieces</dt>
                        <dd class="col-5 text-end">{{ (int) $transfer->lines->sum('qty') }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-light"><h5 class="card-title mb-0">Audit</h5></div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        @if ($transfer->creator)
                            <dt class="col-5 text-muted">Created by</dt>
                            <dd class="col-7">{{ $transfer->creator->name }}</dd>
                        @endif
                        @if ($transfer->updater)
                            <dt class="col-5 text-muted">Last update by</dt>
                            <dd class="col-7">{{ $transfer->updater->name }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    $('.js-status-action').on('click', function () {
        const url     = $(this).data('url');
        const confirm = $(this).data('confirm');
        if (confirm && !window.confirm(confirm)) return;
        $.ajax({
            url, type: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: function (res) {
                if (res.ok) window.location.reload();
                else alert(res.message || 'Action failed.');
            },
            error: function (xhr) {
                alert((xhr.responseJSON && xhr.responseJSON.message) || 'Action failed.');
            },
        });
    });
});
</script>
<style>
    @media print {
        .breadcrumb, .page-title-head .text-end,
        .js-status-action, .btn { display: none !important; }
    }
</style>
@endpush
