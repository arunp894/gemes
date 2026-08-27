@extends('layout.app')

@section('title', 'Users')

@section('content')

<div class="container-fluid users-page">

    {{-- Page title --}}
    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Users</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Administration</a></li>
                <li class="breadcrumb-item active">Users</li>
            </ol>
        </div>
    </div>

    {{-- Toast notifications for AJAX actions (status toggle, delete) --}}
    <div class="toast-container position-fixed top-0 end-0 p-3" id="usersToastContainer" style="z-index: 1080;"></div>

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
                <div class="stat-value">{{ $stats['users_total'] }}</div>
                <div class="stat-label">Total Users</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-success"><i class="ti ti-circle-check"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['users_active'] }}</div>
                <div class="stat-label">Active Users</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-danger"><i class="ti ti-circle-x"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['users_inactive'] }}</div>
                <div class="stat-label">Inactive Users</div>
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
                            <input id="userSearch" type="search" class="form-control"
                                placeholder="Search users..." />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1">
                        <div class="app-search">
                            <select id="userStatusFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <i class="ti ti-circle app-search-icon text-muted"></i>
                        </div>

                        @permission('users.create')
                        <a href="{{ route('users.create') }}" class="add-btn ms-1">
                            <i class="ti ti-plus fs-sm me-2"></i> Add User
                        </a>
                        @endpermission
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table id="usersTable" class="table table-custom table-centered table-select table-hover w-100 mb-0">
                        <thead class="align-middle thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3" style="width: 1%;">
                                    <input id="userSelectAll" class="form-check-input form-check-input-light fs-14 mt-0"
                                        type="checkbox" />
                                </th>
                                <th class="text-center" style="width: 1%;">S.No</th>
                                <th><i class="ti ti-user me-1"></i>Name / Email</th>
                                <th><i class="ti ti-shield me-1"></i>Roles</th>
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
                        <div id="usersInfoSlot" class="text-muted small"></div>
                        <div class="d-flex align-items-center gap-2 footer-pagination-group">
                            <select id="userPerPage" class="form-select form-select-sm" style="width: auto;">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="15">15</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                            </select>
                            <div id="usersPaginationSlot"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== Delete Confirmation Modal ==================== --}}
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="delete-modal-icon mx-auto mb-3">
                        <i class="ti ti-trash"></i>
                    </div>
                    <h5 class="modal-title mb-2" id="deleteUserModalLabel">Delete this user?</h5>
                    <p class="text-muted mb-0">
                        Are you sure you want to delete <strong id="deleteUserName"></strong>?
                        This is a soft delete and can be restored later if needed.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteUserBtn">
                        <i class="ti ti-trash me-1"></i>Delete User
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
       Users page — compact ERP styling
       Scoped entirely under .users-page so nothing here leaks
       into other modules that share the same layout/theme classes.
       ========================================================== */
    .users-page {
        --users-primary: #1d4ed8;
        --users-primary-dark: #1e3a8a;
        --users-cyan: #14b8a6;
        --users-success: #059669;
        --users-warning: #d97706;
        --users-danger: #dc2626;
        --users-bg: #f8fafc;
        --users-surface: #ffffff;
        --users-border: #e2e8f0;
        --users-text: #1e293b;
        --users-text-muted: #64748b;
        padding-top: 0;
        padding-bottom: 20px;
    }

    /* ---------- Page header ---------- */
    .users-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid var(--users-border);
    }
    .users-page .page-title-head > * { display: flex; align-items: center; }
    .users-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        color: var(--users-text);
        position: relative;
        padding-left: 12px;
    }
    .users-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 2px;
        bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, var(--users-primary), var(--users-cyan));
    }
    .users-page .breadcrumb { font-size: 0.75rem; }

    /* ---------- Summary cards ---------- */
    .users-page .stat-cards-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 16px;
    }
    .users-page .stat-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--users-surface);
        border: 1px solid var(--users-border);
        border-radius: 10px;
        padding: 14px 16px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .users-page .stat-icon {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    .users-page .stat-icon-primary { background: #eff6ff; color: var(--users-primary); }
    .users-page .stat-icon-success { background: #ecfdf5; color: var(--users-success); }
    .users-page .stat-icon-danger  { background: #fef2f2; color: var(--users-danger); }
    .users-page .stat-value { font-size: 1.375rem; font-weight: 700; line-height: 1.2; color: var(--users-text); }
    .users-page .stat-label { font-size: 0.75rem; color: var(--users-text-muted); font-weight: 500; }

    @media (max-width: 992px) {
        .users-page .stat-cards-row { grid-template-columns: repeat(2, 1fr); }
    }

    /* ---------- Card ---------- */
    .users-page .card {
        border: 1px solid var(--users-border);
        border-radius: 10px;
        box-shadow: none;
    }
    .users-page .card-header {
        padding: 12px 16px;
        background: var(--users-surface);
    }
    .users-page .card-footer { padding: 10px 16px; }

    /* Tighten the app-search wrapper so the dropdowns look right in the card header */
    .users-page .app-search { position: relative; }
    .users-page .app-search > .app-search-icon {
        position: absolute; right: 0.75rem; top: 50%;
        transform: translateY(-50%); pointer-events: none;
    }
    .users-page .app-search > .form-control { padding-right: 2.25rem; min-width: 180px; }
    .users-page .card-header .form-control,
    .users-page .card-header .form-select {
        height: 38px;
        font-size: 0.8125rem;
        border-color: var(--users-border);
    }

    /* Primary "Add User" button */
    .users-page .add-btn {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, var(--users-primary-dark), var(--users-primary));
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 9px 18px;
        font-weight: 600;
        font-size: 0.8125rem;
        text-decoration: none;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    .users-page .add-btn:hover {
        color: #fff;
        box-shadow: 0 4px 10px rgba(29, 78, 216, 0.25);
        transform: translateY(-1px);
    }

    /* ---------- Table ---------- */
    .users-page #usersTable thead th {
        background: #f1f5f9;
        color: var(--users-text);
        font-weight: 700;
        font-size: 0.6875rem;
        letter-spacing: 0.03em;
        padding: 8px 12px;
        border-bottom: 1px solid var(--users-border);
    }

    /* Sort arrows are too faint (low opacity) against the light header background — darken them */
    .users-page #usersTable thead th span.dt-column-order:before,
    .users-page #usersTable thead th span.dt-column-order:after {
        color: #475569;
    }
    .users-page #usersTable thead th span.dt-column-order:before { opacity: .45; }
    .users-page #usersTable thead th span.dt-column-order:after { opacity: .9; }
    .users-page #usersTable thead th.dt-ordering-asc span.dt-column-order:before,
    .users-page #usersTable thead th.dt-ordering-desc span.dt-column-order:after {
        color: var(--users-primary);
        opacity: 1;
    }
    .users-page #usersTable tbody td {
        padding: 6px 12px;
        vertical-align: middle;
        border-bottom: 1px solid var(--users-border);
        font-size: 0.8125rem;
    }
    .users-page #usersTable tbody tr {
        transition: background 0.2s ease;
    }
    .users-page #usersTable tbody tr:hover {
        background: #f8fafc;
    }

    /* User name (no image field on this module — bold link + muted sub-line, no thumbnail) */
    .users-page .user-name-cell { line-height: 1.3; }
    .users-page .user-name-link {
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--users-text);
        text-decoration: none;
    }
    .users-page .user-name-link:hover { color: var(--users-primary); }

    /* Status pill */
    .users-page .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .users-page .status-pill .status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        display: inline-block;
    }
    .users-page .status-active { background: #ecfdf5; color: var(--users-success); }
    .users-page .status-active .status-dot { background: var(--users-success); }
    .users-page .status-inactive { background: #fef2f2; color: var(--users-danger); }
    .users-page .status-inactive .status-dot { background: var(--users-danger); }

    /* Action buttons */
    .users-page .action-btn {
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
    .users-page .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }
    .users-page .action-view {
        color: #2563eb;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
    }
    .users-page .action-edit {
        color: #059669;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
    }
    .users-page .action-toggle {
        color: #d97706;
        background: #fffbeb;
        border: 1px solid #fde68a;
    }
    .users-page .action-delete {
        color: #dc2626;
        background: #fef2f2;
        border: 1px solid #fecaca;
    }
    /* Self-guard: some actions are disabled for the currently logged-in user's own row */
    .users-page .action-btn:disabled {
        opacity: 0.4;
        cursor: not-allowed;
        pointer-events: none;
        box-shadow: none;
        transform: none;
    }

    /* DataTables "processing" loading indicator */
    .users-page .dataTables_processing {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    .users-page .users-loading {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--users-surface);
        border: 1px solid var(--users-border);
        padding: 8px 18px;
        border-radius: 999px;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--users-text);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.1);
    }
    .users-page .users-loading .spinner-border {
        width: 1rem;
        height: 1rem;
        color: var(--users-primary);
        border-width: 0.15em;
    }

    /* Delete confirmation modal */
    .users-page .delete-modal-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fef2f2;
        color: var(--users-danger);
        font-size: 1.5rem;
    }

    /* Hide DataTables built-in length+filter+info+paginate (we render our own slots) */
    #usersTable_wrapper .dataTables_length,
    #usersTable_wrapper .dataTables_filter { display: none !important; }

    /* The cloned DataTables info/pagination land inside our card-footer slots */
    #usersInfoSlot .dataTables_info { padding: 0; color: var(--bs-body-color); font-size: 0.875rem; }
    #usersPaginationSlot .pagination { margin-bottom: 0; }
    #usersPaginationSlot .dataTables_paginate { margin: 0; }

    /* "Showing x to y..." info on the left, pagination on the right — overrides the
       app-wide pagination-left/info-right order, scoped to this page only. */
    .users-page .card-footer #usersInfoSlot { order: 1; }
    .users-page .card-footer .footer-pagination-group { order: 2; }
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
            document.getElementById('usersToastContainer').appendChild(el);
            const toast = new bootstrap.Toast(el, { delay: 3000 });
            el.addEventListener('hidden.bs.toast', () => el.remove());
            toast.show();
        }

        // ============= DataTable =============
        const dt = $('#usersTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            order: [[5, 'desc']],
            ajax: {
                url: '{{ route('users.data') }}',
                type: 'GET',
            },
            // dom: render table + (info+paginate) into a hidden wrapper; we'll move them via initComplete
            dom: 'rt<"datatables-tail"ip>',
            pageLength: 10,
            columns: [
                { data: 'checkbox',     name: 'checkbox',            orderable: false, searchable: false, className: 'ps-3' },
                { data: 'DT_RowIndex',  name: 'DT_RowIndex',         orderable: false, searchable: false, className: 'text-center' },
                { data: 'name',         name: 'users.name' },
                { data: 'roles_badges', name: 'roles_badges',        orderable: false, searchable: false },
                { data: 'status_badge', name: 'users.is_active',     searchable: true },
                { data: 'created_at',   name: 'users.created_at' },
                { data: 'action',       name: 'action',              orderable: false, searchable: false, className: 'text-center' },
            ],
            language: {
                info: 'Showing _START_ to _END_ of _TOTAL_ users',
                infoEmpty: 'No users found',
                infoFiltered: ' (filtered from _MAX_ total)',
                emptyTable: 'No users yet. Click "Add User" to get started.',
                zeroRecords: 'No users match your search.',
                processing: '<div class="users-loading"><span class="spinner-border spinner-border-sm"></span>Loading users&hellip;</div>',
                paginate: {
                    previous: '<i class="ti ti-chevron-left"></i>',
                    next:     '<i class="ti ti-chevron-right"></i>',
                },
            },
            initComplete: function () {
                // Move DataTables-rendered info & pagination into our card-footer slots
                $('#usersInfoSlot').append($('#usersTable_info'));
                $('#usersPaginationSlot').append($('.datatables-tail'));
            },
        });

        // ============= Custom search wire-up (debounced) =============
        let searchTimer;
        $('#userSearch').on('keyup', function () {
            clearTimeout(searchTimer);
            const v = this.value;
            searchTimer = setTimeout(() => dt.search(v).draw(), 250);
        });

        // Per-page
        $('#userPerPage').on('change', function () {
            dt.page.len(parseInt(this.value, 10)).draw();
        });

        // Status filter — column index 4 is the status_badge column
        $('#userStatusFilter').on('change', function () {
            dt.column(4).search(this.value).draw();
        });

        // ============= Select-all =============
        $('#userSelectAll').on('change', function () {
            $('#usersTable tbody .product-item-check').prop('checked', this.checked);
        });

        // ============= Toggle Status =============
        $('#usersTable tbody').on('click', '.js-toggle-status', function () {
            if ($(this).prop('disabled')) return;
            const url = $(this).data('url');
            $.ajax({
                url: url,
                type: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    if (res.success) {
                        dt.ajax.reload(null, false);
                        showToast('success', 'User marked as ' + (res.label || (res.active ? 'Active' : 'Inactive')) + '.');
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
        const deleteModalEl = document.getElementById('deleteUserModal');
        const deleteModal = new bootstrap.Modal(deleteModalEl);
        let pendingDeleteUrl = null;

        $('#usersTable tbody').on('click', '.js-delete', function () {
            if ($(this).prop('disabled')) return;
            pendingDeleteUrl = $(this).data('url');
            $('#deleteUserName').text($(this).data('name'));
            deleteModal.show();
        });

        $('#confirmDeleteUserBtn').on('click', function () {
            if (!pendingDeleteUrl) return;
            const $btn = $(this).prop('disabled', true);

            $.ajax({
                url: pendingDeleteUrl,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    if (res.success) {
                        dt.ajax.reload(null, false);
                        showToast('success', res.message || 'User deleted successfully.');
                    } else {
                        showToast('error', res.message || 'Could not delete user.');
                    }
                },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Failed to delete user.';
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
