@extends('layout.app')

@section('title', $role->name)

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">{{ $role->name }}</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Paces</a></li>
                <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles</a></li>
                <li class="breadcrumb-item active">{{ $role->name }}</li>
            </ol>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="mb-1">
                        {{ $role->name }}
                        @if ($role->is_super)
                            <span class="badge badge-soft-danger ms-1">Super</span>
                        @endif
                    </h4>
                    <p class="text-muted mb-2"><code>{{ $role->slug }}</code></p>

                    @if ($role->description)
                        <p class="mb-3">{{ $role->description }}</p>
                    @endif

                    <hr>

                    <dl class="row mb-0">
                        <dt class="col-5 text-muted small">Role ID</dt>
                        <dd class="col-7 small">#{{ $role->id }}</dd>

                        <dt class="col-5 text-muted small">Users</dt>
                        <dd class="col-7 small">{{ $role->users->count() }}</dd>

                        <dt class="col-5 text-muted small">Permissions</dt>
                        <dd class="col-7 small">
                            {{ $role->is_super ? 'All (super bypass)' : $role->permissions->count() }}
                        </dd>

                        <dt class="col-5 text-muted small">Created</dt>
                        <dd class="col-7 small">{{ optional($role->created_at)->format('d M Y, h:i A') ?? '—' }}</dd>

                        <dt class="col-5 text-muted small">Last Modified</dt>
                        <dd class="col-7 small">{{ optional($role->updated_at)->format('d M Y, h:i A') ?? '—' }}</dd>
                    </dl>

                    @permission('roles.edit')
                    <div class="mt-3 d-flex gap-2">
                        <a href="{{ route('roles.edit', $role) }}" class="btn btn-primary btn-sm">
                            <i class="ti ti-edit me-1"></i> Edit
                        </a>
                        <a href="{{ route('roles.index') }}" class="btn btn-light btn-sm">Back</a>
                    </div>
                    @endpermission
                </div>
            </div>

            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Users with this Role <span class="text-muted fs-sm">({{ $role->users->count() }})</span></h5>
                </div>
                <div class="card-body">
                    @if ($role->users->isEmpty())
                        <p class="text-muted mb-0">No users hold this role yet.</p>
                    @else
                        <ul class="list-unstyled mb-0">
                            @foreach ($role->users as $u)
                                <li class="mb-2">
                                    <a href="{{ route('users.show', $u) }}" class="link-reset">
                                        <i class="ti ti-user me-1"></i> {{ $u->name }}
                                    </a>
                                    <small class="text-muted d-block ms-3">{{ $u->email }}</small>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Granted Permissions</h5>
                </div>
                <div class="card-body">
                    @if ($role->is_super)
                        <div class="alert alert-danger mb-0">
                            <i class="ti ti-shield-lock me-1"></i>
                            <strong>Super Role.</strong> Bypasses every permission check at runtime — effectively grants <em>all</em> permissions, present and future.
                        </div>
                    @elseif ($groupedPermissions->isEmpty())
                        <p class="text-muted mb-0">No permissions granted. Users with this role can only access auth-only routes.</p>
                    @else
                        @foreach ($groupedPermissions as $module => $perms)
                            <div class="mb-3">
                                <h6 class="text-uppercase text-muted mb-2">{{ $module }}</h6>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach ($perms as $perm)
                                        <span class="badge badge-soft-info" title="{{ $perm->slug }}">
                                            {{ $perm->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>

@endsection
