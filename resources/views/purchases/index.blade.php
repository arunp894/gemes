@extends('layout.app')

@section('title', 'Purchases')

@section('content')

<div class="container-fluid purchases-page">

    {{-- Page title --}}
    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Purchases</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Procurement</a></li>
                <li class="breadcrumb-item active">Purchases</li>
            </ol>
        </div>
    </div>

    {{-- Toast notifications for AJAX actions (delete) --}}
    <div class="toast-container position-fixed top-0 end-0 p-3" id="purchasesToastContainer" style="z-index: 1080;"></div>

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
            <div class="stat-icon stat-icon-primary"><i class="ti ti-file-invoice"></i></div>
            <div class="stat-body">
                <div class="stat-value" id="statTotalInvoices">{{ $summary['total_invoices'] }}</div>
                <div class="stat-label">Total Invoices</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-cyan"><i class="ti ti-report-money"></i></div>
            <div class="stat-body">
                <div class="stat-value" id="statTotalAmount">{{ $summary['total_amount'] }}</div>
                <div class="stat-label">Total Value</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-success"><i class="ti ti-circle-check"></i></div>
            <div class="stat-body">
                <div class="stat-value" id="statPaidAmount">{{ $summary['paid_amount'] }}</div>
                <div class="stat-label">Paid</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-warning"><i class="ti ti-alert-triangle"></i></div>
            <div class="stat-body">
                <div class="stat-value" id="statUnpaidAmount">{{ $summary['unpaid_amount'] }}</div>
                <div class="stat-label">Outstanding</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-light justify-content-between">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="app-search">
                            <input id="purchaseSearch" type="search" class="form-control" placeholder="Search invoice / supplier..." />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1">
                        <div>
                            <select id="purchasePerPage" class="form-select form-control my-1 my-md-0">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                        <div class="app-search">
                            <select id="purchaseStatusFilter" class="form-select form-control">
                                <option value="">All Status</option>
                                <option value="draft">Draft</option>
                                <option value="posted">Posted</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <i class="ti ti-flag app-search-icon text-muted"></i>
                        </div>
                        <div class="app-search">
                            <select id="purchasePaymentFilter" class="form-select form-control">
                                <option value="">All Payments</option>
                                <option value="unpaid">Unpaid</option>
                                <option value="partial">Partial</option>
                                <option value="paid">Paid</option>
                            </select>
                            <i class="ti ti-credit-card app-search-icon text-muted"></i>
                        </div>
                        @permission('purchases.create')
                        <a href="{{ route('purchases.create') }}" class="add-btn ms-1">
                            <i class="ti ti-plus fs-sm me-2"></i> Purchase
                        </a>
                        @endpermission
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="purchasesTable" class="table table-custom table-centered table-hover w-100 mb-0">
                        <thead class="align-middle thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="text-center" style="width: 1%;">S.No</th>
                                <th><i class="ti ti-file-invoice me-1"></i>Invoice #</th>
                                <th><i class="ti ti-calendar-time me-1"></i>Date</th>
                                <th><i class="ti ti-building me-1"></i>Supplier</th>
                                <th><i class="ti ti-map-pin me-1"></i>Location</th>
                                <th class="text-end"><i class="ti ti-currency-rupee me-1"></i>Grand Total</th>
                                <th class="text-end"><i class="ti ti-alert-circle me-1"></i>Due</th>
                                <th><i class="ti ti-credit-card me-1"></i>Payment</th>
                                <th><i class="ti ti-flag me-1"></i>Status</th>
                                <th class="text-center" style="width: 1%;"><i class="ti ti-settings me-1"></i>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div class="card-footer border-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div id="purchasesInfoSlot" class="text-muted small"></div>
                        <div id="purchasesPaginationSlot"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== Delete Confirmation Modal ==================== --}}
    <div class="modal fade" id="deletePurchaseModal" tabindex="-1" aria-labelledby="deletePurchaseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="delete-modal-icon mx-auto mb-3">
                        <i class="ti ti-trash"></i>
                    </div>
                    <h5 class="modal-title mb-2" id="deletePurchaseModalLabel">Delete this purchase?</h5>
                    <p class="text-muted mb-0">
                        Are you sure you want to delete invoice <strong id="deletePurchaseInvoice"></strong>?
                        This cannot be undone.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeletePurchaseBtn">
                        <i class="ti ti-trash me-1"></i>Delete Purchase
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
       Purchases page — compact ERP styling
       Scoped entirely under .purchases-page so nothing here leaks
       into other modules that share the same layout/theme classes.
       ========================================================== */
    .purchases-page {
        --purchases-primary: #1d4ed8;
        --purchases-primary-dark: #1e3a8a;
        --purchases-cyan: #14b8a6;
        --purchases-success: #059669;
        --purchases-warning: #d97706;
        --purchases-danger: #dc2626;
        --purchases-bg: #f8fafc;
        --purchases-surface: #ffffff;
        --purchases-border: #e2e8f0;
        --purchases-text: #1e293b;
        --purchases-text-muted: #64748b;
        padding-top: 0;
        padding-bottom: 20px;
    }

    /* ---------- Page header ---------- */
    .purchases-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid var(--purchases-border);
    }
    .purchases-page .page-title-head > * { display: flex; align-items: center; }
    .purchases-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        color: var(--purchases-text);
        position: relative;
        padding-left: 12px;
    }
    .purchases-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 2px;
        bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, var(--purchases-primary), var(--purchases-cyan));
    }
    .purchases-page .breadcrumb { font-size: 0.75rem; }

    /* ---------- Summary cards ---------- */
    .purchases-page .stat-cards-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 16px;
    }
    .purchases-page .stat-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--purchases-surface);
        border: 1px solid var(--purchases-border);
        border-radius: 10px;
        padding: 14px 16px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .purchases-page .stat-icon {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    .purchases-page .stat-icon-primary { background: #eff6ff; color: var(--purchases-primary); }
    .purchases-page .stat-icon-cyan    { background: #ecfeff; color: var(--purchases-cyan); }
    .purchases-page .stat-icon-success { background: #ecfdf5; color: var(--purchases-success); }
    .purchases-page .stat-icon-warning { background: #fffbeb; color: var(--purchases-warning); }
    .purchases-page .stat-icon-danger  { background: #fef2f2; color: var(--purchases-danger); }
    .purchases-page .stat-value { font-size: 1.25rem; font-weight: 700; line-height: 1.2; color: var(--purchases-text); }
    .purchases-page .stat-label { font-size: 0.75rem; color: var(--purchases-text-muted); font-weight: 500; }

    @media (max-width: 992px) {
        .purchases-page .stat-cards-row { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 576px) {
        .purchases-page .stat-cards-row { grid-template-columns: 1fr; }
    }

    /* ---------- Card ---------- */
    .purchases-page .card {
        border: 1px solid var(--purchases-border);
        border-radius: 10px;
        box-shadow: none;
    }
    .purchases-page .card-header {
        padding: 12px 16px;
        background: var(--purchases-surface);
    }
    .purchases-page .card-footer { padding: 10px 16px; }

    /* Tighten the app-search wrapper so the dropdowns look right in the card header */
    .purchases-page .app-search { position: relative; }
    .purchases-page .app-search > .app-search-icon {
        position: absolute; right: 0.75rem; top: 50%;
        transform: translateY(-50%); pointer-events: none;
    }
    .purchases-page .app-search > .form-control,
    .purchases-page .app-search > .form-select { padding-right: 2.25rem; min-width: 150px; }
    .purchases-page .card-header .form-control,
    .purchases-page .card-header .form-select {
        height: 38px;
        font-size: 0.8125rem;
        border-color: var(--purchases-border);
    }

    /* Primary "+ Purchase" button */
    .purchases-page .add-btn {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, var(--purchases-primary-dark), var(--purchases-primary));
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 9px 18px;
        font-weight: 600;
        font-size: 0.8125rem;
        text-decoration: none;
        white-space: nowrap;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    .purchases-page .add-btn:hover {
        color: #fff;
        box-shadow: 0 4px 10px rgba(29, 78, 216, 0.25);
        transform: translateY(-1px);
    }

    /* ---------- Table ---------- */
    .purchases-page #purchasesTable thead th {
        background: #f1f5f9;
        color: var(--purchases-text);
        font-weight: 700;
        font-size: 0.6875rem;
        letter-spacing: 0.03em;
        padding: 8px 12px;
        border-bottom: 1px solid var(--purchases-border);
    }

    /* Sort arrows are too faint (low opacity) against the light header background — darken them */
    .purchases-page #purchasesTable thead th span.dt-column-order:before,
    .purchases-page #purchasesTable thead th span.dt-column-order:after {
        color: #475569;
    }
    .purchases-page #purchasesTable thead th span.dt-column-order:before { opacity: .45; }
    .purchases-page #purchasesTable thead th span.dt-column-order:after { opacity: .9; }
    .purchases-page #purchasesTable thead th.dt-ordering-asc span.dt-column-order:before,
    .purchases-page #purchasesTable thead th.dt-ordering-desc span.dt-column-order:after {
        color: var(--purchases-primary);
        opacity: 1;
    }
    .purchases-page #purchasesTable tbody td {
        padding: 6px 12px;
        vertical-align: middle;
        border-bottom: 1px solid var(--purchases-border);
        font-size: 0.8125rem;
    }
    .purchases-page #purchasesTable tbody tr {
        transition: background 0.2s ease;
    }
    .purchases-page #purchasesTable tbody tr:hover {
        background: #f8fafc;
    }

    /* Bold the invoice number (primary column) since there's no thumbnail on this table */
    .purchases-page #purchasesTable tbody td:nth-child(2) {
        font-weight: 600;
        color: var(--purchases-text);
    }

    /* Action buttons */
    .purchases-page .action-btn {
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
    .purchases-page .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }
    .purchases-page .action-view {
        color: #2563eb;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
    }
    .purchases-page .action-edit {
        color: #059669;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
    }
    .purchases-page .action-post {
        color: #d97706;
        background: #fffbeb;
        border: 1px solid #fde68a;
    }
    .purchases-page .action-delete {
        color: #dc2626;
        background: #fef2f2;
        border: 1px solid #fecaca;
    }

    /* DataTables "processing" loading indicator */
    .purchases-page .dataTables_processing {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    .purchases-page .purchases-loading {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--purchases-surface);
        border: 1px solid var(--purchases-border);
        padding: 8px 18px;
        border-radius: 999px;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--purchases-text);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.1);
    }
    .purchases-page .purchases-loading .spinner-border {
        width: 1rem;
        height: 1rem;
        color: var(--purchases-primary);
        border-width: 0.15em;
    }

    /* Delete confirmation modal */
    .purchases-page .delete-modal-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fef2f2;
        color: var(--purchases-danger);
        font-size: 1.5rem;
    }

    /* Hide DataTables built-in length+filter+info+paginate (we render our own slots) */
    #purchasesTable_wrapper .dataTables_length,
    #purchasesTable_wrapper .dataTables_filter { display: none !important; }

    /* The cloned DataTables info/pagination land inside our card-footer slots */
    #purchasesInfoSlot .dataTables_info { padding: 0; color: var(--bs-body-color); font-size: 0.875rem; }
    #purchasesPaginationSlot .pagination { margin-bottom: 0; }
    #purchasesPaginationSlot .dataTables_paginate { margin: 0; }

    /* "Showing x to y..." info on the left, pagination on the right — overrides the
       app-wide pagination-left/info-right order, scoped to this page only. */
    .purchases-page .card-footer #purchasesInfoSlot { order: 1; }
    .purchases-page .card-footer #purchasesPaginationSlot { order: 2; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

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
        document.getElementById('purchasesToastContainer').appendChild(el);
        const toast = new bootstrap.Toast(el, { delay: 3000 });
        el.addEventListener('hidden.bs.toast', () => el.remove());
        toast.show();
    }

    const table = $('#purchasesTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('purchases.data') }}",
            data: function (d) {
                d.status = $('#purchaseStatusFilter').val();
                d.payment_status = $('#purchasePaymentFilter').val();
            }
        },
        dom: 'rt<"datatables-tail"ip>',
        columns: [
            { data: 'DT_RowIndex',     name: 'DT_RowIndex',   orderable: false, searchable: false, className: 'text-center' },
            { data: 'invoice_number',  name: 'invoice_number' },
            { data: 'purchase_date',   name: 'purchase_date' },
            { data: 'supplier_label',  name: 'supplier.name', orderable: false },
            { data: 'location_label',  name: 'location.name', orderable: false },
            { data: 'grand_total',     name: 'grand_total',  className: 'text-end' },
            { data: 'due_amount',      name: 'due_amount',   className: 'text-end' },
            { data: 'payment_badge',   name: 'payment_status', orderable: false, searchable: false },
            { data: 'status_badge',    name: 'status' },
            { data: 'actions',         orderable: false, searchable: false, className: 'text-center' },
        ],
        language: {
            info: 'Showing _START_ to _END_ of _TOTAL_ purchases',
            infoEmpty: 'No purchases found',
            infoFiltered: ' (filtered from _MAX_ total)',
            emptyTable: 'No purchases yet. Click "Purchase" to get started.',
            zeroRecords: 'No purchases match your search.',
            processing: '<div class="purchases-loading"><span class="spinner-border spinner-border-sm"></span>Loading purchases&hellip;</div>',
            paginate: {
                previous: '<i class="ti ti-chevron-left"></i>',
                next:     '<i class="ti ti-chevron-right"></i>',
            },
        },
        initComplete: function () {
            $('#purchasesInfoSlot').append($('#purchasesTable_info'));
            $('#purchasesPaginationSlot').append($('.datatables-tail'));
        },
        order: [[2, 'desc']],
        pageLength: 10,
    });

    // Keep the summary cards in sync with whatever filters the table is currently showing —
    // the backend already computes a filter-matched summary on every DataTables request.
    table.on('xhr', function (e, settings, json) {
        if (json && json.summary) {
            $('#statTotalInvoices').text(json.summary.total_invoices);
            $('#statTotalAmount').text(json.summary.total_amount);
            $('#statPaidAmount').text(json.summary.paid_amount);
            $('#statUnpaidAmount').text(json.summary.unpaid_amount);
        }
    });

    let searchTimer;
    $('#purchaseSearch').on('keyup', function () {
        clearTimeout(searchTimer);
        const v = this.value;
        searchTimer = setTimeout(() => table.search(v).draw(), 250);
    });
    $('#purchaseStatusFilter, #purchasePaymentFilter').on('change', () => table.draw());
    $('#purchasePerPage').on('change', function () {
        table.page.len(parseInt(this.value, 10)).draw();
    });

    // ============= Post (kept as native confirm — not in scope for the modal treatment) =============
    $(document).on('click', '.js-post-purchase', function () {
        const id = this.dataset.id;
        if (!confirm('Post this purchase? Posted purchases cannot be edited.')) return;
        fetch(`/purchases/${id}/post`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        }).then(r => r.json()).then(() => table.ajax.reload(null, false));
    });

    // ============= Delete (styled confirmation modal + toasts) =============
    const deleteModalEl = document.getElementById('deletePurchaseModal');
    const deleteModal = new bootstrap.Modal(deleteModalEl);
    let pendingDeleteId = null;

    $(document).on('click', '.js-delete-purchase', function () {
        pendingDeleteId = this.dataset.id;
        $('#deletePurchaseInvoice').text(this.dataset.invoice || '');
        deleteModal.show();
    });

    $('#confirmDeletePurchaseBtn').on('click', function () {
        if (!pendingDeleteId) return;
        const $btn = $(this).prop('disabled', true);

        fetch(`/purchases/${pendingDeleteId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        })
            .then(async (r) => {
                const data = await r.json().catch(() => ({}));
                if (r.ok) {
                    table.ajax.reload(null, false);
                    showToast('success', data.message || 'Purchase deleted.');
                } else {
                    showToast('error', data.message || 'Could not delete purchase.');
                }
            })
            .catch(() => showToast('error', 'Failed to delete purchase.'))
            .finally(() => {
                $btn.prop('disabled', false);
                pendingDeleteId = null;
                deleteModal.hide();
            });
    });
})();
</script>
@endpush
