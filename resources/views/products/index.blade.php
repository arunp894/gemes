@extends('layout.app')

@section('title', 'Products')

@section('content')

<div class="container-fluid">

    {{-- Page title --}}
    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Products</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Paces</a></li>
                <li class="breadcrumb-item"><a href="#">Catalogue</a></li>
                <li class="breadcrumb-item active">Products</li>
            </ol>
        </div>
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                {{-- Card header: search + filters + add button --}}
                <div class="card-header border-light justify-content-between">
                    <div class="d-flex gap-2 flex-wrap">
                        <div class="app-search">
                            <input id="productSearch" type="search" class="form-control"
                                placeholder="Search product..." />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1 flex-wrap">
                        <div>
                            <select id="productPerPage" class="form-select form-control my-1 my-md-0">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>

                        <div class="app-search">
                            <select id="productCategoryFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All Categories</option>
                                @foreach ($topCategories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <i class="ti ti-category app-search-icon text-muted"></i>
                        </div>

                        <div class="app-search">
                            <select id="productStatusFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All Statuses</option>
                                <option value="1">Active</option>
                                <option value="0">Draft</option>
                            </select>
                            <i class="ti ti-circle app-search-icon text-muted"></i>
                        </div>

                        <div class="app-search">
                            <select id="productWebsiteFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">Website: All</option>
                                <option value="1">Enabled</option>
                                <option value="0">Disabled</option>
                            </select>
                            <i class="ti ti-world app-search-icon text-muted"></i>
                        </div>

                        {{-- Bulk Actions Dropdown --}}
                        <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle ms-1" type="button"
                                id="bulkActionsBtn" data-bs-toggle="dropdown" aria-expanded="false" disabled>
                                <i class="ti ti-checkbox me-1"></i>Bulk Actions
                                <span id="bulkCount" class="badge bg-primary ms-1">0</span>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="bulkActionsBtn">
                                <li>
                                    <button class="dropdown-item js-bulk-action" data-action="enable" type="button">
                                        <i class="ti ti-world me-2 text-success"></i>Enable for Website
                                    </button>
                                </li>
                                <li>
                                    <button class="dropdown-item js-bulk-action" data-action="disable" type="button">
                                        <i class="ti ti-world-off me-2 text-secondary"></i>Disable for Website
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <a href="{{ route('products.create') }}" class="btn btn-primary ms-1">
                            <i class="ti ti-plus fs-sm me-2"></i> Add Product
                        </a>
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table id="productsTable" class="table table-custom table-centered table-select table-hover w-100 mb-0">
                        <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3" style="width: 1%;">
                                    <input id="productSelectAll"
                                        class="form-check-input form-check-input-light fs-14 mt-0"
                                        type="checkbox" />
                                </th>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Primary Barcode</th>
                                <th>Status</th>
                                <th>Website</th>
                                <th>Last Modified</th>
                                <th class="text-center" style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                {{-- Card footer: info + pagination slots --}}
                <div class="card-footer border-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div id="productsInfoSlot" class="text-muted small"></div>
                        <div id="productsPaginationSlot"></div>
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
    .app-search > .app-search-icon {
        position: absolute; right: 0.75rem; top: 50%;
        transform: translateY(-50%); pointer-events: none;
    }
    .app-search > .form-control { padding-right: 2.25rem; min-width: 160px; }

    /* Hide DataTables built-in length+filter+info+paginate (we render our own slots) */
    #productsTable_wrapper .dataTables_length,
    #productsTable_wrapper .dataTables_filter { display: none !important; }

    #productsInfoSlot .dataTables_info { padding: 0; color: var(--bs-body-color); font-size: 0.875rem; }
    #productsPaginationSlot .pagination { margin-bottom: 0; }
    #productsPaginationSlot .dataTables_paginate { margin: 0; }

    .avatar-md { width: 48px; height: 48px; }
    .avatar-md > img { object-fit: cover; width: 100%; height: 100%; }
</style>
@endpush

@push('scripts')
<script>
    $(function () {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        /* ===================== DataTable ===================== */
        const dt = $('#productsTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            order: [[6, 'desc']], // latest updated first
            ajax: {
                url: '{{ route('products.data') }}',
                type: 'GET',
                data: function (d) {
                    d.category_id     = $('#productCategoryFilter').val();
                    d.status          = $('#productStatusFilter').val();
                    d.website_enabled = $('#productWebsiteFilter').val();
                },
            },
            dom: 'rt<"d-none datatables-tail"ip>',
            pageLength: 10,
            columns: [
                { data: 'checkbox',        name: 'checkbox',          orderable: false, searchable: false, className: 'ps-3' },
                { data: 'title',           name: 'products.title' },
                { data: 'sku',             name: 'products.sku' },
                { data: 'primary_barcode', name: 'primary_barcode',   orderable: false, searchable: false },
                { data: 'status_badge',    name: 'products.status',   searchable: false },
                { data: 'website_badge',   name: 'products.website_enabled', searchable: false },
                { data: 'updated_at',      name: 'products.updated_at' },
                { data: 'action',          name: 'action',            orderable: false, searchable: false, className: 'text-center' },
            ],
            language: {
                info: 'Showing _START_ to _END_ of _TOTAL_ products',
                infoEmpty: 'No products found',
                infoFiltered: ' (filtered from _MAX_ total)',
                emptyTable: 'No products yet. Click "Add Product" to get started.',
                zeroRecords: 'No products match your search.',
                processing: '<div class="spinner-border spinner-border-sm text-primary"></div>',
                paginate: {
                    previous: '<i class="ti ti-chevron-left"></i>',
                    next:     '<i class="ti ti-chevron-right"></i>',
                },
            },
            initComplete: function () {
                $('#productsInfoSlot').append($('#productsTable_info'));
                $('#productsPaginationSlot').append($('#productsTable_paginate'));
            },
            drawCallback: function () {
                $('#productSelectAll').prop('checked', false);
                refreshBulkCount();
            },
        });

        /* ===================== Search + Filters ===================== */
        let searchTimer;
        $('#productSearch').on('keyup', function () {
            clearTimeout(searchTimer);
            const v = this.value;
            searchTimer = setTimeout(() => dt.search(v).draw(), 250);
        });

        $('#productPerPage').on('change', function () {
            dt.page.len(parseInt(this.value, 10)).draw();
        });

        $('#productCategoryFilter, #productStatusFilter, #productWebsiteFilter').on('change', function () {
            dt.draw();
        });

        /* ===================== Selection + Bulk ===================== */
        function refreshBulkCount() {
            const checked = $('#productsTable tbody .product-item-check:checked').length;
            $('#bulkCount').text(checked);
            $('#bulkActionsBtn').prop('disabled', checked === 0);
        }

        $('#productSelectAll').on('change', function () {
            $('#productsTable tbody .product-item-check').prop('checked', this.checked);
            refreshBulkCount();
        });

        $('#productsTable tbody').on('change', '.product-item-check', refreshBulkCount);

        $('.js-bulk-action').on('click', function () {
            const action = $(this).data('action');
            const ids = $('#productsTable tbody .product-item-check:checked')
                .map(function () { return parseInt(this.value, 10); })
                .get();

            if (ids.length === 0) return;
            if (ids.length > 500) {
                alert('Bulk actions are limited to 500 products at a time.');
                return;
            }
            const verb = action === 'enable' ? 'enable' : 'disable';
            if (!confirm(verb.charAt(0).toUpperCase() + verb.slice(1) + ' ' + ids.length + ' product(s) for the website?')) return;

            $.ajax({
                url: '{{ route('products.bulk-website-toggle') }}',
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                data: { ids: ids, action: action },
                success: function (res) {
                    if (res.success) {
                        alert(res.message);
                        dt.ajax.reload(null, false);
                    }
                },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Bulk action failed.';
                    alert(msg);
                },
            });
        });

        /* ===================== Row Actions ===================== */
        $('#productsTable tbody').on('click', '.js-toggle-status', function () {
            const url = $(this).data('url');
            $.ajax({
                url: url,
                type: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) { if (res.success) dt.ajax.reload(null, false); },
                error: function () { alert('Failed to update status.'); },
            });
        });

        $('#productsTable tbody').on('click', '.js-toggle-website', function () {
            const url = $(this).data('url');
            $.ajax({
                url: url,
                type: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) { if (res.success) dt.ajax.reload(null, false); },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Failed to update website visibility.';
                    alert(msg);
                },
            });
        });

        $('#productsTable tbody').on('click', '.js-delete', function () {
            const url  = $(this).data('url');
            const name = $(this).data('name');
            if (!confirm('Delete product "' + name + '"? (Soft delete — can be restored.)')) return;
            $.ajax({
                url: url,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    if (res.success) dt.ajax.reload(null, false);
                    else alert(res.message || 'Could not delete product.');
                },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Failed to delete product.';
                    alert(msg);
                },
            });
        });
    });
</script>
@endpush
