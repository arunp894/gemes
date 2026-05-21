@extends('layout.app')

@section('title', 'Category Details')

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Category Details</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Paces</a></li>
                <li class="breadcrumb-item"><a href="{{ route('categories.index') }}">Categories</a></li>
                @if ($category->parent)
                    <li class="breadcrumb-item">
                        <a href="{{ route('categories.show', $category->parent) }}">{{ $category->parent->name }}</a>
                    </li>
                @endif
                <li class="breadcrumb-item active">{{ $category->name }}</li>
            </ol>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h4 class="header-title mb-1">
                                {{ $category->name }}
                                @if ($category->parent)
                                    <span class="badge badge-soft-info ms-1">Subcategory</span>
                                @else
                                    <span class="badge badge-soft-primary ms-1">Top-Level</span>
                                @endif
                                @if ($category->is_gemstone && ! $category->parent)
                                    <span class="badge badge-soft-warning ms-1" title="Products in this category show gemstone fields">
                                        <i class="ti ti-diamond me-1"></i>Gemstone
                                    </span>
                                @endif
                            </h4>
                            <p class="text-muted mb-0">
                                <code>{{ $category->code }}</code>
                                <span class="ms-2 badge {{ $category->isActive() ? 'badge-soft-success' : 'badge-soft-danger' }} fs-xxs">
                                    {{ $category->statusLabel() }}
                                </span>
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('categories.edit', $category) }}" class="btn btn-primary">
                                <i class="ti ti-pencil me-1"></i>Edit
                            </a>
                            <a href="{{ route('categories.index') }}" class="btn btn-light">Back</a>
                        </div>
                    </div>

                    <hr>

                    <div class="row g-3">
                        @if ($category->parent)
                            <div class="col-md-6">
                                <small class="text-muted d-block">Parent Category</small>
                                <strong>
                                    <a href="{{ route('categories.show', $category->parent) }}" class="link-reset">
                                        {{ $category->parent->name }}
                                    </a>
                                </strong>
                            </div>
                        @endif
                        <div class="col-md-6">
                            <small class="text-muted d-block">Display Order</small>
                            <strong>{{ $category->display_order }}</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">
                                @if ($category->parent) Sibling Subcategories @else Subcategories @endif
                            </small>
                            <strong>{{ $category->subcategories_count ?? $category->subcategories()->count() }}</strong>
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block">Description</small>
                            <p class="mb-0">{{ $category->description ?: '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Subcategories list (if any) --}}
            @if (!$category->parent && $category->subcategories()->exists())
                <div class="card">
                    <div class="card-header border-light justify-content-between">
                        <h5 class="header-title mb-0">Subcategories</h5>
                        <a href="{{ route('categories.create') }}" class="btn btn-sm btn-light">
                            <i class="ti ti-plus me-1"></i>Add Subcategory
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom table-centered table-hover w-100 mb-0">
                            <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                <tr class="text-uppercase fs-xxs">
                                    <th class="ps-3">Name</th>
                                    <th>Code</th>
                                    <th>Status</th>
                                    <th class="text-center" style="width:1%;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($category->subcategories()->ordered()->get() as $sub)
                                    <tr>
                                        <td class="ps-3">
                                            <a href="{{ route('categories.show', $sub) }}" class="link-reset fw-semibold">
                                                {{ $sub->name }}
                                            </a>
                                        </td>
                                        <td><code class="text-muted">{{ $sub->code }}</code></td>
                                        <td>
                                            <span class="badge {{ $sub->isActive() ? 'badge-soft-success' : 'badge-soft-danger' }} fs-xxs">
                                                {{ $sub->statusLabel() }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('categories.edit', $sub) }}" class="btn btn-default btn-icon btn-sm" title="Edit">
                                                <i class="ti ti-edit fs-lg"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="header-title text-start mb-3">Category Image</h5>
                    @if ($category->image_url)
                        <img src="{{ $category->image_url }}" alt="{{ $category->name }}"
                             class="img-fluid rounded border" style="max-height:240px;">
                    @else
                        <div class="bg-light rounded py-5 text-muted">
                            <i class="ti ti-photo-off" style="font-size:3rem;"></i>
                            <p class="mb-0 mt-2 small">No image uploaded</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="header-title">Audit Trail</h5>
                    <p class="mb-1 small text-muted">
                        <strong>Created:</strong>
                        {{ $category->created_at?->format('d M Y, h:i A') }}
                        @if ($category->creator)
                            by {{ $category->creator->name ?? '—' }}
                        @endif
                    </p>
                    <p class="mb-0 small text-muted">
                        <strong>Last Modified:</strong>
                        {{ $category->updated_at?->format('d M Y, h:i A') }}
                        @if ($category->updater)
                            by {{ $category->updater->name ?? '—' }}
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection
