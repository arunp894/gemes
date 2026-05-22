{{-- Sticky purchase summary that lives in the right column. --}}

<div class="card position-sticky" style="top: 1rem;">
    <div class="card-header border-light d-flex align-items-center gap-2">
        <i class="ti ti-receipt-2 fs-18 text-primary"></i>
        <h5 class="card-title mb-0">Summary</h5>
    </div>

    <div class="card-body">

        <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Subtotal</span>
            <span class="fw-semibold">@{{ formatMoney(totals.subtotal) }}</span>
        </div>

        <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Discount</span>
            <span class="text-danger">- @{{ formatMoney(totals.discount) }}</span>
        </div>

        {{-- Tax: split for CGST/SGST display, single line for IGST/none --}}
        <template v-if="form.tax_type === 'cgst_sgst'">
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">CGST</span>
                <span>@{{ formatMoney(totals.tax / 2) }}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">SGST</span>
                <span>@{{ formatMoney(totals.tax / 2) }}</span>
            </div>
        </template>
        <template v-else-if="form.tax_type === 'igst'">
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">IGST</span>
                <span>@{{ formatMoney(totals.tax) }}</span>
            </div>
        </template>
        <template v-else>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Tax</span>
                <span>@{{ formatMoney(totals.tax) }}</span>
            </div>
        </template>

        <hr class="my-2">

        <div class="d-flex justify-content-between mb-3">
            <span class="fw-semibold">Grand Total</span>
            <span class="fw-bold fs-18 text-primary">@{{ formatMoney(totals.grand) }}</span>
        </div>

        <div class="mb-2">
            <label class="form-label small text-muted">Paid Amount</label>
            <input type="number" step="0.01" min="0" class="form-control" v-model.number="form.paid_amount">
        </div>

        <div class="d-flex justify-content-between mb-3 small">
            <span class="text-muted">Due</span>
            <span class="fw-semibold" :class="totals.due > 0 ? 'text-warning' : 'text-success'">
                @{{ formatMoney(totals.due) }}
            </span>
        </div>

        <div class="mb-3">
            <label class="form-label small text-muted">Notes</label>
            <textarea class="form-control" rows="2" v-model="form.note" placeholder="optional"></textarea>
        </div>

        {{-- Quick stats row --}}
        <div class="row text-center g-2 mb-3">
            <div class="col-4">
                <div class="border rounded p-2">
                    <div class="fw-bold fs-16">@{{ form.lines.length }}</div>
                    <small class="text-muted">Lines</small>
                </div>
            </div>
            <div class="col-4">
                <div class="border rounded p-2">
                    <div class="fw-bold fs-16">@{{ totalRows }}</div>
                    <small class="text-muted">Rows</small>
                </div>
            </div>
            <div class="col-4">
                <div class="border rounded p-2">
                    <div class="fw-bold fs-16">@{{ totalPiecesAll }}</div>
                    <small class="text-muted">Pieces</small>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="d-grid gap-2">
            <button type="button" class="btn btn-outline-primary" @click="submit(false)"
                    :disabled="submitting || form.lines.length === 0">
                <i class="ti ti-device-floppy me-1"></i>
                @{{ submitting ? 'Saving…' : 'Save Draft' }}
            </button>

            @permission('purchases.post')
            <button type="button" class="btn btn-primary" @click="submit(true)"
                    :disabled="submitting || form.lines.length === 0">
                <i class="ti ti-check me-1"></i>
                @{{ submitting ? 'Saving…' : 'Save & Post' }}
            </button>
            @endpermission

            <a href="{{ route('purchases.index') }}" class="btn btn-light">Cancel</a>
        </div>

    </div>
</div>
