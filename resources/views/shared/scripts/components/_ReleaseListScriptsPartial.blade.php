<script src="{{ asset('js/procurement/ProcurementSharedCache.js') }}"></script>

<script src="{{ asset('js/procurement/ReleaseListAPI.js') }}"></script>
<script src="{{ asset('js/procurement/ReleaseListFilter.js') }}"></script>
<script src="{{ asset('js/procurement/ReleaseListTable.js') }}"></script>
<script src="{{ asset('js/procurement/ReleaseListView.js') }}"></script>

<style>
    @@keyframes spin {
        to { transform: rotate(360deg); }
    }
    .spin {
        animation: spin 1s linear infinite;
    }
</style>

<script>
    'use strict';

    // Performance-optimized ReleaseList functionality class
    class ReleaseListManager {
        constructor() {
            this.filterCollapsed = true; // Default collapsed
            this.cachedElements = this.cacheElements();
            this.currentPRNumber = null;
            this.selectedPRNumbers = new Set(); // Track selected PRs for bulk release
            this.bulkyCheckboxMode = null; // null, 'mode1', or 'mode2' - tracks which checkbox mode is active
            this.bulkyPRAdditionalCache = new Map(); // Cache PR Additional data: PR Number -> { StartPeriod, EndPeriod, exists }
            this.isTogglingSelectAll = false; // Flag to prevent race condition in updateBulkySelectAllCheckbox

            // Initialize modules
            if (typeof ReleaseListAPI !== 'undefined') {
                this.apiModule = new ReleaseListAPI();
            }
            if (typeof ReleaseListFilter !== 'undefined') {
                this.filterModule = new ReleaseListFilter(this);
            }
            if (typeof ReleaseListTable !== 'undefined') {
                this.tableModule = new ReleaseListTable(this);
            }
            if (typeof ReleaseListView !== 'undefined') {
                this.viewModule = new ReleaseListView(this);
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
            }

            this.init();
        }

        cacheElements() {
            return {
                filterForm: document.getElementById('filterForm'),
                filterContent: document.getElementById('filter-content'),
                filterChevron: document.getElementById('filter-chevron'),
                releaseTable: document.getElementById('releaseTable')
            };
        }

        init() {
            this.bindEvents();

            // Initialize filter module
            if (this.filterModule) {
                this.filterModule.init();
            }

            // Initialize DataTable
            if (this.tableModule) {
                this.tableModule.initializeDataTable();
                // Store dataTable reference for backward compatibility
                if (this.tableModule.dataTable) {
                    this.dataTable = this.tableModule.dataTable;
                }
            } else {
                this.initializeDataTable();
            }
        }

        initializeDataTable() {
            // Delegate to Table module
            if (this.tableModule) {
                const result = this.tableModule.initializeDataTable();
                // Store dataTable reference for backward compatibility
                if (this.tableModule.dataTable) {
                    this.dataTable = this.tableModule.dataTable;
                }
                return result;
            }

            // Fallback if module not available
            console.warn('ReleaseListTable module not available');
        }

        bindEvents() {
            // Filter form submission is handled by filterModule.init()

            // Prevent form submit for bulkyReleaseForm (use custom validation instead)
            const bulkyReleaseForm = document.getElementById('bulkyReleaseForm');
            if (bulkyReleaseForm) {
                bulkyReleaseForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    // Validation is handled in submitBulkyRelease function
                });

                // Add event listeners to remove error messages when fields are filled
                bulkyReleaseForm.addEventListener('change', (e) => {
                    const target = e.target;
                    if (target) {
                        // Remove error message when field is changed
                        const fieldGroup = target.closest('.col-md-6') || target.closest('.col-12');
                        if (fieldGroup) {
                            const errorDiv = fieldGroup.querySelector('.field-error-message');
                            if (errorDiv) {
                                errorDiv.remove();
                            }
                        }

                        // For hidden inputs (dropdowns), also check the dropdown button
                        if (target.type === 'hidden') {
                            const dropdownBtnId = target.id + 'DropdownBtn';
                            const dropdownBtn = document.getElementById(dropdownBtnId);
                            if (dropdownBtn) {
                                const dropdownGroup = dropdownBtn.closest('.col-md-6') || dropdownBtn.closest('.col-12');
                                if (dropdownGroup) {
                                    const errorDiv = dropdownGroup.querySelector('.field-error-message');
                                    if (errorDiv) {
                                        errorDiv.remove();
                                    }
                                }
                            }
                        }

                        // Clear custom validity
                        if (target.setCustomValidity) {
                            target.setCustomValidity('');
                        }
                    }
                });

                // Also listen for input events for text fields
                bulkyReleaseForm.addEventListener('input', (e) => {
                    const target = e.target;
                    if (target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA')) {
                        const fieldGroup = target.closest('.col-md-6') || target.closest('.col-12');
                        if (fieldGroup) {
                            const errorDiv = fieldGroup.querySelector('.field-error-message');
                            if (errorDiv) {
                                errorDiv.remove();
                            }
                        }
                    }
                });

                // Listen for radio button changes
                bulkyReleaseForm.addEventListener('change', (e) => {
                    if (e.target && e.target.type === 'radio' && e.target.name === 'vendorType') {
                        const fieldGroup = e.target.closest('.col-md-6') || e.target.closest('.col-12');
                        if (fieldGroup) {
                            const errorDiv = fieldGroup.querySelector('.field-error-message');
                            if (errorDiv) {
                                errorDiv.remove();
                            }
                        }
                        // Clear custom validity on both radio buttons
                        const vendorTypeContract = bulkyReleaseForm.querySelector('#bulkyPageVendorTypeContract') || bulkyReleaseForm.querySelector('#vendorTypeContract');
                        const vendorTypeNonContract = bulkyReleaseForm.querySelector('#bulkyPageVendorTypeNonContract') || bulkyReleaseForm.querySelector('#vendorTypeNonContract');
                        if (vendorTypeContract) vendorTypeContract.setCustomValidity('');
                        if (vendorTypeNonContract) vendorTypeNonContract.setCustomValidity('');
                    }
                });
            }

            if (typeof toggleFilter === 'undefined') {
                window.toggleFilter = () => this.toggleFilter();
            }

            if (typeof searchRelease === 'undefined') {
                window.searchRelease = () => this.searchRelease();
            }

            if (typeof resetFilter === 'undefined') {
                window.resetFilter = () => this.resetFilter();
            }

            if (typeof viewRelease === 'undefined') {
                window.viewRelease = (prNumber) => this.viewRelease(prNumber);
            }

            if (typeof backToList === 'undefined') {
                window.backToList = () => this.backToList();
            }

            if (typeof submitRelease === 'undefined') {
                window.submitRelease = () => this.submitRelease();
            }

            if (typeof submitBulkyRelease === 'undefined') {
                window.submitBulkyRelease = () => this.submitBulkyRelease();
            }

            if (typeof toggleBulkyRowSelection === 'undefined') {
                window.toggleBulkyRowSelection = (prNumber, isSelected) => this.toggleBulkyRowSelection(prNumber, isSelected);
            }

            if (typeof toggleBulkySelectAll === 'undefined') {
                window.toggleBulkySelectAll = (isChecked) => this.toggleBulkySelectAll(isChecked);
            }

            if (typeof searchBulkyRelease === 'undefined') {
                window.searchBulkyRelease = () => this.searchBulkyRelease();
            }

            if (typeof resetBulkyReleaseFilter === 'undefined') {
                window.resetBulkyReleaseFilter = () => this.resetBulkyReleaseFilter();
            }

            if (typeof showBulkReleasePage === 'undefined') {
                window.showBulkReleasePage = () => this.showBulkReleasePage();
            }

            if (typeof downloadDocument === 'undefined') {
                const self = this;
                window.downloadDocument = function(documentId) {
                    // Get fileName from button's data attribute using 'this' context
                    const button = this;
                    const fileName = button?.getAttribute('data-file-name') || 'document';
                    if (self.viewModule && self.viewModule.downloadDocument) {
                        self.viewModule.downloadDocument.call(button, documentId, fileName);
                    } else {
                        self.downloadDocument(documentId, fileName);
                    }
                };
            }
        }

        toggleFilter() {
            if (this.filterModule && this.filterModule.toggleFilter) {
                return this.filterModule.toggleFilter();
            }
            console.warn('ReleaseListFilter module not available');
        }

        debouncedSearch() {
            if (this.filterModule && this.filterModule.debouncedSearch) {
                return this.filterModule.debouncedSearch();
            }
            console.warn('ReleaseListFilter module not available');
        }

        async searchRelease() {
            if (this.tableModule && this.tableModule.searchRelease) {
                return this.tableModule.searchRelease();
            }
            // Fallback if module not available
            if (this.dataTable) {
                this.dataTable.ajax.reload();
            }
        }

        async resetFilter() {
            if (this.filterModule && this.filterModule.resetFilter) {
                return this.filterModule.resetFilter();
            }
            console.warn('ReleaseListFilter module not available');
        }

        async refreshTable() {
            if (this.dataTable) {
                this.dataTable.ajax.reload();
            } else {
                // DataTable will load automatically on initialization
            }
        }

        updateDateRangeInfo() {
            if (this.filterModule && this.filterModule.updateDateRangeInfo) {
                return this.filterModule.updateDateRangeInfo();
            }
            console.warn('ReleaseListFilter module not available');
        }

        showBulkReleasePage() {
            // Hide list section and show bulky release section
            this.hideListSection();
            this.showBulkyReleaseSection();

            // Initialize bulky release DataTable if not already initialized
            if (this.tableModule && this.tableModule.initializeBulkyReleaseDataTable) {
                if (!this.tableModule.bulkyReleaseDataTable) {
                    this.tableModule.initializeBulkyReleaseDataTable();
                } else {
                    this.tableModule.bulkyReleaseDataTable.ajax.reload();
                }
            } else {
                console.warn('ReleaseListTable module not available for bulky release');
            }

            // Store reference for backward compatibility (after initialization)
            if (this.tableModule && this.tableModule.bulkyReleaseDataTable) {
                this.bulkyReleaseDataTable = this.tableModule.bulkyReleaseDataTable;
            }
        }

        hideListSection() {
            if (this.viewModule && this.viewModule.hideListSection) {
                return this.viewModule.hideListSection();
            }
            // Fallback
            const filterSection = document.querySelector('.filter-section');
            const listSection = document.getElementById('listSection');
            const viewReleaseSection = document.getElementById('viewReleaseSection');

            if (filterSection) filterSection.style.display = 'none';
            if (listSection) listSection.style.display = 'none';
            if (viewReleaseSection) viewReleaseSection.style.display = 'none';
        }

        showListSection() {
            if (this.viewModule && this.viewModule.showListSection) {
                return this.viewModule.showListSection();
            }
            // Fallback
            const filterSection = document.querySelector('.filter-section');
            const listSection = document.getElementById('listSection');
            const viewReleaseSection = document.getElementById('viewReleaseSection');
            const bulkyReleaseSection = document.getElementById('bulkyReleaseSection');

            if (filterSection) filterSection.style.display = 'block';
            if (listSection) listSection.style.display = 'block';
            if (viewReleaseSection) viewReleaseSection.style.display = 'none';
            if (bulkyReleaseSection) bulkyReleaseSection.style.display = 'none';
        }

        showBulkyReleaseSection() {
            const bulkyReleaseSection = document.getElementById('bulkyReleaseSection');
            if (bulkyReleaseSection) {
                bulkyReleaseSection.style.display = 'block';
            }
        }

        async toggleBulkyRowSelection(prNumber, isSelected) {
            if (!prNumber) return;

            if (isSelected) {
                // When first selection: load period data for ALL PRs in table, then set mode and disable wrong-group checkboxes
                if (this.bulkyCheckboxMode === null) {
                    // Always load additional data for entire table so we can disable PRs that don't match TOP group
                    if (this.tableModule && this.tableModule.loadBulkyPRAdditionalData) {
                        await this.tableModule.loadBulkyPRAdditionalData();
                    }
                    // Determine mode from first selected PR (same logic as "Should filter TOP")
                    const hasPeriods = this.tableModule && this.tableModule.getPRHasPeriods
                        ? this.tableModule.getPRHasPeriods(prNumber)
                        : false;
                    // mode2 = PRs WITH periods (TOP filtered to 765/766/767/768); mode1 = PRs WITHOUT periods
                    this.bulkyCheckboxMode = hasPeriods ? 'mode2' : 'mode1';
                    // Disable checkboxes for all PRs that don't belong to this TOP group
                    if (this.tableModule && this.tableModule.updateBulkyCheckboxStates) {
                        this.tableModule.updateBulkyCheckboxStates();
                        // Re-apply after short delay so DataTables-rendered DOM is fully ready
                        setTimeout(() => this.tableModule.updateBulkyCheckboxStates(), 150);
                    }
                }

                // Reload TOP dropdown after PR is selected to apply filter based on StartPeriod/EndPeriod
                if (typeof window.loadBulkyPageTermOfPayments === 'function') {
                    setTimeout(() => {
                        window.loadBulkyPageTermOfPayments();
                    }, 300);
                }

                // Validate if this PR can be selected based on current mode
                const canSelect = this.canSelectBulkyPR(prNumber);
                if (!canSelect) {
                    // Revert checkbox state
                    const checkbox = document.querySelector(`#bulkyReleaseTableBody .row-checkbox[data-pr-number="${this.escapeHtml(prNumber)}"]`);
                    if (checkbox) {
                        checkbox.checked = false;
                    }

                    // Show appropriate error message
                    if (this.viewModule && this.viewModule.showAlertModal) {
                        if (this.bulkyCheckboxMode === 'mode1') {
                            this.viewModule.showAlertModal('Only Purchase Requests without Additional data or with NULL StartPeriod/EndPeriod can be selected.', 'warning');
                        } else if (this.bulkyCheckboxMode === 'mode2') {
                            this.viewModule.showAlertModal('Only Purchase Requests with Additional data and non-NULL StartPeriod/EndPeriod can be selected.', 'warning');
                        }
                    }
                    return;
                }

                this.selectedPRNumbers.add(prNumber);
            } else {
                // Unchecking - if this was the last selected PR, reset mode
                this.selectedPRNumbers.delete(prNumber);

                if (this.selectedPRNumbers.size === 0) {
                    this.bulkyCheckboxMode = null;
                    if (this.tableModule && this.tableModule.updateBulkyCheckboxStates) {
                        this.tableModule.updateBulkyCheckboxStates();
                    }
                }
            }

            this.updateBulkySelectAllCheckbox();
        }

        async toggleBulkySelectAll(isChecked) {
            if (this.tableModule && this.tableModule.toggleBulkySelectAll) {
                return this.tableModule.toggleBulkySelectAll(isChecked);
            }
            console.warn('ReleaseListTable module not available');
        }

        updateBulkySelectAllCheckbox() {
            if (this.tableModule && this.tableModule.updateBulkySelectAllCheckbox) {
                return this.tableModule.updateBulkySelectAllCheckbox();
            }
            console.warn('ReleaseListTable module not available');
        }

        async searchBulkyRelease() {
            if (this.tableModule && this.tableModule.searchBulkyRelease) {
                return this.tableModule.searchBulkyRelease();
            }
            // Fallback if module not available
            if (this.bulkyReleaseDataTable) {
                this.bulkyReleaseDataTable.ajax.reload();
            }
        }

        async resetBulkyReleaseFilter() {
            if (this.filterModule && this.filterModule.resetBulkyReleaseFilter) {
                return this.filterModule.resetBulkyReleaseFilter();
            }
            console.warn('ReleaseListFilter module not available');
        }

        canSelectBulkyPR(prNumber) {
            if (this.tableModule && this.tableModule.canSelectBulkyPR) {
                return this.tableModule.canSelectBulkyPR(prNumber);
            }
            console.warn('ReleaseListTable module not available');
            return false;
        }

        async viewRelease(prNumber) {
            if (this.viewModule && this.viewModule.viewRelease) {
                return this.viewModule.viewRelease(prNumber);
            }
            console.warn('ReleaseListView module not available');
        }

        async submitRelease() {
            if (this.viewModule && this.viewModule.submitRelease) {
                return this.viewModule.submitRelease();
            }
            console.warn('ReleaseListView module not available');
        }

        async submitBulkyRelease() {
            if (this.viewModule && this.viewModule.submitBulkyRelease) {
                return this.viewModule.submitBulkyRelease();
            }
            console.warn('ReleaseListView module not available');
        }

        backToList() {
            if (this.viewModule && this.viewModule.backToList) {
                return this.viewModule.backToList();
            }
            // Fallback
            this.currentPRNumber = null;
            this.selectedPRNumbers.clear();
            this.showListSection();
        }

        async downloadDocument(documentId, fileName) {
            if (this.viewModule && this.viewModule.downloadDocument) {
                return this.viewModule.downloadDocument(documentId, fileName);
            }
            console.warn('ReleaseListView module not available');
        }

        // Helper functions for backward compatibility
        escapeHtml(text) {
            if (this.viewModule && this.viewModule.escapeHtml) {
                return this.viewModule.escapeHtml(text);
            }
            // Fallback
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        showError(message) {
            if (this.viewModule && this.viewModule.showError) {
                return this.viewModule.showError(message);
            }
            // Fallback
            const tbody = document.getElementById('releaseTableBody');
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="12" class="text-center py-4">
                            <div class="text-danger">${this.escapeHtml(message)}</div>
                        </td>
                    </tr>
                `;
            }
        }

        showSuccessMessage(message) {
            if (this.viewModule && this.viewModule.showSuccessMessage) {
                return this.viewModule.showSuccessMessage(message);
            }
            // Fallback
            const existingAlert = document.querySelector('.alert-success');
            if (existingAlert) {
                existingAlert.remove();
            }

            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show';
            alertDiv.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="icon-base bx bx-check-circle me-2"></i>
                    <span>${this.escapeHtml(message)}</span>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;

            const container = document.querySelector('.release-list-container');
            if (container) {
                container.insertBefore(alertDiv, container.firstChild);
            } else {
                document.body.insertBefore(alertDiv, document.body.firstChild);
            }

            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 3000);
        }

        showAlertModal(message, type = 'info', onClose = null) {
            if (this.viewModule && this.viewModule.showAlertModal) {
                return this.viewModule.showAlertModal(message, type, onClose);
            }
            // Fallback
            if (typeof Swal === 'undefined') {
                window.alert(message);
                if (onClose && typeof onClose === 'function') {
                    onClose();
                }
                return;
            }

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
            }).then((result) => {
                if (onClose && typeof onClose === 'function') {
                    onClose();
                }
            });
        }
    }

    // Flag to prevent multiple initializations
    let releaseListInitialized = false;

    // Initialize manager when all dependencies are ready
    function initializeReleaseList() {
        // Prevent multiple initializations
        if (releaseListInitialized) {
            return;
        }

        if (typeof $ === 'undefined' || typeof $.fn.DataTable === 'undefined') {
            setTimeout(initializeReleaseList, 100);
            return;
        }

        if (typeof CreateTable === 'undefined' || typeof ColumnBuilder === 'undefined') {
            setTimeout(initializeReleaseList, 100);
            return;
        }

        // All dependencies are ready, initialize
        window.releaseListManager = new ReleaseListManager();
        releaseListInitialized = true;
    }

    // Start initialization when DOM is ready (only once)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeReleaseList);
    } else {
        initializeReleaseList();
    }

    // Global function for toggle BulkyRelease filter
    function toggleBulkyReleaseFilter() {
        const filterContent = document.getElementById('bulkyReleaseFilterContent');
        const chevron = document.getElementById('bulkyReleaseFilterChevron');
        const header = document.querySelector('[onclick="toggleBulkyReleaseFilter()"]');

        if (filterContent && chevron && header) {
            const isCollapsed = filterContent.style.display === 'none' || !filterContent.style.display;

            if (isCollapsed) {
                filterContent.style.display = 'block';
                chevron.classList.remove('bx-chevron-down');
                chevron.classList.add('bx-chevron-up');
                header.setAttribute('aria-expanded', 'true');
            } else {
                filterContent.style.display = 'none';
                chevron.classList.remove('bx-chevron-up');
                chevron.classList.add('bx-chevron-down');
                header.setAttribute('aria-expanded', 'false');
            }
        }
    }
</script>
