@extends('layout.app')

@section('title', $customer->display_name)

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">{{ $customer->display_name }}</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
                <li class="breadcrumb-item active">{{ $customer->display_name }}</li>
            </ol>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar-lg mx-auto mb-3">
                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fw-bold fs-1">
                            {{ strtoupper(mb_substr($customer->display_name, 0, 1)) }}
                        </span>
                    </div>
                    <h4 class="mb-1">{{ $customer->display_name }}</h4>
                    @if ($customer->company_name && $customer->name !== $customer->company_name)
                        <p class="text-muted mb-2">Contact: {{ $customer->name }}</p>
                    @endif
                    <p class="mb-2"><code>{{ $customer->customer_code }}</code></p>

                    <div class="d-flex justify-content-center gap-1 flex-wrap">
                        <span class="badge {{ $customer->typeBadgeClass() }}">{{ $customer->typeLabel() }}</span>
                        <span class="badge {{ $customer->statusBadgeClass() }}">{{ $customer->statusLabel() }}</span>
                    </div>

                    <hr>

                    <dl class="row text-start mb-0">
                        <dt class="col-5 text-muted small">ID</dt>
                        <dd class="col-7 small">#{{ $customer->id }}</dd>
                        <dt class="col-5 text-muted small">Created</dt>
                        <dd class="col-7 small">{{ optional($customer->created_at)->format('d M Y, h:i A') ?? '—' }}</dd>
                        <dt class="col-5 text-muted small">Modified</dt>
                        <dd class="col-7 small">{{ optional($customer->updated_at)->format('d M Y, h:i A') ?? '—' }}</dd>
                        @if ($customer->creator)
                            <dt class="col-5 text-muted small">Created By</dt>
                            <dd class="col-7 small">{{ $customer->creator->name }}</dd>
                        @endif
                    </dl>

                    @permission('customers.edit')
                    <div class="mt-3 d-flex gap-2 justify-content-center">
                        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-primary btn-sm">
                            <i class="ti ti-edit me-1"></i> Edit
                        </a>
                        <a href="{{ route('customers.index') }}" class="btn btn-light btn-sm">Back</a>
                    </div>
                    @endpermission
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-light"><h5 class="card-title mb-0">Contact</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3 text-muted small">Phone</dt>
                        <dd class="col-sm-9">{{ $customer->phone ?? '—' }}</dd>
                        <dt class="col-sm-3 text-muted small">Alt. Phone</dt>
                        <dd class="col-sm-9">{{ $customer->alternate_phone ?? '—' }}</dd>
                        <dt class="col-sm-3 text-muted small">Email</dt>
                        <dd class="col-sm-9">
                            @if ($customer->email) <a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a>
                            @else <span class="text-muted">—</span> @endif
                        </dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-light"><h5 class="card-title mb-0">Tax / KYC</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3 text-muted small">GST Number</dt>
                        <dd class="col-sm-9">{{ $customer->gst_number ?? '—' }}</dd>
                        <dt class="col-sm-3 text-muted small">PAN Number</dt>
                        <dd class="col-sm-9">{{ $customer->pan_number ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header border-light"><h5 class="card-title mb-0">Address</h5></div>
                <div class="card-body">
                    @php
                        $parts = array_filter([
                            $customer->address_line1, $customer->address_line2,
                            trim(implode(', ', array_filter([$customer->city, trim(($customer->state ?? '') . ' ' . ($customer->zip_code ?? ''))]))),
                            $customer->country,
                        ]);
                    @endphp
                    @if (empty($parts))
                        <p class="text-muted mb-0">No address on file.</p>
                    @else
                        <address class="mb-0">@foreach ($parts as $line){{ $line }}<br>@endforeach</address>
                    @endif
                </div>
            </div>

            @if ($recentSales && $recentSales->count() > 0)
            <div class="card">
                <div class="card-header border-light"><h5 class="card-title mb-0">Recent Sales</h5></div>
                <div class="table-responsive">
                    <table class="table table-custom align-middle mb-0">
                        <thead class="bg-light bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th>#</th>
                                <th>Sale</th>
                                <th>Date</th>
                                <th class="text-end">Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentSales as $sale)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><a href="{{ route('sales.show', $sale) }}"><code>{{ $sale->sale_number }}</code></a></td>
                                    <td>{{ optional($sale->sale_date)->format('d M Y') }}</td>
                                    <td class="text-end">{{ number_format((float) $sale->grand_total, 2) }}</td>
                                    <td><span class="badge {{ $sale->statusBadgeClass() }}">{{ $sale->statusLabel() }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            @if ($customer->notes)
            <div class="card">
                <div class="card-header border-light"><h5 class="card-title mb-0">Notes</h5></div>
                <div class="card-body">
                    <p class="mb-0" style="white-space: pre-wrap;">{{ $customer->notes }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>

</div>

@endsection
