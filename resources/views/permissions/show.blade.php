@extends('layout.app')

@section('title', $permission->name)

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">{{ $permission->name }}</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('permissions.index') }}">Permissions</a></li>
                <li class="breadcrumb-item active">{{ $permission->name }}</li>
            </ol>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-body">
                    <h4 class="mb-1">{{ $permission->name }}</h4>
                    <p class="text-muted mb-2"><code>{{ $permission->slug }}</code></p>
                    <span class="badge badge-soft-primary text-uppercase">{{ $permission->module }}</span>

                    @if ($permission->description)
                        <p class="mt-3 mb-0">{{ $permission->description }}</p>
                    @endif

                    <hr>

                    <dl class="row mb-0">
                        <dt class="col-5 text-muted small">Permission ID</dt>
                        <dd class="col-7 small">#{{ $permission->id }}</dd>

                        <dt class="col-5 text-muted small">Module</dt>
                        <dd class="col-7 small">{{ $permission->module }}</dd>

                        <dt class="col-5 text-muted small">Used by Roles</dt>
                        <dd class="col-7 small">{{ $permission->roles->count() }}</dd>

                        <dt class="col-5 text-muted small">Created</dt>
                        <dd class="col-7 small">{{ optional($permission->created_at)->format('d M Y, h:i A') ?? '—' }}</dd>

                        <dt class="col-5 text-muted small">Last Modified</dt>
                        <dd class="col-7 small">{{ optional($permission->updated_at)->format('d M Y, h:i A') ?? '—' }}</dd>
                    </dl>

                    <div class="mt-3 d-flex gap-2">
                        <a href="{{ route('permissions.edit', $permission) }}" class="btn btn-primary btn-sm">
                            <i class="ti ti-edit me-1"></i> Edit
                        </a>
                        <a href="{{ route('permissions.index') }}" class="btn btn-light btn-sm">Back</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">
                        Roles Holding this Permission
                        <span class="text-muted fs-sm">({{ $permission->roles->count() }})</span>
                    </h5>
                </div>
                <div class="card-body">
                    @if ($permission->roles->isEmpty())
                        <p class="text-muted mb-0">This permission is not attached to any role yet.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr class="text-uppercase fs-xxs text-muted">
                                        <th>Role</th>
                                        <th>Slug</th>
                                        <th class="text-end">Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($permission->roles as $role)
                                        <tr>
                                            <td>
                                                <a href="{{ route('roles.show', $role) }}" class="link-reset">
                                                    {{ $role->name }}
                                                </a>
                                            </td>
                                            <td><code class="text-muted">{{ $role->slug }}</code></td>
                                            <td class="text-end">
                                                @if ($role->is_super)
                                                    <span class="badge badge-soft-danger fs-xxs">Super</span>
                                                @else
                                                    <span class="badge badge-soft-info fs-xxs">Standard</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>

@endsection
