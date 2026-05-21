@extends('layout.app')

@section('title', 'Add Role')

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Add Role</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Paces</a></li>
                <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles</a></li>
                <li class="breadcrumb-item active">Add</li>
            </ol>
        </div>
    </div>

    <div class="row" id="roleFormApp">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form id="roleForm" novalidate @submit.prevent="submitForm"
                        :class="{ 'was-validated': wasValidated }">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Role Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.name }"
                                    id="name" v-model="form.name" @input="autoSlug" maxlength="100" required>
                                <div class="invalid-feedback">@{{ errors.name }}</div>
                            </div>

                            <div class="col-md-6">
                                <label for="slug" class="form-label">Slug <span class="text-danger">*</span></label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.slug }"
                                    id="slug" v-model="form.slug" @input="slugTouched = true" maxlength="100" required>
                                <div class="invalid-feedback">@{{ errors.slug }}</div>
                                <small class="text-muted">Lowercase letters, numbers, dashes. Used in code, not editable later.</small>
                            </div>

                            <div class="col-md-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" v-model="form.description"
                                    rows="2" maxlength="255" placeholder="What does this role do?"></textarea>
                            </div>

                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_super"
                                        v-model="form.is_super">
                                    <label class="form-check-label" for="is_super">
                                        <strong>Super Role</strong>
                                        <small class="d-block text-muted">Holders bypass every permission check. Use sparingly.</small>
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-12" v-show="!form.is_super">
                                <label class="form-label">Permissions</label>
                                <div class="border rounded p-3">
                                    @foreach ($groupedPermissions as $module => $perms)
                                        <div class="mb-3">
                                            <div class="d-flex align-items-center mb-2">
                                                <h6 class="text-uppercase mb-0 me-2">{{ $module }}</h6>
                                                <button type="button" class="btn btn-link btn-sm p-0"
                                                    @click="toggleModule('{{ $module }}', {{ $perms->pluck('id')->toJson() }})">
                                                    Toggle all
                                                </button>
                                            </div>
                                            <div class="row g-2 ps-1">
                                                @foreach ($perms as $perm)
                                                    <div class="col-md-4 col-sm-6">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox"
                                                                id="perm_{{ $perm->id }}"
                                                                value="{{ $perm->id }}"
                                                                v-model="form.permission_ids">
                                                            <label class="form-check-label" for="perm_{{ $perm->id }}">
                                                                {{ $perm->name }}
                                                                <small class="d-block text-muted">
                                                                    <code>{{ $perm->slug }}</code>
                                                                </small>
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <small class="text-muted">Selected: @{{ form.permission_ids.length }} permission(s).</small>
                            </div>

                            <div v-if="form.is_super" class="col-md-12">
                                <div class="alert alert-warning mb-0">
                                    <i class="ti ti-shield-lock me-1"></i>
                                    Super roles bypass all permission checks. Individual permission selections are ignored.
                                </div>
                            </div>
                        </div>

                        <div v-if="serverError" class="alert alert-danger mt-3 mb-0">@{{ serverError }}</div>

                        <div class="mt-4 d-flex gap-2 justify-content-end">
                            <a href="{{ route('roles.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary" :disabled="submitting">
                                <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                                Create Role
                            </button>
                        </div>
                    </form>
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
            el: '#roleFormApp',
            data: {
                slugTouched: false,
                form: {
                    name: '',
                    slug: '',
                    description: '',
                    is_super: false,
                    permission_ids: [],
                },
                errors: {},
                submitting: false,
                wasValidated: false,
                serverError: null,
            },
            methods: {
                autoSlug() {
                    if (this.slugTouched) return;
                    this.form.slug = this.form.name
                        .toLowerCase()
                        .replace(/[^a-z0-9\s\-]/g, '')
                        .trim()
                        .replace(/\s+/g, '-');
                },
                toggleModule(module, ids) {
                    const allSelected = ids.every(id => this.form.permission_ids.includes(id));
                    if (allSelected) {
                        this.form.permission_ids = this.form.permission_ids.filter(id => !ids.includes(id));
                    } else {
                        const merged = new Set([...this.form.permission_ids, ...ids]);
                        this.form.permission_ids = Array.from(merged);
                    }
                },
                validateLocal() {
                    this.errors = {};
                    if (!this.form.name.trim()) this.$set(this.errors, 'name', 'Name is required.');
                    if (!this.form.slug.trim()) this.$set(this.errors, 'slug', 'Slug is required.');
                    else if (!/^[a-z0-9\-]+$/.test(this.form.slug)) {
                        this.$set(this.errors, 'slug', 'Lowercase letters, numbers, dashes only.');
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
                    fd.append('description', this.form.description || '');
                    fd.append('is_super', this.form.is_super ? 1 : 0);
                    if (!this.form.is_super) {
                        this.form.permission_ids.forEach(id => fd.append('permission_ids[]', id));
                    }

                    try {
                        const res = await fetch('{{ route('roles.store') }}', {
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
                            Object.keys(fe).forEach((k) => this.$set(this.errors, k.replace(/\.\d+$/, ''), fe[k][0]));
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
                        window.location.href = data.redirect || '{{ route('roles.index') }}';
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
