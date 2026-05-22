@extends('layout.app')

@section('title', 'Purchase ' . $purchase->invoice_number)

@section('content')
<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">
                Purchase
                <span class="badge bg-soft-primary text-primary ms-2">{{ $purchase->invoice_number }}</span>
                <span class="badge {{ $purchase->statusBadgeClass() }} ms-1">{{ $purchase->statusLabel() }}</span>
            </h4>
        </div>
        <div class="text-end d-flex gap-1 align-items-center">
            @permission('purchases.edit')
                @if ($purchase->isDraft())
                    <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-soft-primary btn-sm">
                        <i class="ti ti-edit me-1"></i> Edit
                    </a>
                @endif
            @endpermission

            @permission('purchases.post')
                @if ($purchase->isDraft())
                    <button type="button" class="btn btn-soft-success btn-sm" id="postBtn" data-id="{{ $purchase->id }}">
                        <i class="ti ti-check me-1"></i> Post
                    </button>
                @endif
            @endpermission

            <a href="{{ route('purchases.index') }}" class="btn btn-light btn-sm">
                <i class="ti ti-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row g-3">

        {{-- ─── Left: invoice ─── --}}
        <div class="col-xl-9">
            <div class="card">
                <div class="card-body">

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted text-uppercase fs-xxs mb-1">Supplier</h6>
                            <strong>{{ $purchase->supplier?->company_name ?: $purchase->supplier?->name }}</strong><br>
                            <small class="text-muted">{{ $purchase->supplier?->supplier_code }}</small>
                            @if ($purchase->supplier?->gst_number)
                                <br><small class="text-muted">GST: {{ $purchase->supplier->gst_number }}</small>
                            @endif
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h6 class="text-muted text-uppercase fs-xxs mb-1">Purchase Date</h6>
                            <strong>{{ $purchase->purchase_date?->format('d M Y') }}</strong><br>
                            <small class="text-muted">Tax: {{ strtoupper($purchase->tax_type) }}</small>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="bg-light bg-opacity-25 text-uppercase fs-xxs">
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th>Type</th>
                                    <th>Pack Qty</th>
                                    <th>Qty</th>
                                    <th>Barcode</th>
                                    <th>Rack</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Tax</th>
                                    <th class="text-end">Disc</th>
                                    {{-- <th>Expiry</th> --}}
                                    <th class="text-end">Net</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($purchase->lines as $i => $line)
                                    {{-- Parent row --}}
                                    <tr class="table-light">
                                        <td class="fw-semibold">{{ $i + 1 }}</td>
                                        <td colspan="2">
                                            <div class="fw-semibold">{{ $line->product?->title }}</div>
                                            <small class="text-muted">SKU: {{ $line->product?->sku }}</small>
                                        </td>
                                        <td>
                                            <strong>{{ $line->package_qty }}</strong>
                                            <small class="text-muted">{{ $line->package_name }}</small>
                                        </td>
                                        <td colspan="6"></td>
                                        <td class="text-end fw-bold">{{ number_format((float) $line->total, 2) }}</td>
                                    </tr>

                                    {{-- Child rows (one per inventory unit) --}}
                                    @foreach ($line->rows as $ri => $row)
                                        <tr>
                                            <td></td>
                                            <td class="ps-4 text-muted small">
                                                <i class="ti ti-corner-down-right me-1"></i>
                                                {{ $line->package_name }} #{{ $ri + 1 }}
                                            </td>
                                            <td colspan="2"></td>
                                            <td>{{ $row->qty }}</td>
                                            <td><code class="small">{{ $row->barcode ?: '—' }}</code></td>
                                            <td>{{ $row->rack?->code ?: '—' }}</td>
                                            <td class="text-end">{{ number_format((float) $row->price, 2) }}</td>
                                            <td class="text-end small">{{ rtrim(rtrim(number_format((float) $row->tax_percent, 2), '0'), '.') }}%</td>
                                            <td class="text-end small">{{ rtrim(rtrim(number_format((float) $row->discount_percent, 2), '0'), '.') }}%</td>
                                            {{-- <td>{{ optional($row->expiry_date)->format('d M Y') ?: '—' }}</td> --}}
                                            <td class="text-end">{{ number_format($row->net(), 2) }}</td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($purchase->note)
                        <div class="alert alert-info mt-3 mb-0">
                            <strong>Notes:</strong> {{ $purchase->note }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ─── Right: summary ─── --}}
        <div class="col-xl-3">
            <div class="card position-sticky" style="top: 1rem;">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Summary</h5>
                </div>
                <div class="card-body">

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal</span>
                        <span>{{ number_format((float) $purchase->subtotal, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Discount</span>
                        <span class="text-danger">- {{ number_format((float) $purchase->discount_total, 2) }}</span>
                    </div>

                    @php $tax = $purchase->tax_breakdown; @endphp
                    @if ($purchase->tax_type === \App\Models\Purchase::TAX_CGST_SGST)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">CGST</span>
                            <span>{{ number_format($tax['cgst'], 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">SGST</span>
                            <span>{{ number_format($tax['sgst'], 2) }}</span>
                        </div>
                    @elseif ($purchase->tax_type === \App\Models\Purchase::TAX_IGST)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">IGST</span>
                            <span>{{ number_format($tax['igst'], 2) }}</span>
                        </div>
                    @else
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Tax</span>
                            <span>{{ number_format((float) $purchase->tax_total, 2) }}</span>
                        </div>
                    @endif

                    <hr class="my-2">

                    <div class="d-flex justify-content-between mb-3">
                        <span class="fw-semibold">Grand Total</span>
                        <span class="fw-bold fs-18 text-primary">{{ number_format((float) $purchase->grand_total, 2) }}</span>
                    </div>

                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Paid</span>
                        <span>{{ number_format((float) $purchase->paid_amount, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 small">
                        <span class="text-muted">Due</span>
                        <span class="fw-semibold {{ $purchase->due_amount > 0 ? 'text-warning' : 'text-success' }}">
                            {{ number_format((float) $purchase->due_amount, 2) }}
                        </span>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const btn = document.getElementById('postBtn');
    if (!btn) return;
    btn.addEventListener('click', function () {
        if (!confirm('Post this purchase? Posted purchases cannot be edited.')) return;
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        fetch(`/purchases/${btn.dataset.id}/post`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        }).then(r => r.json()).then(() => window.location.reload());
    });
})();
</script>
@endpush
@endsection
