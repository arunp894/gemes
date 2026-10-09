@extends('layout.app')

@section('title', 'Sales Report')

@section('content')
@php
    $m   = fn ($v) => $settings->formatMoney($v, 2);
    $ct  = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 3), '0'), '.') . ' ct';
    $pct = fn ($v) => $v === null ? '—' : number_format($v, 1) . '%';

    // Rich hover card for one row — escaped pieces only, rendered by Bootstrap's tooltip.
    $tip = function ($r) use ($m, $ct, $pct) {
        $line = fn ($k, $v) => '<div class="d-flex justify-content-between gap-3"><span class="opacity-75">' . e($k) . '</span><strong>' . e($v) . '</strong></div>';
        $head = fn ($t) => '<div class="text-uppercase small opacity-75 mt-2 mb-1" style="letter-spacing:.5px">' . e($t) . '</div>';

        $html  = '<div class="text-start" style="min-width:230px">';
        $html .= $head('Sale');
        $html .= $line('Invoice', $r->invoice);
        $html .= $line('Date', optional($r->date)->format('d M Y'));
        $html .= $line('Customer', $r->sale->customer?->company_name ?: ($r->sale->customer?->name ?? '—'));
        $html .= $line('Location', $r->sale->location?->name ?? '—');
        $html .= $line('Channel', $r->sale->channel?->name ?? '—');
        $html .= $line('Payment', ucfirst((string) $r->sale->payment_status));

        $html .= $head('Purchase');
        $html .= $line('Lot', $r->lot ?? '—');
        $html .= $line('Purchase Inv', $r->purchase?->invoice_number ?? '—');
        $html .= $line('Supplier', $r->supplier ?? '—');
        $html .= $line('Product', $r->name);
        $html .= $line('SKU', $r->sku ?? '—');

        $html .= $head('Amounts');
        $html .= $line('Line amount', $m($r->gross));
        if ($r->discount > 0) $html .= $line('Discount', '− ' . $m($r->discount));
        $html .= $line('Net sale', $m($r->total_sale));
        $html .= $line('Cost', $m($r->total_cost));
        $html .= $line('Profit', $m($r->profit));
        $html .= $line('Margin', $pct($r->margin));
        if ($r->tax > 0) $html .= $line('Tax (not in profit)', $m($r->tax));
        if (! $r->weighed) $html .= '<div class="small opacity-75 mt-1">Not carat-weighed: cost = rate × pieces.</div>';
        return $html . '</div>';
    };
@endphp

<div class="container-fluid sales-report-page">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Sales Report</h4>
            <small class="text-muted">One row per sold item, linked to its purchase lot &middot; posted and completed sales only</small>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Reports</a></li>
                <li class="breadcrumb-item active">Sales Report</li>
            </ol>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('reports.sales') }}" class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">From</label>
                    <input type="date" name="from" class="form-control" value="{{ $from }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <input type="date" name="to" class="form-control" value="{{ $to }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" class="form-select js-select2">
                        <option value="">All customers</option>
                        @foreach ($customers as $c)
                            <option value="{{ $c->id }}" @selected($customerId == $c->id)>
                                {{ $c->company_name ?: $c->name }}{{ $c->customer_code ? ' · ' . $c->customer_code : '' }}
                            </option>
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
                    <label class="form-label">Search</label>
                    <input type="search" name="q" class="form-control" value="{{ $search }}" placeholder="Lot no or customer name">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>Apply</button>
                    <a href="{{ route('reports.sales') }}" class="btn btn-light" title="Reset"><i class="ti ti-x"></i> Reset</a>
                    <a href="{{ route('reports.sales.export', ['from' => $from, 'to' => $to, 'customer_id' => $customerId, 'location_id' => $locationId, 'q' => $search ?: null]) }}"
                       class="btn btn-success ms-auto" title="Download as Excel"><i class="ti ti-file-spreadsheet me-1"></i>Excel</a>
                </div>
            </div>
        </div>
    </form>

    {{-- Summary --}}
    <div class="row g-3 mb-3">
        @foreach ([
            ['Total Sale', $m($totals['sale']), 'text-dark'],
            ['Total Cost', $m($totals['cost']), 'text-dark'],
            ['Total Profit', $m($totals['profit']), $totals['profit'] >= 0 ? 'text-success' : 'text-danger'],
            ['Carat / Pieces', $ct($totals['carat']) . ' / ' . number_format($totals['pcs']), 'text-dark'],
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
        <div class="alert alert-warning py-2">Showing the first {{ number_format($rowLimit) }} rows — narrow the date range to see the rest.</div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0 sales-report-table">
                <thead class="bg-light bg-opacity-25 text-uppercase fs-xxs">
                    <tr>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Stone</th>
                        <th>Lot No</th>
                        <th>INV No</th>
                        <th class="text-end">Carat</th>
                        <th class="text-end">Pcs</th>
                        <th class="text-end" data-bs-toggle="tooltip" title="Purchase rate per carat">Cost / CT</th>
                        <th class="text-end" data-bs-toggle="tooltip" title="Net sale ÷ carat">Sold / CT</th>
                        <th class="text-end" data-bs-toggle="tooltip" title="Line amount minus discount, before tax">Total Sale</th>
                        <th class="text-end" data-bs-toggle="tooltip" title="Cost rate × carat (or × pieces when not weighed)">Total Cost</th>
                        <th class="text-end" data-bs-toggle="tooltip" title="Total Sale − Total Cost">Profit</th>
                        <th class="text-end" data-bs-toggle="tooltip" title="Running total of Total Sale down the list">Total Sale (Σ)</th>
                        <th class="text-end" data-bs-toggle="tooltip" title="Running total of Profit down the list">Total Profit (Σ)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $r)
                        <tr data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
                            data-bs-custom-class="sales-report-tip" data-bs-title="{{ $tip($r) }}">
                            <td class="text-nowrap">{{ optional($r->date)->format('d M Y') }}</td>
                            <td>{{ $r->customer }}</td>
                            <td>
                                <div class="fw-semibold">{{ $r->stone }}</div>
                                @if ($r->name !== $r->stone)<div class="small text-muted">{{ $r->name }}</div>@endif
                            </td>
                            <td><code class="small">{{ $r->lot ?? '—' }}</code></td>
                            <td class="text-nowrap">{{ $r->invoice }}</td>
                            <td class="text-end">{{ $ct($r->carat) }}</td>
                            <td class="text-end">{{ number_format($r->pcs) }}</td>
                            <td class="text-end">{{ $r->cost_per_ct !== null ? $m($r->cost_per_ct) : '—' }}</td>
                            <td class="text-end">{{ $r->sold_per_ct !== null ? $m($r->sold_per_ct) : '—' }}</td>
                            <td class="text-end">{{ $m($r->total_sale) }}</td>
                            <td class="text-end">{{ $m($r->total_cost) }}</td>
                            <td class="text-end fw-semibold {{ $r->profit >= 0 ? 'text-success' : 'text-danger' }}">{{ $m($r->profit) }}</td>
                            <td class="text-end text-muted">{{ $m($r->run_sale) }}</td>
                            <td class="text-end text-muted">{{ $m($r->run_profit) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="14" class="text-center text-muted py-5">No sales match these filters.</td></tr>
                    @endforelse
                </tbody>
                @if ($rows->isNotEmpty())
                <tfoot class="fw-semibold bg-light">
                    <tr>
                        <td colspan="5" class="text-end">Total</td>
                        <td class="text-end">{{ $ct($totals['carat']) }}</td>
                        <td class="text-end">{{ number_format($totals['pcs']) }}</td>
                        <td></td><td></td>
                        <td class="text-end">{{ $m($totals['sale']) }}</td>
                        <td class="text-end">{{ $m($totals['cost']) }}</td>
                        <td class="text-end {{ $totals['profit'] >= 0 ? 'text-success' : 'text-danger' }}">{{ $m($totals['profit']) }}</td>
                        <td></td><td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .sales-report-page .card { border-radius: 10px; box-shadow: none; border: 1px solid #e2e8f0; }
    .sales-report-table tbody tr { cursor: default; }
    .sales-report-table th, .sales-report-table td { white-space: nowrap; }
    .sales-report-tip .tooltip-inner { max-width: 340px; padding: 10px 12px; font-size: .78rem; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.bootstrap) {
        document.querySelectorAll('.sales-report-page [data-bs-toggle="tooltip"]').forEach(function (el) {
            new bootstrap.Tooltip(el, { trigger: 'hover', container: 'body' });
        });
    }
    if (window.jQuery && jQuery.fn.select2) {
        jQuery('.sales-report-page .js-select2').select2({ width: '100%' });
    }
});
</script>
@endpush
