@extends('layout.app')

@section('title', $channel->name)

@section('content')
<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">
                @if ($channel->icon)
                    <i class="{{ $channel->icon }} text-primary me-2"></i>
                @endif
                {{ $channel->name }}
                <span class="badge {{ $channel->isActive() ? 'badge-soft-success' : 'badge-soft-secondary' }} ms-2">{{ $channel->statusLabel() }}</span>
            </h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('channels.index') }}">Channels</a></li>
                <li class="breadcrumb-item active">{{ $channel->name }}</li>
            </ol>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header border-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Details</h5>
                    @permission('channels.edit')
                    <a href="{{ route('channels.edit', $channel) }}" class="btn btn-sm btn-soft-primary">
                        <i class="ti ti-edit me-1"></i> Edit
                    </a>
                    @endpermission
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Name</dt>
                        <dd class="col-7 fw-semibold">{{ $channel->name }}</dd>

                        <dt class="col-5 text-muted">Code</dt>
                        <dd class="col-7"><code>{{ $channel->code }}</code></dd>

                        <dt class="col-5 text-muted">Icon</dt>
                        <dd class="col-7">
                            @if ($channel->icon)
                                <i class="{{ $channel->icon }} fs-lg me-1"></i>
                                <code>{{ $channel->icon }}</code>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </dd>

                        <dt class="col-5 text-muted">Display Order</dt>
                        <dd class="col-7">{{ $channel->display_order }}</dd>

                        <dt class="col-5 text-muted">Status</dt>
                        <dd class="col-7">
                            <span class="badge {{ $channel->isActive() ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                {{ $channel->statusLabel() }}
                            </span>
                        </dd>

                        <dt class="col-5 text-muted">Total Sales</dt>
                        <dd class="col-7 fw-bold">{{ $channel->sales_count }}</dd>

                        <dt class="col-5 text-muted">Created</dt>
                        <dd class="col-7">{{ optional($channel->created_at)->format('d M Y') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Recent Sales via this channel</h5>
                </div>
                <div class="card-body p-0">
                    @php $recentSales = $channel->sales()->with('customer:id,name,company_name')->latest()->limit(10)->get(); @endphp
                    @if ($recentSales->isEmpty())
                        <p class="text-muted text-center py-4 mb-0">No sales recorded for this channel yet.</p>
                    @else
                        <table class="table table-custom table-hover mb-0">
                            <thead class="thead-sm text-uppercase fs-xxs bg-light bg-opacity-25">
                                <tr>
                                    <th>Sale #</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentSales as $sale)
                                <tr>
                                    <td>
                                        <a href="{{ route('sales.show', $sale) }}" class="link-reset">
                                            <code>{{ $sale->sale_number }}</code>
                                        </a>
                                    </td>
                                    <td>{{ optional($sale->sale_date)->format('d M Y') }}</td>
                                    <td>{{ optional($sale->customer)->display_name ?? '—' }}</td>
                                    <td class="text-end fw-semibold">{{ number_format((float) $sale->grand_total, 2) }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $sale->statusBadgeClass() }} fs-xxs">{{ $sale->statusLabel() }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if ($channel->sales_count > 10)
                            <p class="text-muted text-center small py-2 mb-0">Showing last 10 of {{ $channel->sales_count }} sales.</p>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
