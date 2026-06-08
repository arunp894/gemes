@extends('layout.app')

@section('title', $banner->title)

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">{{ $banner->title }}</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('banners.index') }}">Banners</a></li>
                <li class="breadcrumb-item active">{{ $banner->title }}</li>
            </ol>
        </div>
    </div>

    <div class="row">

        {{-- Left — image + status card --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">

                    @if ($banner->hasImage())
                        <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}"
                            class="img-fluid rounded mb-3"
                            style="max-height: 200px; object-fit: cover; width: 100%;">
                    @else
                        <div class="avatar-lg mx-auto mb-3">
                            <span class="avatar-title bg-light text-muted rounded fs-1">
                                <i class="ti ti-photo"></i>
                            </span>
                        </div>
                    @endif

                    <h4 class="mb-1">{{ $banner->title }}</h4>
                    @if ($banner->subtitle)
                        <p class="text-muted mb-2">{{ $banner->subtitle }}</p>
                    @endif

                    <div class="d-flex justify-content-center gap-1 flex-wrap mb-2">
                        <span class="badge {{ $banner->positionBadgeClass() }}">{{ $banner->positionLabel() }}</span>
                        <span class="badge {{ $banner->statusBadgeClass() }}">{{ $banner->statusLabel() }}</span>
                        {!! $banner->liveBadge() !!}
                    </div>

                    <hr>

                    <dl class="row text-start mb-0">
                        <dt class="col-5 text-muted small">ID</dt>
                        <dd class="col-7 small">#{{ $banner->id }}</dd>

                        <dt class="col-5 text-muted small">Sort Order</dt>
                        <dd class="col-7 small">{{ $banner->sort_order }}</dd>

                        <dt class="col-5 text-muted small">Starts</dt>
                        <dd class="col-7 small">
                            {{ optional($banner->starts_at)->format('d M Y, h:i A') ?? '—' }}
                        </dd>

                        <dt class="col-5 text-muted small">Ends</dt>
                        <dd class="col-7 small">
                            {{ optional($banner->ends_at)->format('d M Y, h:i A') ?? '—' }}
                        </dd>

                        @if ($banner->link_url)
                            <dt class="col-5 text-muted small">Link</dt>
                            <dd class="col-7 small" style="word-break:break-all;">
                                <a href="{{ $banner->link_url }}" target="_blank" rel="noopener">
                                    {{ $banner->link_text ?: $banner->link_url }}
                                    <i class="ti ti-external-link fs-xs ms-1"></i>
                                </a>
                            </dd>
                        @endif

                        <dt class="col-5 text-muted small">Created</dt>
                        <dd class="col-7 small">{{ optional($banner->created_at)->format('d M Y, h:i A') ?? '—' }}</dd>

                        <dt class="col-5 text-muted small">Modified</dt>
                        <dd class="col-7 small">{{ optional($banner->updated_at)->format('d M Y, h:i A') ?? '—' }}</dd>

                        @if ($banner->creator)
                            <dt class="col-5 text-muted small">Created By</dt>
                            <dd class="col-7 small">{{ $banner->creator->name }}</dd>
                        @endif
                        @if ($banner->updater)
                            <dt class="col-5 text-muted small">Updated By</dt>
                            <dd class="col-7 small">{{ $banner->updater->name }}</dd>
                        @endif
                    </dl>
                </div>

                <div class="card-footer border-0 d-flex gap-2 justify-content-center">
                    @permission('banners.edit')
                    <a href="{{ route('banners.edit', $banner) }}" class="btn btn-primary btn-sm">
                        <i class="ti ti-edit me-1"></i> Edit
                    </a>
                    @endpermission
                    <a href="{{ route('banners.index') }}" class="btn btn-light btn-sm">
                        <i class="ti ti-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>

        {{-- Right — details --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Banner Details</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <p class="mb-1 text-muted small text-uppercase fw-semibold">Title</p>
                            <p class="mb-0">{{ $banner->title }}</p>
                        </div>

                        <div class="col-sm-6">
                            <p class="mb-1 text-muted small text-uppercase fw-semibold">Subtitle</p>
                            <p class="mb-0">{{ $banner->subtitle ?: '—' }}</p>
                        </div>

                        <div class="col-sm-6">
                            <p class="mb-1 text-muted small text-uppercase fw-semibold">Position</p>
                            <p class="mb-0">
                                <span class="badge {{ $banner->positionBadgeClass() }}">{{ $banner->positionLabel() }}</span>
                            </p>
                        </div>

                        <div class="col-sm-6">
                            <p class="mb-1 text-muted small text-uppercase fw-semibold">Sort Order</p>
                            <p class="mb-0">{{ $banner->sort_order }}</p>
                        </div>

                        <div class="col-sm-6">
                            <p class="mb-1 text-muted small text-uppercase fw-semibold">Link URL</p>
                            <p class="mb-0" style="word-break:break-all;">
                                @if ($banner->link_url)
                                    <a href="{{ $banner->link_url }}" target="_blank" rel="noopener">
                                        {{ $banner->link_url }}
                                    </a>
                                @else
                                    —
                                @endif
                            </p>
                        </div>

                        <div class="col-sm-6">
                            <p class="mb-1 text-muted small text-uppercase fw-semibold">Link Text</p>
                            <p class="mb-0">{{ $banner->link_text ?: '—' }}</p>
                        </div>

                        <div class="col-sm-6">
                            <p class="mb-1 text-muted small text-uppercase fw-semibold">Start Date</p>
                            <p class="mb-0">{{ optional($banner->starts_at)->format('d M Y, h:i A') ?? '— (immediate)' }}</p>
                        </div>

                        <div class="col-sm-6">
                            <p class="mb-1 text-muted small text-uppercase fw-semibold">End Date</p>
                            <p class="mb-0">{{ optional($banner->ends_at)->format('d M Y, h:i A') ?? '— (no expiry)' }}</p>
                        </div>

                        <div class="col-sm-12">
                            <p class="mb-1 text-muted small text-uppercase fw-semibold">Notes</p>
                            <p class="mb-0">{{ $banner->notes ?: '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

@endsection
