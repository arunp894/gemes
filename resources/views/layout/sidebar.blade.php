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
        {{-- =========================
    CATALOGUE MENU
========================= --}}

        <li class="side-nav-title mt-2">Catalogue</li>

        <li
            class="side-nav-item {{ request()->routeIs('categories.*') || request()->routeIs('subcategories.*') || request()->routeIs('products.*') || request()->routeIs('website-visibility.*') ? 'menuitem-active' : '' }}">

            <a data-bs-toggle="collapse" href="#catalogueMenu"
                aria-expanded="{{ request()->routeIs('categories.*') || request()->routeIs('subcategories.*') || request()->routeIs('products.*') || request()->routeIs('website-visibility.*') ? 'true' : 'false' }}"
                aria-controls="catalogueMenu" class="side-nav-link">

                <span class="menu-icon">
                    <i class="ti ti-package"></i>
                </span>

                <span class="menu-text">
                    Catalogue
                </span>

                <span class="badge bg-success text-white">4</span>

                <span class="menu-arrow"></span>
            </a>

            <div class="collapse {{ request()->routeIs('categories.*') || request()->routeIs('subcategories.*') || request()->routeIs('products.*') || request()->routeIs('website-visibility.*') ? 'show' : '' }}"
                id="catalogueMenu">

                <ul class="sub-menu">

                    {{-- Categories --}}
                    <li class="side-nav-item {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                        <a href="{{ route('categories.index') }}" class="side-nav-link">

                            <span class="menu-text">
                                Categories
                            </span>
                        </a>
                    </li>

                    {{-- Subcategories --}}
                    <li class="side-nav-item {{ request()->routeIs('subcategories.*') ? 'active' : '' }}">
                        <a href="{{ \Illuminate\Support\Facades\Route::has('subcategories.index')
                    ? route('subcategories.index')
                    : url('/subcategories') }}" class="side-nav-link">

                            <span class="menu-text">
                                Subcategories
                            </span>
                        </a>
                    </li>

                    {{-- Products --}}
                    <li class="side-nav-item {{ request()->routeIs('products.*') ? 'active' : '' }}">
                        <a href="{{ \Illuminate\Support\Facades\Route::has('products.index')
                    ? route('products.index')
                    : url('/products') }}" class="side-nav-link">

                            <span class="menu-text">
                                Products
                            </span>
                        </a>
                    </li>

                    {{-- Website Visibility --}}
                    <li class="side-nav-item {{ request()->routeIs('website-visibility.*') ? 'active' : '' }}">
                        <a href="{{ \Illuminate\Support\Facades\Route::has('website-visibility.index')
                    ? route('website-visibility.index')
                    : url('/website-visibility') }}" class="side-nav-link">

                            <span class="menu-text">
                                Website Visibility
                            </span>
                        </a>
                    </li>

                </ul>
            </div>
        </li>
    </ul>
</div>
