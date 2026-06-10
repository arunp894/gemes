@extends('layout.app')

@section('title', 'New Channel')

@section('content')
<div class="container-fluid" id="channelFormApp">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">
                <i class="ti ti-broadcast text-primary me-2"></i>New Channel
            </h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('channels.index') }}">Channels</a></li>
                <li class="breadcrumb-item active">New</li>
            </ol>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Channel Details</h5>
                </div>
                <div class="card-body">
                    <div v-if="serverError" class="alert alert-danger">@{{ serverError }}</div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control"
                                v-model="form.name"
                                :class="{ 'is-invalid': errors.name }"
                                placeholder="e.g. eBay, Website, POS"
                                @input="autoCode" maxlength="50">
                            <div class="invalid-feedback">@{{ errors.name }}</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control"
                                v-model="form.code"
                                :class="{ 'is-invalid': errors.code }"
                                placeholder="e.g. ebay, website, pos"
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
                                Tabler icon class.
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
                                <input class="form-check-input" type="checkbox" v-model="form.status" id="chStatus">
                                <label class="form-check-label" for="chStatus">
                                    @{{ form.status ? 'Active' : 'Inactive' }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button type="button" class="btn btn-primary" :disabled="submitting" @click="submit">
                        <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="ti ti-device-floppy me-1"></i> Save Channel
                    </button>
                    <a href="{{ route('channels.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    new Vue({
        el: '#channelFormApp',
        data: {
            form: {
                name:          '',
                code:          '',
                icon:          '',
                display_order: 0,
                status:        true,
            },
            errors: {},
            submitting: false,
            serverError: null,
        },
        methods: {
            autoCode() {
                if (!this.form.code || this._codeWasAutoFilled) {
                    this.form.code = this.form.name
                        .toLowerCase()
                        .replace(/[^a-z0-9]+/g, '_')
                        .replace(/^_+|_+$/g, '');
                    this._codeWasAutoFilled = true;
                }
            },
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
                    const res = await fetch('{{ route('channels.store') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
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
