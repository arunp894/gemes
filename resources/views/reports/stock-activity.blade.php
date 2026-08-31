@extends('layout.app')

@section('title', 'Stock Activity Report')

@section('content')

<div class="container-fluid sar-page">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Stock Activity Report</h4>
            <small class="text-muted">{{ $rangeLabel }}</small>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Reports</a></li>
                <li class="breadcrumb-item active">Stock Activity</li>
            </ol>
        </div>
    </div>

    {{-- ── Period switcher + date navigation ─────────────────── --}}
    <div class="sar-toolbar mb-3">
        <div class="sar-pills">
            @foreach (['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'halfyearly' => 'Half-Yearly', 'yearly' => 'Yearly'] as $key => $label)
                <a href="{{ route('reports.stock-activity', ['period' => $key, 'date' => $anchor->toDateString()]) }}"
                   class="sar-pill {{ $period === $key ? 'active' : '' }}">{{ $label }}</a>
            @endforeach
            <a href="{{ route('reports.stock-activity', ['period' => 'custom', 'from' => $customFrom, 'to' => $customTo]) }}"
               class="sar-pill {{ $period === 'custom' ? 'active' : '' }}"><i class="ti ti-calendar-due me-1"></i>Custom</a>
        </div>

        @if ($period !== 'custom')
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('reports.stock-activity', ['period' => $period, 'date' => $prevDate]) }}"
                   class="sar-nav-btn" title="Previous {{ ['daily' => 'day', 'weekly' => 'week', 'monthly' => 'month', 'quarterly' => 'quarter', 'halfyearly' => 'half-year', 'yearly' => 'year'][$period] }}">
                    <i class="ti ti-chevron-left"></i>
                </a>
                <form method="GET" action="{{ route('reports.stock-activity') }}" id="sarDateForm">
                    <input type="hidden" name="period" value="{{ $period }}">
                    <input type="date" name="date" class="form-control form-control-sm" style="width: 155px;"
                           value="{{ $anchor->toDateString() }}" onchange="document.getElementById('sarDateForm').submit()">
                </form>
                <a href="{{ route('reports.stock-activity', ['period' => $period, 'date' => $nextDate]) }}"
                   class="sar-nav-btn" title="Next {{ ['daily' => 'day', 'weekly' => 'week', 'monthly' => 'month', 'quarterly' => 'quarter', 'halfyearly' => 'half-year', 'yearly' => 'year'][$period] }}">
                    <i class="ti ti-chevron-right"></i>
                </a>
                @if ($anchor->toDateString() !== $todayDate)
                    <a href="{{ route('reports.stock-activity', ['period' => $period, 'date' => $todayDate]) }}" class="sar-today-btn">Today</a>
                @endif
            </div>
        @else
            <form method="GET" action="{{ route('reports.stock-activity') }}" class="d-flex align-items-center gap-2">
                <input type="hidden" name="period" value="custom">
                <span class="text-muted small">From</span>
                <input type="date" name="from" class="form-control form-control-sm" style="width: 155px;" value="{{ $customFrom }}" required>
                <span class="text-muted small">To</span>
                <input type="date" name="to" class="form-control form-control-sm" style="width: 155px;" value="{{ $customTo }}" required>
                <button type="submit" class="sar-today-btn border-0"><i class="ti ti-filter me-1"></i>Apply</button>
            </form>
        @endif
    </div>

    @php
        $fmtCarat = fn ($v) => rtrim(rtrim(number_format((float) $v, 3), '0'), '.') ?: '0';
    @endphp

    {{-- ── KPI row ─────────────────────────────────────────── --}}
    <div class="row g-3 mb-1">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="sar-card sar-card-success">
                <div class="sar-card-icon"><i class="ti ti-arrow-down-circle"></i></div>
                <div class="sar-card-value">{{ number_format($stockInQty) }}</div>
                <div class="sar-card-label">Stock In</div>
                <div class="sar-card-sub">pieces · {{ $fmtCarat($stockInCarat) }} ct</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="sar-card sar-card-danger">
                <div class="sar-card-icon"><i class="ti ti-arrow-up-circle"></i></div>
                <div class="sar-card-value">{{ number_format($stockOutQty) }}</div>
                <div class="sar-card-label">Stock Out</div>
                <div class="sar-card-sub">pieces · {{ $fmtCarat($stockOutCarat) }} ct</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="sar-card {{ $netChangeQty >= 0 ? 'sar-card-primary' : 'sar-card-warning' }}">
                <div class="sar-card-icon"><i class="ti ti-arrows-exchange"></i></div>
                <div class="sar-card-value">{{ $netChangeQty >= 0 ? '+' : '' }}{{ number_format($netChangeQty) }}</div>
                <div class="sar-card-label">Net Change</div>
                <div class="sar-card-sub">{{ $netChangeCarat >= 0 ? '+' : '' }}{{ $fmtCarat($netChangeCarat) }} ct</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="sar-card sar-card-info">
                <div class="sar-card-icon"><i class="ti ti-transfer"></i></div>
                <div class="sar-card-value">{{ number_format($transfersCount) }}</div>
                <div class="sar-card-label">Transfers</div>
                <div class="sar-card-sub">records this period</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="sar-card sar-card-teal">
                <div class="sar-card-icon"><i class="ti ti-world"></i></div>
                <div class="sar-card-value">{{ number_format($websiteOrdersCount) }}</div>
                <div class="sar-card-label">Website Orders</div>
                <div class="sar-card-sub">via storefront</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="sar-card sar-card-purple">
                <div class="sar-card-icon"><i class="ti ti-users"></i></div>
                <div class="sar-card-value">{{ number_format($activeUsers) }}</div>
                <div class="sar-card-label">Active Users</div>
                <div class="sar-card-sub">recorded an action</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- ── Stock In vs Out over time ───────────────────────── --}}
        <div class="col-xl-8">
            <div class="sar-panel h-100">
                <div class="sar-panel-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="sar-panel-title mb-0"><i class="ti ti-chart-bar me-2 text-primary"></i>Stock In vs Stock Out</h5>
                    <div class="fs-sm">
                        <span class="text-success fw-semibold">{{ $fmtCarat($stockInCarat) }} ct in</span>
                        <span class="text-muted mx-1">·</span>
                        <span class="text-danger fw-semibold">{{ $fmtCarat($stockOutCarat) }} ct out</span>
                    </div>
                </div>
                <div class="sar-panel-body">
                    <div id="inOutChart"></div>
                </div>
            </div>
        </div>

        {{-- ── Movement type breakdown ──────────────────────────── --}}
        <div class="col-xl-4">
            <div class="sar-panel h-100">
                <div class="sar-panel-header">
                    <h5 class="sar-panel-title"><i class="ti ti-chart-donut me-2 text-teal"></i>Movement Types</h5>
                </div>
                <div class="sar-panel-body d-flex align-items-center justify-content-center">
                    @if (count($movementTypeLabels) > 0)
                        <div id="movementTypeChart" class="w-100"></div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="ti ti-inbox d-block fs-2xl mb-2"></i>
                            No stock movements in this period.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- ── Top selling stones by carat ──────────────────────── --}}
        <div class="col-xl-6">
            <div class="sar-panel h-100">
                <div class="sar-panel-header">
                    <h5 class="sar-panel-title"><i class="ti ti-trending-up me-2 text-danger"></i>Top Selling Stones (by Carat)</h5>
                </div>
                <div class="sar-panel-body">
                    @if ($topSellingByCarat->isNotEmpty())
                        @php $topSellMax = $topSellingByCarat->max('carat') ?: 1; @endphp
                        <ul class="sar-rank-list">
                            @foreach ($topSellingByCarat as $i => $row)
                                <li>
                                    <span class="sar-rank-num">{{ $i + 1 }}</span>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-semibold text-truncate">{{ $row->category_name }}</span>
                                            <span class="fw-semibold flex-shrink-0 ms-2">{{ $fmtCarat($row->carat) }} ct</span>
                                        </div>
                                        <div class="sar-bar-track mt-1">
                                            <div class="sar-bar-fill sar-bar-fill-danger" style="width: {{ round($row->carat / $topSellMax * 100) }}%;"></div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="ti ti-inbox d-block fs-2xl mb-2"></i>
                            No sales recorded in this period.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── Top purchased stones by carat ────────────────────── --}}
        <div class="col-xl-6">
            <div class="sar-panel h-100">
                <div class="sar-panel-header">
                    <h5 class="sar-panel-title"><i class="ti ti-trending-down me-2 text-success"></i>Top Purchased Stones (by Carat)</h5>
                </div>
                <div class="sar-panel-body">
                    @if ($topPurchasedByCarat->isNotEmpty())
                        @php $topBuyMax = $topPurchasedByCarat->max('carat') ?: 1; @endphp
                        <ul class="sar-rank-list">
                            @foreach ($topPurchasedByCarat as $i => $row)
                                <li>
                                    <span class="sar-rank-num">{{ $i + 1 }}</span>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-semibold text-truncate">{{ $row->category_name }}</span>
                                            <span class="fw-semibold flex-shrink-0 ms-2">{{ $fmtCarat($row->carat) }} ct</span>
                                        </div>
                                        <div class="sar-bar-track mt-1">
                                            <div class="sar-bar-fill sar-bar-fill-success" style="width: {{ round($row->carat / $topBuyMax * 100) }}%;"></div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="ti ti-inbox d-block fs-2xl mb-2"></i>
                            No purchases recorded in this period.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- ── Top active users ─────────────────────────────────── --}}
        <div class="col-xl-6">
            <div class="sar-panel h-100">
                <div class="sar-panel-header">
                    <h5 class="sar-panel-title"><i class="ti ti-trophy me-2 text-warning"></i>Top Active Users</h5>
                </div>
                <div class="sar-panel-body">
                    @if ($userActivity->isNotEmpty())
                        <div id="userActivityChart"></div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="ti ti-user-off d-block fs-2xl mb-2"></i>
                            No user activity recorded in this period.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── Recent transfers ─────────────────────────────────── --}}
        <div class="col-xl-6">
            <div class="sar-panel h-100">
                <div class="sar-panel-header d-flex align-items-center justify-content-between">
                    <h5 class="sar-panel-title"><i class="ti ti-transfer me-2 text-info"></i>Transfers This Period</h5>
                    <div class="d-flex gap-2 fs-xxs">
                        @foreach (['draft' => 'Draft', 'in_transit' => 'In Transit', 'received' => 'Received', 'cancelled' => 'Cancelled'] as $st => $stLabel)
                            @if (($transfersByStatus[$st] ?? 0) > 0)
                                <span class="badge badge-soft-secondary">{{ $stLabel }}: {{ $transfersByStatus[$st] }}</span>
                            @endif
                        @endforeach
                    </div>
                </div>
                <div class="sar-panel-body p-0">
                    @if ($recentTransfers->isNotEmpty())
                        <ul class="sar-list">
                            @foreach ($recentTransfers as $t)
                                <li>
                                    <span class="sar-list-icon"><i class="ti ti-arrows-exchange"></i></span>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="fw-semibold text-truncate">
                                            <a href="{{ route('stock-transfers.show', $t) }}" class="text-reset text-decoration-none">{{ $t->transfer_number }}</a>
                                        </div>
                                        <small class="text-muted">{{ $t->fromLocation?->name ?? '—' }} <i class="ti ti-arrow-right fs-xxs"></i> {{ $t->toLocation?->name ?? '—' }}</small>
                                    </div>
                                    <div class="text-end flex-shrink-0">
                                        <div class="fw-semibold">{{ $t->lines_count }} <small class="text-muted fw-normal">lines</small></div>
                                        <small class="text-muted">{{ $t->transfer_date?->format('d M') }}</small>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="ti ti-inbox d-block fs-2xl mb-2"></i>
                            No transfers in this period.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── User activity table ─────────────────────────────────── --}}
    <div class="row g-3">
        <div class="col-12">
            <div class="sar-panel">
                <div class="sar-panel-header">
                    <h5 class="sar-panel-title"><i class="ti ti-list-details me-2 text-primary"></i>User Activity Breakdown</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom table-centered align-middle mb-0">
                        <thead class="bg-light bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th>User</th>
                                <th class="text-end">Purchases</th>
                                <th class="text-end">Sales</th>
                                <th class="text-end">Transfers</th>
                                <th class="text-end">Audit Scans</th>
                                <th style="width: 30%;">Total Activity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($userActivity as $u)
                                @php $pct = $userActivity->first()->total > 0 ? round($u->total / $userActivity->first()->total * 100) : 0; @endphp
                                <tr>
                                    <td class="fw-semibold">
                                        <span class="sar-avatar">{{ strtoupper(substr($u->name, 0, 1)) }}</span>
                                        {{ $u->name }}
                                    </td>
                                    <td class="text-end">{{ $u->purchases }}</td>
                                    <td class="text-end">{{ $u->sales }}</td>
                                    <td class="text-end">{{ $u->transfers }}</td>
                                    <td class="text-end">{{ $u->scans }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="sar-bar-track flex-grow-1">
                                                <div class="sar-bar-fill" style="width: {{ $pct }}%;"></div>
                                            </div>
                                            <span class="fw-semibold small" style="min-width: 2em; text-align: right;">{{ $u->total }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-5">
                                        <i class="ti ti-inbox d-block fs-2xl mb-2"></i>
                                        No user activity recorded in this period.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('styles')
<style>
    .sar-page {
        --sar-primary: #1d4ed8;
        --sar-primary-dark: #1e3a8a;
        --sar-success: #059669;
        --sar-danger: #dc2626;
        --sar-warning: #d97706;
        --sar-info: #0891b2;
        --sar-purple: #9333ea;
        --sar-teal: #0f5e57;
        --sar-border: #e2e8f0;
        --sar-text: #1e293b;
        --sar-text-muted: #64748b;
        padding-top: 0;
        padding-bottom: 20px;
    }
    .sar-page .page-title-head {
        display: flex !important; align-items: center !important; min-height: 35px !important;
        margin-top: 0 !important; padding: 10px 0 !important; margin-bottom: 16px !important;
        border-bottom: 2px solid var(--sar-border);
    }
    .sar-page .page-main-title { font-size: 1.375rem; font-weight: 700; position: relative; padding-left: 12px; }
    .sar-page .page-main-title::before {
        content: ''; position: absolute; left: 0; top: 2px; bottom: 2px; width: 4px; border-radius: 2px;
        background: linear-gradient(180deg, var(--sar-primary-dark), var(--sar-primary));
    }
    .sar-page .breadcrumb { font-size: 0.75rem; }

    /* ---------- Toolbar ---------- */
    .sar-toolbar {
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px;
        background: #fff; border: 1px solid var(--sar-border); border-radius: 12px; padding: 10px 14px;
    }
    .sar-pills { display: flex; gap: 6px; }
    .sar-pill {
        display: inline-flex; align-items: center; border: 1px solid var(--sar-border); background: #fff;
        color: var(--sar-text-muted); border-radius: 999px; padding: 6px 16px; font-size: 0.8125rem; font-weight: 600;
        text-decoration: none; transition: all .15s ease;
    }
    .sar-pill:hover { border-color: var(--sar-primary); color: var(--sar-primary); }
    .sar-pill.active { background: var(--sar-primary); border-color: var(--sar-primary); color: #fff; }
    .sar-nav-btn {
        width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--sar-border); background: #fff;
        color: var(--sar-text); display: inline-flex; align-items: center; justify-content: center; text-decoration: none;
        transition: all .15s ease;
    }
    .sar-nav-btn:hover { border-color: var(--sar-primary); color: var(--sar-primary); }
    .sar-today-btn {
        display: inline-flex; align-items: center; background: var(--sar-primary); color: #fff; border-radius: 8px;
        padding: 6px 14px; font-size: 0.8125rem; font-weight: 600; text-decoration: none;
    }

    /* ---------- KPI cards ---------- */
    .sar-card {
        background: #fff; border: 1px solid var(--sar-border); border-radius: 12px; padding: 16px;
        height: 100%; position: relative;
    }
    .sar-card-icon {
        width: 38px; height: 38px; border-radius: 10px; display: inline-flex; align-items: center;
        justify-content: center; font-size: 1.1rem; margin-bottom: 10px;
    }
    .sar-card-success .sar-card-icon { background: #ecfdf5; color: var(--sar-success); }
    .sar-card-danger  .sar-card-icon { background: #fef2f2; color: var(--sar-danger); }
    .sar-card-primary .sar-card-icon { background: #eff6ff; color: var(--sar-primary); }
    .sar-card-warning .sar-card-icon { background: #fffbeb; color: var(--sar-warning); }
    .sar-card-info    .sar-card-icon { background: #ecfeff; color: var(--sar-info); }
    .sar-card-purple  .sar-card-icon { background: #f5f3ff; color: var(--sar-purple); }
    .sar-card-teal    .sar-card-icon { background: #f0fdfa; color: var(--sar-teal); }
    .sar-card-value { font-size: 1.5rem; font-weight: 800; color: var(--sar-text); line-height: 1.15; }
    .sar-card-label { font-size: 0.8125rem; font-weight: 600; color: var(--sar-text); margin-top: 4px; }
    .sar-card-sub { font-size: 0.75rem; color: var(--sar-text-muted); margin-top: 2px; }

    /* ---------- Panels ---------- */
    .sar-panel { background: #fff; border: 1px solid var(--sar-border); border-radius: 12px; margin-bottom: 16px; }
    .sar-panel-header { padding: 12px 16px; border-bottom: 1px solid var(--sar-border); }
    .sar-panel-title { font-size: 0.9375rem; font-weight: 700; margin: 0; color: var(--sar-text); }
    .sar-panel-body { padding: 16px; min-height: 120px; }

    /* ---------- Transfers list ---------- */
    .sar-list { list-style: none; margin: 0; padding: 0; }
    .sar-list li { display: flex; align-items: center; gap: 10px; padding: 10px 16px; border-bottom: 1px solid #f1f5f9; }
    .sar-list li:last-child { border-bottom: none; }
    .sar-list-icon {
        width: 32px; height: 32px; border-radius: 50%; background: #eff6ff; color: var(--sar-primary);
        display: inline-flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0;
    }

    /* ---------- User activity table ---------- */
    .sar-avatar {
        display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px;
        border-radius: 50%; background: var(--sar-primary); color: #fff; font-size: 0.75rem; font-weight: 700;
        margin-right: 8px;
    }
    .sar-bar-track { height: 8px; border-radius: 999px; background: #eef0f3; overflow: hidden; }
    .sar-bar-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg, var(--sar-primary), var(--sar-teal)); }
    .sar-bar-fill-danger  { background: linear-gradient(90deg, #f87171, var(--sar-danger)); }
    .sar-bar-fill-success { background: linear-gradient(90deg, #6ee7b7, var(--sar-success)); }

    /* ---------- Top stones by carat rank lists ---------- */
    .sar-rank-list { list-style: none; margin: 0; padding: 0; }
    .sar-rank-list li { display: flex; align-items: center; gap: 12px; padding: 9px 0; }
    .sar-rank-list li:not(:last-child) { border-bottom: 1px dashed #f1f5f9; }
    .sar-rank-num {
        display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px;
        border-radius: 50%; background: #f1f5f9; color: var(--sar-text-muted); font-size: 0.75rem; font-weight: 700;
        flex-shrink: 0;
    }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const bucketLabels       = @json($bucketLabels);
    const seriesIn           = @json($seriesIn);
    const seriesOut          = @json($seriesOut);
    const seriesInCarat      = @json($seriesInCarat);
    const seriesOutCarat     = @json($seriesOutCarat);
    const movementTypeLabels = @json($movementTypeLabels);
    const movementTypeQty    = @json($movementTypeQty);
    const movementTypeCarat  = @json($movementTypeCarat);
    const userNames          = @json($userActivity->pluck('name'));
    const userTotals         = @json($userActivity->pluck('total'));

    const fmtCarat = (v) => {
        const n = Number(v) || 0;
        return (Math.round(n * 1000) / 1000).toString().replace(/\.?0+$/, '') || '0';
    };

    // ── Stock In vs Out (in above axis, out below) ──────────────
    new ApexCharts(document.querySelector('#inOutChart'), {
        chart: { type: 'bar', height: 320, stacked: true, toolbar: { show: false }, fontFamily: 'inherit' },
        series: [
            { name: 'Stock In',  data: seriesIn },
            { name: 'Stock Out', data: seriesOut },
        ],
        colors: ['#059669', '#dc2626'],
        plotOptions: { bar: { columnWidth: '55%', borderRadius: 3 } },
        dataLabels: { enabled: false },
        xaxis: { categories: bucketLabels, labels: { rotate: 0, style: { fontSize: '11px' } } },
        yaxis: { labels: { formatter: (v) => Math.abs(v).toLocaleString() } },
        tooltip: {
            y: {
                formatter: (v, { seriesIndex, dataPointIndex }) => {
                    const carat = seriesIndex === 0 ? seriesInCarat[dataPointIndex] : seriesOutCarat[dataPointIndex];
                    return Math.abs(v).toLocaleString() + ' pcs · ' + fmtCarat(carat) + ' ct';
                },
            },
        },
        legend: { position: 'top', horizontalAlign: 'right' },
        grid: { borderColor: '#f1f5f9' },
    }).render();

    // ── Movement type donut ──────────────────────────────────────
    if (movementTypeLabels.length > 0) {
        new ApexCharts(document.querySelector('#movementTypeChart'), {
            chart: { type: 'donut', height: 280, fontFamily: 'inherit' },
            series: movementTypeQty,
            labels: movementTypeLabels,
            colors: ['#059669', '#9333ea', '#0891b2', '#16a34a', '#d97706'],
            dataLabels: { enabled: true, formatter: (v) => Math.round(v) + '%' },
            legend: {
                position: 'bottom',
                formatter: (seriesName, opts) => seriesName + ' — ' + fmtCarat(movementTypeCarat[opts.seriesIndex]) + ' ct',
            },
            tooltip: {
                y: {
                    formatter: (v, { seriesIndex }) => v.toLocaleString() + ' pcs · ' + fmtCarat(movementTypeCarat[seriesIndex]) + ' ct',
                },
            },
        }).render();
    }

    // ── Top active users horizontal bar ──────────────────────────
    if (userNames.length > 0) {
        new ApexCharts(document.querySelector('#userActivityChart'), {
            chart: { type: 'bar', height: Math.max(180, userNames.length * 42), toolbar: { show: false }, fontFamily: 'inherit' },
            series: [{ name: 'Actions', data: userTotals }],
            colors: ['#1d4ed8'],
            plotOptions: { bar: { horizontal: true, borderRadius: 3, barHeight: '55%' } },
            dataLabels: { enabled: true },
            xaxis: { categories: userNames },
            grid: { borderColor: '#f1f5f9' },
        }).render();
    }
});
</script>
@endpush
