class GRNApprovalFilter {
    constructor(manager) {
        this.manager = manager;
    }

    init() {
        const form = document.getElementById('grnApprovalFilterForm');
        if (!form) return;
        const status = document.getElementById('approvalStatus');
        if (status && !status.value) status.value = 'waiting';
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            if (this.manager) this.manager.search();
        });
    }

    buildFilter() {
        const form = document.getElementById('grnApprovalFilterForm');
        const fd = form ? new FormData(form) : new FormData();
        const filter = {};
        if (fd.get('grNumber')) filter.grNumber = String(fd.get('grNumber')).trim();
        if (fd.get('poNumber')) filter.poNumber = String(fd.get('poNumber')).trim();
        if (fd.get('approvalStatus')) filter.approvalStatus = String(fd.get('approvalStatus')).trim();
        if (fd.get('createdStartDate')) filter.createdStartDate = fd.get('createdStartDate');
        if (fd.get('createdEndDate')) filter.createdEndDate = fd.get('createdEndDate');
        return filter;
    }

    resetFilter() {
        const form = document.getElementById('grnApprovalFilterForm');
        if (form) form.reset();
        const status = document.getElementById('approvalStatus');
        if (status) status.value = 'waiting';
        if (this.manager && this.manager.dataTable) this.manager.dataTable.ajax.reload();
    }
}

if (typeof window !== 'undefined') window.GRNApprovalFilter = GRNApprovalFilter;
