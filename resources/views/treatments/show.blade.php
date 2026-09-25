@extends('layout.app')

@section('title', $treatment->name)

@section('content')
<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">
                {{ $treatment->name }}
                <span class="badge {{ $treatment->isActive() ? 'badge-soft-success' : 'badge-soft-secondary' }} ms-2">{{ $treatment->statusLabel() }}</span>
            </h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('treatments.index') }}">Treatment</a></li>
                <li class="breadcrumb-item active">{{ $treatment->name }}</li>
            </ol>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header border-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Details</h5>
                    @permission('treatments.edit')
                    <a href="{{ route('treatments.edit', $treatment) }}" class="btn btn-sm btn-soft-primary">
                        <i class="ti ti-edit me-1"></i> Edit
                    </a>
                    @endpermission
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Name</dt>
                        <dd class="col-7 fw-semibold">{{ $treatment->name }}</dd>

                        <dt class="col-5 text-muted">Code</dt>
                        <dd class="col-7"><code>{{ $treatment->code }}</code></dd>

                        <dt class="col-5 text-muted">Display Order</dt>
                        <dd class="col-7">{{ $treatment->display_order }}</dd>

                        <dt class="col-5 text-muted">Status</dt>
                        <dd class="col-7">
                            <span class="badge {{ $treatment->isActive() ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                {{ $treatment->statusLabel() }}
                            </span>
                        </dd>

                        <dt class="col-5 text-muted">Created</dt>
                        <dd class="col-7">{{ optional($treatment->created_at)->format('d M Y') }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
