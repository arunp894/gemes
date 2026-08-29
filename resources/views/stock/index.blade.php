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

            <ul class="nav nav-tabs mb-3 stock-tabs" id="stockReportTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="onHandTab" data-bs-toggle="tab" data-bs-target="#onHandPane"
                        type="button" role="tab" aria-controls="onHandPane" aria-selected="false">
                        <i class="ti ti-stack-2 fs-sm me-1"></i> Current Stock
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="movementTab" data-bs-toggle="tab" data-bs-target="#movementPane"
                        type="button" role="tab" aria-controls="movementPane" aria-selected="true">
                        <i class="ti ti-arrows-exchange fs-sm me-1"></i> Stock Movement
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="byStoneTab" data-bs-toggle="tab" data-bs-target="#byStonePane"
                        type="button" role="tab" aria-controls="byStonePane" aria-selected="false">
                        <i class="ti ti-scale fs-sm me-1"></i> Stones &amp; Carat
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="stockReportTabContent">

                {{-- ═══════════════════════ STOCK MOVEMENT ═══════════════════════
                     One unified ledger for every inventory change — purchases,
                     sales, transfers, and adjustments all in one place, each with
                     a clear reference number and an unmistakable In/Out label.
                     Detailed sales analysis (revenue, margins, etc.) stays in the
                     Sales module; a sale only ever appears here as a movement. ── --}}
                <div class="tab-pane fade show active" id="movementPane" role="tabpanel" aria-labelledby="movementTab">
                    <div class="card">
                        <div class="card-body pb-0">
                            <h5 class="card-title mb-0">Stock Movement</h5>
                            <p class="text-muted fs-sm mb-3">Track every change made to your inventory in one place</p>

                            <div class="movement-pills mb-3" id="movementPills">
                                <button type="button" class="movement-pill active" data-type="">All Movements</button>
                                <button type="button" class="movement-pill movement-pill-success" data-type="in"><i class="ti ti-arrow-down"></i> Received</button>
                                <button type="button" class="movement-pill movement-pill-danger" data-type="out"><i class="ti ti-arrow-up"></i> Removed</button>
                                <button type="button" class="movement-pill movement-pill-info" data-type="transfer"><i class="ti ti-arrows-exchange"></i> Transfer</button>
                                <button type="button" class="movement-pill movement-pill-purple" data-type="sale"><i class="ti ti-shopping-cart"></i> Sale</button>
                                <button type="button" class="movement-pill movement-pill-success" data-type="return"><i class="ti ti-arrow-back-up"></i> Return</button>
                                <button type="button" class="movement-pill movement-pill-warning" data-type="adjustment"><i class="ti ti-adjustments"></i> Adjustment</button>
                            </div>
                        </div>

                        <div class="card-header border-light movement-toolbar flex-wrap gap-2">
                            <div class="d-flex flex-wrap align-items-center gap-1">
                                <input type="date" id="movementDateFrom" class="form-control form-control-sm" title="From date" style="width: 145px;">
                                <span class="text-muted small">to</span>
                                <input type="date" id="movementDateTo" class="form-control form-control-sm" title="To date" style="width: 145px;">

                                <select id="movementProductFilter" class="form-select" style="min-width: 180px;">
                                    <option value="">All Products</option>
                                </select>

                                <div class="app-search">
                                    <select id="movementLocationFilter" class="form-select form-control my-1 my-md-0">
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
                                    <input id="movementSearch" type="search" class="form-control" placeholder="Search product, SKU, or reference…" style="min-width: 200px;" />
                                    <i class="ti ti-search app-search-icon text-muted"></i>
                                </div>
                                <button type="button" id="movementFilterReset" class="btn btn-outline-secondary btn-sm" title="Clear filters">
                                    <i class="ti ti-filter-x"></i>
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="movementTable" class="table table-custom table-centered table-hover w-100 mb-0">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th class="text-center" style="width: 1%;">S.No</th>
                                        <th><i class="ti ti-calendar me-1"></i>Date &amp; Time</th>
                                        <th><i class="ti ti-diamond me-1"></i>Product</th>
                                        <th><i class="ti ti-arrows-exchange me-1"></i>Movement Type</th>
                                        <th class="text-end"><i class="ti ti-arrow-down me-1"></i>Stock In</th>
                                        <th class="text-end"><i class="ti ti-arrow-up me-1"></i>Stock Out</th>
                                        <th><i class="ti ti-hash me-1"></i>Reference No.</th>
                                        <th><i class="ti ti-building-warehouse me-1"></i>Source / Destination</th>
                                        <th><i class="ti ti-map-pin me-1"></i>Location</th>
                                        <th><i class="ti ti-user me-1"></i>User</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>

                        <div class="card-footer border-0">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div id="movementInfoSlot" class="text-muted small"></div>
                                <div class="d-flex align-items-center gap-2 footer-pagination-group">
                                    <select id="movementPerPage" class="form-select form-select-sm" style="width: auto;">
                                        <option value="25" selected>25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                        <option value="200">200</option>
                                    </select>
                                    <div id="movementPaginationSlot"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Bottom summary strip: today's activity by category --}}
                        <div class="movement-summary-strip">
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
                    </div>
                </div>

                {{-- ═══════════════════════ CURRENT STOCK (On Hand) ═══════════════════════ --}}
                <div class="tab-pane fade" id="onHandPane" role="tabpanel" aria-labelledby="onHandTab">
                    <div class="card" id="categoryRollupCard">
                        <div class="card-header border-light d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <i class="ti ti-diamond fs-18 text-primary"></i>
                                <h5 class="card-title mb-0">By Stone</h5>
                            </div>
                            <small class="text-muted">
                                <span id="categoryRollupCount" class="fw-semibold text-primary me-2"></span>
                                Click a stone to filter the table below
                            </small>
                        </div>
                        <div class="card-body p-0">
                            <ul class="stone-rollup-list" id="categoryRollup">
                                <li class="text-muted small px-3 py-3">Loading…</li>
                            </ul>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header border-light justify-content-between">
                            <div class="d-flex gap-2">
                                <div class="app-search">
                                    <input id="stockSearch" type="search" class="form-control" placeholder="Search product / SKU…" />
                                    <i class="ti ti-search app-search-icon text-muted"></i>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-1">
                                <div class="app-search">
                                    <select id="stockLocationFilter" class="form-select form-control my-1 my-md-0">
                                        <option value="">All Locations</option>
                                        @foreach ($locations as $l)
                                            <option value="{{ $l->id }}" @if($l->is_default) selected @endif>
                                                {{ $l->name }} ({{ $l->location_code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <i class="ti ti-map-pin app-search-icon text-muted"></i>
                                </div>

                                <div class="app-search">
                                    <select id="stockCategoryFilter" class="form-select form-control my-1 my-md-0">
                                        <option value="">All Stones</option>
                                        @foreach ($categories as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                    <i class="ti ti-tag app-search-icon text-muted"></i>
                                </div>

                                @permission('stock-transfers.create')
                                <a href="{{ route('stock-transfers.create') }}" class="add-btn ms-1">
                                    <i class="ti ti-transfer fs-sm me-2"></i> New Transfer
                                </a>
                                @endpermission
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="stockTable" class="table table-custom table-centered table-hover w-100 mb-0">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th class="text-center" style="width: 1%;">S.No</th>
                                        <th><i class="ti ti-diamond me-1"></i>Product</th>
                                        <th><i class="ti ti-map-pin me-1"></i>Location</th>
                                        <th class="text-end"><i class="ti ti-stack-2 me-1"></i>On Hand</th>
                                        <th class="text-end"><i class="ti ti-scale me-1"></i>Remaining Ct</th>
                                        <th class="text-center" style="width: 1%;"><i class="ti ti-history me-1"></i>Ledger</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>

                        <div class="card-footer border-0">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div id="stockInfoSlot" class="text-muted small"></div>
                                <div class="d-flex align-items-center gap-2 footer-pagination-group">
                                    <select id="stockPerPage" class="form-select form-select-sm" style="width: auto;">
                                        <option value="10">10</option>
                                        <option value="25" selected>25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                    <div id="stockPaginationSlot"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

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
                            <table id="byStoneTable" class="table table-custom table-centered table-hover w-100 mb-0">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th class="text-center" style="width: 1%;">S.No</th>
                                        <th><i class="ti ti-diamond me-1"></i>Stone Name</th>
                                        <th class="text-end"><i class="ti ti-stack-2 me-1"></i>Pieces</th>
                                        <th class="text-end"><i class="ti ti-scale me-1"></i>Carat Weight</th>
                                        <th class="text-end">Rate / Ct (Avg.)</th>
                                        <th class="text-end">Stock Value</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center" style="width: 1%;">Action</th>
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
                    <a href="#" onclick="document.getElementById('onHandTab').click(); return false;" class="fs-sm">View All</a>
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

    /* Category rollup cards */
    .stone-rollup-list { list-style: none; margin: 0; padding: 0; }
    .stone-rollup-list .stone-rollup-row {
        display: flex; align-items: center; gap: 10px; padding: 10px 16px;
        border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background .15s ease;
    }
    .stone-rollup-list .stone-rollup-row:last-child { border-bottom: none; }
    .stone-rollup-list .stone-rollup-row:hover { background: #f8fafc; }
    .stone-rollup-list .stone-rollup-row.active {
        background: var(--stock-primary-light); border-left: 3px solid var(--stock-primary); padding-left: 13px;
    }
    .stock-page #categoryRollup.rollup-scroll { max-height: 340px; overflow-y: auto; }

    /* Movement type pill filters */
    .movement-pills { display: flex; flex-wrap: wrap; gap: 8px; }
    .movement-pill {
        display: inline-flex; align-items: center; gap: 6px;
        border: 1px solid var(--stock-border); background: #fff; color: #475569;
        border-radius: 999px; padding: 7px 16px; font-size: 0.8125rem; font-weight: 600;
        transition: all .15s ease;
    }
    .movement-pill:hover { border-color: var(--stock-primary); }
    .movement-pill.active { background: var(--stock-primary); border-color: var(--stock-primary); color: #fff; }
    .movement-pill.active i { color: #fff; }
    .movement-pill-success i { color: var(--stock-success); }
    .movement-pill-danger i { color: var(--stock-danger); }
    .movement-pill-info i { color: var(--stock-teal); }
    .movement-pill-purple i { color: var(--stock-purple); }
    .movement-pill-warning i { color: var(--stock-warning); }

    /* Tables */
    .stock-page #stockTable thead th,
    .stock-page #movementTable thead th {
        background: #f1f5f9; font-weight: 700; font-size: 0.6875rem; letter-spacing: 0.03em; padding: 8px 12px;
    }
    .stock-page #stockTable tbody td,
    .stock-page #movementTable tbody td { padding: 6px 12px; font-size: 0.8125rem; vertical-align: middle; }
    .stock-page #stockTable tbody tr:hover,
    .stock-page #movementTable tbody tr:hover { background: #f8fafc; }
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
    .stock-page #movementTable .badge,
    .stock-page #movementTable small { font-size: 0.75rem; }

    /* Numeric/date columns: tabular figures so digits line up vertically
       from row to row instead of drifting with proportional widths. */
    .stock-page #movementTable .movement-qty,
    .stock-page #movementTable .movement-date,
    .stock-page #movementTable .movement-time {
        font-variant-numeric: tabular-nums;
        font-feature-settings: "tnum";
    }
    .stock-page #movementTable .movement-qty { display: inline-block; min-width: 2.5em; text-align: right; }
    .badge-soft-purple { background: #ede9fe; color: var(--stock-purple); }

    /* Stock Movement — sticky toolbar/header while scrolling long results */
    .stock-page .movement-toolbar { position: sticky; top: 65px; z-index: 5; background: #fff; display: flex; align-items: center; }
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

    #stockTable_wrapper .dataTables_length, #stockTable_wrapper .dataTables_filter { display: none !important; }
    #stockInfoSlot .dataTables_info { padding: 0; font-size: 0.875rem; }
    #movementTable_wrapper .dataTables_length, #movementTable_wrapper .dataTables_filter { display: none !important; }
    #movementInfoSlot .dataTables_info { padding: 0; font-size: 0.875rem; }
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

    // ═══════════════════ Current Stock (On Hand) tab ═══════════════════
    // Lazy-init on first show — this tab is no longer the default active
    // one (Stock Movement is), so initializing DataTables against a
    // hidden .tab-pane here would render it with zero column widths.
    let onHandDt = null;

    function initOnHandTable() {
        if (onHandDt) return;

        onHandDt = $('#stockTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            order: [[3, 'desc']],
            ajax: {
                url: '{{ route('stock.data') }}',
                data: function (d) {
                    d.location_id = $('#stockLocationFilter').val();
                    d.category_id = $('#stockCategoryFilter').val();
                },
            },
            dom: 'rt<"stock-tail"ip>',
            pageLength: 25,
            columns: [
                { data: 'DT_RowIndex',    name: 'DT_RowIndex',     orderable: false, searchable: false, className: 'text-center' },
                { data: 'product_label',  name: 'product_label',  orderable: false },
                { data: 'location_label', name: 'locations.name',  orderable: false, searchable: false },
                { data: 'on_hand',        name: 'on_hand',         orderable: true,  searchable: false, className: 'text-end' },
                { data: 'remaining_ct',   name: 'remaining_ct',    orderable: false, searchable: false, className: 'text-end' },
                { data: 'action',         name: 'action',          orderable: false, searchable: false, className: 'text-center' },
            ],
            language: {
                info: 'Showing _START_ to _END_ of _TOTAL_ rows',
                emptyTable: 'No stock recorded yet.',
                processing: '<div class="stock-loading"><span class="spinner-border spinner-border-sm"></span>Loading stock&hellip;</div>',
                paginate: { previous: '<i class="ti ti-chevron-left"></i>', next: '<i class="ti ti-chevron-right"></i>' },
            },
            initComplete: function () {
                $('#stockInfoSlot').append($('#stockTable_info'));
                $('#stockPaginationSlot').append($('.stock-tail'));
            },
        });

        let timer;
        $('#stockSearch').on('keyup', function () {
            clearTimeout(timer);
            const v = this.value;
            timer = setTimeout(() => onHandDt.search(v).draw(), 250);
        });
        $('#stockPerPage').on('change', function () { onHandDt.page.len(parseInt(this.value, 10)).draw(); });
        $('#stockLocationFilter').on('change', function () {
            onHandDt.draw();
            loadCategoryRollup();
        });
        $('#stockCategoryFilter').on('change', function () {
            onHandDt.draw();
            highlightActiveCategory();
        });

        loadCategoryRollup();
    }

    function highlightActiveCategory() {
        const active = $('#stockCategoryFilter').val();
        $('#categoryRollup .stone-rollup-row').each(function () {
            $(this).toggleClass('active', String($(this).data('categoryId')) === String(active) && active !== '');
        });
    }

    function loadCategoryRollup() {
        const params = new URLSearchParams();
        const loc = $('#stockLocationFilter').val();
        if (loc) params.set('location_id', loc);

        fetch(`{{ route('stock.category-data') }}?${params.toString()}`, { headers: { 'Accept': 'application/json' } })
            .then((r) => r.json())
            .then((data) => {
                const wrap = $('#categoryRollup').empty();
                if (!data.ok || !data.categories || data.categories.length === 0) {
                    wrap.append('<li class="text-muted small px-3 py-3">No stock recorded yet.</li>');
                    $('#categoryRollupCount').text('');
                    wrap.removeClass('rollup-scroll');
                    return;
                }

                const isLarge = data.categories.length > 12;
                wrap.toggleClass('rollup-scroll', isLarge);
                $('#categoryRollupCount').text(`${data.categories.length} stone${data.categories.length === 1 ? '' : 's'}`);

                data.categories.forEach((c) => {
                    const safeName = $('<div>').text(c.category_name).html();
                    const ct = Number(c.on_hand_carats);
                    const row = $(`
                        <li class="stone-rollup-row" role="button" data-category-id="${c.category_id}">
                            <span class="movement-thumb movement-thumb-sm"><i class="ti ti-diamond"></i></span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold fs-sm text-truncate" title="${safeName}">${safeName}</div>
                                <small class="text-muted">${c.product_count} item${c.product_count == 1 ? '' : 's'}${ct > 0 ? ' &middot; ' + ct.toFixed(3).replace(/\.?0+$/, '') + ' ct' : ''}</small>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <div class="fw-semibold movement-qty">${c.on_hand}</div>
                                <small class="text-muted">Pcs</small>
                            </div>
                        </li>
                    `);
                    row.on('click', function () {
                        $('#stockCategoryFilter').val(c.category_id).trigger('change');
                    });
                    wrap.append(row);
                });
                highlightActiveCategory();
            })
            .catch(() => {
                $('#categoryRollup').html('<li class="text-muted small px-3 py-3">Could not load category totals.</li>');
            });
    }

    document.getElementById('onHandTab').addEventListener('shown.bs.tab', initOnHandTable);

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
                { data: 'action',        name: 'action',         orderable: false, searchable: false, className: 'text-center' },
            ],
            language: {
                info: 'Showing _START_ to _END_ of _TOTAL_ rows',
                emptyTable: 'No stock recorded yet.',
                processing: '<div class="stock-loading"><span class="spinner-border spinner-border-sm"></span>Loading stones&hellip;</div>',
                paginate: { previous: '<i class="ti ti-chevron-left"></i>', next: '<i class="ti ti-chevron-right"></i>' },
            },
            initComplete: function () {
                $('#byStoneInfoSlot').append($('#byStoneTable_info'));
                $('#byStonePaginationSlot').append($('.stock-tail'));
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

        $('#byStoneTable').on('click', '.js-view-stone', function () {
            const categoryId = $(this).data('category-id');
            document.getElementById('onHandTab').click();
            setTimeout(() => {
                $('#stockCategoryFilter').val(categoryId).trigger('change');
            }, 150);
        });
    }

    document.getElementById('byStoneTab').addEventListener('shown.bs.tab', initByStoneTable);

    // ═══════════════════ Stock Movement tab ═══════════════════
    let movementType = '';

    $('#movementProductFilter').select2({
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

    const movementDt = $('#movementTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: false,
        ordering: false,
        ajax: {
            url: '{{ route('stock.movements-data') }}',
            data: function (d) {
                d.product_id  = $('#movementProductFilter').val();
                d.location_id = $('#movementLocationFilter').val();
                d.type        = movementType;
                d.date_from   = $('#movementDateFrom').val();
                d.date_to     = $('#movementDateTo').val();
            },
        },
        dom: 'rt<"movement-tail"ip>',
        pageLength: 25,
        columns: [
            { data: 'DT_RowIndex',      name: 'DT_RowIndex',      orderable: false, searchable: false, className: 'text-center' },
            { data: 'when_label',       name: 'when_label',       orderable: false, searchable: false },
            { data: 'product_label',    name: 'product_label',    orderable: false },
            { data: 'movement_label',   name: 'movement_label',   orderable: false, searchable: false },
            { data: 'stock_in_label',   name: 'stock_in_label',   orderable: false, searchable: false, className: 'text-end' },
            { data: 'stock_out_label',  name: 'stock_out_label',  orderable: false, searchable: false, className: 'text-end' },
            { data: 'reference_label',  name: 'reference_label',  orderable: false, searchable: false },
            { data: 'source_label',     name: 'source_label',     orderable: false, searchable: false },
            { data: 'location_label',   name: 'location_label',   orderable: false, searchable: false },
            { data: 'by_label',         name: 'by_label',         orderable: false, searchable: false },
        ],
        language: {
            info: 'Showing _START_ to _END_ of _TOTAL_ movements',
            emptyTable: 'No stock movements recorded yet.',
            zeroRecords: 'No movements match your filters.',
            processing: '<div class="stock-loading"><span class="spinner-border spinner-border-sm"></span>Loading movements&hellip;</div>',
            paginate: { previous: '<i class="ti ti-chevron-left"></i>', next: '<i class="ti ti-chevron-right"></i>' },
        },
        initComplete: function () {
            $('#movementInfoSlot').append($('#movementTable_info'));
            $('#movementPaginationSlot').append($('.movement-tail'));
        },
    });

    $('#movementPills').on('click', '.movement-pill', function () {
        $('.movement-pill').removeClass('active');
        $(this).addClass('active');
        movementType = $(this).data('type') || '';
        movementDt.draw();
    });

    let moveTimer;
    $('#movementSearch').on('keyup', function () {
        clearTimeout(moveTimer);
        const v = this.value;
        moveTimer = setTimeout(() => movementDt.search(v).draw(), 250);
    });
    $('#movementPerPage').on('change', function () { movementDt.page.len(parseInt(this.value, 10)).draw(); });
    $('#movementProductFilter').on('change', () => movementDt.draw());
    $('#movementLocationFilter, #movementDateFrom, #movementDateTo').on('change', () => movementDt.draw());

    $('#movementFilterReset').on('click', function () {
        $('#movementSearch').val('');
        $('#movementLocationFilter').val('');
        $('#movementDateFrom, #movementDateTo').val('');
        $('#movementProductFilter').val(null).trigger('change');
        $('.movement-pill').removeClass('active');
        $('.movement-pill[data-type=""]').addClass('active');
        movementType = '';
        movementDt.search('').draw();
    });
});
</script>
@endpush
