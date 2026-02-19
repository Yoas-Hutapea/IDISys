@extends('layouts.app')

@section('title', 'Cancel Period')

@section('styles')
    <style>
        .master-list-container {
            contain: layout style;
        }

        .filter-section {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 0.375rem;
            contain: layout;
            transition: all 0.3s ease;
        }

        [data-bs-theme="dark"] .filter-section {
            background-color: #2b2b2b;
            border-color: #495057;
        }

        .filter-header {
            background-color: #2352a1;
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 0.375rem 0.375rem 0 0;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.2s ease;
        }

        .filter-header:hover {
            background-color: #0b5ed7;
        }

        .filter-content {
            padding: 1.5rem;
            contain: layout;
            display: none;
        }

        .filter-content.show {
            display: block;
        }

        .list-section {
            background-color: white;
            border-radius: 0.375rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            contain: layout;
        }

        [data-bs-theme="dark"] .list-section {
            background-color: #2b2b2b;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.3);
        }

        .list-partial-content {
            padding: 1.5rem;
        }

        .list-partial-content .dataTables_wrapper {
            padding: 0;
        }

        .list-header {
            background-color: #f8f9fa;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e9ecef;
            border-radius: 0.375rem 0.375rem 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            contain: layout;
        }

        [data-bs-theme="dark"] .list-header {
            background-color: #3a3a3a;
            border-bottom-color: #495057;
        }

        .table-responsive {
            border-radius: 0 0 0.375rem 0.375rem;
            contain: layout;
            overflow: auto;
        }

        .section-card {
            background-color: white;
            border: 1px solid #e9ecef;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
            contain: layout;
        }

        [data-bs-theme="dark"] .section-card {
            background-color: #2b2b2b;
            border-color: #495057;
        }

        .section-header {
            background-color: #f8f9fa;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e9ecef;
            border-radius: 0.375rem 0.375rem 0 0;
        }

        [data-bs-theme="dark"] .section-header {
            background-color: #3a3a3a;
            border-bottom-color: #495057;
        }

        .section-body {
            padding: 1.5rem;
        }

        .form-check-input.cancel-checkbox:checked {
            background-color: #dc3545;
            border-color: #dc3545;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M6 6l8 8M14 6l-8 8'/%3e%3c/svg%3e");
        }

        .form-check-input.cancel-checkbox:checked:hover {
            background-color: #bb2d3b;
            border-color: #bb2d3b;
        }

        .form-check-input.cancel-checkbox:focus {
            border-color: #dc3545;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }
    </style>
@endsection

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y master-list-container">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center py-3 mb-4">
                    <h5 class="fw-bold mb-0">
                        <span class="text-muted fw-light">Procurement / Purchase Order /</span> Cancel Period
                    </h5>
                </div>
            </div>
        </div>

        <section class="filter-section mb-4" aria-labelledby="filter-heading">
            <header class="filter-header" onclick="toggleFilter()" role="button" tabindex="0" aria-expanded="false" aria-controls="filter-content">
                <span id="filter-heading">
                    <i class="icon-base bx bx-filter me-2" aria-hidden="true"></i>
                    Filter
                </span>
                <i class="icon-base bx bx-chevron-down" id="filter-chevron" aria-hidden="true"></i>
            </header>
            <div class="filter-content" id="filter-content" role="region" aria-labelledby="filter-heading">
                @include('procurement.purchase_order.cancel_period.list.partials._FilterPartial')
            </div>
        </section>

        <section class="list-section" id="listSection" aria-labelledby="list-heading">
            <header class="list-header">
                <div class="d-flex align-items-center">
                    <h5 id="list-heading" class="fw-semibold mb-0">
                        List Purchase Order<span id="dateRangeInfo" class="text-muted ms-2" style="font-size: 0.9em; font-weight: normal;"></span>
                    </h5>
                </div>
            </header>

            @include('procurement.purchase_order.cancel_period.list.partials._ListPartial')
        </section>

        <section class="list-section" id="viewCancelPeriodSection" style="display: none;" aria-labelledby="view-cancel-period-heading">
            <header class="list-header">
                <div class="d-flex align-items-center">
                    <h5 id="view-cancel-period-heading" class="fw-semibold mb-0">Cancel Period</h5>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-secondary" onclick="cancelPeriodManager.backToList()">
                        <i class="icon-base bx bx-arrow-back me-1"></i>
                        Back to List
                    </button>
                </div>
            </header>

            <div id="viewCancelPeriodContainer">
                <div id="viewCancelPeriodLoading" style="display: none;" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-3">Loading PO details...</div>
                </div>

                <div id="viewCancelPeriodData" style="display: none;">
                    <div class="row g-4 p-4">
                        @include('procurement.purchase_order.cancel_period.partials._POInformationPartial')
                        <div class="col-12">
                            @include('procurement.purchase_order.cancel_period.partials._AdditionalInformationPartial')
                        </div>
                        @include('procurement.purchase_order.cancel_period.partials._VendorInformationPartial')
                        @include('procurement.purchase_order.cancel_period.partials._ItemListPartial')
                        {{-- Term Of Payment vs Period Of Payment: tampil salah satu berdasarkan response getAmortizations. Jika data.periodOfPayment tidak kosong tampil Period Of Payment; jika tidak tampil Term Of Payment. Logic di CancelPeriodView.js selectPO() setelah getAmortizations(poNumber). --}}
                        @include('procurement.purchase_order.cancel_period.partials._TermOfPaymentPartial')
                        @include('procurement.purchase_order.cancel_period.partials._PeriodOfPaymentPartial')
                    </div>
                    <div class="row mt-4">
                        <div class="col-12">
                            @include('procurement.purchase_order.cancel_period.partials._SubmitButtonsPartial')
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('scripts')
    @include('shared.scripts.components._CancelPeriodScriptsPartial')
@endsection
