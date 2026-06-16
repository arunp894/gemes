@extends('layout.app')

@section('title', 'Piece #' . $purchaseProduct->id)

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1 d-flex align-items-center gap-3">
            <a onclick="history.back()" class="btn btn-default btn-icon btn-sm" title="Go back" style="cursor:pointer">
                <i class="ti ti-arrow-left fs-md"></i>
            </a>
            <div>
                <h4 class="page-main-title m-0">
                    Piece <code>#{{ $purchaseProduct->id }}</code>
                    @if ($purchaseProduct->line && $purchaseProduct->line->product)
                        <span class="text-muted fw-normal fs-sm ms-1">— {{ $purchaseProduct->line->product->title }}</span>
                    @endif
                </h4>
                <small class="text-muted">Per-piece movement history</small>
            </div>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('stock.index') }}">Stock</a></li>
                @if ($purchaseProduct->line && $purchaseProduct->line->product)
                    <li class="breadcrumb-item">
                        <a href="{{ route('stock.product', $purchaseProduct->line->product) }}">
                            {{ Str::limit($purchaseProduct->line->product->title, 20) }}
                        </a>
                    </li>
                @endif
                <li class="breadcrumb-item active">Piece #{{ $purchaseProduct->id }}</li>
            </ol>
        </div>
    </div>

    <div class="row">

        {{-- Left Panel --}}
        <div class="col-lg-3">

            {{-- Piece Details --}}
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-gem me-2 text-primary"></i>Piece Details
                    </h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        @if ($purchaseProduct->line && $purchaseProduct->line->product)
                            <dt class="col-5 text-muted">Product</dt>
                            <dd class="col-7">
                                <a href="{{ route('products.show', $purchaseProduct->line->product) }}" class="text-primary">
                                    {{ $purchaseProduct->line->product->title }}
                                </a>
                            </dd>
                            <dt class="col-5 text-muted">SKU</dt>
                            <dd class="col-7"><code>{{ $purchaseProduct->line->product->sku }}</code></dd>
                        @endif
                        <dt class="col-5 text-muted">Barcode</dt>
                        <dd class="col-7"><code>{{ $purchaseProduct->barcode ?? '—' }}</code></dd>
                        <dt class="col-5 text-muted">Cost</dt>
                        <dd class="col-7">{{ number_format((float) $purchaseProduct->price, 2) }}</dd>
                        @if ($purchaseProduct->line && $purchaseProduct->line->purchase)
                            <dt class="col-5 text-muted">Purchase</dt>
                            <dd class="col-7">
                                <a href="{{ route('purchases.show', $purchaseProduct->line->purchase) }}" class="d-inline-flex align-items-center gap-1 text-decoration-none">
                                    <span class="badge badge-soft-success d-inline-flex align-items-center gap-1">
                                        <i class="ti ti-shopping-cart fs-xs"></i>
                                        {{ $purchaseProduct->line->purchase->invoice_number }}
                                    </span>
                                    <i class="ti ti-external-link fs-xxs text-muted"></i>
                                </a>
                            </dd>
                        @endif
                        @if ($purchaseProduct->expiry_date)
                            <dt class="col-5 text-muted">Expiry</dt>
                            <dd class="col-7">{{ $purchaseProduct->expiry_date->format('d M Y') }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Where It Is Now --}}
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-map-pin me-2 text-info"></i>Where It Is Now
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if (empty($byLocation))
                        <p class="text-muted small mb-0 p-3">
                            <i class="ti ti-info-circle me-1"></i>No stock on hand for this piece.
                        </p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach ($byLocation as $locId => $balance)
                                @php $loc = \App\Models\Location::find($locId); @endphp
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold small">{{ $loc ? $loc->name : 'Location #'.$locId }}</div>
                                        @if($loc)<small class="text-muted">{{ $loc->location_code }}</small>@endif
                                    </div>
                                    <span class="badge {{ $balance <= 0 ? 'badge-soft-danger' : 'badge-soft-success' }} fs-sm">
                                        {{ (int) $balance }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

        </div>

        {{-- Right Panel: Movement History --}}
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Movement History</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom table-centered align-middle mb-0">
                        <thead class="bg-light bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th>Date</th>
                                <th>Direction</th>
                                <th>Reason</th>
                                <th style="min-width:180px">Source Document</th>
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
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold small">{{ optional($m->movement_date)->format('d M Y') }}</div>
                                        @if($m->notes)
                                            <small class="text-muted d-block" title="{{ $m->notes }}">
                                                {{ Str::limit($m->notes, 35) }}
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
                                    <td>{{ optional($m->location)->name ?? '—' }}</td>
                                    <td class="text-end {{ $m->isIn() ? 'text-success' : 'text-danger' }} fw-semibold">
                                        {{ $m->isIn() ? '+' : '−' }}{{ (int) $m->qty }}
                                    </td>
                                    <td class="text-end fw-semibold">{{ (int) $row['balance_after'] }}</td>
                                    <td><small class="text-muted">{{ optional($m->creator)->name ?? '—' }}</small></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="ti ti-inbox d-block fs-2xl mb-2"></i>
                                        No movements recorded for this piece.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</div>

@endsection

@push('styles')
<style>
.src-link { transition: opacity .15s; }
.src-link:hover { opacity: .8; }
</style>
@endpush
