@extends('layout.app')

@section('title', 'Edit Purchase')

@section('content')
<div class="container-fluid purchases-page purchases-form-page" id="purchaseFormApp">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">
                Edit Purchase
                <span class="badge badge-soft-secondary ms-2">{{ $purchase->invoice_number }}</span>
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

    <div class="toast-container position-fixed top-0 end-0 p-3" id="purchaseToastContainer" style="z-index: 1080;"></div>

    @if ($purchase->isPosted())
        <div class="alert alert-info d-flex align-items-start gap-2">
            <i class="ti ti-info-circle fs-lg mt-1"></i>
            <div>
                <strong>This purchase is already posted.</strong> Saving will adjust the stock ledger
                accordingly &mdash; the current items are reversed and the new items are posted in their place.
                This is only possible because no stock from this purchase has been sold yet.
            </div>
        </div>
    @endif

    {{-- No 'was-validated' class here on purpose — see create.blade.php's
         form tag for why: it painted false-positive green checkmarks on
         genuinely optional, still-empty fields. --}}
    <form id="purchaseForm" novalidate @submit.prevent="submit(false)">
        <div class="row g-3">
            <div class="col-xl-10">

                <div class="card mb-3">
                    <div class="card-body py-2">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label"><i class="ti ti-truck-delivery me-1 text-muted"></i>Supplier</label>
                                <input type="text" class="form-control bg-light" readonly
                                       value="{{ $purchase->supplier ? ($purchase->supplier->company_name ?: $purchase->supplier->name) : '' }}">
                                <small class="text-muted">Cannot be changed.</small>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label"><i class="ti ti-calendar me-1 text-muted"></i>Purchase Date</label>
                                <input type="date" class="form-control" v-model="form.purchase_date">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label"><i class="ti ti-receipt me-1 text-muted"></i>Invoice #</label>
                                <input type="text" class="form-control bg-light" :value="form.invoice_number_preview" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label"><i class="ti ti-percentage me-1 text-muted"></i>Tax Type</label>
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
                                        @{{ loc.name }} &middot; @{{ loc.location_code }}
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

                @include('purchases._partials._line_table')

            </div>

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
</style>
@endpush

@push('scripts')
@include('purchases._partials._purchase_app_script', [
    'mode'             => 'edit',
    'suppliersJson'    => $suppliers->toJson(),
    'locationsJson'    => $locations->toJson(),
    'racksJson'        => $racks->toJson(),
    'categoriesJson'   => $categories->toJson(),
    'countriesOfOriginJson' => $countriesOfOrigin->toJson(),
    'previewUrl'       => route('purchases.preview-invoice-number'),
    'lotCodePreviewUrl'=> route('purchases.preview-lot-code'),
    'submitUrl'        => route('purchases.update', $purchase),
    'submitMethod'     => 'PUT',
    'existingPurchase' => $purchase,
    'currencySymbol'   => $currencySymbol,
])
@endpush
