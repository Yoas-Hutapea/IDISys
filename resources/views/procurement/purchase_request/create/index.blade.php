@extends('layouts.app')

@section('title', 'Create Procurement')

@php
    $employee = session('employee');
    $user = auth()->user();
    $currentUserFullName = $employee?->name ?? $employee?->nick_name ?? $user?->Username ?? 'User';
    $currentUserEmployeeID = $employee?->Employ_Id ?? $user?->Username ?? '';
@endphp

@section('styles')
    <link rel="preload" href="{{ asset('assets/vendor/libs/bs-stepper/bs-stepper.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="{{ asset('assets/vendor/libs/bs-stepper/bs-stepper.css') }}"></noscript>
    <style>
        .procurement-wizard {
            contain: layout style;
            will-change: transform;
        }

        .step-trigger {
            cursor: not-allowed;
            opacity: 0.6;
            pointer-events: none;
            transition: opacity 0.2s ease;
        }

        .step-trigger:hover {
            background-color: transparent;
        }

        .bs-stepper-content .content {
            display: none;
            contain: layout;
        }

        .bs-stepper-content .content:first-child {
            display: block;
        }

        .step {
            pointer-events: none;
            contain: layout;
        }

        .step button {
            pointer-events: none;
        }

        .btn-next, .btn-prev, .btn-submit {
            cursor: pointer;
            pointer-events: auto;
            transition: all 0.2s ease;
        }

        .btn-next *, .btn-prev *, .btn-submit * {
            pointer-events: none;
        }

        .table-responsive {
            contain: layout;
            overflow: auto;
        }

        .alert {
            will-change: opacity, transform;
            contain: layout;
        }

        .modal {
            will-change: opacity, transform;
            contain: layout;
        }

        .form-control.is-invalid {
            border-color: #dc3545;
            animation: shake 0.5s ease-in-out;
        }

        .form-control.is-valid {
            border-color: #198754;
        }

        .lazy-load {
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .lazy-load.loaded {
            opacity: 1;
        }
    </style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center py-3 mb-4">
                <h5 class="fw-bold mb-0">
                    <span class="text-muted fw-light">Procurement /</span> Create Purchase Request
                </h5>
                <button class="btn btn-danger" onclick="cancelCreatePR()">Cancel</button>
            </div>
        </div>
    </div>

    <div class="bs-stepper wizard-icons wizard-modern wizard-modern-icons-example mt-2 procurement-wizard" role="application" aria-label="Procurement Creation Wizard">
        <div class="card mb-4">
            <div class="card-body">
                <div class="bs-stepper-header">
                    <div class="step" data-target="#basic-information">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-icon">
                                <svg viewbox="0 0 54 54">
                                    <use xlink:href="/assets/svg/icons/wizard-step-1.svg#wizardStep1"></use>
                                </svg>
                            </span>
                            <span class="bs-stepper-label">Basic Information</span>
                        </button>
                    </div>
                    <div class="line"><i class="icon-base bx bx-right-arrow-alt icon-md scaleX-n1-rtl"></i></div>
                    <div class="step" data-target="#additional" id="additional-step-header" style="display: none;">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-icon">
                                <svg viewbox="0 0 54 54">
                                    <use xlink:href="/assets/svg/icons/wizard-step-2.svg#wizardStep2"></use>
                                </svg>
                            </span>
                            <span class="bs-stepper-label">Additional</span>
                        </button>
                    </div>
                    <div class="line" id="additional-line-after" style="display: none;"><i class="icon-base bx bx-right-arrow-alt icon-md scaleX-n1-rtl"></i></div>
                    <div class="step" data-target="#detail">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-icon">
                                <svg viewbox="0 0 54 54">
                                    <use xlink:href="/assets/svg/icons/wizard-step-2.svg#wizardStep2"></use>
                                </svg>
                            </span>
                            <span class="bs-stepper-label">Detail</span>
                        </button>
                    </div>
                    <div class="line"><i class="icon-base bx bx-right-arrow-alt icon-md scaleX-n1-rtl"></i></div>
                    <div class="step" data-target="#assign-approval">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-icon">
                                <svg viewbox="0 0 54 54">
                                    <use xlink:href="/assets/svg/icons/wizard-step-3.svg#wizardStep3"></use>
                                </svg>
                            </span>
                            <span class="bs-stepper-label">Assign Approval</span>
                        </button>
                    </div>
                    <div class="line"><i class="icon-base bx bx-right-arrow-alt icon-md scaleX-n1-rtl"></i></div>
                    <div class="step" data-target="#document">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-icon">
                                <svg viewbox="0 0 54 54">
                                    <use xlink:href="/assets/svg/icons/wizard-step-4.svg#wizardStep4"></use>
                                </svg>
                            </span>
                            <span class="bs-stepper-label">Document</span>
                        </button>
                    </div>
                    <div class="line"><i class="icon-base bx bx-right-arrow-alt icon-md scaleX-n1-rtl"></i></div>
                    <div class="step" data-target="#summary">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-icon">
                                <svg viewbox="0 0 54 54">
                                    <use xlink:href="/assets/svg/icons/wizard-step-5.svg#wizardStep5"></use>
                                </svg>
                            </span>
                            <span class="bs-stepper-label">Summary</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="bs-stepper-content">
            <form id="procurement-wizard-form" onsubmit="return false">
                @include('procurement.purchase_request.create.partials._BasicInformationPartial')
                @include('procurement.purchase_request.create.partials._AdditionalPartial')
                @include('procurement.purchase_request.create.partials._DetailPartial')
                @include('procurement.purchase_request.create.partials._AssignApprovalPartial')
                @include('procurement.purchase_request.create.partials._DocumentPartial')
                @include('procurement.purchase_request.create.partials._SummaryPartial')
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('assets/vendor/libs/bs-stepper/bs-stepper.js') }}"></script>
    @include('shared.scripts.procurement_wizard')

    <script>
        window.ProcurementConfig = {
            currentUserFullName: @json($currentUserFullName),
            currentUserEmployeeID: @json($currentUserEmployeeID)
        };
    </script>

    <script>
        const preloadLink = document.createElement('link');
        preloadLink.rel = 'preload';
        preloadLink.href = '/assets/vendor/libs/bs-stepper/bs-stepper.css';
        preloadLink.as = 'style';
        document.head.appendChild(preloadLink);

        document.addEventListener('DOMContentLoaded', function() {
            const svgs = document.querySelectorAll('svg use');
            svgs.forEach(svg => {
                if (!svg.getAttribute('xlink:href').includes('wizard-step-1')) {
                    svg.style.opacity = '0.7';
                }
            });
        });
    </script>
@endsection
