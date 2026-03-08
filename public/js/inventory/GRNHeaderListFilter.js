/**
 * GR Header List Filter.
 */
class GRNHeaderListFilter {
    constructor(manager) {
        this.manager = manager;
    }

    init() {
        const form = document.getElementById('filterForm');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            if (this.manager) this.manager.search();
        });

        const now = new Date();
        const first = new Date(now.getFullYear(), now.getMonth(), 1);
        const last = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        const fmt = d => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

        const start = document.getElementById('createdStartDate');
        const end = document.getElementById('createdEndDate');
        if (start) start.value = fmt(first);
        if (end) end.value = fmt(last);
    }

    buildFilter() {
        const form = document.getElementById('filterForm');
        const fd = form ? new FormData(form) : new FormData();
        const filter = {};

        if (fd.get('grNumber') && fd.get('grNumber').trim()) filter.grNumber = fd.get('grNumber').trim();
        if (fd.get('poNumber') && fd.get('poNumber').trim()) filter.poNumber = fd.get('poNumber').trim();
        if (fd.get('createdBy') && fd.get('createdBy').trim()) filter.createdBy = fd.get('createdBy').trim();
        if (fd.get('createdStartDate')) filter.createdStartDate = fd.get('createdStartDate');
        if (fd.get('createdEndDate')) filter.createdEndDate = fd.get('createdEndDate');

        return filter;
    }

    resetFilter() {
        const form = document.getElementById('filterForm');
        if (form) form.reset();

        const now = new Date();
        const first = new Date(now.getFullYear(), now.getMonth(), 1);
        const last = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        const fmt = d => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        const start = document.getElementById('createdStartDate');
        const end = document.getElementById('createdEndDate');
        if (start) start.value = fmt(first);
        if (end) end.value = fmt(last);

        if (this.manager && this.manager.dataTable) this.manager.dataTable.ajax.reload();
    }
}

if (typeof window !== 'undefined') window.GRNHeaderListFilter = GRNHeaderListFilter;
