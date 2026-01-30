{{-- Filter Partial for Purchase Order List --}}
<form id="filterForm" class="dark-mode-compatible">
    <div class="row g-3">
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">PO Period</label>
                <div class="row g-2">
                    <div class="col-6">
                        <input type="date" class="form-control bg-body text-body" id="poStartDate" name="poStartDate" placeholder="Start Date">
                    </div>
                    <div class="col-1 d-flex align-items-center justify-content-center">
                        <span class="text-muted">to</span>
                    </div>
                    <div class="col-5">
                        <input type="date" class="form-control bg-body text-body" id="poEndDate" name="poEndDate" placeholder="End Date">
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Purchase Order Number</label>
                <input type="text" class="form-control bg-body text-body" id="poNumber" name="poNumber" placeholder="PO Number">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Purchase Type</label>
                <div class="dropdown" id="purchTypeDropdownContainer">
                    <button class="form-select text-start bg-body text-body" type="button" id="purchTypeDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                        <span id="purchTypeSelectedText">Select Purchase Type</span>
                    </button>
                    <input type="hidden" id="purchType" name="purchType">
                    <ul class="dropdown-menu w-100" id="purchTypeDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                        <li class="px-3 py-2">
                            <input type="text" class="form-control form-control-sm" id="purchTypeSearchInput" placeholder="Search purchase type..." autocomplete="off">
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li id="purchTypeDropdownItems">
                            <div class="px-3 py-2 text-muted text-center">Loading purchase types...</div>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Purchase Sub Type</label>
                <div class="dropdown" id="purchSubTypeDropdownContainer">
                    <button class="form-select text-start bg-body text-body" type="button" id="purchSubTypeDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                        <span id="purchSubTypeSelectedText">Select Purchase Sub Type</span>
                    </button>
                    <input type="hidden" id="purchSubType" name="purchSubType">
                    <ul class="dropdown-menu w-100" id="purchSubTypeDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                        <li class="px-3 py-2">
                            <input type="text" class="form-control form-control-sm" id="purchSubTypeSearchInput" placeholder="Search purchase sub type..." autocomplete="off">
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li id="purchSubTypeDropdownItems">
                            <div class="px-3 py-2 text-muted text-center">Loading purchase sub types...</div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Purchase Name</label>
                <input type="text" class="form-control bg-body text-body" id="purchName" name="purchName" placeholder="Purchase Name">
            </div>

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

            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Vendor</label>
                <div class="dropdown" id="vendorDropdownContainer">
                    <button class="form-select text-start bg-body text-body" type="button" id="vendorDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                        <span id="vendorSelectedText">Select Vendor</span>
                    </button>
                    <input type="hidden" id="vendorName" name="vendorName">
                    <ul class="dropdown-menu w-100" id="vendorDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                        <li class="px-3 py-2">
                            <input type="text" class="form-control form-control-sm" id="vendorSearchInput" placeholder="Search vendor..." autocomplete="off">
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li id="vendorDropdownItems">
                            <div class="px-3 py-2 text-muted text-center">Loading vendors...</div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="d-flex justify-content-center gap-3">
                <button type="button" class="btn btn-primary" onclick="poListManager.search()">
                    <i class="icon-base bx bx-search me-2"></i>
                    Search
                </button>
                <button type="button" class="btn btn-secondary" onclick="poListManager.resetFilter()">
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

            const poStartDateInput = document.getElementById('poStartDate');
            const poEndDateInput = document.getElementById('poEndDate');

            if (poStartDateInput) {
                poStartDateInput.value = formatDate(firstDay);
            }
            if (poEndDateInput) {
                poEndDateInput.value = formatDate(lastDay);
            }
        }

        async function loadPurchaseTypes() {
            const typeHiddenInput = document.getElementById('purchType');
            const typeDropdownItems = document.getElementById('purchTypeDropdownItems');
            const typeSearchInput = document.getElementById('purchTypeSearchInput');

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
                    dropdownItemsId: 'purchTypeDropdownItems',
                    hiddenInputId: 'purchType',
                    selectedTextId: 'purchTypeSelectedText',
                    searchInputId: 'purchTypeSearchInput',
                    dropdownBtnId: 'purchTypeDropdownBtn',
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
                    onItemSelect: (item) => {
                        const subTypeHiddenInput = document.getElementById('purchSubType');
                        const subTypeSelectedText = document.getElementById('purchSubTypeSelectedText');
                        if (subTypeHiddenInput) subTypeHiddenInput.value = '';
                        if (subTypeSelectedText) subTypeSelectedText.textContent = 'Select Purchase Sub Type';

                        const typeHiddenInput = document.getElementById('purchType');
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
                            dropdownItemsId: 'purchTypeDropdownItems',
                            hiddenInputId: 'purchType',
                            selectedTextId: 'purchTypeSelectedText',
                            searchInputId: 'purchTypeSearchInput',
                            dropdownBtnId: 'purchTypeDropdownBtn',
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
                            onItemSelect: (item) => {
                                const typeHiddenInput = document.getElementById('purchType');
                                const typeId = item.ID || item.id || '';
                                if (typeHiddenInput && typeId) {
                                    typeHiddenInput.value = typeId.toString();
                                }

                                const subTypeHiddenInput = document.getElementById('purchSubType');
                                const subTypeSelectedText = document.getElementById('purchSubTypeSelectedText');
                                if (subTypeHiddenInput) subTypeHiddenInput.value = '';
                                if (subTypeSelectedText) subTypeSelectedText.textContent = 'Select Purchase Sub Type';

                                if (typeId) {
                                    const typeIdInt = parseInt(typeId, 10);
                                    loadPurchaseSubTypes(typeIdInt);
                                }
                            }
                        });
                    });

                    newSearchInput.addEventListener('click', (e) => {
                        e.stopPropagation();
                    });
                }

                const typeDropdownBtn = document.getElementById('purchTypeDropdownBtn');
                if (typeDropdownBtn) {
                    typeDropdownBtn.addEventListener('shown.bs.dropdown', () => {
                        const searchInput = document.getElementById('purchTypeSearchInput');
                        if (searchInput) {
                            setTimeout(() => {
                                searchInput.focus();
                            }, 100);
                        }
                    });
                }
            } catch (error) {
                typeDropdownItems.innerHTML = '<div class="px-3 py-2 text-danger text-center">Error loading purchase types</div>';
            }
        }

        async function loadPurchaseSubTypes(mstPROPurchaseTypeID = null) {
            const subTypeHiddenInput = document.getElementById('purchSubType');
            const subTypeDropdownItems = document.getElementById('purchSubTypeDropdownItems');
            const subTypeSelectedText = document.getElementById('purchSubTypeSelectedText');
            const subTypeSearchInput = document.getElementById('purchSubTypeSearchInput');

            if (!subTypeHiddenInput || !subTypeDropdownItems) return;

            if (subTypeHiddenInput) subTypeHiddenInput.value = '';
            if (subTypeSelectedText) subTypeSelectedText.textContent = 'Select Purchase Sub Type';

            subTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Loading purchase sub types...</div>';

            try {
                let endpoint = '/Procurement/Master/PurchaseTypes/SubTypes?isActive=true';
                if (mstPROPurchaseTypeID && mstPROPurchaseTypeID > 0) {
                    endpoint += `&mstPROPurchaseTypeID=${encodeURIComponent(mstPROPurchaseTypeID)}`;
                }

                const data = await apiCall('Procurement', endpoint, 'GET');
                const subTypes = Array.isArray(data) ? data : (data.data || []);

                if (!Array.isArray(subTypes) || subTypes.length === 0) {
                    subTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No purchase sub types found</div>';
                    if (subTypeHiddenInput) subTypeHiddenInput.value = '';
                    if (subTypeSelectedText) subTypeSelectedText.textContent = 'Select Purchase Sub Type';
                    return;
                }

                renderDropdownWithSearch({
                    items: subTypes,
                    searchTerm: '',
                    dropdownItemsId: 'purchSubTypeDropdownItems',
                    hiddenInputId: 'purchSubType',
                    selectedTextId: 'purchSubTypeSelectedText',
                    searchInputId: 'purchSubTypeSearchInput',
                    dropdownBtnId: 'purchSubTypeDropdownBtn',
                    getValue: (subType) => (subType.ID || subType.id || '').toString(),
                    getText: (subType) => subType.PurchaseRequestSubType || subType.purchaseRequestSubType || '',
                    getSearchableText: (subType) => (subType.PurchaseRequestSubType || subType.purchaseRequestSubType || '').toString(),
                    limit: 5,
                    onItemSelect: (item) => {
                        const subTypeHiddenInput = document.getElementById('purchSubType');
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
                            dropdownItemsId: 'purchSubTypeDropdownItems',
                            hiddenInputId: 'purchSubType',
                            selectedTextId: 'purchSubTypeSelectedText',
                            searchInputId: 'purchSubTypeSearchInput',
                            dropdownBtnId: 'purchSubTypeDropdownBtn',
                            getValue: (subType) => (subType.ID || subType.id || '').toString(),
                            getText: (subType) => subType.PurchaseRequestSubType || subType.purchaseRequestSubType || '',
                            getSearchableText: (subType) => (subType.PurchaseRequestSubType || subType.purchaseRequestSubType || '').toString(),
                            limit: 5,
                            onItemSelect: (item) => {
                                const subTypeHiddenInput = document.getElementById('purchSubType');
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

                const subTypeDropdownBtn = document.getElementById('purchSubTypeDropdownBtn');
                if (subTypeDropdownBtn) {
                    subTypeDropdownBtn.addEventListener('shown.bs.dropdown', () => {
                        const searchInput = document.getElementById('purchSubTypeSearchInput');
                        if (searchInput) {
                            setTimeout(() => {
                                searchInput.focus();
                            }, 100);
                        }
                    });
                }
            } catch (error) {
                subTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-danger text-center">Error loading purchase sub types</div>';
            }
        }

        async function loadCompanies() {
            const companyHiddenInput = document.getElementById('company');
            const companyDropdownItems = document.getElementById('companyDropdownItems');
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

        async function loadVendors() {
            const vendorHiddenInput = document.getElementById('vendorName');
            const vendorDropdownItems = document.getElementById('vendorDropdownItems');
            const vendorSearchInput = document.getElementById('vendorSearchInput');

            if (!vendorHiddenInput || !vendorDropdownItems) return;

            vendorDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Loading vendors...</div>';

            try {
                const endpoint = '/Procurement/Master/Vendors?isActive=true';
                const data = await apiCall('Procurement', endpoint, 'GET');
                const vendors = data.data || data;

                if (!Array.isArray(vendors) || vendors.length === 0) {
                    vendorDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No vendors found</div>';
                    return;
                }

                renderDropdownWithSearch({
                    items: vendors,
                    searchTerm: '',
                    dropdownItemsId: 'vendorDropdownItems',
                    hiddenInputId: 'vendorName',
                    selectedTextId: 'vendorSelectedText',
                    searchInputId: 'vendorSearchInput',
                    dropdownBtnId: 'vendorDropdownBtn',
                    getValue: (vendor) => vendor.VendorID || vendor.vendorID || vendor.ID || vendor.id || '',
                    getText: (vendor) => (vendor.VendorName || vendor.vendorName || vendor.VendorID || vendor.vendorID || '').toString(),
                    getSearchableText: (vendor) => (vendor.VendorName || vendor.vendorName || vendor.VendorID || vendor.vendorID || '').toString(),
                    limit: 5
                });

                if (vendorSearchInput) {
                    const newSearchInput = vendorSearchInput.cloneNode(true);
                    vendorSearchInput.parentNode.replaceChild(newSearchInput, vendorSearchInput);

                    newSearchInput.addEventListener('input', (e) => {
                        const searchTerm = e.target.value.toLowerCase().trim();
                        renderDropdownWithSearch({
                            items: vendors,
                            searchTerm: searchTerm,
                            dropdownItemsId: 'vendorDropdownItems',
                            hiddenInputId: 'vendorName',
                            selectedTextId: 'vendorSelectedText',
                            searchInputId: 'vendorSearchInput',
                            dropdownBtnId: 'vendorDropdownBtn',
                            getValue: (vendor) => vendor.VendorID || vendor.vendorID || vendor.ID || vendor.id || '',
                            getText: (vendor) => (vendor.VendorName || vendor.vendorName || vendor.VendorID || vendor.vendorID || '').toString(),
                            getSearchableText: (vendor) => (vendor.VendorName || vendor.vendorName || vendor.VendorID || vendor.vendorID || '').toString(),
                            limit: 5
                        });
                    });

                    newSearchInput.addEventListener('click', (e) => {
                        e.stopPropagation();
                    });
                }

                const vendorDropdownBtn = document.getElementById('vendorDropdownBtn');
                if (vendorDropdownBtn) {
                    vendorDropdownBtn.addEventListener('shown.bs.dropdown', () => {
                        const searchInput = document.getElementById('vendorSearchInput');
                        if (searchInput) {
                            setTimeout(() => {
                                searchInput.focus();
                            }, 100);
                        }
                    });
                }
            } catch (error) {
                vendorDropdownItems.innerHTML = '<div class="px-3 py-2 text-danger text-center">Error loading vendors</div>';
            }
        }

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

        if (typeof window.poListManager === 'undefined') {
            window.poListManager = {};
        }

        if (!window.poListManager.resetFilter || typeof window.poListManager.resetFilter !== 'function') {
            window.poListManager.resetFilter = function() {
                const form = document.getElementById('filterForm');
                if (!form) return;

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

                const poStartDateInput = document.getElementById('poStartDate');
                const poEndDateInput = document.getElementById('poEndDate');
                if (poStartDateInput) poStartDateInput.value = formatDate(firstDay);
                if (poEndDateInput) poEndDateInput.value = formatDate(lastDay);

                const poNumberInput = document.getElementById('poNumber');
                const purchNameInput = document.getElementById('purchName');
                if (poNumberInput) poNumberInput.value = '';
                if (purchNameInput) purchNameInput.value = '';

                const dropdownFields = [
                    { hiddenId: 'purchType', textId: 'purchTypeSelectedText', defaultText: 'Select Purchase Type', searchId: 'purchTypeSearchInput' },
                    { hiddenId: 'purchSubType', textId: 'purchSubTypeSelectedText', defaultText: 'Select Purchase Sub Type', searchId: 'purchSubTypeSearchInput' },
                    { hiddenId: 'company', textId: 'companySelectedText', defaultText: 'Select Company', searchId: 'companySearchInput' },
                    { hiddenId: 'vendorName', textId: 'vendorSelectedText', defaultText: 'Select Vendor', searchId: 'vendorSearchInput' }
                ];

                dropdownFields.forEach(field => {
                    const hiddenInput = document.getElementById(field.hiddenId);
                    const selectedText = document.getElementById(field.textId);
                    const searchInput = document.getElementById(field.searchId);

                    if (hiddenInput) hiddenInput.value = '';
                    if (selectedText) selectedText.textContent = field.defaultText;
                    if (searchInput) searchInput.value = '';
                });

                const dropdowns = document.querySelectorAll('.dropdown-menu.show');
                dropdowns.forEach(dropdown => {
                    const dropdownInstance = bootstrap.Dropdown.getInstance(dropdown.previousElementSibling);
                    if (dropdownInstance) {
                        dropdownInstance.hide();
                    }
                });

                if (window.poListManager && window.poListManager.poDataTable) {
                    window.poListManager.poDataTable.ajax.reload();
                }
            };
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setDefaultDateRange();
                loadPurchaseTypes();
                loadCompanies();
                loadVendors();
            });
        } else {
            setDefaultDateRange();
            loadPurchaseTypes();
            loadCompanies();
            loadVendors();
        }
    })();
</script>
