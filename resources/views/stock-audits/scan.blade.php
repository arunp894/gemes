@extends('layout.app')

@section('title', 'Scan — ' . $audit->audit_number)

@section('content')
<div class="container-fluid stock-audits-scan-page" id="scanApp">

    <div class="toast-container position-fixed top-0 end-0 p-3" id="scanToastContainer" style="z-index: 1080;"></div>

    {{-- ── Header ─────────────────────────────────────────── --}}
    <div class="scan-header d-flex align-items-center gap-3 flex-wrap">
        <a href="{{ route('stock-audits.show', $audit) }}" class="scan-back-btn" title="Back to Summary">
            <i class="ti ti-arrow-left"></i>
        </a>
        <div class="flex-grow-1 d-flex align-items-center gap-2 flex-wrap">
            <h4 class="scan-title mb-0">{{ $audit->audit_number }}</h4>
            <span class="scan-subtitle">{{ $audit->location?->name }}</span>
            @if ($audit->category)
                <span class="scan-pill">{{ $audit->category->name }}</span>
            @endif
        </div>
        <a href="{{ route('stock-audits.show', $audit) }}" class="scan-ghost-btn">
            <i class="ti ti-list-details me-1"></i> Summary
        </a>
    </div>

    {{-- ── Progress card ──────────────────────────────────── --}}
    <div class="scan-card scan-progress-card">
        <div class="d-flex flex-wrap align-items-center gap-4">
            <div class="d-flex align-items-end gap-4 flex-grow-1 flex-wrap">
                <div>
                    <div class="scan-big-number">@{{ progress.matched_total }}</div>
                    <div class="scan-big-sub">/ @{{ progress.expected_total }} matched</div>
                </div>
                <div class="scan-divider-v"></div>
                <div>
                    <div class="scan-big-number scan-big-number-accent">@{{ progress.missing_total }}</div>
                    <div class="scan-big-sub">remaining</div>
                </div>
            </div>
            <div class="scan-ring" :style="{ '--pct': progress.percent }">
                <div class="scan-ring-inner">
                    <div class="scan-ring-value">@{{ progress.percent }}%</div>
                    <div class="scan-ring-label">Progress</div>
                </div>
            </div>
        </div>

        <div class="scan-progress-track-wrap">
            <div class="scan-progress-label">@{{ progress.percent }}% Completed</div>
            <div class="scan-progress-track">
                <div class="scan-progress-fill" :style="{ width: progress.percent + '%' }"></div>
            </div>
        </div>

        <div class="scan-stats-row">
            <div class="scan-stat">
                <span class="scan-stat-icon scan-stat-icon-success"><i class="ti ti-circle-check"></i></span>
                <div>
                    <div class="scan-stat-value">@{{ scanCounts.matched }}</div>
                    <div class="scan-stat-label">matched</div>
                </div>
            </div>
            <div class="scan-stat">
                <span class="scan-stat-icon scan-stat-icon-warning"><i class="ti ti-alert-triangle"></i></span>
                <div>
                    <div class="scan-stat-value">@{{ scanCounts.duplicate }}</div>
                    <div class="scan-stat-label">duplicate</div>
                </div>
            </div>
            <div class="scan-stat">
                <span class="scan-stat-icon scan-stat-icon-danger"><i class="ti ti-alert-circle"></i></span>
                <div>
                    <div class="scan-stat-value">@{{ scanCounts.unexpected }}</div>
                    <div class="scan-stat-label">unexpected</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── "All matched" nudge ────────────────────────────── --}}
    <div class="all-matched-banner d-flex align-items-center gap-2" v-if="progress.missing_total === 0 && progress.expected_total > 0">
        <i class="ti ti-confetti fs-24"></i>
        <div class="flex-grow-1">
            <strong>Everything's accounted for!</strong>
            <div class="small">Every expected item at this location has been matched. Ready to complete the audit whenever you are.</div>
        </div>
        <button type="button" class="scan-btn-pill scan-btn-success flex-shrink-0" @click="openCompleteModal">
            <i class="ti ti-check me-1"></i> Complete Audit
        </button>
    </div>

    {{-- ── Scan input card ────────────────────────────────── --}}
    <div class="scan-card">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div class="d-flex align-items-center gap-2">
                <h5 class="scan-card-title mb-0">Scan or enter a lot code / barcode</h5>
                <i class="ti ti-info-circle text-muted" data-bs-toggle="tooltip"
                    title="Tap the box below and scan — most barcode scanners type into whatever field is focused and press Enter automatically."></i>
            </div>
            <button type="button" class="scan-ghost-btn" @click="focusManualInput">
                <i class="ti ti-keyboard me-1"></i> Keyboard Entry
            </button>
        </div>

        <div class="scan-hero" :class="scanFlashClass" @click="focusInput">
            <div class="scan-hero-icon"><i class="ti ti-scan"></i></div>
            <div class="scan-hero-title">
                <span v-if="scanning">Scanning…</span>
                <span v-else-if="lastResult === 'matched'">Matched!</span>
                <span v-else-if="lastResult === 'duplicate'">Already scanned</span>
                <span v-else-if="lastResult === 'unexpected'">Not expected here</span>
                <span v-else-if="lastResult === 'error'">Something went wrong</span>
                <span v-else>Ready to scan</span>
            </div>
            <div class="scan-hero-sub">
                <span v-if="lastResult">@{{ lastMessage }}</span>
                <span v-else>Scan a barcode or enter a lot code to begin</span>
            </div>
            <input ref="scanInput" type="text" class="scan-hero-input" v-model="scanValue"
                @keyup.enter="submitScan" :disabled="scanning" autocomplete="off" aria-label="Scan input">
        </div>

        <div class="scan-or-divider"><span>OR</span></div>

        <div class="d-flex gap-2">
            <input ref="manualInput" type="text" class="form-control scan-manual-input" v-model="scanValue"
                @keyup.enter="submitScan" :disabled="scanning" placeholder="Enter lot code or barcode" autocomplete="off">
            <button type="button" class="scan-enter-btn" @click="submitScan" :disabled="scanning || !scanValue.trim()">
                Enter <i class="ti ti-corner-down-left ms-1"></i>
            </button>
        </div>

        <div class="scan-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="scan-tip"><i class="ti ti-bulb"></i> Tip: You can also paste (Ctrl+V) a lot code or barcode.</div>
                <button type="button" class="scan-undo-link" @click="undoLast" :disabled="undoing || scans.length === 0">
                    <span v-if="undoing" class="spinner-border spinner-border-sm"></span>
                    <i v-else class="ti ti-arrow-back-up"></i> Undo last scan
                </button>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="scan-btn-pill scan-btn-danger" @click="openCancelModal" :disabled="closing">
                    <i class="ti ti-ban me-1"></i> Cancel Audit
                </button>
                <button type="button" class="scan-btn-pill scan-btn-success"
                    :class="{ 'pulse-btn': progress.missing_total === 0 && progress.expected_total > 0 }"
                    @click="openCompleteModal" :disabled="closing">
                    <i class="ti ti-check me-1"></i> Complete Audit
                </button>
            </div>
        </div>
    </div>

    {{-- ── Recent scans ───────────────────────────────────── --}}
    <div class="scan-card">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h5 class="scan-card-title mb-0">Recent Scans</h5>
            <a href="{{ route('stock-audits.show', $audit) }}" class="scan-view-all">View all <i class="ti ti-arrow-right"></i></a>
        </div>
        <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
            <table class="table table-sm table-custom align-middle mb-0">
                <thead class="bg-light bg-opacity-25 thead-sm">
                    <tr class="text-uppercase fs-xxs">
                        <th>Time</th>
                        <th>Lot Code / Barcode</th>
                        <th>Product</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody v-if="scans.length === 0">
                    <tr>
                        <td colspan="4" class="text-center py-5">
                            <div class="scan-empty-icon mx-auto mb-2"><i class="ti ti-clipboard-list"></i></div>
                            <div class="fw-bold">No scans yet</div>
                            <div class="text-muted small">Start scanning above to see results here.</div>
                        </td>
                    </tr>
                </tbody>
                <transition-group name="scan-row" tag="tbody" v-else>
                    <tr v-for="s in scans" :key="s.id" :class="{ 'scan-row-newest': s === scans[0] }">
                        <td class="text-muted">@{{ s.scanned_at }}</td>
                        <td><code>@{{ s.scanned_value }}</code></td>
                        <td>@{{ s.product_title || '—' }}</td>
                        <td><span class="badge fs-xxs" :class="s.badge_class">@{{ s.result_label }}</span></td>
                    </tr>
                </transition-group>
            </table>
        </div>
    </div>

    {{-- ==================== Complete Audit Confirmation Modal ==================== --}}
    <div class="modal fade" id="completeAuditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="confirm-modal-icon confirm-modal-icon-success mx-auto mb-3">
                        <i class="ti ti-check"></i>
                    </div>
                    <h5 class="modal-title mb-2">Complete this audit?</h5>
                    <p class="text-muted mb-0">This closes the count and locks it from further scanning.</p>
                    <div class="alert alert-warning d-flex gap-2 align-items-center mt-3 mb-0 text-start" v-if="progress.missing_total > 0">
                        <i class="ti ti-alert-triangle fs-18"></i>
                        <div>@{{ progress.missing_total }} item(s) are still unmatched. You'll be able to review the missing-stock report afterward.</div>
                    </div>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Scanning</button>
                    <button type="button" class="btn btn-success" :disabled="closing" @click="completeAudit">
                        <i class="ti ti-check me-1"></i>Complete Audit
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== Cancel Audit Confirmation Modal ==================== --}}
    <div class="modal fade" id="cancelAuditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="confirm-modal-icon confirm-modal-icon-danger mx-auto mb-3">
                        <i class="ti ti-ban"></i>
                    </div>
                    <h5 class="modal-title mb-2">Cancel this audit?</h5>
                    <p class="text-muted mb-0">
                        All progress will be kept for reference but no stock will be adjusted. This can't be undone.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Auditing</button>
                    <button type="button" class="btn btn-danger" :disabled="closing" @click="cancelAudit">
                        <i class="ti ti-ban me-1"></i>Cancel Audit
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
    /* ==========================================================
       Stock Audit — Scan page. Self-contained indigo/lavender
       design scoped under .stock-audits-scan-page.
       ========================================================== */
    .stock-audits-scan-page {
        --scan-primary: #4f46e5;
        --scan-primary-dark: #4338ca;
        --scan-primary-light: #eef2ff;
        --scan-primary-border: #c7d2fe;
        --scan-success: #16a34a;
        --scan-success-bg: #dcfce7;
        --scan-warning: #d97706;
        --scan-warning-bg: #fef3c7;
        --scan-danger: #dc2626;
        --scan-danger-bg: #fee2e2;
        --scan-border: #e5e7eb;
        --scan-text: #111827;
        --scan-text-muted: #6b7280;
        padding-top: 16px;
        padding-bottom: 24px;
    }

    /* ---------- Header ---------- */
    .scan-header { margin-bottom: 16px; }
    .scan-back-btn {
        width: 38px; height: 38px; border-radius: 10px;
        background: var(--scan-primary-light); color: var(--scan-primary);
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.1rem; flex-shrink: 0; text-decoration: none;
        transition: background .15s ease;
    }
    .scan-back-btn:hover { background: var(--scan-primary-border); color: var(--scan-primary-dark); }
    .scan-title { font-size: 1.25rem; font-weight: 800; color: var(--scan-text); }
    .scan-subtitle { color: var(--scan-text-muted); font-size: 0.9rem; }
    .scan-pill {
        display: inline-flex; align-items: center;
        background: var(--scan-primary-light); color: var(--scan-primary);
        border-radius: 999px; padding: 3px 12px; font-size: 0.75rem; font-weight: 600;
    }
    .scan-ghost-btn {
        display: inline-flex; align-items: center;
        background: #fff; color: var(--scan-text); border: 1px solid var(--scan-border);
        border-radius: 10px; padding: 7px 14px; font-size: 0.8125rem; font-weight: 600;
        text-decoration: none; transition: border-color .15s ease, background .15s ease;
    }
    .scan-ghost-btn:hover { border-color: var(--scan-primary-border); background: var(--scan-primary-light); color: var(--scan-primary-dark); }

    /* ---------- Shared card shell ---------- */
    .scan-card {
        background: #fff; border: 1px solid var(--scan-border); border-radius: 14px;
        padding: 20px; margin-bottom: 16px; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .scan-card-title { font-size: 1rem; font-weight: 700; color: var(--scan-text); }

    /* ---------- Progress card ---------- */
    .scan-big-number { font-size: 2.25rem; font-weight: 800; line-height: 1; color: var(--scan-text); }
    .scan-big-number-accent { color: var(--scan-primary); }
    .scan-big-sub { color: var(--scan-text-muted); font-size: 0.8125rem; margin-top: 2px; }
    .scan-divider-v { width: 1px; align-self: stretch; background: var(--scan-border); }

    .scan-ring {
        width: 96px; height: 96px; border-radius: 50%; flex-shrink: 0;
        background: conic-gradient(var(--scan-primary) calc(var(--pct, 0) * 1%), #e5e7eb 0);
        display: flex; align-items: center; justify-content: center;
        transition: background 0.3s ease;
    }
    .scan-ring-inner {
        width: 76px; height: 76px; border-radius: 50%; background: #fff;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
    }
    .scan-ring-value { font-size: 1.1rem; font-weight: 800; color: var(--scan-text); line-height: 1.1; }
    .scan-ring-label { font-size: 0.6875rem; color: var(--scan-text-muted); }

    .scan-progress-track-wrap { margin-top: 18px; }
    .scan-progress-label { font-size: 0.8125rem; font-weight: 600; color: var(--scan-text); margin-bottom: 6px; }
    .scan-progress-track { height: 10px; border-radius: 999px; background: #eef0f3; overflow: hidden; }
    .scan-progress-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg, var(--scan-primary), var(--scan-success)); transition: width 0.3s ease; }

    .scan-stats-row {
        display: flex; flex-wrap: wrap; gap: 24px; margin-top: 18px; padding-top: 16px;
        border-top: 1px dashed var(--scan-border);
    }
    .scan-stat { display: flex; align-items: center; gap: 10px; }
    .scan-stat-icon {
        width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center; font-size: 1rem;
    }
    .scan-stat-icon-success { background: var(--scan-success-bg); color: var(--scan-success); }
    .scan-stat-icon-warning { background: var(--scan-warning-bg); color: var(--scan-warning); }
    .scan-stat-icon-danger  { background: var(--scan-danger-bg);  color: var(--scan-danger); }
    .scan-stat-value { font-weight: 800; font-size: 1.0625rem; color: var(--scan-text); line-height: 1.1; }
    .scan-stat-label { font-size: 0.75rem; color: var(--scan-text-muted); }

    /* ---------- "All matched" banner ---------- */
    .all-matched-banner {
        background: var(--scan-success-bg); color: #065f46; border: 1px solid #bbf7d0;
        border-radius: 14px; padding: 16px 20px; margin-bottom: 16px;
    }

    /* ---------- Scan hero ---------- */
    .scan-hero {
        position: relative;
        border: 2px dashed var(--scan-primary-border);
        background: var(--scan-primary-light);
        border-radius: 14px;
        padding: 40px 20px;
        text-align: center;
        cursor: text;
        transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
    }
    .scan-hero-icon {
        width: 56px; height: 56px; border-radius: 50%;
        background: #e0e7ff; color: var(--scan-primary);
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.5rem; margin-bottom: 14px;
    }
    .scan-hero-title { font-size: 1.375rem; font-weight: 800; color: var(--scan-primary-dark); }
    .scan-hero-sub { color: var(--scan-text-muted); font-size: 0.875rem; margin-top: 4px; }
    .scan-hero-input {
        position: absolute; inset: 0; width: 100%; height: 100%;
        opacity: 0; border: 0; background: transparent; padding: 0; margin: 0; cursor: text;
    }
    .scan-hero.scan-flash-matched    { border-color: var(--scan-success); background: var(--scan-success-bg); box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.12); }
    .scan-hero.scan-flash-duplicate  { border-color: var(--scan-warning); background: var(--scan-warning-bg); box-shadow: 0 0 0 4px rgba(217, 119, 6, 0.12); }
    .scan-hero.scan-flash-unexpected { border-color: var(--scan-danger);  background: var(--scan-danger-bg);  box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.12); }
    .scan-hero.scan-flash-matched    .scan-hero-icon { background: #bbf7d0; color: var(--scan-success); }
    .scan-hero.scan-flash-duplicate  .scan-hero-icon { background: #fde68a; color: var(--scan-warning); }
    .scan-hero.scan-flash-unexpected .scan-hero-icon { background: #fecaca; color: var(--scan-danger); }
    .scan-hero.scan-flash-matched    .scan-hero-title { color: var(--scan-success); }
    .scan-hero.scan-flash-duplicate  .scan-hero-title { color: var(--scan-warning); }
    .scan-hero.scan-flash-unexpected .scan-hero-title { color: var(--scan-danger); }

    .scan-or-divider { display: flex; align-items: center; gap: 12px; color: var(--scan-text-muted); font-size: 0.75rem; font-weight: 700; margin: 18px 0; }
    .scan-or-divider::before, .scan-or-divider::after { content: ''; flex: 1; height: 1px; background: var(--scan-border); }

    .scan-manual-input { border-radius: 10px; padding: 0.65rem 0.9rem; font-size: 0.9375rem; border-color: var(--scan-border); }
    .scan-manual-input:focus { border-color: var(--scan-primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12); }
    .scan-enter-btn {
        display: inline-flex; align-items: center; white-space: nowrap;
        background: var(--scan-primary); color: #fff; border: none; border-radius: 10px;
        padding: 0 20px; font-weight: 700; font-size: 0.9375rem; transition: background .15s ease;
    }
    .scan-enter-btn:hover:not(:disabled) { background: var(--scan-primary-dark); }
    .scan-enter-btn:disabled { opacity: 0.5; }

    /* ---------- Footer row ---------- */
    .scan-footer { margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--scan-border); }
    .scan-tip { color: var(--scan-text-muted); font-size: 0.8125rem; display: inline-flex; align-items: center; gap: 6px; }
    .scan-tip .ti-bulb { color: var(--scan-primary); }
    .scan-undo-link {
        background: none; border: none; color: var(--scan-text-muted); font-size: 0.8125rem; font-weight: 600;
        display: inline-flex; align-items: center; gap: 5px; padding: 4px 8px; border-radius: 8px;
        transition: background .15s ease, color .15s ease;
    }
    .scan-undo-link:hover:not(:disabled) { background: var(--scan-primary-light); color: var(--scan-primary-dark); }
    .scan-undo-link:disabled { opacity: 0.45; }
    .scan-undo-link .spinner-border { width: 0.85rem; height: 0.85rem; border-width: 0.15em; }

    .scan-btn-pill {
        display: inline-flex; align-items: center; border: none; border-radius: 999px;
        padding: 9px 20px; font-weight: 700; font-size: 0.8125rem; transition: all .15s ease;
    }
    .scan-btn-danger { background: var(--scan-danger-bg); color: var(--scan-danger); }
    .scan-btn-danger:hover:not(:disabled) { background: #fecaca; }
    .scan-btn-success { background: var(--scan-success); color: #fff; }
    .scan-btn-success:hover:not(:disabled) { background: #15803d; }
    .scan-btn-pill:disabled { opacity: 0.6; }

    @keyframes pulse-glow {
        0%, 100% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.45); }
        50%      { box-shadow: 0 0 0 8px rgba(22, 163, 74, 0); }
    }
    .pulse-btn { animation: pulse-glow 1.6s ease-out infinite; }

    /* ---------- Recent scans ---------- */
    .scan-view-all { color: var(--scan-primary); font-size: 0.8125rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
    .scan-view-all:hover { color: var(--scan-primary-dark); }
    .scan-empty-icon {
        width: 64px; height: 64px; border-radius: 50%; background: var(--scan-primary-light); color: var(--scan-primary);
        display: flex; align-items: center; justify-content: center; font-size: 1.75rem;
    }
    @keyframes row-highlight {
        0%   { background-color: #fef9c3; }
        100% { background-color: transparent; }
    }
    .scan-row-newest { animation: row-highlight 1.6s ease-out; }
    .scan-row-enter-active { transition: opacity .25s ease; }
    .scan-row-enter { opacity: 0; }

    /* ---------- Confirmation modal icon badges ---------- */
    .confirm-modal-icon {
        width: 56px; height: 56px; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem;
    }
    .confirm-modal-icon-success { background: var(--scan-success-bg); color: var(--scan-success); }
    .confirm-modal-icon-danger  { background: var(--scan-danger-bg);  color: var(--scan-danger); }

    @media (max-width: 576px) {
        .scan-hero { padding: 28px 14px; }
        .scan-hero-title { font-size: 1.125rem; }
        .scan-ring { display: none; }
    }
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
    $scanCountsData = $scanCounts;
@endphp
<script>
$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    function showToast(type, message) {
        const container = document.getElementById('scanToastContainer');
        if (!container) return;
        const isSuccess = type === 'success';
        const el = document.createElement('div');
        el.className = 'toast align-items-center border-0 text-bg-' + (isSuccess ? 'success' : 'danger');
        el.setAttribute('role', 'alert');
        el.innerHTML = '<div class="d-flex">'
            + '<div class="toast-body d-flex align-items-center gap-2">'
            + '<i class="ti ' + (isSuccess ? 'ti-circle-check' : 'ti-alert-circle') + ' fs-lg"></i>'
            + $('<div/>').text(message).html()
            + '</div>'
            + '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>'
            + '</div>';
        container.appendChild(el);
        const toast = new bootstrap.Toast(el, { delay: 2500 });
        el.addEventListener('hidden.bs.toast', () => el.remove());
        toast.show();
    }

    // Short tone feedback so staff can keep their eyes on the item being
    // scanned instead of the screen — one clear beep for a match, two lower
    // beeps for anything that needs a second look (duplicate/unexpected).
    let audioCtx = null;
    function beep(freq, duration) {
        try {
            audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
            const osc  = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.type = 'sine';
            osc.frequency.value = freq;
            gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + duration);
            osc.connect(gain).connect(audioCtx.destination);
            osc.start();
            osc.stop(audioCtx.currentTime + duration);
        } catch (e) { /* Web Audio unsupported/blocked — silently skip */ }
    }
    function playResultSound(result) {
        if (result === 'matched') {
            beep(880, 0.12);
        } else {
            beep(300, 0.15);
            setTimeout(() => beep(300, 0.15), 160);
        }
    }

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
            scanCounts: @json($scanCountsData),
            scans: @json($scanRecentScansData),
            resultTimer: null,
        },
        computed: {
            scanFlashClass() {
                if (this.lastResult === 'matched') return 'scan-flash-matched';
                if (this.lastResult === 'duplicate') return 'scan-flash-duplicate';
                if (this.lastResult === 'unexpected') return 'scan-flash-unexpected';
                return '';
            },
        },
        mounted() {
            this.focusInput();
            this.$nextTick(() => {
                document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => new bootstrap.Tooltip(el));
            });
        },
        methods: {
            focusInput() {
                this.$nextTick(() => this.$refs.scanInput?.focus());
            },
            focusManualInput() {
                this.$nextTick(() => this.$refs.manualInput?.focus());
            },
            vibrate(pattern) {
                if (navigator.vibrate) {
                    try { navigator.vibrate(pattern); } catch (e) {}
                }
            },
            scheduleResultClear() {
                clearTimeout(this.resultTimer);
                this.resultTimer = setTimeout(() => { this.lastResult = null; }, 4000);
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
                        playResultSound('error');
                    } else {
                        this.lastResult = data.result;
                        this.lastMessage = data.message;
                        this.progress = data.progress;
                        this.scanCounts = data.scan_counts;
                        this.scans.unshift(data.scan);
                        this.vibrate(data.result === 'matched' ? 40 : [80, 40, 80]);
                        playResultSound(data.result);
                    }
                } catch (err) {
                    this.lastResult = 'error';
                    this.lastMessage = 'Network error. Please try again.';
                } finally {
                    this.scanValue = '';
                    this.scanning = false;
                    this.focusInput();
                    this.scheduleResultClear();
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
                        this.scanCounts = data.scan_counts;
                        // Remove the specific scan that was undone (scoped
                        // server-side to this user's own last scan) rather
                        // than blindly popping the front of the feed --
                        // another device may have scanned more recently.
                        if (data.scan_id) {
                            this.scans = this.scans.filter((s) => s.id !== data.scan_id);
                        }
                        this.lastResult = null;
                        showToast('success', data.message);
                    } else {
                        showToast('error', data.message || 'Could not undo.');
                    }
                } finally {
                    this.undoing = false;
                    this.focusInput();
                }
            },
            openCompleteModal() {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('completeAuditModal')).show();
            },
            openCancelModal() {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('cancelAuditModal')).show();
            },
            async completeAudit() {
                await this.closeAudit('{{ route('stock-audits.complete', $audit) }}', 'completeAuditModal');
            },
            async cancelAudit() {
                await this.closeAudit('{{ route('stock-audits.cancel', $audit) }}', 'cancelAuditModal');
            },
            async closeAudit(url, modalId) {
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
                        bootstrap.Modal.getOrCreateInstance(document.getElementById(modalId)).hide();
                        showToast('error', data.message || 'Something went wrong.');
                        this.closing = false;
                    }
                } catch (err) {
                    bootstrap.Modal.getOrCreateInstance(document.getElementById(modalId)).hide();
                    showToast('error', 'Network error. Please try again.');
                    this.closing = false;
                }
            },
        },
    });
});
</script>
@endpush
