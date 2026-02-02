@extends('layouts.app')

@section('title', 'Vendor Management')

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

        [data-bs-theme="dark"] .list-header {
            background-color: #3a3a3a;
            border-bottom-color: #495057;
        }

        .table-responsive {
            border-radius: 0 0 0.375rem 0.375rem;
            contain: layout;
            overflow: auto;
        }

        .nav-tabs {
            border-bottom: 2px solid #dee2e6;
        }

        .nav-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            color: #6c757d;
            padding: 0.75rem 1.5rem;
            transition: all 0.2s ease;
        }

        .nav-tabs .nav-link:hover {
            border-bottom-color: #2352a1;
            color: #2352a1;
        }

        .nav-tabs .nav-link.active {
            color: #2352a1;
            border-bottom-color: #2352a1;
            font-weight: 600;
        }
    </style>
@endsection

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y master-list-container">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center py-3 mb-4">
                    <h5 class="fw-bold mb-0">
                        <span class="text-muted fw-light">Finance /</span> Vendor Management
                    </h5>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs mb-4" id="vendorManagementTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="vendor-data-tab" data-bs-toggle="tab" data-bs-target="#vendor-data" type="button" role="tab" aria-controls="vendor-data" aria-selected="true">
                    Vendor Data
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="vendor-contract-tab" data-bs-toggle="tab" data-bs-target="#vendor-contract" type="button" role="tab" aria-controls="vendor-contract" aria-selected="false">
                    Vendor Contract
                </button>
            </li>
        </ul>

        <div class="tab-content" id="vendorManagementTabContent">
            <div class="tab-pane fade show active" id="vendor-data" role="tabpanel" aria-labelledby="vendor-data-tab">
                <section class="filter-section mb-4" aria-labelledby="filter-vendor-heading">
                    <header class="filter-header" onclick="toggleVendorFilter()" role="button" tabindex="0" aria-expanded="false" aria-controls="filter-vendor-content">
                        <span id="filter-vendor-heading">
                            <i class="icon-base bx bx-filter me-2" aria-hidden="true"></i>
                            Filter
                        </span>
                        <i class="icon-base bx bx-chevron-down" id="filter-vendor-chevron" aria-hidden="true"></i>
                    </header>
                    <div class="filter-content" id="filter-vendor-content" role="region" aria-labelledby="filter-vendor-heading">
                        <form id="filterVendorForm" class="dark-mode-compatible">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-body">Vendor Name</label>
                                    <input type="text" class="form-control bg-body text-body" id="filterVendorName" placeholder="Search vendor name...">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-body">Status</label>
                                    <select class="form-select bg-body text-body" id="filterVendorStatus">
                                        <option value="">All</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="col-12 d-flex justify-content-center gap-2">
                                    <button type="button" class="btn btn-primary" onclick="applyVendorFilter()">
                                        <i class="icon-base bx bx-search me-1"></i>
                                        Search
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="resetVendorFilter()">
                                        <i class="icon-base bx bx-refresh me-1"></i>
                                        Reset
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </section>

                <section class="list-section" id="vendorDataSection" aria-labelledby="vendor-data-heading">
                    <header class="list-header">
                        <div class="d-flex align-items-center">
                            <h5 id="vendor-data-heading" class="fw-semibold mb-0">List Vendor Data</h5>
                        </div>
                        {{-- Create/Update functionality disabled - only List view is available --}}
                    </header>

                    <div class="list-partial-content">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="vendorDataTable">
                                <thead class="table-light dark:table-dark">
                                    <tr>
                                        <th class="text-center">Vendor ID</th>
                                        <th class="text-center">Vendor Name</th>
                                        <th width="120" class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="vendorDataTableBody">
                                    <tr>
                                        <td colspan="3" class="text-center py-4">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <div class="mt-2">Loading data...</div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>

            <div class="tab-pane fade" id="vendor-contract" role="tabpanel" aria-labelledby="vendor-contract-tab">
                <section class="filter-section mb-4" aria-labelledby="filter-contract-heading">
                    <header class="filter-header" onclick="toggleContractFilter()" role="button" tabindex="0" aria-expanded="false" aria-controls="filter-contract-content">
                        <span id="filter-contract-heading">
                            <i class="icon-base bx bx-filter me-2" aria-hidden="true"></i>
                            Filter
                        </span>
                        <i class="icon-base bx bx-chevron-down" id="filter-contract-chevron" aria-hidden="true"></i>
                    </header>
                    <div class="filter-content" id="filter-contract-content" role="region" aria-labelledby="filter-contract-heading">
                        <form id="filterContractForm" class="dark-mode-compatible">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-body">Contract Number</label>
                                    <input type="text" class="form-control bg-body text-body" id="filterContractNumber" placeholder="Search contract number...">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-body">Vendor ID</label>
                                    <input type="text" class="form-control bg-body text-body" id="filterContractVendorID" placeholder="Search vendor ID...">
                                </div>
                                <div class="col-12 d-flex justify-content-center gap-2">
                                    <button type="button" class="btn btn-primary" onclick="applyContractFilter()">
                                        <i class="icon-base bx bx-search me-1"></i>
                                        Search
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="resetContractFilter()">
                                        <i class="icon-base bx bx-refresh me-1"></i>
                                        Reset
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </section>

                <section class="list-section" id="vendorContractSection" aria-labelledby="vendor-contract-heading">
                    <header class="list-header">
                        <div class="d-flex align-items-center">
                            <h5 id="vendor-contract-heading" class="fw-semibold mb-0">List Vendor Contract</h5>
                        </div>
                    </header>

                    <div class="list-partial-content">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="vendorContractTable">
                                <thead class="table-light dark:table-dark">
                                    <tr>
                                        <th class="text-center">Contract Number</th>
                                        <th class="text-center">Vendor ID</th>
                                        <th class="text-center">Company Name</th>
                                        <th class="text-center">Start Date</th>
                                        <th class="text-center">End Date</th>
                                    </tr>
                                </thead>
                                <tbody id="vendorContractTableBody">
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <div class="mt-2">Loading data...</div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        'use strict';

        let vendorDataTable;
        let vendorContractTable;

        document.addEventListener('DOMContentLoaded', function () {
            const vendorTbody = document.getElementById('vendorDataTableBody');
            vendorTbody.innerHTML = '';
            initializeVendorDataTable();
            loadVendorData();

            const contractTbody = document.getElementById('vendorContractTableBody');
            contractTbody.innerHTML = '';
            initializeVendorContractTable();
            loadVendorContractData();
        });

        function initializeVendorDataTable() {
            vendorDataTable = $('#vendorDataTable').DataTable({
                processing: true,
                serverSide: false,
                responsive: true,
                data: [],
                order: [[0, 'asc']],
                paging: true,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                columns: [
                    {
                        data: 'vendorID',
                        name: 'Vendor ID',
                        className: 'text-center',
                        defaultContent: '',
                        render: function (data, type, row) {
                            return data || row.VendorID || row.vendorID || '';
                        }
                    },
                    {
                        data: 'vendorName',
                        name: 'Vendor Name',
                        className: 'text-center',
                        defaultContent: '',
                        render: function (data, type, row) {
                            return data || row.VendorName || row.vendorName || '';
                        }
                    },
                    {
                        data: 'isActive',
                        name: 'Status',
                        width: '120px',
                        className: 'text-center',
                        defaultContent: false,
                        render: function (data, type, row) {
                            const isActive = data !== undefined ? data : (row.IsActive !== undefined ? row.IsActive : (row.isActive !== undefined ? row.isActive : false));
                            if (isActive === true || isActive === 'true') {
                                return '<span class="badge bg-label-success">Active</span>';
                            }
                            return '<span class="badge bg-label-secondary">Inactive</span>';
                        }
                    }
                ]
            });
        }

        function initializeVendorContractTable() {
            vendorContractTable = $('#vendorContractTable').DataTable({
                processing: true,
                serverSide: false,
                responsive: true,
                data: [],
                order: [[0, 'asc']],
                paging: true,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                columns: [
                    {
                        data: 'contractNumber',
                        name: 'Contract Number',
                        className: 'text-center',
                        defaultContent: '',
                        render: function (data, type, row) {
                            return data || row.ContractNumber || row.contractNumber || '';
                        }
                    },
                    {
                        data: 'vendorID',
                        name: 'Vendor ID',
                        className: 'text-center',
                        defaultContent: '-',
                        render: function (data, type, row) {
                            return (data || row.VendorID || row.vendorID || '') || '-';
                        }
                    },
                    {
                        data: 'companyName',
                        name: 'Company Name',
                        className: 'text-center',
                        defaultContent: '-',
                        render: function (data, type, row) {
                            return (data || row.CompanyName || row.companyName || '') || '-';
                        }
                    },
                    {
                        data: 'startDate',
                        name: 'Start Date',
                        className: 'text-center',
                        defaultContent: '-',
                        render: function (data, type, row) {
                            const dateValue = data || row.StartDate || row.startDate;
                            if (!dateValue) return '-';
                            const date = new Date(dateValue);
                            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                        }
                    },
                    {
                        data: 'endDate',
                        name: 'End Date',
                        className: 'text-center',
                        defaultContent: '-',
                        render: function (data, type, row) {
                            const dateValue = data || row.EndDate || row.endDate;
                            if (!dateValue) return '-';
                            const date = new Date(dateValue);
                            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                        }
                    }
                ]
            });
        }

        function toggleVendorFilter() {
            const filterContent = document.getElementById('filter-vendor-content');
            const filterChevron = document.getElementById('filter-vendor-chevron');
            const isExpanded = filterContent.classList.contains('show');

            if (isExpanded) {
                filterContent.classList.remove('show');
                filterChevron.classList.remove('bx-chevron-up');
                filterChevron.classList.add('bx-chevron-down');
            } else {
                filterContent.classList.add('show');
                filterChevron.classList.remove('bx-chevron-down');
                filterChevron.classList.add('bx-chevron-up');
            }
        }

        function toggleContractFilter() {
            const filterContent = document.getElementById('filter-contract-content');
            const filterChevron = document.getElementById('filter-contract-chevron');
            const isExpanded = filterContent.classList.contains('show');

            if (isExpanded) {
                filterContent.classList.remove('show');
                filterChevron.classList.remove('bx-chevron-up');
                filterChevron.classList.add('bx-chevron-down');
            } else {
                filterContent.classList.add('show');
                filterChevron.classList.remove('bx-chevron-down');
                filterChevron.classList.add('bx-chevron-up');
            }
        }

        async function loadVendorData() {
            try {
                const tbody = document.getElementById('vendorDataTableBody');
                tbody.innerHTML = `
                    <tr>
                        <td colspan="3" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="mt-2">Loading data...</div>
                        </td>
                    </tr>
                `;

                const response = await apiCall('Procurement', '/Procurement/Master/Vendors', 'GET');
                const rawData = Array.isArray(response) ? response : (response.data || response.Data || []);

                if (Array.isArray(rawData)) {
                    vendorDataTable.clear();
                    if (rawData.length > 0) {
                        const mappedData = rawData.map(item => {
                            const getValue = (camel, pascal, defaultValue) => {
                                if (item && item[camel] !== undefined && item[camel] !== null) return item[camel];
                                if (item && item[pascal] !== undefined && item[pascal] !== null) return item[pascal];
                                return defaultValue;
                            };

                            return {
                                id: getValue('id', 'ID', null),
                                vendorID: getValue('vendorID', 'VendorID', ''),
                                vendorName: getValue('vendorName', 'VendorName', ''),
                                isActive: getValue('isActive', 'IsActive', false)
                            };
                        });
                        vendorDataTable.rows.add(mappedData);
                    } else {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">No data available</td>
                            </tr>
                        `;
                    }
                    vendorDataTable.draw();
                }
            } catch (error) {
                const tbody = document.getElementById('vendorDataTableBody');
                tbody.innerHTML = `
                    <tr>
                        <td colspan="3" class="text-center py-4 text-danger">
                            <i class="icon-base bx bx-error-circle"></i>
                            <div class="mt-2">Failed to load data</div>
                        </td>
                    </tr>
                `;
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'Failed to load data'
                });
            }
        }

        async function loadVendorContractData() {
            try {
                const tbody = document.getElementById('vendorContractTableBody');
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="mt-2">Loading data...</div>
                        </td>
                    </tr>
                `;

                const response = await apiCall('Procurement', '/Procurement/Master/VendorContracts', 'GET');
                const rawData = Array.isArray(response) ? response : (response.data || response.Data || []);

                if (Array.isArray(rawData)) {
                    vendorContractTable.clear();
                    if (rawData.length > 0) {
                        const mappedData = rawData.map(item => {
                            const getValue = (camel, pascal, defaultValue) => {
                                if (item && item[camel] !== undefined && item[camel] !== null) return item[camel];
                                if (item && item[pascal] !== undefined && item[pascal] !== null) return item[pascal];
                                return defaultValue;
                            };

                            return {
                                id: getValue('id', 'ID', null),
                                contractNumber: getValue('contractNumber', 'ContractNumber', '') || '',
                                vendorID: getValue('vendorID', 'VendorID', null),
                                companyName: getValue('companyName', 'CompanyName', null),
                                startDate: getValue('startDate', 'StartDate', null),
                                endDate: getValue('endDate', 'EndDate', null)
                            };
                        });
                        vendorContractTable.rows.add(mappedData);
                    } else {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No data available</td>
                            </tr>
                        `;
                    }
                    vendorContractTable.draw();
                }
            } catch (error) {
                const tbody = document.getElementById('vendorContractTableBody');
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-4 text-danger">
                            <i class="icon-base bx bx-error-circle"></i>
                            <div class="mt-2">Failed to load data</div>
                        </td>
                    </tr>
                `;
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'Failed to load data'
                });
            }
        }

        function applyVendorFilter() {
            const vendorName = $('#filterVendorName').val().toLowerCase();
            const status = $('#filterVendorStatus').val();

            vendorDataTable.search(function () {
                const data = this;
                const matchesVendorName = !vendorName || (data.vendorName && data.vendorName.toLowerCase().includes(vendorName)) || (data.VendorName && data.VendorName.toLowerCase().includes(vendorName));
                const matchesStatus = !status ||
                    (status === 'active' && (data.isActive === true || data.IsActive === true || data.isActive === 'true')) ||
                    (status === 'inactive' && (data.isActive === false || data.IsActive === false || data.isActive === 'false'));
                return matchesVendorName && matchesStatus;
            }).draw();
        }

        function resetVendorFilter() {
            $('#filterVendorName').val('');
            $('#filterVendorStatus').val('');
            vendorDataTable.search('').draw();
        }

        function applyContractFilter() {
            const contractNumber = $('#filterContractNumber').val().toLowerCase();
            const vendorID = $('#filterContractVendorID').val().toLowerCase();

            vendorContractTable.search(function () {
                const data = this;
                const matchesContractNumber = !contractNumber || (data.contractNumber && data.contractNumber.toLowerCase().includes(contractNumber)) || (data.ContractNumber && data.ContractNumber.toLowerCase().includes(contractNumber));
                const matchesVendorID = !vendorID || (data.vendorID && data.vendorID.toLowerCase().includes(vendorID)) || (data.VendorID && data.VendorID.toLowerCase().includes(vendorID));
                return matchesContractNumber && matchesVendorID;
            }).draw();
        }

        function resetContractFilter() {
            $('#filterContractNumber').val('');
            $('#filterContractVendorID').val('');
            vendorContractTable.search('').draw();
        }
    </script>
@endsection
