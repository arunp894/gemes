@extends('layout.app')

@section('title', 'Stock Transfers')

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Stock Transfers</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Inventory</a></li>
                <li class="breadcrumb-item active">Transfers</li>
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
                            <input id="transferSearch" type="search" class="form-control" placeholder="Search transfers…" />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1">
                        <div class="app-search">
                            <select id="transferStatusFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All Status</option>
                                <option value="draft">Draft</option>
                                <option value="in_transit">In Transit</option>
                                <option value="received">Received</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <i class="ti ti-circle app-search-icon text-muted"></i>
                        </div>

                        @permission('stock-transfers.create')
                        <a href="{{ route('stock-transfers.create') }}" class="btn btn-primary ms-1">
                            <i class="ti ti-plus fs-sm me-2"></i> New Transfer
                        </a>
                        @endpermission
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="transfersTable" class="table table-custom table-centered table-hover w-100 mb-0">
                        <thead class="bg-light bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th>Transfer #</th>
                                <th>Date</th>
                                <th>From</th>
                                <th>To</th>
                                <th class="text-end">Lines</th>
                                <th>Status</th>
                                <th class="text-center" style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <div class="card-footer border-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div id="transfersInfoSlot" class="text-muted small"></div>
                        <div id="transfersPaginationSlot"></div>
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
    #transfersTable_wrapper .dataTables_length, #transfersTable_wrapper .dataTables_filter { display: none !important; }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    const dt = $('#transfersTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: false,
        order: [[1, 'desc']],
        ajax: {
            url: '{{ route('stock-transfers.data') }}',
            data: function (d) { d.status = $('#transferStatusFilter').val(); },
        },
        dom: 'rt<"d-none datatables-tail"ip>',
        columns: [
            { data: 'transfer_number', name: 'stock_transfers.transfer_number' },
            { data: 'transfer_date',   name: 'stock_transfers.transfer_date' },
            { data: 'from_label',      name: 'from_label', orderable: false, searchable: false },
            { data: 'to_label',        name: 'to_label',   orderable: false, searchable: false },
            { data: 'line_count',      name: 'line_count', orderable: false, searchable: false, className: 'text-end' },
            { data: 'status_badge',    name: 'stock_transfers.status', orderable: false, searchable: false },
            { data: 'actions',         name: 'actions',    orderable: false, searchable: false, className: 'text-center' },
        ],
        language: {
            info: 'Showing _START_ to _END_ of _TOTAL_ transfers',
            emptyTable: 'No transfers yet.',
            processing: '<div class="spinner-border spinner-border-sm text-primary"></div>',
            paginate: { previous: '<i class="ti ti-chevron-left"></i>', next: '<i class="ti ti-chevron-right"></i>' },
        },
        initComplete: function () {
            $('#transfersInfoSlot').append($('#transfersTable_info'));
            $('#transfersPaginationSlot').append($('#transfersTable_paginate'));
        },
    });

    let timer;
    $('#transferSearch').on('keyup', function () {
        clearTimeout(timer);
        const v = this.value;
        timer = setTimeout(() => dt.search(v).draw(), 250);
    });
    $('#transferStatusFilter').on('change', () => dt.draw());

    $('#transfersTable tbody').on('click', '.js-status-action', function () {
        const url     = $(this).data('url');
        const confirm = $(this).data('confirm');
        if (confirm && !window.confirm(confirm)) return;
        $.ajax({
            url, type: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: function (res) { if (res.ok) dt.ajax.reload(null, false); else alert(res.message); },
            error: function (xhr) { alert((xhr.responseJSON && xhr.responseJSON.message) || 'Failed.'); },
        });
    });
});
</script>
@endpush
