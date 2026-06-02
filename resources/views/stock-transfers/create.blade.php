@extends('layout.app')

@section('title', 'New Stock Transfer')

@section('content')
<div class="container-fluid" id="transferApp">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">
                <i class="ti ti-transfer text-primary me-2"></i>New Stock Transfer
            </h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('stock-transfers.index') }}">Transfers</a></li>
                <li class="breadcrumb-item active">New</li>
            </ol>
        </div>
    </div>

    <form id="transferForm" novalidate @submit.prevent="submit('in_transit')" :class="{ 'was-validated': wasValidated }">

        <div class="row g-3">
            <div class="col-xl-8">

                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Transfer Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" v-model="form.transfer_date"
                                    :class="{ 'is-invalid': errors.transfer_date }" required>
                                <div class="invalid-feedback">@{{ errors.transfer_date }}</div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">From Location <span class="text-danger">*</span></label>
                                <select class="form-select" v-model.number="form.from_location_id"
                                    @change="onSourceChange"
                                    :class="{ 'is-invalid': errors.from_location_id }" required>
                                    <option :value="null">— Select source —</option>
                                    <option v-for="l in locations" :key="'f-' + l.id" :value="l.id"
                                        :disabled="l.id === form.to_location_id">
                                        @{{ l.name }} (@{{ l.location_code }})
                                    </option>
                                </select>
                                <div class="invalid-feedback">@{{ errors.from_location_id }}</div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">To Location <span class="text-danger">*</span></label>
                                <select class="form-select" v-model.number="form.to_location_id"
                                    :class="{ 'is-invalid': errors.to_location_id }" required>
                                    <option :value="null">— Select destination —</option>
                                    <option v-for="l in locations" :key="'t-' + l.id" :value="l.id"
                                        :disabled="l.id === form.from_location_id">
                                        @{{ l.name }} (@{{ l.location_code }})
                                    </option>
                                </select>
                                <div class="invalid-feedback">@{{ errors.to_location_id }}</div>
                            </div>

                            <div class="col-md-3" v-if="form.from_location_id && form.to_location_id">
                                <label class="form-label d-block opacity-0">.</label>
                                <div class="alert alert-info py-2 px-3 mb-0 small">
                                    <i class="ti ti-arrow-right me-1"></i>
                                    @{{ locationNameById(form.from_location_id) }} → @{{ locationNameById(form.to_location_id) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Scanner --}}
                <div class="card">
                    <div class="card-header border-light d-flex align-items-center gap-2">
                        <i class="ti ti-barcode fs-18 text-primary"></i>
                        <h5 class="card-title mb-0">Scan Piece</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-8">
                                <label class="form-label">
                                    Barcode
                                    <small class="text-muted">Scans only match pieces present at the source location.</small>
                                </label>
                                <input ref="barcodeInput" type="text" class="form-control form-control-lg"
                                    v-model="barcodeInput"
                                    :disabled="!form.from_location_id"
                                    :placeholder="form.from_location_id ? 'Scan barcode and press Enter' : 'Select source location first'"
                                    @keyup.enter.prevent="onBarcodeEnter">
                            </div>
                            <div class="col-md-4" v-if="scannerMessage">
                                <div class="alert mb-0 py-2" :class="scannerAlertClass">
                                    <i :class="scannerIconClass" class="me-1"></i>@{{ scannerMessage }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Lines --}}
                <div class="card">
                    <div class="card-header border-light d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Pieces to Transfer</h5>
                        <span class="text-muted small" v-if="form.lines.length">
                            @{{ form.lines.length }} line@{{ form.lines.length === 1 ? '' : 's' }}
                        </span>
                    </div>

                    <div v-if="form.lines.length === 0" class="card-body text-center text-muted py-5">
                        <i class="ti ti-package-off fs-1 d-block mb-2"></i>
                        Scan a barcode to add pieces to this transfer.
                    </div>

                    <div v-else class="table-responsive">
                        <table class="table table-custom align-middle mb-0">
                            <thead class="bg-light bg-opacity-25 thead-sm">
                                <tr class="text-uppercase fs-xxs">
                                    <th>Product</th>
                                    <th>Barcode</th>
                                    <th class="text-end" style="width: 10%;">On Hand</th>
                                    <th class="text-end" style="width: 10%;">Qty</th>
                                    <th style="width: 18%;">To Rack</th>
                                    <th>Notes</th>
                                    <th class="text-center" style="width: 1%;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(line, idx) in form.lines" :key="idx"
                                    :class="{ 'table-warning': line.qty > line.on_hand }">
                                    <td>
                                        <div class="fw-semibold">@{{ line.product_title }}</div>
                                        <small class="text-muted">SKU: @{{ line.product_sku }}</small>
                                    </td>
                                    <td><code class="small">@{{ line.barcode || '—' }}</code></td>
                                    <td class="text-end">@{{ line.on_hand }}</td>
                                    <td>
                                        <input type="number" min="1" :max="line.on_hand" step="1"
                                            class="form-control form-control-sm text-end"
                                            v-model.number="line.qty">
                                        <small v-if="line.qty > line.on_hand" class="d-block text-danger">
                                            <i class="ti ti-alert-triangle"></i> exceeds on-hand
                                        </small>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm" v-model.number="line.to_rack_id">
                                            <option :value="null">— None —</option>
                                            <option v-for="r in racks" :key="r.id" :value="r.id">
                                                @{{ r.code }} — @{{ r.name }}
                                            </option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm"
                                            v-model="line.notes" maxlength="500">
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

                <div class="card">
                    <div class="card-header border-light"><h5 class="card-title mb-0">Note</h5></div>
                    <div class="card-body">
                        <textarea class="form-control" rows="2" v-model="form.note" maxlength="2000"
                            placeholder="Optional note for this transfer"></textarea>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">

                <div class="card">
                    <div class="card-header border-light"><h5 class="card-title mb-0">Summary</h5></div>
                    <div class="card-body">
                        <dl class="row mb-0 small">
                            <dt class="col-7 text-muted">Lines</dt>
                            <dd class="col-5 text-end">@{{ form.lines.length }}</dd>
                            <dt class="col-7 text-muted">Total pieces</dt>
                            <dd class="col-5 text-end">@{{ totalPieces }}</dd>
                            <dt class="col-7 text-muted">Distinct products</dt>
                            <dd class="col-5 text-end">@{{ distinctProducts }}</dd>
                        </dl>
                    </div>
                </div>

                <div v-if="serverError" class="alert alert-danger">@{{ serverError }}</div>

                <div class="d-grid gap-2 mb-4">
                    <button type="button" class="btn btn-lg btn-primary" :disabled="submitting || !canPost"
                        @click="submit('in_transit')">
                        <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="ti ti-send me-1"></i>
                        Post Transfer
                    </button>
                    <button type="button" class="btn btn-light" :disabled="submitting" @click="submit('draft')">
                        <i class="ti ti-device-floppy me-1"></i> Save as Draft
                    </button>
                    <a href="{{ route('stock-transfers.index') }}" class="btn btn-link text-muted">Cancel and go back</a>
                </div>

                <div class="alert alert-info small">
                    <strong>How transfers work:</strong>
                    <ol class="ps-3 mb-0 mt-1">
                        <li>Post → pieces leave source location (in transit)</li>
                        <li>Receive at destination → pieces arrive on-hand</li>
                        <li>Cancel before receive → pieces return to source</li>
                    </ol>
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
        el: '#transferApp',
        data: {
            locations: @json($locations),
            racks:     @json($racks),

            barcodeInput: '',
            scannerMessage: '',
            scannerAlertClass: 'alert-info',
            scannerIconClass: 'ti ti-info-circle',

            form: {
                transfer_date:    new Date().toISOString().slice(0, 10),
                from_location_id: null,
                to_location_id:   null,
                note:             '',
                lines:            [],
            },

            errors: {},
            submitting: false,
            wasValidated: false,
            serverError: null,
        },
        computed: {
            totalPieces() {
                return this.form.lines.reduce((s, l) => s + (Number(l.qty) || 0), 0);
            },
            distinctProducts() {
                const ids = new Set(this.form.lines.map((l) => l.product_id));
                return ids.size;
            },
            canPost() {
                if (this.form.lines.length === 0) return false;
                if (!this.form.from_location_id || !this.form.to_location_id) return false;
                if (this.form.from_location_id === this.form.to_location_id) return false;
                return this.form.lines.every((l) => Number(l.qty) > 0 && Number(l.qty) <= Number(l.on_hand));
            },
        },
        mounted() {
            this.$nextTick(() => this.$refs.barcodeInput?.focus());
        },
        methods: {
            locationNameById(id) {
                const l = this.locations.find((x) => x.id === id);
                return l ? l.name : '';
            },

            onSourceChange() {
                // Clear cart when source changes — pieces from old source
                // aren't valid against the new one.
                if (this.form.lines.length > 0) {
                    if (!confirm('Changing source location will clear the cart. Continue?')) {
                        return;
                    }
                    this.form.lines = [];
                }
                this.$nextTick(() => this.$refs.barcodeInput?.focus());
            },

            async onBarcodeEnter() {
                const code = this.barcodeInput.trim();
                if (!code || !this.form.from_location_id) return;
                this.scannerMessage = '';

                try {
                    const params = new URLSearchParams({
                        barcode:          code,
                        from_location_id: String(this.form.from_location_id),
                    });
                    const url = `{{ route('stock-transfers.lookup-barcode') }}?${params.toString()}`;
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();

                    if (!res.ok || !data.ok) {
                        this.scannerAlertClass = 'alert-danger';
                        this.scannerIconClass  = 'ti ti-alert-circle';
                        this.scannerMessage    = data.message || 'Barcode not found.';
                        return;
                    }

                    // If piece already in cart, bump qty (capped at on_hand).
                    const existing = this.form.lines.find(
                        (l) => l.purchase_product_id === data.piece.purchase_product_id
                    );
                    if (existing) {
                        if (existing.qty < existing.on_hand) {
                            existing.qty += 1;
                        }
                    } else {
                        this.form.lines.push({
                            purchase_product_id: data.piece.purchase_product_id,
                            product_id:          data.piece.product_id,
                            product_title:      data.piece.product_title,
                            product_sku:        data.piece.product_sku,
                            barcode:            data.piece.barcode,
                            on_hand:            data.piece.on_hand,
                            qty:                1,
                            to_rack_id:         null,
                            notes:              '',
                        });
                    }

                    this.barcodeInput = '';
                    this.scannerAlertClass = 'alert-success';
                    this.scannerIconClass  = 'ti ti-check';
                    this.scannerMessage    = `Added: ${data.piece.product_title} (on hand: ${data.piece.on_hand})`;
                    setTimeout(() => { this.scannerMessage = ''; }, 1800);
                } catch (err) {
                    this.scannerAlertClass = 'alert-danger';
                    this.scannerIconClass  = 'ti ti-alert-circle';
                    this.scannerMessage    = 'Network error during lookup.';
                }
            },

            removeLine(idx) { this.form.lines.splice(idx, 1); },

            validate() {
                this.errors = {};
                if (!this.form.transfer_date)    this.$set(this.errors, 'transfer_date',    'Transfer date is required.');
                if (!this.form.from_location_id) this.$set(this.errors, 'from_location_id', 'Source is required.');
                if (!this.form.to_location_id)   this.$set(this.errors, 'to_location_id',   'Destination is required.');
                if (this.form.from_location_id && this.form.from_location_id === this.form.to_location_id) {
                    this.$set(this.errors, 'to_location_id', 'Destination must differ from source.');
                }
                if (this.form.lines.length === 0) {
                    this.serverError = 'Add at least one piece to the transfer.';
                    return false;
                }
                for (const l of this.form.lines) {
                    if (Number(l.qty) > Number(l.on_hand)) {
                        this.serverError = `Quantity for ${l.product_title} exceeds on-hand stock.`;
                        return false;
                    }
                }
                return Object.keys(this.errors).length === 0;
            },

            async submit(status) {
                this.serverError = null;
                this.wasValidated = true;
                if (!this.validate()) return;
                this.submitting = true;

                const payload = {
                    transfer_date:    this.form.transfer_date,
                    from_location_id: this.form.from_location_id,
                    to_location_id:   this.form.to_location_id,
                    status,
                    note:             this.form.note,
                    lines: this.form.lines.map((l) => ({
                        purchase_product_id: l.purchase_product_id,
                        qty:                 Number(l.qty) || 1,
                        to_rack_id:          l.to_rack_id || null,
                        notes:               l.notes || null,
                    })),
                };

                try {
                    const res = await fetch('{{ route('stock-transfers.store') }}', {
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
                    window.location.href = data.redirect || '{{ route('stock-transfers.index') }}';
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
