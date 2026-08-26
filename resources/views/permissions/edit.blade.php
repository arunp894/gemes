@extends('layout.app')

@section('title', 'Edit Permission')

@section('content')

<div class="container-fluid permissions-page permissions-form-page">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Edit Permission</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('permissions.index') }}">Permissions</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </div>
    </div>

    <div class="row" id="permFormApp">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-2">Permission Details</h4>
                    <p class="text-muted mb-3">Fields marked with <span class="text-danger">*</span> are required.</p>

                    <form id="permForm" novalidate @submit.prevent="submitForm"
                        :class="{ 'was-validated': wasValidated }">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="module" class="form-label">Module <span class="text-danger">*</span></label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.module }"
                                    id="module" v-model="form.module" list="modulesList"
                                    maxlength="50" required>
                                <datalist id="modulesList">
                                    @foreach ($modules as $m)
                                        <option value="{{ $m }}">
                                    @endforeach
                                </datalist>
                                <div class="invalid-feedback">@{{ errors.module }}</div>
                            </div>

                            <div class="col-md-6">
                                <label for="slug" class="form-label">Slug <span class="text-danger">*</span></label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.slug }"
                                    id="slug" v-model="form.slug" maxlength="100" required>
                                <div class="invalid-feedback">@{{ errors.slug }}</div>
                                <small class="text-warning">
                                    <i class="ti ti-alert-triangle"></i>
                                    Renaming will break middleware that references the old slug.
                                </small>
                            </div>

                            <div class="col-md-12">
                                <label for="name" class="form-label">Display Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.name }"
                                    id="name" v-model="form.name" maxlength="150" required>
                                <div class="invalid-feedback">@{{ errors.name }}</div>
                            </div>

                            <div class="col-md-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" v-model="form.description"
                                    rows="2" maxlength="255"></textarea>
                            </div>
                        </div>

                        <div v-if="serverError" class="alert alert-danger mt-3 mb-0">@{{ serverError }}</div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('permissions.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary" :disabled="submitting">
                                <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                                <span v-if="!submitting"><i class="ti ti-device-floppy me-1"></i></span>
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="header-title">Heads-up</h5>
                    <ul class="text-muted small mb-0 ps-3">
                        <li>This permission is currently attached to {{ $permission->roles_count ?? $permission->roles()->count() }} role(s).</li>
                        <li>Editing the slug does not auto-update <code>permission:</code> middleware in <code>routes/web.php</code> &mdash; search-and-replace those references manually.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('styles')
<style>
    /* Compact spacing for the Add/Edit Permission form — scoped to this page only */
    .permissions-form-page { padding-top: 20px; padding-bottom: 20px; }
    .permissions-form-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid #e2e8f0;
    }
    .permissions-form-page .page-title-head > * { display: flex; align-items: center; }
    .permissions-form-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        position: relative;
        padding-left: 12px;
    }
    .permissions-form-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0; top: 2px; bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, #1e3a8a, #1d4ed8);
    }
    .permissions-form-page .breadcrumb { font-size: 0.75rem; }
    .permissions-form-page .card { border-radius: 10px; box-shadow: none; border: 1px solid #e2e8f0; }
    .permissions-form-page .card-body { padding: 16px; }
    .permissions-form-page .header-title { font-size: 1rem; font-weight: 700; }
    .permissions-form-page .mb-3, .permissions-form-page .mb-4 { margin-bottom: 12px !important; }
    .permissions-form-page .form-label { margin-bottom: 4px; font-size: 0.8125rem; font-weight: 600; }
    .permissions-form-page .form-control, .permissions-form-page .form-select { padding: 0.4rem 0.65rem; font-size: 0.8125rem; }
    .permissions-form-page small.text-muted { display: inline-block; margin-top: 3px; font-size: 0.75rem; }
    .permissions-form-page .d-flex.justify-content-end.gap-2 { margin-top: 16px !important; }
    .permissions-form-page ul.ps-3 { padding-left: 1.1rem !important; }
    .permissions-form-page ul.ps-3 li { margin-bottom: 6px; }
    .permissions-form-page ul.ps-3 li:last-child { margin-bottom: 0; }
</style>
@endpush

@push('scripts')
<script>
    $(function () {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        new Vue({
            el: '#permFormApp',
            data: {
                form: {
                    name: @json($permission->name),
                    slug: @json($permission->slug),
                    module: @json($permission->module),
                    description: @json($permission->description),
                },
                errors: {},
                submitting: false,
                wasValidated: false,
                serverError: null,
            },
            methods: {
                validateLocal() {
                    this.errors = {};
                    if (!this.form.name.trim())   this.$set(this.errors, 'name', 'Display name is required.');
                    if (!this.form.module.trim()) this.$set(this.errors, 'module', 'Module is required.');
                    else if (!/^[a-z0-9\-]+$/.test(this.form.module)) {
                        this.$set(this.errors, 'module', 'Lowercase letters, numbers, dashes only.');
                    }
                    if (!this.form.slug.trim()) this.$set(this.errors, 'slug', 'Slug is required.');
                    else if (!/^[a-z0-9]+(?:[\-\.][a-z0-9]+)*$/.test(this.form.slug)) {
                        this.$set(this.errors, 'slug', 'Use lowercase, e.g. products.edit');
                    }
                    return Object.keys(this.errors).length === 0;
                },
                async submitForm() {
                    this.serverError = null;
                    this.wasValidated = true;
                    if (!this.validateLocal()) return;
                    this.submitting = true;

                    const fd = new FormData();
                    fd.append('_method', 'PUT');
                    fd.append('name', this.form.name);
                    fd.append('slug', this.form.slug);
                    fd.append('module', this.form.module);
                    fd.append('description', this.form.description || '');

                    try {
                        const res = await fetch('{{ route('permissions.update', $permission) }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: fd,
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
                        window.location.href = data.redirect || '{{ route('permissions.index') }}';
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
