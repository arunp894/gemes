@extends('layout.app')

@section('title', 'Sales Terminal')

@section('content')
<div class="container-fluid" id="salesTerminalApp">

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

    <form id="saleForm" novalidate @submit.prevent="submit('completed')" :class="{ 'was-validated': wasValidated }">

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
                                <label class="form-label">Sale Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control"
                                    v-model="form.sale_date"
                                    @change="refreshSaleNumber"
                                    :class="{ 'is-invalid': errors.sale_date }" required>
                                <div class="invalid-feedback">@{{ errors.sale_date }}</div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Sale #</label>
                                <input type="text" class="form-control bg-light"
                                    :value="form.sale_number_preview"
                                    readonly placeholder="auto-generated">
                                <small class="text-muted">Format: <code>SALE-YYYYMM-####</code></small>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Location <span class="text-danger">*</span></label>

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
                                <label class="form-label">Salesperson</label>
                                <select class="form-select" v-model.number="form.salesperson_id">
                                    <option :value="null">— Unassigned —</option>
                                    <option v-for="u in salespeople" :key="u.id" :value="u.id">@{{ u.name }}</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Sales Channel</label>
                                <select class="form-select" v-model.number="form.channel_id">
                                    <option :value="null">— No Channel —</option>
                                    <option v-for="c in channels" :key="c.id" :value="c.id">@{{ c.name }}</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Tax Type</label>
                                <select class="form-select" v-model="form.tax_type">
                                    <option value="none">No Tax</option>
                                    <option value="cgst_sgst">CGST + SGST</option>
                                    <option value="igst">IGST</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Shipping</label>
                                <input type="number" min="0" step="0.01" class="form-control"
                                    v-model.number="form.shipping_charge">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Status</label>
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
                                    Barcode
                                    <small class="text-muted">Scan and press Enter to add to cart.</small>
                                </label>
                                <input ref="barcodeInput" type="text" class="form-control form-control-lg"
                                    v-model="barcodeInput"
                                    placeholder="Scan or type barcode then Enter"
                                    @keyup.enter.prevent="onBarcodeEnter" autofocus>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Search by name / SKU</label>
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
                                    <th style="width: 24%;">Product</th>
                                    <th>Barcode</th>
                                    <th class="text-end" style="width: 8%;">Qty</th>
                                    <th class="text-end" style="width: 12%;">Unit Price</th>
                                    <th class="text-end" style="width: 8%;">Disc %</th>
                                    <th class="text-end" style="width: 8%;">Tax %</th>
                                    <th class="text-end" style="width: 14%;">Total</th>
                                    <th class="text-center" style="width: 1%;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(line, idx) in form.lines" :key="idx"
                                    :class="{ 'table-warning': line._stockWarning }">
                                    <td>
                                        <div class="fw-semibold">@{{ line.product_title }}</div>
                                        <small class="text-muted">SKU: @{{ line.product_sku }}</small>
                                        <small v-if="line.cost_price > 0" class="d-block text-muted">
                                            Cost: @{{ formatMoney(line.cost_price) }}
                                        </small>
                                    </td>
                                    <td>
                                        <code class="small">@{{ line.barcode || '—' }}</code>
                                        <small v-if="line._stockWarning" class="d-block text-danger">
                                            <i class="ti ti-alert-triangle"></i> Qty &gt; recorded stock
                                        </small>
                                    </td>
                                    <td>
                                        <input type="number" min="1" step="1"
                                            class="form-control form-control-sm text-end"
                                            v-model.number="line.qty"
                                            @input="checkStockWarning(idx)">
                                    </td>
                                    <td>
                                        <input type="number" min="0" step="0.01"
                                            class="form-control form-control-sm text-end"
                                            v-model.number="line.unit_price">
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
                            <button type="button" class="btn btn-light w-100" :disabled="submitting"
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

    new Vue({
        el: '#salesTerminalApp',
        data: {
            // Reference data
            userLocations, salespeople,
            locationMode,
            channels,
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
            wasValidated: false,
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
                return Number(v || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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

                this.form.lines.push({
                    product_id:          p.id,
                    product_title:       p.title,
                    product_sku:         p.sku,
                    purchase_product_id: inv ? inv.purchase_product_id : null,
                    barcode:             data.barcode || null,
                    qty:                 1,
                    unit_price:          inv && inv.cost_price ? +(Number(inv.cost_price) * 1.3).toFixed(2) : 0,
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
                    product_title:       p.title,
                    product_sku:         p.sku,
                    purchase_product_id: null,
                    barcode:             null,
                    qty:                 1,
                    unit_price:          0,
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

            checkStockWarning(idx) {
                const l = this.form.lines[idx];
                if (!l) return;
                l._stockWarning = (l.qty_on_record !== null && Number(l.qty) > Number(l.qty_on_record));
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
                            Object.keys(data.errors).forEach((k) => {
                                this.$set(this.errors, k.replace(/\.\d+(\.|$)/g, '$1'), data.errors[k][0]);
                            });
                        }
                        this.serverError = data.message || 'Please fix the highlighted fields.';
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
