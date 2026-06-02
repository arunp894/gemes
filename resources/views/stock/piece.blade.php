@extends('layout.app')

@section('title', 'Piece #' . $purchaseProduct->id)

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">
                Piece #{{ $purchaseProduct->id }}
                @if ($purchaseProduct->line && $purchaseProduct->line->product)
                    — {{ $purchaseProduct->line->product->title }}
                @endif
            </h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('stock.index') }}">Stock</a></li>
                <li class="breadcrumb-item active">Piece #{{ $purchaseProduct->id }}</li>
            </ol>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header border-light"><h5 class="card-title mb-0">Piece Details</h5></div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        @if ($purchaseProduct->line && $purchaseProduct->line->product)
                            <dt class="col-5 text-muted">Product</dt>
                            <dd class="col-7">{{ $purchaseProduct->line->product->title }}</dd>
                            <dt class="col-5 text-muted">SKU</dt>
                            <dd class="col-7">{{ $purchaseProduct->line->product->sku }}</dd>
                        @endif
                        <dt class="col-5 text-muted">Barcode</dt>
                        <dd class="col-7"><code>{{ $purchaseProduct->barcode ?? '—' }}</code></dd>
                        <dt class="col-5 text-muted">Cost</dt>
                        <dd class="col-7">{{ number_format((float) $purchaseProduct->price, 2) }}</dd>
                        @if ($purchaseProduct->line && $purchaseProduct->line->purchase)
                            <dt class="col-5 text-muted">From Purchase</dt>
                            <dd class="col-7">
                                <a href="{{ route('purchases.show', $purchaseProduct->line->purchase) }}">
                                    <code>{{ $purchaseProduct->line->purchase->invoice_number }}</code>
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

            <div class="card">
                <div class="card-header border-light"><h5 class="card-title mb-0">Where It Is Now</h5></div>
                <div class="card-body">
                    @if (empty($byLocation))
                        <p class="text-muted small mb-0">No stock on hand for this piece.</p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach ($byLocation as $locId => $balance)
                                @php $loc = \App\Models\Location::find($locId); @endphp
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span>{{ $loc ? $loc->name : 'Location #' . $locId }}</span>
                                    <span class="fw-semibold {{ $balance <= 0 ? 'text-danger' : 'text-success' }}">
                                        {{ (int) $balance }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-light"><h5 class="card-title mb-0">Movement History</h5></div>
                <div class="table-responsive">
                    <table class="table table-custom table-centered align-middle mb-0">
                        <thead class="bg-light bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th>Date</th>
                                <th>Reason</th>
                                <th>Location</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Balance</th>
                                <th>Source</th>
                                <th>By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                @php $m = $row['movement']; @endphp
                                <tr>
                                    <td>{{ optional($m->movement_date)->format('d M Y') }}</td>
                                    <td><span class="badge {{ $m->reasonBadgeClass() }} fs-xxs">{{ $m->reasonLabel() }}</span></td>
                                    <td>{{ optional($m->location)->name ?? '—' }}</td>
                                    <td class="text-end {{ $m->isIn() ? 'text-success' : 'text-danger' }} fw-semibold">
                                        {{ $m->isIn() ? '+' : '−' }}{{ (int) $m->qty }}
                                    </td>
                                    <td class="text-end fw-semibold">{{ (int) $row['balance_after'] }}</td>
                                    <td>
                                        @if ($m->source_type)
                                            <small class="text-muted">{{ str_replace('_', ' ', $m->source_type) }} #{{ $m->source_id }}</small>
                                        @else — @endif
                                    </td>
                                    <td><small>{{ optional($m->creator)->name ?? '—' }}</small></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">No movements recorded for this piece.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection
