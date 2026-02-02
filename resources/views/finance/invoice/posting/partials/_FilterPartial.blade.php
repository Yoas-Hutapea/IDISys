<form id="filterForm" class="dark-mode-compatible">
    <div class="row g-3">
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Invoice Date Period</label>
                <div class="row g-2">
                    <div class="col-6">
                        <input type="date" class="form-control bg-body text-body" id="filterStartDate" name="filterStartDate" placeholder="Start Date">
                    </div>
                    <div class="col-1 d-flex align-items-center justify-content-center">
                        <span class="text-muted">to</span>
                    </div>
                    <div class="col-5">
                        <input type="date" class="form-control bg-body text-body" id="filterEndDate" name="filterEndDate" placeholder="End Date">
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Invoice Number</label>
                <input type="text" class="form-control bg-body text-body" id="filterInvoiceNumber" name="filterInvoiceNumber" placeholder="Search invoice number...">
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Request Number</label>
                <input type="text" class="form-control bg-body text-body" id="filterRequestNumber" name="filterRequestNumber" placeholder="Search request number...">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold text-body">PO Number</label>
                <input type="text" class="form-control bg-body text-body" id="filterPurchOrderID" name="filterPurchOrderID" placeholder="Search PO number...">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Work Type</label>
                <div class="dropdown" id="workTypeDropdownContainer">
                    <button class="form-select text-start bg-body text-body" type="button" id="workTypeDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                        <span id="workTypeSelectedText">Select Work Type</span>
                    </button>
                    <input type="hidden" id="filterWorkType" name="filterWorkType">
                    <ul class="dropdown-menu w-100" id="workTypeDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                        <li class="px-3 py-2">
                            <input type="text" class="form-control form-control-sm" id="workTypeSearchInput" placeholder="Search work type..." autocomplete="off">
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li id="workTypeDropdownItems">
                            <div class="px-3 py-2 text-muted text-center">Loading work types...</div>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Status</label>
                <div class="dropdown" id="statusDropdownContainer">
                    <button class="form-select text-start bg-body text-body" type="button" id="statusDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                        <span id="statusSelectedText">Select Status</span>
                    </button>
                    <input type="hidden" id="filterStatus" name="filterStatus">
                    <ul class="dropdown-menu w-100" id="statusDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                        <li class="px-3 py-2">
                            <input type="text" class="form-control form-control-sm" id="statusSearchInput" placeholder="Search status..." autocomplete="off">
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li id="statusDropdownItems">
                            <div class="px-3 py-2 text-muted text-center">Loading statuses...</div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-center gap-3 mt-4">
                <button type="button" class="btn btn-primary" onclick="applyFilter()">
                    <i class="icon-base bx bx-search me-2"></i>
                    Search
                </button>
                <button type="button" class="btn btn-secondary" onclick="resetFilter()">
                    <i class="icon-base bx bx-refresh me-2"></i>
                    Reset
                </button>
            </div>
        </div>
    </div>
</form>

<script>
    (function() {
        'use strict';

        function setDefaultDateRange() {
            const now = new Date();
            const currentYear = now.getFullYear();
            const currentMonth = now.getMonth();
            const firstDay = new Date(currentYear, currentMonth, 1);
            const lastDay = new Date(currentYear, currentMonth + 1, 0);

            const formatDate = (date) => {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            };

            const startDateInput = document.getElementById('filterStartDate');
            const endDateInput = document.getElementById('filterEndDate');

            if (startDateInput) {
                startDateInput.value = formatDate(firstDay);
            }
            if (endDateInput) {
                endDateInput.value = formatDate(lastDay);
            }
        }

        async function loadWorkTypes() {
            const workTypeHiddenInput = document.getElementById('filterWorkType');
            const workTypeDropdownItems = document.getElementById('workTypeDropdownItems');
            const workTypeSelectedText = document.getElementById('workTypeSelectedText');
            const workTypeSearchInput = document.getElementById('workTypeSearchInput');

            if (!workTypeHiddenInput || !workTypeDropdownItems) return;

            workTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Loading work types...</div>';

            try {
                const endpoint = '/Finance/EBilling/InvoiceWorkTypes?isActive=true';
                const data = await apiCall('Procurement', endpoint, 'GET');
                const workTypes = data.data || data;

                if (!Array.isArray(workTypes) || workTypes.length === 0) {
                    workTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No work types found</div>';
                    return;
                }

                renderDropdownWithSearch({
                    items: workTypes,
                    searchTerm: '',
                    dropdownItemsId: 'workTypeDropdownItems',
                    hiddenInputId: 'filterWorkType',
                    selectedTextId: 'workTypeSelectedText',
                    searchInputId: 'workTypeSearchInput',
                    dropdownBtnId: 'workTypeDropdownBtn',
                    getValue: (workType) => (workType.ID || workType.id || '').toString(),
                    getText: (workType) => workType.WorkType || workType.workType || '',
                    getSearchableText: (workType) => (workType.WorkType || workType.workType || '').toString(),
                    limit: 5
                });

                if (workTypeSearchInput) {
                    const newSearchInput = workTypeSearchInput.cloneNode(true);
                    workTypeSearchInput.parentNode.replaceChild(newSearchInput, workTypeSearchInput);

                    newSearchInput.addEventListener('input', (e) => {
                        const searchTerm = e.target.value.toLowerCase().trim();
                        renderDropdownWithSearch({
                            items: workTypes,
                            searchTerm: searchTerm,
                            dropdownItemsId: 'workTypeDropdownItems',
                            hiddenInputId: 'filterWorkType',
                            selectedTextId: 'workTypeSelectedText',
                            searchInputId: 'workTypeSearchInput',
                            dropdownBtnId: 'workTypeDropdownBtn',
                            getValue: (workType) => (workType.ID || workType.id || '').toString(),
                            getText: (workType) => workType.WorkType || workType.workType || '',
                            getSearchableText: (workType) => (workType.WorkType || workType.workType || '').toString(),
                            limit: 5
                        });
                    });

                    newSearchInput.addEventListener('click', (e) => {
                        e.stopPropagation();
                    });
                }

                const workTypeDropdownBtn = document.getElementById('workTypeDropdownBtn');
                if (workTypeDropdownBtn) {
                    workTypeDropdownBtn.addEventListener('shown.bs.dropdown', () => {
                        const searchInput = document.getElementById('workTypeSearchInput');
                        if (searchInput) {
                            setTimeout(() => {
                                searchInput.focus();
                            }, 100);
                        }
                    });
                }
            } catch (error) {
                workTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-danger text-center">Error loading work types</div>';
            }
        }

        async function loadStatuses() {
            const statusHiddenInput = document.getElementById('filterStatus');
            const statusDropdownItems = document.getElementById('statusDropdownItems');
            const statusSelectedText = document.getElementById('statusSelectedText');
            const statusSearchInput = document.getElementById('statusSearchInput');

            if (!statusHiddenInput || !statusDropdownItems) return;

            statusDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Loading statuses...</div>';

            try {
                const response = await apiCall('Procurement', '/Procurement/Master/ApprovalStatuses?isActive=true', 'GET');
                const statuses = Array.isArray(response) ? response : (response.data || response.Data || []);

                if (!Array.isArray(statuses) || statuses.length === 0) {
                    statusDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No statuses found</div>';
                    return;
                }

                renderDropdownWithSearch({
                    items: statuses,
                    searchTerm: '',
                    dropdownItemsId: 'statusDropdownItems',
                    hiddenInputId: 'filterStatus',
                    selectedTextId: 'statusSelectedText',
                    searchInputId: 'statusSearchInput',
                    dropdownBtnId: 'statusDropdownBtn',
                    getValue: (status) => (status.ID || status.id || '').toString(),
                    getText: (status) => status.ApprovalStatus || status.approvalStatus || '',
                    getSearchableText: (status) => status.ApprovalStatus || status.approvalStatus || '',
                    limit: 5
                });

                if (statusSearchInput) {
                    const newSearchInput = statusSearchInput.cloneNode(true);
                    statusSearchInput.parentNode.replaceChild(newSearchInput, statusSearchInput);

                    newSearchInput.addEventListener('input', (e) => {
                        const searchTerm = e.target.value.toLowerCase().trim();
                        renderDropdownWithSearch({
                            items: statuses,
                            searchTerm: searchTerm,
                            dropdownItemsId: 'statusDropdownItems',
                            hiddenInputId: 'filterStatus',
                            selectedTextId: 'statusSelectedText',
                            searchInputId: 'statusSearchInput',
                            dropdownBtnId: 'statusDropdownBtn',
                            getValue: (status) => (status.ID || status.id || '').toString(),
                            getText: (status) => status.ApprovalStatus || status.approvalStatus || '',
                            getSearchableText: (status) => status.ApprovalStatus || status.approvalStatus || '',
                            limit: 5
                        });
                    });
                }
            } catch (error) {
                statusDropdownItems.innerHTML = '<div class="px-3 py-2 text-danger text-center">Error loading statuses</div>';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            setDefaultDateRange();
            loadWorkTypes();
            loadStatuses();
        });
    })();
</script>
