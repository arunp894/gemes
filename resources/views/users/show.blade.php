@extends('layout.app')

@section('title', $user->name)

@section('content')

<div class="container-fluid">

    <div class="page-title-head d-flex align-items-center">
        <div class="flex-grow-1">
            <h4 class="page-main-title m-0">{{ $user->name }}</h4>
        </div>
        <div class="text-end">
            <ol class="breadcrumb m-0 py-0">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Paces</a></li>
                <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                <li class="breadcrumb-item active">{{ $user->name }}</li>
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
                            {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                        </span>
                    </div>
                    <h4 class="mb-1">{{ $user->name }}</h4>
                    <p class="text-muted mb-2">{{ $user->email }}</p>

                    @if ($user->is_active)
                        <span class="badge badge-soft-success">Active</span>
                    @else
                        <span class="badge badge-soft-danger">Inactive</span>
                    @endif
                    @if ($user->isSuperAdmin())
                        <span class="badge badge-soft-danger ms-1">Super Admin</span>
                    @endif

                    <hr>

                    <dl class="row text-start mb-0">
                        <dt class="col-5 text-muted small">User ID</dt>
                        <dd class="col-7 small">#{{ $user->id }}</dd>

                        <dt class="col-5 text-muted small">Created</dt>
                        <dd class="col-7 small">{{ optional($user->created_at)->format('d M Y, h:i A') ?? '—' }}</dd>

                        <dt class="col-5 text-muted small">Last Modified</dt>
                        <dd class="col-7 small">{{ optional($user->updated_at)->format('d M Y, h:i A') ?? '—' }}</dd>

                        <dt class="col-5 text-muted small">Email Verified</dt>
                        <dd class="col-7 small">
                            @if ($user->email_verified_at)
                                <i class="ti ti-check text-success"></i> Yes
                            @else
                                <i class="ti ti-x text-muted"></i> No
                            @endif
                        </dd>
                    </dl>

                    @permission('users.edit')
                    <div class="mt-3 d-flex gap-2 justify-content-center">
                        <a href="{{ route('users.edit', $user) }}" class="btn btn-primary btn-sm">
                            <i class="ti ti-edit me-1"></i> Edit
                        </a>
                        <a href="{{ route('users.index') }}" class="btn btn-light btn-sm">Back</a>
                    </div>
                    @endpermission
                </div>
            </div>
        </div>

        {{-- Roles + Effective Permissions --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">Assigned Roles</h5>
                </div>
                <div class="card-body">
                    @if ($user->roles->isEmpty())
                        <p class="text-muted mb-0">No roles assigned.</p>
                    @else
                        <div class="row g-2">
                            @foreach ($user->roles as $role)
                                <div class="col-md-6">
                                    <div class="border rounded p-2 d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0">
                                                <a href="{{ route('roles.show', $role) }}" class="link-reset">
                                                    {{ $role->name }}
                                                </a>
                                                @if ($role->is_super)
                                                    <span class="badge badge-soft-danger fs-xxs ms-1">Super</span>
                                                @endif
                                            </h6>
                                            <small class="text-muted"><code>{{ $role->slug }}</code></small>
                                        </div>
                                        <span class="badge badge-soft-secondary">
                                            {{ $role->is_super ? 'All' : $role->permissions->count() }} perms
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-0">
                        Effective Permissions
                        @if ($user->isSuperAdmin())
                            <span class="badge badge-soft-danger fs-xxs ms-1">Bypassed by Super Role</span>
                        @else
                            <span class="text-muted fs-sm">({{ $permissionSlugs->count() }})</span>
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    @if ($user->isSuperAdmin())
                        <p class="text-muted mb-0">This user holds a super role and bypasses every permission check at runtime.</p>
                    @elseif ($permissionSlugs->isEmpty())
                        <p class="text-muted mb-0">No effective permissions. Assign roles to grant access.</p>
                    @else
                        <div class="d-flex flex-wrap gap-1">
                            @foreach ($permissionSlugs as $slug)
                                <code class="badge bg-light text-dark border">{{ $slug }}</code>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>

@endsection
