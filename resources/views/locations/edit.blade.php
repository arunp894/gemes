@extends('layout.app')

@section('title', 'Edit Location')

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Edit Location</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('locations.index') }}">Locations</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </div>
    </div>

    <div class="row" id="locationFormApp">
        <div class="col-12">
            <form id="locationForm" novalidate @submit.prevent="submitForm"
                :class="{ 'was-validated': wasValidated }">

                {{-- ─────────────────  Identification  ───────────────── --}}
                <div class="card">
                    <div class="card-header border-light">
                        <h5 class="card-title mb-0">Identification</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="location_code" class="form-label">Location Code</label>
                                <input type="text" class="form-control bg-light"
                                    id="location_code" v-model="form.location_code" readonly>
                                <small class="text-muted">Location code is permanent.</small>
                            </div>

                            <div class="col-md-5">
                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.name }"
                                    id="name" v-model="form.name" maxlength="191" required>
                                <div class="invalid-feedback">@{{ errors.name }}</div>
                            </div>

                            <div class="col-md-4">
                                <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                                <select id="type" class="form-select"
                                    :class="{ 'is-invalid': errors.type }"
                                    v-model="form.type" required>
                                    @foreach ($types as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">@{{ errors.type }}</div>
                            </div>

                            <div class="col-md-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" rows="2" id="description" maxlength="2000"
                                    :class="{ 'is-invalid': errors.description }"
                                    v-model="form.description"></textarea>
                                <div class="invalid-feedback">@{{ errors.description }}</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label d-block">Status <span class="text-danger">*</span></label>
                                <div class="form-check form-switch mt-1">
                                    <input class="form-check-input" type="checkbox" role="switch" id="status"
                                        v-model="form.status">
                                    <label class="form-check-label" for="status">
                                        @{{ form.status ? 'Active' : 'Inactive' }}
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label d-block">Default Location</label>
                                <div class="form-check form-switch mt-1">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_default"
                                        v-model="form.is_default">
                                    <label class="form-check-label" for="is_default">
                                        @{{ form.is_default ? 'This is the default location' : 'Not default' }}
                                    </label>
                                </div>
                                <small class="text-muted">Only one location can be the default at a time.</small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─────────────────  Responsibility  ───────────────── --}}
                <div class="card">
                    <div class="card-header border-light">
                        <h5 class="card-title mb-0">Responsibility</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="manager_id" class="form-label">Manager</label>
                                <select id="manager_id" class="form-select"
                                    :class="{ 'is-invalid': errors.manager_id }"
                                    v-model="form.manager_id">
                                    <option value="">— Unassigned —</option>
                                    @foreach ($managers as $user)
                                        <option value="{{ $user->id }}">
                                            {{ $user->name }}@if ($user->email) ({{ $user->email }})@endif
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">@{{ errors.manager_id }}</div>
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
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.phone }"
                                    id="phone" v-model="form.phone" maxlength="30">
                                <div class="invalid-feedback">@{{ errors.phone }}</div>
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control"
                                    :class="{ 'is-invalid': errors.email }"
                                    id="email" v-model="form.email" maxlength="191">
                                <div class="invalid-feedback">@{{ errors.email }}</div>
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
                            <div class="col-md-6">
                                <label for="address_line1" class="form-label">Address Line 1</label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.address_line1 }"
                                    id="address_line1" v-model="form.address_line1" maxlength="191">
                                <div class="invalid-feedback">@{{ errors.address_line1 }}</div>
                            </div>

                            <div class="col-md-6">
                                <label for="address_line2" class="form-label">Address Line 2</label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.address_line2 }"
                                    id="address_line2" v-model="form.address_line2" maxlength="191">
                                <div class="invalid-feedback">@{{ errors.address_line2 }}</div>
                            </div>

                            <div class="col-md-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.city }"
                                    id="city" v-model="form.city" maxlength="100">
                                <div class="invalid-feedback">@{{ errors.city }}</div>
                            </div>

                            <div class="col-md-3">
                                <label for="state" class="form-label">State</label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.state }"
                                    id="state" v-model="form.state" maxlength="100">
                                <div class="invalid-feedback">@{{ errors.state }}</div>
                            </div>

                            <div class="col-md-3">
                                <label for="zip_code" class="form-label">Zip Code</label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.zip_code }"
                                    id="zip_code" v-model="form.zip_code" maxlength="20">
                                <div class="invalid-feedback">@{{ errors.zip_code }}</div>
                            </div>

                            <div class="col-md-3">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" class="form-control"
                                    :class="{ 'is-invalid': errors.country }"
                                    id="country" v-model="form.country" maxlength="100">
                                <div class="invalid-feedback">@{{ errors.country }}</div>
                            </div>

                            <div class="col-md-6">
                                <label for="latitude" class="form-label">Latitude</label>
                                <input type="number" step="0.0000001" min="-90" max="90" class="form-control"
                                    :class="{ 'is-invalid': errors.latitude }"
                                    id="latitude" v-model="form.latitude">
                                <div class="invalid-feedback">@{{ errors.latitude }}</div>
                            </div>

                            <div class="col-md-6">
                                <label for="longitude" class="form-label">Longitude</label>
                                <input type="number" step="0.0000001" min="-180" max="180" class="form-control"
                                    :class="{ 'is-invalid': errors.longitude }"
                                    id="longitude" v-model="form.longitude">
                                <div class="invalid-feedback">@{{ errors.longitude }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─────────────────  Notes  ───────────────── --}}
                <div class="card">
                    <div class="card-header border-light">
                        <h5 class="card-title mb-0">Notes</h5>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" rows="3" id="notes" maxlength="2000"
                            :class="{ 'is-invalid': errors.notes }"
                            v-model="form.notes"></textarea>
                        <div class="invalid-feedback">@{{ errors.notes }}</div>
                    </div>
                </div>

                <div v-if="serverError" class="alert alert-danger">@{{ serverError }}</div>

                <div class="d-flex gap-2 justify-content-end mb-4">
                    <a href="{{ route('locations.index') }}" class="btn btn-light">Cancel</a>
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
            el: '#locationFormApp',
            data: {
                form: {
                    location_code: @json($location->location_code),
                    name:          @json($location->name),
                    type:          @json($location->type),
                    description:   @json($location->description ?? ''),
                    manager_id:    @json($location->manager_id ?? ''),
                    address_line1: @json($location->address_line1 ?? ''),
                    address_line2: @json($location->address_line2 ?? ''),
                    city:          @json($location->city ?? ''),
                    state:         @json($location->state ?? ''),
                    country:       @json($location->country ?? ''),
                    zip_code:      @json($location->zip_code ?? ''),
                    phone:         @json($location->phone ?? ''),
                    email:         @json($location->email ?? ''),
                    latitude:      @json($location->latitude !== null ? (string) $location->latitude : ''),
                    longitude:     @json($location->longitude !== null ? (string) $location->longitude : ''),
                    is_default:    {{ $location->is_default ? 'true' : 'false' }},
                    status:        {{ $location->status ? 'true' : 'false' }},
                    notes:         @json($location->notes ?? ''),
                },
                errors: {},
                submitting: false,
                wasValidated: false,
                serverError: null,
            },
            methods: {
                validateLocal() {
                    this.errors = {};
                    if (!this.form.name.trim()) this.$set(this.errors, 'name', 'Name is required.');
                    if (!this.form.type)        this.$set(this.errors, 'type', 'Type is required.');
                    if (this.form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) {
                        this.$set(this.errors, 'email', 'Please enter a valid email address.');
                    }
                    if (this.form.latitude !== '' && this.form.latitude !== null) {
                        const lat = Number(this.form.latitude);
                        if (Number.isNaN(lat) || lat < -90 || lat > 90) {
                            this.$set(this.errors, 'latitude', 'Latitude must be between -90 and 90.');
                        }
                    }
                    if (this.form.longitude !== '' && this.form.longitude !== null) {
                        const lng = Number(this.form.longitude);
                        if (Number.isNaN(lng) || lng < -180 || lng > 180) {
                            this.$set(this.errors, 'longitude', 'Longitude must be between -180 and 180.');
                        }
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
                        if (k === 'status' || k === 'is_default') {
                            fd.append(k, v ? 1 : 0);
                        } else if (k === 'location_code') {
                            // immutable on edit — skip
                        } else if (v !== null && v !== undefined) {
                            fd.append(k, v);
                        }
                    });

                    try {
                        const res = await fetch('{{ route('locations.update', $location) }}', {
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
                        window.location.href = data.redirect || '{{ route('locations.index') }}';
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
