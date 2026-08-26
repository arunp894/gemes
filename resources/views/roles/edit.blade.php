@extends('layout.app')

@section('title', 'Edit Role')

@section('content')

<div class="container-fluid roles-page roles-form-page">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Edit Role</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </div>
    </div>

    <div class="row" id="roleFormApp">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-2">Edit "{{ $role->name }}"</h4>
                    <p class="text-muted mb-3">Fields marked with <span class="text-danger">*</span> are required.</p>

                    <form id="roleForm" novalidate @submit.prevent="submitForm"
                        :class="{ 'was-validated': wasValidated }">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Role Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.name }"
                                    id="name" v-model="form.name" maxlength="100" required>
                                <div class="invalid-feedback">@{{ errors.name }}</div>
                            </div>

                            <div class="col-md-6">
                                <label for="slug" class="form-label">Slug</label>
                                <input type="text" class="form-control" id="slug"
                                    v-model="form.slug" readonly>
                                <small class="text-muted">Slug is immutable after creation.</small>
                            </div>

                            <div class="col-md-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" v-model="form.description"
                                    rows="2" maxlength="255"></textarea>
                            </div>

                            <div class="col-md-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_super"
                                        v-model="form.is_super">
                                    <label class="form-check-label" for="is_super">
                                        <strong>Super Role</strong>
                                        <small class="d-block text-muted">Holders bypass every permission check.</small>
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
                                    Super roles bypass all permission checks. Individual permission selections are ignored at runtime.
                                </div>
                            </div>
                        </div>

                        <div v-if="serverError" class="alert alert-danger mt-3 mb-0">@{{ serverError }}</div>

                        <div class="mt-4 d-flex gap-2 justify-content-end">
                            <a href="{{ route('roles.index') }}" class="btn btn-secondary">Cancel</a>
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
    </div>

</div>

@endsection

@push('styles')
<style>
    /* Compact spacing for the Add/Edit Role form — scoped to this page only */
    .roles-form-page { padding-top: 20px; padding-bottom: 20px; }
    .roles-form-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid #e2e8f0;
    }
    .roles-form-page .page-title-head > * { display: flex; align-items: center; }
    .roles-form-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        position: relative;
        padding-left: 12px;
    }
    .roles-form-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0; top: 2px; bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, #1e3a8a, #1d4ed8);
    }
    .roles-form-page .breadcrumb { font-size: 0.75rem; }
    .roles-form-page .card { border-radius: 10px; box-shadow: none; border: 1px solid #e2e8f0; }
    .roles-form-page .card-body { padding: 16px; }
    .roles-form-page .header-title { font-size: 1rem; font-weight: 700; }
    .roles-form-page .mb-3, .roles-form-page .mb-4 { margin-bottom: 12px !important; }
    .roles-form-page .form-label { margin-bottom: 4px; font-size: 0.8125rem; font-weight: 600; }
    .roles-form-page .form-control, .roles-form-page .form-select { padding: 0.4rem 0.65rem; font-size: 0.8125rem; }
    .roles-form-page small.text-muted { display: inline-block; margin-top: 3px; font-size: 0.75rem; }
    .roles-form-page .d-flex.justify-content-end.gap-2,
    .roles-form-page .mt-4.d-flex.gap-2.justify-content-end { margin-top: 16px !important; }
</style>
@endpush

@push('scripts')
<script>
    $(function () {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        const assigned  = @json($assignedPermissionIds);

        new Vue({
            el: '#roleFormApp',
            data: {
                form: {
                    name: @json($role->name),
                    slug: @json($role->slug),
                    description: @json($role->description),
                    is_super: {{ $role->is_super ? 'true' : 'false' }},
                    permission_ids: assigned.map(Number),
                },
                errors: {},
                submitting: false,
                wasValidated: false,
                serverError: null,
            },
            methods: {
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
                    fd.append('description', this.form.description || '');
                    fd.append('is_super', this.form.is_super ? 1 : 0);
                    if (!this.form.is_super) {
                        this.form.permission_ids.forEach(id => fd.append('permission_ids[]', id));
                    }

                    try {
                        const res = await fetch('{{ route('roles.update', $role) }}', {
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
