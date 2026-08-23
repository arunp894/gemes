@extends('layout.app')

@section('title', 'New Stock Audit')

@section('content')
<div class="container-fluid">

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
                    count stays accurate to a single point in time. Only one audit can be in progress
                    per location at once.
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
                                <label class="form-label">Audit Date</label>
                                <input type="date" name="audit_date" id="audit_date" class="form-control"
                                    value="{{ now()->toDateString() }}">
                                <div class="invalid-feedback" id="err-audit_date"></div>
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
                        <a href="{{ route('stock-audits.index') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" id="submitBtn" class="btn btn-primary">
                            <i class="ti ti-scan me-1"></i> Start Audit &amp; Begin Scanning
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    const $form      = $('#auditForm');
    const $submitBtn = $('#submitBtn');
    const $formError = $('#formError');
    const resetLabel = '<i class="ti ti-scan me-1"></i> Start Audit &amp; Begin Scanning';

    $form.on('submit', async function (e) {
        e.preventDefault();

        $formError.addClass('d-none').text('');
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').text('');

        $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Starting…');

        const payload = {
            location_id: $('#location_id').val(),
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
