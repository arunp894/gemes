<!doctype html>
<html lang="en" data-menu-color="gradient">

<!-- Mirrored from coderthemes.com/paces/bootstrap/index.html by HTTrack Website Copier/3.x [XR&CO'2014], Mon, 11 May 2026 07:15:56 GMT -->

<head>
    <meta charset="utf-8" />
    <title>@hasSection('title')@yield('title') | @endif{{ $settings->get('site_name', 'Sukaina Gems') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description"
        content="{{ $settings->get('site_name', 'Sukaina Gems') }} — ERP and e-commerce platform for gemstone and jewelry procurement, inventory, and sales." />
    <meta name="keywords"
        content="{{ $settings->get('site_name', 'Sukaina Gems') }}, gemstone ERP, jewelry inventory, admin dashboard" />
    <meta name="author" content="{{ $settings->get('site_name', 'Sukaina Gems') }}" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ $settings->faviconUrl() ?? asset('assets/images/favicon.ico') }}" />


    <!-- Vector Maps css -->
    <link href="{{ asset('assets/plugins/jsvectormap/jsvectormap.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- Theme Config Js -->
    <script src="{{ asset('assets/js/config.js') }}"></script>
    <script src="{{ asset('assets/js/demo.js') }}"></script>

    <!-- Vendor css -->
    <link href="{{ asset('assets/css/vendors.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/plugins/datatables/responsive.bootstrap5.min.css')}}" rel="stylesheet" type="text/css" />

    <!-- App css -->
    <link id="app-style" href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <link id="app-style" href="{{ asset('assets/plugins/select2/select2.min.css') }}" rel="stylesheet" type="text/css" />
            <link rel="stylesheet" href="{{ asset('assets/plugins/filepond/filepond.min.css') }}" type="text/css" />
        <link rel="stylesheet" href="{{ asset('assets/plugins/filepond/filepond-plugin-image-preview.min.css') }}" />
    @stack('styles')
    <style>
        .dt-container{
            margin-top: 0px !important;
        }
        table thead.bg-light{
            background-color: #f3f1f1 !important;
        }
        /* Global DataTables layout: pagination on the left, "Showing x of y" info on the right */
        .card-footer [id$="PaginationSlot"] { order: 1; }
        .card-footer [id$="InfoSlot"] { order: 2; }
        
    </style>
</head>

<body>
    <!-- Begin page -->
    <div class="wrapper">
        @include('layout.header')
        <!-- Topbar End -->
        <div class="sidenav-menu">
            <!-- Brand Logo -->
            <a href="{{ route('dashboard') }}" class="logo">
                <span class="logo-lg d-flex align-items-center justify-content-center gap-2">
                    <img src="{{ $settings->logoUrl() ?? asset('assets/images/logo-sm.png') }}"
                        alt="{{ $settings->get('site_name', 'Sukaina Gems') }}" style="height: 32px; width: auto;" />
                    <span class="pro-username fw-bold" style="color: white;">{{ $settings->get('site_name', 'Sukaina Gems') }}</span>
                </span>
                <span class="logo-sm">
                    <img src="{{ $settings->logoUrl() ?? asset('assets/images/logo-sm.png') }}"
                        alt="{{ $settings->get('site_name', 'Sukaina Gems') }}" style="height: 28px; width: auto;" />
                </span>
            </a>

            <!-- Sidebar Hover Menu Toggle Button -->
            <button class="button-on-hover">
                <span class="btn-on-hover-icon"></span>
            </button>

            <!-- Full Sidebar Menu Close Button -->
            <button class="button-close-offcanvas">
                <i class="ti ti-menu-4 align-middle"></i>
            </button>

            <div class="scrollbar" data-simplebar="">
                <div id="user-profile-settings" class="sidenav-user"
                    style="background: url(assets/images/user-bg-pattern.svg)">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <a href="#!" class="link-reset">
                                <img src="{{ asset('assets/images/users/user-1.jpg') }}" alt="user-image"
                                    class="rounded-circle mb-2 avatar-md" />
                                <span class="sidenav-user-name fw-bold">{{ auth()->user()?->name ?? 'Guest' }}</span>
                                <span class="fs-12 fw-semibold" data-lang="user-role">{{ auth()->user()?->roles->first()?->name ?? '—' }}</span>
                            </a>
                        </div>
                        <div>
                            <a class="dropdown-toggle drop-arrow-none link-reset sidenav-user-set-icon"
                                data-bs-toggle="dropdown" data-bs-offset="0,12" href="#!" aria-haspopup="false"
                                aria-expanded="false">
                                <i class="ti ti-settings fs-24 align-middle ms-1"></i>
                            </a>

                            <div class="dropdown-menu">
                                <!-- Header -->
                                <div class="dropdown-header noti-title">
                                    <h6 class="text-overflow m-0">Welcome back!</h6>
                                </div>

                                <!-- My Profile -->
                                <a href="#!" class="dropdown-item">
                                    <i class="ti ti-user-circle me-1 fs-lg align-middle"></i>
                                    <span class="align-middle">Profile</span>
                                </a>

                                <!-- Settings -->
                                <a href="javascript:void(0);" class="dropdown-item">
                                    <i class="ti ti-settings-2 me-1 fs-lg align-middle"></i>
                                    <span class="align-middle">Account Settings</span>
                                </a>

                                <!-- Lock -->
                                <a href="auth-lock-screen.html" class="dropdown-item">
                                    <i class="ti ti-lock me-1 fs-lg align-middle"></i>
                                    <span class="align-middle">Lock Screen</span>
                                </a>

                                <!-- Logout -->
                                <a href="#!" class="dropdown-item text-danger fw-semibold"
                                   onclick="event.preventDefault(); document.getElementById('paces-logout-form').submit();">
                                    <i class="ti ti-logout me-1 fs-lg align-middle"></i>
                                    <span class="align-middle">Log Out</span>
                                </a>
                                <form id="paces-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!--- Sidenav Menu -->
                @include('layout.sidebar')
            </div>
        </div>
        <!-- Sidenav Menu End -->


        <!-- ============================================================== -->
        <!-- Start Main Content -->
        <!-- ============================================================== -->

        <div class="content-page">
            @yield('content')
            <!-- container -->

            <!-- Footer Start -->
            @include('layout.footer')
            <!-- end Footer -->

        </div>

        <!-- ============================================================== -->
        <!-- End of Main Content -->
        <!-- ============================================================== -->
    </div>
    <!-- end offcanvas-->
    <!-- Vendor js -->
    <script src="{{ asset('assets/js/vendors.min.js') }}"></script>

    <!-- App js -->
    <script src="{{ asset('assets/js/app.js') }}"></script>


    <!-- Apex Chart js -->
    <script src="{{ asset('assets/plugins/apexcharts/apexcharts.min.js') }}"></script>

    <!-- Vector Map Js -->
    <script src="{{ asset('assets/plugins/jsvectormap/jsvectormap.min.js') }}"></script>
    <script src="{{ asset('assets/js/maps/world-merc.js') }}"></script>
    <script src="{{ asset('assets/js/maps/world.js') }}"></script>

    <!-- Custom table -->
    <script src="{{ asset('assets/js/pages/custom-table.js') }}"></script>

    <!-- Dashboard js -->
    <script src="{{ asset('assets/js/pages/dashboard-ecommerce.js') }}"></script>
    <script src="{{ asset('assets/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/select2/select2.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables/dataTables.min.js')}}"></script>
    <script src="{{ asset('assets/plugins/datatables/dataTables.bootstrap5.min.js')}}"></script>
    <script src="{{ asset('assets/plugins/datatables/dataTables.responsive.min.js')}}"></script>
    <script src="{{ asset('assets/plugins/datatables/responsive.bootstrap5.min.js')}}"></script>    
    <script src="{{ asset('assets/js/datatables-defaults.js') }}"></script>
    <script src="{{ asset('assets/plugins/dropzone/dropzone-min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.7.16/dist/vue.min.js"></script>
    
    @stack('scripts')
</body>

<!-- Mirrored from coderthemes.com/paces/bootstrap/index.html by HTTrack Website Copier/3.x [XR&CO'2014], Mon, 11 May 2026 07:18:05 GMT -->

</html>
