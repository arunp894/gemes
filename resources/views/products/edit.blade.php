@extends('layout.app')

@section('title', 'Edit Product')

@section('content')

<div class="container-fluid" id="productApp">

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

    <form id="productForm" @submit.prevent="submitForm" novalidate>
        <div class="row">
            <div class="col-lg-8">
                @include('products._partials._core_fields')
                @include('products._partials._gemstone_fields')
                @include('products._partials._barcode_panel')
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
                            <a href="{{ route('products.index') }}" class="btn btn-link text-muted">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                @include('products._partials._website_visibility')

            </div>
        </div>
    </form>

</div>

@endsection

@push('scripts')
@php
    // Bootstrap payload for the productApp Vue instance.
    // NOTE: built as a PHP variable first because Blade's @json directive
    // splits its argument on every comma (see compileJson) and silently
    // mangles multi-line inline arrays.
    $productBootstrap = [
        'id'                 => $product->id,
        'top_category_id'    => $topCategoryId,
        'subcategories'      => $subcategories,
        'primary_image_url'  => $product->primary_image_url,
        'certificate_url'    => $product->certificate_url,
        'gallery'            => $product->gallery_urls,
        'form'               => [
            'title'              => $product->title,
            'sku'                => $product->sku,
            'category_id'        => $product->category_id,
            'short_description'  => $product->short_description,
            'full_description'   => $product->full_description,
            'country_of_origin'  => $product->country_of_origin,
            'notes_tags'         => $product->notes_tags,
            'status'             => (bool) $product->status,
            'carat_weight'       => $product->carat_weight,
            'stone_type'         => $product->stone_type,
            'colour_grade'       => $product->colour_grade,
            'clarity_grade'      => $product->clarity_grade,
            'cut_shape'          => $product->cut_shape,
            'treatment'          => $product->treatment,
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
