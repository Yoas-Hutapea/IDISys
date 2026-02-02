<script src="{{ asset('js/procurement/ProcurementSharedCache.js') }}"></script>
<script src="{{ asset('js/procurement/ProcurementWizardUtils.js') }}"></script>

<script src="{{ asset('js/procurement/ApprovalPOListAPI.js') }}"></script>
<script src="{{ asset('js/procurement/ApprovalPOListFilter.js') }}"></script>
<script src="{{ asset('js/procurement/ApprovalPOListTable.js') }}"></script>
<script src="{{ asset('js/procurement/ApprovalPOListView.js') }}"></script>
<script src="{{ asset('js/procurement/ApprovalPOListApproval.js') }}"></script>

<script>
    'use strict';

    if (typeof window.renderDropdownWithSearch !== 'function' &&
        window.ProcurementWizardUtils &&
        typeof window.ProcurementWizardUtils.renderDropdownWithSearch === 'function') {
        window.renderDropdownWithSearch = (config) => window.ProcurementWizardUtils.renderDropdownWithSearch(config);
    }

    window.toggleFilter = function() {
        if (window.approvalPOManager && typeof window.approvalPOManager.toggleFilter === 'function') {
            window.approvalPOManager.toggleFilter();
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

    class ApprovalPOManager {
        constructor() {
            this.currentPage = 1;
            this.rowsPerPage = 10;
            this.totalRecords = 0;
            this.filterCollapsed = true;
            this.debounceTimer = null;
            this.isLoading = false;
            this.selectedPONumbers = new Set();
            this.cachedElements = this.cacheElements();
            this.approvalPODataTable = null;
            this.currentPONumber = null;
            this.viewPOItems = [];
            this.viewPRData = null;
            this.viewPRDocuments = [];
            this.viewPOAdditional = null;

            if (typeof ApprovalPOListAPI !== 'undefined') {
                this.apiModule = new ApprovalPOListAPI();
            }
            if (typeof ApprovalPOListFilter !== 'undefined') {
                this.filterModule = new ApprovalPOListFilter(this);
            }
            if (typeof ApprovalPOListTable !== 'undefined') {
                this.tableModule = new ApprovalPOListTable(this);
            }
            if (typeof ApprovalPOListView !== 'undefined') {
                this.viewModule = new ApprovalPOListView(this);
            }
            if (typeof ApprovalPOListApproval !== 'undefined') {
                this.approvalModule = new ApprovalPOListApproval(this);
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
            } else if (window.confirmPOManager && window.confirmPOManager.employeeCacheModule) {
                this.employeeCacheModule = window.confirmPOManager.employeeCacheModule;
            }

            this.init();
        }

        cacheElements() {
            return {
                filterForm: document.getElementById('filterForm'),
                filterContent: document.getElementById('filter-content'),
                filterChevron: document.getElementById('filter-chevron'),
                approvalPOTable: document.getElementById('approvalPOTable'),
                processBulkApprovalBtn: document.getElementById('processBulkApprovalBtn')
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
                        this.approvalPODataTable = this.tableModule.dataTable;
                    }
                } catch (error) {
                    console.error('Error initializing DataTable:', error);
                }
            } else {
                console.warn('ApprovalPOListTable module not available, falling back to legacy initialization');
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
            window.approvalPOManager = this;

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
            } else if (this.approvalPODataTable) {
                this.approvalPODataTable.ajax.reload();
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
                    this.approvalPODataTable = this.tableModule.dataTable;
                }
                return result;
            }
            console.warn('Table module not available');
        }

        refreshTable() {
            if (this.tableModule && this.tableModule.refreshTable) {
                this.tableModule.refreshTable();
            } else if (this.approvalPODataTable) {
                this.approvalPODataTable.ajax.reload();
            }
        }

        async viewApproval(poNumber) {
            if (this.viewModule && this.viewModule.viewApproval) {
                return await this.viewModule.viewApproval(poNumber);
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

        async processBulkApproval() {
            if (this.approvalModule && this.approvalModule.processBulkApproval) {
                return await this.approvalModule.processBulkApproval();
            }
        }

        async submitApproval() {
            if (this.approvalModule && this.approvalModule.submitApproval) {
                return await this.approvalModule.submitApproval();
            }
        }

        toggleSelectAll() {
            if (this.approvalModule && this.approvalModule.toggleSelectAll) {
                return this.approvalModule.toggleSelectAll();
            }
        }

        toggleSelectPO(poNumber) {
            if (this.approvalModule && this.approvalModule.toggleSelectPO) {
                return this.approvalModule.toggleSelectPO(poNumber);
            }
        }

        updateProcessButtonState() {
            if (this.approvalModule && this.approvalModule.updateProcessButtonState) {
                return this.approvalModule.updateProcessButtonState();
            }
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
            if (this.approvalModule && this.approvalModule.showAlertModal) {
                return this.approvalModule.showAlertModal(message, type, onClose);
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
    }

    document.addEventListener('DOMContentLoaded', () => {
        new ApprovalPOManager();
    });
</script>
