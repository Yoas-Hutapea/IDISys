@extends('layouts.app')

@section('title', 'Good Receive Notes')

@section('styles')
    <style>
        .grn-list-container { contain: layout style; }
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
        }
        .filter-header:hover { background-color: #0b5ed7; }
        .filter-content { padding: 1.5rem; display: none; }
        .filter-content.show { display: block; }
        .list-section {
            background-color: white;
            border-radius: 0.375rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        [data-bs-theme="dark"] .list-section {
            background-color: #2b2b2b;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.3);
        }
        .list-partial-content { padding: 1.5rem; }
        .list-header {
            background-color: #f8f9fa;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e9ecef;
            border-radius: 0.375rem 0.375rem 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        [data-bs-theme="dark"] .list-header {
            background-color: #3a3a3a;
            border-bottom-color: #495057;
        }
        .table-responsive { border-radius: 0 0 0.375rem 0.375rem; overflow: auto; }
    </style>
@endsection

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y grn-list-container">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center py-3 mb-4">
                    <h5 class="fw-bold mb-0">
                        <span class="text-muted fw-light">Inventory /</span> Good Receive Notes
                    </h5>
                </div>
            </div>
        </div>

        <section class="filter-section mb-4" aria-labelledby="grn-filter-heading">
            <header class="filter-header" onclick="toggleGRNFilter()" role="button" tabindex="0">
                <span id="grn-filter-heading"><i class="icon-base bx bx-filter me-2"></i>Filter</span>
                <i class="icon-base bx bx-chevron-down" id="grn-filter-chevron"></i>
            </header>
            <div class="filter-content" id="grn-filter-content">
                @include('inventory.good_receive_notes.list.partials._FilterPartial')
            </div>
        </section>

        <section class="list-section" id="grnListSection">
            <header class="list-header">
                <h5 id="grn-list-heading" class="fw-semibold mb-0">
                    List Purchase Order (Fully Approved)<span id="grnDateRangeInfo" class="text-muted ms-2" style="font-size: 0.9em;"></span>
                </h5>
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-success" id="grnExportExcelBtn" onclick="exportExcel('#grnPOTable')" title="Export to Excel">
                        <i class="icon-base bx bx-file me-1"></i>Excel
                    </button>
                </div>
            </header>
            @include('inventory.good_receive_notes.list.partials._ListPartial')
        </section>

        <section class="list-section" id="viewGRNSection" style="display: none;">
            <header class="list-header">
                <h5 class="fw-semibold mb-0">Good Receive Note</h5>
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-secondary" onclick="grnManager.backToList()">
                        <i class="icon-base bx bx-arrow-back me-1"></i>Back to List
                    </button>
                </div>
            </header>
            <div id="viewGRNContainer">
                @include('inventory.good_receive_notes.view._ViewGRN')
            </div>
        </section>
    </div>
@endsection

@section('scripts')
    @include('shared.scripts.components._GRNListScriptsPartial')
@endsection
