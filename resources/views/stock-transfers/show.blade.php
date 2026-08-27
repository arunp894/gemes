@extends('layout.app')

@section('title', $transfer->transfer_number)

@section('content')
<div class="container-fluid">

    <div class="toast-container position-fixed top-0 end-0 p-3" id="transferShowToastContainer" style="z-index: 1080;"></div>

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
                                    <th class="text-end">Carat</th>
                                    <th class="text-end">Qty</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($transfer->lines as $line)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $line->product?->title }}</div>
                                            <small class="text-muted">SKU: {{ $line->product?->sku }}</small>
                                        </td>
                                        <td>
                                            <code class="small">
                                                <a href="{{ route('stock.piece', $line->purchase_product_id) }}">
                                                    {{ optional($line->purchaseProduct)->barcode ?? optional($line->purchaseProduct)->lot_code ?? '#' . $line->purchase_product_id }}
                                                </a>
                                            </code>
                                        </td>
                                        @php $lineCarat = $line->carat_weight ?? optional($line->purchaseProduct)->carat_weight; @endphp
                                        <td class="text-end">{{ $lineCarat ? $lineCarat . ' ct' : '—' }}</td>
                                        <td class="text-end fw-semibold">{{ (int) $line->qty }}</td>
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

    {{-- ==================== Status Action Confirmation Modal ==================== --}}
    <div class="modal fade" id="actionConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="confirm-modal-icon confirm-modal-icon-primary mx-auto mb-3">
                        <i class="ti ti-help"></i>
                    </div>
                    <h5 class="modal-title mb-2">Please confirm</h5>
                    <p class="text-muted mb-0" id="actionConfirmText"></p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmActionBtn">
                        <i class="ti ti-check me-1"></i>Confirm
                    </button>
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

    function showToast(type, message) {
        const container = document.getElementById('transferShowToastContainer');
        if (!container) return;
        const isSuccess = type === 'success';
        const el = document.createElement('div');
        el.className = 'toast align-items-center border-0 text-bg-' + (isSuccess ? 'success' : 'danger');
        el.setAttribute('role', 'alert');
        el.innerHTML = '<div class="d-flex">'
            + '<div class="toast-body d-flex align-items-center gap-2">'
            + '<i class="ti ' + (isSuccess ? 'ti-circle-check' : 'ti-alert-circle') + ' fs-lg"></i>'
            + $('<div/>').text(message).html()
            + '</div>'
            + '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>'
            + '</div>';
        container.appendChild(el);
        const toast = new bootstrap.Toast(el, { delay: 2500 });
        el.addEventListener('hidden.bs.toast', () => el.remove());
        toast.show();
    }

    const actionModalEl = document.getElementById('actionConfirmModal');
    const actionModal = actionModalEl ? new bootstrap.Modal(actionModalEl) : null;
    let pendingActionUrl = null;

    $('.js-status-action').on('click', function () {
        pendingActionUrl = $(this).data('url');
        $('#actionConfirmText').text($(this).data('confirm') || 'Are you sure?');
        if (actionModal) actionModal.show();
    });

    $('#confirmActionBtn').on('click', function () {
        if (!pendingActionUrl) return;
        const $btn = $(this).prop('disabled', true);

        $.ajax({
            url: pendingActionUrl, type: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: function (res) {
                if (res.ok) {
                    if (actionModal) actionModal.hide();
                    showToast('success', res.message || 'Done.');
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    showToast('error', res.message || 'Action failed.');
                    $btn.prop('disabled', false);
                }
            },
            error: function (xhr) {
                showToast('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Action failed.');
                $btn.prop('disabled', false);
            },
        });
    });

    if (actionModalEl) {
        actionModalEl.addEventListener('hidden.bs.modal', function () {
            pendingActionUrl = null;
            $('#confirmActionBtn').prop('disabled', false);
        });
    }
});
</script>
<style>
    @media print {
        .breadcrumb, .page-title-head .text-end,
        .js-status-action, .btn { display: none !important; }
    }

    /* Confirmation modal icon badge, shared with Purchase/Stock Audit show pages */
    .confirm-modal-icon {
        width: 56px; height: 56px; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem;
    }
    .confirm-modal-icon-primary { background: #eff6ff; color: #1d4ed8; }
</style>
@endpush
