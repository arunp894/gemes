@extends('layout.app')

@section('title', 'Racks')

@section('content')

<div class="container-fluid racks-page">

    {{-- Page title --}}
    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Racks</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Procurement</a></li>
                <li class="breadcrumb-item active">Racks</li>
            </ol>
        </div>
    </div>

    {{-- Toast notifications for AJAX actions (status toggle, delete) --}}
    <div class="toast-container position-fixed top-0 end-0 p-3" id="racksToastContainer" style="z-index: 1080;"></div>

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
            <div class="stat-icon stat-icon-primary"><i class="ti ti-building-warehouse"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['racks_total'] }}</div>
                <div class="stat-label">Total Racks</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-success"><i class="ti ti-circle-check"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['racks_active'] }}</div>
                <div class="stat-label">Active Racks</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-danger"><i class="ti ti-circle-x"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['racks_inactive'] }}</div>
                <div class="stat-label">Inactive Racks</div>
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
                            <input id="rackSearch" type="search" class="form-control"
                                placeholder="Search racks..." />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1">
                        <div class="app-search">
                            <select id="rackStatusFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <i class="ti ti-circle app-search-icon text-muted"></i>
                        </div>

                        @permission('racks.create')
                        <a href="{{ route('racks.create') }}" class="add-btn ms-1">
                            <i class="ti ti-plus fs-sm me-2"></i> Add Rack
                        </a>
                        @endpermission
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table id="racksTable" class="table table-custom table-centered table-hover w-100 mb-0">
                        <thead class="align-middle thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="text-center" style="width: 1%;">S.No</th>
                                <th><i class="ti ti-barcode me-1"></i>Code</th>
                                <th><i class="ti ti-box me-1"></i>Name</th>
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
                        <div id="racksInfoSlot" class="text-muted small"></div>
                        <div class="d-flex align-items-center gap-2 footer-pagination-group">
                            <select id="rackPerPage" class="form-select form-select-sm" style="width: auto;">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="15">15</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                            </select>
                            <div id="racksPaginationSlot"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== Delete Confirmation Modal ==================== --}}
    <div class="modal fade" id="deleteRackModal" tabindex="-1" aria-labelledby="deleteRackModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="delete-modal-icon mx-auto mb-3">
                        <i class="ti ti-trash"></i>
                    </div>
                    <h5 class="modal-title mb-2" id="deleteRackModalLabel">Delete this rack?</h5>
                    <p class="text-muted mb-0">
                        Are you sure you want to delete <strong id="deleteRackName"></strong>?
                        This is a soft delete and can be restored later if needed.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteRackBtn">
                        <i class="ti ti-trash me-1"></i>Delete Rack
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
       Racks page — compact ERP styling
       Scoped entirely under .racks-page so nothing here leaks
       into other modules that share the same layout/theme classes.
       ========================================================== */
    .racks-page {
        --racks-primary: #1d4ed8;
        --racks-primary-dark: #1e3a8a;
        --racks-cyan: #14b8a6;
        --racks-success: #059669;
        --racks-warning: #d97706;
        --racks-danger: #dc2626;
        --racks-bg: #f8fafc;
        --racks-surface: #ffffff;
        --racks-border: #e2e8f0;
        --racks-text: #1e293b;
        --racks-text-muted: #64748b;
        padding-top: 0;
        padding-bottom: 20px;
    }

    /* ---------- Page header ---------- */
    .racks-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid var(--racks-border);
    }
    .racks-page .page-title-head > * { display: flex; align-items: center; }
    .racks-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        color: var(--racks-text);
        position: relative;
        padding-left: 12px;
    }
    .racks-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 2px;
        bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, var(--racks-primary), var(--racks-cyan));
    }
    .racks-page .breadcrumb { font-size: 0.75rem; }

    /* ---------- Summary cards ---------- */
    .racks-page .stat-cards-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 16px;
    }
    .racks-page .stat-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--racks-surface);
        border: 1px solid var(--racks-border);
        border-radius: 10px;
        padding: 14px 16px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .racks-page .stat-icon {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    .racks-page .stat-icon-primary { background: #eff6ff; color: var(--racks-primary); }
    .racks-page .stat-icon-success { background: #ecfdf5; color: var(--racks-success); }
    .racks-page .stat-icon-danger  { background: #fef2f2; color: var(--racks-danger); }
    .racks-page .stat-value { font-size: 1.375rem; font-weight: 700; line-height: 1.2; color: var(--racks-text); }
    .racks-page .stat-label { font-size: 0.75rem; color: var(--racks-text-muted); font-weight: 500; }

    @media (max-width: 992px) {
        .racks-page .stat-cards-row { grid-template-columns: repeat(2, 1fr); }
    }

    /* ---------- Card ---------- */
    .racks-page .card {
        border: 1px solid var(--racks-border);
        border-radius: 10px;
        box-shadow: none;
    }
    .racks-page .card-header {
        padding: 12px 16px;
        background: var(--racks-surface);
    }
    .racks-page .card-footer { padding: 10px 16px; }

    /* Tighten the app-search wrapper so the dropdowns look right in the card header */
    .racks-page .app-search { position: relative; }
    .racks-page .app-search > .app-search-icon {
        position: absolute; right: 0.75rem; top: 50%;
        transform: translateY(-50%); pointer-events: none;
    }
    .racks-page .app-search > .form-control { padding-right: 2.25rem; min-width: 180px; }
    .racks-page .card-header .form-control,
    .racks-page .card-header .form-select {
        height: 38px;
        font-size: 0.8125rem;
        border-color: var(--racks-border);
    }

    /* Primary "Add Rack" button */
    .racks-page .add-btn {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, var(--racks-primary-dark), var(--racks-primary));
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 9px 18px;
        font-weight: 600;
        font-size: 0.8125rem;
        text-decoration: none;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    .racks-page .add-btn:hover {
        color: #fff;
        box-shadow: 0 4px 10px rgba(29, 78, 216, 0.25);
        transform: translateY(-1px);
    }

    /* ---------- Table ---------- */
    .racks-page #racksTable thead th {
        background: #f1f5f9;
        color: var(--racks-text);
        font-weight: 700;
        font-size: 0.6875rem;
        letter-spacing: 0.03em;
        padding: 8px 12px;
        border-bottom: 1px solid var(--racks-border);
    }

    /* Sort arrows are too faint (low opacity) against the light header background — darken them */
    .racks-page #racksTable thead th span.dt-column-order:before,
    .racks-page #racksTable thead th span.dt-column-order:after {
        color: #475569;
    }
    .racks-page #racksTable thead th span.dt-column-order:before { opacity: .45; }
    .racks-page #racksTable thead th span.dt-column-order:after { opacity: .9; }
    .racks-page #racksTable thead th.dt-ordering-asc span.dt-column-order:before,
    .racks-page #racksTable thead th.dt-ordering-desc span.dt-column-order:after {
        color: var(--racks-primary);
        opacity: 1;
    }
    .racks-page #racksTable tbody td {
        padding: 6px 12px;
        vertical-align: middle;
        border-bottom: 1px solid var(--racks-border);
        font-size: 0.8125rem;
    }
    .racks-page #racksTable tbody tr {
        transition: background 0.2s ease;
    }
    .racks-page #racksTable tbody tr:hover {
        background: #f8fafc;
    }

    /* Rack name — bold, no image column on this module */
    .racks-page .rack-name {
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--racks-text);
    }

    /* Status pill */
    .racks-page .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .racks-page .status-pill .status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        display: inline-block;
    }
    .racks-page .status-active { background: #ecfdf5; color: var(--racks-success); }
    .racks-page .status-active .status-dot { background: var(--racks-success); }
    .racks-page .status-inactive { background: #fef2f2; color: var(--racks-danger); }
    .racks-page .status-inactive .status-dot { background: var(--racks-danger); }

    /* Action buttons */
    .racks-page .action-btn {
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
    .racks-page .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }
    .racks-page .action-edit {
        color: #059669;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
    }
    .racks-page .action-toggle {
        color: #d97706;
        background: #fffbeb;
        border: 1px solid #fde68a;
    }
    .racks-page .action-delete {
        color: #dc2626;
        background: #fef2f2;
        border: 1px solid #fecaca;
    }

    /* DataTables "processing" loading indicator */
    .racks-page .dataTables_processing {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    .racks-page .racks-loading {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--racks-surface);
        border: 1px solid var(--racks-border);
        padding: 8px 18px;
        border-radius: 999px;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--racks-text);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.1);
    }
    .racks-page .racks-loading .spinner-border {
        width: 1rem;
        height: 1rem;
        color: var(--racks-primary);
        border-width: 0.15em;
    }

    /* Delete confirmation modal */
    .racks-page .delete-modal-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fef2f2;
        color: var(--racks-danger);
        font-size: 1.5rem;
    }

    /* Hide DataTables built-in length+filter+info+paginate (we render our own slots) */
    #racksTable_wrapper .dataTables_length,
    #racksTable_wrapper .dataTables_filter { display: none !important; }

    /* The cloned DataTables info/pagination land inside our card-footer slots */
    #racksInfoSlot .dataTables_info { padding: 0; color: var(--bs-body-color); font-size: 0.875rem; }
    #racksPaginationSlot .pagination { margin-bottom: 0; }
    #racksPaginationSlot .dataTables_paginate { margin: 0; }

    /* "Showing x to y..." info on the left, pagination on the right — overrides the
       app-wide pagination-left/info-right order, scoped to this page only. */
    .racks-page .card-footer #racksInfoSlot { order: 1; }
    .racks-page .card-footer .footer-pagination-group { order: 2; }
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
            document.getElementById('racksToastContainer').appendChild(el);
            const toast = new bootstrap.Toast(el, { delay: 3000 });
            el.addEventListener('hidden.bs.toast', () => el.remove());
            toast.show();
        }

        // ============= DataTable =============
        const dt = $('#racksTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            order: [[1, 'asc']], // code asc
            ajax: {
                url: '{{ route('racks.data') }}',
                type: 'GET',
                data: function (d) {
                    d.status = $('#rackStatusFilter').val();
                },
            },
            // dom: render table + (info+paginate) into a hidden wrapper; we'll move them via initComplete
            dom: 'rt<"datatables-tail"ip>',
            pageLength: 10,
            columns: [
                { data: 'DT_RowIndex',   name: 'DT_RowIndex',  orderable: false, searchable: false, className: 'text-center' },
                { data: 'code',          name: 'code' },
                { data: 'name',          name: 'name' },
                { data: 'location',      name: 'location' },
                { data: 'status_badge',  name: 'status',       searchable: false, orderable: false },
                { data: 'created_at',    name: 'created_at' },
                { data: 'actions',       name: 'actions',      orderable: false, searchable: false, className: 'text-center' },
            ],
            language: {
                info: 'Showing _START_ to _END_ of _TOTAL_ racks',
                infoEmpty: 'No racks found',
                infoFiltered: ' (filtered from _MAX_ total)',
                emptyTable: 'No racks yet. Click "Add Rack" to get started.',
                zeroRecords: 'No racks match your search.',
                processing: '<div class="racks-loading"><span class="spinner-border spinner-border-sm"></span>Loading racks&hellip;</div>',
                paginate: {
                    previous: '<i class="ti ti-chevron-left"></i>',
                    next:     '<i class="ti ti-chevron-right"></i>',
                },
            },
            initComplete: function () {
                // Move DataTables-rendered info & pagination into our card-footer slots
                $('#racksInfoSlot').append($('#racksTable_info'));
                $('#racksPaginationSlot').append($('.datatables-tail'));
            },
        });

        // ============= Custom search wire-up (debounced) =============
        let searchTimer;
        $('#rackSearch').on('keyup', function () {
            clearTimeout(searchTimer);
            const v = this.value;
            searchTimer = setTimeout(() => dt.search(v).draw(), 250);
        });

        // Per-page
        $('#rackPerPage').on('change', function () {
            dt.page.len(parseInt(this.value, 10)).draw();
        });

        // Status filter — sent as an extra `status` param on the ajax request (see data() above)
        $('#rackStatusFilter').on('change', function () {
            dt.draw();
        });

        // ============= Toggle Status =============
        $('#racksTable tbody').on('click', '.js-toggle-rack', function () {
            const url = $(this).data('url');
            $.ajax({
                url: url,
                type: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    dt.ajax.reload(null, false);
                    showToast('success', (res && res.message) || 'Status updated.');
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
        const deleteModalEl = document.getElementById('deleteRackModal');
        const deleteModal = new bootstrap.Modal(deleteModalEl);
        let pendingDeleteUrl = null;

        $('#racksTable tbody').on('click', '.js-delete-rack', function () {
            pendingDeleteUrl = $(this).data('url');
            $('#deleteRackName').text($(this).data('name'));
            deleteModal.show();
        });

        $('#confirmDeleteRackBtn').on('click', function () {
            if (!pendingDeleteUrl) return;
            const $btn = $(this).prop('disabled', true);

            $.ajax({
                url: pendingDeleteUrl,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    dt.ajax.reload(null, false);
                    showToast('success', (res && res.message) || 'Rack deleted successfully.');
                },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Failed to delete rack.';
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
