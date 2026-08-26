@extends('layout.app')

@section('title', 'New Purchase')

@section('content')
<div class="container-fluid purchases-page purchases-form-page" id="purchaseFormApp">

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

    <div class="toast-container position-fixed top-0 end-0 p-3" id="purchaseToastContainer" style="z-index: 1080;"></div>

    <form id="purchaseForm" novalidate @submit.prevent="submit(false)" :class="{ 'was-validated': wasValidated }">

        <div class="row g-3">

            {{-- ╔════════════════════════════════════════════════════╗
                                 LEFT  COLUMN
                ╚════════════════════════════════════════════════════╝ --}}
            <div class="col-xl-10">

                {{-- ──────── Header card ──────── --}}
                <div class="card mb-3">
                    <div class="card-body py-2">
                        <div class="row g-2">

                            <div class="col-md-3">
                                <label class="form-label"><i class="ti ti-truck-delivery me-1 text-muted"></i>Supplier <span class="text-danger">*</span></label>
                                <select v-select2 data-placeholder="— Select supplier —" class="form-select" v-model.number="form.supplier_id"
                                        :class="{ 'is-invalid': errors.supplier_id }"
                                        @change="onSupplierChange" required>
                                    <option :value="null">— Select supplier —</option>
                                    <option v-for="s in suppliers" :key="s.id" :value="s.id">
                                        @{{ s.company_name || s.name }}
                                    </option>
                                </select>
                                <div class="invalid-feedback">@{{ errors.supplier_id }}</div>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label"><i class="ti ti-calendar me-1 text-muted"></i>Purchase Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" v-model="form.purchase_date"
                                       :class="{ 'is-invalid': errors.purchase_date }"
                                       @change="refreshInvoiceNumber" required>
                                <div class="invalid-feedback">@{{ errors.purchase_date }}</div>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label"><i class="ti ti-receipt me-1 text-muted"></i>Invoice #</label>
                                <input type="text" class="form-control bg-light" :value="form.invoice_number_preview"
                                       readonly placeholder="auto-generated">
                                <small class="text-muted"><code style="font-size: 0.7rem;">PREFIX-YYYYMM-####</code></small>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label"><i class="ti ti-percentage me-1 text-muted"></i>Tax Type <span class="text-danger">*</span></label>
                                <select class="form-select" v-model="form.tax_type">
                                    <option value="none">No Tax</option>
                                    <option value="cgst_sgst">CGST + SGST</option>
                                    <option value="igst">IGST</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label"><i class="ti ti-map-pin me-1 text-muted"></i>Location <span class="text-danger">*</span></label>
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

@push('styles')
<style>
    /* Full-width layout for this page only: hides the sidebar and reclaims
       its space so the (very wide) purchase line-item table has more room.
       Scoped by virtue of only being pushed to the 'styles' stack when this
       specific view renders — layout/app.blade.php and sidebar.blade.php
       are never touched, so every other page keeps its normal sidebar. */
    .sidenav-menu { display: none !important; }
    .content-page { margin-inline-start: 0 !important; -webkit-margin-start: 0 !important; }
    .sidenav-toggle-button { display: none !important; }
    .app-topbar { display: none !important; }

    .purchases-form-page { padding-top: 10px; padding-bottom: 20px; }
    .purchases-form-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid #e2e8f0;
    }
    .purchases-form-page .page-title-head > * { display: flex; align-items: center; }
    .purchases-form-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        position: relative;
        padding-left: 12px;
    }
    .purchases-form-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0; top: 2px; bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, #1e3a8a, #1d4ed8);
    }
    .purchases-form-page .breadcrumb { font-size: 0.75rem; }
    .purchases-form-page .card { border-radius: 10px; box-shadow: none; border: 1px solid #e2e8f0; }
    .purchases-form-page .card-body { padding: 16px; }
    .purchases-form-page .header-title, .purchases-form-page .card-title { font-size: 1rem; font-weight: 700; }
    .purchases-form-page .mb-3, .purchases-form-page .mb-4 { margin-bottom: 12px !important; }
    .purchases-form-page .form-label { margin-bottom: 4px; font-size: 0.8125rem; font-weight: 600; }
    .purchases-form-page .form-control, .purchases-form-page .form-select { padding: 0.4rem 0.65rem; font-size: 0.8125rem; }
    .purchases-form-page small.text-muted { display: inline-block; margin-top: 3px; font-size: 0.75rem; }
    .purchases-form-page .d-flex.justify-content-end.gap-2 { margin-top: 16px !important; }

    /* Searchable supplier select (Select2) — match the compact .form-select sizing above */
    .purchases-form-page .select2-container--default .select2-selection--single {
        height: calc(1.5em + 0.8rem + 2px);
        padding: 0.4rem 0.65rem;
        font-size: 0.8125rem;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        display: flex;
        align-items: center;
    }
    .purchases-form-page .select2-container--default .select2-selection--single .select2-selection__rendered {
        padding: 0;
        line-height: normal;
    }
    .purchases-form-page .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 100%;
        top: 0;
        right: 6px;
    }
    .purchases-form-page .select2-container--default .select2-selection--single .select2-selection__clear {
        margin-right: 6px;
    }
    .purchases-form-page .select2-container--default.select2-container--focus .select2-selection--single,
    .purchases-form-page .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #86b7fe;
    }
    /* Select2's open-dropdown font size is set globally, once, from
       _partials/_line_table.blade.php (see comment there for why). */
</style>
@endpush

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
