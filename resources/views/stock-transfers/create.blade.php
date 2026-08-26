@extends('layout.app')

@section('title', 'New Stock Transfer')

@section('content')
<div class="container-fluid stock-transfers-form-page" id="transferApp">

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

                {{-- Search (lot code / barcode / product) --}}
                <div class="card">
                    <div class="card-header border-light d-flex align-items-center gap-2">
                        <i class="ti ti-search fs-18 text-primary"></i>
                        <h5 class="card-title mb-0">Search Stock</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 mb-3" v-if="form.from_location_id">
                            <div class="col-md-8">
                                <input ref="searchInput" type="text" class="form-control" v-model="searchTerm"
                                    placeholder="Lot code / barcode / product title…" @keyup.enter="searchPieces">
                            </div>
                            <div class="col-md-auto">
                                <button type="button" class="btn btn-soft-primary" :disabled="searching" @click="searchPieces">
                                    <i class="ti ti-search me-1"></i> Search
                                </button>
                            </div>
                        </div>
                        <div class="text-muted small" v-else>Select a source location above to search stock.</div>

                        <div class="table-responsive" v-if="searchResults.length">
                            <table class="table table-sm align-middle">
                                <thead class="bg-light bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th>Lot Code</th><th>Product</th>
                                        <th class="text-end">On Hand</th><th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="r in searchResults" :key="r.purchase_product_id">
                                        <td><code>@{{ r.lot_code || r.barcode || ('#' + r.purchase_product_id) }}</code></td>
                                        <td>@{{ r.product_title }}</td>
                                        <td class="text-end">@{{ r.on_hand }}</td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-soft-success" @click="addPieceFromSearch(r)" :disabled="isPieceAdded(r.purchase_product_id)">
                                                <i class="ti ti-plus"></i> @{{ isPieceAdded(r.purchase_product_id) ? 'Added' : 'Add' }}
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
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
                        Search stock above to add pieces to this transfer.
                    </div>

                    <div v-else class="table-responsive">
                        <table class="table table-custom align-middle mb-0">
                            <thead class="bg-light bg-opacity-25 thead-sm">
                                <tr class="text-uppercase fs-xxs">
                                    <th>Product</th>
                                    <th style="width: 12%;">Ct</th>
                                    <th>Barcode</th>
                                    <th class="text-end" style="width: 10%;">On Hand</th>
                                    <th class="text-end" style="width: 10%;">Qty</th>
                                    <th>Notes</th>
                                    <th class="text-center" style="width: 1%;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(line, idx) in form.lines" :key="idx"
                                    :class="{ 'table-warning': line.qty > line.on_hand || line._stockWarning }">
                                    <td>
                                        <div class="fw-semibold">
                                            @{{ line.product_title }}
                                            <span v-if="line.is_group" class="badge badge-soft-info fs-xxs ms-1">group of @{{ line.on_hand }}</span>
                                        </div>
                                        <small class="text-muted" v-if="line.product_sku">SKU: @{{ line.product_sku }}</small>
                                    </td>
                                    <td>
                                        {{-- CT is its own independent ledger, separate from qty — the
                                             staff member enters exactly how much CT this line moves,
                                             capped at that piece's actual remaining CT balance at the
                                             source location (never a qty-derived guess; different units
                                             on the same row can carry different individual weights). --}}
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
                                    <td><code class="small">@{{ line.barcode || line.lot_code || '—' }}</code></td>
                                    <td class="text-end">@{{ line.on_hand }}</td>
                                    <td>
                                        <input type="number" min="1" :max="line.on_hand" step="1"
                                            :disabled="line.on_hand === 1"
                                            class="form-control form-control-sm text-end"
                                            v-model.number="line.qty"
                                            @input="checkStockWarning(idx)">
                                        <small v-if="line._stockWarning" class="d-block text-danger">
                                            <i class="ti ti-alert-triangle"></i> Capped at on-hand
                                        </small>
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
                    <button type="button" class="btn btn-lg btn-success" :disabled="submitting || !canPost"
                        @click="submit('in_transit')">
                        <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="ti ti-send me-1"></i>
                        Post Transfer
                    </button>
                    <button type="button" class="btn btn-light" :disabled="submitting" @click="submit('draft')">
                        <i class="ti ti-device-floppy me-1"></i> Save as Draft
                    </button>
                    <a href="{{ route('stock-transfers.index') }}" class="btn btn-secondary">Cancel and go back</a>
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

@push('styles')
<style>
    /* Compact spacing for the New/Edit Transfer form — scoped to this page only.
       The Search Stock / Pieces line-item table is left untouched by design. */
    .stock-transfers-form-page { padding-top: 20px; padding-bottom: 20px; }
    .stock-transfers-form-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid #e2e8f0;
    }
    .stock-transfers-form-page .page-title-head > * { display: flex; align-items: center; }
    .stock-transfers-form-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        position: relative;
        padding-left: 12px;
    }
    .stock-transfers-form-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0; top: 2px; bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, #1e3a8a, #1d4ed8);
    }
    .stock-transfers-form-page .breadcrumb { font-size: 0.75rem; }
    .stock-transfers-form-page .card { border-radius: 10px; box-shadow: none; border: 1px solid #e2e8f0; }
    .stock-transfers-form-page .card-body { padding: 16px; }
    .stock-transfers-form-page .header-title { font-size: 1rem; font-weight: 700; }
    .stock-transfers-form-page .mb-3, .stock-transfers-form-page .mb-4 { margin-bottom: 12px !important; }
    .stock-transfers-form-page .form-label { margin-bottom: 4px; font-size: 0.8125rem; font-weight: 600; }
    .stock-transfers-form-page .form-control, .stock-transfers-form-page .form-select { padding: 0.4rem 0.65rem; font-size: 0.8125rem; }
    .stock-transfers-form-page small.text-muted { display: inline-block; margin-top: 3px; font-size: 0.75rem; }
    .stock-transfers-form-page .d-flex.justify-content-end.gap-2 { margin-top: 16px !important; }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    new Vue({
        el: '#transferApp',
        data: {
            locations: @json($locations),
            racks:     @json($racks),

            searchTerm: '',
            searchResults: [],
            searching: false,

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
                // Count distinct pieces actually being moved, not cart
                // rows -- a box-group row expands into several distinct
                // purchase_product_id picks at submit time.
                const ids = new Set();
                this.form.lines.forEach((l) => {
                    this.allocatePicks(l).forEach((p) => ids.add(p.purchase_product_id));
                });
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
            this.$nextTick(() => this.$refs.searchInput?.focus());
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
                this.searchResults = [];
                this.$nextTick(() => this.$refs.searchInput?.focus());
            },

            searchPieces() {
                if (!this.form.from_location_id) return;
                this.searching = true;
                const params = new URLSearchParams({
                    from_location_id: String(this.form.from_location_id),
                    search:           this.searchTerm,
                });
                fetch(`{{ route('stock-transfers.search-pieces') }}?${params.toString()}`, { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(res => { this.searchResults = res.ok ? res.items : []; })
                    .finally(() => { this.searching = false; });
            },

            isPieceAdded(purchaseProductId) {
                return this.form.lines.some((l) => l.purchase_product_id === purchaseProductId);
            },

            // Adds a piece straight into the cart, defaulting qty to the
            // full on-hand balance found.
            addPieceFromSearch(r) {
                if (this.isPieceAdded(r.purchase_product_id)) return;
                this.form.lines.push({
                    is_group:             false,
                    purchase_product_id:  r.purchase_product_id,
                    product_id:           r.product_id || null,
                    product_title:        r.product_title,
                    product_sku:          r.product_sku || null,
                    barcode:              r.barcode || null,
                    lot_code:             r.lot_code || null,
                    // Defaults to "move everything left on this piece" —
                    // must be the live remaining balance at the source
                    // location, not the piece's original recorded weight
                    // (they differ once a piece has been partially moved
                    // or sold before).
                    carat_weight:         r.remaining_carat,
                    // Flag for "is this a weighed item at all" (drives the
                    // v-if below) — the piece's original recorded per-unit
                    // carat, immutable, never used in a calculation.
                    piece_carat_weight:   r.carat_weight,
                    // Snapshot of this piece's actual remaining CT balance
                    // at the source location (from the ledger) at the
                    // moment it's added — the cap the entered carat_weight
                    // is clamped against. Independent of qty entirely.
                    remaining_carat_before: r.remaining_carat,
                    on_hand:              r.on_hand,
                    qty:                  r.on_hand,
                    to_rack_id:           null,
                    notes:                '',
                    _stockWarning:        false,
                });
            },

            removeLine(idx) { this.form.lines.splice(idx, 1); },

            /* ── carat / qty validation ────── */
            // CT is its own ledger, entirely independent of qty — this is
            // the piece's actual remaining balance at the source location
            // (snapshotted when the line was added) minus whatever's been
            // typed as the carat_weight being moved on this line.
            remainingCaratAfter(line) {
                if (line.piece_carat_weight === null || line.piece_carat_weight === undefined) return null;
                return Number(line.remaining_carat_before || 0) - Number(line.carat_weight || 0);
            },
            formatCarat(v) {
                if (v === null || v === undefined || isNaN(v)) return '—';
                return (Math.round(Number(v) * 1000) / 1000).toString();
            },
            // Clamps qty to on-hand stock rather than just flagging it.
            checkStockWarning(idx) {
                const l = this.form.lines[idx];
                if (!l) return;
                if (!l.qty || Number(l.qty) < 1) l.qty = 1;
                if (Number(l.qty) > Number(l.on_hand)) {
                    l.qty = Number(l.on_hand);
                    l._stockWarning = true;
                    setTimeout(() => { l._stockWarning = false; }, 2000);
                }
            },
            // Clamps the moved-carat figure to this piece's actual
            // remaining CT balance — never a qty-derived guess, since
            // different units on the same row can carry different
            // individual weights.
            checkCaratLimit(idx) {
                const l = this.form.lines[idx];
                if (!l || l.piece_carat_weight === null || l.piece_carat_weight === undefined) return;
                if (l.carat_weight === null || l.carat_weight === undefined || l.carat_weight === '') return;
                const maxCarat = Number(l.remaining_carat_before || 0);
                if (Number(l.carat_weight) > maxCarat) l.carat_weight = maxCarat;
                if (Number(l.carat_weight) < 0) l.carat_weight = 0;
            },

            // Expands one grouped cart row into the distinct
            // purchase_product_id picks that actually get submitted --
            // FIFO across line.pieces, capped at the chosen qty. A
            // single-piece line just passes itself through.
            allocatePicks(line) {
                if (!line.is_group) {
                    return [{ purchase_product_id: line.purchase_product_id, qty: Number(line.qty) || 1 }];
                }
                let remaining = Number(line.qty) || 0;
                const picks = [];
                for (const p of line.pieces) {
                    if (remaining <= 0) break;
                    const take = Math.min(p.balance, remaining);
                    if (take <= 0) continue;
                    picks.push({ purchase_product_id: p.purchase_product_id, qty: take });
                    remaining -= take;
                }
                return picks;
            },

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
                    lines: this.form.lines.flatMap((l) => this.allocatePicks(l).map((pick) => ({
                        purchase_product_id: pick.purchase_product_id,
                        qty:                 pick.qty,
                        carat_weight:        (l.carat_weight === '' || l.carat_weight === undefined) ? null : l.carat_weight,
                        to_rack_id:          l.to_rack_id || null,
                        notes:               l.notes || null,
                    }))),
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
