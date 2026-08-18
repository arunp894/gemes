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

    <div class="row">
        <div class="col-12">

            <div class="card" id="categoryRollupCard">
                <div class="card-header border-light d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">By Category</h5>
                    <small class="text-muted">Click a category to filter the table below</small>
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
                                <option value="">All Categories</option>
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
                                <th>Product</th>
                                <th>Location</th>
                                <th class="text-end">On Hand</th>
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

@endsection

@push('styles')
<style>
    .app-search { position: relative; }
    .app-search > .app-search-icon { position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); pointer-events: none; }
    .app-search > .form-control { padding-right: 2.25rem; min-width: 200px; }
    #stockTable_wrapper .dataTables_length, #stockTable_wrapper .dataTables_filter { display: none !important; }
    #stockInfoSlot .dataTables_info { padding: 0; font-size: 0.875rem; }
    .category-card { cursor: pointer; transition: box-shadow .15s, border-color .15s; }
    .category-card:hover { box-shadow: 0 0 0 1px rgba(var(--bs-primary-rgb),.25); }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const dt = $('#stockTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: false,
        order: [[2, 'desc']],
        ajax: {
            url: '{{ route('stock.data') }}',
            data: function (d) {
                d.location_id = $('#stockLocationFilter').val();
                d.category_id = $('#stockCategoryFilter').val();
            },
        },
        dom: 'rt<"d-none datatables-tail"ip>',
        pageLength: 25,
        columns: [
            { data: 'product_label',  name: 'products.title',  orderable: false },
            { data: 'location_label', name: 'locations.name',  orderable: false, searchable: false },
            { data: 'on_hand',        name: 'on_hand',         orderable: true,  searchable: false, className: 'text-end' },
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
            $('#stockPaginationSlot').append($('#stockTable_paginate'));
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
});
</script>
@endpush
