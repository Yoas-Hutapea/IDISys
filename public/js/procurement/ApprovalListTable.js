/**
 * ApprovalListTable Module
 * Handles DataTable initialization, rendering, and employee name updates for Approval List
 */
class ApprovalListTable {
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
            endpoint: '/Procurement/PurchaseRequest/PurchaseRequestApprovals/Grid',
            tableId: '#approvalTable',
            columns: ColumnBuilder([
                {
                    type: 'actions',
                    buttons: [
                        {
                            label: 'View',
                            icon: '<i class="bx bx-pointer"></i>',
                            className: 'btn-primary',
                            title: 'View & Approve',
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
        $(document).off('click', '#approvalTable .action-btn').on('click', '#approvalTable .action-btn', function() {
            const action = $(this).data('action');
            const row = self.dataTable.row($(this).closest('tr')).data();
            // Handle both camelCase and PascalCase property names
            const prNumber = row.purchReqNumber || row.PurchReqNumber || '';

            switch(action) {
                case 'view':
                    if (self.manager && self.manager.viewModule && self.manager.viewModule.viewApproval) {
                        self.manager.viewModule.viewApproval(prNumber);
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
        // Get all employee IDs from the table
        const employeeNameSpans = document.querySelectorAll('#approvalTable .employee-name');
        if (employeeNameSpans.length === 0) return;
        
        // Collect unique employee IDs
        const employeeIds = new Set();
        employeeNameSpans.forEach(span => {
            const employeeId = span.getAttribute('data-employee-id');
            if (employeeId && employeeId !== '-') {
                employeeIds.add(employeeId);
            }
        });
        
        if (employeeIds.size === 0) return;
        
        // Use shared cache or employee cache module if available
        let employeeCache = null;
        if (window.procurementSharedCache && window.procurementSharedCache.batchGetEmployeeNames) {
            employeeCache = window.procurementSharedCache;
        } else if (this.manager && this.manager.employeeCacheModule && this.manager.employeeCacheModule.batchGetEmployeeNames) {
            employeeCache = this.manager.employeeCacheModule;
        } else if (window.prListManager && window.prListManager.employeeCacheModule && window.prListManager.employeeCacheModule.batchGetEmployeeNames) {
            employeeCache = window.prListManager.employeeCacheModule;
        }
        
        // Batch lookup all employee names at once (optimized - single API call)
        let nameMap = new Map();
        if (employeeCache && employeeCache.batchGetEmployeeNames) {
            nameMap = await employeeCache.batchGetEmployeeNames(Array.from(employeeIds));
        } else {
            // Fallback: individual lookups (less efficient)
            const lookupPromises = Array.from(employeeIds).map(async (employeeId) => {
                if (employeeCache && employeeCache.getEmployeeNameByEmployId) {
                    const name = await employeeCache.getEmployeeNameByEmployId(employeeId);
                    return { employeeId, name };
                }
                return { employeeId, name: '' };
            });
            
            const results = await Promise.all(lookupPromises);
            results.forEach(({ employeeId, name }) => {
                if (name) {
                    nameMap.set(employeeId.trim().toLowerCase(), name);
                }
            });
        }
        
        // Update all spans with employee names
        employeeNameSpans.forEach(span => {
            const employeeId = span.getAttribute('data-employee-id');
            if (employeeId && employeeId !== '-') {
                const cacheKey = employeeId.trim().toLowerCase();
                const name = nameMap.get(cacheKey);
                if (name) {
                    span.textContent = name;
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
     * Search approval (refresh table with current filters)
     */
    searchApproval() {
        if (this.dataTable) {
            this.dataTable.ajax.reload();
        }
    }

    /**
     * Get badge class based on PR type
     */
    getBadgeClass(prType) {
        const type = prType.toLowerCase();
        if (type.includes('general')) return 'bg-label-primary';
        if (type.includes('goods')) return 'bg-label-success';
        if (type.includes('services')) return 'bg-label-warning';
        if (type.includes('works')) return 'bg-label-info';
        if (type.includes('consultancy')) return 'bg-label-secondary';
        return 'bg-label-primary';
    }

    /**
     * Get status badge class
     */
    getStatusBadgeClass(statusID) {
        if (!statusID) return 'bg-label-secondary';
        const statusClassMap = {
            1: 'bg-label-warning',   // Waiting Approval Diketahui
            2: 'bg-label-info',      // Waiting Approval Disetujui
            3: 'bg-label-primary',   // Waiting Approval Dikonfirmasi
            4: 'bg-label-success',   // Finish Purchase Request
            5: 'bg-label-danger',    // Rejected Purchase Request
            6: 'bg-label-secondary'  // Draft Create PR
        };
        return statusClassMap[statusID] || 'bg-label-secondary';
    }

    /**
     * Format currency
     */
    formatCurrency(amount) {
        // Handle null, undefined, empty string
        if (amount === null || amount === undefined || amount === '') return '-';
        // Convert to number and handle NaN - show as Rp 0
        const numAmount = parseFloat(amount);
        if (isNaN(numAmount)) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(0);
        }
        // Handle 0 - show as Rp 0
        if (numAmount === 0) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(0);
        }
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(numAmount);
    }

    /**
     * Escape HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.ApprovalListTable = ApprovalListTable;
}

