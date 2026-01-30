<script src="{{ asset('js/procurement/ProcurementSharedCache.js') }}"></script>
<script src="{{ asset('js/procurement/ProcurementWizardUtils.js') }}"></script>

<script src="{{ asset('js/procurement/ConfirmPOListAPI.js') }}"></script>
<script src="{{ asset('js/procurement/ConfirmPOListFilter.js') }}"></script>
<script src="{{ asset('js/procurement/ConfirmPOListTable.js') }}"></script>
<script src="{{ asset('js/procurement/ConfirmPOListView.js') }}"></script>
<script src="{{ asset('js/procurement/ConfirmPOListConfirm.js') }}"></script>

<script>
    'use strict';

    if (typeof window.renderDropdownWithSearch !== 'function' &&
        window.ProcurementWizardUtils &&
        typeof window.ProcurementWizardUtils.renderDropdownWithSearch === 'function') {
        window.renderDropdownWithSearch = (config) => window.ProcurementWizardUtils.renderDropdownWithSearch(config);
    }

    window.toggleFilter = function() {
        if (window.confirmPOManager && typeof window.confirmPOManager.toggleFilter === 'function') {
            window.confirmPOManager.toggleFilter();
        } else {
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

    class ConfirmPOManager {
        constructor() {
            this.currentPage = 1;
            this.rowsPerPage = 10;
            this.totalRecords = 0;
            this.filterCollapsed = true;
            this.debounceTimer = null;
            this.isLoading = false;
            this.selectedPONumbers = new Set();
            this.cachedElements = this.cacheElements();
            this.confirmPODataTable = null;
            this.deletedItemIds = new Set();
            this.currentPONumber = null;
            this.originalItemsData = [];
            this.viewPOAdditional = null;

            if (typeof ConfirmPOListAPI !== 'undefined') {
                this.apiModule = new ConfirmPOListAPI();
            }
            if (typeof ConfirmPOListFilter !== 'undefined') {
                this.filterModule = new ConfirmPOListFilter(this);
            }
            if (typeof ConfirmPOListTable !== 'undefined') {
                this.tableModule = new ConfirmPOListTable(this);
            }
            if (typeof ConfirmPOListView !== 'undefined') {
                this.viewModule = new ConfirmPOListView(this);
            }
            if (typeof ConfirmPOListConfirm !== 'undefined') {
                this.confirmModule = new ConfirmPOListConfirm(this);
            }
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
            } else if (window.poListManager && window.poListManager.employeeCacheModule) {
                this.employeeCacheModule = window.poListManager.employeeCacheModule;
            }

            this.init();
        }

        cacheElements() {
            return {
                filterForm: document.getElementById('filterForm'),
                filterContent: document.getElementById('filter-content'),
                filterChevron: document.getElementById('filter-chevron'),
                confirmPOTable: document.getElementById('confirmPOTable'),
                processBulkConfirmBtn: document.getElementById('processBulkConfirmBtn'),
                updateBulkyPriceBtn: document.getElementById('updateBulkyPriceBtn')
            };
        }

        async init() {
            this.bindEvents();

            if (this.filterModule) {
                this.filterModule.init();
            }

            if (this.tableModule) {
                try {
                    await this.tableModule.initializeDataTable();
                    if (this.tableModule.dataTable) {
                        this.confirmPODataTable = this.tableModule.dataTable;
                    }
                } catch (error) {
                    console.error('Error initializing DataTable:', error);
                }
            } else {
                console.warn('ConfirmPOListTable module not available, falling back to legacy initialization');
                await this.initializeDataTable();
            }

            setTimeout(() => {
                if (this.filterModule && this.filterModule.updateDateRangeInfo) {
                    this.filterModule.updateDateRangeInfo();
                } else {
                    this.updateDateRangeInfo();
                }
            }, 200);
        }

        bindEvents() {
            window.confirmPOManager = this;

            if (typeof toggleFilter === 'undefined') {
                window.toggleFilter = () => this.toggleFilter();
            }

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
            } else if (this.confirmPODataTable) {
                this.confirmPODataTable.ajax.reload();
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

        initializeDataTable() {
            if (this.tableModule && this.tableModule.initializeDataTable) {
                const result = this.tableModule.initializeDataTable();
                if (this.tableModule.dataTable) {
                    this.confirmPODataTable = this.tableModule.dataTable;
                }
                return result;
            }
            console.warn('Table module not available');
        }

        refreshTable() {
            if (this.tableModule && this.tableModule.refreshTable) {
                this.tableModule.refreshTable();
            } else if (this.confirmPODataTable) {
                this.confirmPODataTable.ajax.reload();
            }
        }

        async viewConfirm(poNumber) {
            if (this.viewModule && this.viewModule.viewConfirm) {
                return await this.viewModule.viewConfirm(poNumber);
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

        async editItem(button) {
            if (this.viewModule && this.viewModule.editItem) {
                return await this.viewModule.editItem(button);
            }
        }

        deleteItem(button) {
            if (this.viewModule && this.viewModule.deleteItem) {
                return this.viewModule.deleteItem(button);
            }
        }

        updateAmountTotal() {
            if (this.viewModule && this.viewModule.updateAmountTotal) {
                return this.viewModule.updateAmountTotal();
            }
        }

        async processBulkConfirm() {
            if (this.confirmModule && this.confirmModule.processBulkConfirm) {
                return await this.confirmModule.processBulkConfirm();
            }
        }

        async submitConfirm() {
            if (this.confirmModule && this.confirmModule.submitConfirm) {
                return await this.confirmModule.submitConfirm();
            }
        }

        toggleSelectAll() {
            if (this.confirmModule && this.confirmModule.toggleSelectAll) {
                return this.confirmModule.toggleSelectAll();
            }
        }

        toggleSelectPO(poNumber) {
            if (this.confirmModule && this.confirmModule.toggleSelectPO) {
                return this.confirmModule.toggleSelectPO(poNumber);
            }
        }

        updateProcessButtonState() {
            if (this.confirmModule && this.confirmModule.updateProcessButtonState) {
                return this.confirmModule.updateProcessButtonState();
            }
        }

        async showUpdateBulkyPrice() {
            const listSection = document.getElementById('listSection');
            const updateSection = document.getElementById('updateBulkyPriceSection');
            const filterSection = document.querySelector('.filter-section');

            if (listSection) listSection.style.display = 'none';
            if (filterSection) filterSection.style.display = 'none';
            if (updateSection) updateSection.style.display = 'block';

            if (typeof loadUpdateBulkyPriceMasterData === 'function') {
                try {
                    await loadUpdateBulkyPriceMasterData();
                } catch (error) {
                    console.error('Error loading master data for Update Bulky Price:', error);
                }
            }

            if (typeof getUpdateBulkyPriceManager === 'function') {
                getUpdateBulkyPriceManager();
            } else if (typeof updateBulkyPriceManager === 'undefined') {
                console.warn('UpdateBulkyPriceManager not available. Make sure _UpdateBulkyPriceScriptsPartial is loaded.');
            }
        }

        backToConfirmList() {
            const listSection = document.getElementById('listSection');
            const updateSection = document.getElementById('updateBulkyPriceSection');
            const filterSection = document.querySelector('.filter-section');

            if (listSection) listSection.style.display = 'block';
            if (filterSection) filterSection.style.display = 'block';
            if (updateSection) updateSection.style.display = 'none';
        }

        escapeHtml(text) {
            if (this.viewModule && this.viewModule.escapeHtml) {
                return this.viewModule.escapeHtml(text);
            }
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

        showAlertModal(message, type = 'info', onClose = null) {
            if (this.confirmModule && this.confirmModule.showAlertModal) {
                return this.confirmModule.showAlertModal(message, type, onClose);
            }
            if (typeof Swal === 'undefined') {
                window.alert(message);
                if (onClose && typeof onClose === 'function') {
                    onClose();
                }
            } else {
                const iconMap = {
                    'success': 'success',
                    'warning': 'warning',
                    'danger': 'error',
                    'error': 'error',
                    'info': 'info'
                };
                const icon = iconMap[type] || 'info';
                Swal.fire({
                    title: message,
                    icon: icon,
                    confirmButtonColor: '#696cff',
                    confirmButtonText: 'OK',
                    allowOutsideClick: false,
                    allowEscapeKey: true,
                    animation: true,
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    }
                }).then(() => {
                    if (onClose && typeof onClose === 'function') {
                        onClose();
                    }
                });
            }
        }

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

        async populateConfirmView(poData, prData, itemsData, documentsData) {
            console.warn('populateConfirmView is deprecated. Use viewModule.populateConfirmView instead.');
            if (this.viewModule && this.viewModule.populateConfirmView) {
                return await this.viewModule.populateConfirmView(poData, prData, itemsData, documentsData);
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
    }

    document.addEventListener('DOMContentLoaded', () => {
        new ConfirmPOManager();
    });
</script>
