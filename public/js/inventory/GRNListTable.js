/**
 * GRN List Table - DataTable list PO dengan mstApprovalStatusID = 11 (Fully Approved).
 * Purchase Type / Sub Type ditampilkan sebagai nama (bukan ID).
 */
class GRNListTable {
    constructor(manager) {
        this.manager = manager;
        this.dataTable = null;
        this.allPurchaseTypes = null;
        this.allPurchaseSubTypes = null;
    }

    async initializeDataTable() {
        if (typeof CreateTable === 'undefined' || typeof ColumnBuilder === 'undefined') {
            console.error('CreateTable or ColumnBuilder not found. Load DataTableHelper.js.');
            return;
        }

        await this.loadPurchaseTypesForFormatting();

        const self = this;
        const buildFilter = () => {
            if (this.manager && this.manager.filterModule && this.manager.filterModule.buildFilter) {
                return this.manager.filterModule.buildFilter();
            }
            return { mstApprovalStatusID: 11 };
        };

        this.dataTable = CreateTable({
            apiType: 'Procurement',
            endpoint: '/Procurement/PurchaseOrder/PurchaseOrders/Grid',
            tableId: '#grnPOTable',
            columns: ColumnBuilder([
                { type: 'actions', buttons: [
                    { label: 'View', icon: '<i class="bx bx-pointer"></i>', className: 'btn-primary', title: 'View', showIf: () => true }
                ]},
                { data: 'purchOrderID', title: 'Purchase Order Number' },
                { data: 'purchOrderName', title: 'Purchase Order Name' },
                { data: 'purchType', title: 'Purchase Type', render: (data, type, row) => self.formatPurchaseType(data, row) },
                { data: 'purchSubType', title: 'Purchase Sub Type', render: (data, type, row) => self.formatPurchaseSubType(data, row) },
                { data: 'poAmount', title: 'PO Amount', type: 'currency' },
                { data: 'poDate', title: 'PO Date', type: 'datetime' },
                { data: 'prNumber', title: 'PR Number' },
                { data: 'mstVendorVendorName', title: 'Vendor Name' },
                { data: 'companyName', title: 'Company' }
            ]),
            filter: buildFilter.bind(this),
            order: [[6, 'desc']],
            serverSide: true,
            leftColumns: 2
        });

        $(document).off('click', '#grnPOTable .action-btn').on('click', '#grnPOTable .action-btn', function() {
            const action = $(this).data('action');
            const row = self.dataTable.row($(this).closest('tr')).data();
            const poNumber = row && (row.purchOrderID || row.PurchOrderID);
            if (action === 'view' && poNumber && self.manager && self.manager.viewModule && self.manager.viewModule.viewGRN) {
                self.manager.viewModule.viewGRN(poNumber);
            }
        });
    }

    async loadPurchaseTypesForFormatting() {
        if (!this.allPurchaseTypes && this.manager && this.manager.viewModule && this.manager.viewModule.allPurchaseTypes) {
            this.allPurchaseTypes = this.manager.viewModule.allPurchaseTypes;
        }
        if (!this.allPurchaseTypes && this.manager && this.manager.apiModule) {
            try {
                this.allPurchaseTypes = await this.manager.apiModule.getPurchaseTypes();
                if (this.manager && this.manager.viewModule) {
                    this.manager.viewModule.allPurchaseTypes = this.allPurchaseTypes;
                }
            } catch (e) {
                console.error('Error loading purchase types:', e);
                this.allPurchaseTypes = [];
            }
        }
        // Jangan pre-load semua Sub Types (ikuti Approval PO: load on-demand saat buka view per PO)
        if (!this.allPurchaseSubTypes) {
            this.allPurchaseSubTypes = new Map();
        }
    }

    formatPurchaseType(data, row, escapeHtml = true) {
        if (data == null) return '-';
        const s = typeof data === 'number' ? String(data) : String(data || '');
        if (!s.trim()) return '-';
        if (s.includes(' ') && isNaN(parseInt(s.trim(), 10))) return escapeHtml ? this.escapeHtml(s) : s;
        const typeId = parseInt(s.trim(), 10);
        if (isNaN(typeId) || typeId <= 0 || !this.allPurchaseTypes || !this.allPurchaseTypes.length) {
            return escapeHtml ? this.escapeHtml(s) : s;
        }
        const type = this.allPurchaseTypes.find(t => parseInt(t.ID || t.id || '0', 10) === typeId);
        if (!type) return escapeHtml ? this.escapeHtml(s) : s;
        const prType = type.PurchaseRequestType || type.purchaseRequestType || '';
        const category = type.Category || type.category || '';
        if (!category || prType === category) return escapeHtml ? this.escapeHtml(prType) : prType;
        const formatted = `${prType} ${category}`;
        return escapeHtml ? this.escapeHtml(formatted) : formatted;
    }

    formatPurchaseSubType(data, row, escapeHtml = true) {
        if (data == null) return '-';
        const s = typeof data === 'number' ? String(data) : String(data || '');
        if (!s.trim()) return '-';
        if (isNaN(parseInt(s.trim(), 10))) return escapeHtml ? this.escapeHtml(s) : s;
        const subTypeId = parseInt(s.trim(), 10);
        if (subTypeId <= 0 || !this.allPurchaseSubTypes || !(this.allPurchaseSubTypes instanceof Map)) {
            return escapeHtml ? this.escapeHtml(s) : s;
        }
        let found = null;
        for (const subTypes of this.allPurchaseSubTypes.values()) {
            if (Array.isArray(subTypes)) {
                found = subTypes.find(st => parseInt(st.ID || st.id || '0', 10) === subTypeId);
                if (found) break;
            }
        }
        if (!found) return escapeHtml ? this.escapeHtml(s) : s;
        const formatted = found.PurchaseRequestSubType || found.purchaseRequestSubType || '';
        return escapeHtml ? this.escapeHtml(formatted) : formatted;
    }

    escapeHtml(text) {
        if (text == null) return '';
        const d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }
}

if (typeof window !== 'undefined') window.GRNListTable = GRNListTable;
