<script src="{{ asset('js/procurement/ProcurementSharedCache.js') }}"></script>
<script src="{{ asset('js/procurement/ProcurementWizardUtils.js') }}"></script>
<script src="{{ asset('js/inventory/GRNHeaderListAPI.js') }}"></script>
<script src="{{ asset('js/inventory/GRNHeaderListFilter.js') }}"></script>
<script src="{{ asset('js/inventory/GRNHeaderListTable.js') }}"></script>
<script src="{{ asset('js/inventory/GRNListView.js') }}"></script>
<script src="{{ asset('js/inventory/GRNHeaderView.js') }}"></script>
<script>
'use strict';

window.toggleGRNFilter = function() {
    const content = document.getElementById('grn-filter-content');
    const chevron = document.getElementById('grn-filter-chevron');
    if (!content || !chevron) return;
    const show = !content.classList.contains('show');
    content.classList.toggle('show', show);
    chevron.classList.toggle('bx-chevron-down', !show);
    chevron.classList.toggle('bx-chevron-up', show);
};

class GRNHeaderManager {
    constructor() {
        this.dataTable = null;
        this.currentPONumber = null;
        this.allPurchaseTypes = null;
        this.apiModule = typeof GRNHeaderListAPI !== 'undefined' ? new GRNHeaderListAPI() : null;
        this.filterModule = typeof GRNHeaderListFilter !== 'undefined' ? new GRNHeaderListFilter(this) : null;
        this.tableModule = typeof GRNHeaderListTable !== 'undefined' ? new GRNHeaderListTable(this) : null;
        this.viewModule = typeof GRNHeaderView !== 'undefined' ? new GRNHeaderView(this) : null;
        this.init();
    }

    async init() {
        if (this.filterModule && this.filterModule.init) this.filterModule.init();
        if (this.tableModule && this.tableModule.initializeDataTable) {
            await this.tableModule.initializeDataTable();
            this.dataTable = this.tableModule.dataTable || null;
        }
    }

    search() {
        if (this.dataTable) this.dataTable.ajax.reload();
    }

    resetFilter() {
        if (this.filterModule && this.filterModule.resetFilter) this.filterModule.resetFilter();
    }

    backToList() {
        if (this.viewModule && this.viewModule.backToList) this.viewModule.backToList();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    window.grnManager = new GRNHeaderManager();
    window.grnHeaderManager = window.grnManager;
    window.grnHeaderDownloadDocumentFromModal = function() {
        const info = window.__grnReadOnlyDocumentPreview;
        if (!info || !info.downloadUrl) return;
        const link = document.createElement('a');
        link.href = info.downloadUrl;
        link.download = info.fileName || 'document';
        link.target = '_blank';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };
});
</script>
