@extends('layout.app')

@section('title', 'Edit Sale ' . $sale->sale_number)

@section('content')
<div class="container-fluid" id="salesTerminalApp">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">
                <i class="ti ti-edit text-primary me-2"></i>Edit Sale {{ $sale->sale_number }}
                <span class="badge {{ $sale->statusBadgeClass() }} ms-2">{{ $sale->statusLabel() }}</span>
            </h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Sales</a></li>
                <li class="breadcrumb-item"><a href="{{ route('sales.show', $sale) }}">{{ $sale->sale_number }}</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </div>
    </div>

    @if (! $sale->isEditable())
        <div class="alert alert-warning">
            <i class="ti ti-alert-triangle me-1"></i>
            Only draft sales can be edited. Use status actions on the show page for posted/completed sales.
        </div>
    @endif

    <form id="saleForm" novalidate @submit.prevent="submit('draft')" :class="{ 'was-validated': wasValidated }">

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Sale Date</label>
                                <input type="date" class="form-control" v-model="form.sale_date">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Sale #</label>
                                <input type="text" class="form-control bg-light" :value="form.sale_number_preview" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Location</label>

                                {{-- NONE --}}
                                @if ($locationMode === 'none')
                                <div class="alert alert-danger mb-0 py-2 px-3 d-flex align-items-center gap-2">
                                    <i class="ti ti-lock fs-18"></i>
                                    <small><strong>No location access.</strong> Contact an administrator.</small>
                                </div>

                                {{-- SINGLE --}}
                                @elseif ($locationMode === 'single')
                                <div class="form-control bg-light d-flex align-items-center gap-2 h-auto py-2">
                                    <i class="ti ti-map-pin text-primary"></i>
                                    <div class="lh-sm">
                                        <div class="fw-semibold">{{ $defaultLocation->name }}</div>
                                        <small class="text-muted">{{ $defaultLocation->location_code }}</small>
                                    </div>
                                </div>

                                {{-- MULTIPLE --}}
                                @else
                                <select class="form-select" v-model.number="form.location_id">
                                    <option v-for="l in userLocations" :key="l.id" :value="l.id">
                                        @{{ l.name }} (@{{ l.location_code }})@{{ l.is_default ? ' ★' : '' }}
                                    </option>
                                </select>
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
                                <input type="number" min="0" step="0.01" class="form-control" v-model.number="form.shipping_charge">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-light d-flex align-items-center gap-2">
                        <i class="ti ti-barcode fs-18 text-primary"></i>
                        <h5 class="card-title mb-0">Scan or Search Product</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-6">
                                <label class="form-label">Barcode</label>
                                <input ref="barcodeInput" type="text" class="form-control form-control-lg"
                                    v-model="barcodeInput"
                                    placeholder="Scan or type barcode then Enter"
                                    @keyup.enter.prevent="onBarcodeEnter">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Search by name / SKU</label>
                                <div class="position-relative">
                                    <input type="text" class="form-control" v-model="productSearch"
                                        @input="onSearchInput" @focus="onSearchInput">
                                    <ul v-if="searchResults.length"
                                        class="list-group position-absolute w-100 mt-1 shadow-sm"
                                        style="z-index: 1050; max-height: 280px; overflow-y: auto;">
                                        <li v-for="p in searchResults" :key="p.id"
                                            class="list-group-item list-group-item-action"
                                            @mousedown.prevent="addProductBySearch(p)" style="cursor: pointer;">
                                            <div class="fw-semibold">@{{ p.title }}</div>
                                            <small class="text-muted">SKU: @{{ p.sku }}</small>
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

                <div class="card">
                    <div class="card-header border-light"><h5 class="card-title mb-0">Cart</h5></div>
                    <div v-if="form.lines.length === 0" class="card-body text-center text-muted py-5">
                        <i class="ti ti-shopping-cart-off fs-1 d-block mb-2"></i>No products yet.
                    </div>
                    <div v-else class="table-responsive">
                        <table class="table table-custom align-middle mb-0">
                            <thead class="bg-light bg-opacity-25 thead-sm">
                                <tr class="text-uppercase fs-xxs">
                                    <th>Product</th><th>Barcode</th>
                                    <th class="text-end">Qty</th><th class="text-end">Unit Price</th>
                                    <th class="text-end">Disc %</th><th class="text-end">Tax %</th>
                                    <th class="text-end">Total</th><th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(line, idx) in form.lines" :key="idx">
                                    <td>
                                        <div class="fw-semibold">@{{ line.product_title }}</div>
                                        <small class="text-muted">SKU: @{{ line.product_sku }}</small>
                                    </td>
                                    <td><code class="small">@{{ line.barcode || '—' }}</code></td>
                                    <td><input type="number" min="1" step="1" class="form-control form-control-sm text-end" v-model.number="line.qty"></td>
                                    <td><input type="number" min="0" step="0.01" class="form-control form-control-sm text-end" v-model.number="line.unit_price"></td>
                                    <td><input type="number" min="0" max="100" step="0.01" class="form-control form-control-sm text-end" v-model.number="line.discount_percent"></td>
                                    <td><input type="number" min="0" max="100" step="0.01" class="form-control form-control-sm text-end" v-model.number="line.tax_percent"></td>
                                    <td class="text-end fw-semibold">@{{ formatMoney(lineTotal(line)) }}</td>
                                    <td>
                                        <button type="button" class="btn btn-default btn-icon btn-sm text-danger" @click="removeLine(idx)">
                                            <i class="ti ti-x"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-light"><h5 class="card-title mb-0">Note</h5></div>
                    <div class="card-body">
                        <textarea class="form-control" rows="2" v-model="form.note" maxlength="2000"></textarea>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header border-light"><h5 class="card-title mb-0">Customer</h5></div>
                    <div class="card-body">
                        <div v-if="!selectedCustomer" class="position-relative">
                            <input type="text" class="form-control" v-model="customerSearch"
                                placeholder="Search name / phone / code…"
                                @input="onCustomerSearchInput" @focus="onCustomerSearchInput">
                            <ul v-if="customerResults.length" class="list-group position-absolute w-100 mt-1 shadow-sm"
                                style="z-index: 1050; max-height: 280px; overflow-y: auto;">
                                <li v-for="c in customerResults" :key="c.id"
                                    class="list-group-item list-group-item-action"
                                    @mousedown.prevent="selectCustomer(c)" style="cursor: pointer;">
                                    <div class="fw-semibold">@{{ c.display_name }}</div>
                                    <small class="text-muted">@{{ c.customer_code }}<span v-if="c.phone"> · @{{ c.phone }}</span></small>
                                </li>
                            </ul>
                        </div>
                        <div v-else>
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold">@{{ selectedCustomer.display_name }}</div>
                                    <small class="text-muted">@{{ selectedCustomer.customer_code }}</small>
                                </div>
                                <button type="button" class="btn btn-default btn-icon btn-sm" @click="clearCustomer">
                                    <i class="ti ti-x"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

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
                            <dd class="col-5 text-end fs-base fw-bold pt-2 border-top mt-2">@{{ formatMoney(totals.grand) }}</dd>
                        </dl>
                    </div>
                </div>

                <div v-if="serverError" class="alert alert-danger">@{{ serverError }}</div>

                <div class="d-grid gap-2 mb-4">
                    <button type="button" class="btn btn-primary" :disabled="submitting" @click="submit('draft')">
                        <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="ti ti-device-floppy me-1"></i> Save Changes
                    </button>
                    <a href="{{ route('sales.show', $sale) }}" class="btn btn-link text-muted">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    new Vue({
        el: '#salesTerminalApp',
        data: {
            userLocations: @json($userLocations),
            salespeople:   @json($salespeople),
            channels:      @json($channels),

            barcodeInput: '',
            productSearch: '',
            searchResults: [],
            _searchTimer: null,

            customerSearch: '',
            customerResults: [],
            selectedCustomer: @json($sale->customer ? [
                'id'            => $sale->customer->id,
                'customer_code' => $sale->customer->customer_code,
                'display_name'  => $sale->customer->display_name,
                'phone'         => $sale->customer->phone,
                'email'         => $sale->customer->email,
            ] : null),
            _customerTimer: null,

            scannerMessage: '',
            scannerAlertClass: 'alert-info',
            scannerIconClass: 'ti ti-info-circle',

            form: {
                sale_date:           @json(optional($sale->sale_date)->toDateString()),
                sale_number_preview: @json($sale->sale_number),
                customer_id:         @json($sale->customer_id),
                location_id:         @json($sale->location_id),
                channel_id:          @json($sale->channel_id),
                salesperson_id:      @json($sale->salesperson_id),
                tax_type:            @json($sale->tax_type),
                shipping_charge:     @json((float) $sale->shipping_charge),
                note:                @json($sale->note),
                lines: @json($sale->lines->map(fn ($l) => [
                    'product_id'          => $l->product_id,
                    'product_title'       => optional($l->product)->title,
                    'product_sku'         => optional($l->product)->sku,
                    'purchase_product_id' => $l->purchase_product_id,
                    'barcode'             => $l->barcode,
                    'qty'                 => $l->qty,
                    'unit_price'          => (float) $l->unit_price,
                    'cost_price'          => (float) $l->cost_price,
                    'tax_percent'         => (float) $l->tax_percent,
                    'discount_percent'    => (float) $l->discount_percent,
                ])),
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
                    const qty = Number(l.qty) || 0, price = Number(l.unit_price) || 0;
                    const dPct = Number(l.discount_percent) || 0, tPct = Number(l.tax_percent) || 0;
                    const gross = qty * price;
                    const dAmt = +(gross * dPct / 100).toFixed(2);
                    const base = gross - dAmt;
                    const tAmt = +(base * tPct / 100).toFixed(2);
                    subtotal += gross; discount += dAmt; tax += tAmt;
                });
                const ship = Number(this.form.shipping_charge) || 0;
                return {
                    subtotal: +subtotal.toFixed(2),
                    discount: +discount.toFixed(2),
                    tax:      +tax.toFixed(2),
                    grand:    +(subtotal - discount + tax + ship).toFixed(2),
                };
            },
        },
        methods: {
            formatMoney(v) { return Number(v || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            lineTotal(l) {
                const qty = Number(l.qty) || 0, price = Number(l.unit_price) || 0;
                const dPct = Number(l.discount_percent) || 0, tPct = Number(l.tax_percent) || 0;
                const gross = qty * price, dAmt = gross * dPct / 100;
                const base = gross - dAmt, tAmt = base * tPct / 100;
                return +(base + tAmt).toFixed(2);
            },
            async onBarcodeEnter() {
                const code = this.barcodeInput.trim();
                if (!code) return;
                try {
                    const res = await fetch(`{{ route('sales.lookup-barcode') }}?barcode=${encodeURIComponent(code)}`, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    if (!res.ok || !data.ok) {
                        this.scannerAlertClass = 'alert-danger'; this.scannerIconClass = 'ti ti-alert-circle';
                        this.scannerMessage = data.message || 'Not found.'; return;
                    }
                    const p = data.product, inv = data.inventory;
                    this.form.lines.push({
                        product_id: p.id, product_title: p.title, product_sku: p.sku,
                        purchase_product_id: inv ? inv.purchase_product_id : null,
                        barcode: data.barcode || null,
                        qty: 1,
                        unit_price: inv && inv.cost_price ? +(Number(inv.cost_price) * 1.3).toFixed(2) : 0,
                        cost_price: inv ? Number(inv.cost_price || 0) : 0,
                        tax_percent: 0, discount_percent: 0,
                    });
                    this.barcodeInput = '';
                    this.scannerAlertClass = 'alert-success'; this.scannerIconClass = 'ti ti-check';
                    this.scannerMessage = `Added: ${p.title}`;
                    setTimeout(() => this.scannerMessage = '', 1500);
                } catch { this.scannerMessage = 'Network error.'; }
            },
            onSearchInput() {
                clearTimeout(this._searchTimer);
                const t = this.productSearch.trim();
                if (t.length < 2) { this.searchResults = []; return; }
                this._searchTimer = setTimeout(async () => {
                    const res = await fetch(`{{ route('sales.search-products') }}?q=${encodeURIComponent(t)}`, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    if (data.ok) this.searchResults = data.items;
                }, 220);
            },
            addProductBySearch(p) {
                this.form.lines.push({
                    product_id: p.id, product_title: p.title, product_sku: p.sku,
                    purchase_product_id: null, barcode: null,
                    qty: 1, unit_price: 0, cost_price: 0,
                    tax_percent: 0, discount_percent: 0,
                });
                this.productSearch = ''; this.searchResults = [];
            },
            removeLine(idx) { this.form.lines.splice(idx, 1); },

            onCustomerSearchInput() {
                clearTimeout(this._customerTimer);
                const t = this.customerSearch.trim();
                this._customerTimer = setTimeout(async () => {
                    const res = await fetch(`{{ route('customers.search') }}?q=${encodeURIComponent(t)}`, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    if (data.ok) this.customerResults = data.items;
                }, 220);
            },
            selectCustomer(c) { this.selectedCustomer = c; this.form.customer_id = c.id; this.customerResults = []; this.customerSearch = ''; },
            clearCustomer() { this.selectedCustomer = null; this.form.customer_id = null; },

            async submit() {
                this.serverError = null; this.wasValidated = true;
                if (this.form.lines.length === 0) { this.serverError = 'Add at least one product.'; return; }
                if (!this.form.customer_id) { this.serverError = 'Customer is required.'; return; }
                if (!this.form.location_id) { this.serverError = 'Location is required.'; return; }
                this.submitting = true;

                const payload = {
                    sale_date: this.form.sale_date,
                    customer_id: this.form.customer_id,
                    location_id: this.form.location_id,
                    channel_id: this.form.channel_id,
                    salesperson_id: this.form.salesperson_id,
                    tax_type: this.form.tax_type,
                    shipping_charge: Number(this.form.shipping_charge) || 0,
                    note: this.form.note,
                    lines: this.form.lines.map((l) => ({
                        product_id: l.product_id,
                        purchase_product_id: l.purchase_product_id,
                        barcode: l.barcode,
                        qty: Number(l.qty) || 1,
                        unit_price: Number(l.unit_price) || 0,
                        tax_percent: Number(l.tax_percent) || 0,
                        discount_percent: Number(l.discount_percent) || 0,
                    })),
                };

                try {
                    const res = await fetch('{{ route('sales.update', $sale) }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json',
                            'Content-Type': 'application/json', 'X-HTTP-Method-Override': 'PUT',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ ...payload, _method: 'PUT' }),
                    });
                    if (!res.ok) {
                        const data = await res.json().catch(() => ({}));
                        this.serverError = data.message || 'Something went wrong.'; this.submitting = false; return;
                    }
                    const data = await res.json();
                    window.location.href = data.redirect || '{{ route('sales.show', $sale) }}';
                } catch (err) { this.serverError = 'Network error.'; this.submitting = false; }
            },
        },
    });
});
</script>
@endpush
