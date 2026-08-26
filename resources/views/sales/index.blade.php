@extends('layout.app')

@section('title', 'Sales')

@section('content')

<div class="container-fluid sales-page">

    {{-- Page title --}}
    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Sales</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Invoices</li>
            </ol>
        </div>
    </div>

    {{-- Toast notifications for AJAX actions (post, delete) --}}
    <div class="toast-container position-fixed top-0 end-0 p-3" id="salesToastContainer" style="z-index: 1080;"></div>

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
            <div class="stat-icon stat-icon-primary"><i class="ti ti-receipt"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['sales_total'] }}</div>
                <div class="stat-label">Total Sales</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-success"><i class="ti ti-circle-check"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['sales_completed'] }}</div>
                <div class="stat-label">Completed</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-warning"><i class="ti ti-clock"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['sales_pending'] }}</div>
                <div class="stat-label">Pending (Draft/Posted)</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-danger"><i class="ti ti-circle-x"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['sales_cancelled'] }}</div>
                <div class="stat-label">Cancelled</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-light justify-content-between">
                    <div class="d-flex gap-2">
                        <div class="app-search">
                            <input id="saleSearch" type="search" class="form-control" placeholder="Search by # / customer…" />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1 flex-wrap">
                        <div>
                            <select id="salePerPage" class="form-select form-control my-1 my-md-0">
                                <option value="10" selected>10</option>
                                <option value="15">15</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>

                        <div class="app-search">
                            <select id="saleStatusFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All Status</option>
                                <option value="draft">Draft</option>
                                <option value="posted">Posted</option>
                                <option value="completed">Completed</option>
                                <option value="refunded">Refunded</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <i class="ti ti-circle app-search-icon text-muted"></i>
                        </div>

                        <div class="app-search">
                            <select id="salePaymentFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All Payments</option>
                                <option value="unpaid">Unpaid</option>
                                <option value="partial">Partial</option>
                                <option value="paid">Paid</option>
                            </select>
                            <i class="ti ti-cash app-search-icon text-muted"></i>
                        </div>

                        @permission('sales.create')
                        <a href="{{ route('sales.create') }}" class="add-btn ms-1">
                            <i class="ti ti-plus fs-sm me-2"></i> New Sale
                        </a>
                        @endpermission
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="salesTable" class="table table-custom table-centered table-hover w-100 mb-0">
                        <thead class="align-middle thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="text-center" style="width: 1%;">S.No</th>
                                <th><i class="ti ti-receipt me-1"></i>Sale #</th>
                                <th><i class="ti ti-calendar-time me-1"></i>Date</th>
                                <th><i class="ti ti-user me-1"></i>Customer</th>
                                <th><i class="ti ti-map-pin me-1"></i>Location</th>
                                <th><i class="ti ti-tag me-1"></i>Channel</th>
                                <th class="text-end"><i class="ti ti-currency-rupee me-1"></i>Total</th>
                                <th class="text-end"><i class="ti ti-wallet me-1"></i>Balance</th>
                                <th><i class="ti ti-credit-card me-1"></i>Payment</th>
                                <th><i class="ti ti-toggle-right me-1"></i>Status</th>
                                <th><i class="ti ti-truck me-1"></i>Shipping</th>
                                <th class="text-center" style="width: 1%;"><i class="ti ti-settings me-1"></i>Actions</th>
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

    {{-- ==================== Delete Confirmation Modal ==================== --}}
    <div class="modal fade" id="deleteSaleModal" tabindex="-1" aria-labelledby="deleteSaleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="delete-modal-icon mx-auto mb-3">
                        <i class="ti ti-trash"></i>
                    </div>
                    <h5 class="modal-title mb-2" id="deleteSaleModalLabel">Delete this sale?</h5>
                    <p class="text-muted mb-0">
                        Are you sure you want to delete <strong id="deleteSaleNumber"></strong>?
                        This is a soft delete and can be restored later if needed.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteSaleBtn">
                        <i class="ti ti-trash me-1"></i>Delete Sale
                    </button>
                </div>
            </div>
        </div>
    </div>
    {{-- ==================== /Delete Confirmation Modal ==================== --}}

</div>

@endsection

@push('styles')
<style>
    /* ==========================================================
       Sales (Invoices) page — compact ERP styling
       Scoped entirely under .sales-page so nothing here leaks
       into other modules that share the same layout/theme classes.
       ========================================================== */
    .sales-page {
        --sales-primary: #1d4ed8;
        --sales-primary-dark: #1e3a8a;
        --sales-cyan: #14b8a6;
        --sales-success: #059669;
        --sales-warning: #d97706;
        --sales-danger: #dc2626;
        --sales-bg: #f8fafc;
        --sales-surface: #ffffff;
        --sales-border: #e2e8f0;
        --sales-text: #1e293b;
        --sales-text-muted: #64748b;
        padding-top: 0;
        padding-bottom: 20px;
    }

    /* ---------- Page header ---------- */
    .sales-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid var(--sales-border);
    }
    .sales-page .page-title-head > * { display: flex; align-items: center; }
    .sales-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        color: var(--sales-text);
        position: relative;
        padding-left: 12px;
    }
    .sales-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 2px;
        bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, var(--sales-primary), var(--sales-cyan));
    }
    .sales-page .breadcrumb { font-size: 0.75rem; }

    /* ---------- Summary cards ---------- */
    .sales-page .stat-cards-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 16px;
    }
    .sales-page .stat-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--sales-surface);
        border: 1px solid var(--sales-border);
        border-radius: 10px;
        padding: 14px 16px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .sales-page .stat-icon {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    .sales-page .stat-icon-primary { background: #eff6ff; color: var(--sales-primary); }
    .sales-page .stat-icon-success { background: #ecfdf5; color: var(--sales-success); }
    .sales-page .stat-icon-warning { background: #fffbeb; color: var(--sales-warning); }
    .sales-page .stat-icon-danger  { background: #fef2f2; color: var(--sales-danger); }
    .sales-page .stat-value { font-size: 1.375rem; font-weight: 700; line-height: 1.2; color: var(--sales-text); }
    .sales-page .stat-label { font-size: 0.75rem; color: var(--sales-text-muted); font-weight: 500; }

    @media (max-width: 992px) {
        .sales-page .stat-cards-row { grid-template-columns: repeat(2, 1fr); }
    }

    /* ---------- Card ---------- */
    .sales-page .card {
        border: 1px solid var(--sales-border);
        border-radius: 10px;
        box-shadow: none;
    }
    .sales-page .card-header {
        padding: 12px 16px;
        background: var(--sales-surface);
    }
    .sales-page .card-footer { padding: 10px 16px; }

    /* Tighten the app-search wrapper so the dropdowns look right in the card header */
    .sales-page .app-search { position: relative; }
    .sales-page .app-search > .app-search-icon {
        position: absolute; right: 0.75rem; top: 50%;
        transform: translateY(-50%); pointer-events: none;
    }
    .sales-page .app-search > .form-control { padding-right: 2.25rem; min-width: 170px; }
    .sales-page .card-header .form-control,
    .sales-page .card-header .form-select {
        height: 38px;
        font-size: 0.8125rem;
        border-color: var(--sales-border);
    }

    /* Primary "New Sale" button */
    .sales-page .add-btn {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, var(--sales-primary-dark), var(--sales-primary));
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 9px 18px;
        font-weight: 600;
        font-size: 0.8125rem;
        text-decoration: none;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    .sales-page .add-btn:hover {
        color: #fff;
        box-shadow: 0 4px 10px rgba(29, 78, 216, 0.25);
        transform: translateY(-1px);
    }

    /* ---------- Table ---------- */
    .sales-page #salesTable thead th {
        background: #f1f5f9;
        color: var(--sales-text);
        font-weight: 700;
        font-size: 0.6875rem;
        letter-spacing: 0.03em;
        padding: 8px 12px;
        border-bottom: 1px solid var(--sales-border);
    }

    /* Sort arrows are too faint (low opacity) against the light header background — darken them */
    .sales-page #salesTable thead th span.dt-column-order:before,
    .sales-page #salesTable thead th span.dt-column-order:after {
        color: #475569;
    }
    .sales-page #salesTable thead th span.dt-column-order:before { opacity: .45; }
    .sales-page #salesTable thead th span.dt-column-order:after { opacity: .9; }
    .sales-page #salesTable thead th.dt-ordering-asc span.dt-column-order:before,
    .sales-page #salesTable thead th.dt-ordering-desc span.dt-column-order:after {
        color: var(--sales-primary);
        opacity: 1;
    }
    .sales-page #salesTable tbody td {
        padding: 6px 12px;
        vertical-align: middle;
        border-bottom: 1px solid var(--sales-border);
        font-size: 0.8125rem;
    }
    .sales-page #salesTable tbody tr {
        transition: background 0.2s ease;
    }
    .sales-page #salesTable tbody tr:hover {
        background: #f8fafc;
    }

    /* Action buttons */
    .sales-page .action-btn {
        width: 28px;
        height: 28px;
        border-radius: 7px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
        transition: all 0.2s ease;
        text-decoration: none;
        border: none;
    }
    .sales-page .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }
    .sales-page .action-view {
        color: #2563eb;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
    }
    .sales-page .action-edit {
        color: #059669;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
    }
    .sales-page .action-post {
        color: #d97706;
        background: #fffbeb;
        border: 1px solid #fde68a;
    }
    .sales-page .action-delete {
        color: #dc2626;
        background: #fef2f2;
        border: 1px solid #fecaca;
    }

    /* DataTables "processing" loading indicator */
    .sales-page .dataTables_processing {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    .sales-page .sales-loading {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--sales-surface);
        border: 1px solid var(--sales-border);
        padding: 8px 18px;
        border-radius: 999px;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--sales-text);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.1);
    }
    .sales-page .sales-loading .spinner-border {
        width: 1rem;
        height: 1rem;
        color: var(--sales-primary);
        border-width: 0.15em;
    }

    /* Delete confirmation modal */
    .sales-page .delete-modal-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fef2f2;
        color: var(--sales-danger);
        font-size: 1.5rem;
    }

    /* Hide DataTables built-in length+filter+info+paginate (we render our own slots) */
    #salesTable_wrapper .dataTables_length,
    #salesTable_wrapper .dataTables_filter { display: none !important; }

    /* The cloned DataTables info/pagination land inside our card-footer slots */
    #salesInfoSlot .dataTables_info { padding: 0; color: var(--bs-body-color); font-size: 0.875rem; }
    #salesPaginationSlot .pagination { margin-bottom: 0; }
    #salesPaginationSlot .dataTables_paginate { margin: 0; }

    /* "Showing x to y..." info on the left, pagination on the right — overrides the
       app-wide pagination-left/info-right order, scoped to this page only. */
    .sales-page .card-footer #salesInfoSlot { order: 1; }
    .sales-page .card-footer #salesPaginationSlot { order: 2; }
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
            document.getElementById('salesToastContainer').appendChild(el);
            const toast = new bootstrap.Toast(el, { delay: 3000 });
            el.addEventListener('hidden.bs.toast', () => el.remove());
            toast.show();
        }

        // ============= DataTable =============
        const dt = $('#salesTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            order: [[2, 'desc']],
            ajax: {
                url: '{{ route('sales.data') }}',
                type: 'GET',
                data: function (d) {
                    d.status         = $('#saleStatusFilter').val();
                    d.payment_status = $('#salePaymentFilter').val();
                },
            },
            dom: 'rt<"datatables-tail"ip>',
            pageLength: 10,
            columns: [
                { data: 'DT_RowIndex',     name: 'DT_RowIndex',        orderable: false, searchable: false, className: 'text-center' },
                { data: 'sale_number',     name: 'sales.sale_number' },
                { data: 'sale_date',       name: 'sales.sale_date' },
                { data: 'customer_label',  name: 'customer_label', orderable: false },
                { data: 'location_label',  name: 'location_label',  orderable: false, searchable: false },
                { data: 'channel_label',   name: 'channel_label',   orderable: false, searchable: false },
                { data: 'grand_total',     name: 'sales.grand_total', className: 'text-end' },
                { data: 'balance_due',     name: 'sales.balance_due', className: 'text-end' },
                { data: 'payment_badge',   name: 'sales.payment_status', orderable: false, searchable: false },
                { data: 'status_badge',    name: 'sales.status',         orderable: false, searchable: false },
                { data: 'shipping_badge',  name: 'sales.shipping_status', orderable: false, searchable: false },
                { data: 'actions',         name: 'actions',              orderable: false, searchable: false, className: 'text-center' },
            ],
            language: {
                info: 'Showing _START_ to _END_ of _TOTAL_ sales',
                infoEmpty: 'No sales found',
                infoFiltered: ' (filtered from _MAX_ total)',
                emptyTable: 'No sales yet.',
                zeroRecords: 'No sales match your search.',
                processing: '<div class="sales-loading"><span class="spinner-border spinner-border-sm"></span>Loading sales&hellip;</div>',
                paginate: { previous: '<i class="ti ti-chevron-left"></i>', next: '<i class="ti ti-chevron-right"></i>' },
            },
            initComplete: function () {
                $('#salesInfoSlot').append($('#salesTable_info'));
                $('#salesPaginationSlot').append($('.datatables-tail'));
            },
        });

        let timer;
        $('#saleSearch').on('keyup', function () {
            clearTimeout(timer);
            const v = this.value;
            timer = setTimeout(() => dt.search(v).draw(), 250);
        });
        $('#salePerPage').on('change', function () { dt.page.len(parseInt(this.value, 10)).draw(); });
        $('#saleStatusFilter, #salePaymentFilter').on('change', function () { dt.draw(); });

        // ============= Post sale =============
        $('#salesTable tbody').on('click', '.js-post-sale', function () {
            const url = $(this).data('url');
            if (!confirm('Post this sale? Inventory will be deducted.')) return;
            $.ajax({
                url, type: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    if (res.ok) {
                        dt.ajax.reload(null, false);
                        showToast('success', res.message || 'Sale posted.');
                    } else {
                        showToast('error', res.message || 'Failed to post sale.');
                    }
                },
                error: function (xhr) {
                    showToast('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to post sale.');
                },
            });
        });

        // ============= Delete (styled confirmation modal) =============
        const deleteModalEl = document.getElementById('deleteSaleModal');
        const deleteModal = new bootstrap.Modal(deleteModalEl);
        let pendingDeleteUrl = null;

        $('#salesTable tbody').on('click', '.js-delete-sale', function () {
            pendingDeleteUrl = $(this).data('url');
            $('#deleteSaleNumber').text($(this).data('number'));
            deleteModal.show();
        });

        $('#confirmDeleteSaleBtn').on('click', function () {
            if (!pendingDeleteUrl) return;
            const $btn = $(this).prop('disabled', true);

            $.ajax({
                url: pendingDeleteUrl,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    if (res.ok) {
                        dt.ajax.reload(null, false);
                        showToast('success', res.message || 'Sale deleted successfully.');
                    } else {
                        showToast('error', res.message || 'Could not delete sale.');
                    }
                },
                error: function (xhr) {
                    showToast('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to delete sale.');
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
