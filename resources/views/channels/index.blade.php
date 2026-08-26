@extends('layout.app')

@section('title', 'Sales Channels')

@section('content')

<div class="container-fluid channels-page">

    {{-- Page title --}}
    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">
                <i class="ti ti-broadcast me-2"></i>Sales Channels
            </h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Channels</li>
            </ol>
        </div>
    </div>

    {{-- Toast notifications for AJAX actions (status toggle, delete) --}}
    <div class="toast-container position-fixed top-0 end-0 p-3" id="channelsToastContainer" style="z-index: 1080;"></div>

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
            <div class="stat-icon stat-icon-primary"><i class="ti ti-broadcast"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['channels_total'] }}</div>
                <div class="stat-label">Total Channels</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-success"><i class="ti ti-circle-check"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['channels_active'] }}</div>
                <div class="stat-label">Active Channels</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-danger"><i class="ti ti-circle-x"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['channels_inactive'] }}</div>
                <div class="stat-label">Inactive Channels</div>
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
                            <input id="channelSearch" type="search" class="form-control"
                                placeholder="Search channels..." />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1 flex-wrap">
                        <div>
                            <select id="channelPerPage" class="form-select form-control my-1 my-md-0">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="15">15</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                            </select>
                        </div>

                        <div class="app-search">
                            <select id="channelStatusFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <i class="ti ti-circle app-search-icon text-muted"></i>
                        </div>

                        @permission('channels.create')
                        <a href="{{ route('channels.create') }}" class="add-btn ms-1">
                            <i class="ti ti-plus fs-sm me-2"></i> New Channel
                        </a>
                        @endpermission
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table id="channelsTable" class="table table-custom table-centered table-hover w-100 mb-0">
                        <thead class="align-middle thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="text-center" style="width: 1%;">S.No</th>
                                <th class="text-center" style="width: 1%;"><i class="ti ti-photo me-1"></i>Icon</th>
                                <th><i class="ti ti-tag me-1"></i>Name</th>
                                <th><i class="ti ti-barcode me-1"></i>Code</th>
                                <th class="text-center"><i class="ti ti-arrows-sort me-1"></i>Order</th>
                                <th class="text-center"><i class="ti ti-shopping-cart me-1"></i>Sales</th>
                                <th class="text-center"><i class="ti ti-toggle-right me-1"></i>Status</th>
                                <th class="text-center" style="width: 1%;"><i class="ti ti-settings me-1"></i>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                {{-- Card footer: DataTables info + pagination get moved here --}}
                <div class="card-footer border-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div id="channelsInfoSlot" class="text-muted small"></div>
                        <div id="channelsPaginationSlot"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== Delete Confirmation Modal ==================== --}}
    <div class="modal fade" id="deleteChannelModal" tabindex="-1" aria-labelledby="deleteChannelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="delete-modal-icon mx-auto mb-3">
                        <i class="ti ti-trash"></i>
                    </div>
                    <h5 class="modal-title mb-2" id="deleteChannelModalLabel">Delete this channel?</h5>
                    <p class="text-muted mb-0">
                        Are you sure you want to delete <strong id="deleteChannelName"></strong>?
                        This is a soft delete and can be restored later if needed.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteChannelBtn">
                        <i class="ti ti-trash me-1"></i>Delete Channel
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
       Channels page — compact ERP styling
       Scoped entirely under .channels-page so nothing here leaks
       into other modules that share the same layout/theme classes.
       ========================================================== */
    .channels-page {
        --channels-primary: #1d4ed8;
        --channels-primary-dark: #1e3a8a;
        --channels-cyan: #14b8a6;
        --channels-success: #059669;
        --channels-warning: #d97706;
        --channels-danger: #dc2626;
        --channels-bg: #f8fafc;
        --channels-surface: #ffffff;
        --channels-border: #e2e8f0;
        --channels-text: #1e293b;
        --channels-text-muted: #64748b;
        padding-top: 0;
        padding-bottom: 20px;
    }

    /* ---------- Page header ---------- */
    .channels-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid var(--channels-border);
    }
    .channels-page .page-title-head > * { display: flex; align-items: center; }
    .channels-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        color: var(--channels-text);
        position: relative;
        padding-left: 12px;
    }
    .channels-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 2px;
        bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, var(--channels-primary), var(--channels-cyan));
    }
    .channels-page .page-main-title .ti-broadcast { color: var(--channels-primary); font-size: 1.1rem; }
    .channels-page .breadcrumb { font-size: 0.75rem; }

    /* ---------- Summary cards ---------- */
    .channels-page .stat-cards-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 16px;
    }
    .channels-page .stat-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--channels-surface);
        border: 1px solid var(--channels-border);
        border-radius: 10px;
        padding: 14px 16px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .channels-page .stat-icon {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    .channels-page .stat-icon-primary { background: #eff6ff; color: var(--channels-primary); }
    .channels-page .stat-icon-success { background: #ecfdf5; color: var(--channels-success); }
    .channels-page .stat-icon-danger  { background: #fef2f2; color: var(--channels-danger); }
    .channels-page .stat-value { font-size: 1.375rem; font-weight: 700; line-height: 1.2; color: var(--channels-text); }
    .channels-page .stat-label { font-size: 0.75rem; color: var(--channels-text-muted); font-weight: 500; }

    @media (max-width: 992px) {
        .channels-page .stat-cards-row { grid-template-columns: repeat(2, 1fr); }
    }

    /* ---------- Card ---------- */
    .channels-page .card {
        border: 1px solid var(--channels-border);
        border-radius: 10px;
        box-shadow: none;
    }
    .channels-page .card-header {
        padding: 12px 16px;
        background: var(--channels-surface);
    }
    .channels-page .card-footer { padding: 10px 16px; }

    /* Tighten the app-search wrapper so the dropdowns look right in the card header */
    .channels-page .app-search { position: relative; }
    .channels-page .app-search > .app-search-icon {
        position: absolute; right: 0.75rem; top: 50%;
        transform: translateY(-50%); pointer-events: none;
    }
    .channels-page .app-search > .form-control { padding-right: 2.25rem; min-width: 180px; }
    .channels-page .card-header .form-control,
    .channels-page .card-header .form-select {
        height: 38px;
        font-size: 0.8125rem;
        border-color: var(--channels-border);
    }

    /* Primary "New Channel" button */
    .channels-page .add-btn {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, var(--channels-primary-dark), var(--channels-primary));
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 9px 18px;
        font-weight: 600;
        font-size: 0.8125rem;
        text-decoration: none;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    .channels-page .add-btn:hover {
        color: #fff;
        box-shadow: 0 4px 10px rgba(29, 78, 216, 0.25);
        transform: translateY(-1px);
    }

    /* ---------- Table ---------- */
    .channels-page #channelsTable thead th {
        background: #f1f5f9;
        color: var(--channels-text);
        font-weight: 700;
        font-size: 0.6875rem;
        letter-spacing: 0.03em;
        padding: 8px 12px;
        border-bottom: 1px solid var(--channels-border);
    }

    /* Sort arrows are too faint (low opacity) against the light header background — darken them */
    .channels-page #channelsTable thead th span.dt-column-order:before,
    .channels-page #channelsTable thead th span.dt-column-order:after {
        color: #475569;
    }
    .channels-page #channelsTable thead th span.dt-column-order:before { opacity: .45; }
    .channels-page #channelsTable thead th span.dt-column-order:after { opacity: .9; }
    .channels-page #channelsTable thead th.dt-ordering-asc span.dt-column-order:before,
    .channels-page #channelsTable thead th.dt-ordering-desc span.dt-column-order:after {
        color: var(--channels-primary);
        opacity: 1;
    }
    .channels-page #channelsTable tbody td {
        padding: 6px 12px;
        vertical-align: middle;
        border-bottom: 1px solid var(--channels-border);
        font-size: 0.8125rem;
    }
    .channels-page #channelsTable tbody tr {
        transition: background 0.2s ease;
    }
    .channels-page #channelsTable tbody tr:hover {
        background: #f8fafc;
    }

    /* Channel icon badge (Tabler icon class stored per-channel, not an uploaded image) */
    .channels-page .channel-icon-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #eff6ff;
        color: var(--channels-primary);
        border: 1px solid #bfdbfe;
        font-size: 0.95rem;
    }
    .channels-page .channel-icon-empty {
        background: #f1f5f9;
        color: var(--channels-text-muted);
        border-color: var(--channels-border);
    }

    /* Channel name — bold, no image column on this module */
    .channels-page .channel-name {
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--channels-text);
    }

    /* Status pill */
    .channels-page .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .channels-page .status-pill .status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        display: inline-block;
    }
    .channels-page .status-active { background: #ecfdf5; color: var(--channels-success); }
    .channels-page .status-active .status-dot { background: var(--channels-success); }
    .channels-page .status-inactive { background: #fef2f2; color: var(--channels-danger); }
    .channels-page .status-inactive .status-dot { background: var(--channels-danger); }

    /* Action buttons */
    .channels-page .action-btn {
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
    .channels-page .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }
    .channels-page .action-view {
        color: #2563eb;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
    }
    .channels-page .action-edit {
        color: #059669;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
    }
    .channels-page .action-toggle {
        color: #d97706;
        background: #fffbeb;
        border: 1px solid #fde68a;
    }
    .channels-page .action-delete {
        color: #dc2626;
        background: #fef2f2;
        border: 1px solid #fecaca;
    }

    /* DataTables "processing" loading indicator */
    .channels-page .dataTables_processing {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    .channels-page .channels-loading {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--channels-surface);
        border: 1px solid var(--channels-border);
        padding: 8px 18px;
        border-radius: 999px;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--channels-text);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.1);
    }
    .channels-page .channels-loading .spinner-border {
        width: 1rem;
        height: 1rem;
        color: var(--channels-primary);
        border-width: 0.15em;
    }

    /* Delete confirmation modal */
    .channels-page .delete-modal-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fef2f2;
        color: var(--channels-danger);
        font-size: 1.5rem;
    }

    /* Hide DataTables built-in length+filter+info+paginate (we render our own slots) */
    #channelsTable_wrapper .dataTables_length,
    #channelsTable_wrapper .dataTables_filter { display: none !important; }

    /* The cloned DataTables info/pagination land inside our card-footer slots */
    #channelsInfoSlot .dataTables_info { padding: 0; color: var(--bs-body-color); font-size: 0.875rem; }
    #channelsPaginationSlot .pagination { margin-bottom: 0; }
    #channelsPaginationSlot .dataTables_paginate { margin: 0; }

    /* "Showing x to y..." info on the left, pagination on the right — overrides the
       app-wide pagination-left/info-right order, scoped to this page only. */
    .channels-page .card-footer #channelsInfoSlot { order: 1; }
    .channels-page .card-footer #channelsPaginationSlot { order: 2; }
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
        document.getElementById('channelsToastContainer').appendChild(el);
        const toast = new bootstrap.Toast(el, { delay: 3000 });
        el.addEventListener('hidden.bs.toast', () => el.remove());
        toast.show();
    }

    // ============= DataTable =============
    const dt = $('#channelsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: false,
        order: [[4, 'asc']],
        ajax: {
            url: '{{ route('channels.data') }}',
            type: 'GET',
            data: function (d) {
                d.status = $('#channelStatusFilter').val();
            },
        },
        dom: 'rt<"datatables-tail"ip>',
        pageLength: 10,
        columns: [
            { data: 'DT_RowIndex',   name: 'DT_RowIndex',   orderable: false, searchable: false, className: 'text-center' },
            { data: 'icon_preview',  name: 'icon',          orderable: false, searchable: false, className: 'text-center' },
            { data: 'name',          name: 'name' },
            { data: 'code',          name: 'code',          render: (d) => '<code class="text-muted">' + d + '</code>' },
            { data: 'display_order', name: 'display_order', className: 'text-center' },
            { data: 'sales_count',   name: 'sales_count',   orderable: false, searchable: false, className: 'text-center fw-semibold' },
            { data: 'status',        name: 'status',        orderable: false, searchable: false, className: 'text-center' },
            { data: 'actions',       name: 'actions',       orderable: false, searchable: false, className: 'text-center' },
        ],
        language: {
            info: 'Showing _START_ to _END_ of _TOTAL_ channels',
            infoEmpty: 'No channels found',
            infoFiltered: ' (filtered from _MAX_ total)',
            emptyTable: 'No channels yet. Click "New Channel" to get started.',
            zeroRecords: 'No channels match your search.',
            processing: '<div class="channels-loading"><span class="spinner-border spinner-border-sm"></span>Loading channels&hellip;</div>',
            paginate: {
                previous: '<i class="ti ti-chevron-left"></i>',
                next:     '<i class="ti ti-chevron-right"></i>',
            },
        },
        initComplete: function () {
            // Move DataTables-rendered info & pagination into our card-footer slots
            $('#channelsInfoSlot').append($('#channelsTable_info'));
            $('#channelsPaginationSlot').append($('.datatables-tail'));
        },
    });

    // ============= Custom search wire-up (debounced) =============
    let searchTimer;
    $('#channelSearch').on('keyup', function () {
        clearTimeout(searchTimer);
        const v = this.value;
        searchTimer = setTimeout(() => dt.search(v).draw(), 250);
    });

    // Per-page
    $('#channelPerPage').on('change', function () {
        dt.page.len(parseInt(this.value, 10)).draw();
    });

    // Status filter — sent as an extra `status` param on the ajax request (see controller's data())
    $('#channelStatusFilter').on('change', function () {
        dt.draw();
    });

    // ============= Toggle Status =============
    $('#channelsTable tbody').on('click', '.js-toggle-channel', function () {
        const url = $(this).data('url');
        $.ajax({
            url: url,
            type: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: function (res) {
                if (res && res.ok !== false) {
                    dt.ajax.reload(null, false);
                    showToast('success', (res && res.label) ? 'Channel marked as ' + res.label + '.' : 'Status updated.');
                } else {
                    showToast('error', (res && res.message) || 'Failed to update status.');
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
    const deleteModalEl = document.getElementById('deleteChannelModal');
    const deleteModal = new bootstrap.Modal(deleteModalEl);
    let pendingDeleteUrl = null;

    $('#channelsTable tbody').on('click', '.js-delete-channel', function () {
        const name     = $(this).data('name');
        const hasSales = $(this).data('has-sales');

        // Client-side pre-check mirrors the server-side delete-protection guard
        // (a channel with sales recorded against it can never be deleted).
        if (hasSales) {
            showToast('error', 'Cannot delete "' + name + '" — it has sales recorded. Deactivate it instead.');
            return;
        }

        pendingDeleteUrl = $(this).data('url');
        $('#deleteChannelName').text(name);
        deleteModal.show();
    });

    $('#confirmDeleteChannelBtn').on('click', function () {
        if (!pendingDeleteUrl) return;
        const $btn = $(this).prop('disabled', true);

        $.ajax({
            url: pendingDeleteUrl,
            type: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: function (res) {
                if (res && res.ok !== false) {
                    dt.ajax.reload(null, false);
                    showToast('success', (res && res.message) || 'Channel deleted successfully.');
                } else {
                    showToast('error', (res && res.message) || 'Could not delete channel.');
                }
            },
            error: function (xhr) {
                // Covers the delete-protected-by-sale guard (422 response from the controller).
                const msg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Failed to delete channel.';
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
