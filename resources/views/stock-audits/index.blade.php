@extends('layout.app')

@section('title', 'Stock Audits')

@section('content')

<div class="container-fluid stock-audits-page">

    {{-- Page title --}}
    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Stock Audits</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Inventory</a></li>
                <li class="breadcrumb-item active">Stock Audits</li>
            </ol>
        </div>
    </div>

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
            <div class="stat-icon stat-icon-primary"><i class="ti ti-clipboard-list"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['audits_total'] }}</div>
                <div class="stat-label">Total Audits</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-info"><i class="ti ti-scan"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['audits_in_progress'] }}</div>
                <div class="stat-label">In Progress</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-success"><i class="ti ti-circle-check"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['audits_completed'] }}</div>
                <div class="stat-label">Completed</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon-danger"><i class="ti ti-circle-x"></i></div>
            <div class="stat-body">
                <div class="stat-value">{{ $stats['audits_cancelled'] }}</div>
                <div class="stat-label">Cancelled</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                {{-- Card header: search + location filter + status filter + add button --}}
                <div class="card-header border-light justify-content-between">
                    <div class="d-flex gap-2">
                        <div class="app-search">
                            <input id="auditSearch" type="search" class="form-control" placeholder="Search audits…" />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1">
                        <div class="app-search">
                            <select id="auditLocationFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All Locations</option>
                                @foreach ($locations as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="app-search">
                            <select id="auditStatusFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All Status</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <i class="ti ti-circle app-search-icon text-muted"></i>
                        </div>

                        @permission('stock-audits.create')
                        <a href="{{ route('stock-audits.create') }}" class="add-btn ms-1">
                            <i class="ti ti-plus fs-sm me-2"></i> New Audit
                        </a>
                        @endpermission
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table id="auditsTable" class="table table-custom table-centered table-hover w-100 mb-0">
                        <thead class="align-middle thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="text-center" style="width: 1%;">S.No</th>
                                <th><i class="ti ti-barcode me-1"></i>Audit #</th>
                                <th><i class="ti ti-calendar-time me-1"></i>Date</th>
                                <th><i class="ti ti-map-pin me-1"></i>Location</th>
                                <th><i class="ti ti-list-check me-1"></i>Progress</th>
                                <th><i class="ti ti-flag me-1"></i>Status</th>
                                <th class="text-center" style="width: 1%;"><i class="ti ti-settings me-1"></i>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                {{-- Card footer: DataTables info + pagination get moved here --}}
                <div class="card-footer border-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div id="auditsInfoSlot" class="text-muted small"></div>
                        <div id="auditsPaginationSlot"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('styles')
<style>
    /* ==========================================================
       Stock Audits — compact ERP styling
       Scoped entirely under .stock-audits-page so nothing here
       leaks into other modules that share the same layout/theme
       classes. Note: this page lists audits only — audits are
       created via a dedicated form and never edited in place, so
       there is no quick-add modal and no delete/cancel action
       here (cancelling an in-progress audit happens from the
       audit's own show/scan screens, which are out of scope).
       ========================================================== */
    .stock-audits-page {
        --audits-primary: #1d4ed8;
        --audits-primary-dark: #1e3a8a;
        --audits-cyan: #14b8a6;
        --audits-info: #0891b2;
        --audits-success: #059669;
        --audits-warning: #d97706;
        --audits-danger: #dc2626;
        --audits-bg: #f8fafc;
        --audits-surface: #ffffff;
        --audits-border: #e2e8f0;
        --audits-text: #1e293b;
        --audits-text-muted: #64748b;
        padding-top: 0;
        padding-bottom: 20px;
    }

    /* ---------- Page header ---------- */
    .stock-audits-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid var(--audits-border);
    }
    .stock-audits-page .page-title-head > * { display: flex; align-items: center; }
    .stock-audits-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        color: var(--audits-text);
        position: relative;
        padding-left: 12px;
    }
    .stock-audits-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 2px;
        bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, var(--audits-primary), var(--audits-cyan));
    }
    .stock-audits-page .breadcrumb { font-size: 0.75rem; }

    /* ---------- Summary cards ---------- */
    .stock-audits-page .stat-cards-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 16px;
    }
    .stock-audits-page .stat-card {
        display: flex;
        align-items: center;
        gap: 12px;
        background: var(--audits-surface);
        border: 1px solid var(--audits-border);
        border-radius: 10px;
        padding: 14px 16px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .stock-audits-page .stat-icon {
        flex-shrink: 0;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }
    .stock-audits-page .stat-icon-primary { background: #eff6ff; color: var(--audits-primary); }
    .stock-audits-page .stat-icon-info    { background: #ecfeff; color: var(--audits-info); }
    .stock-audits-page .stat-icon-success { background: #ecfdf5; color: var(--audits-success); }
    .stock-audits-page .stat-icon-danger  { background: #fef2f2; color: var(--audits-danger); }
    .stock-audits-page .stat-value { font-size: 1.375rem; font-weight: 700; line-height: 1.2; color: var(--audits-text); }
    .stock-audits-page .stat-label { font-size: 0.75rem; color: var(--audits-text-muted); font-weight: 500; }

    @media (max-width: 992px) {
        .stock-audits-page .stat-cards-row { grid-template-columns: repeat(2, 1fr); }
    }

    /* ---------- Card ---------- */
    .stock-audits-page .card {
        border: 1px solid var(--audits-border);
        border-radius: 10px;
        box-shadow: none;
    }
    .stock-audits-page .card-header {
        padding: 12px 16px;
        background: var(--audits-surface);
    }
    .stock-audits-page .card-footer { padding: 10px 16px; }

    /* Tighten the app-search wrapper so the dropdowns look right in the card header */
    .stock-audits-page .app-search { position: relative; }
    .stock-audits-page .app-search > .app-search-icon {
        position: absolute; right: 0.75rem; top: 50%;
        transform: translateY(-50%); pointer-events: none;
    }
    .stock-audits-page .app-search > .form-control { padding-right: 2.25rem; min-width: 180px; }
    .stock-audits-page .card-header .form-control,
    .stock-audits-page .card-header .form-select {
        height: 38px;
        font-size: 0.8125rem;
        border-color: var(--audits-border);
    }

    /* Primary "New Audit" button */
    .stock-audits-page .add-btn {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(135deg, var(--audits-primary-dark), var(--audits-primary));
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 9px 18px;
        font-weight: 600;
        font-size: 0.8125rem;
        text-decoration: none;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }
    .stock-audits-page .add-btn:hover {
        color: #fff;
        box-shadow: 0 4px 10px rgba(29, 78, 216, 0.25);
        transform: translateY(-1px);
    }

    /* ---------- Table ---------- */
    .stock-audits-page #auditsTable thead th {
        background: #f1f5f9;
        color: var(--audits-text);
        font-weight: 700;
        font-size: 0.6875rem;
        letter-spacing: 0.03em;
        padding: 8px 12px;
        border-bottom: 1px solid var(--audits-border);
    }

    /* Sort arrows are too faint (low opacity) against the light header background — darken them */
    .stock-audits-page #auditsTable thead th span.dt-column-order:before,
    .stock-audits-page #auditsTable thead th span.dt-column-order:after {
        color: #475569;
    }
    .stock-audits-page #auditsTable thead th span.dt-column-order:before { opacity: .45; }
    .stock-audits-page #auditsTable thead th span.dt-column-order:after { opacity: .9; }
    .stock-audits-page #auditsTable thead th.dt-ordering-asc span.dt-column-order:before,
    .stock-audits-page #auditsTable thead th.dt-ordering-desc span.dt-column-order:after {
        color: var(--audits-primary);
        opacity: 1;
    }
    .stock-audits-page #auditsTable tbody td {
        padding: 6px 12px;
        vertical-align: middle;
        border-bottom: 1px solid var(--audits-border);
        font-size: 0.8125rem;
    }
    .stock-audits-page #auditsTable tbody tr {
        transition: background 0.2s ease;
    }
    .stock-audits-page #auditsTable tbody tr:hover {
        background: #f8fafc;
    }

    /* Status pill (3-stage lifecycle: in_progress / completed / cancelled) */
    .stock-audits-page .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .stock-audits-page .status-pill .status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        display: inline-block;
    }
    .stock-audits-page .status-in-progress { background: #ecfeff; color: var(--audits-info); }
    .stock-audits-page .status-in-progress .status-dot { background: var(--audits-info); }
    .stock-audits-page .status-completed { background: #ecfdf5; color: var(--audits-success); }
    .stock-audits-page .status-completed .status-dot { background: var(--audits-success); }
    .stock-audits-page .status-cancelled { background: #fef2f2; color: var(--audits-danger); }
    .stock-audits-page .status-cancelled .status-dot { background: var(--audits-danger); }

    /* Action buttons */
    .stock-audits-page .action-btn {
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
    .stock-audits-page .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }
    .stock-audits-page .action-view {
        color: #2563eb;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
    }
    /* Continue Scanning — distinct from view/edit/delete's blue/green/red trio */
    .stock-audits-page .action-scan {
        color: #7c3aed;
        background: #f5f3ff;
        border: 1px solid #ddd6fe;
    }

    /* DataTables "processing" loading indicator */
    .stock-audits-page .dataTables_processing {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    .stock-audits-page .audits-loading {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--audits-surface);
        border: 1px solid var(--audits-border);
        padding: 8px 18px;
        border-radius: 999px;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--audits-text);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.1);
    }
    .stock-audits-page .audits-loading .spinner-border {
        width: 1rem;
        height: 1rem;
        color: var(--audits-primary);
        border-width: 0.15em;
    }

    /* Hide DataTables built-in length+filter+info+paginate (we render our own slots) */
    #auditsTable_wrapper .dataTables_length,
    #auditsTable_wrapper .dataTables_filter { display: none !important; }

    /* The cloned DataTables info/pagination land inside our card-footer slots */
    #auditsInfoSlot .dataTables_info { padding: 0; color: var(--bs-body-color); font-size: 0.875rem; }
    #auditsPaginationSlot .pagination { margin-bottom: 0; }
    #auditsPaginationSlot .dataTables_paginate { margin: 0; }

    /* "Showing x to y..." info on the left, pagination on the right — overrides the
       app-wide pagination-left/info-right order, scoped to this page only. */
    .stock-audits-page .card-footer #auditsInfoSlot { order: 1; }
    .stock-audits-page .card-footer #auditsPaginationSlot { order: 2; }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const dt = $('#auditsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: false,
        order: [[2, 'desc']],
        ajax: {
            url: '{{ route('stock-audits.data') }}',
            data: function (d) {
                d.status = $('#auditStatusFilter').val();
                d.location_id = $('#auditLocationFilter').val();
            },
        },
        dom: 'rt<"datatables-tail"ip>',
        columns: [
            { data: 'DT_RowIndex',      name: 'DT_RowIndex',                 orderable: false, searchable: false, className: 'text-center' },
            { data: 'audit_number',     name: 'stock_audits.audit_number' },
            { data: 'audit_date',       name: 'stock_audits.audit_date' },
            { data: 'location_label',   name: 'location_label', orderable: false, searchable: false },
            { data: 'progress_label',   name: 'progress_label', orderable: false, searchable: false },
            { data: 'status_badge',     name: 'stock_audits.status', orderable: false, searchable: false },
            { data: 'actions',          name: 'actions',        orderable: false, searchable: false, className: 'text-center' },
        ],
        language: {
            info: 'Showing _START_ to _END_ of _TOTAL_ audits',
            emptyTable: 'No stock audits yet.',
            zeroRecords: 'No audits match your search.',
            processing: '<div class="audits-loading"><span class="spinner-border spinner-border-sm"></span>Loading audits&hellip;</div>',
            paginate: { previous: '<i class="ti ti-chevron-left"></i>', next: '<i class="ti ti-chevron-right"></i>' },
        },
        initComplete: function () {
            $('#auditsInfoSlot').append($('#auditsTable_info'));
            $('#auditsPaginationSlot').append($('.datatables-tail'));
        },
    });

    let timer;
    $('#auditSearch').on('keyup', function () {
        clearTimeout(timer);
        const v = this.value;
        timer = setTimeout(() => dt.search(v).draw(), 250);
    });
    $('#auditStatusFilter, #auditLocationFilter').on('change', () => dt.draw());
});
</script>
@endpush
