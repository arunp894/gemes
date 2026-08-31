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

    {{-- No 'was-validated' class here on purpose: validation is entirely
         manual (.is-invalid bindings driven by `errors`), and Bootstrap's
         was-validated turns on native :valid/:invalid styling for every
         control including ones with no `required` attribute — which would
         paint a false-positive green checkmark on genuinely optional,
         still-empty fields (Notes, To Rack…). --}}
    <form id="transferForm" novalidate @submit.prevent="submit('in_transit')">

        <div class="row g-3">
            <div class="col-xl-8">

                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label"><i class="ti ti-calendar text-primary me-2"></i>Transfer Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" v-model="form.transfer_date"
                                    :class="{ 'is-invalid': errors.transfer_date }" required>
                                <div class="invalid-feedback">@{{ errors.transfer_date }}</div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label"><i class="ti ti-map-pin text-primary me-2"></i>From Location <span class="text-danger">*</span></label>
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
                                <label class="form-label"><i class="ti ti-map-pin-check text-primary me-2"></i>To Location <span class="text-danger">*</span></label>
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
                        <div class="d-flex align-items-center gap-2">
                            <i class="ti ti-list-details fs-18 text-primary"></i>
                            <h5 class="card-title mb-0">Pieces to Transfer</h5>
                        </div>
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
                                    <th class="text-end" style="width: 10%;">Piece <span class="text-danger">*</span></th>
                                    <th>Notes</th>
                                    <th class="text-center" style="width: 1%;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(line, idx) in form.lines" :key="idx"
                                    :class="{ 'table-warning': line.qty > line.on_hand || line._stockWarning, 'line-has-error': hasLineError(idx) }">
                                    <td>
                                        <div class="fw-semibold">
                                            @{{ line.product_title }}
                                            <span v-if="line.is_group" class="badge badge-soft-info fs-xxs ms-1">group of @{{ line.on_hand }}</span>
                                        </div>
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
                                            :placeholder="'max ' + line.on_hand"
                                            class="form-control form-control-sm text-end"
                                            :class="{ 'is-invalid': lineError(idx, 'qty') }"
                                            v-model.number="line.qty"
                                            @input="checkStockWarning(idx)"
                                            @blur="normalizeQty(idx)">
                                        <small v-if="line._stockWarning" class="d-block text-danger">
                                            <i class="ti ti-alert-triangle"></i> Capped at on-hand
                                        </small>
                                        <div class="invalid-feedback d-block" v-if="lineError(idx, 'qty')">@{{ lineError(idx, 'qty') }}</div>
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
                    <div class="card-header border-light d-flex align-items-center gap-2">
                        <i class="ti ti-note fs-18 text-primary"></i>
                        <h5 class="card-title mb-0">Note</h5>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" rows="2" v-model="form.note" maxlength="2000"
                            placeholder="Optional note for this transfer"></textarea>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">

                <div class="card">
                    <div class="card-header border-light d-flex align-items-center gap-2">
                        <i class="ti ti-receipt-2 fs-18 text-primary"></i>
                        <h5 class="card-title mb-0">Summary</h5>
                    </div>
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

    {{-- Confirm clearing the cart on source-location change — a Bootstrap
         modal instead of a native confirm(), matching the rest of the app. --}}
    <div class="modal fade" id="clearCartModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title"><i class="ti ti-alert-triangle text-warning me-1"></i>Change source location?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Changing the source location will clear the pieces already added to this transfer,
                    since they belong to the previous location. Continue?
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="clearCartConfirmBtn">
                        <i class="ti ti-trash me-1"></i>Clear and Continue
                    </button>
                </div>
            </div>
        </div>
    </div>
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

    /* A cart row carrying a real validation error — a solid left stripe
       plus a faint red tint, matching the same treatment on the Purchase
       and Sales forms' line tables, so the eye goes straight to it. */
    .stock-transfers-form-page tr.line-has-error > td {
        background-color: #fef2f2 !important;
    }
    .stock-transfers-form-page tr.line-has-error > td:first-child {
        box-shadow: inset 3px 0 0 #dc2626;
    }
    .stock-transfers-form-page .invalid-feedback {
        font-size: 0.6875rem;
        margin-top: 2px;
    }
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
            this._prevFromLocationId = this.form.from_location_id;
            this.$nextTick(() => this.$refs.searchInput?.focus());
        },
        methods: {
            locationNameById(id) {
                const l = this.locations.find((x) => x.id === id);
                return l ? l.name : '';
            },

            // Clear cart when source changes — pieces from the old source
            // aren't valid against the new one. Confirmed via a Bootstrap
            // modal rather than a native confirm(), matching the rest of
            // the app; if the user cancels, the location select reverts
            // to its previous value (v-model already applied the new one
            // by the time @change fires).
            onSourceChange() {
                if (this.form.lines.length === 0) {
                    this._prevFromLocationId = this.form.from_location_id;
                    this.searchResults = [];
                    this.$nextTick(() => this.$refs.searchInput?.focus());
                    return;
                }

                const newLocationId = this.form.from_location_id;
                const modalEl = document.getElementById('clearCartModal');
                const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                const confirmBtn = document.getElementById('clearCartConfirmBtn');

                // Replace the button on each open so listeners never stack
                // across repeated location changes.
                const freshBtn = confirmBtn.cloneNode(true);
                confirmBtn.parentNode.replaceChild(freshBtn, confirmBtn);

                let confirmed = false;
                const onHidden = () => {
                    if (!confirmed) this.form.from_location_id = this._prevFromLocationId;
                    modalEl.removeEventListener('hidden.bs.modal', onHidden);
                };
                freshBtn.addEventListener('click', () => {
                    confirmed = true;
                    this.form.lines = [];
                    this._prevFromLocationId = newLocationId;
                    this.searchResults = [];
                    modal.hide();
                    this.$nextTick(() => this.$refs.searchInput?.focus());
                }, { once: true });
                modalEl.addEventListener('hidden.bs.modal', onHidden);

                modal.show();
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
                    // Left blank on purpose — the person moving stock
                    // must consciously type how many to transfer, rather
                    // than the field silently pre-filling "move all N".
                    qty:                  null,
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
            // Clamps qty to on-hand stock rather than just flagging it —
            // typing past what's available snaps back to the ceiling.
            // Deliberately does NOT floor an empty/invalid value to 1 here
            // (see normalizeQty()) — this runs on every keystroke, and
            // snapping back to 1 the instant the box goes empty would make
            // it impossible to clear the field and type a new number.
            checkStockWarning(idx) {
                const l = this.form.lines[idx];
                if (!l) return;
                if (Number(l.qty) > Number(l.on_hand)) {
                    l.qty = Number(l.on_hand);
                    l._stockWarning = true;
                    setTimeout(() => { l._stockWarning = false; }, 2000);
                }
            },
            // Runs on blur, once the person is done editing — this is
            // where an emptied or invalid qty finally falls back to 1,
            // instead of on every keystroke.
            normalizeQty(idx) {
                const l = this.form.lines[idx];
                if (!l) return;
                if (!l.qty || Number(l.qty) < 1) l.qty = 1;
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

            /* ── error mapping ─────────────── */
            // Laravel returns dot-path keys like:
            //   from_location_id       — a top-level field, kept flat
            //   lines.1.qty            — a specific piece row's field, nested
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

            async submit(status) {
                this.serverError = null;
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
