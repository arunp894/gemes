@extends('layout.app')

@section('title', $audit->audit_number)

@section('content')
<div class="container-fluid stock-audits-page stock-audits-show-page">

    <div class="toast-container position-fixed top-0 end-0 p-3" id="auditShowToastContainer" style="z-index: 1080;"></div>

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">
                {{ $audit->audit_number }}
                <span class="badge {{ $audit->statusBadgeClass() }} align-middle ms-1">{{ $audit->statusLabel() }}</span>
            </h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('stock-audits.index') }}">Stock Audits</a></li>
                <li class="breadcrumb-item active">{{ $audit->audit_number }}</li>
            </ol>
        </div>
    </div>

    <div class="row g-3">

        {{-- Header / summary card --}}
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header border-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Details</h5>
                    @permission('stock-audits.scan')
                        @if ($audit->isInProgress())
                            <a href="{{ route('stock-audits.scan', $audit) }}" class="btn btn-sm btn-primary">
                                <i class="ti ti-scan me-1"></i> Continue Scanning
                            </a>
                        @endif
                    @endpermission
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td class="text-muted" style="width: 40%;">Location</td>
                            <td class="fw-semibold">{{ $audit->location?->name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Stone</td>
                            <td>{{ $audit->categoryLabel() }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Audit Date</td>
                            <td>{{ optional($audit->audit_date)->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Started</td>
                            <td>{{ optional($audit->started_at)->format('d M Y, H:i') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Completed</td>
                            <td>{{ optional($audit->completed_at)->format('d M Y, H:i') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Cancelled</td>
                            <td>{{ optional($audit->cancelled_at)->format('d M Y, H:i') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Started by</td>
                            <td>{{ $audit->creator?->name ?? '—' }}</td>
                        </tr>
                        @if ($audit->note)
                        <tr>
                            <td class="text-muted align-top">Note</td>
                            <td>{{ $audit->note }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
                @if ($audit->isInProgress())
                <div class="card-footer border-0 d-flex gap-2">
                    <button type="button" class="btn btn-soft-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelAuditModal">
                        <i class="ti ti-ban me-1"></i> Cancel Audit
                    </button>
                    <button type="button" class="btn btn-soft-success btn-sm" data-bs-toggle="modal" data-bs-target="#completeAuditModal">
                        <i class="ti ti-check me-1"></i> Complete Audit
                    </button>
                </div>
                @endif
            </div>
        </div>

        {{-- Progress + scan breakdown --}}
        <div class="col-xl-8">
            <div class="row g-3">
                <div class="col-sm-3 col-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 class="mb-0">{{ (int) $audit->expected_total }}</h3>
                            <p class="text-muted mb-0 fs-xxs text-uppercase">Expected</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3 col-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 class="mb-0 text-success">{{ (int) $audit->matched_total }}</h3>
                            <p class="text-muted mb-0 fs-xxs text-uppercase">Matched</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3 col-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 class="mb-0 text-danger">{{ $audit->missingTotal() }}</h3>
                            <p class="text-muted mb-0 fs-xxs text-uppercase">Missing</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3 col-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <h3 class="mb-0">{{ $audit->progressPercent() }}%</h3>
                            <p class="text-muted mb-0 fs-xxs text-uppercase">Counted</p>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card">
                        <div class="card-header border-light">
                            <h5 class="card-title mb-0">Scan Activity</h5>
                        </div>
                        <div class="card-body d-flex flex-wrap gap-4">
                            <div>
                                <span class="badge badge-soft-success fs-xxs me-1">Matched</span>
                                <span class="fw-semibold">{{ $scanCounts['matched'] }}</span>
                            </div>
                            <div>
                                <span class="badge badge-soft-warning fs-xxs me-1">Duplicate</span>
                                <span class="fw-semibold">{{ $scanCounts['duplicate'] }}</span>
                            </div>
                            <div>
                                <span class="badge badge-soft-danger fs-xxs me-1">Unexpected</span>
                                <span class="fw-semibold">{{ $scanCounts['unexpected'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Missing stock report --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header border-light justify-content-between">
                    <h5 class="card-title mb-0">
                        Missing Stock
                        <span class="text-muted fs-sm fw-normal">— not found during this count</span>
                    </h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('stock-audits.export.pdf', $audit) }}" class="btn btn-sm btn-soft-danger">
                            <i class="ti ti-file-type-pdf me-1"></i> Export PDF
                        </a>
                        <a href="{{ route('stock-audits.export.excel', $audit) }}" class="btn btn-sm btn-soft-success">
                            <i class="ti ti-file-type-xls me-1"></i> Export Excel
                        </a>
                        @permission('stock-audits.write-off')
                        @if ($canWriteOff)
                        <button type="button" class="btn btn-sm btn-soft-warning" data-bs-toggle="modal" data-bs-target="#writeOffModal">
                            <i class="ti ti-adjustments me-1"></i> Write Off Missing Stock
                        </button>
                        @endif
                        @endpermission
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="missingTable" class="table table-custom table-centered table-hover w-100 mb-0">
                        <thead class="bg-light bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="text-center" style="width: 1%;">#</th>
                                <th>Lot Code</th>
                                <th>Product</th>
                                <th>SKU</th>
                                <th class="text-end">Carat</th>
                                <th>Stone</th>
                                <th>Supplier</th>
                                <th>Invoice #</th>
                                <th>Purchase Date</th>
                                <th class="text-end">Cost Price</th>
                                <th class="text-end">Qty</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <div class="card-footer border-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div id="missingInfoSlot" class="text-muted small"></div>
                        <div id="missingPaginationSlot"></div>
                    </div>
                </div>
            </div>
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
                    <p class="text-muted mb-0">
                        This closes the count and locks it from further scanning.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmCompleteBtn">
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
                    <button type="button" class="btn btn-danger" id="confirmCancelBtn">
                        <i class="ti ti-ban me-1"></i>Cancel Audit
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== Write Off Missing Stock Confirmation Modal ==================== --}}
    <div class="modal fade" id="writeOffModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="confirm-modal-icon confirm-modal-icon-warning mx-auto mb-3">
                        <i class="ti ti-adjustments"></i>
                    </div>
                    <h5 class="modal-title mb-2">Write off missing stock?</h5>
                    <p class="text-muted mb-0">
                        This will book a stock adjustment for every item still missing, reducing on-hand quantity
                        in the ledger. This cannot be undone from here.
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="confirmWriteOffBtn">
                        <i class="ti ti-adjustments me-1"></i>Write Off Missing Stock
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
       Stock Audit — Show page. Shares the Stock Audits module's
       visual identity with stock-audits/index.blade.php. Scoped
       under .stock-audits-page / .stock-audits-show-page.
       ========================================================== */
    .stock-audits-page {
        --audits-primary: #1d4ed8;
        --audits-primary-dark: #1e3a8a;
        --audits-cyan: #14b8a6;
        --audits-info: #0891b2;
        --audits-success: #059669;
        --audits-warning: #d97706;
        --audits-danger: #dc2626;
        --audits-surface: #ffffff;
        --audits-border: #e2e8f0;
        --audits-text: #1e293b;
        --audits-text-muted: #64748b;
        padding-top: 0;
        padding-bottom: 20px;
    }
    .stock-audits-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid var(--audits-border);
    }
    .stock-audits-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        color: var(--audits-text);
        position: relative;
        padding-left: 12px;
    }
    .stock-audits-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0; top: 2px; bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, var(--audits-primary), var(--audits-cyan));
    }
    .stock-audits-page .breadcrumb { font-size: 0.75rem; }

    .stock-audits-page .card { border: 1px solid var(--audits-border); border-radius: 10px; box-shadow: none; }
    .stock-audits-page .card-header { padding: 12px 16px; background: var(--audits-surface); }
    .stock-audits-page .card-footer { padding: 10px 16px; }
    .stock-audits-page .card-title { font-size: 0.9375rem; font-weight: 700; }

    .stock-audits-page #missingTable thead th {
        background: #f1f5f9;
        color: var(--audits-text);
        font-weight: 700;
        font-size: 0.6875rem;
        letter-spacing: 0.03em;
        padding: 8px 12px;
        border-bottom: 1px solid var(--audits-border);
    }
    .stock-audits-page #missingTable tbody td {
        padding: 6px 12px;
        vertical-align: middle;
        border-bottom: 1px solid var(--audits-border);
        font-size: 0.8125rem;
    }
    .stock-audits-page #missingTable tbody tr:hover { background: #f8fafc; }

    /* Confirmation modal icon badges, shared with Purchase/Supplier show pages */
    .confirm-modal-icon {
        width: 56px; height: 56px; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center; font-size: 1.5rem;
    }
    .confirm-modal-icon-success { background: #ecfdf5; color: var(--audits-success); }
    .confirm-modal-icon-danger  { background: #fef2f2; color: var(--audits-danger); }
    .confirm-modal-icon-warning { background: #fffbeb; color: var(--audits-warning); }

    #missingTable_wrapper .dataTables_length, #missingTable_wrapper .dataTables_filter { display: none !important; }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    function showToast(type, message) {
        const container = document.getElementById('auditShowToastContainer');
        if (!container) return;
        const icons = { success: 'ti-circle-check', error: 'ti-alert-circle', info: 'ti-info-circle' };
        const bg    = { success: 'success', error: 'danger', info: 'info' };
        const el = document.createElement('div');
        el.className = 'toast align-items-center border-0 text-bg-' + (bg[type] || 'secondary');
        el.setAttribute('role', 'alert');
        el.innerHTML = '<div class="d-flex">'
            + '<div class="toast-body d-flex align-items-center gap-2">'
            + '<i class="ti ' + (icons[type] || 'ti-info-circle') + ' fs-lg"></i>'
            + $('<div/>').text(message).html()
            + '</div>'
            + '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>'
            + '</div>';
        container.appendChild(el);
        const toast = new bootstrap.Toast(el, { delay: 3500 });
        el.addEventListener('hidden.bs.toast', () => el.remove());
        toast.show();
    }

    @if (session('info'))
        showToast('info', @json(session('info')));
    @endif
    @if (session('success'))
        showToast('success', @json(session('success')));
    @endif
    @if (session('error'))
        showToast('error', @json(session('error')));
    @endif

    const dt = $('#missingTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: false,
        order: [[0, 'asc']],
        ajax: '{{ route('stock-audits.missing-data', $audit) }}',
        dom: 'rt<"datatables-tail"ip>',
        columns: [
            { data: 'DT_RowIndex',      name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
            { data: 'lot_code_label',   name: 'lot_code',    orderable: false, searchable: false },
            { data: 'product_label',    name: 'product_label', orderable: false, searchable: false },
            { data: 'sku',              name: 'sku',          orderable: false, searchable: false },
            { data: 'carat_weight',     name: 'carat_weight', orderable: false, searchable: false, className: 'text-end' },
            { data: 'category',         name: 'category',     orderable: false, searchable: false },
            { data: 'supplier',         name: 'supplier',     orderable: false, searchable: false },
            { data: 'invoice_number',   name: 'invoice_number', orderable: false, searchable: false },
            { data: 'purchase_date',    name: 'purchase_date', orderable: false, searchable: false },
            { data: 'cost_price',       name: 'cost_price',   orderable: false, searchable: false, className: 'text-end' },
            { data: 'qty',              name: 'qty',          orderable: false, searchable: false, className: 'text-end' },
        ],
        language: {
            info: 'Showing _START_ to _END_ of _TOTAL_ missing items',
            emptyTable: 'Nothing missing — every expected item was scanned.',
            processing: '<div class="spinner-border spinner-border-sm text-primary"></div>',
            paginate: { previous: '<i class="ti ti-chevron-left"></i>', next: '<i class="ti ti-chevron-right"></i>' },
        },
        initComplete: function () {
            $('#missingInfoSlot').append($('#missingTable_info'));
            $('#missingPaginationSlot').append($('.datatables-tail'));
        },
    });

    function postAction(url, btnSelector, modalSelector, onSuccess) {
        const $btn = $(btnSelector);
        const modalEl = document.querySelector(modalSelector);
        const modal = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
        const resetLabel = $btn.html();

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Working…');

        $.ajax({
            url, type: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            success: function (res) {
                if (res.ok) {
                    if (modal) modal.hide();
                    // Success toast comes from the flashed session message after reload/redirect below, not here.
                    if (onSuccess) onSuccess(res);
                } else {
                    showToast('error', res.message || 'Something went wrong.');
                    $btn.prop('disabled', false).html(resetLabel);
                }
            },
            error: function (xhr) {
                showToast('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong.');
                $btn.prop('disabled', false).html(resetLabel);
            },
        });
    }

    $('#confirmCompleteBtn').on('click', function () {
        postAction('{{ $audit->isInProgress() ? route('stock-audits.complete', $audit) : '#' }}',
            '#confirmCompleteBtn', '#completeAuditModal',
            (res) => window.location.href = res.redirect || window.location.href);
    });

    $('#confirmCancelBtn').on('click', function () {
        postAction('{{ $audit->isInProgress() ? route('stock-audits.cancel', $audit) : '#' }}',
            '#confirmCancelBtn', '#cancelAuditModal',
            (res) => window.location.href = res.redirect || window.location.href);
    });

    $('#confirmWriteOffBtn').on('click', function () {
        postAction('{{ route('stock-audits.write-off-missing', $audit) }}',
            '#confirmWriteOffBtn', '#writeOffModal',
            () => window.location.reload());
    });
});
</script>
@endpush
