@extends('layout.app')

@section('title', $audit->audit_number)

@section('content')
<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">
                {{ $audit->audit_number }}
                <span class="badge {{ $audit->statusBadgeClass() }} align-middle ms-1">{{ $audit->statusLabel() }}</span>
            </h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('stock-audits.index') }}">Stock Audits</a></li>
                <li class="breadcrumb-item active">{{ $audit->audit_number }}</li>
            </ol>
        </div>
    </div>

    <div id="actionAlert" class="alert alert-danger d-none"></div>

    <div class="row g-3">

        {{-- Header / summary card --}}
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header border-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Details</h5>
                    @permission('stock-audits.scan')
                        @if ($audit->isInProgress())
                            <a href="{{ route('stock-audits.scan', $audit) }}" class="btn btn-sm btn-primary">
                                <i class="ti ti-scan me-1"></i> Continue Scanning
                            </a>
                        @endif
                    @endpermission
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td class="text-muted" style="width: 40%;">Location</td>
                            <td class="fw-semibold">{{ $audit->location?->name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Audit Date</td>
                            <td>{{ optional($audit->audit_date)->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Started</td>
                            <td>{{ optional($audit->started_at)->format('d M Y, H:i') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Completed</td>
                            <td>{{ optional($audit->completed_at)->format('d M Y, H:i') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Cancelled</td>
                            <td>{{ optional($audit->cancelled_at)->format('d M Y, H:i') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Started by</td>
                            <td>{{ $audit->creator?->name ?? '—' }}</td>
                        </tr>
                        @if ($audit->note)
                        <tr>
                            <td class="text-muted align-top">Note</td>
                            <td>{{ $audit->note }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
                @if ($audit->isInProgress())
                <div class="card-footer border-0 d-flex gap-2">
                    <button type="button" class="btn btn-soft-danger btn-sm js-cancel-audit">
                        <i class="ti ti-ban me-1"></i> Cancel Audit
                    </button>
                    <button type="button" class="btn btn-soft-success btn-sm js-complete-audit">
                        <i class="ti ti-check me-1"></i> Complete Audit
                    </button>
                </div>
                @endif
            </div>
        </div>

        {{-- Progress + scan breakdown --}}
        <div class="col-xl-8">
            <div class="row g-3">
                <div class="col-sm-3 col-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 class="mb-0">{{ (int) $audit->expected_total }}</h3>
                            <p class="text-muted mb-0 fs-xxs text-uppercase">Expected</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3 col-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 class="mb-0 text-success">{{ (int) $audit->matched_total }}</h3>
                            <p class="text-muted mb-0 fs-xxs text-uppercase">Matched</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3 col-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 class="mb-0 text-danger">{{ $audit->missingTotal() }}</h3>
                            <p class="text-muted mb-0 fs-xxs text-uppercase">Missing</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3 col-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 class="mb-0">{{ $audit->progressPercent() }}%</h3>
                            <p class="text-muted mb-0 fs-xxs text-uppercase">Counted</p>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card">
                        <div class="card-header border-light">
                            <h5 class="card-title mb-0">Scan Activity</h5>
                        </div>
                        <div class="card-body d-flex flex-wrap gap-4">
                            <div>
                                <span class="badge badge-soft-success fs-xxs me-1">Matched</span>
                                <span class="fw-semibold">{{ $scanCounts['matched'] }}</span>
                            </div>
                            <div>
                                <span class="badge badge-soft-warning fs-xxs me-1">Duplicate</span>
                                <span class="fw-semibold">{{ $scanCounts['duplicate'] }}</span>
                            </div>
                            <div>
                                <span class="badge badge-soft-danger fs-xxs me-1">Unexpected</span>
                                <span class="fw-semibold">{{ $scanCounts['unexpected'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Missing stock report --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header border-light justify-content-between">
                    <h5 class="card-title mb-0">
                        Missing Stock
                        <span class="text-muted fs-sm fw-normal">— not found during this count</span>
                    </h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('stock-audits.export.pdf', $audit) }}" class="btn btn-sm btn-soft-danger">
                            <i class="ti ti-file-type-pdf me-1"></i> Export PDF
                        </a>
                        <a href="{{ route('stock-audits.export.excel', $audit) }}" class="btn btn-sm btn-soft-success">
                            <i class="ti ti-file-type-xls me-1"></i> Export Excel
                        </a>
                        @permission('stock-audits.write-off')
                        @if ($canWriteOff)
                        <button type="button" class="btn btn-sm btn-soft-warning js-write-off">
                            <i class="ti ti-adjustments me-1"></i> Write Off Missing Stock
                        </button>
                        @endif
                        @endpermission
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="missingTable" class="table table-custom table-centered table-hover w-100 mb-0">
                        <thead class="bg-light bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="text-center" style="width: 1%;">#</th>
                                <th>Lot Code</th>
                                <th>Product</th>
                                <th>SKU</th>
                                <th class="text-end">Carat</th>
                                <th>Category</th>
                                <th>Supplier</th>
                                <th>Invoice #</th>
                                <th>Purchase Date</th>
                                <th class="text-end">Cost Price</th>
                                <th class="text-end">Qty</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <div class="card-footer border-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div id="missingInfoSlot" class="text-muted small"></div>
                        <div id="missingPaginationSlot"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection

@push('styles')
<style>
    #missingTable_wrapper .dataTables_length, #missingTable_wrapper .dataTables_filter { display: none !important; }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const csrfToken   = $('meta[name="csrf-token"]').attr('content');
    const $actionAlert = $('#actionAlert');

    const dt = $('#missingTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: false,
        order: [[0, 'asc']],
        ajax: '{{ route('stock-audits.missing-data', $audit) }}',
        dom: 'rt<"d-none datatables-tail"ip>',
        columns: [
            { data: 'DT_RowIndex',      name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
            { data: 'lot_code_label',   name: 'lot_code',    orderable: false, searchable: false },
            { data: 'product_label',    name: 'product_label', orderable: false, searchable: false },
            { data: 'sku',              name: 'sku',          orderable: false, searchable: false },
            { data: 'carat_weight',     name: 'carat_weight', orderable: false, searchable: false, className: 'text-end' },
            { data: 'category',         name: 'category',     orderable: false, searchable: false },
            { data: 'supplier',         name: 'supplier',     orderable: false, searchable: false },
            { data: 'invoice_number',   name: 'invoice_number', orderable: false, searchable: false },
            { data: 'purchase_date',    name: 'purchase_date', orderable: false, searchable: false },
            { data: 'cost_price',       name: 'cost_price',   orderable: false, searchable: false, className: 'text-end' },
            { data: 'qty',              name: 'qty',          orderable: false, searchable: false, className: 'text-end' },
        ],
        language: {
            info: 'Showing _START_ to _END_ of _TOTAL_ missing items',
            emptyTable: 'Nothing missing — every expected item was scanned.',
            processing: '<div class="spinner-border spinner-border-sm text-primary"></div>',
            paginate: { previous: '<i class="ti ti-chevron-left"></i>', next: '<i class="ti ti-chevron-right"></i>' },
        },
        initComplete: function () {
            $('#missingInfoSlot').append($('#missingTable_info'));
            $('#missingPaginationSlot').append($('#missingTable_paginate'));
        },
    });

    function postAction(url, confirmMsg, onSuccess) {
        if (confirmMsg && !window.confirm(confirmMsg)) return;
        $actionAlert.addClass('d-none').text('');
        $.ajax({
            url, type: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: function (res) {
                if (res.ok) {
                    if (onSuccess) onSuccess(res);
                } else {
                    $actionAlert.removeClass('d-none').text(res.message || 'Something went wrong.');
                }
            },
            error: function (xhr) {
                $actionAlert.removeClass('d-none').text((xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong.');
            },
        });
    }

    $('.js-complete-audit').on('click', function () {
        postAction('{{ $audit->isInProgress() ? route('stock-audits.complete', $audit) : '#' }}',
            'Complete this audit?',
            (res) => window.location.href = res.redirect || window.location.href);
    });

    $('.js-cancel-audit').on('click', function () {
        postAction('{{ $audit->isInProgress() ? route('stock-audits.cancel', $audit) : '#' }}',
            'Cancel this audit? All progress will be kept for reference but no stock will be adjusted.',
            (res) => window.location.href = res.redirect || window.location.href);
    });

    $('.js-write-off').on('click', function () {
        postAction('{{ route('stock-audits.write-off-missing', $audit) }}',
            'This will book a stock adjustment for every item still missing, reducing on-hand quantity in the ledger. This cannot be undone from here. Continue?',
            () => window.location.reload());
    });
});
</script>
@endpush
