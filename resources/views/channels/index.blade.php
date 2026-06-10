@extends('layout.app')

@section('title', 'Sales Channels')

@section('content')
<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">
                <i class="ti ti-broadcast text-primary me-2"></i>Sales Channels
            </h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Channels</li>
            </ol>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-light justify-content-between">
                    <div class="d-flex gap-2">
                        <div class="app-search">
                            <input id="channelSearch" type="search" class="form-control" placeholder="Search channels…" />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1 flex-wrap">
                        @permission('channels.create')
                        <a href="{{ route('channels.create') }}" class="btn btn-primary ms-1">
                            <i class="ti ti-plus fs-sm me-2"></i> New Channel
                        </a>
                        @endpermission
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="channelsTable" class="table table-custom table-centered table-hover w-100 mb-0">
                        <thead class="bg-light bg-opacity-25 thead-sm align-middle">
                            <tr class="text-uppercase fs-xxs">
                                <th style="width: 4%;">#</th>
                                <th style="width: 4%;">Icon</th>
                                <th>Name</th>
                                <th>Code</th>
                                <th class="text-center">Order</th>
                                <th class="text-center">Sales</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <div class="card-footer border-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div id="channelsInfoSlot" class="text-muted small"></div>
                        <div id="channelsPaginationSlot"></div>
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
    .app-search > .form-control { padding-right: 2.25rem; min-width: 160px; }
    #channelsTable_wrapper .dataTables_length, #channelsTable_wrapper .dataTables_filter { display: none !important; }
    #channelsInfoSlot .dataTables_info { padding: 0; }
    #channelsPaginationSlot .pagination { margin-bottom: 0; }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    const dt = $('#channelsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: false,
        order: [[4, 'asc']],
        ajax: { url: '{{ route('channels.data') }}', type: 'GET' },
        dom: 'rt<"d-none datatables-tail"ip>',
        pageLength: 25,
        columns: [
            { data: 'id',            name: 'id',            className: 'text-muted small' },
            { data: 'icon_preview',  name: 'icon',          orderable: false, searchable: false, className: 'text-center' },
            { data: 'name',          name: 'name' },
            { data: 'code',          name: 'code',          render: (d) => '<code>' + d + '</code>' },
            { data: 'display_order', name: 'display_order', className: 'text-center' },
            { data: 'sales_count',   name: 'sales_count',   orderable: false, searchable: false, className: 'text-center fw-semibold' },
            { data: 'status',        name: 'status',        orderable: false, searchable: false, className: 'text-center' },
            { data: 'actions',       name: 'actions',       orderable: false, searchable: false, className: 'text-center' },
        ],
        language: {
            info: 'Showing _START_ to _END_ of _TOTAL_ channels',
            infoEmpty: 'No channels found',
            emptyTable: 'No channels yet. <a href="{{ route('channels.create') }}">Add one</a>.',
            processing: '<div class="spinner-border spinner-border-sm text-primary"></div>',
            paginate: { previous: '<i class="ti ti-chevron-left"></i>', next: '<i class="ti ti-chevron-right"></i>' },
        },
        initComplete: function () {
            $('#channelsInfoSlot').append($('#channelsTable_info'));
            $('#channelsPaginationSlot').append($('#channelsTable_paginate'));
        },
    });

    let timer;
    $('#channelSearch').on('keyup', function () {
        clearTimeout(timer);
        const v = this.value;
        timer = setTimeout(() => dt.search(v).draw(), 250);
    });

    // Toggle active/inactive
    $('#channelsTable tbody').on('click', '.js-toggle-channel', function () {
        const url = $(this).data('url');
        $.ajax({
            url, type: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: (res) => { if (res.ok) dt.ajax.reload(null, false); else alert(res.message); },
            error: (xhr) => alert((xhr.responseJSON && xhr.responseJSON.message) || 'Failed.'),
        });
    });

    // Delete — blocked server-side & client-side if has sales
    $('#channelsTable tbody').on('click', '.js-delete-channel', function () {
        const url     = $(this).data('url');
        const name    = $(this).data('name');
        const hasSales = $(this).data('has-sales');

        if (hasSales) {
            alert('Cannot delete "' + name + '" — it has sales recorded. Deactivate it instead.');
            return;
        }
        if (!confirm('Delete channel "' + name + '"?')) return;

        $.ajax({
            url, type: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: (res) => { if (res.ok) dt.ajax.reload(null, false); else alert(res.message); },
            error: (xhr) => alert((xhr.responseJSON && xhr.responseJSON.message) || 'Failed.'),
        });
    });
});
</script>
@endpush
