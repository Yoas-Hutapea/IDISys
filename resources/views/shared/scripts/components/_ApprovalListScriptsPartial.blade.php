<script src="{{ asset('js/procurement/ProcurementSharedCache.js') }}"></script>

<script src="{{ asset('js/procurement/ApprovalListAPI.js') }}"></script>
<script src="{{ asset('js/procurement/ApprovalListFilter.js') }}"></script>
<script src="{{ asset('js/procurement/ApprovalListTable.js') }}"></script>
<script src="{{ asset('js/procurement/ApprovalListView.js') }}"></script>

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

    // Performance-optimized ApprovalList functionality class
    class ApprovalListManager {
        constructor() {
            this.currentPage = 1;
            this.rowsPerPage = 10;
            this.totalRecords = 0;
            this.isLoading = false;
            this.cachedElements = this.cacheElements();

            // Initialize modules
            if (typeof ApprovalListAPI !== 'undefined') {
                this.apiModule = new ApprovalListAPI();
            }
            if (typeof ApprovalListFilter !== 'undefined') {
                this.filterModule = new ApprovalListFilter(this);
            }
            if (typeof ApprovalListTable !== 'undefined') {
                this.tableModule = new ApprovalListTable(this);
            }
            if (typeof ApprovalListView !== 'undefined') {
                this.viewModule = new ApprovalListView(this);
            }
            // Use shared employee cache if available
            if (window.procurementSharedCache && window.procurementSharedCache.getEmployeeNameByEmployId) {
                this.employeeCacheModule = window.procurementSharedCache;
            } else if (window.prListManager && window.prListManager.employeeCacheModule) {
                this.employeeCacheModule = window.prListManager.employeeCacheModule;
            }

            this.init();
        }

        cacheElements() {
            return {
                filterForm: document.getElementById('filterForm'),
                filterContent: document.getElementById('filter-content'),
                filterChevron: document.getElementById('filter-chevron'),
                approvalTable: document.getElementById('approvalTable')
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

            // Don't call loadData() here - DataTable will load automatically
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
            console.warn('ApprovalListTable module not available');
        }

        bindEvents() {
            // Filter events are handled by filterModule.init()

            // Rows per page is now handled by DataTables automatically

            if (typeof searchApproval === 'undefined') {
                window.searchApproval = () => this.searchApproval();
            }

            if (typeof resetFilter === 'undefined') {
                window.resetFilter = () => this.resetFilter();
            }

            if (typeof toggleFilter === 'undefined') {
                window.toggleFilter = () => this.toggleFilter();
            }

            if (typeof viewApproval === 'undefined') {
                window.viewApproval = (prNumber) => this.viewApproval(prNumber);
            }

            if (typeof backToList === 'undefined') {
                window.backToList = () => this.backToList();
            }

            if (typeof submitApproval === 'undefined') {
                window.submitApproval = () => this.submitApproval();
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

            if (typeof goToPage === 'undefined') {
                window.goToPage = (page) => this.goToPage(page);
            }
        }

        toggleFilter() {
            if (this.filterModule && this.filterModule.toggleFilter) {
                return this.filterModule.toggleFilter();
            }
            console.warn('ApprovalListFilter module not available');
        }

        debouncedSearch() {
            if (this.filterModule && this.filterModule.debouncedSearch) {
                return this.filterModule.debouncedSearch();
            }
            console.warn('ApprovalListFilter module not available');
        }

        async searchApproval() {
            if (this.tableModule && this.tableModule.searchApproval) {
                return this.tableModule.searchApproval();
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
            console.warn('ApprovalListFilter module not available');
        }

        updateDateRangeInfo() {
            if (this.filterModule && this.filterModule.updateDateRangeInfo) {
                return this.filterModule.updateDateRangeInfo();
            }
            console.warn('ApprovalListFilter module not available');
        }

        showLoading() {
            const tbody = document.getElementById('approvalTableBody');
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="12" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="mt-2">Loading...</div>
                        </td>
                    </tr>
                `;
            }
        }

        async refreshTable() {
            if (this.tableModule && this.tableModule.refreshTable) {
                return this.tableModule.refreshTable();
            }
            // Fallback if module not available
            if (this.dataTable) {
                this.dataTable.ajax.reload();
            }
        }

        // loadData and renderTable are no longer needed - DataTable handles this automatically
        // Kept for backward compatibility if needed (fallback when DataTable is not available)
        async loadData() {
            // This function is deprecated - DataTable handles data loading automatically
            console.warn('loadData is deprecated - DataTable handles data loading automatically');
        }

        async renderTable(data) {
            // This function is deprecated - DataTable handles table rendering automatically
            console.warn('renderTable is deprecated - DataTable handles table rendering automatically');
        }

        // Update employee names in DataTable - Delegated to Table module
        async updateEmployeeNamesInTable() {
            if (this.tableModule && this.tableModule.updateEmployeeNamesInTable) {
                return this.tableModule.updateEmployeeNamesInTable();
            }
            console.warn('ApprovalListTable module not available');
        }

        // Helper function to get employee name - Delegated to shared cache or employee cache module
        async getEmployeeNameByEmployId(employId) {
            // Use shared cache or employee cache module if available
            if (this.employeeCacheModule && this.employeeCacheModule.getEmployeeNameByEmployId) {
                return this.employeeCacheModule.getEmployeeNameByEmployId(employId);
            }
            console.warn('Employee cache module not available');
            return '';
        }

        // These functions are no longer needed as employee names are updated via DataTable draw event
        // Kept for backward compatibility if needed
        async updateApplicantNames(prList) {
            // Functionality moved to updateEmployeeNamesInTable() in Table module
            console.warn('updateApplicantNames is deprecated, use updateEmployeeNamesInTable instead');
        }

        async updatePicNames(prList) {
            // Functionality moved to updateEmployeeNamesInTable() in Table module
            console.warn('updatePicNames is deprecated, use updateEmployeeNamesInTable instead');
        }

        getBadgeClass(prType) {
            if (this.tableModule && this.tableModule.getBadgeClass) {
                return this.tableModule.getBadgeClass(prType);
            }
            // Fallback
            const type = prType.toLowerCase();
            if (type.includes('general')) return 'bg-label-primary';
            if (type.includes('goods')) return 'bg-label-success';
            if (type.includes('services')) return 'bg-label-warning';
            if (type.includes('works')) return 'bg-label-info';
            if (type.includes('consultancy')) return 'bg-label-secondary';
            return 'bg-label-primary';
        }

        getStatusBadgeClass(statusID) {
            if (this.tableModule && this.tableModule.getStatusBadgeClass) {
                return this.tableModule.getStatusBadgeClass(statusID);
            }
            // Fallback
            if (!statusID) return 'bg-label-secondary';
            const statusClassMap = {
                1: 'bg-label-warning',
                2: 'bg-label-info',
                3: 'bg-label-primary',
                4: 'bg-label-success',
                5: 'bg-label-danger',
                6: 'bg-label-secondary'
            };
            return statusClassMap[statusID] || 'bg-label-secondary';
        }

        formatCurrency(amount) {
            if (this.tableModule && this.tableModule.formatCurrency) {
                return this.tableModule.formatCurrency(amount);
            }
            if (this.viewModule && this.viewModule.formatCurrency) {
                return this.viewModule.formatCurrency(amount);
            }
            // Fallback
            if (amount === null || amount === undefined || amount === '') return '-';
            const numAmount = parseFloat(amount);
            if (isNaN(numAmount)) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }).format(0);
            }
            if (numAmount === 0) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }).format(0);
            }
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(numAmount);
        }

        formatDate(date) {
            if (!date) return '-';
            const dateObj = typeof date === 'string' ? new Date(date) : date;
            if (isNaN(dateObj.getTime())) return '-';
            return new Intl.DateTimeFormat('id-ID', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            }).format(dateObj);
        }

        escapeHtml(text) {
            if (this.tableModule && this.tableModule.escapeHtml) {
                return this.tableModule.escapeHtml(text);
            }
            if (this.viewModule && this.viewModule.escapeHtml) {
                return this.viewModule.escapeHtml(text);
            }
            // Fallback
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Pagination info and controls are now handled by DataTables automatically
        // No need for custom updatePaginationInfo and updatePaginationControls

        goToPage(page) {
            // Pagination is now handled by DataTables automatically
            console.warn('goToPage is deprecated - DataTables handles pagination automatically');
        }

        showError(message) {
            if (this.viewModule && this.viewModule.showError) {
                return this.viewModule.showError(message);
            }
            // Fallback
            const tbody = document.getElementById('approvalTableBody');
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
            // Remove existing alerts
            const existingAlert = document.querySelector('.alert-success');
            if (existingAlert) {
                existingAlert.remove();
            }

            // Create new alert
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show';
            alertDiv.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="icon-base bx bx-check-circle me-2"></i>
                    <span>${this.escapeHtml(message)}</span>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;

            // Insert at the top of the container
            const container = document.querySelector('.approval-list-container');
            if (container) {
                container.insertBefore(alertDiv, container.firstChild);
            } else {
                // Fallback: insert at top of body
                document.body.insertBefore(alertDiv, document.body.firstChild);
            }

            // Auto-dismiss after 3 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 3000);
        }

        // View approval - Delegated to View module
        async viewApproval(prNumber) {
            if (this.viewModule && this.viewModule.viewApproval) {
                // Store currentPRNumber in manager for backward compatibility
                this.currentPRNumber = prNumber;
                if (this.viewModule.currentPRNumber !== undefined) {
                    this.viewModule.currentPRNumber = prNumber;
                }
                return this.viewModule.viewApproval(prNumber);
            }
            console.warn('ApprovalListView module not available');
        }

        hideListSection() {
            if (this.viewModule && this.viewModule.hideListSection) {
                return this.viewModule.hideListSection();
            }
            console.warn('ApprovalListView module not available');
        }

        showListSection() {
            if (this.viewModule && this.viewModule.showListSection) {
                return this.viewModule.showListSection();
            }
            console.warn('ApprovalListView module not available');
        }

        showViewApprovalSection() {
            if (this.viewModule && this.viewModule.showViewApprovalSection) {
                return this.viewModule.showViewApprovalSection();
            }
            console.warn('ApprovalListView module not available');
        }

        showViewApprovalLoading() {
            if (this.viewModule && this.viewModule.showViewApprovalLoading) {
                return this.viewModule.showViewApprovalLoading();
            }
            console.warn('ApprovalListView module not available');
        }

        // Helper function to check if Type ID and SubType ID require Additional Section - Delegated to View module
        requiresAdditionalSection(typeId, subTypeId) {
            if (this.viewModule && this.viewModule.requiresAdditionalSection) {
                return this.viewModule.requiresAdditionalSection(typeId, subTypeId);
            }
            // Fallback
            if (!typeId) return false;
            if (typeId === 5 || typeId === 7) return true;
            if (typeId === 6 && subTypeId === 2) return true;
            if (typeId === 8 && subTypeId === 4) return true;
            if (typeId === 2 && (subTypeId === 1 || subTypeId === 3)) return true;
            if (typeId === 4 && subTypeId === 3) return true;
            if (typeId === 3 && (subTypeId === 4 || subTypeId === 5)) return true;
            return false;
        }

        // Load PR details for view - Delegated to View module
        async loadPRDetailsForView(prNumber, pr = null) {
            if (this.viewModule && this.viewModule.loadPRDetailsForView) {
                return this.viewModule.loadPRDetailsForView(prNumber, pr);
            }
            console.warn('ApprovalListView module not available');
        }

        // Populate view approval - Delegated to View module
        async populateViewApproval(pr, isViewOnly = false) {
            if (this.viewModule && this.viewModule.populateViewApproval) {
                return this.viewModule.populateViewApproval(pr, isViewOnly);
            }
            console.warn('ApprovalListView module not available');
        }

        // Get additional information summary HTML - Delegated to View module
        async getAdditionalInformationSummaryHTML(pr) {
            if (this.viewModule && this.viewModule.getAdditionalInformationSummaryHTML) {
                return this.viewModule.getAdditionalInformationSummaryHTML(pr);
            }
            console.warn('ApprovalListView module not available');
            return '';
        }

        // Populate additional section - Delegated to View module
        async populateAdditionalSection(pr) {
            if (this.viewModule && this.viewModule.populateAdditionalSection) {
                return this.viewModule.populateAdditionalSection(pr);
            }
            console.warn('ApprovalListView module not available');
        }

        // Submit approval - Delegated to View module
        async submitApproval() {
            // Store currentPRNumber in viewModule for backward compatibility
            if (this.viewModule && this.viewModule.currentPRNumber !== undefined) {
                this.viewModule.currentPRNumber = this.currentPRNumber;
            }
            if (this.viewModule && this.viewModule.submitApproval) {
                return this.viewModule.submitApproval();
            }
            console.warn('ApprovalListView module not available');
        }

        // Show confirm modal - Delegated to View module
        showConfirmModal(title, message) {
            if (this.viewModule && this.viewModule.showConfirmModal) {
                return this.viewModule.showConfirmModal(title, message);
            }
            // Fallback
            return new Promise((resolve) => {
                if (typeof Swal === 'undefined') {
                    const confirmed = window.confirm(`${title}\n\n${message}`);
                    resolve(confirmed);
                    return;
                }
                Swal.fire({
                    title: title,
                    text: message,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#696cff',
                    cancelButtonColor: '#a8aaae',
                    confirmButtonText: '<i class="icon-base bx bx-check me-2"></i>Confirm',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    allowOutsideClick: false,
                    allowEscapeKey: true,
                    animation: true,
                    customClass: {
                        confirmButton: 'btn btn-primary',
                        cancelButton: 'btn btn-label-secondary'
                    }
                }).then((result) => {
                    resolve(result.isConfirmed);
                });
            });
        }

        // Show alert modal - Delegated to View module
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

        // Back to list - Delegated to View module
        backToList() {
            this.currentPRNumber = null;
            if (this.viewModule && this.viewModule.backToList) {
                return this.viewModule.backToList();
            }
            console.warn('ApprovalListView module not available');
        }

        // Download document - Delegated to View module
        async downloadDocument(documentId, fileName) {
            if (this.viewModule && this.viewModule.downloadDocument) {
                return this.viewModule.downloadDocument.call(this, documentId, fileName);
            }
            console.warn('ApprovalListView module not available');
        }

    }

    // Flag to prevent multiple initializations
    let approvalListInitialized = false;

    // Initialize manager when all dependencies are ready
    function initializeApprovalList() {
        // Prevent multiple initializations
        if (approvalListInitialized) {
            return;
        }

        // Wait for jQuery, DataTables, and DataTableHelper to be available
        if (typeof $ === 'undefined' || typeof $.fn.DataTable === 'undefined') {
            setTimeout(initializeApprovalList, 100);
            return;
        }

        if (typeof CreateTable === 'undefined' || typeof ColumnBuilder === 'undefined') {
            setTimeout(initializeApprovalList, 100);
            return;
        }

        // All dependencies are ready, initialize
        window.approvalListManager = new ApprovalListManager();
        approvalListInitialized = true;
    }

    // Start initialization when DOM is ready (only once)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeApprovalList);
    } else {
        // DOM is already loaded
        initializeApprovalList();
    }
</script>



