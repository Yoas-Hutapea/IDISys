@extends('layouts.app')

@section('title', 'Master Document Checklist')

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

        .form-check.form-switch .form-check-input {
            width: 3.5rem;
            height: 1.75rem;
            cursor: pointer;
        }

        .form-check.form-switch .form-check-label {
            margin-left: 0.75rem;
            font-weight: 500;
            line-height: 1.75rem;
        }
    </style>
@endsection

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y master-list-container">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center py-3 mb-4">
                    <h5 class="fw-bold mb-0">
                        <span class="text-muted fw-light">Finance AP /</span> Master Document Checklist
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
                <form id="filterForm" class="dark-mode-compatible">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-body">Document Name</label>
                            <input type="text" class="form-control bg-body text-body" id="filterDocumentName" placeholder="Search document name...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-body">Status</label>
                            <select class="form-select bg-body text-body" id="filterStatus">
                                <option value="">All</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12 d-flex justify-content-center gap-2">
                            <button type="button" class="btn btn-primary" onclick="applyFilter()">
                                <i class="icon-base bx bx-search me-1"></i>
                                Search
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetFilter()">
                                <i class="icon-base bx bx-refresh me-1"></i>
                                Reset
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <section class="list-section" id="listSection" aria-labelledby="list-heading">
            <header class="list-header">
                <div class="d-flex align-items-center">
                    <h5 id="list-heading" class="fw-semibold mb-0">List Document Checklist</h5>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <i class="icon-base bx bx-plus me-1"></i>
                        Add New
                    </button>
                </div>
            </header>

            <div class="list-partial-content">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="documentChecklistTable">
                        <thead class="table-light dark:table-dark">
                            <tr>
                                <th width="100" class="text-center">Actions</th>
                                <th class="text-center">Document Name</th>
                                <th width="120" class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="documentChecklistTableBody">
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

    <div class="modal fade" id="addEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Document Checklist</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addEditForm">
                    <div class="modal-body">
                        <div class="row g-3 align-items-center">
                            <div class="col-12">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <label class="form-label mb-0">Document Name <span class="text-danger">*</span></label>
                                    </div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="modalDocumentName" required>
                                        <div class="invalid-feedback">Document Name is required.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <label class="form-label mb-0">Status <span class="text-danger">*</span></label>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="modalIsActiveSwitch" onchange="setStatus(this.checked)" checked>
                                            <label class="form-check-label" for="modalIsActiveSwitch" id="statusLabel">Yes</label>
                                            <input type="hidden" id="modalIsActive" value="true">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        'use strict';

        let documentChecklistTable;
        let currentEditId = null;

        document.addEventListener('DOMContentLoaded', function () {
            const tbody = document.getElementById('documentChecklistTableBody');
            tbody.innerHTML = '';

            initializeDataTable();
            loadData();
        });

        function initializeDataTable() {
            documentChecklistTable = $('#documentChecklistTable').DataTable({
                processing: true,
                serverSide: false,
                responsive: true,
                data: [],
                order: [[1, 'asc']],
                paging: true,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                columns: [
                    {
                        data: null,
                        orderable: false,
                        width: '100px',
                        name: 'Actions',
                        className: 'text-center',
                        defaultContent: '',
                        render: function (data, type, row) {
                            const id = row.id || row.ID || 0;
                            return `
                                <div class="d-flex justify-content-center gap-2">
                                    <button type="button" class="btn btn-sm btn-warning action-btn" onclick="editRecord(${id})" title="Edit">
                                        <i class="icon-base bx bx-edit"></i>
                                    </button>
                                </div>
                            `;
                        }
                    },
                    {
                        data: 'documentName',
                        name: 'Document Name',
                        className: 'text-center',
                        defaultContent: '',
                        render: function (data, type, row) {
                            return data || row.DocumentName || row.documentName || '';
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

        function toggleFilter() {
            const filterContent = document.getElementById('filter-content');
            const filterChevron = document.getElementById('filter-chevron');
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

        async function loadData() {
            try {
                const tbody = document.getElementById('documentChecklistTableBody');
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="mt-2">Loading data...</div>
                        </td>
                    </tr>
                `;

                const response = await apiCall('Procurement', '/Finance/EBilling/InvoiceDocumentChecklists', 'GET');
                const rawData = Array.isArray(response) ? response : (response.data || response.Data || []);

                if (Array.isArray(rawData)) {
                    documentChecklistTable.clear();
                    if (rawData.length > 0) {
                        const mappedData = rawData.map(item => {
                            const getValue = (camel, pascal, defaultValue) => {
                                if (item && item[camel] !== undefined && item[camel] !== null) return item[camel];
                                if (item && item[pascal] !== undefined && item[pascal] !== null) return item[pascal];
                                return defaultValue;
                            };

                            const docName = getValue('documentName', 'DocumentName', '');

                            return {
                                id: getValue('id', 'ID', null),
                                documentName: docName !== null && docName !== undefined ? String(docName) : '',
                                fileName: getValue('fileName', 'FileName', null),
                                filePath: getValue('filePath', 'FilePath', null),
                                isActive: getValue('isActive', 'IsActive', false),
                                createdBy: getValue('createdBy', 'CreatedBy', ''),
                                createdDate: getValue('createdDate', 'CreatedDate', null),
                                updatedBy: getValue('updatedBy', 'UpdatedBy', null),
                                updatedDate: getValue('updatedDate', 'UpdatedDate', null)
                            };
                        });
                        documentChecklistTable.rows.add(mappedData);
                    } else {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">No data available</td>
                            </tr>
                        `;
                    }
                    documentChecklistTable.draw();
                }
            } catch (error) {
                const tbody = document.getElementById('documentChecklistTableBody');
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

        function applyFilter() {
            const documentName = $('#filterDocumentName').val().toLowerCase();
            const status = $('#filterStatus').val();

            documentChecklistTable.search(function () {
                const data = this;
                const matchesDocumentName = !documentName || (data.documentName && data.documentName.toLowerCase().includes(documentName)) || (data.DocumentName && data.DocumentName.toLowerCase().includes(documentName));
                const matchesStatus = !status ||
                    (status === 'active' && (data.isActive === true || data.IsActive === true || data.isActive === 'true')) ||
                    (status === 'inactive' && (data.isActive === false || data.IsActive === false || data.isActive === 'false'));
                return matchesDocumentName && matchesStatus;
            });

            documentChecklistTable.draw();
        }

        function resetFilter() {
            $('#filterDocumentName').val('');
            $('#filterStatus').val('');
            documentChecklistTable.search('').draw();
        }

        function openAddModal() {
            currentEditId = null;
            $('#modalTitle').text('Add Document Checklist');
            $('#modalDocumentName').val('');
            $('#modalIsActiveSwitch').prop('checked', true);
            $('#modalIsActive').val('true');
            $('#statusLabel').text('Yes');
            $('#addEditForm').removeClass('was-validated');
            $('#addEditModal').modal('show');
        }

        function editRecord(id) {
            const data = documentChecklistTable.rows().data().toArray().find(item => (item.id || item.ID) === id);
            if (!data) return;

            currentEditId = id;
            $('#modalTitle').text('Edit Document Checklist');
            $('#modalDocumentName').val(data.documentName || data.DocumentName || '');

            const isActive = data.isActive !== undefined ? data.isActive : data.IsActive;
            $('#modalIsActiveSwitch').prop('checked', isActive === true || isActive === 'true');
            $('#modalIsActive').val(isActive === true || isActive === 'true' ? 'true' : 'false');
            $('#statusLabel').text(isActive === true || isActive === 'true' ? 'Yes' : 'No');

            $('#addEditForm').removeClass('was-validated');
            $('#addEditModal').modal('show');
        }

        function setStatus(isActive) {
            $('#modalIsActive').val(isActive ? 'true' : 'false');
            $('#statusLabel').text(isActive ? 'Yes' : 'No');
        }

        $('#addEditForm').on('submit', async function (e) {
            e.preventDefault();

            if (!this.checkValidity()) {
                $(this).addClass('was-validated');
                return;
            }

            const payload = {
                documentName: $('#modalDocumentName').val().trim(),
                isActive: $('#modalIsActive').val() === 'true'
            };

            try {
                if (currentEditId) {
                    await apiCall('Procurement', `/Finance/EBilling/InvoiceDocumentChecklists/${currentEditId}`, 'PUT', payload);
                } else {
                    await apiCall('Procurement', '/Finance/EBilling/InvoiceDocumentChecklists', 'POST', payload);
                }

                $('#addEditModal').modal('hide');
                await loadData();

                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: currentEditId ? 'Document checklist updated successfully' : 'Document checklist added successfully',
                    timer: 1500,
                    showConfirmButton: false
                });
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'Failed to save data'
                });
            }
        });
    </script>
@endsection
