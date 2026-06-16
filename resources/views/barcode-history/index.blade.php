@extends('layout.app')

@section('content')
<div class="container-fluid" id="barcodeHistoryApp">

    {{-- ── Page Title ───────────────────────────────────────────────────── --}}
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('stock.index') }}">Inventory</a></li>
                        <li class="breadcrumb-item active">Barcode History</li>
                    </ol>
                </div>
                <h4 class="page-title">
                    <i class="ti ti-barcode me-2 text-primary"></i>Barcode Product History
                </h4>
            </div>
        </div>
    </div>

    {{-- ── Scanner Card ─────────────────────────────────────────────────── --}}
    <div class="row justify-content-center mb-4">
        <div class="col-lg-6 col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="text-center mb-3">
                        <div class="avatar-lg bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-2">
                            <i class="ti ti-scan fs-1 text-primary"></i>
                        </div>
                        <h5 class="mb-1 fw-bold">Scan or Enter a Barcode</h5>
                        <p class="text-muted small mb-0">
                            Point your scanner here, or type the barcode value manually and press Enter
                        </p>
                    </div>
                    <div class="input-group input-group-lg shadow-sm">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="ti ti-barcode text-muted"></i>
                        </span>
                        <input
                            type="text"
                            class="form-control border-start-0 text-center fw-semibold font-monospace fs-5"
                            placeholder="e.g. 2000000000017"
                            ref="barcodeInput"
                            v-model="barcodeInput"
                            @keyup.enter="search"
                            :disabled="loading"
                            autocomplete="off"
                            spellcheck="false"
                        >
                        <button
                            class="btn btn-primary px-4 fw-semibold"
                            @click="search"
                            :disabled="loading || !barcodeInput.trim()">
                            <span v-if="loading" class="d-flex align-items-center gap-2">
                                <span class="spinner-border spinner-border-sm"></span> Searching…
                            </span>
                            <span v-else>
                                <i class="ti ti-search me-1"></i>Search
                            </span>
                        </button>
                        <button
                            class="btn btn-outline-secondary"
                            v-if="result || error"
                            @click="clearResult"
                            title="Clear and search again">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <div class="text-center mt-2">
                        <small class="text-muted">
                            <i class="ti ti-info-circle me-1"></i>
                            Scanned barcodes auto-submit. Supports EAN-13, EAN-8, UPC-A, Code 128, QR Code &amp; Custom formats.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Loading State ────────────────────────────────────────────────── --}}
    <div class="row justify-content-center" v-if="loading">
        <div class="col-auto">
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status" style="width:3rem;height:3rem;border-width:.3em;"></div>
                <p class="text-muted mt-3 mb-0 fw-semibold">Looking up product history…</p>
                <small class="text-muted">Checking purchases, sales &amp; stock movements</small>
            </div>
        </div>
    </div>

    {{-- ── Error State ──────────────────────────────────────────────────── --}}
    <div class="row justify-content-center" v-if="error && !loading">
        <div class="col-lg-6 col-md-8">
            <div class="alert alert-danger border-0 shadow-sm d-flex align-items-start gap-3">
                <i class="ti ti-alert-triangle fs-2 flex-shrink-0 mt-1"></i>
                <div>
                    <strong class="d-block mb-1">Barcode Not Found</strong>
                    <span>@{{ error }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Results ──────────────────────────────────────────────────────── --}}
    <template v-if="result && !loading">

        {{-- Product Banner ──────────────────────────────────────────────── --}}
        <div class="row mb-3">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-start gap-3 flex-wrap">

                            {{-- Thumbnail --}}
                            <div class="flex-shrink-0">
                                <img v-if="result.product.thumb_url"
                                     :src="result.product.thumb_url"
                                     class="rounded border"
                                     style="width:90px;height:90px;object-fit:cover;">
                                <div v-else
                                     class="rounded border bg-light d-flex align-items-center justify-content-center"
                                     style="width:90px;height:90px;">
                                    <i class="ti ti-photo text-muted" style="font-size:2rem;"></i>
                                </div>
                            </div>

                            {{-- Product Info --}}
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <h5 class="mb-0 fw-bold">@{{ result.product.title }}</h5>
                                    <span class="badge" :class="result.product.status_class">@{{ result.product.status }}</span>
                                    <span class="badge badge-soft-info" v-if="result.product.website_enabled">
                                        <i class="ti ti-world me-1"></i>Online
                                    </span>
                                    <span class="badge badge-soft-warning" v-if="result.product.is_gemstone">
                                        <i class="ti ti-diamond me-1"></i>Gemstone
                                    </span>
                                </div>
                                <div class="text-muted small mb-1">
                                    <i class="ti ti-tag me-1"></i>
                                    SKU: <strong class="text-dark font-monospace">@{{ result.product.sku }}</strong>
                                    &nbsp;&bull;&nbsp;
                                    <i class="ti ti-folder me-1"></i>@{{ result.product.category_path }}
                                </div>
                                <div class="text-muted small">
                                    <i class="ti ti-barcode me-1"></i>
                                    <span class="font-monospace text-dark">@{{ result.barcode.value }}</span>
                                    <span class="badge bg-secondary ms-2">@{{ result.barcode.format }}</span>
                                    <span class="badge bg-primary ms-1" v-if="result.barcode.is_primary">Primary</span>
                                    <span class="badge bg-light text-muted border ms-1" v-if="result.barcode.label">
                                        @{{ result.barcode.label }}
                                    </span>
                                </div>
                            </div>

                            {{-- View Product Link --}}
                            <div class="flex-shrink-0 ms-auto align-self-center">
                                <a :href="result.product.url"
                                   class="btn btn-sm btn-outline-primary"
                                   target="_blank">
                                    <i class="ti ti-external-link me-1"></i>View Product
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPI Cards ───────────────────────────────────────────────────── --}}
        <div class="row g-3 mb-3">

            {{-- Purchases --}}
            <div class="col-6 col-md-3">
                <div class="card h-100 border-0 text-white"
                     style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%)">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="small fw-semibold opacity-75">Purchase Orders</span>
                            <i class="ti ti-truck-delivery opacity-40" style="font-size:1.75rem;"></i>
                        </div>
                        <div class="fw-bold mb-1" style="font-size:2.2rem;line-height:1;">
                            @{{ result.summary.purchase_count }}
                        </div>
                        <div class="small opacity-75">
                            @{{ result.summary.total_purchased_qty }} units &bull;
                            ₹@{{ fmt(result.summary.total_purchased_value) }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sales --}}
            <div class="col-6 col-md-3">
                <div class="card h-100 border-0 text-white"
                     style="background:linear-gradient(135deg,#f5576c 0%,#f093fb 100%)">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="small fw-semibold opacity-75">Sales Made</span>
                            <i class="ti ti-cash-register opacity-40" style="font-size:1.75rem;"></i>
                        </div>
                        <div class="fw-bold mb-1" style="font-size:2.2rem;line-height:1;">
                            @{{ result.summary.sale_count }}
                        </div>
                        <div class="small opacity-75">
                            @{{ result.summary.total_sold_qty }} units &bull;
                            ₹@{{ fmt(result.summary.total_sold_value) }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- On Hand --}}
            <div class="col-6 col-md-3">
                <div class="card h-100 border-0 text-white"
                     style="background:linear-gradient(135deg,#4facfe 0%,#00f2fe 100%)">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="small fw-semibold opacity-75">Available (On Hand)</span>
                            <i class="ti ti-packages opacity-40" style="font-size:1.75rem;"></i>
                        </div>
                        <div class="fw-bold mb-1" style="font-size:2.2rem;line-height:1;">
                            @{{ result.summary.on_hand_qty }}
                        </div>
                        <div class="small opacity-75">
                            IN: @{{ result.summary.in_qty }} &bull; OUT: @{{ result.summary.out_qty }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Gemstone carats OR net value --}}
            <div class="col-6 col-md-3">
                <div class="card h-100 border-0 text-white"
                     style="background:linear-gradient(135deg,#43e97b 0%,#38f9d7 100%)">
                    <div class="card-body p-3">
                        <template v-if="result.product.is_gemstone">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="small fw-semibold opacity-75">Total Carats Purchased</span>
                                <i class="ti ti-diamond opacity-40" style="font-size:1.75rem;"></i>
                            </div>
                            <div class="fw-bold mb-1" style="font-size:2.2rem;line-height:1;">
                                @{{ result.summary.total_purchased_carats }}
                            </div>
                            <div class="small opacity-75">ct gemstone total</div>
                        </template>
                        <template v-else>
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="small fw-semibold opacity-75">Net Profit / Loss</span>
                                <i class="ti ti-chart-line opacity-40" style="font-size:1.75rem;"></i>
                            </div>
                            <div class="fw-bold mb-1" style="font-size:2.2rem;line-height:1;">
                                ₹@{{ fmt(result.summary.total_sold_value - result.summary.total_purchased_value) }}
                            </div>
                            <div class="small opacity-75">Sales ₹@{{ fmt(result.summary.total_sold_value) }} − Cost ₹@{{ fmt(result.summary.total_purchased_value) }}</div>
                        </template>
                    </div>
                </div>
            </div>

        </div>

        {{-- Detail Tabs ─────────────────────────────────────────────────── --}}
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">

                    {{-- Tab Nav --}}
                    <div class="card-header p-0 border-bottom bg-white">
                        <ul class="nav nav-tabs mb-0 px-2" role="tablist" style="border-bottom:none;">
                            <li class="nav-item">
                                <button class="nav-link py-3 px-3 fw-semibold"
                                        :class="{active: activeTab==='purchases'}"
                                        @click="activeTab='purchases'">
                                    <i class="ti ti-truck-delivery me-1"></i>
                                    Purchase History
                                    <span class="badge ms-1"
                                          :class="activeTab==='purchases' ? 'bg-primary' : 'bg-secondary'">
                                        @{{ result.purchases.length }}
                                    </span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link py-3 px-3 fw-semibold"
                                        :class="{active: activeTab==='sales'}"
                                        @click="activeTab='sales'">
                                    <i class="ti ti-receipt me-1"></i>
                                    Sales History
                                    <span class="badge ms-1"
                                          :class="activeTab==='sales' ? 'bg-primary' : 'bg-secondary'">
                                        @{{ result.sales.length }}
                                    </span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link py-3 px-3 fw-semibold"
                                        :class="{active: activeTab==='movements'}"
                                        @click="activeTab='movements'">
                                    <i class="ti ti-arrows-exchange me-1"></i>
                                    Stock Movements
                                    <span class="badge ms-1"
                                          :class="activeTab==='movements' ? 'bg-primary' : 'bg-secondary'">
                                        @{{ result.movements.length }}
                                    </span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link py-3 px-3 fw-semibold"
                                        :class="{active: activeTab==='details'}"
                                        @click="activeTab='details'">
                                    <i class="ti ti-info-circle me-1"></i>
                                    Product Details
                                </button>
                            </li>
                        </ul>
                    </div>

                    {{-- Tab Content --}}
                    <div class="card-body p-0">

                        {{-- ───────────── PURCHASE HISTORY TAB ─────────────── --}}
                        <div v-show="activeTab==='purchases'" class="p-3">
                            <div v-if="result.purchases.length === 0"
                                 class="text-center py-5 text-muted">
                                <i class="ti ti-inbox d-block mb-2" style="font-size:3rem;opacity:.3;"></i>
                                <p class="mb-0">No purchase history found for this product.</p>
                            </div>
                            <div v-else>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-muted text-center" style="width:40px">#</th>
                                                <th>Invoice No.</th>
                                                <th>Date</th>
                                                <th>Supplier</th>
                                                <th class="text-center">Type</th>
                                                <th class="text-end">Qty</th>
                                                <th class="text-end">Carats</th>
                                                <th class="text-end">Value (₹)</th>
                                                <th class="text-center">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(row, idx) in result.purchases" :key="idx">
                                                <td class="text-muted text-center small">@{{ idx + 1 }}</td>
                                                <td>
                                                    <a v-if="row.id"
                                                       :href="'/purchases/' + row.id"
                                                       class="text-decoration-none fw-semibold"
                                                       target="_blank">
                                                        <i class="ti ti-file-invoice me-1 text-muted"></i>@{{ row.invoice_number }}
                                                    </a>
                                                    <span v-else class="fw-semibold">@{{ row.invoice_number }}</span>
                                                </td>
                                                <td class="text-nowrap">
                                                    <i class="ti ti-calendar me-1 text-muted"></i>@{{ row.purchase_date }}
                                                </td>
                                                <td>
                                                    <i class="ti ti-building-store me-1 text-muted"></i>@{{ row.supplier }}
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-light text-dark border">@{{ row.type }}</span>
                                                </td>
                                                <td class="text-end fw-bold text-primary">@{{ row.qty }}</td>
                                                <td class="text-end text-muted small">
                                                    @{{ row.carats > 0 ? row.carats + ' ct' : '—' }}
                                                </td>
                                                <td class="text-end fw-semibold">₹@{{ fmt(row.total) }}</td>
                                                <td class="text-center">
                                                    <span class="badge" :class="row.status_class">@{{ row.status }}</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="table-light border-top-2">
                                            <tr class="fw-bold">
                                                <td colspan="5" class="text-end text-muted">
                                                    <i class="ti ti-calculator me-1"></i>Total (@{{ result.purchases.length }} purchases)
                                                </td>
                                                <td class="text-end text-primary">@{{ result.summary.total_purchased_qty }}</td>
                                                <td class="text-end text-muted">
                                                    @{{ result.product.is_gemstone ? result.summary.total_purchased_carats + ' ct' : '—' }}
                                                </td>
                                                <td class="text-end">₹@{{ fmt(result.summary.total_purchased_value) }}</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- ─────────────── SALES HISTORY TAB ─────────────── --}}
                        <div v-show="activeTab==='sales'" class="p-3">
                            <div v-if="result.sales.length === 0"
                                 class="text-center py-5 text-muted">
                                <i class="ti ti-inbox d-block mb-2" style="font-size:3rem;opacity:.3;"></i>
                                <p class="mb-0">No sales recorded for this product yet.</p>
                            </div>
                            <div v-else>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-muted text-center" style="width:40px">#</th>
                                                <th>Sale No.</th>
                                                <th>Date</th>
                                                <th>Customer</th>
                                                <th>Location</th>
                                                <th>Channel</th>
                                                <th class="text-end">Qty</th>
                                                <th class="text-end">Unit Price</th>
                                                <th class="text-end">Total (₹)</th>
                                                <th class="text-center">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(row, idx) in result.sales" :key="idx">
                                                <td class="text-muted text-center small">@{{ idx + 1 }}</td>
                                                <td>
                                                    <a v-if="row.id"
                                                       :href="'/sales/' + row.id"
                                                       class="text-decoration-none fw-semibold"
                                                       target="_blank">
                                                        <i class="ti ti-receipt me-1 text-muted"></i>@{{ row.sale_number }}
                                                    </a>
                                                    <span v-else class="fw-semibold">@{{ row.sale_number }}</span>
                                                </td>
                                                <td class="text-nowrap">
                                                    <i class="ti ti-calendar me-1 text-muted"></i>@{{ row.sale_date }}
                                                </td>
                                                <td>
                                                    <i class="ti ti-user me-1 text-muted"></i>@{{ row.customer }}
                                                </td>
                                                <td class="text-muted">@{{ row.location }}</td>
                                                <td class="text-muted">@{{ row.channel }}</td>
                                                <td class="text-end fw-bold text-danger">@{{ row.qty }}</td>
                                                <td class="text-end text-muted">₹@{{ fmt(row.unit_price) }}</td>
                                                <td class="text-end fw-semibold">₹@{{ fmt(row.total) }}</td>
                                                <td class="text-center">
                                                    <span class="badge" :class="row.status_class">@{{ row.status }}</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="table-light border-top-2">
                                            <tr class="fw-bold">
                                                <td colspan="6" class="text-end text-muted">
                                                    <i class="ti ti-calculator me-1"></i>Total (@{{ result.sales.length }} sales)
                                                </td>
                                                <td class="text-end text-danger">@{{ result.summary.total_sold_qty }}</td>
                                                <td></td>
                                                <td class="text-end">₹@{{ fmt(result.summary.total_sold_value) }}</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- ──────────── STOCK MOVEMENTS TAB ───────────────── --}}
                        <div v-show="activeTab==='movements'" class="p-3">
                            <div v-if="result.movements.length === 0"
                                 class="text-center py-5 text-muted">
                                <i class="ti ti-inbox d-block mb-2" style="font-size:3rem;opacity:.3;"></i>
                                <p class="mb-0">No stock movements recorded for this product.</p>
                            </div>
                            <div v-else>
                                {{-- Running balance chips --}}
                                <div class="d-flex gap-2 mb-3 flex-wrap">
                                    <span class="badge badge-soft-success fs-sm py-2 px-3">
                                        <i class="ti ti-arrow-narrow-down me-1"></i>
                                        Total IN: <strong>@{{ result.summary.in_qty }}</strong>
                                    </span>
                                    <span class="badge badge-soft-danger fs-sm py-2 px-3">
                                        <i class="ti ti-arrow-narrow-up me-1"></i>
                                        Total OUT: <strong>@{{ result.summary.out_qty }}</strong>
                                    </span>
                                    <span class="badge bg-primary fs-sm py-2 px-3">
                                        <i class="ti ti-packages me-1"></i>
                                        Balance (On Hand): <strong>@{{ result.summary.on_hand_qty }}</strong>
                                    </span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-muted text-center" style="width:40px">#</th>
                                                <th>Date</th>
                                                <th class="text-center">Direction</th>
                                                <th class="text-end">Qty</th>
                                                <th>Reason</th>
                                                <th>Location</th>
                                                <th>Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(row, idx) in result.movements" :key="idx">
                                                <td class="text-muted text-center small">@{{ idx + 1 }}</td>
                                                <td class="text-nowrap">@{{ row.date }}</td>
                                                <td class="text-center">
                                                    <span class="badge"
                                                          :class="row.direction === 'IN' ? 'badge-soft-success' : 'badge-soft-danger'">
                                                        <i class="ti me-1"
                                                           :class="row.direction === 'IN' ? 'ti-arrow-narrow-down' : 'ti-arrow-narrow-up'"></i>
                                                        @{{ row.direction }}
                                                    </span>
                                                </td>
                                                <td class="text-end fw-bold"
                                                    :class="row.direction === 'IN' ? 'text-success' : 'text-danger'">
                                                    @{{ row.direction === 'IN' ? '+' : '-' }}@{{ row.qty }}
                                                </td>
                                                <td>
                                                    <span class="badge" :class="row.reason_class">@{{ row.reason }}</span>
                                                </td>
                                                <td class="text-muted small">@{{ row.location }}</td>
                                                <td class="text-muted small">@{{ row.notes || '—' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- ──────────── PRODUCT DETAILS TAB ───────────────── --}}
                        <div v-show="activeTab==='details'" class="p-4">
                            <div class="row g-4">

                                {{-- General Info --}}
                                <div class="col-md-6">
                                    <h6 class="text-uppercase text-muted fw-bold mb-3 fs-xs border-bottom pb-2">
                                        <i class="ti ti-info-circle me-1"></i>General Information
                                    </h6>
                                    <table class="table table-borderless table-sm align-middle">
                                        <tbody>
                                            <tr>
                                                <td class="text-muted ps-0" style="width:45%">Product ID</td>
                                                <td class="fw-semibold">#@{{ result.product.id }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted ps-0">SKU</td>
                                                <td class="fw-semibold font-monospace">@{{ result.product.sku }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted ps-0">Category</td>
                                                <td>@{{ result.product.category_path }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted ps-0">Status</td>
                                                <td>
                                                    <span class="badge" :class="result.product.status_class">
                                                        @{{ result.product.status }}
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted ps-0">Country of Origin</td>
                                                <td>@{{ result.product.country_origin || '—' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted ps-0">Website Visibility</td>
                                                <td>
                                                    <span class="badge"
                                                          :class="result.product.website_enabled ? 'badge-soft-success' : 'badge-soft-secondary'">
                                                        @{{ result.product.website_enabled ? 'Enabled' : 'Disabled' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Gemstone Attributes --}}
                                <div class="col-md-6" v-if="result.product.is_gemstone">
                                    <h6 class="text-uppercase text-muted fw-bold mb-3 fs-xs border-bottom pb-2">
                                        <i class="ti ti-diamond me-1 text-warning"></i>Gemstone Attributes
                                    </h6>
                                    <table class="table table-borderless table-sm align-middle">
                                        <tbody>
                                            <tr>
                                                <td class="text-muted ps-0" style="width:45%">Stone Type</td>
                                                <td class="fw-semibold">@{{ result.product.stone_type || '—' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted ps-0">Carat Weight</td>
                                                <td class="fw-semibold">
                                                    @{{ result.product.carat_weight ? result.product.carat_weight + ' ct' : '—' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted ps-0">Colour Grade</td>
                                                <td>@{{ result.product.colour_grade || '—' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted ps-0">Clarity Grade</td>
                                                <td>@{{ result.product.clarity_grade || '—' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted ps-0">Cut / Shape</td>
                                                <td>@{{ result.product.cut_shape || '—' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted ps-0">Treatment</td>
                                                <td>@{{ result.product.treatment || '—' }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted ps-0">Certificate No.</td>
                                                <td class="font-monospace">@{{ result.product.certificate_no || '—' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Non-gemstone placeholder --}}
                                <div class="col-md-6 d-flex align-items-center" v-if="!result.product.is_gemstone">
                                    <div class="text-center text-muted w-100 py-4">
                                        <i class="ti ti-circle-off d-block mb-2"
                                           style="font-size:2.5rem;opacity:.3;"></i>
                                        Not a gemstone product — no gemstone attributes.
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>{{-- /card-body --}}
                </div>{{-- /card --}}
            </div>
        </div>

    </template>

</div>{{-- /#barcodeHistoryApp --}}
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    new Vue({
        el: '#barcodeHistoryApp',

        data: function () {
            return {
                barcodeInput: '',
                loading:      false,
                error:        null,
                result:       null,
                activeTab:    'purchases',
            };
        },

        methods: {
            /**
             * Hit the lookup endpoint and populate result or error.
             */
            search: function () {
                var self    = this;
                var barcode = this.barcodeInput.trim();
                if (!barcode) return;

                this.loading = true;
                this.error   = null;
                this.result  = null;

                fetch('/barcode-history/lookup?barcode=' + encodeURIComponent(barcode), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept':           'application/json',
                    },
                })
                .then(function (res) {
                    if (!res.ok) throw new Error('Server error: ' + res.status);
                    return res.json();
                })
                .then(function (data) {
                    if (data.found) {
                        self.result    = data;
                        self.activeTab = 'purchases';
                    } else {
                        self.error = data.message || 'Barcode not found.';
                    }
                })
                .catch(function (e) {
                    self.error = 'Failed to look up barcode. ' + (e.message || 'Please try again.');
                })
                .finally(function () {
                    self.loading = false;
                });
            },

            /**
             * Reset state and refocus the scanner input.
             */
            clearResult: function () {
                var self = this;
                this.result       = null;
                this.error        = null;
                this.barcodeInput = '';
                this.$nextTick(function () {
                    if (self.$refs.barcodeInput) {
                        self.$refs.barcodeInput.focus();
                    }
                });
            },

            /**
             * Format a number as Indian currency (no ₹ symbol — caller adds it).
             */
            fmt: function (val) {
                var n = parseFloat(val) || 0;
                return n.toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            },
        },

        mounted: function () {
            var self = this;
            this.$nextTick(function () {
                if (self.$refs.barcodeInput) {
                    self.$refs.barcodeInput.focus();
                }
            });
        },
    });
})();
</script>
@endpush
