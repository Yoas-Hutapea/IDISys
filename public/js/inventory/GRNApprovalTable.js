class GRNApprovalTable {
    constructor(manager) {
        this.manager = manager;
        this.dataTable = null;
    }

    async initializeDataTable() {
        if (typeof CreateTable === 'undefined' || typeof ColumnBuilder === 'undefined') {
            console.error('CreateTable or ColumnBuilder not found.');
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
            endpoint: '/Inventory/GoodReceiveNotes/Headers/ApprovalGrid',
            tableId: '#grnApprovalTable',
            columns: ColumnBuilder([
                {
                    type: 'actions',
                    buttons: [
                        { label: 'View', icon: '<i class="bx bx-pointer"></i>', className: 'btn-primary', title: 'View', showIf: () => true }
                    ]
                },
                { data: 'goodReceiveNoteNumber', title: 'GR Number' },
                { data: 'poNumber', title: 'PO Number' },
                { data: 'approvalStatus', title: 'Status' },
                { data: 'remark', title: 'Remark' },
                { data: 'createdBy', title: 'Created By' },
                { data: 'createdDate', title: 'Created Date', type: 'datetime' },
                { data: 'updatedBy', title: 'Updated By' },
                { data: 'updatedDate', title: 'Updated Date', type: 'datetime' }
            ]),
            filter: buildFilter.bind(this),
            order: [[6, 'desc']],
            serverSide: true,
            leftColumns: 2
        });

        $(document).off('click', '#grnApprovalTable .action-btn').on('click', '#grnApprovalTable .action-btn', function () {
            const action = $(this).data('action');
            const row = self.dataTable.row($(this).closest('tr')).data();
            if (action === 'view' && row && self.manager && self.manager.viewApproval) {
                self.manager.viewApproval(row.goodReceiveNoteNumber, row.poNumber, row.approvalStatus);
            }
        });
    }
}

if (typeof window !== 'undefined') window.GRNApprovalTable = GRNApprovalTable;
