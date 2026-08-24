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
            <div class="col-xl-10">

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

                            <div class="col-md-3">
                                <label class="form-label">Location <span class="text-danger">*</span></label>
                                <select class="form-select" v-model.number="form.location_id"
                                        :class="{ 'is-invalid': errors.location_id }"
                                        required>
                                    <option :value="null">&mdash; Select location &mdash;</option>
                                    <option v-for="loc in locations" :key="loc.id" :value="loc.id">
                                        @{{ loc.name }} <span v-if="loc.location_code">&middot; @{{ loc.location_code }}</span>
                                    </option>
                                </select>
                                <div class="invalid-feedback">@{{ errors.location_id }}</div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="alert mb-3 py-2" :class="formAlertClass" v-if="formMessage">
                    <i :class="formIconClass"></i>
                    @{{ formMessage }}
                </div>

                {{-- ──────── Lines table ──────── --}}
                @include('purchases._partials._line_table')

            </div>{{-- /col-xl-9 --}}

            {{-- ╔════════════════════════════════════════════════════╗
                                  RIGHT COLUMN — SUMMARY
                ╚════════════════════════════════════════════════════╝ --}}
            <div class="col-xl-2">
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
    'locationsJson'   => $locations->toJson(),
    'racksJson'       => $racks->toJson(),
    'categoriesJson'  => $categories->toJson(),
    'countriesOfOriginJson' => $countriesOfOrigin->toJson(),
    'previewUrl'      => route('purchases.preview-invoice-number'),
    'lotCodePreviewUrl' => route('purchases.preview-lot-code'),
    'submitUrl'       => route('purchases.store'),
    'submitMethod'    => 'POST',
    'existingPurchase'=> null,
    'currencySymbol'  => $currencySymbol,
])
@endpush
