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

            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Purchase Type</label>
                <div class="dropdown" id="purchaseTypeDropdownContainer">
                    <button class="form-select text-start bg-body text-body" type="button" id="purchaseTypeDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                        <span id="purchaseTypeSelectedText">Select Purchase Type</span>
                    </button>
                    <input type="hidden" id="filterPurchaseType" name="filterPurchaseType">
                    <ul class="dropdown-menu w-100" id="purchaseTypeDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                        <li class="px-3 py-2">
                            <input type="text" class="form-control form-control-sm" id="purchaseTypeSearchInput" placeholder="Search purchase type..." autocomplete="off">
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li id="purchaseTypeDropdownItems">
                            <div class="px-3 py-2 text-muted text-center">Loading purchase types...</div>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Purchase Sub Type</label>
                <div class="dropdown" id="purchaseSubTypeDropdownContainer">
                    <button class="form-select text-start bg-body text-body" type="button" id="purchaseSubTypeDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                        <span id="purchaseSubTypeSelectedText">Select Purchase Sub Type</span>
                    </button>
                    <input type="hidden" id="filterPurchaseSubType" name="filterPurchaseSubType">
                    <ul class="dropdown-menu w-100" id="purchaseSubTypeDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                        <li class="px-3 py-2">
                            <input type="text" class="form-control form-control-sm" id="purchaseSubTypeSearchInput" placeholder="Search purchase sub type..." autocomplete="off">
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li id="purchaseSubTypeDropdownItems">
                            <div class="px-3 py-2 text-muted text-center">Loading purchase sub types...</div>
                        </li>
                    </ul>
                </div>
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

        async function loadPurchaseTypes() {
            const typeHiddenInput = document.getElementById('filterPurchaseType');
            const typeDropdownItems = document.getElementById('purchaseTypeDropdownItems');
            const typeSelectedText = document.getElementById('purchaseTypeSelectedText');
            const typeSearchInput = document.getElementById('purchaseTypeSearchInput');

            if (!typeHiddenInput || !typeDropdownItems) return;

            typeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Loading purchase types...</div>';

            try {
                let types = [];
                if (window.procurementSharedCache && typeof window.procurementSharedCache.getPurchaseTypes === 'function') {
                    types = await window.procurementSharedCache.getPurchaseTypes();
                } else {
                    const endpoint = '/Procurement/Master/PurchaseTypes/List?isActive=true';
                    const data = await apiCall('Procurement', endpoint, 'GET', null, { cancelPrevious: false });
                    types = data.data || data;
                }

                if (!Array.isArray(types) || types.length === 0) {
                    typeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No purchase types found</div>';
                    return;
                }

                renderDropdownWithSearch({
                    items: types,
                    searchTerm: '',
                    dropdownItemsId: 'purchaseTypeDropdownItems',
                    hiddenInputId: 'filterPurchaseType',
                    selectedTextId: 'purchaseTypeSelectedText',
                    searchInputId: 'purchaseTypeSearchInput',
                    dropdownBtnId: 'purchaseTypeDropdownBtn',
                    getValue: (type) => (type.ID || type.id || '').toString(),
                    getText: (type) => {
                        const prType = type.PurchaseRequestType || type.purchaseRequestType || '';
                        const category = type.Category || type.category || '';
                        if (!category) return prType;
                        if (prType === category) return prType;
                        return `${prType} ${category}`;
                    },
                    getSearchableText: (type) => {
                        const prType = type.PurchaseRequestType || type.purchaseRequestType || '';
                        const category = type.Category || type.category || '';
                        if (!category) return prType;
                        if (prType === category) return prType;
                        return `${prType} ${category}`;
                    },
                    limit: 5,
                    onItemSelect: (item, value, text) => {
                        const subTypeHiddenInput = document.getElementById('filterPurchaseSubType');
                        const subTypeSelectedText = document.getElementById('purchaseSubTypeSelectedText');
                        if (subTypeHiddenInput) subTypeHiddenInput.value = '';
                        if (subTypeSelectedText) subTypeSelectedText.textContent = 'Select Purchase Sub Type';

                        const typeId = item.ID || item.id || '';
                        if (typeHiddenInput && typeId) {
                            typeHiddenInput.value = typeId.toString();
                        }

                        if (typeId) {
                            const typeIdInt = parseInt(typeId, 10);
                            loadPurchaseSubTypes(typeIdInt);
                        }
                    }
                });

                if (typeSearchInput) {
                    const newSearchInput = typeSearchInput.cloneNode(true);
                    typeSearchInput.parentNode.replaceChild(newSearchInput, typeSearchInput);

                    newSearchInput.addEventListener('input', (e) => {
                        const searchTerm = e.target.value.toLowerCase().trim();
                        renderDropdownWithSearch({
                            items: types,
                            searchTerm: searchTerm,
                            dropdownItemsId: 'purchaseTypeDropdownItems',
                            hiddenInputId: 'filterPurchaseType',
                            selectedTextId: 'purchaseTypeSelectedText',
                            searchInputId: 'purchaseTypeSearchInput',
                            dropdownBtnId: 'purchaseTypeDropdownBtn',
                            getValue: (type) => (type.ID || type.id || '').toString(),
                            getText: (type) => {
                                const prType = type.PurchaseRequestType || type.purchaseRequestType || '';
                                const category = type.Category || type.category || '';
                                if (!category) return prType;
                                if (prType === category) return prType;
                                return `${prType} ${category}`;
                            },
                            getSearchableText: (type) => {
                                const prType = type.PurchaseRequestType || type.purchaseRequestType || '';
                                const category = type.Category || type.category || '';
                                if (!category) return prType;
                                if (prType === category) return prType;
                                return `${prType} ${category}`;
                            },
                            limit: 5
                        });
                    });
                }
            } catch (error) {
                console.error('Error loading purchase types:', error);
                typeDropdownItems.innerHTML = '<div class="px-3 py-2 text-danger text-center">Error loading purchase types</div>';
            }
        }

        async function loadPurchaseSubTypes(typeId) {
            const subTypeHiddenInput = document.getElementById('filterPurchaseSubType');
            const subTypeDropdownItems = document.getElementById('purchaseSubTypeDropdownItems');
            const subTypeSelectedText = document.getElementById('purchaseSubTypeSelectedText');
            const subTypeSearchInput = document.getElementById('purchaseSubTypeSearchInput');

            if (!subTypeHiddenInput || !subTypeDropdownItems) return;

            subTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Loading purchase sub types...</div>';

            try {
                let subTypes = [];
                if (window.procurementSharedCache && typeof window.procurementSharedCache.getPurchaseSubTypes === 'function') {
                    subTypes = await window.procurementSharedCache.getPurchaseSubTypes(typeId);
                } else {
                    const endpoint = `/Procurement/Master/PurchaseTypes/SubTypes?mstPROPurchaseTypeID=${typeId}&isActive=true`;
                    const data = await apiCall('Procurement', endpoint, 'GET', null, { cancelPrevious: false });
                    subTypes = data.data || data;
                }

                if (!Array.isArray(subTypes) || subTypes.length === 0) {
                    subTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No purchase sub types found</div>';
                    return;
                }

                renderDropdownWithSearch({
                    items: subTypes,
                    searchTerm: '',
                    dropdownItemsId: 'purchaseSubTypeDropdownItems',
                    hiddenInputId: 'filterPurchaseSubType',
                    selectedTextId: 'purchaseSubTypeSelectedText',
                    searchInputId: 'purchaseSubTypeSearchInput',
                    dropdownBtnId: 'purchaseSubTypeDropdownBtn',
                    getValue: (subType) => (subType.ID || subType.id || '').toString(),
                    getText: (subType) => subType.PurchaseRequestSubType || subType.purchaseRequestSubType || '',
                    getSearchableText: (subType) => subType.PurchaseRequestSubType || subType.purchaseRequestSubType || '',
                    limit: 5,
                    onItemSelect: (item, value, text) => {
                        if (subTypeHiddenInput) {
                            subTypeHiddenInput.value = value;
                        }
                    }
                });

                if (subTypeSearchInput) {
                    const newSearchInput = subTypeSearchInput.cloneNode(true);
                    subTypeSearchInput.parentNode.replaceChild(newSearchInput, subTypeSearchInput);

                    newSearchInput.addEventListener('input', (e) => {
                        const searchTerm = e.target.value.toLowerCase().trim();
                        renderDropdownWithSearch({
                            items: subTypes,
                            searchTerm: searchTerm,
                            dropdownItemsId: 'purchaseSubTypeDropdownItems',
                            hiddenInputId: 'filterPurchaseSubType',
                            selectedTextId: 'purchaseSubTypeSelectedText',
                            searchInputId: 'purchaseSubTypeSearchInput',
                            dropdownBtnId: 'purchaseSubTypeDropdownBtn',
                            getValue: (subType) => (subType.ID || subType.id || '').toString(),
                            getText: (subType) => subType.PurchaseRequestSubType || subType.purchaseRequestSubType || '',
                            getSearchableText: (subType) => subType.PurchaseRequestSubType || subType.purchaseRequestSubType || '',
                            limit: 5
                        });
                    });
                }
            } catch (error) {
                console.error('Error loading purchase sub types:', error);
                subTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-danger text-center">Error loading purchase sub types</div>';
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
                const response = await apiCall('Procurement', '/Finance/EBilling/InvoiceWorkTypes?isActive=true', 'GET');
                const workTypes = Array.isArray(response) ? response : (response.data || response.Data || []);

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
                    getValue: (workType) => (workType.id || workType.ID || '').toString(),
                    getText: (workType) => workType.WorkType || workType.workType || '',
                    getSearchableText: (workType) => workType.WorkType || workType.workType || '',
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
                            getValue: (workType) => (workType.id || workType.ID || '').toString(),
                            getText: (workType) => workType.WorkType || workType.workType || '',
                            getSearchableText: (workType) => workType.WorkType || workType.workType || '',
                            limit: 5
                        });
                    });
                }
            } catch (error) {
                console.error('Error loading work types:', error);
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
                console.error('Error loading statuses:', error);
                statusDropdownItems.innerHTML = '<div class="px-3 py-2 text-danger text-center">Error loading statuses</div>';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            setDefaultDateRange();
            loadPurchaseTypes();
            loadWorkTypes();
            loadStatuses();
        });
    })();
</script>
