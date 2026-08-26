@extends('layout.app')

@section('title', 'Add Product')

@section('content')

<div class="container-fluid products-form-page" id="productApp">

    {{-- Page title --}}
    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Add New Product</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
                <li class="breadcrumb-item active">Add Product</li>
            </ol>
        </div>
    </div>

    <form id="productForm" @submit.prevent="submitForm" novalidate>
        <div class="row">
            <div class="col-lg-8">
                @include('products._partials._core_fields')
                @include('products._partials._gemstone_fields')
                @include('products._partials._barcode_panel')

            </div>

            <div class="col-lg-4">
                @include('products._partials._website_visibility')            
                <div class="card">
                    <div class="card-body">
                        <div v-if="serverError" class="alert alert-danger">@{{ serverError }}</div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success" :disabled="submitting">
                                <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                                <i class="ti ti-device-floppy me-1"></i> Save Product
                            </button>
                            <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </div>    
            </div>
        </div>
    </form>

</div>

@endsection

@push('styles')
<style>
    /* Compact spacing for the Add/Edit Product form — scoped to this page only.
       Applies uniformly across all included partials (core fields, gemstone
       panel, barcode panel, website visibility) without altering their markup. */
    .products-form-page { padding-top: 20px; padding-bottom: 20px; }
    .products-form-page .page-title-head {
        display: flex !important;
        align-items: center !important;
        min-height: 35px !important;
        margin-top: 0 !important;
        padding: 10px 0 !important;
        margin-bottom: 16px !important;
        border-bottom: 2px solid #e2e8f0;
    }
    .products-form-page .page-title-head > * { display: flex; align-items: center; }
    .products-form-page .page-main-title {
        font-size: 1.375rem;
        font-weight: 700;
        position: relative;
        padding-left: 12px;
    }
    .products-form-page .page-main-title::before {
        content: '';
        position: absolute;
        left: 0; top: 2px; bottom: 2px;
        width: 4px;
        border-radius: 2px;
        background: linear-gradient(180deg, #1e3a8a, #1d4ed8);
    }
    .products-form-page .breadcrumb { font-size: 0.75rem; }
    .products-form-page .card { border-radius: 10px; box-shadow: none; border: 1px solid #e2e8f0; }
    .products-form-page .card-body { padding: 16px; }
    .products-form-page .card-header { padding: 12px 16px; }
    .products-form-page .header-title,
    .products-form-page .card-title { font-size: 1rem; font-weight: 700; }
    .products-form-page .mb-3, .products-form-page .mb-4 { margin-bottom: 12px !important; }
    .products-form-page .form-label { margin-bottom: 4px; font-size: 0.8125rem; font-weight: 600; }
    .products-form-page .form-control, .products-form-page .form-select { padding: 0.4rem 0.65rem; font-size: 0.8125rem; }
    .products-form-page textarea.form-control { padding: 0.5rem 0.65rem; }
    .products-form-page small.text-muted { display: inline-block; margin-top: 3px; font-size: 0.75rem; }
    .products-form-page .d-flex.justify-content-end.gap-2,
    .products-form-page .d-grid.gap-2 { margin-top: 4px; }
    .products-form-page .form-check { margin-bottom: 2px; }
</style>
@endpush

@push('scripts')
@include('products._partials._product_app_script')
@endpush
