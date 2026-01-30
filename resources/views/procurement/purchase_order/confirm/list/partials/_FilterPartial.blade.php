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
                <button type="button" class="btn btn-primary" onclick="confirmPOManager.search()">
                    <i class="icon-base bx bx-search me-2"></i>
                    Search
                </button>
                <button type="button" class="btn btn-secondary" onclick="confirmPOManager.resetFilter()">
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

        function renderDropdownWithSearchSafe(config) {
            const renderFn = (typeof window.renderDropdownWithSearch === 'function')
                ? window.renderDropdownWithSearch
                : (window.ProcurementWizardUtils &&
                   typeof window.ProcurementWizardUtils.renderDropdownWithSearch === 'function')
                    ? (cfg) => window.ProcurementWizardUtils.renderDropdownWithSearch(cfg)
                    : null;

            if (!renderFn) {
                console.warn('renderDropdownWithSearch is not available');
                return;
            }
            renderFn(config);
        }

        async function loadPurchaseTypes() {
            const typeHiddenInput = document.getElementById('purchType');
            const typeDropdownItems = document.getElementById('purchTypeDropdownItems');
            const typeSelectedText = document.getElementById('purchTypeSelectedText');
            const typeSearchInput = document.getElementById('purchTypeSearchInput');

            if (!typeHiddenInput || !typeDropdownItems) return;

            typeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Loading purchase types...</div>';

            try {
                let types = [];
                if (window.procurementSharedCache && typeof window.procurementSharedCache.getPurchaseTypes === 'function') {
                    types = await window.procurementSharedCache.getPurchaseTypes();
                } else {
                    const endpoint = '/Procurement/Master/PurchaseTypes/List?isActive=true';
                    const data = await apiCall('Procurement', endpoint, 'GET', null, { cancelPrevious: false })
                        .catch((error) => {
                            if (String(error?.message || '').toLowerCase().includes('cancel')) {
                                return null;
                            }
                            throw error;
                        });
                    const payload = data?.data ?? data ?? [];
                    types = Array.isArray(payload) ? payload : [];
                }

                if (!Array.isArray(types) || types.length === 0) {
                    typeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No purchase types found</div>';
                    return;
                }

                renderDropdownWithSearchSafe({
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

                        const typeId = item.ID || item.id || '';
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
                        renderDropdownWithSearchSafe({
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
                                const subTypeHiddenInput = document.getElementById('purchSubType');
                                const subTypeSelectedText = document.getElementById('purchSubTypeSelectedText');
                                if (subTypeHiddenInput) subTypeHiddenInput.value = '';
                                if (subTypeSelectedText) subTypeSelectedText.textContent = 'Select Purchase Sub Type';

                                const typeId = item.ID || item.id || '';
                                if (typeId) {
                                    const typeIdInt = parseInt(typeId, 10);
                                    loadPurchaseSubTypes(typeIdInt);
                                }
                            }
                        });
                    });
                }
            } catch (error) {
                console.error('Error loading purchase types:', error);
                typeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center text-danger">Error loading purchase types</div>';
            }
        }

        async function loadPurchaseSubTypes(typeId) {
            const subTypeHiddenInput = document.getElementById('purchSubType');
            const subTypeDropdownItems = document.getElementById('purchSubTypeDropdownItems');
            const subTypeSelectedText = document.getElementById('purchSubTypeSelectedText');
            const subTypeSearchInput = document.getElementById('purchSubTypeSearchInput');

            if (!subTypeHiddenInput || !subTypeDropdownItems) return;

            subTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Loading purchase sub types...</div>';

            try {
                let subTypes = [];
                if (window.procurementSharedCache && typeof window.procurementSharedCache.getPurchaseSubTypes === 'function') {
                    subTypes = await window.procurementSharedCache.getPurchaseSubTypes(typeId);
                } else {
                    const endpoint = `/Procurement/Master/PurchaseTypes/SubTypes?isActive=true&mstPROPurchaseTypeID=${typeId}`;
                    const data = await apiCall('Procurement', endpoint, 'GET', null, { cancelPrevious: false })
                        .catch((error) => {
                            if (String(error?.message || '').toLowerCase().includes('cancel')) {
                                return null;
                            }
                            throw error;
                        });
                    const payload = data?.data ?? data ?? [];
                    subTypes = Array.isArray(payload) ? payload : [];
                }

                if (!Array.isArray(subTypes) || subTypes.length === 0) {
                    subTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No purchase sub types found</div>';
                    return;
                }

                renderDropdownWithSearchSafe({
                    items: subTypes,
                    searchTerm: '',
                    dropdownItemsId: 'purchSubTypeDropdownItems',
                    hiddenInputId: 'purchSubType',
                    selectedTextId: 'purchSubTypeSelectedText',
                    searchInputId: 'purchSubTypeSearchInput',
                    dropdownBtnId: 'purchSubTypeDropdownBtn',
                    getValue: (subType) => (subType.ID || subType.id || '').toString(),
                    getText: (subType) => subType.PurchaseRequestSubType || subType.purchaseRequestSubType || '',
                    getSearchableText: (subType) => subType.PurchaseRequestSubType || subType.purchaseRequestSubType || '',
                    limit: 5
                });

                if (subTypeSearchInput) {
                    const newSearchInput = subTypeSearchInput.cloneNode(true);
                    subTypeSearchInput.parentNode.replaceChild(newSearchInput, subTypeSearchInput);
                    newSearchInput.addEventListener('input', (e) => {
                        const searchTerm = e.target.value.toLowerCase().trim();
                        renderDropdownWithSearchSafe({
                            items: subTypes,
                            searchTerm: searchTerm,
                            dropdownItemsId: 'purchSubTypeDropdownItems',
                            hiddenInputId: 'purchSubType',
                            selectedTextId: 'purchSubTypeSelectedText',
                            searchInputId: 'purchSubTypeSearchInput',
                            dropdownBtnId: 'purchSubTypeDropdownBtn',
                            getValue: (subType) => (subType.ID || subType.id || '').toString(),
                            getText: (subType) => subType.PurchaseRequestSubType || subType.purchaseRequestSubType || '',
                            getSearchableText: (subType) => subType.PurchaseRequestSubType || subType.purchaseRequestSubType || '',
                            limit: 5
                        });
                    });
                }
            } catch (error) {
                console.error('Error loading purchase sub types:', error);
                subTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center text-danger">Error loading purchase sub types</div>';
            }
        }

        async function loadCompanies() {
            const companyHiddenInput = document.getElementById('company');
            const companyDropdownItems = document.getElementById('companyDropdownItems');
            const companySelectedText = document.getElementById('companySelectedText');
            const companySearchInput = document.getElementById('companySearchInput');

            if (!companyHiddenInput || !companyDropdownItems) return;

            companyDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Loading companies...</div>';

            try {
                let companies = [];
                if (typeof loadSharedCompanies === 'function') {
                    companies = await loadSharedCompanies();
                } else if (window.procurementSharedCache && typeof window.procurementSharedCache.getCompanies === 'function') {
                    companies = await window.procurementSharedCache.getCompanies();
                } else {
                    const endpoint = '/Procurement/Master/Companies?isActive=true';
                    const data = await apiCall('Procurement', endpoint, 'GET', null, { cancelPrevious: false })
                        .catch((error) => {
                            if (String(error?.message || '').toLowerCase().includes('cancel')) {
                                return null;
                            }
                            throw error;
                        });
                    const payload = data?.data ?? data ?? [];
                    companies = Array.isArray(payload) ? payload : [];
                }

                if (!Array.isArray(companies) || companies.length === 0) {
                    companyDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No companies found</div>';
                    return;
                }

                renderDropdownWithSearchSafe({
                    items: companies,
                    searchTerm: '',
                    dropdownItemsId: 'companyDropdownItems',
                    hiddenInputId: 'company',
                    selectedTextId: 'companySelectedText',
                    searchInputId: 'companySearchInput',
                    dropdownBtnId: 'companyDropdownBtn',
                    getValue: (company) => company.CompanyName || company.companyName || '',
                    getText: (company) => company.CompanyName || company.companyName || '',
                    getSearchableText: (company) => company.CompanyName || company.companyName || '',
                    limit: 5
                });

                if (companySearchInput && companySearchInput.parentNode) {
                    const newSearchInput = companySearchInput.cloneNode(true);
                    companySearchInput.parentNode.replaceChild(newSearchInput, companySearchInput);
                    newSearchInput.addEventListener('input', (e) => {
                        const searchTerm = e.target.value.toLowerCase().trim();
                        renderDropdownWithSearchSafe({
                            items: companies,
                            searchTerm: searchTerm,
                            dropdownItemsId: 'companyDropdownItems',
                            hiddenInputId: 'company',
                            selectedTextId: 'companySelectedText',
                            searchInputId: 'companySearchInput',
                            dropdownBtnId: 'companyDropdownBtn',
                            getValue: (company) => company.CompanyName || company.companyName || '',
                            getText: (company) => company.CompanyName || company.companyName || '',
                            getSearchableText: (company) => company.CompanyName || company.companyName || '',
                            limit: 5
                        });
                    });
                }
            } catch (error) {
                console.error('Error loading companies:', error);
                companyDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center text-danger">Error loading companies</div>';
            }
        }

        async function loadVendors() {
            const vendorHiddenInput = document.getElementById('vendorName');
            const vendorDropdownItems = document.getElementById('vendorDropdownItems');
            const vendorSelectedText = document.getElementById('vendorSelectedText');
            const vendorSearchInput = document.getElementById('vendorSearchInput');

            if (!vendorHiddenInput || !vendorDropdownItems) return;

            vendorDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Loading vendors...</div>';

            try {
                let vendors = [];
                if (typeof loadSharedVendors === 'function') {
                    vendors = await loadSharedVendors();
                } else if (window.procurementSharedCache && typeof window.procurementSharedCache.getVendors === 'function') {
                    vendors = await window.procurementSharedCache.getVendors();
                } else {
                    const endpoint = '/Procurement/Master/Vendors?isActive=true';
                    const data = await apiCall('Procurement', endpoint, 'GET', null, { cancelPrevious: false })
                        .catch((error) => {
                            if (String(error?.message || '').toLowerCase().includes('cancel')) {
                                return null;
                            }
                            throw error;
                        });
                    const payload = data?.data ?? data ?? [];
                    vendors = Array.isArray(payload) ? payload : [];
                }

                if (!Array.isArray(vendors) || vendors.length === 0) {
                    vendorDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No vendors found</div>';
                    return;
                }

                renderDropdownWithSearchSafe({
                    items: vendors,
                    searchTerm: '',
                    dropdownItemsId: 'vendorDropdownItems',
                    hiddenInputId: 'vendorName',
                    selectedTextId: 'vendorSelectedText',
                    searchInputId: 'vendorSearchInput',
                    dropdownBtnId: 'vendorDropdownBtn',
                    getValue: (vendor) => vendor.VendorName || vendor.vendorName || '',
                    getText: (vendor) => vendor.VendorName || vendor.vendorName || '',
                    getSearchableText: (vendor) => vendor.VendorName || vendor.vendorName || '',
                    limit: 5
                });

                if (vendorSearchInput && vendorSearchInput.parentNode) {
                    const newSearchInput = vendorSearchInput.cloneNode(true);
                    vendorSearchInput.parentNode.replaceChild(newSearchInput, vendorSearchInput);
                    newSearchInput.addEventListener('input', (e) => {
                        const searchTerm = e.target.value.toLowerCase().trim();
                        renderDropdownWithSearchSafe({
                            items: vendors,
                            searchTerm: searchTerm,
                            dropdownItemsId: 'vendorDropdownItems',
                            hiddenInputId: 'vendorName',
                            selectedTextId: 'vendorSelectedText',
                            searchInputId: 'vendorSearchInput',
                            dropdownBtnId: 'vendorDropdownBtn',
                            getValue: (vendor) => vendor.VendorName || vendor.vendorName || '',
                            getText: (vendor) => vendor.VendorName || vendor.vendorName || '',
                            getSearchableText: (vendor) => vendor.VendorName || vendor.vendorName || '',
                            limit: 5
                        });
                    });
                }
            } catch (error) {
                console.error('Error loading vendors:', error);
                vendorDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center text-danger">Error loading vendors</div>';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            setDefaultDateRange();
            loadPurchaseTypes();
            loadCompanies();
            loadVendors();
        });
    })();
</script>
