@extends('layout.app')

@section('title', 'Purchase ' . $purchase->invoice_number)

@section('content')

@push('styles')
<style>
    /* ==========================================================
       Purchase show/view page — compact ERP styling
       Scoped under .purchases-show-page so nothing here leaks into
       other pages that share the same layout/theme classes.
       ========================================================== */
    .purchases-show-page {
        --psp-primary: #1d4ed8;
        --psp-primary-dark: #1e3a8a;
        --psp-success: #059669;
        --psp-warning: #d97706;
        --psp-danger: #dc2626;
        --psp-border: #e2e8f0;
        --psp-text-muted: #64748b;
        padding-top: 0;
        padding-bottom: 20px;
    }
    .purchases-show-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid var(--psp-border);
        flex-wrap: wrap;
        row-gap: 8px;
    }
    .purchases-show-page .page-main-title {
        font-size: 1.25rem;
        font-weight: 700;
        position: relative;
        padding-left: 12px;
    }
    .purchases-show-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0; top: 2px; bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, var(--psp-primary-dark), var(--psp-primary));
    }
    .purchases-show-page .card { border-radius: 10px; box-shadow: none; border: 1px solid var(--psp-border); }
    .purchases-show-page .card-header { padding: 10px 16px; }
    .purchases-show-page .card-title { font-size: 0.9375rem; font-weight: 700; }

    /* Status pill (3-state, shared meaning with the index/create pages) */
    .purchases-show-page .status-pill {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 600;
        vertical-align: middle;
    }
    .purchases-show-page .status-pill .status-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
    .purchases-show-page .status-draft     { background: #fffbeb; color: var(--psp-warning); }
    .purchases-show-page .status-draft .status-dot     { background: var(--psp-warning); }
    .purchases-show-page .status-posted    { background: #ecfdf5; color: var(--psp-success); }
    .purchases-show-page .status-posted .status-dot    { background: var(--psp-success); }
    .purchases-show-page .status-cancelled { background: #fef2f2; color: var(--psp-danger); }
    .purchases-show-page .status-cancelled .status-dot { background: var(--psp-danger); }

    /* Action buttons (payment remove) */
    .purchases-show-page .action-btn {
        width: 28px; height: 28px; border-radius: 7px;
        display: inline-flex; align-items: center; justify-content: center;
        transition: all 0.2s ease; text-decoration: none; border: 1px solid transparent;
    }
    .purchases-show-page .action-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,0.08); }
    .purchases-show-page .action-delete { color: #dc2626; background: #fef2f2; border-color: #fecaca; }

    /* Confirmation modal icon */
    .confirm-modal-icon {
        width: 56px; height: 56px; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem;
    }
    .confirm-modal-icon-success { background: #ecfdf5; color: var(--psp-success); }
    .confirm-modal-icon-danger  { background: #fef2f2; color: var(--psp-danger); }
</style>
@endpush

<div class="container-fluid purchases-page purchases-show-page">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">
                Purchase
                <span class="badge badge-soft-primary ms-2">{{ $purchase->invoice_number }}</span>
                @php
                    $statusPillClass = match ($purchase->status) {
                        'posted'    => 'status-pill status-posted',
                        'cancelled' => 'status-pill status-cancelled',
                        default     => 'status-pill status-draft',
                    };
                    $paymentPillClass = match ($purchase->payment_status) {
                        'paid'    => 'status-pill status-posted',
                        'partial' => 'status-pill status-draft',
                        default   => 'status-pill status-cancelled',
                    };
                @endphp
                <span class="{{ $statusPillClass }} ms-1"><span class="status-dot"></span>{{ $purchase->statusLabel() }}</span>
                <span class="{{ $paymentPillClass }} ms-1"><span class="status-dot"></span>{{ $purchase->paymentStatusLabel() }}</span>
            </h4>
        </div>
        <div class="text-end d-flex gap-1 align-items-center">
            <a href="{{ route('purchases.invoice', $purchase) }}" class="btn btn-soft-primary btn-sm" target="_blank">
                <i class="ti ti-file-invoice me-1"></i> Invoice
            </a>

            @permission('purchases.edit')
                @if (! $editBlockReason)
                    <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-primary btn-sm">
                        <i class="ti ti-edit me-1"></i> Edit
                    </a>
                @endif
            @endpermission

            @permission('purchases.post')
                @if ($purchase->isDraft())
                    <button type="button" class="btn btn-success btn-sm" id="postBtn" data-id="{{ $purchase->id }}">
                        <i class="ti ti-check me-1"></i> Post
                    </button>
                @endif
            @endpermission

            <a href="{{ route('purchases.index') }}" class="btn btn-secondary btn-sm">
                <i class="ti ti-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="toast-container position-fixed top-0 end-0 p-3" id="purchaseShowToastContainer" style="z-index: 1080;"></div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @permission('purchases.edit')
        @if ($editBlockReason && ! $purchase->isCancelled())
            <div class="alert alert-warning d-flex align-items-center gap-2" role="alert">
                <i class="ti ti-lock fs-lg"></i>
                <div><strong>Editing locked:</strong> {{ $editBlockReason }}</div>
            </div>
        @endif
    @endpermission

    <div class="row g-3">

        {{-- ─── Left: invoice ─── --}}
        <div class="col-xl-10">
            <div class="card">
                <div class="card-header border-light d-flex align-items-center gap-2">
                    <i class="ti ti-list-details fs-18 text-primary"></i>
                    <h5 class="card-title mb-0">Purchase Items</h5>
                </div>
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
                        <div class="col-md-3">
                            <h6 class="text-muted text-uppercase fs-xxs mb-1">Location</h6>
                            @if ($purchase->location)
                                <strong>{{ $purchase->location->name }}</strong><br>
                                <small class="text-muted">{{ $purchase->location->location_code }}</small>
                                <br><small class="text-muted">{{ $purchase->location->typeLabel() }}</small>
                            @else
                                <span class="text-muted">&mdash;</span>
                            @endif
                        </div>
                        <div class="col-md-3 text-md-end">
                            <h6 class="text-muted text-uppercase fs-xxs mb-1">Purchase Date</h6>
                            <strong>{{ $purchase->purchase_date?->format('d M Y') }}</strong><br>
                            <small class="text-muted">Tax: {{ strtoupper($purchase->tax_type) }}</small>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end align-items-center gap-2 mb-2">
                        <span class="text-muted small fw-bold" id="labelSelectedCount">
                            <i class="ti ti-info-circle me-1"></i>Please select the checkbox next to a row to print its label
                        </span>
                        <button type="button" class="btn btn-soft-primary btn-sm" id="printLabelsBtn" disabled
                                title="Please select the checkbox next to at least one row to print its label">
                            <i class="ti ti-tag me-1"></i> Print Labels
                        </button>
                    </div>
                    <form id="printLabelsForm" method="GET" action="{{ route('purchases.print-labels', $purchase) }}" target="_blank" class="d-none"></form>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="bg-light bg-opacity-25 text-uppercase fs-xxs">
                                <tr>
                                    <th style="width:36px;">
                                        <input type="checkbox" class="form-check-input" id="labelSelectAll" title="Select all">
                                    </th>
                                    <th>#</th>
                                    <th><i class="ti ti-diamond me-1"></i>Product</th>
                                    <th><i class="ti ti-map-pin me-1"></i>Origin</th>
                                    <th><i class="ti ti-stack-2 me-1"></i>Pcs</th>
                                    <th class="text-end"><i class="ti ti-scale me-1"></i>Carat</th>
                                    <th><i class="ti ti-barcode me-1"></i>Barcode</th>
                                    <th><i class="ti ti-tag me-1"></i>Lot</th>
                                    {{-- Rack column hidden --}}
                                    <th class="text-end"><i class="ti ti-cash me-1"></i>Price</th>
                                    <th class="text-end"><i class="ti ti-world me-1"></i>S.Price</th>
                                    {{-- Tax and Disc columns hidden --}}
                                    <th class="text-end"><i class="ti ti-calculator me-1"></i>Net</th>
                                    <th style="width:90px;">Copies</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($purchase->lines as $i => $line)
                                    {{-- Parent row — the line is a product-creation template now, not a
                                         single product, since each row below created its own. --}}
                                    <tr class="table-light">
                                        <td></td>
                                        <td class="fw-semibold">{{ $i + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div>
                                                    <div class="fw-semibold">{{ $line->title ?? $line->product?->title }}</div>
                                                    <small class="text-muted">{{ $line->category?->name ?? ('SKU: ' . $line->product?->sku) }}</small>
                                                </div>
                                                <i class="ti fs-16 {{ $line->website_enabled ? 'ti-world text-info' : 'ti-world-off text-muted' }}"
                                                   title="{{ $line->website_enabled ? 'Website hint: On (default when this stock is packed)' : 'Website hint: Off (default when this stock is packed)' }}"></i>
                                            </div>
                                        </td>
                                        <td class="small">{{ $line->countryOfOrigin?->name ?? '—' }}</td>
                                        <td colspan="6"></td>
                                        <td class="text-end fw-bold">{{ number_format((float) $line->total, 2) }}</td>
                                        <td></td>
                                    </tr>

                                    {{-- Child rows (one per inventory unit — one per product) --}}
                                    @foreach ($line->rows as $ri => $row)
                                        <tr>
                                            <td class="text-center">
                                                <input type="checkbox" class="form-check-input js-label-row" value="{{ $row->id }}">
                                            </td>
                                            <td></td>
                                            <td class="ps-4 text-muted small">
                                                <i class="ti ti-corner-down-right me-1"></i>
                                                Pcs #{{ $ri + 1 }}
                                                <i class="ti fs-14 ms-1 {{ $row->product?->website_enabled ? 'ti-world text-info' : 'ti-world-off text-muted' }}"
                                                   title="{{ $row->product?->website_enabled ? 'Listed on website' : 'Not listed on website' }}"></i>
                                                @if ($row->product)
                                                    <br><code class="fs-xxs">{{ $row->product->sku }}</code>
                                                @endif
                                            </td>
                                            <td></td>
                                            <td>{{ $row->qty }}</td>
                                            <td class="text-end small">{{ $row->carat_weight !== null ? rtrim(rtrim(number_format((float) $row->carat_weight, 3), '0'), '.') : '—' }}</td>
                                            <td><code class="small">{{ $row->barcode ?: '—' }}</code></td>
                                            <td><code class="small">{{ $row->lot_code ?: '—' }}</code></td>
                                            {{-- Rack column hidden --}}
                                            <td class="text-end">{{ number_format((float) $row->price, 2) }}</td>
                                            <td class="text-end {{ $row->product?->website_enabled ? 'text-info fw-semibold' : '' }}">
                                                {{ $row->website_price !== null ? number_format((float) $row->website_price, 2) : '—' }}
                                            </td>
                                            {{-- Tax and Disc columns hidden --}}
                                            <td class="text-end">{{ number_format($row->net(), 2) }}</td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm js-label-copies" value="1" min="1" max="100" style="width:70px;">
                                            </td>
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
        <div class="col-xl-2">
            <div class="card position-sticky" style="top: 1rem;">
                <div class="card-header border-light d-flex align-items-center gap-2">
                    <i class="ti ti-receipt-2 fs-18 text-primary"></i>
                    <h5 class="card-title mb-0">Summary</h5>
                </div>
                <div class="card-body">

                    <div class="mb-3 pb-3 border-bottom">
                        <div class="text-muted fs-xxs text-uppercase mb-1">Location</div>
                        @if ($purchase->location)
                            <div class="fw-semibold">{{ $purchase->location->name }}</div>
                            <div class="text-muted small">{{ $purchase->location->location_code }} &middot; {{ $purchase->location->typeLabel() }}</div>
                        @else
                            <span class="text-muted">&mdash;</span>
                        @endif
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal</span>
                        <span>{{ app(\App\Services\SettingService::class)->formatMoney($purchase->subtotal) }}</span>
                    </div>
                    {{-- Discount and Tax rows hidden (line totals are Carat × Price) --}}

                    <hr class="my-2">

                    <div class="d-flex justify-content-between mb-3">
                        <span class="fw-semibold">Grand Total</span>
                        <span class="fw-bold fs-18 text-primary">{{ app(\App\Services\SettingService::class)->formatMoney($purchase->grand_total) }}</span>
                    </div>

                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Paid</span>
                        <span>{{ app(\App\Services\SettingService::class)->formatMoney($purchase->paid_amount) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 small">
                        <span class="text-muted">Due</span>
                        <span class="fw-semibold {{ $purchase->due_amount > 0 ? 'text-warning' : 'text-success' }}">
                            {{ app(\App\Services\SettingService::class)->formatMoney($purchase->due_amount) }}
                        </span>
                    </div>

                </div>
            </div>

            {{-- Payments list + add-payment form --}}
            <div class="card">
                <div class="card-header border-light d-flex align-items-center gap-2">
                    <i class="ti ti-credit-card fs-18 text-primary"></i>
                    <h5 class="card-title mb-0">Payments</h5>
                </div>
                <div class="card-body p-0">
                    @if ($purchase->payments->isEmpty())
                        <p class="text-muted small text-center py-3 mb-0">No payments recorded.</p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach ($purchase->payments as $p)
                                <li class="list-group-item px-3 py-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <span class="badge {{ $p->methodBadgeClass() }} fs-xxs">{{ $p->methodLabel() }}</span>
                                        @permission('purchases.edit')
                                        @if (! $purchase->isCancelled())
                                            <button type="button" class="action-btn action-delete js-remove-payment"
                                                data-url="{{ route('purchases.payments.destroy', [$purchase, $p]) }}"
                                                data-amount="{{ number_format(abs((float) $p->amount), 2) }}"
                                                title="Remove">
                                                <i class="ti ti-x"></i>
                                            </button>
                                        @endif
                                        @endpermission
                                    </div>
                                    <div class="fw-semibold {{ $p->isRefund() ? 'text-danger' : '' }} mt-1">
                                        {{ $p->isRefund() ? '−' : '' }}{{ number_format(abs((float) $p->amount), 2) }}
                                    </div>
                                    <small class="d-block text-muted">
                                        {{ optional($p->payment_date)->format('d M Y') }}
                                        @if ($p->reference_number) <br>Ref: {{ $p->reference_number }} @endif
                                    </small>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                @permission('purchases.edit')
                @if ($purchase->isPosted())
                <div class="card-footer border-top">
                    <h6 class="text-muted text-uppercase small mb-2">Add Payment</h6>
                    <form id="addPaymentForm">
                        <select class="form-select form-select-sm mb-1" id="paymentMethod">
                            @foreach ($paymentMethods as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <input type="number" step="0.01" class="form-control form-control-sm mb-1 text-end"
                            id="paymentAmount" placeholder="Amount"
                            value="{{ number_format((float) $purchase->due_amount, 2, '.', '') }}" required>
                        <input type="date" class="form-control form-control-sm mb-1" id="paymentDate"
                            value="{{ now()->toDateString() }}" required>
                        <input type="text" class="form-control form-control-sm mb-2" id="paymentReference"
                            placeholder="Reference (optional)">
                        <button type="submit" class="btn btn-success btn-sm w-100" id="paymentSubmitBtn">
                            <i class="ti ti-device-floppy me-1"></i> Save
                        </button>
                        <div id="paymentError" class="alert alert-danger mt-2 mb-0 py-2 small d-none"></div>
                    </form>
                </div>
                @endif
                @endpermission
            </div>
        </div>
    </div>

    {{-- ==================== Post Confirmation Modal ==================== --}}
    <div class="modal fade" id="postPurchaseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="confirm-modal-icon confirm-modal-icon-success mx-auto mb-3">
                        <i class="ti ti-check"></i>
                    </div>
                    <h5 class="modal-title mb-2">Post this purchase?</h5>
                    <p class="text-muted mb-0">
                        This posts the stock into the ledger and locks the supplier/date. This action can be undone
                        by cancelling the purchase afterward, but not by editing it back to draft.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmPostBtn">
                        <i class="ti ti-check me-1"></i>Post Purchase
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== Remove Payment Confirmation Modal ==================== --}}
    <div class="modal fade" id="removePaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="confirm-modal-icon confirm-modal-icon-danger mx-auto mb-3">
                        <i class="ti ti-trash"></i>
                    </div>
                    <h5 class="modal-title mb-2">Remove this payment?</h5>
                    <p class="text-muted mb-0">
                        Remove the <strong id="removePaymentAmount"></strong> payment? This can't be undone.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmRemovePaymentBtn">
                        <i class="ti ti-trash me-1"></i>Remove Payment
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    function showToast(type, message) {
        const container = document.getElementById('purchaseShowToastContainer');
        if (!container) return;
        const isSuccess = type === 'success';
        const el = document.createElement('div');
        el.className = 'toast align-items-center border-0 text-bg-' + (isSuccess ? 'success' : 'danger');
        el.setAttribute('role', 'alert');
        el.innerHTML = '<div class="d-flex">'
            + '<div class="toast-body d-flex align-items-center gap-2">'
            + '<i class="ti ' + (isSuccess ? 'ti-circle-check' : 'ti-alert-circle') + ' fs-lg"></i>'
            + $('<div/>').text(message).html()
            + '</div>'
            + '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>'
            + '</div>';
        container.appendChild(el);
        const toast = new bootstrap.Toast(el, { delay: 2500 });
        el.addEventListener('hidden.bs.toast', () => el.remove());
        toast.show();
    }

    const postBtn = document.getElementById('postBtn');
    if (postBtn) {
        const postModalEl = document.getElementById('postPurchaseModal');
        const postModal = new bootstrap.Modal(postModalEl);

        postBtn.addEventListener('click', function () {
            postModal.show();
        });

        document.getElementById('confirmPostBtn').addEventListener('click', function () {
            const btn = this;
            btn.disabled = true;
            fetch(`/purchases/${postBtn.dataset.id}/post`, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            })
            .then(async (r) => {
                const data = await r.json().catch(() => ({}));
                if (!r.ok) {
                    showToast('error', data.message || 'Could not post this purchase.');
                    btn.disabled = false;
                    return;
                }
                showToast('success', data.message || 'Purchase posted.');
                setTimeout(() => window.location.reload(), 600);
            })
            .catch(() => {
                showToast('error', 'Network error. Please try again.');
                btn.disabled = false;
            });
        });
    }

    // Payments: add + remove
    const addPaymentForm = document.getElementById('addPaymentForm');
    if (addPaymentForm) {
        addPaymentForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const btn    = document.getElementById('paymentSubmitBtn');
            const errBox = document.getElementById('paymentError');
            errBox.classList.add('d-none');
            btn.disabled = true;

            fetch('{{ route('purchases.payments.store', $purchase) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    payment_date:     document.getElementById('paymentDate').value,
                    amount:           Number(document.getElementById('paymentAmount').value),
                    payment_method:   document.getElementById('paymentMethod').value,
                    reference_number: document.getElementById('paymentReference').value || null,
                }),
            })
            .then(async (r) => {
                const data = await r.json().catch(() => ({}));
                if (!r.ok) {
                    errBox.textContent = data.message || 'Please check the amount and method.';
                    errBox.classList.remove('d-none');
                    btn.disabled = false;
                    return;
                }
                showToast('success', 'Payment recorded.');
                setTimeout(() => window.location.reload(), 600);
            })
            .catch(() => {
                errBox.textContent = 'Network error. Please try again.';
                errBox.classList.remove('d-none');
                btn.disabled = false;
            });
        });
    }

    const removePaymentModalEl = document.getElementById('removePaymentModal');
    const removePaymentModal = new bootstrap.Modal(removePaymentModalEl);
    let pendingRemovePaymentUrl = null;

    document.querySelectorAll('.js-remove-payment').forEach(function (btn) {
        btn.addEventListener('click', function () {
            pendingRemovePaymentUrl = this.dataset.url;
            document.getElementById('removePaymentAmount').textContent = this.dataset.amount;
            removePaymentModal.show();
        });
    });

    document.getElementById('confirmRemovePaymentBtn').addEventListener('click', function () {
        if (!pendingRemovePaymentUrl) return;
        const btn = this;
        btn.disabled = true;
        fetch(pendingRemovePaymentUrl, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        })
        .then(async (r) => {
            const data = await r.json().catch(() => ({}));
            if (!r.ok) {
                showToast('error', data.message || 'Could not remove payment.');
                btn.disabled = false;
                return;
            }
            showToast('success', data.message || 'Payment removed.');
            setTimeout(() => window.location.reload(), 600);
        })
        .catch(() => {
            showToast('error', 'Network error. Please try again.');
            btn.disabled = false;
        })
        .finally(() => { removePaymentModal.hide(); pendingRemovePaymentUrl = null; });
    });

    // Print labels: select rows + copies, submit to a new tab.
    const rowChecks   = () => Array.from(document.querySelectorAll('.js-label-row'));
    const selectAll   = document.getElementById('labelSelectAll');
    const printBtn    = document.getElementById('printLabelsBtn');
    const countLabel  = document.getElementById('labelSelectedCount');
    const printForm   = document.getElementById('printLabelsForm');

    function refreshLabelUI() {
        const checked = rowChecks().filter(cb => cb.checked);
        printBtn.disabled = checked.length === 0;

        if (checked.length === 0) {
            countLabel.innerHTML = '<i class="ti ti-info-circle me-1"></i>Please select the checkbox next to a row to print its label';
            printBtn.title = 'Please select the checkbox next to at least one row to print its label';
        } else {
            countLabel.textContent = checked.length + ' selected';
            printBtn.title = '';
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            rowChecks().forEach(cb => { cb.checked = selectAll.checked; });
            refreshLabelUI();
        });
    }

    rowChecks().forEach(cb => cb.addEventListener('change', refreshLabelUI));

    if (printBtn) {
        printBtn.addEventListener('click', function () {
            const checked = rowChecks().filter(cb => cb.checked);
            if (checked.length === 0) return;

            printForm.innerHTML = '';
            checked.forEach(function (cb, idx) {
                const tr = cb.closest('tr');
                const copiesInput = tr ? tr.querySelector('.js-label-copies') : null;
                const copies = copiesInput ? (parseInt(copiesInput.value, 10) || 1) : 1;

                const idField = document.createElement('input');
                idField.type = 'hidden';
                idField.name = `items[${idx}][id]`;
                idField.value = cb.value;
                printForm.appendChild(idField);

                const copiesField = document.createElement('input');
                copiesField.type = 'hidden';
                copiesField.name = `items[${idx}][copies]`;
                copiesField.value = copies;
                printForm.appendChild(copiesField);
            });

            printForm.submit();
        });
    }
})();
</script>
@endpush
@endsection
