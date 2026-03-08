/**
 * GR Header List Table - source from trxInventoryGoodReceiveNoteHeader.
 */
class GRNHeaderListTable {
    constructor(manager) {
        this.manager = manager;
        this.dataTable = null;
    }

    async initializeDataTable() {
        if (typeof CreateTable === 'undefined' || typeof ColumnBuilder === 'undefined') {
            console.error('CreateTable or ColumnBuilder not found. Load DataTableHelper.js.');
            return;
        }

        const self = this;
        const buildFilter = () => {
            if (this.manager && this.manager.filterModule && this.manager.filterModule.buildFilter) {
                return this.manager.filterModule.buildFilter();
            }
            return {};
        };

        this.dataTable = CreateTable({
            apiType: 'Inventory',
            endpoint: '/Inventory/GoodReceiveNotes/Headers/Grid',
            tableId: '#grnHeaderTable',
            columns: ColumnBuilder([
                { type: 'actions', buttons: [
                    { label: 'View', icon: '<i class="bx bx-pointer"></i>', className: 'btn-primary', title: 'View', showIf: () => true }
                ]},
                { data: 'goodReceiveNoteNumber', title: 'GR Number' },
                { data: 'poNumber', title: 'PO Number' },
                { data: 'remark', title: 'Remark' },
                { data: 'createdBy', title: 'Created By' },
                { data: 'createdDate', title: 'Created Date', type: 'datetime' },
                { data: 'updatedBy', title: 'Updated By' },
                { data: 'updatedDate', title: 'Updated Date', type: 'datetime' }
            ]),
            filter: buildFilter.bind(this),
            order: [[5, 'desc']],
            serverSide: true,
            leftColumns: 2
        });

        $(document).off('click', '#grnHeaderTable .action-btn').on('click', '#grnHeaderTable .action-btn', function() {
            const action = $(this).data('action');
            const row = self.dataTable.row($(this).closest('tr')).data();
            const poNumber = row && (row.poNumber || row.trxPROPurchaseOrderNumber);
            if (action === 'view' && poNumber && self.manager && self.manager.viewModule && self.manager.viewModule.viewGRN) {
                self.manager.viewModule.viewGRN(poNumber);
            }
        });
    }
}

if (typeof window !== 'undefined') window.GRNHeaderListTable = GRNHeaderListTable;
