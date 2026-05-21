<div id="sidenav-menu">
    <ul class="side-nav">
        <li class="side-nav-title mt-2" data-lang="main">Main</li>
        <li class="side-nav-item">
            <a data-bs-toggle="collapse" href="#dashboards" aria-expanded="false" aria-controls="dashboards"
                class="side-nav-link">
                <span class="menu-icon"><i class="ti ti-dashboard"></i></span>
                <span class="menu-text" data-lang="dashboards">Dashboards</span>
                <span class="menu-arrow"></span>
            </a>
            <div class="collapse" id="dashboards">
                <ul class="sub-menu">
                    <li class="side-nav-item">
                        <a href="index.html" class="side-nav-link">
                            <span class="menu-text" data-lang="dashboard-ecommerce">Ecommerce</span>
                        </a>
                    </li>
                    <li class="side-nav-item">
                        <a href="dashboard-analytics.html" class="side-nav-link">
                            <span class="menu-text" data-lang="dashboard-analytics">Analytics</span>
                        </a>
                    </li>
                    <li class="side-nav-item">
                        <a href="dashboard-crm.html" class="side-nav-link">
                            <span class="menu-text" data-lang="dashboard-crm">CRM</span>
                        </a>
                    </li>
                    <li class="side-nav-item">
                        <a href="dashboard-finance.html" class="side-nav-link">
                            <span class="menu-text" data-lang="dashboard-finance">Finance</span>
                        </a>
                    </li>
                    <li class="side-nav-item">
                        <a href="dashboard-projects.html" class="side-nav-link">
                            <span class="menu-text" data-lang="dashboard-projects">Projects</span>
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="side-nav-item {{ request()->routeIs('categories.*') || request()->routeIs('subcategories.*') || request()->routeIs('products.*') || request()->routeIs('website-visibility.*') ? 'menuitem-active' : '' }}">

            <a data-bs-toggle="collapse" href="#catalogueMenu"
                aria-expanded="{{ request()->routeIs('categories.*') || request()->routeIs('subcategories.*') || request()->routeIs('products.*') || request()->routeIs('website-visibility.*') ? 'true' : 'false' }}"
                aria-controls="catalogueMenu" class="side-nav-link">

                <span class="menu-icon">
                    <i class="ti ti-package"></i>
                </span>

                <span class="menu-text">
                    Models
                </span>
                <span class="menu-arrow"></span>
            </a>

            <div class="collapse {{ request()->routeIs('categories.*') || request()->routeIs('subcategories.*') || request()->routeIs('products.*') || request()->routeIs('website-visibility.*') ? 'show' : '' }}"
                id="catalogueMenu">

                <ul class="sub-menu">

                    <li class="side-nav-item {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                        <a href="{{ route('categories.index') }}" class="side-nav-link">

                            <span class="menu-text"> Categories</span>
                            <span class="badge bg-success text-white">4</span>
                        </a>
                    </li>

                    <li class="side-nav-item {{ request()->routeIs('products.*') ? 'active' : '' }}">
                        <a href="{{ \Illuminate\Support\Facades\Route::has('products.index')
                    ? route('products.index')
                    : url('/products') }}" class="side-nav-link">

                            <span class="menu-text">
                                Products
                            </span>
                        </a>
                    </li>

                </ul>
            </div>
        </li>

        {{-- =========================
    ADMINISTRATION MENU
========================= --}}

        @if (auth()->check() && (auth()->user()->hasAnyPermission(['users.view', 'roles.view']) ||
        auth()->user()->hasRole('admin') || auth()->user()->isSuperAdmin()))
        <li class="side-nav-title mt-2">Administration</li>

        <li
            class="side-nav-item {{ request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('permissions.*') ? 'menuitem-active' : '' }}">

            <a data-bs-toggle="collapse" href="#adminMenu"
                aria-expanded="{{ request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('permissions.*') ? 'true' : 'false' }}"
                aria-controls="adminMenu" class="side-nav-link">

                <span class="menu-icon">
                    <i class="ti ti-shield-lock"></i>
                </span>

                <span class="menu-text">
                    Administration
                </span>

                <span class="menu-arrow"></span>
            </a>

            <div class="collapse {{ request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('permissions.*') ? 'show' : '' }}"
                id="adminMenu">

                <ul class="sub-menu">

                    {{-- Users --}}
                    @permission('users.view')
                    <li class="side-nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <a href="{{ route('users.index') }}" class="side-nav-link">
                            <span class="menu-text">
                                Users
                            </span>
                        </a>
                    </li>
                    @endpermission

                    {{-- Roles --}}
                    @permission('roles.view')
                    <li class="side-nav-item {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                        <a href="{{ route('roles.index') }}" class="side-nav-link">
                            <span class="menu-text">
                                Roles
                            </span>
                        </a>
                    </li>
                    @endpermission

                    {{-- Permissions --}}
                    @role('admin')
                    <li class="side-nav-item {{ request()->routeIs('permissions.*') ? 'active' : '' }}">
                        <a href="{{ route('permissions.index') }}" class="side-nav-link">
                            <span class="menu-text">
                                Permissions
                            </span>
                        </a>
                    </li>
                    @endrole

                </ul>
            </div>
        </li>
        @endif
    </ul>
</div>
