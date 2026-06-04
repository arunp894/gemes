@extends('layout.app')

@section('content')
<div class="container-fluid">

    {{-- ── Page title ─────────────────────────────────────── --}}
    <div class="page-title-head d-flex align-items-center mb-3">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Dashboard</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="#">Sukina Gems</a></li>
                <li class="breadcrumb-item active">Dashboard</li>
            </ol>
        </div>
    </div>

    {{-- ── Row 1: KPI stat cards ───────────────────────────── --}}
    <div class="row g-3 mb-3">

        {{-- Sales Revenue --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted fs-base text-uppercase fw-semibold mb-1">Sales This Month</p>
                            <h3 class="fw-bold mb-1">₹{{ number_format($salesThisMonth->revenue, 0) }}</h3>
                            <p class="mb-0 text-muted fs-sm">
                                @if($salesRevenueChange >= 0)
                                    <span class="text-success me-1"><i class="ti ti-arrow-up"></i> {{ abs($salesRevenueChange) }}%</span>
                                @else
                                    <span class="text-danger me-1"><i class="ti ti-arrow-down"></i> {{ abs($salesRevenueChange) }}%</span>
                                @endif
                                vs last month
                            </p>
                        </div>
                        <div class="avatar-md flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded-circle fs-22">
                                <i class="ti ti-cash text-success"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-top border-dashed d-flex gap-3">
                        <span class="text-muted fs-sm"><i class="ti ti-receipt me-1"></i>{{ $salesThisMonth->count }} invoices</span>
                        <a href="{{ route('sales.index') }}" class="ms-auto text-primary fs-sm fw-semibold">View all &rarr;</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Purchase Spend --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted fs-base text-uppercase fw-semibold mb-1">Purchases This Month</p>
                            <h3 class="fw-bold mb-1">₹{{ number_format($purchasesThisMonth->spend, 0) }}</h3>
                            <p class="mb-0 text-muted fs-sm">
                                @if($purchaseSpendChange >= 0)
                                    <span class="text-danger me-1"><i class="ti ti-arrow-up"></i> {{ abs($purchaseSpendChange) }}%</span>
                                @else
                                    <span class="text-success me-1"><i class="ti ti-arrow-down"></i> {{ abs($purchaseSpendChange) }}%</span>
                                @endif
                                vs last month
                            </p>
                        </div>
                        <div class="avatar-md flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle rounded-circle fs-22">
                                <i class="ti ti-truck-delivery text-primary"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-top border-dashed d-flex gap-3">
                        <span class="text-muted fs-sm"><i class="ti ti-file-invoice me-1"></i>{{ $purchasesThisMonth->count }} invoices</span>
                        <a href="{{ route('purchases.index') }}" class="ms-auto text-primary fs-sm fw-semibold">View all &rarr;</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Products --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted fs-base text-uppercase fw-semibold mb-1">Products</p>
                            <h3 class="fw-bold mb-1">{{ number_format($totalProducts) }}</h3>
                            <p class="mb-0 text-muted fs-sm">
                                <span class="text-success me-1"><i class="ti ti-circle-check"></i> {{ $activeProducts }} active</span>
                            </p>
                        </div>
                        <div class="avatar-md flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded-circle fs-22">
                                <i class="ti ti-diamond text-warning"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-top border-dashed d-flex">
                        <span class="text-muted fs-sm"><i class="ti ti-box me-1"></i>{{ $inStockCount }} in stock</span>
                        <a href="{{ route('products.index') }}" class="ms-auto text-primary fs-sm fw-semibold">View all &rarr;</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Customers --}}
        <div class="col-xl-3 col-md-6">
            <div class="card card-h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted fs-base text-uppercase fw-semibold mb-1">Customers</p>
                            <h3 class="fw-bold mb-1">{{ number_format($totalCustomers) }}</h3>
                            <p class="mb-0 text-muted fs-sm">
                                <span class="text-success me-1"><i class="ti ti-user-check"></i> {{ $activeCustomers }} active</span>
                            </p>
                        </div>
                        <div class="avatar-md flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded-circle fs-22">
                                <i class="ti ti-users text-info"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-top border-dashed d-flex">
                        <span class="text-muted fs-sm"><i class="ti ti-building-store me-1"></i>{{ $totalSuppliers }} suppliers</span>
                        <a href="{{ route('customers.index') }}" class="ms-auto text-primary fs-sm fw-semibold">View all &rarr;</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- end KPI row --}}

    {{-- ── Row 2: Charts ───────────────────────────────────── --}}
    <div class="row g-3 mb-3">

        {{-- 12-Month Sales vs Purchases Trend --}}
        <div class="col-xl-8">
            <div class="card card-h-100">
                <div class="card-header justify-content-between">
                    <h4 class="card-title">Sales vs Purchases — Last 12 Months</h4>
                    <div class="d-flex gap-2">
                        <span class="badge bg-success-subtle text-success px-2 py-1 rounded-pill fs-12">
                            <i class="ti ti-circle-filled me-1" style="font-size:8px"></i> Sales
                        </span>
                        <span class="badge bg-primary-subtle text-primary px-2 py-1 rounded-pill fs-12">
                            <i class="ti ti-circle-filled me-1" style="font-size:8px"></i> Purchases
                        </span>
                    </div>
                </div>
                <div class="card-body pb-2">
                    <div id="dashboard-trend-chart" class="apex-charts" style="min-height:280px"></div>
                </div>
            </div>
        </div>

        {{-- Today Summary --}}
        <div class="col-xl-4">
            <div class="card card-h-100">
                <div class="card-header">
                    <h4 class="card-title">Today's Summary</h4>
                </div>
                <div class="card-body">
                    <div id="dashboard-today-chart" class="apex-charts" style="min-height:180px"></div>

                    <div class="mt-3">
                        <div class="d-flex align-items-center justify-content-between py-2 border-bottom border-dashed">
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar-sm avatar-title bg-success-subtle rounded-circle">
                                    <i class="ti ti-cash text-success"></i>
                                </span>
                                <span class="fw-semibold">Sales Today</span>
                            </div>
                            <span class="badge bg-success-subtle text-success fs-sm px-2">{{ $todaySalesCount }} invoices</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between py-2 border-bottom border-dashed">
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar-sm avatar-title bg-primary-subtle rounded-circle">
                                    <i class="ti ti-truck-delivery text-primary"></i>
                                </span>
                                <span class="fw-semibold">Purchases Today</span>
                            </div>
                            <span class="badge bg-primary-subtle text-primary fs-sm px-2">{{ $todayPurchaseCount }} invoices</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between py-2 border-bottom border-dashed">
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar-sm avatar-title bg-warning-subtle rounded-circle">
                                    <i class="ti ti-diamond text-warning"></i>
                                </span>
                                <span class="fw-semibold">In Stock (pieces)</span>
                            </div>
                            <span class="badge bg-warning-subtle text-warning fs-sm px-2">{{ number_format($inStockCount) }}</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between py-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar-sm avatar-title bg-info-subtle rounded-circle">
                                    <i class="ti ti-building-warehouse text-info"></i>
                                </span>
                                <span class="fw-semibold">Active Suppliers</span>
                            </div>
                            <span class="badge bg-info-subtle text-info fs-sm px-2">{{ $activeSuppliers }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- end charts row --}}

    {{-- ── Row 3: Recent Sales + Recent Purchases ──────────── --}}
    <div class="row g-3 mb-3">

        {{-- Recent Sales --}}
        <div class="col-xl-7">
            <div class="card">
                <div class="card-header justify-content-between">
                    <h4 class="card-title">Recent Sales</h4>
                    <a href="{{ route('sales.create') }}" class="btn btn-sm btn-primary">
                        <i class="ti ti-plus me-1"></i> New Sale
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom table-centered table-hover w-100 mb-0">
                            <thead class="bg-light bg-opacity-25 thead-sm">
                                <tr class="text-uppercase fs-xxs">
                                    <th>Invoice</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody class="text-nowrap">
                                @forelse($recentSales as $sale)
                                <tr>
                                    <td>
                                        <a href="{{ route('sales.show', $sale) }}" class="fw-semibold text-reset">
                                            {{ $sale->sale_number }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="fs-sm">{{ $sale->customer?->display_name ?? 'Walk-in' }}</span>
                                    </td>
                                    <td class="text-muted fs-sm">{{ $sale->sale_date->format('d M Y') }}</td>
                                    <td class="fw-semibold">₹{{ number_format($sale->grand_total, 2) }}</td>
                                    <td>
                                        <span class="badge {{ $sale->statusBadgeClass() }} px-2 py-1 rounded-pill fs-12">
                                            {{ $sale->statusLabel() }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="ti ti-receipt-off fs-24 d-block mb-1"></i>
                                        No sales recorded yet
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer border-0 text-end">
                    <a href="{{ route('sales.index') }}" class="text-primary fs-sm fw-semibold">View all sales &rarr;</a>
                </div>
            </div>
        </div>

        {{-- Recent Purchases --}}
        <div class="col-xl-5">
            <div class="card">
                <div class="card-header justify-content-between">
                    <h4 class="card-title">Recent Purchases</h4>
                    <a href="{{ route('purchases.create') }}" class="btn btn-sm btn-primary">
                        <i class="ti ti-plus me-1"></i> New Purchase
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom table-centered table-hover w-100 mb-0">
                            <thead class="bg-light bg-opacity-25 thead-sm">
                                <tr class="text-uppercase fs-xxs">
                                    <th>Invoice</th>
                                    <th>Supplier</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody class="text-nowrap">
                                @forelse($recentPurchases as $purchase)
                                <tr>
                                    <td>
                                        <a href="{{ route('purchases.show', $purchase) }}" class="fw-semibold text-reset fs-sm">
                                            {{ $purchase->invoice_number }}
                                        </a>
                                        <br>
                                        <span class="text-muted" style="font-size:11px">{{ $purchase->purchase_date->format('d M Y') }}</span>
                                    </td>
                                    <td class="fs-sm">{{ $purchase->supplier?->display_name }}</td>
                                    <td class="fw-semibold fs-sm">₹{{ number_format($purchase->grand_total, 0) }}</td>
                                    <td>
                                        <span class="badge {{ $purchase->statusBadgeClass() }} px-2 py-1 rounded-pill fs-12">
                                            {{ $purchase->statusLabel() }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="ti ti-package-off fs-24 d-block mb-1"></i>
                                        No purchases recorded yet
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer border-0 text-end">
                    <a href="{{ route('purchases.index') }}" class="text-primary fs-sm fw-semibold">View all purchases &rarr;</a>
                </div>
            </div>
        </div>
    </div>
    {{-- end recent tables row --}}

    {{-- ── Row 4: Quick Actions ─────────────────────────────── --}}
    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Quick Actions</h4>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6 col-md-3 col-xl-2">
                            <a href="{{ route('sales.create') }}" class="btn btn-outline-success w-100 py-3 d-flex flex-column align-items-center gap-1">
                                <i class="ti ti-plus-circle fs-24"></i>
                                <span class="fs-sm fw-semibold">New Sale</span>
                            </a>
                        </div>
                        <div class="col-6 col-md-3 col-xl-2">
                            <a href="{{ route('purchases.create') }}" class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center gap-1">
                                <i class="ti ti-truck-delivery fs-24"></i>
                                <span class="fs-sm fw-semibold">New Purchase</span>
                            </a>
                        </div>
                        <div class="col-6 col-md-3 col-xl-2">
                            <a href="{{ route('products.create') }}" class="btn btn-outline-warning w-100 py-3 d-flex flex-column align-items-center gap-1">
                                <i class="ti ti-diamond fs-24"></i>
                                <span class="fs-sm fw-semibold">Add Product</span>
                            </a>
                        </div>
                        <div class="col-6 col-md-3 col-xl-2">
                            <a href="{{ route('suppliers.create') }}" class="btn btn-outline-secondary w-100 py-3 d-flex flex-column align-items-center gap-1">
                                <i class="ti ti-building fs-24"></i>
                                <span class="fs-sm fw-semibold">Add Supplier</span>
                            </a>
                        </div>
                        <div class="col-6 col-md-3 col-xl-2">
                            <a href="{{ route('customers.create') }}" class="btn btn-outline-info w-100 py-3 d-flex flex-column align-items-center gap-1">
                                <i class="ti ti-user-plus fs-24"></i>
                                <span class="fs-sm fw-semibold">Add Customer</span>
                            </a>
                        </div>
                        <div class="col-6 col-md-3 col-xl-2">
                            <a href="{{ route('stock.index') }}" class="btn btn-outline-dark w-100 py-3 d-flex flex-column align-items-center gap-1">
                                <i class="ti ti-stack-2 fs-24"></i>
                                <span class="fs-sm fw-semibold">View Stock</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- end quick actions --}}

</div>
@endsection

@push('scripts')
@php
    $chartMonths       = $months;
    $chartSalesData    = $salesData;
    $chartPurchaseData = $purchaseData;
    $todaySales        = $todaySalesCount;
    $todayPurchases    = $todayPurchaseCount;
@endphp
<script>
(function () {
    'use strict';

    /* ── colour helpers from the Paces theme ─────────────────────── */
    var isDark   = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    var gridCol  = isDark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.07)';
    var textCol  = isDark ? '#adb5bd' : '#6c757d';

    /* ── 12-Month Sales vs Purchases area chart ─────────────────── */
    var trendOptions = {
        series: [
            { name: 'Sales',     data: @json($chartSalesData) },
            { name: 'Purchases', data: @json($chartPurchaseData) }
        ],
        chart: {
            type: 'area',
            height: 280,
            toolbar: { show: false },
            zoom:    { enabled: false },
            fontFamily: 'inherit'
        },
        colors: ['#0acf97', '#3d7cc9'],
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.35,
                opacityTo:   0.05,
                stops: [0, 90, 100]
            }
        },
        stroke:    { curve: 'smooth', width: 2 },
        dataLabels: { enabled: false },
        xaxis: {
            categories: @json($chartMonths),
            axisBorder: { show: false },
            axisTicks:  { show: false },
            labels: { style: { colors: textCol, fontSize: '12px' } }
        },
        yaxis: {
            labels: {
                formatter: function (v) {
                    if (v >= 100000) return '₹' + (v / 100000).toFixed(1) + 'L';
                    if (v >= 1000)   return '₹' + (v / 1000).toFixed(0) + 'k';
                    return '₹' + v;
                },
                style: { colors: textCol, fontSize: '11px' }
            }
        },
        grid: {
            borderColor: gridCol,
            strokeDashArray: 4
        },
        tooltip: {
            y: {
                formatter: function (v) {
                    return '₹' + new Intl.NumberFormat('en-IN').format(v);
                }
            }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right',
            labels: { colors: textCol }
        }
    };

    var trendChart = new ApexCharts(document.querySelector('#dashboard-trend-chart'), trendOptions);
    trendChart.render();

    /* ── Today's donut chart ────────────────────────────────────── */
    var todayOptions = {
        series:  [@json($todaySales), @json($todayPurchases)],
        labels:  ['Sales Today', 'Purchases Today'],
        chart: {
            type: 'donut',
            height: 180,
            fontFamily: 'inherit'
        },
        colors: ['#0acf97', '#3d7cc9'],
        plotOptions: {
            pie: {
                donut: {
                    size: '70%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Today',
                            color: textCol,
                            formatter: function (w) {
                                return w.globals.seriesTotals.reduce(function (a, b) { return a + b; }, 0);
                            }
                        }
                    }
                }
            }
        },
        dataLabels: { enabled: false },
        legend: { show: false },
        tooltip: {
            y: { formatter: function (v) { return v + ' invoices'; } }
        }
    };

    var todayChart = new ApexCharts(document.querySelector('#dashboard-today-chart'), todayOptions);
    todayChart.render();

})();
</script>
@endpush
