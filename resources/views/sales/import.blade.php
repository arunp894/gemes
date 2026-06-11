@extends('layout.app')

@section('title', 'Import Sales')

@section('content')
<div class="container-fluid" id="saleImportApp">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Import Sales</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Sales</a></li>
                <li class="breadcrumb-item active">Import</li>
            </ol>
        </div>
    </div>

    {{-- ── Step indicator ───────────────────────────────────────────── --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex align-items-center gap-0">
                <div class="import-step" :class="stepClass(1)">
                    <span class="step-num">1</span>
                    <span class="step-label">Upload File</span>
                </div>
                <div class="import-step-line" :class="step >= 2 ? 'active' : ''"></div>
                <div class="import-step" :class="stepClass(2)">
                    <span class="step-num">2</span>
                    <span class="step-label">Preview &amp; Validate</span>
                </div>
                <div class="import-step-line" :class="step >= 3 ? 'active' : ''"></div>
                <div class="import-step" :class="stepClass(3)">
                    <span class="step-num">3</span>
                    <span class="step-label">Confirm Import</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════════
         STEP 1 – Upload
    ════════════════════════════════════════════════════════════════ --}}
    <div v-if="step === 1" class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0"><i class="ti ti-file-upload me-2 text-primary"></i>Upload Excel / CSV</h5>
                </div>
                <div class="card-body">

                    <div class="alert alert-info border-0 d-flex gap-2 align-items-start mb-4">
                        <i class="ti ti-info-circle fs-lg mt-1 flex-shrink-0"></i>
                        <div class="small">
                            Use the <strong>sample template</strong> to format your file.
                            Each row is one product line. Repeat order-level fields on every line of the same order.
                            <br>The importer groups lines by <code>external_ref</code> (eBay Sales Record #) or by date + customer email.
                        </div>
                    </div>

                    <a href="{{ route('sales.import.template') }}" class="btn btn-outline-secondary btn-sm mb-4">
                        <i class="ti ti-download me-1"></i> Download Sample Template (.xlsx)
                    </a>

                    {{-- Drop zone --}}
                    <div
                        class="import-dropzone"
                        :class="{ 'is-dragover': isDragover, 'has-file': selectedFile }"
                        @dragover.prevent="isDragover = true"
                        @dragleave.prevent="isDragover = false"
                        @drop.prevent="onDrop"
                        @click="$refs.fileInput.click()"
                    >
                        <input
                            type="file"
                            ref="fileInput"
                            accept=".xlsx,.xls,.csv"
                            style="display:none"
                            @change="onFileSelect"
                        >
                        <template v-if="!selectedFile">
                            <i class="ti ti-table-import fs-1 text-muted mb-2 d-block"></i>
                            <p class="mb-1 fw-semibold">Drag &amp; drop your file here</p>
                            <p class="text-muted small mb-0">or click to browse &nbsp;·&nbsp; .xlsx, .xls, .csv &nbsp;·&nbsp; max 5 MB</p>
                        </template>
                        <template v-else>
                            <i class="ti ti-file-spreadsheet fs-1 text-success mb-2 d-block"></i>
                            <p class="mb-1 fw-semibold">@{{ selectedFile.name }}</p>
                            <p class="text-muted small mb-0">@{{ fileSizeLabel }}</p>
                        </template>
                    </div>

                    <div v-if="uploadError" class="alert alert-danger mt-3 small">
                        <i class="ti ti-alert-circle me-1"></i> @{{ uploadError }}
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button
                            type="button"
                            class="btn btn-primary"
                            :disabled="!selectedFile || uploading"
                            @click="uploadPreview"
                        >
                            <span v-if="uploading" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="ti ti-search me-1"></i>
                            @{{ uploading ? 'Analysing…' : 'Preview &amp; Validate' }}
                        </button>
                        <button v-if="selectedFile" type="button" class="btn btn-outline-secondary" @click="reset">
                            <i class="ti ti-x me-1"></i> Clear
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Instructions sidebar --}}
        <div class="col-lg-5">
            <div class="card border-light">
                <div class="card-header border-light">
                    <h6 class="card-title mb-0"><i class="ti ti-list-check me-2"></i>Required columns</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0 small">
                        <thead class="table-light">
                            <tr><th>Column</th><th>Notes</th></tr>
                        </thead>
                        <tbody>
                            <tr><td><code>sale_date</code></td><td>YYYY-MM-DD</td></tr>
                            <tr><td><code>customer_name</code></td><td>Full name</td></tr>
                            <tr><td><code>channel_code</code></td><td>ebay / pos / website</td></tr>
                            <tr><td><code>location_code</code></td><td>e.g. LOC-0001</td></tr>
                            <tr><td><code>sku</code></td><td>Must match Paces product SKU</td></tr>
                            <tr><td><code>qty</code></td><td>≥ 1</td></tr>
                            <tr><td><code>unit_price_inr</code></td><td>Selling price in INR</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════════
         STEP 2 – Preview
    ════════════════════════════════════════════════════════════════ --}}
    <div v-if="step === 2">

        {{-- Summary bar --}}
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="card text-center p-3">
                    <div class="fs-3 fw-semibold text-primary">@{{ preview.summary.total_groups }}</div>
                    <div class="small text-muted">Total Orders</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center p-3">
                    <div class="fs-3 fw-semibold text-success">@{{ preview.summary.clean_groups }}</div>
                    <div class="small text-muted">Ready to Import</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center p-3">
                    <div class="fs-3 fw-semibold text-danger">@{{ preview.summary.error_groups }}</div>
                    <div class="small text-muted">Have Errors</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center p-3">
                    <div class="fs-3 fw-semibold text-secondary">@{{ preview.summary.total_lines }}</div>
                    <div class="small text-muted">Total Lines</div>
                </div>
            </div>
        </div>

        {{-- Error panel --}}
        <div v-if="preview.summary.error_groups > 0" class="alert alert-warning border-warning mb-3">
            <h6 class="mb-2"><i class="ti ti-alert-triangle me-1"></i>@{{ preview.summary.error_groups }} order(s) have errors and will be skipped</h6>
            <ul class="mb-0 small">
                <template v-for="(errs, key) in preview.errors" :key="key">
                    <li v-for="err in errs">@{{ err }}</li>
                </template>
            </ul>
        </div>

        {{-- Preview table --}}
        <div class="card mb-3">
            <div class="card-header border-light d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Order Preview</h5>
                <span class="badge bg-secondary">@{{ groupKeys.length }} orders</span>
            </div>
            <div class="table-responsive">
                <table class="table table-striped dt-responsive align-middle mb-0 small">
                    <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                        <tr class="text-uppercase fs-xxs">
                            <th>#</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Channel</th>
                            <th>Location</th>
                            <th>Ext. Ref</th>
                            <th class="text-center">Lines</th>
                            <th>Products (SKU × qty @ price)</th>
                            <th class="text-end">Shipping</th>
                            <th>Payment</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(key, idx) in groupKeys" :key="key"
                            :class="preview.errors[key] ? 'table-danger' : ''">
                            <td class="text-muted">@{{ idx + 1 }}</td>
                            <td>@{{ preview.groups[key].sale_date }}</td>
                            <td>
                                <div class="fw-semibold">@{{ preview.groups[key].customer_name }}</div>
                                <div class="text-muted small">@{{ preview.groups[key].customer_email }}</div>
                            </td>
                            <td><span class="badge badge-soft-info fs-xxs">@{{ preview.groups[key].channel_code }}</span></td>
                            <td><code>@{{ preview.groups[key].location_code }}</code></td>
                            <td><code class="text-muted">@{{ preview.groups[key].external_ref || '—' }}</code></td>
                            <td class="text-center">@{{ preview.groups[key].line_count }}</td>
                            <td>
                                <div v-for="line in preview.groups[key].lines" class="small">
                                    <code>@{{ line.sku }}</code> × @{{ line.qty }} @ ₹@{{ line.price }}
                                </div>
                            </td>
                            <td class="text-end">₹@{{ preview.groups[key].shipping || 0 }}</td>
                            <td><span class="badge badge-soft-secondary fs-xxs">@{{ preview.groups[key].payment_method }}</span></td>
                            <td class="text-center">
                                <span v-if="preview.errors[key]" class="badge badge-soft-danger fs-xxs">Error</span>
                                <span v-else class="badge badge-soft-success fs-xxs">OK</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary" @click="reset">
                <i class="ti ti-arrow-left me-1"></i> Back
            </button>
            <button
                type="button"
                class="btn btn-success"
                :disabled="preview.summary.clean_groups === 0 || confirming"
                @click="confirmImport"
            >
                <span v-if="confirming" class="spinner-border spinner-border-sm me-1"></span>
                <i v-else class="ti ti-check me-1"></i>
                @{{ confirming ? 'Importing…' : 'Confirm Import (' + preview.summary.clean_groups + ' orders)' }}
            </button>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════════
         STEP 3 – Result
    ════════════════════════════════════════════════════════════════ --}}
    <div v-if="step === 3" class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="ti ti-circle-check fs-1 text-success mb-3 d-block"></i>
                    <h4 class="mb-1">Import Complete</h4>
                    <p class="text-muted mb-4">Here's what happened:</p>

                    <div class="row g-3 text-start mb-4">
                        <div class="col-6">
                            <div class="p-3 rounded bg-success bg-opacity-10 text-success">
                                <div class="fs-4 fw-semibold">@{{ importResult.imported }}</div>
                                <div class="small">Orders imported</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded bg-warning bg-opacity-10 text-warning">
                                <div class="fs-4 fw-semibold">@{{ importResult.duplicate }}</div>
                                <div class="small">Duplicates skipped</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded bg-secondary bg-opacity-10 text-secondary">
                                <div class="fs-4 fw-semibold">@{{ importResult.skipped }}</div>
                                <div class="small">Skipped (errors)</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded bg-danger bg-opacity-10 text-danger">
                                <div class="fs-4 fw-semibold">@{{ importResult.failed }}</div>
                                <div class="small">Failed</div>
                            </div>
                        </div>
                    </div>

                    <div v-if="Object.keys(importResult.errors).length > 0" class="alert alert-danger text-start small mb-4">
                        <strong>Errors during import:</strong>
                        <ul class="mb-0 mt-1">
                            <template v-for="(errs, key) in importResult.errors" :key="key">
                                <li v-for="err in errs">@{{ err }}</li>
                            </template>
                        </ul>
                    </div>

                    <div class="d-flex gap-2 justify-content-center">
                        <a href="{{ route('sales.index') }}" class="btn btn-primary">
                            <i class="ti ti-list me-1"></i> View Sales
                        </a>
                        <button type="button" class="btn btn-outline-secondary" @click="reset">
                            <i class="ti ti-upload me-1"></i> Import Another File
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
.import-step {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px 8px 0;
}
.import-step .step-num {
    width: 28px; height: 28px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 600;
    background: var(--bs-border-color);
    color: #888;
    transition: background .2s, color .2s;
    flex-shrink: 0;
}
.import-step.active .step-num   { background: var(--bs-primary); color: #fff; }
.import-step.done   .step-num   { background: var(--bs-success);  color: #fff; }
.import-step .step-label { font-size: 13px; color: var(--bs-secondary-color); white-space: nowrap; }
.import-step.active .step-label { color: var(--bs-primary); font-weight: 600; }
.import-step.done   .step-label { color: var(--bs-success); }

.import-step-line {
    flex: 1; height: 2px;
    background: var(--bs-border-color);
    transition: background .3s;
    min-width: 40px;
}
.import-step-line.active { background: var(--bs-success); }

.import-dropzone {
    border: 2px dashed var(--bs-border-color);
    border-radius: 12px;
    padding: 48px 24px;
    text-align: center;
    cursor: pointer;
    transition: border-color .2s, background .2s;
}
.import-dropzone:hover, .import-dropzone.is-dragover {
    border-color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), .04);
}
.import-dropzone.has-file {
    border-color: var(--bs-success);
    background: rgba(var(--bs-success-rgb), .04);
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/vue@2.7.16/dist/vue.min.js"></script>
<script>
new Vue({
    el: '#saleImportApp',

    data: {
        step:         1,
        selectedFile: null,
        isDragover:   false,
        uploading:    false,
        confirming:   false,
        uploadError:  null,
        preview:      { groups: {}, errors: {}, summary: {} },
        importResult: {},
    },

    computed: {
        fileSizeLabel() {
            if (!this.selectedFile) return '';
            const kb = this.selectedFile.size / 1024;
            return kb > 1024 ? (kb / 1024).toFixed(1) + ' MB' : Math.round(kb) + ' KB';
        },
        groupKeys() {
            return Object.keys(this.preview.groups || {});
        },
    },

    methods: {
        stepClass(n) {
            if (this.step > n)  return 'import-step done';
            if (this.step === n) return 'import-step active';
            return 'import-step';
        },

        onFileSelect(e) {
            this.setFile(e.target.files[0]);
        },

        onDrop(e) {
            this.isDragover = false;
            const f = e.dataTransfer.files[0];
            if (f) this.setFile(f);
        },

        setFile(file) {
            const allowed = ['xlsx', 'xls', 'csv'];
            const ext = file.name.split('.').pop().toLowerCase();
            if (!allowed.includes(ext)) {
                this.uploadError = 'Only .xlsx, .xls, and .csv files are accepted.';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                this.uploadError = 'File must be under 5 MB.';
                return;
            }
            this.selectedFile = file;
            this.uploadError  = null;
        },

        async uploadPreview() {
            if (!this.selectedFile) return;
            this.uploading   = true;
            this.uploadError = null;

            const fd = new FormData();
            fd.append('file', this.selectedFile);
            fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);

            try {
                const res = await fetch('{{ route("sales.import.preview") }}', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: fd,
                });
                const data = await res.json();

                if (!data.ok) {
                    this.uploadError = data.message || 'Server error.';
                    return;
                }

                this.preview = data.result;
                this.step    = 2;
            } catch (err) {
                this.uploadError = 'Network error — please try again.';
            } finally {
                this.uploading = false;
            }
        },

        async confirmImport() {
            if (!confirm('Confirm import? This will create ' + this.preview.summary.clean_groups + ' sale(s) and deduct stock. This cannot be undone.')) return;

            this.confirming = true;

            try {
                const res = await fetch('{{ route("sales.import.confirm") }}', {
                    method: 'POST',
                    headers: {
                        'Accept':       'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({}),
                });
                const data = await res.json();

                if (!data.ok) {
                    alert(data.message || 'Import failed.');
                    return;
                }

                this.importResult = data.result;
                this.step         = 3;
            } catch (err) {
                alert('Network error — please try again.');
            } finally {
                this.confirming = false;
            }
        },

        reset() {
            this.step         = 1;
            this.selectedFile = null;
            this.uploading    = false;
            this.confirming   = false;
            this.uploadError  = null;
            this.preview      = { groups: {}, errors: {}, summary: {} };
            this.importResult = {};
            if (this.$refs.fileInput) this.$refs.fileInput.value = '';
        },
    },
});
</script>
@endpush
