@extends('layout.app')

@section('title', 'Add Rack')

@section('content')
<div class="container-fluid" id="rackFormApp">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1"><h4 class="page-main-title m-0">Add Rack</h4></div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('racks.index') }}">Racks</a></li>
                <li class="breadcrumb-item active">Add</li>
            </ol>
        </div>
    </div>

    <form id="rackForm" novalidate @submit.prevent="submit" :class="{ 'was-validated': wasValidated }">
        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Code</label>
                        <input type="text" class="form-control text-uppercase" v-model="form.code"
                               :placeholder="@json($suggestedCode)" maxlength="50"
                               :class="{ 'is-invalid': errors.code }">
                        <div class="invalid-feedback">@{{ errors.code }}</div>
                        <small class="text-muted">Leave blank to auto-generate: <code>{{ $suggestedCode }}</code></small>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" v-model="form.name" maxlength="100" required
                               :class="{ 'is-invalid': errors.name }">
                        <div class="invalid-feedback">@{{ errors.name }}</div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" v-model="form.location" maxlength="200"
                               placeholder="e.g. Warehouse A · Aisle 3 · Bin 5">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" rows="3" v-model="form.description"></textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label d-block">Status</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" v-model="form.status">
                            <label class="form-check-label">@{{ form.status ? 'Active' : 'Inactive' }}</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="{{ route('racks.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                    @{{ submitting ? 'Saving…' : 'Save Rack' }}
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
new Vue({
    el: '#rackFormApp',
    data: {
        form: { code: '', name: '', location: '', description: '', status: true },
        errors: {},
        wasValidated: false,
        submitting: false,
    },
    methods: {
        submit() {
            this.wasValidated = true;
            this.submitting   = true;
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            fetch("{{ route('racks.store') }}", {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify(this.form),
            })
            .then(async r => {
                const j = await r.json();
                if (!r.ok) {
                    this.errors = {};
                    if (j.errors) Object.keys(j.errors).forEach(k => { this.errors[k] = j.errors[k][0]; });
                    return;
                }
                window.location.href = j.redirect;
            })
            .finally(() => { this.submitting = false; });
        }
    }
});
</script>
@endpush
@endsection
