@extends('layouts.app')

@section('title', 'Vendor Contract')

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
    </style>
@endsection

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y master-list-container">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center py-3 mb-4">
                    <h5 class="fw-bold mb-0">
                        <span class="text-muted fw-light">Finance / Vendor Management /</span> Vendor Contract
                    </h5>
                </div>
            </div>
        </div>

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
@endsection

@section('scripts')
    <script>
        'use strict';

        let vendorContractTable;

        document.addEventListener('DOMContentLoaded', function () {
            const contractTbody = document.getElementById('vendorContractTableBody');
            contractTbody.innerHTML = '';
            initializeVendorContractTable();
            loadVendorContractData();
        });

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
