@extends('layout.app')

@section('title', 'Blog')

@section('content')

<div class="container-fluid blogs-page">

    {{-- Page title --}}
    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Blog</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Marketing</a></li>
                <li class="breadcrumb-item active">Blog</li>
            </ol>
        </div>
    </div>

    {{-- Toast notifications for AJAX actions (status toggle, delete) --}}
    <div class="toast-container position-fixed top-0 end-0 p-3" id="blogsToastContainer" style="z-index: 1080;"></div>

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
            <div class="stat-icon stat-icon-primary"><i class="ti ti-article"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['blogs_total'] }}</div>
                <div class="stat-label">Total Posts</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-success"><i class="ti ti-circle-check"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['blogs_published'] }}</div>
                <div class="stat-label">Published</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-danger"><i class="ti ti-file-pencil"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['blogs_draft'] }}</div>
                <div class="stat-label">Draft</div>
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
                            <input id="blogSearch" type="search" class="form-control"
                                placeholder="Search posts..." />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1">
                        <div class="app-search">
                            <select id="blogStatusFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All Status</option>
                                <option value="1">Published</option>
                                <option value="0">Draft</option>
                            </select>
                            <i class="ti ti-circle app-search-icon text-muted"></i>
                        </div>

                        @permission('blogs.create')
                        <a href="{{ route('blogs.create') }}" class="add-btn ms-1">
                            <i class="ti ti-plus fs-sm me-2"></i> Add Post
                        </a>
                        @endpermission
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table id="blogsTable" class="table table-custom table-centered table-select table-hover w-100 mb-0">
                        <thead class="align-middle thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="text-center" style="width: 1%;">S.No</th>
                                <th><i class="ti ti-article me-1"></i>Post</th>
                                <th class="text-center"><i class="ti ti-toggle-right me-1"></i>Status</th>
                                <th><i class="ti ti-calendar-time me-1"></i>Published</th>
                                <th><i class="ti ti-calendar-plus me-1"></i>Created</th>
                                <th class="text-center" style="width: 1%;"><i class="ti ti-settings me-1"></i>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                {{-- Card footer: DataTables info + pagination get moved here --}}
                <div class="card-footer border-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div id="blogsInfoSlot" class="text-muted small"></div>
                        <div class="d-flex align-items-center gap-2 footer-pagination-group">
                            <select id="blogPerPage" class="form-select form-select-sm" style="width: auto;">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                            <div id="blogsPaginationSlot"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== Delete Confirmation Modal ==================== --}}
    <div class="modal fade" id="deleteBlogModal" tabindex="-1" aria-labelledby="deleteBlogModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="delete-modal-icon mx-auto mb-3">
                        <i class="ti ti-trash"></i>
                    </div>
                    <h5 class="modal-title mb-2" id="deleteBlogModalLabel">Delete this post?</h5>
                    <p class="text-muted mb-0">
                        Are you sure you want to delete <strong id="deleteBlogName"></strong>?
                        This is a soft delete and can be restored later if needed.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBlogBtn">
                        <i class="ti ti-trash me-1"></i>Delete Post
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
       Blog page — compact ERP styling
       Scoped entirely under .blogs-page so nothing here leaks
       into other modules that share the same layout/theme classes.
       ========================================================== */
    .blogs-page {
        --blogs-primary: #1d4ed8;
        --blogs-primary-dark: #1e3a8a;
        --blogs-cyan: #14b8a6;
        --blogs-success: #059669;
        --blogs-warning: #d97706;
        --blogs-danger: #dc2626;
        --blogs-bg: #f8fafc;
        --blogs-surface: #ffffff;
        --blogs-border: #e2e8f0;
        --blogs-text: #1e293b;
        --blogs-text-muted: #64748b;
        padding-top: 0;
        padding-bottom: 20px;
    }

    /* ---------- Page header ---------- */
    .blogs-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid var(--blogs-border);
    }
    .blogs-page .page-title-head > * { display: flex; align-items: center; }
    .blogs-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        color: var(--blogs-text);
        position: relative;
        padding-left: 12px;
    }
    .blogs-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 2px;
        bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, var(--blogs-primary), var(--blogs-cyan));
    }
    .blogs-page .breadcrumb { font-size: 0.75rem; }

    /* ---------- Summary cards ---------- */
    .blogs-page .stat-cards-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 16px;
    }
    .blogs-page .stat-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--blogs-surface);
        border: 1px solid var(--blogs-border);
        border-radius: 10px;
        padding: 14px 16px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .blogs-page .stat-icon {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    .blogs-page .stat-icon-primary { background: #eff6ff; color: var(--blogs-primary); }
    .blogs-page .stat-icon-success { background: #ecfdf5; color: var(--blogs-success); }
    .blogs-page .stat-icon-danger  { background: #fef2f2; color: var(--blogs-danger); }
    .blogs-page .stat-value { font-size: 1.375rem; font-weight: 700; line-height: 1.2; color: var(--blogs-text); }
    .blogs-page .stat-label { font-size: 0.75rem; color: var(--blogs-text-muted); font-weight: 500; }

    @media (max-width: 992px) {
        .blogs-page .stat-cards-row { grid-template-columns: repeat(2, 1fr); }
    }

    /* ---------- Card ---------- */
    .blogs-page .card {
        border: 1px solid var(--blogs-border);
        border-radius: 10px;
        box-shadow: none;
    }
    .blogs-page .card-header {
        padding: 12px 16px;
        background: var(--blogs-surface);
    }
    .blogs-page .card-footer { padding: 10px 16px; }

    /* Tighten the app-search wrapper so the dropdowns look right in the card header */
    .blogs-page .app-search { position: relative; }
    .blogs-page .app-search > .app-search-icon {
        position: absolute; right: 0.75rem; top: 50%;
        transform: translateY(-50%); pointer-events: none;
    }
    .blogs-page .app-search > .form-control { padding-right: 2.25rem; min-width: 180px; }
    .blogs-page .card-header .form-control,
    .blogs-page .card-header .form-select {
        height: 38px;
        font-size: 0.8125rem;
        border-color: var(--blogs-border);
    }

    /* Primary "Add Post" button */
    .blogs-page .add-btn {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, var(--blogs-primary-dark), var(--blogs-primary));
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 9px 18px;
        font-weight: 600;
        font-size: 0.8125rem;
        text-decoration: none;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    .blogs-page .add-btn:hover {
        color: #fff;
        box-shadow: 0 4px 10px rgba(29, 78, 216, 0.25);
        transform: translateY(-1px);
    }

    /* ---------- Table ---------- */
    .blogs-page #blogsTable thead th {
        background: #f1f5f9;
        color: var(--blogs-text);
        font-weight: 700;
        font-size: 0.6875rem;
        letter-spacing: 0.03em;
        padding: 8px 12px;
        border-bottom: 1px solid var(--blogs-border);
    }

    /* Sort arrows are too faint (low opacity) against the light header background — darken them */
    .blogs-page #blogsTable thead th span.dt-column-order:before,
    .blogs-page #blogsTable thead th span.dt-column-order:after {
        color: #475569;
    }
    .blogs-page #blogsTable thead th span.dt-column-order:before { opacity: .45; }
    .blogs-page #blogsTable thead th span.dt-column-order:after { opacity: .9; }
    .blogs-page #blogsTable thead th.dt-ordering-asc span.dt-column-order:before,
    .blogs-page #blogsTable thead th.dt-ordering-desc span.dt-column-order:after {
        color: var(--blogs-primary);
        opacity: 1;
    }
    .blogs-page #blogsTable tbody td {
        padding: 6px 12px;
        vertical-align: middle;
        border-bottom: 1px solid var(--blogs-border);
        font-size: 0.8125rem;
    }
    .blogs-page #blogsTable tbody tr {
        transition: background 0.2s ease;
    }
    .blogs-page #blogsTable tbody tr:hover {
        background: #f8fafc;
    }

    /* Post title + thumbnail */
    .blogs-page .blog-title-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .blogs-page .blog-thumb {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
        border: 1px solid var(--blogs-border);
    }
    .blogs-page .blog-thumb-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .blogs-page .blog-thumb-placeholder {
        width: 100%;
        height: 100%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f1f5f9;
        color: var(--blogs-text-muted);
    }
    .blogs-page .blog-title-link {
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--blogs-text);
        text-decoration: none;
    }
    .blogs-page .blog-title-link:hover { color: var(--blogs-primary); }
    .blogs-page .blog-slug-text {
        font-size: 0.75rem;
        color: var(--blogs-text-muted);
    }

    /* Status pill */
    .blogs-page .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .blogs-page .status-pill .status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        display: inline-block;
    }
    .blogs-page .status-active { background: #ecfdf5; color: var(--blogs-success); }
    .blogs-page .status-active .status-dot { background: var(--blogs-success); }
    .blogs-page .status-inactive { background: #fef2f2; color: var(--blogs-danger); }
    .blogs-page .status-inactive .status-dot { background: var(--blogs-danger); }

    /* Action buttons */
    .blogs-page .action-btn {
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
    .blogs-page .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }
    .blogs-page .action-view {
        color: #2563eb;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
    }
    .blogs-page .action-edit {
        color: #059669;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
    }
    .blogs-page .action-toggle {
        color: #d97706;
        background: #fffbeb;
        border: 1px solid #fde68a;
    }
    .blogs-page .action-delete {
        color: #dc2626;
        background: #fef2f2;
        border: 1px solid #fecaca;
    }

    /* DataTables "processing" loading indicator */
    .blogs-page .dataTables_processing {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    .blogs-page .blogs-loading {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--blogs-surface);
        border: 1px solid var(--blogs-border);
        padding: 8px 18px;
        border-radius: 999px;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--blogs-text);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.1);
    }
    .blogs-page .blogs-loading .spinner-border {
        width: 1rem;
        height: 1rem;
        color: var(--blogs-primary);
        border-width: 0.15em;
    }

    /* Delete confirmation modal */
    .blogs-page .delete-modal-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fef2f2;
        color: var(--blogs-danger);
        font-size: 1.5rem;
    }

    /* Hide DataTables built-in length+filter+info+paginate (we render our own slots) */
    #blogsTable_wrapper .dataTables_length,
    #blogsTable_wrapper .dataTables_filter { display: none !important; }

    /* The cloned DataTables info/pagination land inside our card-footer slots */
    #blogsInfoSlot .dataTables_info { padding: 0; color: var(--bs-body-color); font-size: 0.875rem; }
    #blogsPaginationSlot .pagination { margin-bottom: 0; }
    #blogsPaginationSlot .dataTables_paginate { margin: 0; }

    /* "Showing x to y..." info on the left, pagination on the right — overrides the
       app-wide pagination-left/info-right order, scoped to this page only. */
    .blogs-page .card-footer #blogsInfoSlot { order: 1; }
    .blogs-page .card-footer .footer-pagination-group { order: 2; }
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
            document.getElementById('blogsToastContainer').appendChild(el);
            const toast = new bootstrap.Toast(el, { delay: 3000 });
            el.addEventListener('hidden.bs.toast', () => el.remove());
            toast.show();
        }

        // ============= DataTable =============
        const dt = $('#blogsTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            order: [[4, 'desc']],
            ajax: {
                url: '{{ route('blogs.data') }}',
                type: 'GET',
            },
            dom: 'rt<"datatables-tail"ip>',
            pageLength: 10,
            columns: [
                { data: 'DT_RowIndex',   name: 'DT_RowIndex',        orderable: false, searchable: false, className: 'text-center' },
                { data: 'title',         name: 'blogs.title' },
                { data: 'status_badge',  name: 'blogs.status',       searchable: true, className: 'text-center' },
                { data: 'published_at',  name: 'blogs.published_at' },
                { data: 'created_at',    name: 'blogs.created_at' },
                { data: 'action',        name: 'action', orderable: false, searchable: false, className: 'text-center' },
            ],
            language: {
                info: 'Showing _START_ to _END_ of _TOTAL_ posts',
                infoEmpty: 'No posts found',
                infoFiltered: ' (filtered from _MAX_ total)',
                emptyTable: 'No blog posts yet.',
                zeroRecords: 'No posts match your search.',
                processing: '<div class="blogs-loading"><span class="spinner-border spinner-border-sm"></span>Loading posts&hellip;</div>',
                paginate: {
                    previous: '<i class="ti ti-chevron-left"></i>',
                    next:     '<i class="ti ti-chevron-right"></i>',
                },
            },
            initComplete: function () {
                // Move DataTables-rendered info & pagination into our card-footer slots
                $('#blogsInfoSlot').append($('#blogsTable_info'));
                $('#blogsPaginationSlot').append($('.datatables-tail'));
            },
        });

        // ============= Custom search wire-up (debounced) =============
        let searchTimer;
        $('#blogSearch').on('keyup', function () {
            clearTimeout(searchTimer);
            const v = this.value;
            searchTimer = setTimeout(() => dt.search(v).draw(), 250);
        });

        // Per-page
        $('#blogPerPage').on('change', function () {
            dt.page.len(parseInt(this.value, 10)).draw();
        });

        // Status filter — column index 2 is the status_badge column
        $('#blogStatusFilter').on('change', function () {
            dt.column(2).search(this.value).draw();
        });

        // ============= Toggle Status =============
        $('#blogsTable tbody').on('click', '.js-toggle-status', function () {
            const url = $(this).data('url');
            $.ajax({
                url: url,
                type: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    if (res.success) {
                        dt.ajax.reload(null, false);
                        showToast('success', 'Post marked as ' + (res.label || (res.status ? 'Published' : 'Draft')) + '.');
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
        const deleteModalEl = document.getElementById('deleteBlogModal');
        const deleteModal = new bootstrap.Modal(deleteModalEl);
        let pendingDeleteUrl = null;

        $('#blogsTable tbody').on('click', '.js-delete', function () {
            pendingDeleteUrl = $(this).data('url');
            $('#deleteBlogName').text($(this).data('name'));
            deleteModal.show();
        });

        $('#confirmDeleteBlogBtn').on('click', function () {
            if (!pendingDeleteUrl) return;
            const $btn = $(this).prop('disabled', true);

            $.ajax({
                url: pendingDeleteUrl,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    if (res.success) {
                        dt.ajax.reload(null, false);
                        showToast('success', res.message || 'Post deleted successfully.');
                    } else {
                        showToast('error', res.message || 'Could not delete post.');
                    }
                },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Failed to delete post.';
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
