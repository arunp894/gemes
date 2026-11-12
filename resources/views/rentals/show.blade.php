@extends('layout.app')

@section('title', $rental->rent_number)

@section('content')
@php
    $money   = fn ($v) => $settings->formatMoney($v);
    $outLines = $rental->lines->where('status', 'out');
    $lineBadge = ['out' => 'badge-soft-info', 'returned' => 'badge-soft-success', 'lost' => 'badge-soft-danger'];
@endphp
<div class="container-fluid rentals-page">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1"><h4 class="page-main-title m-0">{{ $rental->rent_number }}</h4></div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('rentals.index') }}">Stone Memo</a></li>
                <li class="breadcrumb-item active">{{ $rental->rent_number }}</li>
            </ol>
        </div>
    </div>

    <div class="toast-container position-fixed top-0 end-0 p-3" id="rentalToasts" style="z-index:1080"></div>

    @if ($rental->isOverdue())
        <div class="alert alert-danger"><i class="ti ti-alert-triangle me-2"></i>
            Overdue by <strong>{{ $rental->overdueDays() }}</strong> day(s) &mdash; due {{ $rental->due_at->format('d M Y H:i') }}.
            {{ $outLines->count() }} stone(s) still out.</div>
    @endif
    @if ($rental->isReturned() && $rental->depositHeld() > 0.004)
        <div class="alert alert-warning">Deposit of <strong>{{ $money($rental->depositHeld()) }}</strong> is still held for this closed memo.</div>
    @endif

    <div class="card">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <span class="badge {{ $rental->statusBadgeClass() }} fs-xs">{{ $rental->statusLabel() }}</span>
                <span class="ms-2 text-muted">Created {{ $rental->created_at->format('d M Y H:i') }} by {{ $rental->creator?->name ?? '—' }}</span>
            </div>
            <div class="d-flex gap-2 no-print">
                <a href="{{ route('rentals.print', $rental) }}" target="_blank" class="btn btn-light btn-sm"><i class="ti ti-printer me-1"></i> Print</a>
                @if ($rental->isActive())
                    @permission('rentals.return')
                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#extendModal"><i class="ti ti-calendar-plus me-1"></i> Extend</button>
                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#returnModal"><i class="ti ti-arrow-back-up me-1"></i> Return stones</button>
                    @endpermission
                    @permission('rentals.cancel')
                        @if ($rental->lines->where('status', '!=', 'out')->isEmpty())
                            <button class="btn btn-outline-danger btn-sm" id="cancelRentalBtn"><i class="ti ti-x me-1"></i> Cancel</button>
                        @endif
                    @endpermission
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Details</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6"><div class="text-muted small">Customer</div><div class="fw-semibold">{{ $rental->customer?->display_name }}</div>
                            <div class="small text-muted">{{ $rental->customer?->phone }}</div></div>
                        <div class="col-sm-6"><div class="text-muted small">Location</div><div class="fw-semibold">{{ $rental->location?->name }}</div></div>
                        <div class="col-sm-6"><div class="text-muted small">From</div><div class="fw-semibold">{{ $rental->start_at->format('d M Y H:i') }}</div></div>
                        <div class="col-sm-6"><div class="text-muted small">To (due back)</div><div class="fw-semibold {{ $rental->isOverdue() ? 'text-danger' : '' }}">{{ $rental->due_at->format('d M Y H:i') }}</div></div>
                        @if ($rental->returned_at)
                            <div class="col-sm-6"><div class="text-muted small">Closed</div><div class="fw-semibold">{{ $rental->returned_at->format('d M Y H:i') }}</div></div>
                        @endif
                        @if ($rental->note)
                            <div class="col-12"><div class="text-muted small">Note</div><div>{{ $rental->note }}</div></div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Stones</h5></div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Lot</th><th>Stone</th><th class="text-end">Pcs</th><th class="text-end">CT</th>
                            <th class="text-end">Rate / CT</th><th class="text-end">Memo Total</th><th class="text-end">Deposit</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach ($rental->lines as $l)
                                <tr>
                                    <td><code>{{ $l->purchaseProduct?->lot_code }}</code></td>
                                    <td>{{ $l->product?->title }}</td>
                                    <td class="text-end">{{ $l->qty }}</td>
                                    <td class="text-end">{{ $l->carat_weight !== null ? rtrim(rtrim(number_format((float) $l->carat_weight, 3), '0'), '.') : '—' }}</td>
                                    <td class="text-end">{{ (float) $l->rent_rate > 0 ? $money($l->rent_rate) : '—' }}</td>
                                    <td class="text-end">{{ $money($l->rent_amount) }}</td>
                                    <td class="text-end">{{ $money($l->deposit_amount) }}</td>
                                    <td><span class="badge {{ $lineBadge[$l->status] ?? 'badge-soft-secondary' }}">{{ ucfirst($l->status) }}</span>
                                        @if ($l->returned_at)<div class="text-muted fs-xxs">{{ $l->returned_at->format('d M H:i') }}</div>@endif</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">History</h5></div>
                <ul class="list-group list-group-flush">
                    @forelse ($rental->logs as $log)
                        <li class="list-group-item d-flex justify-content-between">
                            <span><span class="badge badge-soft-secondary me-2">{{ ucfirst($log->action) }}</span>{{ $log->detail }}</span>
                            <small class="text-muted">{{ $log->user?->name ?? '—' }} &middot; {{ $log->created_at->format('d M Y H:i') }}</small>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No history.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Money</h5></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between"><span>Memo fee</span><strong>{{ $money($rental->rent_total) }}</strong></div>
                    <div class="d-flex justify-content-between text-muted"><span>&nbsp;&nbsp;collected</span><span>{{ $money($rental->rent_paid) }}</span></div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between"><span>Deposit (refundable)</span><strong>{{ $money($rental->deposit_total) }}</strong></div>
                    <div class="d-flex justify-content-between text-muted"><span>&nbsp;&nbsp;collected</span><span>{{ $money($rental->deposit_paid) }}</span></div>
                    <div class="d-flex justify-content-between text-muted"><span>&nbsp;&nbsp;refunded</span><span>{{ $money($rental->deposit_refunded) }}</span></div>
                    <div class="d-flex justify-content-between text-muted"><span>&nbsp;&nbsp;kept (late / damage / lost)</span><span>{{ $money($rental->deduction_total) }}</span></div>
                    <div class="d-flex justify-content-between fw-semibold mt-1"><span>Deposit held</span><span>{{ $money(max(0, $rental->depositHeld())) }}</span></div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Payments</h5></div>
                <ul class="list-group list-group-flush">
                    @forelse ($rental->payments as $p)
                        <li class="list-group-item d-flex justify-content-between">
                            <span><span class="badge {{ $p->type === 'refund' ? 'badge-soft-warning' : 'badge-soft-success' }}">{{ ucfirst($p->type) }}</span>
                                <small class="text-muted ms-1">{{ $p->payment_date->format('d M') }} &middot; {{ ucfirst(str_replace('_', ' ', $p->payment_method)) }}</small></span>
                            <strong>{{ $p->type === 'refund' ? '−' : '' }}{{ $money($p->amount) }}</strong>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No payments.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

@if ($rental->isActive())
{{-- Return modal --}}
<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Return stones &mdash; {{ $rental->rent_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <table class="table table-sm align-middle">
                    <thead><tr><th>Lot</th><th>Stone</th><th class="text-end">Deposit</th><th style="width:150px">Outcome</th></tr></thead>
                    <tbody>
                        @foreach ($outLines as $l)
                            <tr>
                                <td><code>{{ $l->purchaseProduct?->lot_code }}</code></td>
                                <td>{{ $l->product?->title }}</td>
                                <td class="text-end">{{ $money($l->deposit_amount) }}</td>
                                <td><select class="form-select form-select-sm js-outcome" data-id="{{ $l->id }}" data-deposit="{{ $l->deposit_amount }}">
                                    <option value="">Still out</option><option value="return" selected>Returned</option><option value="lost">Lost (keep deposit)</option>
                                </select></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="row g-3">
                    <div class="col-sm-4"><label class="form-label">Deduction (late / damage)</label>
                        <input type="number" min="0" step="0.01" class="form-control" id="retDeduction" value="0"></div>
                    <div class="col-sm-4"><label class="form-label">Refund to customer</label>
                        <input type="number" min="0" step="0.01" class="form-control" id="retRefund" value="0"></div>
                    <div class="col-sm-4"><label class="form-label">Method</label>
                        <select class="form-select" id="retMethod">@foreach ($paymentMethods as $k => $lbl)<option value="{{ $k }}">{{ $lbl }}</option>@endforeach</select></div>
                    <div class="col-12"><label class="form-label">Note</label><input type="text" class="form-control" id="retNote" maxlength="255"></div>
                </div>
                <div class="form-text mt-2">Refund defaults to the deposit of returned stones less the deduction. Deposit on lost stones is kept.</div>
            </div>
            <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-success" id="saveReturnBtn">Save return</button></div>
        </div>
    </div>
</div>

{{-- Extend modal --}}
<div class="modal fade" id="extendModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title">Extend due date</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><label class="form-label">New due date &amp; time</label>
            <input type="datetime-local" class="form-control" id="newDue" value="{{ $rental->due_at->copy()->addDay()->format('Y-m-d\TH:i') }}"></div>
        <div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Close</button><button class="btn btn-primary" id="saveExtendBtn">Extend</button></div>
    </div></div>
</div>

@push('scripts')
<script>
$(function () {
    const csrf = $('meta[name="csrf-token"]').attr('content');
    const held = {{ max(0, $rental->depositHeld()) }};

    function toast(ok, msg) {
        const el = $('<div class="toast align-items-center text-bg-' + (ok ? 'success' : 'danger') + ' border-0"><div class="d-flex"><div class="toast-body"></div></div></div>');
        el.find('.toast-body').text(msg);
        $('#rentalToasts').append(el);
        new bootstrap.Toast(el[0], { delay: 3500 }).show();
    }
    function post(url, body, done) {
        $.ajax({ url, type: 'POST', data: JSON.stringify(body), contentType: 'application/json',
            headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            success: r => { toast(true, r.message); setTimeout(() => location.reload(), 600); },
            error: x => toast(false, (x.responseJSON && (x.responseJSON.message || Object.values(x.responseJSON.errors || {}).flat().join(' '))) || 'Failed.'),
            complete: done });
    }

    function recalcRefund() {
        let dep = 0;
        $('.js-outcome').each(function () { if ($(this).val() === 'return') dep += parseFloat($(this).data('deposit')) || 0; });
        const refund = Math.max(0, Math.min(dep - (parseFloat($('#retDeduction').val()) || 0), held));
        $('#retRefund').val(refund.toFixed(2));
    }
    $('.js-outcome, #retDeduction').on('change input', recalcRefund);
    $('#returnModal').on('shown.bs.modal', recalcRefund);

    $('#saveReturnBtn').on('click', function () {
        const ret = [], lost = [];
        $('.js-outcome').each(function () {
            const v = $(this).val(), id = $(this).data('id');
            if (v === 'return') ret.push(id); else if (v === 'lost') lost.push(id);
        });
        const $b = $(this).prop('disabled', true);
        post('{{ route('rentals.return', $rental) }}', {
            return_ids: ret, lost_ids: lost, deduction: $('#retDeduction').val() || 0, refund: $('#retRefund').val() || 0,
            payment_method: $('#retMethod').val(), note: $('#retNote').val(),
        }, () => $b.prop('disabled', false));
    });

    $('#saveExtendBtn').on('click', function () {
        const $b = $(this).prop('disabled', true);
        post('{{ route('rentals.extend', $rental) }}', { due_at: $('#newDue').val() }, () => $b.prop('disabled', false));
    });

    $('#cancelRentalBtn').on('click', function () {
        if (!confirm('Cancel this memo? Stones go back to stock and everything collected is refunded.')) return;
        post('{{ route('rentals.cancel', $rental) }}', {}, () => {});
    });
});
</script>
@endpush
@endif
@endsection
