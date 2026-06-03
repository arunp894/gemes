@extends('layout.app')

@section('title', 'New Purchase')

@section('content')
<div class="container-fluid" id="purchaseFormApp">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">New Purchase</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('purchases.index') }}">Purchases</a></li>
                <li class="breadcrumb-item active">New</li>
            </ol>
        </div>
    </div>

    <form id="purchaseForm" novalidate @submit.prevent="submit(false)" :class="{ 'was-validated': wasValidated }">

        <div class="row g-3">

            {{-- ╔════════════════════════════════════════════════════╗
                                 LEFT  COLUMN
                ╚════════════════════════════════════════════════════╝ --}}
            <div class="col-xl-9">

                {{-- ──────── Header card ──────── --}}
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">

                            <div class="col-md-3">
                                <label class="form-label">Supplier <span class="text-danger">*</span></label>
                                <select class="form-select" v-model.number="form.supplier_id"
                                        :class="{ 'is-invalid': errors.supplier_id }"
                                        @change="onSupplierChange" required>
                                    <option :value="null">— Select supplier —</option>
                                    <option v-for="s in suppliers" :key="s.id" :value="s.id">
                                        @{{ s.company_name || s.name }} (@{{ s.supplier_code }})
                                    </option>
                                </select>
                                <div class="invalid-feedback">@{{ errors.supplier_id }}</div>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Purchase Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" v-model="form.purchase_date"
                                       :class="{ 'is-invalid': errors.purchase_date }"
                                       @change="refreshInvoiceNumber" required>
                                <div class="invalid-feedback">@{{ errors.purchase_date }}</div>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Invoice #</label>
                                <input type="text" class="form-control bg-light" :value="form.invoice_number_preview"
                                       readonly placeholder="auto-generated">
                                <small class="text-muted"><code>PREFIX-YYYYMM-####</code></small>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Tax Type <span class="text-danger">*</span></label>
                                <select class="form-select" v-model="form.tax_type">
                                    <option value="none">No Tax</option>
                                    <option value="cgst_sgst">CGST + SGST</option>
                                    <option value="igst">IGST</option>
                                </select>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- ──────── Barcode / Product picker ──────── --}}
                <div class="card">
                    <div class="card-header border-light d-flex align-items-center gap-2">
                        <i class="ti ti-barcode fs-18 text-primary"></i>
                        <h5 class="card-title mb-0">Scan or Search Product</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 align-items-end">

                            <div class="col-md-5">
                                <label class="form-label">Barcode <small class="text-muted">Scanning a barcode adds the product to the table.</small></label>
                                <input ref="barcodeInput" type="text" class="form-control form-control-lg"
                                       v-model="barcodeInput"
                                       placeholder="Scan or type barcode then Enter"
                                       @keyup.enter.prevent="onBarcodeEnter"
                                       autofocus>
                                
                            </div>

                            <div class="col-md-5">
                                <label class="form-label">Search by name / SKU</label>
                                <div class="position-relative">
                                    <input type="text" class="form-control" v-model="productSearch"
                                           placeholder="e.g. Cigarette or SKU-001"
                                           @input="onSearchInput" @focus="onSearchInput">

                                    <ul v-if="searchResults.length"
                                        class="list-group position-absolute w-100 mt-1 shadow-sm"
                                        style="z-index: 1050; max-height: 280px; overflow-y: auto;">
                                        <li v-for="p in searchResults" :key="p.id"
                                            class="list-group-item list-group-item-action"
                                            @mousedown.prevent="addProduct(p)" style="cursor: pointer;">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <div class="fw-semibold">@{{ p.title }}</div>
                                                    <small class="text-muted">SKU: @{{ p.sku }} · @{{ p.packaging.pack_type }}</small>
                                                </div>
                                                <span class="badge bg-light text-dark align-self-center">
                                                    @{{ packBadge(p.packaging) }}
                                                </span>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <button type="button" class="btn btn-soft-secondary w-100" style="color: white;"
                                        @click="resetForm" :disabled="form.lines.length === 0">
                                    <i class="ti ti-refresh me-1"></i> Clear
                                </button>
                            </div>

                            <div class="col-12" v-if="scannerMessage">
                                <div class="alert mb-0 py-2" :class="scannerAlertClass">
                                    <i :class="scannerIconClass"></i>
                                    @{{ scannerMessage }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ──────── Lines table ──────── --}}
                @include('purchases._partials._line_table')

            </div>{{-- /col-xl-9 --}}

            {{-- ╔════════════════════════════════════════════════════╗
                                  RIGHT COLUMN — SUMMARY
                ╚════════════════════════════════════════════════════╝ --}}
            <div class="col-xl-3">
                @include('purchases._partials._summary_card')
            </div>

        </div>
    </form>
</div>
@endsection

@push('scripts')
@include('purchases._partials._purchase_app_script', [
    'mode'            => 'create',
    'suppliersJson'   => $suppliers->toJson(),
    'racksJson'       => $racks->toJson(),
    'lookupUrl'       => route('purchases.lookup-barcode'),
    'searchUrl'       => route('purchases.search-products'),
    'previewUrl'      => route('purchases.preview-invoice-number'),
    'submitUrl'       => route('purchases.store'),
    'submitMethod'    => 'POST',
    'existingPurchase'=> null,
])
@endpush
