@extends('layout.app')

@section('title', 'Edit Purchase')

@section('content')
<div class="container-fluid" id="purchaseFormApp">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">
                Edit Purchase
                <span class="badge bg-soft-secondary text-secondary ms-2">{{ $purchase->invoice_number }}</span>
            </h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('purchases.index') }}">Purchases</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </div>
    </div>

    <form id="purchaseForm" novalidate @submit.prevent="submit(false)" :class="{ 'was-validated': wasValidated }">
        <div class="row g-3">
            <div class="col-xl-9">

                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Supplier</label>
                                <input type="text" class="form-control bg-light" readonly
                                       value="{{ $purchase->supplier ? ($purchase->supplier->company_name ?: $purchase->supplier->name) : '' }}">
                                <small class="text-muted">Cannot be changed.</small>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Purchase Date</label>
                                <input type="date" class="form-control" v-model="form.purchase_date">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Invoice #</label>
                                <input type="text" class="form-control bg-light" :value="form.invoice_number_preview" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Tax Type</label>
                                <select class="form-select" v-model="form.tax_type">
                                    <option value="none">No Tax</option>
                                    <option value="cgst_sgst">CGST + SGST</option>
                                    <option value="igst">IGST</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header border-light d-flex align-items-center gap-2">
                        <i class="ti ti-barcode fs-18 text-primary"></i>
                        <h5 class="card-title mb-0">Scan or Search Product</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label">Barcode</label>
                                <input ref="barcodeInput" type="text" class="form-control form-control-lg"
                                       v-model="barcodeInput" placeholder="Scan or type barcode"
                                       @keyup.enter.prevent="onBarcodeEnter">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Search by name / SKU</label>
                                <div class="position-relative">
                                    <input type="text" class="form-control" v-model="productSearch"
                                           @input="onSearchInput" @focus="onSearchInput">
                                    <ul v-if="searchResults.length"
                                        class="list-group position-absolute w-100 mt-1 shadow-sm"
                                        style="z-index: 1050; max-height: 280px; overflow-y: auto;">
                                        <li v-for="p in searchResults" :key="p.id"
                                            class="list-group-item list-group-item-action"
                                            @mousedown.prevent="addProduct(p)" style="cursor: pointer;">
                                            <div class="fw-semibold">@{{ p.title }}</div>
                                            <small class="text-muted">SKU: @{{ p.sku }} · @{{ p.packaging.pack_type }}</small>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-soft-secondary w-100" @click="resetForm">
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

                @include('purchases._partials._line_table')

            </div>

            <div class="col-xl-3">
                @include('purchases._partials._summary_card')
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
@include('purchases._partials._purchase_app_script', [
    'mode'             => 'edit',
    'suppliersJson'    => $suppliers->toJson(),
    'racksJson'        => $racks->toJson(),
    'lookupUrl'        => route('purchases.lookup-barcode'),
    'searchUrl'        => route('purchases.search-products'),
    'previewUrl'       => route('purchases.preview-invoice-number'),
    'submitUrl'        => route('purchases.update', $purchase),
    'submitMethod'     => 'PUT',
    'existingPurchase' => $purchase,
])
@endpush
