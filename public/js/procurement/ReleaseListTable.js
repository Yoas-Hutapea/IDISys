/**
 * ReleaseListTable Module
 * Handles DataTable initialization for normal and bulky release, employee name updates, and bulky checkbox management
 */
class ReleaseListTable {
    constructor(managerInstance) {
        this.manager = managerInstance;
        this.dataTable = null;
        this.bulkyReleaseDataTable = null;
        this.bulkyCheckboxDisabledClickHandler = null;
    }

    /**
     * Initialize normal release DataTable
     */
    initializeDataTable() {
        // Initialize DataTable using CreateTable helper with GetGrid endpoint
        if (typeof CreateTable === 'undefined' || typeof ColumnBuilder === 'undefined') {
            console.error('CreateTable or ColumnBuilder helper not found. Make sure DataTableHelper.js is loaded.');
            return;
        }

        const self = this;

        // Build filter function for GetGrid
        const buildFilter = () => {
            if (this.manager && this.manager.filterModule && this.manager.filterModule.buildFilter) {
                return this.manager.filterModule.buildFilter();
            }
            return {};
        };

        // Initialize DataTable
        this.dataTable = CreateTable({
            apiType: 'Procurement',
            endpoint: '/Procurement/PurchaseRequest/PurchaseRequestReleases/Grid',
            tableId: '#releaseTable',
            columns: ColumnBuilder([
                {
                    type: 'actions',
                    buttons: [
                        {
                            label: 'View',
                            icon: '<i class="bx bx-pointer"></i>',
                            className: 'btn-primary',
                            title: 'View & Release',
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
                    render: function(data, type, row) {
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
                    render: function(data, type, row) {
                        const requestorId = data || row.requestor || row.Requestor || '';
                        if (!requestorId) return '-';
                        const escapedId = self.escapeHtml(requestorId);
                        return `<span class="employee-name" data-employee-id="${escapedId}">${escapedId}</span>`;
                    }
                },
                {
                    data: 'applicant',
                    title: 'Applicant',
                    render: function(data, type, row) {
                        const applicantId = data || row.applicant || row.Applicant || '';
                        if (!applicantId) return '-';
                        const escapedId = self.escapeHtml(applicantId);
                        return `<span class="employee-name" data-employee-id="${escapedId}">${escapedId}</span>`;
                    }
                },
                { data: 'createdDate', title: 'Request Date', type: 'datetime' }
            ]),
            filter: buildFilter.bind(this),
            order: [[11, 'desc']], // Order by CreatedDate (column index 11) descending
            serverSide: true
        });

        // Bind action buttons
        $(document).off('click', '#releaseTable .action-btn').on('click', '#releaseTable .action-btn', function() {
            const action = $(this).data('action');
            const row = self.dataTable.row($(this).closest('tr')).data();
            const prNumber = row.purchReqNumber || row.PurchReqNumber || '';

            switch(action) {
                case 'view':
                    if (self.manager && self.manager.viewModule && self.manager.viewModule.viewRelease) {
                        self.manager.viewModule.viewRelease(prNumber);
                    }
                    break;
            }
        });

        // Update employee names after DataTable draws
        this.dataTable.on('draw', () => {
            this.updateEmployeeNamesInTable();
        });

        // Initial update after table is drawn
        setTimeout(() => this.updateEmployeeNamesInTable(), 500);
    }

    /**
     * Initialize bulky release DataTable
     */
    initializeBulkyReleaseDataTable() {
        if (typeof CreateTable === 'undefined' || typeof ColumnBuilder === 'undefined') {
            console.error('CreateTable or ColumnBuilder helper not found.');
            return;
        }

        const self = this;

        // Build filter function for GetGrid
        const buildBulkyFilter = () => {
            if (this.manager && this.manager.filterModule && this.manager.filterModule.buildBulkyFilter) {
                return this.manager.filterModule.buildBulkyFilter();
            }
            return {};
        };

        // Setup event delegation for disabled checkbox clicks (do this once, before table initialization)
        const tableBody = document.getElementById('bulkyReleaseTableBody');
        if (tableBody && !this.bulkyCheckboxDisabledClickHandler) {
            this.bulkyCheckboxDisabledClickHandler = (e) => {
                // Find the TD that was clicked
                let clickedTd = null;

                // Check if clicked directly on checkbox
                if (e.target && e.target.type === 'checkbox' && e.target.classList.contains('row-checkbox')) {
                    clickedTd = e.target.closest('td');
                } else if (e.target && e.target.closest) {
                    // Check if clicked on TD
                    clickedTd = e.target.closest('td');
                }

                // If clicked on TR, get first TD (checkbox column)
                if (!clickedTd && e.target && e.target.tagName === 'TR') {
                    clickedTd = e.target.querySelector('td:first-child');
                }

                // Check if this is the first TD (checkbox column) and contains a disabled checkbox
                if (clickedTd) {
                    const tr = clickedTd.closest('tr');
                    if (tr) {
                        const firstTd = tr.querySelector('td:first-child');
                        // Only handle if clicking on first TD (checkbox column)
                        if (clickedTd === firstTd && clickedTd.dataset.checkboxDisabled === 'true') {
                            const checkbox = clickedTd.querySelector('.row-checkbox');
                            if (checkbox && checkbox.disabled) {
                                e.preventDefault();
                                e.stopPropagation();
                                e.stopImmediatePropagation();

                                console.log('Disabled checkbox clicked, mode:', clickedTd.dataset.checkboxMode);

                                const mode = clickedTd.dataset.checkboxMode;
                                // Show appropriate message based on mode
                                if (mode === 'mode1') {
                                    if (self.manager && self.manager.viewModule && self.manager.viewModule.showAlertModal) {
                                        self.manager.viewModule.showAlertModal('Only PRs without Period (StartPeriod/EndPeriod is NULL or not in Additional) can be selected.\n\nPlease select PRs without Period.', 'warning');
                                    }
                                } else if (mode === 'mode2') {
                                    if (self.manager && self.manager.viewModule && self.manager.viewModule.showAlertModal) {
                                        self.manager.viewModule.showAlertModal('Only PRs with Period (StartPeriod/EndPeriod is not NULL) can be selected.\n\nPlease select PRs with Period.', 'warning');
                                    }
                                }
                                return false;
                            }
                        }
                    }
                }
            };

            // Add event listeners with capture phase to catch disabled checkbox clicks
            // Use both click and mousedown for better compatibility
            tableBody.addEventListener('click', this.bulkyCheckboxDisabledClickHandler, true);
            tableBody.addEventListener('mousedown', this.bulkyCheckboxDisabledClickHandler, true);
        }

        // Initialize DataTable for Bulky Release
        this.bulkyReleaseDataTable = CreateTable({
            apiType: 'Procurement',
            endpoint: '/Procurement/PurchaseRequest/PurchaseRequestReleases/Grid',
            tableId: '#bulkyReleaseTable',
            columns: ColumnBuilder([
                {
                    // Checkbox column for bulk release
                    data: 'purchReqNumber',
                    title: '',
                    orderable: false,
                    searchable: false,
                    width: '50px',
                    render: function(data, type, row) {
                        const prNumber = data || row.purchReqNumber || row.PurchReqNumber || '';
                        const isSelected = self.manager && self.manager.selectedPRNumbers && self.manager.selectedPRNumbers.has(prNumber);
                        // Checkbox will be enabled/disabled by updateBulkyCheckboxStates after data is loaded
                        return `<input type="checkbox" class="row-checkbox" data-pr-number="${self.escapeHtml(prNumber)}" ${isSelected ? 'checked' : ''} onchange="releaseListManager.toggleBulkyRowSelection('${self.escapeHtml(prNumber)}', this.checked)">`;
                    }
                },
                { data: 'purchReqNumber', title: 'Purchase Request Number' },
                { data: 'purchReqName', title: 'Purchase Request Name' },
                { data: 'purchReqType', title: 'Purchase Request Type' },
                { data: 'purchReqSubType', title: 'Purchase Request Sub Type' },
                { data: 'approvalStatus', title: 'Status' },
                {
                    data: 'pic',
                    title: 'PIC',
                    render: function(data, type, row) {
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
                    render: function(data, type, row) {
                        const requestorId = data || row.requestor || row.Requestor || '';
                        if (!requestorId) return '-';
                        const escapedId = self.escapeHtml(requestorId);
                        return `<span class="employee-name" data-employee-id="${escapedId}">${escapedId}</span>`;
                    }
                },
                {
                    data: 'applicant',
                    title: 'Applicant',
                    render: function(data, type, row) {
                        const applicantId = data || row.applicant || row.Applicant || '';
                        if (!applicantId) return '-';
                        const escapedId = self.escapeHtml(applicantId);
                        return `<span class="employee-name" data-employee-id="${escapedId}">${escapedId}</span>`;
                    }
                },
                { data: 'createdDate', title: 'Request Date', type: 'datetime' }
            ]),
            filter: buildBulkyFilter.bind(this),
            order: [[11, 'desc']], // Order by CreatedDate (column index 11) descending
            serverSide: true
        });

        // Update employee names and load PR Additional data after DataTable draws
        this.bulkyReleaseDataTable.on('draw', async () => {
            this.updateEmployeeNamesInBulkyTable();
            await this.loadBulkyPRAdditionalData();
            this.updateBulkyCheckboxStates();
            this.updateBulkySelectAllCheckbox();

            // Re-add overlays after a short delay to ensure DOM is ready
            setTimeout(() => {
                this.updateBulkyCheckboxStates();
            }, 100);
        });

        // Initial update after table is drawn
        setTimeout(() => this.updateEmployeeNamesInBulkyTable(), 500);
    }

    /**
     * Load bulky PR Additional data
     */
    async loadBulkyPRAdditionalData() {
        try {
            if (!this.bulkyReleaseDataTable) return;

            // Get all PR numbers from current table data
            const tableData = this.bulkyReleaseDataTable.rows({ search: 'applied' }).data().toArray();
            const prNumbers = tableData.map(row => {
                return row.purchReqNumber || row.PurchReqNumber || '';
            }).filter(pr => pr);

            if (prNumbers.length === 0) {
                if (this.manager && this.manager.bulkyPRAdditionalCache) {
                    this.manager.bulkyPRAdditionalCache.clear();
                }
                return;
            }

            // Use API module to get batch additional data
            if (this.manager && this.manager.apiModule && this.manager.apiModule.getPRAdditionalBatch) {
                const rawResponse = await this.manager.apiModule.getPRAdditionalBatch(prNumbers);

                // Backend returns array of { purchReqNumber, startPeriod, endPeriod }; build map by PR number
                const additionalDataMap = new Map();
                if (Array.isArray(rawResponse)) {
                    rawResponse.forEach(item => {
                        const key = item.purchReqNumber ?? item.PurchReqNumber ?? item.trxPROPurchaseRequestNumber ?? '';
                        if (key) additionalDataMap.set(String(key).trim(), item);
                    });
                } else if (rawResponse && typeof rawResponse === 'object' && !Array.isArray(rawResponse)) {
                    Object.entries(rawResponse).forEach(([key, item]) => {
                        additionalDataMap.set(String(key).trim(), item);
                    });
                }

                // Update cache in manager (normalize period; ensure every PR has an entry)
                if (this.manager.bulkyPRAdditionalCache) {
                    this.manager.bulkyPRAdditionalCache.clear();
                    for (const prNum of prNumbers) {
                        const key = String(prNum).trim();
                        const additionalDto = additionalDataMap.get(key) || additionalDataMap.get(prNum);
                        if (additionalDto && (additionalDto.startPeriod != null || additionalDto.endPeriod != null || additionalDto.StartPeriod != null || additionalDto.EndPeriod != null)) {
                            const start = additionalDto.startPeriod ?? additionalDto.StartPeriod ?? null;
                            const end = additionalDto.endPeriod ?? additionalDto.EndPeriod ?? null;
                            this.manager.bulkyPRAdditionalCache.set(prNum, {
                                exists: true,
                                startPeriod: start,
                                endPeriod: end,
                                StartPeriod: start,
                                EndPeriod: end
                            });
                        } else {
                            this.manager.bulkyPRAdditionalCache.set(prNum, {
                                exists: false,
                                startPeriod: null,
                                endPeriod: null
                            });
                        }
                    }
                }
            }
        } catch (error) {
            console.error('Error loading PR Additional data:', error);
        }
    }

    /**
     * Helper: determine if a PR has complete period (StartPeriod & EndPeriod not NULL)
     */
    getPRHasPeriods(prNumber) {
        if (!prNumber || !this.manager || !this.manager.bulkyPRAdditionalCache) return false;
        const additionalData = this.manager.bulkyPRAdditionalCache.get(prNumber);

        // No additional data -> treat as no period
        if (!additionalData || !additionalData.exists) {
            return false;
        }

        // Consider both camelCase and PascalCase properties
        const start = additionalData.startPeriod ?? additionalData.StartPeriod ?? null;
        const end = additionalData.endPeriod ?? additionalData.EndPeriod ?? null;

        return start != null && end != null;
    }

    /**
     * Check if PR can be selected based on current mode (TOP filter group)
     * Mode mapping (for backward compatibility):
     * - mode1: PRs WITHOUT complete periods (TOP not filtered)
     * - mode2: PRs WITH complete periods (TOP filtered to 759/760/761)
     */
    canSelectBulkyPR(prNumber) {
        if (!prNumber) return false;
        if (!this.manager || !this.manager.bulkyPRAdditionalCache) return false;

        // If no mode is set, all PRs can be selected (no grouping yet)
        if (this.manager.bulkyCheckboxMode === null) {
            return true;
        }

        const hasPeriods = this.getPRHasPeriods(prNumber);

        if (this.manager.bulkyCheckboxMode === 'mode1') {
            // Mode 1: only PRs WITHOUT complete periods (TOP not filtered)
            return !hasPeriods;
        } else if (this.manager.bulkyCheckboxMode === 'mode2') {
            // Mode 2: only PRs WITH complete periods (TOP filtered 759/760/761)
            return hasPeriods;
        }

        return false;
    }

    /**
     * Update bulky checkbox states based on mode
     */
    updateBulkyCheckboxStates() {
        if (!this.manager || !this.manager.bulkyPRAdditionalCache) return;

        // Support both tbody id and table id (DataTables may not preserve tbody id)
        const checkboxes = document.querySelectorAll('#bulkyReleaseTable tbody .row-checkbox');

        checkboxes.forEach(cb => {
            const prNumber = cb.getAttribute('data-pr-number');
            if (!prNumber) return;

            const canSelect = this.canSelectBulkyPR(prNumber);
            const isSelected = this.manager.selectedPRNumbers && this.manager.selectedPRNumbers.has(prNumber);

            // Enable/disable checkbox
            cb.disabled = !canSelect;

            // If checkbox is disabled and was selected, uncheck it
            if (!canSelect && isSelected) {
                cb.checked = false;
                if (this.manager.selectedPRNumbers) {
                    this.manager.selectedPRNumbers.delete(prNumber);
                }
            }

            // Add click handler directly on TD and TR for disabled checkbox
            const parentTd = cb.closest('td');
            const parentTr = cb.closest('tr');

            if (parentTd && parentTr) {
                // Remove existing handler if any
                if (parentTd.dataset.hasDisabledHandler === 'true') {
                    // Remove old event listeners from both TD and TR
                    const oldHandler = parentTd._disabledClickHandler;
                    if (oldHandler) {
                        parentTd.removeEventListener('click', oldHandler, true);
                        parentTd.removeEventListener('mousedown', oldHandler, true);
                        parentTr.removeEventListener('click', oldHandler, true);
                        parentTr.removeEventListener('mousedown', oldHandler, true);
                    }
                }

                if (!canSelect && this.manager.bulkyCheckboxMode !== null) {
                    parentTd.dataset.checkboxDisabled = 'true';
                    parentTd.dataset.checkboxMode = this.manager.bulkyCheckboxMode;
                    parentTd.style.cursor = 'not-allowed';
                    parentTd.dataset.hasDisabledHandler = 'true';

                    // Create handler function
                    const handleDisabledClick = (e) => {
                        // Check if clicking on this TD (checkbox column)
                        const clickedTd = e.target.closest('td');

                        // Only handle if clicking on the checkbox TD
                        if (clickedTd === parentTd) {
                            const clickedCheckbox = parentTd.querySelector('.row-checkbox');
                            if (clickedCheckbox && clickedCheckbox.disabled) {
                                e.preventDefault();
                                e.stopPropagation();
                                e.stopImmediatePropagation();

                                console.log('Disabled checkbox clicked, mode:', this.manager.bulkyCheckboxMode);

                                // Show appropriate message based on mode
                                if (this.manager.bulkyCheckboxMode === 'mode1') {
                                    if (this.manager.viewModule && this.manager.viewModule.showAlertModal) {
                                        this.manager.viewModule.showAlertModal('Only PRs without Period (StartPeriod/EndPeriod is NULL or not in Additional) can be selected.\n\nPlease select PRs without Period.', 'warning');
                                    }
                                } else if (this.manager.bulkyCheckboxMode === 'mode2') {
                                    if (this.manager.viewModule && this.manager.viewModule.showAlertModal) {
                                        this.manager.viewModule.showAlertModal('Only PRs with Period (StartPeriod/EndPeriod is not NULL) can be selected.\n\nPlease select PRs with Period.', 'warning');
                                    }
                                }
                                return false;
                            }
                        }
                    };

                    // Store handler for cleanup
                    parentTd._disabledClickHandler = handleDisabledClick;

                    // Add event listeners with capture phase on both TD and TR
                    parentTd.addEventListener('click', handleDisabledClick, true);
                    parentTd.addEventListener('mousedown', handleDisabledClick, true);
                    parentTr.addEventListener('click', handleDisabledClick, true);
                    parentTr.addEventListener('mousedown', handleDisabledClick, true);
                } else {
                    // Remove handler and reset for enabled checkbox
                    parentTd.removeAttribute('data-checkbox-disabled');
                    parentTd.removeAttribute('data-checkbox-mode');
                    parentTd.removeAttribute('data-has-disabled-handler');
                    parentTd.style.cursor = '';

                    const oldHandler = parentTd._disabledClickHandler;
                    if (oldHandler) {
                        parentTd.removeEventListener('click', oldHandler, true);
                        parentTd.removeEventListener('mousedown', oldHandler, true);
                        if (parentTr) {
                            parentTr.removeEventListener('click', oldHandler, true);
                            parentTr.removeEventListener('mousedown', oldHandler, true);
                        }
                        delete parentTd._disabledClickHandler;
                    }
                }
            }
        });
    }

    /**
     * Update bulky select all checkbox state
     */
    updateBulkySelectAllCheckbox() {
        const selectAllCheckbox = document.getElementById('bulkySelectAllCheckbox');
        if (!selectAllCheckbox) return;

        const checkboxes = document.querySelectorAll('#bulkyReleaseTableBody .row-checkbox');
        if (checkboxes.length === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
            return;
        }

        const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
        if (checkedCount === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        } else if (checkedCount === checkboxes.length) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = true;
        } else {
            selectAllCheckbox.indeterminate = true;
            selectAllCheckbox.checked = false;
        }
    }

    /**
     * Toggle bulky select all
     */
    async toggleBulkySelectAll(isChecked) {
        if (!this.manager) return;

        const checkboxes = document.querySelectorAll('#bulkyReleaseTableBody .row-checkbox');

        if (isChecked) {
            // Find first PR to determine mode (if mode not already set)
            if (this.manager.bulkyCheckboxMode === null) {
                let firstPR = null;
                for (const cb of checkboxes) {
                    const prNumber = cb.getAttribute('data-pr-number');
                    if (prNumber) {
                        firstPR = prNumber;
                        break;
                    }
                }

                if (firstPR) {
                    // Set mode based on first PR
                    const additionalData = this.manager.bulkyPRAdditionalCache && this.manager.bulkyPRAdditionalCache.get(firstPR);
                    if (!additionalData || !additionalData.exists) {
                        this.manager.bulkyCheckboxMode = 'mode1';
                    } else if (additionalData.exists && additionalData.startPeriod != null && additionalData.endPeriod != null) {
                        this.manager.bulkyCheckboxMode = 'mode2';
                    } else {
                        this.manager.bulkyCheckboxMode = 'mode1';
                    }
                }
            }

            // Update checkbox states and select only selectable ones
            this.updateBulkyCheckboxStates();

            checkboxes.forEach(cb => {
                const prNumber = cb.getAttribute('data-pr-number');
                if (prNumber && this.canSelectBulkyPR(prNumber)) {
                    cb.checked = true;
                    if (this.manager.selectedPRNumbers) {
                        this.manager.selectedPRNumbers.add(prNumber);
                    }
                } else {
                    cb.checked = false;
                    if (this.manager.selectedPRNumbers && prNumber) {
                        this.manager.selectedPRNumbers.delete(prNumber);
                    }
                }
            });
        } else {
            checkboxes.forEach(cb => {
                cb.checked = false;
                const prNumber = cb.getAttribute('data-pr-number');
                if (prNumber && this.manager.selectedPRNumbers) {
                    this.manager.selectedPRNumbers.delete(prNumber);
                }
            });
            if (this.manager.bulkyCheckboxMode !== undefined) {
                this.manager.bulkyCheckboxMode = null;
            }
            this.updateBulkyCheckboxStates();
        }

        this.updateBulkySelectAllCheckbox();
    }

    /**
     * Update employee names in normal release table
     */
    async updateEmployeeNamesInTable() {
        if (!this.manager || !this.manager.employeeCacheModule) return;

        const employeeNameSpans = document.querySelectorAll('#releaseTable .employee-name');
        if (employeeNameSpans.length === 0) return;

        // Collect unique employee IDs
        const employeeIds = new Set();
        employeeNameSpans.forEach(span => {
            const employeeId = span.getAttribute('data-employee-id');
            if (employeeId && employeeId !== '-') {
                employeeIds.add(employeeId);
            }
        });

        if (employeeIds.size === 0) return;

        // Batch lookup all employee names
        if (this.manager.employeeCacheModule.batchGetEmployeeNames) {
            const nameMap = await this.manager.employeeCacheModule.batchGetEmployeeNames(Array.from(employeeIds));

            // Update all employee name spans
            employeeNameSpans.forEach(span => {
                const employeeId = span.getAttribute('data-employee-id');
                if (employeeId && employeeId !== '-') {
                    const cacheKey = employeeId.trim().toLowerCase();
                    const name = nameMap.get(cacheKey);
                    if (name) {
                        span.textContent = name;
                    }
                }
            });
        } else {
            // Fallback: individual lookups
            const lookupPromises = Array.from(employeeIds).map(async (employeeId) => {
                const name = await this.manager.employeeCacheModule.getEmployeeNameByEmployId(employeeId);
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
                    const name = nameMap.get(cacheKey);
                    if (name) {
                        span.textContent = name;
                    }
                }
            });
        }
    }

    /**
     * Update employee names in bulky release table
     */
    async updateEmployeeNamesInBulkyTable() {
        if (!this.manager || !this.manager.employeeCacheModule) return;

        const employeeNameSpans = document.querySelectorAll('#bulkyReleaseTable .employee-name');
        if (employeeNameSpans.length === 0) return;

        // Collect unique employee IDs
        const employeeIds = new Set();
        employeeNameSpans.forEach(span => {
            const employeeId = span.getAttribute('data-employee-id');
            if (employeeId && employeeId !== '-') {
                employeeIds.add(employeeId);
            }
        });

        if (employeeIds.size === 0) return;

        // Batch lookup all employee names
        if (this.manager.employeeCacheModule.batchGetEmployeeNames) {
            const nameMap = await this.manager.employeeCacheModule.batchGetEmployeeNames(Array.from(employeeIds));

            // Update all employee name spans
            employeeNameSpans.forEach(span => {
                const employeeId = span.getAttribute('data-employee-id');
                if (employeeId && employeeId !== '-') {
                    const cacheKey = employeeId.trim().toLowerCase();
                    const name = nameMap.get(cacheKey);
                    if (name) {
                        span.textContent = name;
                    }
                }
            });
        } else {
            // Fallback: individual lookups
            const lookupPromises = Array.from(employeeIds).map(async (employeeId) => {
                const name = await this.manager.employeeCacheModule.getEmployeeNameByEmployId(employeeId);
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
                    const name = nameMap.get(cacheKey);
                    if (name) {
                        span.textContent = name;
                    }
                }
            });
        }
    }

    /**
     * Search release (reload table with filters)
     */
    searchRelease() {
        if (this.dataTable) {
            this.dataTable.ajax.reload();
        }
    }

    /**
     * Search bulky release (reload table with filters)
     */
    searchBulkyRelease() {
        if (this.bulkyReleaseDataTable) {
            this.bulkyReleaseDataTable.ajax.reload();
        }
    }

    /**
     * Refresh table
     */
    refreshTable() {
        if (this.dataTable) {
            this.dataTable.ajax.reload();
        }
    }

    /**
     * Escape HTML
     */
    escapeHtml(text) {
        if (text == null) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.ReleaseListTable = ReleaseListTable;
}

