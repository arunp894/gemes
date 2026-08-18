@extends('layout.app')

@section('title', 'Edit Country of Origin')

@section('content')

<div class="container-fluid">

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
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Country Details</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control"
                                :class="{ 'is-invalid': errors.name }"
                                id="name" v-model="form.name" maxlength="100" required>
                            <div class="invalid-feedback">@{{ errors.name }}</div>
                        </div>

                        <div class="col-md-4">
                            <label for="display_order" class="form-label">Sort Order</label>
                            <input type="number" class="form-control"
                                :class="{ 'is-invalid': errors.display_order }"
                                id="display_order" v-model="form.display_order" min="0" max="65535">
                            <div class="invalid-feedback">@{{ errors.display_order }}</div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label d-block">Status <span class="text-danger">*</span></label>
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input" type="checkbox" role="switch" id="status"
                                    v-model="form.status">
                                <label class="form-check-label" for="status">
                                    @{{ form.status ? 'Active' : 'Inactive' }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <div v-if="serverError" class="alert alert-danger mt-3 mb-0">@{{ serverError }}</div>

                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <a href="{{ route('country-origins.index') }}" class="btn btn-light">Cancel</a>
                        <button type="button" class="btn btn-primary" :disabled="submitting" @click="submitForm">
                            <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                            Save Changes
                        </button>
                    </div>
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
