@extends('layout.app')

@section('title', 'Blog')

@section('content')

<div class="container-fluid">

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
                            <input id="blogSearch" type="search" class="form-control"
                                placeholder="Search posts..." />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1">
                        <div>
                            <select id="blogPerPage" class="form-select form-control my-1 my-md-0">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>

                        <div class="app-search">
                            <select id="blogStatusFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All Status</option>
                                <option value="1">Published</option>
                                <option value="0">Draft</option>
                            </select>
                            <i class="ti ti-circle app-search-icon text-muted"></i>
                        </div>

                        @permission('blogs.create')
                        <a href="{{ route('blogs.create') }}" class="btn btn-primary ms-1">
                            <i class="ti ti-plus fs-sm me-2"></i> Add Post
                        </a>
                        @endpermission
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="blogsTable" class="table table-striped dt-responsive align-middle mb-0">
                        <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="text-center" style="width: 1%;">S.No</th>
                                <th>Post</th>
                                <th class="text-center">Status</th>
                                <th>Published</th>
                                <th>Created</th>
                                <th class="text-center" style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <div class="card-footer border-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div id="blogsInfoSlot" class="text-muted small"></div>
                        <div id="blogsPaginationSlot"></div>
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
    .app-search > .form-control { padding-right: 2.25rem; min-width: 160px; }

    #blogsTable_wrapper .dataTables_length,
    #blogsTable_wrapper .dataTables_filter { display: none !important; }

    #blogsInfoSlot .dataTables_info { padding: 0; color: var(--bs-body-color); font-size: 0.875rem; }
    #blogsPaginationSlot .pagination { margin-bottom: 0; }
    #blogsPaginationSlot .dataTables_paginate { margin: 0; }
</style>
@endpush

@push('scripts')
<script>
    $(function () {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        const dt = $('#blogsTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            order: [[4, 'desc']],
            ajax: {
                url: '{{ route('blogs.data') }}',
                type: 'GET',
            },
            dom: 'rt<"d-none datatables-tail"ip>',
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
                processing: '<div class="spinner-border spinner-border-sm text-primary"></div>',
                paginate: {
                    previous: '<i class="ti ti-chevron-left"></i>',
                    next:     '<i class="ti ti-chevron-right"></i>',
                },
            },
            initComplete: function () {
                $('#blogsInfoSlot').append($('#blogsTable_info'));
                $('#blogsPaginationSlot').append($('#blogsTable_paginate'));
            },
        });

        let searchTimer;
        $('#blogSearch').on('keyup', function () {
            clearTimeout(searchTimer);
            const v = this.value;
            searchTimer = setTimeout(() => dt.search(v).draw(), 250);
        });

        $('#blogPerPage').on('change', function () {
            dt.page.len(parseInt(this.value, 10)).draw();
        });

        $('#blogStatusFilter').on('change', function () {
            dt.column(2).search(this.value).draw();
        });

        $('#blogsTable tbody').on('click', '.js-toggle-status', function () {
            const url = $(this).data('url');
            $.ajax({
                url: url,
                type: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) { if (res.success) dt.ajax.reload(null, false); },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to update status.';
                    alert(msg);
                },
            });
        });

        $('#blogsTable tbody').on('click', '.js-delete', function () {
            const url  = $(this).data('url');
            const name = $(this).data('name');
            if (!confirm('Delete "' + name + '"? (This is a soft delete.)')) return;
            $.ajax({
                url: url,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    if (res.success) dt.ajax.reload(null, false);
                    else alert(res.message || 'Could not delete post.');
                },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to delete post.';
                    alert(msg);
                },
            });
        });
    });
</script>
@endpush
