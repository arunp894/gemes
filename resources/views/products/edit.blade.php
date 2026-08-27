@extends('layout.app')

@section('title', 'Edit Product')

@section('content')

<div class="container-fluid products-form-page" id="productApp">

    {{-- Page title --}}
    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Edit Product</h4>
            <small class="text-muted">{{ $product->title }}</small>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </div>
    </div>

    {{-- Toast notifications for inline validation feedback (file type/size, barcode generation) --}}
    <div class="toast-container position-fixed top-0 end-0 p-3" id="productFormToastContainer" style="z-index: 1080;"></div>

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
                            <button type="submit" class="btn btn-primary" :disabled="submitting">
                                <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                                <i class="ti ti-device-floppy me-1"></i> Update Product
                            </button>
                            <a href="{{ route('products.show', $product) }}" class="btn btn-light">
                                <i class="ti ti-eye me-1"></i>View Product
                            </a>
                            <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- ==================== Switch to Single Barcode Mode Confirmation Modal ==================== --}}
    <div class="modal fade" id="switchBarcodeModeModal" tabindex="-1" aria-labelledby="switchBarcodeModeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div class="confirm-modal-icon mx-auto mb-3">
                        <i class="ti ti-barcode"></i>
                    </div>
                    <h5 class="modal-title mb-2" id="switchBarcodeModeModalLabel">Switch to Single mode?</h5>
                    <p class="text-muted mb-0">
                        Switching to Single mode will remove all but the primary barcode. Continue?
                    </p>
                </div>
                <div class="modal-footer border-0 justify-content-center pb-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmSwitchBarcodeModeBtn">
                        <i class="ti ti-check me-1"></i>Continue
                    </button>
                </div>
            </div>
        </div>
    </div>
    {{-- ==================== /Switch to Single Barcode Mode Confirmation Modal ==================== --}}

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
    .products-form-page .confirm-modal-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 1.5rem;
    }
</style>
@endpush

@push('scripts')
@php
    // Bootstrap payload for the productApp Vue instance.
    // NOTE: built as a PHP variable first because Blade's @json directive
    // splits its argument on every comma (see compileJson) and silently
    // mangles multi-line inline arrays.
    $productBootstrap = [
        'id'                 => $product->id,
        'primary_image_url'  => $product->primary_image_url,
        'certificate_url'    => $product->certificate_url,
        'gallery'            => $product->gallery_urls,
        'form'               => [
            'title'              => $product->title,
            'sku'                => $product->sku,
            'category_id'        => $product->category_id,
            'short_description'  => $product->short_description,
            'full_description'   => $product->full_description,
            'country_of_origin_id' => $product->country_of_origin_id,
            'notes_tags'         => $product->notes_tags,
            'status'             => (bool) $product->status,
            'carat_weight'       => $product->carat_weight,
            'stone_type'         => $product->stone_type,
            'colour_grade'       => $product->colour_grade,
            'clarity_grade'      => $product->clarity_grade,
            'cut_shape'          => $product->cut_shape,
            'treatment'          => $product->treatment,
            'stone_description'  => $product->stone_description,
            'certificate_number' => $product->certificate_number,
            'website_enabled'    => (bool) $product->website_enabled,
            'website_price'      => $product->website_price,
            'website_title'      => $product->website_title,
            'website_description'=> $product->website_description,
            'featured_product'   => (bool) $product->featured_product,
            'website_sort_order' => $product->website_sort_order,
        ],
        'barcodes' => $product->barcodes->map(function ($b) {
            return [
                'id'             => $b->id,
                'barcode_value'  => $b->barcode_value,
                'barcode_format' => $b->barcode_format,
                'barcode_label'  => $b->barcode_label,
                'is_primary'     => (bool) $b->is_primary,
                'channels'       => $b->channels->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
            ];
        })->values(),
    ];
@endphp
<script>
    // Loaded BEFORE _product_app_script via stack ordering.
    window.__productBootstrap = @json($productBootstrap);
</script>

{{-- Reuse the create form's Vue logic verbatim. --}}
@include('products._partials._product_app_script')
@endpush
