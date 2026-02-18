<script src="{{ asset('js/procurement/ProcurementSharedCache.js') }}"></script>
<script src="{{ asset('js/procurement/ProcurementWizardUtils.js') }}"></script>
<script src="{{ asset('js/inventory/GRNListAPI.js') }}"></script>
<script src="{{ asset('js/inventory/GRNListFilter.js') }}"></script>
<script src="{{ asset('js/inventory/GRNListTable.js') }}"></script>
<script src="{{ asset('js/inventory/GRNListView.js') }}"></script>
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

class GRNManager {
    constructor() {
        this.dataTable = null;
        this.currentPONumber = null;
        this.allPurchaseTypes = null;
        this.apiModule = typeof GRNListAPI !== 'undefined' ? new GRNListAPI() : null;
        this.filterModule = typeof GRNListFilter !== 'undefined' ? new GRNListFilter(this) : null;
        this.tableModule = typeof GRNListTable !== 'undefined' ? new GRNListTable(this) : null;
        this.viewModule = typeof GRNListView !== 'undefined' ? new GRNListView(this) : null;
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

    submitGRN() {
        if (this.viewModule && this.viewModule.submitGRN) this.viewModule.submitGRN();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    window.grnManager = new GRNManager();
});
</script>
