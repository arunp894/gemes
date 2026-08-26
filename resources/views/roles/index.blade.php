@extends('layout.app')

@section('title', 'Roles')

@section('content')

<div class="container-fluid roles-page">

    {{-- Page title --}}
    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Roles</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Administration</a></li>
                <li class="breadcrumb-item active">Roles</li>
            </ol>
        </div>
    </div>

    {{-- Toast notifications for AJAX actions (delete) --}}
    <div class="toast-container position-fixed top-0 end-0 p-3" id="rolesToastContainer" style="z-index: 1080;"></div>

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
            <div class="stat-icon stat-icon-primary"><i class="ti ti-shield"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['roles_total'] }}</div>
                <div class="stat-label">Total Roles</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-danger"><i class="ti ti-shield-star"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['roles_super'] }}</div>
                <div class="stat-label">Super Roles</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-success"><i class="ti ti-key"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['permissions_total'] }}</div>
                <div class="stat-label">Total Permissions</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                {{-- Card header: search + per-page + add button --}}
                <div class="card-header border-light justify-content-between">
                    <div class="d-flex gap-2">
                        <div class="app-search">
                            <input id="roleSearch" type="search" class="form-control"
                                placeholder="Search roles..." />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1">
                        <div>
                            <select id="rolePerPage" class="form-select form-control my-1 my-md-0">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="15">15</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                            </select>
                        </div>

                        @permission('roles.create')
                        <a href="{{ route('roles.create') }}" class="add-btn ms-1">
                            <i class="ti ti-plus fs-sm me-2"></i> Add Role
                        </a>
                        @endpermission
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table id="rolesTable" class="table table-custom table-centered table-hover w-100 mb-0">
                        <thead class="align-middle thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3 text-center" style="width: 1%;">S.No</th>
                                <th><i class="ti ti-shield me-1"></i>Role</th>
                                <th><i class="ti ti-code me-1"></i>Slug</th>
                                <th><i class="ti ti-users me-1"></i>Users</th>
                                <th><i class="ti ti-key me-1"></i>Permissions</th>
                                <th><i class="ti ti-calendar-time me-1"></i>Last Modified</th>
                                <th class="text-center" style="width: 1%;"><i class="ti ti-settings me-1"></i>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                {{-- Card footer: DataTables info + pagination get moved here --}}
                <div class="card-footer border-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div id="rolesInfoSlot" class="text-muted small"></div>
                        <div id="rolesPaginationSlot"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== Delete Confirmation Modal ==================== --}}
    <div class="modal fade" id="deleteRoleModal" tabindex="-1" aria-labelledby="deleteRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="delete-modal-icon mx-auto mb-3">
                        <i class="ti ti-trash"></i>
                    </div>
                    <h5 class="modal-title mb-2" id="deleteRoleModalLabel">Delete this role?</h5>
                    <p class="text-muted mb-0">
                        Are you sure you want to delete <strong id="deleteRoleName"></strong>?
                        This is a soft delete and can be restored later if needed.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteRoleBtn">
                        <i class="ti ti-trash me-1"></i>Delete Role
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
       Roles page — compact ERP styling
       Scoped entirely under .roles-page so nothing here leaks
       into other modules that share the same layout/theme classes.
       ========================================================== */
    .roles-page {
        --roles-primary: #1d4ed8;
        --roles-primary-dark: #1e3a8a;
        --roles-cyan: #14b8a6;
        --roles-success: #059669;
        --roles-warning: #d97706;
        --roles-danger: #dc2626;
        --roles-bg: #f8fafc;
        --roles-surface: #ffffff;
        --roles-border: #e2e8f0;
        --roles-text: #1e293b;
        --roles-text-muted: #64748b;
        padding-top: 0;
        padding-bottom: 20px;
    }

    /* ---------- Page header ---------- */
    .roles-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid var(--roles-border);
    }
    .roles-page .page-title-head > * { display: flex; align-items: center; }
    .roles-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        color: var(--roles-text);
        position: relative;
        padding-left: 12px;
    }
    .roles-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 2px;
        bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, var(--roles-primary), var(--roles-cyan));
    }
    .roles-page .breadcrumb { font-size: 0.75rem; }

    /* ---------- Summary cards ---------- */
    .roles-page .stat-cards-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 16px;
    }
    .roles-page .stat-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--roles-surface);
        border: 1px solid var(--roles-border);
        border-radius: 10px;
        padding: 14px 16px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .roles-page .stat-icon {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    .roles-page .stat-icon-primary { background: #eff6ff; color: var(--roles-primary); }
    .roles-page .stat-icon-success { background: #ecfdf5; color: var(--roles-success); }
    .roles-page .stat-icon-danger  { background: #fef2f2; color: var(--roles-danger); }
    .roles-page .stat-value { font-size: 1.375rem; font-weight: 700; line-height: 1.2; color: var(--roles-text); }
    .roles-page .stat-label { font-size: 0.75rem; color: var(--roles-text-muted); font-weight: 500; }

    @media (max-width: 992px) {
        .roles-page .stat-cards-row { grid-template-columns: repeat(2, 1fr); }
    }

    /* ---------- Card ---------- */
    .roles-page .card {
        border: 1px solid var(--roles-border);
        border-radius: 10px;
        box-shadow: none;
    }
    .roles-page .card-header {
        padding: 12px 16px;
        background: var(--roles-surface);
    }
    .roles-page .card-footer { padding: 10px 16px; }

    /* Tighten the app-search wrapper so the dropdowns look right in the card header */
    .roles-page .app-search { position: relative; }
    .roles-page .app-search > .app-search-icon {
        position: absolute; right: 0.75rem; top: 50%;
        transform: translateY(-50%); pointer-events: none;
    }
    .roles-page .app-search > .form-control { padding-right: 2.25rem; min-width: 180px; }
    .roles-page .card-header .form-control,
    .roles-page .card-header .form-select {
        height: 38px;
        font-size: 0.8125rem;
        border-color: var(--roles-border);
    }

    /* Primary "Add Role" button */
    .roles-page .add-btn {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, var(--roles-primary-dark), var(--roles-primary));
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 9px 18px;
        font-weight: 600;
        font-size: 0.8125rem;
        text-decoration: none;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    .roles-page .add-btn:hover {
        color: #fff;
        box-shadow: 0 4px 10px rgba(29, 78, 216, 0.25);
        transform: translateY(-1px);
    }

    /* ---------- Table ---------- */
    .roles-page #rolesTable thead th {
        background: #f1f5f9;
        color: var(--roles-text);
        font-weight: 700;
        font-size: 0.6875rem;
        letter-spacing: 0.03em;
        padding: 8px 12px;
        border-bottom: 1px solid var(--roles-border);
    }

    /* Sort arrows are too faint (low opacity) against the light header background — darken them */
    .roles-page #rolesTable thead th span.dt-column-order:before,
    .roles-page #rolesTable thead th span.dt-column-order:after {
        color: #475569;
    }
    .roles-page #rolesTable thead th span.dt-column-order:before { opacity: .45; }
    .roles-page #rolesTable thead th span.dt-column-order:after { opacity: .9; }
    .roles-page #rolesTable thead th.dt-ordering-asc span.dt-column-order:before,
    .roles-page #rolesTable thead th.dt-ordering-desc span.dt-column-order:after {
        color: var(--roles-primary);
        opacity: 1;
    }
    .roles-page #rolesTable tbody td {
        padding: 6px 12px;
        vertical-align: middle;
        border-bottom: 1px solid var(--roles-border);
        font-size: 0.8125rem;
    }
    .roles-page #rolesTable tbody tr {
        transition: background 0.2s ease;
    }
    .roles-page #rolesTable tbody tr:hover {
        background: #f8fafc;
    }

    /* Role name link */
    .roles-page .role-name-link {
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--roles-text);
        text-decoration: none;
    }
    .roles-page .role-name-link:hover { color: var(--roles-primary); }

    /* Action buttons */
    .roles-page .action-btn {
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
    .roles-page .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }
    .roles-page .action-view {
        color: #2563eb;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
    }
    .roles-page .action-edit {
        color: #059669;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
    }
    .roles-page .action-delete {
        color: #dc2626;
        background: #fef2f2;
        border: 1px solid #fecaca;
    }

    /* DataTables "processing" loading indicator */
    .roles-page .dataTables_processing {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    .roles-page .roles-loading {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--roles-surface);
        border: 1px solid var(--roles-border);
        padding: 8px 18px;
        border-radius: 999px;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--roles-text);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.1);
    }
    .roles-page .roles-loading .spinner-border {
        width: 1rem;
        height: 1rem;
        color: var(--roles-primary);
        border-width: 0.15em;
    }

    /* Delete confirmation modal */
    .roles-page .delete-modal-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fef2f2;
        color: var(--roles-danger);
        font-size: 1.5rem;
    }

    /* Hide DataTables built-in length+filter+info+paginate (we render our own slots) */
    #rolesTable_wrapper .dataTables_length,
    #rolesTable_wrapper .dataTables_filter { display: none !important; }

    /* The cloned DataTables info/pagination land inside our card-footer slots */
    #rolesInfoSlot .dataTables_info { padding: 0; color: var(--bs-body-color); font-size: 0.875rem; }
    #rolesPaginationSlot .pagination { margin-bottom: 0; }
    #rolesPaginationSlot .dataTables_paginate { margin: 0; }

    /* "Showing x to y..." info on the left, pagination on the right — overrides the
       app-wide pagination-left/info-right order, scoped to this page only. */
    .roles-page .card-footer #rolesInfoSlot { order: 1; }
    .roles-page .card-footer #rolesPaginationSlot { order: 2; }
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
            document.getElementById('rolesToastContainer').appendChild(el);
            const toast = new bootstrap.Toast(el, { delay: 3000 });
            el.addEventListener('hidden.bs.toast', () => el.remove());
            toast.show();
        }

        // ============= DataTable =============
        const dt = $('#rolesTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            order: [[1, 'asc']],
            ajax: {
                url: '{{ route('roles.data') }}',
                type: 'GET',
            },
            dom: 'rt<"datatables-tail"ip>',
            pageLength: 10,
            columns: [
                { data: 'DT_RowIndex',             name: 'DT_RowIndex',        orderable: false, searchable: false, className: 'text-center' },
                { data: 'name',                    name: 'roles.name' },
                { data: 'slug',                    name: 'roles.slug' },
                { data: 'users_count_badge',       name: 'users_count',        orderable: false, searchable: false },
                { data: 'permissions_count_badge', name: 'permissions_count',  orderable: false, searchable: false },
                { data: 'updated_at',              name: 'roles.updated_at' },
                { data: 'action',                  name: 'action',             orderable: false, searchable: false, className: 'text-center' },
            ],
            language: {
                info: 'Showing _START_ to _END_ of _TOTAL_ roles',
                infoEmpty: 'No roles found',
                infoFiltered: ' (filtered from _MAX_ total)',
                emptyTable: 'No roles yet.',
                zeroRecords: 'No roles match your search.',
                processing: '<div class="roles-loading"><span class="spinner-border spinner-border-sm"></span>Loading roles&hellip;</div>',
                paginate: {
                    previous: '<i class="ti ti-chevron-left"></i>',
                    next:     '<i class="ti ti-chevron-right"></i>',
                },
            },
            initComplete: function () {
                // Move DataTables-rendered info & pagination into our card-footer slots
                $('#rolesInfoSlot').append($('#rolesTable_info'));
                $('#rolesPaginationSlot').append($('.datatables-tail'));
            },
        });

        // ============= Custom search wire-up (debounced) =============
        let searchTimer;
        $('#roleSearch').on('keyup', function () {
            clearTimeout(searchTimer);
            const v = this.value;
            searchTimer = setTimeout(() => dt.search(v).draw(), 250);
        });

        // Per-page
        $('#rolePerPage').on('change', function () {
            dt.page.len(parseInt(this.value, 10)).draw();
        });

        // ============= Delete (styled confirmation modal) =============
        const deleteModalEl = document.getElementById('deleteRoleModal');
        const deleteModal = new bootstrap.Modal(deleteModalEl);
        let pendingDeleteUrl = null;

        $('#rolesTable tbody').on('click', '.js-delete', function () {
            pendingDeleteUrl = $(this).data('url');
            $('#deleteRoleName').text($(this).data('name'));
            deleteModal.show();
        });

        $('#confirmDeleteRoleBtn').on('click', function () {
            if (!pendingDeleteUrl) return;
            const $btn = $(this).prop('disabled', true);

            $.ajax({
                url: pendingDeleteUrl,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    if (res.success) {
                        dt.ajax.reload(null, false);
                        showToast('success', res.message || 'Role deleted successfully.');
                    } else {
                        showToast('error', res.message || 'Could not delete role.');
                    }
                },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Failed to delete role.';
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
