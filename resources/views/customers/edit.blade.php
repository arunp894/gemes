@extends('layout.app')

@section('title', 'Edit Customer')

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Edit Customer</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </div>
    </div>

    <div class="row" id="customerFormApp">
        <div class="col-12">
            <form id="customerForm" novalidate @submit.prevent="submitForm" :class="{ 'was-validated': wasValidated }">

                <div class="card">
                    <div class="card-header border-light"><h5 class="card-title mb-0">Identification</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="customer_code" class="form-label">Customer Code</label>
                                <input type="text" class="form-control bg-light" id="customer_code" v-model="form.customer_code" readonly>
                                <small class="text-muted">Customer code is permanent.</small>
                            </div>
                            <div class="col-md-4">
                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" :class="{ 'is-invalid': errors.name }"
                                    id="name" v-model="form.name" maxlength="191" required>
                                <div class="invalid-feedback">@{{ errors.name }}</div>
                            </div>
                            <div class="col-md-3">
                                <label for="company_name" class="form-label">Company Name</label>
                                <input type="text" class="form-control" :class="{ 'is-invalid': errors.company_name }"
                                    id="company_name" v-model="form.company_name" maxlength="191">
                                <div class="invalid-feedback">@{{ errors.company_name }}</div>
                            </div>
                            <div class="col-md-2">
                                <label for="customer_type" class="form-label">Type <span class="text-danger">*</span></label>
                                <select id="customer_type" class="form-select" :class="{ 'is-invalid': errors.customer_type }"
                                    v-model="form.customer_type" required>
                                    @foreach ($types as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">@{{ errors.customer_type }}</div>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label d-block">Status <span class="text-danger">*</span></label>
                                <div class="form-check form-switch mt-1">
                                    <input class="form-check-input" type="checkbox" role="switch" id="status" v-model="form.status">
                                    <label class="form-check-label" for="status">@{{ form.status ? 'Active' : 'Inactive' }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-light"><h5 class="card-title mb-0">Contact</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6"><label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" v-model="form.phone" maxlength="30"></div>
                            <div class="col-md-6"><label for="alternate_phone" class="form-label">Alternate Phone</label>
                                <input type="text" class="form-control" id="alternate_phone" v-model="form.alternate_phone" maxlength="30"></div>
                            <div class="col-md-6"><label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" :class="{ 'is-invalid': errors.email }"
                                    id="email" v-model="form.email" maxlength="191">
                                <div class="invalid-feedback">@{{ errors.email }}</div></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-light"><h5 class="card-title mb-0">Tax / KYC</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6"><label for="gst_number" class="form-label">GST Number</label>
                                <input type="text" class="form-control" id="gst_number" v-model="form.gst_number" maxlength="50"></div>
                            <div class="col-md-6"><label for="pan_number" class="form-label">PAN Number</label>
                                <input type="text" class="form-control text-uppercase" id="pan_number" v-model="form.pan_number" maxlength="20"></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-light"><h5 class="card-title mb-0">Address</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6"><label for="address_line1" class="form-label">Address Line 1</label>
                                <input type="text" class="form-control" id="address_line1" v-model="form.address_line1" maxlength="191"></div>
                            <div class="col-md-6"><label for="address_line2" class="form-label">Address Line 2</label>
                                <input type="text" class="form-control" id="address_line2" v-model="form.address_line2" maxlength="191"></div>
                            <div class="col-md-3"><label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" v-model="form.city" maxlength="100"></div>
                            <div class="col-md-3"><label for="state" class="form-label">State</label>
                                <input type="text" class="form-control" id="state" v-model="form.state" maxlength="100"></div>
                            <div class="col-md-3"><label for="zip_code" class="form-label">Zip Code</label>
                                <input type="text" class="form-control" id="zip_code" v-model="form.zip_code" maxlength="20"></div>
                            <div class="col-md-3"><label for="country" class="form-label">Country</label>
                                <input type="text" class="form-control" id="country" v-model="form.country" maxlength="100"></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-light"><h5 class="card-title mb-0">Notes</h5></div>
                    <div class="card-body">
                        <textarea class="form-control" rows="3" id="notes" maxlength="2000" v-model="form.notes"></textarea>
                    </div>
                </div>

                <div v-if="serverError" class="alert alert-danger">@{{ serverError }}</div>

                <div class="d-flex gap-2 justify-content-end mb-4">
                    <a href="{{ route('customers.index') }}" class="btn btn-light">Cancel</a>
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
            el: '#customerFormApp',
            data: {
                form: {
                    customer_code:   @json($customer->customer_code),
                    name:            @json($customer->name),
                    company_name:    @json($customer->company_name ?? ''),
                    customer_type:   @json($customer->customer_type),
                    email:           @json($customer->email ?? ''),
                    phone:           @json($customer->phone ?? ''),
                    alternate_phone: @json($customer->alternate_phone ?? ''),
                    gst_number:      @json($customer->gst_number ?? ''),
                    pan_number:      @json($customer->pan_number ?? ''),
                    address_line1:   @json($customer->address_line1 ?? ''),
                    address_line2:   @json($customer->address_line2 ?? ''),
                    city:            @json($customer->city ?? ''),
                    state:           @json($customer->state ?? ''),
                    country:         @json($customer->country ?? ''),
                    zip_code:        @json($customer->zip_code ?? ''),
                    status:          {{ $customer->status ? 'true' : 'false' }},
                    notes:           @json($customer->notes ?? ''),
                },
                errors: {}, submitting: false, wasValidated: false, serverError: null,
            },
            methods: {
                validateLocal() {
                    this.errors = {};
                    if (!this.form.name.trim()) this.$set(this.errors, 'name', 'Name is required.');
                    if (this.form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) {
                        this.$set(this.errors, 'email', 'Please enter a valid email address.');
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
                        if (k === 'status') fd.append(k, v ? 1 : 0);
                        else if (k === 'customer_code') {/* immutable */}
                        else if (v !== null && v !== undefined) fd.append(k, v);
                    });

                    try {
                        const res = await fetch('{{ route('customers.update', $customer) }}', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            body: fd,
                        });
                        if (res.status === 422) {
                            const data = await res.json();
                            const fe = data.errors || {};
                            Object.keys(fe).forEach((k) => this.$set(this.errors, k.replace(/\.\d+$/, ''), fe[k][0]));
                            this.submitting = false; return;
                        }
                        if (!res.ok) {
                            const data = await res.json().catch(() => ({}));
                            this.serverError = data.message || 'Something went wrong.';
                            this.submitting = false; return;
                        }
                        const data = await res.json();
                        window.location.href = data.redirect || '{{ route('customers.index') }}';
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
