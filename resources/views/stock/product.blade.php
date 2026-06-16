@extends('layout.app')

@section('title', 'Stock Ledger — ' . $product->title)

@section('content')

<div class="container-fluid">

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
                    <small class="text-muted">units on hand</small>
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

    {{-- Movements Table --}}
    <div class="card">
        <div class="card-header border-light d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="card-title mb-0">Movement History</h5>
            {{-- Filter tabs --}}
            <div class="d-flex flex-wrap gap-1" id="categoryTabs">
                <button class="btn btn-sm btn-primary filter-tab active" data-filter="all">
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
            <table class="table table-custom table-centered align-middle mb-0" id="movementsTbl">
                <thead class="bg-light bg-opacity-25 thead-sm">
                    <tr class="text-uppercase fs-xxs">
                        <th style="min-width:110px">Date</th>
                        <th>Direction</th>
                        <th>Reason</th>
                        <th style="min-width:180px">Source Document</th>
                        <th>Piece</th>
                        <th>Location</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Balance</th>
                        <th>By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php
                            $m       = $row['movement'];
                            $srcType = $m->source_type;
                            $srcId   = $m->source_id;

                            // Source document link
                            $srcLabel = null;
                            $srcUrl   = null;
                            $srcIcon  = null;
                            $srcBadge = 'secondary';
                            if ($srcType && $srcId) {
                                switch ($srcType) {
                                    case 'purchase':
                                        $srcLabel = $sourceLabels['purchase'][$srcId] ?? ('PUR #'.$srcId);
                                        $srcUrl   = route('purchases.show', $srcId);
                                        $srcIcon  = 'ti-shopping-cart';
                                        $srcBadge = 'success';
                                        break;
                                    case 'sale':
                                        $srcLabel = $sourceLabels['sale'][$srcId] ?? ('SALE #'.$srcId);
                                        $srcUrl   = route('sales.show', $srcId);
                                        $srcIcon  = 'ti-receipt';
                                        $srcBadge = 'danger';
                                        break;
                                    case 'stock_transfer':
                                        $srcLabel = $sourceLabels['stock_transfer'][$srcId] ?? ('TRF #'.$srcId);
                                        $srcUrl   = route('stock-transfers.show', $srcId);
                                        $srcIcon  = 'ti-transfer';
                                        $srcBadge = 'info';
                                        break;
                                }
                            }

                            // Category for JS filter
                            $cat = match(true) {
                                in_array($m->reason, ['purchase','purchase_cancel'])  => 'purchase',
                                in_array($m->reason, ['sale','sale_return','sale_cancel','sale_edit_reverse']) => 'sale',
                                in_array($m->reason, ['transfer_out','transfer_in','transfer_cancel_out']) => 'transfer',
                                default => 'adjustment',
                            };
                        @endphp
                        <tr data-category="{{ $cat }}">
                            <td>
                                <div class="fw-semibold small">{{ optional($m->movement_date)->format('d M Y') }}</div>
                                @if($m->notes)
                                    <small class="text-muted d-block" title="{{ $m->notes }}">
                                        <i class="ti ti-message-circle fs-xxs"></i>
                                        {{ Str::limit($m->notes, 30) }}
                                    </small>
                                @endif
                            </td>
                            <td>
                                @if($m->isIn())
                                    <span class="badge badge-soft-success"><i class="ti ti-arrow-down-circle me-1"></i>IN</span>
                                @else
                                    <span class="badge badge-soft-danger"><i class="ti ti-arrow-up-circle me-1"></i>OUT</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $m->reasonBadgeClass() }} fs-xxs">{{ $m->reasonLabel() }}</span>
                            </td>
                            <td>
                                @if($srcUrl)
                                    <a href="{{ $srcUrl }}" class="d-inline-flex align-items-center gap-1 text-decoration-none src-link">
                                        <span class="badge badge-soft-{{ $srcBadge }} d-inline-flex align-items-center gap-1">
                                            <i class="ti {{ $srcIcon }} fs-xs"></i>
                                            {{ $srcLabel }}
                                        </span>
                                        <i class="ti ti-external-link fs-xxs text-muted"></i>
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('stock.piece', $m->purchase_product_id) }}" class="text-primary">
                                    <code class="small">#{{ $m->purchase_product_id }}</code>
                                </a>
                                @if ($m->purchaseProduct?->barcode)
                                    <small class="d-block text-muted">{{ $m->purchaseProduct->barcode }}</small>
                                @endif
                            </td>
                            <td>{{ optional($m->location)->name ?? '—' }}</td>
                            <td class="text-end {{ $m->isIn() ? 'text-success' : 'text-danger' }} fw-semibold">
                                {{ $m->isIn() ? '+' : '−' }}{{ (int) $m->qty }}
                            </td>
                            <td class="text-end fw-semibold">{{ (int) $row['balance_after'] }}</td>
                            <td><small class="text-muted">{{ optional($m->creator)->name ?? '—' }}</small></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="ti ti-inbox d-block fs-2xl mb-2"></i>
                                No movements recorded for this product yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection

@push('styles')
<style>
.src-link { transition: opacity .15s; }
.src-link:hover { opacity: .8; }
.filter-tab { border-radius: 4px; }
.filter-tab.active { box-shadow: 0 0 0 2px rgba(var(--bs-primary-rgb),.25); }
.avatar.avatar-sm { width: 32px; height: 32px; display:inline-flex; align-items:center; justify-content:center; border-radius: .375rem; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var tabs = document.querySelectorAll('.filter-tab');
    var rows = document.querySelectorAll('#movementsTbl tbody tr[data-category]');

    tabs.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var filter = this.dataset.filter;

            rows.forEach(function (row) {
                row.style.display = (filter === 'all' || row.dataset.category === filter) ? '' : 'none';
            });

            tabs.forEach(function (b) {
                b.classList.remove('active');
                // toggle outline vs solid
                var cls = b.className;
                if (!b.classList.contains('btn-primary')) {
                    b.classList.remove('btn-outline-success','btn-outline-danger','btn-outline-info','btn-outline-warning','btn-outline-secondary');
                    var colorMap = {purchase:'btn-outline-success', sale:'btn-outline-danger', transfer:'btn-outline-info', adjustment:'btn-outline-warning'};
                    b.classList.add(colorMap[b.dataset.filter] || 'btn-outline-secondary');
                }
            });

            this.classList.add('active');
        });
    });
});
</script>
@endpush
