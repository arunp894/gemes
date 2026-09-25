@extends('layout.app')

@section('title', 'Colors')

@section('content')

<div class="container-fluid colors-page">

    {{-- Page title --}}
    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">
                <i class="ti ti-palette me-2"></i>Colors
            </h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Colors</li>
            </ol>
        </div>
    </div>

    {{-- Toast notifications for AJAX actions (status toggle, delete) --}}
    <div class="toast-container position-fixed top-0 end-0 p-3" id="colorsToastContainer" style="z-index: 1080;"></div>

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
            <div class="stat-icon stat-icon-primary"><i class="ti ti-palette"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['colors_total'] }}</div>
                <div class="stat-label">Total Colors</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-success"><i class="ti ti-circle-check"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['colors_active'] }}</div>
                <div class="stat-label">Active Colors</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-danger"><i class="ti ti-circle-x"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['colors_inactive'] }}</div>
                <div class="stat-label">Inactive Colors</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                {{-- Card header: search + status filter + add button --}}
                <div class="card-header border-light justify-content-between">
                    <div class="d-flex gap-2">
                        <div class="app-search">
                            <input id="colorSearch" type="search" class="form-control"
                                placeholder="Search colors..." />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1 flex-wrap">
                        <div class="app-search">
                            <select id="colorStatusFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <i class="ti ti-circle app-search-icon text-muted"></i>
                        </div>

                        @permission('colors.create')
                        <a href="{{ route('colors.create') }}" class="add-btn ms-1">
                            <i class="ti ti-plus fs-sm me-2"></i> New Color
                        </a>
                        @endpermission
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table id="colorsTable" class="table table-custom table-centered table-hover w-100 mb-0">
                        <thead class="align-middle thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="text-center" style="width: 1%;">S.No</th>
                                <th><i class="ti ti-tag me-1"></i>Name</th>
                                <th><i class="ti ti-barcode me-1"></i>Code</th>
                                <th class="text-center"><i class="ti ti-arrows-sort me-1"></i>Order</th>
                                <th class="text-center"><i class="ti ti-toggle-right me-1"></i>Status</th>
                                <th class="text-center" style="width: 1%;"><i class="ti ti-settings me-1"></i>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                {{-- Card footer: DataTables info + pagination get moved here --}}
                <div class="card-footer border-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div id="colorsInfoSlot" class="text-muted small"></div>
                        <div class="d-flex align-items-center gap-2 footer-pagination-group">
                            <select id="colorPerPage" class="form-select form-select-sm" style="width: auto;">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="15">15</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                            </select>
                            <div id="colorsPaginationSlot"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== Delete Confirmation Modal ==================== --}}
    <div class="modal fade" id="deleteColorModal" tabindex="-1" aria-labelledby="deleteColorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="delete-modal-icon mx-auto mb-3">
                        <i class="ti ti-trash"></i>
                    </div>
                    <h5 class="modal-title mb-2" id="deleteColorModalLabel">Delete this color?</h5>
                    <p class="text-muted mb-0">
                        Are you sure you want to delete <strong id="deleteColorName"></strong>?
                        This is a soft delete and can be restored later if needed.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteColorBtn">
                        <i class="ti ti-trash me-1"></i>Delete Color
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
       Colors page — compact ERP styling
       Scoped entirely under .colors-page so nothing here leaks
       into other modules that share the same layout/theme classes.
       ========================================================== */
    .colors-page {
        --colors-primary: #1d4ed8;
        --colors-primary-dark: #1e3a8a;
        --colors-cyan: #14b8a6;
        --colors-success: #059669;
        --colors-warning: #d97706;
        --colors-danger: #dc2626;
        --colors-bg: #f8fafc;
        --colors-surface: #ffffff;
        --colors-border: #e2e8f0;
        --colors-text: #1e293b;
        --colors-text-muted: #64748b;
        padding-top: 0;
        padding-bottom: 20px;
    }

    /* ---------- Page header ---------- */
    .colors-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid var(--colors-border);
    }
    .colors-page .page-title-head > * { display: flex; align-items: center; }
    .colors-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        color: var(--colors-text);
        position: relative;
        padding-left: 12px;
    }
    .colors-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 2px;
        bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, var(--colors-primary), var(--colors-cyan));
    }
    .colors-page .page-main-title .ti-palette { color: var(--colors-primary); font-size: 1.1rem; }
    .colors-page .breadcrumb { font-size: 0.75rem; }

    /* ---------- Summary cards ---------- */
    .colors-page .stat-cards-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 16px;
    }
    .colors-page .stat-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--colors-surface);
        border: 1px solid var(--colors-border);
        border-radius: 10px;
        padding: 14px 16px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .colors-page .stat-icon {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    .colors-page .stat-icon-primary { background: #eff6ff; color: var(--colors-primary); }
    .colors-page .stat-icon-success { background: #ecfdf5; color: var(--colors-success); }
    .colors-page .stat-icon-danger  { background: #fef2f2; color: var(--colors-danger); }
    .colors-page .stat-value { font-size: 1.375rem; font-weight: 700; line-height: 1.2; color: var(--colors-text); }
    .colors-page .stat-label { font-size: 0.75rem; color: var(--colors-text-muted); font-weight: 500; }

    @media (max-width: 992px) {
        .colors-page .stat-cards-row { grid-template-columns: repeat(2, 1fr); }
    }

    /* ---------- Card ---------- */
    .colors-page .card {
        border: 1px solid var(--colors-border);
        border-radius: 10px;
        box-shadow: none;
    }
    .colors-page .card-header {
        padding: 12px 16px;
        background: var(--colors-surface);
    }
    .colors-page .card-footer { padding: 10px 16px; }

    .colors-page .app-search { position: relative; }
    .colors-page .app-search > .app-search-icon {
        position: absolute; right: 0.75rem; top: 50%;
        transform: translateY(-50%); pointer-events: none;
    }
    .colors-page .app-search > .form-control { padding-right: 2.25rem; min-width: 180px; }
    .colors-page .card-header .form-control,
    .colors-page .card-header .form-select {
        height: 38px;
        font-size: 0.8125rem;
        border-color: var(--colors-border);
    }

    /* Primary "New Color" button */
    .colors-page .add-btn {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, var(--colors-primary-dark), var(--colors-primary));
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 9px 18px;
        font-weight: 600;
        font-size: 0.8125rem;
        text-decoration: none;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    .colors-page .add-btn:hover {
        color: #fff;
        box-shadow: 0 4px 10px rgba(29, 78, 216, 0.25);
        transform: translateY(-1px);
    }

    /* ---------- Table ---------- */
    .colors-page #colorsTable thead th {
        background: #f1f5f9;
        color: var(--colors-text);
        font-weight: 700;
        font-size: 0.6875rem;
        letter-spacing: 0.03em;
        padding: 8px 12px;
        border-bottom: 1px solid var(--colors-border);
    }

    .colors-page #colorsTable thead th span.dt-column-order:before,
    .colors-page #colorsTable thead th span.dt-column-order:after {
        color: #475569;
    }
    .colors-page #colorsTable thead th span.dt-column-order:before { opacity: .45; }
    .colors-page #colorsTable thead th span.dt-column-order:after { opacity: .9; }
    .colors-page #colorsTable thead th.dt-ordering-asc span.dt-column-order:before,
    .colors-page #colorsTable thead th.dt-ordering-desc span.dt-column-order:after {
        color: var(--colors-primary);
        opacity: 1;
    }
    .colors-page #colorsTable tbody td {
        padding: 6px 12px;
        vertical-align: middle;
        border-bottom: 1px solid var(--colors-border);
        font-size: 0.8125rem;
    }
    .colors-page #colorsTable tbody tr {
        transition: background 0.2s ease;
    }
    .colors-page #colorsTable tbody tr:hover {
        background: #f8fafc;
    }

    .colors-page .color-name {
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--colors-text);
    }

    /* Status pill */
    .colors-page .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .colors-page .status-pill .status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        display: inline-block;
    }
    .colors-page .status-active { background: #ecfdf5; color: var(--colors-success); }
    .colors-page .status-active .status-dot { background: var(--colors-success); }
    .colors-page .status-inactive { background: #fef2f2; color: var(--colors-danger); }
    .colors-page .status-inactive .status-dot { background: var(--colors-danger); }

    /* Action buttons */
    .colors-page .action-btn {
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
    .colors-page .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }
    .colors-page .action-view {
        color: #2563eb;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
    }
    .colors-page .action-edit {
        color: #059669;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
    }
    .colors-page .action-toggle {
        color: #d97706;
        background: #fffbeb;
        border: 1px solid #fde68a;
    }
    .colors-page .action-delete {
        color: #dc2626;
        background: #fef2f2;
        border: 1px solid #fecaca;
    }

    /* DataTables "processing" loading indicator */
    .colors-page .dataTables_processing {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    .colors-page .colors-loading {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--colors-surface);
        border: 1px solid var(--colors-border);
        padding: 8px 18px;
        border-radius: 999px;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--colors-text);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.1);
    }
    .colors-page .colors-loading .spinner-border {
        width: 1rem;
        height: 1rem;
        color: var(--colors-primary);
        border-width: 0.15em;
    }

    /* Delete confirmation modal */
    .colors-page .delete-modal-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fef2f2;
        color: var(--colors-danger);
        font-size: 1.5rem;
    }

    /* Hide DataTables built-in length+filter+info+paginate (we render our own slots) */
    #colorsTable_wrapper .dataTables_length,
    #colorsTable_wrapper .dataTables_filter { display: none !important; }

    #colorsInfoSlot .dataTables_info { padding: 0; color: var(--bs-body-color); font-size: 0.875rem; }
    #colorsPaginationSlot .pagination { margin-bottom: 0; }
    #colorsPaginationSlot .dataTables_paginate { margin: 0; }

    .colors-page .card-footer #colorsInfoSlot { order: 1; }
    .colors-page .card-footer .footer-pagination-group { order: 2; }
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
        document.getElementById('colorsToastContainer').appendChild(el);
        const toast = new bootstrap.Toast(el, { delay: 3000 });
        el.addEventListener('hidden.bs.toast', () => el.remove());
        toast.show();
    }

    // ============= DataTable =============
    const dt = $('#colorsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: false,
        order: [[3, 'asc']],
        ajax: {
            url: '{{ route('colors.data') }}',
            type: 'GET',
            data: function (d) {
                d.status = $('#colorStatusFilter').val();
            },
        },
        dom: 'rt<"datatables-tail"ip>',
        pageLength: 10,
        columns: [
            { data: 'DT_RowIndex',   name: 'DT_RowIndex',   orderable: false, searchable: false, className: 'text-center' },
            { data: 'name',          name: 'name' },
            { data: 'code',          name: 'code',          render: (d) => '<code class="text-muted">' + d + '</code>' },
            { data: 'display_order', name: 'display_order', className: 'text-center' },
            { data: 'status',        name: 'status',        orderable: false, searchable: false, className: 'text-center' },
            { data: 'actions',       name: 'actions',       orderable: false, searchable: false, className: 'text-center' },
        ],
        language: {
            info: 'Showing _START_ to _END_ of _TOTAL_ colors',
            infoEmpty: 'No colors found',
            infoFiltered: ' (filtered from _MAX_ total)',
            emptyTable: 'No colors yet. Click "New Color" to get started.',
            zeroRecords: 'No colors match your search.',
            processing: '<div class="colors-loading"><span class="spinner-border spinner-border-sm"></span>Loading colors&hellip;</div>',
            paginate: {
                previous: '<i class="ti ti-chevron-left"></i>',
                next:     '<i class="ti ti-chevron-right"></i>',
            },
        },
        initComplete: function () {
            $('#colorsInfoSlot').append($('#colorsTable_info'));
            $('#colorsPaginationSlot').append($('.datatables-tail'));
        },
    });

    // ============= Custom search wire-up (debounced) =============
    let searchTimer;
    $('#colorSearch').on('keyup', function () {
        clearTimeout(searchTimer);
        const v = this.value;
        searchTimer = setTimeout(() => dt.search(v).draw(), 250);
    });

    // Per-page
    $('#colorPerPage').on('change', function () {
        dt.page.len(parseInt(this.value, 10)).draw();
    });

    // Status filter
    $('#colorStatusFilter').on('change', function () {
        dt.draw();
    });

    // ============= Toggle Status =============
    $('#colorsTable tbody').on('click', '.js-toggle-color', function () {
        const url = $(this).data('url');
        $.ajax({
            url: url,
            type: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: function (res) {
                if (res && res.ok !== false) {
                    dt.ajax.reload(null, false);
                    showToast('success', (res && res.label) ? 'Color marked as ' + res.label + '.' : 'Status updated.');
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
    const deleteModalEl = document.getElementById('deleteColorModal');
    const deleteModal = new bootstrap.Modal(deleteModalEl);
    let pendingDeleteUrl = null;

    $('#colorsTable tbody').on('click', '.js-delete-color', function () {
        const name = $(this).data('name');
        pendingDeleteUrl = $(this).data('url');
        $('#deleteColorName').text(name);
        deleteModal.show();
    });

    $('#confirmDeleteColorBtn').on('click', function () {
        if (!pendingDeleteUrl) return;
        const $btn = $(this).prop('disabled', true);

        $.ajax({
            url: pendingDeleteUrl,
            type: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: function (res) {
                if (res && res.ok !== false) {
                    dt.ajax.reload(null, false);
                    showToast('success', (res && res.message) || 'Color deleted successfully.');
                } else {
                    showToast('error', (res && res.message) || 'Could not delete color.');
                }
            },
            error: function (xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Failed to delete color.';
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
