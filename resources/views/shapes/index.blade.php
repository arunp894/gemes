@extends('layout.app')

@section('title', 'Shapes')

@section('content')

<div class="container-fluid shapes-page">

    {{-- Page title --}}
    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">
                <i class="ti ti-diamond me-2"></i>Shapes
            </h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Shapes</li>
            </ol>
        </div>
    </div>

    {{-- Toast notifications for AJAX actions (status toggle, delete) --}}
    <div class="toast-container position-fixed top-0 end-0 p-3" id="shapesToastContainer" style="z-index: 1080;"></div>

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
            <div class="stat-icon stat-icon-primary"><i class="ti ti-diamond"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['shapes_total'] }}</div>
                <div class="stat-label">Total Shapes</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-success"><i class="ti ti-circle-check"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['shapes_active'] }}</div>
                <div class="stat-label">Active Shapes</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-danger"><i class="ti ti-circle-x"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['shapes_inactive'] }}</div>
                <div class="stat-label">Inactive Shapes</div>
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
                            <input id="shapeSearch" type="search" class="form-control"
                                placeholder="Search shapes..." />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1 flex-wrap">
                        <div class="app-search">
                            <select id="shapeStatusFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <i class="ti ti-circle app-search-icon text-muted"></i>
                        </div>

                        @permission('shapes.create')
                        <a href="{{ route('shapes.create') }}" class="add-btn ms-1">
                            <i class="ti ti-plus fs-sm me-2"></i> New Shape
                        </a>
                        @endpermission
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table id="shapesTable" class="table table-custom table-centered table-hover w-100 mb-0">
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
                        <div id="shapesInfoSlot" class="text-muted small"></div>
                        <div class="d-flex align-items-center gap-2 footer-pagination-group">
                            <select id="shapePerPage" class="form-select form-select-sm" style="width: auto;">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="15">15</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                            </select>
                            <div id="shapesPaginationSlot"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== Delete Confirmation Modal ==================== --}}
    <div class="modal fade" id="deleteShapeModal" tabindex="-1" aria-labelledby="deleteShapeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="delete-modal-icon mx-auto mb-3">
                        <i class="ti ti-trash"></i>
                    </div>
                    <h5 class="modal-title mb-2" id="deleteShapeModalLabel">Delete this shape?</h5>
                    <p class="text-muted mb-0">
                        Are you sure you want to delete <strong id="deleteShapeName"></strong>?
                        This is a soft delete and can be restored later if needed.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteShapeBtn">
                        <i class="ti ti-trash me-1"></i>Delete Shape
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
       Shapes page — compact ERP styling
       Scoped entirely under .shapes-page so nothing here leaks
       into other modules that share the same layout/theme classes.
       ========================================================== */
    .shapes-page {
        --shapes-primary: #1d4ed8;
        --shapes-primary-dark: #1e3a8a;
        --shapes-cyan: #14b8a6;
        --shapes-success: #059669;
        --shapes-warning: #d97706;
        --shapes-danger: #dc2626;
        --shapes-bg: #f8fafc;
        --shapes-surface: #ffffff;
        --shapes-border: #e2e8f0;
        --shapes-text: #1e293b;
        --shapes-text-muted: #64748b;
        padding-top: 0;
        padding-bottom: 20px;
    }

    /* ---------- Page header ---------- */
    .shapes-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid var(--shapes-border);
    }
    .shapes-page .page-title-head > * { display: flex; align-items: center; }
    .shapes-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        color: var(--shapes-text);
        position: relative;
        padding-left: 12px;
    }
    .shapes-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 2px;
        bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, var(--shapes-primary), var(--shapes-cyan));
    }
    .shapes-page .page-main-title .ti-diamond { color: var(--shapes-primary); font-size: 1.1rem; }
    .shapes-page .breadcrumb { font-size: 0.75rem; }

    /* ---------- Summary cards ---------- */
    .shapes-page .stat-cards-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 16px;
    }
    .shapes-page .stat-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--shapes-surface);
        border: 1px solid var(--shapes-border);
        border-radius: 10px;
        padding: 14px 16px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .shapes-page .stat-icon {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    .shapes-page .stat-icon-primary { background: #eff6ff; color: var(--shapes-primary); }
    .shapes-page .stat-icon-success { background: #ecfdf5; color: var(--shapes-success); }
    .shapes-page .stat-icon-danger  { background: #fef2f2; color: var(--shapes-danger); }
    .shapes-page .stat-value { font-size: 1.375rem; font-weight: 700; line-height: 1.2; color: var(--shapes-text); }
    .shapes-page .stat-label { font-size: 0.75rem; color: var(--shapes-text-muted); font-weight: 500; }

    @media (max-width: 992px) {
        .shapes-page .stat-cards-row { grid-template-columns: repeat(2, 1fr); }
    }

    /* ---------- Card ---------- */
    .shapes-page .card {
        border: 1px solid var(--shapes-border);
        border-radius: 10px;
        box-shadow: none;
    }
    .shapes-page .card-header {
        padding: 12px 16px;
        background: var(--shapes-surface);
    }
    .shapes-page .card-footer { padding: 10px 16px; }

    .shapes-page .app-search { position: relative; }
    .shapes-page .app-search > .app-search-icon {
        position: absolute; right: 0.75rem; top: 50%;
        transform: translateY(-50%); pointer-events: none;
    }
    .shapes-page .app-search > .form-control { padding-right: 2.25rem; min-width: 180px; }
    .shapes-page .card-header .form-control,
    .shapes-page .card-header .form-select {
        height: 38px;
        font-size: 0.8125rem;
        border-color: var(--shapes-border);
    }

    /* Primary "New Shape" button */
    .shapes-page .add-btn {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, var(--shapes-primary-dark), var(--shapes-primary));
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 9px 18px;
        font-weight: 600;
        font-size: 0.8125rem;
        text-decoration: none;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    .shapes-page .add-btn:hover {
        color: #fff;
        box-shadow: 0 4px 10px rgba(29, 78, 216, 0.25);
        transform: translateY(-1px);
    }

    /* ---------- Table ---------- */
    .shapes-page #shapesTable thead th {
        background: #f1f5f9;
        color: var(--shapes-text);
        font-weight: 700;
        font-size: 0.6875rem;
        letter-spacing: 0.03em;
        padding: 8px 12px;
        border-bottom: 1px solid var(--shapes-border);
    }

    .shapes-page #shapesTable thead th span.dt-column-order:before,
    .shapes-page #shapesTable thead th span.dt-column-order:after {
        color: #475569;
    }
    .shapes-page #shapesTable thead th span.dt-column-order:before { opacity: .45; }
    .shapes-page #shapesTable thead th span.dt-column-order:after { opacity: .9; }
    .shapes-page #shapesTable thead th.dt-ordering-asc span.dt-column-order:before,
    .shapes-page #shapesTable thead th.dt-ordering-desc span.dt-column-order:after {
        color: var(--shapes-primary);
        opacity: 1;
    }
    .shapes-page #shapesTable tbody td {
        padding: 6px 12px;
        vertical-align: middle;
        border-bottom: 1px solid var(--shapes-border);
        font-size: 0.8125rem;
    }
    .shapes-page #shapesTable tbody tr {
        transition: background 0.2s ease;
    }
    .shapes-page #shapesTable tbody tr:hover {
        background: #f8fafc;
    }

    .shapes-page .shape-name {
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--shapes-text);
    }

    /* Status pill */
    .shapes-page .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .shapes-page .status-pill .status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        display: inline-block;
    }
    .shapes-page .status-active { background: #ecfdf5; color: var(--shapes-success); }
    .shapes-page .status-active .status-dot { background: var(--shapes-success); }
    .shapes-page .status-inactive { background: #fef2f2; color: var(--shapes-danger); }
    .shapes-page .status-inactive .status-dot { background: var(--shapes-danger); }

    /* Action buttons */
    .shapes-page .action-btn {
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
    .shapes-page .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }
    .shapes-page .action-view {
        color: #2563eb;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
    }
    .shapes-page .action-edit {
        color: #059669;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
    }
    .shapes-page .action-toggle {
        color: #d97706;
        background: #fffbeb;
        border: 1px solid #fde68a;
    }
    .shapes-page .action-delete {
        color: #dc2626;
        background: #fef2f2;
        border: 1px solid #fecaca;
    }

    /* DataTables "processing" loading indicator */
    .shapes-page .dataTables_processing {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    .shapes-page .shapes-loading {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--shapes-surface);
        border: 1px solid var(--shapes-border);
        padding: 8px 18px;
        border-radius: 999px;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--shapes-text);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.1);
    }
    .shapes-page .shapes-loading .spinner-border {
        width: 1rem;
        height: 1rem;
        color: var(--shapes-primary);
        border-width: 0.15em;
    }

    /* Delete confirmation modal */
    .shapes-page .delete-modal-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fef2f2;
        color: var(--shapes-danger);
        font-size: 1.5rem;
    }

    /* Hide DataTables built-in length+filter+info+paginate (we render our own slots) */
    #shapesTable_wrapper .dataTables_length,
    #shapesTable_wrapper .dataTables_filter { display: none !important; }

    #shapesInfoSlot .dataTables_info { padding: 0; color: var(--bs-body-color); font-size: 0.875rem; }
    #shapesPaginationSlot .pagination { margin-bottom: 0; }
    #shapesPaginationSlot .dataTables_paginate { margin: 0; }

    .shapes-page .card-footer #shapesInfoSlot { order: 1; }
    .shapes-page .card-footer .footer-pagination-group { order: 2; }
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
        document.getElementById('shapesToastContainer').appendChild(el);
        const toast = new bootstrap.Toast(el, { delay: 3000 });
        el.addEventListener('hidden.bs.toast', () => el.remove());
        toast.show();
    }

    // ============= DataTable =============
    const dt = $('#shapesTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: false,
        order: [[3, 'asc']],
        ajax: {
            url: '{{ route('shapes.data') }}',
            type: 'GET',
            data: function (d) {
                d.status = $('#shapeStatusFilter').val();
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
            info: 'Showing _START_ to _END_ of _TOTAL_ shapes',
            infoEmpty: 'No shapes found',
            infoFiltered: ' (filtered from _MAX_ total)',
            emptyTable: 'No shapes yet. Click "New Shape" to get started.',
            zeroRecords: 'No shapes match your search.',
            processing: '<div class="shapes-loading"><span class="spinner-border spinner-border-sm"></span>Loading shapes&hellip;</div>',
            paginate: {
                previous: '<i class="ti ti-chevron-left"></i>',
                next:     '<i class="ti ti-chevron-right"></i>',
            },
        },
        initComplete: function () {
            $('#shapesInfoSlot').append($('#shapesTable_info'));
            $('#shapesPaginationSlot').append($('.datatables-tail'));
        },
    });

    // ============= Custom search wire-up (debounced) =============
    let searchTimer;
    $('#shapeSearch').on('keyup', function () {
        clearTimeout(searchTimer);
        const v = this.value;
        searchTimer = setTimeout(() => dt.search(v).draw(), 250);
    });

    // Per-page
    $('#shapePerPage').on('change', function () {
        dt.page.len(parseInt(this.value, 10)).draw();
    });

    // Status filter
    $('#shapeStatusFilter').on('change', function () {
        dt.draw();
    });

    // ============= Toggle Status =============
    $('#shapesTable tbody').on('click', '.js-toggle-shape', function () {
        const url = $(this).data('url');
        $.ajax({
            url: url,
            type: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: function (res) {
                if (res && res.ok !== false) {
                    dt.ajax.reload(null, false);
                    showToast('success', (res && res.label) ? 'Shape marked as ' + res.label + '.' : 'Status updated.');
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
    const deleteModalEl = document.getElementById('deleteShapeModal');
    const deleteModal = new bootstrap.Modal(deleteModalEl);
    let pendingDeleteUrl = null;

    $('#shapesTable tbody').on('click', '.js-delete-shape', function () {
        const name = $(this).data('name');
        pendingDeleteUrl = $(this).data('url');
        $('#deleteShapeName').text(name);
        deleteModal.show();
    });

    $('#confirmDeleteShapeBtn').on('click', function () {
        if (!pendingDeleteUrl) return;
        const $btn = $(this).prop('disabled', true);

        $.ajax({
            url: pendingDeleteUrl,
            type: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: function (res) {
                if (res && res.ok !== false) {
                    dt.ajax.reload(null, false);
                    showToast('success', (res && res.message) || 'Shape deleted successfully.');
                } else {
                    showToast('error', (res && res.message) || 'Could not delete shape.');
                }
            },
            error: function (xhr) {
                const msg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Failed to delete shape.';
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
