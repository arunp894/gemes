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

        {{-- Discount and Tax rows hidden (not used; line totals are Carat × Price) --}}

        <hr class="my-2">

        <div class="d-flex justify-content-between mb-3">
            <span class="fw-semibold">Grand Total</span>
            <span class="fw-bold fs-18 text-primary">@{{ formatMoney(totals.grand) }}</span>
        </div>

        <div class="d-flex justify-content-between mb-2 small">
            <span class="text-muted">Paid</span>
            <span>@{{ formatMoney(totals.paid) }}</span>
        </div>
        <div class="d-flex justify-content-between mb-3 small">
            <span class="text-muted">Due</span>
            <span class="fw-semibold" :class="totals.due > 0 ? 'text-warning' : 'text-success'">
                @{{ formatMoney(totals.due) }}
            </span>
        </div>

        @if (! isset($purchase))
        {{-- Payments editor — create mode only. Once a purchase exists,
             payments are added/removed from its detail page instead
             (PurchaseService::update() never touches existing payments). --}}
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label small text-muted mb-0">Payments</label>
                <button type="button" class="btn btn-sm btn-soft-primary py-0 px-2" @click="addPayment">
                    <i class="ti ti-plus"></i>
                </button>
            </div>

            <p v-if="form.payments.length === 0" class="text-muted small mb-0">No payments yet.</p>

            <div v-for="(p, idx) in form.payments" :key="idx" class="border rounded p-2 mb-2">
                <select class="form-select form-select-sm mb-1" v-model="p.payment_method">
                    @foreach ($paymentMethods as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <input type="number" step="0.01" class="form-control form-control-sm mb-1 text-end"
                    v-model.number="p.amount" placeholder="Amount">
                <input type="date" class="form-control form-control-sm mb-1" v-model="p.payment_date">
                <input type="text" class="form-control form-control-sm mb-1" v-model="p.reference_number"
                    placeholder="Reference (optional)">
                <button type="button" class="btn btn-sm btn-soft-danger w-100" @click="removePayment(idx)">
                    <i class="ti ti-trash me-1"></i> Remove
                </button>
            </div>
        </div>
        @else
        <p class="text-muted small mb-3">
            <a href="{{ route('purchases.show', $purchase) }}">Manage payments</a> from the purchase's detail page.
        </p>
        @endif

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
            @if (isset($purchase) && $purchase->isPosted())
                <button type="button" class="btn btn-primary" @click="submit(true)"
                        :disabled="submitting || form.lines.length === 0">
                    <i class="ti ti-device-floppy me-1"></i>
                    @{{ submitting ? 'Saving…' : 'Save Changes' }}
                </button>
            @else
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
            @endif

            <a href="{{ route('purchases.index') }}" class="btn btn-light">Cancel</a>
        </div>

    </div>
</div>
