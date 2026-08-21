@extends('layout.app')

@section('title', 'Packings')

@section('content')
<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Packings</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Inventory</a></li>
                <li class="breadcrumb-item active">Packings</li>
            </ol>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header border-light justify-content-between d-flex flex-wrap gap-2">
            <div class="d-flex gap-2 flex-wrap">
                <div class="app-search">
                    <input id="packingSearch" type="search" class="form-control" placeholder="Search packings...">
                    <i class="ti ti-search app-search-icon text-muted"></i>
                </div>
                <select id="statusFilter" class="form-select" style="width:auto">
                    <option value="">All statuses</option>
                    <option value="draft">Draft</option>
                    <option value="posted">Posted</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div>
                @permission('packings.create')
                <a href="{{ route('packings.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> New Packing
                </a>
                @endpermission
            </div>
        </div>

        <div class="table-responsive">
            <table id="packingsTable" class="table table-custom table-centered table-hover w-100 mb-0">
                <thead class="bg-light bg-opacity-25 thead-sm">
                    <tr class="text-uppercase fs-xxs">
                        <th class="text-center" style="width: 1%;">S.No</th>
                        <th>Packing #</th>
                        <th>Date</th>
                        <th>Location</th>
                        <th class="text-center">Outputs</th>
                        <th>Status</th>
                        <th class="text-center" style="width: 1%;">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const table = $('#packingsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('packings.data') }}",
            data: function (d) {
                d.status = $('#statusFilter').val();
            },
        },
        columns: [
            { data: 'DT_RowIndex',      name: 'DT_RowIndex',     orderable: false, searchable: false, className: 'text-center' },
            { data: 'packing_number',   name: 'packing_number' },
            { data: 'packing_date',     name: 'packing_date' },
            { data: 'location_label',   name: 'location_id', orderable: false, searchable: false },
            { data: 'output_count',     name: 'output_count', orderable: false, searchable: false, className: 'text-center' },
            { data: 'status_badge',     name: 'status' },
            { data: 'actions',          orderable: false, searchable: false, className: 'text-center' },
        ],
        order: [[2, 'desc']],
    });

    $('#packingSearch').on('keyup', function () { table.search(this.value).draw(); });
    $('#statusFilter').on('change', function () { table.draw(); });

    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    $(document).on('click', '.js-status-action', function () {
        const url     = this.dataset.url;
        const confirm = this.dataset.confirm;
        if (confirm && !window.confirm(confirm)) return;
        fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(res => {
            if (res.ok) { table.draw(false); }
            else { alert(res.message || 'Action failed.'); }
        })
        .catch(() => alert('A network error occurred.'));
    });
})();
</script>
@endpush
@endsection
