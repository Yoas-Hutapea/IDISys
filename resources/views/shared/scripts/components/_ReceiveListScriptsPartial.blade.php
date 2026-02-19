<script src="{{ asset('js/procurement/ProcurementSharedCache.js') }}"></script>

<script src="{{ asset('js/procurement/ReceiveListAPI.js') }}"></script>
<script src="{{ asset('js/procurement/ReceiveListFilter.js') }}"></script>
<script src="{{ asset('js/procurement/ReceiveListTable.js') }}"></script>
<script src="{{ asset('js/procurement/ReceiveListView.js') }}"></script>

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

    // Performance-optimized ReceiveList functionality class
    class ReceiveListManager {
        constructor() {
            this.currentPage = 1;
            this.rowsPerPage = 10;
            this.totalRecords = 0;
            this.isLoading = false;
            this.cachedElements = this.cacheElements();
            this.currentPRNumber = null;
            this.selectedPRNumbers = new Set(); // Track selected PRs for bulk receive
            this.isTogglingSelectAll = false; // Flag to prevent race condition in updateSelectAllCheckbox

            // Initialize modules
            if (typeof ReceiveListAPI !== 'undefined') {
                this.apiModule = new ReceiveListAPI();
            }
            if (typeof ReceiveListFilter !== 'undefined') {
                this.filterModule = new ReceiveListFilter(this);
            }
            if (typeof ReceiveListTable !== 'undefined') {
                this.tableModule = new ReceiveListTable(this);
            }
            if (typeof ReceiveListView !== 'undefined') {
                this.viewModule = new ReceiveListView(this);
            }
            // Use shared employee cache if available
            if (window.procurementSharedCache && window.procurementSharedCache.getEmployeeNameByEmployId) {
                this.employeeCacheModule = window.procurementSharedCache;
            } else if (window.prListManager && window.prListManager.employeeCacheModule) {
                this.employeeCacheModule = window.prListManager.employeeCacheModule;
            } else if (window.approvalListManager && window.approvalListManager.employeeCacheModule) {
                this.employeeCacheModule = window.approvalListManager.employeeCacheModule;
            }

            this.init();
        }

        cacheElements() {
            return {
                filterForm: document.getElementById('filterForm'),
                filterContent: document.getElementById('filter-content'),
                filterChevron: document.getElementById('filter-chevron'),
                receiveTable: document.getElementById('receiveTable'),
                selectAllCheckbox: document.getElementById('selectAllCheckbox'),
                processBulkReceiveBtn: document.getElementById('processBulkReceiveBtn')
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
            console.warn('ReceiveListTable module not available');
        }

        toggleRowSelection(prNumber, isSelected) {
            if (isSelected) {
                this.selectedPRNumbers.add(prNumber);
            } else {
                this.selectedPRNumbers.delete(prNumber);
            }
            this.updateProcessButton();
            this.updateSelectAllCheckbox();
        }

        updateSelectAllCheckbox() {
            // Skip update if we're currently toggling select all to prevent race condition
            if (this.isTogglingSelectAll) return;

            if (!this.cachedElements.selectAllCheckbox) return;

            // Use selector that works with DataTable (DataTable creates its own tbody)
            const checkboxes = document.querySelectorAll('#receiveTable tbody .row-checkbox');
            if (checkboxes.length === 0) {
                this.cachedElements.selectAllCheckbox.indeterminate = false;
                this.cachedElements.selectAllCheckbox.checked = false;
                return;
            }

            // Count checked checkboxes in visible rows
            const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;

            // Also check if all data in selectedPRNumbers matches visible rows (for server-side pagination)
            if (this.dataTable) {
                const visibleData = this.dataTable.rows({ page: 'current' }).data().toArray();
                const visiblePRNumbers = new Set(visibleData.map(row => row.purchReqNumber || row.PurchReqNumber || '').filter(p => p));
                const visibleSelectedCount = Array.from(visiblePRNumbers).filter(pr => this.selectedPRNumbers.has(pr)).length;

                if (visibleSelectedCount === 0) {
                    this.cachedElements.selectAllCheckbox.indeterminate = false;
                    this.cachedElements.selectAllCheckbox.checked = false;
                } else if (visibleSelectedCount === visiblePRNumbers.size && visiblePRNumbers.size > 0) {
                    this.cachedElements.selectAllCheckbox.indeterminate = false;
                    this.cachedElements.selectAllCheckbox.checked = true;
                } else {
                    this.cachedElements.selectAllCheckbox.indeterminate = true;
                    this.cachedElements.selectAllCheckbox.checked = false;
                }
            } else {
                // Fallback for manual rendering
                if (checkedCount === 0) {
                    this.cachedElements.selectAllCheckbox.indeterminate = false;
                    this.cachedElements.selectAllCheckbox.checked = false;
                } else if (checkedCount === checkboxes.length) {
                    this.cachedElements.selectAllCheckbox.indeterminate = false;
                    this.cachedElements.selectAllCheckbox.checked = true;
                } else {
                    this.cachedElements.selectAllCheckbox.indeterminate = true;
                    this.cachedElements.selectAllCheckbox.checked = false;
                }
            }
        }

        updateProcessButton() {
            if (this.cachedElements.processBulkReceiveBtn) {
                this.cachedElements.processBulkReceiveBtn.disabled = this.selectedPRNumbers.size === 0;
            }
        }

        bindEvents() {
            // Filter events are handled by filterModule.init()

            // Rows per page is now handled by DataTables automatically

            if (typeof searchReceive === 'undefined') {
                window.searchReceive = () => this.searchReceive();
            }

            if (typeof resetFilter === 'undefined') {
                window.resetFilter = () => this.resetFilter();
            }

            if (typeof toggleFilter === 'undefined') {
                window.toggleFilter = () => this.toggleFilter();
            }

            if (typeof viewReceive === 'undefined') {
                window.viewReceive = (prNumber) => this.viewReceive(prNumber);
            }

            if (typeof backToList === 'undefined') {
                window.backToList = () => this.backToList();
            }

            if (typeof submitReceive === 'undefined') {
                window.submitReceive = () => this.submitReceive();
            }

            if (typeof processBulkReceive === 'undefined') {
                window.processBulkReceive = () => this.processBulkReceive();
            }

            if (typeof toggleSelectAll === 'undefined') {
                window.toggleSelectAll = (checkbox) => this.toggleSelectAll(checkbox);
            }

            // Also bind event listener directly to selectAllCheckbox to ensure it works
            if (this.cachedElements.selectAllCheckbox) {
                this.cachedElements.selectAllCheckbox.addEventListener('change', (e) => {
                    this.toggleSelectAll(e.target);
                });
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

        toggleSelectAll(checkbox) {
            // Set flag to prevent updateSelectAllCheckbox from interfering
            this.isTogglingSelectAll = true;

            const isChecked = checkbox.checked;

            // If DataTable is initialized, use it to get visible rows on current page
            if (this.dataTable) {
                // Get only visible rows on current page (for server-side pagination, we only select current page)
                const visibleData = this.dataTable.rows({ page: 'current' }).data().toArray();

                // Update selectedPRNumbers set for visible rows
                visibleData.forEach(row => {
                    const prNumber = row.purchReqNumber || row.PurchReqNumber || '';
                    if (prNumber) {
                        if (isChecked) {
                            this.selectedPRNumbers.add(prNumber);
                        } else {
                            this.selectedPRNumbers.delete(prNumber);
                        }
                    }
                });

                // Update all visible checkboxes in current page
                const visibleCheckboxes = document.querySelectorAll('#receiveTable tbody .row-checkbox');
                visibleCheckboxes.forEach(cb => {
                    cb.checked = isChecked;
                });
            } else {
                // Fallback: use selector for manual rendering
                const checkboxes = document.querySelectorAll('#receiveTable tbody .row-checkbox, #receiveTableBody .row-checkbox');
                checkboxes.forEach(cb => {
                    cb.checked = isChecked;
                    const prNumber = cb.getAttribute('data-pr-number');
                    if (prNumber) {
                        if (isChecked) {
                            this.selectedPRNumbers.add(prNumber);
                        } else {
                            this.selectedPRNumbers.delete(prNumber);
                        }
                    }
                });
            }

            this.updateProcessButton();

            // Reset flag and update select all checkbox after a short delay to ensure DOM is updated
            setTimeout(() => {
                this.isTogglingSelectAll = false;
                this.updateSelectAllCheckbox();
            }, 50);
        }

        toggleFilter() {
            if (this.filterModule && this.filterModule.toggleFilter) {
                return this.filterModule.toggleFilter();
            }
            console.warn('ReceiveListFilter module not available');
        }

        debouncedSearch() {
            if (this.filterModule && this.filterModule.debouncedSearch) {
                return this.filterModule.debouncedSearch();
            }
            console.warn('ReceiveListFilter module not available');
        }

        async searchReceive() {
            if (this.tableModule && this.tableModule.searchReceive) {
                return this.tableModule.searchReceive();
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
            console.warn('ReceiveListFilter module not available');
        }

        updateDateRangeInfo() {
            if (this.filterModule && this.filterModule.updateDateRangeInfo) {
                return this.filterModule.updateDateRangeInfo();
            }
            console.warn('ReceiveListFilter module not available');
        }

        async refreshTable() {
            if (this.dataTable) {
                this.dataTable.ajax.reload();
            } else {
                await this.loadData();
            }
        }

        async loadData() {
            if (this.isLoading) return;

            try {
                this.isLoading = true;
                this.showLoading();

                const formData = new FormData(this.cachedElements.filterForm);
                const startDate = formData.get('startDate');
                const endDate = formData.get('endDate');
                const requestNumber = formData.get('requestNumber');
                const prNumber = formData.get('prNumber');
                const prType = formData.get('prType');
                const prSubType = formData.get('prSubType');
                const prName = formData.get('prName');
                const statusPR = formData.get('statusPR');
                const source = formData.get('source');

                const queryParams = new URLSearchParams();
                if (startDate) queryParams.append('fromDate', startDate);
                if (endDate) queryParams.append('toDate', endDate);
                if (requestNumber) queryParams.append('requestNumber', requestNumber.trim());
                if (prNumber) queryParams.append('purchReqNum', prNumber.trim());
                if (prType) queryParams.append('purchReqType', prType.trim());
                if (prSubType) queryParams.append('purchReqSubType', prSubType.trim());
                if (prName) queryParams.append('purchReqName', prName.trim());
                if (statusPR) {
                    const statusId = parseInt(statusPR);
                    if (!isNaN(statusId)) {
                        queryParams.append('statusPR', statusId.toString());
                    }
                }
                if (source) queryParams.append('source', source.trim().toUpperCase());

                const endpoint = `/Procurement/PurchaseRequest/PurchaseRequestReceives?${queryParams.toString()}`;
                const response = await apiCall('Procurement', endpoint, 'GET');
                const data = response.data || response;
                const receiveList = Array.isArray(data) ? data : [];

                this.totalRecords = receiveList.length;
                const startIndex = (this.currentPage - 1) * this.rowsPerPage;
                const endIndex = Math.min(startIndex + this.rowsPerPage, receiveList.length);
                const paginatedData = receiveList.slice(startIndex, endIndex);

                await this.renderTable(paginatedData);
                // Pagination info and controls are now handled by DataTables automatically

            } catch (error) {
                console.error('Error loading receive list:', error);
                this.showError('Failed to load receive list. Please try again.');
            } finally {
                this.isLoading = false;
            }
        }

        async renderTable(data) {
            const tbody = document.getElementById('receiveTableBody');
            if (!tbody) return;

            if (data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="13" class="text-center py-4">
                            <div class="text-muted">No receive requests found</div>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = data.map(pr => {
                const prNumber = pr.purchReqNumber || pr.PurchReqNumber || '';
                const prName = pr.purchReqName || pr.PurchReqName || '';
                const prType = pr.purchReqType || pr.PurchReqType || 'General';
                const prSubType = pr.purchReqSubType || pr.PurchReqSubType || '';
                const approvalStatusID = pr.mstApprovalStatusID || pr.MstApprovalStatusID || null;
                let approvalStatus = pr.approvalStatus || pr.ApprovalStatus || 'Draft';

                // If mstApprovalStatusID = 4, show "Waiting Receive Purchase Request"
                if (approvalStatusID === 4) {
                    approvalStatus = 'Waiting Receive Purchase Request';
                }
                // If status is "Waiting Receive Purchase Request" (ID = 4), PIC should be "-"
                const pic = (approvalStatusID === 4) ? '-' : (pr.pic || pr.PIC || '-');
                const totalAmount = pr.totalAmount || pr.TotalAmount || null;
                const company = pr.company || pr.Company || '';
                const requestor = pr.requestor || pr.Requestor || '';
                const applicant = pr.applicant || pr.Applicant || '';
                const createdDate = pr.createdDate || pr.CreatedDate || null;

                const amountDisplay = totalAmount ? this.formatCurrency(totalAmount) : '-';
                const dateDisplay = createdDate ? this.formatDate(createdDate) : '-';
                const badgeClass = this.getBadgeClass(prType);
                const isSelected = this.selectedPRNumbers.has(prNumber);

                return `
                    <tr data-applicant-id="${this.escapeHtml(applicant)}" data-pic-id="${this.escapeHtml(pic)}" data-status-id="${approvalStatusID}">
                        <td>
                            <input type="checkbox" class="row-checkbox" data-pr-number="${this.escapeHtml(prNumber)}" ${isSelected ? 'checked' : ''} onchange="receiveListManager.toggleRowSelection('${this.escapeHtml(prNumber)}', this.checked)">
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button type="button" class="btn btn-sm btn-outline-primary action-btn" title="View & Receive" onclick="viewReceive('${this.escapeHtml(prNumber)}')">
                                    <i class="icon-base bx bx-pointer"></i>
                                </button>
                            </div>
                        </td>
                        <td>
                            <span class="fw-semibold text-primary">${this.escapeHtml(prNumber)}</span>
                        </td>
                        <td>
                            <div class="text-wrap text-body" style="max-width: 400px;">
                                ${this.escapeHtml(prName)}
                            </div>
                        </td>
                        <td>
                            <span class="badge ${badgeClass}">${this.escapeHtml(prType)}</span>
                        </td>
                        <td>${this.escapeHtml(prSubType)}</td>
                        <td>${this.escapeHtml(approvalStatus)}</td>
                        <td class="pic-cell">${this.escapeHtml(pic)}</td>
                        <td class="text-end">${amountDisplay}</td>
                        <td>${this.escapeHtml(company)}</td>
                        <td>${this.escapeHtml(requestor)}</td>
                        <td class="applicant-cell">${this.escapeHtml(applicant)}</td>
                        <td>${dateDisplay}</td>
                    </tr>
                `;
            }).join('');

            // Update applicant names and PIC names asynchronously
            await Promise.all([
                this.updateApplicantNames(data),
                this.updatePicNames(data)
            ]);
        }

        async updateApplicantNames(prList) {
            const applicantIds = [...new Set(prList
                .map(pr => pr.applicant || pr.Applicant || '')
                .filter(id => id && id.trim()))];

            if (applicantIds.length === 0) return;

            const applicantNameMap = new Map();
            const fetchPromises = applicantIds.map(async (applicantId) => {
                const name = await this.getEmployeeNameByEmployId(applicantId);
                if (name) {
                    applicantNameMap.set(applicantId, name);
                }
            });

            await Promise.all(fetchPromises);

            const rows = document.querySelectorAll('#receiveTableBody tr[data-applicant-id]');
            rows.forEach(row => {
                const applicantId = row.getAttribute('data-applicant-id');
                if (applicantId && applicantNameMap.has(applicantId)) {
                    const applicantCell = row.querySelector('.applicant-cell');
                    if (applicantCell) {
                        applicantCell.textContent = applicantNameMap.get(applicantId);
                    }
                }
            });
        }

        async getEmployeeNameByEmployId(employId) {
            if (!employId || employId === '-') return '';

            const cacheKey = employId.trim().toLowerCase();
            if (this.employeeNameCache.has(cacheKey)) {
                return this.employeeNameCache.get(cacheKey);
            }

            if (this.pendingEmployeeLookups.has(cacheKey)) {
                return await this.pendingEmployeeLookups.get(cacheKey);
            }

            const lookupPromise = (async () => {
                try {
                    const endpoint = `/Procurement/Master/Employees?searchTerm=${encodeURIComponent(employId)}`;
                    const data = await apiCall('Procurement', endpoint, 'GET');
                    const employees = data.data || data;

                    if (Array.isArray(employees) && employees.length > 0) {
                        const employee = employees.find(emp => {
                            const empId = emp.Employ_Id || emp.employ_Id || emp.EmployId || emp.employeeId || '';
                            return empId.trim().toLowerCase() === employId.trim().toLowerCase();
                        });

                        if (employee) {
                            const name = employee.name || employee.Name || '';
                            this.employeeNameCache.set(cacheKey, name);
                            return name;
                        }
                    }

                    this.employeeNameCache.set(cacheKey, '');
                    return '';
                } catch (error) {
                    console.error('Error fetching employee name:', error);
                    this.employeeNameCache.set(cacheKey, '');
                    return '';
                } finally {
                    this.pendingEmployeeLookups.delete(cacheKey);
                }
            })();

            this.pendingEmployeeLookups.set(cacheKey, lookupPromise);
            return await lookupPromise;
        }

        async updateEmployeeNamesInTable() {
            const employeeNameSpans = document.querySelectorAll('#receiveTable .employee-name');
            if (employeeNameSpans.length === 0) return;

            const employeeIds = new Set();
            employeeNameSpans.forEach(span => {
                const employeeId = span.getAttribute('data-employee-id');
                if (employeeId && employeeId !== '-') {
                    employeeIds.add(employeeId);
                }
            });

            if (employeeIds.size === 0) return;

            const lookupPromises = Array.from(employeeIds).map(async (employeeId) => {
                const name = await this.getEmployeeNameByEmployId(employeeId);
                return { employeeId, name };
            });

            const results = await Promise.all(lookupPromises);

            const nameMap = new Map();
            results.forEach(({ employeeId, name }) => {
                if (name) {
                    nameMap.set(employeeId.trim().toLowerCase(), name);
                }
            });

            employeeNameSpans.forEach(span => {
                const employeeId = span.getAttribute('data-employee-id');
                if (employeeId && employeeId !== '-') {
                    const cacheKey = employeeId.trim().toLowerCase();
                    const name = nameMap.get(cacheKey) || this.employeeNameCache.get(cacheKey);
                    if (name) {
                        span.textContent = name;
                    }
                }
            });
        }

        async updatePicNames(prList) {
            const picIds = [...new Set(prList
                .map(pr => pr.pic || pr.PIC || '')
                .filter(id => id && id.trim() && id !== '-'))];

            if (picIds.length === 0) return;

            const picNameMap = new Map();
            const fetchPromises = picIds.map(async (picId) => {
                const name = await this.getEmployeeNameByEmployId(picId);
                if (name) {
                    picNameMap.set(picId, name);
                }
            });

            await Promise.all(fetchPromises);

            const rows = document.querySelectorAll('#receiveTableBody tr[data-pic-id]');
            rows.forEach(row => {
                const picId = row.getAttribute('data-pic-id');
                if (picId && picId !== '-' && picNameMap.has(picId)) {
                    const picCell = row.querySelector('.pic-cell');
                    if (picCell) {
                        picCell.textContent = picNameMap.get(picId);
                    }
                }
            });
        }

        getBadgeClass(prType) {
            const type = prType.toLowerCase();
            if (type.includes('general')) return 'bg-label-primary';
            if (type.includes('goods')) return 'bg-label-success';
            if (type.includes('services')) return 'bg-label-warning';
            if (type.includes('works')) return 'bg-label-info';
            if (type.includes('consultancy')) return 'bg-label-secondary';
            return 'bg-label-primary';
        }

        getStatusBadgeClass(statusID) {
            if (!statusID) return 'bg-label-secondary';
            const statusClassMap = {
                1: 'bg-label-warning',
                2: 'bg-label-info',
                3: 'bg-label-primary',
                4: 'bg-label-success',
                5: 'bg-label-danger',
                6: 'bg-label-secondary',
                7: 'bg-label-success'
            };
            return statusClassMap[statusID] || 'bg-label-secondary';
        }

        formatCurrency(amount) {
            if (amount === null || amount === undefined || amount === '') return '-';
            const numAmount = parseFloat(amount);
            if (isNaN(numAmount)) return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(0);
            return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(numAmount);
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

        // Format date from YYYY-MM-DD to compact format (30 Nov 2025)
        formatDateCompact(dateString, includeYear = true) {
            if (!dateString) return '';

            const date = new Date(dateString + 'T00:00:00');
            if (isNaN(date.getTime())) return '';

            const months = [
                'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
                'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
            ];

            const day = date.getDate();
            const month = months[date.getMonth()];
            const year = date.getFullYear();

            return includeYear ? `${day} ${month} ${year}` : `${day} ${month}`;
        }

        // Update date range info in header with compact format
        updateDateRangeInfo() {
            const dateRangeInfo = document.getElementById('dateRangeInfo');
            if (!dateRangeInfo) return;

            const form = document.getElementById('filterForm');
            if (!form) return;

            const formData = new FormData(form);
            const startDate = formData.get('startDate');
            const endDate = formData.get('endDate');

            if (startDate && endDate) {
                const startDateObj = new Date(startDate + 'T00:00:00');
                const endDateObj = new Date(endDate + 'T00:00:00');

                const startYear = startDateObj.getFullYear();
                const endYear = endDateObj.getFullYear();
                const startMonth = startDateObj.getMonth();
                const endMonth = endDateObj.getMonth();
                const startDay = startDateObj.getDate();
                const endDay = endDateObj.getDate();

                // If same year and month, show: "1 Des 2025 s/d 31 Des 2025"
                if (startYear === endYear && startMonth === endMonth) {
                    // Check if same day
                    if (startDay === endDay) {
                        // Same day, show only one date
                        const formatted = this.formatDateCompact(startDate, true);
                        dateRangeInfo.textContent = ` ${formatted}`;
                    } else {
                        // Same month but different days, show range with year on both dates
                        const startFormatted = this.formatDateCompact(startDate, true);
                        const endFormatted = this.formatDateCompact(endDate, true);
                        dateRangeInfo.textContent = ` ${startFormatted} s/d ${endFormatted}`;
                    }
                }
                // If same year, show: "30 Nov 2025 s/d 30 Des 2025"
                else if (startYear === endYear) {
                    const startFormatted = this.formatDateCompact(startDate, true);
                    const endFormatted = this.formatDateCompact(endDate, true);
                    dateRangeInfo.textContent = ` ${startFormatted} s/d ${endFormatted}`;
                }
                // Different years, show: "30 Nov 2024 s/d 30 Des 2025"
                else {
                    const startFormatted = this.formatDateCompact(startDate, true);
                    const endFormatted = this.formatDateCompact(endDate, true);
                    dateRangeInfo.textContent = ` ${startFormatted} s/d ${endFormatted}`;
                }
            } else if (startDate) {
                const startFormatted = this.formatDateCompact(startDate, true);
                dateRangeInfo.textContent = ` ${startFormatted}`;
            } else if (endDate) {
                const endFormatted = this.formatDateCompact(endDate, true);
                dateRangeInfo.textContent = ` ${endFormatted}`;
            } else {
                dateRangeInfo.textContent = '';
            }
        }

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        showLoading() {
            const tbody = document.getElementById('receiveTableBody');
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="13" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="mt-2">Loading...</div>
                        </td>
                    </tr>
                `;
            }
        }

        showError(message) {
            const tbody = document.getElementById('receiveTableBody');
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="13" class="text-center py-4">
                            <div class="text-danger">${this.escapeHtml(message)}</div>
                        </td>
                    </tr>
                `;
            }
        }

        showSuccessMessage(message) {
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

            const container = document.querySelector('.receive-list-container');
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

        async viewReceive(prNumber) {
            if (this.viewModule && this.viewModule.viewReceive) {
                return this.viewModule.viewReceive(prNumber);
            }
            console.warn('ReceiveListView module not available');
        }

        async submitReceive() {
            if (this.viewModule && this.viewModule.submitReceive) {
                return this.viewModule.submitReceive();
            }
            console.warn('ReceiveListView module not available');
        }

        async processBulkReceive() {
            if (this.viewModule && this.viewModule.processBulkReceive) {
                return this.viewModule.processBulkReceive();
            }
            console.warn('ReceiveListView module not available');
        }

        backToList() {
            if (this.viewModule && this.viewModule.backToList) {
                return this.viewModule.backToList();
            }
            console.warn('ReceiveListView module not available');
        }


    }

    // Flag to prevent multiple initializations
    let receiveListInitialized = false;

    // Initialize manager when all dependencies are ready
    function initializeReceiveList() {
        // Prevent multiple initializations
        if (receiveListInitialized) {
            return;
        }

        if (typeof $ === 'undefined' || typeof $.fn.DataTable === 'undefined') {
            setTimeout(initializeReceiveList, 100);
            return;
        }

        if (typeof CreateTable === 'undefined' || typeof ColumnBuilder === 'undefined') {
            setTimeout(initializeReceiveList, 100);
            return;
        }

        // All dependencies are ready, initialize
        window.receiveListManager = new ReceiveListManager();
        receiveListInitialized = true;
    }

    // Start initialization when DOM is ready (only once)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeReceiveList);
    } else {
        initializeReceiveList();
    }
</script>

