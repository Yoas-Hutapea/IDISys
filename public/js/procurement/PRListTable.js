/**
 * PRListTable Module
 * Handles DataTable initialization, rendering, and employee name updates
 */
class PRListTable {
    constructor(managerInstance) {
        this.manager = managerInstance;
        this.dataTable = null;
    }

    /**
     * Initialize DataTable
     */
    initializeDataTable() {
        // Initialize DataTable using CreateTable helper with GetGrid endpoint
        if (typeof CreateTable === 'undefined' || typeof ColumnBuilder === 'undefined') {
            console.error('CreateTable or ColumnBuilder helper not found. Make sure DataTableHelper.js is loaded.');
            return;
        }

        const self = this;
        
        // Build filter function for GetGrid
        const buildFilter = () => {
            if (this.manager && this.manager.filterModule && this.manager.filterModule.buildFilter) {
                return this.manager.filterModule.buildFilter();
            }
            return {};
        };

        // Initialize DataTable
        this.dataTable = CreateTable({
            apiType: 'Procurement',
            endpoint: '/Procurement/PurchaseRequest/PurchaseRequests/Grid',
            tableId: '#prTable',
            columns: ColumnBuilder([
                {
                    type: 'actions',
                    buttons: [
                        {
                            label: 'View',
                            icon: '<i class="bx bx-show"></i>',
                            className: 'btn-primary',
                            title: 'View',
                            showIf: () => true
                        },
                        {
                            label: 'Edit',
                            icon: '<i class="bx bx-edit"></i>',
                            className: 'btn-warning',
                            title: 'Edit',
                            showIf: (row) => {
                                const statusId = row.mstApprovalStatusID ?? row.MstApprovalStatusID ?? null;
                                const requestor = row.requestor ?? row.Requestor ?? '';
                                
                                // Get current user employee ID from config
                                const currentUserEmployeeID = (window.PRListConfig && window.PRListConfig.currentUserEmployeeID) || '';
                                
                                // Show Edit only if:
                                // 1. Status is 5 or 6 (draft/cancelled)
                                // 2. AND current user is the Requestor
                                if (statusId === 5 || statusId === 6 || statusId === null) {
                                    // Check if current user is Requestor
                                    if (currentUserEmployeeID && requestor) {
                                        // Normalize comparison (case-insensitive, trim whitespace)
                                        const currentUserNormalized = currentUserEmployeeID.trim().toLowerCase();
                                        const requestorNormalized = requestor.trim().toLowerCase();
                                        
                                        // Show Edit only if current user is Requestor
                                        return currentUserNormalized === requestorNormalized;
                                    }
                                    // If no current user or requestor info, don't show Edit
                                    return false;
                                }
                                
                                // For other statuses, don't show Edit
                                return false;
                            }
                        },
                        {
                            label: 'History',
                            icon: '<i class="bx bx-history"></i>',
                            className: 'btn-info',
                            title: 'History',
                            showIf: () => true
                        }
                    ]
                },
                { data: 'purchReqNumber', title: 'Purchase Request Number' },
                { data: 'purchReqName', title: 'Purchase Request Name' },
                { data: 'purchReqType', title: 'Purchase Request Type' },
                { data: 'purchReqSubType', title: 'Purchase Request Sub Type' },
                { data: 'approvalStatus', title: 'Status' },
                { 
                    data: 'pic', 
                    title: 'PIC',
                    render: function(data, type, row) {
                        const picId = data || row.pic || row.PIC || '';
                        if (!picId || picId === '-') return '-';
                        // Escape HTML for security
                        const escapedId = self.escapeHtml(picId);
                        // Return employee_id first, will be updated with name async
                        return `<span class="employee-name" data-employee-id="${escapedId}">${escapedId}</span>`;
                    }
                },
                { data: 'totalAmount', title: 'Amount PR', type: 'currency' },
                { data: 'company', title: 'Company' },
                { 
                    data: 'requestor', 
                    title: 'Requestor',
                    render: function(data, type, row) {
                        const requestorId = data || row.requestor || row.Requestor || '';
                        if (!requestorId) return '-';
                        // Escape HTML for security
                        const escapedId = self.escapeHtml(requestorId);
                        // Return employee_id first, will be updated with name async
                        return `<span class="employee-name" data-employee-id="${escapedId}">${escapedId}</span>`;
                    }
                },
                { 
                    data: 'applicant', 
                    title: 'Applicant',
                    render: function(data, type, row) {
                        const applicantId = data || row.applicant || row.Applicant || '';
                        if (!applicantId) return '-';
                        // Escape HTML for security
                        const escapedId = self.escapeHtml(applicantId);
                        // Return employee_id first, will be updated with name async
                        return `<span class="employee-name" data-employee-id="${escapedId}">${escapedId}</span>`;
                    }
                },
                { data: 'createdDate', title: 'Request Date', type: 'datetime' }
            ]),
            filter: buildFilter.bind(this),
            order: [[11, 'desc']], // Order by CreatedDate (column index 11) descending
            serverSide: true
        });

        // Bind action buttons
        $(document).off('click', '#prTable .action-btn').on('click', '#prTable .action-btn', function() {
            const action = $(this).data('action');
            const row = self.dataTable.row($(this).closest('tr')).data();
            // Handle both camelCase and PascalCase property names
            const prNumber = row.purchReqNumber || row.PurchReqNumber || '';

            switch(action) {
                case 'view':
                    if (self.manager && self.manager.viewModule && self.manager.viewModule.viewPR) {
                        self.manager.viewModule.viewPR(prNumber);
                    }
                    break;
                case 'edit':
                    if (self.manager && self.manager.editPR) {
                        self.manager.editPR(prNumber);
                    }
                    break;
                case 'history':
                    if (self.manager && self.manager.viewHistory) {
                        self.manager.viewHistory(prNumber);
                    }
                    break;
            }
        });

        // Update employee names after DataTable draws
        this.dataTable.on('draw', () => {
            this.updateEmployeeNamesInTable();
        });

        // Initial update after table is drawn
        setTimeout(() => this.updateEmployeeNamesInTable(), 500);
    }

    /**
     * Update employee names in DataTable after draw
     */
    async updateEmployeeNamesInTable() {
        if (!this.manager || !this.manager.employeeCacheModule) return;

        // Get all employee IDs from the table
        const employeeNameSpans = document.querySelectorAll('#prTable .employee-name');
        if (employeeNameSpans.length === 0) return;
        
        // Collect unique employee IDs
        // Filter out values that look like names (contain spaces) - these are likely already names, not IDs
        const employeeIds = new Set();
        employeeNameSpans.forEach(span => {
            const employeeId = span.getAttribute('data-employee-id');
            if (employeeId && employeeId !== '-') {
                // Skip if it looks like a name (contains spaces) - likely already a name, not an ID
                // Employee IDs are typically numeric or alphanumeric without spaces
                if (!employeeId.includes(' ')) {
                    employeeIds.add(employeeId);
                } else {
                    // If it's already a name, update the span text directly (no API call needed)
                    if (span.textContent === employeeId) {
                        // Already showing the name, no need to update
                    }
                }
            }
        });
        
        if (employeeIds.size === 0) return;
        
        // Batch lookup all employee names
        const nameMap = await this.manager.employeeCacheModule.batchGetEmployeeNames(Array.from(employeeIds));
        
        // Update all employee name spans
        employeeNameSpans.forEach(span => {
            const employeeId = span.getAttribute('data-employee-id');
            if (employeeId && employeeId !== '-') {
                // Skip if it looks like a name (contains spaces)
                if (!employeeId.includes(' ')) {
                    const cacheKey = employeeId.trim().toLowerCase();
                    const name = nameMap.get(cacheKey);
                    if (name) {
                        span.textContent = name;
                    }
                }
            }
        });
    }

    /**
     * Refresh table
     */
    refreshTable() {
        if (this.dataTable) {
            this.dataTable.ajax.reload();
        }
    }

    /**
     * Search PR (reload table with filters)
     */
    searchPR() {
        if (this.dataTable) {
            // Reload DataTable with new filters
            this.dataTable.ajax.reload();
        }
    }

    /**
     * Escape HTML
     */
    escapeHtml(text) {
        if (text == null) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.PRListTable = PRListTable;
}

