@extends('layout.app')

@section('title', $supplier->display_name)

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">{{ $supplier->display_name }}</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('suppliers.index') }}">Suppliers</a></li>
                <li class="breadcrumb-item active">{{ $supplier->display_name }}</li>
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
                            {{ strtoupper(mb_substr($supplier->display_name, 0, 1)) }}
                        </span>
                    </div>
                    <h4 class="mb-1">{{ $supplier->display_name }}</h4>
                    @if ($supplier->company_name && $supplier->name !== $supplier->company_name)
                        <p class="text-muted mb-2">Contact: {{ $supplier->name }}</p>
                    @endif

                    <p class="mb-2"><code>{{ $supplier->supplier_code }}</code></p>

                    <span class="badge {{ $supplier->statusBadgeClass() }}">{{ $supplier->statusLabel() }}</span>

                    <hr>

                    <dl class="row text-start mb-0">
                        <dt class="col-5 text-muted small">ID</dt>
                        <dd class="col-7 small">#{{ $supplier->id }}</dd>

                        <dt class="col-5 text-muted small">Created</dt>
                        <dd class="col-7 small">
                            {{ optional($supplier->created_at)->format('d M Y, h:i A') ?? '—' }}
                        </dd>

                        <dt class="col-5 text-muted small">Modified</dt>
                        <dd class="col-7 small">
                            {{ optional($supplier->updated_at)->format('d M Y, h:i A') ?? '—' }}
                        </dd>

                        @if ($supplier->creator)
                            <dt class="col-5 text-muted small">Created By</dt>
                            <dd class="col-7 small">{{ $supplier->creator->name }}</dd>
                        @endif
                        @if ($supplier->updater)
                            <dt class="col-5 text-muted small">Updated By</dt>
                            <dd class="col-7 small">{{ $supplier->updater->name }}</dd>
                        @endif
                    </dl>

                    @permission('suppliers.edit')
                    <div class="mt-3 d-flex gap-2 justify-content-center">
                        <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-primary btn-sm">
                            <i class="ti ti-edit me-1"></i> Edit
                        </a>
                        <a href="{{ route('suppliers.index') }}" class="btn btn-light btn-sm">Back</a>
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
                        <dt class="col-sm-3 text-muted small">Email</dt>
                        <dd class="col-sm-9">
                            @if ($supplier->email)
                                <a href="mailto:{{ $supplier->email }}">{{ $supplier->email }}</a>
                            @else <span class="text-muted">—</span>
                            @endif
                        </dd>

                        <dt class="col-sm-3 text-muted small">Phone</dt>
                        <dd class="col-sm-9">{{ $supplier->phone }}</dd>

                        <dt class="col-sm-3 text-muted small">Alt. Phone</dt>
                        <dd class="col-sm-9">{{ $supplier->alternate_phone ?? '—' }}</dd>

                        <dt class="col-sm-3 text-muted small">Website</dt>
                        <dd class="col-sm-9">
                            @if ($supplier->website)
                                <a href="{{ $supplier->website }}" target="_blank" rel="noopener">
                                    {{ $supplier->website }} <i class="ti ti-external-link fs-xs"></i>
                                </a>
                            @else <span class="text-muted">—</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>

            {{-- Tax & Compliance --}}
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Tax &amp; Compliance</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3 text-muted small">GST Number</dt>
                        <dd class="col-sm-9">{{ $supplier->gst_number ?? '—' }}</dd>

                        <dt class="col-sm-3 text-muted small">Tax Number</dt>
                        <dd class="col-sm-9">{{ $supplier->tax_number ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            {{-- Address --}}
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Address</h5>
                </div>
                <div class="card-body">
                    @php
                        $parts = array_filter([
                            $supplier->address,
                            $supplier->city,
                            $supplier->state . ($supplier->zip_code ? ' ' . $supplier->zip_code : ''),
                            $supplier->country,
                        ]);
                    @endphp
                    @if (empty($parts))
                        <p class="text-muted mb-0">No address on file.</p>
                    @else
                        <address class="mb-0">
                            @foreach ($parts as $line)
                                {{ $line }}<br>
                            @endforeach
                        </address>
                    @endif
                </div>
            </div>

            {{-- Financial --}}
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Financial</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="border rounded p-3">
                                <small class="text-muted d-block">Opening Balance</small>
                                <span class="fs-4 fw-bold">
                                    ${{ number_format((float) $supplier->opening_balance, 2) }}
                                </span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="border rounded p-3">
                                <small class="text-muted d-block">Credit Limit</small>
                                <span class="fs-4 fw-bold">
                                    ${{ number_format((float) $supplier->credit_limit, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection
