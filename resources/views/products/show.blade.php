@extends('layout.app')

@section('title', $product->title)

@section('content')

<div class="container-fluid">

    {{-- Page title --}}
    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">{{ $product->title }}</h4>
            <small class="text-muted">SKU: <code>{{ $product->sku }}</code></small>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Paces</a></li>
                <li class="breadcrumb-item"><a href="#">Catalogue</a></li>
                <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
                <li class="breadcrumb-item active">{{ \Illuminate\Support\Str::limit($product->title, 32) }}</li>
            </ol>
        </div>
    </div>

    <div class="row">
        {{-- Left: images --}}
        <div class="col-lg-5">
            <div class="card">
                <div class="card-body">
                    @if ($product->primary_image_url)
                        <img src="{{ $product->primary_image_url }}" alt="{{ $product->title }}"
                            class="img-fluid rounded mb-3 w-100" style="max-height: 380px; object-fit: contain;">
                    @else
                        <div class="bg-light rounded mb-3 d-flex align-items-center justify-content-center"
                             style="height: 380px;">
                            <span class="text-muted"><i class="ti ti-photo-off fs-1"></i></span>
                        </div>
                    @endif

                    @if (!empty($product->gallery_urls))
                        <h6 class="text-muted text-uppercase fs-xs mb-2">Gallery</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($product->gallery_urls as $g)
                                <a href="{{ $g['url'] }}" target="_blank">
                                    <img src="{{ $g['thumb'] }}" alt="" class="rounded border"
                                        style="width: 72px; height: 72px; object-fit: cover;">
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @if ($product->certificate_url)
                        <hr>
                        <a href="{{ $product->certificate_url }}" target="_blank" class="btn btn-light">
                            <i class="ti ti-certificate me-1"></i>View Certificate
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right: details --}}
        <div class="col-lg-7">
            {{-- Actions toolbar --}}
            <div class="card mb-3">
                <div class="card-body d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <div>
                        <span class="{{ $product->isActive() ? 'badge-soft-success' : 'badge-soft-warning' }} badge me-1">
                            {{ $product->statusLabel() }}
                        </span>
                        <span class="{{ $product->isWebsiteEnabled() ? 'badge-soft-info' : 'badge-soft-secondary' }} badge me-1">
                            Website: {{ $product->websiteVisibilityLabel() }}
                        </span>
                        @if ($product->featured_product)
                            <span class="badge badge-soft-warning"><i class="ti ti-star-filled"></i> Featured</span>
                        @endif
                    </div>
                    <div class="d-flex gap-1">
                        <a href="{{ route('products.edit', $product) }}" class="btn btn-primary btn-sm">
                            <i class="ti ti-edit me-1"></i> Edit
                        </a>
                        <a href="{{ route('products.index') }}" class="btn btn-light btn-sm">
                            <i class="ti ti-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>

            {{-- Core info --}}
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-title mb-0">Details</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">Category</dt>
                        <dd class="col-sm-8">
                            {{ $product->category?->parent?->name ?? '—' }}
                            <i class="ti ti-chevron-right text-muted"></i>
                            <strong>{{ $product->category?->name ?? '—' }}</strong>
                        </dd>

                        <dt class="col-sm-4 text-muted">Country of Origin</dt>
                        <dd class="col-sm-8">{{ $product->country_of_origin ?: '—' }}</dd>

                        @if ($product->short_description)
                            <dt class="col-sm-4 text-muted">Short Description</dt>
                            <dd class="col-sm-8">{{ $product->short_description }}</dd>
                        @endif

                        @if ($product->full_description)
                            <dt class="col-sm-4 text-muted">Full Description</dt>
                            <dd class="col-sm-8" style="white-space: pre-line;">{{ $product->full_description }}</dd>
                        @endif

                        @if ($product->notes_tags)
                            <dt class="col-sm-4 text-muted">Notes / Tags</dt>
                            <dd class="col-sm-8"><small class="text-muted">{{ $product->notes_tags }}</small></dd>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Gemstone details --}}
            @if ($product->isGemstone())
                <div class="card mb-3">
                    <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-diamond me-1"></i>Gemstone Details</h5></div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4 text-muted">Carat Weight</dt>
                            <dd class="col-sm-8">{{ $product->carat_weight ? $product->carat_weight . ' ct' : '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Stone Type</dt>
                            <dd class="col-sm-8">{{ $product->stone_type ?: '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Treatment</dt>
                            <dd class="col-sm-8">{{ $product->treatment ?: '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Cut / Shape</dt>
                            <dd class="col-sm-8">{{ $product->cut_shape ?: '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Colour Grade</dt>
                            <dd class="col-sm-8">{{ $product->colour_grade ?: '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Clarity Grade</dt>
                            <dd class="col-sm-8">{{ $product->clarity_grade ?: '—' }}</dd>

                            <dt class="col-sm-4 text-muted">Certificate Number</dt>
                            <dd class="col-sm-8">{{ $product->certificate_number ?: '—' }}</dd>
                        </dl>
                    </div>
                </div>
            @endif

            {{-- Barcodes --}}
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-barcode me-1"></i>Barcodes</h5></div>
                <div class="card-body p-0">
                    <table class="table table-sm table-centered mb-0">
                        <thead class="bg-light bg-opacity-25 fs-xxs text-uppercase">
                            <tr>
                                <th class="ps-3">#</th>
                                <th>Value</th>
                                <th>Format</th>
                                <th>Label</th>
                                <th>Channels</th>
                                <th>Primary</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($product->barcodes->sortBy('sequence_number') as $b)
                                <tr>
                                    <td class="ps-3">{{ $b->sequence_number }}</td>
                                    <td><code>{{ $b->barcode_value }}</code></td>
                                    <td><span class="badge badge-soft-info">{{ $b->barcode_format }}</span></td>
                                    <td>{{ $b->barcode_label ?: '—' }}</td>
                                    <td>
                                        @if ($b->channels->isEmpty())
                                            <small class="text-muted">All channels</small>
                                        @else
                                            @foreach ($b->channels as $c)
                                                <span class="badge bg-light text-dark me-1">{{ $c->name }}</span>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td>
                                        @if ($b->is_primary)
                                            <i class="ti ti-star-filled text-warning" title="Primary"></i>
                                        @else
                                            <i class="ti ti-star text-muted"></i>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted text-center py-3">No barcodes assigned.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Website visibility --}}
            <div class="card mb-3">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-world me-1"></i>Website Visibility</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">Status</dt>
                        <dd class="col-sm-8">
                            <span class="{{ $product->isWebsiteEnabled() ? 'badge-soft-info' : 'badge-soft-secondary' }} badge">
                                {{ $product->websiteVisibilityLabel() }}
                            </span>
                        </dd>

                        @if ($product->website_enabled_at)
                            <dt class="col-sm-4 text-muted">Enabled On</dt>
                            <dd class="col-sm-8">{{ $product->website_enabled_at->format('d M Y, h:i A') }}</dd>
                        @endif

                        @if ($product->website_disabled_at)
                            <dt class="col-sm-4 text-muted">Disabled On</dt>
                            <dd class="col-sm-8">{{ $product->website_disabled_at->format('d M Y, h:i A') }}</dd>
                        @endif

                        <dt class="col-sm-4 text-muted">Featured</dt>
                        <dd class="col-sm-8">
                            {{ $product->featured_product ? 'Yes' : 'No' }}
                        </dd>

                        @if (!is_null($product->website_price))
                            <dt class="col-sm-4 text-muted">Website Price</dt>
                            <dd class="col-sm-8">{{ number_format($product->website_price, 2) }}</dd>
                        @endif

                        @if ($product->website_title)
                            <dt class="col-sm-4 text-muted">Website Title (override)</dt>
                            <dd class="col-sm-8">{{ $product->website_title }}</dd>
                        @endif

                        @if ($product->website_description)
                            <dt class="col-sm-4 text-muted">Website Description (override)</dt>
                            <dd class="col-sm-8" style="white-space: pre-line;">{{ $product->website_description }}</dd>
                        @endif

                        @if (!is_null($product->website_sort_order))
                            <dt class="col-sm-4 text-muted">Website Sort Order</dt>
                            <dd class="col-sm-8">{{ $product->website_sort_order }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Audit --}}
            <div class="card">
                <div class="card-body small text-muted">
                    Created {{ $product->created_at?->diffForHumans() }}
                    @if ($product->creator) by <strong>{{ $product->creator->name }}</strong>@endif
                    &nbsp;·&nbsp;
                    Last updated {{ $product->updated_at?->diffForHumans() }}
                    @if ($product->updater) by <strong>{{ $product->updater->name }}</strong>@endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
