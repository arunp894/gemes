@extends('layout.app')

@section('title', 'Edit Country of Origin')

@section('content')

<div class="container-fluid country-origins-page country-origins-form-page">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Edit Country of Origin</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('country-origins.index') }}">Countries of Origin</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </div>
    </div>

    <div class="row" id="originFormApp">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-2">Country Details</h4>

                    <div class="mb-3">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control"
                            :class="{ 'is-invalid': errors.name }"
                            id="name" v-model="form.name" maxlength="100" required>
                        <div class="invalid-feedback">@{{ errors.name }}</div>
                    </div>

                    <div class="mb-3">
                        <label for="display_order" class="form-label">Sort Order</label>
                        <input type="number" class="form-control"
                            :class="{ 'is-invalid': errors.display_order }"
                            id="display_order" v-model="form.display_order" min="0" max="65535">
                        <div class="invalid-feedback">@{{ errors.display_order }}</div>
                        <small class="text-muted">Lower numbers appear first.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-block">Status <span class="text-danger">*</span></label>
                        <div class="form-check form-switch mt-1">
                            <input class="form-check-input" type="checkbox" role="switch" id="status"
                                v-model="form.status">
                            <label class="form-check-label" for="status">
                                @{{ form.status ? 'Active' : 'Inactive' }}
                            </label>
                        </div>
                        <small class="text-muted">Inactive countries are hidden from purchase/product forms.</small>
                    </div>

                    <div v-if="serverError" class="alert alert-danger mt-3 mb-0">@{{ serverError }}</div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('country-origins.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="button" class="btn btn-primary" :disabled="submitting" @click="submitForm">
                            <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                            <span v-if="!submitting"><i class="ti ti-device-floppy me-1"></i></span>
                            Update Country
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="header-title">Audit</h5>
                    <p class="mb-1 text-muted small">
                        <strong>Created:</strong> {{ $origin->created_at?->format('d M Y, h:i A') }}
                    </p>
                    <p class="mb-0 text-muted small">
                        <strong>Last Modified:</strong> {{ $origin->updated_at?->format('d M Y, h:i A') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('styles')
<style>
    /* Compact spacing for the Add/Edit Country of Origin form — scoped to this page only */
    .country-origins-form-page { padding-top: 20px; padding-bottom: 20px; }
    .country-origins-form-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid #e2e8f0;
    }
    .country-origins-form-page .page-title-head > * { display: flex; align-items: center; }
    .country-origins-form-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        position: relative;
        padding-left: 12px;
    }
    .country-origins-form-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0; top: 2px; bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, #1e3a8a, #1d4ed8);
    }
    .country-origins-form-page .breadcrumb { font-size: 0.75rem; }
    .country-origins-form-page .card { border-radius: 10px; box-shadow: none; border: 1px solid #e2e8f0; }
    .country-origins-form-page .card-body { padding: 16px; }
    .country-origins-form-page .header-title { font-size: 1rem; font-weight: 700; }
    .country-origins-form-page .mb-3, .country-origins-form-page .mb-4 { margin-bottom: 12px !important; }
    .country-origins-form-page .form-label { margin-bottom: 4px; font-size: 0.8125rem; font-weight: 600; }
    .country-origins-form-page .form-control, .country-origins-form-page .form-select { padding: 0.4rem 0.65rem; font-size: 0.8125rem; }
    .country-origins-form-page small.text-muted { display: inline-block; margin-top: 3px; font-size: 0.75rem; }
    .country-origins-form-page .d-flex.justify-content-end.gap-2 { margin-top: 16px !important; }
    .country-origins-form-page .form-check { margin-bottom: 2px; }
</style>
@endpush

@push('scripts')
<script>
    $(function () {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        new Vue({
            el: '#originFormApp',
            data: {
                form: {
                    name:          @json($origin->name),
                    display_order: @json($origin->display_order),
                    status:        @json((bool) $origin->status),
                },
                errors: {},
                submitting: false,
                serverError: null,
            },
            methods: {
                validateLocal() {
                    this.errors = {};
                    if (!this.form.name.trim()) this.$set(this.errors, 'name', 'Name is required.');
                    return Object.keys(this.errors).length === 0;
                },
                async submitForm() {
                    this.serverError = null;
                    if (!this.validateLocal()) return;
                    this.submitting = true;

                    try {
                        const res = await fetch('{{ route('country-origins.update', $origin) }}', {
                            method: 'PUT',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                name: this.form.name,
                                display_order: this.form.display_order || 0,
                                status: this.form.status ? 1 : 0,
                            }),
                        });

                        if (res.status === 422) {
                            const data = await res.json();
                            const fe = data.errors || {};
                            Object.keys(fe).forEach((k) => this.$set(this.errors, k, fe[k][0]));
                            this.submitting = false;
                            return;
                        }
                        if (!res.ok) {
                            const data = await res.json().catch(() => ({}));
                            this.serverError = data.message || 'Something went wrong.';
                            this.submitting = false;
                            return;
                        }

                        const data = await res.json();
                        window.location.href = data.redirect || '{{ route('country-origins.index') }}';
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
