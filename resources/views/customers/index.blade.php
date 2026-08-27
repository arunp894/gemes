@extends('layout.app')

@section('title', 'Customers')

@section('content')

<div class="container-fluid customers-page">

    {{-- Page title --}}
    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Customers</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Sales</a></li>
                <li class="breadcrumb-item active">Customers</li>
            </ol>
        </div>
    </div>

    {{-- Toast notifications for AJAX actions (status toggle, delete) --}}
    <div class="toast-container position-fixed top-0 end-0 p-3" id="customersToastContainer" style="z-index: 1080;"></div>

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
            <div class="stat-icon stat-icon-primary"><i class="ti ti-users"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['customers_total'] }}</div>
                <div class="stat-label">Total Customers</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-success"><i class="ti ti-circle-check"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['customers_active'] }}</div>
                <div class="stat-label">Active Customers</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-danger"><i class="ti ti-circle-x"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['customers_inactive'] }}</div>
                <div class="stat-label">Inactive Customers</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                {{-- Card header: search + per-page + type filter + status filter + add button --}}
                <div class="card-header border-light justify-content-between">
                    <div class="d-flex gap-2">
                        <div class="app-search">
                            <input id="customerSearch" type="search" class="form-control"
                                placeholder="Search customers..." />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1">
                        <div class="app-search">
                            <select id="customerTypeFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All Types</option>
                                @foreach (\App\Models\Customer::TYPES as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <i class="ti ti-users app-search-icon text-muted"></i>
                        </div>

                        <div class="app-search">
                            <select id="customerStatusFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <i class="ti ti-circle app-search-icon text-muted"></i>
                        </div>

                        @permission('customers.create')
                        <a href="{{ route('customers.create') }}" class="add-btn ms-1">
                            <i class="ti ti-plus fs-sm me-2"></i> Add Customer
                        </a>
                        @endpermission
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table id="customersTable" class="table table-custom table-centered table-select table-hover w-100 mb-0">
                        <thead class="align-middle thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3" style="width: 1%;">
                                    <input id="customerSelectAll" class="form-check-input form-check-input-light fs-14 mt-0"
                                        type="checkbox" />
                                </th>
                                <th class="text-center" style="width: 1%;">S.No</th>
                                <th><i class="ti ti-barcode me-1"></i>Code</th>
                                <th><i class="ti ti-user me-1"></i>Customer</th>
                                <th><i class="ti ti-tag me-1"></i>Type</th>
                                <th><i class="ti ti-phone me-1"></i>Contact</th>
                                <th><i class="ti ti-map-pin me-1"></i>Location</th>
                                <th><i class="ti ti-toggle-right me-1"></i>Status</th>
                                <th><i class="ti ti-calendar-time me-1"></i>Created</th>
                                <th class="text-center" style="width: 1%;"><i class="ti ti-settings me-1"></i>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                {{-- Card footer: DataTables info + pagination get moved here --}}
                <div class="card-footer border-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div id="customersInfoSlot" class="text-muted small"></div>
                        <div class="d-flex align-items-center gap-2 footer-pagination-group">
                            <select id="customerPerPage" class="form-select form-select-sm" style="width: auto;">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="15">15</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                            </select>
                            <div id="customersPaginationSlot"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== Delete Confirmation Modal ==================== --}}
    <div class="modal fade" id="deleteCustomerModal" tabindex="-1" aria-labelledby="deleteCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="delete-modal-icon mx-auto mb-3">
                        <i class="ti ti-trash"></i>
                    </div>
                    <h5 class="modal-title mb-2" id="deleteCustomerModalLabel">Delete this customer?</h5>
                    <p class="text-muted mb-0">
                        Are you sure you want to delete <strong id="deleteCustomerName"></strong>?
                        This is a soft delete and can be restored later if needed.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteCustomerBtn">
                        <i class="ti ti-trash me-1"></i>Delete Customer
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
       Customers page — compact ERP styling
       Scoped entirely under .customers-page so nothing here leaks
       into other modules that share the same layout/theme classes.
       ========================================================== */
    .customers-page {
        --customers-primary: #1d4ed8;
        --customers-primary-dark: #1e3a8a;
        --customers-cyan: #14b8a6;
        --customers-success: #059669;
        --customers-warning: #d97706;
        --customers-danger: #dc2626;
        --customers-bg: #f8fafc;
        --customers-surface: #ffffff;
        --customers-border: #e2e8f0;
        --customers-text: #1e293b;
        --customers-text-muted: #64748b;
        padding-top: 0;
        padding-bottom: 20px;
    }

    /* ---------- Page header ---------- */
    .customers-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid var(--customers-border);
    }
    .customers-page .page-title-head > * { display: flex; align-items: center; }
    .customers-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        color: var(--customers-text);
        position: relative;
        padding-left: 12px;
    }
    .customers-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 2px;
        bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, var(--customers-primary), var(--customers-cyan));
    }
    .customers-page .breadcrumb { font-size: 0.75rem; }

    /* ---------- Summary cards ---------- */
    .customers-page .stat-cards-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 16px;
    }
    .customers-page .stat-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--customers-surface);
        border: 1px solid var(--customers-border);
        border-radius: 10px;
        padding: 14px 16px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .customers-page .stat-icon {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    .customers-page .stat-icon-primary { background: #eff6ff; color: var(--customers-primary); }
    .customers-page .stat-icon-success { background: #ecfdf5; color: var(--customers-success); }
    .customers-page .stat-icon-danger  { background: #fef2f2; color: var(--customers-danger); }
    .customers-page .stat-value { font-size: 1.375rem; font-weight: 700; line-height: 1.2; color: var(--customers-text); }
    .customers-page .stat-label { font-size: 0.75rem; color: var(--customers-text-muted); font-weight: 500; }

    @media (max-width: 992px) {
        .customers-page .stat-cards-row { grid-template-columns: repeat(2, 1fr); }
    }

    /* ---------- Card ---------- */
    .customers-page .card {
        border: 1px solid var(--customers-border);
        border-radius: 10px;
        box-shadow: none;
    }
    .customers-page .card-header {
        padding: 12px 16px;
        background: var(--customers-surface);
    }
    .customers-page .card-footer { padding: 10px 16px; }

    /* Tighten the app-search wrapper so the dropdowns look right in the card header */
    .customers-page .app-search { position: relative; }
    .customers-page .app-search > .app-search-icon {
        position: absolute; right: 0.75rem; top: 50%;
        transform: translateY(-50%); pointer-events: none;
    }
    .customers-page .app-search > .form-control { padding-right: 2.25rem; min-width: 180px; }
    .customers-page .card-header .form-control,
    .customers-page .card-header .form-select {
        height: 38px;
        font-size: 0.8125rem;
        border-color: var(--customers-border);
    }

    /* Primary "Add Customer" button */
    .customers-page .add-btn {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, var(--customers-primary-dark), var(--customers-primary));
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 9px 18px;
        font-weight: 600;
        font-size: 0.8125rem;
        text-decoration: none;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    .customers-page .add-btn:hover {
        color: #fff;
        box-shadow: 0 4px 10px rgba(29, 78, 216, 0.25);
        transform: translateY(-1px);
    }

    /* ---------- Table ---------- */
    .customers-page #customersTable thead th {
        background: #f1f5f9;
        color: var(--customers-text);
        font-weight: 700;
        font-size: 0.6875rem;
        letter-spacing: 0.03em;
        padding: 8px 12px;
        border-bottom: 1px solid var(--customers-border);
    }

    /* Sort arrows are too faint (low opacity) against the light header background — darken them */
    .customers-page #customersTable thead th span.dt-column-order:before,
    .customers-page #customersTable thead th span.dt-column-order:after {
        color: #475569;
    }
    .customers-page #customersTable thead th span.dt-column-order:before { opacity: .45; }
    .customers-page #customersTable thead th span.dt-column-order:after { opacity: .9; }
    .customers-page #customersTable thead th.dt-ordering-asc span.dt-column-order:before,
    .customers-page #customersTable thead th.dt-ordering-desc span.dt-column-order:after {
        color: var(--customers-primary);
        opacity: 1;
    }
    .customers-page #customersTable tbody td {
        padding: 6px 12px;
        vertical-align: middle;
        border-bottom: 1px solid var(--customers-border);
        font-size: 0.8125rem;
    }
    .customers-page #customersTable tbody tr {
        transition: background 0.2s ease;
    }
    .customers-page #customersTable tbody tr:hover {
        background: #f8fafc;
    }

    /* Customer name (no image field on this module — bold link + muted sub-line, no thumbnail) */
    .customers-page .customer-name-cell { line-height: 1.3; }
    .customers-page .customer-name-link {
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--customers-text);
        text-decoration: none;
    }
    .customers-page .customer-name-link:hover { color: var(--customers-primary); }

    /* Status pill */
    .customers-page .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .customers-page .status-pill .status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        display: inline-block;
    }
    .customers-page .status-active { background: #ecfdf5; color: var(--customers-success); }
    .customers-page .status-active .status-dot { background: var(--customers-success); }
    .customers-page .status-inactive { background: #fef2f2; color: var(--customers-danger); }
    .customers-page .status-inactive .status-dot { background: var(--customers-danger); }

    /* Action buttons */
    .customers-page .action-btn {
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
    .customers-page .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }
    .customers-page .action-view {
        color: #2563eb;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
    }
    .customers-page .action-edit {
        color: #059669;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
    }
    .customers-page .action-toggle {
        color: #d97706;
        background: #fffbeb;
        border: 1px solid #fde68a;
    }
    .customers-page .action-delete {
        color: #dc2626;
        background: #fef2f2;
        border: 1px solid #fecaca;
    }

    /* DataTables "processing" loading indicator */
    .customers-page .dataTables_processing {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    .customers-page .customers-loading {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--customers-surface);
        border: 1px solid var(--customers-border);
        padding: 8px 18px;
        border-radius: 999px;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--customers-text);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.1);
    }
    .customers-page .customers-loading .spinner-border {
        width: 1rem;
        height: 1rem;
        color: var(--customers-primary);
        border-width: 0.15em;
    }

    /* Delete confirmation modal */
    .customers-page .delete-modal-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fef2f2;
        color: var(--customers-danger);
        font-size: 1.5rem;
    }

    /* Hide DataTables built-in length+filter+info+paginate (we render our own slots) */
    #customersTable_wrapper .dataTables_length,
    #customersTable_wrapper .dataTables_filter { display: none !important; }

    /* The cloned DataTables info/pagination land inside our card-footer slots */
    #customersInfoSlot .dataTables_info { padding: 0; color: var(--bs-body-color); font-size: 0.875rem; }
    #customersPaginationSlot .pagination { margin-bottom: 0; }
    #customersPaginationSlot .dataTables_paginate { margin: 0; }

    /* "Showing x to y..." info on the left, pagination on the right — overrides the
       app-wide pagination-left/info-right order, scoped to this page only. */
    .customers-page .card-footer #customersInfoSlot { order: 1; }
    .customers-page .card-footer .footer-pagination-group { order: 2; }
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
            document.getElementById('customersToastContainer').appendChild(el);
            const toast = new bootstrap.Toast(el, { delay: 3000 });
            el.addEventListener('hidden.bs.toast', () => el.remove());
            toast.show();
        }

        // ============= DataTable =============
        const dt = $('#customersTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            order: [[8, 'desc']],
            ajax: {
                url: '{{ route('customers.data') }}',
                type: 'GET',
            },
            // dom: render table + (info+paginate) into a hidden wrapper; we'll move them via initComplete
            dom: 'rt<"datatables-tail"ip>',
            pageLength: 10,
            columns: [
                { data: 'checkbox',      name: 'checkbox',                  orderable: false, searchable: false, className: 'ps-3' },
                { data: 'DT_RowIndex',   name: 'DT_RowIndex',               orderable: false, searchable: false, className: 'text-center' },
                { data: 'customer_code', name: 'customers.customer_code' },
                { data: 'name',          name: 'customers.name' },
                { data: 'type_badge',    name: 'customers.customer_type',   searchable: true },
                { data: 'contact',       name: 'contact',                   orderable: false, searchable: false },
                { data: 'location',      name: 'location',                  orderable: false, searchable: false },
                { data: 'status_badge',  name: 'customers.status',          searchable: true },
                { data: 'created_at',    name: 'customers.created_at' },
                { data: 'action',        name: 'action',                    orderable: false, searchable: false, className: 'text-center' },
            ],
            language: {
                info: 'Showing _START_ to _END_ of _TOTAL_ customers',
                infoEmpty: 'No customers found',
                infoFiltered: ' (filtered from _MAX_ total)',
                emptyTable: 'No customers yet. Click "Add Customer" to get started.',
                zeroRecords: 'No customers match your search.',
                processing: '<div class="customers-loading"><span class="spinner-border spinner-border-sm"></span>Loading customers&hellip;</div>',
                paginate: {
                    previous: '<i class="ti ti-chevron-left"></i>',
                    next:     '<i class="ti ti-chevron-right"></i>',
                },
            },
            initComplete: function () {
                // Move DataTables-rendered info & pagination into our card-footer slots
                $('#customersInfoSlot').append($('#customersTable_info'));
                $('#customersPaginationSlot').append($('.datatables-tail'));
            },
        });

        // ============= Custom search wire-up (debounced) =============
        let searchTimer;
        $('#customerSearch').on('keyup', function () {
            clearTimeout(searchTimer);
            const v = this.value;
            searchTimer = setTimeout(() => dt.search(v).draw(), 250);
        });

        // Per-page
        $('#customerPerPage').on('change', function () {
            dt.page.len(parseInt(this.value, 10)).draw();
        });

        // Type filter — column index 4 is the type_badge column
        $('#customerTypeFilter').on('change', function () {
            dt.column(4).search(this.value).draw();
        });

        // Status filter — column index 7 is the status_badge column
        $('#customerStatusFilter').on('change', function () {
            dt.column(7).search(this.value).draw();
        });

        // ============= Select-all =============
        $('#customerSelectAll').on('change', function () {
            $('#customersTable tbody .product-item-check').prop('checked', this.checked);
        });

        // ============= Toggle Status =============
        $('#customersTable tbody').on('click', '.js-toggle-status', function () {
            const url = $(this).data('url');
            $.ajax({
                url: url,
                type: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    if (res.success) {
                        dt.ajax.reload(null, false);
                        showToast('success', 'Customer marked as ' + (res.label || (res.status ? 'Active' : 'Inactive')) + '.');
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
        const deleteModalEl = document.getElementById('deleteCustomerModal');
        const deleteModal = new bootstrap.Modal(deleteModalEl);
        let pendingDeleteUrl = null;

        $('#customersTable tbody').on('click', '.js-delete', function () {
            pendingDeleteUrl = $(this).data('url');
            $('#deleteCustomerName').text($(this).data('name'));
            deleteModal.show();
        });

        $('#confirmDeleteCustomerBtn').on('click', function () {
            if (!pendingDeleteUrl) return;
            const $btn = $(this).prop('disabled', true);

            $.ajax({
                url: pendingDeleteUrl,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    if (res.success) {
                        dt.ajax.reload(null, false);
                        showToast('success', res.message || 'Customer deleted successfully.');
                    } else {
                        showToast('error', res.message || 'Could not delete customer.');
                    }
                },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Failed to delete customer.';
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
