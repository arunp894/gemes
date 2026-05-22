@extends('layout.app')

@section('title', 'Suppliers')

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Suppliers</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Procurement</a></li>
                <li class="breadcrumb-item active">Suppliers</li>
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
                            <input id="supplierSearch" type="search" class="form-control"
                                placeholder="Search suppliers..." />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1">
                        <div>
                            <select id="supplierPerPage" class="form-select form-control my-1 my-md-0">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="15">15</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                            </select>
                        </div>

                        <div class="app-search">
                            <select id="supplierStatusFilter" class="form-select form-control my-1 my-md-0">
                                <option value="">All Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <i class="ti ti-circle app-search-icon text-muted"></i>
                        </div>

                        @permission('suppliers.create')
                        <a href="{{ route('suppliers.create') }}" class="btn btn-primary ms-1">
                            <i class="ti ti-plus fs-sm me-2"></i> Add Supplier
                        </a>
                        @endpermission
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="suppliersTable" class="table table-custom table-centered table-select table-hover w-100 mb-0">
                        <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3" style="width: 1%;">
                                    <input id="supplierSelectAll" class="form-check-input form-check-input-light fs-14 mt-0"
                                        type="checkbox" />
                                </th>
                                <th>Code</th>
                                <th>Supplier</th>
                                <th>Contact</th>
                                <th>Location</th>
                                <th class="text-end">Credit Limit</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="text-center" style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <div class="card-footer border-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div id="suppliersInfoSlot" class="text-muted small"></div>
                        <div id="suppliersPaginationSlot"></div>
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

    #suppliersTable_wrapper .dataTables_length,
    #suppliersTable_wrapper .dataTables_filter { display: none !important; }

    #suppliersInfoSlot .dataTables_info { padding: 0; color: var(--bs-body-color); font-size: 0.875rem; }
    #suppliersPaginationSlot .pagination { margin-bottom: 0; }
    #suppliersPaginationSlot .dataTables_paginate { margin: 0; }
</style>
@endpush

@push('scripts')
<script>
    $(function () {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        const dt = $('#suppliersTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            order: [[7, 'desc']],
            ajax: {
                url: '{{ route('suppliers.data') }}',
                type: 'GET',
            },
            dom: 'rt<"d-none datatables-tail"ip>',
            pageLength: 10,
            columns: [
                { data: 'checkbox',      name: 'checkbox',                orderable: false, searchable: false, className: 'ps-3' },
                { data: 'supplier_code', name: 'suppliers.supplier_code' },
                { data: 'name',          name: 'suppliers.name' },
                { data: 'contact',       name: 'contact',                 orderable: false, searchable: false },
                { data: 'location',      name: 'location',                orderable: false, searchable: false },
                { data: 'credit_limit',  name: 'suppliers.credit_limit',  className: 'text-end' },
                { data: 'status_badge',  name: 'suppliers.status',        searchable: true },
                { data: 'created_at',    name: 'suppliers.created_at' },
                { data: 'action',        name: 'action',                  orderable: false, searchable: false, className: 'text-center' },
            ],
            language: {
                info: 'Showing _START_ to _END_ of _TOTAL_ suppliers',
                infoEmpty: 'No suppliers found',
                infoFiltered: ' (filtered from _MAX_ total)',
                emptyTable: 'No suppliers yet.',
                zeroRecords: 'No suppliers match your search.',
                processing: '<div class="spinner-border spinner-border-sm text-primary"></div>',
                paginate: {
                    previous: '<i class="ti ti-chevron-left"></i>',
                    next:     '<i class="ti ti-chevron-right"></i>',
                },
            },
            initComplete: function () {
                $('#suppliersInfoSlot').append($('#suppliersTable_info'));
                $('#suppliersPaginationSlot').append($('#suppliersTable_paginate'));
            },
        });

        let searchTimer;
        $('#supplierSearch').on('keyup', function () {
            clearTimeout(searchTimer);
            const v = this.value;
            searchTimer = setTimeout(() => dt.search(v).draw(), 250);
        });

        $('#supplierPerPage').on('change', function () {
            dt.page.len(parseInt(this.value, 10)).draw();
        });

        $('#supplierStatusFilter').on('change', function () {
            dt.column(6).search(this.value).draw();
        });

        $('#supplierSelectAll').on('change', function () {
            $('#suppliersTable tbody .product-item-check').prop('checked', this.checked);
        });

        $('#suppliersTable tbody').on('click', '.js-toggle-status', function () {
            const url = $(this).data('url');
            $.ajax({
                url: url,
                type: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) { if (res.success) dt.ajax.reload(null, false); },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Failed to update status.';
                    alert(msg);
                },
            });
        });

        $('#suppliersTable tbody').on('click', '.js-delete', function () {
            const url  = $(this).data('url');
            const name = $(this).data('name');
            if (!confirm('Delete supplier "' + name + '"? (This is a soft delete.)')) return;
            $.ajax({
                url: url,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    if (res.success) dt.ajax.reload(null, false);
                    else alert(res.message || 'Could not delete supplier.');
                },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Failed to delete supplier.';
                    alert(msg);
                },
            });
        });
    });
</script>
@endpush
