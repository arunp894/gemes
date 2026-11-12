@extends('layout.app')

@section('title', 'New Memo')

@section('content')
<div class="container-fluid rentals-page" id="rentalApp">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1"><h4 class="page-main-title m-0">New Stone Memo</h4></div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('rentals.index') }}">Stone Memo</a></li>
                <li class="breadcrumb-item active">New</li>
            </ol>
        </div>
    </div>

    @if ($locations->isEmpty())
        <div class="alert alert-warning">You are not assigned to any location, so you cannot create a memo.</div>
    @else
    <div class="alert alert-danger py-2" v-if="error" v-text="error"></div>

    <div class="row g-3">
        <div class="col-lg-8">

            {{-- Customer + period --}}
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Customer &amp; period</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6 position-relative">
                            <label class="form-label">Customer <span class="text-danger">*</span></label>
                            <div v-if="customer" class="d-flex align-items-center justify-content-between border rounded px-2 py-1">
                                <span><strong v-text="customer.display_name"></strong> <small class="text-muted" v-text="customer.phone"></small></span>
                                <button type="button" class="btn btn-sm btn-link text-danger" @click="customer = null">change</button>
                            </div>
                            <template v-else>
                                <input type="text" class="form-control" placeholder="Search name, phone, code..." v-model="customerTerm" @input="searchCustomers">
                                <div class="list-group position-absolute w-100 shadow" style="z-index:20" v-if="customerResults.length">
                                    <button type="button" class="list-group-item list-group-item-action" v-for="c in customerResults" :key="c.id" @click="pickCustomer(c)">
                                        <span v-text="c.display_name"></span> <small class="text-muted" v-text="c.phone || c.customer_code"></small>
                                    </button>
                                </div>
                            </template>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Location <span class="text-danger">*</span></label>
                            <select class="form-select" v-model="form.location_id">
                                @foreach ($locations as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">From <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" v-model="form.start_at">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">To (due back) <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" v-model="form.due_at">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stones --}}
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Stones</h5></div>
                <div class="card-body">
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="ti ti-barcode"></i></span>
                        <input type="text" class="form-control" placeholder="Scan or type lot code / barcode and press Enter" v-model="scan" @keydown.enter.prevent="addStone" ref="scan">
                        <button type="button" class="btn btn-primary" @click="addStone">Add</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Lot</th><th>Stone</th><th style="width:90px">Pcs</th><th style="width:110px">CT</th>
                                <th style="width:110px">Rate / CT ({{ $currency }})</th><th style="width:120px">Memo Total ({{ $currency }})</th><th style="width:120px">Deposit ({{ $currency }})</th><th></th></tr></thead>
                            <tbody>
                                <tr v-if="!lines.length"><td colspan="8" class="text-center text-muted py-3">No stones added yet.</td></tr>
                                <tr v-for="(l, i) in lines" :key="l.purchase_product_id">
                                    <td><code v-text="l.lot"></code></td>
                                    <td><span v-text="l.title"></span><div class="text-muted fs-xxs">on hand <span v-text="l.on_hand"></span> pcs<span v-if="l.remaining_carat !== null"> / <span v-text="l.remaining_carat"></span> ct</span></div></td>
                                    <td><input type="number" min="1" :max="l.on_hand" class="form-control form-control-sm" v-model.number="l.qty"></td>
                                    <td><input type="number" min="0" step="0.001" class="form-control form-control-sm" v-model.number="l.carat_weight" :disabled="l.remaining_carat === null"></td>
                                    <td><input type="number" min="0" step="0.01" class="form-control form-control-sm" v-model.number="l.rent_rate"></td>
                                    <td class="fw-semibold text-end" v-text="lineTotal(l).toFixed(2)"></td>
                                    <td><input type="number" min="0" step="0.01" class="form-control form-control-sm" v-model.number="l.deposit_amount"></td>
                                    <td><button type="button" class="btn btn-sm btn-link text-danger" @click="lines.splice(i, 1)"><i class="ti ti-trash"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <label class="form-label">Note</label>
                    <textarea class="form-control" rows="2" v-model="form.note" maxlength="2000"></textarea>
                </div>
            </div>
        </div>

        {{-- Money --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Payment</h5></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between"><span>Memo fee</span><strong>{{ $currency }} @{{ rentTotal.toFixed(2) }}</strong></div>
                    <div class="d-flex justify-content-between"><span>Refundable deposit</span><strong>{{ $currency }} @{{ depositTotal.toFixed(2) }}</strong></div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3"><span>To collect now</span><strong class="fs-5">{{ $currency }} @{{ (rentTotal + depositTotal).toFixed(2) }}</strong></div>

                    <label class="form-label">Memo collected</label>
                    <input type="number" min="0" step="0.01" class="form-control mb-2" v-model.number="form.rent_paid" @input="paidTouched = true">
                    <label class="form-label">Deposit collected</label>
                    <input type="number" min="0" step="0.01" class="form-control mb-2" v-model.number="form.deposit_paid" @input="paidTouched = true">
                    <label class="form-label">Method</label>
                    <select class="form-select mb-3" v-model="form.payment_method">
                        @foreach ($paymentMethods as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    <button type="button" class="btn btn-primary w-100" :disabled="saving" @click="save">
                        <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>Save memo &amp; give stones out
                    </button>
                    <p class="text-muted small mt-2 mb-0">Saving takes the stones out of stock until they are returned.</p>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
$(function () {
    if (!document.getElementById('rentalApp') || !@json($locations->isNotEmpty())) return;

    const pad = n => String(n).padStart(2, '0');
    const local = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    const now = new Date(), tomorrow = new Date(Date.now() + 86400000);
    const csrf = $('meta[name="csrf-token"]').attr('content');

    new Vue({
        el: '#rentalApp',
        data: {
            customer: null, customerTerm: '', customerResults: [],
            scan: '', lines: [], error: '', saving: false, paidTouched: false,
            form: { location_id: @json($defaultLocId), start_at: local(now), due_at: local(tomorrow), note: '',
                    rent_paid: 0, deposit_paid: 0, payment_method: 'cash' },
        },
        computed: {
            rentTotal()    { return this.lines.reduce((s, l) => s + this.lineTotal(l), 0); },
            depositTotal() { return this.lines.reduce((s, l) => s + (Number(l.deposit_amount) || 0), 0); },
        },
        watch: {
            // Pre-fill what to collect from the totals until the user types their own amounts.
            rentTotal(v)    { if (!this.paidTouched) this.form.rent_paid = v; },
            depositTotal(v) { if (!this.paidTouched) this.form.deposit_paid = v; },
        },
        methods: {
            // Weighed stone: CT x rate (the CT is already the line total). No carat: pcs x rate.
            lineTotal(l) {
                const rate = Number(l.rent_rate) || 0, ct = Number(l.carat_weight) || 0;
                return l.remaining_carat !== null && ct > 0 ? ct * rate : (Number(l.qty) || 0) * rate;
            },
            searchCustomers() {
                clearTimeout(this._t);
                this._t = setTimeout(async () => {
                    const r = await fetch(`{{ route('customers.search') }}?q=${encodeURIComponent(this.customerTerm)}`, { headers: { Accept: 'application/json' } });
                    const d = await r.json();
                    if (d.ok) this.customerResults = d.items;
                }, 220);
            },
            pickCustomer(c) { this.customer = c; this.customerResults = []; this.customerTerm = ''; },
            async addStone() {
                const code = this.scan.trim();
                if (!code) return;
                this.error = '';
                const r = await fetch(`{{ route('rentals.lookup-barcode') }}?barcode=${encodeURIComponent(code)}`, { headers: { Accept: 'application/json' } });
                const d = await r.json();
                this.scan = '';
                if (!r.ok || !d.ok) { this.error = d.message || 'Stone not found.'; return; }
                const ppId = d.inventory.purchase_product_id;
                if (!ppId) { this.error = 'Scan the lot code of the exact stone (this barcode is not tied to one piece).'; return; }
                if (this.lines.some(l => l.purchase_product_id === ppId)) { this.error = 'That stone is already added.'; return; }
                if (!(d.inventory.on_hand > 0)) { this.error = d.product.title + ' is not in stock (sold or already on memo).'; return; }
                this.lines.push({
                    purchase_product_id: ppId, lot: d.barcode, title: d.product.title,
                    on_hand: d.inventory.on_hand, remaining_carat: d.remaining_carat,
                    qty: d.inventory.on_hand, carat_weight: d.remaining_carat, rent_rate: 0, deposit_amount: 0,
                });
                this.$nextTick(() => this.$refs.scan && this.$refs.scan.focus());
            },
            async save() {
                this.error = '';
                if (!this.customer) { this.error = 'Choose a customer.'; return; }
                if (!this.lines.length) { this.error = 'Add at least one stone.'; return; }
                this.saving = true;
                try {
                    const r = await fetch('{{ route('rentals.store') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ ...this.form, customer_id: this.customer.id, lines: this.lines }),
                    });
                    const d = await r.json();
                    if (r.ok && d.ok) { window.location = d.redirect; return; }
                    this.error = d.message || Object.values(d.errors || {}).flat().join(' ') || 'Could not save.';
                } catch (e) { this.error = 'Network error.'; }
                this.saving = false;
            },
        },
    });
});
</script>
@endpush
