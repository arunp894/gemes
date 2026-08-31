@extends('layout.app')

@section('content')
<div class="container-fluid dashboard-page">

    {{-- ── Page title ─────────────────────────────────────── --}}
    <div class="page-title-head d-flex align-items-center mb-3">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Dashboard</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="#">{{ $settings->get('site_name', 'Sukaina Gems') }}</a></li>
                <li class="breadcrumb-item active">Dashboard</li>
            </ol>
        </div>
    </div>

    {{-- ── Personalized greeting banner ─────────────────────── --}}
    @php
        $hour = now()->hour;
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
        $firstName = explode(' ', trim(auth()->user()->name ?? 'there'))[0];
    @endphp
    <div class="dashboard-greeting mb-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h4 class="mb-1">{{ $greeting }}, {{ $firstName }} <span class="dashboard-wave">👋</span></h4>
                <p class="mb-0 text-white-50">{{ now()->format('l, d F Y') }} — here's what's happening with your gemstone business today.</p>
            </div>
            <i class="ti ti-diamond dashboard-greeting-icon"></i>
        </div>
    </div>

    {{-- ── Row 1: KPI stat cards ───────────────────────────── --}}
    <div class="row g-3 mb-3">

        {{-- Sales Revenue --}}
        @permission('sales.view')
        <div class="col-xl-3 col-md-6">
            <div class="card card-h-100 dashboard-kpi-card dashboard-kpi-success">
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
        @endpermission

        {{-- Website Orders --}}
        @permission('sales.view')
        <div class="col-xl-3 col-md-6">
            <div class="card card-h-100 dashboard-kpi-card dashboard-kpi-teal">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted fs-base text-uppercase fw-semibold mb-1">Website Orders This Month</p>
                            <h3 class="fw-bold mb-1">{{ number_format($websiteOrdersThisMonth->count) }}</h3>
                            <p class="mb-0 text-muted fs-sm">
                                @if($websiteOrdersChange >= 0)
                                    <span class="text-success me-1"><i class="ti ti-arrow-up"></i> {{ abs($websiteOrdersChange) }}%</span>
                                @else
                                    <span class="text-danger me-1"><i class="ti ti-arrow-down"></i> {{ abs($websiteOrdersChange) }}%</span>
                                @endif
                                vs last month
                            </p>
                        </div>
                        <div class="avatar-md flex-shrink-0">
                            <span class="avatar-title bg-teal-subtle rounded-circle fs-22">
                                <i class="ti ti-world text-teal"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-top border-dashed d-flex gap-3">
                        <span class="text-muted fs-sm"><i class="ti ti-currency-rupee me-1"></i>₹{{ number_format($websiteOrdersThisMonth->revenue, 0) }}</span>
                        <a href="{{ route('sales.index') }}" class="ms-auto text-primary fs-sm fw-semibold">View all &rarr;</a>
                    </div>
                </div>
            </div>
        </div>
        @endpermission

        {{-- Purchase Spend --}}
        @permission('purchases.view')
        <div class="col-xl-3 col-md-6">
            <div class="card card-h-100 dashboard-kpi-card dashboard-kpi-primary">
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
        @endpermission

        {{-- Products --}}
        @permission('products.view')
        <div class="col-xl-3 col-md-6">
            <div class="card card-h-100 dashboard-kpi-card dashboard-kpi-warning">
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
        @endpermission

        {{-- Customers --}}
        @permission('customers.view')
        <div class="col-xl-3 col-md-6">
            <div class="card card-h-100 dashboard-kpi-card dashboard-kpi-info">
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
                        @if($canSuppliers)
                        <span class="text-muted fs-sm"><i class="ti ti-building-store me-1"></i>{{ $totalSuppliers }} suppliers</span>
                        @endif
                        <a href="{{ route('customers.index') }}" class="ms-auto text-primary fs-sm fw-semibold">View all &rarr;</a>
                    </div>
                </div>
            </div>
        </div>
        @endpermission
    </div>
    {{-- end KPI row --}}

    {{-- ── Row 2: Charts ───────────────────────────────────── --}}
    <div class="row g-3 mb-3">

        {{-- 12-Month Sales vs Purchases Trend --}}
        @permission('sales.view|purchases.view')
        <div class="col-xl-8">
            <div class="card card-h-100">
                <div class="card-header justify-content-between">
                    <h4 class="card-title"><i class="ti ti-chart-area text-primary me-2"></i>
                        @if($canSales && $canPurchases) Sales vs Purchases
                        @elseif($canSales) Sales
                        @else Purchases
                        @endif
                        — Last 12 Months
                    </h4>
                    <div class="d-flex gap-2">
                        @if($canSales)
                        <span class="badge bg-success-subtle text-success px-2 py-1 rounded-pill fs-12">
                            <i class="ti ti-circle-filled me-1" style="font-size:8px"></i> Sales
                        </span>
                        @endif
                        @if($canPurchases)
                        <span class="badge bg-primary-subtle text-primary px-2 py-1 rounded-pill fs-12">
                            <i class="ti ti-circle-filled me-1" style="font-size:8px"></i> Purchases
                        </span>
                        @endif
                    </div>
                </div>
                <div class="card-body pb-2">
                    <div id="dashboard-trend-chart" class="apex-charts" style="min-height:280px"></div>
                </div>
            </div>
        </div>
        @endpermission

        {{-- Today Summary --}}
        @permission('sales.view|purchases.view|stock.view|suppliers.view')
        <div class="col-xl-4">
            <div class="card card-h-100">
                <div class="card-header">
                    <h4 class="card-title"><i class="ti ti-sparkles text-warning me-2"></i>Today's Summary</h4>
                </div>
                <div class="card-body">
                    @if($canSales || $canPurchases)
                    <div id="dashboard-today-chart" class="apex-charts" style="min-height:180px"></div>
                    @endif

                    <div class="mt-3">
                        @permission('sales.view')
                        <div class="d-flex align-items-center justify-content-between py-2 border-bottom border-dashed">
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar-sm avatar-title bg-success-subtle rounded-circle">
                                    <i class="ti ti-cash text-success"></i>
                                </span>
                                <span class="fw-semibold">Sales Today</span>
                            </div>
                            <span class="badge bg-success-subtle text-success fs-sm px-2">{{ $todaySalesCount }} invoices</span>
                        </div>
                        @endpermission
                        @permission('purchases.view')
                        <div class="d-flex align-items-center justify-content-between py-2 border-bottom border-dashed">
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar-sm avatar-title bg-primary-subtle rounded-circle">
                                    <i class="ti ti-truck-delivery text-primary"></i>
                                </span>
                                <span class="fw-semibold">Purchases Today</span>
                            </div>
                            <span class="badge bg-primary-subtle text-primary fs-sm px-2">{{ $todayPurchaseCount }} invoices</span>
                        </div>
                        @endpermission
                        @permission('stock.view')
                        <div class="d-flex align-items-center justify-content-between py-2 border-bottom border-dashed">
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar-sm avatar-title bg-warning-subtle rounded-circle">
                                    <i class="ti ti-diamond text-warning"></i>
                                </span>
                                <span class="fw-semibold">In Stock (pieces)</span>
                            </div>
                            <span class="badge bg-warning-subtle text-warning fs-sm px-2">{{ number_format($inStockCount) }}</span>
                        </div>
                        @endpermission
                        @permission('suppliers.view')
                        <div class="d-flex align-items-center justify-content-between py-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="avatar-sm avatar-title bg-info-subtle rounded-circle">
                                    <i class="ti ti-building-warehouse text-info"></i>
                                </span>
                                <span class="fw-semibold">Active Suppliers</span>
                            </div>
                            <span class="badge bg-info-subtle text-info fs-sm px-2">{{ $activeSuppliers }}</span>
                        </div>
                        @endpermission
                    </div>
                </div>
            </div>
        </div>
        @endpermission
    </div>
    {{-- end charts row --}}

    {{-- ── Row 3: Recent Sales + Recent Purchases ──────────── --}}
    <div class="row g-3 mb-3">

        {{-- Recent Sales --}}
        @permission('sales.view')
        <div class="col-xl-7">
            <div class="card">
                <div class="card-header justify-content-between">
                    <h4 class="card-title"><i class="ti ti-receipt text-success me-2"></i>Recent Sales</h4>
                    @permission('sales.create')
                    <a href="{{ route('sales.create') }}" class="btn btn-sm btn-primary">
                        <i class="ti ti-plus me-1"></i> New Sale
                    </a>
                    @endpermission
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
        @endpermission

        {{-- Recent Purchases --}}
        @permission('purchases.view')
        <div class="col-xl-5">
            <div class="card">
                <div class="card-header justify-content-between">
                    <h4 class="card-title"><i class="ti ti-truck-delivery text-primary me-2"></i>Recent Purchases</h4>
                    @permission('purchases.create')
                    <a href="{{ route('purchases.create') }}" class="btn btn-sm btn-primary">
                        <i class="ti ti-plus me-1"></i> New Purchase
                    </a>
                    @endpermission
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
        @endpermission
    </div>
    {{-- end recent tables row --}}

    {{-- ── Row 4: Quick Actions ─────────────────────────────── --}}
    @anypermission('sales.create', 'purchases.create', 'products.create', 'suppliers.create', 'customers.create', 'stock.view')
    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title"><i class="ti ti-bolt text-warning me-2"></i>Quick Actions</h4>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @permission('sales.create')
                        <div class="col-6 col-md-3 col-xl-2">
                            <a href="{{ route('sales.create') }}" class="btn btn-outline-success w-100 py-3 d-flex flex-column align-items-center gap-1 dashboard-quick-action">
                                <i class="ti ti-plus-circle fs-24"></i>
                                <span class="fs-sm fw-semibold">New Sale</span>
                            </a>
                        </div>
                        @endpermission
                        @permission('purchases.create')
                        <div class="col-6 col-md-3 col-xl-2">
                            <a href="{{ route('purchases.create') }}" class="btn btn-outline-primary w-100 py-3 d-flex flex-column align-items-center gap-1 dashboard-quick-action">
                                <i class="ti ti-truck-delivery fs-24"></i>
                                <span class="fs-sm fw-semibold">New Purchase</span>
                            </a>
                        </div>
                        @endpermission
                        @permission('products.create')
                        <div class="col-6 col-md-3 col-xl-2">
                            <a href="{{ route('products.create') }}" class="btn btn-outline-warning w-100 py-3 d-flex flex-column align-items-center gap-1 dashboard-quick-action">
                                <i class="ti ti-diamond fs-24"></i>
                                <span class="fs-sm fw-semibold">Add Product</span>
                            </a>
                        </div>
                        @endpermission
                        @permission('suppliers.create')
                        <div class="col-6 col-md-3 col-xl-2">
                            <a href="{{ route('suppliers.create') }}" class="btn btn-outline-secondary w-100 py-3 d-flex flex-column align-items-center gap-1 dashboard-quick-action">
                                <i class="ti ti-building fs-24"></i>
                                <span class="fs-sm fw-semibold">Add Supplier</span>
                            </a>
                        </div>
                        @endpermission
                        @permission('customers.create')
                        <div class="col-6 col-md-3 col-xl-2">
                            <a href="{{ route('customers.create') }}" class="btn btn-outline-info w-100 py-3 d-flex flex-column align-items-center gap-1 dashboard-quick-action">
                                <i class="ti ti-user-plus fs-24"></i>
                                <span class="fs-sm fw-semibold">Add Customer</span>
                            </a>
                        </div>
                        @endpermission
                        @permission('stock.view')
                        <div class="col-6 col-md-3 col-xl-2">
                            <a href="{{ route('stock.index') }}" class="btn btn-outline-dark w-100 py-3 d-flex flex-column align-items-center gap-1 dashboard-quick-action">
                                <i class="ti ti-stack-2 fs-24"></i>
                                <span class="fs-sm fw-semibold">View Stock</span>
                            </a>
                        </div>
                        @endpermission
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endanypermission
    {{-- end quick actions --}}

</div>
@endsection

@push('styles')
<style>
    /* ==========================================================
       Dashboard — compact spacing + a few creative touches
       (greeting banner, accent borders, hover-lift). Scoped under
       .dashboard-page so nothing here leaks into other pages.
       ========================================================== */
    .dashboard-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        border-bottom: 2px solid #e2e8f0;
    }
    .dashboard-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        position: relative;
        padding-left: 12px;
    }
    .dashboard-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0; top: 2px; bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, #1e3a8a, #1d4ed8);
    }
    .dashboard-page .breadcrumb { font-size: 0.75rem; }

    /* Greeting banner */
    .dashboard-greeting {
        background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 55%, #14b8a6 100%);
        border-radius: 12px;
        padding: 20px 24px;
        color: #fff;
        position: relative;
        overflow: hidden;
    }
    .dashboard-greeting h4 { color: #fff; font-weight: 700; }
    .dashboard-wave { display: inline-block; animation: dashboard-wave-anim 2.2s ease-in-out infinite; transform-origin: 70% 70%; }
    @keyframes dashboard-wave-anim {
        0%, 60%, 100% { transform: rotate(0deg); }
        10% { transform: rotate(14deg); }
        20% { transform: rotate(-8deg); }
        30% { transform: rotate(14deg); }
        40% { transform: rotate(-4deg); }
        50% { transform: rotate(10deg); }
    }
    .dashboard-greeting-icon {
        font-size: 3.5rem;
        opacity: 0.18;
        position: relative;
    }

    /* KPI cards — accent top border + hover lift */
    .dashboard-page .dashboard-kpi-card {
        border-top: 3px solid transparent;
        border-radius: 10px;
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }
    .dashboard-page .dashboard-kpi-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08); }
    .dashboard-page .dashboard-kpi-success { border-top-color: #0acf97; }
    .dashboard-page .dashboard-kpi-primary { border-top-color: #3d7cc9; }
    .dashboard-page .dashboard-kpi-warning { border-top-color: #f9bf59; }
    .dashboard-page .dashboard-kpi-info    { border-top-color: #5bc3e1; }
    .dashboard-page .dashboard-kpi-teal    { border-top-color: #14b8a6; }
    .dashboard-page .bg-teal-subtle { background-color: #ccfbf1 !important; }
    .dashboard-page .text-teal { color: #0d9488 !important; }

    /* Chart / list cards */
    .dashboard-page .card { border-radius: 10px; }

    /* Recent tables: subtle compact rows */
    .dashboard-page table.table-custom tbody td { padding-top: 10px; padding-bottom: 10px; }
    .dashboard-page table.table-custom tbody tr:hover { background: #f8fafc; }

    /* Quick actions — lively hover */
    .dashboard-page .dashboard-quick-action {
        border-radius: 10px;
        transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
    }
    .dashboard-page .dashboard-quick-action:hover {
        transform: translateY(-3px) scale(1.02);
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
    }
</style>
@endpush

@push('scripts')
@php
    $chartMonths       = $months;
    $todaySales        = $todaySalesCount;
    $todayPurchases    = $todayPurchaseCount;

    $trendSeries = [];
    if ($canSales) {
        $trendSeries[] = ['name' => 'Sales', 'data' => $salesData];
    }
    if ($canPurchases) {
        $trendSeries[] = ['name' => 'Purchases', 'data' => $purchaseData];
    }

    $todaySeries = [];
    $todayLabels = [];
    if ($canSales) {
        $todaySeries[] = $todaySales;
        $todayLabels[] = 'Sales Today';
    }
    if ($canPurchases) {
        $todaySeries[] = $todayPurchases;
        $todayLabels[] = 'Purchases Today';
    }
@endphp
<script>
(function () {
    'use strict';

    /* ── colour helpers from the Paces theme ─────────────────────── */
    var isDark   = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    var gridCol  = isDark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.07)';
    var textCol  = isDark ? '#adb5bd' : '#6c757d';

    /* ── 12-Month Sales vs Purchases area chart ─────────────────── */
    var trendSeries = @json($trendSeries);
    var trendEl = document.querySelector('#dashboard-trend-chart');
    if (trendEl && trendSeries.length) {
    var trendOptions = {
        series: trendSeries,
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

    var trendChart = new ApexCharts(trendEl, trendOptions);
    trendChart.render();
    }

    /* ── Today's donut chart ────────────────────────────────────── */
    var todaySeries = @json($todaySeries);
    var todayEl = document.querySelector('#dashboard-today-chart');
    if (todayEl && todaySeries.length) {
    var todayOptions = {
        series:  todaySeries,
        labels:  @json($todayLabels),
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

    var todayChart = new ApexCharts(todayEl, todayOptions);
    todayChart.render();
    }

})();
</script>
@endpush
