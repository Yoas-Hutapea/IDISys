@extends('layouts.app')

@section('title', 'Vendor Data')

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
                        <span class="text-muted fw-light">Finance / Vendor Management /</span> Vendor Data
                    </h5>
                </div>
            </div>
        </div>

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
@endsection

@section('scripts')
    <script>
        'use strict';

        let vendorDataTable;

        document.addEventListener('DOMContentLoaded', function () {
            const vendorTbody = document.getElementById('vendorDataTableBody');
            vendorTbody.innerHTML = '';
            initializeVendorDataTable();
            loadVendorData();
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
    </script>
@endsection
