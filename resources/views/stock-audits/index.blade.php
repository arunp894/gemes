@extends('layout.app')

@section('title', 'Stock Audits')

@section('content')

<div class="container-fluid">

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

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
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
                        <a href="{{ route('stock-audits.create') }}" class="btn btn-primary ms-1">
                            <i class="ti ti-plus fs-sm me-2"></i> New Audit
                        </a>
                        @endpermission
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="auditsTable" class="table table-custom table-centered table-hover w-100 mb-0">
                        <thead class="bg-light bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="text-center" style="width: 1%;">S.No</th>
                                <th>Audit #</th>
                                <th>Date</th>
                                <th>Location</th>
                                <th>Progress</th>
                                <th>Status</th>
                                <th class="text-center" style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

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
    .app-search { position: relative; }
    .app-search > .app-search-icon { position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); pointer-events: none; }
    .app-search > .form-control { padding-right: 2.25rem; min-width: 200px; }
    #auditsTable_wrapper .dataTables_length, #auditsTable_wrapper .dataTables_filter { display: none !important; }
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
            processing: '<div class="spinner-border spinner-border-sm text-primary"></div>',
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
