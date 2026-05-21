@extends('layout.app')

@section('title', 'Permissions')

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Permissions</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Paces</a></li>
                <li class="breadcrumb-item"><a href="#">Administration</a></li>
                <li class="breadcrumb-item active">Permissions</li>
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
                            <input id="permSearch" type="search" class="form-control"
                                placeholder="Search permissions..." />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1">
                        <div>
                            <select id="permPerPage" class="form-select form-control my-1 my-md-0">
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="15">15</option>
                                <option value="25" selected>25</option>
                                <option value="50">50</option>
                            </select>
                        </div>

                        <div class="app-search">
                            <select id="permModuleFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All Modules</option>
                                @foreach ($modules as $m)
                                    <option value="{{ $m }}">{{ ucfirst($m) }}</option>
                                @endforeach
                            </select>
                            <i class="ti ti-package app-search-icon text-muted"></i>
                        </div>

                        <a href="{{ route('permissions.create') }}" class="btn btn-primary ms-1">
                            <i class="ti ti-plus fs-sm me-2"></i> Add Permission
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="permsTable" class="table table-custom table-centered table-hover w-100 mb-0">
                        <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3">Permission</th>
                                <th>Slug</th>
                                <th>Module</th>
                                <th>Roles</th>
                                <th>Last Modified</th>
                                <th class="text-center" style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <div class="card-footer border-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div id="permsInfoSlot" class="text-muted small"></div>
                        <div id="permsPaginationSlot"></div>
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

    #permsTable_wrapper .dataTables_length,
    #permsTable_wrapper .dataTables_filter { display: none !important; }

    #permsInfoSlot .dataTables_info { padding: 0; color: var(--bs-body-color); font-size: 0.875rem; }
    #permsPaginationSlot .pagination { margin-bottom: 0; }
    #permsPaginationSlot .dataTables_paginate { margin: 0; }
</style>
@endpush

@push('scripts')
<script>
    $(function () {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        const dt = $('#permsTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            order: [[2, 'asc'], [0, 'asc']],
            ajax: {
                url: '{{ route('permissions.data') }}',
                type: 'GET',
            },
            dom: 'rt<"d-none datatables-tail"ip>',
            pageLength: 25,
            columns: [
                { data: 'name',              name: 'permissions.name',       className: 'ps-3' },
                { data: 'slug',              name: 'permissions.slug' },
                { data: 'module',            name: 'permissions.module',     searchable: true },
                { data: 'roles_count_badge', name: 'roles_count',            orderable: false, searchable: false },
                { data: 'updated_at',        name: 'permissions.updated_at' },
                { data: 'action',            name: 'action',                 orderable: false, searchable: false, className: 'text-center' },
            ],
            language: {
                info: 'Showing _START_ to _END_ of _TOTAL_ permissions',
                infoEmpty: 'No permissions found',
                emptyTable: 'No permissions defined yet.',
                zeroRecords: 'No permissions match your search.',
                processing: '<div class="spinner-border spinner-border-sm text-primary"></div>',
                paginate: {
                    previous: '<i class="ti ti-chevron-left"></i>',
                    next:     '<i class="ti ti-chevron-right"></i>',
                },
            },
            initComplete: function () {
                $('#permsInfoSlot').append($('#permsTable_info'));
                $('#permsPaginationSlot').append($('#permsTable_paginate'));
            },
        });

        let searchTimer;
        $('#permSearch').on('keyup', function () {
            clearTimeout(searchTimer);
            const v = this.value;
            searchTimer = setTimeout(() => dt.search(v).draw(), 250);
        });

        $('#permPerPage').on('change', function () {
            dt.page.len(parseInt(this.value, 10)).draw();
        });

        $('#permModuleFilter').on('change', function () {
            dt.column(2).search(this.value).draw();
        });

        $('#permsTable tbody').on('click', '.js-delete', function () {
            const url  = $(this).data('url');
            const name = $(this).data('name');
            if (!confirm('Delete permission "' + name + '"? This will detach it from any roles using it. (This is a soft delete.)')) return;
            $.ajax({
                url: url,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    if (res.success) dt.ajax.reload(null, false);
                    else alert(res.message || 'Could not delete permission.');
                },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Failed to delete permission.';
                    alert(msg);
                },
            });
        });
    });
</script>
@endpush
