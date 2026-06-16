@extends('layout.app')

@section('title', $supplier->display_name)

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">{{ $supplier->display_name }}</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('suppliers.index') }}">Suppliers</a></li>
                <li class="breadcrumb-item active">{{ $supplier->display_name }}</li>
            </ol>
        </div>
    </div>

    {{-- ─── Tabs ─── --}}
    <ul class="nav nav-tabs nav-bordered mb-3">
        <li class="nav-item">
            <a href="#supplier-details" data-bs-toggle="tab" aria-expanded="true" class="nav-link active">
                <i class="ti ti-building-store fs-lg me-1 align-middle"></i>
                <span class="align-middle">Details</span>
            </a>
        </li>
        @permission('purchases.view')
        <li class="nav-item">
            <a href="#supplier-purchases" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                <i class="ti ti-shopping-cart fs-lg me-1 align-middle"></i>
                <span class="align-middle">Purchases</span>
                @if ($purchaseStats['count'] > 0)
                    <span class="badge badge-soft-primary ms-1">{{ $purchaseStats['count'] }}</span>
                @endif
            </a>
        </li>
        @endpermission
    </ul>

    <div class="tab-content">

        {{-- ════════════════════════════════════════════════════════════
             TAB 1 — Details
        ════════════════════════════════════════════════════════════ --}}
        <div class="tab-pane show active" id="supplier-details">
            <div class="row">
                {{-- Profile card --}}
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="avatar-lg mx-auto mb-3">
                                <span class="avatar-title bg-primary-subtle text-primary rounded-circle fw-bold fs-1">
                                    {{ strtoupper(mb_substr($supplier->display_name, 0, 1)) }}
                                </span>
                            </div>
                            <h4 class="mb-1">{{ $supplier->display_name }}</h4>
                            @if ($supplier->company_name && $supplier->name !== $supplier->company_name)
                                <p class="text-muted mb-2">Contact: {{ $supplier->name }}</p>
                            @endif

                            <p class="mb-2"><code>{{ $supplier->supplier_code }}</code></p>

                            <span class="badge {{ $supplier->statusBadgeClass() }}">{{ $supplier->statusLabel() }}</span>

                            <hr>

                            <dl class="row text-start mb-0">
                                <dt class="col-5 text-muted small">ID</dt>
                                <dd class="col-7 small">#{{ $supplier->id }}</dd>

                                <dt class="col-5 text-muted small">Created</dt>
                                <dd class="col-7 small">
                                    {{ optional($supplier->created_at)->format('d M Y, h:i A') ?? '—' }}
                                </dd>

                                <dt class="col-5 text-muted small">Modified</dt>
                                <dd class="col-7 small">
                                    {{ optional($supplier->updated_at)->format('d M Y, h:i A') ?? '—' }}
                                </dd>

                                @if ($supplier->creator)
                                    <dt class="col-5 text-muted small">Created By</dt>
                                    <dd class="col-7 small">{{ $supplier->creator->name }}</dd>
                                @endif
                                @if ($supplier->updater)
                                    <dt class="col-5 text-muted small">Updated By</dt>
                                    <dd class="col-7 small">{{ $supplier->updater->name }}</dd>
                                @endif
                            </dl>

                            @permission('suppliers.edit')
                            <div class="mt-3 d-flex gap-2 justify-content-center">
                                <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-primary btn-sm">
                                    <i class="ti ti-edit me-1"></i> Edit
                                </a>
                                <a href="{{ route('suppliers.index') }}" class="btn btn-light btn-sm">Back</a>
                            </div>
                            @endpermission
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    {{-- Contact --}}
                    <div class="card">
                        <div class="card-header border-light">
                            <h5 class="card-title mb-0">Contact</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-3 text-muted small">Email</dt>
                                <dd class="col-sm-9">
                                    @if ($supplier->email)
                                        <a href="mailto:{{ $supplier->email }}">{{ $supplier->email }}</a>
                                    @else <span class="text-muted">—</span>
                                    @endif
                                </dd>

                                <dt class="col-sm-3 text-muted small">Phone</dt>
                                <dd class="col-sm-9">{{ $supplier->phone }}</dd>

                                <dt class="col-sm-3 text-muted small">Alt. Phone</dt>
                                <dd class="col-sm-9">{{ $supplier->alternate_phone ?? '—' }}</dd>

                                <dt class="col-sm-3 text-muted small">Website</dt>
                                <dd class="col-sm-9">
                                    @if ($supplier->website)
                                        <a href="{{ $supplier->website }}" target="_blank" rel="noopener">
                                            {{ $supplier->website }} <i class="ti ti-external-link fs-xs"></i>
                                        </a>
                                    @else <span class="text-muted">—</span>
                                    @endif
                                </dd>
                            </dl>
                        </div>
                    </div>

                    {{-- Tax & Compliance --}}
                    <div class="card">
                        <div class="card-header border-light">
                            <h5 class="card-title mb-0">Tax &amp; Compliance</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-3 text-muted small">GST Number</dt>
                                <dd class="col-sm-9">{{ $supplier->gst_number ?? '—' }}</dd>

                                <dt class="col-sm-3 text-muted small">Tax Number</dt>
                                <dd class="col-sm-9">{{ $supplier->tax_number ?? '—' }}</dd>
                            </dl>
                        </div>
                    </div>

                    {{-- Address --}}
                    <div class="card">
                        <div class="card-header border-light">
                            <h5 class="card-title mb-0">Address</h5>
                        </div>
                        <div class="card-body">
                            @php
                                $parts = array_filter([
                                    $supplier->address,
                                    $supplier->city,
                                    $supplier->state . ($supplier->zip_code ? ' ' . $supplier->zip_code : ''),
                                    $supplier->country,
                                ]);
                            @endphp
                            @if (empty($parts))
                                <p class="text-muted mb-0">No address on file.</p>
                            @else
                                <address class="mb-0">
                                    @foreach ($parts as $line)
                                        {{ $line }}<br>
                                    @endforeach
                                </address>
                            @endif
                        </div>
                    </div>

                    {{-- Financial --}}
                    <div class="card">
                        <div class="card-header border-light">
                            <h5 class="card-title mb-0">Financial</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <div class="border rounded p-3">
                                        <small class="text-muted d-block">Opening Balance</small>
                                        <span class="fs-4 fw-bold">
                                            ${{ number_format((float) $supplier->opening_balance, 2) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="border rounded p-3">
                                        <small class="text-muted d-block">Credit Limit</small>
                                        <span class="fs-4 fw-bold">
                                            ${{ number_format((float) $supplier->credit_limit, 2) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- /TAB 1 — Details --}}

        {{-- ════════════════════════════════════════════════════════════
             TAB 2 — Purchases (server-side DataTable)
        ════════════════════════════════════════════════════════════ --}}
        @permission('purchases.view')
        <div class="tab-pane" id="supplier-purchases">

            {{-- Summary cards --}}
            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3">
                    <div class="card mb-0">
                        <div class="card-body">
                            <p class="text-muted mb-1 fs-xxs text-uppercase">Total Purchases</p>
                            <h4 class="mb-0">{{ number_format($purchaseStats['count']) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card mb-0">
                        <div class="card-body">
                            <p class="text-muted mb-1 fs-xxs text-uppercase">Posted</p>
                            <h4 class="mb-0">{{ number_format($purchaseStats['posted']) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card mb-0">
                        <div class="card-body">
                            <p class="text-muted mb-1 fs-xxs text-uppercase">Total Amount</p>
                            <h4 class="mb-0">${{ number_format($purchaseStats['total'], 2) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card mb-0">
                        <div class="card-body">
                            <p class="text-muted mb-1 fs-xxs text-uppercase">Due Amount</p>
                            <h4 class="mb-0 {{ $purchaseStats['due'] > 0 ? 'text-danger' : '' }}">
                                ${{ number_format($purchaseStats['due'], 2) }}
                            </h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-light justify-content-between">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="app-search">
                            <input id="supplierPurchaseSearch" type="search" class="form-control"
                                placeholder="Search invoice..." />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1">
                        <div>
                            <select id="supplierPurchasePerPage" class="form-select form-control my-1 my-md-0">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="15">15</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                        <select id="supplierPurchaseStatusFilter" class="form-select form-control">
                            <option value="">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="posted">Posted</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        @permission('purchases.create')
                        <a href="{{ route('purchases.create') }}" class="btn btn-primary ms-1">
                            <i class="ti ti-plus fs-sm me-2"></i> Purchase
                        </a>
                        @endpermission
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="supplierPurchasesTable" class="table table-custom table-centered table-hover w-100 mb-0">
                        <thead class="bg-light bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th>Invoice #</th>
                                <th>Date</th>
                                <th>Location</th>
                                <th class="text-end">Grand Total</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Due</th>
                                <th>Status</th>
                                <th class="text-center" style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div class="card-footer border-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div id="supplierPurchasesInfoSlot" class="text-muted small"></div>
                        <div id="supplierPurchasesPaginationSlot"></div>
                    </div>
                </div>
            </div>
        </div>
        @endpermission
        {{-- /TAB 2 — Purchases --}}

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

    #supplierPurchasesTable_wrapper .dataTables_length,
    #supplierPurchasesTable_wrapper .dataTables_filter { display: none !important; }

    #supplierPurchasesInfoSlot .dataTables_info { padding: 0; color: var(--bs-body-color); font-size: 0.875rem; }
    #supplierPurchasesPaginationSlot .pagination { margin-bottom: 0; }
    #supplierPurchasesPaginationSlot .dataTables_paginate { margin: 0; }
</style>
@endpush

@permission('purchases.view')
@push('scripts')
<script>
    $(function () {
        let purchasesTableInitialized = false;

        function initSupplierPurchasesTable() {
            if (purchasesTableInitialized) return;
            purchasesTableInitialized = true;

            const dt = $('#supplierPurchasesTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: '{{ route('suppliers.purchases-data', $supplier) }}',
                    type: 'GET',
                    data: function (d) {
                        d.status = $('#supplierPurchaseStatusFilter').val();
                    },
                },
                dom: 'rt<"d-none datatables-tail"ip>',
                pageLength: 10,
                order: [[1, 'desc']],
                columns: [
                    { data: 'invoice_link',   name: 'invoice_number' },
                    { data: 'purchase_date',  name: 'purchase_date' },
                    { data: 'location_label', name: 'location.name', orderable: false },
                    { data: 'grand_total',    name: 'grand_total',   className: 'text-end' },
                    { data: 'paid_amount',    name: 'paid_amount',   className: 'text-end' },
                    { data: 'due_amount',     name: 'due_amount',    className: 'text-end' },
                    { data: 'status_badge',   name: 'status' },
                    { data: 'actions',        orderable: false, searchable: false, className: 'text-center' },
                ],
                language: {
                    info: 'Showing _START_ to _END_ of _TOTAL_ purchases',
                    infoEmpty: 'No purchases found',
                    infoFiltered: ' (filtered from _MAX_ total)',
                    emptyTable: 'No purchases recorded for this supplier yet.',
                    zeroRecords: 'No purchases match your search.',
                    processing: '<div class="spinner-border spinner-border-sm text-primary"></div>',
                    paginate: {
                        previous: '<i class="ti ti-chevron-left"></i>',
                        next:     '<i class="ti ti-chevron-right"></i>',
                    },
                },
                initComplete: function () {
                    $('#supplierPurchasesInfoSlot').append($('#supplierPurchasesTable_info'));
                    $('#supplierPurchasesPaginationSlot').append($('#supplierPurchasesTable_paginate'));
                },
            });

            let searchTimer;
            $('#supplierPurchaseSearch').on('keyup', function () {
                clearTimeout(searchTimer);
                const v = this.value;
                searchTimer = setTimeout(() => dt.search(v).draw(), 250);
            });

            $('#supplierPurchasePerPage').on('change', function () {
                dt.page.len(parseInt(this.value, 10)).draw();
            });

            $('#supplierPurchaseStatusFilter').on('change', function () {
                dt.draw();
            });
        }

        // Build immediately if the Purchases tab is already active on load,
        // otherwise initialize on first show (DataTables needs a visible
        // table to size its columns correctly).
        if ($('#supplier-purchases').hasClass('active')) {
            initSupplierPurchasesTable();
        }
        $('a[href="#supplier-purchases"]').on('shown.bs.tab', initSupplierPurchasesTable);
    });
</script>
@endpush
@endpermission
