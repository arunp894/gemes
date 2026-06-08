@extends('layout.app')

@section('title', 'Banners')

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Banners</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Marketing</a></li>
                <li class="breadcrumb-item active">Banners</li>
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
                            <input id="bannerSearch" type="search" class="form-control"
                                placeholder="Search banners..." />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1">
                        <div>
                            <select id="bannerPerPage" class="form-select form-control my-1 my-md-0">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="15">15</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                            </select>
                        </div>

                        <div class="app-search">
                            <select id="bannerPositionFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All Positions</option>
                                @foreach (\App\Models\Banner::POSITIONS as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <i class="ti ti-layout app-search-icon text-muted"></i>
                        </div>

                        <div class="app-search">
                            <select id="bannerStatusFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <i class="ti ti-circle app-search-icon text-muted"></i>
                        </div>

                        @permission('banners.create')
                        <a href="{{ route('banners.create') }}" class="btn btn-primary ms-1">
                            <i class="ti ti-plus fs-sm me-2"></i> Add Banner
                        </a>
                        @endpermission
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="bannersTable" class="table table-striped dt-responsive align-middle mb-0">
                        <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3" style="width: 1%;">
                                    <input id="bannerSelectAll" class="form-check-input form-check-input-light fs-14 mt-0"
                                        type="checkbox" />
                                </th>
                                <th>Banner</th>
                                <th>Position</th>
                                <th class="text-center">Live</th>
                                <th class="text-center">Status</th>
                                <th>Date Range</th>
                                <th class="text-center">Order</th>
                                <th>Created</th>
                                <th class="text-center" style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <div class="card-footer border-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div id="bannersInfoSlot" class="text-muted small"></div>
                        <div id="bannersPaginationSlot"></div>
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

    #bannersTable_wrapper .dataTables_length,
    #bannersTable_wrapper .dataTables_filter { display: none !important; }

    #bannersInfoSlot .dataTables_info { padding: 0; color: var(--bs-body-color); font-size: 0.875rem; }
    #bannersPaginationSlot .pagination { margin-bottom: 0; }
    #bannersPaginationSlot .dataTables_paginate { margin: 0; }
</style>
@endpush

@push('scripts')
<script>
    $(function () {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        const dt = $('#bannersTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            order: [[6, 'asc']],
            ajax: {
                url: '{{ route('banners.data') }}',
                type: 'GET',
            },
            dom: 'rt<"d-none datatables-tail"ip>',
            pageLength: 10,
            columns: [
                { data: 'checkbox',        name: 'checkbox',         orderable: false, searchable: false, className: 'ps-3' },
                { data: 'title',           name: 'banners.title' },
                { data: 'position_badge',  name: 'banners.position', searchable: true },
                { data: 'live_badge',      name: 'live_badge',       orderable: false, searchable: false, className: 'text-center' },
                { data: 'status_badge',    name: 'banners.status',   searchable: true, className: 'text-center' },
                { data: 'date_range',      name: 'date_range',       orderable: false, searchable: false },
                { data: 'sort_order',      name: 'banners.sort_order', className: 'text-center' },
                { data: 'created_at',      name: 'banners.created_at' },
                { data: 'action',          name: 'action',           orderable: false, searchable: false, className: 'text-center' },
            ],
            language: {
                info: 'Showing _START_ to _END_ of _TOTAL_ banners',
                infoEmpty: 'No banners found',
                infoFiltered: ' (filtered from _MAX_ total)',
                emptyTable: 'No banners yet.',
                zeroRecords: 'No banners match your search.',
                processing: '<div class="spinner-border spinner-border-sm text-primary"></div>',
                paginate: {
                    previous: '<i class="ti ti-chevron-left"></i>',
                    next:     '<i class="ti ti-chevron-right"></i>',
                },
            },
            initComplete: function () {
                $('#bannersInfoSlot').append($('#bannersTable_info'));
                $('#bannersPaginationSlot').append($('#bannersTable_paginate'));
            },
        });

        let searchTimer;
        $('#bannerSearch').on('keyup', function () {
            clearTimeout(searchTimer);
            const v = this.value;
            searchTimer = setTimeout(() => dt.search(v).draw(), 250);
        });

        $('#bannerPerPage').on('change', function () {
            dt.page.len(parseInt(this.value, 10)).draw();
        });

        $('#bannerPositionFilter').on('change', function () {
            dt.column(2).search(this.value).draw();
        });

        $('#bannerStatusFilter').on('change', function () {
            dt.column(4).search(this.value).draw();
        });

        $('#bannerSelectAll').on('change', function () {
            $('#bannersTable tbody .banner-item-check').prop('checked', this.checked);
        });

        // Toggle status
        $('#bannersTable tbody').on('click', '.js-toggle-status', function () {
            const url = $(this).data('url');
            $.ajax({
                url: url,
                type: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) { if (res.success) dt.ajax.reload(null, false); },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message : 'Failed to update status.';
                    alert(msg);
                },
            });
        });

        // Soft-delete
        $('#bannersTable tbody').on('click', '.js-delete', function () {
            const url  = $(this).data('url');
            const name = $(this).data('name');
            if (!confirm('Delete banner "' + name + '"? (This is a soft delete.)')) return;
            $.ajax({
                url: url,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    if (res.success) dt.ajax.reload(null, false);
                    else alert(res.message || 'Could not delete banner.');
                },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message : 'Failed to delete banner.';
                    alert(msg);
                },
            });
        });
    });
</script>
@endpush
