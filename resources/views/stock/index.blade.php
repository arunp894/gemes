@extends('layout.app')

@section('title', 'Stock Dashboard')

@section('content')

<div class="container-fluid stock-page">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Stock Dashboard</h4>
            <small class="text-muted">Overview of your inventory</small>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Inventory</a></li>
                <li class="breadcrumb-item active">Stock</li>
            </ol>
        </div>
    </div>

    {{-- ── KPI row ─────────────────────────────────────────── --}}
    <div class="row g-3 mb-1">
        <div class="col-6 col-xl-3">
            <div class="stock-kpi">
                <div class="stock-kpi-icon stock-kpi-icon-primary"><i class="ti ti-package"></i></div>
                <div class="stock-kpi-value">{{ number_format($totalCurrentStock) }} <span class="stock-kpi-unit">Pcs</span></div>
                <div class="stock-kpi-label">Total Current Stock</div>
                @if ($totalCurrentStockCt > 0)
                    <div class="stock-kpi-sub">{{ rtrim(rtrim(number_format($totalCurrentStockCt, 2), '0'), '.') }} Ct</div>
                @endif
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stock-kpi">
                <div class="stock-kpi-icon stock-kpi-icon-gold"><i class="ti ti-currency-rupee"></i></div>
                <div class="stock-kpi-value">₹{{ number_format($totalStockValue) }}</div>
                <div class="stock-kpi-label">Total Stock Value</div>
                <div class="stock-kpi-sub">At Cost Price</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stock-kpi">
                <div class="stock-kpi-icon stock-kpi-icon-success"><i class="ti ti-arrow-down-circle"></i></div>
                <div class="stock-kpi-value text-success">+{{ number_format($todayReceivedQty) }} <span class="stock-kpi-unit">Pcs</span></div>
                <div class="stock-kpi-label">Today Stock Received</div>
                @if ($todayReceivedCt > 0)
                    <div class="stock-kpi-sub">{{ rtrim(rtrim(number_format($todayReceivedCt, 2), '0'), '.') }} Ct</div>
                @endif
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stock-kpi">
                <div class="stock-kpi-icon stock-kpi-icon-danger"><i class="ti ti-arrow-up-circle"></i></div>
                <div class="stock-kpi-value text-danger">-{{ number_format($todayRemovedQty) }} <span class="stock-kpi-unit">Pcs</span></div>
                <div class="stock-kpi-label">Today Stock Removed</div>
                @if ($todayRemovedCt > 0)
                    <div class="stock-kpi-sub">{{ rtrim(rtrim(number_format($todayRemovedCt, 2), '0'), '.') }} Ct</div>
                @endif
            </div>
        </div>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="stock-kpi stock-kpi-wide">
                <div class="stock-kpi-icon stock-kpi-icon-warning"><i class="ti ti-alert-triangle"></i></div>
                <div class="flex-grow-1">
                    <div class="stock-kpi-value">{{ number_format($lowStockCount) }} <span class="stock-kpi-unit">Items</span></div>
                    <div class="stock-kpi-label">Low Stock Items <span class="text-muted fw-normal">— {{ $lowStockThreshold }} units or fewer remaining</span></div>
                </div>
                <a href="#lowStockCard" class="stock-kpi-link">View Details</a>
            </div>
        </div>
    </div>

    <div class="row g-3">

        {{-- ── Main column: On Hand / Stock Movement ───────────────── --}}
        <div class="col-xl-8">

            @php
                // Each of the three ledger tabs is the exact same table
                // shape, filtered server-side by a fixed `type` — the tab
                // itself is the filter now, replacing the old single
                // "Stock Movement" tab's pill-button row.
                $ledgerTabs = [
                    [
                        'id' => 'stockIn', 'label' => 'Stock In', 'icon' => 'ti-arrow-down-circle', 'type' => 'in',
                        'subtitle' => 'Everything added to inventory — purchases received, transfers in, returns, adjustments.',
                        'qtyCols' => 'in', 'sourceLabel' => 'Source',
                    ],
                    [
                        'id' => 'stockOut', 'label' => 'Stock Out', 'icon' => 'ti-arrow-up-circle', 'type' => 'out',
                        'subtitle' => 'Everything removed from inventory — sales, transfers out, adjustments.',
                        'qtyCols' => 'out', 'sourceLabel' => 'Destination',
                    ],
                    [
                        'id' => 'transfer', 'label' => 'Transfer', 'icon' => 'ti-arrows-exchange', 'type' => 'transfer',
                        'subtitle' => 'Stock moved between your locations.',
                        'qtyCols' => 'both', 'sourceLabel' => 'Source / Destination',
                    ],
                ];
            @endphp

            <ul class="nav nav-tabs mb-3 stock-tabs" id="stockReportTabs" role="tablist">
                @foreach ($ledgerTabs as $i => $t)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link @if($i === 0) active @endif" id="{{ $t['id'] }}Tab" data-bs-toggle="tab" data-bs-target="#{{ $t['id'] }}Pane"
                            type="button" role="tab" aria-controls="{{ $t['id'] }}Pane" aria-selected="{{ $i === 0 ? 'true' : 'false' }}">
                            <i class="ti {{ $t['icon'] }} fs-sm me-1"></i> {{ $t['label'] }}
                        </button>
                    </li>
                @endforeach
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="byStoneTab" data-bs-toggle="tab" data-bs-target="#byStonePane"
                        type="button" role="tab" aria-controls="byStonePane" aria-selected="false">
                        <i class="ti ti-scale fs-sm me-1"></i> Stones &amp; Carat
                    </button>
                </li>
            </ul>

            {{-- Today's activity, at a glance — shown once above the tabs
                 rather than duplicated on all three (it spans both
                 directions and every category, so it doesn't belong to
                 any single Stock In/Out/Transfer tab). --}}
            <div class="movement-summary-strip mb-3">
                <div class="movement-summary-tile">
                    <span class="movement-summary-icon movement-summary-icon-success"><i class="ti ti-arrow-down"></i></span>
                    <div>
                        <div class="movement-summary-value">{{ number_format($todayReceivedQty) }} <span class="fw-normal fs-xs text-muted">Pcs</span></div>
                        <div class="movement-summary-label">Stock Received <span class="text-muted">Today</span></div>
                    </div>
                </div>
                <div class="movement-summary-tile">
                    <span class="movement-summary-icon movement-summary-icon-danger"><i class="ti ti-arrow-up"></i></span>
                    <div>
                        <div class="movement-summary-value">{{ number_format($todayRemovedQty) }} <span class="fw-normal fs-xs text-muted">Pcs</span></div>
                        <div class="movement-summary-label">Stock Removed <span class="text-muted">Today</span></div>
                    </div>
                </div>
                <div class="movement-summary-tile">
                    <span class="movement-summary-icon movement-summary-icon-info"><i class="ti ti-arrows-exchange"></i></span>
                    <div>
                        <div class="movement-summary-value">{{ number_format($todayTransfersQty) }} <span class="fw-normal fs-xs text-muted">Pcs</span></div>
                        <div class="movement-summary-label">Transfers <span class="text-muted">Today</span></div>
                    </div>
                </div>
                <div class="movement-summary-tile">
                    <span class="movement-summary-icon movement-summary-icon-purple"><i class="ti ti-shopping-cart"></i></span>
                    <div>
                        <div class="movement-summary-value">{{ number_format($todaySalesQty) }} <span class="fw-normal fs-xs text-muted">Pcs</span></div>
                        <div class="movement-summary-label">Sales (Impact) <span class="text-muted">Today</span></div>
                    </div>
                </div>
                <div class="movement-summary-tile">
                    <span class="movement-summary-icon movement-summary-icon-success"><i class="ti ti-arrow-back-up"></i></span>
                    <div>
                        <div class="movement-summary-value">{{ number_format($todayReturnsQty) }} <span class="fw-normal fs-xs text-muted">Pcs</span></div>
                        <div class="movement-summary-label">Returns <span class="text-muted">Today</span></div>
                    </div>
                </div>
                <div class="movement-summary-tile">
                    <span class="movement-summary-icon movement-summary-icon-warning"><i class="ti ti-adjustments"></i></span>
                    <div>
                        <div class="movement-summary-value">{{ number_format($todayAdjustmentsQty) }} <span class="fw-normal fs-xs text-muted">Pcs</span></div>
                        <div class="movement-summary-label">Adjustments <span class="text-muted">Today</span></div>
                    </div>
                </div>
            </div>

            <div class="tab-content" id="stockReportTabContent">

                {{-- ═══════════════════════ STOCK IN / OUT / TRANSFER ═══════════════════════
                     Three focused ledgers instead of one unified table with
                     filter pills — each tab IS the filter (fixed `type` sent
                     to the same stock.movements-data endpoint), so the table
                     only ever shows columns relevant to that direction. ── --}}
                @foreach ($ledgerTabs as $i => $t)
                <div class="tab-pane fade @if($i === 0) show active @endif" id="{{ $t['id'] }}Pane" role="tabpanel" aria-labelledby="{{ $t['id'] }}Tab">
                    <div class="card">
                        <div class="card-body pb-0">
                            <h5 class="card-title mb-0">{{ $t['label'] }}</h5>
                            <p class="text-muted fs-sm mb-3">{{ $t['subtitle'] }}</p>
                        </div>

                        <div class="card-header border-light movement-toolbar flex-wrap gap-2">
                            <div class="d-flex flex-wrap align-items-center gap-1">
                                <input type="date" id="{{ $t['id'] }}DateFrom" class="form-control form-control-sm" title="From date" style="width: 145px;">
                                <span class="text-muted small">to</span>
                                <input type="date" id="{{ $t['id'] }}DateTo" class="form-control form-control-sm" title="To date" style="width: 145px;">

                                <select id="{{ $t['id'] }}ProductFilter" class="form-select" style="min-width: 180px;">
                                    <option value="">All Products</option>
                                </select>

                                <div class="app-search">
                                    <select id="{{ $t['id'] }}LocationFilter" class="form-select form-control my-1 my-md-0">
                                        <option value="">All Locations</option>
                                        @foreach ($locations as $l)
                                            <option value="{{ $l->id }}">{{ $l->name }}</option>
                                        @endforeach
                                    </select>
                                    <i class="ti ti-map-pin app-search-icon text-muted"></i>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap align-items-center gap-1 flex-grow-1 justify-content-end">
                                <div class="app-search">
                                    <input id="{{ $t['id'] }}Search" type="search" class="form-control" placeholder="Search product, SKU, or reference…" style="min-width: 200px;" />
                                    <i class="ti ti-search app-search-icon text-muted"></i>
                                </div>
                                <button type="button" id="{{ $t['id'] }}FilterReset" class="btn btn-outline-secondary btn-sm" title="Clear filters">
                                    <i class="ti ti-filter-x"></i>
                                </button>
                                @if ($t['id'] === 'transfer')
                                    @permission('stock-transfers.create')
                                    <a href="{{ route('stock-transfers.create') }}" class="add-btn ms-1">
                                        <i class="ti ti-transfer fs-sm me-2"></i> New Transfer
                                    </a>
                                    @endpermission
                                @endif
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="{{ $t['id'] }}Table" class="table table-custom table-centered table-hover w-100 mb-0 stock-ledger-table">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th class="text-center" style="width: 1%;">S.No</th>
                                        <th style="width: 12%;"><i class="ti ti-calendar me-1"></i>Date &amp; Time</th>
                                        <th style="width: 20%;"><i class="ti ti-diamond me-1"></i>Product</th>
                                        <th style="width: 12%;"><i class="ti ti-arrows-exchange me-1"></i>Movement Type</th>
                                        @if ($t['qtyCols'] === 'in' || $t['qtyCols'] === 'both')
                                            <th class="text-end" style="width: 8%;"><i class="ti ti-arrow-down me-1"></i>Stock In</th>
                                        @endif
                                        @if ($t['qtyCols'] === 'out' || $t['qtyCols'] === 'both')
                                            <th class="text-end" style="width: 8%;"><i class="ti ti-arrow-up me-1"></i>Stock Out</th>
                                        @endif
                                        <th style="width: 13%;"><i class="ti ti-hash me-1"></i>Reference No.</th>
                                        <th style="width: 13%;"><i class="ti ti-building-warehouse me-1"></i>{{ $t['sourceLabel'] }}</th>
                                        <th style="width: 10%;"><i class="ti ti-map-pin me-1"></i>Location</th>
                                        <th style="width: 10%;"><i class="ti ti-user me-1"></i>User</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>

                        <div class="card-footer border-0">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div id="{{ $t['id'] }}InfoSlot" class="text-muted small"></div>
                                <div class="d-flex align-items-center gap-2 footer-pagination-group">
                                    <select id="{{ $t['id'] }}PerPage" class="form-select form-select-sm" style="width: auto;">
                                        <option value="25" selected>25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                        <option value="200">200</option>
                                    </select>
                                    <div id="{{ $t['id'] }}PaginationSlot"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach

                {{-- ═══════════════════════ STONES & CARAT ═══════════════════════
                     Stone-wise (category) rollup: how many pieces, how much
                     carat weight, and how much stock value sits under each
                     gemstone type — the report-style counterpart to the
                     compact "By Stone" list on the Current Stock tab. ── --}}
                <div class="tab-pane fade" id="byStonePane" role="tabpanel" aria-labelledby="byStoneTab">
                    <div class="card">
                        <div class="card-header border-light justify-content-between flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="ti ti-scale fs-18 text-primary"></i>
                                <div>
                                    <h5 class="card-title mb-0">Stones Wise Stock &amp; Carat List</h5>
                                    <p class="text-muted fs-xs mb-0">Pieces, carat weight, and stock value per gemstone type</p>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap align-items-center gap-1">
                                <div class="app-search">
                                    <select id="byStoneCategoryFilter" class="form-select form-control my-1 my-md-0">
                                        <option value="">All Stones</option>
                                        @foreach ($categories as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                    <i class="ti ti-tag app-search-icon text-muted"></i>
                                </div>
                                <div class="app-search">
                                    <select id="byStoneLocationFilter" class="form-select form-control my-1 my-md-0">
                                        <option value="">All Locations</option>
                                        @foreach ($locations as $l)
                                            <option value="{{ $l->id }}">{{ $l->name }}</option>
                                        @endforeach
                                    </select>
                                    <i class="ti ti-map-pin app-search-icon text-muted"></i>
                                </div>
                                <div class="app-search">
                                    <input id="byStoneSearch" type="search" class="form-control" placeholder="Search stone…" />
                                    <i class="ti ti-search app-search-icon text-muted"></i>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="byStoneTable" class="table table-custom table-centered table-hover w-100 mb-0 stock-ledger-table">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th class="text-center" style="width: 1%;">S.No</th>
                                        <th><i class="ti ti-diamond me-1"></i>Stone Name</th>
                                        <th class="text-end"><i class="ti ti-stack-2 me-1"></i>Pieces</th>
                                        <th class="text-end"><i class="ti ti-scale me-1"></i>Carat Weight</th>
                                        <th class="text-end">Rate / Ct (Avg.)</th>
                                        <th class="text-end">Stock Value</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>

                        <div class="card-footer border-0">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div id="byStoneInfoSlot" class="text-muted small"></div>
                                <div class="d-flex align-items-center gap-2 footer-pagination-group">
                                    <select id="byStonePerPage" class="form-select form-select-sm" style="width: auto;">
                                        <option value="10">10</option>
                                        <option value="25" selected>25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                    <div id="byStonePaginationSlot"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ── Sidebar: Stock by Location / Low Stock / Recent Transfers ── --}}
        <div class="col-xl-4">

            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Stock by Location</h5>
                </div>
                <div class="card-body">
                    @if ($byLocation->isEmpty())
                        <p class="text-muted small mb-0 text-center py-3">No stock recorded yet.</p>
                    @else
                        <div id="locationDonutChart"></div>
                        <ul class="location-legend">
                            @foreach ($byLocation as $i => $loc)
                                <li>
                                    <span class="location-legend-dot" style="background: {{ ['#0f5e57','#b8860b','#2563eb','#dc2626','#059669','#9333ea'][$i % 6] }}"></span>
                                    <span class="flex-grow-1">{{ $loc->name }}</span>
                                    <span class="fw-semibold">{{ round(($loc->on_hand / max($byLocation->sum('on_hand'), 1)) * 100) }}%</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="card" id="lowStockCard">
                <div class="card-header border-light d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Low Stock Items</h5>
                    <a href="#" onclick="document.getElementById('byStoneTab').click(); return false;" class="fs-sm">View All</a>
                </div>
                <div class="card-body p-0">
                    @if ($lowStockItems->isEmpty())
                        <p class="text-muted small mb-0 text-center py-3">Nothing running low right now.</p>
                    @else
                        <ul class="low-stock-list">
                            @foreach ($lowStockItems as $item)
                                <li>
                                    <span class="movement-thumb movement-thumb-sm"><i class="ti ti-diamond"></i></span>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="fw-semibold fs-sm text-truncate">{{ $item->title }}</div>
                                        <small class="text-muted">{{ $item->category_name ?? $item->sku }}</small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fs-sm">{{ (int) $item->on_hand }} Pcs</div>
                                        <span class="badge badge-soft-danger fs-xxs">Low Stock</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            @permission('stock-transfers.view')
            <div class="card">
                <div class="card-header border-light d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Recent Transfers</h5>
                    <a href="{{ route('stock-transfers.index') }}" class="fs-sm">View All</a>
                </div>
                <div class="card-body p-0">
                    @if ($recentTransfers->isEmpty())
                        <p class="text-muted small mb-0 text-center py-3">No transfers yet.</p>
                    @else
                        <ul class="recent-transfer-list">
                            @foreach ($recentTransfers as $t)
                                <li>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <a href="{{ route('stock-transfers.show', $t) }}" class="fw-semibold fs-sm">{{ $t->transfer_number }}</a>
                                        <span class="badge {{ $t->statusBadgeClass() }} fs-xxs">{{ $t->statusLabel() }}</span>
                                    </div>
                                    <div class="text-muted fs-xs">{{ optional($t->transfer_date)->format('d M Y') }}</div>
                                    <div class="fs-sm">{{ $t->fromLocation?->name }} <i class="ti ti-arrow-right fs-xxs"></i> {{ $t->toLocation?->name }}</div>
                                    <small class="text-muted">{{ (int) ($t->lines_sum_qty ?? 0) }} Ct</small>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
            @endpermission

        </div>
    </div>

</div>

@endsection

@push('styles')
<style>
    /* ==========================================================
       Stock Dashboard — compact ERP styling
       Scoped under .stock-page so nothing here leaks into other
       pages that share the same layout/theme classes.
       ========================================================== */
    .stock-page {
        /* Deep-teal brand primary (matches the login page's jewelry-brand
           identity) instead of a generic SaaS blue, with the rest of the
           palette kept semantic: green = in/received, red = out/removed,
           blue = transfer, amber = adjustment, plum = sale, gold = value. */
        --stock-primary: #0f5e57;
        --stock-primary-dark: #0b3d3a;
        --stock-primary-light: #e6f5f3;
        --stock-success: #059669;
        --stock-teal: #2563eb;
        --stock-warning: #d97706;
        --stock-danger: #dc2626;
        --stock-purple: #9333ea;
        --stock-gold: #b8860b;
        --stock-gold-light: #fdf6e3;
        --stock-border: #e2e8f0;
        --stock-text-muted: #64748b;
        padding-top: 0;
        padding-bottom: 20px;
    }
    .stock-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid var(--stock-border);
    }
    .stock-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        position: relative;
        padding-left: 12px;
    }
    .stock-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0; top: 2px; bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, var(--stock-primary-dark), var(--stock-primary));
    }
    .stock-page .breadcrumb { font-size: 0.75rem; }

    /* KPI cards */
    .stock-kpi {
        background: #fff; border: 1px solid var(--stock-border); border-radius: 12px;
        padding: 16px; height: 100%;
    }
    .stock-kpi.stock-kpi-wide { display: flex; align-items: center; gap: 14px; }
    .stock-kpi-icon {
        width: 42px; height: 42px; border-radius: 10px; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center; font-size: 1.2rem;
        margin-bottom: 10px;
    }
    .stock-kpi-wide .stock-kpi-icon { margin-bottom: 0; }
    .stock-kpi-icon-primary { background: var(--stock-primary-light); color: var(--stock-primary); }
    .stock-kpi-icon-success { background: #ecfdf5; color: var(--stock-success); }
    .stock-kpi-icon-gold    { background: var(--stock-gold-light); color: var(--stock-gold); }
    .stock-kpi-icon-danger  { background: #fef2f2; color: var(--stock-danger); }
    .stock-kpi-icon-warning { background: #fffbeb; color: var(--stock-warning); }
    .stock-kpi-value { font-size: 1.375rem; font-weight: 800; line-height: 1.2; }
    .stock-kpi-unit { font-size: 0.8125rem; font-weight: 600; color: var(--stock-text-muted); }
    .stock-kpi-label { font-size: 0.8125rem; font-weight: 600; color: var(--stock-text-muted); margin-top: 2px; }
    .stock-kpi-sub { font-size: 0.75rem; color: var(--stock-text-muted); margin-top: 2px; }
    .stock-kpi-link { font-size: 0.8125rem; font-weight: 600; flex-shrink: 0; }

    /* Tabs */
    .stock-page .stock-tabs .nav-link { font-size: 0.8125rem; font-weight: 600; padding: 8px 14px; }
    .stock-page .stock-tabs .nav-link.active { color: var(--stock-primary); }

    .stock-page .card { border-radius: 10px; box-shadow: none; border: 1px solid var(--stock-border); }
    .stock-page .card-header { padding: 10px 16px; }
    .stock-page .card-title { font-size: 0.9375rem; font-weight: 700; }

    /* Toolbar */
    .app-search { position: relative; }
    .app-search > .app-search-icon { position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); pointer-events: none; }
    .app-search > .form-control { padding-right: 2.25rem; min-width: 200px; }
    .stock-page .card-header .form-control,
    .stock-page .card-header .form-select { height: 38px; font-size: 0.8125rem; }
    .stock-page .add-btn {
        display: inline-flex; align-items: center;
        background: linear-gradient(135deg, var(--stock-primary-dark), var(--stock-primary));
        color: #fff; border: none; border-radius: 8px; padding: 9px 18px;
        font-weight: 600; font-size: 0.8125rem; text-decoration: none;
    }
    .stock-page .add-btn:hover { color: #fff; box-shadow: 0 4px 10px rgba(29, 78, 216, 0.25); }

    /* Tables — shared by the Stock In / Stock Out / Transfer ledgers and
       the Stones & Carat table, so all four read as one consistent system
       rather than each having its own slightly-different styling. */
    .stock-page .stock-ledger-table thead th {
        background: #f1f5f9; font-weight: 700; font-size: 0.6875rem; letter-spacing: 0.03em; padding: 8px 12px;
    }
    .stock-page .stock-ledger-table tbody td {
        padding: 6px 12px; font-size: 0.75rem; vertical-align: middle;
        white-space: nowrap; max-width: 220px; overflow: hidden; text-overflow: ellipsis;
    }
    .stock-page .stock-ledger-table tbody tr:hover { background: #f8fafc; }
    /* Product cell links to that product's full stock history — a subtle
       underline-on-hover is the only cue since it deliberately keeps the
       row's normal text color instead of looking like a typical blue link. */
    .stock-page .stock-ledger-table td > a.text-reset:hover .fw-semibold { text-decoration: underline; }
    .stock-page span.dt-column-order:before,
    .stock-page span.dt-column-order:after { color: #475569; }
    .stock-page span.dt-column-order:before { opacity: .45; }
    .stock-page span.dt-column-order:after { opacity: .9; }

    .movement-thumb {
        display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
        width: 32px; height: 32px; border-radius: 50%; background: var(--stock-primary-light); color: var(--stock-primary); font-size: 0.9rem;
    }
    .movement-thumb-sm { width: 28px; height: 28px; font-size: 0.8rem; }
    /* Typography consistency: badges and muted sub-text ("small") were
       two different sizes (0.6875rem vs. the browser's 80% default on
       small), which reads as visually "unaligned" next to each other in
       the same row. Both now match at 0.75rem. */
    .stock-page .stock-ledger-table .badge,
    .stock-page .stock-ledger-table small { font-size: 0.75rem; }

    /* Numeric/date columns: tabular figures so digits line up vertically
       from row to row instead of drifting with proportional widths. */
    .stock-page .stock-ledger-table .movement-qty,
    .stock-page .stock-ledger-table .movement-date,
    .stock-page .stock-ledger-table .movement-time {
        font-variant-numeric: tabular-nums;
        font-feature-settings: "tnum";
    }
    /* Date + time now sit on one line per row instead of stacking —
       shrunk slightly further so both fit comfortably together. */
    .stock-page .stock-ledger-table .movement-date,
    .stock-page .stock-ledger-table .movement-time {
        font-size: 0.6875rem;
    }
    .stock-page .stock-ledger-table .movement-qty { display: inline-block; min-width: 2.5em; text-align: right; }
    .badge-soft-purple { background: #ede9fe; color: var(--stock-purple); }

    /* Stock In / Out / Transfer — sticky toolbar while scrolling long results */
    .stock-page .movement-toolbar { position: sticky; top: 65px; z-index: 5; background: #fff; display: flex; align-items: center; }
    /* The theme's global Select2 CSS stretches every instance to 100% of
       its flex parent regardless of the JS `width` option passed at
       init — without this override the product filter swallows the
       whole toolbar row and pushes location/search/reset onto their own
       wrapped lines instead of sitting inline. */
    .stock-page .movement-toolbar .select2-container { width: 180px !important; flex: 0 0 auto; }
    /* Sticky table headers (both plain <thead> and per-<th>) rendered a
       ghost/duplicate header row mid-scroll in real testing — dropped in
       favor of just the sticky filter toolbar above, which is a plain
       <div> and doesn't have the same cross-browser table quirks. */

    /* Bottom summary strip */
    .movement-summary-strip {
        display: grid; grid-template-columns: repeat(6, 1fr); gap: 1px;
        background: var(--stock-border); border-top: 1px solid var(--stock-border);
    }
    .movement-summary-tile { background: #fff; padding: 14px 12px; display: flex; align-items: center; gap: 10px; }
    .movement-summary-icon {
        width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center; font-size: 1rem;
    }
    .movement-summary-icon-success { background: #ecfdf5; color: var(--stock-success); }
    .movement-summary-icon-danger  { background: #fef2f2; color: var(--stock-danger); }
    .movement-summary-icon-info    { background: #eff6ff; color: var(--stock-teal); }
    .movement-summary-icon-purple  { background: #ede9fe; color: var(--stock-purple); }
    .movement-summary-icon-warning { background: #fffbeb; color: var(--stock-warning); }
    .movement-summary-value { font-size: 1rem; font-weight: 800; line-height: 1.2; }
    .movement-summary-label { font-size: 0.6875rem; color: var(--stock-text-muted); font-weight: 600; }
    @media (max-width: 1400px) { .movement-summary-strip { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 576px) { .movement-summary-strip { grid-template-columns: repeat(2, 1fr); } }

    /* Sidebar: location legend */
    .location-legend { list-style: none; margin: 12px 0 0; padding: 0; }
    .location-legend li { display: flex; align-items: center; gap: 8px; padding: 5px 0; font-size: 0.8125rem; }
    .location-legend-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }

    /* Sidebar: low stock + recent transfers lists */
    .low-stock-list, .recent-transfer-list { list-style: none; margin: 0; padding: 0; }
    .low-stock-list li { display: flex; align-items: center; gap: 10px; padding: 10px 16px; border-bottom: 1px solid #f1f5f9; }
    .low-stock-list li:last-child { border-bottom: none; }
    .recent-transfer-list li { padding: 10px 16px; border-bottom: 1px solid #f1f5f9; }
    .recent-transfer-list li:last-child { border-bottom: none; }
    .min-w-0 { min-width: 0; }

    /* Ledger action links */
    .stock-page .action-link {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 5px 12px; border-radius: 7px; font-size: 0.75rem; font-weight: 600;
        text-decoration: none; transition: all 0.2s ease;
    }
    .stock-page .action-link:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,0.08); }
    .stock-page .action-link-view   { color: #2563eb; background: #eff6ff; border: 1px solid #bfdbfe; }
    .stock-page .action-link-danger { color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; }

    /* Loading indicator */
    .stock-page .dataTables_processing { background: transparent !important; border: 0 !important; box-shadow: none !important; }
    .stock-page .stock-loading {
        display: inline-flex; align-items: center; gap: 8px;
        background: #fff; border: 1px solid var(--stock-border);
        padding: 8px 18px; border-radius: 999px; font-size: 0.8125rem; font-weight: 600;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.1);
    }
    .stock-page .stock-loading .spinner-border { width: 1rem; height: 1rem; color: var(--stock-primary); border-width: 0.15em; }

    #stockInTable_wrapper .dataTables_length, #stockInTable_wrapper .dataTables_filter,
    #stockOutTable_wrapper .dataTables_length, #stockOutTable_wrapper .dataTables_filter,
    #transferTable_wrapper .dataTables_length, #transferTable_wrapper .dataTables_filter,
    #byStoneTable_wrapper .dataTables_length, #byStoneTable_wrapper .dataTables_filter {
        display: none !important;
    }
    #stockInInfoSlot .dataTables_info, #stockOutInfoSlot .dataTables_info,
    #transferInfoSlot .dataTables_info, #byStoneInfoSlot .dataTables_info {
        padding: 0; font-size: 0.875rem;
    }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    // ═══════════════════ Stock by Location donut ═══════════════════
    const locationEl = document.querySelector('#locationDonutChart');
    if (locationEl) {
        new ApexCharts(locationEl, {
            series: @json($byLocation->pluck('on_hand')),
            labels: @json($byLocation->pluck('name')),
            chart: { type: 'donut', height: 200, fontFamily: 'inherit' },
            colors: ['#0f5e57', '#b8860b', '#2563eb', '#dc2626', '#059669', '#9333ea'],
            dataLabels: { enabled: false },
            legend: { show: false },
            tooltip: { y: { formatter: (v) => v.toLocaleString() + ' Pcs' } },
        }).render();
    }


    // ═══════════════════ Stones & Carat tab ═══════════════════
    let byStoneDt = null;

    function initByStoneTable() {
        if (byStoneDt) return;

        byStoneDt = $('#byStoneTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            order: [[3, 'desc']],
            ajax: {
                url: '{{ route('stock.by-stone-data') }}',
                data: function (d) {
                    d.location_id = $('#byStoneLocationFilter').val();
                    d.category_id = $('#byStoneCategoryFilter').val();
                },
            },
            dom: 'rt<"stock-tail"ip>',
            pageLength: 25,
            columns: [
                { data: 'DT_RowIndex',   name: 'DT_RowIndex',    orderable: false, searchable: false, className: 'text-center' },
                { data: 'stone_label',   name: 'stone_label',    orderable: false },
                { data: 'pieces',        name: 'pieces',         orderable: true,  searchable: false, className: 'text-end' },
                { data: 'carat_label',   name: 'carat_weight',   orderable: true,  searchable: false, className: 'text-end' },
                { data: 'rate_label',    name: 'rate_label',     orderable: false, searchable: false, className: 'text-end' },
                { data: 'value_label',   name: 'stock_value',    orderable: true,  searchable: false, className: 'text-end' },
                { data: 'status_label',  name: 'status_label',   orderable: false, searchable: false, className: 'text-center' },
            ],
            language: {
                info: 'Showing _START_ to _END_ of _TOTAL_ rows',
                emptyTable: 'No stock recorded yet.',
                processing: '<div class="stock-loading"><span class="spinner-border spinner-border-sm"></span>Loading stones&hellip;</div>',
                paginate: { previous: '<i class="ti ti-chevron-left"></i>', next: '<i class="ti ti-chevron-right"></i>' },
            },
            initComplete: function () {
                const container = $(this.api().table().container());
                $('#byStoneInfoSlot').append(container.find('#byStoneTable_info'));
                $('#byStonePaginationSlot').append(container.find('.stock-tail'));
            },
        });

        let timer;
        $('#byStoneSearch').on('keyup', function () {
            clearTimeout(timer);
            const v = this.value;
            timer = setTimeout(() => byStoneDt.search(v).draw(), 250);
        });
        $('#byStonePerPage').on('change', function () { byStoneDt.page.len(parseInt(this.value, 10)).draw(); });
        $('#byStoneLocationFilter').on('change', () => byStoneDt.draw());
        $('#byStoneCategoryFilter').on('change', () => byStoneDt.draw());
    }

    document.getElementById('byStoneTab').addEventListener('shown.bs.tab', initByStoneTable);

    // ═══════════════════ Stock In / Stock Out / Transfer tabs ═══════════════════
    // Each tab is the same ledger table with a FIXED `type` sent to
    // stock.movements-data — no pill row needed since the tab itself is
    // the filter. `columns` differs only in which of Stock In / Stock Out
    // is present (Transfer keeps both, since a transfer produces one leg
    // of each direction).
    const ledgerTabs = [
        { id: 'stockIn',  type: 'in',       qtyCols: 'in'   },
        { id: 'stockOut', type: 'out',      qtyCols: 'out'  },
        { id: 'transfer', type: 'transfer', qtyCols: 'both' },
    ];
    const ledgerInstances = {};

    function initLedgerTable(cfg) {
        if (ledgerInstances[cfg.id]) return;

        $(`#${cfg.id}ProductFilter`).select2({
            placeholder: 'All Products',
            allowClear: true,
            width: '180px',
            ajax: {
                url: '{{ route('stock.search-products') }}',
                dataType: 'json',
                delay: 250,
                data: (params) => ({ q: params.term || '' }),
                processResults: (data) => ({
                    results: (data.items || []).map((p) => ({ id: p.id, text: `${p.title} (${p.sku})` })),
                }),
            },
        });

        const columns = [
            { data: 'DT_RowIndex',   name: 'DT_RowIndex',   orderable: false, searchable: false, className: 'text-center' },
            { data: 'when_label',    name: 'when_label',    orderable: false, searchable: false },
            { data: 'product_label', name: 'product_label', orderable: false },
            { data: 'movement_label', name: 'movement_label', orderable: false, searchable: false },
        ];
        if (cfg.qtyCols === 'in' || cfg.qtyCols === 'both') {
            columns.push({ data: 'stock_in_label', name: 'stock_in_label', orderable: false, searchable: false, className: 'text-end' });
        }
        if (cfg.qtyCols === 'out' || cfg.qtyCols === 'both') {
            columns.push({ data: 'stock_out_label', name: 'stock_out_label', orderable: false, searchable: false, className: 'text-end' });
        }
        columns.push(
            { data: 'reference_label', name: 'reference_label', orderable: false, searchable: false },
            { data: 'source_label',    name: 'source_label',    orderable: false, searchable: false },
            { data: 'location_label',  name: 'location_label',  orderable: false, searchable: false },
            { data: 'by_label',        name: 'by_label',        orderable: false, searchable: false },
        );

        const dt = $(`#${cfg.id}Table`).DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            ordering: false,
            ajax: {
                url: '{{ route('stock.movements-data') }}',
                data: function (d) {
                    d.product_id  = $(`#${cfg.id}ProductFilter`).val();
                    d.location_id = $(`#${cfg.id}LocationFilter`).val();
                    d.type        = cfg.type;
                    d.date_from   = $(`#${cfg.id}DateFrom`).val();
                    d.date_to     = $(`#${cfg.id}DateTo`).val();
                },
            },
            dom: `rt<"${cfg.id}-tail"ip>`,
            pageLength: 25,
            columns,
            language: {
                info: 'Showing _START_ to _END_ of _TOTAL_ movements',
                emptyTable: 'No stock movements recorded yet.',
                zeroRecords: 'No movements match your filters.',
                processing: '<div class="stock-loading"><span class="spinner-border spinner-border-sm"></span>Loading movements&hellip;</div>',
                paginate: { previous: '<i class="ti ti-chevron-left"></i>', next: '<i class="ti ti-chevron-right"></i>' },
            },
            initComplete: function () {
                const container = $(this.api().table().container());
                $(`#${cfg.id}InfoSlot`).append(container.find(`#${cfg.id}Table_info`));
                $(`#${cfg.id}PaginationSlot`).append(container.find(`.${cfg.id}-tail`));
            },
        });
        ledgerInstances[cfg.id] = dt;

        let searchTimer;
        $(`#${cfg.id}Search`).on('keyup', function () {
            clearTimeout(searchTimer);
            const v = this.value;
            searchTimer = setTimeout(() => dt.search(v).draw(), 250);
        });
        $(`#${cfg.id}PerPage`).on('change', function () { dt.page.len(parseInt(this.value, 10)).draw(); });
        $(`#${cfg.id}ProductFilter`).on('change', () => dt.draw());
        $(`#${cfg.id}LocationFilter, #${cfg.id}DateFrom, #${cfg.id}DateTo`).on('change', () => dt.draw());

        $(`#${cfg.id}FilterReset`).on('click', function () {
            $(`#${cfg.id}Search`).val('');
            $(`#${cfg.id}LocationFilter`).val('');
            $(`#${cfg.id}DateFrom, #${cfg.id}DateTo`).val('');
            $(`#${cfg.id}ProductFilter`).val(null).trigger('change');
            dt.search('').draw();
        });
    }

    // Stock In is the default active tab — init immediately. The other
    // two lazy-init on first show (the known hidden-tab-pane DataTables
    // zero-width-column bug).
    initLedgerTable(ledgerTabs[0]);
    document.getElementById('stockOutTab').addEventListener('shown.bs.tab', () => initLedgerTable(ledgerTabs[1]));
    document.getElementById('transferTab').addEventListener('shown.bs.tab', () => initLedgerTable(ledgerTabs[2]));
});
</script>
@endpush
