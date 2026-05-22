@extends('layout.app')

@section('title', 'Edit Supplier')

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Edit Supplier</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('suppliers.index') }}">Suppliers</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </div>
    </div>

    <div class="row" id="supplierFormApp">
        <div class="col-12">
            <form id="supplierForm" novalidate @submit.prevent="submitForm"
                :class="{ 'was-validated': wasValidated }">

                {{-- ─────────────────  Identification  ───────────────── --}}
                <div class="card">
                    <div class="card-header border-light">
                        <h5 class="card-title mb-0">Identification</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="supplier_code" class="form-label">Supplier Code</label>
                                <input type="text" class="form-control bg-light"
                                    id="supplier_code" v-model="form.supplier_code" readonly>
                                <small class="text-muted">Supplier code is permanent.</small>
                            </div>

                            <div class="col-md-4">
                                <label for="name" class="form-label">Contact Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.name }"
                                    id="name" v-model="form.name" maxlength="191" required>
                                <div class="invalid-feedback">@{{ errors.name }}</div>
                            </div>

                            <div class="col-md-4">
                                <label for="company_name" class="form-label">Company Name</label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.company_name }"
                                    id="company_name" v-model="form.company_name" maxlength="191">
                                <div class="invalid-feedback">@{{ errors.company_name }}</div>
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
                    </div>
                </div>

                {{-- ─────────────────  Contact  ───────────────── --}}
                <div class="card">
                    <div class="card-header border-light">
                        <h5 class="card-title mb-0">Contact</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control"
                                    :class="{ 'is-invalid': errors.email }"
                                    id="email" v-model="form.email" maxlength="191">
                                <div class="invalid-feedback">@{{ errors.email }}</div>
                            </div>

                            <div class="col-md-6">
                                <label for="website" class="form-label">Website</label>
                                <input type="url" class="form-control"
                                    :class="{ 'is-invalid': errors.website }"
                                    id="website" v-model="form.website" maxlength="191"
                                    placeholder="https://example.com">
                                <div class="invalid-feedback">@{{ errors.website }}</div>
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.phone }"
                                    id="phone" v-model="form.phone" maxlength="30" required>
                                <div class="invalid-feedback">@{{ errors.phone }}</div>
                            </div>

                            <div class="col-md-6">
                                <label for="alternate_phone" class="form-label">Alternate Phone</label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.alternate_phone }"
                                    id="alternate_phone" v-model="form.alternate_phone" maxlength="30">
                                <div class="invalid-feedback">@{{ errors.alternate_phone }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─────────────────  Tax  ───────────────── --}}
                <div class="card">
                    <div class="card-header border-light">
                        <h5 class="card-title mb-0">Tax &amp; Compliance</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="gst_number" class="form-label">GST Number</label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.gst_number }"
                                    id="gst_number" v-model="form.gst_number" maxlength="50">
                                <div class="invalid-feedback">@{{ errors.gst_number }}</div>
                            </div>

                            <div class="col-md-6">
                                <label for="tax_number" class="form-label">Tax Number</label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.tax_number }"
                                    id="tax_number" v-model="form.tax_number" maxlength="50">
                                <div class="invalid-feedback">@{{ errors.tax_number }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─────────────────  Address  ───────────────── --}}
                <div class="card">
                    <div class="card-header border-light">
                        <h5 class="card-title mb-0">Address</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.country }"
                                    id="country" v-model="form.country" maxlength="100">
                                <div class="invalid-feedback">@{{ errors.country }}</div>
                            </div>

                            <div class="col-md-3">
                                <label for="state" class="form-label">State</label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.state }"
                                    id="state" v-model="form.state" maxlength="100">
                                <div class="invalid-feedback">@{{ errors.state }}</div>
                            </div>

                            <div class="col-md-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.city }"
                                    id="city" v-model="form.city" maxlength="100">
                                <div class="invalid-feedback">@{{ errors.city }}</div>
                            </div>

                            <div class="col-md-3">
                                <label for="zip_code" class="form-label">Zip Code</label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.zip_code }"
                                    id="zip_code" v-model="form.zip_code" maxlength="20">
                                <div class="invalid-feedback">@{{ errors.zip_code }}</div>
                            </div>

                            <div class="col-md-12">
                                <label for="address" class="form-label">Street Address</label>
                                <textarea class="form-control" rows="2"
                                    :class="{ 'is-invalid': errors.address }"
                                    id="address" v-model="form.address" maxlength="1000"></textarea>
                                <div class="invalid-feedback">@{{ errors.address }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─────────────────  Financial  ───────────────── --}}
                <div class="card">
                    <div class="card-header border-light">
                        <h5 class="card-title mb-0">Financial</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="opening_balance" class="form-label">Opening Balance</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0" class="form-control"
                                        :class="{ 'is-invalid': errors.opening_balance }"
                                        id="opening_balance" v-model="form.opening_balance">
                                    <div class="invalid-feedback">@{{ errors.opening_balance }}</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="credit_limit" class="form-label">Credit Limit</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" min="0" class="form-control"
                                        :class="{ 'is-invalid': errors.credit_limit }"
                                        id="credit_limit" v-model="form.credit_limit">
                                    <div class="invalid-feedback">@{{ errors.credit_limit }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="serverError" class="alert alert-danger">@{{ serverError }}</div>

                <div class="d-flex gap-2 justify-content-end mb-4">
                    <a href="{{ route('suppliers.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary" :disabled="submitting">
                        <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
    $(function () {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        new Vue({
            el: '#supplierFormApp',
            data: {
                form: {
                    supplier_code:   @json($supplier->supplier_code),
                    name:            @json($supplier->name),
                    company_name:    @json($supplier->company_name ?? ''),
                    email:           @json($supplier->email ?? ''),
                    phone:           @json($supplier->phone),
                    alternate_phone: @json($supplier->alternate_phone ?? ''),
                    gst_number:      @json($supplier->gst_number ?? ''),
                    tax_number:      @json($supplier->tax_number ?? ''),
                    website:         @json($supplier->website ?? ''),
                    country:         @json($supplier->country ?? ''),
                    state:           @json($supplier->state ?? ''),
                    city:            @json($supplier->city ?? ''),
                    zip_code:        @json($supplier->zip_code ?? ''),
                    address:         @json($supplier->address ?? ''),
                    opening_balance: {{ (float) $supplier->opening_balance }},
                    credit_limit:    {{ (float) $supplier->credit_limit }},
                    status:          {{ $supplier->status ? 'true' : 'false' }},
                },
                errors: {},
                submitting: false,
                wasValidated: false,
                serverError: null,
            },
            methods: {
                validateLocal() {
                    this.errors = {};
                    if (!this.form.name.trim())  this.$set(this.errors, 'name',  'Contact name is required.');
                    if (!this.form.phone.trim()) this.$set(this.errors, 'phone', 'Phone is required.');
                    if (this.form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) {
                        this.$set(this.errors, 'email', 'Please enter a valid email address.');
                    }
                    if (this.form.opening_balance !== '' && Number(this.form.opening_balance) < 0) {
                        this.$set(this.errors, 'opening_balance', 'Opening balance cannot be negative.');
                    }
                    if (this.form.credit_limit !== '' && Number(this.form.credit_limit) < 0) {
                        this.$set(this.errors, 'credit_limit', 'Credit limit cannot be negative.');
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
                    Object.keys(this.form).forEach((k) => {
                        const v = this.form[k];
                        if (k === 'status') {
                            fd.append('status', v ? 1 : 0);
                        } else if (k === 'supplier_code') {
                            // immutable on edit — skip
                        } else if (v !== null && v !== undefined) {
                            fd.append(k, v);
                        }
                    });

                    try {
                        const res = await fetch('{{ route('suppliers.update', $supplier) }}', {
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
                        window.location.href = data.redirect || '{{ route('suppliers.index') }}';
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
