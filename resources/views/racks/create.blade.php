@extends('layout.app')

@section('title', 'Add Rack')

@section('content')
<div class="container-fluid" id="rackFormApp">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1"><h4 class="page-main-title m-0">Add Rack</h4></div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('racks.index') }}">Racks</a></li>
                <li class="breadcrumb-item active">Add</li>
            </ol>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Code</label>
                    <input type="text" class="form-control text-uppercase" v-model="form.code"
                           placeholder="{{ $suggestedCode }}" maxlength="50"
                           :class="{ 'is-invalid': errors.code, 'is-valid': wasValidated && !errors.code }">
                    <div class="invalid-feedback" v-if="errors.code">@{{ errors.code }}</div>
                    <small class="text-muted">Leave blank to auto-generate: <code>{{ $suggestedCode }}</code></small>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" v-model="form.name" maxlength="100"
                           :class="{ 'is-invalid': errors.name, 'is-valid': wasValidated && !errors.name && form.name }">
                    <div class="invalid-feedback" v-if="errors.name">@{{ errors.name }}</div>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Location</label>
                    <input type="text" class="form-control" v-model="form.location" maxlength="200"
                           placeholder="e.g. Warehouse A · Aisle 3 · Bin 5">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" rows="3" v-model="form.description"></textarea>
                </div>
                <div class="col-md-12">
                    <label class="form-label d-block">Status</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" v-model="form.status" id="statusSwitch">
                        <label class="form-check-label" for="statusSwitch">@{{ form.status ? 'Active' : 'Inactive' }}</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer text-end">
            <a href="{{ route('racks.index') }}" class="btn btn-light me-1">Cancel</a>
            <button type="button" class="btn btn-primary" :disabled="submitting" @click="submit">
                <span v-if="submitting"><span class="spinner-border spinner-border-sm me-1"></span>Saving…</span>
                <span v-else>Save Rack</span>
            </button>
        </div>
    </div>

    <div v-if="toast.show" class="position-fixed bottom-0 end-0 p-3" style="z-index:9999">
        <div class="toast show align-items-center" :class="'text-bg-' + toast.type" role="alert">
            <div class="d-flex">
                <div class="toast-body">@{{ toast.message }}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" @click="toast.show=false"></button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
new Vue({
    el: '#rackFormApp',
    data: {
        form: { code: '', name: '', location: '', description: '', status: true },
        errors: {},
        wasValidated: false,
        submitting: false,
        toast: { show: false, message: '', type: 'danger' },
    },
    methods: {
        submit() {
            this.wasValidated = true;
            if (!this.form.name.trim()) {
                this.$set(this.errors, 'name', 'Name is required.');
                return;
            }
            this.submitting = true;
            this.errors = {};
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            fetch('{{ route('racks.store') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify(this.form),
            })
            .then(async r => {
                const j = await r.json();
                if (!r.ok) {
                    const errs = {};
                    if (j.errors) Object.keys(j.errors).forEach(k => { errs[k] = j.errors[k][0]; });
                    this.errors = errs;
                    this.showToast(j.message || 'Please fix the errors below.', 'danger');
                    return;
                }
                window.location.href = j.redirect;
            })
            .catch(() => this.showToast('A network error occurred. Please try again.', 'danger'))
            .finally(() => { this.submitting = false; });
        },
        showToast(message, type) {
            this.toast = { show: true, message, type };
            setTimeout(() => { this.toast.show = false; }, 4000);
        },
    },
});
</script>
@endpush
@endsection
