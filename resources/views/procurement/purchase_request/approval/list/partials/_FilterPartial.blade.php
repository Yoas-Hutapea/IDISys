{{--
    Filter Partial for Purchase Request Approval List
    Contains all filter form fields as shown in the image
    Supports dark mode with proper Bootstrap classes
--}}

<form id="filterForm" class="dark-mode-compatible">
    <div class="row g-3">
        <!-- Left Column -->
        <div class="col-md-6">
            <!-- Request Period PR -->
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Request Period PR</label>
                <div class="row g-2">
                    <div class="col-6">
                        <input type="date" class="form-control bg-body text-body" id="startDate" name="startDate" placeholder="Start Date">
                    </div>
                    <div class="col-1 d-flex align-items-center justify-content-center">
                        <span class="text-muted">to</span>
                    </div>
                    <div class="col-5">
                        <input type="date" class="form-control bg-body text-body" id="endDate" name="endDate" placeholder="End Date">
                    </div>
                </div>
            </div>

            <!-- Purchase Request Number -->
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Purchase Request Number</label>
                <input type="text" class="form-control bg-body text-body" id="prNumber" name="prNumber" placeholder="PR Number">
            </div>

            <!-- Purchase Request Type -->
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Purchase Request Type</label>
                <div class="dropdown" id="prTypeDropdownContainer">
                    <button class="form-select text-start bg-body text-body" type="button" id="prTypeDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                        <span id="prTypeSelectedText">Select Purchase Request Type</span>
                    </button>
                    <input type="hidden" id="prType" name="prType">
                    <ul class="dropdown-menu w-100" id="prTypeDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                        <li class="px-3 py-2">
                            <input type="text" class="form-control form-control-sm" id="prTypeSearchInput" placeholder="Search purchase request type..." autocomplete="off">
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li id="prTypeDropdownItems">
                            <div class="px-3 py-2 text-muted text-center">Loading purchase request types...</div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Purchase Request Sub Type -->
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Purchase Request Sub Type</label>
                <div class="dropdown" id="prSubTypeDropdownContainer">
                    <button class="form-select text-start bg-body text-body" type="button" id="prSubTypeDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                        <span id="prSubTypeSelectedText">Select Purchase Request Sub Type</span>
                    </button>
                    <input type="hidden" id="prSubType" name="prSubType">
                    <ul class="dropdown-menu w-100" id="prSubTypeDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                        <li class="px-3 py-2">
                            <input type="text" class="form-control form-control-sm" id="prSubTypeSearchInput" placeholder="Search purchase request sub type..." autocomplete="off">
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li id="prSubTypeDropdownItems">
                            <div class="px-3 py-2 text-muted text-center">Loading purchase request sub types...</div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-md-6">
            <!-- Purchase Request Name -->
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Purchase Request Name</label>
                <input type="text" class="form-control bg-body text-body" id="prName" name="prName" placeholder="Purchase Request Name">
            </div>

            <!-- Status PR -->
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Status PR</label>
                <div class="dropdown" id="statusPRDropdownContainer">
                    <button class="form-select text-start bg-body text-body" type="button" id="statusPRDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                        <span id="statusPRSelectedText">Select Status</span>
                    </button>
                    <input type="hidden" id="statusPR" name="statusPR">
                    <ul class="dropdown-menu w-100" id="statusPRDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                        <li class="px-3 py-2">
                            <input type="text" class="form-control form-control-sm" id="statusPRSearchInput" placeholder="Search status..." autocomplete="off">
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li id="statusPRDropdownItems">
                            <div class="px-3 py-2 text-muted text-center">Loading statuses...</div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Region -->
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Region</label>
                <div class="dropdown" id="regionDropdownContainer">
                    <button class="form-select text-start bg-body text-body" type="button" id="regionDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                        <span id="regionSelectedText">Select Region</span>
                    </button>
                    <input type="hidden" id="region" name="region">
                    <ul class="dropdown-menu w-100" id="regionDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                        <li class="px-3 py-2">
                            <input type="text" class="form-control form-control-sm" id="regionSearchInput" placeholder="Search region..." autocomplete="off">
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li id="regionDropdownItems">
                            <div class="px-3 py-2 text-muted text-center">Loading regions...</div>
                        </li>
                    </ul>
            </div>
            </div>
        </div>
    </div>

    <!-- Filter Action Buttons -->
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-center gap-3 mt-4">
                <button type="button" class="btn btn-primary" onclick="searchApproval()">
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
        
        // Set default date range to current month
        // Auto fill: 01/MM/YYYY to last day of MM/YYYY
        function setDefaultDateRange() {
            const now = new Date();
            const currentYear = now.getFullYear();
            const currentMonth = now.getMonth(); // 0-based (0 = January, 11 = December)
            
            // First day of current month: 01/MM/YYYY
            const firstDay = new Date(currentYear, currentMonth, 1);
            
            // Last day of current month: last day/MM/YYYY
            // Using new Date(year, month + 1, 0) gets the last day of the current month
            const lastDay = new Date(currentYear, currentMonth + 1, 0);
            
            // Format to YYYY-MM-DD for input type="date"
            const formatDate = (date) => {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            };
            
            const startDateInput = document.getElementById('startDate');
            const endDateInput = document.getElementById('endDate');
            
            // Always set the date range to current month (auto fill)
            if (startDateInput) {
                startDateInput.value = formatDate(firstDay);
            }
            if (endDateInput) {
                endDateInput.value = formatDate(lastDay);
            }
        }

        // Load Purchase Request Types from API
        async function loadPurchaseRequestTypes() {
            const typeHiddenInput = document.getElementById('prType');
            const typeDropdownItems = document.getElementById('prTypeDropdownItems');
            const typeSelectedText = document.getElementById('prTypeSelectedText');
            const typeSearchInput = document.getElementById('prTypeSearchInput');
            
            if (!typeHiddenInput || !typeDropdownItems) return;

            typeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Loading purchase request types...</div>';

            try {
                // Use shared cache to prevent duplicate API calls and cancellation issues
                let types = [];
                if (window.procurementSharedCache && typeof window.procurementSharedCache.getPurchaseTypes === 'function') {
                    types = await window.procurementSharedCache.getPurchaseTypes();
                } else {
                    // Fallback to direct API call if shared cache is not available
                    const endpoint = '/Procurement/Master/PurchaseTypes/List?isActive=true';
                    const data = await apiCall('Procurement', endpoint, 'GET', null, { cancelPrevious: false });
                    types = data.data || data;
                }

                if (!Array.isArray(types) || types.length === 0) {
                    typeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No purchase request types found</div>';
                    return;
                }

                renderDropdownWithSearch({
                    items: types,
                    searchTerm: '',
                    dropdownItemsId: 'prTypeDropdownItems',
                    hiddenInputId: 'prType',
                    selectedTextId: 'prTypeSelectedText',
                    searchInputId: 'prTypeSearchInput',
                    dropdownBtnId: 'prTypeDropdownBtn',
                    getValue: (type) => (type.ID || type.id || '').toString(), // Return ID, not string value
                    getText: (type) => {
                        const prType = type.PurchaseRequestType || type.purchaseRequestType || '';
                        const category = type.Category || type.category || '';
                        if (!category) return prType;
                        // Jika PurchaseRequestType dan Category sama, cukup tampilkan salah satu
                        if (prType === category) return prType;
                        return `${prType} ${category}`;
                    },
                    getSearchableText: (type) => {
                        const prType = type.PurchaseRequestType || type.purchaseRequestType || '';
                        const category = type.Category || type.category || '';
                        if (!category) return prType;
                        // Jika PurchaseRequestType dan Category sama, cukup tampilkan salah satu
                        if (prType === category) return prType;
                        return `${prType} ${category}`;
                    },
                    limit: 5,
                    onItemSelect: (item, value, text) => {
                        // Reset sub type dropdown first
                        const subTypeHiddenInput = document.getElementById('prSubType');
                        const subTypeSelectedText = document.getElementById('prSubTypeSelectedText');
                        if (subTypeHiddenInput) subTypeHiddenInput.value = '';
                        if (subTypeSelectedText) subTypeSelectedText.textContent = 'Select Purchase Request Sub Type';
                        
                        // Store Type ID in hidden field (value already set by getValue, but ensure it's ID)
                        const typeHiddenInput = document.getElementById('prType');
                        const typeId = item.ID || item.id || '';
                        if (typeHiddenInput && typeId) {
                            typeHiddenInput.value = typeId.toString();
                        }
                        
                        // Load subtypes when type is selected
                        if (typeId) {
                            const typeIdInt = parseInt(typeId, 10);
                            loadPurchaseRequestSubTypes(typeIdInt);
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
                            dropdownItemsId: 'prTypeDropdownItems',
                            hiddenInputId: 'prType',
                            selectedTextId: 'prTypeSelectedText',
                            searchInputId: 'prTypeSearchInput',
                            dropdownBtnId: 'prTypeDropdownBtn',
                            getValue: (type) => (type.ID || type.id || '').toString(), // Return ID, not string value
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
                                // Reset sub type dropdown first
                                const subTypeHiddenInput = document.getElementById('prSubType');
                                const subTypeSelectedText = document.getElementById('prSubTypeSelectedText');
                                if (subTypeHiddenInput) subTypeHiddenInput.value = '';
                                if (subTypeSelectedText) subTypeSelectedText.textContent = 'Select Purchase Request Sub Type';
                                
                                // Store Type ID in hidden field (value already set by getValue, but ensure it's ID)
                                const typeHiddenInput = document.getElementById('prType');
                                const typeId = item.ID || item.id || '';
                                if (typeHiddenInput && typeId) {
                                    typeHiddenInput.value = typeId.toString();
                                }
                                
                                // Load subtypes when type is selected
                                if (typeId) {
                                    const typeIdInt = parseInt(typeId, 10);
                                    loadPurchaseRequestSubTypes(typeIdInt);
                                }
                            }
                        });
                    });

                    newSearchInput.addEventListener('click', (e) => {
                        e.stopPropagation();
                    });
                }

                const typeDropdownBtn = document.getElementById('prTypeDropdownBtn');
                if (typeDropdownBtn) {
                    typeDropdownBtn.addEventListener('shown.bs.dropdown', () => {
                        const searchInput = document.getElementById('prTypeSearchInput');
                        if (searchInput) {
                            setTimeout(() => {
                                searchInput.focus();
                            }, 100);
                        }
                    });
                }
            } catch (error) {
                typeDropdownItems.innerHTML = '<div class="px-3 py-2 text-danger text-center">Error loading purchase request types</div>';
            }
        }

        // Load Purchase Request Sub Types from API (filtered by Type if mstPROPurchaseTypeID is provided)
        async function loadPurchaseRequestSubTypes(mstPROPurchaseTypeID = null) {
            const subTypeHiddenInput = document.getElementById('prSubType');
            const subTypeDropdownItems = document.getElementById('prSubTypeDropdownItems');
            const subTypeSelectedText = document.getElementById('prSubTypeSelectedText');
            const subTypeSearchInput = document.getElementById('prSubTypeSearchInput');
            
            if (!subTypeHiddenInput || !subTypeDropdownItems) return;

            // Reset sub type dropdown first
            if (subTypeHiddenInput) subTypeHiddenInput.value = '';
            if (subTypeSelectedText) subTypeSelectedText.textContent = 'Select Purchase Request Sub Type';

            subTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Loading purchase request sub types...</div>';

            try {
                // Build endpoint with optional mstPROPurchaseTypeID filter
                let endpoint = '/Procurement/Master/PurchaseTypes/SubTypes?isActive=true';
                if (mstPROPurchaseTypeID && mstPROPurchaseTypeID > 0) {
                    endpoint += `&mstPROPurchaseTypeID=${encodeURIComponent(mstPROPurchaseTypeID)}`;
                }
                
                const data = await apiCall('Procurement', endpoint, 'GET');
                const subTypes = Array.isArray(data) ? data : (data.data || []);

                if (!Array.isArray(subTypes) || subTypes.length === 0) {
                    subTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No purchase request sub types found</div>';
                    // Clear selected value
                    if (subTypeHiddenInput) subTypeHiddenInput.value = '';
                    if (subTypeSelectedText) subTypeSelectedText.textContent = 'Select Purchase Request Sub Type';
                    return;
                }

                renderDropdownWithSearch({
                    items: subTypes,
                    searchTerm: '',
                    dropdownItemsId: 'prSubTypeDropdownItems',
                    hiddenInputId: 'prSubType',
                    selectedTextId: 'prSubTypeSelectedText',
                    searchInputId: 'prSubTypeSearchInput',
                    dropdownBtnId: 'prSubTypeDropdownBtn',
                    getValue: (subType) => (subType.ID || subType.id || '').toString(), // Return ID, not string value
                    getText: (subType) => subType.PurchaseRequestSubType || subType.purchaseRequestSubType || '',
                    getSearchableText: (subType) => (subType.PurchaseRequestSubType || subType.purchaseRequestSubType || '').toString(),
                    limit: 5,
                    onItemSelect: (item, value, text) => {
                        // Store Sub Type ID in hidden field
                        const subTypeHiddenInput = document.getElementById('prSubType');
                        const subTypeId = item.ID || item.id || '';
                        if (subTypeHiddenInput && subTypeId) {
                            subTypeHiddenInput.value = subTypeId.toString();
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
                            dropdownItemsId: 'prSubTypeDropdownItems',
                            hiddenInputId: 'prSubType',
                            selectedTextId: 'prSubTypeSelectedText',
                            searchInputId: 'prSubTypeSearchInput',
                            dropdownBtnId: 'prSubTypeDropdownBtn',
                            getValue: (subType) => (subType.ID || subType.id || '').toString(), // Return ID, not string value
                            getText: (subType) => subType.PurchaseRequestSubType || subType.purchaseRequestSubType || '',
                            getSearchableText: (subType) => (subType.PurchaseRequestSubType || subType.purchaseRequestSubType || '').toString(),
                            limit: 5,
                            onItemSelect: (item, value, text) => {
                                // Store Sub Type ID in hidden field
                                const subTypeHiddenInput = document.getElementById('prSubType');
                                const subTypeId = item.ID || item.id || '';
                                if (subTypeHiddenInput && subTypeId) {
                                    subTypeHiddenInput.value = subTypeId.toString();
                                }
                            }
                        });
                    });

                    newSearchInput.addEventListener('click', (e) => {
                        e.stopPropagation();
                    });
                }

                const subTypeDropdownBtn = document.getElementById('prSubTypeDropdownBtn');
                if (subTypeDropdownBtn) {
                    subTypeDropdownBtn.addEventListener('shown.bs.dropdown', () => {
                        const searchInput = document.getElementById('prSubTypeSearchInput');
                        if (searchInput) {
                            setTimeout(() => {
                                searchInput.focus();
                            }, 100);
                        }
                    });
                }
            } catch (error) {
                subTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-danger text-center">Error loading purchase request sub types</div>';
            }
        }

        // Load Status PR (IDs: 1,2,3,4,5,6,7,8) with dropdown search
        async function loadStatusPR() {
            const statusHiddenInput = document.getElementById('statusPR');
            const statusDropdownItems = document.getElementById('statusPRDropdownItems');
            const statusSelectedText = document.getElementById('statusPRSelectedText');
            const statusSearchInput = document.getElementById('statusPRSearchInput');
            
            if (!statusHiddenInput || !statusDropdownItems) return;

            statusDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Loading statuses...</div>';

            try {
                const endpoint = '/Procurement/Master/ApprovalStatuses?isActive=true';
                const data = await apiCall('Procurement', endpoint, 'GET');
                const statuses = data.data || data;

                if (!Array.isArray(statuses)) {
                    statusDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No statuses found</div>';
                    return;
                }

                // Filter statuses with ID 1-8
                const prStatuses = statuses.filter(s => {
                    const id = s.ID || s.id || 0;
                    return id >= 1 && id <= 8;
                });

                if (prStatuses.length === 0) {
                    statusDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No statuses found</div>';
                    return;
                }

                renderDropdownWithSearch({
                    items: prStatuses,
                    searchTerm: '',
                    dropdownItemsId: 'statusPRDropdownItems',
                    hiddenInputId: 'statusPR',
                    selectedTextId: 'statusPRSelectedText',
                    searchInputId: 'statusPRSearchInput',
                    dropdownBtnId: 'statusPRDropdownBtn',
                    getValue: (status) => (status.ID || status.id || '').toString(),
                    getText: (status) => status.ApprovalStatus || status.approvalStatus || '',
                    getSearchableText: (status) => (status.ApprovalStatus || status.approvalStatus || '').toString(),
                    limit: 5
                });

                if (statusSearchInput) {
                    const newSearchInput = statusSearchInput.cloneNode(true);
                    statusSearchInput.parentNode.replaceChild(newSearchInput, statusSearchInput);
                    
                    newSearchInput.addEventListener('input', (e) => {
                        const searchTerm = e.target.value.toLowerCase().trim();
                        renderDropdownWithSearch({
                            items: prStatuses,
                            searchTerm: searchTerm,
                            dropdownItemsId: 'statusPRDropdownItems',
                            hiddenInputId: 'statusPR',
                            selectedTextId: 'statusPRSelectedText',
                            searchInputId: 'statusPRSearchInput',
                            dropdownBtnId: 'statusPRDropdownBtn',
                            getValue: (status) => (status.ID || status.id || '').toString(),
                            getText: (status) => status.ApprovalStatus || status.approvalStatus || '',
                            getSearchableText: (status) => (status.ApprovalStatus || status.approvalStatus || '').toString(),
                            limit: 5
                        });
                    });

                    newSearchInput.addEventListener('click', (e) => {
                        e.stopPropagation();
                    });
                }

                const statusDropdownBtn = document.getElementById('statusPRDropdownBtn');
                if (statusDropdownBtn) {
                    statusDropdownBtn.addEventListener('shown.bs.dropdown', () => {
                        const searchInput = document.getElementById('statusPRSearchInput');
                        if (searchInput) {
                            setTimeout(() => {
                                searchInput.focus();
                            }, 100);
                        }
                    });
                }
            } catch (error) {
                statusDropdownItems.innerHTML = '<div class="px-3 py-2 text-danger text-center">Error loading statuses</div>';
            }
        }

        // Load Region from API
        async function loadRegions() {
            const regionHiddenInput = document.getElementById('region');
            const regionDropdownItems = document.getElementById('regionDropdownItems');
            const regionSelectedText = document.getElementById('regionSelectedText');
            const regionSearchInput = document.getElementById('regionSearchInput');
            
            if (!regionHiddenInput || !regionDropdownItems) return;

            regionDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Loading regions...</div>';

            try {
                const endpoint = '/Procurement/Master/Regions?isActive=true';
                const data = await apiCall('Procurement', endpoint, 'GET');
                const regions = data.data || data;

                if (!Array.isArray(regions) || regions.length === 0) {
                    regionDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No regions found</div>';
                    return;
                }

                renderDropdownWithSearch({
                    items: regions,
                    searchTerm: '',
                    dropdownItemsId: 'regionDropdownItems',
                    hiddenInputId: 'region',
                    selectedTextId: 'regionSelectedText',
                    searchInputId: 'regionSearchInput',
                    dropdownBtnId: 'regionDropdownBtn',
                    getValue: (region) => (region.RegionID || region.regionID || '').toString(),
                    getText: (region) => region.RegionName || region.regionName || '',
                    getSearchableText: (region) => (region.RegionName || region.regionName || '').toString(),
                    limit: 5
                });

                if (regionSearchInput) {
                    const newSearchInput = regionSearchInput.cloneNode(true);
                    regionSearchInput.parentNode.replaceChild(newSearchInput, regionSearchInput);
                    
                    newSearchInput.addEventListener('input', (e) => {
                        const searchTerm = e.target.value.toLowerCase().trim();
                        renderDropdownWithSearch({
                            items: regions,
                            searchTerm: searchTerm,
                            dropdownItemsId: 'regionDropdownItems',
                            hiddenInputId: 'region',
                            selectedTextId: 'regionSelectedText',
                            searchInputId: 'regionSearchInput',
                            dropdownBtnId: 'regionDropdownBtn',
                            getValue: (region) => (region.RegionID || region.regionID || '').toString(),
                            getText: (region) => region.RegionName || region.regionName || '',
                            getSearchableText: (region) => (region.RegionName || region.regionName || '').toString(),
                            limit: 5
                        });
                    });

                    newSearchInput.addEventListener('click', (e) => {
                        e.stopPropagation();
                    });
                }

                const regionDropdownBtn = document.getElementById('regionDropdownBtn');
                if (regionDropdownBtn) {
                    regionDropdownBtn.addEventListener('shown.bs.dropdown', () => {
                        const searchInput = document.getElementById('regionSearchInput');
                        if (searchInput) {
                            setTimeout(() => {
                                searchInput.focus();
                            }, 100);
                        }
                    });
                }
            } catch (error) {
                regionDropdownItems.innerHTML = '<div class="px-3 py-2 text-danger text-center">Error loading regions</div>';
            }
        }

        // Helper function to render dropdown with search
        function renderDropdownWithSearch(config) {
            const {
                items,
                searchTerm = '',
                dropdownItemsId,
                hiddenInputId,
                selectedTextId,
                searchInputId,
                dropdownBtnId,
                getValue,
                getText,
                getSearchableText,
                limit = 5,
                onItemSelect = null
            } = config;

            const dropdownItems = document.getElementById(dropdownItemsId);
            const hiddenInput = document.getElementById(hiddenInputId);
            const selectedText = document.getElementById(selectedTextId);
            
            if (!dropdownItems) return;

            let filteredItems = items;
            if (searchTerm) {
                filteredItems = items.filter(item => {
                    const searchableText = getSearchableText ? getSearchableText(item) : getText(item);
                    return searchableText.toLowerCase().includes(searchTerm.toLowerCase());
                });
            }

            dropdownItems.innerHTML = '';

            if (filteredItems.length === 0) {
                dropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No items found</div>';
                return;
            }

            // Display all items - scroll bar will appear automatically if more than 5 items
            // The dropdown menu already has max-height: 300px; overflow-y: auto; in the HTML
            filteredItems.forEach(item => {
                const value = getValue(item);
                const text = getText(item);
                
                const li = document.createElement('li');
                li.className = 'dropdown-item';
                li.style.cursor = 'pointer';
                li.textContent = text;
                
                li.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    if (hiddenInput) hiddenInput.value = value;
                    if (selectedText) selectedText.textContent = text;
                    
                    const searchInput = document.getElementById(searchInputId);
                    if (searchInput) {
                        searchInput.value = '';
                    }
                    
                    renderDropdownWithSearch({
                        ...config,
                        searchTerm: ''
                    });
                    
                    const dropdown = bootstrap.Dropdown.getInstance(document.getElementById(dropdownBtnId));
                    if (dropdown) {
                        dropdown.hide();
                    }
                    
                    if (hiddenInput) {
                        hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    if (onItemSelect) {
                        onItemSelect(item, value, text);
                    }
                });
                
                dropdownItems.appendChild(li);
            });
        }

        // Reset Filter function
        window.resetFilter = function() {
            const form = document.getElementById('filterForm');
            if (!form) return;

            // Reset date inputs to current month
            // Use the same logic as setDefaultDateRange()
            const now = new Date();
            const currentYear = now.getFullYear();
            const currentMonth = now.getMonth();
            
            const firstDay = new Date(currentYear, currentMonth, 1);
            const lastDay = new Date(currentYear, currentMonth + 1, 0);
            
            // Format to YYYY-MM-DD for input type="date"
            const formatDate = (date) => {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            };
            
            const startDateInput = document.getElementById('startDate');
            const endDateInput = document.getElementById('endDate');
            if (startDateInput) startDateInput.value = formatDate(firstDay);
            if (endDateInput) endDateInput.value = formatDate(lastDay);

            // Reset text inputs
            const prNumberInput = document.getElementById('prNumber');
            const prNameInput = document.getElementById('prName');
            if (prNumberInput) prNumberInput.value = '';
            if (prNameInput) prNameInput.value = '';

            // Reset dropdown with search (hidden inputs and selected text)
            const dropdownFields = [
                { hiddenId: 'prType', textId: 'prTypeSelectedText', defaultText: 'Select Purchase Request Type', searchId: 'prTypeSearchInput' },
                { hiddenId: 'prSubType', textId: 'prSubTypeSelectedText', defaultText: 'Select Purchase Request Sub Type', searchId: 'prSubTypeSearchInput' },
                { hiddenId: 'statusPR', textId: 'statusPRSelectedText', defaultText: 'Select Status', searchId: 'statusPRSearchInput' },
                { hiddenId: 'region', textId: 'regionSelectedText', defaultText: 'Select Region', searchId: 'regionSearchInput' }
            ];

            dropdownFields.forEach(field => {
                const hiddenInput = document.getElementById(field.hiddenId);
                const selectedText = document.getElementById(field.textId);
                const searchInput = document.getElementById(field.searchId);
                
                if (hiddenInput) hiddenInput.value = '';
                if (selectedText) selectedText.textContent = field.defaultText;
                if (searchInput) searchInput.value = '';
            });

            // Close any open dropdowns
            const dropdowns = document.querySelectorAll('.dropdown-menu.show');
            dropdowns.forEach(dropdown => {
                const dropdownInstance = bootstrap.Dropdown.getInstance(dropdown.previousElementSibling);
                if (dropdownInstance) {
                    dropdownInstance.hide();
                }
            });
        };

        // Initialize on page load
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setDefaultDateRange();
                loadPurchaseRequestTypes();
                    // Don't load sub types on initial load - wait for type selection
                loadStatusPR();
                    loadRegions();
            });
        } else {
            setDefaultDateRange();
            loadPurchaseRequestTypes();
                // Don't load sub types on initial load - wait for type selection
            loadStatusPR();
                loadRegions();
        }
    })();
</script>

