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

        @if (auth()->check() && auth()->user()->hasAnyPermission(['sales.view', 'sales.create', 'sales.import', 'customers.view']))
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

                    @permission('sales.import')
                    <li class="side-nav-item {{ request()->routeIs('sales.import*') ? 'active' : '' }}">
                        <a href="{{ route('sales.import') }}" class="side-nav-link">
                            <span class="menu-text">
                                <i class="ti ti-file-upload fs-sm text-info me-1"></i> Import
                            </span>
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
        <li class="side-nav-item {{ request()->routeIs('stock.*') || request()->routeIs('stock-transfers.*') || request()->routeIs('barcode-history.*') ? 'menuitem-active' : '' }}">

            <a data-bs-toggle="collapse" href="#inventoryMenu"
                aria-expanded="{{ request()->routeIs('stock.*') || request()->routeIs('stock-transfers.*') || request()->routeIs('barcode-history.*') ? 'true' : 'false' }}"
                aria-controls="inventoryMenu" class="side-nav-link">

                <span class="menu-icon">
                    <i class="ti ti-packages"></i>
                </span>

                <span class="menu-text">
                    Inventory
                </span>
                <span class="menu-arrow"></span>
            </a>

            <div class="collapse {{ request()->routeIs('stock.*') || request()->routeIs('stock-transfers.*') || request()->routeIs('barcode-history.*') ? 'show' : '' }}"
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

                    @permission('stock.view')
                    <li class="side-nav-item {{ request()->routeIs('barcode-history.*') ? 'active' : '' }}">
                        <a href="{{ route('barcode-history.index') }}" class="side-nav-link">
                            <span class="menu-text">
                                <i class="ti ti-barcode fs-sm text-info me-1"></i> Barcode History
                            </span>
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
        <li class="side-nav-item {{ request()->routeIs('locations.*') || request()->routeIs('channels.*') ? 'menuitem-active' : '' }}">

            <a data-bs-toggle="collapse" href="#operationsMenu"
                aria-expanded="{{ request()->routeIs('locations.*') || request()->routeIs('channels.*') ? 'true' : 'false' }}"
                aria-controls="operationsMenu" class="side-nav-link">

                <span class="menu-icon">
                    <i class="ti ti-building-store"></i>
                </span>

                <span class="menu-text">
                    Operations
                </span>
                <span class="menu-arrow"></span>
            </a>

            <div class="collapse {{ request()->routeIs('locations.*') || request()->routeIs('channels.*') ? 'show' : '' }}"
                id="operationsMenu">

                <ul class="sub-menu">

                    <li class="side-nav-item {{ request()->routeIs('locations.*') ? 'active' : '' }}">
                        <a href="{{ route('locations.index') }}" class="side-nav-link">
                            <span class="menu-text">Locations</span>
                        </a>
                    </li>

                    @permission('channels.view')
                    <li class="side-nav-item {{ request()->routeIs('channels.*') ? 'active' : '' }}">
                        <a href="{{ route('channels.index') }}" class="side-nav-link">
                            <span class="menu-text">Channels</span>
                        </a>
                    </li>
                    @endpermission

                </ul>
            </div>
        </li>
        @endpermission

        {{-- =========================
    MARKETING MENU
========================= --}}

        @permission('banners.view')
        <li class="side-nav-item {{ request()->routeIs('banners.*') ? 'menuitem-active' : '' }}">

            <a data-bs-toggle="collapse" href="#marketingMenu"
                aria-expanded="{{ request()->routeIs('banners.*') ? 'true' : 'false' }}"
                aria-controls="marketingMenu" class="side-nav-link">

                <span class="menu-icon">
                    <i class="ti ti-speakerphone"></i>
                </span>

                <span class="menu-text">
                    Marketing
                </span>
                <span class="menu-arrow"></span>
            </a>

            <div class="collapse {{ request()->routeIs('banners.*') ? 'show' : '' }}"
                id="marketingMenu">

                <ul class="sub-menu">

                    <li class="side-nav-item {{ request()->routeIs('banners.*') ? 'active' : '' }}">
                        <a href="{{ route('banners.index') }}" class="side-nav-link">
                            <span class="menu-text">Banners</span>
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

        <li class="side-nav-item {{ request()->routeIs('settings.*') ? 'menuitem-active' : '' }}">

            <a data-bs-toggle="collapse" href="#settingsMenu"
                aria-expanded="{{ request()->routeIs('settings.*') ? 'true' : 'false' }}"
                aria-controls="settingsMenu" class="side-nav-link">

                <span class="menu-icon">
                    <i class="ti ti-settings"></i>
                </span>

                <span class="menu-text">
                    Settings
                </span>
                <span class="menu-arrow"></span>
            </a>

            <div class="collapse {{ request()->routeIs('settings.*') ? 'show' : '' }}"
                id="settingsMenu">
                <ul class="sub-menu">
                    <li class="side-nav-item {{ request()->routeIs('settings.index') ? 'active' : '' }}">
                        <a href="{{ route('settings.index') }}" class="side-nav-link">
                            <span class="menu-text">App Settings</span>
                        </a>
                    </li>
                </ul>
            </div>
        </li>

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

                    @permission('users.view')
                    <li class="side-nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <a href="{{ route('users.index') }}" class="side-nav-link">
                            <span class="menu-text">Users</span>
                        </a>
                    </li>
                    @endpermission

                    @permission('roles.view')
                    <li class="side-nav-item {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                        <a href="{{ route('roles.index') }}" class="side-nav-link">
                            <span class="menu-text">Roles</span>
                        </a>
                    </li>
                    @endpermission

                    @role('admin')
                    <li class="side-nav-item {{ request()->routeIs('permissions.*') ? 'active' : '' }}">
                        <a href="{{ route('permissions.index') }}" class="side-nav-link">
                            <span class="menu-text">Permissions</span>
                        </a>
                    </li>
                    @endrole

                </ul>
            </div>
        </li>
        @endif
    </ul>
</div>
