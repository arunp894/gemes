@extends('layout.app')

@section('title', 'Countries of Origin')

@section('content')

<div class="container-fluid country-origins-page">

    {{-- Page title --}}
    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Countries of Origin</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Countries of Origin</li>
            </ol>
        </div>
    </div>

    {{-- Toast notifications for AJAX actions (status toggle, delete) --}}
    <div class="toast-container position-fixed top-0 end-0 p-3" id="originsToastContainer" style="z-index: 1080;"></div>

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
            <div class="stat-icon stat-icon-primary"><i class="ti ti-world"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['origins_total'] }}</div>
                <div class="stat-label">Total Countries</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-success"><i class="ti ti-circle-check"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['origins_active'] }}</div>
                <div class="stat-label">Active Countries</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-danger"><i class="ti ti-circle-x"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['origins_inactive'] }}</div>
                <div class="stat-label">Inactive Countries</div>
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
                            <input id="originSearch" type="search" class="form-control"
                                placeholder="Search countries..." />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1">
                        <div>
                            <select id="originPerPage" class="form-select form-control my-1 my-md-0">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>

                        <div class="app-search">
                            <select id="originStatusFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <i class="ti ti-circle app-search-icon text-muted"></i>
                        </div>

                        @permission('country-origins.create')
                        <a href="{{ route('country-origins.create') }}" class="add-btn ms-1">
                            <i class="ti ti-plus fs-sm me-2"></i> Add Country
                        </a>
                        @endpermission
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table id="originsTable" class="table table-custom table-centered table-hover w-100 mb-0">
                        <thead class="align-middle thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="text-center" style="width: 1%;">S.No</th>
                                <th><i class="ti ti-world me-1"></i>Name</th>
                                <th class="text-center"><i class="ti ti-toggle-right me-1"></i>Status</th>
                                <th class="text-center"><i class="ti ti-arrows-sort me-1"></i>Order</th>
                                <th class="text-center" style="width: 1%;"><i class="ti ti-settings me-1"></i>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                {{-- Card footer: DataTables info + pagination get moved here --}}
                <div class="card-footer border-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div id="originsInfoSlot" class="text-muted small"></div>
                        <div id="originsPaginationSlot"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== Delete Confirmation Modal ==================== --}}
    <div class="modal fade" id="deleteCountryOriginModal" tabindex="-1" aria-labelledby="deleteCountryOriginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="delete-modal-icon mx-auto mb-3">
                        <i class="ti ti-trash"></i>
                    </div>
                    <h5 class="modal-title mb-2" id="deleteCountryOriginModalLabel">Delete this country?</h5>
                    <p class="text-muted mb-0">
                        Are you sure you want to delete <strong id="deleteCountryOriginName"></strong>?
                        This is a soft delete and can be restored later if needed.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteCountryOriginBtn">
                        <i class="ti ti-trash me-1"></i>Delete Country
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
       Countries of Origin page — compact ERP styling
       Scoped entirely under .country-origins-page so nothing here
       leaks into other modules that share the same layout/theme classes.
       ========================================================== */
    .country-origins-page {
        --origins-primary: #1d4ed8;
        --origins-primary-dark: #1e3a8a;
        --origins-cyan: #14b8a6;
        --origins-success: #059669;
        --origins-warning: #d97706;
        --origins-danger: #dc2626;
        --origins-bg: #f8fafc;
        --origins-surface: #ffffff;
        --origins-border: #e2e8f0;
        --origins-text: #1e293b;
        --origins-text-muted: #64748b;
        padding-top: 0;
        padding-bottom: 20px;
    }

    /* ---------- Page header ---------- */
    .country-origins-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid var(--origins-border);
    }
    .country-origins-page .page-title-head > * { display: flex; align-items: center; }
    .country-origins-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        color: var(--origins-text);
        position: relative;
        padding-left: 12px;
    }
    .country-origins-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 2px;
        bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, var(--origins-primary), var(--origins-cyan));
    }
    .country-origins-page .breadcrumb { font-size: 0.75rem; }

    /* ---------- Summary cards ---------- */
    .country-origins-page .stat-cards-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 16px;
    }
    .country-origins-page .stat-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--origins-surface);
        border: 1px solid var(--origins-border);
        border-radius: 10px;
        padding: 14px 16px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .country-origins-page .stat-icon {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    .country-origins-page .stat-icon-primary { background: #eff6ff; color: var(--origins-primary); }
    .country-origins-page .stat-icon-success { background: #ecfdf5; color: var(--origins-success); }
    .country-origins-page .stat-icon-danger  { background: #fef2f2; color: var(--origins-danger); }
    .country-origins-page .stat-value { font-size: 1.375rem; font-weight: 700; line-height: 1.2; color: var(--origins-text); }
    .country-origins-page .stat-label { font-size: 0.75rem; color: var(--origins-text-muted); font-weight: 500; }

    @media (max-width: 992px) {
        .country-origins-page .stat-cards-row { grid-template-columns: repeat(2, 1fr); }
    }

    /* ---------- Card ---------- */
    .country-origins-page .card {
        border: 1px solid var(--origins-border);
        border-radius: 10px;
        box-shadow: none;
    }
    .country-origins-page .card-header {
        padding: 12px 16px;
        background: var(--origins-surface);
    }
    .country-origins-page .card-footer { padding: 10px 16px; }

    /* Tighten the app-search wrapper so the dropdowns look right in the card header */
    .country-origins-page .app-search { position: relative; }
    .country-origins-page .app-search > .app-search-icon {
        position: absolute; right: 0.75rem; top: 50%;
        transform: translateY(-50%); pointer-events: none;
    }
    .country-origins-page .app-search > .form-control { padding-right: 2.25rem; min-width: 180px; }
    .country-origins-page .card-header .form-control,
    .country-origins-page .card-header .form-select {
        height: 38px;
        font-size: 0.8125rem;
        border-color: var(--origins-border);
    }

    /* Primary "Add Country" button */
    .country-origins-page .add-btn {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, var(--origins-primary-dark), var(--origins-primary));
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 9px 18px;
        font-weight: 600;
        font-size: 0.8125rem;
        text-decoration: none;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    .country-origins-page .add-btn:hover {
        color: #fff;
        box-shadow: 0 4px 10px rgba(29, 78, 216, 0.25);
        transform: translateY(-1px);
    }

    /* ---------- Table ---------- */
    .country-origins-page #originsTable thead th {
        background: #f1f5f9;
        color: var(--origins-text);
        font-weight: 700;
        font-size: 0.6875rem;
        letter-spacing: 0.03em;
        padding: 8px 12px;
        border-bottom: 1px solid var(--origins-border);
    }

    /* Sort arrows are too faint (low opacity) against the light header background — darken them */
    .country-origins-page #originsTable thead th span.dt-column-order:before,
    .country-origins-page #originsTable thead th span.dt-column-order:after {
        color: #475569;
    }
    .country-origins-page #originsTable thead th span.dt-column-order:before { opacity: .45; }
    .country-origins-page #originsTable thead th span.dt-column-order:after { opacity: .9; }
    .country-origins-page #originsTable thead th.dt-ordering-asc span.dt-column-order:before,
    .country-origins-page #originsTable thead th.dt-ordering-desc span.dt-column-order:after {
        color: var(--origins-primary);
        opacity: 1;
    }
    .country-origins-page #originsTable tbody td {
        padding: 6px 12px;
        vertical-align: middle;
        border-bottom: 1px solid var(--origins-border);
        font-size: 0.8125rem;
    }
    .country-origins-page #originsTable tbody tr {
        transition: background 0.2s ease;
    }
    .country-origins-page #originsTable tbody tr:hover {
        background: #f8fafc;
    }

    /* Country name */
    .country-origins-page .origin-name-link {
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--origins-text);
        text-decoration: none;
    }
    .country-origins-page .origin-name-link:hover { color: var(--origins-primary); }

    /* Status pill */
    .country-origins-page .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .country-origins-page .status-pill .status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        display: inline-block;
    }
    .country-origins-page .status-active { background: #ecfdf5; color: var(--origins-success); }
    .country-origins-page .status-active .status-dot { background: var(--origins-success); }
    .country-origins-page .status-inactive { background: #fef2f2; color: var(--origins-danger); }
    .country-origins-page .status-inactive .status-dot { background: var(--origins-danger); }

    /* Action buttons */
    .country-origins-page .action-btn {
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
    .country-origins-page .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }
    .country-origins-page .action-view {
        color: #2563eb;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
    }
    .country-origins-page .action-edit {
        color: #059669;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
    }
    .country-origins-page .action-toggle {
        color: #d97706;
        background: #fffbeb;
        border: 1px solid #fde68a;
    }
    .country-origins-page .action-delete {
        color: #dc2626;
        background: #fef2f2;
        border: 1px solid #fecaca;
    }

    /* DataTables "processing" loading indicator */
    .country-origins-page .dataTables_processing {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    .country-origins-page .origins-loading {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--origins-surface);
        border: 1px solid var(--origins-border);
        padding: 8px 18px;
        border-radius: 999px;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--origins-text);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.1);
    }
    .country-origins-page .origins-loading .spinner-border {
        width: 1rem;
        height: 1rem;
        color: var(--origins-primary);
        border-width: 0.15em;
    }

    /* Delete confirmation modal */
    .country-origins-page .delete-modal-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fef2f2;
        color: var(--origins-danger);
        font-size: 1.5rem;
    }

    /* Hide DataTables built-in length+filter+info+paginate (we render our own slots) */
    #originsTable_wrapper .dataTables_length,
    #originsTable_wrapper .dataTables_filter { display: none !important; }

    /* The cloned DataTables info/pagination land inside our card-footer slots */
    #originsInfoSlot .dataTables_info { padding: 0; color: var(--bs-body-color); font-size: 0.875rem; }
    #originsPaginationSlot .pagination { margin-bottom: 0; }
    #originsPaginationSlot .dataTables_paginate { margin: 0; }

    /* "Showing x to y..." info on the left, pagination on the right — overrides the
       app-wide pagination-left/info-right order, scoped to this page only. */
    .country-origins-page .card-footer #originsInfoSlot { order: 1; }
    .country-origins-page .card-footer #originsPaginationSlot { order: 2; }
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
            document.getElementById('originsToastContainer').appendChild(el);
            const toast = new bootstrap.Toast(el, { delay: 3000 });
            el.addEventListener('hidden.bs.toast', () => el.remove());
            toast.show();
        }

        // ============= DataTable =============
        const dt = $('#originsTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            order: [[3, 'asc']],
            ajax: {
                url: '{{ route('country-origins.data') }}',
                type: 'GET',
            },
            dom: 'rt<"datatables-tail"ip>',
            pageLength: 10,
            columns: [
                { data: 'DT_RowIndex',   name: 'DT_RowIndex',    orderable: false, searchable: false, className: 'text-center' },
                { data: 'name',          name: 'countries_of_origin.name' },
                { data: 'status',        name: 'countries_of_origin.status', className: 'text-center' },
                { data: 'display_order', name: 'countries_of_origin.display_order', className: 'text-center' },
                { data: 'action',        name: 'action', orderable: false, searchable: false, className: 'text-center' },
            ],
            language: {
                info: 'Showing _START_ to _END_ of _TOTAL_ countries',
                infoEmpty: 'No countries found',
                infoFiltered: ' (filtered from _MAX_ total)',
                emptyTable: 'No countries of origin yet. Click "Add Country" to get started.',
                zeroRecords: 'No countries match your search.',
                processing: '<div class="origins-loading"><span class="spinner-border spinner-border-sm"></span>Loading countries&hellip;</div>',
                paginate: {
                    previous: '<i class="ti ti-chevron-left"></i>',
                    next:     '<i class="ti ti-chevron-right"></i>',
                },
            },
            initComplete: function () {
                // Move DataTables-rendered info & pagination into our card-footer slots
                $('#originsInfoSlot').append($('#originsTable_info'));
                $('#originsPaginationSlot').append($('.datatables-tail'));
            },
        });

        // ============= Custom search wire-up (debounced) =============
        let searchTimer;
        $('#originSearch').on('keyup', function () {
            clearTimeout(searchTimer);
            const v = this.value;
            searchTimer = setTimeout(() => dt.search(v).draw(), 250);
        });

        // Per-page
        $('#originPerPage').on('change', function () {
            dt.page.len(parseInt(this.value, 10)).draw();
        });

        // Status filter — column index 2 is the status column
        $('#originStatusFilter').on('change', function () {
            dt.column(2).search(this.value).draw();
        });

        // ============= Toggle Status =============
        $('#originsTable tbody').on('click', '.js-toggle-status', function () {
            const url = $(this).data('url');
            $.ajax({
                url: url,
                type: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    if (res.success) {
                        dt.ajax.reload(null, false);
                        showToast('success', 'Country marked as ' + (res.label || (res.status ? 'Active' : 'Inactive')) + '.');
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
        const deleteModalEl = document.getElementById('deleteCountryOriginModal');
        const deleteModal = new bootstrap.Modal(deleteModalEl);
        let pendingDeleteUrl = null;

        $('#originsTable tbody').on('click', '.js-delete', function () {
            pendingDeleteUrl = $(this).data('url');
            $('#deleteCountryOriginName').text($(this).data('name'));
            deleteModal.show();
        });

        $('#confirmDeleteCountryOriginBtn').on('click', function () {
            if (!pendingDeleteUrl) return;
            const $btn = $(this).prop('disabled', true);

            $.ajax({
                url: pendingDeleteUrl,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    if (res.success) {
                        dt.ajax.reload(null, false);
                        showToast('success', res.message || 'Country deleted successfully.');
                    } else {
                        showToast('error', res.message || 'Could not delete country.');
                    }
                },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Failed to delete country.';
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
