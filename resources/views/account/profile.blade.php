@extends('layouts.app')

@section('title', 'User Profile')

@php
    $employee = session('employee');
    $user = auth()->user();

    $fullName = $employee?->name ?? $employee?->nick_name ?? $user?->Username ?? 'User';
    $email = $employee?->Email ?? '';
    $phone = $employee?->Phone ?? $employee?->HP_No ?? '';
    $employeeId = $employee?->Employ_Id ?? $user?->Username ?? '';
    $jobTitleName = $employee?->JobTitleName ?? '';
    $positionName = $employee?->PositionName ?? '';
    $departmentName = $employee?->DepartmentName ?? '';
    $divisionName = $employee?->DivisionName ?? '';
    $directorateName = $employee?->DirectorateName ?? '';
    $superior = $employee?->Report_Code ?? '';
@endphp

@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-profile.css') }}">
@endsection

@section('content')
<!-- Content wrapper -->
<div class="content-wrapper">
    <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Header -->
        <div class="row">
            <div class="col-12">
                <div class="card mb-6">
                    <div class="user-profile-header-banner">
                        <img src="{{ asset('assets/img/pages/profile-banner.png') }}" alt="Banner image" class="rounded-top">
                    </div>
                    <div class="user-profile-header d-flex flex-column flex-lg-row text-sm-start text-center mb-8">
                        <div class="flex-shrink-0 mt-1 mx-sm-0 mx-auto">
                            <div class="avatar ms-0 ms-sm-6 rounded-3 user-profile-img" style="width: 150px; height: 150px;">
                                <span class="avatar-initial rounded-circle bg-label-primary" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 3rem;">
                                    {{ strtoupper(substr($fullName, 0, 1)) }}
                                </span>
                            </div>
                        </div>
                        <div class="flex-grow-1 mt-3 mt-lg-5">
                            <div class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-5 flex-md-row flex-column gap-4">
                                <div class="user-profile-info">
                                    <h4 class="mb-2 mt-lg-7">{{ $fullName }}</h4>
                                    <ul class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4 mt-4">
                                        @if ($jobTitleName !== '')
                                            <li class="list-inline-item">
                                                <i class="icon-base bx bx-briefcase me-2 align-top"></i>
                                                <span class="fw-medium">{{ $jobTitleName }}</span>
                                            </li>
                                        @endif
                                        @if ($departmentName !== '')
                                            <li class="list-inline-item">
                                                <i class="icon-base bx bx-building me-2 align-top"></i>
                                                <span class="fw-medium">{{ $departmentName }}</span>
                                            </li>
                                        @endif
                                        @if ($employeeId !== '')
                                            <li class="list-inline-item">
                                                <i class="icon-base bx bx-id-card me-2 align-top"></i>
                                                <span class="fw-medium">{{ $employeeId }}</span>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                                <a href="javascript:void(0)" class="btn btn-primary mb-1">
                                    <i class="icon-base bx bx-user-check icon-sm me-2"></i>Active
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--/ Header -->

        <!-- User Profile Content -->
        <div class="row">
            <div class="col-xl-4 col-lg-5 col-md-5">
                <!-- About User -->
                <div class="card mb-6">
                    <div class="card-body">
                        <small class="card-text text-uppercase text-body-secondary small">About</small>
                        <ul class="list-unstyled my-3 py-1">
                            <li class="d-flex align-items-center mb-4">
                                <i class="icon-base bx bx-user"></i>
                                <span class="fw-medium mx-2">Full Name:</span>
                                <span>{{ $fullName }}</span>
                            </li>
                            <li class="d-flex align-items-center mb-4">
                                <i class="icon-base bx bx-check"></i>
                                <span class="fw-medium mx-2">Status:</span>
                                <span>Active</span>
                            </li>
                            @if ($jobTitleName !== '' || $positionName !== '')
                                <li class="d-flex align-items-center mb-4">
                                    <i class="icon-base bx bx-briefcase"></i>
                                    <span class="fw-medium mx-2">Position:</span>
                                    <span>{{ $jobTitleName !== '' ? $jobTitleName : $positionName }}</span>
                                </li>
                            @endif
                            @if ($employeeId !== '')
                                <li class="d-flex align-items-center mb-4">
                                    <i class="icon-base bx bx-id-card"></i>
                                    <span class="fw-medium mx-2">Employee ID:</span>
                                    <span>{{ $employeeId }}</span>
                                </li>
                            @endif
                            @if ($departmentName !== '')
                                <li class="d-flex align-items-center mb-2">
                                    <i class="icon-base bx bx-building"></i>
                                    <span class="fw-medium mx-2">Department:</span>
                                    <span>{{ $departmentName }}</span>
                                </li>
                            @endif
                        </ul>

                        <small class="card-text text-uppercase text-body-secondary small">Contacts</small>
                        <ul class="list-unstyled my-3 py-1">
                            @if ($phone !== '')
                                <li class="d-flex align-items-center mb-4">
                                    <i class="icon-base bx bx-phone"></i>
                                    <span class="fw-medium mx-2">Contact:</span>
                                    <span>{{ $phone }}</span>
                                </li>
                            @endif
                            @if ($email !== '')
                                <li class="d-flex align-items-center mb-4">
                                    <i class="icon-base bx bx-envelope"></i>
                                    <span class="fw-medium mx-2">Email:</span>
                                    <span>{{ $email }}</span>
                                </li>
                            @endif
                        </ul>

                        @if ($divisionName !== '' || $directorateName !== '')
                            <small class="card-text text-uppercase text-body-secondary small">Organization</small>
                            <ul class="list-unstyled mb-0 mt-3 pt-1">
                                @if ($divisionName !== '')
                                    <li class="d-flex flex-wrap mb-4">
                                        <span class="fw-medium me-2">Division:</span>
                                        <span>{{ $divisionName }}</span>
                                    </li>
                                @endif
                                @if ($directorateName !== '')
                                    <li class="d-flex flex-wrap">
                                        <span class="fw-medium me-2">Directorate:</span>
                                        <span>{{ $directorateName }}</span>
                                    </li>
                                @endif
                            </ul>
                        @endif
                    </div>
                </div>
                <!--/ About User -->

                <!-- Profile Overview -->
                <div class="card mb-6">
                    <div class="card-body">
                        <small class="card-text text-uppercase text-body-secondary small">Overview</small>
                        <ul class="list-unstyled mb-0 mt-3 pt-1">
                            <li class="d-flex align-items-center mb-4">
                                <i class="icon-base bx bx-building"></i>
                                <span class="fw-medium mx-2">Department:</span>
                                <span>{{ $departmentName !== '' ? $departmentName : 'N/A' }}</span>
                            </li>
                            @if ($positionName !== '')
                                <li class="d-flex align-items-center mb-4">
                                    <i class="icon-base bx bx-briefcase"></i>
                                    <span class="fw-medium mx-2">Position:</span>
                                    <span>{{ $positionName }}</span>
                                </li>
                            @endif
                            @if ($superior !== '')
                                <li class="d-flex align-items-center">
                                    <i class="icon-base bx bx-user"></i>
                                    <span class="fw-medium mx-2">Superior:</span>
                                    <span>{{ $superior }}</span>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
                <!--/ Profile Overview -->
            </div>

            <div class="col-xl-8 col-lg-7 col-md-7">
                <!-- Activity Timeline -->
                <div class="card card-action mb-6">
                    <div class="card-header align-items-center">
                        <h5 class="card-action-title mb-0">
                            <i class="icon-base bx bx-bar-chart-alt-2 icon-lg text-body me-4"></i>Activity Timeline
                        </h5>
                    </div>
                    <div class="card-body pt-3">
                        <ul class="timeline mb-0">
                            <li class="timeline-item timeline-item-transparent">
                                <span class="timeline-point timeline-point-primary"></span>
                                <div class="timeline-event">
                                    <div class="timeline-header mb-3">
                                        <h6 class="mb-0">Profile Created</h6>
                                        <small class="text-body-secondary">Profile setup completed</small>
                                    </div>
                                    <p class="mb-2">Your profile has been successfully created and is now active.</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <!--/ Activity Timeline -->
            </div>
            <!--/ User Profile Content -->
        </div>
    </div>
    <!-- / Content -->
</div>
<!-- Content wrapper -->
@endsection