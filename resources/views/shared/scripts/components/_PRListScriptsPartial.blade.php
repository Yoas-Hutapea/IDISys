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
                                    const statusId = row.mstApprovalStatusID ?? row.MstApprovalStatusID ?? null;
                                    const requestor = row.requestor ?? row.Requestor ?? '';
                                    const currentUserEmployeeID = (window.PRListConfig && window.PRListConfig.currentUserEmployeeID) || '';

                                    if (statusId === 5 || statusId === 6 || statusId === null) {
                                        if (currentUserEmployeeID && requestor) {
                                            const currentUserNormalized = currentUserEmployeeID.trim().toLowerCase();
                                            const requestorNormalized = requestor.trim().toLowerCase();
                                            return currentUserNormalized === requestorNormalized;
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
            // Existing implementation lives in PRListView.js in IDEANET.
            console.warn('History modal not yet implemented in IDISys.');
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
