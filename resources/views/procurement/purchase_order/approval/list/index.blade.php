@extends('layouts.app')

@section('title', 'Approval Purchase Order')

@section('styles')
    <style>
        .approval-po-list-container {
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

        .action-buttons {
            display: flex;
            gap: 0.25rem;
            contain: layout;
        }

        #approvalPOTable .action-btn {
            min-width: 38px;
            min-height: 38px;
            padding: 0.5rem 0.75rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        #approvalPOTable .action-btn i {
            font-size: 1rem;
            line-height: 1;
        }

        #viewApprovalContainer .btn {
            min-height: 38px;
            padding: 0.5rem 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        #viewApprovalContainer .btn i {
            font-size: 1rem;
            line-height: 1;
        }

        #approval-documents-tbody .btn {
            min-width: 38px;
            min-height: 38px;
            padding: 0.5rem 0.75rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .table-responsive {
            border-radius: 0 0 0.375rem 0.375rem;
            contain: layout;
            overflow: auto;
        }
    </style>
@endsection

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y approval-po-list-container">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center py-3 mb-4">
                    <h5 class="fw-bold mb-0">
                        <span class="text-muted fw-light">Procurement /</span> Approval Purchase Order
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
                @include('procurement.purchase_order.approval.list.partials._FilterPartial')
            </div>
        </section>

        <section class="list-section" id="listSection" aria-labelledby="list-heading">
            <header class="list-header">
                <div class="d-flex align-items-center">
                    <h5 id="list-heading" class="fw-semibold mb-0">
                        List Purchase Order<span id="dateRangeInfo" class="text-muted ms-2" style="font-size: 0.9em; font-weight: normal;"></span>
                    </h5>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-success" id="exportExcelBtn" onclick="exportExcel('#approvalPOTable')" title="Export to Excel">
                        <i class="icon-base bx bx-file me-1"></i>
                        Excel
                    </button>
                    <button class="btn btn-warning" id="processBulkApprovalBtn" onclick="approvalPOManager.processBulkApproval()" disabled>
                        <i class="icon-base bx bx-check-square me-1"></i>
                        Process
                    </button>
                </div>
            </header>

            @include('procurement.purchase_order.approval.list.partials._ListPartial')
        </section>

        <section class="list-section" id="viewApprovalSection" style="display: none;" aria-labelledby="view-approval-heading">
            <header class="list-header">
                <div class="d-flex align-items-center">
                    <h5 id="view-approval-heading" class="fw-semibold mb-0">Approval Purchase Order</h5>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-secondary" onclick="approvalPOManager.backToList()">
                        <i class="icon-base bx bx-arrow-back me-1"></i>
                        Back to List
                    </button>
                </div>
            </header>

            <div id="viewApprovalContainer">
                @include('procurement.purchase_order.approval.view._ViewApproval')
            </div>
        </section>
    </div>
@endsection

@section('scripts')
    @include('shared.scripts.components._ApprovalPOListScriptsPartial')
@endsection
