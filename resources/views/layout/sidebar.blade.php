<div id="sidenav-menu">
    <ul class="side-nav">
        <li class="side-nav-title mt-2" data-lang="main">Main</li>
        <li class="side-nav-item">
            <a href="{{ route('dashboard') }}" class="side-nav-link">
                <span class="menu-icon"><i class="ti ti-dashboard"></i></span>
                <span class="menu-text" data-lang="dashboards">Dashboards</span>               
            </a>            
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
                            {{-- <span class="badge bg-success text-white">4</span> --}}
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
    PROCUREMENT MENU
========================= --}}

        @permission('suppliers.view')
        <li class="side-nav-item {{ request()->routeIs('suppliers.*') || request()->routeIs('racks.*') || request()->routeIs('purchases.*') ? 'menuitem-active' : '' }}">

            <a data-bs-toggle="collapse" href="#procurementMenu"
                aria-expanded="{{ request()->routeIs('suppliers.*') || request()->routeIs('racks.*') || request()->routeIs('purchases.*') ? 'true' : 'false' }}"
                aria-controls="procurementMenu" class="side-nav-link">

                <span class="menu-icon">
                    <i class="ti ti-truck-delivery"></i>
                </span>

                <span class="menu-text">
                    Procurement
                </span>
                <span class="menu-arrow"></span>
            </a>

            <div class="collapse {{ request()->routeIs('suppliers.*') || request()->routeIs('racks.*') || request()->routeIs('purchases.*') ? 'show' : '' }}"
                id="procurementMenu">

                <ul class="sub-menu">

                    <li class="side-nav-item {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                        <a href="{{ route('suppliers.index') }}" class="side-nav-link">
                            <span class="menu-text">Suppliers</span>
                        </a>
                    </li>

                    @permission('racks.view')
                    <li class="side-nav-item {{ request()->routeIs('racks.*') ? 'active' : '' }}">
                        <a href="{{ route('racks.index') }}" class="side-nav-link">
                            <span class="menu-text">Racks</span>
                        </a>
                    </li>
                    @endpermission

                    @permission('purchases.view')
                    <li class="side-nav-item {{ request()->routeIs('purchases.*') ? 'active' : '' }}">
                        <a href="{{ route('purchases.index') }}" class="side-nav-link">
                            <span class="menu-text">Purchases</span>
                        </a>
                    </li>
                    @endpermission

                </ul>
            </div>
        </li>
        @endpermission

        {{-- =========================
    SALES MENU
========================= --}}

        @if (auth()->check() && auth()->user()->hasAnyPermission(['sales.view', 'customers.view']))
        <li class="side-nav-item {{ request()->routeIs('sales.*') || request()->routeIs('customers.*') ? 'menuitem-active' : '' }}">

            <a data-bs-toggle="collapse" href="#salesMenu"
                aria-expanded="{{ request()->routeIs('sales.*') || request()->routeIs('customers.*') ? 'true' : 'false' }}"
                aria-controls="salesMenu" class="side-nav-link">

                <span class="menu-icon">
                    <i class="ti ti-cash-register"></i>
                </span>

                <span class="menu-text">
                    Sales
                </span>
                <span class="menu-arrow"></span>
            </a>

            <div class="collapse {{ request()->routeIs('sales.*') || request()->routeIs('customers.*') ? 'show' : '' }}"
                id="salesMenu">

                <ul class="sub-menu">

                    @permission('sales.create')
                    <li class="side-nav-item {{ request()->routeIs('sales.create') ? 'active' : '' }}">
                        <a href="{{ route('sales.create') }}" class="side-nav-link">
                            <span class="menu-text">
                                <i class="ti ti-bolt fs-sm text-warning me-1"></i> Terminal
                            </span>
                        </a>
                    </li>
                    @endpermission

                    @permission('sales.view')
                    <li class="side-nav-item {{ request()->routeIs('sales.index') || request()->routeIs('sales.show') || request()->routeIs('sales.edit') ? 'active' : '' }}">
                        <a href="{{ route('sales.index') }}" class="side-nav-link">
                            <span class="menu-text">Invoices</span>
                        </a>
                    </li>
                    @endpermission

                    @permission('customers.view')
                    <li class="side-nav-item {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                        <a href="{{ route('customers.index') }}" class="side-nav-link">
                            <span class="menu-text">Customers</span>
                        </a>
                    </li>
                    @endpermission

                </ul>
            </div>
        </li>
        @endif

        {{-- =========================
    INVENTORY MENU
========================= --}}

        @if (auth()->check() && auth()->user()->hasAnyPermission(['stock.view', 'stock-transfers.view']))
        <li class="side-nav-item {{ request()->routeIs('stock.*') || request()->routeIs('stock-transfers.*') ? 'menuitem-active' : '' }}">

            <a data-bs-toggle="collapse" href="#inventoryMenu"
                aria-expanded="{{ request()->routeIs('stock.*') || request()->routeIs('stock-transfers.*') ? 'true' : 'false' }}"
                aria-controls="inventoryMenu" class="side-nav-link">

                <span class="menu-icon">
                    <i class="ti ti-packages"></i>
                </span>

                <span class="menu-text">
                    Inventory
                </span>
                <span class="menu-arrow"></span>
            </a>

            <div class="collapse {{ request()->routeIs('stock.*') || request()->routeIs('stock-transfers.*') ? 'show' : '' }}"
                id="inventoryMenu">

                <ul class="sub-menu">

                    @permission('stock.view')
                    <li class="side-nav-item {{ request()->routeIs('stock.*') ? 'active' : '' }}">
                        <a href="{{ route('stock.index') }}" class="side-nav-link">
                            <span class="menu-text">Stock Report</span>
                        </a>
                    </li>
                    @endpermission

                    @permission('stock-transfers.view')
                    <li class="side-nav-item {{ request()->routeIs('stock-transfers.*') ? 'active' : '' }}">
                        <a href="{{ route('stock-transfers.index') }}" class="side-nav-link">
                            <span class="menu-text">Transfers</span>
                        </a>
                    </li>
                    @endpermission

                </ul>
            </div>
        </li>
        @endif

        {{-- =========================
    OPERATIONS MENU (sales venues)
========================= --}}

        @permission('locations.view')
        <li class="side-nav-item {{ request()->routeIs('locations.*') ? 'menuitem-active' : '' }}">

            <a data-bs-toggle="collapse" href="#operationsMenu"
                aria-expanded="{{ request()->routeIs('locations.*') ? 'true' : 'false' }}"
                aria-controls="operationsMenu" class="side-nav-link">

                <span class="menu-icon">
                    <i class="ti ti-building-store"></i>
                </span>

                <span class="menu-text">
                    Operations
                </span>
                <span class="menu-arrow"></span>
            </a>

            <div class="collapse {{ request()->routeIs('locations.*') ? 'show' : '' }}"
                id="operationsMenu">

                <ul class="sub-menu">

                    <li class="side-nav-item {{ request()->routeIs('locations.*') ? 'active' : '' }}">
                        <a href="{{ route('locations.index') }}" class="side-nav-link">
                            <span class="menu-text">Locations</span>
                        </a>
                    </li>

                </ul>
            </div>
        </li>
        @endpermission

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
