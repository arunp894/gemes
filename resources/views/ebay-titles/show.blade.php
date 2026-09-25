@extends('layout.app')

@section('title', Str::limit($ebayTitle->title, 60))

@section('content')
<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">
                {{ Str::limit($ebayTitle->title, 60) }}
                <span class="badge {{ $ebayTitle->isActive() ? 'badge-soft-success' : 'badge-soft-secondary' }} ms-2">{{ $ebayTitle->statusLabel() }}</span>
            </h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('ebay-titles.index') }}">eBay Title</a></li>
                <li class="breadcrumb-item active">View</li>
            </ol>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header border-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Details</h5>
                    @permission('ebay-titles.edit')
                    <a href="{{ route('ebay-titles.edit', $ebayTitle) }}" class="btn btn-sm btn-soft-primary">
                        <i class="ti ti-edit me-1"></i> Edit
                    </a>
                    @endpermission
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-4 text-muted">Stone</dt>
                        <dd class="col-8 fw-semibold">{{ $ebayTitle->category?->name ?? '—' }}</dd>

                        <dt class="col-4 text-muted">eBay Title</dt>
                        <dd class="col-8">{{ $ebayTitle->title }}</dd>

                        <dt class="col-4 text-muted">Length</dt>
                        <dd class="col-8">{{ strlen($ebayTitle->title) }}/80 characters</dd>

                        <dt class="col-4 text-muted">Display Order</dt>
                        <dd class="col-8">{{ $ebayTitle->display_order }}</dd>

                        <dt class="col-4 text-muted">Status</dt>
                        <dd class="col-8">
                            <span class="badge {{ $ebayTitle->isActive() ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                {{ $ebayTitle->statusLabel() }}
                            </span>
                        </dd>

                        <dt class="col-4 text-muted">Created</dt>
                        <dd class="col-8">{{ optional($ebayTitle->created_at)->format('d M Y') }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
