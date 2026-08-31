@extends('layout.app')

@section('title', 'Stock Ledger — ' . $product->title)

@section('content')

<div class="container-fluid stock-page">

    {{-- Page header --}}
    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1 d-flex align-items-center gap-3">
            <a href="{{ route('stock.index') }}" class="btn btn-default btn-icon btn-sm" title="Back to Stock">
                <i class="ti ti-arrow-left fs-md"></i>
            </a>
            <div>
                <h4 class="page-main-title m-0">
                    {{ $product->title }}
                    <span class="badge badge-soft-secondary ms-1 fs-xs fw-normal">SKU: {{ $product->sku }}</span>
                </h4>
                <small class="text-muted">Stock Ledger</small>
            </div>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('stock.index') }}">Stock</a></li>
                <li class="breadcrumb-item active">{{ $product->title }}</li>
            </ol>
        </div>
    </div>

    {{-- KPI Summary Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="card h-100 mb-0">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="avatar avatar-sm bg-success-subtle text-success rounded">
                            <i class="ti ti-package-import fs-md"></i>
                        </span>
                        <span class="text-muted small">Total Received</span>
                    </div>
                    <h3 class="mb-0 text-success">{{ number_format($summary['total_in']) }}</h3>
                    <small class="text-muted">{{ number_format($summary['purchased_qty']) }} from purchases</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 mb-0">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="avatar avatar-sm bg-danger-subtle text-danger rounded">
                            <i class="ti ti-package-export fs-md"></i>
                        </span>
                        <span class="text-muted small">Total Dispatched</span>
                    </div>
                    <h3 class="mb-0 text-danger">{{ number_format($summary['total_out']) }}</h3>
                    <small class="text-muted">{{ number_format($summary['sold_qty']) }} via sales</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 mb-0">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="avatar avatar-sm bg-primary-subtle text-primary rounded">
                            <i class="ti ti-stack-2 fs-md"></i>
                        </span>
                        <span class="text-muted small">Current Balance
                            @if($locationId)
                                <span class="badge badge-soft-info ms-1 fs-xxs">Filtered</span>
                            @else
                                <span class="badge badge-soft-secondary ms-1 fs-xxs">Global</span>
                            @endif
                        </span>
                    </div>
                    <h3 class="mb-0 {{ $onHand <= 0 ? 'text-danger' : '' }}">{{ number_format($onHand) }}</h3>
                    <small class="text-muted">
                        units on hand
                        @if ($onHandCt !== null)
                            &bull; {{ rtrim(rtrim(number_format($onHandCt, 3), '0'), '.') }} ct remaining
                        @endif
                    </small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 mb-0">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="avatar avatar-sm bg-warning-subtle text-warning rounded">
                            <i class="ti ti-list-details fs-md"></i>
                        </span>
                        <span class="text-muted small">Total Movements</span>
                    </div>
                    <h3 class="mb-0">{{ number_format($summary['count']) }}</h3>
                    <small class="text-muted">ledger entries</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Controls row: location filter + product link --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="d-flex align-items-center gap-2 flex-grow-1">
                    <i class="ti ti-map-pin text-muted"></i>
                    <form method="GET" action="{{ route('stock.product', $product) }}" id="locFilter" class="d-flex align-items-center gap-2">
                        <select name="location_id" class="form-select form-select-sm" style="min-width:200px" onchange="document.getElementById('locFilter').submit()">
                            <option value="">All Locations (Global)</option>
                            @foreach ($locations as $l)
                                <option value="{{ $l->id }}" @if($locationId == $l->id) selected @endif>
                                    {{ $l->name }} ({{ $l->location_code }})
                                </option>
                            @endforeach
                        </select>
                        @if($locationId)
                            <a href="{{ route('stock.product', $product) }}" class="btn btn-outline-secondary btn-sm">
                                <i class="ti ti-x fs-xs me-1"></i>Clear
                            </a>
                        @endif
                    </form>
                </div>
                <a href="{{ route('products.show', $product) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-box fs-sm me-1"></i>View Product
                </a>
            </div>
        </div>
    </div>

    {{-- Movement History — the same ledger table (and pagination) used by
         the Stock Dashboard's Stock In / Stock Out / Transfer tabs, fixed
         to this one product via a hidden product_id filter. --}}
    <div class="card">
        <div class="card-header border-light d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="card-title mb-0">Movement History</h5>
            <div class="d-flex flex-wrap gap-1" id="categoryTabs">
                <button class="btn btn-sm btn-primary filter-tab active" data-filter="">
                    All <span class="badge bg-white text-primary ms-1">{{ $summary['count'] }}</span>
                </button>
                @if($summary['cat_purchase'] > 0)
                <button class="btn btn-sm btn-outline-success filter-tab" data-filter="purchase">
                    <i class="ti ti-shopping-cart fs-xs me-1"></i>Purchases
                    <span class="badge badge-soft-success ms-1">{{ $summary['cat_purchase'] }}</span>
                </button>
                @endif
                @if($summary['cat_sale'] > 0)
                <button class="btn btn-sm btn-outline-danger filter-tab" data-filter="sale">
                    <i class="ti ti-receipt fs-xs me-1"></i>Sales
                    <span class="badge badge-soft-danger ms-1">{{ $summary['cat_sale'] }}</span>
                </button>
                @endif
                @if($summary['cat_transfer'] > 0)
                <button class="btn btn-sm btn-outline-info filter-tab" data-filter="transfer">
                    <i class="ti ti-transfer fs-xs me-1"></i>Transfers
                    <span class="badge badge-soft-info ms-1">{{ $summary['cat_transfer'] }}</span>
                </button>
                @endif
                @if($summary['cat_adjustment'] > 0)
                <button class="btn btn-sm btn-outline-warning filter-tab" data-filter="adjustment">
                    <i class="ti ti-adjustments-horizontal fs-xs me-1"></i>Adjustments
                    <span class="badge badge-soft-warning ms-1">{{ $summary['cat_adjustment'] }}</span>
                </button>
                @endif
            </div>
        </div>

        <div class="table-responsive">
            <table id="movementsTbl" class="table table-custom table-centered table-hover w-100 mb-0 stock-ledger-table">
                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                    <tr class="text-uppercase fs-xxs">
                        <th class="text-center" style="width: 1%;">S.No</th>
                        <th><i class="ti ti-calendar me-1"></i>Date &amp; Time</th>
                        <th><i class="ti ti-arrows-exchange me-1"></i>Movement Type</th>
                        <th class="text-end"><i class="ti ti-arrow-down me-1"></i>Stock In</th>
                        <th class="text-end"><i class="ti ti-arrow-up me-1"></i>Stock Out</th>
                        <th><i class="ti ti-hash me-1"></i>Reference No.</th>
                        <th><i class="ti ti-building-warehouse me-1"></i>Source / Destination</th>
                        <th><i class="ti ti-map-pin me-1"></i>Location</th>
                        <th><i class="ti ti-user me-1"></i>User</th>
                    </tr>
                </thead>
            </table>
        </div>

        <div class="card-footer border-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div id="movementsInfoSlot" class="text-muted small"></div>
                <div class="d-flex align-items-center gap-2 footer-pagination-group">
                    <select id="movementsPerPage" class="form-select form-select-sm" style="width: auto;">
                        <option value="25" selected>25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="200">200</option>
                    </select>
                    <div id="movementsPaginationSlot"></div>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('styles')
<style>
    /* ==========================================================
       Product ledger page — shares the Stock module's visual
       identity with stock/index.blade.php. Scoped under .stock-page.
       ========================================================== */
    .stock-page {
        --stock-primary: #1d4ed8;
        --stock-primary-dark: #1e3a8a;
        --stock-success: #059669;
        --stock-warning: #d97706;
        --stock-danger: #dc2626;
        --stock-border: #e2e8f0;
        --stock-text-muted: #64748b;
        padding-top: 0;
        padding-bottom: 20px;
    }
    .stock-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid var(--stock-border);
    }
    .stock-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        position: relative;
        padding-left: 12px;
    }
    .stock-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0; top: 2px; bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, var(--stock-primary-dark), var(--stock-primary));
    }
    .stock-page .breadcrumb { font-size: 0.75rem; }

    .stock-page .card { border-radius: 10px; box-shadow: none; border: 1px solid var(--stock-border); }
    .stock-page .card-header { padding: 10px 16px; }
    .stock-page .card-title { font-size: 0.9375rem; font-weight: 700; }

    .stock-page .stock-ledger-table thead th {
        background: #f1f5f9; font-weight: 700; font-size: 0.6875rem; letter-spacing: 0.03em; padding: 8px 12px;
    }
    .stock-page .stock-ledger-table tbody td {
        padding: 6px 12px; font-size: 0.75rem; vertical-align: middle;
        white-space: nowrap; max-width: 220px; overflow: hidden; text-overflow: ellipsis;
    }
    .stock-page .stock-ledger-table tbody tr:hover { background: #f8fafc; }
    .stock-page .stock-ledger-table .badge,
    .stock-page .stock-ledger-table small { font-size: 0.75rem; }
    .stock-page .stock-ledger-table .movement-qty,
    .stock-page .stock-ledger-table .movement-date,
    .stock-page .stock-ledger-table .movement-time {
        font-variant-numeric: tabular-nums;
        font-feature-settings: "tnum";
    }
    /* Date + time now sit on one line per row instead of stacking —
       shrunk slightly further so both fit comfortably together. */
    .stock-page .stock-ledger-table .movement-date,
    .stock-page .stock-ledger-table .movement-time {
        font-size: 0.6875rem;
    }
    .stock-page .stock-ledger-table .movement-qty { display: inline-block; min-width: 2.5em; text-align: right; }
    .badge-soft-purple { background: #ede9fe; color: #9333ea; }

    #movementsTbl_wrapper .dataTables_length, #movementsTbl_wrapper .dataTables_filter { display: none !important; }
    #movementsInfoSlot .dataTables_info { padding: 0; font-size: 0.875rem; }

    .filter-tab { border-radius: 4px; }
    .filter-tab.active { box-shadow: 0 0 0 2px rgba(var(--bs-primary-rgb),.25); }
    .avatar.avatar-sm { width: 32px; height: 32px; display:inline-flex; align-items:center; justify-content:center; border-radius: .375rem; }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    let movementType = '';

    const dt = $('#movementsTbl').DataTable({
        processing: true,
        serverSide: true,
        responsive: false,
        ordering: false,
        ajax: {
            url: '{{ route('stock.movements-data') }}',
            data: function (d) {
                d.product_id  = {{ $product->id }};
                d.location_id = {{ $locationId ?? 0 }};
                d.type        = movementType;
            },
        },
        dom: 'rt<"movements-tail"ip>',
        pageLength: 25,
        columns: [
            { data: 'DT_RowIndex',     name: 'DT_RowIndex',     orderable: false, searchable: false, className: 'text-center' },
            { data: 'when_label',      name: 'when_label',      orderable: false, searchable: false },
            { data: 'movement_label',  name: 'movement_label',  orderable: false, searchable: false },
            { data: 'stock_in_label',  name: 'stock_in_label',  orderable: false, searchable: false, className: 'text-end' },
            { data: 'stock_out_label', name: 'stock_out_label', orderable: false, searchable: false, className: 'text-end' },
            { data: 'reference_label', name: 'reference_label', orderable: false, searchable: false },
            { data: 'source_label',    name: 'source_label',    orderable: false, searchable: false },
            { data: 'location_label',  name: 'location_label',  orderable: false, searchable: false },
            { data: 'by_label',        name: 'by_label',        orderable: false, searchable: false },
        ],
        language: {
            info: 'Showing _START_ to _END_ of _TOTAL_ movements',
            emptyTable: 'No movements recorded for this product yet.',
            zeroRecords: 'No movements match this filter.',
            paginate: { previous: '<i class="ti ti-chevron-left"></i>', next: '<i class="ti ti-chevron-right"></i>' },
        },
        initComplete: function () {
            const container = $(this.api().table().container());
            $('#movementsInfoSlot').append(container.find('#movementsTbl_info'));
            $('#movementsPaginationSlot').append(container.find('.movements-tail'));
        },
    });

    $('#movementsPerPage').on('change', function () { dt.page.len(parseInt(this.value, 10)).draw(); });

    $('#categoryTabs .filter-tab').on('click', function () {
        const $btn = $(this);
        movementType = $btn.data('filter') || '';

        $('.filter-tab').each(function () {
            const $b = $(this);
            $b.removeClass('active');
            if (!$b.hasClass('btn-primary')) {
                $b.removeClass('btn-outline-success btn-outline-danger btn-outline-info btn-outline-warning btn-outline-secondary');
                const colorMap = { purchase: 'btn-outline-success', sale: 'btn-outline-danger', transfer: 'btn-outline-info', adjustment: 'btn-outline-warning' };
                $b.addClass(colorMap[$b.data('filter')] || 'btn-outline-secondary');
            }
        });
        $btn.addClass('active');

        dt.draw();
    });
});
</script>
@endpush
