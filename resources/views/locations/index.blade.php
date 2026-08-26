@extends('layout.app')

@section('title', 'Locations')

@section('content')

<div class="container-fluid locations-page">

    {{-- Page title --}}
    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Locations</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Operations</a></li>
                <li class="breadcrumb-item active">Locations</li>
            </ol>
        </div>
    </div>

    {{-- Toast notifications for AJAX actions (status toggle, set default, delete) --}}
    <div class="toast-container position-fixed top-0 end-0 p-3" id="locationsToastContainer" style="z-index: 1080;"></div>

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
            <div class="stat-icon stat-icon-primary"><i class="ti ti-map-pin"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['locations_total'] }}</div>
                <div class="stat-label">Total Locations</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-success"><i class="ti ti-circle-check"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['locations_active'] }}</div>
                <div class="stat-label">Active Locations</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-danger"><i class="ti ti-circle-x"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['locations_inactive'] }}</div>
                <div class="stat-label">Inactive Locations</div>
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
                            <input id="locationSearch" type="search" class="form-control"
                                placeholder="Search locations..." />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1">
                        <div>
                            <select id="locationPerPage" class="form-select form-control my-1 my-md-0">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="15">15</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                            </select>
                        </div>

                        <div class="app-search">
                            <select id="locationTypeFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All Types</option>
                                @foreach (\App\Models\Location::TYPES as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <i class="ti ti-building app-search-icon text-muted"></i>
                        </div>

                        <div class="app-search">
                            <select id="locationStatusFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <i class="ti ti-circle app-search-icon text-muted"></i>
                        </div>

                        @permission('locations.create')
                        <a href="{{ route('locations.create') }}" class="add-btn ms-1">
                            <i class="ti ti-plus fs-sm me-2"></i> Add Location
                        </a>
                        @endpermission
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table id="locationsTable" class="table table-custom table-centered table-select table-hover w-100 mb-0">
                        <thead class="align-middle thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3" style="width: 1%;">
                                    <input id="locationSelectAll" class="form-check-input form-check-input-light fs-14 mt-0"
                                        type="checkbox" />
                                </th>
                                <th class="text-center" style="width: 1%;">S.No</th>
                                <th><i class="ti ti-barcode me-1"></i>Code</th>
                                <th><i class="ti ti-map-pin me-1"></i>Location</th>
                                <th><i class="ti ti-building me-1"></i>Type</th>
                                <th><i class="ti ti-user me-1"></i>Manager</th>
                                <th><i class="ti ti-phone me-1"></i>Contact</th>
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
                        <div id="locationsInfoSlot" class="text-muted small"></div>
                        <div id="locationsPaginationSlot"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== Delete Confirmation Modal ==================== --}}
    <div class="modal fade" id="deleteLocationModal" tabindex="-1" aria-labelledby="deleteLocationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="delete-modal-icon mx-auto mb-3">
                        <i class="ti ti-trash"></i>
                    </div>
                    <h5 class="modal-title mb-2" id="deleteLocationModalLabel">Delete this location?</h5>
                    <p class="text-muted mb-0">
                        Are you sure you want to delete <strong id="deleteLocationName"></strong>?
                        This is a soft delete and can be restored later if needed.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteLocationBtn">
                        <i class="ti ti-trash me-1"></i>Delete Location
                    </button>
                </div>
            </div>
        </div>
    </div>
    {{-- ==================== /Delete Confirmation Modal ==================== --}}

    {{-- ==================== Set Default Confirmation Modal ==================== --}}
    <div class="modal fade" id="setDefaultLocationModal" tabindex="-1" aria-labelledby="setDefaultLocationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="default-modal-icon mx-auto mb-3">
                        <i class="ti ti-star"></i>
                    </div>
                    <h5 class="modal-title mb-2" id="setDefaultLocationModalLabel">Set as default location?</h5>
                    <p class="text-muted mb-0">
                        Make <strong id="setDefaultLocationName"></strong> the default location?
                        The current default location will be demoted.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmSetDefaultLocationBtn">
                        <i class="ti ti-star me-1"></i>Set as Default
                    </button>
                </div>
            </div>
        </div>
    </div>
    {{-- ==================== /Set Default Confirmation Modal ==================== --}}

</div>

@endsection

@push('styles')
<style>
    /* ==========================================================
       Locations page — compact ERP styling
       Scoped entirely under .locations-page so nothing here leaks
       into other modules that share the same layout/theme classes.
       ========================================================== */
    .locations-page {
        --locations-primary: #1d4ed8;
        --locations-primary-dark: #1e3a8a;
        --locations-cyan: #14b8a6;
        --locations-success: #059669;
        --locations-warning: #d97706;
        --locations-danger: #dc2626;
        --locations-bg: #f8fafc;
        --locations-surface: #ffffff;
        --locations-border: #e2e8f0;
        --locations-text: #1e293b;
        --locations-text-muted: #64748b;
        padding-top: 0;
        padding-bottom: 20px;
    }

    /* ---------- Page header ---------- */
    .locations-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid var(--locations-border);
    }
    .locations-page .page-title-head > * { display: flex; align-items: center; }
    .locations-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        color: var(--locations-text);
        position: relative;
        padding-left: 12px;
    }
    .locations-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 2px;
        bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, var(--locations-primary), var(--locations-cyan));
    }
    .locations-page .breadcrumb { font-size: 0.75rem; }

    /* ---------- Summary cards ---------- */
    .locations-page .stat-cards-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 16px;
    }
    .locations-page .stat-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--locations-surface);
        border: 1px solid var(--locations-border);
        border-radius: 10px;
        padding: 14px 16px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .locations-page .stat-icon {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    .locations-page .stat-icon-primary { background: #eff6ff; color: var(--locations-primary); }
    .locations-page .stat-icon-success { background: #ecfdf5; color: var(--locations-success); }
    .locations-page .stat-icon-danger  { background: #fef2f2; color: var(--locations-danger); }
    .locations-page .stat-value { font-size: 1.375rem; font-weight: 700; line-height: 1.2; color: var(--locations-text); }
    .locations-page .stat-label { font-size: 0.75rem; color: var(--locations-text-muted); font-weight: 500; }

    @media (max-width: 992px) {
        .locations-page .stat-cards-row { grid-template-columns: repeat(2, 1fr); }
    }

    /* ---------- Card ---------- */
    .locations-page .card {
        border: 1px solid var(--locations-border);
        border-radius: 10px;
        box-shadow: none;
    }
    .locations-page .card-header {
        padding: 12px 16px;
        background: var(--locations-surface);
    }
    .locations-page .card-footer { padding: 10px 16px; }

    /* Tighten the app-search wrapper so the dropdowns look right in the card header */
    .locations-page .app-search { position: relative; }
    .locations-page .app-search > .app-search-icon {
        position: absolute; right: 0.75rem; top: 50%;
        transform: translateY(-50%); pointer-events: none;
    }
    .locations-page .app-search > .form-control { padding-right: 2.25rem; min-width: 160px; }
    .locations-page .card-header .form-control,
    .locations-page .card-header .form-select {
        height: 38px;
        font-size: 0.8125rem;
        border-color: var(--locations-border);
    }

    /* Primary "Add Location" button */
    .locations-page .add-btn {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, var(--locations-primary-dark), var(--locations-primary));
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 9px 18px;
        font-weight: 600;
        font-size: 0.8125rem;
        text-decoration: none;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    .locations-page .add-btn:hover {
        color: #fff;
        box-shadow: 0 4px 10px rgba(29, 78, 216, 0.25);
        transform: translateY(-1px);
    }

    /* ---------- Table ---------- */
    .locations-page #locationsTable thead th {
        background: #f1f5f9;
        color: var(--locations-text);
        font-weight: 700;
        font-size: 0.6875rem;
        letter-spacing: 0.03em;
        padding: 8px 12px;
        border-bottom: 1px solid var(--locations-border);
    }

    /* Sort arrows are too faint (low opacity) against the light header background — darken them */
    .locations-page #locationsTable thead th span.dt-column-order:before,
    .locations-page #locationsTable thead th span.dt-column-order:after {
        color: #475569;
    }
    .locations-page #locationsTable thead th span.dt-column-order:before { opacity: .45; }
    .locations-page #locationsTable thead th span.dt-column-order:after { opacity: .9; }
    .locations-page #locationsTable thead th.dt-ordering-asc span.dt-column-order:before,
    .locations-page #locationsTable thead th.dt-ordering-desc span.dt-column-order:after {
        color: var(--locations-primary);
        opacity: 1;
    }
    .locations-page #locationsTable tbody td {
        padding: 6px 12px;
        vertical-align: middle;
        border-bottom: 1px solid var(--locations-border);
        font-size: 0.8125rem;
    }
    .locations-page #locationsTable tbody tr {
        transition: background 0.2s ease;
    }
    .locations-page #locationsTable tbody tr:hover {
        background: #f8fafc;
    }

    /* Location name (no image field on this module — bold link + muted sub-line, no thumbnail) */
    .locations-page .location-name-cell { line-height: 1.3; }
    .locations-page .location-name-link {
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--locations-text);
        text-decoration: none;
    }
    .locations-page .location-name-link:hover { color: var(--locations-primary); }

    /* Default-location badge (next to the location code) */
    .locations-page .default-badge {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        margin-left: 6px;
        padding: 2px 7px;
        border-radius: 999px;
        font-size: 0.6875rem;
        font-weight: 700;
        background: #fffbeb;
        color: #b45309;
        border: 1px solid #fde68a;
        vertical-align: middle;
    }
    .locations-page .default-badge i { font-size: 0.7rem; }

    /* Status pill */
    .locations-page .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .locations-page .status-pill .status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        display: inline-block;
    }
    .locations-page .status-active { background: #ecfdf5; color: var(--locations-success); }
    .locations-page .status-active .status-dot { background: var(--locations-success); }
    .locations-page .status-inactive { background: #fef2f2; color: var(--locations-danger); }
    .locations-page .status-inactive .status-dot { background: var(--locations-danger); }

    /* Action buttons */
    .locations-page .action-btn {
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
    .locations-page .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }
    .locations-page .action-btn:disabled {
        cursor: not-allowed;
        opacity: .65;
    }
    .locations-page .action-btn:disabled:hover {
        transform: none;
        box-shadow: none;
    }
    .locations-page .action-view {
        color: #2563eb;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
    }
    .locations-page .action-edit {
        color: #059669;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
    }
    .locations-page .action-default {
        color: #7c3aed;
        background: #f5f3ff;
        border: 1px solid #ddd6fe;
    }
    .locations-page .action-toggle {
        color: #d97706;
        background: #fffbeb;
        border: 1px solid #fde68a;
    }
    .locations-page .action-delete {
        color: #dc2626;
        background: #fef2f2;
        border: 1px solid #fecaca;
    }

    /* DataTables "processing" loading indicator */
    .locations-page .dataTables_processing {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    .locations-page .locations-loading {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--locations-surface);
        border: 1px solid var(--locations-border);
        padding: 8px 18px;
        border-radius: 999px;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--locations-text);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.1);
    }
    .locations-page .locations-loading .spinner-border {
        width: 1rem;
        height: 1rem;
        color: var(--locations-primary);
        border-width: 0.15em;
    }

    /* Delete confirmation modal */
    .locations-page .delete-modal-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fef2f2;
        color: var(--locations-danger);
        font-size: 1.5rem;
    }

    /* Set-default confirmation modal */
    .locations-page .default-modal-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f5f3ff;
        color: #7c3aed;
        font-size: 1.5rem;
    }

    /* Hide DataTables built-in length+filter+info+paginate (we render our own slots) */
    #locationsTable_wrapper .dataTables_length,
    #locationsTable_wrapper .dataTables_filter { display: none !important; }

    /* The cloned DataTables info/pagination land inside our card-footer slots */
    #locationsInfoSlot .dataTables_info { padding: 0; color: var(--bs-body-color); font-size: 0.875rem; }
    #locationsPaginationSlot .pagination { margin-bottom: 0; }
    #locationsPaginationSlot .dataTables_paginate { margin: 0; }

    /* "Showing x to y..." info on the left, pagination on the right — overrides the
       app-wide pagination-left/info-right order, scoped to this page only. */
    .locations-page .card-footer #locationsInfoSlot { order: 1; }
    .locations-page .card-footer #locationsPaginationSlot { order: 2; }
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
            document.getElementById('locationsToastContainer').appendChild(el);
            const toast = new bootstrap.Toast(el, { delay: 3000 });
            el.addEventListener('hidden.bs.toast', () => el.remove());
            toast.show();
        }

        // ============= DataTable =============
        const dt = $('#locationsTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            order: [[8, 'desc']],
            ajax: {
                url: '{{ route('locations.data') }}',
                type: 'GET',
            },
            // dom: render table + (info+paginate) into a hidden wrapper; we'll move them via initComplete
            dom: 'rt<"datatables-tail"ip>',
            pageLength: 10,
            columns: [
                { data: 'checkbox',      name: 'checkbox',                  orderable: false, searchable: false, className: 'ps-3' },
                { data: 'DT_RowIndex',   name: 'DT_RowIndex',               orderable: false, searchable: false, className: 'text-center' },
                { data: 'location_code', name: 'locations.location_code' },
                { data: 'name',          name: 'locations.name' },
                { data: 'type_badge',    name: 'locations.type',            searchable: true },
                { data: 'manager',       name: 'manager',                   orderable: false, searchable: false },
                { data: 'contact',       name: 'contact',                   orderable: false, searchable: false },
                { data: 'status_badge',  name: 'locations.status',          searchable: true },
                { data: 'created_at',    name: 'locations.created_at' },
                { data: 'action',        name: 'action',                    orderable: false, searchable: false, className: 'text-center' },
            ],
            language: {
                info: 'Showing _START_ to _END_ of _TOTAL_ locations',
                infoEmpty: 'No locations found',
                infoFiltered: ' (filtered from _MAX_ total)',
                emptyTable: 'No locations yet. Click "Add Location" to get started.',
                zeroRecords: 'No locations match your search.',
                processing: '<div class="locations-loading"><span class="spinner-border spinner-border-sm"></span>Loading locations&hellip;</div>',
                paginate: {
                    previous: '<i class="ti ti-chevron-left"></i>',
                    next:     '<i class="ti ti-chevron-right"></i>',
                },
            },
            initComplete: function () {
                // Move DataTables-rendered info & pagination into our card-footer slots
                $('#locationsInfoSlot').append($('#locationsTable_info'));
                $('#locationsPaginationSlot').append($('.datatables-tail'));
            },
        });

        // ============= Custom search wire-up (debounced) =============
        let searchTimer;
        $('#locationSearch').on('keyup', function () {
            clearTimeout(searchTimer);
            const v = this.value;
            searchTimer = setTimeout(() => dt.search(v).draw(), 250);
        });

        // Per-page
        $('#locationPerPage').on('change', function () {
            dt.page.len(parseInt(this.value, 10)).draw();
        });

        // Type filter — column index 4 is the type_badge column
        $('#locationTypeFilter').on('change', function () {
            dt.column(4).search(this.value).draw();
        });

        // Status filter — column index 7 is the status_badge column
        $('#locationStatusFilter').on('change', function () {
            dt.column(7).search(this.value).draw();
        });

        // ============= Select-all =============
        $('#locationSelectAll').on('change', function () {
            $('#locationsTable tbody .product-item-check').prop('checked', this.checked);
        });

        // ============= Toggle Status =============
        $('#locationsTable tbody').on('click', '.js-toggle-status', function () {
            const url = $(this).data('url');
            $.ajax({
                url: url,
                type: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    if (res.success) {
                        dt.ajax.reload(null, false);
                        showToast('success', 'Location marked as ' + (res.label || (res.status ? 'Active' : 'Inactive')) + '.');
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

        // ============= Set Default (styled confirmation modal) =============
        const setDefaultModalEl = document.getElementById('setDefaultLocationModal');
        const setDefaultModal = new bootstrap.Modal(setDefaultModalEl);
        let pendingSetDefaultUrl = null;

        $('#locationsTable tbody').on('click', '.js-set-default', function () {
            pendingSetDefaultUrl = $(this).data('url');
            $('#setDefaultLocationName').text($(this).data('name'));
            setDefaultModal.show();
        });

        $('#confirmSetDefaultLocationBtn').on('click', function () {
            if (!pendingSetDefaultUrl) return;
            const $btn = $(this).prop('disabled', true);

            $.ajax({
                url: pendingSetDefaultUrl,
                type: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    if (res.success) {
                        dt.ajax.reload(null, false);
                        showToast('success', res.message || 'Default location updated.');
                    } else {
                        showToast('error', res.message || 'Failed to set default.');
                    }
                },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Failed to set default.';
                    showToast('error', msg);
                },
                complete: function () {
                    $btn.prop('disabled', false);
                    pendingSetDefaultUrl = null;
                    setDefaultModal.hide();
                },
            });
        });

        // ============= Delete (styled confirmation modal) =============
        const deleteModalEl = document.getElementById('deleteLocationModal');
        const deleteModal = new bootstrap.Modal(deleteModalEl);
        let pendingDeleteUrl = null;

        $('#locationsTable tbody').on('click', '.js-delete', function () {
            pendingDeleteUrl = $(this).data('url');
            $('#deleteLocationName').text($(this).data('name'));
            deleteModal.show();
        });

        $('#confirmDeleteLocationBtn').on('click', function () {
            if (!pendingDeleteUrl) return;
            const $btn = $(this).prop('disabled', true);

            $.ajax({
                url: pendingDeleteUrl,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    if (res.success) {
                        dt.ajax.reload(null, false);
                        showToast('success', res.message || 'Location deleted successfully.');
                    } else {
                        showToast('error', res.message || 'Could not delete location.');
                    }
                },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Failed to delete location.';
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
