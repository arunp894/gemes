@extends('layout.app')

@section('title', 'Stones & Carat — ' . $category->name)

@section('content')

<div class="container-fluid stock-page">

    {{-- Page header --}}
    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1 d-flex align-items-center gap-3">
            <a href="{{ route('stock.index') }}" class="btn btn-default btn-icon btn-sm" title="Back to Stock">
                <i class="ti ti-arrow-left fs-md"></i>
            </a>
            <div>
                <h4 class="page-main-title m-0">{{ $category->name }}</h4>
                <small class="text-muted">Stones &amp; Carat — product breakdown</small>
            </div>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('stock.index') }}">Stock</a></li>
                <li class="breadcrumb-item active">{{ $category->name }}</li>
            </ol>
        </div>
    </div>

    {{-- KPI Summary Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="card h-100 mb-0">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="avatar avatar-sm bg-primary-subtle text-primary rounded">
                            <i class="ti ti-stack-2 fs-md"></i>
                        </span>
                        <span class="text-muted small">Total Pieces</span>
                    </div>
                    <h3 class="mb-0">{{ number_format($totalPieces) }}</h3>
                    <small class="text-muted">{{ $products->count() }} product{{ $products->count() === 1 ? '' : 's' }}</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 mb-0">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="avatar avatar-sm bg-info-subtle text-info rounded">
                            <i class="ti ti-scale fs-md"></i>
                        </span>
                        <span class="text-muted small">Carat Weight</span>
                    </div>
                    <h3 class="mb-0">{{ rtrim(rtrim(number_format($totalCarat, 3), '0'), '.') }}</h3>
                    <small class="text-muted">total carats</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 mb-0">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="avatar avatar-sm bg-warning-subtle text-warning rounded">
                            <i class="ti ti-receipt-2 fs-md"></i>
                        </span>
                        <span class="text-muted small">Rate / Ct (Avg.)</span>
                    </div>
                    <h3 class="mb-0">{{ $avgRatePerCarat !== null ? $settings->formatMoney($avgRatePerCarat) : '—' }}</h3>
                    <small class="text-muted">weighted average, approx.</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 mb-0">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="avatar avatar-sm bg-success-subtle text-success rounded">
                            <i class="ti ti-currency-rupee fs-md"></i>
                        </span>
                        <span class="text-muted small">Stock Value</span>
                    </div>
                    <h3 class="mb-0">{{ $settings->formatMoney($totalValue) }}</h3>
                    <small class="text-muted">at cost price</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Location filter --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('stock.stone', $category) }}" id="locFilter" class="d-flex align-items-center gap-2">
                <i class="ti ti-map-pin text-muted"></i>
                <select name="location_id" class="form-select form-select-sm" style="min-width:200px" onchange="document.getElementById('locFilter').submit()">
                    <option value="">All Locations (Global)</option>
                    @foreach ($locations as $l)
                        <option value="{{ $l->id }}" @if($locationId == $l->id) selected @endif>
                            {{ $l->name }} ({{ $l->location_code }})
                        </option>
                    @endforeach
                </select>
                @if($locationId)
                    <a href="{{ route('stock.stone', $category) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="ti ti-x fs-xs me-1"></i>Clear
                    </a>
                @endif
            </form>
        </div>
    </div>

    {{-- Products table --}}
    <div class="card">
        <div class="card-header border-light">
            <h5 class="card-title mb-0">Products</h5>
            <p class="text-muted fs-xs mb-0">Click a product to see its full stock history</p>
        </div>

        <div class="table-responsive">
            <table class="table table-custom table-centered align-middle mb-0 stock-ledger-table">
                <thead class="bg-light bg-opacity-25 thead-sm">
                    <tr class="text-uppercase fs-xxs">
                        <th><i class="ti ti-diamond me-1"></i>Product</th>
                        <th class="text-end"><i class="ti ti-stack-2 me-1"></i>Pieces</th>
                        <th class="text-end"><i class="ti ti-scale me-1"></i>Carat Weight</th>
                        <th class="text-end">Rate / Ct (Approx.)</th>
                        <th class="text-end">Stock Value</th>
                        <th class="text-center" style="width: 1%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $p)
                        @php
                            $rate = $p->carat_weight > 0 ? $p->stock_value / $p->carat_weight : null;
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('stock.product', $p->product_id) }}"
                                   class="d-flex align-items-center gap-2 text-decoration-none text-reset"
                                   title="View full stock history for this product">
                                    <span class="movement-thumb"><i class="ti ti-diamond"></i></span>
                                    <div class="fw-semibold">{{ $p->product_title }}</div>
                                </a>
                            </td>
                            <td class="text-end">{{ number_format((int) $p->pieces) }}</td>
                            <td class="text-end">{{ rtrim(rtrim(number_format((float) $p->carat_weight, 3), '0'), '.') }} ct</td>
                            <td class="text-end">{{ $rate !== null ? $settings->formatMoney($rate) . ' / ct' : '—' }}</td>
                            <td class="text-end fw-semibold">{{ $settings->formatMoney((float) $p->stock_value) }}</td>
                            <td class="text-center">
                                @if ((int) $p->pieces <= $lowStockThreshold)
                                    <span class="badge badge-soft-warning">Low Stock</span>
                                @else
                                    <span class="badge badge-soft-success">In Stock</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="ti ti-inbox d-block fs-2xl mb-2"></i>
                                No stock on hand for this stone yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->isNotEmpty())
        <div class="card-footer border-0">
            <small class="text-muted">
                <i class="ti ti-info-circle me-1"></i>
                Rate / Ct is approximate — a product's stock can come from more than one purchase at different
                prices, so this is stock value ÷ remaining carat weight, not a single quoted rate.
            </small>
        </div>
        @endif
    </div>

</div>

@endsection

@push('styles')
<style>
    /* Self-contained, like stock/product.blade.php — this is its own page
       (not a tab inside stock/index.blade.php), so it can't rely on that
       view's scoped .stock-page rules or CSS variables being present. */
    .stock-page {
        --stock-primary: #1d4ed8;
        --stock-primary-dark: #1e3a8a;
        --stock-primary-light: #e6f5f3;
        --stock-border: #e2e8f0;
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

    .stock-page .avatar.avatar-sm { width: 32px; height: 32px; display:inline-flex; align-items:center; justify-content:center; border-radius: .375rem; }

    .stock-page .movement-thumb {
        display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
        width: 32px; height: 32px; border-radius: 50%; background: var(--stock-primary-light); color: var(--stock-primary); font-size: 0.9rem;
    }

    .stock-page .stock-ledger-table thead th {
        background: #f1f5f9; font-weight: 700; font-size: 0.6875rem; letter-spacing: 0.03em; padding: 8px 12px;
    }
    .stock-page .stock-ledger-table tbody td {
        padding: 8px 12px; font-size: 0.75rem; vertical-align: middle;
        white-space: nowrap; max-width: 220px; overflow: hidden; text-overflow: ellipsis;
    }
    .stock-page .stock-ledger-table tbody tr:hover { background: #f8fafc; }
    .stock-page .stock-ledger-table td > a.text-reset:hover .fw-semibold { text-decoration: underline; }
</style>
@endpush
