<!DOCTYPE html>
<html lang="en" class="layout-navbar-fixed layout-menu-fixed layout-compact" dir="ltr" data-skin="default" data-assets-path="{{ asset('assets') }}/" data-template="vertical-menu-template" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - IDEANET</title>

    <!-- Canonical SEO -->
    <meta name="description" content="IDEANET - Track, manage, and collect receivables efficiently from a single, powerful platform.">
    <meta name="keywords" content="IDEANET, receivables management, dashboard, business platform">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/fonts/iconify-icons.css') }}">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" crossorigin="anonymous">

    <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/core.css') }}" class="template-customizer-core-css">
    <link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}">

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') }}">

    <!-- Custom Bootstrap Overrides -->
    <link rel="stylesheet" href="{{ asset('css/custom-bootstrap.css') }}">

    <!-- Performance optimizations -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link rel="dns-prefetch" href="//unpkg.com">

    <!-- Page CSS -->
    @yield('styles')

    <!-- Helpers -->
    <script src="{{ asset('assets/vendor/js/helpers.js') }}"></script>

    <!-- Template config -->
    <script src="{{ asset('assets/js/config.js') }}"></script>
</head>

<body class="layout-navbar-fixed layout-menu-fixed layout-compact">
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Menu -->
            @include('shared.sidebar')
            <!-- / Menu -->

            <div class="menu-mobile-toggler d-xl-none rounded-1">
                <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large text-bg-secondary p-2 rounded-1">
                    <i class="bx bx-menu icon-base"></i>
                    <i class="bx bx-chevron-right icon-base"></i>
                </a>
            </div>

            <!-- Layout page -->
            <div class="layout-page">
                <!-- Navbar -->
                @include('shared.navbar')
                <!-- / Navbar -->

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    @yield('content')

                    <!-- Footer -->
                    @include('shared.footer')
                    <!-- / Footer -->

                    <div class="content-backdrop fade"></div>
                </div>
                <!-- Content wrapper -->
            </div>
            <!-- / Layout page -->
        </div>

        <!-- Overlay -->
        <div class="layout-overlay layout-menu-toggle"></div>

        <!-- Drag Target Area To SlideIn Menu On Small Screens -->
        <div class="drag-target"></div>
    </div>
    <!-- / Layout wrapper -->

    <!-- Core Scripts -->
    @include('shared.scripts')

    <!-- Sync layout-menu-collapsed between html and body + Sidebar auto-expand on hover when collapsed -->
    <script>
        (function() {
            var html = document.documentElement;
            var body = document.body;

            function syncCollapsedClass() {
                if (html.classList.contains('layout-menu-collapsed')) {
                    body.classList.add('layout-menu-collapsed');
                } else {
                    body.classList.remove('layout-menu-collapsed');
                }
            }

            function isDesktop() {
                return (window.innerWidth || document.documentElement.clientWidth) >= 1200;
            }

            function isCollapsed() {
                return html.classList.contains('layout-menu-collapsed');
            }

            function initSidebarHoverExpand() {
                var menu = document.getElementById('layout-menu');
                if (!menu) return;
                function addHover() {
                    if (isDesktop() && isCollapsed()) {
                        html.classList.add('layout-menu-hover');
                        body.classList.add('layout-menu-hover');
                    }
                }
                function removeHover() {
                    html.classList.remove('layout-menu-hover');
                    body.classList.remove('layout-menu-hover');
                }
                menu.addEventListener('mouseenter', addHover);
                menu.addEventListener('mouseleave', removeHover);
                menu.addEventListener('touchstart', function(e) {
                    if (isDesktop() && isCollapsed()) {
                        addHover();
                        document.addEventListener('touchend', function rem() {
                            removeHover();
                            document.removeEventListener('touchend', rem);
                        }, { once: true });
                    }
                }, { passive: true });
                window.addEventListener('resize', function() {
                    if (!isDesktop()) removeHover();
                });
            }

            function initCollapsedSync() {
                syncCollapsedClass();
                var obs = new MutationObserver(syncCollapsedClass);
                obs.observe(html, { attributes: true, attributeFilter: ['class'] });
                document.querySelectorAll('.layout-menu-toggle').forEach(function(el) {
                    el.addEventListener('click', function() { setTimeout(syncCollapsedClass, 50); });
                });
                initSidebarHoverExpand();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initCollapsedSync);
            } else {
                initCollapsedSync();
            }
        })();
    </script>

    <!-- API Helper Scripts - Must be before section Scripts -->
    <script>
        const API_URLS = @json(config('api_modules', []));
    </script>

    @yield('scripts')
</body>
</html>
