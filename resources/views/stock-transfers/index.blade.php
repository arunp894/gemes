@extends('layout.app')

@section('title', 'Stock Transfers')

@section('content')

<div class="container-fluid stock-transfers-page">

    {{-- Page title --}}
    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Stock Transfers</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Inventory</a></li>
                <li class="breadcrumb-item active">Transfers</li>
            </ol>
        </div>
    </div>

    {{-- Toast notifications for AJAX actions (post / receive / cancel) --}}
    <div class="toast-container position-fixed top-0 end-0 p-3" id="transfersToastContainer" style="z-index: 1080;"></div>

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

    {{-- Summary cards — Stock Transfers has a 4-stage lifecycle (draft → in_transit →
         received / cancelled), not a simple active/inactive boolean, so this is Total
         plus one card per stage instead of the Total/Active/Inactive trio. --}}
    <div class="stat-cards-row">
        <div class="stat-card">
            <div class="stat-icon stat-icon-total"><i class="ti ti-transfer"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['total'] }}</div>
                <div class="stat-label">Total Transfers</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-draft"><i class="ti ti-file-text"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['draft'] }}</div>
                <div class="stat-label">Draft</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-transit"><i class="ti ti-truck-delivery"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['in_transit'] }}</div>
                <div class="stat-label">In Transit</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-received"><i class="ti ti-circle-check"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['received'] }}</div>
                <div class="stat-label">Received</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-cancelled"><i class="ti ti-ban"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['cancelled'] }}</div>
                <div class="stat-label">Cancelled</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                {{-- Card header: search + per-page + status filter + add button --}}
                <div class="card-header border-light justify-content-between">
                    <div class="d-flex gap-2">
                        <div class="app-search">
                            <input id="transferSearch" type="search" class="form-control"
                                placeholder="Search transfers…" />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1">
                        <div>
                            <select id="transferPerPage" class="form-select form-control my-1 my-md-0">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="15">15</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                            </select>
                        </div>

                        <div class="app-search">
                            <select id="transferStatusFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All Status</option>
                                <option value="draft">Draft</option>
                                <option value="in_transit">In Transit</option>
                                <option value="received">Received</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <i class="ti ti-circle app-search-icon text-muted"></i>
                        </div>

                        @permission('stock-transfers.create')
                        <a href="{{ route('stock-transfers.create') }}" class="add-btn ms-1">
                            <i class="ti ti-plus fs-sm me-2"></i> New Transfer
                        </a>
                        @endpermission
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table id="transfersTable" class="table table-custom table-centered table-hover w-100 mb-0">
                        <thead class="align-middle thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="text-center" style="width: 1%;">S.No</th>
                                <th><i class="ti ti-receipt me-1"></i>Transfer #</th>
                                <th><i class="ti ti-calendar-time me-1"></i>Date</th>
                                <th><i class="ti ti-map-pin me-1"></i>From</th>
                                <th><i class="ti ti-map-pin-share me-1"></i>To</th>
                                <th class="text-end"><i class="ti ti-list-numbers me-1"></i>Lines</th>
                                <th><i class="ti ti-flag me-1"></i>Status</th>
                                <th class="text-center" style="width: 1%;"><i class="ti ti-settings me-1"></i>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                {{-- Card footer: DataTables info + pagination get moved here --}}
                <div class="card-footer border-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div id="transfersInfoSlot" class="text-muted small"></div>
                        <div id="transfersPaginationSlot"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== Confirm Action Modal (Post / Receive / Cancel) ==================== --}}
    <div class="modal fade" id="confirmActionModal" tabindex="-1" aria-labelledby="confirmActionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="action-modal-icon mx-auto mb-3" id="confirmActionIconWrap">
                        <i class="ti" id="confirmActionIconGlyph"></i>
                    </div>
                    <h5 class="modal-title mb-2" id="confirmActionModalLabel">Confirm action</h5>
                    <p class="text-muted mb-0" id="confirmActionMessage"></p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn" id="confirmActionBtn">
                        <i class="ti me-1" id="confirmActionBtnIcon"></i><span id="confirmActionBtnLabel">Confirm</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    {{-- ==================== /Confirm Action Modal ==================== --}}

</div>

@endsection

@push('styles')
<style>
    /* ==========================================================
       Stock Transfers page — compact ERP styling
       Scoped entirely under .stock-transfers-page so nothing here
       leaks into other modules that share the same layout/theme classes.
       ========================================================== */
    .stock-transfers-page {
        --st-primary: #1d4ed8;
        --st-primary-dark: #1e3a8a;
        --st-cyan: #14b8a6;
        --st-success: #059669;
        --st-warning: #d97706;
        --st-danger: #dc2626;
        --st-slate: #64748b;
        --st-info: #0891b2;
        --st-bg: #f8fafc;
        --st-surface: #ffffff;
        --st-border: #e2e8f0;
        --st-text: #1e293b;
        --st-text-muted: #64748b;
        padding-top: 0;
        padding-bottom: 20px;
    }

    /* ---------- Page header ---------- */
    .stock-transfers-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid var(--st-border);
    }
    .stock-transfers-page .page-title-head > * { display: flex; align-items: center; }
    .stock-transfers-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        color: var(--st-text);
        position: relative;
        padding-left: 12px;
    }
    .stock-transfers-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 2px;
        bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, var(--st-primary), var(--st-cyan));
    }
    .stock-transfers-page .breadcrumb { font-size: 0.75rem; }

    /* ---------- Summary cards ---------- */
    .stock-transfers-page .stat-cards-row {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 16px;
        margin-bottom: 16px;
    }
    .stock-transfers-page .stat-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--st-surface);
        border: 1px solid var(--st-border);
        border-radius: 10px;
        padding: 14px 16px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .stock-transfers-page .stat-icon {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    .stock-transfers-page .stat-icon-total     { background: #eff6ff; color: var(--st-primary); }
    .stock-transfers-page .stat-icon-draft     { background: #f1f5f9; color: var(--st-slate); }
    .stock-transfers-page .stat-icon-transit   { background: #fffbeb; color: var(--st-warning); }
    .stock-transfers-page .stat-icon-received  { background: #ecfdf5; color: var(--st-success); }
    .stock-transfers-page .stat-icon-cancelled { background: #fef2f2; color: var(--st-danger); }
    .stock-transfers-page .stat-value { font-size: 1.375rem; font-weight: 700; line-height: 1.2; color: var(--st-text); }
    .stock-transfers-page .stat-label { font-size: 0.75rem; color: var(--st-text-muted); font-weight: 500; }

    @media (max-width: 1200px) {
        .stock-transfers-page .stat-cards-row { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 768px) {
        .stock-transfers-page .stat-cards-row { grid-template-columns: repeat(2, 1fr); }
    }

    /* ---------- Card ---------- */
    .stock-transfers-page .card {
        border: 1px solid var(--st-border);
        border-radius: 10px;
        box-shadow: none;
    }
    .stock-transfers-page .card-header {
        padding: 12px 16px;
        background: var(--st-surface);
    }
    .stock-transfers-page .card-footer { padding: 10px 16px; }

    /* Tighten the app-search wrapper so the dropdowns look right in the card header */
    .stock-transfers-page .app-search { position: relative; }
    .stock-transfers-page .app-search > .app-search-icon {
        position: absolute; right: 0.75rem; top: 50%;
        transform: translateY(-50%); pointer-events: none;
    }
    .stock-transfers-page .app-search > .form-control { padding-right: 2.25rem; min-width: 200px; }
    .stock-transfers-page .card-header .form-control,
    .stock-transfers-page .card-header .form-select {
        height: 38px;
        font-size: 0.8125rem;
        border-color: var(--st-border);
    }

    /* Primary "New Transfer" button */
    .stock-transfers-page .add-btn {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, var(--st-primary-dark), var(--st-primary));
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 9px 18px;
        font-weight: 600;
        font-size: 0.8125rem;
        text-decoration: none;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    .stock-transfers-page .add-btn:hover {
        color: #fff;
        box-shadow: 0 4px 10px rgba(29, 78, 216, 0.25);
        transform: translateY(-1px);
    }

    /* ---------- Table ---------- */
    .stock-transfers-page #transfersTable thead th {
        background: #f1f5f9;
        color: var(--st-text);
        font-weight: 700;
        font-size: 0.6875rem;
        letter-spacing: 0.03em;
        padding: 8px 12px;
        border-bottom: 1px solid var(--st-border);
    }

    /* Sort arrows are too faint (low opacity) against the light header background — darken them */
    .stock-transfers-page #transfersTable thead th span.dt-column-order:before,
    .stock-transfers-page #transfersTable thead th span.dt-column-order:after {
        color: #475569;
    }
    .stock-transfers-page #transfersTable thead th span.dt-column-order:before { opacity: .45; }
    .stock-transfers-page #transfersTable thead th span.dt-column-order:after { opacity: .9; }
    .stock-transfers-page #transfersTable thead th.dt-ordering-asc span.dt-column-order:before,
    .stock-transfers-page #transfersTable thead th.dt-ordering-desc span.dt-column-order:after {
        color: var(--st-primary);
        opacity: 1;
    }
    .stock-transfers-page #transfersTable tbody td {
        padding: 6px 12px;
        vertical-align: middle;
        border-bottom: 1px solid var(--st-border);
        font-size: 0.8125rem;
    }
    .stock-transfers-page #transfersTable tbody tr {
        transition: background 0.2s ease;
    }
    .stock-transfers-page #transfersTable tbody tr:hover {
        background: #f8fafc;
    }
    .stock-transfers-page #transfersTable tbody td code {
        font-weight: 600;
        color: var(--st-text);
    }

    /* Status pill — one per lifecycle stage */
    .stock-transfers-page .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .stock-transfers-page .status-pill .status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        display: inline-block;
    }
    .stock-transfers-page .status-draft     { background: #f1f5f9; color: var(--st-slate); }
    .stock-transfers-page .status-draft .status-dot     { background: var(--st-slate); }
    .stock-transfers-page .status-transit   { background: #fffbeb; color: var(--st-warning); }
    .stock-transfers-page .status-transit .status-dot   { background: var(--st-warning); }
    .stock-transfers-page .status-received  { background: #ecfdf5; color: var(--st-success); }
    .stock-transfers-page .status-received .status-dot  { background: var(--st-success); }
    .stock-transfers-page .status-cancelled { background: #fef2f2; color: var(--st-danger); }
    .stock-transfers-page .status-cancelled .status-dot { background: var(--st-danger); }

    /* Action buttons */
    .stock-transfers-page .action-btn {
        width: 28px;
        height: 28px;
        border-radius: 7px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
        transition: all 0.2s ease;
        text-decoration: none;
        border: 1px solid transparent;
    }
    .stock-transfers-page .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }
    .stock-transfers-page .action-view {
        color: #2563eb;
        background: #eff6ff;
        border-color: #bfdbfe;
    }
    .stock-transfers-page .action-edit {
        color: #059669;
        background: #ecfdf5;
        border-color: #a7f3d0;
    }
    .stock-transfers-page .action-post {
        color: #d97706;
        background: #fffbeb;
        border-color: #fde68a;
    }
    .stock-transfers-page .action-receive {
        color: #0891b2;
        background: #ecfeff;
        border-color: #a5f3fc;
    }
    .stock-transfers-page .action-cancel {
        color: #dc2626;
        background: #fef2f2;
        border-color: #fecaca;
    }

    /* DataTables "processing" loading indicator */
    .stock-transfers-page .dataTables_processing {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    .stock-transfers-page .st-loading {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--st-surface);
        border: 1px solid var(--st-border);
        padding: 8px 18px;
        border-radius: 999px;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--st-text);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.1);
    }
    .stock-transfers-page .st-loading .spinner-border {
        width: 1rem;
        height: 1rem;
        color: var(--st-primary);
        border-width: 0.15em;
    }

    /* Confirm-action modal icon (post / receive / cancel share the modal, colored per action) */
    .stock-transfers-page .action-modal-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        background: #fffbeb;
        color: var(--st-warning);
    }
    .stock-transfers-page .action-modal-icon.icon-post     { background: #fffbeb; color: var(--st-warning); }
    .stock-transfers-page .action-modal-icon.icon-receive  { background: #ecfeff; color: var(--st-info); }
    .stock-transfers-page .action-modal-icon.icon-cancel   { background: #fef2f2; color: var(--st-danger); }

    /* Hide DataTables built-in length+filter+info+paginate (we render our own slots) */
    #transfersTable_wrapper .dataTables_length,
    #transfersTable_wrapper .dataTables_filter { display: none !important; }

    /* The cloned DataTables info/pagination land inside our card-footer slots */
    #transfersInfoSlot .dataTables_info { padding: 0; color: var(--bs-body-color); font-size: 0.875rem; }
    #transfersPaginationSlot .pagination { margin-bottom: 0; }
    #transfersPaginationSlot .dataTables_paginate { margin: 0; }

    /* "Showing x to y..." info on the left, pagination on the right — overrides the
       app-wide pagination-left/info-right order, scoped to this page only. */
    .stock-transfers-page .card-footer #transfersInfoSlot { order: 1; }
    .stock-transfers-page .card-footer #transfersPaginationSlot { order: 2; }
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
        document.getElementById('transfersToastContainer').appendChild(el);
        const toast = new bootstrap.Toast(el, { delay: 3000 });
        el.addEventListener('hidden.bs.toast', () => el.remove());
        toast.show();
    }

    // ============= DataTable =============
    const dt = $('#transfersTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: false,
        order: [[2, 'desc']],
        ajax: {
            url: '{{ route('stock-transfers.data') }}',
            data: function (d) { d.status = $('#transferStatusFilter').val(); },
        },
        dom: 'rt<"datatables-tail"ip>',
        pageLength: 10,
        columns: [
            { data: 'DT_RowIndex',     name: 'DT_RowIndex',                    orderable: false, searchable: false, className: 'text-center' },
            { data: 'transfer_number', name: 'stock_transfers.transfer_number' },
            { data: 'transfer_date',   name: 'stock_transfers.transfer_date' },
            { data: 'from_label',      name: 'from_label', orderable: false, searchable: false },
            { data: 'to_label',        name: 'to_label',   orderable: false, searchable: false },
            { data: 'line_count',      name: 'line_count', orderable: false, searchable: false, className: 'text-end' },
            { data: 'status_badge',    name: 'stock_transfers.status', orderable: false, searchable: false },
            { data: 'actions',         name: 'actions',    orderable: false, searchable: false, className: 'text-center' },
        ],
        language: {
            info: 'Showing _START_ to _END_ of _TOTAL_ transfers',
            infoEmpty: 'No transfers found',
            infoFiltered: ' (filtered from _MAX_ total)',
            emptyTable: 'No transfers yet.',
            zeroRecords: 'No transfers match your search.',
            processing: '<div class="st-loading"><span class="spinner-border spinner-border-sm"></span>Loading transfers&hellip;</div>',
            paginate: {
                previous: '<i class="ti ti-chevron-left"></i>',
                next:     '<i class="ti ti-chevron-right"></i>',
            },
        },
        initComplete: function () {
            $('#transfersInfoSlot').append($('#transfersTable_info'));
            $('#transfersPaginationSlot').append($('.datatables-tail'));
        },
    });

    // ============= Custom search wire-up (debounced) =============
    let searchTimer;
    $('#transferSearch').on('keyup', function () {
        clearTimeout(searchTimer);
        const v = this.value;
        searchTimer = setTimeout(() => dt.search(v).draw(), 250);
    });

    // Per-page
    $('#transferPerPage').on('change', function () {
        dt.page.len(parseInt(this.value, 10)).draw();
    });

    // Status filter — server-side filter via the ajax `data` callback above
    $('#transferStatusFilter').on('change', () => dt.draw());

    // ============= Status action confirmation (Post / Receive / Cancel) =============
    const confirmModalEl = document.getElementById('confirmActionModal');
    const confirmModal = new bootstrap.Modal(confirmModalEl);
    let pendingUrl = null;

    const KIND_META = {
        post:    { icon: 'ti-send',  iconClass: 'icon-post',    btnClass: 'btn-warning', title: 'Post transfer?' },
        receive: { icon: 'ti-check', iconClass: 'icon-receive', btnClass: 'btn-info',    title: 'Receive transfer?' },
        cancel:  { icon: 'ti-ban',   iconClass: 'icon-cancel',  btnClass: 'btn-danger',  title: 'Cancel transfer?' },
    };

    $('#transfersTable tbody').on('click', '.js-status-action', function () {
        const url     = $(this).data('url');
        const kind     = $(this).data('kind') || 'post';
        const message  = $(this).data('confirm') || 'Are you sure?';
        const meta     = KIND_META[kind] || KIND_META.post;

        pendingUrl = url;

        $('#confirmActionModalLabel').text(meta.title);
        $('#confirmActionMessage').text(message);
        $('#confirmActionIconWrap').attr('class', 'action-modal-icon mx-auto mb-3 ' + meta.iconClass);
        $('#confirmActionIconGlyph').attr('class', 'ti ' + meta.icon);
        $('#confirmActionBtn').attr('class', 'btn ' + meta.btnClass);
        $('#confirmActionBtnIcon').attr('class', 'ti me-1 ' + meta.icon);
        $('#confirmActionBtnLabel').text(meta.title.replace('?', ''));

        confirmModal.show();
    });

    $('#confirmActionBtn').on('click', function () {
        if (!pendingUrl) return;
        const $btn = $(this).prop('disabled', true);

        $.ajax({
            url: pendingUrl,
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: function (res) {
                if (res.ok) {
                    dt.ajax.reload(null, false);
                    showToast('success', res.message || 'Action completed.');
                } else {
                    showToast('error', res.message || 'Action failed.');
                }
            },
            error: function (xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Action failed.';
                showToast('error', msg);
            },
            complete: function () {
                $btn.prop('disabled', false);
                pendingUrl = null;
                confirmModal.hide();
            },
        });
    });
});
</script>
@endpush
