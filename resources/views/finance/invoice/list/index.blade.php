@extends('layouts.app')

@section('title', 'Invoice List')

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

        .action-buttons {
            display: flex;
            gap: 0.25rem;
            contain: layout;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            border-radius: 0.25rem;
        }

        #invoiceTable .action-btn i,
        #invoiceTable .action-btn .icon-base,
        #invoiceTable .action-btn .bx,
        #invoiceTable .btn.action-btn i,
        #invoiceTable .btn.action-btn .icon-base,
        #invoiceTable .btn.action-btn .bx,
        #invoiceTable .btn-sm.action-btn i,
        #invoiceTable .btn-sm.action-btn .icon-base,
        #invoiceTable .btn-sm.action-btn .bx,
        .action-btn i,
        .action-btn .icon-base,
        .action-btn .bx {
            font-size: 16px !important;
            line-height: 1 !important;
        }

        .action-btn:hover {
            transform: scale(1.05);
        }
    </style>
@endsection

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y master-list-container">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center py-3 mb-4">
                    <h5 class="fw-bold mb-0">
                        <span class="text-muted fw-light">Finance AP / E-Billing /</span> Invoice List
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
                @include('finance.invoice.list.partials._FilterPartial')
            </div>
        </section>

        <section class="list-section" id="listSection" aria-labelledby="list-heading">
            <header class="list-header">
                <div class="d-flex align-items-center">
                    <h5 id="list-heading" class="fw-semibold mb-0">
                        List Invoice Transaction PO <span id="dateRangeText" class="text-muted fw-normal"></span>
                    </h5>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-primary" onclick="window.location.href='/Finance/EBilling/Invoice/Create'">
                        <i class="icon-base bx bx-plus me-1"></i>
                        Create New
                    </button>
                </div>
            </header>

            @include('finance.invoice.list.partials._ListPartial')
        </section>

        <section class="list-section" id="viewInvoiceSection" style="display: none;" aria-labelledby="view-invoice-heading">
            <header class="list-header">
                <div class="d-flex align-items-center">
                    <h5 id="view-invoice-heading" class="fw-semibold mb-0">View Invoice</h5>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-secondary" onclick="backToList()">
                        <i class="icon-base bx bx-arrow-back me-1"></i>
                        Back to List
                    </button>
                </div>
            </header>

            <div id="viewInvoiceSummaryContainer">
                @include('finance.invoice.list.view_invoice._ViewInvoice')
            </div>
        </section>
    </div>
@endsection

@section('scripts')
    @include('shared.scripts.components._InvoiceListScriptsPartial')
@endsection
