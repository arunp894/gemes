@extends('layout.app')

@section('title', 'Stock Report')

@section('content')

<div class="container-fluid stock-page">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Stock Report</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Inventory</a></li>
                <li class="breadcrumb-item active">Stock</li>
            </ol>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3 stock-tabs" id="stockReportTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="onHandTab" data-bs-toggle="tab" data-bs-target="#onHandPane"
                type="button" role="tab" aria-controls="onHandPane" aria-selected="true">
                <i class="ti ti-stack-2 fs-sm me-1"></i> On Hand
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="salesReportTab" data-bs-toggle="tab" data-bs-target="#salesReportPane"
                type="button" role="tab" aria-controls="salesReportPane" aria-selected="false">
                <i class="ti ti-receipt fs-sm me-1"></i> Sales Report
            </button>
        </li>
    </ul>

    <div class="tab-content" id="stockReportTabContent">

        {{-- ═══════════════════════ ON HAND ═══════════════════════ --}}
        <div class="tab-pane fade show active" id="onHandPane" role="tabpanel" aria-labelledby="onHandTab">
            <div class="row">
                <div class="col-12">

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
                        <div class="card-body">
                            <div class="row g-2" id="categoryRollup">
                                <div class="col-12 text-muted small">Loading…</div>
                            </div>
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
            </div>
        </div>

        {{-- ═══════════════════════ SALES REPORT ═══════════════════════ --}}
        <div class="tab-pane fade" id="salesReportPane" role="tabpanel" aria-labelledby="salesReportTab">
            <div class="row">
                <div class="col-12">

                    <div class="stat-cards-row mb-3">
                        <div class="stat-card">
                            <div class="stat-icon stat-icon-danger"><i class="ti ti-package-export"></i></div>
                            <div class="stat-body">
                                <div class="stat-value" id="salesKpiQty">—</div>
                                <div class="stat-label">Qty Sold</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon stat-icon-primary"><i class="ti ti-box"></i></div>
                            <div class="stat-body">
                                <div class="stat-value" id="salesKpiProducts">—</div>
                                <div class="stat-label">Products Sold</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon stat-icon-warning"><i class="ti ti-receipt"></i></div>
                            <div class="stat-body">
                                <div class="stat-value" id="salesKpiCount">—</div>
                                <div class="stat-label">Sales</div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header border-light justify-content-between">
                            <div class="d-flex gap-2">
                                <div class="app-search">
                                    <input id="salesSearch" type="search" class="form-control" placeholder="Search product / SKU…" />
                                    <i class="ti ti-search app-search-icon text-muted"></i>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-1">
                                <div class="app-search">
                                    <select id="salesLocationFilter" class="form-select form-control my-1 my-md-0">
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
                                    <select id="salesCategoryFilter" class="form-select form-control my-1 my-md-0">
                                        <option value="">All Stones</option>
                                        @foreach ($categories as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                    <i class="ti ti-tag app-search-icon text-muted"></i>
                                </div>

                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="salesReportTable" class="table table-custom table-centered table-hover w-100 mb-0">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th class="text-center" style="width: 1%;">S.No</th>
                                        <th><i class="ti ti-diamond me-1"></i>Product</th>
                                        <th><i class="ti ti-map-pin me-1"></i>Location</th>
                                        <th class="text-end"><i class="ti ti-package-export me-1"></i>Qty Sold</th>
                                        <th class="text-end"><i class="ti ti-cash me-1"></i>Sales</th>
                                        <th><i class="ti ti-calendar me-1"></i>Last Sale</th>
                                        <th class="text-center" style="width: 1%;"><i class="ti ti-history me-1"></i>Ledger</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>

                        <div class="card-footer border-0">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div id="salesInfoSlot" class="text-muted small"></div>
                                <div class="d-flex align-items-center gap-2 footer-pagination-group">
                                    <select id="salesPerPage" class="form-select form-select-sm" style="width: auto;">
                                        <option value="10">10</option>
                                        <option value="25" selected>25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                    <div id="salesPaginationSlot"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

@endsection

@push('styles')
<style>
    /* ==========================================================
       Stock Report page — compact ERP styling
       Scoped under .stock-page so nothing here leaks into other
       pages that share the same layout/theme classes.
       ========================================================== */
    .stock-page {
        --stock-primary: #1d4ed8;
        --stock-primary-dark: #1e3a8a;
        --stock-success: #059669;
        --stock-warning: #d97706;
        --stock-danger: #dc2626;
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
    .stock-page .category-card { cursor: pointer; transition: box-shadow .15s, border-color .15s; border-radius: 8px; }
    .stock-page .category-card:hover { box-shadow: 0 0 0 1px rgba(var(--bs-primary-rgb),.25); }

    /* Large stone catalogues (100+): denser tiles in a scrollable panel
       instead of an ever-growing page. */
    .stock-page #categoryRollup.rollup-scroll {
        max-height: 340px;
        overflow-y: auto;
        padding-right: 4px;
    }
    .stock-page .category-card-compact .card-body { padding: 8px 10px; }
    .stock-page .category-card-compact h4 { font-size: 1.1rem; }

    /* Summary stat cards (Sales Report tab) */
    .stock-page .stat-cards-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
    .stock-page .stat-card {
        display: flex; align-items: center; gap: 12px;
        background: #fff; border: 1px solid var(--stock-border); border-radius: 10px;
        padding: 14px 16px; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .stock-page .stat-icon {
        flex-shrink: 0; width: 40px; height: 40px; border-radius: 8px;
        display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem;
    }
    .stock-page .stat-icon-primary { background: #eff6ff; color: var(--stock-primary); }
    .stock-page .stat-icon-warning { background: #fffbeb; color: var(--stock-warning); }
    .stock-page .stat-icon-danger  { background: #fef2f2; color: var(--stock-danger); }
    .stock-page .stat-value { font-size: 1.25rem; font-weight: 700; line-height: 1.2; }
    .stock-page .stat-label { font-size: 0.75rem; color: var(--stock-text-muted); font-weight: 500; }
    @media (max-width: 768px) { .stock-page .stat-cards-row { grid-template-columns: repeat(1, 1fr); } }

    /* Tables */
    .stock-page #stockTable thead th,
    .stock-page #salesReportTable thead th {
        background: #f1f5f9; font-weight: 700; font-size: 0.6875rem; letter-spacing: 0.03em; padding: 8px 12px;
    }
    .stock-page #stockTable tbody td,
    .stock-page #salesReportTable tbody td { padding: 8px 12px; font-size: 0.8125rem; }
    .stock-page #stockTable tbody tr:hover,
    .stock-page #salesReportTable tbody tr:hover { background: #f8fafc; }
    .stock-page span.dt-column-order:before,
    .stock-page span.dt-column-order:after { color: #475569; }
    .stock-page span.dt-column-order:before { opacity: .45; }
    .stock-page span.dt-column-order:after { opacity: .9; }

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
    #salesReportTable_wrapper .dataTables_length, #salesReportTable_wrapper .dataTables_filter { display: none !important; }
    #salesInfoSlot .dataTables_info { padding: 0; font-size: 0.875rem; }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const dt = $('#stockTable').DataTable({
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
            { data: 'product_label',  name: 'products.title',  orderable: false },
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
        timer = setTimeout(() => dt.search(v).draw(), 250);
    });
    $('#stockPerPage').on('change', function () { dt.page.len(parseInt(this.value, 10)).draw(); });
    $('#stockLocationFilter').on('change', function () {
        dt.draw();
        loadCategoryRollup();
    });
    $('#stockCategoryFilter').on('change', function () {
        dt.draw();
        highlightActiveCategory();
    });

    function highlightActiveCategory() {
        const active = $('#stockCategoryFilter').val();
        $('#categoryRollup .category-card').each(function () {
            $(this).toggleClass('border-primary', String($(this).data('categoryId')) === String(active) && active !== '');
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
                    wrap.append('<div class="col-12 text-muted small">No stock recorded yet.</div>');
                    $('#categoryRollupCount').text('');
                    wrap.removeClass('rollup-scroll');
                    return;
                }

                // Large stone catalogues (100+) render as a denser, scrollable
                // grid instead of pushing the rest of the page down — same
                // data/click-to-filter behavior either way, just more compact.
                const isLarge = data.categories.length > 100;
                wrap.toggleClass('rollup-scroll', isLarge);
                $('#categoryRollupCount').text(
                    isLarge ? `${data.categories.length} stones — scroll for more` : ''
                );
                const colClass = isLarge
                    ? 'col-6 col-sm-4 col-md-3 col-lg-2 col-xl-2'
                    : 'col-6 col-md-4 col-lg-3 col-xl-2';

                data.categories.forEach((c) => {
                    const safeName = $('<div>').text(c.category_name).html();
                    const card = $(`
                        <div class="${colClass}">
                            <div class="card h-100 mb-0 category-card border${isLarge ? ' category-card-compact' : ''}" role="button" data-category-id="${c.category_id}">
                                <div class="card-body py-2 px-3">
                                    <div class="small text-muted text-truncate" title="${safeName}">${safeName}</div>
                                    <div class="d-flex align-items-baseline justify-content-between">
                                        <h4 class="mb-0">${c.on_hand}</h4>
                                        <small class="text-muted">${c.product_count} item${c.product_count == 1 ? '' : 's'}</small>
                                    </div>
                                    ${Number(c.on_hand_carats) > 0 ? `<small class="text-muted d-block">${Number(c.on_hand_carats).toFixed(3).replace(/\.?0+$/, '')} ct</small>` : ''}
                                </div>
                            </div>
                        </div>
                    `);
                    card.find('.category-card').on('click', function () {
                        $('#stockCategoryFilter').val(c.category_id).trigger('change');
                    });
                    wrap.append(card);
                });
                highlightActiveCategory();
            })
            .catch(() => {
                $('#categoryRollup').html('<div class="col-12 text-muted small">Could not load category totals.</div>');
            });
    }

    loadCategoryRollup();

    // ── Sales Report tab: lazy-init on first show. Avoids the classic
    // DataTables-inside-a-hidden-Bootstrap-tab column-width bug, and
    // skips the query entirely if the tab is never opened. ─────────────
    let salesDt = null;

    function loadSalesSummary() {
        const params = new URLSearchParams();
        const loc = $('#salesLocationFilter').val();
        const cat = $('#salesCategoryFilter').val();
        if (loc) params.set('location_id', loc);
        if (cat) params.set('category_id', cat);

        fetch(`{{ route('stock.sales-summary') }}?${params.toString()}`, { headers: { 'Accept': 'application/json' } })
            .then((r) => r.json())
            .then((data) => {
                if (!data.ok) return;
                $('#salesKpiQty').text(data.qty_sold.toLocaleString());
                $('#salesKpiProducts').text(data.products_sold.toLocaleString());
                $('#salesKpiCount').text(data.sales_count.toLocaleString());
            })
            .catch(() => {
                $('#salesKpiQty, #salesKpiProducts, #salesKpiCount').text('—');
            });
    }

    function initSalesTable() {
        if (salesDt) return;

        salesDt = $('#salesReportTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            order: [[3, 'desc']],
            ajax: {
                url: '{{ route('stock.sales-data') }}',
                data: function (d) {
                    d.location_id = $('#salesLocationFilter').val();
                    d.category_id = $('#salesCategoryFilter').val();
                },
            },
            dom: 'rt<"sales-tail"ip>',
            pageLength: 25,
            columns: [
                { data: 'DT_RowIndex',    name: 'DT_RowIndex',    orderable: false, searchable: false, className: 'text-center' },
                { data: 'product_label',  name: 'products.title', orderable: false },
                { data: 'location_label', name: 'locations.name', orderable: false, searchable: false },
                { data: 'qty_sold',       name: 'qty_sold',       orderable: true,  searchable: false, className: 'text-end' },
                { data: 'sales_count',    name: 'sales_count',    orderable: false, searchable: false, className: 'text-end' },
                { data: 'last_sale_date', name: 'last_sale_date', orderable: false, searchable: false },
                { data: 'action',         name: 'action',         orderable: false, searchable: false, className: 'text-center' },
            ],
            language: {
                info: 'Showing _START_ to _END_ of _TOTAL_ rows',
                emptyTable: 'No sales recorded yet.',
                processing: '<div class="stock-loading"><span class="spinner-border spinner-border-sm"></span>Loading sales&hellip;</div>',
                paginate: { previous: '<i class="ti ti-chevron-left"></i>', next: '<i class="ti ti-chevron-right"></i>' },
            },
            initComplete: function () {
                $('#salesInfoSlot').append($('#salesReportTable_info'));
                $('#salesPaginationSlot').append($('.sales-tail'));
            },
        });

        let salesTimer;
        $('#salesSearch').on('keyup', function () {
            clearTimeout(salesTimer);
            const v = this.value;
            salesTimer = setTimeout(() => salesDt.search(v).draw(), 250);
        });
        $('#salesPerPage').on('change', function () { salesDt.page.len(parseInt(this.value, 10)).draw(); });
        $('#salesLocationFilter, #salesCategoryFilter').on('change', function () {
            salesDt.draw();
            loadSalesSummary();
        });

        loadSalesSummary();
    }

    document.getElementById('salesReportTab').addEventListener('shown.bs.tab', initSalesTable);
});
</script>
@endpush
