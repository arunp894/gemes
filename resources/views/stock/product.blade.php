@extends('layout.app')

@section('title', 'Stock Ledger — ' . $product->title)

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">
                Ledger — {{ $product->title }}
                <small class="text-muted fs-sm">SKU: {{ $product->sku }}</small>
            </h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('stock.index') }}">Stock</a></li>
                <li class="breadcrumb-item active">{{ $product->title }}</li>
            </ol>
        </div>
    </div>

    <div class="card">
        <div class="card-body d-flex flex-wrap align-items-end gap-2">
            <div>
                <label class="form-label small mb-1">Location</label>
                <form method="GET" action="{{ route('stock.product', $product) }}" id="locFilter">
                    <select name="location_id" class="form-select" onchange="document.getElementById('locFilter').submit()">
                        <option value="">All locations</option>
                        @foreach ($locations as $l)
                            <option value="{{ $l->id }}" @if($locationId == $l->id) selected @endif>
                                {{ $l->name }} ({{ $l->location_code }})
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            @if ($onHand !== null)
                <div class="ms-auto text-end">
                    <small class="d-block text-muted">On hand at this location</small>
                    <h3 class="mb-0 {{ $onHand <= 0 ? 'text-danger' : '' }}">{{ (int) $onHand }}</h3>
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header border-light">
            <h5 class="card-title mb-0">Movements</h5>
        </div>

        <div class="table-responsive">
            <table class="table table-custom table-centered align-middle mb-0">
                <thead class="bg-light bg-opacity-25 thead-sm">
                    <tr class="text-uppercase fs-xxs">
                        <th>Date</th>
                        <th>Reason</th>
                        <th>Source</th>
                        <th>Piece</th>
                        <th>Location</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Balance After</th>
                        <th>Notes</th>
                        <th>By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php $m = $row['movement']; @endphp
                        <tr>
                            <td>{{ optional($m->movement_date)->format('d M Y') }}</td>
                            <td><span class="badge {{ $m->reasonBadgeClass() }} fs-xxs">{{ $m->reasonLabel() }}</span></td>
                            <td>
                                @if ($m->source_type && $m->source_id)
                                    <small class="text-muted">{{ str_replace('_', ' ', $m->source_type) }}</small>
                                    <code class="d-block fs-xs">#{{ $m->source_id }}</code>
                                @else — @endif
                            </td>
                            <td>
                                <code class="small">
                                    <a href="{{ route('stock.piece', $m->purchase_product_id) }}">#{{ $m->purchase_product_id }}</a>
                                </code>
                                @if ($m->purchaseProduct && $m->purchaseProduct->barcode)
                                    <small class="d-block text-muted">{{ $m->purchaseProduct->barcode }}</small>
                                @endif
                            </td>
                            <td>{{ optional($m->location)->name ?? '—' }}</td>
                            <td class="text-end {{ $m->isIn() ? 'text-success' : 'text-danger' }} fw-semibold">
                                {{ $m->isIn() ? '+' : '−' }}{{ (int) $m->qty }}
                            </td>
                            <td class="text-end fw-semibold">{{ (int) $row['balance_after'] }}</td>
                            <td><small>{{ $m->notes }}</small></td>
                            <td><small>{{ optional($m->creator)->name ?? '—' }}</small></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No movements yet for this product.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection
