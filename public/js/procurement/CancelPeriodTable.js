/**
 * CancelPeriodTable Module
 * Handles DataTable initialization and rendering for PO list
 * Uses CreateTable helper with server-side processing like other table modules
 */
class CancelPeriodTable {
    constructor(managerInstance) {
        this.manager = managerInstance;
        this.dataTable = null;
        this.allPurchaseTypes = null;
        this.allPurchaseSubTypes = null;
    }

    /**
     * Initialize DataTable
     */
    async initializeDataTable() {
        // Initialize DataTable using CreateTable helper with GetGrid endpoint
        if (typeof CreateTable === 'undefined' || typeof ColumnBuilder === 'undefined') {
            console.error('CreateTable or ColumnBuilder helper not found. Make sure DataTableHelper.js is loaded.');
            return;
        }

        // Load PurchaseTypes BEFORE initializing DataTable to ensure formatPurchaseType works correctly
        await this.loadPurchaseTypesForFormatting();

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
            endpoint: '/Procurement/PurchaseOrder/PurchaseOrders/Grid',
            tableId: '#poTable',
            leftColumns: 2, // Fixed columns: Action, Purchase Order Number
            columns: ColumnBuilder([
                {
                    type: 'actions',
                    buttons: [
                        {
                            label: 'Select',
                            icon: '<i class="bx bx-pointer"></i>',
                            className: 'btn-primary',
                            title: 'Select PO',
                            showIf: () => true
                        }
                    ]
                },
                { data: 'purchOrderID', title: 'Purchase Order Number' },
                { data: 'purchOrderName', title: 'Purchase Order Name' },
                { 
                    data: 'purchType', 
                    title: 'Purchase Type',
                    render: function(data, type, row) {
                        return self.formatPurchaseType(data, row);
                    }
                },
                { 
                    data: 'purchSubType', 
                    title: 'Purchase Sub Type',
                    render: function(data, type, row) {
                        return self.formatPurchaseSubType(data, row);
                    }
                },
                { data: 'approvalStatus', title: 'Status' },
                { data: 'poAmount', title: 'PO Amount', type: 'currency' },
                { data: 'poDate', title: 'PO Date', type: 'datetime' },
                { data: 'prNumber', title: 'PR Number' },
                { data: 'mstVendorVendorName', title: 'Vendor Name' },
                { data: 'companyName', title: 'Company' },
                { 
                    data: 'poAuthor', 
                    title: 'PO Author',
                    render: function(data, type, row) {
                        const poAuthorId = data || row.poAuthor || row.POAuthor || '';
                        if (!poAuthorId || poAuthorId === '-') return '-';
                        const escapedId = self.escapeHtml(poAuthorId);
                        return `<span class="employee-name" data-employee-id="${escapedId}">${escapedId}</span>`;
                    }
                },
                { 
                    data: 'prRequestor', 
                    title: 'PR Requestor',
                    render: function(data, type, row) {
                        const requestorId = data || row.prRequestor || row.PRRequestor || '';
                        if (!requestorId || requestorId === '-') return '-';
                        const escapedId = self.escapeHtml(requestorId);
                        return `<span class="employee-name" data-employee-id="${escapedId}">${escapedId}</span>`;
                    }
                }
            ]),
            filter: buildFilter.bind(this),
            order: [[7, 'desc']], // Order by PO Date (column index 7) descending
            serverSide: true
        });

        // Bind action buttons
        $(document).off('click', '#poTable .action-btn').on('click', '#poTable .action-btn', function() {
            const action = $(this).data('action');
            const row = self.dataTable.row($(this).closest('tr')).data();
            const poNumber = row.purchOrderID || row.PurchOrderID || '';

            switch(action) {
                case 'select':
                    if (self.manager && self.manager.viewModule && self.manager.viewModule.selectPO) {
                        self.manager.viewModule.selectPO(poNumber);
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

        return this.dataTable;
    }

    /**
     * Load PurchaseTypes and PurchaseSubTypes for formatting
     */
    async loadPurchaseTypesForFormatting() {
        // First, check if manager's viewModule already has purchase types loaded (to share cache)
        if (!this.allPurchaseTypes && this.manager && this.manager.viewModule && this.manager.viewModule.allPurchaseTypes) {
            this.allPurchaseTypes = this.manager.viewModule.allPurchaseTypes;
            return; // Already loaded, no need to fetch
        }
        
        // Load PurchaseTypes if not already loaded
        // Use shared cache to prevent duplicate API calls
        if (!this.allPurchaseTypes && this.manager && this.manager.apiModule) {
            try {
                // Use API module which uses shared cache (ProcurementSharedCache)
                this.allPurchaseTypes = await this.manager.apiModule.getPurchaseTypes();
                
                // Share with viewModule if available
                if (this.manager && this.manager.viewModule) {
                    this.manager.viewModule.allPurchaseTypes = this.allPurchaseTypes;
                }
            } catch (error) {
                console.error('Error loading purchase types:', error);
                this.allPurchaseTypes = [];
            }
        }
        
        // Initialize PurchaseSubTypes Map for efficiency
        if (!this.allPurchaseSubTypes) {
            this.allPurchaseSubTypes = new Map(); // key: typeId, value: subTypes array
        }
    }

    /**
     * Format PurchaseType with Category
     * Returns escaped HTML string for safe display
     */
    formatPurchaseType(purchType, row, escapeHtml = true) {
        // Handle null/undefined
        if (!purchType && purchType !== 0) {
            return escapeHtml ? this.escapeHtml('-') : '-';
        }
        
        // Convert to string if it's a number
        const purchTypeStr = typeof purchType === 'number' ? purchType.toString() : String(purchType || '');
        
        if (purchTypeStr.trim() === '') {
            return escapeHtml ? this.escapeHtml('-') : '-';
        }
        
        // Check if it's already formatted (contains space and not just a number)
        if (purchTypeStr.includes(' ') && isNaN(parseInt(purchTypeStr.trim()))) {
            return escapeHtml ? this.escapeHtml(purchTypeStr) : purchTypeStr;
        }
        
        // If it's a number (ID), try to format it
        const typeId = parseInt(purchTypeStr.trim(), 10);
        if (!isNaN(typeId) && typeId > 0) {
            // Check if allPurchaseTypes is loaded
            if (!this.allPurchaseTypes || !Array.isArray(this.allPurchaseTypes) || this.allPurchaseTypes.length === 0) {
                // Purchase types not loaded yet, return ID for now
                console.warn('Purchase types not loaded yet for typeId:', typeId);
                return escapeHtml ? this.escapeHtml(purchTypeStr) : purchTypeStr;
            }
            
            const type = this.allPurchaseTypes.find(t => {
                const tId = parseInt(t.ID || t.id || '0', 10);
                return tId === typeId;
            });
            
            if (type) {
                const prType = type.PurchaseRequestType || type.purchaseRequestType || '';
                const category = type.Category || type.category || '';
                if (!category) {
                    return escapeHtml ? this.escapeHtml(prType) : prType;
                }
                // Jika PurchaseRequestType dan Category sama, cukup tampilkan salah satu
                if (prType === category) {
                    return escapeHtml ? this.escapeHtml(prType) : prType;
                }
                const formatted = `${prType} ${category}`;
                return escapeHtml ? this.escapeHtml(formatted) : formatted;
            } else {
                // Type ID not found in allPurchaseTypes
                console.warn('Purchase type not found for typeId:', typeId);
            }
        }
        
        // If not found or invalid, return as is
        return escapeHtml ? this.escapeHtml(purchTypeStr) : purchTypeStr;
    }

    /**
     * Format PurchaseSubType
     * Returns escaped HTML string for safe display
     */
    formatPurchaseSubType(purchSubType, row, escapeHtml = true) {
        // Handle null/undefined
        if (!purchSubType && purchSubType !== 0) {
            return escapeHtml ? this.escapeHtml('-') : '-';
        }
        
        // Convert to string if it's a number
        const purchSubTypeStr = typeof purchSubType === 'number' ? purchSubType.toString() : String(purchSubType || '');
        
        if (purchSubTypeStr.trim() === '') {
            return escapeHtml ? this.escapeHtml('-') : '-';
        }
        
        // Check if it's already formatted (not just a number)
        if (isNaN(parseInt(purchSubTypeStr.trim()))) {
            return escapeHtml ? this.escapeHtml(purchSubTypeStr) : purchSubTypeStr;
        }
        
        // If it's a number (ID), try to format it
        const subTypeId = parseInt(purchSubTypeStr.trim(), 10);
        if (!isNaN(subTypeId) && subTypeId > 0 && this.allPurchaseSubTypes) {
            // Try to find in allPurchaseSubTypes (which should be loaded as array, not Map)
            let foundSubType = null;
            
            // If allPurchaseSubTypes is a Map, iterate through values
            if (this.allPurchaseSubTypes instanceof Map) {
                for (const subTypes of this.allPurchaseSubTypes.values()) {
                    if (Array.isArray(subTypes)) {
                        foundSubType = subTypes.find(st => 
                            parseInt(st.ID || st.id || '0', 10) === subTypeId
                        );
                        if (foundSubType) break;
                    }
                }
            } else if (Array.isArray(this.allPurchaseSubTypes)) {
                // If it's an array (fallback from original code)
                foundSubType = this.allPurchaseSubTypes.find(st => 
                    parseInt(st.ID || st.id || '0', 10) === subTypeId
                );
            }
            
            if (foundSubType) {
                const formatted = foundSubType.PurchaseRequestSubType || foundSubType.purchaseRequestSubType || '';
                return escapeHtml ? this.escapeHtml(formatted) : formatted;
            }
        }
        
        // If not found or invalid, return as is
        return escapeHtml ? this.escapeHtml(purchSubTypeStr) : purchSubTypeStr;
    }

    /**
     * Update employee names in DataTable after draw
     */
    async updateEmployeeNamesInTable() {
        if (!this.manager || !this.manager.employeeCacheModule) return;

        const employeeNameSpans = document.querySelectorAll('#poTable .employee-name');
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
     * Search PO (reload table with filters)
     */
    searchPO() {
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
    window.CancelPeriodTable = CancelPeriodTable;
}

