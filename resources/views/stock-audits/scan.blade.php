@extends('layout.app')

@section('title', 'Scan — ' . $audit->audit_number)

@section('content')
<div class="container-fluid" id="scanApp">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">
                <i class="ti ti-scan text-primary me-2"></i>{{ $audit->audit_number }}
                <span class="fs-sm text-muted fw-normal ms-2">{{ $audit->location?->name }}</span>
            </h4>
        </div>
        <div class="text-end">
            <a href="{{ route('stock-audits.show', $audit) }}" class="btn btn-light btn-sm">
                <i class="ti ti-list-details me-1"></i> Summary
            </a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <span class="fs-24 fw-bold">@{{ progress.matched_total }}</span>
                            <span class="text-muted"> / @{{ progress.expected_total }} matched</span>
                        </div>
                        <div class="text-end">
                            <span class="badge badge-soft-danger fs-sm" v-if="progress.missing_total > 0">@{{ progress.missing_total }} remaining</span>
                            <span class="badge badge-soft-success fs-sm" v-else>All accounted for</span>
                        </div>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-success" role="progressbar" :style="{ width: progress.percent + '%' }"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <label class="form-label">Scan or enter a lot code / barcode</label>
                    <input ref="scanInput" type="text" class="form-control form-control-lg"
                        v-model="scanValue" @keyup.enter="submitScan" :disabled="scanning"
                        placeholder="Waiting for scan…" autocomplete="off">

                    <div v-if="lastResult" class="alert mt-3 mb-0 d-flex align-items-center gap-2"
                        :class="lastResultAlertClass">
                        <i :class="lastResultIconClass" class="fs-18"></i>
                        <div>@{{ lastMessage }}</div>
                    </div>
                </div>
                <div class="card-footer border-0 d-flex flex-wrap gap-2 justify-content-between">
                    <button type="button" class="btn btn-soft-warning" @click="undoLast" :disabled="undoing || scans.length === 0">
                        <i class="ti ti-arrow-back-up me-1"></i> Undo Last Scan
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-soft-danger" @click="cancelAudit" :disabled="closing">
                            <i class="ti ti-ban me-1"></i> Cancel Audit
                        </button>
                        <button type="button" class="btn btn-success" @click="completeAudit" :disabled="closing">
                            <i class="ti ti-check me-1"></i> Complete Audit
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Recent Scans</h5>
                </div>
                <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                    <table class="table table-sm table-custom align-middle mb-0">
                        <thead class="bg-light bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th>Time</th>
                                <th>Scanned Value</th>
                                <th>Product</th>
                                <th>Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="scans.length === 0">
                                <td colspan="4" class="text-center text-muted py-4">No scans yet — start scanning above.</td>
                            </tr>
                            <tr v-for="s in scans" :key="s.id">
                                <td class="text-muted">@{{ s.scanned_at }}</td>
                                <td><code>@{{ s.scanned_value }}</code></td>
                                <td>@{{ s.product_title || '—' }}</td>
                                <td><span class="badge fs-xxs" :class="s.badge_class">@{{ s.result_label }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
    #scanApp input.form-control-lg { font-size: 1.4rem; text-align: center; letter-spacing: 0.05em; }
</style>
@endpush

@push('scripts')
@php
    $scanProgressData = [
        'expected_total' => (int) $audit->expected_total,
        'matched_total'  => (int) $audit->matched_total,
        'missing_total'  => $audit->missingTotal(),
        'percent'        => $audit->progressPercent(),
    ];
    $scanRecentScansData = $recentScans->map(fn ($s) => [
        'id'            => $s->id,
        'scanned_value' => $s->scanned_value,
        'result'        => $s->result,
        'result_label'  => $s->resultLabel(),
        'badge_class'   => $s->resultBadgeClass(),
        'product_title' => $s->item?->product?->title,
        'scanned_at'    => $s->scanned_at->format('H:i:s'),
    ]);
@endphp
<script>
$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    new Vue({
        el: '#scanApp',
        data: {
            scanValue: '',
            scanning: false,
            undoing: false,
            closing: false,
            lastResult: null,
            lastMessage: '',
            progress: @json($scanProgressData),
            scans: @json($scanRecentScansData),
        },
        computed: {
            lastResultAlertClass() {
                if (this.lastResult === 'matched') return 'alert-success';
                if (this.lastResult === 'duplicate') return 'alert-warning';
                if (this.lastResult === 'unexpected') return 'alert-danger';
                return 'alert-secondary';
            },
            lastResultIconClass() {
                if (this.lastResult === 'matched') return 'ti ti-circle-check';
                if (this.lastResult === 'duplicate') return 'ti ti-alert-triangle';
                if (this.lastResult === 'unexpected') return 'ti ti-alert-circle';
                return 'ti ti-info-circle';
            },
        },
        mounted() {
            this.focusInput();
        },
        methods: {
            focusInput() {
                this.$nextTick(() => this.$refs.scanInput?.focus());
            },
            vibrate(pattern) {
                if (navigator.vibrate) {
                    try { navigator.vibrate(pattern); } catch (e) {}
                }
            },
            async submitScan() {
                const value = this.scanValue.trim();
                if (!value || this.scanning) return;

                this.scanning = true;
                try {
                    const res = await fetch('{{ route('stock-audits.scan.store', $audit) }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ value }),
                    });
                    const data = await res.json();

                    if (!data.ok) {
                        this.lastResult = 'error';
                        this.lastMessage = data.message || 'Something went wrong.';
                        this.vibrate([80, 40, 80]);
                    } else {
                        this.lastResult = data.result;
                        this.lastMessage = data.message;
                        this.progress = data.progress;
                        this.scans.unshift(data.scan);
                        this.vibrate(data.result === 'matched' ? 40 : [80, 40, 80]);
                    }
                } catch (err) {
                    this.lastResult = 'error';
                    this.lastMessage = 'Network error. Please try again.';
                } finally {
                    this.scanValue = '';
                    this.scanning = false;
                    this.focusInput();
                }
            },
            async undoLast() {
                if (this.undoing) return;
                this.undoing = true;
                try {
                    const res = await fetch('{{ route('stock-audits.undo-scan', $audit) }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                    });
                    const data = await res.json();
                    if (data.ok) {
                        this.progress = data.progress;
                        // Remove the specific scan that was undone (scoped
                        // server-side to this user's own last scan) rather
                        // than blindly popping the front of the feed --
                        // another device may have scanned more recently.
                        if (data.scan_id) {
                            this.scans = this.scans.filter((s) => s.id !== data.scan_id);
                        }
                        this.lastResult = null;
                    } else {
                        alert(data.message || 'Could not undo.');
                    }
                } finally {
                    this.undoing = false;
                    this.focusInput();
                }
            },
            async completeAudit() {
                if (this.progress.missing_total > 0) {
                    if (!confirm(`${this.progress.missing_total} item(s) are still unmatched. Complete the audit anyway? You'll be able to review the missing-stock report afterward.`)) {
                        return;
                    }
                } else if (!confirm('Complete this audit?')) {
                    return;
                }
                await this.closeAudit('{{ route('stock-audits.complete', $audit) }}');
            },
            async cancelAudit() {
                if (!confirm('Cancel this audit? All progress will be kept for reference but no stock will be adjusted.')) return;
                await this.closeAudit('{{ route('stock-audits.cancel', $audit) }}');
            },
            async closeAudit(url) {
                this.closing = true;
                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                        },
                    });
                    const data = await res.json();
                    if (data.ok) {
                        window.location.href = data.redirect;
                    } else {
                        alert(data.message || 'Something went wrong.');
                        this.closing = false;
                    }
                } catch (err) {
                    alert('Network error. Please try again.');
                    this.closing = false;
                }
            },
        },
    });
});
</script>
@endpush
