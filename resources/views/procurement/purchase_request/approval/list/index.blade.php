@extends('layouts.app')

@section('title', 'Purchase Request Approval List')

@section('styles')
    <style>
        /* Performance-optimized ApprovalList styles */
        .approval-list-container {
            contain: layout style;
        }
        
        .filter-section {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 0.375rem;
            contain: layout;
            transition: all 0.3s ease;
        }
        
        /* Dark mode support */
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
            display: none; /* Default collapsed */
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
        
        /* Dark mode support for list section */
        [data-bs-theme="dark"] .list-section {
            background-color: #2b2b2b;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.3);
        }
        
        /* Padding for DataTables content (buttons, search, pagination) */
        .list-partial-content {
            padding: 1.5rem;
        }
        
        .list-partial-content .dataTables_wrapper {
            padding: 0;
        }
        
        .list-partial-content .dataTables_wrapper .dt-buttons {
            margin-bottom: 1rem;
        }
        
        .list-partial-content .dataTables_wrapper .dataTables_filter {
            margin-bottom: 1rem;
        }
        
        .list-partial-content .dataTables_wrapper .dataTables_length {
            margin-bottom: 1rem;
        }
        
        .list-partial-content .dataTables_wrapper .dataTables_info {
            margin-top: 1rem;
        }
        
        .list-partial-content .dataTables_wrapper .dataTables_paginate {
            margin-top: 1rem;
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
        
        /* Dark mode support for list header */
        [data-bs-theme="dark"] .list-header {
            background-color: #3a3a3a;
            border-bottom-color: #495057;
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
        
        .action-btn:hover {
            transform: scale(1.05);
        }
        
        .table-responsive {
            border-radius: 0 0 0.375rem 0.375rem;
            contain: layout;
            overflow: auto;
        }
        
        .pagination-info {
            color: #6c757d;
            font-size: 0.875rem;
        }
        
        .rows-per-page {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Performance optimizations */
        .table tbody tr {
            transition: background-color 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        /* Loading states */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #0d6efd;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
    </style>
@endsection

<div class="container-xxl flex-grow-1 container-p-y approval-list-container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center py-3 mb-4">
                <h5 class="fw-bold mb-0">
                    <span class="text-muted fw-light">Procurement /</span> Purchase Request Approval List
                </h5>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <section class="filter-section mb-4" aria-labelledby="filter-heading">
        <header class="filter-header" onclick="toggleFilter()" role="button" tabindex="0" aria-expanded="false" aria-controls="filter-content">
            <span id="filter-heading">
                <i class="icon-base bx bx-filter me-2" aria-hidden="true"></i>
                Filter
            </span>
            <i class="icon-base bx bx-chevron-down" id="filter-chevron" aria-hidden="true"></i>
        </header>
        <div class="filter-content" id="filter-content" role="region" aria-labelledby="filter-heading">
            @include('procurement.purchase_request.approval.list.partials._FilterPartial')
        </div>
    </section>

    <!-- List Section -->
    <section class="list-section" id="listSection" aria-labelledby="list-heading">
        <header class="list-header">
            <div class="d-flex align-items-center">
                <h5 id="list-heading" class="fw-semibold mb-0">
                    List Purchase Request Approval<span id="dateRangeInfo" class="text-muted ms-2" style="font-size: 0.9em; font-weight: normal;"></span>
                </h5>
            </div>
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-success" id="exportExcelBtn" onclick="exportExcel('#approvalTable')" title="Export to Excel">
                    <i class="icon-base bx bx-file me-1"></i>
                    Excel
                </button>
            </div>
        </header>
        
        @include('procurement.purchase_request.approval.list.partials._ListPartial')
    </section>

    <!-- View Approval Section (hidden by default) -->
    <section class="list-section" id="viewApprovalSection" style="display: none;" aria-labelledby="view-approval-heading">
        <header class="list-header">
            <div class="d-flex align-items-center">
                <h5 id="view-approval-heading" class="fw-semibold mb-0">View Purchase Request Approval</h5>
            </div>
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-secondary" onclick="backToList()">
                    <i class="icon-base bx bx-arrow-back me-1"></i>
                    Back to List
                </button>
            </div>
        </header>
        
        <div id="viewApprovalContainer">
            @include('procurement.purchase_request.approval.view._ViewApproval')
        </div>
    </section>
</div>
@section('scripts')
    @include('shared.scripts.components._ApprovalListScriptsPartial')
@endsection

