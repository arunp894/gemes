@extends('layout.app')

@section('title', 'Products')

@section('content')

<div class="container-fluid products-page">

    {{-- Page title --}}
    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Products</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Product</a></li>
            </ol>
        </div>
    </div>

    {{-- Toast notifications for AJAX actions (status/website toggle, delete, bulk actions) --}}
    <div class="toast-container position-fixed top-0 end-0 p-3" id="productsToastContainer" style="z-index: 1080;"></div>

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

    {{-- Summary cards --}}
    <div class="stat-cards-row">
        <div class="stat-card">
            <div class="stat-icon stat-icon-primary"><i class="ti ti-package"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['products_total'] }}</div>
                <div class="stat-label">Total Products</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-success"><i class="ti ti-circle-check"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['products_active'] }}</div>
                <div class="stat-label">Active Products</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-warning"><i class="ti ti-file-text"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['products_draft'] }}</div>
                <div class="stat-label">Draft Products</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                {{-- Card header: search + filters + bulk actions --}}
                <div class="card-header border-light justify-content-between">
                    <div class="d-flex gap-2 flex-wrap">
                        <div class="app-search">
                            <input id="productSearch" type="search" class="form-control"
                                placeholder="Search product..." />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1 flex-wrap">
                        <div class="app-search">
                            <select id="productCategoryFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All Stones</option>
                                @foreach ($categories as $cat)
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

                        {{-- <a href="{{ route('products.create') }}" class="add-btn ms-1">
                            <i class="ti ti-plus fs-sm me-2"></i> Add Product
                        </a> --}}
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table id="productsTable" class="table table-custom table-centered table-select table-hover w-100 mb-0">
                        <thead class="align-middle thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3" style="width: 1%;">
                                    <input id="productSelectAll"
                                        class="form-check-input form-check-input-light fs-14 mt-0"
                                        type="checkbox" />
                                </th>
                                <th class="text-center" style="width: 1%;">S.No</th>
                                <th><i class="ti ti-package me-1"></i>Product</th>
                                <th><i class="ti ti-hash me-1"></i>SKU</th>
                                <th><i class="ti ti-barcode me-1"></i>Primary Barcode</th>
                                <th><i class="ti ti-toggle-right me-1"></i>Status</th>
                                <th><i class="ti ti-world me-1"></i>Website</th>
                                <th><i class="ti ti-calendar-time me-1"></i>Last Modified</th>
                                <th class="text-center" style="width: 1%;"><i class="ti ti-settings me-1"></i>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                {{-- Card footer: info + pagination slots --}}
                <div class="card-footer border-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div id="productsInfoSlot" class="text-muted small"></div>
                        <div class="d-flex align-items-center gap-2 footer-pagination-group">
                            <select id="productPerPage" class="form-select form-select-sm" style="width: auto;">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <div id="productsPaginationSlot"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== Delete Confirmation Modal ==================== --}}
    <div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="delete-modal-icon mx-auto mb-3">
                        <i class="ti ti-trash"></i>
                    </div>
                    <h5 class="modal-title mb-2" id="deleteProductModalLabel">Delete this product?</h5>
                    <p class="text-muted mb-0">
                        Are you sure you want to delete <strong id="deleteProductName"></strong>?
                        This is a soft delete and can be restored later if needed.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteProductBtn">
                        <i class="ti ti-trash me-1"></i>Delete Product
                    </button>
                </div>
            </div>
        </div>
    </div>
    {{-- ==================== /Delete Confirmation Modal ==================== --}}

    {{-- ==================== Bulk Website Visibility Confirmation Modal ==================== --}}
    <div class="modal fade" id="bulkWebsiteModal" tabindex="-1" aria-labelledby="bulkWebsiteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="confirm-modal-icon mx-auto mb-3">
                        <i class="ti ti-world"></i>
                    </div>
                    <h5 class="modal-title mb-2" id="bulkWebsiteModalLabel">Update website visibility?</h5>
                    <p class="text-muted mb-0" id="bulkWebsiteModalMessage"></p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmBulkWebsiteBtn">
                        <i class="ti ti-check me-1"></i>Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>
    {{-- ==================== /Bulk Website Visibility Confirmation Modal ==================== --}}

</div>

@endsection

@push('styles')
<style>
    /* ==========================================================
       Products page — compact ERP styling
       Scoped entirely under .products-page so nothing here leaks
       into other modules that share the same layout/theme classes.
       ========================================================== */
    .products-page {
        --products-primary: #1d4ed8;
        --products-primary-dark: #1e3a8a;
        --products-cyan: #14b8a6;
        --products-success: #059669;
        --products-warning: #d97706;
        --products-danger: #dc2626;
        --products-info: #0891b2;
        --products-purple: #7c3aed;
        --products-bg: #f8fafc;
        --products-surface: #ffffff;
        --products-border: #e2e8f0;
        --products-text: #1e293b;
        --products-text-muted: #64748b;
        padding-top: 0;
        padding-bottom: 20px;
    }

    /* ---------- Page header ---------- */
    .products-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid var(--products-border);
    }
    .products-page .page-title-head > * { display: flex; align-items: center; }
    .products-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        color: var(--products-text);
        position: relative;
        padding-left: 12px;
    }
    .products-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 2px;
        bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, var(--products-primary), var(--products-cyan));
    }
    .products-page .breadcrumb { font-size: 0.75rem; }

    /* ---------- Summary cards ---------- */
    .products-page .stat-cards-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 16px;
    }
    .products-page .stat-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--products-surface);
        border: 1px solid var(--products-border);
        border-radius: 10px;
        padding: 14px 16px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .products-page .stat-icon {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    .products-page .stat-icon-primary { background: #eff6ff; color: var(--products-primary); }
    .products-page .stat-icon-success { background: #ecfdf5; color: var(--products-success); }
    .products-page .stat-icon-warning { background: #fffbeb; color: var(--products-warning); }
    .products-page .stat-value { font-size: 1.375rem; font-weight: 700; line-height: 1.2; color: var(--products-text); }
    .products-page .stat-label { font-size: 0.75rem; color: var(--products-text-muted); font-weight: 500; }

    @media (max-width: 992px) {
        .products-page .stat-cards-row { grid-template-columns: repeat(2, 1fr); }
    }

    /* ---------- Card ---------- */
    .products-page .card {
        border: 1px solid var(--products-border);
        border-radius: 10px;
        box-shadow: none;
    }
    .products-page .card-header {
        padding: 12px 16px;
        background: var(--products-surface);
    }
    .products-page .card-footer { padding: 10px 16px; }

    /* Tighten the app-search wrapper so the dropdowns look right in the card header */
    .products-page .app-search { position: relative; }
    .products-page .app-search > .app-search-icon {
        position: absolute; right: 0.75rem; top: 50%;
        transform: translateY(-50%); pointer-events: none;
    }
    .products-page .app-search > .form-control { padding-right: 2.25rem; min-width: 160px; }
    .products-page .card-header .form-control,
    .products-page .card-header .form-select {
        height: 38px;
        font-size: 0.8125rem;
        border-color: var(--products-border);
    }
    .products-page .card-header .btn.dropdown-toggle {
        height: 38px;
        font-size: 0.8125rem;
    }

    /* Primary "Add Product" button (kept for parity — currently unused on this page) */
    .products-page .add-btn {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, var(--products-primary-dark), var(--products-primary));
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 9px 18px;
        font-weight: 600;
        font-size: 0.8125rem;
        text-decoration: none;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    .products-page .add-btn:hover {
        color: #fff;
        box-shadow: 0 4px 10px rgba(29, 78, 216, 0.25);
        transform: translateY(-1px);
    }

    /* ---------- Table ---------- */
    .products-page #productsTable thead th {
        background: #f1f5f9;
        color: var(--products-text);
        font-weight: 700;
        font-size: 0.6875rem;
        letter-spacing: 0.03em;
        padding: 8px 12px;
        border-bottom: 1px solid var(--products-border);
    }

    /* Sort arrows are too faint (low opacity) against the light header background — darken them */
    .products-page #productsTable thead th span.dt-column-order:before,
    .products-page #productsTable thead th span.dt-column-order:after {
        color: #475569;
    }
    .products-page #productsTable thead th span.dt-column-order:before { opacity: .45; }
    .products-page #productsTable thead th span.dt-column-order:after { opacity: .9; }
    .products-page #productsTable thead th.dt-ordering-asc span.dt-column-order:before,
    .products-page #productsTable thead th.dt-ordering-desc span.dt-column-order:after {
        color: var(--products-primary);
        opacity: 1;
    }
    .products-page #productsTable tbody td {
        padding: 6px 12px;
        vertical-align: middle;
        border-bottom: 1px solid var(--products-border);
        font-size: 0.8125rem;
    }
    .products-page #productsTable tbody tr {
        transition: background 0.2s ease;
    }
    .products-page #productsTable tbody tr:hover {
        background: #f8fafc;
    }

    /* Product name + thumbnail */
    .products-page .product-name-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .products-page .product-thumb {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
        border: 1px solid var(--products-border);
    }
    .products-page .product-thumb-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .products-page .product-thumb-placeholder {
        width: 100%;
        height: 100%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f1f5f9;
        color: var(--products-text-muted);
    }
    .products-page .product-name-link {
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--products-text);
        text-decoration: none;
    }
    .products-page .product-name-link:hover { color: var(--products-primary); }

    /* Status pill (Active / Draft) */
    .products-page .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .products-page .status-pill .status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        display: inline-block;
    }
    .products-page .status-active { background: #ecfdf5; color: var(--products-success); }
    .products-page .status-active .status-dot { background: var(--products-success); }
    .products-page .status-draft { background: #fffbeb; color: var(--products-warning); }
    .products-page .status-draft .status-dot { background: var(--products-warning); }

    /* Website pill (Enabled / Disabled) */
    .products-page .website-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .products-page .website-pill .status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        display: inline-block;
    }
    .products-page .website-enabled { background: #ecfeff; color: var(--products-info); }
    .products-page .website-enabled .status-dot { background: var(--products-info); }
    .products-page .website-disabled { background: #f1f5f9; color: var(--products-text-muted); }
    .products-page .website-disabled .status-dot { background: var(--products-text-muted); }

    /* Action buttons */
    .products-page .action-btn {
        width: 28px;
        height: 28px;
        border-radius: 7px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
        transition: all 0.2s ease;
        text-decoration: none;
    }
    .products-page .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }
    .products-page .action-view {
        color: #2563eb;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
    }
    .products-page .action-edit {
        color: #059669;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
    }
    .products-page .action-toggle {
        color: #d97706;
        background: #fffbeb;
        border: 1px solid #fde68a;
    }
    .products-page .action-frontend {
        color: #7c3aed;
        background: #f5f3ff;
        border: 1px solid #ddd6fe;
    }
    .products-page .action-delete {
        color: #dc2626;
        background: #fef2f2;
        border: 1px solid #fecaca;
    }

    /* DataTables "processing" loading indicator */
    .products-page .dataTables_processing {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    .products-page .products-loading {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--products-surface);
        border: 1px solid var(--products-border);
        padding: 8px 18px;
        border-radius: 999px;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--products-text);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.1);
    }
    .products-page .products-loading .spinner-border {
        width: 1rem;
        height: 1rem;
        color: var(--products-primary);
        border-width: 0.15em;
    }

    /* Delete confirmation modal */
    .products-page .delete-modal-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fef2f2;
        color: var(--products-danger);
        font-size: 1.5rem;
    }

    /* Bulk website-visibility confirmation modal (non-destructive, so blue not red) */
    .products-page .confirm-modal-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #eff6ff;
        color: var(--products-primary);
        font-size: 1.5rem;
    }

    /* Hide DataTables built-in length+filter+info+paginate (we render our own slots) */
    #productsTable_wrapper .dataTables_length,
    #productsTable_wrapper .dataTables_filter { display: none !important; }

    /* The cloned DataTables info/pagination land inside our card-footer slots */
    #productsInfoSlot .dataTables_info { padding: 0; color: var(--bs-body-color); font-size: 0.875rem; }
    #productsPaginationSlot .pagination { margin-bottom: 0; }
    #productsPaginationSlot .dataTables_paginate { margin: 0; }

    /* "Showing x to y..." info on the left, pagination on the right — overrides the
       app-wide pagination-left/info-right order, scoped to this page only. */
    .products-page .card-footer #productsInfoSlot { order: 1; }
    .products-page .card-footer .footer-pagination-group { order: 2; }
</style>
@endpush

@push('scripts')
<script>
    $(function () {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        // ============= Toast helper =============
        function showToast(type, message) {
            const isSuccess = type === 'success';
            const el = document.createElement('div');
            el.className = 'toast align-items-center border-0 text-bg-' + (isSuccess ? 'success' : 'danger');
            el.setAttribute('role', 'alert');
            el.setAttribute('aria-live', 'assertive');
            el.setAttribute('aria-atomic', 'true');
            el.innerHTML = '<div class="d-flex">'
                + '<div class="toast-body d-flex align-items-center gap-2">'
                + '<i class="ti ' + (isSuccess ? 'ti-circle-check' : 'ti-alert-circle') + ' fs-lg"></i>'
                + $('<div/>').text(message).html()
                + '</div>'
                + '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>'
                + '</div>';
            document.getElementById('productsToastContainer').appendChild(el);
            const toast = new bootstrap.Toast(el, { delay: 3000 });
            el.addEventListener('hidden.bs.toast', () => el.remove());
            toast.show();
        }

        /* ===================== DataTable ===================== */
        const dt = $('#productsTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            order: [[7, 'desc']], // latest updated first
            ajax: {
                url: '{{ route('products.data') }}',
                type: 'GET',
                data: function (d) {
                    d.category_id = $('#productCategoryFilter').val();
                    d.status      = $('#productStatusFilter').val();
                },
            },
            dom: 'rt<"datatables-tail"ip>',
            pageLength: 10,
            columns: [
                { data: 'checkbox',        name: 'checkbox',          orderable: false, searchable: false, className: 'ps-3' },
                { data: 'DT_RowIndex',     name: 'DT_RowIndex',       orderable: false, searchable: false, className: 'text-center' },
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
                processing: '<div class="products-loading"><span class="spinner-border spinner-border-sm"></span>Loading products&hellip;</div>',
                paginate: {
                    previous: '<i class="ti ti-chevron-left"></i>',
                    next:     '<i class="ti ti-chevron-right"></i>',
                },
            },
            initComplete: function () {
                $('#productsInfoSlot').append($('#productsTable_info'));
                $('#productsPaginationSlot').append($('.datatables-tail'));
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

        $('#productCategoryFilter, #productStatusFilter').on('change', function () {
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

        // ============= Bulk Website Toggle (styled confirmation modal) =============
        const bulkWebsiteModalEl = document.getElementById('bulkWebsiteModal');
        const bulkWebsiteModal = new bootstrap.Modal(bulkWebsiteModalEl);
        let pendingBulkAction = null;
        let pendingBulkIds = null;

        $('.js-bulk-action').on('click', function () {
            const action = $(this).data('action');
            const ids = $('#productsTable tbody .product-item-check:checked')
                .map(function () { return parseInt(this.value, 10); })
                .get();

            if (ids.length === 0) return;
            if (ids.length > 500) {
                showToast('error', 'Bulk actions are limited to 500 products at a time.');
                return;
            }
            const verb = action === 'enable' ? 'enable' : 'disable';

            pendingBulkAction = action;
            pendingBulkIds = ids;
            $('#bulkWebsiteModalMessage').text(verb.charAt(0).toUpperCase() + verb.slice(1) + ' ' + ids.length + ' product(s) for the website?');
            bulkWebsiteModal.show();
        });

        $('#confirmBulkWebsiteBtn').on('click', function () {
            if (!pendingBulkIds) return;
            const $btn = $(this).prop('disabled', true);

            $.ajax({
                url: '{{ route('products.bulk-website-toggle') }}',
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                data: { ids: pendingBulkIds, action: pendingBulkAction },
                success: function (res) {
                    if (res.success) {
                        showToast('success', res.message);
                        dt.ajax.reload(null, false);
                    } else {
                        showToast('error', res.message || 'Bulk action failed.');
                    }
                },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Bulk action failed.';
                    showToast('error', msg);
                },
                complete: function () {
                    $btn.prop('disabled', false);
                    pendingBulkAction = null;
                    pendingBulkIds = null;
                    bulkWebsiteModal.hide();
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
                success: function (res) {
                    if (res.success) {
                        dt.ajax.reload(null, false);
                        showToast('success', 'Product marked as ' + (res.label || (res.status ? 'Active' : 'Draft')) + '.');
                    } else {
                        showToast('error', res.message || 'Failed to update status.');
                    }
                },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Failed to update status.';
                    showToast('error', msg);
                },
            });
        });

        // ============= Delete (styled confirmation modal) =============
        const deleteModalEl = document.getElementById('deleteProductModal');
        const deleteModal = new bootstrap.Modal(deleteModalEl);
        let pendingDeleteUrl = null;

        $('#productsTable tbody').on('click', '.js-delete', function () {
            pendingDeleteUrl = $(this).data('url');
            $('#deleteProductName').text($(this).data('name'));
            deleteModal.show();
        });

        $('#confirmDeleteProductBtn').on('click', function () {
            if (!pendingDeleteUrl) return;
            const $btn = $(this).prop('disabled', true);

            $.ajax({
                url: pendingDeleteUrl,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    if (res.success) {
                        dt.ajax.reload(null, false);
                        showToast('success', res.message || 'Product deleted successfully.');
                    } else {
                        showToast('error', res.message || 'Could not delete product.');
                    }
                },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Failed to delete product.';
                    showToast('error', msg);
                },
                complete: function () {
                    $btn.prop('disabled', false);
                    pendingDeleteUrl = null;
                    deleteModal.hide();
                },
            });
        });
    });
</script>
@endpush
