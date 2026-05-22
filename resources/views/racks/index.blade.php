@extends('layout.app')

@section('title', 'Racks')

@section('content')
<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Racks</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Procurement</a></li>
                <li class="breadcrumb-item active">Racks</li>
            </ol>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header border-light justify-content-between">
            <div class="app-search">
                <input id="rackSearch" type="search" class="form-control" placeholder="Search racks...">
                <i class="ti ti-search app-search-icon text-muted"></i>
            </div>
            <div>
                @permission('racks.create')
                <a href="{{ route('racks.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> Add Rack
                </a>
                @endpermission
            </div>
        </div>

        <div class="table-responsive">
            <table id="racksTable" class="table table-custom table-centered table-hover w-100 mb-0">
                <thead class="bg-light bg-opacity-25 thead-sm">
                    <tr class="text-uppercase fs-xxs">
                        <th>Code</th>
                        <th>Name</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Created</th>
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
    const table = $('#racksTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: { url: "{{ route('racks.data') }}" },
        columns: [
            { data: 'code',          name: 'code' },
            { data: 'name',          name: 'name' },
            { data: 'location',      name: 'location' },
            { data: 'status_badge',  name: 'status' },
            { data: 'created_at',    name: 'created_at' },
            { data: 'actions',       orderable: false, searchable: false, className: 'text-center' },
        ],
        order: [[0, 'asc']],
    });

    $('#rackSearch').on('keyup', function () { table.search(this.value).draw(); });

    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    $(document).on('click', '.js-toggle-rack', function () {
        fetch(`/racks/${this.dataset.id}/toggle-status`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        }).then(() => table.draw(false));
    });

    $(document).on('click', '.js-delete-rack', function () {
        if (!confirm('Delete this rack?')) return;
        fetch(`/racks/${this.dataset.id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        }).then(() => table.draw(false));
    });
})();
</script>
@endpush
@endsection
