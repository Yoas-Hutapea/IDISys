{{-- Invoice List Scripts Partial --}}
<script src="{{ asset('js/procurement/ProcurementSharedCache.js') }}"></script>
<script src="{{ asset('js/procurement/ProcurementWizardUtils.js') }}"></script>

<script src="{{ asset('js/finance/InvoiceListAPI.js') }}"></script>
<script src="{{ asset('js/finance/InvoiceListFilter.js') }}"></script>
<script src="{{ asset('js/finance/InvoiceListTable.js') }}"></script>
<script src="{{ asset('js/finance/InvoiceListView.js') }}"></script>

<script src="{{ asset('js/finance/InvoiceDetailAPI.js') }}"></script>
<script src="{{ asset('js/finance/InvoiceDetailManager.js') }}"></script>

<script>
    'use strict';

    window.renderDropdownWithSearch = window.renderDropdownWithSearch
        || (window.ProcurementWizardUtils && window.ProcurementWizardUtils.renderDropdownWithSearch);

    class InvoiceListManager {
        constructor() {
            if (typeof InvoiceListAPI !== 'undefined') {
                this.apiModule = new InvoiceListAPI();
            }
            if (typeof InvoiceListFilter !== 'undefined') {
                this.filterModule = new InvoiceListFilter(this);
            }
            if (typeof InvoiceListTable !== 'undefined') {
                this.tableModule = new InvoiceListTable(this);
            }
            if (typeof InvoiceListView !== 'undefined') {
                this.viewModule = new InvoiceListView(this);
            }

            this.init().catch(error => {
                console.error('Error initializing InvoiceListManager:', error);
            });
        }

        async init() {
            this.bindEvents();

            if (this.filterModule) {
                this.filterModule.init();
            }

            if (this.filterModule && this.filterModule.loadMasterData) {
                await this.filterModule.loadMasterData();
            }

            if (this.tableModule) {
                this.tableModule.initializeDataTable();
                if (this.tableModule.dataTable) {
                    this.dataTable = this.tableModule.dataTable;
                }

                if (this.tableModule.loadData) {
                    await this.tableModule.loadData();
                }
            } else {
                this.initializeDataTable();
            }
        }

        initializeDataTable() {
            if (this.tableModule) {
                const result = this.tableModule.initializeDataTable();
                if (this.tableModule.dataTable) {
                    this.dataTable = this.tableModule.dataTable;
                }
                return result;
            }
            console.warn('InvoiceListTable module not available');
        }

        bindEvents() {
            window.invoiceListManager = this;

            if (typeof toggleFilter === 'undefined') {
                window.toggleFilter = () => this.toggleFilter();
            }

            if (typeof applyFilter === 'undefined') {
                window.applyFilter = () => this.applyFilter();
            }

            if (typeof resetFilter === 'undefined') {
                window.resetFilter = () => this.resetFilter();
            }

            if (typeof viewDetail === 'undefined') {
                window.viewDetail = (id) => this.viewDetail(id);
            }

            if (typeof editInvoice === 'undefined') {
                window.editInvoice = (id) => this.editInvoice(id);
            }

            if (typeof backToList === 'undefined') {
                window.backToList = () => this.backToList();
            }
        }

        toggleFilter() {
            if (this.filterModule && this.filterModule.toggleFilter) {
                return this.filterModule.toggleFilter();
            }
            console.warn('InvoiceListFilter module not available');
        }

        async applyFilter() {
            if (this.tableModule && this.tableModule.loadData) {
                return await this.tableModule.loadData();
            }
            if (this.dataTable) {
                this.dataTable.ajax.reload();
            }
        }

        async resetFilter() {
            if (this.filterModule && this.filterModule.resetFilter) {
                return await this.filterModule.resetFilter();
            }
            console.warn('InvoiceListFilter module not available');
        }

        async refreshTable() {
            if (this.dataTable) {
                this.dataTable.ajax.reload();
            } else if (this.tableModule && this.tableModule.loadData) {
                await this.tableModule.loadData();
            }
        }

        viewDetail(id) {
            if (this.viewModule && this.viewModule.viewInvoice) {
                return this.viewModule.viewInvoice(id);
            }
            console.warn('InvoiceListView module not available');
        }

        editInvoice(id) {
            window.location.href = `/Finance/EBilling/Invoice/Create?id=${id}`;
        }

        backToList() {
            if (this.viewModule && this.viewModule.backToList) {
                return this.viewModule.backToList();
            }

            const filterSection = document.querySelector('.filter-section');
            const listSection = document.getElementById('listSection');
            const viewInvoiceSection = document.getElementById('viewInvoiceSection');

            if (filterSection) filterSection.style.display = 'block';
            if (listSection) listSection.style.display = 'block';
            if (viewInvoiceSection) viewInvoiceSection.style.display = 'none';
        }

        async loadData() {
            console.warn('loadData is deprecated. Use tableModule.loadData instead.');
            if (this.tableModule && this.tableModule.loadData) {
                return await this.tableModule.loadData();
            }
        }
    }

    let invoiceListInitialized = false;

    function initializeInvoiceList() {
        if (invoiceListInitialized) {
            return;
        }

        if (typeof $ === 'undefined' || typeof $.fn.DataTable === 'undefined') {
            setTimeout(initializeInvoiceList, 100);
            return;
        }

        invoiceListInitialized = true;
        window.invoiceListManager = new InvoiceListManager();
    }

    document.addEventListener('DOMContentLoaded', () => {
        initializeInvoiceList();
    });
</script>
