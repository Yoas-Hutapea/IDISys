/**
 * PostingListTable Module
 * Handles DataTable initialization, rendering, and data loading
 */
class PostingListTable {
    constructor(managerInstance) {
        this.manager = managerInstance;
        this.dataTable = null;
        this.selectedInvoiceIDs = new Set();
        
        // Expose selectedInvoiceIDs to manager for backward compatibility
        if (this.manager) {
            this.manager.selectedInvoiceIDs = this.selectedInvoiceIDs;
        }
    }

    /**
     * Initialize DataTable
     */
    initializeDataTable() {
        const self = this;
        
        this.dataTable = $('#postingTable').DataTable({
            processing: true,
            serverSide: false,
            responsive: false,
            data: [],
            order: [[4, 'desc']], // Order by Invoice Date (column index 4 after checkbox and action)
            paging: true,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            scrollX: true,
            fixedColumns: {
                leftColumns: 3 // Fixed columns: Checkbox, Action, Request Number
            },
            columns: [
                { 
                    data: null, 
                    orderable: false,
                    width: '50px',
                    name: 'Checkbox',
                    className: 'text-center',
                    defaultContent: '',
                    render: function(data, type, row) {
                        const id = row.invoiceHeaderID || row.InvoiceHeaderID || 0;
                        const isSelected = self.selectedInvoiceIDs.has(id);
                        return `
                            <input type="checkbox" class="row-checkbox" data-invoice-id="${id}" ${isSelected ? 'checked' : ''} onchange="postingListManager.toggleRowSelection(${id}, this.checked)">
                        `;
                    }
                },
                { 
                    data: null, 
                    orderable: false,
                    width: '120px',
                    name: 'Actions',
                    className: 'text-center',
                    defaultContent: '',
                    render: function(data, type, row) {
                        const id = row.invoiceHeaderID || row.InvoiceHeaderID || 0;
                        return `
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-sm btn-primary action-btn" onclick="event.stopPropagation(); postingListManager.viewPosting(${id})" title="Post">
                                    <i class="bx bx-check"></i>
                                </button>
                            </div>
                        `;
                    }
                },
                { 
                    data: 'requestNumber',
                    name: 'Request Number',
                    className: 'text-center',
                    defaultContent: ''
                },
                { 
                    data: 'invoiceNumber',
                    name: 'Invoice Number',
                    className: 'text-center',
                    defaultContent: ''
                },
                { 
                    data: 'invoiceDate',
                    name: 'Invoice Date',
                    className: 'text-center',
                    defaultContent: '',
                    render: function(data, type, row) {
                        if (!data && !row.InvoiceDate && !row.invoiceDate) return '';
                        const date = data || row.InvoiceDate || row.invoiceDate;
                        if (date instanceof Date) {
                            return date.toLocaleDateString('en-GB');
                        }
                        if (typeof date === 'string') {
                            return new Date(date).toLocaleDateString('en-GB');
                        }
                        return date;
                    }
                },
                { 
                    data: 'workflowStatus',
                    name: 'Status',
                    className: 'text-center',
                    defaultContent: ''
                },
                { 
                    data: 'picName',
                    name: 'PIC',
                    className: 'text-center',
                    defaultContent: ''
                },
                { 
                    data: 'purchOrderID',
                    name: 'PO Number',
                    className: 'text-center',
                    defaultContent: ''
                },
                { 
                    data: 'termPaidValue',
                    name: 'Term Paid Value',
                    className: 'text-center',
                    defaultContent: ''
                },
                { 
                    data: 'monthPeriod',
                    name: 'Period Month',
                    className: 'text-center',
                    defaultContent: ''
                },
                { 
                    data: 'purchaseType',
                    name: 'Purchase Type',
                    className: 'text-center',
                    defaultContent: ''
                },
                { 
                    data: 'purchaseSubType',
                    name: 'Purchase Sub Type',
                    className: 'text-center',
                    defaultContent: ''
                },
                { 
                    data: 'sonumber',
                    name: 'SONumber',
                    className: 'text-center',
                    defaultContent: ''
                },
                { 
                    data: 'siteID',
                    name: 'Site ID',
                    className: 'text-center',
                    defaultContent: ''
                },
                { 
                    data: 'siteName',
                    name: 'Site Name',
                    className: 'text-center',
                    defaultContent: ''
                },
                { 
                    data: 'poReleaseDate',
                    name: 'PO Date',
                    className: 'text-center',
                    defaultContent: '',
                    render: function(data, type, row) {
                        if (!data && !row.POReleaseDate && !row.poReleaseDate) return '';
                        const date = data || row.POReleaseDate || row.poReleaseDate;
                        if (date instanceof Date) {
                            return date.toLocaleDateString('en-GB');
                        }
                        if (typeof date === 'string') {
                            return new Date(date).toLocaleDateString('en-GB');
                        }
                        return date;
                    }
                },
                { 
                    data: 'company',
                    name: 'Company',
                    className: 'text-center',
                    defaultContent: ''
                },
                { 
                    data: 'invoiceAmount',
                    name: 'Invoice Amount',
                    className: 'text-center',
                    defaultContent: '',
                    render: function(data, type, row) {
                        const amount = data || row.InvoiceAmount || row.invoiceAmount || 0;
                        const formatCurrency = self.manager && self.manager.utilsModule ? 
                            self.manager.utilsModule.formatCurrency.bind(self.manager.utilsModule) : 
                            (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
                        return formatCurrency(amount);
                    }
                },
                { 
                    data: 'workType',
                    name: 'Work Type',
                    className: 'text-center',
                    defaultContent: ''
                }
            ],
            drawCallback: function(settings) {
                // Recalculate fixed columns layout after each draw
                setTimeout(function() {
                    try {
                        if (self.dataTable && self.dataTable.fixedColumns && self.dataTable.fixedColumns().relayout) {
                            self.dataTable.fixedColumns().relayout();
                        }
                    } catch (e) {
                        // Ignore error
                    }
                }, 100);
            }
        });

        // Update checkbox states after DataTable draw
        this.dataTable.on('draw', () => {
            if (this.manager && this.manager.actionModule) {
                this.manager.actionModule.updateSelectAllCheckbox();
            }
            // Restore checkbox states
            const checkboxes = document.querySelectorAll('#postingTable tbody .row-checkbox');
            checkboxes.forEach(cb => {
                const invoiceID = parseInt(cb.getAttribute('data-invoice-id'));
                if (!isNaN(invoiceID) && this.selectedInvoiceIDs.has(invoiceID)) {
                    cb.checked = true;
                }
            });
        });
    }

    /**
     * Load data into DataTable
     */
    async loadData() {
        try {
            const tbody = document.getElementById('postingTableBody');
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="19" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="mt-2">Loading data...</div>
                        </td>
                    </tr>
                `;
            }

            // Get filter values
            const filters = this.manager && this.manager.filterModule ? 
                this.manager.filterModule.getFilterValues() : 
                { statusID: '13' };

            // Get data from API
            if (!this.manager || !this.manager.apiModule) {
                throw new Error('API module not available');
            }

            const filteredData = await this.manager.apiModule.getPostingInvoiceList(filters);
            
            if (this.dataTable) {
                this.dataTable.clear();
                
                if (filteredData && filteredData.length > 0) {
                    const mappedData = filteredData.map(item => {
                        const getValue = (camel, pascal, defaultValue) => {
                            if (item && item[camel] !== undefined && item[camel] !== null && item[camel] !== '') return item[camel];
                            if (item && item[pascal] !== undefined && item[pascal] !== null && item[pascal] !== '') return item[pascal];
                            return defaultValue;
                        };
                        
                        return {
                            invoiceHeaderID: getValue('invoiceHeaderID', 'InvoiceHeaderID', null),
                            requestNumber: getValue('requestNumber', 'RequestNumber', ''),
                            invoiceNumber: getValue('invoiceNumber', 'InvoiceNumber', ''),
                            invoiceDate: getValue('invoiceDate', 'InvoiceDate', null),
                            workflowStatus: getValue('workflowStatus', 'WorkflowStatus', ''),
                            picName: getValue('picName', 'PICName', ''),
                            purchOrderID: getValue('purchOrderID', 'PurchOrderID', ''),
                            termPaidValue: getValue('termPaidValue', 'TermPaidValue', ''),
                            monthPeriod: getValue('monthPeriod', 'MonthPeriod', ''),
                            purchaseType: getValue('purchaseType', 'PurchaseType', ''),
                            purchaseSubType: getValue('purchaseSubType', 'PurchaseSubType', ''),
                            sonumber: getValue('soNumber', 'SONumber', '') || getValue('sonumber', 'SONumber', ''),
                            siteID: getValue('siteID', 'SiteID', ''),
                            siteName: getValue('siteName', 'SiteName', ''),
                            poReleaseDate: getValue('poReleaseDate', 'POReleaseDate', null),
                            company: getValue('company', 'Company', ''),
                            invoiceAmount: getValue('invoiceAmount', 'InvoiceAmount', 0),
                            workType: getValue('workType', 'WorkType', '')
                        };
                    });
                    
                    this.dataTable.rows.add(mappedData);
                } else {
                    if (tbody) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="19" class="text-center py-4 text-muted">No data available</td>
                            </tr>
                        `;
                    }
                }
                
                this.dataTable.draw();
                
                // Recalculate fixed columns after data is loaded
                setTimeout(() => {
                    try {
                        if (this.dataTable && this.dataTable.fixedColumns && this.dataTable.fixedColumns().relayout) {
                            this.dataTable.fixedColumns().relayout();
                        }
                    } catch (e) {
                        // Ignore error
                    }
                }, 200);
                
                // Update checkbox states
                if (this.manager && this.manager.actionModule) {
                    this.manager.actionModule.updateSelectAllCheckbox();
                }
            }
        } catch (error) {
            const tbody = document.getElementById('postingTableBody');
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="19" class="text-center py-4 text-danger">
                            <i class="bx bx-error-circle"></i>
                            <div class="mt-2">Failed to load data</div>
                        </td>
                    </tr>
                `;
            }
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'Failed to load data'
                });
            } else {
                console.error('Error loading data:', error);
            }
        }
    }

    /**
     * Toggle row selection
     */
    toggleRowSelection(invoiceID, isSelected) {
        if (isSelected) {
            this.selectedInvoiceIDs.add(invoiceID);
        } else {
            this.selectedInvoiceIDs.delete(invoiceID);
        }
        
        if (this.manager && this.manager.actionModule) {
            this.manager.actionModule.updateProcessButton();
            this.manager.actionModule.updateSelectAllCheckbox();
        }
    }

    /**
     * Get selected invoice IDs
     */
    getSelectedInvoiceIDs() {
        return Array.from(this.selectedInvoiceIDs);
    }

    /**
     * Clear selected invoice IDs
     */
    clearSelectedInvoiceIDs() {
        this.selectedInvoiceIDs.clear();
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.PostingListTable = PostingListTable;
}

