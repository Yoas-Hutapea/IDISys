@php
    $user = auth()->user();
    $employee = session('employee');
    $fullName = $employee?->name
        ?? $employee?->nick_name
        ?? $user?->Username
        ?? 'User';
    $jobTitle = $employee?->JobTitleName ?? $employee?->PositionName ?? '';
    $departmentName = $employee?->DepartmentName ?? '';
@endphp

<!-- Navbar -->
<nav class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
            <i class="icon-base bx bx-menu icon-md"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
        <ul class="navbar-nav flex-row align-items-center ms-md-auto">
            <!-- Theme Toggle -->
            <li class="nav-item lh-1 me-3">
                <a class="nav-link style-switcher-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Theme Toggle">
                    <i class="icon-base bx bx-sun icon-md theme-icon-active"></i>
                </a>
            </li>

            <!-- User -->
            <li class="nav-item navbar-dropdown dropdown user-dropdown">
                <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                        <span class="avatar-initial rounded-circle bg-label-primary">
                            {{ strtoupper(substr($fullName, 0, 1)) }}
                        </span>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="javascript:void(0);">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar avatar-online">
                                        <span class="avatar-initial rounded-circle bg-label-primary">
                                            {{ strtoupper(substr($fullName, 0, 1)) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">{{ $fullName }}</h6>
                                    <small class="text-body-secondary">{{ $jobTitle !== '' ? $jobTitle : 'Employee' }}</small>
                                    @if ($departmentName !== '')
                                        <br />
                                        <small class="text-body-secondary" style="font-size: 0.75rem;">{{ $departmentName }}</small>
                                    @endif
                                </div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider my-1"></div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('profile') }}">
                            <i class="icon-base bx bx-user me-2"></i>
                            <span class="align-middle">My Profile</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="javascript:void(0);">
                            <i class="icon-base bx bx-cog me-2"></i>
                            <span class="align-middle">Settings</span>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider my-1"></div>
                    </li>
                    <li>
                        <form id="logoutForm" action="{{ route('api.auth.logout') }}" method="post" class="d-inline w-100">
                            @csrf
                            <button type="button" id="logoutButton" class="dropdown-item border-0 bg-transparent w-100 text-start" style="cursor: pointer;">
                                <i class="icon-base bx bx-power-off me-2"></i>
                                <span class="align-middle">Log Out</span>
                            </button>
                        </form>
                    </li>
                </ul>
            </li>
            <!--/ User -->
        </ul>
    </div>
</nav>
<!-- / Navbar -->

<script>
    // Ensure logout button works on all pages
    document.addEventListener('DOMContentLoaded', function() {
        const logoutButton = document.getElementById('logoutButton');
        const logoutForm = document.getElementById('logoutForm');

        if (logoutButton && logoutForm) {
            const newLogoutButton = logoutButton.cloneNode(true);
            logoutButton.parentNode.replaceChild(newLogoutButton, logoutButton);

            newLogoutButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                if (logoutForm) {
                    logoutForm.submit();
                }
            });
        }
    });
</script>
