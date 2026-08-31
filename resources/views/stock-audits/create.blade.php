@extends('layout.app')

@section('title', 'New Stock Audit')

@section('content')
<div class="container-fluid stock-audits-page stock-audits-form-page">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">
                <i class="ti ti-clipboard-list text-primary me-2"></i>New Stock Audit
            </h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('stock-audits.index') }}">Stock Audits</a></li>
                <li class="breadcrumb-item active">New</li>
            </ol>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-xl-7">

            <div class="alert alert-info d-flex gap-2 align-items-start">
                <i class="ti ti-info-circle fs-18 mt-1"></i>
                <div>
                    Starting an audit takes a snapshot of everything the system currently shows as
                    on-hand at the chosen location — that's the list you'll be scanning against.
                    Sales or transfers made <em>after</em> you start won't change that list, so the
                    count stays accurate to a single point in time. You can optionally narrow it to
                    one stone type below; otherwise it covers everything at the location. Only one
                    audit covering the same stone (or the whole location) can be in progress at a
                    location at once.
                </div>
            </div>

            <div id="formError" class="alert alert-danger d-none"></div>

            <form id="auditForm" novalidate>
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Location <span class="text-danger">*</span></label>
                                <select id="location_id" name="location_id" class="form-select" required>
                                    <option value="">— Select a location —</option>
                                    @foreach ($locations as $loc)
                                        <option value="{{ $loc->id }}">{{ $loc->name }} ({{ $loc->location_code }})</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="err-location_id"></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Stone</label>
                                <select id="category_id" name="category_id" class="form-select">
                                    <option value="">All Stones</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="err-category_id"></div>
                                <small class="text-muted">Optional - narrows the count to one stone type at this location.</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Audit Date</label>
                                <input type="date" name="audit_date" id="audit_date" class="form-control"
                                    value="{{ now()->toDateString() }}">
                                <div class="invalid-feedback" id="err-audit_date"></div>
                            </div>

                            <div class="col-12" id="previewCountWrap" style="display: none;">
                                <div class="d-flex align-items-center gap-3 flex-wrap p-3 rounded" style="background: #f1f5f9;">
                                    <span class="text-muted small flex-shrink-0">
                                        <i class="ti ti-scan me-1"></i>This audit will start with:
                                    </span>
                                    <span id="previewLoading" class="text-muted small" style="display: none;">
                                        <span class="spinner-border spinner-border-sm me-1"></span>Calculating…
                                    </span>
                                    <div id="previewCounts" class="d-flex gap-4 flex-wrap">
                                        <div>
                                            <span class="fw-bold fs-16" id="previewPieces">0</span>
                                            <span class="text-muted small">Pieces</span>
                                        </div>
                                        <div>
                                            <span class="fw-bold fs-16" id="previewCarat">0</span>
                                            <span class="text-muted small">Carat</span>
                                        </div>
                                        <div>
                                            <span class="fw-bold fs-16" id="previewProducts">0</span>
                                            <span class="text-muted small">Products</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Note</label>
                                <textarea name="note" id="note" rows="3" class="form-control"
                                    placeholder="Optional — reason for this count, staff involved, etc."></textarea>
                                <div class="invalid-feedback" id="err-note"></div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer border-0 d-flex justify-content-end gap-2">
                        <a href="{{ route('stock-audits.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" id="submitBtn" class="btn btn-success">
                            <i class="ti ti-scan me-1"></i> Start Audit &amp; Begin Scanning
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
    /* Compact spacing for the New Stock Audit form — scoped to this page only */
    .stock-audits-form-page { padding-top: 20px; padding-bottom: 20px; }
    .stock-audits-form-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid #e2e8f0;
    }
    .stock-audits-form-page .page-title-head > * { display: flex; align-items: center; }
    .stock-audits-form-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        position: relative;
        padding-left: 12px;
    }
    .stock-audits-form-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0; top: 2px; bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, #1e3a8a, #1d4ed8);
    }
    .stock-audits-form-page .breadcrumb { font-size: 0.75rem; }
    .stock-audits-form-page .card { border-radius: 10px; box-shadow: none; border: 1px solid #e2e8f0; }
    .stock-audits-form-page .card-body { padding: 16px; }
    .stock-audits-form-page .header-title { font-size: 1rem; font-weight: 700; }
    .stock-audits-form-page .mb-3, .stock-audits-form-page .mb-4 { margin-bottom: 12px !important; }
    .stock-audits-form-page .form-label { margin-bottom: 4px; font-size: 0.8125rem; font-weight: 600; }
    .stock-audits-form-page .form-control,
    .stock-audits-form-page .form-select { padding: 0.4rem 0.65rem; font-size: 0.8125rem; }
    .stock-audits-form-page small.text-muted { display: inline-block; margin-top: 3px; font-size: 0.75rem; }
    .stock-audits-form-page .d-flex.justify-content-end.gap-2 { margin-top: 16px !important; }
    .stock-audits-form-page .card-footer.d-flex.justify-content-end.gap-2 { margin-top: 0 !important; }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    const $form      = $('#auditForm');
    const $submitBtn = $('#submitBtn');
    const $formError = $('#formError');
    const resetLabel = '<i class="ti ti-scan me-1"></i> Start Audit &amp; Begin Scanning';

    // ── Live pieces / carat preview — updates as soon as a location (and
    // optionally a stone) is picked, before the audit is actually started,
    // via the same on-hand query start() itself snapshots from. ─────────
    let previewTimer;
    function refreshPreview() {
        clearTimeout(previewTimer);
        const locationId = $('#location_id').val();

        if (!locationId) {
            $('#previewCountWrap').hide();
            return;
        }

        $('#previewCountWrap').show();
        $('#previewLoading').show();
        $('#previewCounts').css('opacity', 0.4);

        previewTimer = setTimeout(() => {
            const params = new URLSearchParams({ location_id: locationId });
            const categoryId = $('#category_id').val();
            if (categoryId) params.set('category_id', categoryId);

            fetch(`{{ route('stock-audits.preview-count') }}?${params.toString()}`, { headers: { 'Accept': 'application/json' } })
                .then((r) => r.json())
                .then((data) => {
                    if (!data.ok) return;
                    $('#previewPieces').text(data.pieces.toLocaleString());
                    $('#previewCarat').text((Math.round(data.carat * 1000) / 1000).toString().replace(/\.?0+$/, '') || '0');
                    $('#previewProducts').text(data.products.toLocaleString());
                })
                .catch(() => {})
                .finally(() => {
                    $('#previewLoading').hide();
                    $('#previewCounts').css('opacity', 1);
                });
        }, 250);
    }

    $('#location_id, #category_id').on('change', refreshPreview);

    $form.on('submit', async function (e) {
        e.preventDefault();

        $formError.addClass('d-none').text('');
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').text('');

        $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Starting…');

        const payload = {
            location_id: $('#location_id').val(),
            category_id: $('#category_id').val() || null,
            audit_date:  $('#audit_date').val(),
            note:        $('#note').val(),
        };

        try {
            const res = await fetch('{{ route('stock-audits.store') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });

            const data = await res.json();

            if (res.status === 422) {
                if (data.errors) {
                    Object.keys(data.errors).forEach((key) => {
                        $('#' + key).addClass('is-invalid');
                        $('#err-' + key).text(data.errors[key][0]);
                    });
                }
                $formError.removeClass('d-none').text(data.message || 'Please fix the highlighted fields.');
                $submitBtn.prop('disabled', false).html(resetLabel);
                return;
            }

            if (!data.ok) {
                $formError.removeClass('d-none').text(data.message || 'Something went wrong.');
                $submitBtn.prop('disabled', false).html(resetLabel);
                return;
            }

            window.location.href = data.redirect;
        } catch (err) {
            $formError.removeClass('d-none').text('Network error. Please try again.');
            $submitBtn.prop('disabled', false).html(resetLabel);
        }
    });
});
</script>
@endpush
