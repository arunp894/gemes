@extends('layout.app')

@section('title', 'Add User')

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Add User</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Paces</a></li>
                <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                <li class="breadcrumb-item active">Add</li>
            </ol>
        </div>
    </div>

    <div class="row" id="userFormApp">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form id="userForm" novalidate @submit.prevent="submitForm"
                        :class="{ 'was-validated': wasValidated }">

                        <div class="row g-3">
                            {{-- Name --}}
                            <div class="col-md-6">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.name }"
                                    id="name" v-model="form.name" maxlength="150" required>
                                <div class="invalid-feedback">@{{ errors.name }}</div>
                            </div>

                            {{-- Email --}}
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control"
                                    :class="{ 'is-invalid': errors.email }"
                                    id="email" v-model="form.email" maxlength="191" required>
                                <div class="invalid-feedback">@{{ errors.email }}</div>
                            </div>

                            {{-- Password --}}
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control"
                                    :class="{ 'is-invalid': errors.password }"
                                    id="password" v-model="form.password" required>
                                <div class="invalid-feedback">@{{ errors.password }}</div>
                                <small class="text-muted">Minimum 8 characters.</small>
                            </div>

                            {{-- Password confirmation --}}
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control"
                                    :class="{ 'is-invalid': errors.password_confirmation }"
                                    id="password_confirmation" v-model="form.password_confirmation" required>
                                <div class="invalid-feedback">@{{ errors.password_confirmation }}</div>
                            </div>

                            {{-- Status --}}
                            <div class="col-md-12">
                                <label class="form-label d-block">Status <span class="text-danger">*</span></label>
                                <div class="form-check form-switch mt-1">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_active"
                                        v-model="form.is_active">
                                    <label class="form-check-label" for="is_active">
                                        @{{ form.is_active ? 'Active' : 'Inactive' }}
                                    </label>
                                </div>
                            </div>

                            {{-- Roles --}}
                            <div class="col-md-12">
                                <label class="form-label">Assign Roles <span class="text-danger">*</span></label>
                                <div class="border rounded p-3"
                                    :class="{ 'border-danger': errors.role_ids }">
                                    <div v-if="!roles.length" class="text-muted small">
                                        No roles available. <a href="{{ route('roles.create') }}">Create one first</a>.
                                    </div>
                                    <div class="row g-2">
                                        <div v-for="role in roles" :key="role.id" class="col-md-4 col-sm-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    :id="'role_' + role.id"
                                                    :value="role.id"
                                                    v-model="form.role_ids">
                                                <label class="form-check-label" :for="'role_' + role.id">
                                                    @{{ role.name }}
                                                    <span v-if="role.is_super" class="badge badge-soft-danger fs-xxs ms-1">Super</span>
                                                    <small class="d-block text-muted" v-if="role.description">@{{ role.description }}</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="errors.role_ids" class="text-danger small mt-1">@{{ errors.role_ids }}</div>
                                <small class="text-muted">A user may hold multiple roles; their effective permissions are the union of all assigned roles.</small>
                            </div>
                        </div>

                        <div v-if="serverError" class="alert alert-danger mt-3 mb-0">@{{ serverError }}</div>

                        <div class="mt-4 d-flex gap-2 justify-content-end">
                            <a href="{{ route('users.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary" :disabled="submitting">
                                <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                                Create User
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
        const rolesData = @json($roles);

        new Vue({
            el: '#userFormApp',
            data: {
                roles: rolesData,
                form: {
                    name: '',
                    email: '',
                    password: '',
                    password_confirmation: '',
                    is_active: true,
                    role_ids: [],
                },
                errors: {},
                submitting: false,
                wasValidated: false,
                serverError: null,
            },
            methods: {
                validateLocal() {
                    this.errors = {};
                    if (!this.form.name.trim())  this.$set(this.errors, 'name', 'Full name is required.');
                    if (!this.form.email.trim()) this.$set(this.errors, 'email', 'Email is required.');
                    if (!this.form.password)     this.$set(this.errors, 'password', 'Password is required.');
                    else if (this.form.password.length < 8) this.$set(this.errors, 'password', 'Minimum 8 characters.');
                    if (this.form.password !== this.form.password_confirmation) {
                        this.$set(this.errors, 'password_confirmation', 'Passwords do not match.');
                    }
                    if (!this.form.role_ids.length) {
                        this.$set(this.errors, 'role_ids', 'Select at least one role.');
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
                    fd.append('email', this.form.email);
                    fd.append('password', this.form.password);
                    fd.append('password_confirmation', this.form.password_confirmation);
                    fd.append('is_active', this.form.is_active ? 1 : 0);
                    this.form.role_ids.forEach(id => fd.append('role_ids[]', id));

                    try {
                        const res = await fetch('{{ route('users.store') }}', {
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
                        window.location.href = data.redirect || '{{ route('users.index') }}';
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
