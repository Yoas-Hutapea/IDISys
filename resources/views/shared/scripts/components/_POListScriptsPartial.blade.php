<script src="{{ asset('js/procurement/ProcurementSharedCache.js') }}"></script>

<script src="{{ asset('js/procurement/POListAPI.js') }}"></script>
<script src="{{ asset('js/procurement/POListFilter.js') }}"></script>
<script src="{{ asset('js/procurement/POListTable.js') }}"></script>
<script src="{{ asset('js/procurement/POListView.js') }}"></script>

<script>
    'use strict';

    // Global toggleFilter function - Define early to ensure it's available when onclick is triggered
    window.toggleFilter = function() {
        if (window.poListManager && typeof window.poListManager.toggleFilter === 'function') {
            window.poListManager.toggleFilter();
        } else {
            // Fallback: manually toggle filter if poListManager not yet initialized
            const content = document.getElementById('filter-content');
            const chevron = document.getElementById('filter-chevron');
            const header = document.querySelector('.filter-header');

            if (content && chevron && header) {
                const isCollapsed = !content.classList.contains('show');

                if (isCollapsed) {
                    content.classList.add('show');
                    chevron.classList.remove('bx-chevron-down');
                    chevron.classList.add('bx-chevron-up');
                    header.setAttribute('aria-expanded', 'true');
                } else {
                    content.classList.remove('show');
                    chevron.classList.remove('bx-chevron-up');
                    chevron.classList.add('bx-chevron-down');
                    header.setAttribute('aria-expanded', 'false');
                }
            }
        }
    };

    // Performance-optimized POList functionality class
    class POListManager {
        constructor() {
            this.currentPage = 1;
            this.rowsPerPage = 10;
            this.totalRecords = 0;
            this.filterCollapsed = true; // Default collapsed
            this.debounceTimer = null;
            this.isLoading = false;
            this.cachedElements = this.cacheElements();
            this.poDataTable = null; // Store reference for backward compatibility

            // Initialize modules
            if (typeof POListAPI !== 'undefined') {
                this.apiModule = new POListAPI();
            }
            if (typeof POListFilter !== 'undefined') {
                this.filterModule = new POListFilter(this);
            }
            if (typeof POListTable !== 'undefined') {
                this.tableModule = new POListTable(this);
            }
            if (typeof POListView !== 'undefined') {
                this.viewModule = new POListView(this);
            }
            // Use shared employee cache if available
            if (window.procurementSharedCache && window.procurementSharedCache.getEmployeeNameByEmployId) {
                this.employeeCacheModule = window.procurementSharedCache;
            } else if (window.prListManager && window.prListManager.employeeCacheModule) {
                this.employeeCacheModule = window.prListManager.employeeCacheModule;
            } else if (window.approvalListManager && window.approvalListManager.employeeCacheModule) {
                this.employeeCacheModule = window.approvalListManager.employeeCacheModule;
            } else if (window.receiveListManager && window.receiveListManager.employeeCacheModule) {
                this.employeeCacheModule = window.receiveListManager.employeeCacheModule;
            } else if (window.releaseListManager && window.releaseListManager.employeeCacheModule) {
                this.employeeCacheModule = window.releaseListManager.employeeCacheModule;
            }

            this.init();
        }

        cacheElements() {
            return {
                filterForm: document.getElementById('filterForm'),
                filterContent: document.getElementById('filter-content'),
                filterChevron: document.getElementById('filter-chevron'),
                poTable: document.getElementById('poTable')
            };
        }

        async init() {
            this.bindEvents();

            // Initialize filter module
            if (this.filterModule) {
                this.filterModule.init();
            }

            // Initialize DataTable (async to wait for purchase types to load)
            if (this.tableModule) {
                try {
                    // initializeDataTable is now async, so we need to await it
                    await this.tableModule.initializeDataTable();
                    // Store dataTable reference for backward compatibility
                    if (this.tableModule.dataTable) {
                        this.poDataTable = this.tableModule.dataTable;
                    }
                } catch (error) {
                    console.error('Error initializing DataTable:', error);
                }
            } else {
                console.warn('POListTable module not available, falling back to legacy initialization');
                await this.initializeDataTable();
            }

            // Initialize date range info after a short delay to ensure default dates are set
            setTimeout(() => {
                if (this.filterModule && this.filterModule.updateDateRangeInfo) {
                    this.filterModule.updateDateRangeInfo();
                } else {
                    this.updateDateRangeInfo();
                }
            }, 200);
        }

        bindEvents() {
            // Expose global functions
            window.poListManager = this;

            // Back to List
            if (typeof backToList === 'undefined') {
                window.backToList = () => this.backToList();
            }

            // Download Document
            if (typeof downloadDocument === 'undefined') {
                const self = this;
                window.downloadDocument = function(documentId) {
                    const button = this;
                    const fileName = button?.getAttribute('data-file-name') || 'document';
                    if (self.viewModule && self.viewModule.downloadDocument) {
                        self.viewModule.downloadDocument(documentId, fileName);
                    }
                };
            }
        }

        /**
         * Delegate methods to modules
         */

        // Filter methods
        toggleFilter() {
            if (this.filterModule && this.filterModule.toggleFilter) {
                this.filterModule.toggleFilter();
            }
        }

        debouncedSearch() {
            if (this.filterModule && this.filterModule.debouncedSearch) {
                this.filterModule.debouncedSearch();
            }
        }

        async search() {
            if (this.tableModule && this.tableModule.dataTable) {
                this.tableModule.dataTable.ajax.reload();
            } else if (this.poDataTable) {
                this.poDataTable.ajax.reload();
            }
        }

        resetFilter() {
            if (this.filterModule && this.filterModule.resetFilter) {
                this.filterModule.resetFilter();
            }
        }

        updateDateRangeInfo() {
            if (this.filterModule && this.filterModule.updateDateRangeInfo) {
                this.filterModule.updateDateRangeInfo();
            }
        }

        // Table methods
        initializeDataTable() {
            if (this.tableModule && this.tableModule.initializeDataTable) {
                const result = this.tableModule.initializeDataTable();
                // Store dataTable reference for backward compatibility
                if (this.tableModule.dataTable) {
                    this.poDataTable = this.tableModule.dataTable;
                }
                return result;
            } else {
                console.warn('Table module not available');
            }
        }

        refreshTable() {
            if (this.tableModule && this.tableModule.refreshTable) {
                this.tableModule.refreshTable();
            } else if (this.poDataTable) {
                this.poDataTable.ajax.reload();
            }
        }

        // View methods
        async viewPO(poNumber) {
            if (this.viewModule && this.viewModule.viewPO) {
                return await this.viewModule.viewPO(poNumber);
            }
        }

        backToList() {
            if (this.viewModule && this.viewModule.backToList) {
                this.viewModule.backToList();
            }
        }

        async downloadDocument(documentId, fileName) {
            if (this.viewModule && this.viewModule.downloadDocument) {
                return await this.viewModule.downloadDocument(documentId, fileName);
            }
        }

        viewHistory(poNumber) {
            if (this.viewModule && this.viewModule.viewHistory) {
                this.viewModule.viewHistory(poNumber);
            }
        }

        printPODocument(poNumber, poId) {
            if (this.viewModule && this.viewModule.printPODocument) {
                this.viewModule.printPODocument(poNumber, poId);
            }
        }

        // View section management
        hideListSection() {
            if (this.viewModule && this.viewModule.hideListSection) {
                this.viewModule.hideListSection();
            }
        }

        showListSection() {
            if (this.viewModule && this.viewModule.showListSection) {
                this.viewModule.showListSection();
            }
        }

        showViewPOSection() {
            if (this.viewModule && this.viewModule.showViewPOSection) {
                this.viewModule.showViewPOSection();
            }
        }

        showViewPOLoading() {
            if (this.viewModule && this.viewModule.showViewPOLoading) {
                this.viewModule.showViewPOLoading();
            }
        }

        // Utility methods (delegated to view module)
        escapeHtml(text) {
            if (this.viewModule && this.viewModule.escapeHtml) {
                return this.viewModule.escapeHtml(text);
            }
            // Fallback
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        showError(message) {
            if (this.viewModule && this.viewModule.showError) {
                this.viewModule.showError(message);
            } else {
                alert(message);
            }
        }

        /**
         * Deprecated methods - kept for backward compatibility
         */

        async loadPurchaseTypesForFormatting() {
            console.warn('loadPurchaseTypesForFormatting is deprecated. Use tableModule.loadPurchaseTypesForFormatting instead.');
            if (this.tableModule && this.tableModule.loadPurchaseTypesForFormatting) {
                return await this.tableModule.loadPurchaseTypesForFormatting();
            }
        }

        async loadPODetailsForView(poNumber, prNumber) {
            console.warn('loadPODetailsForView is deprecated. Use viewModule.loadPODetailsForView instead.');
            if (this.viewModule && this.viewModule.loadPODetailsForView) {
                return await this.viewModule.loadPODetailsForView(poNumber, prNumber);
            }
        }

        async populateViewPO(po) {
            console.warn('populateViewPO is deprecated. Use viewModule.populateViewPO instead.');
            if (this.viewModule && this.viewModule.populateViewPO) {
                return await this.viewModule.populateViewPO(po);
            }
        }

        async getAdditionalInformationSummaryHTML(po) {
            console.warn('getAdditionalInformationSummaryHTML is deprecated. Use viewModule.getAdditionalInformationSummaryHTML instead.');
            if (this.viewModule && this.viewModule.getAdditionalInformationSummaryHTML) {
                return await this.viewModule.getAdditionalInformationSummaryHTML(po);
            }
            return '';
        }

        requiresAdditionalSection(typeId, subTypeId) {
            console.warn('requiresAdditionalSection is deprecated. Use viewModule.requiresAdditionalSection instead.');
            if (this.viewModule && this.viewModule.requiresAdditionalSection) {
                return this.viewModule.requiresAdditionalSection(typeId, subTypeId);
            }
            return false;
        }

        formatPurchaseType(purchType, row, escapeHtml = true) {
            console.warn('formatPurchaseType is deprecated. Use tableModule.formatPurchaseType instead.');
            if (this.tableModule && this.tableModule.formatPurchaseType) {
                return this.tableModule.formatPurchaseType(purchType, row, escapeHtml);
            }
            return escapeHtml ? this.escapeHtml(purchType || '-') : (purchType || '-');
        }

        formatPurchaseSubType(purchSubType, row, escapeHtml = true) {
            console.warn('formatPurchaseSubType is deprecated. Use tableModule.formatPurchaseSubType instead.');
            if (this.tableModule && this.tableModule.formatPurchaseSubType) {
                return this.tableModule.formatPurchaseSubType(purchSubType, row, escapeHtml);
            }
            return escapeHtml ? this.escapeHtml(purchSubType || '-') : (purchSubType || '-');
        }

        async getEmployeeNameByEmployId(employId) {
            console.warn('getEmployeeNameByEmployId is deprecated. Use employeeCacheModule.getEmployeeNameByEmployId instead.');
            if (this.employeeCacheModule && this.employeeCacheModule.getEmployeeNameByEmployId) {
                return await this.employeeCacheModule.getEmployeeNameByEmployId(employId);
            }
            return '';
        }

        async updateEmployeeNamesInTable() {
            console.warn('updateEmployeeNamesInTable is deprecated. Use tableModule.updateEmployeeNamesInTable instead.');
            if (this.tableModule && this.tableModule.updateEmployeeNamesInTable) {
                return await this.tableModule.updateEmployeeNamesInTable();
            }
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', () => {
        new POListManager();
    });
</script>
