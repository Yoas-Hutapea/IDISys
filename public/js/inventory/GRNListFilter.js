/**
 * GRN List Filter - filter list PO (status 11 only).
 */
class GRNListFilter {
    constructor(manager) {
        this.manager = manager;
    }

    init() {
        const form = document.getElementById('filterForm');
        if (form) {
            form.addEventListener('submit', (e) => { e.preventDefault(); if (this.manager) this.manager.search(); });
            const now = new Date();
            const first = new Date(now.getFullYear(), now.getMonth(), 1);
            const last = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            const fmt = d => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
            const start = document.getElementById('poStartDate');
            const end = document.getElementById('poEndDate');
            if (start) start.value = fmt(first);
            if (end) end.value = fmt(last);
        }
    }

    buildFilter() {
        const form = document.getElementById('filterForm');
        const fd = form ? new FormData(form) : new FormData();
        const filter = { mstApprovalStatusID: 11 };
        if (fd.get('poNumber') && fd.get('poNumber').trim()) filter.poNumber = fd.get('poNumber').trim();
        if (fd.get('prNumber') && fd.get('prNumber').trim()) filter.prNumber = fd.get('prNumber').trim();
        if (fd.get('purchType') && fd.get('purchType').trim()) filter.purchType = fd.get('purchType').trim();
        if (fd.get('purchSubType') && fd.get('purchSubType').trim()) filter.purchSubType = fd.get('purchSubType').trim();
        if (fd.get('purchName') && fd.get('purchName').trim()) filter.purchName = fd.get('purchName').trim();
        if (fd.get('company') && fd.get('company').trim()) filter.company = fd.get('company').trim();
        if (fd.get('vendorName') && fd.get('vendorName').trim()) filter.vendorName = fd.get('vendorName').trim();
        if (fd.get('poStartDate')) filter.poStartDate = fd.get('poStartDate');
        if (fd.get('poEndDate')) filter.poEndDate = fd.get('poEndDate');
        return filter;
    }

    resetFilter() {
        const form = document.getElementById('filterForm');
        if (form) form.reset();
        const now = new Date();
        const first = new Date(now.getFullYear(), now.getMonth(), 1);
        const last = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        const fmt = d => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
        const start = document.getElementById('poStartDate');
        const end = document.getElementById('poEndDate');
        if (start) start.value = fmt(first);
        if (end) end.value = fmt(last);
        if (this.manager && this.manager.dataTable) this.manager.dataTable.ajax.reload();
    }
}

if (typeof window !== 'undefined') window.GRNListFilter = GRNListFilter;
