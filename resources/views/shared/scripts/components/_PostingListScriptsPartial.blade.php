{{-- Posting List Scripts Partial --}}
<script src="{{ asset('js/procurement/ProcurementSharedCache.js') }}"></script>
<script src="{{ asset('js/procurement/ProcurementWizardUtils.js') }}"></script>

<script src="{{ asset('js/finance/InvoiceDetailAPI.js') }}"></script>
<script src="{{ asset('js/finance/InvoiceDetailManager.js') }}"></script>

<script src="{{ asset('js/finance/PostingListUtils.js') }}"></script>
<script src="{{ asset('js/finance/PostingListAPI.js') }}"></script>
<script src="{{ asset('js/finance/PostingListFilter.js') }}"></script>
<script src="{{ asset('js/finance/PostingListTable.js') }}"></script>
<script src="{{ asset('js/finance/PostingListView.js') }}"></script>
<script src="{{ asset('js/finance/PostingListAction.js') }}"></script>

<script>
    'use strict';

    window.renderDropdownWithSearch = window.renderDropdownWithSearch
        || (window.ProcurementWizardUtils && window.ProcurementWizardUtils.renderDropdownWithSearch);

    class PostingListManager {
        constructor() {
            if (typeof PostingListAPI !== 'undefined') {
                this.apiModule = new PostingListAPI();
            }
            if (typeof PostingListUtils !== 'undefined') {
                this.utilsModule = new PostingListUtils();
            }
            if (typeof PostingListFilter !== 'undefined') {
                this.filterModule = new PostingListFilter(this);
            }
            if (typeof PostingListTable !== 'undefined') {
                this.tableModule = new PostingListTable(this);
            }
            if (typeof PostingListView !== 'undefined') {
                this.viewModule = new PostingListView(this);
            }
            if (typeof PostingListAction !== 'undefined') {
                this.actionModule = new PostingListAction(this);
            }

            this.init().catch(error => {
                console.error('Error initializing PostingListManager:', error);
            });
        }

        async init() {
            this.bindEvents();

            await this.loadMasterDataForFormatting();

            if (this.filterModule) {
                this.filterModule.init();
                if (this.filterModule.loadMasterData) {
                    await this.filterModule.loadMasterData();
                }
            }

            if (this.tableModule) {
                this.tableModule.initializeDataTable();
                if (this.tableModule.dataTable) {
                    this.dataTable = this.tableModule.dataTable;
                }

                if (this.tableModule.loadData) {
                    await this.tableModule.loadData();
                }
            }

            if (this.actionModule && this.actionModule.setupPostingActionFormListeners) {
                this.actionModule.setupPostingActionFormListeners();
            }
        }

        async loadMasterDataForFormatting() {
            try {
                if (!this.apiModule || !this.utilsModule) return;

                const [purchaseTypes, purchaseSubTypes, taxes] = await Promise.all([
                    this.apiModule.getPurchaseTypes(),
                    this.apiModule.getPurchaseSubTypes(),
                    this.apiModule.getTaxes()
                ]);

                if (this.utilsModule.setMasterData) {
                    this.utilsModule.setMasterData(purchaseTypes, purchaseSubTypes, taxes);
                }
            } catch (error) {
                console.error('Error loading master data for formatting:', error);
            }
        }

        bindEvents() {
            window.postingListManager = this;

            if (typeof toggleFilter === 'undefined') {
                window.toggleFilter = () => this.toggleFilter();
            }

            if (typeof applyFilter === 'undefined') {
                window.applyFilter = () => this.applyFilter();
            }

            if (typeof resetFilter === 'undefined') {
                window.resetFilter = () => this.resetFilter();
            }

            if (typeof viewPosting === 'undefined') {
                window.viewPosting = (id) => this.viewPosting(id);
            }

            if (typeof backToList === 'undefined') {
                window.backToList = () => this.backToList();
            }

            if (typeof submitPosting === 'undefined') {
                window.submitPosting = () => this.submitPosting();
            }

            if (typeof toggleSelectAll === 'undefined') {
                window.toggleSelectAll = (checkbox) => this.toggleSelectAll(checkbox);
            }

            if (typeof toggleRowSelection === 'undefined') {
                window.toggleRowSelection = (invoiceID, isSelected) => this.toggleRowSelection(invoiceID, isSelected);
            }

            if (typeof processBulkPost === 'undefined') {
                window.processBulkPost = () => this.processBulkPost();
            }
        }

        toggleFilter() {
            if (this.filterModule && this.filterModule.toggleFilter) {
                return this.filterModule.toggleFilter();
            }
            console.warn('PostingListFilter module not available');
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
            console.warn('PostingListFilter module not available');
        }

        viewPosting(id) {
            if (this.viewModule && this.viewModule.viewPosting) {
                return this.viewModule.viewPosting(id);
            }
            console.warn('PostingListView module not available');
        }

        backToList() {
            if (this.viewModule && this.viewModule.backToList) {
                return this.viewModule.backToList();
            }

            const listSection = document.getElementById('listSection');
            const viewPostingSection = document.getElementById('viewPostingSection');

            if (listSection) listSection.style.display = 'block';
            if (viewPostingSection) viewPostingSection.style.display = 'none';
        }

        async submitPosting() {
            if (this.actionModule && this.actionModule.submitPosting) {
                return await this.actionModule.submitPosting();
            }
            console.warn('PostingListAction module not available');
        }

        toggleSelectAll(checkbox) {
            if (this.actionModule && this.actionModule.toggleSelectAll) {
                return this.actionModule.toggleSelectAll(checkbox);
            }
        }

        toggleRowSelection(invoiceID, isSelected) {
            if (this.actionModule && this.actionModule.toggleRowSelection) {
                return this.actionModule.toggleRowSelection(invoiceID, isSelected);
            }
        }

        processBulkPost() {
            if (this.actionModule && this.actionModule.processBulkPost) {
                return this.actionModule.processBulkPost();
            }
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        window.postingListManager = new PostingListManager();
    });
</script>
