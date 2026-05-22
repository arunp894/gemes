@extends('layout.app')

@section('title', 'Add Permission')

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Add Permission</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('permissions.index') }}">Permissions</a></li>
                <li class="breadcrumb-item active">Add</li>
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
                                    id="module" v-model="form.module" @input="autoSlug" list="modulesList"
                                    maxlength="50" required>
                                <datalist id="modulesList">
                                    @foreach ($modules as $m)
                                        <option value="{{ $m }}">
                                    @endforeach
                                </datalist>
                                <div class="invalid-feedback">@{{ errors.module }}</div>
                                <small class="text-muted">e.g. <code>products</code>, <code>barcodes</code>. Lowercase.</small>
                            </div>

                            <div class="col-md-6">
                                <label for="action" class="form-label">Action <span class="text-danger">*</span></label>
                                <input type="text" class="form-control"
                                    id="action" v-model="actionPart" @input="autoSlug"
                                    placeholder="e.g. view, create, edit, delete" maxlength="50">
                                <small class="text-muted">Combined into the slug as <code>module.action</code>.</small>
                            </div>

                            <div class="col-md-6">
                                <label for="name" class="form-label">Display Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.name }"
                                    id="name" v-model="form.name" maxlength="150" required>
                                <div class="invalid-feedback">@{{ errors.name }}</div>
                                <small class="text-muted">Human label shown in the UI.</small>
                            </div>

                            <div class="col-md-6">
                                <label for="slug" class="form-label">Slug <span class="text-danger">*</span></label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.slug }"
                                    id="slug" v-model="form.slug" @input="slugTouched = true"
                                    maxlength="100" required>
                                <div class="invalid-feedback">@{{ errors.slug }}</div>
                                <small class="text-muted">Used in middleware: <code>permission:{slug}</code>.</small>
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
                                Create Permission
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-2"><i class="ti ti-info-circle me-1"></i> Tips</h6>
                    <ul class="text-muted small mb-0 ps-3">
                        <li>Slug convention: <code>module.action</code> (e.g. <code>products.edit</code>).</li>
                        <li>Modules group related permissions in the role-edit UI.</li>
                        <li>After creating a permission, attach it to one or more roles via <a href="{{ route('roles.index') }}">Roles</a>.</li>
                        <li>Renaming a slug breaks any middleware referencing the old value — change carefully.</li>
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
                slugTouched: false,
                actionPart: '',
                form: {
                    name: '',
                    slug: '',
                    module: '',
                    description: '',
                },
                errors: {},
                submitting: false,
                wasValidated: false,
                serverError: null,
            },
            methods: {
                autoSlug() {
                    if (this.slugTouched) return;
                    const m = (this.form.module || '').toLowerCase().trim().replace(/[^a-z0-9\-]/g, '');
                    const a = (this.actionPart || '').toLowerCase().trim().replace(/[^a-z0-9\-]/g, '');
                    if (m && a) this.form.slug = m + '.' + a;
                    else if (m) this.form.slug = m;
                    else this.form.slug = '';
                },
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
                    fd.append('name', this.form.name);
                    fd.append('slug', this.form.slug);
                    fd.append('module', this.form.module);
                    fd.append('description', this.form.description || '');

                    try {
                        const res = await fetch('{{ route('permissions.store') }}', {
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
