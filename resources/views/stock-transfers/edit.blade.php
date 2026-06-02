@extends('layout.app')

@section('title', 'Edit Transfer ' . $transfer->transfer_number)

@section('content')
<div class="container-fluid" id="transferApp">

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

                <div class="card">
                    <div class="card-header border-light d-flex align-items-center gap-2">
                        <i class="ti ti-barcode fs-18 text-primary"></i>
                        <h5 class="card-title mb-0">Scan Piece</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-8">
                                <label class="form-label">Barcode</label>
                                <input ref="barcodeInput" type="text" class="form-control form-control-lg"
                                    v-model="barcodeInput"
                                    :disabled="!form.from_location_id"
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
                                    <th>Barcode</th>
                                    <th class="text-end">On Hand</th>
                                    <th class="text-end">Qty</th>
                                    <th>To Rack</th>
                                    <th>Notes</th>
                                    <th></th>
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
                    <a href="{{ route('stock-transfers.show', $transfer) }}" class="btn btn-link text-muted">Cancel</a>
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
                transfer_date:    @json(optional($transfer->transfer_date)->toDateString()),
                from_location_id: @json($transfer->from_location_id),
                to_location_id:   @json($transfer->to_location_id),
                note:             @json($transfer->note),
                lines: @json($transfer->lines->map(fn ($l) => [
                    'purchase_product_id' => $l->purchase_product_id,
                    'product_id'          => $l->product_id,
                    'product_title'       => optional($l->product)->title,
                    'product_sku'         => optional($l->product)->sku,
                    'barcode'             => optional($l->purchaseProduct)->barcode,
                    'qty'                 => (int) $l->qty,
                    'to_rack_id'          => $l->to_rack_id,
                    'notes'               => $l->notes,
                    // On-hand is recomputed below once Vue mounts.
                    'on_hand'             => 9999,
                ])),
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
            this.$nextTick(() => this.$refs.barcodeInput?.focus());
        },
        methods: {
            async refreshOnHandForExistingLines() {
                // We don't have a bulk endpoint, so just iterate the
                // lines and pull each piece's on_hand individually. For
                // a draft transfer this is a handful of requests.
                for (const line of this.form.lines) {
                    if (!line.barcode) continue;
                    try {
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
                            // qty in draft was already deducted from on_hand
                            // when posted; for draft transfers no movements
                            // exist yet so on_hand reflects true source stock.
                            this.$set(line, 'on_hand', data.piece.on_hand);
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
                    const res = await fetch(
                        `{{ route('stock-transfers.lookup-barcode') }}?${params.toString()}`,
                        { headers: { 'Accept': 'application/json' } }
                    );
                    const data = await res.json();

                    if (!res.ok || !data.ok) {
                        this.scannerAlertClass = 'alert-danger';
                        this.scannerIconClass  = 'ti ti-alert-circle';
                        this.scannerMessage    = data.message || 'Not found.';
                        return;
                    }

                    const existing = this.form.lines.find(
                        (l) => l.purchase_product_id === data.piece.purchase_product_id
                    );
                    if (existing) {
                        if (existing.qty < existing.on_hand) existing.qty += 1;
                    } else {
                        this.form.lines.push({
                            purchase_product_id: data.piece.purchase_product_id,
                            product_id:          data.piece.product_id,
                            product_title:       data.piece.product_title,
                            product_sku:         data.piece.product_sku,
                            barcode:             data.piece.barcode,
                            on_hand:             data.piece.on_hand,
                            qty:                 1,
                            to_rack_id:          null,
                            notes:               '',
                        });
                    }

                    this.barcodeInput = '';
                    this.scannerAlertClass = 'alert-success';
                    this.scannerIconClass  = 'ti ti-check';
                    this.scannerMessage    = `Added: ${data.piece.product_title}`;
                    setTimeout(() => { this.scannerMessage = ''; }, 1500);
                } catch (err) {
                    this.scannerMessage = 'Network error.';
                }
            },

            removeLine(idx) { this.form.lines.splice(idx, 1); },

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
                    lines: this.form.lines.map((l) => ({
                        purchase_product_id: l.purchase_product_id,
                        qty:                 Number(l.qty) || 1,
                        to_rack_id:          l.to_rack_id || null,
                        notes:               l.notes || null,
                    })),
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
