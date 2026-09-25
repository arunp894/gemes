@extends('layout.app')

@section('title', 'New eBay Title')

@section('content')
<div class="container-fluid ebay-titles-page ebay-titles-form-page" id="ebayTitleFormApp">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">
                <i class="ti ti-brand-ebay me-2"></i>New eBay Title
            </h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('ebay-titles.index') }}">eBay Title</a></li>
                <li class="breadcrumb-item active">New</li>
            </ol>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-2">eBay Title Details</h4>
                    <p class="text-muted mb-3">Fields marked with <span class="text-danger">*</span> are required.</p>

                    <div v-if="serverError" class="alert alert-danger" role="alert">@{{ serverError }}</div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Stone <span class="text-danger">*</span></label>
                            <select class="form-select" v-model="form.category_id" :class="{ 'is-invalid': errors.category_id }">
                                <option value="">Select a stone…</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">@{{ errors.category_id }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label d-block">Status</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" role="switch" v-model="form.status" id="etStatus">
                                <label class="form-check-label" for="etStatus">
                                    @{{ form.status ? 'Active' : 'Inactive' }}
                                </label>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">eBay Title <span class="text-danger">*</span></label>
                            <textarea class="form-control" rows="2" maxlength="80"
                                v-model="form.title"
                                :class="{ 'is-invalid': errors.title }"
                                placeholder="e.g. Natural Paraiba Tourmaline 2.15ct Pear Cut GIA Certified Loose Gemstone"></textarea>
                            <div class="invalid-feedback">@{{ errors.title }}</div>
                            <small class="text-muted" :class="{ 'text-danger': form.title.length >= 80 }">
                                @{{ form.title.length }}/80 characters — matches eBay's listing title limit.
                            </small>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Display Order</label>
                            <input type="number" min="0" step="1" class="form-control"
                                v-model.number="form.display_order">
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('ebay-titles.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="button" class="btn btn-success" :disabled="submitting" @click="submit">
                            <span v-if="submitting" class="spinner-border spinner-border-sm me-1" role="status"></span>
                            <span v-if="!submitting"><i class="ti ti-device-floppy me-1"></i></span>
                            Save eBay Title
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="header-title">Tips</h5>
                    <ul class="text-muted small mb-0 ps-3">
                        <li>eBay listing titles are capped at 80 characters — this form enforces the same limit.</li>
                        <li>Pick the Stone (category) this title template applies to.</li>
                        <li>Inactive titles are hidden from selection lists but not deleted.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
    .ebay-titles-form-page { padding-top: 20px; padding-bottom: 20px; }
    .ebay-titles-form-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid #e2e8f0;
    }
    .ebay-titles-form-page .page-title-head > * { display: flex; align-items: center; }
    .ebay-titles-form-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        position: relative;
        padding-left: 12px;
    }
    .ebay-titles-form-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0; top: 2px; bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, #1e3a8a, #1d4ed8);
    }
    .ebay-titles-form-page .breadcrumb { font-size: 0.75rem; }
    .ebay-titles-form-page .card { border-radius: 10px; box-shadow: none; border: 1px solid #e2e8f0; }
    .ebay-titles-form-page .card-body { padding: 16px; }
    .ebay-titles-form-page .header-title { font-size: 1rem; font-weight: 700; }
    .ebay-titles-form-page .mb-3, .ebay-titles-form-page .mb-4 { margin-bottom: 12px !important; }
    .ebay-titles-form-page .form-label { margin-bottom: 4px; font-size: 0.8125rem; font-weight: 600; }
    .ebay-titles-form-page .form-control,
    .ebay-titles-form-page .form-select { padding: 0.4rem 0.65rem; font-size: 0.8125rem; }
    .ebay-titles-form-page textarea.form-control { resize: vertical; }
    .ebay-titles-form-page small.text-muted { display: inline-block; margin-top: 3px; font-size: 0.75rem; }
    .ebay-titles-form-page .d-flex.justify-content-end.gap-2 { margin-top: 16px !important; }
    .ebay-titles-form-page .form-check { margin-bottom: 2px; }
    .ebay-titles-form-page ul.ps-3 { padding-left: 1.1rem !important; }
    .ebay-titles-form-page ul.ps-3 li { margin-bottom: 6px; }
    .ebay-titles-form-page ul.ps-3 li:last-child { margin-bottom: 0; }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    new Vue({
        el: '#ebayTitleFormApp',
        data: {
            form: {
                category_id:    '',
                title:          '',
                display_order:  0,
                status:         true,
            },
            errors: {},
            submitting: false,
            serverError: null,
        },
        methods: {
            validate() {
                const e = {};
                if (!this.form.category_id) e.category_id = 'Please select a stone.';
                if (!this.form.title.trim()) e.title = 'eBay Title is required.';
                else if (this.form.title.length > 80) e.title = 'eBay Title cannot exceed 80 characters.';
                this.errors = e;
                return Object.keys(e).length === 0;
            },
            async submit() {
                this.serverError = null;
                if (!this.validate()) return;
                this.submitting = true;
                try {
                    const res = await fetch('{{ route('ebay-titles.store') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            category_id:    this.form.category_id,
                            title:          this.form.title.trim(),
                            display_order:  Number(this.form.display_order) || 0,
                            status:         this.form.status ? 1 : 0,
                        }),
                    });
                    if (res.status === 422) {
                        const data = await res.json();
                        if (data.errors) {
                            const fresh = {};
                            Object.keys(data.errors).forEach(k => { fresh[k] = data.errors[k][0]; });
                            this.errors = fresh;
                        }
                        this.serverError = data.message || 'Validation failed.';
                        this.submitting = false;
                        return;
                    }
                    const data = await res.json();
                    if (data.ok) window.location.href = data.redirect || '{{ route('ebay-titles.index') }}';
                    else { this.serverError = data.message || 'Failed.'; this.submitting = false; }
                } catch (err) {
                    this.serverError = 'Network error. Please try again.';
                    this.submitting = false;
                }
            },
        },
    });
});
</script>
@endpush
