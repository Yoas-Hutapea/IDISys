@extends('layouts.app')

@section('title', 'Create Invoice')

@section('styles')
    <style>
        .invoice-create-container {
            contain: layout style;
        }

        .section-card {
            background-color: white;
            border-radius: 0.375rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1.5rem;
            contain: layout;
        }

        [data-bs-theme="dark"] .section-card {
            background-color: #2b2b2b;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.3);
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

        .form-label-required::after {
            content: " *";
            color: #dc3545;
        }

        .readonly-field {
            background-color: #e9ecef;
            cursor: not-allowed;
        }

        [data-bs-theme="dark"] .readonly-field {
            background-color: #3a3a3a;
            color: #e9ecef;
            border-color: #495057;
        }

        [data-bs-theme="dark"] .form-control {
            background-color: #2b2b2b;
            color: #e9ecef;
            border-color: #495057;
        }

        [data-bs-theme="dark"] .form-control:focus {
            background-color: #2b2b2b;
            color: #e9ecef;
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        [data-bs-theme="dark"] .form-control::placeholder {
            color: #6c757d;
        }

        [data-bs-theme="dark"] .form-select {
            background-color: #2b2b2b;
            color: #e9ecef;
            border-color: #495057;
        }

        [data-bs-theme="dark"] .form-select:focus {
            background-color: #2b2b2b;
            color: #e9ecef;
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        [data-bs-theme="dark"] textarea.form-control {
            background-color: #2b2b2b;
            color: #e9ecef;
            border-color: #495057;
        }

        [data-bs-theme="dark"] textarea.form-control:focus {
            background-color: #2b2b2b;
            color: #e9ecef;
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        [data-bs-theme="dark"] .form-label {
            color: #e9ecef;
        }

        [data-bs-theme="dark"] .form-label-required::after {
            color: #ff6b6b;
        }

        [data-bs-theme="dark"] .input-group .form-control {
            background-color: #2b2b2b;
            color: #e9ecef;
            border-color: #495057;
        }

        [data-bs-theme="dark"] .input-group .readonly-field {
            background-color: #3a3a3a;
            color: #e9ecef;
            border-color: #495057;
        }

        [data-bs-theme="dark"] .invalid-feedback {
            color: #ff6b6b;
        }

        [data-bs-theme="dark"] .table {
            color: #e9ecef;
        }

        [data-bs-theme="dark"] .table-striped > tbody > tr:nth-of-type(odd) > td {
            background-color: #343a40;
        }

        [data-bs-theme="dark"] .table-hover tbody tr:hover {
            background-color: #3a3a3a;
        }

        [data-bs-theme="dark"] .text-muted {
            color: #adb5bd !important;
        }

        [data-bs-theme="dark"] .modal-content {
            background-color: #2b2b2b;
            color: #e9ecef;
        }

        [data-bs-theme="dark"] .modal-header {
            border-bottom-color: #495057;
        }

        [data-bs-theme="dark"] .modal-footer {
            border-top-color: #495057;
        }

        [data-bs-theme="dark"] .badge {
            color: #fff;
        }

        [data-bs-theme="dark"] .spinner-border {
            border-color: currentColor;
        }

        #tblDocumentChecklist .document-number-input,
        #tblDocumentChecklist input[type="text"][id^="txtRemarks_"] {
            background-color: #ffffff !important;
            color: #212529 !important;
        }

        [data-bs-theme="dark"] #tblDocumentChecklist .document-number-input,
        [data-bs-theme="dark"] #tblDocumentChecklist input[type="text"][id^="txtRemarks_"] {
            background-color: #2b2b2b !important;
            color: #e9ecef !important;
        }

        #tblDocumentChecklist .document-number-input:focus,
        #tblDocumentChecklist input[type="text"][id^="txtRemarks_"]:focus {
            background-color: #ffffff !important;
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        [data-bs-theme="dark"] #tblDocumentChecklist .document-number-input:focus,
        [data-bs-theme="dark"] #tblDocumentChecklist input[type="text"][id^="txtRemarks_"]:focus {
            background-color: #2b2b2b !important;
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
    </style>
@endsection

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y invoice-create-container">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center py-3 mb-4">
                    <h5 class="fw-bold mb-0">
                        <span class="text-muted fw-light">Finance AP / E-Billing /</span> Create Invoice
                    </h5>
                    <button class="btn btn-outline-secondary" onclick="window.location.href='/Finance/EBilling/Invoice/List'">
                        <i class="icon-base bx bx-arrow-back me-1"></i>
                        Back
                    </button>
                </div>
            </div>
        </div>

        <form id="invoiceForm" novalidate>
            @include('finance.invoice.create.partials._POInformationPartial')
            @include('finance.invoice.create.partials._VendorInformationPartial')
            @include('finance.invoice.create.partials._ItemListPartial')
            @include('finance.invoice.create.partials._TermOfPaymentPartial')
            @include('finance.invoice.create.partials._PeriodOfPaymentPartial')
            @include('finance.invoice.create.partials._InvoiceBasicInformationPartial')
            @include('finance.invoice.create.partials._InvoiceAmountSummaryPartial')
            @include('finance.invoice.create.partials._DocumentChecklistPartial')
            @include('finance.invoice.create.partials._SubmitButtonsPartial')
        </form>

        @include('finance.invoice.create.partials._ChoosePOModalPartial')
    </div>
@endsection

@section('scripts')
    @include('shared.scripts.components._InvoiceCreateScriptsPartial')
@endsection
