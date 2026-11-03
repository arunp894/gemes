@extends('layout.app')

@section('title', 'Rent Report')

@section('content')
@php
    $m  = fn ($v) => $settings->formatMoney($v, 2);
    $ct = fn ($v) => rtrim(rtrim(number_format((float) $v, 3), '0'), '.') . ' ct';
@endphp

<div class="container-fluid rent-report-page">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Rent Report</h4>
            <small class="text-muted">Stone rentals by customer &middot; rent income is kept separate from sales</small>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Reports</a></li>
                <li class="breadcrumb-item active">Rent Report</li>
            </ol>
        </div>
    </div>

    <form method="GET" action="{{ route('reports.rentals') }}" class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-2"><label class="form-label">From</label><input type="date" name="from" class="form-control" value="{{ $from }}"></div>
                <div class="col-md-2"><label class="form-label">To</label><input type="date" name="to" class="form-control" value="{{ $to }}"></div>
                <div class="col-md-3">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" class="form-select js-select2">
                        <option value="">All customers</option>
                        @foreach ($customers as $c)
                            <option value="{{ $c->id }}" @selected($customerId == $c->id)>{{ $c->company_name ?: $c->name }}{{ $c->customer_code ? ' · ' . $c->customer_code : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Location</label>
                    <select name="location_id" class="form-select">
                        <option value="">All locations</option>
                        @foreach ($locations as $loc)
                            <option value="{{ $loc->id }}" @selected($locationId == $loc->id)>{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        @foreach (['' => 'All', 'active' => 'Active', 'overdue' => 'Overdue', 'returned' => 'Returned', 'cancelled' => 'Cancelled'] as $k => $lbl)
                            <option value="{{ $k }}" @selected($status === $k)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>Apply</button>
                    <a href="{{ route('reports.rentals') }}" class="btn btn-light"><i class="ti ti-x"></i> Reset</a>
                    <a href="{{ route('reports.rentals.export', array_filter(['from' => $from, 'to' => $to, 'customer_id' => $customerId, 'location_id' => $locationId, 'status' => $status])) }}"
                       class="btn btn-success ms-auto"><i class="ti ti-file-spreadsheet me-1"></i>Excel</a>
                </div>
            </div>
        </div>
    </form>

    <div class="row g-3 mb-3">
        @foreach ([
            ['Rent Income', $m($totals['rent']), 'text-dark'],
            ['Deposit Held', $m($totals['held']), 'text-dark'],
            ['Rentals / Stones', number_format($totals['rentals']) . ' / ' . number_format($totals['stones']), 'text-dark'],
            ['Overdue Rentals', number_format($totals['overdue']), $totals['overdue'] ? 'text-danger' : 'text-dark'],
        ] as [$label, $value, $cls])
            <div class="col-6 col-xl-3">
                <div class="card mb-0"><div class="card-body py-3">
                    <div class="text-muted small text-uppercase">{{ $label }}</div>
                    <div class="fs-4 fw-semibold {{ $cls }}">{{ $value }}</div>
                </div></div>
            </div>
        @endforeach
    </div>

    @if ($truncated)
        <div class="alert alert-warning py-2">Showing the first {{ number_format($rowLimit) }} rentals &mdash; narrow the date range to see the rest.</div>
    @endif

    {{-- Customer-wise --}}
    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">By customer</h5></div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="bg-light bg-opacity-25 text-uppercase fs-xxs">
                    <tr><th>Customer</th><th class="text-end">Rentals</th><th class="text-end">Stones</th><th class="text-end">Pcs</th><th class="text-end">CT</th>
                        <th class="text-end">Rent Income</th><th class="text-end">Deposit Collected</th><th class="text-end">Refunded</th>
                        <th class="text-end">Kept</th><th class="text-end">Deposit Held</th><th class="text-end">Overdue</th></tr>
                </thead>
                <tbody>
                    @forelse ($byCustomer as $c)
                        <tr>
                            <td class="fw-semibold">{{ $c->name }}</td>
                            <td class="text-end">{{ $c->rentals }}</td><td class="text-end">{{ $c->stones }}</td><td class="text-end">{{ $c->pcs }}</td>
                            <td class="text-end">{{ $ct($c->carat) }}</td><td class="text-end">{{ $m($c->rent) }}</td>
                            <td class="text-end">{{ $m($c->deposit_paid) }}</td><td class="text-end">{{ $m($c->refunded) }}</td>
                            <td class="text-end">{{ $m($c->kept) }}</td><td class="text-end">{{ $m($c->held) }}</td>
                            <td class="text-end">@if ($c->overdue)<span class="badge badge-soft-danger">{{ $c->overdue }} &middot; {{ $c->max_days }}d</span>@else<span class="text-muted">&mdash;</span>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center text-muted py-5">No rentals match these filters.</td></tr>
                    @endforelse
                </tbody>
                @if ($byCustomer->isNotEmpty())
                    <tfoot class="fw-semibold bg-light"><tr>
                        <td>Total</td><td class="text-end">{{ $totals['rentals'] }}</td><td class="text-end">{{ $totals['stones'] }}</td><td class="text-end">{{ $totals['pcs'] }}</td>
                        <td class="text-end">{{ $ct($totals['carat']) }}</td><td class="text-end">{{ $m($totals['rent']) }}</td><td class="text-end">{{ $m($totals['deposit_paid']) }}</td>
                        <td class="text-end">{{ $m($totals['refunded']) }}</td><td class="text-end">{{ $m($totals['kept']) }}</td><td class="text-end">{{ $m($totals['held']) }}</td>
                        <td class="text-end">{{ $totals['overdue'] }}</td>
                    </tr></tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Every rental --}}
    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">Rentals</h5></div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="bg-light bg-opacity-25 text-uppercase fs-xxs">
                    <tr><th>Rent No</th><th>Customer</th><th>Lots</th><th>From</th><th>To</th><th class="text-end">Pcs</th><th class="text-end">CT</th>
                        <th class="text-end">Rent</th><th class="text-end">Deposit Held</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($rentals as $r)
                        <tr>
                            <td><a href="{{ route('rentals.show', $r) }}"><code>{{ $r->rent_number }}</code></a></td>
                            <td>{{ $r->customer?->company_name ?: $r->customer?->name }}</td>
                            <td class="small">{{ $r->lines->map(fn ($l) => $l->purchaseProduct?->lot_code)->filter()->implode(', ') }}</td>
                            <td class="text-nowrap">{{ $r->start_at->format('d M Y H:i') }}</td>
                            <td class="text-nowrap {{ $r->isOverdue() ? 'text-danger' : '' }}">{{ $r->due_at->format('d M Y H:i') }}@if ($r->isOverdue()) <small>({{ $r->overdueDays() }}d late)</small>@endif</td>
                            <td class="text-end">{{ $r->lines->sum('qty') }}</td>
                            <td class="text-end">{{ $ct($r->lines->sum('carat_weight')) }}</td>
                            <td class="text-end">{{ $m($r->isCancelled() ? 0 : $r->rent_total) }}</td>
                            <td class="text-end">{{ $m($r->isCancelled() ? 0 : max(0, $r->depositHeld())) }}</td>
                            <td><span class="badge {{ $r->statusBadgeClass() }}">{{ $r->statusLabel() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">No rentals.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .rent-report-page .card { border-radius: 10px; box-shadow: none; border: 1px solid #e2e8f0; }
    .rent-report-page th, .rent-report-page td { white-space: nowrap; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.jQuery && jQuery.fn.select2) {
        jQuery('.rent-report-page .js-select2').select2({ width: '100%' });
    }
});
</script>
@endpush
