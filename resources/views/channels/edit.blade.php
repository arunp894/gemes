@extends('layout.app')

@section('title', 'Edit Channel — ' . $channel->name)

@section('content')
<div class="container-fluid channels-page channels-form-page" id="channelFormApp">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">
                <i class="ti ti-broadcast me-2"></i>Edit Channel
            </h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('channels.index') }}">Channels</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">
                        @if ($channel->icon)
                            <i class="{{ $channel->icon }} me-1"></i>
                        @endif
                        {{ $channel->name }}
                    </h5>
                </div>
                <div class="card-body">
                    <div v-if="serverError" class="alert alert-danger" role="alert">@{{ serverError }}</div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control"
                                v-model="form.name"
                                :class="{ 'is-invalid': errors.name }"
                                maxlength="50">
                            <div class="invalid-feedback">@{{ errors.name }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control"
                                v-model="form.code"
                                :class="{ 'is-invalid': errors.code }"
                                maxlength="30">
                            <div class="invalid-feedback">@{{ errors.code }}</div>
                            <small class="text-muted">Lowercase slug. Used internally.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Icon class</label>
                            <input type="text" class="form-control"
                                v-model="form.icon"
                                :class="{ 'is-invalid': errors.icon }"
                                placeholder="e.g. ti ti-brand-ebay">
                            <div class="invalid-feedback">@{{ errors.icon }}</div>
                            <small class="text-muted">
                                Tabler icon.
                                <span v-if="form.icon"><i :class="form.icon"></i> preview</span>
                            </small>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Display Order</label>
                            <input type="number" min="0" step="1" class="form-control"
                                v-model.number="form.display_order">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label d-block">Status</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" role="switch" v-model="form.status" id="chStatus">
                                <label class="form-check-label" for="chStatus">
                                    @{{ form.status ? 'Active' : 'Inactive' }}
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('channels.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="button" class="btn btn-primary" :disabled="submitting" @click="submit">
                            <span v-if="submitting" class="spinner-border spinner-border-sm me-1" role="status"></span>
                            <span v-if="!submitting"><i class="ti ti-device-floppy me-1"></i></span>
                            Save Changes
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
                        <strong>Created:</strong> {{ $channel->created_at?->format('d M Y, h:i A') }}
                    </p>
                    <p class="mb-0 text-muted small">
                        <strong>Last Modified:</strong> {{ $channel->updated_at?->format('d M Y, h:i A') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
    /* Compact spacing for the Add/Edit Channel form — scoped to this page only */
    .channels-form-page { padding-top: 20px; padding-bottom: 20px; }
    .channels-form-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid #e2e8f0;
    }
    .channels-form-page .page-title-head > * { display: flex; align-items: center; }
    .channels-form-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        position: relative;
        padding-left: 12px;
    }
    .channels-form-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0; top: 2px; bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, #1e3a8a, #1d4ed8);
    }
    .channels-form-page .breadcrumb { font-size: 0.75rem; }
    .channels-form-page .card { border-radius: 10px; box-shadow: none; border: 1px solid #e2e8f0; }
    .channels-form-page .card-body { padding: 16px; }
    .channels-form-page .header-title { font-size: 1rem; font-weight: 700; }
    .channels-form-page .mb-3, .channels-form-page .mb-4 { margin-bottom: 12px !important; }
    .channels-form-page .form-label { margin-bottom: 4px; font-size: 0.8125rem; font-weight: 600; }
    .channels-form-page .form-control,
    .channels-form-page .form-select { padding: 0.4rem 0.65rem; font-size: 0.8125rem; }
    .channels-form-page small.text-muted { display: inline-block; margin-top: 3px; font-size: 0.75rem; }
    .channels-form-page .d-flex.justify-content-end.gap-2 { margin-top: 16px !important; }
    .channels-form-page .form-check { margin-bottom: 2px; }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    new Vue({
        el: '#channelFormApp',
        data: {
            form: {
                name:          @json($channel->name),
                code:          @json($channel->code),
                icon:          @json($channel->icon ?? ''),
                display_order: @json($channel->display_order),
                status:        @json((bool) $channel->status),
            },
            errors: {},
            submitting: false,
            serverError: null,
        },
        methods: {
            validate() {
                const e = {};
                if (!this.form.name.trim()) e.name = 'Name is required.';
                if (!this.form.code.trim()) e.code = 'Code is required.';
                this.errors = e;
                return Object.keys(e).length === 0;
            },
            async submit() {
                this.serverError = null;
                if (!this.validate()) return;
                this.submitting = true;
                try {
                    const res = await fetch('{{ route('channels.update', $channel) }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-HTTP-Method-Override': 'PUT',
                        },
                        body: JSON.stringify({
                            _method:       'PUT',
                            name:          this.form.name.trim(),
                            code:          this.form.code.trim(),
                            icon:          this.form.icon.trim() || null,
                            display_order: Number(this.form.display_order) || 0,
                            status:        this.form.status ? 1 : 0,
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
                    if (data.ok) window.location.href = data.redirect || '{{ route('channels.index') }}';
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
