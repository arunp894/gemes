@extends('layout.app')

@section('title', "Today's Performance")

@section('content')

<div class="container-fluid today-performance-page">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Today's Performance</h4>
            <small class="text-muted">{{ now()->format('l, d F Y') }}</small>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Reports</a></li>
                <li class="breadcrumb-item active">Today's Performance</li>
            </ol>
        </div>
    </div>

    {{-- ── KPI row ─────────────────────────────────────────── --}}
    <div class="row g-3 mb-1">
        @permission('sales.view')
        <div class="col-6 col-xl-3">
            <div class="tp-card tp-card-success">
                <div class="tp-card-icon"><i class="ti ti-receipt"></i></div>
                <div class="tp-card-value">₹{{ number_format($salesToday->revenue) }}</div>
                <div class="tp-card-label">Sales Today</div>
                <div class="tp-card-sub">{{ $salesToday->count }} invoice{{ $salesToday->count == 1 ? '' : 's' }}</div>
            </div>
        </div>
        @endpermission

        @permission('purchases.view')
        <div class="col-6 col-xl-3">
            <div class="tp-card tp-card-primary">
                <div class="tp-card-icon"><i class="ti ti-shopping-cart"></i></div>
                <div class="tp-card-value">₹{{ number_format($purchasesToday->spend) }}</div>
                <div class="tp-card-label">Purchases Today</div>
                <div class="tp-card-sub">{{ $purchasesToday->count }} order{{ $purchasesToday->count == 1 ? '' : 's' }}</div>
            </div>
        </div>
        @endpermission

        @permission('stock.view')
        <div class="col-6 col-xl-3">
            <div class="tp-card tp-card-warning">
                <div class="tp-card-icon"><i class="ti ti-stack-2"></i></div>
                <div class="tp-card-value">
                    <span class="text-success">+{{ $stockInQty }}</span>
                    <span class="text-muted mx-1">/</span>
                    <span class="text-danger">-{{ $stockOutQty }}</span>
                </div>
                <div class="tp-card-label">Stock Movements Today</div>
                <div class="tp-card-sub">pieces in / out</div>
            </div>
        </div>
        @endpermission

        @permission('customers.view')
        <div class="col-6 col-xl-3">
            <div class="tp-card tp-card-info">
                <div class="tp-card-icon"><i class="ti ti-user-plus"></i></div>
                <div class="tp-card-value">{{ $newCustomersToday }}</div>
                <div class="tp-card-label">New Customers Today</div>
                <div class="tp-card-sub">first-time sign-ups</div>
            </div>
        </div>
        @endpermission

        @permission('purchases.view')
        <div class="col-6 col-xl-3">
            <div class="tp-card tp-card-purple">
                <div class="tp-card-icon"><i class="ti ti-cash-banknote"></i></div>
                <div class="tp-card-value">₹{{ number_format($payoutToday->amount) }}</div>
                <div class="tp-card-label">Payout Today</div>
                <div class="tp-card-sub">{{ $payoutToday->count }} payment{{ $payoutToday->count == 1 ? '' : 's' }} to suppliers</div>
            </div>
        </div>
        @endpermission

        @permission('sales.view')
        <div class="col-6 col-xl-3">
            <div class="tp-card tp-card-teal">
                <div class="tp-card-icon"><i class="ti ti-world"></i></div>
                <div class="tp-card-value">₹{{ number_format($websitePaymentsToday->amount) }}</div>
                <div class="tp-card-label">Website Payments Today</div>
                <div class="tp-card-sub">{{ $websitePaymentsToday->count }} payment{{ $websitePaymentsToday->count == 1 ? '' : 's' }} via storefront</div>
            </div>
        </div>
        @endpermission
    </div>

    <div class="row g-3">

        {{-- ── Hourly activity chart ───────────────────────── --}}
        @permission('sales.view|purchases.view')
        <div class="col-xl-8">
            <div class="tp-panel h-100">
                <div class="tp-panel-header">
                    <h5 class="tp-panel-title"><i class="ti ti-chart-area me-2 text-primary"></i>Today's Activity by Hour</h5>
                </div>
                <div class="tp-panel-body">
                    <div id="hourlyActivityChart"></div>
                </div>
            </div>
        </div>
        @endpermission

        {{-- ── Supplier-wise payment today ─────────────────── --}}
        @permission('purchases.view')
        <div class="col-xl-4">
            <div class="tp-panel h-100">
                <div class="tp-panel-header">
                    <h5 class="tp-panel-title"><i class="ti ti-building-store me-2 text-purple"></i>Supplier Payments Today</h5>
                </div>
                <div class="tp-panel-body p-0">
                    @if ($supplierPaymentsToday->isEmpty())
                        <div class="text-center text-muted py-5">
                            <i class="ti ti-cash-banknote-off d-block fs-2xl mb-2"></i>
                            No supplier payments recorded yet today.
                        </div>
                    @else
                        <ul class="tp-rank-list">
                            @foreach ($supplierPaymentsToday as $sp)
                                <li>
                                    <div class="tp-rank-body">
                                        <div class="tp-rank-title">{{ $sp->company_name ?: $sp->name }}</div>
                                        <div class="tp-rank-sub">{{ $sp->count }} payment{{ $sp->count == 1 ? '' : 's' }}</div>
                                    </div>
                                    <div class="tp-rank-amount">₹{{ number_format($sp->amount) }}</div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
        @endpermission

    </div>

    <div class="row g-3">

        {{-- ── Top products today ──────────────────────────── --}}
        @permission('sales.view')
        <div class="col-xl-5">
            <div class="tp-panel h-100">
                <div class="tp-panel-header">
                    <h5 class="tp-panel-title"><i class="ti ti-trophy me-2 text-warning"></i>Top Products Today</h5>
                </div>
                <div class="tp-panel-body p-0">
                    @if ($topProductsToday->isEmpty())
                        <div class="text-center text-muted py-5">
                            <i class="ti ti-diamond d-block fs-2xl mb-2"></i>
                            No products sold yet today.
                        </div>
                    @else
                        <ul class="tp-rank-list">
                            @foreach ($topProductsToday as $i => $p)
                                <li>
                                    <span class="tp-rank-num">{{ $i + 1 }}</span>
                                    <div class="tp-rank-body">
                                        <div class="tp-rank-title">{{ $p->title }}</div>
                                        <div class="tp-rank-sub">{{ $p->sku }} &middot; {{ (int) $p->qty_sold }} sold</div>
                                    </div>
                                    <div class="tp-rank-amount">₹{{ number_format($p->revenue) }}</div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
        @endpermission

        {{-- ── Stock movement donut ────────────────────────── --}}
        @permission('stock.view')
        <div class="col-xl-3">
            <div class="tp-panel h-100">
                <div class="tp-panel-header">
                    <h5 class="tp-panel-title"><i class="ti ti-arrows-exchange me-2 text-info"></i>Stock Movement</h5>
                </div>
                <div class="tp-panel-body d-flex align-items-center justify-content-center">
                    @if ($stockInQty + $stockOutQty > 0)
                        <div id="stockMovementChart" class="w-100"></div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="ti ti-inbox d-block fs-2xl mb-2"></i>
                            No stock movement yet today.
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endpermission

        {{-- ── Recent activity feed ────────────────────────── --}}
        @permission('sales.view|purchases.view|stock-audits.view')
        <div class="col-xl-4">
            <div class="tp-panel h-100">
                <div class="tp-panel-header">
                    <h5 class="tp-panel-title"><i class="ti ti-activity me-2 text-primary"></i>Recent Activity Today</h5>
                </div>
                <div class="tp-panel-body p-0" style="max-height: 360px; overflow-y: auto;">
                    @if ($activity->isEmpty())
                        <div class="text-center text-muted py-5">
                            <i class="ti ti-clock d-block fs-2xl mb-2"></i>
                            Nothing recorded yet today.
                        </div>
                    @else
                        <ul class="tp-activity-list">
                            @foreach ($activity as $item)
                                <li>
                                    <a href="{{ $item['url'] }}" class="tp-activity-row">
                                        <span class="tp-activity-icon tp-activity-icon-{{ $item['color'] }}">
                                            <i class="ti {{ $item['icon'] }}"></i>
                                        </span>
                                        <div class="tp-activity-body">
                                            <div class="tp-activity-title">{{ $item['title'] }}</div>
                                            <div class="tp-activity-meta">{{ $item['meta'] }}</div>
                                        </div>
                                        <div class="text-end">
                                            @if ($item['amount'] !== null)
                                                <div class="tp-activity-amount">₹{{ number_format($item['amount']) }}</div>
                                            @endif
                                            <div class="tp-activity-time">{{ $item['time']->format('h:i A') }}</div>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
        @endpermission

    </div>

</div>

@endsection

@push('styles')
<style>
    /* ==========================================================
       Today's Performance report — scoped under .today-performance-page
       so nothing here leaks into other pages.
       ========================================================== */
    .today-performance-page {
        --tp-primary: #1d4ed8;
        --tp-primary-dark: #1e3a8a;
        --tp-success: #059669;
        --tp-warning: #d97706;
        --tp-info: #0891b2;
        --tp-danger: #dc2626;
        --tp-purple: #9333ea;
        --tp-teal: #0d9488;
        --tp-border: #e2e8f0;
        --tp-text: #1e293b;
        --tp-text-muted: #64748b;
        padding-top: 0;
        padding-bottom: 20px;
    }
    .today-performance-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid var(--tp-border);
    }
    .today-performance-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        position: relative;
        padding-left: 12px;
    }
    .today-performance-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0; top: 2px; bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, var(--tp-primary-dark), var(--tp-primary));
    }
    .today-performance-page .breadcrumb { font-size: 0.75rem; }

    /* KPI cards */
    .tp-card {
        background: #fff; border: 1px solid var(--tp-border); border-radius: 12px;
        padding: 18px; margin-bottom: 16px; position: relative; overflow: hidden;
    }
    .tp-card-icon {
        width: 38px; height: 38px; border-radius: 10px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.1rem; margin-bottom: 10px;
    }
    .tp-card-success .tp-card-icon { background: #ecfdf5; color: var(--tp-success); }
    .tp-card-primary .tp-card-icon { background: #eff6ff; color: var(--tp-primary); }
    .tp-card-warning .tp-card-icon { background: #fffbeb; color: var(--tp-warning); }
    .tp-card-info    .tp-card-icon { background: #ecfeff; color: var(--tp-info); }
    .tp-card-purple  .tp-card-icon { background: #f5f3ff; color: var(--tp-purple); }
    .tp-card-teal    .tp-card-icon { background: #f0fdfa; color: var(--tp-teal); }
    .text-purple { color: var(--tp-purple) !important; }
    .tp-card-value { font-size: 1.5rem; font-weight: 800; color: var(--tp-text); line-height: 1.15; }
    .tp-card-label { font-size: 0.8125rem; font-weight: 600; color: var(--tp-text); margin-top: 4px; }
    .tp-card-sub { font-size: 0.75rem; color: var(--tp-text-muted); margin-top: 2px; }

    /* Panels */
    .tp-panel { background: #fff; border: 1px solid var(--tp-border); border-radius: 12px; margin-bottom: 16px; }
    .tp-panel-header { padding: 12px 16px; border-bottom: 1px solid var(--tp-border); }
    .tp-panel-title { font-size: 0.9375rem; font-weight: 700; margin: 0; color: var(--tp-text); }
    .tp-panel-body { padding: 16px; min-height: 120px; }

    /* Top products rank list */
    .tp-rank-list { list-style: none; margin: 0; padding: 0; }
    .tp-rank-list li { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-bottom: 1px solid #f1f5f9; }
    .tp-rank-list li:last-child { border-bottom: none; }
    .tp-rank-num {
        flex-shrink: 0; width: 26px; height: 26px; border-radius: 50%;
        background: #f1f5f9; color: var(--tp-text-muted); font-size: 0.75rem; font-weight: 700;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .tp-rank-list li:first-child .tp-rank-num { background: #fef3c7; color: #b45309; }
    .tp-rank-body { flex-grow: 1; min-width: 0; }
    .tp-rank-title { font-size: 0.875rem; font-weight: 600; color: var(--tp-text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tp-rank-sub { font-size: 0.75rem; color: var(--tp-text-muted); }
    .tp-rank-amount { font-weight: 700; font-size: 0.875rem; color: var(--tp-text); flex-shrink: 0; }

    /* Activity feed */
    .tp-activity-list { list-style: none; margin: 0; padding: 0; }
    .tp-activity-row {
        display: flex; align-items: center; gap: 12px; padding: 10px 16px;
        text-decoration: none; color: inherit; border-bottom: 1px solid #f1f5f9;
        transition: background .15s ease;
    }
    .tp-activity-row:hover { background: #f8fafc; }
    .tp-activity-icon {
        flex-shrink: 0; width: 32px; height: 32px; border-radius: 9px;
        display: inline-flex; align-items: center; justify-content: center; font-size: 0.9rem;
    }
    .tp-activity-icon-success { background: #ecfdf5; color: var(--tp-success); }
    .tp-activity-icon-primary { background: #eff6ff; color: var(--tp-primary); }
    .tp-activity-icon-warning { background: #fffbeb; color: var(--tp-warning); }
    .tp-activity-icon-info    { background: #ecfeff; color: var(--tp-info); }
    .tp-activity-body { flex-grow: 1; min-width: 0; }
    .tp-activity-title { font-size: 0.8125rem; font-weight: 700; color: var(--tp-text); }
    .tp-activity-meta { font-size: 0.75rem; color: var(--tp-text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .tp-activity-amount { font-size: 0.8125rem; font-weight: 700; color: var(--tp-text); }
    .tp-activity-time { font-size: 0.6875rem; color: var(--tp-text-muted); }
</style>
@endpush

@push('scripts')
<script>
(function () {
    'use strict';

    var isDark  = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    var gridCol = isDark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.07)';
    var textCol = isDark ? '#adb5bd' : '#6c757d';

    var hourLabels = @json(array_map(fn($h) => \Carbon\Carbon::createFromTime($h)->format('g A'), $hours));

    @if($canSales || $canPurchases)
    var hourlyEl = document.querySelector('#hourlyActivityChart');
    if (hourlyEl) {
        var hourlySeries = [];
        @if($canSales)
            hourlySeries.push({ name: 'Sales', data: @json($salesByHour) });
        @endif
        @if($canPurchases)
            hourlySeries.push({ name: 'Purchases', data: @json($purchasesByHour) });
        @endif

        new ApexCharts(hourlyEl, {
            series: hourlySeries,
            chart: { type: 'area', height: 300, toolbar: { show: false }, zoom: { enabled: false }, fontFamily: 'inherit' },
            colors: ['#0acf97', '#3d7cc9'],
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 90, 100] } },
            stroke: { curve: 'smooth', width: 2 },
            dataLabels: { enabled: false },
            xaxis: { categories: hourLabels, axisBorder: { show: false }, axisTicks: { show: false }, labels: { style: { colors: textCol, fontSize: '11px' }, rotate: -45 } },
            yaxis: { labels: { formatter: function (v) { if (v >= 100000) return '₹' + (v/100000).toFixed(1) + 'L'; if (v >= 1000) return '₹' + (v/1000).toFixed(0) + 'k'; return '₹' + v; }, style: { colors: textCol, fontSize: '11px' } } },
            grid: { borderColor: gridCol, strokeDashArray: 4 },
            tooltip: { y: { formatter: function (v) { return '₹' + new Intl.NumberFormat('en-IN').format(v); } } },
            legend: { position: 'top', horizontalAlign: 'right', labels: { colors: textCol } },
        }).render();
    }
    @endif

    @if($canStock)
    var smEl = document.querySelector('#stockMovementChart');
    if (smEl) {
        new ApexCharts(smEl, {
            series: [{{ $stockInQty }}, {{ $stockOutQty }}],
            labels: ['In', 'Out'],
            chart: { type: 'donut', height: 220, fontFamily: 'inherit' },
            colors: ['#0acf97', '#fa5c7c'],
            dataLabels: { enabled: false },
            legend: { position: 'bottom', labels: { colors: textCol } },
        }).render();
    }
    @endif

})();
</script>
@endpush
