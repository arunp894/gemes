@extends('layout.app')

@section('title', 'Sales Terminal')

@section('content')
<div class="container-fluid sales-terminal-page" id="salesTerminalApp">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">
                <i class="ti ti-cash-register text-primary me-2"></i>Sales Terminal
            </h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Sales</a></li>
                <li class="breadcrumb-item active">New Sale</li>
            </ol>
        </div>
    </div>

    {{-- No 'was-validated' class here on purpose: validation is entirely
         manual (.is-invalid bindings driven by `errors`), and Bootstrap's
         was-validated turns on native :valid/:invalid styling for every
         control including ones with no `required` attribute — which would
         paint a false-positive green checkmark on genuinely optional,
         still-empty fields (Discount %, Tax %, Note, Reference…). --}}
    <form id="saleForm" novalidate @submit.prevent="submit('completed')">

        <div class="row g-3">

            {{-- ╔═══════════════════════════════════════════════╗
                              LEFT — Cart
                ╚═══════════════════════════════════════════════╝ --}}
            <div class="col-xl-8">

                {{-- Header card --}}
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label"><i class="ti ti-calendar text-primary me-2"></i> Sale Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control"
                                    v-model="form.sale_date"
                                    @change="refreshSaleNumber"
                                    :class="{ 'is-invalid': errors.sale_date }" required>
                                <div class="invalid-feedback">@{{ errors.sale_date }}</div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label"><i class="ti ti-shopping-cart text-primary me-2"></i> Sale #</label>
                                <input type="text" class="form-control bg-light"
                                    :value="form.sale_number_preview"
                                    readonly placeholder="auto-generated">
                                <small class="text-muted">Format: <code>SALE-YYYYMM-####</code></small>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label"><i class="ti ti-location-pin text-primary me-2"></i> Location <span class="text-danger">*</span></label>

                                {{-- ── NONE: user has no assigned locations ── --}}
                                @if ($locationMode === 'none')
                                <div class="alert alert-danger mb-0 py-2 px-3 d-flex align-items-center gap-2">
                                    <i class="ti ti-lock fs-18 flex-shrink-0"></i>
                                    <div>
                                        <strong>No location access.</strong><br>
                                        <small>Ask an administrator to assign you to a location before creating sales.</small>
                                    </div>
                                </div>
                                {{-- hide the submit buttons server-side by passing locationMode to JS --}}

                                {{-- ── SINGLE: user has exactly one location ── --}}
                                @elseif ($locationMode === 'single')
                                <div class="form-control bg-light d-flex align-items-center gap-2 h-auto py-2">
                                    <i class="ti ti-map-pin text-primary"></i>
                                    <div class="lh-sm">
                                        <div class="fw-semibold">{{ $defaultLocation->name }}</div>
                                        <small class="text-muted">{{ $defaultLocation->location_code }}</small>
                                    </div>
                                </div>

                                {{-- ── MULTIPLE: user has 2+ locations → dropdown ── --}}
                                @else
                                <select class="form-select" v-model.number="form.location_id"
                                    :class="{ 'is-invalid': errors.location_id }" required>
                                    <option :value="null">— Select location —</option>
                                    <option v-for="l in userLocations" :key="l.id" :value="l.id">
                                        @{{ l.name }} (@{{ l.location_code }})@{{ l.is_default ? ' ★' : '' }}
                                    </option>
                                </select>
                                <div class="invalid-feedback">@{{ errors.location_id }}</div>
                                @endif
                            </div>

                            <div class="col-md-3">
                                <label class="form-label"><i class="ti ti-user text-primary me-2"></i> Sales person</label>
                                <select class="form-select" v-model.number="form.salesperson_id">
                                    <option :value="null">— Unassigned —</option>
                                    <option v-for="u in salespeople" :key="u.id" :value="u.id">@{{ u.name }}</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label"><i class="ti ti-direction text-primary me-2"></i> Sales Channel</label>
                                <select class="form-select" v-model.number="form.channel_id">
                                    <option :value="null">— No Channel —</option>
                                    <option v-for="c in channels" :key="c.id" :value="c.id">@{{ c.name }}</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label"><i class="ti ti-receipt text-primary me-2"></i> Tax Type</label>
                                <select class="form-select" v-model="form.tax_type">
                                    <option value="none">No Tax</option>
                                    <option value="cgst_sgst">CGST + SGST</option>
                                    <option value="igst">IGST</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label"><i class="ti ti-truck text-primary me-2"></i> Shipping</label>
                                <input type="number" min="0" step="0.01" class="form-control"
                                    v-model.number="form.shipping_charge">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label"><i class="ti ti-check text-primary me-2"></i> Status</label>
                                <select class="form-select" v-model="form.status">
                                    <option value="draft">Draft (save for later)</option>
                                    <option value="posted">Posted</option>
                                    <option value="completed">Completed (paid + delivered)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Scanner / search --}}
                <div class="card">
                    <div class="card-header border-light d-flex align-items-center gap-2">
                        <i class="ti ti-barcode fs-18 text-primary"></i>
                        <h5 class="card-title mb-0">Scan or Search Product</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="ti ti-barcode text-primary me-2"></i>
                                    Barcode / Lot Code
                                    <small class="text-muted">Scan and press Enter to add to cart.</small>
                                </label>
                                <input ref="barcodeInput" type="text" class="form-control form-control-lg"
                                    v-model="barcodeInput"
                                    placeholder="Scan or type barcode / lot code then Enter"
                                    @keyup.enter.prevent="onBarcodeEnter" autofocus>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="ti ti-search text-primary me-2"></i>
                                    Search by name / SKU</label>
                                <div class="position-relative">
                                    <input type="text" class="form-control" v-model="productSearch"
                                        placeholder="e.g. Sapphire 6mm" @input="onSearchInput" @focus="onSearchInput">

                                    <ul v-if="searchResults.length"
                                        class="list-group position-absolute w-100 mt-1 shadow-sm"
                                        style="z-index: 1050; max-height: 280px; overflow-y: auto;">
                                        <li v-for="p in searchResults" :key="p.id"
                                            class="list-group-item list-group-item-action"
                                            @mousedown.prevent="addProductBySearch(p)" style="cursor: pointer;">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <div class="fw-semibold">@{{ p.title }}</div>
                                                    <small class="text-muted">SKU: @{{ p.sku }}</small>
                                                </div>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="col-12" v-if="scannerMessage">
                                <div class="alert mb-0 py-2" :class="scannerAlertClass">
                                    <i :class="scannerIconClass" class="me-1"></i>@{{ scannerMessage }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Cart table --}}
                <div class="card">
                    <div class="card-header border-light d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Cart</h5>
                        <span class="text-muted small" v-if="form.lines.length">
                            @{{ form.lines.length }} line@{{ form.lines.length === 1 ? '' : 's' }}
                        </span>
                    </div>

                    <div v-if="form.lines.length === 0" class="card-body text-center text-muted py-5">
                        <i class="ti ti-shopping-cart-off fs-1 d-block mb-2"></i>
                        Scan a barcode or search for a product to add it to the cart.
                    </div>

                    <div v-else class="table-responsive">
                        <table class="table table-custom align-middle mb-0">
                            <thead class="bg-light bg-opacity-25 thead-sm">
                                <tr class="text-uppercase fs-xxs">
                                    <th style="width: 26%;">Product</th>
                                    <th class="text-center" style="width: 8%;">Ct</th>
                                    <th style="width: 13%;">Barcode</th>
                                    <th class="text-end" style="width: 8%;">Piece <span class="text-danger">*</span></th>
                                    <th class="text-end" style="width: 12%;">Unit Price <span class="text-danger">*</span></th>
                                    <th class="text-end" style="width: 8%;">Disc %</th>
                                    <th class="text-end" style="width: 8%;">Tax %</th>
                                    <th class="text-end" style="width: 14%;">Total</th>
                                    <th class="text-center" style="width: 3%;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(line, idx) in form.lines" :key="idx"
                                    :class="{ 'table-warning': line._stockWarning, 'line-has-error': hasLineError(idx) }">
                                    <td>
                                        <div class="fw-semibold">@{{ line.product_title }}</div>
                                        <small class="text-muted">SKU: @{{ line.product_sku }}</small>
                                    </td>
                                    <td class="text-center">
                                        {{-- CT is its own independent ledger, separate from qty — the
                                             seller enters exactly how much CT this line consumes, capped
                                             at that piece's actual remaining CT balance (never a
                                             qty-derived guess; different units on the same row can carry
                                             different individual weights). --}}
                                        <input v-if="line.piece_carat_weight !== null && line.piece_carat_weight !== undefined"
                                            type="number" min="0" step="0.001"
                                            :max="line.remaining_carat_before"
                                            class="form-control form-control-sm text-end mb-1"
                                            v-model.number="line.carat_weight"
                                            @input="checkCaratLimit(idx)">
                                        <span v-if="line.piece_carat_weight !== null && line.piece_carat_weight !== undefined"
                                            class="badge badge-soft-info d-block">
                                            Remaining Ct: @{{ formatCarat(remainingCaratAfter(line)) }}
                                        </span>
                                        <span v-else class="text-muted">—</span>
                                    </td>
                                    <td>
                                        <code class="small">@{{ line.barcode || '—' }}</code>
                                        <small v-if="line._stockWarning" class="d-block text-danger">
                                            <i class="ti ti-alert-triangle"></i> Capped at stock on hand
                                        </small>
                                        <small v-else class="d-block text-success">@{{ line.on_hand }} Piece@{{ line.on_hand === 1 ? '' : 's' }} On Hand</small>
                                    </td>
                                    <td>
                                        <input type="number" min="1" step="1" :max="line.on_hand"
                                            :disabled="line.on_hand === 1"
                                            :title="line.on_hand === 1 ? 'Only 1 in stock — quantity is locked' : ''"
                                            class="form-control form-control-sm text-end"
                                            :class="{ 'is-invalid': lineError(idx, 'qty') }"
                                            v-model.number="line.qty"
                                            @input="checkStockWarning(idx)"
                                            @blur="normalizeQty(idx)">
                                        <div class="invalid-feedback d-block" v-if="lineError(idx, 'qty')">@{{ lineError(idx, 'qty') }}</div>
                                    </td>
                                    <td>
                                        <input type="number" min="0" step="0.01"
                                            class="form-control form-control-sm text-end"
                                            :class="{ 'is-invalid': lineError(idx, 'unit_price') }"
                                            v-model.number="line.unit_price">
                                        <div class="invalid-feedback d-block" v-if="lineError(idx, 'unit_price')">@{{ lineError(idx, 'unit_price') }}</div>
                                    </td>
                                    <td>
                                        <input type="number" min="0" max="100" step="0.01"
                                            class="form-control form-control-sm text-end"
                                            v-model.number="line.discount_percent">
                                    </td>
                                    <td>
                                        <input type="number" min="0" max="100" step="0.01"
                                            class="form-control form-control-sm text-end"
                                            v-model.number="line.tax_percent">
                                    </td>
                                    <td class="text-end fw-semibold">
                                        @{{ formatMoney(lineTotal(line)) }}
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-default btn-icon btn-sm text-danger"
                                            @click="removeLine(idx)" title="Remove">
                                            <i class="ti ti-x"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Note --}}
                <div class="card">
                    <div class="card-header border-light"><h5 class="card-title mb-0">Note</h5></div>
                    <div class="card-body">
                        <textarea class="form-control" rows="2" v-model="form.note" maxlength="2000"
                            placeholder="Optional note for this sale"></textarea>
                    </div>
                </div>

            </div>{{-- /col-xl-8 --}}

            {{-- ╔═══════════════════════════════════════════════╗
                          RIGHT — Customer + Summary
                ╚═══════════════════════════════════════════════╝ --}}
            <div class="col-xl-4">

                {{-- Customer picker --}}
                <div class="card">
                    <div class="card-header border-light d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="ti ti-user fs-18 text-primary me-1"></i>Customer
                        </h5>
                        @permission('customers.create')
                        <a :href="newCustomerUrl" target="_blank" class="btn btn-sm btn-soft-secondary">
                            <i class="ti ti-plus me-1"></i> New
                        </a>
                        @endpermission
                    </div>
                    <div class="card-body">
                        <div v-if="!selectedCustomer" class="position-relative">
                            <input type="text" class="form-control"
                                v-model="customerSearch"
                                placeholder="Search name / phone / code…"
                                @input="onCustomerSearchInput" @focus="onCustomerSearchInput"
                                :class="{ 'is-invalid': errors.customer_id }">
                            <div class="invalid-feedback">@{{ errors.customer_id }}</div>

                            <ul v-if="customerResults.length"
                                class="list-group position-absolute w-100 mt-1 shadow-sm"
                                style="z-index: 1050; max-height: 280px; overflow-y: auto;">
                                <li v-for="c in customerResults" :key="c.id"
                                    class="list-group-item list-group-item-action"
                                    @mousedown.prevent="selectCustomer(c)" style="cursor: pointer;">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="fw-semibold">@{{ c.display_name }}</div>
                                            <small class="text-muted">
                                                @{{ c.customer_code }}
                                                <span v-if="c.phone"> · @{{ c.phone }}</span>
                                            </small>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <div v-else>
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold">@{{ selectedCustomer.display_name }}</div>
                                    <small class="text-muted d-block">@{{ selectedCustomer.customer_code }}</small>
                                    <small v-if="selectedCustomer.phone" class="text-muted d-block">
                                        <i class="ti ti-phone me-1"></i>@{{ selectedCustomer.phone }}
                                    </small>
                                    <small v-if="selectedCustomer.email" class="text-muted d-block">
                                        <i class="ti ti-mail me-1"></i>@{{ selectedCustomer.email }}
                                    </small>
                                    <small v-if="selectedCustomer.gst_number" class="text-muted d-block">
                                        GST: @{{ selectedCustomer.gst_number }}
                                    </small>
                                </div>
                                <button type="button" class="btn btn-default btn-icon btn-sm"
                                    @click="clearCustomer" title="Change">
                                    <i class="ti ti-x"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Summary --}}
                <div class="card">
                    <div class="card-header border-light"><h5 class="card-title mb-0">Summary</h5></div>
                    <div class="card-body">
                        <dl class="row mb-0 small">
                            <dt class="col-7 text-muted">Subtotal</dt>
                            <dd class="col-5 text-end">@{{ formatMoney(totals.subtotal) }}</dd>

                            <dt class="col-7 text-muted">Discount</dt>
                            <dd class="col-5 text-end">− @{{ formatMoney(totals.discount) }}</dd>

                            <dt class="col-7 text-muted">Tax</dt>
                            <dd class="col-5 text-end">+ @{{ formatMoney(totals.tax) }}</dd>

                            <dt class="col-7 text-muted">Shipping</dt>
                            <dd class="col-5 text-end">+ @{{ formatMoney(form.shipping_charge || 0) }}</dd>

                            <dt class="col-7 fs-base fw-bold pt-2 border-top mt-2">Grand Total</dt>
                            <dd class="col-5 text-end fs-base fw-bold pt-2 border-top mt-2">
                                @{{ formatMoney(totals.grand) }}
                            </dd>

                            <dt class="col-7 text-muted pt-1">Paid</dt>
                            <dd class="col-5 text-end pt-1">@{{ formatMoney(totals.paid) }}</dd>

                            <dt class="col-7 fw-semibold" :class="{ 'text-danger': totals.balance > 0 }">Balance Due</dt>
                            <dd class="col-5 text-end fw-semibold" :class="{ 'text-danger': totals.balance > 0 }">
                                @{{ formatMoney(totals.balance) }}
                            </dd>
                        </dl>
                    </div>
                </div>

                {{-- Payments --}}
                <div class="card">
                    <div class="card-header border-light d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Payments</h5>
                        <button type="button" class="btn btn-sm btn-soft-primary" @click="addPayment">
                            <i class="ti ti-plus me-1"></i> Add
                        </button>
                    </div>
                    <div class="card-body">
                        <div v-if="form.payments.length === 0" class="text-muted small text-center py-2">
                            No payments yet. Click <strong>Add</strong> to record one.
                        </div>

                        <div v-for="(p, idx) in form.payments" :key="idx" class="border rounded p-2 mb-2">
                            <div class="row g-2">
                                <div class="col-7">
                                    <label class="form-label small mb-1">Method</label>
                                    <select class="form-select form-select-sm" v-model="p.payment_method">
                                        @foreach ($paymentMethods as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-5">
                                    <label class="form-label small mb-1">Amount</label>
                                    <input type="number" step="0.01" class="form-control form-control-sm text-end"
                                        v-model.number="p.amount">
                                </div>
                                <div class="col-7">
                                    <label class="form-label small mb-1">Date</label>
                                    <input type="date" class="form-control form-control-sm" v-model="p.payment_date">
                                </div>
                                <div class="col-5 d-flex align-items-end">
                                    <button type="button" class="btn btn-sm btn-soft-danger w-100"
                                        @click="removePayment(idx)">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small mb-1">Reference (optional)</label>
                                    <input type="text" class="form-control form-control-sm"
                                        v-model="p.reference_number"
                                        placeholder="UPI ref, cheque #, txn id…">
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-sm btn-soft-secondary w-100 mt-1"
                            v-if="totals.balance > 0 && form.payments.length > 0"
                            @click="payRemainingAsLast">
                            <i class="ti ti-equal me-1"></i> Set last payment to balance (@{{ formatMoney(totals.balance) }})
                        </button>
                    </div>
                </div>

                {{-- Actions --}}
                <div v-if="serverError" class="alert alert-danger">@{{ serverError }}</div>

                @if ($locationMode === 'none')
                {{-- Full block: no location access --}}
                <div class="alert alert-danger d-flex align-items-start gap-2 mb-3">
                    <i class="ti ti-lock fs-22 flex-shrink-0 mt-1"></i>
                    <div>
                        <strong>Sales entry blocked.</strong><br>
                        You have no location assigned to your account. Contact an administrator to get access before creating sales.
                    </div>
                </div>
                <a href="{{ route('sales.index') }}" class="btn btn-secondary w-100">Back to Sales</a>
                @else
                <div class="d-grid gap-2 mb-4">
                    <button type="button" class="btn btn-lg btn-success" :disabled="submitting"
                        @click="submit('completed')">
                        <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="ti ti-check me-1"></i>
                        Complete Sale (@{{ formatMoney(totals.grand) }})
                    </button>
                    <div class="row g-2">
                        <div class="col-6">
                            <button type="button" class="btn btn-outline-success w-100" :disabled="submitting"
                                @click="submit('draft')">
                                <i class="ti ti-device-floppy me-1"></i> Save Draft
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn-primary w-100" :disabled="submitting"
                                @click="submit('posted')">
                                <i class="ti ti-send me-1"></i> Post (Unpaid OK)
                            </button>
                        </div>
                    </div>
                    <a href="{{ route('sales.index') }}" class="btn btn-link text-muted">Cancel and go back</a>
                </div>
                @endif

            </div>{{-- /col-xl-4 --}}

        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
    /* Sales Terminal — compact spacing + title-bar polish, matching the rest
       of the app. CSS-only: the cart/scan/payment Vue logic below is
       completely untouched. */
    .sales-terminal-page { padding-top: 10px; padding-bottom: 20px; }
    .sales-terminal-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid #e2e8f0;
    }
    .sales-terminal-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        position: relative;
        padding-left: 12px;
    }
    .sales-terminal-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0; top: 2px; bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, #1e3a8a, #1d4ed8);
    }
    .sales-terminal-page .page-main-title i { position: relative; }
    .sales-terminal-page .breadcrumb { font-size: 0.75rem; }
    .sales-terminal-page .card { border-radius: 10px; box-shadow: none; border: 1px solid #e2e8f0; }
    .sales-terminal-page .card-body { padding: 16px; }
    .sales-terminal-page .card-title { font-size: 0.9375rem; font-weight: 700; }
    .sales-terminal-page .form-label { margin-bottom: 4px; font-size: 0.8125rem; font-weight: 600; }
    .sales-terminal-page .mb-3 { margin-bottom: 12px !important; }

    /* A cart row carrying a real validation error — a solid left stripe
       plus a faint red tint, matching the same treatment on the Purchase
       form's line table, so the eye goes straight to it instead of
       scanning every row for a stray .is-invalid border. */
    .sales-terminal-page .table tr.line-has-error > td {
        background-color: #fef2f2 !important;
    }
    .sales-terminal-page .table tr.line-has-error > td:first-child {
        box-shadow: inset 3px 0 0 #dc2626;
    }
    .sales-terminal-page .table .invalid-feedback {
        font-size: 0.6875rem;
        margin-top: 2px;
    }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    const userLocations = @json($userLocations);
    const salespeople   = @json($salespeople);
    const defaultLoc    = @json($defaultLocation);
    const locationMode  = @json($locationMode);
    const defaultSPId   = @json($defaultSalespersonId);
    const channels      = @json($channels);
    const defaultChId   = @json($defaultChannelId);
    const currencySymbol   = @json($currencySymbol);
    const currencyCode     = @json($currencyCode);
    const currencyPosition = @json($currencyPosition);

    new Vue({
        el: '#salesTerminalApp',
        data: {
            // Reference data
            userLocations, salespeople,
            locationMode,
            channels,
            currencySymbol, currencyCode, currencyPosition,
            newCustomerUrl: '{{ route('customers.create') }}',

            // Search states
            barcodeInput: '',
            productSearch: '',
            searchResults: [],
            _searchTimer: null,

            customerSearch: '',
            customerResults: [],
            selectedCustomer: null,
            _customerTimer: null,

            scannerMessage: '',
            scannerAlertClass: 'alert-info',
            scannerIconClass: 'ti ti-info-circle',

            form: {
                sale_date: new Date().toISOString().slice(0, 10),
                sale_number_preview: '',
                customer_id: null,
                location_id: defaultLoc ? defaultLoc.id : null,
                channel_id: defaultChId || null,
                salesperson_id: defaultSPId || null,
                tax_type: 'none',
                shipping_charge: 0,
                note: '',
                status: 'completed', // default optimistic path
                lines: [],
                payments: [],
            },

            errors: {},
            submitting: false,
            serverError: null,
        },
        computed: {
            totals() {
                let subtotal = 0, discount = 0, tax = 0;
                this.form.lines.forEach((l) => {
                    const qty   = Number(l.qty) || 0;
                    const price = Number(l.unit_price) || 0;
                    const dPct  = Number(l.discount_percent) || 0;
                    const tPct  = Number(l.tax_percent) || 0;

                    const gross  = qty * price;
                    const dAmt   = +(gross * dPct / 100).toFixed(2);
                    const base   = gross - dAmt;
                    const tAmt   = +(base * tPct / 100).toFixed(2);

                    subtotal += gross;
                    discount += dAmt;
                    tax      += tAmt;
                });

                const ship  = Number(this.form.shipping_charge) || 0;
                const grand = subtotal - discount + tax + ship;
                const paid  = this.form.payments.reduce((s, p) => s + (Number(p.amount) || 0), 0);
                const balance = Math.max(0, grand - paid);

                return {
                    subtotal: +subtotal.toFixed(2),
                    discount: +discount.toFixed(2),
                    tax:      +tax.toFixed(2),
                    grand:    +grand.toFixed(2),
                    paid:     +paid.toFixed(2),
                    balance:  +balance.toFixed(2),
                };
            },
        },
        mounted() {
            this.refreshSaleNumber();
            this.$nextTick(() => this.$refs.barcodeInput?.focus());
        },
        methods: {
            /* ── formatting ────────────────── */
            formatMoney(v) {
                const formatted = Number(v || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                return this.currencyPosition === 'after'
                    ? `${formatted} ${this.currencyCode}`
                    : `${this.currencySymbol}${formatted}`;
            },
            lineTotal(l) {
                const qty   = Number(l.qty) || 0;
                const price = Number(l.unit_price) || 0;
                const dPct  = Number(l.discount_percent) || 0;
                const tPct  = Number(l.tax_percent) || 0;
                const gross = qty * price;
                const dAmt  = gross * dPct / 100;
                const base  = gross - dAmt;
                const tAmt  = base * tPct / 100;
                return +(base + tAmt).toFixed(2);
            },

            /* ── sale number preview ───────── */
            async refreshSaleNumber() {
                try {
                    const url = `{{ route('sales.preview-number') }}?date=${encodeURIComponent(this.form.sale_date)}`;
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    if (res.ok) {
                        const data = await res.json();
                        if (data.ok) this.form.sale_number_preview = data.sale_number;
                    }
                } catch (_) {}
            },

            /* ── scanner ───────────────────── */
            async onBarcodeEnter() {
                const code = this.barcodeInput.trim();
                if (!code) return;
                this.scannerMessage = '';

                if (!this.form.location_id) {
                    this.scannerAlertClass = 'alert-warning';
                    this.scannerIconClass  = 'ti ti-alert-circle';
                    this.scannerMessage    = 'Please select a location before scanning so stock can be checked.';
                    return;
                }

                try {
                    const params = new URLSearchParams({ barcode: code, location_id: String(this.form.location_id) });
                    const url = `{{ route('sales.lookup-barcode') }}?${params.toString()}`;
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();

                    if (!res.ok || !data.ok) {
                        this.scannerAlertClass = 'alert-danger';
                        this.scannerIconClass  = 'ti ti-alert-circle';
                        this.scannerMessage    = data.message || 'Barcode not found.';
                        return;
                    }

                    // Refuse to add a piece with zero stock at the location.
                    if (data.inventory && data.inventory.on_hand !== null && data.inventory.on_hand <= 0) {
                        this.scannerAlertClass = 'alert-danger';
                        this.scannerIconClass  = 'ti ti-x';
                        this.scannerMessage    = `${data.product.title} is out of stock.`;
                        return;
                    }

                    this.addProductFromLookup(data);
                    this.barcodeInput = '';
                    this.scannerAlertClass = 'alert-success';
                    this.scannerIconClass  = 'ti ti-check';
                    this.scannerMessage    = `Added: ${data.product.title}` +
                        (data.inventory && data.inventory.on_hand !== null ? ` (on hand: ${data.inventory.on_hand})` : '');
                    setTimeout(() => { this.scannerMessage = ''; }, 1800);
                } catch (err) {
                    this.scannerAlertClass = 'alert-danger';
                    this.scannerIconClass  = 'ti ti-alert-circle';
                    this.scannerMessage    = 'Network error during lookup.';
                }
            },

            addProductFromLookup(data) {
                const p   = data.product;
                const inv = data.inventory;

                // If the same purchase_product_id is already in cart and we have one — bump qty
                if (inv && inv.purchase_product_id) {
                    const existing = this.form.lines.find(
                        (l) => l.purchase_product_id === inv.purchase_product_id
                    );
                    if (existing) {
                        existing.qty = Number(existing.qty) + 1;
                        this.checkStockWarning(this.form.lines.indexOf(existing));
                        return;
                    }
                }

                // Prefer the price actually set on the product (seeded at
                // purchase time, editable on the product screen); fall back
                // to a cost-based estimate only when nothing's been set yet.
                const defaultPrice = (p.website_price !== null && p.website_price !== undefined)
                    ? Number(p.website_price)
                    : (inv && inv.cost_price ? +(Number(inv.cost_price) * 1.3).toFixed(2) : 0);

                this.form.lines.push({
                    product_id:          p.id,
                    // Defaults to "sell everything left on this piece" —
                    // the seller can type a smaller amount. Must default to
                    // the live remaining balance, not the piece's original
                    // recorded weight: once a piece has been partially sold
                    // before, those two numbers differ (see remaining_carat_before).
                    carat_weight:        data.remaining_carat,
                    // Flag for "is this a weighed item at all" (drives the
                    // v-if below) — the piece's original recorded per-unit
                    // carat, immutable, never used in a calculation.
                    piece_carat_weight:  data.carat_weight,
                    // Snapshot of this piece's actual remaining CT balance
                    // (from the ledger) at the moment it's added to the
                    // sale — the cap the seller's entered carat_weight is
                    // clamped against. Independent of qty entirely: a
                    // piece's remaining CT doesn't change just because
                    // this line's qty changes.
                    remaining_carat_before: data.remaining_carat,
                    product_title:       p.title,
                    on_hand:             inv.on_hand,
                    product_sku:         p.sku,
                    purchase_product_id: inv ? inv.purchase_product_id : null,
                    barcode:             data.barcode || null,
                    qty:                 1,
                    unit_price:          defaultPrice,
                    cost_price:          inv ? Number(inv.cost_price || 0) : 0,
                    qty_on_record:       inv && inv.on_hand !== undefined && inv.on_hand !== null
                                            ? Number(inv.on_hand)
                                            : (inv ? Number(inv.qty_on_record || 0) : null),
                    tax_percent:         0,
                    discount_percent:    0,
                    _stockWarning:       false,
                });
            },

            /* ── product search ────────────── */
            onSearchInput() {
                clearTimeout(this._searchTimer);
                const term = this.productSearch.trim();
                if (term.length < 2) { this.searchResults = []; return; }
                this._searchTimer = setTimeout(() => this.runSearch(), 220);
            },
            async runSearch() {
                try {
                    const url = `{{ route('sales.search-products') }}?q=${encodeURIComponent(this.productSearch.trim())}`;
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    if (res.ok && data.ok) this.searchResults = data.items;
                } catch (_) {}
            },
            addProductBySearch(p) {
                this.form.lines.push({
                    product_id:          p.id,
                    carat_weight:        null,
                    piece_carat_weight:  null,
                    product_title:       p.title,
                    product_sku:         p.sku,
                    purchase_product_id: null,
                    barcode:             null,
                    qty:                 1,
                    unit_price:          (p.website_price !== null && p.website_price !== undefined) ? Number(p.website_price) : 0,
                    cost_price:          0,
                    qty_on_record:       null,
                    tax_percent:         0,
                    discount_percent:    0,
                    _stockWarning:       false,
                });
                this.productSearch = '';
                this.searchResults = [];
                this.$nextTick(() => this.$refs.barcodeInput?.focus());
            },

            removeLine(idx) { this.form.lines.splice(idx, 1); },

            // Clamps qty to on-hand stock rather than just flagging it —
            // typing past what's available snaps back to the ceiling.
            // Deliberately does NOT floor an empty/invalid value to 1 here
            // (see normalizeQty()) — this runs on every keystroke, and
            // snapping back to 1 the instant the box goes empty made it
            // impossible to clear the field and type a new number.
            checkStockWarning(idx) {
                const l = this.form.lines[idx];
                if (!l) return;
                if (l.qty_on_record !== null && Number(l.qty) > Number(l.qty_on_record)) {
                    l.qty = Number(l.qty_on_record);
                    l._stockWarning = true;
                    setTimeout(() => { l._stockWarning = false; }, 2000);
                }
            },

            // Runs on blur, once the seller is done editing — this is
            // where an emptied or invalid qty finally falls back to 1,
            // instead of on every keystroke.
            normalizeQty(idx) {
                const l = this.form.lines[idx];
                if (!l) return;
                if (!l.qty || Number(l.qty) < 1) l.qty = 1;
            },

            /* ── carat ─────────────────────── */
            // CT is its own ledger, entirely independent of qty — this is
            // the piece's actual remaining balance (snapshotted when the
            // line was added) minus whatever the seller has typed as the
            // carat_weight being sold on this line.
            remainingCaratAfter(line) {
                if (line.piece_carat_weight === null || line.piece_carat_weight === undefined) return null;
                return Number(line.remaining_carat_before || 0) - Number(line.carat_weight || 0);
            },
            formatCarat(v) {
                if (v === null || v === undefined || isNaN(v)) return '—';
                return (Math.round(Number(v) * 1000) / 1000).toString();
            },
            // Clamps the sold-carat figure to this piece's actual
            // remaining CT balance — never a qty-derived guess, since
            // different units on the same row can carry different
            // individual weights.
            checkCaratLimit(idx) {
                const l = this.form.lines[idx];
                if (!l || l.piece_carat_weight === null || l.piece_carat_weight === undefined) return;
                const maxCarat = Number(l.remaining_carat_before || 0);
                if (l.carat_weight === null || l.carat_weight === undefined || l.carat_weight === '') return;
                if (Number(l.carat_weight) > maxCarat) l.carat_weight = maxCarat;
                if (Number(l.carat_weight) < 0) l.carat_weight = 0;
            },

            /* ── customer search ──────────── */
            onCustomerSearchInput() {
                clearTimeout(this._customerTimer);
                const term = this.customerSearch.trim();
                this._customerTimer = setTimeout(() => this.runCustomerSearch(term), 220);
            },
            async runCustomerSearch(term) {
                try {
                    const url = `{{ route('customers.search') }}?q=${encodeURIComponent(term)}`;
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    if (res.ok && data.ok) this.customerResults = data.items;
                } catch (_) {}
            },
            selectCustomer(c) {
                this.selectedCustomer  = c;
                this.form.customer_id  = c.id;
                this.customerResults   = [];
                this.customerSearch    = '';
            },
            clearCustomer() {
                this.selectedCustomer = null;
                this.form.customer_id = null;
            },

            /* ── payments ─────────────────── */
            addPayment() {
                this.form.payments.push({
                    payment_date:     this.form.sale_date,
                    amount:           +this.totals.balance.toFixed(2),
                    payment_method:   'cash',
                    reference_number: '',
                });
            },
            removePayment(idx) { this.form.payments.splice(idx, 1); },
            payRemainingAsLast() {
                if (this.form.payments.length === 0) return;
                const last = this.form.payments[this.form.payments.length - 1];
                const others = this.form.payments
                    .slice(0, -1)
                    .reduce((s, p) => s + (Number(p.amount) || 0), 0);
                last.amount = +(this.totals.grand - others).toFixed(2);
            },

            /* ── error mapping ─────────────── */
            // Laravel returns dot-path keys like:
            //   customer_id            — a top-level field, kept flat
            //   lines.2.qty            — a specific cart row's field, nested
            //     under errors.lines[idx] so that exact row/input can look
            //     its own message up, instead of every field on the page
            //     silently sharing one raw, meaningless dot-path string.
            applyServerErrors(errs) {
                const flat = { lines: {} };
                Object.keys(errs).forEach((key) => {
                    const msg = Array.isArray(errs[key]) ? errs[key][0] : String(errs[key]);
                    const m = key.match(/^lines\.(\d+)\.(.+)$/);
                    if (m) {
                        const [, idx, field] = m;
                        flat.lines[idx] = flat.lines[idx] || {};
                        flat.lines[idx][field] = msg;
                    } else {
                        flat[key] = msg;
                    }
                });
                this.errors = flat;
            },
            lineError(idx, field) {
                const l = this.errors.lines && this.errors.lines[idx];
                return (l && l[field]) || '';
            },
            hasLineError(idx) {
                const l = this.errors.lines && this.errors.lines[idx];
                return !!(l && Object.values(l).some(Boolean));
            },

            /* ── submit ───────────────────── */
            validate(intendedStatus) {
                this.errors = {};
                if (!this.form.customer_id) {
                    this.$set(this.errors, 'customer_id', 'Customer is required.');
                }
                if (this.locationMode === 'none' || !this.form.location_id) {
                    this.$set(this.errors, 'location_id', 'Location is required.');
                }
                if (!this.form.sale_date) {
                    this.$set(this.errors, 'sale_date', 'Sale date is required.');
                }
                if (this.form.lines.length === 0) {
                    this.serverError = 'Add at least one product to the cart.';
                    return false;
                }
                if (intendedStatus === 'completed' && this.totals.balance > 0.0001) {
                    this.serverError = 'Cannot complete a sale with an outstanding balance. Save as Posted instead, or record full payment.';
                    return false;
                }
                return Object.keys(this.errors).length === 0;
            },

            async submit(status) {
                this.serverError = null;
                this.wasValidated = true;
                this.form.status = status;
                if (!this.validate(status)) return;
                this.submitting = true;

                const payload = {
                    sale_date:       this.form.sale_date,
                    customer_id:     this.form.customer_id,
                    location_id:     this.form.location_id,
                    channel_id:      this.form.channel_id,
                    salesperson_id:  this.form.salesperson_id,
                    tax_type:        this.form.tax_type,
                    shipping_charge: Number(this.form.shipping_charge) || 0,
                    note:            this.form.note,
                    status,
                    lines: this.form.lines.map((l) => ({
                        product_id:          l.product_id,
                        purchase_product_id: l.purchase_product_id,
                        barcode:             l.barcode,
                        qty:                 Number(l.qty) || 1,
                        carat_weight:        (l.carat_weight === '' || l.carat_weight === null || l.carat_weight === undefined) ? null : Number(l.carat_weight),
                        unit_price:          Number(l.unit_price) || 0,
                        tax_percent:         Number(l.tax_percent) || 0,
                        discount_percent:    Number(l.discount_percent) || 0,
                    })),
                    payments: this.form.payments
                        .filter((p) => Math.abs(Number(p.amount) || 0) > 0.001)
                        .map((p) => ({
                            payment_date:     p.payment_date,
                            amount:           Number(p.amount),
                            payment_method:   p.payment_method,
                            reference_number: p.reference_number || null,
                        })),
                };

                try {
                    const res = await fetch('{{ route('sales.store') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(payload),
                    });

                    if (res.status === 422) {
                        const data = await res.json();
                        if (data.errors) {
                            this.applyServerErrors(data.errors);
                            const count = Object.keys(data.errors).length;
                            this.serverError = count > 1
                                ? `Please fix the ${count} highlighted fields below.`
                                : (data.message || 'Please fix the highlighted field below.');
                        } else {
                            this.serverError = data.message || 'Please fix the highlighted fields.';
                        }
                        this.submitting = false;
                        return;
                    }
                    if (!res.ok) {
                        const data = await res.json().catch(() => ({}));
                        this.serverError = data.message || 'Something went wrong.';
                        this.submitting = false;
                        return;
                    }
                    const data = await res.json();
                    window.location.href = data.redirect || '{{ route('sales.index') }}';
                } catch (err) {
                    this.serverError = 'Network error. Please try again.';
                    this.submitting = false;
                }
            },
        },
    });
});
</script>
@endpush
