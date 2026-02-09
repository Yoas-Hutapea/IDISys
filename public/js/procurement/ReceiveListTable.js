/**
 * ReceiveListTable Module
 * Handles DataTable initialization, rendering, employee name updates, and bulk selection
 */
class ReceiveListTable {
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
            endpoint: '/Procurement/PurchaseRequest/PurchaseRequestReceives/Grid',
            tableId: '#receiveTable',
            leftColumns: 3, // Fixed columns: Checkbox, Action, Purchase Request Number
            columns: ColumnBuilder([
                {
                    // Checkbox column for bulk receive
                    data: 'purchReqNumber',
                    title: '',
                    orderable: false,
                    searchable: false,
                    width: '50px',
                    render: function(data, type, row) {
                        const prNumber = data || row.purchReqNumber || row.PurchReqNumber || '';
                        const isSelected = self.manager && self.manager.selectedPRNumbers && self.manager.selectedPRNumbers.has(prNumber);
                        const escapedPrNumber = self.escapeHtml(prNumber);
                        return `<input type="checkbox" class="row-checkbox" data-pr-number="${escapedPrNumber}" ${isSelected ? 'checked' : ''} onchange="receiveListManager.toggleRowSelection('${escapedPrNumber}', this.checked)">`;
                    }
                },
                {
                    type: 'actions',
                    buttons: [
                        {
                            label: 'View',
                            icon: '<i class="bx bx-pointer"></i>',
                            className: 'btn-primary',
                            title: 'View & Receive',
                            showIf: () => true
                        }
                    ]
                },
                { data: 'purchReqNumber', title: 'Purchase Request Number' },
                { data: 'purchReqName', title: 'Purchase Request Name' },
                { data: 'purchReqType', title: 'Purchase Request Type' },
                { data: 'purchReqSubType', title: 'Purchase Request Sub Type' },
                {
                    data: 'approvalStatus',
                    title: 'Status',
                    render: function(data, type, row) {
                    // Normalize approval status ID to integer for reliable comparisons
                    const approvalStatusIDRaw = row.mstApprovalStatusID || row.MstApprovalStatusID || null;
                    const approvalStatusID = Number.isFinite(Number(approvalStatusIDRaw)) ? parseInt(approvalStatusIDRaw, 10) : null;
                    let statusText = data || row.approvalStatus || row.ApprovalStatus || 'Draft';

                    // If mstApprovalStatusID = 4, show "Waiting Receive Purchase Request"
                    if (approvalStatusID === 4) {
                        statusText = 'Waiting Receive Purchase Request';
                    }

                    return self.escapeHtml(statusText);
                    }
                },
                {
                    data: 'pic',
                    title: 'PIC',
                    render: function(data, type, row) {
                        // Normalize approval status ID to integer for reliable comparisons
                        const approvalStatusIDRaw = row.mstApprovalStatusID || row.MstApprovalStatusID || null;
                        const approvalStatusID = Number.isFinite(Number(approvalStatusIDRaw)) ? parseInt(approvalStatusIDRaw, 10) : null;
                        // If status is "Waiting Receive Purchase Request" (ID = 4), show "-"
                        if (approvalStatusID === 4) {
                            return '-';
                        }
                        const picId = data || row.pic || row.PIC || '';
                        if (!picId || picId === '-') return '-';
                        const escapedId = self.escapeHtml(picId);
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
                        const escapedId = self.escapeHtml(requestorId);
                        return `<span class="employee-name" data-employee-id="${escapedId}">${escapedId}</span>`;
                    }
                },
                {
                    data: 'applicant',
                    title: 'Applicant',
                    render: function(data, type, row) {
                        const applicantId = data || row.applicant || row.Applicant || '';
                        if (!applicantId) return '-';
                        const escapedId = self.escapeHtml(applicantId);
                        return `<span class="employee-name" data-employee-id="${escapedId}">${escapedId}</span>`;
                    }
                },
                { data: 'createdDate', title: 'Request Date', type: 'datetime' }
            ]),
            filter: buildFilter.bind(this),
            order: [[12, 'desc']], // Order by CreatedDate (column index 12) descending
            serverSide: true
        });

        // Bind action buttons
        $(document).off('click', '#receiveTable .action-btn').on('click', '#receiveTable .action-btn', function() {
            const action = $(this).data('action');
            const row = self.dataTable.row($(this).closest('tr')).data();
            const prNumber = row.purchReqNumber || row.PurchReqNumber || '';

            switch(action) {
                case 'view':
                    if (self.manager && self.manager.viewModule && self.manager.viewModule.viewReceive) {
                        self.manager.viewModule.viewReceive(prNumber);
                    }
                    break;
            }
        });

        // Update employee names and select all checkbox after DataTable draws
        this.dataTable.on('draw', () => {
            this.updateEmployeeNamesInTable();
            if (this.manager && this.manager.updateSelectAllCheckbox) {
                this.manager.updateSelectAllCheckbox();
            }
        });

        // Initial update after table is drawn
        setTimeout(() => {
            this.updateEmployeeNamesInTable();
            if (this.manager && this.manager.updateSelectAllCheckbox) {
                this.manager.updateSelectAllCheckbox();
            }
        }, 500);
    }

    /**
     * Update employee names in DataTable after draw
     */
    async updateEmployeeNamesInTable() {
        if (!this.manager || !this.manager.employeeCacheModule) return;

        // Get all employee IDs from the table
        const employeeNameSpans = document.querySelectorAll('#receiveTable .employee-name');
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

        // Batch lookup all employee names
        if (this.manager.employeeCacheModule.batchGetEmployeeNames) {
            const nameMap = await this.manager.employeeCacheModule.batchGetEmployeeNames(Array.from(employeeIds));

            // Update all employee name spans
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
        } else {
            // Fallback: individual lookups
            const lookupPromises = Array.from(employeeIds).map(async (employeeId) => {
                const name = await this.manager.employeeCacheModule.getEmployeeNameByEmployId(employeeId);
                return { employeeId, name };
            });

            const results = await Promise.all(lookupPromises);
            const nameMap = new Map();
            results.forEach(({ employeeId, name }) => {
                if (name) {
                    nameMap.set(employeeId.trim().toLowerCase(), name);
                }
            });

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
     * Search receive (reload table with filters)
     */
    searchReceive() {
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
    window.ReceiveListTable = ReceiveListTable;
}

