@extends('layout.app')

@section('title', 'Roles')

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Roles</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Paces</a></li>
                <li class="breadcrumb-item"><a href="#">Administration</a></li>
                <li class="breadcrumb-item active">Roles</li>
            </ol>
        </div>
    </div>

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

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-light justify-content-between">
                    <div class="d-flex gap-2">
                        <div class="app-search">
                            <input id="roleSearch" type="search" class="form-control"
                                placeholder="Search roles..." />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1">
                        <div>
                            <select id="rolePerPage" class="form-select form-control my-1 my-md-0">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="15">15</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                            </select>
                        </div>

                        @permission('roles.create')
                        <a href="{{ route('roles.create') }}" class="btn btn-primary ms-1">
                            <i class="ti ti-plus fs-sm me-2"></i> Add Role
                        </a>
                        @endpermission
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="rolesTable" class="table table-custom table-centered table-hover w-100 mb-0">
                        <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3">Role</th>
                                <th>Slug</th>
                                <th>Users</th>
                                <th>Permissions</th>
                                <th>Last Modified</th>
                                <th class="text-center" style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <div class="card-footer border-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div id="rolesInfoSlot" class="text-muted small"></div>
                        <div id="rolesPaginationSlot"></div>
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
    .app-search > .app-search-icon {
        position: absolute; right: 0.75rem; top: 50%;
        transform: translateY(-50%); pointer-events: none;
    }
    .app-search > .form-control { padding-right: 2.25rem; min-width: 180px; }

    #rolesTable_wrapper .dataTables_length,
    #rolesTable_wrapper .dataTables_filter { display: none !important; }

    #rolesInfoSlot .dataTables_info { padding: 0; color: var(--bs-body-color); font-size: 0.875rem; }
    #rolesPaginationSlot .pagination { margin-bottom: 0; }
    #rolesPaginationSlot .dataTables_paginate { margin: 0; }
</style>
@endpush

@push('scripts')
<script>
    $(function () {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        const dt = $('#rolesTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            order: [[0, 'asc']],
            ajax: {
                url: '{{ route('roles.data') }}',
                type: 'GET',
            },
            dom: 'rt<"d-none datatables-tail"ip>',
            pageLength: 10,
            columns: [
                { data: 'name',                    name: 'roles.name',         className: 'ps-3' },
                { data: 'slug',                    name: 'roles.slug' },
                { data: 'users_count_badge',       name: 'users_count',        orderable: false, searchable: false },
                { data: 'permissions_count_badge', name: 'permissions_count',  orderable: false, searchable: false },
                { data: 'updated_at',              name: 'roles.updated_at' },
                { data: 'action',                  name: 'action',             orderable: false, searchable: false, className: 'text-center' },
            ],
            language: {
                info: 'Showing _START_ to _END_ of _TOTAL_ roles',
                infoEmpty: 'No roles found',
                emptyTable: 'No roles yet.',
                zeroRecords: 'No roles match your search.',
                processing: '<div class="spinner-border spinner-border-sm text-primary"></div>',
                paginate: {
                    previous: '<i class="ti ti-chevron-left"></i>',
                    next:     '<i class="ti ti-chevron-right"></i>',
                },
            },
            initComplete: function () {
                $('#rolesInfoSlot').append($('#rolesTable_info'));
                $('#rolesPaginationSlot').append($('#rolesTable_paginate'));
            },
        });

        let searchTimer;
        $('#roleSearch').on('keyup', function () {
            clearTimeout(searchTimer);
            const v = this.value;
            searchTimer = setTimeout(() => dt.search(v).draw(), 250);
        });

        $('#rolePerPage').on('change', function () {
            dt.page.len(parseInt(this.value, 10)).draw();
        });

        $('#rolesTable tbody').on('click', '.js-delete', function () {
            const url  = $(this).data('url');
            const name = $(this).data('name');
            if (!confirm('Delete role "' + name + '"?')) return;
            $.ajax({
                url: url,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    if (res.success) dt.ajax.reload(null, false);
                    else alert(res.message || 'Could not delete role.');
                },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Failed to delete role.';
                    alert(msg);
                },
            });
        });
    });
</script>
@endpush
