<script src="/js/procurement/ProcurementSharedCache.js" asp-append-version="true"></script>
<script src="/js/procurement/PRListAPI.js" asp-append-version="true"></script>
<script src="/js/procurement/PRListEmployeeCache.js" asp-append-version="true"></script>
<script src="/js/procurement/PRListFilter.js" asp-append-version="true"></script>
<script src="/js/procurement/PRListTable.js" asp-append-version="true"></script>
<script src="/js/procurement/PRListView.js" asp-append-version="true"></script>

<script>
    'use strict';

    class PRListManager {
        constructor() {
            this.currentPage = 1;
            this.rowsPerPage = 10;
            this.totalRecords = 0;
            this.debounceTimer = null;
            this.isLoading = false;
            this.cachedElements = this.cacheElements();
            this.performanceMetrics = {
                startTime: performance.now(),
                searchCount: 0,
                domQueries: 0
            };

            if (typeof PRListAPI !== 'undefined') {
                this.apiModule = new PRListAPI();
            }
            if (typeof PRListEmployeeCache !== 'undefined') {
                this.employeeCacheModule = new PRListEmployeeCache();
            }
            if (typeof PRListFilter !== 'undefined') {
                this.filterModule = new PRListFilter(this);
            }
            if (typeof PRListTable !== 'undefined') {
                this.tableModule = new PRListTable(this);
            }
            if (typeof PRListView !== 'undefined') {
                this.viewModule = new PRListView(this);
            }

            this.init();
        }

        cacheElements() {
            return {
                filterForm: document.getElementById('filterForm'),
                filterContent: document.getElementById('filter-content'),
                filterChevron: document.getElementById('filter-chevron'),
                prTable: document.getElementById('prTable')
            };
        }

        init() {
            this.bindEvents();

            if (this.filterModule) {
                this.filterModule.init();
            }

            if (this.tableModule) {
                this.tableModule.initializeDataTable();
            } else {
                this.initializeDataTable();
            }
        }

        bindEvents() {
            this.cachedElements.filterForm?.addEventListener('submit', (e) => {
                e.preventDefault();
                this.debouncedSearch();
            });

            const searchInputs = this.cachedElements.filterForm?.querySelectorAll('input, select');
            searchInputs?.forEach(input => {
                input.addEventListener('input', () => {
                    if (input.id === 'startDate' || input.id === 'endDate') {
                        this.updateDateRangeInfo();
                    }
                    this.debouncedSearch();
                });
            });

            const startDateInput = document.getElementById('startDate');
            const endDateInput = document.getElementById('endDate');
            if (startDateInput) {
                startDateInput.addEventListener('change', () => this.updateDateRangeInfo());
            }
            if (endDateInput) {
                endDateInput.addEventListener('change', () => this.updateDateRangeInfo());
            }

            if (typeof searchPR === 'undefined') {
                window.searchPR = () => this.searchPR();
            }

            if (typeof resetFilter === 'undefined') {
                window.resetFilter = () => this.resetFilter();
            }

            if (typeof toggleFilter === 'undefined') {
                window.toggleFilter = () => this.toggleFilter();
            }

            if (typeof editPR === 'undefined') {
                window.editPR = (prNumber) => this.editPR(prNumber);
            }

            if (typeof viewPR === 'undefined') {
                window.viewPR = (prNumber) => this.viewPR(prNumber);
            }

            if (typeof viewHistory === 'undefined') {
                window.viewHistory = (prNumber) => this.viewHistory(prNumber);
            }

            if (typeof goToPage === 'undefined') {
                window.goToPage = (page) => this.goToPage(page);
            }

            if (typeof goToNextPage === 'undefined') {
                window.goToNextPage = () => this.goToNextPage();
            }

            if (typeof createNewPR === 'undefined') {
                window.createNewPR = () => this.createNewPR();
            }

            if (typeof backToList === 'undefined') {
                window.backToList = () => this.backToList();
            }

            if (typeof downloadDocument === 'undefined') {
                const self = this;
                window.downloadDocument = function(documentId) {
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

        initializeDataTable() {
            if (this.tableModule) {
                return this.tableModule.initializeDataTable();
            }

            if (typeof CreateTable === 'undefined' || typeof ColumnBuilder === 'undefined') {
                console.error('CreateTable or ColumnBuilder helper not found. Make sure DataTableHelper.js is loaded.');
                return;
            }

            const self = this;
            const buildFilter = () => {
                if (this.filterModule && this.filterModule.buildFilter) {
                    return this.filterModule.buildFilter();
                }
                return {};
            };

            this.dataTable = CreateTable({
                apiType: 'Procurement',
                endpoint: '/Procurement/PurchaseRequest/PurchaseRequests/Grid',
                tableId: '#prTable',
                columns: ColumnBuilder([
                    {
                        type: 'actions',
                        buttons: [
                            {
                                label: 'View',
                                icon: '<i class="bx bx-show"></i>',
                                className: 'btn-primary',
                                title: 'View',
                                showIf: () => true
                            },
                            {
                                label: 'Edit',
                                icon: '<i class="bx bx-edit"></i>',
                                className: 'btn-warning',
                                title: 'Edit',
                                showIf: (row) => {
                                    const statusIdRaw = row.mstApprovalStatusID ?? row.MstApprovalStatusID ?? null;
                                    const statusId = Number.isFinite(Number(statusIdRaw))
                                        ? parseInt(statusIdRaw, 10)
                                        : null;
                                    const requestor = row.requestor ?? row.Requestor ?? '';

                                    // Get current user employee ID from config
                                const currentUserEmployeeID = (window.PRListConfig && window.PRListConfig.currentUserEmployeeID) || '';
                                const currentUserIdentifiers = (window.PRListConfig && window.PRListConfig.currentUserIdentifiers) || [];

                                    // Show Edit only if:
                                // 1. Status is 5 or 6 (rejected/draft)
                                    // 2. AND current user is the Requestor
                                    if (statusId === 5 || statusId === 6) {
                                    if (requestor) {
                                        const requestorNormalized = requestor.trim().toLowerCase();
                                        if (currentUserEmployeeID) {
                                            const currentUserNormalized = currentUserEmployeeID.trim().toLowerCase();
                                            if (currentUserNormalized === requestorNormalized) {
                                                return true;
                                            }
                                        }
                                        if (Array.isArray(currentUserIdentifiers)) {
                                            return currentUserIdentifiers.some(id => {
                                                if (!id) return false;
                                                return id.toString().trim().toLowerCase() === requestorNormalized;
                                            });
                                        }
                                    }
                                    return false;
                                    }

                                    return false;
                                }
                            },
                            {
                                label: 'History',
                                icon: '<i class="bx bx-history"></i>',
                                className: 'btn-info',
                                title: 'History',
                                showIf: () => true
                            }
                        ]
                    },
                    { data: 'purchReqNumber', title: 'Purchase Request Number' },
                    { data: 'purchReqName', title: 'Purchase Request Name' },
                    { data: 'purchReqType', title: 'Purchase Request Type' },
                    { data: 'purchReqSubType', title: 'Purchase Request Sub Type' },
                    { data: 'approvalStatus', title: 'Status' },
                    {
                        data: 'pic',
                        title: 'PIC',
                        render: (data, type, row) => {
                            const picId = data || row.pic || row.PIC || '';
                            if (!picId || picId === '-') return '-';
                            const escapedId = self.escapeHtml(picId);
                            return `<span class="employee-name" data-employee-id="${escapedId}">${escapedId}</span>`;
                        }
                    },
                    { data: 'totalAmount', title: 'Amount PR', type: 'currency' },
                    { data: 'company', title: 'Company' },
                    {
                        data: 'requestor',
                        title: 'Requestor',
                        render: (data, type, row) => {
                            const requestorId = data || row.requestor || row.Requestor || '';
                            if (!requestorId) return '-';
                            const escapedId = self.escapeHtml(requestorId);
                            return `<span class="employee-name" data-employee-id="${escapedId}">${escapedId}</span>`;
                        }
                    },
                    {
                        data: 'applicant',
                        title: 'Applicant',
                        render: (data, type, row) => {
                            const applicantId = data || row.applicant || row.Applicant || '';
                            if (!applicantId) return '-';
                            const escapedId = self.escapeHtml(applicantId);
                            return `<span class="employee-name" data-employee-id="${escapedId}">${escapedId}</span>`;
                        }
                    },
                    { data: 'createdDate', title: 'Request Date', type: 'datetime' }
                ]),
                filter: buildFilter.bind(this),
                order: [[11, 'desc']],
                serverSide: true
            });

            if (this.tableModule) {
                this.dataTable.on('draw', () => {
                    if (this.tableModule.updateEmployeeNamesInTable) {
                        this.tableModule.updateEmployeeNamesInTable();
                    }
                });

                setTimeout(() => {
                    if (this.tableModule.updateEmployeeNamesInTable) {
                        this.tableModule.updateEmployeeNamesInTable();
                    }
                }, 500);
            }
        }

        toggleFilter() {
            if (this.filterModule && this.filterModule.toggleFilter) {
                return this.filterModule.toggleFilter();
            }
            console.warn('PRListFilter module not available');
        }

        debouncedSearch() {
            if (this.filterModule && this.filterModule.debouncedSearch) {
                return this.filterModule.debouncedSearch();
            }
            console.warn('PRListFilter module not available');
        }

        updateDateRangeInfo() {
            if (this.filterModule && this.filterModule.updateDateRangeInfo) {
                return this.filterModule.updateDateRangeInfo();
            }
            console.warn('PRListFilter module not available');
        }

        async searchPR() {
            this.performanceMetrics.searchCount++;
            if (this.tableModule && this.tableModule.searchPR) {
                return this.tableModule.searchPR();
            }
            if (this.dataTable) {
                this.dataTable.ajax.reload();
            }
        }

        async resetFilter() {
            if (this.filterModule && this.filterModule.resetFilter) {
                return this.filterModule.resetFilter();
            }
            console.warn('PRListFilter module not available');
        }

        async refreshTable() {
            if (this.dataTable) {
                this.dataTable.ajax.reload();
            } else {
                await this.loadData();
            }
        }

        showError(message) {
            const tbody = document.getElementById('prTableBody');
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

        editPR(prNumber) {
            localStorage.setItem('procurementDraft', JSON.stringify({
                purchReqNumber: prNumber,
                loadExisting: true
            }));
            window.location.href = `/Procurement/PurchaseRequest/Create?prNumber=${encodeURIComponent(prNumber)}`;
        }

        async viewPR(prNumber) {
            if (this.viewModule && this.viewModule.viewPR) {
                return this.viewModule.viewPR(prNumber);
            }
            console.warn('PRListView module not available');
        }

        backToList() {
            if (this.viewModule && this.viewModule.backToList) {
                return this.viewModule.backToList();
            }
            console.warn('PRListView module not available');
        }

        async downloadDocument(documentId, fileName) {
            if (this.viewModule && this.viewModule.downloadDocument) {
                return this.viewModule.downloadDocument.call(this, documentId, fileName);
            }
            console.warn('PRListView module not available');
        }

        viewHistory(prNumber) {
            this.showHistoryModal(prNumber);
        }

        async showHistoryModal(prNumber) {
            // Pagination state
            let historyData = [];
            let currentPage = 1;
            const rowsPerPage = 10;

            // Create modal HTML with loading state
            const modalHtml = `
                <div class="modal fade" id="historyModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">History - ${this.escapeHtml(prNumber)}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div id="historyLoading" class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <div class="mt-3">Loading approval history...</div>
                                </div>
                                <div id="historyContent" style="display: none;">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>PIC</th>
                                                    <th>Position PIC</th>
                                                    <th>Activity</th>
                                                    <th>Decision</th>
                                                    <th>Remark</th>
                                                    <th>Activity Date</th>
                                                </tr>
                                            </thead>
                                            <tbody id="historyTableBody">
                                                <!-- History data will be populated here -->
                                            </tbody>
                                        </table>
                                    </div>
                                    <div id="historyPagination" class="d-flex justify-content-between align-items-center mt-3">
                                        <div id="historyPaginationInfo" class="text-muted"></div>
                                        <nav>
                                            <ul class="pagination pagination-sm mb-0" id="historyPaginationControls">
                                                <!-- Pagination controls will be populated here -->
                                            </ul>
                                        </nav>
                                    </div>
                                </div>
                                <div id="historyError" style="display: none;" class="alert alert-danger">
                                    <p id="historyErrorMessage"></p>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Remove existing modal if any
            const existingModal = document.getElementById('historyModal');
            if (existingModal) {
                existingModal.remove();
            }

            // Add modal to DOM
            document.body.insertAdjacentHTML('beforeend', modalHtml);

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('historyModal'));
            modal.show();

            // Function to render table rows for current page
            const renderTable = () => {
                const tbody = document.getElementById('historyTableBody');
                if (!tbody) return;

                const totalPages = Math.ceil(historyData.length / rowsPerPage);
                const startIndex = (currentPage - 1) * rowsPerPage;
                const endIndex = Math.min(startIndex + rowsPerPage, historyData.length);
                const pageData = historyData.slice(startIndex, endIndex);

                if (pageData.length > 0) {
                    const self = this;
                    tbody.innerHTML = pageData.map(item => {
                        // Format Activity Date
                        const rawCreatedDate = item.createdDate || item.CreatedDate || '';
                        const activityDate = rawCreatedDate ? new Date(rawCreatedDate).toLocaleString('en-US', {
                            year: 'numeric',
                            month: '2-digit',
                            day: '2-digit',
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit'
                        }) : '-';

                        // Get badge class based on decision
                        let badgeClass = 'bg-label-secondary';
                        const rawDecision = item.decision || item.Decision || '';
                        if (rawDecision) {
                            const decision = rawDecision.toLowerCase();
                            if (decision === 'approved' || decision === 'approve' || decision === 'reviewed' || decision === 'confirmed' || decision === 'finished' || decision === 'submitted') {
                                badgeClass = 'bg-label-success';
                            } else if (decision === 'reject' || decision === 'rejected') {
                                badgeClass = 'bg-label-danger';
                            } else if (decision === 'pending') {
                                badgeClass = 'bg-label-warning';
                            }
                        }

                        // Get data for each column
                        const pic = item.employeeName || item.EmployeeName || item.mstEmployeeID || item.MstEmployeeID || '-'; // PIC
                        const positionPIC = item.positionName || item.PositionName || item.mstEmployeePositionID || item.MstEmployeePositionID || '-'; // Position PIC
                        const activity = item.activity || item.Activity || '-'; // Activity
                        const decision = item.decision || item.Decision || '-'; // Decision
                        const remark = item.remark || item.Remark || '-'; // Remark

                        return `
                            <tr>
                                <td>${self.escapeHtml(pic)}</td>
                                <td>${self.escapeHtml(positionPIC)}</td>
                                <td>${self.escapeHtml(activity)}</td>
                                <td><span class="badge ${badgeClass}">${self.escapeHtml(decision)}</span></td>
                                <td>${self.escapeHtml(remark)}</td>
                                <td>${self.escapeHtml(activityDate)}</td>
                            </tr>
                        `;
                    }).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center">No data available</td></tr>';
                }

                // Update pagination info
                const paginationInfo = document.getElementById('historyPaginationInfo');
                if (paginationInfo) {
                    if (historyData.length > 0) {
                        paginationInfo.textContent = `Showing ${startIndex + 1} to ${endIndex} of ${historyData.length} entries`;
                    } else {
                        paginationInfo.textContent = '';
                    }
                }

                // Update pagination controls
                const paginationControls = document.getElementById('historyPaginationControls');
                if (paginationControls && totalPages > 1) {
                    let paginationHtml = '';

                    // Previous button
                    paginationHtml += `
                        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                            <a class="page-link" href="#" data-page="${currentPage - 1}" ${currentPage === 1 ? 'tabindex="-1" aria-disabled="true"' : ''}>Previous</a>
                        </li>
                    `;

                    // Page numbers
                    const maxVisiblePages = 5;
                    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
                    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

                    if (endPage - startPage < maxVisiblePages - 1) {
                        startPage = Math.max(1, endPage - maxVisiblePages + 1);
                    }

                    if (startPage > 1) {
                        paginationHtml += `
                            <li class="page-item">
                                <a class="page-link" href="#" data-page="1">1</a>
                            </li>
                        `;
                        if (startPage > 2) {
                            paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                        }
                    }

                    for (let i = startPage; i <= endPage; i++) {
                        paginationHtml += `
                            <li class="page-item ${i === currentPage ? 'active' : ''}">
                                <a class="page-link" href="#" data-page="${i}">${i}</a>
                            </li>
                        `;
                    }

                    if (endPage < totalPages) {
                        if (endPage < totalPages - 1) {
                            paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                        }
                        paginationHtml += `
                            <li class="page-item">
                                <a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a>
                            </li>
                        `;
                    }

                    // Next button
                    paginationHtml += `
                        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                            <a class="page-link" href="#" data-page="${currentPage + 1}" ${currentPage === totalPages ? 'tabindex="-1" aria-disabled="true"' : ''}>Next</a>
                        </li>
                    `;

                    paginationControls.innerHTML = paginationHtml;

                    // Attach event listeners
                    paginationControls.querySelectorAll('a.page-link').forEach(link => {
                        link.addEventListener('click', (e) => {
                            e.preventDefault();
                            const page = parseInt(link.getAttribute('data-page'));
                            if (page && page !== currentPage && page >= 1 && page <= totalPages) {
                                currentPage = page;
                                renderTable();
                            }
                        });
                    });
                } else if (paginationControls) {
                    paginationControls.innerHTML = '';
                }
            };

            try {
                // Fetch approval history from API
                const response = await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequestApprovals/History/${encodeURIComponent(prNumber)}`, 'GET');
                historyData = response.data || response;

                // Hide loading, show content
                const loadingEl = document.getElementById('historyLoading');
                const contentEl = document.getElementById('historyContent');
                const errorEl = document.getElementById('historyError');

                if (loadingEl) loadingEl.style.display = 'none';
                if (errorEl) errorEl.style.display = 'none';

                if (historyData && Array.isArray(historyData) && historyData.length > 0) {
                    // Resolve employee names for PIC (mstEmployeeID)
                    try {
                        const employeeIds = historyData
                            .map(item => item.mstEmployeeID || item.MstEmployeeID || '')
                            .filter(id => id && id.toString().trim() !== '');

                        if (employeeIds.length > 0 && window.prListManager && window.prListManager.employeeCacheModule && window.prListManager.employeeCacheModule.batchGetEmployeeNames) {
                            const nameMap = await window.prListManager.employeeCacheModule.batchGetEmployeeNames(employeeIds);
                            historyData = historyData.map(item => {
                                const rawId = item.mstEmployeeID || item.MstEmployeeID || '';
                                const key = rawId ? rawId.toString().trim().toLowerCase() : '';
                                const resolvedName = key ? nameMap.get(key) : '';
                                if (resolvedName) {
                                    return {
                                        ...item,
                                        employeeName: resolvedName,
                                        EmployeeName: resolvedName
                                    };
                                }
                                return item;
                            });
                        }
                    } catch (nameError) {
                        console.warn('Failed to resolve PIC employee names:', nameError);
                    }

                    currentPage = 1;
                    renderTable();
                    if (contentEl) contentEl.style.display = 'block';
                } else {
                    // No history data
                    const tbody = document.getElementById('historyTableBody');
                    if (tbody) {
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No approval history found</td></tr>';
                    }
                    const paginationInfo = document.getElementById('historyPaginationInfo');
                    if (paginationInfo) paginationInfo.textContent = '';
                    const paginationControls = document.getElementById('historyPaginationControls');
                    if (paginationControls) paginationControls.innerHTML = '';
                    if (contentEl) contentEl.style.display = 'block';
                }
            } catch (error) {
                console.error('Error loading approval history:', error);

                // Hide loading, show error
                const loadingEl = document.getElementById('historyLoading');
                const contentEl = document.getElementById('historyContent');
                const errorEl = document.getElementById('historyError');
                const errorMsgEl = document.getElementById('historyErrorMessage');

                if (loadingEl) loadingEl.style.display = 'none';
                if (contentEl) contentEl.style.display = 'none';
                if (errorEl) errorEl.style.display = 'block';
                if (errorMsgEl) {
                    errorMsgEl.textContent = `Failed to load approval history: ${error.message || 'Unknown error'}`;
                }
            }
        }

        async goToPage(page) {
            const maxPage = Math.ceil(this.totalRecords / this.rowsPerPage);
            if (page < 1 || page > maxPage) return;
            this.currentPage = page;
            await this.refreshTable();
        }

        async goToNextPage() {
            const maxPage = Math.ceil(this.totalRecords / this.rowsPerPage);
            if (this.currentPage < maxPage) {
                await this.goToPage(this.currentPage + 1);
            }
        }

        createNewPR() {
            window.location.href = '/Procurement/PurchaseRequest/Create';
        }

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }

    let prListInitialized = false;

    function initializePRList() {
        if (prListInitialized) {
            return;
        }

        if (typeof $ === 'undefined' || typeof $.fn.DataTable === 'undefined') {
            setTimeout(initializePRList, 100);
            return;
        }

        if (typeof CreateTable === 'undefined' || typeof ColumnBuilder === 'undefined') {
            setTimeout(initializePRList, 100);
            return;
        }

        window.prListManager = new PRListManager();
        prListInitialized = true;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializePRList);
    } else {
        initializePRList();
    }
</script>
