@extends('layout.app')

@section('title', 'Edit Permission')

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Edit Permission</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Paces</a></li>
                <li class="breadcrumb-item"><a href="{{ route('permissions.index') }}">Permissions</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </div>
    </div>

    <div class="row" id="permFormApp">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
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

                        <div class="mt-4 d-flex gap-2 justify-content-end">
                            <a href="{{ route('permissions.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary" :disabled="submitting">
                                <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
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
                    <h6 class="mb-2"><i class="ti ti-info-circle me-1"></i> Heads-up</h6>
                    <ul class="text-muted small mb-0 ps-3">
                        <li>This permission is currently attached to {{ $permission->roles_count ?? $permission->roles()->count() }} role(s).</li>
                        <li>Editing the slug does not auto-update <code>permission:</code> middleware in <code>routes/web.php</code> — search-and-replace those references manually.</li>
                    </ul>
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
