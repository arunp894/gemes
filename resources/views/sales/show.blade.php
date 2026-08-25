@extends('layout.app')

@section('title', $sale->sale_number)

@section('content')
<div class="container-fluid" id="saleShowApp">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">
                Sale {{ $sale->sale_number }}
                <span class="badge {{ $sale->statusBadgeClass() }} ms-2">{{ $sale->statusLabel() }}</span>
                <span class="badge {{ $sale->paymentStatusBadgeClass() }}">{{ $sale->paymentStatusLabel() }}</span>
                @if ($sale->needsShipping())
                    <span class="badge {{ $sale->shippingStatusBadgeClass() }}">
                        <i class="ti ti-truck-delivery me-1"></i>{{ $sale->shippingStatusLabel() }}
                    </span>
                @endif
            </h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Sales</a></li>
                <li class="breadcrumb-item active">{{ $sale->sale_number }}</li>
            </ol>
        </div>
    </div>

    {{-- Actions bar --}}
    @if (session('error'))
        <div class="alert alert-warning alert-dismissible fade show">
            <i class="ti ti-alert-triangle me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body d-flex flex-wrap gap-2">
            @permission('sales.edit')
                @if (! $editBlockReason)
                    <a href="{{ route('sales.edit', $sale) }}" class="btn btn-primary">
                        <i class="ti ti-edit me-1"></i> Edit
                    </a>
                @else
                    <button class="btn btn-soft-secondary" disabled title="{{ $editBlockReason }}">
                        <i class="ti ti-edit-off me-1"></i> Edit
                    </button>
                @endif
            @endpermission

            @permission('sales.post')
                @if ($sale->isDraft())
                    <button class="btn btn-success js-status-action" data-url="{{ route('sales.post', $sale) }}"
                        data-confirm="Post this sale? Inventory will be deducted.">
                        <i class="ti ti-send me-1"></i> Post
                    </button>
                @endif
                @if ($sale->isPosted() && (float) $sale->balance_due <= 0)
                    <button class="btn btn-success js-status-action" data-url="{{ route('sales.complete', $sale) }}"
                        data-confirm="Mark this sale as completed?">
                        <i class="ti ti-check me-1"></i> Complete
                    </button>
                @endif
                @if (in_array($sale->status, ['posted', 'completed']))
                    <button class="btn btn-soft-warning js-status-action" data-url="{{ route('sales.refund', $sale) }}"
                        data-confirm="Refund this sale? Inventory adjustments will need to be made separately.">
                        <i class="ti ti-arrow-back-up me-1"></i> Refund
                    </button>
                @endif
                @if (in_array($sale->status, ['draft', 'posted']))
                    <button class="btn btn-soft-danger js-status-action" data-url="{{ route('sales.cancel', $sale) }}"
                        data-confirm="Cancel this sale?">
                        <i class="ti ti-ban me-1"></i> Cancel
                    </button>
                @endif

                @if ($sale->needsShipping())
                    <div class="d-flex align-items-center gap-1 border-start ps-2">
                        <select id="shippingStatusSelect" class="form-select form-select-sm">
                            @foreach (\App\Models\Sale::SHIPPING_STATUSES as $s)
                                <option value="{{ $s }}" @selected($sale->shipping_status === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-soft-primary btn-sm js-shipping-status"
                            data-url="{{ route('sales.shipping-status', [$sale, '__STATUS__']) }}">
                            <i class="ti ti-truck-delivery me-1"></i> Update
                        </button>
                        <button type="button" class="btn btn-soft-secondary btn-sm" data-bs-toggle="modal"
                            data-bs-target="#shippingDetailsModal">
                            <i class="ti ti-map-pin me-1"></i> Shipping Details
                        </button>
                    </div>
                @endif
            @endpermission

            <button class="btn btn-light ms-auto" onclick="window.print()">
                <i class="ti ti-printer me-1"></i> Print
            </button>
        </div>
    </div>

    <div class="row">
        {{-- ─── Invoice ─── --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <h6 class="text-muted text-uppercase small">Bill To</h6>
                            @if ($sale->customer)
                                <div class="fw-semibold">{{ $sale->customer->display_name }}</div>
                                <small class="text-muted d-block">{{ $sale->customer->customer_code }}</small>
                                @if ($sale->customer->phone)
                                    <small class="d-block">{{ $sale->customer->phone }}</small>
                                @endif
                                @if ($sale->customer->gst_number)
                                    <small class="d-block">GST: {{ $sale->customer->gst_number }}</small>
                                @endif
                                @php
                                    $addr = array_filter([
                                        $sale->customer->address_line1,
                                        $sale->customer->address_line2,
                                        trim(implode(', ', array_filter([$sale->customer->city, $sale->customer->state, $sale->customer->zip_code]))),
                                        $sale->customer->country,
                                    ]);
                                @endphp
                                @if (!empty($addr))
                                    <address class="mt-2 mb-0 small">
                                        @foreach ($addr as $line){{ $line }}<br>@endforeach
                                    </address>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                        <div class="col-6 text-end">
                            <h6 class="text-muted text-uppercase small">Invoice</h6>
                            <div><code class="fs-base">{{ $sale->sale_number }}</code></div>
                            <small class="d-block text-muted">Date: {{ optional($sale->sale_date)->format('d M Y') }}</small>
                            @if ($sale->location)
                                <small class="d-block text-muted">Location: {{ $sale->location->name }}</small>
                            @endif
                            @if ($sale->channel)
                                <small class="d-block text-muted">
                                    <i class="{{ $sale->channel->icon ?? 'ti ti-tag' }} me-1"></i>Channel: {{ $sale->channel->name }}
                                </small>
                            @endif
                            @if ($sale->salesperson)
                                <small class="d-block text-muted">Salesperson: {{ $sale->salesperson->name }}</small>
                            @endif
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-custom align-middle mb-0">
                            <thead class="bg-light bg-opacity-25 thead-sm">
                                <tr class="text-uppercase fs-xxs">
                                    <th>#</th>
                                    <th>Product</th>
                                    <th>Ct</th>
                                    <th>Barcode</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Disc</th>
                                    <th class="text-end">Tax</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sale->lines as $line)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $line->product->title ?? '—' }}</div>
                                            <small class="text-muted">SKU: {{ $line->product->sku ?? '—' }}</small>
                                            @if ($line->notes)
                                                <small class="d-block text-muted">{{ $line->notes }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $line->carat_weight ?? optional($line->purchaseProduct)->carat_weight ?? '—' }}</td>
                                        <td><code class="small">{{ $line->barcode ?? '—' }}</code></td>
                                        <td class="text-end">{{ $line->qty }}</td>
                                        <td class="text-end">{{ number_format((float) $line->unit_price, 2) }}</td>
                                        <td class="text-end">{{ number_format((float) $line->discount_amount, 2) }}</td>
                                        <td class="text-end">{{ number_format((float) $line->tax_amount, 2) }}</td>
                                        <td class="text-end fw-semibold">{{ number_format((float) $line->total, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center text-muted py-4">No line items.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if ($sale->note)
                <div class="card">
                    <div class="card-header border-light"><h5 class="card-title mb-0">Note</h5></div>
                    <div class="card-body">
                        <p class="mb-0" style="white-space: pre-wrap;">{{ $sale->note }}</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- ─── Sidebar: totals + payments ─── --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header border-light"><h5 class="card-title mb-0">Totals</h5></div>
                <div class="card-body">
                    @php $tb = $sale->tax_breakdown; @endphp
                    <dl class="row mb-0 small">
                        <dt class="col-7 text-muted">Subtotal</dt>
                        <dd class="col-5 text-end">{{ app(\App\Services\SettingService::class)->formatMoney($sale->subtotal) }}</dd>

                        <dt class="col-7 text-muted">Discount</dt>
                        <dd class="col-5 text-end">− {{ app(\App\Services\SettingService::class)->formatMoney($sale->discount_total) }}</dd>

                        @if ($sale->tax_type === 'cgst_sgst')
                            <dt class="col-7 text-muted">CGST</dt>
                            <dd class="col-5 text-end">+ {{ app(\App\Services\SettingService::class)->formatMoney($tb['cgst']) }}</dd>
                            <dt class="col-7 text-muted">SGST</dt>
                            <dd class="col-5 text-end">+ {{ app(\App\Services\SettingService::class)->formatMoney($tb['sgst']) }}</dd>
                        @elseif ($sale->tax_type === 'igst')
                            <dt class="col-7 text-muted">IGST</dt>
                            <dd class="col-5 text-end">+ {{ app(\App\Services\SettingService::class)->formatMoney($tb['igst']) }}</dd>
                        @endif

                        <dt class="col-7 text-muted">Shipping</dt>
                        <dd class="col-5 text-end">+ {{ app(\App\Services\SettingService::class)->formatMoney($sale->shipping_charge) }}</dd>

                        <dt class="col-7 fw-bold pt-2 border-top mt-2">Grand Total</dt>
                        <dd class="col-5 text-end fw-bold pt-2 border-top mt-2">{{ app(\App\Services\SettingService::class)->formatMoney($sale->grand_total) }}</dd>

                        <dt class="col-7 text-muted">Paid</dt>
                        <dd class="col-5 text-end">{{ app(\App\Services\SettingService::class)->formatMoney($sale->paid_amount) }}</dd>

                        <dt class="col-7 fw-semibold {{ (float) $sale->balance_due > 0 ? 'text-danger' : '' }}">Balance Due</dt>
                        <dd class="col-5 text-end fw-semibold {{ (float) $sale->balance_due > 0 ? 'text-danger' : '' }}">
                            {{ app(\App\Services\SettingService::class)->formatMoney($sale->balance_due) }}
                        </dd>
                    </dl>
                </div>
            </div>

            {{-- Payments list + add-payment form --}}
            <div class="card">
                <div class="card-header border-light"><h5 class="card-title mb-0">Payments</h5></div>
                <div class="card-body p-0">
                    @if ($sale->payments->isEmpty())
                        <p class="text-muted small text-center py-3 mb-0">No payments recorded.</p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach ($sale->payments as $p)
                                <li class="list-group-item d-flex justify-content-between align-items-center px-3">
                                    <div>
                                        <span class="badge {{ $p->methodBadgeClass() }} fs-xxs">{{ $p->methodLabel() }}</span>
                                        <small class="d-block text-muted mt-1">
                                            {{ optional($p->payment_date)->format('d M Y') }}
                                            @if ($p->reference_number) · Ref: {{ $p->reference_number }} @endif
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <strong class="{{ $p->isRefund() ? 'text-danger' : '' }}">
                                            {{ $p->isRefund() ? '−' : '' }}{{ number_format(abs((float) $p->amount), 2) }}
                                        </strong>
                                        @permission('sales.edit')
                                        @if (! in_array($sale->status, ['cancelled']))
                                            <button class="btn btn-default btn-icon btn-sm text-danger ms-1 js-remove-payment"
                                                data-url="{{ route('sales.payments.destroy', [$sale, $p]) }}"
                                                title="Remove">
                                                <i class="ti ti-x"></i>
                                            </button>
                                        @endif
                                        @endpermission
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                @permission('sales.edit')
                @if (in_array($sale->status, ['posted', 'completed', 'refunded']))
                <div class="card-footer border-top">
                    <h6 class="text-muted text-uppercase small mb-2">Add Payment</h6>
                    <form id="addPaymentForm" @submit.prevent="addPayment">
                        <div class="row g-2">
                            <div class="col-7">
                                <select class="form-select form-select-sm" v-model="payment.payment_method">
                                    @foreach ($paymentMethods as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-5">
                                <input type="number" step="0.01" class="form-control form-control-sm text-end"
                                    v-model.number="payment.amount" placeholder="Amount" required>
                            </div>
                            <div class="col-7">
                                <input type="date" class="form-control form-control-sm" v-model="payment.payment_date" required>
                            </div>
                            <div class="col-5">
                                <button class="btn btn-primary btn-sm w-100" :disabled="submitting">
                                    <span v-if="submitting" class="spinner-border spinner-border-sm"></span>
                                    <span v-else>Save</span>
                                </button>
                            </div>
                            <div class="col-12">
                                <input type="text" class="form-control form-control-sm" v-model="payment.reference_number"
                                    placeholder="Reference (optional)">
                            </div>
                        </div>
                        <div v-if="paymentError" class="alert alert-danger mt-2 mb-0 py-2 small">@{{ paymentError }}</div>
                    </form>
                </div>
                @endif
                @endpermission
            </div>

            {{-- Audit --}}
            <div class="card">
                <div class="card-header border-light"><h5 class="card-title mb-0">Audit</h5></div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Created</dt>
                        <dd class="col-7">{{ optional($sale->created_at)->format('d M Y, h:i A') }}</dd>
                        @if ($sale->creator)
                            <dt class="col-5 text-muted">Created by</dt>
                            <dd class="col-7">{{ $sale->creator->name }}</dd>
                        @endif
                        <dt class="col-5 text-muted">Updated</dt>
                        <dd class="col-7">{{ optional($sale->updated_at)->format('d M Y, h:i A') }}</dd>
                        @if ($sale->updater)
                            <dt class="col-5 text-muted">Updated by</dt>
                            <dd class="col-7">{{ $sale->updater->name }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Edit History --}}
            @if ($sale->editLogs->isNotEmpty())
            <div class="card">
                <div class="card-header border-light d-flex align-items-center gap-2">
                    <i class="ti ti-history text-muted"></i>
                    <h5 class="card-title mb-0">Edit History</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach ($sale->editLogs as $log)
                            <li class="list-group-item px-3 py-2">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-semibold small">{{ $log->editor->name ?? 'Unknown user' }}</span>
                                    <small class="text-muted">{{ optional($log->created_at)->format('d M Y, h:i A') }}</small>
                                </div>
                                @if (!empty($log->changes))
                                    <ul class="list-unstyled mb-0 mt-1 small text-muted">
                                        @foreach ($log->changes as $field => $diff)
                                            <li>
                                                <code>{{ $field }}</code>:
                                                <span class="text-decoration-line-through">{{ \Illuminate\Support\Str::limit((string) ($diff['from'] ?? '—'), 30) }}</span>
                                                <i class="ti ti-arrow-right mx-1"></i>
                                                <span>{{ \Illuminate\Support\Str::limit((string) ($diff['to'] ?? '—'), 30) }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <small class="text-muted">No field changes recorded.</small>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif
        </div>
    </div>

    @if ($sale->needsShipping())
        {{-- ==================== Shipping Details Modal ==================== --}}
        <div class="modal fade" id="shippingDetailsModal" tabindex="-1" aria-labelledby="shippingDetailsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="shippingDetailsModalLabel">
                            <i class="ti ti-map-pin me-1"></i> Shipping Details
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="shippingDetailsForm" novalidate>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <h6 class="text-muted text-uppercase fs-xxs mb-1">Delivery Address</h6>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Address Line 1</label>
                                    <input type="text" name="shipping_address_line1" class="form-control"
                                        value="{{ $sale->shipping_address_line1 ?? optional($sale->customer)->address_line1 }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Address Line 2</label>
                                    <input type="text" name="shipping_address_line2" class="form-control"
                                        value="{{ $sale->shipping_address_line2 ?? optional($sale->customer)->address_line2 }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">City</label>
                                    <input type="text" name="shipping_city" class="form-control"
                                        value="{{ $sale->shipping_city ?? optional($sale->customer)->city }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">State</label>
                                    <input type="text" name="shipping_state" class="form-control"
                                        value="{{ $sale->shipping_state ?? optional($sale->customer)->state }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">ZIP / Pincode</label>
                                    <input type="text" name="shipping_zip_code" class="form-control"
                                        value="{{ $sale->shipping_zip_code ?? optional($sale->customer)->zip_code }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Country</label>
                                    <input type="text" name="shipping_country" class="form-control"
                                        value="{{ $sale->shipping_country ?? optional($sale->customer)->country }}">
                                </div>

                                <div class="col-12 mt-4">
                                    <h6 class="text-muted text-uppercase fs-xxs mb-1">Tracking</h6>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Carrier / Courier</label>
                                    <input type="text" name="shipping_carrier" class="form-control"
                                        value="{{ $sale->shipping_carrier }}" placeholder="e.g. Blue Dart, DHL">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tracking Number</label>
                                    <input type="text" name="tracking_number" class="form-control"
                                        value="{{ $sale->tracking_number }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Shipped Date</label>
                                    <input type="date" name="shipped_at" class="form-control"
                                        value="{{ optional($sale->shipped_at)->toDateString() }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Estimated Delivery Date</label>
                                    <input type="date" name="estimated_delivery_date" class="form-control"
                                        value="{{ optional($sale->estimated_delivery_date)->toDateString() }}">
                                </div>
                            </div>
                            <div id="shippingDetailsError" class="alert alert-danger mt-3 py-2 mb-0 d-none"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="shippingDetailsSubmit">
                                <i class="ti ti-device-floppy me-1"></i> Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // Status action buttons (post / complete / refund / cancel)
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

    $('.js-shipping-status').on('click', function () {
        const status = $('#shippingStatusSelect').val();
        const url = $(this).data('url').replace('__STATUS__', status);

        $.ajax({
            url, type: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: function (res) {
                if (res.ok) window.location.reload();
                else alert(res.message || 'Update failed.');
            },
            error: function (xhr) {
                alert((xhr.responseJSON && xhr.responseJSON.message) || 'Update failed.');
            },
        });
    });

    $('#shippingDetailsForm').on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#shippingDetailsSubmit');
        const $err = $('#shippingDetailsError').addClass('d-none').text('');

        $btn.prop('disabled', true);
        $.ajax({
            url: @json(route('sales.shipping-details', $sale)),
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            data: $(this).serialize(),
            success: function (res) {
                if (res.ok) window.location.reload();
                else $err.removeClass('d-none').text(res.message || 'Update failed.');
            },
            error: function (xhr) {
                const errors = xhr.responseJSON && xhr.responseJSON.errors;
                const message = errors
                    ? Object.values(errors).flat().join(' ')
                    : ((xhr.responseJSON && xhr.responseJSON.message) || 'Update failed.');
                $err.removeClass('d-none').text(message);
            },
            complete: function () { $btn.prop('disabled', false); },
        });
    });

    $('.js-remove-payment').on('click', function () {
        if (!confirm('Remove this payment?')) return;
        const url = $(this).data('url');
        $.ajax({
            url, type: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: function (res) {
                if (res.ok) window.location.reload();
                else alert(res.message || 'Failed.');
            },
            error: function (xhr) { alert((xhr.responseJSON && xhr.responseJSON.message) || 'Failed.'); },
        });
    });

    // Vue app for the small "add payment" form in the sidebar
    if (document.getElementById('addPaymentForm')) {
        new Vue({
            el: '#saleShowApp',
            data: {
                payment: {
                    payment_date:     '{{ now()->toDateString() }}',
                    amount:           Number(@json((float) $sale->balance_due)).toFixed(2),
                    payment_method:   'cash',
                    reference_number: '',
                },
                submitting: false,
                paymentError: null,
            },
            methods: {
                async addPayment() {
                    this.paymentError = null;
                    this.submitting = true;
                    try {
                        const res = await fetch('{{ route('sales.payments.store', $sale) }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                payment_date:     this.payment.payment_date,
                                amount:           Number(this.payment.amount),
                                payment_method:   this.payment.payment_method,
                                reference_number: this.payment.reference_number || null,
                            }),
                        });
                        if (res.status === 422) {
                            const data = await res.json();
                            this.paymentError = data.message || 'Please check the amount and method.';
                            this.submitting = false;
                            return;
                        }
                        if (!res.ok) {
                            const data = await res.json().catch(() => ({}));
                            this.paymentError = data.message || 'Failed.';
                            this.submitting = false;
                            return;
                        }
                        window.location.reload();
                    } catch (err) {
                        this.paymentError = 'Network error. Please try again.';
                        this.submitting = false;
                    }
                },
            },
        });
    }
});
</script>
<style>
    @media print {
        .breadcrumb, .page-title-head .text-end,
        .js-status-action, .js-remove-payment,
        #addPaymentForm, .btn { display: none !important; }
    }
</style>
@endpush
