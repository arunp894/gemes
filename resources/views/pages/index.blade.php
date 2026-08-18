@extends('layout.app')

@section('title', 'Pages')

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">Pages</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">Settings</a></li>
                <li class="breadcrumb-item active">Pages</li>
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
                <div class="card-header border-light d-flex justify-content-between align-items-center">
                    <p class="text-muted fs-xs mb-0">
                        Static content pages rendered on the storefront at <code>/pages/&#123;slug&#125;</code>
                        — e.g. About Us and Terms &amp; Conditions in the site footer.
                    </p>
                    @permission('pages.create')
                    <a href="{{ route('pages.create') }}" class="btn btn-primary btn-sm">
                        <i class="ti ti-plus fs-sm me-1"></i> Add Page
                    </a>
                    @endpermission
                </div>

                <div class="table-responsive">
                    <table class="table table-striped dt-responsive align-middle mb-0">
                        <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th>Title</th>
                                <th>Slug</th>
                                <th>Last Updated</th>
                                <th class="text-center" style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pages as $page)
                                <tr>
                                    <td class="fw-medium">{{ $page->title }}</td>
                                    <td><code class="text-muted">/pages/{{ $page->slug }}</code></td>
                                    <td>{{ optional($page->updated_at)->format('d M, Y') ?? '—' }}</td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="{{ route('website.pages.show', $page) }}" target="_blank"
                                                class="btn btn-default btn-icon btn-sm" title="View on Site">
                                                <i class="ti ti-external-link fs-lg"></i>
                                            </a>
                                            @permission('pages.edit')
                                            <a href="{{ route('pages.edit', $page) }}" class="btn btn-default btn-icon btn-sm" title="Edit">
                                                <i class="ti ti-edit fs-lg"></i>
                                            </a>
                                            @endpermission
                                            @permission('pages.delete')
                                            <button type="button" class="btn btn-default btn-icon btn-sm js-delete text-danger"
                                                data-url="{{ route('pages.destroy', $page) }}" data-name="{{ e($page->title) }}" title="Delete">
                                                <i class="ti ti-trash fs-lg"></i>
                                            </button>
                                            @endpermission
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No pages yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
    $(function () {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        $('.js-delete').on('click', function () {
            const url  = $(this).data('url');
            const name = $(this).data('name');
            if (!confirm('Delete "' + name + '"? (This is a soft delete.)')) return;
            $.ajax({
                url: url,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    if (res.success) location.reload();
                    else alert(res.message || 'Could not delete page.');
                },
                error: function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to delete page.';
                    alert(msg);
                },
            });
        });
    });
</script>
@endpush
