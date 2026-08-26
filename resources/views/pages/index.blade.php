@extends('layout.app')

@section('title', 'Pages')

@section('content')

<div class="container-fluid pages-page">

    {{-- Page title --}}
    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Pages</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">Settings</a></li>
                <li class="breadcrumb-item active">Pages</li>
            </ol>
        </div>
    </div>

    {{-- Toast notifications for AJAX actions (delete) --}}
    <div class="toast-container position-fixed top-0 end-0 p-3" id="pagesToastContainer" style="z-index: 1080;"></div>

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

    {{-- Summary cards (Page has no status field — genuinely-computed metrics instead) --}}
    <div class="stat-cards-row">
        <div class="stat-card">
            <div class="stat-icon stat-icon-primary"><i class="ti ti-file-text"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['pages_total'] }}</div>
                <div class="stat-label">Total Pages</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-success"><i class="ti ti-history"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['pages_recent'] }}</div>
                <div class="stat-label">Updated (7 days)</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-cyan"><i class="ti ti-tags"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['pages_with_seo'] }}</div>
                <div class="stat-label">With SEO Meta</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                {{-- Card header: description + search + add button --}}
                <div class="card-header border-light justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <div class="app-search">
                            <input id="pageSearch" type="search" class="form-control"
                                placeholder="Search pages..." />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                        <p class="text-muted fs-xs mb-0 d-none d-lg-block">
                            Rendered on the storefront at <code>/pages/&#123;slug&#125;</code>.
                        </p>
                    </div>

                    <div class="d-flex align-items-center gap-1">
                        @permission('pages.create')
                        <a href="{{ route('pages.create') }}" class="add-btn ms-1">
                            <i class="ti ti-plus fs-sm me-2"></i> Add Page
                        </a>
                        @endpermission
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table id="pagesTable" class="table table-custom table-centered table-hover w-100 mb-0">
                        <thead class="align-middle thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th><i class="ti ti-file-text me-1"></i>Title</th>
                                <th><i class="ti ti-link me-1"></i>Slug</th>
                                <th><i class="ti ti-calendar-time me-1"></i>Last Modified</th>
                                <th class="text-center" style="width: 1%;"><i class="ti ti-settings me-1"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pages as $page)
                                <tr data-search="{{ strtolower($page->title . ' ' . $page->slug) }}">
                                    <td><span class="page-name-link">{{ $page->title }}</span></td>
                                    <td><code class="text-muted">/pages/{{ $page->slug }}</code></td>
                                    <td>{{ optional($page->updated_at)->format('d M, Y') ?? '—' }}</td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="{{ route('website.pages.show', $page) }}" target="_blank"
                                                class="action-btn action-view" title="View on Site">
                                                <i class="ti ti-external-link"></i>
                                            </a>
                                            @permission('pages.edit')
                                            <a href="{{ route('pages.edit', $page) }}" class="action-btn action-edit" title="Edit">
                                                <i class="ti ti-edit"></i>
                                            </a>
                                            @endpermission
                                            @permission('pages.delete')
                                            <button type="button" class="action-btn action-delete js-delete"
                                                data-url="{{ route('pages.destroy', $page) }}" data-name="{{ e($page->title) }}" title="Delete">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                            @endpermission
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No pages yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div id="pagesNoResults" class="text-center text-muted py-4" style="display:none;">
                        No pages match your search.
                    </div>
                </div>

                {{-- Card footer: record count --}}
                <div class="card-footer border-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div id="pagesInfoSlot" class="text-muted small">
                            {{ $pages->count() }} {{ Str::plural('page', $pages->count()) }} total
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== Delete Confirmation Modal ==================== --}}
    <div class="modal fade" id="deletePageModal" tabindex="-1" aria-labelledby="deletePageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="delete-modal-icon mx-auto mb-3">
                        <i class="ti ti-trash"></i>
                    </div>
                    <h5 class="modal-title mb-2" id="deletePageModalLabel">Delete this page?</h5>
                    <p class="text-muted mb-0">
                        Are you sure you want to delete <strong id="deletePageName"></strong>?
                        This is a soft delete and can be restored later if needed.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeletePageBtn">
                        <i class="ti ti-trash me-1"></i>Delete Page
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
       Pages page — compact ERP styling
       Scoped entirely under .pages-page so nothing here leaks
       into other modules that share the same layout/theme classes.
       ========================================================== */
    .pages-page {
        --pages-primary: #1d4ed8;
        --pages-primary-dark: #1e3a8a;
        --pages-cyan: #14b8a6;
        --pages-success: #059669;
        --pages-warning: #d97706;
        --pages-danger: #dc2626;
        --pages-bg: #f8fafc;
        --pages-surface: #ffffff;
        --pages-border: #e2e8f0;
        --pages-text: #1e293b;
        --pages-text-muted: #64748b;
        padding-top: 0;
        padding-bottom: 20px;
    }

    /* ---------- Page header ---------- */
    .pages-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid var(--pages-border);
    }
    .pages-page .page-title-head > * { display: flex; align-items: center; }
    .pages-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        color: var(--pages-text);
        position: relative;
        padding-left: 12px;
    }
    .pages-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 2px;
        bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, var(--pages-primary), var(--pages-cyan));
    }
    .pages-page .breadcrumb { font-size: 0.75rem; }

    /* ---------- Summary cards ---------- */
    .pages-page .stat-cards-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 16px;
    }
    .pages-page .stat-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--pages-surface);
        border: 1px solid var(--pages-border);
        border-radius: 10px;
        padding: 14px 16px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .pages-page .stat-icon {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    .pages-page .stat-icon-primary { background: #eff6ff; color: var(--pages-primary); }
    .pages-page .stat-icon-success { background: #ecfdf5; color: var(--pages-success); }
    .pages-page .stat-icon-cyan    { background: #ecfeff; color: var(--pages-cyan); }
    .pages-page .stat-value { font-size: 1.375rem; font-weight: 700; line-height: 1.2; color: var(--pages-text); }
    .pages-page .stat-label { font-size: 0.75rem; color: var(--pages-text-muted); font-weight: 500; }

    @media (max-width: 992px) {
        .pages-page .stat-cards-row { grid-template-columns: repeat(2, 1fr); }
    }

    /* ---------- Card ---------- */
    .pages-page .card {
        border: 1px solid var(--pages-border);
        border-radius: 10px;
        box-shadow: none;
    }
    .pages-page .card-header {
        padding: 12px 16px;
        background: var(--pages-surface);
    }
    .pages-page .card-footer { padding: 10px 16px; }

    /* Tighten the app-search wrapper so it looks right in the card header */
    .pages-page .app-search { position: relative; }
    .pages-page .app-search > .app-search-icon {
        position: absolute; right: 0.75rem; top: 50%;
        transform: translateY(-50%); pointer-events: none;
    }
    .pages-page .app-search > .form-control { padding-right: 2.25rem; min-width: 200px; }
    .pages-page .card-header .form-control,
    .pages-page .card-header .form-select {
        height: 38px;
        font-size: 0.8125rem;
        border-color: var(--pages-border);
    }

    /* Primary "Add Page" button */
    .pages-page .add-btn {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, var(--pages-primary-dark), var(--pages-primary));
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 9px 18px;
        font-weight: 600;
        font-size: 0.8125rem;
        text-decoration: none;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    .pages-page .add-btn:hover {
        color: #fff;
        box-shadow: 0 4px 10px rgba(29, 78, 216, 0.25);
        transform: translateY(-1px);
    }

    /* ---------- Table ---------- */
    .pages-page #pagesTable thead th {
        background: #f1f5f9;
        color: var(--pages-text);
        font-weight: 700;
        font-size: 0.6875rem;
        letter-spacing: 0.03em;
        padding: 8px 12px;
        border-bottom: 1px solid var(--pages-border);
    }
    .pages-page #pagesTable tbody td {
        padding: 6px 12px;
        vertical-align: middle;
        border-bottom: 1px solid var(--pages-border);
        font-size: 0.8125rem;
    }
    .pages-page #pagesTable tbody tr {
        transition: background 0.2s ease;
    }
    .pages-page #pagesTable tbody tr:hover {
        background: #f8fafc;
    }

    /* Page title cell */
    .pages-page .page-name-link {
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--pages-text);
    }

    /* Action buttons */
    .pages-page .action-btn {
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
    .pages-page .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }
    .pages-page .action-view {
        color: #2563eb;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
    }
    .pages-page .action-edit {
        color: #059669;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
    }
    .pages-page .action-delete {
        color: #dc2626;
        background: #fef2f2;
        border: 1px solid #fecaca;
    }

    /* Delete confirmation modal */
    .pages-page .delete-modal-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fef2f2;
        color: var(--pages-danger);
        font-size: 1.5rem;
    }
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
            document.getElementById('pagesToastContainer').appendChild(el);
            const toast = new bootstrap.Toast(el, { delay: 3000 });
            el.addEventListener('hidden.bs.toast', () => el.remove());
            toast.show();
        }

        // ============= Client-side search (plain server-rendered table, no DataTables) =============
        const $rows = $('#pagesTable tbody tr[data-search]');
        $('#pageSearch').on('keyup', function () {
            const v = this.value.trim().toLowerCase();
            let visible = 0;
            $rows.each(function () {
                const match = $(this).data('search').toString().indexOf(v) !== -1;
                $(this).toggle(match);
                if (match) visible++;
            });
            $('#pagesNoResults').toggle($rows.length > 0 && visible === 0);
        });

        // ============= Delete (styled confirmation modal) =============
        const deleteModalEl = document.getElementById('deletePageModal');
        const deleteModal = new bootstrap.Modal(deleteModalEl);
        let pendingDeleteUrl = null;
        let pendingDeleteRow = null;

        $('#pagesTable tbody').on('click', '.js-delete', function () {
            pendingDeleteUrl = $(this).data('url');
            pendingDeleteRow = $(this).closest('tr');
            $('#deletePageName').text($(this).data('name'));
            deleteModal.show();
        });

        $('#confirmDeletePageBtn').on('click', function () {
            if (!pendingDeleteUrl) return;
            const $btn = $(this).prop('disabled', true);

            $.ajax({
                url: pendingDeleteUrl,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    if (res.success) {
                        if (pendingDeleteRow) pendingDeleteRow.remove();
                        showToast('success', res.message || 'Page deleted successfully.');
                    } else {
                        showToast('error', res.message || 'Could not delete page.');
                    }
                },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Failed to delete page.';
                    showToast('error', msg);
                },
                complete: function () {
                    $btn.prop('disabled', false);
                    pendingDeleteUrl = null;
                    pendingDeleteRow = null;
                    deleteModal.hide();
                },
            });
        });
    });
</script>
@endpush
