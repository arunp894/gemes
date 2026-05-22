@extends('layout.app')

@section('title', 'Purchases')

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Purchases</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="#">Procurement</a></li>
                <li class="breadcrumb-item active">Purchases</li>
            </ol>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-light justify-content-between">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="app-search">
                            <input id="purchaseSearch" type="search" class="form-control" placeholder="Search invoice / supplier..." />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>

                        <select id="purchaseStatusFilter" class="form-select form-control">
                            <option value="">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="posted">Posted</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="d-flex align-items-center gap-1">
                        <div>
                            <select id="purchasePerPage" class="form-select form-control my-1 my-md-0">
                                <option value="10" selected>10</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>

                        @permission('purchases.create')
                        <a href="{{ route('purchases.create') }}" class="btn btn-primary ms-1">
                            <i class="ti ti-plus fs-sm me-2"></i> New Purchase
                        </a>
                        @endpermission
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="purchasesTable" class="table table-custom table-centered table-hover w-100 mb-0">
                        <thead class="bg-light bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th>Invoice #</th>
                                <th>Date</th>
                                <th>Supplier</th>
                                <th class="text-end">Grand Total</th>
                                <th class="text-end">Due</th>
                                <th>Status</th>
                                <th class="text-center" style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const table = $('#purchasesTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('purchases.data') }}",
            data: function (d) {
                d.status = $('#purchaseStatusFilter').val();
            }
        },
        columns: [
            { data: 'invoice_number',  name: 'invoice_number' },
            { data: 'purchase_date',   name: 'purchase_date' },
            { data: 'supplier_label',  name: 'supplier.name', orderable: false },
            { data: 'grand_total',     name: 'grand_total',  className: 'text-end' },
            { data: 'due_amount',      name: 'due_amount',   className: 'text-end' },
            { data: 'status_badge',    name: 'status' },
            { data: 'actions',         orderable: false, searchable: false, className: 'text-center' },
        ],
        order: [[0, 'desc']],
        pageLength: 10,
    });

    $('#purchaseSearch').on('keyup', function () {
        table.search(this.value).draw();
    });
    $('#purchaseStatusFilter').on('change', () => table.draw());
    $('#purchasePerPage').on('change', function () {
        table.page.len(parseInt(this.value, 10)).draw();
    });

    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    $(document).on('click', '.js-post-purchase', function () {
        const id = this.dataset.id;
        if (!confirm('Post this purchase? Posted purchases cannot be edited.')) return;
        fetch(`/purchases/${id}/post`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        }).then(r => r.json()).then(() => table.draw(false));
    });

    $(document).on('click', '.js-delete-purchase', function () {
        const id = this.dataset.id;
        if (!confirm('Delete this purchase? This cannot be undone.')) return;
        fetch(`/purchases/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        }).then(r => r.json()).then(() => table.draw(false));
    });
})();
</script>
@endpush

@endsection
