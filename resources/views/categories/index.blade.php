@extends('layout.app')

@section('title', 'Stones')

@section('content')

<div class="container-fluid stones-page">

    {{-- Page title --}}
    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Stones</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Stones</a></li>
            </ol>
        </div>
    </div>

    {{-- Toast notifications for AJAX actions (status toggle, delete) --}}
    <div class="toast-container position-fixed top-0 end-0 p-3" id="stonesToastContainer" style="z-index: 1080;"></div>

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
                <div class="stat-value">{{ $stats['stones_total'] }}</div>
                <div class="stat-label">Total Stones</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-success"><i class="ti ti-circle-check"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['stones_active'] }}</div>
                <div class="stat-label">Active Stones</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-danger"><i class="ti ti-circle-x"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['stones_inactive'] }}</div>
                <div class="stat-label">Inactive Stones</div>
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
                            <input id="categorySearch" type="search" class="form-control"
                                placeholder="Search stone..." />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1">
                        <div class="app-search">
                            <select id="categoryStatusFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <i class="ti ti-circle app-search-icon text-muted"></i>
                        </div>

                        @permission('categories.create')
                        <a href="#!" class="add-btn ms-1" data-bs-toggle="modal"
                            data-bs-target="#addCategoryModal">
                            <i class="ti ti-plus fs-sm me-2"></i> Add Stone
                        </a>
                        @endpermission
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table id="categoriesTable" class="table table-custom table-centered table-select table-hover w-100 mb-0">
                        <thead class="align-middle thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3" style="width: 1%;">
                                    <input id="categorySelectAll" class="form-check-input form-check-input-light fs-14 mt-0"
                                        type="checkbox" />
                                </th>
                                <th class="text-center" style="width: 1%;">S.No</th>
                                <th><i class="ti ti-diamond me-1"></i>Stone Name</th>
                                <th><i class="ti ti-barcode me-1"></i>Code</th>
                                <th><i class="ti ti-toggle-right me-1"></i>Status</th>
                                <th><i class="ti ti-calendar-time me-1"></i>Last Modified</th>
                                <th class="text-center" style="width: 1%;"><i class="ti ti-settings me-1"></i>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                {{-- Card footer: DataTables info + pagination get moved here --}}
                <div class="card-footer border-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div id="categoriesInfoSlot" class="text-muted small"></div>
                        <div class="d-flex align-items-center gap-2 footer-pagination-group">
                            <select id="categoryPerPage" class="form-select form-select-sm" style="width: auto;">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="15">15</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                            </select>
                            <div id="categoriesPaginationSlot"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== Add Category Modal ==================== --}}
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" id="addCategoryModalApp">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">
                        <i class="ti ti-plus me-1"></i>
                        Add New Stone
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="quickAddForm" novalidate @submit.prevent="submitForm">
                    <div class="modal-body">
                        <div class="row g-3">
                            {{-- Status --}}
                            <div class="col-md-6">
                                <label class="form-label d-block">Status <span class="text-danger">*</span></label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="quick_status"
                                        v-model="form.status">
                                    <label class="form-check-label" for="quick_status">
                                        @{{ form.status ? 'Active' : 'Inactive' }}
                                    </label>
                                </div>
                            </div>

                            {{-- Name --}}
                            <div class="col-md-6">
                                <label for="quick_name" class="form-label">Stone Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.name }"
                                    id="quick_name" v-model="form.name" maxlength="150" required>
                                <div class="invalid-feedback">@{{ errors.name }}</div>
                            </div>

                            {{-- Code --}}
                            <div class="col-md-6">
                                <label for="quick_code" class="form-label">Stone Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-uppercase"
                                    :class="{ 'is-invalid': errors.code }"
                                    id="quick_code" v-model="form.code" maxlength="50" required>
                                <div class="invalid-feedback">@{{ errors.code }}</div>
                                <small class="text-muted">Letters, numbers, underscores only.</small>
                            </div>

                            {{-- Image --}}
                            <div class="col-md-12">
                                <label for="quick_image" class="form-label">Stone Image</label>
                                <input type="file" class="form-control" :class="{ 'is-invalid': errors.image }"
                                    id="quick_image" accept="image/jpeg,image/png" @change="onImageChange">
                                <div class="invalid-feedback">@{{ errors.image }}</div>
                                <small class="text-muted">JPG or PNG, max 2 MB.</small>
                            </div>

                            {{-- Description --}}
                            <div class="col-md-12">
                                <label for="quick_description" class="form-label">Description (optional)</label>
                                <textarea class="form-control" id="quick_description" v-model="form.description"
                                    rows="2" maxlength="1000" placeholder="Brief description..."></textarea>
                            </div>
                        </div>

                        <div v-if="serverError" class="alert alert-danger mt-3 mb-0">@{{ serverError }}</div>
                    </div>

                    <div class="modal-footer">
                        <a href="{{ route('categories.create') }}" class="btn btn-light me-auto">
                            <i class="ti ti-external-link me-1"></i>Open Full Form
                        </a>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" :disabled="submitting">
                            <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                            Add Stone
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- ==================== /Modal ==================== --}}

    {{-- ==================== Delete Confirmation Modal ==================== --}}
    <div class="modal fade" id="deleteStoneModal" tabindex="-1" aria-labelledby="deleteStoneModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="delete-modal-icon mx-auto mb-3">
                        <i class="ti ti-trash"></i>
                    </div>
                    <h5 class="modal-title mb-2" id="deleteStoneModalLabel">Delete this stone?</h5>
                    <p class="text-muted mb-0">
                        Are you sure you want to delete <strong id="deleteStoneName"></strong>?
                        This is a soft delete and can be restored later if needed.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteStoneBtn">
                        <i class="ti ti-trash me-1"></i>Delete Stone
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
       Stones (Category) page — compact ERP styling
       Scoped entirely under .stones-page so nothing here leaks
       into other modules that share the same layout/theme classes.
       ========================================================== */
    .stones-page {
        --stones-primary: #1d4ed8;
        --stones-primary-dark: #1e3a8a;
        --stones-cyan: #14b8a6;
        --stones-success: #059669;
        --stones-warning: #d97706;
        --stones-danger: #dc2626;
        --stones-bg: #f8fafc;
        --stones-surface: #ffffff;
        --stones-border: #e2e8f0;
        --stones-text: #1e293b;
        --stones-text-muted: #64748b;
        padding-top: 0;
        padding-bottom: 20px;
    }

    /* ---------- Page header ---------- */
    .stones-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid var(--stones-border);
    }
    .stones-page .page-title-head > * { display: flex; align-items: center; }
    .stones-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        color: var(--stones-text);
        position: relative;
        padding-left: 12px;
    }
    .stones-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 2px;
        bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, var(--stones-primary), var(--stones-cyan));
    }
    .stones-page .breadcrumb { font-size: 0.75rem; }

    /* ---------- Summary cards ---------- */
    .stones-page .stat-cards-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 16px;
    }
    .stones-page .stat-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--stones-surface);
        border: 1px solid var(--stones-border);
        border-radius: 10px;
        padding: 14px 16px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .stones-page .stat-icon {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    .stones-page .stat-icon-primary { background: #eff6ff; color: var(--stones-primary); }
    .stones-page .stat-icon-success { background: #ecfdf5; color: var(--stones-success); }
    .stones-page .stat-icon-danger  { background: #fef2f2; color: var(--stones-danger); }
    .stones-page .stat-value { font-size: 1.375rem; font-weight: 700; line-height: 1.2; color: var(--stones-text); }
    .stones-page .stat-label { font-size: 0.75rem; color: var(--stones-text-muted); font-weight: 500; }

    @media (max-width: 992px) {
        .stones-page .stat-cards-row { grid-template-columns: repeat(2, 1fr); }
    }

    /* ---------- Card ---------- */
    .stones-page .card {
        border: 1px solid var(--stones-border);
        border-radius: 10px;
        box-shadow: none;
    }
    .stones-page .card-header {
        padding: 12px 16px;
        background: var(--stones-surface);
    }
    .stones-page .card-footer { padding: 10px 16px; }

    /* Tighten the app-search wrapper so the dropdowns look right in the card header */
    .stones-page .app-search { position: relative; }
    .stones-page .app-search > .app-search-icon {
        position: absolute; right: 0.75rem; top: 50%;
        transform: translateY(-50%); pointer-events: none;
    }
    .stones-page .app-search > .form-control { padding-right: 2.25rem; min-width: 180px; }
    .stones-page .card-header .form-control,
    .stones-page .card-header .form-select {
        height: 38px;
        font-size: 0.8125rem;
        border-color: var(--stones-border);
    }

    /* Primary "Add Stone" button */
    .stones-page .add-btn {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, var(--stones-primary-dark), var(--stones-primary));
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 9px 18px;
        font-weight: 600;
        font-size: 0.8125rem;
        text-decoration: none;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    .stones-page .add-btn:hover {
        color: #fff;
        box-shadow: 0 4px 10px rgba(29, 78, 216, 0.25);
        transform: translateY(-1px);
    }

    /* ---------- Table ---------- */
    .stones-page #categoriesTable thead th {
        background: #f1f5f9;
        color: var(--stones-text);
        font-weight: 700;
        font-size: 0.6875rem;
        letter-spacing: 0.03em;
        padding: 8px 12px;
        border-bottom: 1px solid var(--stones-border);
    }

    /* Sort arrows are too faint (low opacity) against the light header background — darken them */
    .stones-page #categoriesTable thead th span.dt-column-order:before,
    .stones-page #categoriesTable thead th span.dt-column-order:after {
        color: #475569;
    }
    .stones-page #categoriesTable thead th span.dt-column-order:before { opacity: .45; }
    .stones-page #categoriesTable thead th span.dt-column-order:after { opacity: .9; }
    .stones-page #categoriesTable thead th.dt-ordering-asc span.dt-column-order:before,
    .stones-page #categoriesTable thead th.dt-ordering-desc span.dt-column-order:after {
        color: var(--stones-primary);
        opacity: 1;
    }
    .stones-page #categoriesTable tbody td {
        padding: 6px 12px;
        vertical-align: middle;
        border-bottom: 1px solid var(--stones-border);
        font-size: 0.8125rem;
    }
    .stones-page #categoriesTable tbody tr {
        transition: background 0.2s ease;
    }
    .stones-page #categoriesTable tbody tr:hover {
        background: #f8fafc;
    }

    /* Stone name + thumbnail */
    .stones-page .stone-name-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .stones-page .stone-thumb {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
        border: 1px solid var(--stones-border);
    }
    .stones-page .stone-thumb-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .stones-page .stone-thumb-placeholder {
        width: 100%;
        height: 100%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f1f5f9;
        color: var(--stones-text-muted);
    }
    .stones-page .stone-name-link {
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--stones-text);
        text-decoration: none;
    }
    .stones-page .stone-name-link:hover { color: var(--stones-primary); }

    /* Status pill */
    .stones-page .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .stones-page .status-pill .status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        display: inline-block;
    }
    .stones-page .status-active { background: #ecfdf5; color: var(--stones-success); }
    .stones-page .status-active .status-dot { background: var(--stones-success); }
    .stones-page .status-inactive { background: #fef2f2; color: var(--stones-danger); }
    .stones-page .status-inactive .status-dot { background: var(--stones-danger); }

    /* Action buttons */
    .stones-page .action-btn {
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
    .stones-page .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }
    .stones-page .action-view {
        color: #2563eb;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
    }
    .stones-page .action-edit {
        color: #059669;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
    }
    .stones-page .action-toggle {
        color: #d97706;
        background: #fffbeb;
        border: 1px solid #fde68a;
    }
    .stones-page .action-delete {
        color: #dc2626;
        background: #fef2f2;
        border: 1px solid #fecaca;
    }

    /* DataTables "processing" loading indicator */
    .stones-page .dataTables_processing {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    .stones-page .stones-loading {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--stones-surface);
        border: 1px solid var(--stones-border);
        padding: 8px 18px;
        border-radius: 999px;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--stones-text);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.1);
    }
    .stones-page .stones-loading .spinner-border {
        width: 1rem;
        height: 1rem;
        color: var(--stones-primary);
        border-width: 0.15em;
    }

    /* Delete confirmation modal */
    .stones-page .delete-modal-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fef2f2;
        color: var(--stones-danger);
        font-size: 1.5rem;
    }

    /* Hide DataTables built-in length+filter+info+paginate (we render our own slots) */
    #categoriesTable_wrapper .dataTables_length,
    #categoriesTable_wrapper .dataTables_filter { display: none !important; }

    /* The cloned DataTables info/pagination land inside our card-footer slots */
    #categoriesInfoSlot .dataTables_info { padding: 0; color: var(--bs-body-color); font-size: 0.875rem; }
    #categoriesPaginationSlot .pagination { margin-bottom: 0; }
    #categoriesPaginationSlot .dataTables_paginate { margin: 0; }

    /* "Showing x to y..." info on the left, pagination on the right — overrides the
       app-wide pagination-left/info-right order, scoped to this page only. */
    .stones-page .card-footer #categoriesInfoSlot { order: 1; }
    .stones-page .card-footer .footer-pagination-group { order: 2; }
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
            document.getElementById('stonesToastContainer').appendChild(el);
            const toast = new bootstrap.Toast(el, { delay: 3000 });
            el.addEventListener('hidden.bs.toast', () => el.remove());
            toast.show();
        }

        // ============= DataTable =============
        const dt = $('#categoriesTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            order: [[5, 'desc']], // most recently updated first
            ajax: {
                url: '{{ route('categories.data') }}',
                type: 'GET',
            },
            // dom: render table + (info+paginate) into a hidden wrapper; we'll move them via initComplete
            dom: 'rt<"datatables-tail"ip>',
            pageLength: 10,
            columns: [
                { data: 'checkbox',            name: 'checkbox',                  orderable: false, searchable: false, className: 'ps-3' },
                { data: 'DT_RowIndex',         name: 'DT_RowIndex',               orderable: false, searchable: false, className: 'text-center' },
                { data: 'name',                name: 'categories.name' },
                { data: 'code',                name: 'categories.code' },
                { data: 'status_badge',        name: 'categories.status',         searchable: true },
                { data: 'updated_at',          name: 'categories.updated_at' },
                { data: 'action',              name: 'action',                    orderable: false, searchable: false, className: 'text-center' },
            ],
            language: {
                info: 'Showing _START_ to _END_ of _TOTAL_ stones',
                infoEmpty: 'No stones found',
                infoFiltered: ' (filtered from _MAX_ total)',
                emptyTable: 'No stones yet. Click "Add Stone" to get started.',
                zeroRecords: 'No stones match your search.',
                processing: '<div class="stones-loading"><span class="spinner-border spinner-border-sm"></span>Loading stones&hellip;</div>',
                paginate: {
                    previous: '<i class="ti ti-chevron-left"></i>',
                    next:     '<i class="ti ti-chevron-right"></i>',
                },
            },
            initComplete: function () {
                // Move DataTables-rendered info & pagination into our card-footer slots
                $('#categoriesInfoSlot').append($('#categoriesTable_info'));
                $('#categoriesPaginationSlot').append($('.datatables-tail'));
            },
        });

        // ============= Custom search wire-up (debounced) =============
        let searchTimer;
        $('#categorySearch').on('keyup', function () {
            clearTimeout(searchTimer);
            const v = this.value;
            searchTimer = setTimeout(() => dt.search(v).draw(), 250);
        });

        // Per-page
        $('#categoryPerPage').on('change', function () {
            dt.page.len(parseInt(this.value, 10)).draw();
        });

        // Status filter — column index 4 is the status_badge column
        $('#categoryStatusFilter').on('change', function () {
            dt.column(4).search(this.value).draw();
        });

        // ============= Select-all =============
        $('#categorySelectAll').on('change', function () {
            $('#categoriesTable tbody .product-item-check').prop('checked', this.checked);
        });

        // ============= Toggle Status =============
        $('#categoriesTable tbody').on('click', '.js-toggle-status', function () {
            const url = $(this).data('url');
            $.ajax({
                url: url,
                type: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    if (res.success) {
                        dt.ajax.reload(null, false);
                        showToast('success', 'Stone marked as ' + (res.label || (res.status ? 'Active' : 'Inactive')) + '.');
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
        const deleteModalEl = document.getElementById('deleteStoneModal');
        const deleteModal = new bootstrap.Modal(deleteModalEl);
        let pendingDeleteUrl = null;

        $('#categoriesTable tbody').on('click', '.js-delete', function () {
            pendingDeleteUrl = $(this).data('url');
            $('#deleteStoneName').text($(this).data('name'));
            deleteModal.show();
        });

        $('#confirmDeleteStoneBtn').on('click', function () {
            if (!pendingDeleteUrl) return;
            const $btn = $(this).prop('disabled', true);

            $.ajax({
                url: pendingDeleteUrl,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    if (res.success) {
                        dt.ajax.reload(null, false);
                        showToast('success', res.message || 'Stone deleted successfully.');
                    } else {
                        showToast('error', res.message || 'Could not delete stone.');
                    }
                },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Failed to delete stone.';
                    showToast('error', msg);
                },
                complete: function () {
                    $btn.prop('disabled', false);
                    pendingDeleteUrl = null;
                    deleteModal.hide();
                },
            });
        });

        // ============= Quick-add modal (Vue) =============
        const quickAddVm = new Vue({
            el: '#addCategoryModalApp',
            data: {
                form: {
                    name: '',
                    code: '',
                    description: '',
                    status: true,
                },
                imageFile: null,
                errors: {},
                submitting: false,
                serverError: null,
            },
            methods: {
                onImageChange(e) {
                    this.$delete(this.errors, 'image');
                    const file = e.target.files[0];
                    if (!file) { this.imageFile = null; return; }
                    if (!['image/jpeg', 'image/png'].includes(file.type)) {
                        this.$set(this.errors, 'image', 'Image must be JPG or PNG.');
                        return;
                    }
                    if (file.size > 2 * 1024 * 1024) {
                        this.$set(this.errors, 'image', 'Image must not exceed 2 MB.');
                        return;
                    }
                    this.imageFile = file;
                },
                resetForm() {
                    this.form = { name: '', code: '', description: '', status: true };
                    this.imageFile = null;
                    this.errors = {};
                    this.serverError = null;
                    this.submitting = false;
                    const imgInput = document.getElementById('quick_image');
                    if (imgInput) imgInput.value = '';
                },
                validateLocal() {
                    this.errors = {};
                    if (!this.form.name || !this.form.name.trim()) {
                        this.$set(this.errors, 'name', 'Name is required.');
                    }
                    if (!this.form.code || !this.form.code.trim()) {
                        this.$set(this.errors, 'code', 'Code is required.');
                    } else if (!/^[A-Za-z0-9_]+$/.test(this.form.code)) {
                        this.$set(this.errors, 'code', 'Only letters, numbers, underscores.');
                    }
                    return Object.keys(this.errors).length === 0;
                },
                async submitForm() {
                    this.serverError = null;
                    if (!this.validateLocal()) return;
                    this.submitting = true;

                    const fd = new FormData();
                    fd.append('name', this.form.name);
                    fd.append('code', this.form.code);
                    fd.append('description', this.form.description || '');
                    fd.append('display_order', 0);
                    fd.append('status', this.form.status ? 1 : 0);
                    if (this.imageFile) fd.append('image', this.imageFile);

                    try {
                        const res = await fetch('{{ route('categories.store') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: fd,
                        });

                        if (res.status === 422) {
                            const data = await res.json();
                            const fe = data.errors || {};
                            Object.keys(fe).forEach((k) => this.$set(this.errors, k, fe[k][0]));
                            this.submitting = false;
                            return;
                        }
                        if (!res.ok) {
                            const data = await res.json().catch(() => ({}));
                            this.serverError = data.message || 'Something went wrong.';
                            this.submitting = false;
                            return;
                        }

                        // Success: close modal, reload table, reset form
                        const modalEl = document.getElementById('addCategoryModal');
                        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                        modal.hide();
                        this.resetForm();
                        dt.ajax.reload(null, false);
                    } catch (err) {
                        this.serverError = 'Network error. Please try again.';
                        this.submitting = false;
                    }
                },
            },
        });

        // Reset the form whenever the modal closes (so reopening is clean)
        document.getElementById('addCategoryModal').addEventListener('hidden.bs.modal', () => {
            quickAddVm.resetForm();
        });
    });
</script>
@endpush
