@extends('layout.app')

@section('title', $blog->title)

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">{{ $blog->title }}</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('blogs.index') }}">Blog</a></li>
                <li class="breadcrumb-item active">{{ $blog->title }}</li>
            </ol>
        </div>
    </div>

    <div class="row">

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">

                    @if ($blog->hasImage())
                        <img src="{{ $blog->image_url }}" alt="{{ $blog->title }}"
                            class="img-fluid rounded mb-3"
                            style="max-height: 200px; object-fit: cover; width: 100%;">
                    @else
                        <div class="avatar-lg mx-auto mb-3">
                            <span class="avatar-title bg-light text-muted rounded fs-1">
                                <i class="ti ti-article"></i>
                            </span>
                        </div>
                    @endif

                    <h4 class="mb-1">{{ $blog->title }}</h4>
                    <p class="text-muted mb-2 small">/blog/{{ $blog->slug }}</p>

                    <div class="d-flex justify-content-center gap-1 flex-wrap mb-2">
                        <span class="badge {{ $blog->statusBadgeClass() }}">{{ $blog->statusLabel() }}</span>
                    </div>

                    @if ($blog->isActive())
                        <a href="{{ route('website.blog.show', $blog) }}" target="_blank" class="btn btn-sm btn-soft-secondary mt-2">
                            <i class="ti ti-external-link me-1"></i> View on Site
                        </a>
                    @endif

                    <hr>

                    <dl class="row text-start mb-0">
                        <dt class="col-5 text-muted small">ID</dt>
                        <dd class="col-7 small">#{{ $blog->id }}</dd>

                        <dt class="col-5 text-muted small">Published</dt>
                        <dd class="col-7 small">{{ optional($blog->published_at)->format('d M Y, h:i A') ?? '—' }}</dd>

                        <dt class="col-5 text-muted small">Reading Time</dt>
                        <dd class="col-7 small">{{ $blog->readingTimeMinutes() }} min</dd>

                        <dt class="col-5 text-muted small">Created</dt>
                        <dd class="col-7 small">{{ optional($blog->created_at)->format('d M Y, h:i A') ?? '—' }}</dd>

                        <dt class="col-5 text-muted small">Modified</dt>
                        <dd class="col-7 small">{{ optional($blog->updated_at)->format('d M Y, h:i A') ?? '—' }}</dd>

                        @if ($blog->creator)
                            <dt class="col-5 text-muted small">Created By</dt>
                            <dd class="col-7 small">{{ $blog->creator->name }}</dd>
                        @endif
                        @if ($blog->updater)
                            <dt class="col-5 text-muted small">Updated By</dt>
                            <dd class="col-7 small">{{ $blog->updater->name }}</dd>
                        @endif
                    </dl>
                </div>

                <div class="card-footer border-0 d-flex gap-2 justify-content-center">
                    @permission('blogs.edit')
                    <a href="{{ route('blogs.edit', $blog) }}" class="btn btn-primary btn-sm">
                        <i class="ti ti-edit me-1"></i> Edit
                    </a>
                    @endpermission
                    <a href="{{ route('blogs.index') }}" class="btn btn-light btn-sm">
                        <i class="ti ti-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Excerpt</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $blog->displayExcerpt(300) }}</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Content</h5>
                </div>
                <div class="card-body blog-content">
                    {!! $blog->content !!}
                </div>
            </div>

            @if ($blog->meta_title || $blog->meta_description)
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">SEO</h5>
                </div>
                <div class="card-body">
                    <p class="mb-1 text-muted small text-uppercase fw-semibold">Meta Title</p>
                    <p class="mb-3">{{ $blog->meta_title ?: '—' }}</p>
                    <p class="mb-1 text-muted small text-uppercase fw-semibold">Meta Description</p>
                    <p class="mb-0">{{ $blog->meta_description ?: '—' }}</p>
                </div>
            </div>
            @endif
        </div>

    </div>

</div>

@endsection
