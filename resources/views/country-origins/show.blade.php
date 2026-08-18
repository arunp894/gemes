@extends('layout.app')

@section('title', $origin->name)

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">{{ $origin->name }}</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('country-origins.index') }}">Countries of Origin</a></li>
                <li class="breadcrumb-item active">{{ $origin->name }}</li>
            </ol>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-center gap-1 flex-wrap mb-3">
                        <span class="badge {{ $origin->statusBadgeClass() }}">{{ $origin->statusLabel() }}</span>
                    </div>

                    <dl class="row mb-0">
                        <dt class="col-5 text-muted small">ID</dt>
                        <dd class="col-7 small">#{{ $origin->id }}</dd>

                        <dt class="col-5 text-muted small">Name</dt>
                        <dd class="col-7 small">{{ $origin->name }}</dd>

                        <dt class="col-5 text-muted small">Sort Order</dt>
                        <dd class="col-7 small">{{ $origin->display_order }}</dd>

                        <dt class="col-5 text-muted small">In Use</dt>
                        <dd class="col-7 small">{{ $origin->isInUse() ? 'Yes' : 'No' }}</dd>

                        <dt class="col-5 text-muted small">Created</dt>
                        <dd class="col-7 small">{{ optional($origin->created_at)->format('d M Y, h:i A') ?? '—' }}</dd>

                        <dt class="col-5 text-muted small">Modified</dt>
                        <dd class="col-7 small">{{ optional($origin->updated_at)->format('d M Y, h:i A') ?? '—' }}</dd>

                        @if ($origin->creator)
                            <dt class="col-5 text-muted small">Created By</dt>
                            <dd class="col-7 small">{{ $origin->creator->name }}</dd>
                        @endif
                        @if ($origin->updater)
                            <dt class="col-5 text-muted small">Updated By</dt>
                            <dd class="col-7 small">{{ $origin->updater->name }}</dd>
                        @endif
                    </dl>
                </div>

                <div class="card-footer border-0 d-flex gap-2 justify-content-center">
                    @permission('country-origins.edit')
                    <a href="{{ route('country-origins.edit', $origin) }}" class="btn btn-primary btn-sm">
                        <i class="ti ti-edit me-1"></i> Edit
                    </a>
                    @endpermission
                    <a href="{{ route('country-origins.index') }}" class="btn btn-light btn-sm">
                        <i class="ti ti-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection
