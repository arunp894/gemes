@extends('layout.app')

@section('title', $packing->packing_number)

@section('content')
<div class="container-fluid" id="packingShowApp">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">
                {{ $packing->packing_number }}
                <span class="badge {{ $packing->statusBadgeClass() }} ms-2">{{ $packing->statusLabel() }}</span>
            </h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('packings.index') }}">Packings</a></li>
                <li class="breadcrumb-item active">{{ $packing->packing_number }}</li>
            </ol>
        </div>
    </div>

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Actions --}}
    <div class="card">
        <div class="card-body d-flex flex-wrap gap-2">
            @permission('packings.edit')
                @if ($packing->isEditable())
                    <a href="{{ route('packings.edit', $packing) }}" class="btn btn-primary">
                        <i class="ti ti-edit me-1"></i> Edit
                    </a>
                @endif
            @endpermission

            @permission('packings.post')
                @if ($packing->isDraft())
                    <button class="btn btn-info js-status-action"
                        data-url="{{ route('packings.post', $packing) }}"
                        data-confirm="Post this packing? The raw stock will be consumed and the new products credited to stock.">
                        <i class="ti ti-send me-1"></i> Post
                    </button>
                @endif
            @endpermission

            @permission('packings.delete')
                @if (! $packing->isCancelled())
                    <button class="btn btn-soft-danger js-status-action"
                        data-url="{{ route('packings.cancel', $packing) }}"
                        data-confirm="{{ $packing->isDraft() ? 'Delete this draft packing?' : 'Cancel this packing? Raw stock will be restored and the packed products removed from stock.' }}">
                        <i class="ti ti-ban me-1"></i> {{ $packing->isDraft() ? 'Delete' : 'Cancel' }}
                    </button>
                @endif
            @endpermission

            @if ($packing->outputs->isNotEmpty())
                <button class="btn btn-light ms-auto" onclick="printPackingLabels()">
                    <i class="ti ti-printer me-1"></i> Print Labels
                </button>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">

            {{-- Sources --}}
            <div class="card">
                <div class="card-header border-light"><h5 class="card-title mb-0">Raw Stock Consumed</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-custom align-middle mb-0">
                            <thead class="bg-light bg-opacity-25 thead-sm">
                                <tr class="text-uppercase fs-xxs">
                                    <th>#</th><th>Lot Code</th><th>Category</th><th>Stone</th><th class="text-end">Qty Taken</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($packing->sources as $source)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <code class="small">
                                                <a href="{{ route('stock.piece', $source->purchase_product_id) }}">
                                                    {{ optional($source->purchaseProduct)->lot_code ?? '#' . $source->purchase_product_id }}
                                                </a>
                                            </code>
                                        </td>
                                        <td>{{ optional(optional($source->purchaseProduct)->line)->category->name ?? '—' }}</td>
                                        <td>{{ optional(optional($source->purchaseProduct)->line)->stone_type ?? '—' }}</td>
                                        <td class="text-end fw-semibold">{{ (int) $source->qty_taken }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">No source stock recorded.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Outputs --}}
            <div class="card">
                <div class="card-header border-light"><h5 class="card-title mb-0">Products Created</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-custom align-middle mb-0">
                            <thead class="bg-light bg-opacity-25 thead-sm">
                                <tr class="text-uppercase fs-xxs">
                                    <th style="width:1%"><input type="checkbox" id="selectAllOutputs"></th>
                                    <th>Product</th><th>Lot Code</th><th class="text-end">Qty</th>
                                    <th class="text-end">Cost</th><th class="text-end">Selling Price</th><th>Website</th><th style="width:80px">Copies</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($packing->outputs as $output)
                                    <tr>
                                        <td><input type="checkbox" class="js-output-check" value="{{ $output->id }}" checked></td>
                                        <td>
                                            @if ($output->product)
                                                <div class="fw-semibold">
                                                    <a href="{{ route('products.show', $output->product) }}">{{ $output->product->title }}</a>
                                                </div>
                                                <small class="text-muted">SKU: {{ $output->product->sku }}</small>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td><code class="small">{{ $output->lot_code }}</code></td>
                                        <td class="text-end fw-semibold">{{ (int) $output->qty }}</td>
                                        <td class="text-end">{{ number_format((float) $output->price, 2) }}</td>
                                        <td class="text-end">
                                            @if ($output->website_price !== null)
                                                {{ number_format((float) $output->website_price, 2) }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($output->product && $output->product->website_enabled)
                                                <span class="badge badge-soft-info">Enabled</span>
                                            @else
                                                <span class="badge badge-soft-secondary">Disabled</span>
                                            @endif
                                        </td>
                                        <td>
                                            <input type="number" min="1" max="100" value="1" class="form-control form-control-sm js-output-copies">
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center text-muted py-4">No products created yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if ($packing->note)
                <div class="card">
                    <div class="card-header border-light"><h5 class="card-title mb-0">Note</h5></div>
                    <div class="card-body"><p class="mb-0" style="white-space: pre-wrap;">{{ $packing->note }}</p></div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header border-light"><h5 class="card-title mb-0">Timeline</h5></div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Packing Date</dt>
                        <dd class="col-7">{{ optional($packing->packing_date)->format('d M Y') }}</dd>

                        <dt class="col-5 text-muted">Location</dt>
                        <dd class="col-7">{{ optional($packing->location)->name ?? '—' }}</dd>

                        <dt class="col-5 text-muted">Created</dt>
                        <dd class="col-7">{{ optional($packing->created_at)->format('d M Y, h:i A') }}</dd>

                        @if ($packing->posted_at)
                            <dt class="col-5 text-muted">Posted</dt>
                            <dd class="col-7">{{ $packing->posted_at->format('d M Y, h:i A') }}</dd>
                        @endif

                        @if ($packing->cancelled_at)
                            <dt class="col-5 text-muted">Cancelled</dt>
                            <dd class="col-7">{{ $packing->cancelled_at->format('d M Y, h:i A') }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-light"><h5 class="card-title mb-0">Summary</h5></div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-7 text-muted">Raw pieces consumed</dt>
                        <dd class="col-5 text-end">{{ (int) $packing->sources->sum('qty_taken') }}</dd>
                        <dt class="col-7 text-muted">Products created</dt>
                        <dd class="col-5 text-end">{{ $packing->outputs->count() }}</dd>
                        <dt class="col-7 text-muted">Total output qty</dt>
                        <dd class="col-5 text-end">{{ (int) $packing->outputs->sum('qty') }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-light"><h5 class="card-title mb-0">Audit</h5></div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        @if ($packing->creator)
                            <dt class="col-5 text-muted">Created by</dt>
                            <dd class="col-7">{{ $packing->creator->name }}</dd>
                        @endif
                        @if ($packing->updater)
                            <dt class="col-5 text-muted">Last update by</dt>
                            <dd class="col-7">{{ $packing->updater->name }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    $('.js-status-action').on('click', function () {
        const url     = $(this).data('url');
        const confirm = $(this).data('confirm');
        if (confirm && !window.confirm(confirm)) return;
        $.ajax({
            url, type: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: function (res) {
                if (res.ok) window.location.reload();
                else alert(res.message || 'Action failed.');
            },
            error: function (xhr) {
                alert((xhr.responseJSON && xhr.responseJSON.message) || 'Action failed.');
            },
        });
    });

    $('#selectAllOutputs').on('change', function () {
        $('.js-output-check').prop('checked', this.checked);
    });
});

function printPackingLabels() {
    const params = new URLSearchParams();
    document.querySelectorAll('.js-output-check:checked').forEach((cb, idx) => {
        const row = cb.closest('tr');
        const copies = row.querySelector('.js-output-copies').value || 1;
        params.append(`items[${idx}][id]`, cb.value);
        params.append(`items[${idx}][copies]`, copies);
    });
    if (!params.toString()) { alert('Select at least one product to print.'); return; }
    window.open("{{ route('packings.print-labels', $packing) }}?" + params.toString(), '_blank');
}
</script>
@endpush
@endsection
