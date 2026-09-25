@extends('layout.app')

@section('title', $clarity->name)

@section('content')
<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">
                {{ $clarity->name }}
                <span class="badge {{ $clarity->isActive() ? 'badge-soft-success' : 'badge-soft-secondary' }} ms-2">{{ $clarity->statusLabel() }}</span>
            </h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('clarities.index') }}">Clarity</a></li>
                <li class="breadcrumb-item active">{{ $clarity->name }}</li>
            </ol>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header border-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Details</h5>
                    @permission('clarities.edit')
                    <a href="{{ route('clarities.edit', $clarity) }}" class="btn btn-sm btn-soft-primary">
                        <i class="ti ti-edit me-1"></i> Edit
                    </a>
                    @endpermission
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Name</dt>
                        <dd class="col-7 fw-semibold">{{ $clarity->name }}</dd>

                        <dt class="col-5 text-muted">Code</dt>
                        <dd class="col-7"><code>{{ $clarity->code }}</code></dd>

                        <dt class="col-5 text-muted">Display Order</dt>
                        <dd class="col-7">{{ $clarity->display_order }}</dd>

                        <dt class="col-5 text-muted">Status</dt>
                        <dd class="col-7">
                            <span class="badge {{ $clarity->isActive() ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                {{ $clarity->statusLabel() }}
                            </span>
                        </dd>

                        <dt class="col-5 text-muted">Created</dt>
                        <dd class="col-7">{{ optional($clarity->created_at)->format('d M Y') }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
