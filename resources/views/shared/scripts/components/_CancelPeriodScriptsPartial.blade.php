{{-- CancelPeriod Scripts Partial --}}
<script src="{{ asset('js/procurement/ProcurementSharedCache.js') }}"></script>
<script src="{{ asset('js/procurement/ProcurementWizardUtils.js') }}"></script>

<script src="{{ asset('js/procurement/CancelPeriodAPI.js') }}"></script>
<script src="{{ asset('js/procurement/CancelPeriodFilter.js') }}"></script>
<script src="{{ asset('js/procurement/CancelPeriodTable.js') }}"></script>
<script src="{{ asset('js/procurement/CancelPeriodView.js') }}"></script>

<script>
    'use strict';

    window.renderDropdownWithSearch = window.renderDropdownWithSearch
        || (window.ProcurementWizardUtils && window.ProcurementWizardUtils.renderDropdownWithSearch);

    window.toggleFilter = function() {
        if (window.cancelPeriodManager && typeof window.cancelPeriodManager.toggleFilter === 'function') {
            window.cancelPeriodManager.toggleFilter();
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

    class CancelPeriodManager {
        constructor() {
            this.cachedElements = this.cacheElements();
            this.currentPO = null;
            this.filterCollapsed = true;
            this.dataTable = null;

            if (typeof CancelPeriodAPI !== 'undefined') {
                this.apiModule = new CancelPeriodAPI();
            }
            if (typeof CancelPeriodFilter !== 'undefined') {
                this.filterModule = new CancelPeriodFilter(this);
            }
            if (typeof CancelPeriodTable !== 'undefined') {
                this.tableModule = new CancelPeriodTable(this);
            }
            if (typeof CancelPeriodView !== 'undefined') {
                this.viewModule = new CancelPeriodView(this);
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
                poTable: document.getElementById('poTable')
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
                        this.dataTable = this.tableModule.dataTable;
                    }
                } catch (error) {
                    console.error('Error initializing DataTable:', error);
                }
            }

            await this.loadMasterData();
        }

        async loadMasterData() {
            try {
                if (this.apiModule && this.apiModule.getPurchaseTypes) {
                    const purchaseTypes = await this.apiModule.getPurchaseTypes();
                    this.populateDropdown('filterPurchaseType', purchaseTypes, 'ID', 'PurchaseRequestType', true);
                }
            } catch (error) {
                console.error('Error loading master data:', error);
            }
        }

        populateDropdown(dropdownId, data, valueField, textField, includeAll = false) {
            const dropdown = $(`#${dropdownId}`);
            dropdown.empty();
            if (includeAll) {
                dropdown.append($('<option></option>').val('').text('All'));
            }
            data.forEach(item => {
                dropdown.append($('<option></option>').val(item[valueField] || item[valueField.toLowerCase()]).text(item[textField] || item[textField.toLowerCase()]));
            });
        }

        bindEvents() {
            window.cancelPeriodManager = this;

            if (typeof applyFilter === 'undefined') {
                window.applyFilter = () => this.applyFilter();
            }

            if (typeof resetFilter === 'undefined') {
                window.resetFilter = () => this.resetFilter();
            }

            if (typeof backToList === 'undefined') {
                window.backToList = () => this.backToList();
            }

            if (typeof submitCancelPeriod === 'undefined') {
                window.submitCancelPeriod = () => this.submitCancelPeriod();
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

        async applyFilter() {
            if (this.tableModule && this.tableModule.searchPO) {
                return this.tableModule.searchPO();
            } else if (this.dataTable) {
                this.dataTable.ajax.reload();
            }
        }

        async resetFilter() {
            if (this.filterModule && this.filterModule.resetFilter) {
                return this.filterModule.resetFilter();
            }
        }

        async searchPO() {
            if (this.tableModule && this.tableModule.searchPO) {
                return this.tableModule.searchPO();
            } else if (this.dataTable) {
                this.dataTable.ajax.reload();
            }
        }

        async initializeDataTable() {
            if (this.tableModule && this.tableModule.initializeDataTable) {
                const result = await this.tableModule.initializeDataTable();
                if (this.tableModule.dataTable) {
                    this.dataTable = this.tableModule.dataTable;
                }
                return result;
            }
            console.warn('Table module not available');
        }

        refreshTable() {
            if (this.tableModule && this.tableModule.refreshTable) {
                this.tableModule.refreshTable();
            } else if (this.dataTable) {
                this.dataTable.ajax.reload();
            }
        }

        async selectPO(poNumber) {
            if (this.viewModule && this.viewModule.selectPO) {
                return await this.viewModule.selectPO(poNumber);
            }
        }

        backToList() {
            if (this.viewModule && this.viewModule.backToList) {
                this.viewModule.backToList();
            }
        }

        async submitCancelPeriod() {
            if (this.viewModule && this.viewModule.submitCancelPeriod) {
                return await this.viewModule.submitCancelPeriod();
            }
        }

        escapeHtml(text) {
            if (this.viewModule && this.viewModule.escapeHtml) {
                return this.viewModule.escapeHtml(text);
            }
            if (text === null || text === undefined) return '';
            return String(text).replace(/[&<>"'`=\/]/g, (s) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
                '/': '&#x2F;', '`': '&#x60;', '=': '&#x3D;'
            })[s]);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        window.cancelPeriodManager = new CancelPeriodManager();
    });
</script>
