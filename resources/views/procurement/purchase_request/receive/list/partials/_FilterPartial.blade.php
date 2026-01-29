{{--
    Filter Partial for Purchase Request Receive List
    Contains all filter form fields
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
                            <div class="px-3 py-2 text-muted text-center">Select Purchase Request Sub Type</div>
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

            <!-- Company -->
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Company</label>
                <div class="dropdown" id="companyDropdownContainer">
                    <button class="form-select text-start bg-body text-body" type="button" id="companyDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                        <span id="companySelectedText">Select Company</span>
                    </button>
                    <input type="hidden" id="company" name="company">
                    <ul class="dropdown-menu w-100" id="companyDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                        <li class="px-3 py-2">
                            <input type="text" class="form-control form-control-sm" id="companySearchInput" placeholder="Search company..." autocomplete="off">
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li id="companyDropdownItems">
                            <div class="px-3 py-2 text-muted text-center">Loading companies...</div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Status PR -->
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Status PR</label>
                <select class="form-select bg-body text-body" id="statusPR" name="statusPR">
                    <option value="" disabled selected>Select Status</option>
                </select>
            </div>

            <!-- Regional -->
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Regional</label>
                <select class="form-select bg-body text-body" id="regional" name="regional">
                    <option value="" disabled selected>Select Regional</option>
                    <option value="Jakarta">Jakarta</option>
                    <option value="Surabaya">Surabaya</option>
                    <option value="Bandung">Bandung</option>
                    <option value="Medan">Medan</option>
                    <option value="Semarang">Semarang</option>
                    <option value="Makassar">Makassar</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Filter Action Buttons -->
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-center gap-3 mt-4">
                <button type="button" class="btn btn-primary" onclick="searchReceive()">
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
                        // Store Type ID in hidden field
                        const typeHiddenInput = document.getElementById('prType');
                        const typeId = item.ID || item.id || '';
                        if (typeHiddenInput && typeId) {
                            typeHiddenInput.value = typeId.toString();
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
                                // Store Type ID in hidden field
                                const typeHiddenInput = document.getElementById('prType');
                                const typeId = item.ID || item.id || '';
                                if (typeHiddenInput && typeId) {
                                    typeHiddenInput.value = typeId.toString();
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

        // Load Purchase Request Sub Types from API (load all, not filtered by Type)
        async function loadPurchaseRequestSubTypes() {
            const subTypeHiddenInput = document.getElementById('prSubType');
            const subTypeDropdownItems = document.getElementById('prSubTypeDropdownItems');
            const subTypeSelectedText = document.getElementById('prSubTypeSelectedText');
            const subTypeSearchInput = document.getElementById('prSubTypeSearchInput');
            
            if (!subTypeHiddenInput || !subTypeDropdownItems) return;

            subTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Loading purchase request sub types...</div>';

            try {
                const endpoint = '/Procurement/Master/PurchaseTypes/SubTypes?isActive=true';
                const data = await apiCall('Procurement', endpoint, 'GET');
                const subTypes = Array.isArray(data) ? data : (data.data || []);

                if (!Array.isArray(subTypes) || subTypes.length === 0) {
                    subTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No purchase request sub types found</div>';
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
                            limit: 5
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

        // Load Company from API
        async function loadCompanies() {
            const companyHiddenInput = document.getElementById('company');
            const companyDropdownItems = document.getElementById('companyDropdownItems');
            const companySelectedText = document.getElementById('companySelectedText');
            const companySearchInput = document.getElementById('companySearchInput');
            
            if (!companyHiddenInput || !companyDropdownItems) return;

            companyDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Loading companies...</div>';

            try {
                const endpoint = '/Procurement/Master/Companies?isActive=true';
                const data = await apiCall('Procurement', endpoint, 'GET');
                const companies = data.data || data;

                if (!Array.isArray(companies) || companies.length === 0) {
                    companyDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No companies found</div>';
                    return;
                }

                renderDropdownWithSearch({
                    items: companies,
                    searchTerm: '',
                    dropdownItemsId: 'companyDropdownItems',
                    hiddenInputId: 'company',
                    selectedTextId: 'companySelectedText',
                    searchInputId: 'companySearchInput',
                    dropdownBtnId: 'companyDropdownBtn',
                    getValue: (company) => company.CompanyID || company.companyID || company.Company || company.company || '',
                    getText: (company) => company.Company || company.company || company.CompanyID || company.companyID || '',
                    getSearchableText: (company) => (company.Company || company.company || company.CompanyID || company.companyID || '').toString(),
                    limit: 5
                });

                if (companySearchInput) {
                    const newSearchInput = companySearchInput.cloneNode(true);
                    companySearchInput.parentNode.replaceChild(newSearchInput, companySearchInput);
                    
                    newSearchInput.addEventListener('input', (e) => {
                        const searchTerm = e.target.value.toLowerCase().trim();
                        renderDropdownWithSearch({
                            items: companies,
                            searchTerm: searchTerm,
                            dropdownItemsId: 'companyDropdownItems',
                            hiddenInputId: 'company',
                            selectedTextId: 'companySelectedText',
                            searchInputId: 'companySearchInput',
                            dropdownBtnId: 'companyDropdownBtn',
                            getValue: (company) => company.CompanyID || company.companyID || company.Company || company.company || '',
                            getText: (company) => company.Company || company.company || company.CompanyID || company.companyID || '',
                            getSearchableText: (company) => (company.Company || company.company || company.CompanyID || company.companyID || '').toString(),
                            limit: 5
                        });
                    });

                    newSearchInput.addEventListener('click', (e) => {
                        e.stopPropagation();
                    });
                }

                const companyDropdownBtn = document.getElementById('companyDropdownBtn');
                if (companyDropdownBtn) {
                    companyDropdownBtn.addEventListener('shown.bs.dropdown', () => {
                        const searchInput = document.getElementById('companySearchInput');
                        if (searchInput) {
                            setTimeout(() => {
                                searchInput.focus();
                            }, 100);
                        }
                    });
                }
            } catch (error) {
                companyDropdownItems.innerHTML = '<div class="px-3 py-2 text-danger text-center">Error loading companies</div>';
            }
        }

        // Load Status PR (IDs: 1,2,3,4,5,6,7,8)
        async function loadStatusPR() {
            const statusSelect = document.getElementById('statusPR');
            if (!statusSelect) return;

            try {
                const endpoint = '/Procurement/Master/ApprovalStatuses?isActive=true';
                const data = await apiCall('Procurement', endpoint, 'GET');
                const statuses = data.data || data;

                if (!Array.isArray(statuses)) {
                    return;
                }

                // Filter statuses with ID 1-8
                const prStatuses = statuses.filter(s => {
                    const id = s.ID || s.id || 0;
                    return id >= 1 && id <= 8;
                });

                // Clear existing options except the first one
                statusSelect.innerHTML = '<option value="" disabled selected>Select Status</option>';

                // Add filtered statuses
                prStatuses.forEach(status => {
                    const option = document.createElement('option');
                    option.value = status.ID || status.id || '';
                    option.textContent = status.ApprovalStatus || status.approvalStatus || '';
                    statusSelect.appendChild(option);
                });
            } catch (error) {
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
                { hiddenId: 'company', textId: 'companySelectedText', defaultText: 'Select Company', searchId: 'companySearchInput' }
            ];

            dropdownFields.forEach(field => {
                const hiddenInput = document.getElementById(field.hiddenId);
                const selectedText = document.getElementById(field.textId);
                const searchInput = document.getElementById(field.searchId);
                
                if (hiddenInput) hiddenInput.value = '';
                if (selectedText) selectedText.textContent = field.defaultText;
                if (searchInput) searchInput.value = '';
            });

            // Reset select dropdowns
            const statusSelect = document.getElementById('statusPR');
            const regionalSelect = document.getElementById('regional');
            if (statusSelect) statusSelect.value = '';
            if (regionalSelect) regionalSelect.value = '';

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
                loadPurchaseRequestSubTypes();
                loadCompanies();
                loadStatusPR();
            });
        } else {
            setDefaultDateRange();
            loadPurchaseRequestTypes();
            loadPurchaseRequestSubTypes();
            loadCompanies();
            loadStatusPR();
        }
    })();
</script>

