@extends('layout.app')

@section('title', 'Edit Rack')

@section('content')

<div class="container-fluid racks-page racks-form-page">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Edit Rack</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('racks.index') }}">Racks</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </div>
    </div>

    <div id="rackFormApp" class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">

                    {{-- Code (read-only) --}}
                    <div class="mb-3">
                        <label for="code" class="form-label">Code</label>
                        <input type="text" class="form-control" id="code" value="{{ $rack->code }}" readonly disabled>
                        <small class="text-muted">
                            <i class="ti ti-lock me-1"></i>Rack code cannot be changed after creation.
                        </small>
                    </div>

                    {{-- Name --}}
                    <div class="mb-3">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name"
                            v-model="form.name" maxlength="100"
                            :class="{ 'is-invalid': errors.name, 'is-valid': wasValidated && !errors.name && form.name }">
                        <div class="invalid-feedback" v-if="errors.name">@{{ errors.name }}</div>
                    </div>

                    {{-- Location --}}
                    <div class="mb-3">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" class="form-control" id="location"
                            v-model="form.location" maxlength="200"
                            placeholder="e.g. Warehouse A · Aisle 3 · Bin 5">
                        <small class="text-muted">Optional physical location hint.</small>
                    </div>

                    {{-- Description --}}
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" v-model="form.description" rows="3"></textarea>
                        <small class="text-muted">Optional.</small>
                    </div>

                    {{-- Status --}}
                    <div class="mb-3">
                        <label class="form-label d-block">Status <span class="text-danger">*</span></label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="statusSwitch"
                                v-model="form.status">
                            <label class="form-check-label" for="statusSwitch">
                                @{{ form.status ? 'Active' : 'Inactive' }}
                            </label>
                        </div>
                        <small class="text-muted">Inactive racks are hidden from stock assignment pickers.</small>
                    </div>

                    {{-- Server error banner --}}
                    <div v-if="serverError" class="alert alert-danger" role="alert">@{{ serverError }}</div>

                    {{-- Actions --}}
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('racks.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="button" class="btn btn-primary" :disabled="submitting" @click="submit">
                            <span v-if="submitting" class="spinner-border spinner-border-sm me-1" role="status"></span>
                            <span v-if="!submitting"><i class="ti ti-device-floppy me-1"></i></span>
                            Update Rack
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="header-title">Audit</h5>
                    <p class="mb-1 text-muted small">
                        <strong>Created:</strong> {{ $rack->created_at?->format('d M Y, h:i A') }}
                    </p>
                    <p class="mb-0 text-muted small">
                        <strong>Last Modified:</strong> {{ $rack->updated_at?->format('d M Y, h:i A') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('styles')
<style>
    /* Compact spacing for the Add/Edit Rack form — scoped to this page only */
    .racks-form-page { padding-top: 20px; padding-bottom: 20px; }
    .racks-form-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid #e2e8f0;
    }
    .racks-form-page .page-title-head > * { display: flex; align-items: center; }
    .racks-form-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        position: relative;
        padding-left: 12px;
    }
    .racks-form-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0; top: 2px; bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, #1e3a8a, #1d4ed8);
    }
    .racks-form-page .breadcrumb { font-size: 0.75rem; }
    .racks-form-page .card { border-radius: 10px; box-shadow: none; border: 1px solid #e2e8f0; }
    .racks-form-page .card-body { padding: 16px; }
    .racks-form-page .header-title { font-size: 1rem; font-weight: 700; }
    .racks-form-page .mb-3 { margin-bottom: 12px !important; }
    .racks-form-page .mb-4 { margin-bottom: 12px !important; }
    .racks-form-page .form-label { margin-bottom: 4px; font-size: 0.8125rem; font-weight: 600; }
    .racks-form-page .form-control,
    .racks-form-page .form-select { padding: 0.4rem 0.65rem; font-size: 0.8125rem; }
    .racks-form-page textarea.form-control { padding: 0.5rem 0.65rem; }
    .racks-form-page small.text-muted { display: inline-block; margin-top: 3px; font-size: 0.75rem; }
    .racks-form-page .d-flex.justify-content-end.gap-2 { margin-top: 16px !important; }
    .racks-form-page .form-check { margin-bottom: 2px; }
</style>
@endpush

@push('scripts')
<script>
new Vue({
    el: '#rackFormApp',
    data: {
        form: {
            name:        @json($rack->name),
            location:    @json($rack->location ?? ''),
            description: @json($rack->description ?? ''),
            status:      {{ $rack->status ? 'true' : 'false' }},
        },
        errors: {},
        wasValidated: false,
        submitting: false,
        serverError: null,
    },
    methods: {
        submit() {
            this.wasValidated = true;
            this.serverError = null;
            if (!this.form.name.trim()) {
                this.$set(this.errors, 'name', 'Name is required.');
                return;
            }
            this.submitting = true;
            this.errors = {};
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            fetch('{{ route('racks.update', $rack) }}', {
                method: 'PUT',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify(this.form),
            })
            .then(async r => {
                const j = await r.json();
                if (!r.ok) {
                    const errs = {};
                    if (j.errors) Object.keys(j.errors).forEach(k => { errs[k] = j.errors[k][0]; });
                    this.errors = errs;
                    this.serverError = j.message || 'Please fix the errors below.';
                    return;
                }
                window.location.href = j.redirect;
            })
            .catch(() => { this.serverError = 'A network error occurred. Please try again.'; })
            .finally(() => { this.submitting = false; });
        },
    },
});
</script>
@endpush
