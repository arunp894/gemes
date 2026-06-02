@extends('layout.app')

@section('title', $location->name)

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">{{ $location->name }}</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('locations.index') }}">Locations</a></li>
                <li class="breadcrumb-item active">{{ $location->name }}</li>
            </ol>
        </div>
    </div>

    <div class="row">
        {{-- Profile card --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar-lg mx-auto mb-3">
                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fw-bold fs-1">
                            {{ strtoupper(mb_substr($location->name, 0, 1)) }}
                        </span>
                    </div>
                    <h4 class="mb-1">{{ $location->name }}</h4>
                    <p class="mb-2"><code>{{ $location->location_code }}</code></p>

                    <div class="d-flex justify-content-center gap-1 flex-wrap">
                        <span class="badge {{ $location->typeBadgeClass() }}">{{ $location->typeLabel() }}</span>
                        <span class="badge {{ $location->statusBadgeClass() }}">{{ $location->statusLabel() }}</span>
                        @if ($location->is_default)
                            <span class="badge badge-soft-warning">
                                <i class="ti ti-star-filled fs-xxs me-1"></i>Default
                            </span>
                        @endif
                    </div>

                    @if ($location->description)
                        <p class="text-muted mt-3 mb-0">{{ $location->description }}</p>
                    @endif

                    <hr>

                    <dl class="row text-start mb-0">
                        <dt class="col-5 text-muted small">ID</dt>
                        <dd class="col-7 small">#{{ $location->id }}</dd>

                        <dt class="col-5 text-muted small">Manager</dt>
                        <dd class="col-7 small">
                            @if ($location->manager)
                                {{ $location->manager->name }}
                            @else
                                <span class="text-muted">— Unassigned —</span>
                            @endif
                        </dd>

                        <dt class="col-5 text-muted small">Created</dt>
                        <dd class="col-7 small">
                            {{ optional($location->created_at)->format('d M Y, h:i A') ?? '—' }}
                        </dd>

                        <dt class="col-5 text-muted small">Modified</dt>
                        <dd class="col-7 small">
                            {{ optional($location->updated_at)->format('d M Y, h:i A') ?? '—' }}
                        </dd>

                        @if ($location->creator)
                            <dt class="col-5 text-muted small">Created By</dt>
                            <dd class="col-7 small">{{ $location->creator->name }}</dd>
                        @endif
                        @if ($location->updater)
                            <dt class="col-5 text-muted small">Updated By</dt>
                            <dd class="col-7 small">{{ $location->updater->name }}</dd>
                        @endif
                    </dl>

                    @permission('locations.edit')
                    <div class="mt-3 d-flex gap-2 justify-content-center">
                        <a href="{{ route('locations.edit', $location) }}" class="btn btn-primary btn-sm">
                            <i class="ti ti-edit me-1"></i> Edit
                        </a>
                        <a href="{{ route('locations.index') }}" class="btn btn-light btn-sm">Back</a>
                    </div>
                    @endpermission
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            {{-- Contact --}}
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Contact</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3 text-muted small">Phone</dt>
                        <dd class="col-sm-9">{{ $location->phone ?? '—' }}</dd>

                        <dt class="col-sm-3 text-muted small">Email</dt>
                        <dd class="col-sm-9">
                            @if ($location->email)
                                <a href="mailto:{{ $location->email }}">{{ $location->email }}</a>
                            @else <span class="text-muted">—</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            {{-- Address --}}
            <div class="card">
                <div class="card-header border-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Address</h5>
                    @if ($location->latitude !== null && $location->longitude !== null)
                        <a href="https://www.google.com/maps?q={{ $location->latitude }},{{ $location->longitude }}"
                            target="_blank" rel="noopener" class="btn btn-light btn-sm">
                            <i class="ti ti-map-pin me-1"></i> Open in Maps
                        </a>
                    @endif
                </div>
                <div class="card-body">
                    @php
                        $lines = array_filter([
                            $location->address_line1,
                            $location->address_line2,
                            trim(implode(', ', array_filter([
                                $location->city,
                                trim(($location->state ?? '') . ' ' . ($location->zip_code ?? '')),
                            ]))),
                            $location->country,
                        ]);
                    @endphp
                    @if (empty($lines))
                        <p class="text-muted mb-0">No address on file.</p>
                    @else
                        <address class="mb-2">
                            @foreach ($lines as $line)
                                {{ $line }}<br>
                            @endforeach
                        </address>
                    @endif

                    @if ($location->latitude !== null && $location->longitude !== null)
                        <small class="text-muted">
                            <i class="ti ti-current-location fs-xs me-1"></i>
                            {{ $location->latitude }}, {{ $location->longitude }}
                        </small>
                    @endif
                </div>
            </div>

            {{-- Notes --}}
            @if ($location->notes)
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Notes</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0 white-space-pre-wrap" style="white-space: pre-wrap;">{{ $location->notes }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>

</div>

@endsection
