@extends('layout.app')

@section('title', 'Banners')

@section('content')

<div class="container-fluid banners-page">

    {{-- Page title --}}
    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Banners</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Marketing</a></li>
                <li class="breadcrumb-item active">Banners</li>
            </ol>
        </div>
    </div>

    {{-- Toast notifications for AJAX actions (status toggle, delete) --}}
    <div class="toast-container position-fixed top-0 end-0 p-3" id="bannersToastContainer" style="z-index: 1080;"></div>

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
            <div class="stat-icon stat-icon-primary"><i class="ti ti-photo"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['banners_total'] }}</div>
                <div class="stat-label">Total Banners</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-success"><i class="ti ti-circle-check"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['banners_active'] }}</div>
                <div class="stat-label">Active Banners</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-danger"><i class="ti ti-circle-x"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['banners_inactive'] }}</div>
                <div class="stat-label">Inactive Banners</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                {{-- Card header: search + per-page + position/status filters + add button --}}
                <div class="card-header border-light justify-content-between">
                    <div class="d-flex gap-2">
                        <div class="app-search">
                            <input id="bannerSearch" type="search" class="form-control"
                                placeholder="Search banners..." />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1">
                        <div class="app-search">
                            <select id="bannerPositionFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All Positions</option>
                                @foreach (\App\Models\Banner::POSITIONS as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <i class="ti ti-layout app-search-icon text-muted"></i>
                        </div>

                        <div class="app-search">
                            <select id="bannerStatusFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <i class="ti ti-circle app-search-icon text-muted"></i>
                        </div>

                        @permission('banners.create')
                        <a href="{{ route('banners.create') }}" class="add-btn ms-1">
                            <i class="ti ti-plus fs-sm me-2"></i> Add Banner
                        </a>
                        @endpermission
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table id="bannersTable" class="table table-custom table-centered table-select table-hover w-100 mb-0">
                        <thead class="align-middle thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3" style="width: 1%;">
                                    <input id="bannerSelectAll" class="form-check-input form-check-input-light fs-14 mt-0"
                                        type="checkbox" />
                                </th>
                                <th class="text-center" style="width: 1%;">S.No</th>
                                <th><i class="ti ti-photo me-1"></i>Banner</th>
                                <th><i class="ti ti-layout me-1"></i>Position</th>
                                <th class="text-center"><i class="ti ti-bolt me-1"></i>Live</th>
                                <th><i class="ti ti-toggle-right me-1"></i>Status</th>
                                <th><i class="ti ti-calendar-time me-1"></i>Date Range</th>
                                <th class="text-center"><i class="ti ti-arrows-sort me-1"></i>Order</th>
                                <th><i class="ti ti-calendar-plus me-1"></i>Created</th>
                                <th class="text-center" style="width: 1%;"><i class="ti ti-settings me-1"></i>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                {{-- Card footer: DataTables info + pagination get moved here --}}
                <div class="card-footer border-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div id="bannersInfoSlot" class="text-muted small"></div>
                        <div class="d-flex align-items-center gap-2 footer-pagination-group">
                            <select id="bannerPerPage" class="form-select form-select-sm" style="width: auto;">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="15">15</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                            </select>
                            <div id="bannersPaginationSlot"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== Delete Confirmation Modal ==================== --}}
    <div class="modal fade" id="deleteBannerModal" tabindex="-1" aria-labelledby="deleteBannerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="delete-modal-icon mx-auto mb-3">
                        <i class="ti ti-trash"></i>
                    </div>
                    <h5 class="modal-title mb-2" id="deleteBannerModalLabel">Delete this banner?</h5>
                    <p class="text-muted mb-0">
                        Are you sure you want to delete <strong id="deleteBannerName"></strong>?
                        This is a soft delete and can be restored later if needed.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBannerBtn">
                        <i class="ti ti-trash me-1"></i>Delete Banner
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
       Banners page — compact ERP styling
       Scoped entirely under .banners-page so nothing here leaks
       into other modules that share the same layout/theme classes.
       ========================================================== */
    .banners-page {
        --banners-primary: #1d4ed8;
        --banners-primary-dark: #1e3a8a;
        --banners-cyan: #14b8a6;
        --banners-success: #059669;
        --banners-warning: #d97706;
        --banners-danger: #dc2626;
        --banners-bg: #f8fafc;
        --banners-surface: #ffffff;
        --banners-border: #e2e8f0;
        --banners-text: #1e293b;
        --banners-text-muted: #64748b;
        padding-top: 0;
        padding-bottom: 20px;
    }

    /* ---------- Page header ---------- */
    .banners-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid var(--banners-border);
    }
    .banners-page .page-title-head > * { display: flex; align-items: center; }
    .banners-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        color: var(--banners-text);
        position: relative;
        padding-left: 12px;
    }
    .banners-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 2px;
        bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, var(--banners-primary), var(--banners-cyan));
    }
    .banners-page .breadcrumb { font-size: 0.75rem; }

    /* ---------- Summary cards ---------- */
    .banners-page .stat-cards-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 16px;
    }
    .banners-page .stat-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--banners-surface);
        border: 1px solid var(--banners-border);
        border-radius: 10px;
        padding: 14px 16px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .banners-page .stat-icon {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    .banners-page .stat-icon-primary { background: #eff6ff; color: var(--banners-primary); }
    .banners-page .stat-icon-success { background: #ecfdf5; color: var(--banners-success); }
    .banners-page .stat-icon-danger  { background: #fef2f2; color: var(--banners-danger); }
    .banners-page .stat-value { font-size: 1.375rem; font-weight: 700; line-height: 1.2; color: var(--banners-text); }
    .banners-page .stat-label { font-size: 0.75rem; color: var(--banners-text-muted); font-weight: 500; }

    @media (max-width: 992px) {
        .banners-page .stat-cards-row { grid-template-columns: repeat(2, 1fr); }
    }

    /* ---------- Card ---------- */
    .banners-page .card {
        border: 1px solid var(--banners-border);
        border-radius: 10px;
        box-shadow: none;
    }
    .banners-page .card-header {
        padding: 12px 16px;
        background: var(--banners-surface);
    }
    .banners-page .card-footer { padding: 10px 16px; }

    /* Tighten the app-search wrapper so the dropdowns look right in the card header */
    .banners-page .app-search { position: relative; }
    .banners-page .app-search > .app-search-icon {
        position: absolute; right: 0.75rem; top: 50%;
        transform: translateY(-50%); pointer-events: none;
    }
    .banners-page .app-search > .form-control { padding-right: 2.25rem; min-width: 160px; }
    .banners-page .card-header .form-control,
    .banners-page .card-header .form-select {
        height: 38px;
        font-size: 0.8125rem;
        border-color: var(--banners-border);
    }

    /* Primary "Add Banner" button */
    .banners-page .add-btn {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, var(--banners-primary-dark), var(--banners-primary));
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 9px 18px;
        font-weight: 600;
        font-size: 0.8125rem;
        text-decoration: none;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    .banners-page .add-btn:hover {
        color: #fff;
        box-shadow: 0 4px 10px rgba(29, 78, 216, 0.25);
        transform: translateY(-1px);
    }

    /* ---------- Table ---------- */
    .banners-page #bannersTable thead th {
        background: #f1f5f9;
        color: var(--banners-text);
        font-weight: 700;
        font-size: 0.6875rem;
        letter-spacing: 0.03em;
        padding: 8px 12px;
        border-bottom: 1px solid var(--banners-border);
    }

    /* Sort arrows are too faint (low opacity) against the light header background — darken them */
    .banners-page #bannersTable thead th span.dt-column-order:before,
    .banners-page #bannersTable thead th span.dt-column-order:after {
        color: #475569;
    }
    .banners-page #bannersTable thead th span.dt-column-order:before { opacity: .45; }
    .banners-page #bannersTable thead th span.dt-column-order:after { opacity: .9; }
    .banners-page #bannersTable thead th.dt-ordering-asc span.dt-column-order:before,
    .banners-page #bannersTable thead th.dt-ordering-desc span.dt-column-order:after {
        color: var(--banners-primary);
        opacity: 1;
    }
    .banners-page #bannersTable tbody td {
        padding: 6px 12px;
        vertical-align: middle;
        border-bottom: 1px solid var(--banners-border);
        font-size: 0.8125rem;
    }
    .banners-page #bannersTable tbody tr {
        transition: background 0.2s ease;
    }
    .banners-page #bannersTable tbody tr:hover {
        background: #f8fafc;
    }

    /* Banner name + thumbnail */
    .banners-page .banner-name-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .banners-page .banner-thumb {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
        border: 1px solid var(--banners-border);
    }
    .banners-page .banner-thumb-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .banners-page .banner-thumb-placeholder {
        width: 100%;
        height: 100%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f1f5f9;
        color: var(--banners-text-muted);
    }
    .banners-page .banner-name-link {
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--banners-text);
        text-decoration: none;
    }
    .banners-page .banner-name-link:hover { color: var(--banners-primary); }

    /* Status pill */
    .banners-page .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .banners-page .status-pill .status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        display: inline-block;
    }
    .banners-page .status-active { background: #ecfdf5; color: var(--banners-success); }
    .banners-page .status-active .status-dot { background: var(--banners-success); }
    .banners-page .status-inactive { background: #fef2f2; color: var(--banners-danger); }
    .banners-page .status-inactive .status-dot { background: var(--banners-danger); }

    /* Action buttons */
    .banners-page .action-btn {
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
    .banners-page .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }
    .banners-page .action-view {
        color: #2563eb;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
    }
    .banners-page .action-edit {
        color: #059669;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
    }
    .banners-page .action-toggle {
        color: #d97706;
        background: #fffbeb;
        border: 1px solid #fde68a;
    }
    .banners-page .action-delete {
        color: #dc2626;
        background: #fef2f2;
        border: 1px solid #fecaca;
    }

    /* DataTables "processing" loading indicator */
    .banners-page .dataTables_processing {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    .banners-page .banners-loading {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--banners-surface);
        border: 1px solid var(--banners-border);
        padding: 8px 18px;
        border-radius: 999px;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--banners-text);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.1);
    }
    .banners-page .banners-loading .spinner-border {
        width: 1rem;
        height: 1rem;
        color: var(--banners-primary);
        border-width: 0.15em;
    }

    /* Delete confirmation modal */
    .banners-page .delete-modal-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fef2f2;
        color: var(--banners-danger);
        font-size: 1.5rem;
    }

    /* Hide DataTables built-in length+filter+info+paginate (we render our own slots) */
    #bannersTable_wrapper .dataTables_length,
    #bannersTable_wrapper .dataTables_filter { display: none !important; }

    /* The cloned DataTables info/pagination land inside our card-footer slots */
    #bannersInfoSlot .dataTables_info { padding: 0; color: var(--bs-body-color); font-size: 0.875rem; }
    #bannersPaginationSlot .pagination { margin-bottom: 0; }
    #bannersPaginationSlot .dataTables_paginate { margin: 0; }

    /* "Showing x to y..." info on the left, pagination on the right — overrides the
       app-wide pagination-left/info-right order, scoped to this page only. */
    .banners-page .card-footer #bannersInfoSlot { order: 1; }
    .banners-page .card-footer .footer-pagination-group { order: 2; }
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
            document.getElementById('bannersToastContainer').appendChild(el);
            const toast = new bootstrap.Toast(el, { delay: 3000 });
            el.addEventListener('hidden.bs.toast', () => el.remove());
            toast.show();
        }

        // ============= DataTable =============
        const dt = $('#bannersTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            order: [[7, 'asc']],
            ajax: {
                url: '{{ route('banners.data') }}',
                type: 'GET',
            },
            dom: 'rt<"datatables-tail"ip>',
            pageLength: 10,
            columns: [
                { data: 'checkbox',        name: 'checkbox',           orderable: false, searchable: false, className: 'ps-3' },
                { data: 'DT_RowIndex',     name: 'DT_RowIndex',        orderable: false, searchable: false, className: 'text-center' },
                { data: 'title',           name: 'banners.title' },
                { data: 'position_badge',  name: 'banners.position',   searchable: true },
                { data: 'live_badge',      name: 'live_badge',         orderable: false, searchable: false, className: 'text-center' },
                { data: 'status_badge',    name: 'banners.status',     searchable: true },
                { data: 'date_range',      name: 'date_range',         orderable: false, searchable: false },
                { data: 'sort_order',      name: 'banners.sort_order', className: 'text-center' },
                { data: 'created_at',      name: 'banners.created_at' },
                { data: 'action',          name: 'action',             orderable: false, searchable: false, className: 'text-center' },
            ],
            language: {
                info: 'Showing _START_ to _END_ of _TOTAL_ banners',
                infoEmpty: 'No banners found',
                infoFiltered: ' (filtered from _MAX_ total)',
                emptyTable: 'No banners yet. Click "Add Banner" to get started.',
                zeroRecords: 'No banners match your search.',
                processing: '<div class="banners-loading"><span class="spinner-border spinner-border-sm"></span>Loading banners&hellip;</div>',
                paginate: {
                    previous: '<i class="ti ti-chevron-left"></i>',
                    next:     '<i class="ti ti-chevron-right"></i>',
                },
            },
            initComplete: function () {
                // Move DataTables-rendered info & pagination into our card-footer slots
                $('#bannersInfoSlot').append($('#bannersTable_info'));
                $('#bannersPaginationSlot').append($('.datatables-tail'));
            },
        });

        // ============= Custom search wire-up (debounced) =============
        let searchTimer;
        $('#bannerSearch').on('keyup', function () {
            clearTimeout(searchTimer);
            const v = this.value;
            searchTimer = setTimeout(() => dt.search(v).draw(), 250);
        });

        // Per-page
        $('#bannerPerPage').on('change', function () {
            dt.page.len(parseInt(this.value, 10)).draw();
        });

        // Position filter — column index 3 is the position_badge column
        $('#bannerPositionFilter').on('change', function () {
            dt.column(3).search(this.value).draw();
        });

        // Status filter — column index 5 is the status_badge column
        $('#bannerStatusFilter').on('change', function () {
            dt.column(5).search(this.value).draw();
        });

        // ============= Select-all =============
        $('#bannerSelectAll').on('change', function () {
            $('#bannersTable tbody .banner-item-check').prop('checked', this.checked);
        });

        // ============= Toggle Status =============
        $('#bannersTable tbody').on('click', '.js-toggle-status', function () {
            const url = $(this).data('url');
            $.ajax({
                url: url,
                type: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    if (res.success) {
                        dt.ajax.reload(null, false);
                        showToast('success', 'Banner marked as ' + (res.label || (res.status ? 'Active' : 'Inactive')) + '.');
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
        const deleteModalEl = document.getElementById('deleteBannerModal');
        const deleteModal = new bootstrap.Modal(deleteModalEl);
        let pendingDeleteUrl = null;

        $('#bannersTable tbody').on('click', '.js-delete', function () {
            pendingDeleteUrl = $(this).data('url');
            $('#deleteBannerName').text($(this).data('name'));
            deleteModal.show();
        });

        $('#confirmDeleteBannerBtn').on('click', function () {
            if (!pendingDeleteUrl) return;
            const $btn = $(this).prop('disabled', true);

            $.ajax({
                url: pendingDeleteUrl,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    if (res.success) {
                        dt.ajax.reload(null, false);
                        showToast('success', res.message || 'Banner deleted successfully.');
                    } else {
                        showToast('error', res.message || 'Could not delete banner.');
                    }
                },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Failed to delete banner.';
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
