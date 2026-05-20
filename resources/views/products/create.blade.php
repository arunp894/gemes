@extends('layout.app')

@section('title', 'Add Product')

@section('content')

<div class="container-fluid" id="productApp">

    {{-- Page title --}}
    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Add New Product</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Paces</a></li>
                <li class="breadcrumb-item"><a href="#">Catalogue</a></li>
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
                            <button type="submit" class="btn btn-primary" :disabled="submitting">
                                <span v-if="submitting" class="spinner-border spinner-border-sm me-1"></span>
                                <i class="ti ti-device-floppy me-1"></i> Save Product
                            </button>
                            <a href="{{ route('products.index') }}" class="btn btn-light">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

</div>

@endsection

@push('scripts')
@include('products._partials._product_app_script')
@endpush
