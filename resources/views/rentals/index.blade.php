@extends('layout.app')

@section('title', 'Stone Rent')

@section('content')
<div class="container-fluid rentals-page">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Stone Rent</h4>
        </div>
        <div class="text-end d-flex align-items-center gap-3">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Sales</a></li>
                <li class="breadcrumb-item active">Stone Rent</li>
            </ol>
            @permission('rentals.create')
                <a href="{{ route('rentals.create') }}" class="btn btn-primary btn-sm"><i class="ti ti-plus me-1"></i> New Rental</a>
            @endpermission
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- Overdue alert: stones that should have been back already --}}
    @if ($stats['overdue'] > 0)
        <div class="alert alert-danger d-flex align-items-center justify-content-between" role="alert">
            <div><i class="ti ti-alert-triangle me-2"></i>
                <strong>{{ $stats['overdue'] }}</strong> {{ \Illuminate\Support\Str::plural('rental', $stats['overdue']) }} overdue &mdash; stones not returned by the due date.</div>
            <button type="button" class="btn btn-sm btn-danger js-tab" data-tab="overdue">View overdue</button>
        </div>
    @endif

    <div class="row g-3 mb-3">
        @foreach ([['Total', $stats['total'], 'ti-list', 'primary'], ['Active', $stats['active'], 'ti-clock-hour-4', 'info'], ['Overdue', $stats['overdue'], 'ti-alert-triangle', 'danger'], ['Returned / Closed', $stats['returned'], 'ti-circle-check', 'success']] as [$label, $val, $icon, $color])
            <div class="col-6 col-lg-3">
                <div class="card mb-0"><div class="card-body d-flex align-items-center gap-3 py-3">
                    <span class="avatar-sm rounded bg-{{ $color }}-subtle text-{{ $color }} d-flex align-items-center justify-content-center fs-4" style="width:44px;height:44px"><i class="ti {{ $icon }}"></i></span>
                    <div><div class="fs-4 fw-semibold">{{ $val }}</div><div class="text-muted small">{{ $label }}</div></div>
                </div></div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header border-bottom">
            <ul class="nav nav-pills" id="rentalTabs">
                <li class="nav-item"><a class="nav-link active js-tab" data-tab="all" href="#">All</a></li>
                <li class="nav-item"><a class="nav-link js-tab" data-tab="active" href="#">Active</a></li>
                <li class="nav-item"><a class="nav-link js-tab" data-tab="overdue" href="#">Overdue
                    @if ($stats['overdue'] > 0)<span class="badge bg-danger ms-1">{{ $stats['overdue'] }}</span>@endif</a></li>
                <li class="nav-item"><a class="nav-link js-tab" data-tab="returned" href="#">Returned</a></li>
            </ul>
        </div>
        <div class="card-body">
            <table id="rentalsTable" class="table table-hover align-middle w-100">
                <thead>
                    <tr>
                        <th>#</th><th>Rent No</th><th>Customer</th><th>Location</th><th>From</th><th>To</th>
                        <th>Stones</th><th class="text-end">Rent</th><th class="text-end">Deposit Held</th><th>Status</th><th></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    let tab = 'all';
    const dt = $('#rentalsTable').DataTable({
        processing: true, serverSide: true, order: [[4, 'desc']], pageLength: 15,
        ajax: { url: '{{ route('rentals.data') }}', data: d => { d.tab = tab; } },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'rent_number', name: 'rent_number' },
            { data: 'customer_label', name: 'customer_label', orderable: false },
            { data: 'location_label', orderable: false, searchable: false },
            { data: 'start_at', name: 'start_at', searchable: false },
            { data: 'due_at', name: 'due_at', searchable: false },
            { data: 'stones', orderable: false, searchable: false },
            { data: 'rent_total', name: 'rent_total', className: 'text-end', searchable: false },
            { data: 'deposit_held', orderable: false, searchable: false, className: 'text-end' },
            { data: 'status_badge', orderable: false, searchable: false },
            { data: 'actions', orderable: false, searchable: false },
        ],
    });
    $(document).on('click', '.js-tab', function (e) {
        e.preventDefault();
        tab = $(this).data('tab');
        $('#rentalTabs .nav-link').removeClass('active').filter('[data-tab="' + tab + '"]').addClass('active');
        dt.draw();
    });
});
</script>
@endpush
