@extends('layout.app')

@section('title', 'Edit Transfer ' . $transfer->transfer_number)

@section('content')
<div class="container-fluid stock-transfers-form-page" id="transferApp">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">
                <i class="ti ti-edit text-primary me-2"></i>Edit Transfer {{ $transfer->transfer_number }}
                <span class="badge {{ $transfer->statusBadgeClass() }} ms-2">{{ $transfer->statusLabel() }}</span>
            </h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('stock-transfers.index') }}">Transfers</a></li>
                <li class="breadcrumb-item"><a href="{{ route('stock-transfers.show', $transfer) }}">{{ $transfer->transfer_number }}</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </div>
    </div>

    @if (! $transfer->isEditable())
        <div class="alert alert-warning">
            <i class="ti ti-alert-triangle me-1"></i>
            Only draft transfers can be edited.
        </div>
    @endif

    <form id="transferForm" novalidate @submit.prevent="submit" :class="{ 'was-validated': wasValidated }">

        <div class="row g-3">
            <div class="col-xl-8">

                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Transfer Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" v-model="form.transfer_date" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">From Location</label>
                                <select class="form-select" v-model.number="form.from_location_id"
                                    @change="onSourceChange" required>
                                    <option :value="null">— Select source —</option>
                                    <option v-for="l in locations" :key="'f-' + l.id" :value="l.id"
                                        :disabled="l.id === form.to_location_id">
                                        @{{ l.name }} (@{{ l.location_code }})
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">To Location</label>
                                <select class="form-select" v-model.number="form.to_location_id" required>
                                    <option :value="null">— Select destination —</option>
                                    <option v-for="l in locations" :key="'t-' + l.id" :value="l.id"
                                        :disabled="l.id === form.from_location_id">
                                        @{{ l.name }} (@{{ l.location_code }})
                                    </option>
                                </select>
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
                                <input type="text" class="form-control" v-model="searchTerm"
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

                <div class="card">
                    <div class="card-header border-light"><h5 class="card-title mb-0">Pieces</h5></div>

                    <div v-if="form.lines.length === 0" class="card-body text-center text-muted py-5">
                        <i class="ti ti-package-off fs-1 d-block mb-2"></i>No pieces.
                    </div>

                    <div v-else class="table-responsive">
                        <table class="table table-custom align-middle mb-0">
                            <thead class="bg-light bg-opacity-25 thead-sm">
                                <tr class="text-uppercase fs-xxs">
                                    <th>Product</th>
                                    <th style="width: 12%;">Ct</th>
                                    <th>Barcode</th>
                                    <th class="text-end">On Hand</th>
                                    <th class="text-end">Qty</th>
                                    <th>Notes</th>
                                    <th></th>
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
                                        <input type="text" class="form-control form-control-sm" v-model="line.notes">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-default btn-icon btn-sm text-danger"
                                            @click="removeLine(idx)">
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
                    <div class="card-header border-light"><h5 class="card-title mb-0">Summary</h5></div>
                    <div class="card-body">
                        <dl class="row mb-0 small">
                            <dt class="col-7 text-muted">Lines</dt>
                            <dd class="col-5 text-end">@{{ form.lines.length }}</dd>
                            <dt class="col-7 text-muted">Total pieces</dt>
                            <dd class="col-5 text-end">@{{ totalPieces }}</dd>
                        </dl>
                    </div>
                </div>

                <div v-if="serverError" class="alert alert-danger">@{{ serverError }}</div>

                <div class="d-grid gap-2 mb-4">
                    <button type="button" class="btn btn-primary" :disabled="submitting" @click="submit">
                        <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="ti ti-device-floppy me-1"></i> Save Changes
                    </button>
                    <a href="{{ route('stock-transfers.show', $transfer) }}" class="btn btn-secondary">Cancel</a>
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
                transfer_date:    @json(optional($transfer->transfer_date)->toDateString()),
                from_location_id: @json($transfer->from_location_id),
                to_location_id:   @json($transfer->to_location_id),
                note:             @json($transfer->note),
                lines:            @json($linesPayload),
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
        },
        async mounted() {
            // Refresh on-hand for existing lines so qty caps are accurate.
            if (this.form.from_location_id) {
                await this.refreshOnHandForExistingLines();
            }
        },
        methods: {
            async refreshOnHandForExistingLines() {
                // We don't have a bulk endpoint, so just iterate the
                // lines and pull each one's on_hand individually. For a
                // draft transfer this is a handful of requests.
                for (const line of this.form.lines) {
                    try {
                        if (line.is_group) {
                            // Box-group lines need the aggregate + per-piece
                            // FIFO breakdown lookupByBarcode() returns, for
                            // allocatePicks() -- search-pieces returns one
                            // row per piece, not that grouped shape.
                            if (!line.barcode) continue;
                            const params = new URLSearchParams({
                                barcode:          line.barcode,
                                from_location_id: String(this.form.from_location_id),
                            });
                            const res = await fetch(
                                `{{ route('stock-transfers.lookup-barcode') }}?${params.toString()}`,
                                { headers: { 'Accept': 'application/json' } }
                            );
                            const data = await res.json();
                            if (res.ok && data.ok) {
                                this.$set(line, 'on_hand', data.piece.on_hand);
                                this.$set(line, 'pieces', data.piece.pieces);
                            }
                        } else {
                            // Single-piece lines -- search by lot_code (set
                            // on every row, unlike barcode which is often
                            // blank) so this works uniformly for every line.
                            const term = line.lot_code || line.barcode;
                            if (!term) continue;
                            const params = new URLSearchParams({
                                from_location_id: String(this.form.from_location_id),
                                search:           term,
                            });
                            const res = await fetch(
                                `{{ route('stock-transfers.search-pieces') }}?${params.toString()}`,
                                { headers: { 'Accept': 'application/json' } }
                            );
                            const data = await res.json();
                            if (res.ok && data.ok) {
                                const mine = (data.items || []).find((p) => p.purchase_product_id === line.purchase_product_id);
                                this.$set(line, 'on_hand', mine ? mine.on_hand : 0);
                                this.$set(line, 'remaining_carat_before', mine ? mine.remaining_carat : 0);
                            }
                        }
                    } catch (_) {}
                }
            },

            onSourceChange() {
                if (this.form.lines.length > 0) {
                    if (!confirm('Changing source will clear the pieces list. Continue?')) {
                        return;
                    }
                    this.form.lines = [];
                }
                this.searchResults = [];
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
                    piece_carat_weight:   r.carat_weight,
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
            // (snapshotted when the line was added, or refreshed at mount
            // for existing lines) minus whatever's been typed as the
            // carat_weight being moved on this line.
            remainingCaratAfter(line) {
                if (line.piece_carat_weight === null || line.piece_carat_weight === undefined) return null;
                return Number(line.remaining_carat_before || 0) - Number(line.carat_weight || 0);
            },
            formatCarat(v) {
                if (v === null || v === undefined || isNaN(v)) return '—';
                return (Math.round(Number(v) * 1000) / 1000).toString();
            },
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

            // Same FIFO walk as the create form: expands one grouped
            // cart row into the distinct purchase_product_id picks that
            // actually get submitted. Single-piece lines pass through
            // unchanged.
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

            async submit() {
                this.serverError = null;
                this.wasValidated = true;
                if (this.form.lines.length === 0) { this.serverError = 'Add at least one piece.'; return; }
                this.submitting = true;

                const payload = {
                    transfer_date:    this.form.transfer_date,
                    from_location_id: this.form.from_location_id,
                    to_location_id:   this.form.to_location_id,
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
                    const res = await fetch('{{ route('stock-transfers.update', $transfer) }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-HTTP-Method-Override': 'PUT',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ ...payload, _method: 'PUT' }),
                    });
                    if (!res.ok) {
                        const data = await res.json().catch(() => ({}));
                        this.serverError = data.message || 'Something went wrong.';
                        this.submitting = false;
                        return;
                    }
                    const data = await res.json();
                    window.location.href = data.redirect || '{{ route('stock-transfers.show', $transfer) }}';
                } catch (err) {
                    this.serverError = 'Network error.';
                    this.submitting = false;
                }
            },
        },
    });
});
</script>
@endpush
