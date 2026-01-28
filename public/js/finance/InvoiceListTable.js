/**
 * InvoiceListTable Module
 * Handles DataTable initialization, rendering, and data loading
 */
class InvoiceListTable {
    constructor(managerInstance) {
        this.manager = managerInstance;
        this.dataTable = null;
    }

    /**
     * Initialize DataTable
     */
    initializeDataTable() {
        const self = this;
        
        this.dataTable = $('#invoiceTable').DataTable({
            processing: true,
            serverSide: false,
            responsive: false, // Disable responsive child rows to prevent unwanted expansion
            data: [],
            order: [[3, 'desc']], // Order by Invoice Date descending
            paging: true,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            scrollX: true,
            fixedColumns: {
                leftColumns: 2 // Fixed columns: Actions, Request Number
            },
            columns: [
                { 
                    data: null, 
                    orderable: false,
                    width: '120px',
                    name: 'Actions',
                    className: 'text-center',
                    defaultContent: '',
                    render: function(data, type, row) {
                        const id = row.invoiceHeaderID || row.InvoiceHeaderID || 0;
                        const statusID = row.statusID || row.StatusID || row.mstApprovalStatusID || row.MstApprovalStatusID || null;
                        const isRejected = statusID === 15; // Status 15 = Rejected Posting Invoice
                        
                        let buttons = `
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-sm btn-primary action-btn" onclick="event.stopPropagation(); invoiceListManager.viewDetail(${id})" title="View">
                                    <i class="bx bx-show"></i>
                                </button>
                        `;
                        
                        // AC1: Show Revised button only if status is 15 (Rejected Posting Invoice)
                        if (isRejected) {
                            buttons += `
                                <button type="button" class="btn btn-sm btn-warning action-btn" onclick="event.stopPropagation(); invoiceListManager.editInvoice(${id})" title="Revised" style="cursor: pointer;">
                                    <i class="bx bx-edit"></i>
                                </button>
                            `;
                        }
                        
                        buttons += `</div>`;
                        return buttons;
                    }
                },
                { 
                    data: 'requestNumber',
                    name: 'Request Number',
                    className: 'text-center',
                    defaultContent: '',
                    render: function(data, type, row) {
                        return data || row.RequestNumber || row.requestNumber || '';
                    }
                },
                { 
                    data: 'invoiceNumber',
                    name: 'Invoice Number',
                    className: 'text-center',
                    defaultContent: '',
                    render: function(data, type, row) {
                        return data || row.InvoiceNumber || row.invoiceNumber || '';
                    }
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
                    defaultContent: '',
                    render: function(data, type, row) {
                        return data || row.WorkflowStatus || row.workflowStatus || row.StatusUser || row.statusUser || '';
                    }
                },
                { 
                    data: 'picName',
                    name: 'PIC',
                    className: 'text-center',
                    defaultContent: '',
                    render: function(data, type, row) {
                        return data || row.PICName || row.picName || row.PICWorkflowName || row.picWorkflowName || '';
                    }
                },
                { 
                    data: 'purchOrderID',
                    name: 'PO Number',
                    className: 'text-center',
                    defaultContent: '',
                    render: function(data, type, row) {
                        return data || row.PurchOrderID || row.purchOrderID || '';
                    }
                },
                { 
                    data: 'termPaidValue',
                    name: 'Term Paid Value',
                    className: 'text-center',
                    defaultContent: '',
                    render: function(data, type, row) {
                        // Format as percentage if not already formatted
                        const value = data || row.TermPaidValue || row.termPaidValue || '';
                        if (value && !value.toString().includes('%')) {
                            // If it's a number, format as percentage
                            const numValue = parseFloat(value);
                            if (!isNaN(numValue)) {
                                return numValue.toFixed(2) + '%';
                            }
                        }
                        return value;
                    }
                },
                { 
                    data: 'monthPeriod',
                    name: 'Period Month',
                    className: 'text-center',
                    defaultContent: '',
                    render: function(data, type, row) {
                        return data || row.MonthPeriod || row.monthPeriod || '';
                    }
                },
                { 
                    data: 'purchaseType',
                    name: 'Purchase Type',
                    className: 'text-center',
                    defaultContent: '',
                    render: function(data, type, row) {
                        return data || row.PurchaseType || row.purchaseType || '';
                    }
                },
                { 
                    data: 'purchaseSubType',
                    name: 'Purchase Sub Type',
                    className: 'text-center',
                    defaultContent: '',
                    render: function(data, type, row) {
                        return data || row.PurchaseSubType || row.purchaseSubType || '';
                    }
                },
                { 
                    data: 'sonumber',
                    name: 'SONumber',
                    className: 'text-center',
                    defaultContent: '',
                    render: function(data, type, row) {
                        // Try multiple case variations (ASP.NET Core JSON serializer uses camelCase by default)
                        return data || row.soNumber || row.SONumber || row.sonumber || row.SoNumber || '';
                    }
                },
                { 
                    data: 'siteID',
                    name: 'Site ID',
                    className: 'text-center',
                    defaultContent: '',
                    render: function(data, type, row) {
                        return data || row.SiteID || row.siteID || '';
                    }
                },
                { 
                    data: 'siteName',
                    name: 'Site Name',
                    className: 'text-center',
                    defaultContent: '',
                    render: function(data, type, row) {
                        return data || row.SiteName || row.siteName || '';
                    }
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
                    defaultContent: '',
                    render: function(data, type, row) {
                        return data || row.Company || row.company || '';
                    }
                },
                { 
                    data: 'invoiceAmount',
                    name: 'Invoice Amount',
                    className: 'text-center',
                    defaultContent: '',
                    render: function(data, type, row) {
                        const amount = data || row.InvoiceAmount || row.invoiceAmount || 0;
                        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
                    }
                },
                { 
                    data: 'workType',
                    name: 'Work Type',
                    className: 'text-center',
                    defaultContent: '',
                    render: function(data, type, row) {
                        return data || row.WorkType || row.workType || '';
                    }
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
        
        return this.dataTable;
    }

    /**
     * Load data into DataTable
     */
    async loadData() {
        try {
            const tbody = document.getElementById('invoiceTableBody');
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="18" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="mt-2">Loading data...</div>
                        </td>
                    </tr>
                `;
            }

            // Get filter values
            const filters = this.manager.filterModule ? this.manager.filterModule.getFilterValues() : {};
            
            // Get invoice list from API
            const rawData = await this.manager.apiModule.getInvoiceList(filters);
            
            if (Array.isArray(rawData)) {
                if (this.dataTable) {
                    this.dataTable.clear();
                    
                    if (rawData.length > 0) {
                        const mappedData = rawData.map(item => {
                            const getValue = (camel, pascal, defaultValue) => {
                                // Try camelCase first (ASP.NET Core default JSON serializer)
                                if (item && item[camel] !== undefined && item[camel] !== null && item[camel] !== '') return item[camel];
                                // Try PascalCase
                                if (item && item[pascal] !== undefined && item[pascal] !== null && item[pascal] !== '') return item[pascal];
                                return defaultValue;
                            };
                            
                            return {
                                invoiceHeaderID: getValue('invoiceHeaderID', 'InvoiceHeaderID', null),
                                requestNumber: getValue('requestNumber', 'RequestNumber', ''),
                                invoiceNumber: getValue('invoiceNumber', 'InvoiceNumber', ''),
                                invoiceDate: getValue('invoiceDate', 'InvoiceDate', null),
                                workflowStatus: getValue('workflowStatus', 'WorkflowStatus', ''),
                                statusUser: getValue('statusUser', 'StatusUser', ''),
                                statusID: getValue('statusID', 'StatusID', null) || getValue('mstApprovalStatusID', 'MstApprovalStatusID', null),
                                picName: getValue('picName', 'PICName', ''),
                                picWorkflowName: getValue('picWorkflowName', 'PICWorkflowName', ''),
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
                                    <td colspan="18" class="text-center py-4 text-muted">No data available</td>
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
                }
            }
        } catch (error) {
            const tbody = document.getElementById('invoiceTableBody');
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="18" class="text-center py-4 text-danger">
                            <i class="icon-base bx bx-error-circle"></i>
                            <div class="mt-2">Failed to load data</div>
                        </td>
                    </tr>
                `;
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Failed to load data'
            });
        }
    }

    /**
     * Refresh table data
     */
    async refreshTable() {
        await this.loadData();
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.InvoiceListTable = InvoiceListTable;
}

