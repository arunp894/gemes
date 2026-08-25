@extends('layout.app')

@section('title', 'Stock Report')

@section('content')

<div class="container-fluid">

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

    <ul class="nav nav-tabs mb-3" id="stockReportTabs" role="tablist">
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
                            <h5 class="card-title mb-0">By Stone</h5>
                            <small class="text-muted">Click a stone to filter the table below</small>
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

                                <div>
                                    <select id="stockPerPage" class="form-select form-control my-1 my-md-0">
                                        <option value="10">10</option>
                                        <option value="25" selected>25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>

                                @permission('stock-transfers.create')
                                <a href="{{ route('stock-transfers.create') }}" class="btn btn-primary ms-1">
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
                                        <th>Product</th>
                                        <th>Location</th>
                                        <th class="text-end">On Hand</th>
                                        <th class="text-end">Remaining Ct</th>
                                        <th class="text-center" style="width: 1%;">Ledger</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>

                        <div class="card-footer border-0">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div id="stockInfoSlot" class="text-muted small"></div>
                                <div id="stockPaginationSlot"></div>
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

                    <div class="row g-3 mb-3">
                        <div class="col-6 col-lg-4">
                            <div class="card h-100 mb-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="avatar avatar-sm bg-danger-subtle text-danger rounded">
                                            <i class="ti ti-package-export fs-md"></i>
                                        </span>
                                        <span class="text-muted small">Qty Sold</span>
                                    </div>
                                    <h3 class="mb-0" id="salesKpiQty">—</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-4">
                            <div class="card h-100 mb-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="avatar avatar-sm bg-primary-subtle text-primary rounded">
                                            <i class="ti ti-box fs-md"></i>
                                        </span>
                                        <span class="text-muted small">Products Sold</span>
                                    </div>
                                    <h3 class="mb-0" id="salesKpiProducts">—</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-4">
                            <div class="card h-100 mb-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="avatar avatar-sm bg-warning-subtle text-warning rounded">
                                            <i class="ti ti-receipt fs-md"></i>
                                        </span>
                                        <span class="text-muted small">Sales</span>
                                    </div>
                                    <h3 class="mb-0" id="salesKpiCount">—</h3>
                                </div>
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

                                <div>
                                    <select id="salesPerPage" class="form-select form-control my-1 my-md-0">
                                        <option value="10">10</option>
                                        <option value="25" selected>25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="salesReportTable" class="table table-custom table-centered table-hover w-100 mb-0">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th class="text-center" style="width: 1%;">S.No</th>
                                        <th>Product</th>
                                        <th>Location</th>
                                        <th class="text-end">Qty Sold</th>
                                        <th class="text-end">Sales</th>
                                        <th>Last Sale</th>
                                        <th class="text-center" style="width: 1%;">Ledger</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>

                        <div class="card-footer border-0">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div id="salesInfoSlot" class="text-muted small"></div>
                                <div id="salesPaginationSlot"></div>
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
    .app-search { position: relative; }
    .app-search > .app-search-icon { position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); pointer-events: none; }
    .app-search > .form-control { padding-right: 2.25rem; min-width: 200px; }
    #stockTable_wrapper .dataTables_length, #stockTable_wrapper .dataTables_filter { display: none !important; }
    #stockInfoSlot .dataTables_info { padding: 0; font-size: 0.875rem; }
    #salesReportTable_wrapper .dataTables_length, #salesReportTable_wrapper .dataTables_filter { display: none !important; }
    #salesInfoSlot .dataTables_info { padding: 0; font-size: 0.875rem; }
    .category-card { cursor: pointer; transition: box-shadow .15s, border-color .15s; }
    .category-card:hover { box-shadow: 0 0 0 1px rgba(var(--bs-primary-rgb),.25); }
    .avatar.avatar-sm { width: 32px; height: 32px; display:inline-flex; align-items:center; justify-content:center; border-radius: .375rem; }
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
        dom: 'rt<"datatables-tail"ip>',
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
            processing: '<div class="spinner-border spinner-border-sm text-primary"></div>',
            paginate: { previous: '<i class="ti ti-chevron-left"></i>', next: '<i class="ti ti-chevron-right"></i>' },
        },
        initComplete: function () {
            $('#stockInfoSlot').append($('#stockTable_info'));
            $('#stockPaginationSlot').append($('.datatables-tail'));
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
                    return;
                }
                data.categories.forEach((c) => {
                    const safeName = $('<div>').text(c.category_name).html();
                    const card = $(`
                        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                            <div class="card h-100 mb-0 category-card border" role="button" data-category-id="${c.category_id}">
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
            dom: 'rt<"d-none datatables-tail"ip>',
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
                processing: '<div class="spinner-border spinner-border-sm text-primary"></div>',
                paginate: { previous: '<i class="ti ti-chevron-left"></i>', next: '<i class="ti ti-chevron-right"></i>' },
            },
            initComplete: function () {
                $('#salesInfoSlot').append($('#salesReportTable_info'));
                $('#salesPaginationSlot').append($('#salesReportTable_paginate'));
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
