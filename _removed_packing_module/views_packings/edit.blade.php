@extends('layout.app')

@section('title', 'Edit ' . $packing->packing_number)

@section('content')
<div class="container-fluid" id="packingFormApp">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1"><h4 class="page-main-title m-0">Edit {{ $packing->packing_number }}</h4></div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('packings.index') }}">Packings</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </div>
    </div>

    <div class="alert alert-danger" v-if="topError">@{{ topError }}</div>

    {{-- Header --}}
    <div class="card">
        <div class="card-header border-light"><h5 class="card-title mb-0">Packing Details</h5></div>
        <div class="card-body row g-3">
            <div class="col-md-3">
                <label class="form-label">Packing Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control" v-model="form.packing_date">
            </div>
            <div class="col-md-5">
                <label class="form-label">Location <span class="text-danger">*</span></label>
                <select class="form-select" v-model="form.location_id" @change="onLocationChange">
                    <option value="">Select location…</option>
                    <option v-for="loc in locations" :key="loc.id" :value="loc.id">@{{ loc.name }} (@{{ loc.location_code }})</option>
                </select>
                <small class="text-muted">Changing location clears the selected raw stock below.</small>
            </div>
            <div class="col-md-4">
                <label class="form-label">Note</label>
                <input type="text" class="form-control" v-model="form.note" maxlength="500">
            </div>
        </div>
    </div>

    {{-- Sources --}}
    <div class="card">
        <div class="card-header border-light justify-content-between">
            <h5 class="card-title mb-0">Raw Stock to Pack</h5>
            <span class="badge badge-soft-secondary">Selected: @{{ totalSourceQty }}</span>
        </div>
        <div class="card-body">
            <div class="row g-2 mb-3" v-if="form.location_id">
                <div class="col-md-6">
                    <input type="text" class="form-control" v-model="searchTerm" placeholder="Scan or type a lot code / barcode…" @keyup.enter="searchSources">
                </div>
                <div class="col-md-auto">
                    <button type="button" class="btn btn-soft-primary" :disabled="searching" @click="searchSources">
                        <i class="ti ti-search me-1"></i> Search
                    </button>
                </div>
            </div>
            <div class="text-muted small mb-2" v-else>Select a location above to see available raw stock.</div>

            <div class="table-responsive mb-3" v-if="searchResults.length">
                <table class="table table-sm align-middle">
                    <thead class="bg-light bg-opacity-25 thead-sm">
                        <tr class="text-uppercase fs-xxs">
                            <th>Lot Code</th><th>Category</th><th>Stone</th><th class="text-end">Carat</th>
                            <th class="text-end">On Hand</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in searchResults" :key="r.purchase_product_id">
                            <td><code>@{{ r.lot_code }}</code></td>
                            <td>@{{ r.category }}</td>
                            <td>@{{ r.stone_type }}</td>
                            <td class="text-end">@{{ r.carat_weight }}</td>
                            <td class="text-end">@{{ r.on_hand }}</td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-soft-success" @click="addSource(r)" :disabled="remainingFor(r) <= 0">
                                    <i class="ti ti-plus"></i> @{{ remainingFor(r) <= 0 ? 'Fully added' : 'Add' }}
                                </button>
                                <div class="text-muted fs-xxs mt-1" v-if="takenSoFar(r.purchase_product_id) > 0">@{{ takenSoFar(r.purchase_product_id) }} already added</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="table-responsive" v-if="form.sources.length">
                <table class="table table-sm align-middle">
                    <thead class="bg-light bg-opacity-25 thead-sm">
                        <tr class="text-uppercase fs-xxs">
                            <th>Lot Code</th><th>Category</th><th class="text-end">Available</th>
                            <th style="width:110px" class="text-end">Qty to Take</th>
                            <th style="width:60px" class="text-center">Website</th>
                            <th style="width:150px" class="text-end">Selling Price</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(s, i) in form.sources" :key="i">
                            <td><code>@{{ s.lot_code }}</code></td>
                            <td>@{{ s.category }}</td>
                            <td class="text-end">@{{ s.on_hand }}</td>
                            <td class="text-end">
                                <input type="number" class="form-control form-control-sm text-end" :class="{'is-invalid': s.qty_taken > maxForRow(s)}" min="1" :max="maxForRow(s)" v-model.number="s.qty_taken" @input="clampQty(s)" @blur="enforceMinQty(s)">
                            </td>
                            <td class="text-center">
                                <div class="form-check form-switch d-flex justify-content-center mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch" v-model="s.website_enabled">
                                </div>
                            </td>
                            <td class="text-end">
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end" v-model.number="s.website_price" placeholder="—">
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-soft-danger" @click="form.sources.splice(i, 1)"><i class="ti ti-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="text-muted small" v-else>No raw stock selected yet.</div>
            <div class="text-muted small mt-2" v-if="form.sources.length">
                <i class="ti ti-info-circle me-1"></i>Each row above becomes its own product when saved.
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-footer text-end d-flex justify-content-end gap-2">
            <a href="{{ route('packings.show', $packing) }}" class="btn btn-light">Cancel</a>
            <button type="button" class="btn btn-soft-primary" :disabled="submitting" @click="submit(false)">
                Save Changes
            </button>
            <button type="button" class="btn btn-primary" :disabled="submitting" @click="submit(true)">
                <span v-if="submitting"><span class="spinner-border spinner-border-sm me-1"></span>Saving…</span>
                <span v-else>Save &amp; Post</span>
            </button>
        </div>
    </div>

    <div v-if="toast.show" class="position-fixed bottom-0 end-0 p-3" style="z-index:9999">
        <div class="toast show align-items-center" :class="'text-bg-' + toast.type" role="alert">
            <div class="d-flex">
                <div class="toast-body">@{{ toast.message }}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" @click="toast.show=false"></button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
new Vue({
    el: '#packingFormApp',
    data: {
        locations: {!! json_encode($locations) !!},

        form: {
            packing_date: '{{ optional($packing->packing_date)->format('Y-m-d') }}',
            location_id: {{ (int) $packing->location_id }},
            note: {!! json_encode($packing->note) !!},
            sources: {!! json_encode($sourcesPayload) !!},
        },
        searchTerm: '',
        searchResults: [],
        searching: false,
        errors: {},
        topError: '',
        submitting: false,
        toast: { show: false, message: '', type: 'danger' },
    },
    computed: {
        totalSourceQty() {
            return this.form.sources.reduce((sum, s) => sum + (parseInt(s.qty_taken) || 0), 0);
        },
    },
    methods: {
        onLocationChange() {
            this.form.sources = [];
            this.searchResults = [];
        },
        searchSources() {
            if (!this.form.location_id) return;
            this.searching = true;
            const url = `{{ route('packings.available-sources') }}?location_id=${this.form.location_id}&search=${encodeURIComponent(this.searchTerm)}`;
            fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(res => { this.searchResults = res.ok ? res.items : []; })
                .finally(() => { this.searching = false; });
        },
        // Sum of qty_taken already queued across every row that shares
        // this lot -- lets one raw piece be split into several output
        // rows (see "Each row above becomes its own product"), and
        // drives the smart default below instead of re-offering the
        // piece's full on-hand every time.
        takenSoFar(purchaseProductId) {
            return this.form.sources
                .filter(s => s.purchase_product_id === purchaseProductId)
                .reduce((sum, s) => sum + (parseInt(s.qty_taken) || 0), 0);
        },
        // Sum of qty_taken on every OTHER row sharing this row's lot --
        // what siblings have already claimed, excluding the row itself.
        // row.on_hand minus this is the true ceiling this row can take
        // without the group overselling the lot.
        otherRowsTotal(row) {
            return this.form.sources
                .filter(s => s !== row && s.purchase_product_id === row.purchase_product_id)
                .reduce((sum, s) => sum + (parseInt(s.qty_taken) || 0), 0);
        },
        maxForRow(row) {
            return Math.max(0, row.on_hand - this.otherRowsTotal(row));
        },
        // Hard-stops typing/pasting/spinner past what's actually left --
        // the max="" attribute alone doesn't block manual entry, only
        // the spinner arrows.
        clampQty(row) {
            const cap = this.maxForRow(row);
            if (row.qty_taken > cap) {
                row.qty_taken = cap;
            }
        },
        // Floors an empty/zero/negative Qty to Take back to 1 once the
        // user leaves the field. Checked on blur rather than every
        // keystroke so clearing the box to retype a number doesn't get
        // stomped back to 1 mid-edit.
        enforceMinQty(row) {
            if (!row.qty_taken || row.qty_taken < 1) {
                row.qty_taken = 1;
            }
        },
        // Physical on-hand for this lot minus what's already queued
        // across every row in the sources table below -- what's actually
        // left to add. Drives both the "Add" button's disabled state and
        // the qty a freshly-added row starts at.
        remainingFor(r) {
            return Math.max(0, r.on_hand - this.takenSoFar(r.purchase_product_id));
        },
        addSource(r) {
            const remaining = this.remainingFor(r);
            if (remaining <= 0) return;
            this.form.sources.push({
                purchase_product_id: r.purchase_product_id,
                lot_code: r.lot_code,
                category: r.category,
                category_id: r.category_id,
                stone_type: r.stone_type,
                colour_grade: r.colour_grade,
                clarity_grade: r.clarity_grade,
                cut_shape: r.cut_shape,
                treatment: r.treatment,
                carat_weight: r.carat_weight,
                price: r.price,
                on_hand: r.on_hand,
                qty_taken: remaining,
                // Defaults straight off the purchase (line hint + the
                // raw row's own Selling Price) -- editable per row below.
                website_enabled: !!r.website_enabled,
                website_price: r.website_price !== null && r.website_price !== undefined ? Number(r.website_price) : null,
            });
        },
        validate() {
            this.topError = '';
            if (!this.form.location_id) { this.topError = 'Select a location.'; return false; }
            if (!this.form.sources.length) { this.topError = 'Select at least one raw piece to pack.'; return false; }

            for (const s of this.form.sources) {
                if (!s.qty_taken || s.qty_taken < 1) {
                    this.topError = `Lot ${s.lot_code}: Qty to Take must be at least 1.`;
                    return false;
                }
            }

            // Rows can now share a lot (see addSource) -- catch any group
            // whose combined qty_taken oversells that lot's on-hand
            // before this ever reaches the server.
            const totals = {};
            for (const s of this.form.sources) {
                totals[s.purchase_product_id] = (totals[s.purchase_product_id] || 0) + (parseInt(s.qty_taken) || 0);
            }
            for (const s of this.form.sources) {
                if (totals[s.purchase_product_id] > s.on_hand) {
                    this.topError = `Lot ${s.lot_code}: rows total ${totals[s.purchase_product_id]}, but only ${s.on_hand} on hand.`;
                    return false;
                }
            }
            return true;
        },
        submit(thenPost) {
            if (!this.validate()) return;
            this.submitting = true;

            const csrf = document.querySelector('meta[name="csrf-token"]').content;

            fetch('{{ route('packings.update', $packing) }}', {
                method: 'PUT',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify(this.form),
            })
            .then(async r => {
                const j = await r.json();
                if (!r.ok) {
                    if (j.errors) {
                        const firstKey = Object.keys(j.errors)[0];
                        this.topError = j.errors[firstKey][0];
                    } else {
                        this.topError = j.message || 'Please fix the errors above.';
                    }
                    this.submitting = false;
                    return;
                }
                if (!thenPost) {
                    window.location.href = j.redirect;
                    return;
                }
                fetch('{{ route('packings.post', $packing) }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                })
                .then(async r2 => {
                    const j2 = await r2.json();
                    if (!r2.ok) { this.topError = j2.message || 'Saved, but posting failed.'; this.submitting = false; return; }
                    window.location.href = j.redirect;
                });
            })
            .catch(() => { this.topError = 'A network error occurred. Please try again.'; this.submitting = false; });
        },
    },
});
</script>
@endpush
@endsection
