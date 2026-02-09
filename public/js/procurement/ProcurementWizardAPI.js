/**
 * Procurement Wizard API Module
 * Handles all API calls for loading and saving data
 *
 * This module extracts all API-related methods from the main ProcurementWizard class
 * to reduce the main file size and improve maintainability.
 */

class ProcurementWizardAPI {
    constructor(wizardInstance) {
        this.wizard = wizardInstance;
        // Use shared cache if available, otherwise create local cache
        this.sharedCache = window.procurementSharedCache || null;
        // Local cache fallback (if shared cache not available)
        this.employeeNameCache = new Map();
        this.pendingEmployeeLookups = new Map();
    }

    /**
     * Load companies from API and populate dropdown
     */
    async loadCompaniesFromApi() {
        const companyHiddenInput = document.getElementById('Company');
        const companyDropdownItems = document.getElementById('companyDropdownItems');
        const companySelectedText = document.getElementById('companySelectedText');
        const companySearchInput = document.getElementById('companySearchInput');

        if (!companyHiddenInput || !companyDropdownItems) return;

        // Show loading state
        companyDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Loading companies...</div>';

        try {
            // Call the API to get companies
            const endpoint = '/Procurement/Master/Companies?isActive=true';
            const data = await apiCall('Procurement', endpoint, 'GET');
            const companies = data.data || data;

            if (!Array.isArray(companies) || companies.length === 0) {
                companyDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No companies found</div>';
                console.warn('No companies found');
                return;
            }

            // Store all companies for filtering
            if (!this.wizard.allCompanies) {
                this.wizard.allCompanies = [];
            }
            this.wizard.allCompanies = companies;

            // Render companies (initially show first 5)
            this.wizard.renderDropdownWithSearch({
                items: companies,
                searchTerm: '',
                dropdownItemsId: 'companyDropdownItems',
                hiddenInputId: 'Company',
                selectedTextId: 'companySelectedText',
                searchInputId: 'companySearchInput',
                dropdownBtnId: 'companyDropdownBtn',
                getValue: (company) => company.CompanyID || company.companyID,
                getText: (company) => company.Company || company.company,
                getSearchableText: (company) => (company.Company || company.company || '').toString(),
                limit: 5
            });

            // Setup search functionality
            if (companySearchInput) {
                const newSearchInput = companySearchInput.cloneNode(true);
                companySearchInput.parentNode.replaceChild(newSearchInput, companySearchInput);

                newSearchInput.addEventListener('input', (e) => {
                    const searchTerm = e.target.value.toLowerCase().trim();
                    this.wizard.renderDropdownWithSearch({
                        items: companies,
                        searchTerm: searchTerm,
                        dropdownItemsId: 'companyDropdownItems',
                        hiddenInputId: 'Company',
                        selectedTextId: 'companySelectedText',
                        searchInputId: 'companySearchInput',
                        dropdownBtnId: 'companyDropdownBtn',
                        getValue: (company) => company.CompanyID || company.companyID,
                        getText: (company) => company.Company || company.company,
                        getSearchableText: (company) => (company.Company || company.company || '').toString(),
                        limit: 5
                    });
                });

                newSearchInput.addEventListener('click', (e) => {
                    e.stopPropagation();
                });
            }

            // Focus search input when dropdown opens
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

            // Auto-fill: Select first company if available
            if (companies.length > 0) {
                const firstCompany = companies[0];
                const firstCompanyValue = firstCompany.CompanyID || firstCompany.companyID;
                const firstCompanyText = firstCompany.Company || firstCompany.company;
                if (firstCompanyValue && companyHiddenInput && companySelectedText) {
                    companyHiddenInput.value = firstCompanyValue;
                    companySelectedText.textContent = firstCompanyText;
                }
            }

        } catch (error) {
            console.error('Error loading companies:', error);
            companyDropdownItems.innerHTML = '<div class="px-3 py-2 text-danger text-center">Error loading companies</div>';
        }
    }

    /**
     * Load applicants from API and populate dropdown
     */
    async loadApplicantsFromApi() {
        const applicantHiddenInput = document.getElementById('Applicant');
        const applicantDropdownItems = document.getElementById('applicantDropdownItems');
        const applicantSelectedText = document.getElementById('applicantSelectedText');
        const applicantSearchInput = document.getElementById('applicantSearchInput');

        if (!applicantHiddenInput || !applicantDropdownItems) return;

        // Show loading state
        applicantDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Loading applicants...</div>';

        try {
            // Call the API to get applicants (userId is optional, will use current user from context)
            const endpoint = '/Procurement/Master/Applicants';
            const data = await apiCall('Procurement', endpoint, 'GET');
            const applicants = data.data || data;

            // Get Requestor employee ID from config (for storing), not from field (which displays name)
            const currentUserEmployeeID = (window.ProcurementConfig && window.ProcurementConfig.currentUserEmployeeID) || '';
            const currentUserFullName = (window.ProcurementConfig && window.ProcurementConfig.currentUserFullName) || '';

            // Track added applicant values/texts to avoid duplicates
            const addedApplicants = new Set();
            const allApplicants = [];

            // Add applicants from API
            if (Array.isArray(applicants) && applicants.length > 0) {
                applicants.forEach(applicant => {
                    const applicantValue = applicant.OnBehalfID || applicant.onBehalfID || applicant.Name || applicant.name;
                    const applicantText = applicant.Name || applicant.name || applicant.OnBehalfID || applicant.onBehalfID;

                    // Only add if not duplicate
                    if (applicantValue && applicantText && !addedApplicants.has(applicantValue.toLowerCase()) && !addedApplicants.has(applicantText.toLowerCase())) {
                        allApplicants.push({
                            value: applicantValue,
                            text: applicantText
                        });
                        addedApplicants.add(applicantValue.toLowerCase());
                        addedApplicants.add(applicantText.toLowerCase());
                    }
                });
            }

            // Add Requestor to dropdown if it exists and is not already in the list
            // Use employee ID as value (for storing), name as text (for display)
            if (currentUserEmployeeID && !addedApplicants.has(currentUserEmployeeID.toLowerCase())) {
                allApplicants.unshift({
                    value: currentUserEmployeeID,
                    text: currentUserFullName || currentUserEmployeeID
                });
            }

            if (allApplicants.length === 0) {
                applicantDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No applicants found</div>';
                console.warn('No applicants found');
                return;
            }

            // Store all applicants for filtering
            if (!this.wizard.allApplicants) {
                this.wizard.allApplicants = [];
            }
            this.wizard.allApplicants = allApplicants;

            // Render applicants (initially show first 5)
            this.wizard.renderDropdownWithSearch({
                items: allApplicants,
                searchTerm: '',
                dropdownItemsId: 'applicantDropdownItems',
                hiddenInputId: 'Applicant',
                selectedTextId: 'applicantSelectedText',
                searchInputId: 'applicantSearchInput',
                dropdownBtnId: 'applicantDropdownBtn',
                getValue: (item) => item.value,
                getText: (item) => item.text,
                getSearchableText: (item) => item.text,
                limit: 5
            });

            // Setup search functionality
            if (applicantSearchInput) {
                const newSearchInput = applicantSearchInput.cloneNode(true);
                applicantSearchInput.parentNode.replaceChild(newSearchInput, applicantSearchInput);

                newSearchInput.addEventListener('input', (e) => {
                    const searchTerm = e.target.value.toLowerCase().trim();
                    this.wizard.renderDropdownWithSearch({
                        items: allApplicants,
                        searchTerm: searchTerm,
                        dropdownItemsId: 'applicantDropdownItems',
                        hiddenInputId: 'Applicant',
                        selectedTextId: 'applicantSelectedText',
                        searchInputId: 'applicantSearchInput',
                        dropdownBtnId: 'applicantDropdownBtn',
                        getValue: (item) => item.value,
                        getText: (item) => item.text,
                        getSearchableText: (item) => item.text,
                        limit: 5
                    });
                });

                newSearchInput.addEventListener('click', (e) => {
                    e.stopPropagation();
                });
            }

            // Focus search input when dropdown opens
            const applicantDropdownBtn = document.getElementById('applicantDropdownBtn');
            if (applicantDropdownBtn) {
                applicantDropdownBtn.addEventListener('shown.bs.dropdown', () => {
                    const searchInput = document.getElementById('applicantSearchInput');
                    if (searchInput) {
                        setTimeout(() => {
                            searchInput.focus();
                        }, 100);
                    }
                });
            }

            // Auto-select current logged-in user as applicant
            if (currentUserEmployeeID && applicantHiddenInput && applicantSelectedText) {
                // Find the applicant in the list
                const currentUserApplicant = allApplicants.find(a =>
                    a.value.toLowerCase() === currentUserEmployeeID.toLowerCase() ||
                    a.text.toLowerCase() === currentUserFullName.toLowerCase()
                );

                if (currentUserApplicant) {
                    applicantHiddenInput.value = currentUserApplicant.value;
                    applicantSelectedText.textContent = currentUserApplicant.text;
                    // Trigger change event
                    applicantHiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }

        } catch (error) {
            console.error('Error loading applicants:', error);
            applicantDropdownItems.innerHTML = '<div class="px-3 py-2 text-danger text-center">Error loading applicants</div>';
        }
    }

    /**
     * Load purchase types from API and populate dropdown
     */
    async loadPurchaseTypesFromApi() {
        const purchaseRequestTypeHiddenInput = document.getElementById('PurchaseRequestType');
        const purchaseRequestTypeDropdownItems = document.getElementById('purchaseRequestTypeDropdownItems');
        const purchaseRequestTypeSelectedText = document.getElementById('purchaseRequestTypeSelectedText');
        const purchaseRequestTypeSearchInput = document.getElementById('purchaseRequestTypeSearchInput');

        if (!purchaseRequestTypeHiddenInput || !purchaseRequestTypeDropdownItems) return;

        // Show loading state
        purchaseRequestTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Loading purchase types...</div>';

        try {
            // Use shared cache if available
            let purchaseTypes;
            if (this.sharedCache && this.sharedCache.getPurchaseTypes) {
                purchaseTypes = await this.sharedCache.getPurchaseTypes();
            } else {
                // Fallback to direct API call
            const endpoint = '/Procurement/Master/PurchaseTypes/List?isActive=true';
            const data = await apiCall('Procurement', endpoint, 'GET');
                purchaseTypes = data.data || data;
            }

            if (!Array.isArray(purchaseTypes) || purchaseTypes.length === 0) {
                purchaseRequestTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No purchase types found</div>';
                console.warn('No purchase types found');
                return;
            }

            // Store all purchase types for filtering
            if (!this.wizard.allPurchaseTypes) {
                this.wizard.allPurchaseTypes = [];
            }
            this.wizard.allPurchaseTypes = purchaseTypes;

            // Helper function to format purchase type display text with category
            const formatPurchaseTypeText = (type) => {
                const prType = type.PurchaseRequestType || type.purchaseRequestType || type.Name || type.name || '';
                const category = type.Category || type.category || '';

                if (!category) {
                    return prType;
                }

                // If PurchaseRequestType and Category are the same, just show one
                if (prType.toLowerCase() === category.toLowerCase()) {
                    return prType;
                }

                // Otherwise, combine them: "PurchaseRequestType Category"
                return `${prType} ${category}`;
            };

            // Render purchase types (initially show first 5)
            this.wizard.renderDropdownWithSearch({
                items: purchaseTypes,
                searchTerm: '',
                dropdownItemsId: 'purchaseRequestTypeDropdownItems',
                hiddenInputId: 'PurchaseRequestType',
                selectedTextId: 'purchaseRequestTypeSelectedText',
                searchInputId: 'purchaseRequestTypeSearchInput',
                dropdownBtnId: 'purchaseRequestTypeDropdownBtn',
                getValue: (type) => type.ID || type.id || type.Id || '',
                getText: formatPurchaseTypeText,
                getSearchableText: (type) => {
                    const prType = type.PurchaseRequestType || type.purchaseRequestType || type.Name || type.name || '';
                    const category = type.Category || type.category || '';
                    return `${prType} ${category}`.trim();
                },
                limit: 5,
                onItemSelect: (item, value, text) => {
                    // Set PurchaseRequestTypeCategory field
                    const categoryField = document.getElementById('PurchaseRequestTypeCategory');
                    if (categoryField) {
                        const category = item.Category || item.category || '';
                        categoryField.value = category;
                    }

                    // Store current purchase request type ID
                    const typeId = value ? parseInt(value) : null;
                    this.wizard.currentPurchaseRequestTypeID = typeId;

                    // Clear sub type when type changes
                    const purchaseRequestSubTypeField = document.getElementById('PurchaseRequestSubType');
                    const purchaseRequestSubTypeSelectedText = document.getElementById('purchaseRequestSubTypeSelectedText');
                    if (purchaseRequestSubTypeField) {
                        purchaseRequestSubTypeField.value = '';
                    }
                    if (purchaseRequestSubTypeSelectedText) {
                        purchaseRequestSubTypeSelectedText.textContent = 'Select Purchase Request Sub Type';
                    }
                    this.wizard.currentPurchaseRequestSubTypeID = null;

                    // When purchase type is selected, load purchase sub types
                    if (this.wizard.loadPurchaseSubTypesFromApi) {
                        this.wizard.loadPurchaseSubTypesFromApi(value);
                    }

                    // Check if Additional step should be shown (for Type ID 5 or 7, no Sub Type needed)
                    if (this.wizard.checkAndToggleAdditionalStep) {
                        this.wizard.checkAndToggleAdditionalStep();
                    }
                }
            });

            // Setup search functionality
            if (purchaseRequestTypeSearchInput) {
                const newSearchInput = purchaseRequestTypeSearchInput.cloneNode(true);
                purchaseRequestTypeSearchInput.parentNode.replaceChild(newSearchInput, purchaseRequestTypeSearchInput);

                newSearchInput.addEventListener('input', (e) => {
                    const searchTerm = e.target.value.toLowerCase().trim();

                    // Helper function to format purchase type display text with category
                    const formatPurchaseTypeText = (type) => {
                        const prType = type.PurchaseRequestType || type.purchaseRequestType || type.Name || type.name || '';
                        const category = type.Category || type.category || '';

                        if (!category) {
                            return prType;
                        }

                        // If PurchaseRequestType and Category are the same, just show one
                        if (prType.toLowerCase() === category.toLowerCase()) {
                            return prType;
                        }

                        // Otherwise, combine them: "PurchaseRequestType Category"
                        return `${prType} ${category}`;
                    };

                    this.wizard.renderDropdownWithSearch({
                        items: purchaseTypes,
                        searchTerm: searchTerm,
                        dropdownItemsId: 'purchaseRequestTypeDropdownItems',
                        hiddenInputId: 'PurchaseRequestType',
                        selectedTextId: 'purchaseRequestTypeSelectedText',
                        searchInputId: 'purchaseRequestTypeSearchInput',
                        dropdownBtnId: 'purchaseRequestTypeDropdownBtn',
                        getValue: (type) => type.ID || type.id || type.Id || '',
                        getText: formatPurchaseTypeText,
                        getSearchableText: (type) => {
                            const prType = type.PurchaseRequestType || type.purchaseRequestType || type.Name || type.name || '';
                            const category = type.Category || type.category || '';
                            return `${prType} ${category}`.trim();
                        },
                        limit: 5,
                        onItemSelect: (item, value, text) => {
                            // Set PurchaseRequestTypeCategory field
                            const categoryField = document.getElementById('PurchaseRequestTypeCategory');
                            if (categoryField) {
                                const category = item.Category || item.category || '';
                                categoryField.value = category;
                            }

                            // Store current purchase request type ID
                            const typeId = value ? parseInt(value) : null;
                            this.wizard.currentPurchaseRequestTypeID = typeId;

                            // Clear sub type when type changes
                            const purchaseRequestSubTypeField = document.getElementById('PurchaseRequestSubType');
                            const purchaseRequestSubTypeSelectedText = document.getElementById('purchaseRequestSubTypeSelectedText');
                            if (purchaseRequestSubTypeField) {
                                purchaseRequestSubTypeField.value = '';
                            }
                            if (purchaseRequestSubTypeSelectedText) {
                                purchaseRequestSubTypeSelectedText.textContent = 'Select Purchase Request Sub Type';
                            }
                            this.wizard.currentPurchaseRequestSubTypeID = null;

                            // When purchase type is selected, load purchase sub types
                            if (this.wizard.loadPurchaseSubTypesFromApi) {
                                this.wizard.loadPurchaseSubTypesFromApi(value);
                            }

                            // Check if Additional step should be shown (for Type ID 5 or 7, no Sub Type needed)
                            if (this.wizard.checkAndToggleAdditionalStep) {
                                this.wizard.checkAndToggleAdditionalStep();
                            }
                        }
                    });
                });

                newSearchInput.addEventListener('click', (e) => {
                    e.stopPropagation();
                });
            }

            // Focus search input when dropdown opens
            const purchaseRequestTypeDropdownBtn = document.getElementById('purchaseRequestTypeDropdownBtn');
            if (purchaseRequestTypeDropdownBtn) {
                purchaseRequestTypeDropdownBtn.addEventListener('shown.bs.dropdown', () => {
                    const searchInput = document.getElementById('purchaseRequestTypeSearchInput');
                    if (searchInput) {
                        setTimeout(() => {
                            searchInput.focus();
                        }, 100);
                    }
                });
            }

        } catch (error) {
            console.error('Error loading purchase types:', error);
            purchaseRequestTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-danger text-center">Error loading purchase types</div>';
        }
    }

    /**
     * Load purchase sub types from API and populate dropdown
     */
    async loadPurchaseSubTypesFromApi(mstPROPurchaseTypeID = null) {
        const purchaseRequestSubTypeHiddenInput = document.getElementById('PurchaseRequestSubType');
        const purchaseRequestSubTypeDropdownItems = document.getElementById('purchaseRequestSubTypeDropdownItems');
        const purchaseRequestSubTypeSelectedText = document.getElementById('purchaseRequestSubTypeSelectedText');
        const purchaseRequestSubTypeSearchInput = document.getElementById('purchaseRequestSubTypeSearchInput');

        if (!purchaseRequestSubTypeHiddenInput || !purchaseRequestSubTypeDropdownItems) return;

        // If no type ID provided, try to get from PurchaseRequestType field
        if (!mstPROPurchaseTypeID) {
            const purchaseRequestTypeField = document.getElementById('PurchaseRequestType');
            if (purchaseRequestTypeField && purchaseRequestTypeField.value) {
                mstPROPurchaseTypeID = purchaseRequestTypeField.value;
            }
        }

        if (!mstPROPurchaseTypeID) {
            // Clear dropdown if no type ID
            purchaseRequestSubTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Please select Purchase Request Type first</div>';
            if (purchaseRequestSubTypeHiddenInput) {
                purchaseRequestSubTypeHiddenInput.value = '';
            }
            if (purchaseRequestSubTypeSelectedText) {
                purchaseRequestSubTypeSelectedText.textContent = '';
            }
            return;
        }

        // Show loading state
        purchaseRequestSubTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Loading purchase sub types...</div>';

        try {
            // Use shared cache if available
            let purchaseSubTypes;
            if (this.sharedCache && this.sharedCache.getPurchaseSubTypes) {
                purchaseSubTypes = await this.sharedCache.getPurchaseSubTypes(mstPROPurchaseTypeID);
            } else {
                // Fallback to direct API call
            const endpoint = `/Procurement/Master/PurchaseTypes/SubTypes?isActive=true&mstPROPurchaseTypeID=${encodeURIComponent(mstPROPurchaseTypeID)}`;
            const data = await apiCall('Procurement', endpoint, 'GET');
                purchaseSubTypes = data.data || data;
            }

            if (!Array.isArray(purchaseSubTypes) || purchaseSubTypes.length === 0) {
                purchaseRequestSubTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No purchase sub types found</div>';
                console.warn('No purchase sub types found');
                // Clear selected value
                if (purchaseRequestSubTypeHiddenInput) {
                    purchaseRequestSubTypeHiddenInput.value = '';
                }
                if (purchaseRequestSubTypeSelectedText) {
                    purchaseRequestSubTypeSelectedText.textContent = '';
                }
                return;
            }

            // Store all purchase sub types for filtering
            if (!this.wizard.allPurchaseSubTypes) {
                this.wizard.allPurchaseSubTypes = [];
            }
            this.wizard.allPurchaseSubTypes = purchaseSubTypes;

            // Render purchase sub types (initially show first 5)
            this.wizard.renderDropdownWithSearch({
                items: purchaseSubTypes,
                searchTerm: '',
                dropdownItemsId: 'purchaseRequestSubTypeDropdownItems',
                hiddenInputId: 'PurchaseRequestSubType',
                selectedTextId: 'purchaseRequestSubTypeSelectedText',
                searchInputId: 'purchaseRequestSubTypeSearchInput',
                dropdownBtnId: 'purchaseRequestSubTypeDropdownBtn',
                getValue: (subType) => subType.ID || subType.id || subType.Id || '',
                getText: (subType) => subType.PurchaseRequestSubType || subType.purchaseRequestSubType || subType.Name || subType.name || '',
                getSearchableText: (subType) => (subType.PurchaseRequestSubType || subType.purchaseRequestSubType || subType.Name || subType.name || '').toString(),
                limit: 5,
                onItemSelect: (item, value, text) => {
                    // Store current purchase request sub type ID
                    const subTypeId = value ? parseInt(value) : null;
                    this.wizard.currentPurchaseRequestSubTypeID = subTypeId;

                    // Check if Additional step should be shown after Sub Type is selected
                    if (this.wizard.checkAndToggleAdditionalStep) {
                        this.wizard.checkAndToggleAdditionalStep();
                    }
                }
            });

            // Setup search functionality
            if (purchaseRequestSubTypeSearchInput) {
                const newSearchInput = purchaseRequestSubTypeSearchInput.cloneNode(true);
                purchaseRequestSubTypeSearchInput.parentNode.replaceChild(newSearchInput, purchaseRequestSubTypeSearchInput);

                newSearchInput.addEventListener('input', (e) => {
                    const searchTerm = e.target.value.toLowerCase().trim();
                    this.wizard.renderDropdownWithSearch({
                        items: purchaseSubTypes,
                        searchTerm: searchTerm,
                        dropdownItemsId: 'purchaseRequestSubTypeDropdownItems',
                        hiddenInputId: 'PurchaseRequestSubType',
                        selectedTextId: 'purchaseRequestSubTypeSelectedText',
                        searchInputId: 'purchaseRequestSubTypeSearchInput',
                        dropdownBtnId: 'purchaseRequestSubTypeDropdownBtn',
                        getValue: (subType) => subType.ID || subType.id || subType.Id || '',
                        getText: (subType) => subType.PurchaseRequestSubType || subType.purchaseRequestSubType || subType.Name || subType.name || '',
                        getSearchableText: (subType) => (subType.PurchaseRequestSubType || subType.purchaseRequestSubType || subType.Name || subType.name || '').toString(),
                        limit: 5,
                        onItemSelect: (item, value, text) => {
                            // Store current purchase request sub type ID
                            const subTypeId = value ? parseInt(value) : null;
                            this.wizard.currentPurchaseRequestSubTypeID = subTypeId;

                            // Check if Additional step should be shown after Sub Type is selected
                            if (this.wizard.checkAndToggleAdditionalStep) {
                                this.wizard.checkAndToggleAdditionalStep();
                            }
                        }
                    });
                });

                newSearchInput.addEventListener('click', (e) => {
                    e.stopPropagation();
                });
            }

            // Focus search input when dropdown opens
            const purchaseRequestSubTypeDropdownBtn = document.getElementById('purchaseRequestSubTypeDropdownBtn');
            if (purchaseRequestSubTypeDropdownBtn) {
                purchaseRequestSubTypeDropdownBtn.addEventListener('shown.bs.dropdown', () => {
                    const searchInput = document.getElementById('purchaseRequestSubTypeSearchInput');
                    if (searchInput) {
                        setTimeout(() => {
                            searchInput.focus();
                        }, 100);
                    }
                });
            }

        } catch (error) {
            console.error('Error loading purchase sub types:', error);
            purchaseRequestSubTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-danger text-center">Error loading purchase sub types</div>';
        }
    }

    /**
     * Load billing types from API
     */
    async loadBillingTypesFromApi() {
        const billingTypeDropdownMenu = document.getElementById('billingTypeDropdownMenu');
        if (!billingTypeDropdownMenu) return;

        try {
            const endpoint = '/Procurement/Master/BillingTypes?isActive=true';
            const data = await apiCall('Procurement', endpoint, 'GET');

            if (!Array.isArray(data) || data.length === 0) {
                console.warn('No billing types found');
                return;
            }

            // Store billing types for later use
            this.wizard.billingTypes = data;

            // Populate dropdown menu
            billingTypeDropdownMenu.innerHTML = '';
            data.forEach(billingType => {
                const id = billingType.ID || billingType.id;
                const name = billingType.Name || billingType.name || '';
                const description = billingType.Description || billingType.description || '';
                const totalMonthPeriod = billingType.TotalMonthPeriod || billingType.totalMonthPeriod || 0;

                const li = document.createElement('li');
                li.innerHTML = `
                    <a class="dropdown-item" href="#" data-value="${id}" data-text="${name}" data-total-month-period="${totalMonthPeriod}">
                        <div>
                            <div class="fw-semibold">${name}</div>
                            <small class="text-muted">${description}</small>
                        </div>
                    </a>
                `;
                billingTypeDropdownMenu.appendChild(li);
            });

            // Re-initialize dropdown after populating
            if (this.wizard.initializeBillingTypeDropdown) {
                this.wizard.initializeBillingTypeDropdown();
            }

            // Also populate and initialize Subscribe Billing Type dropdown
            if (this.wizard.initializeSubscribeBillingTypeDropdown) {
                this.wizard.initializeSubscribeBillingTypeDropdown();
            }
        } catch (error) {
            console.error('Error loading billing types:', error);
        }
    }

    /**
     * Load items from API for choose item table
     */
    async loadItemsFromApi(itemId = null, itemName = null, prodPool = null, coa = null) {
        const tableBody = document.getElementById('chooseItemTableBody');
        if (!tableBody) return;

        // Show loading state
        tableBody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center">
                    <div class="spinner-border spinner-border-sm me-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    Loading items...
                </td>
            </tr>
        `;

        try {
            // Get PurchaseRequestType, Category, and PurchaseRequestSubType from Basic Information form
            const purchaseRequestTypeSelect = document.getElementById('PurchaseRequestType');
            const purchaseRequestTypeCategorySelect = document.getElementById('PurchaseRequestTypeCategory');
            const purchaseRequestSubTypeSelect = document.getElementById('PurchaseRequestSubType');
            const purchaseRequestType = purchaseRequestTypeSelect ? purchaseRequestTypeSelect.value : null;
            const purchaseRequestTypeCategory = purchaseRequestTypeCategorySelect ? purchaseRequestTypeCategorySelect.value : null;
            const purchaseRequestSubType = purchaseRequestSubTypeSelect ? purchaseRequestSubTypeSelect.value : null;

            // Build endpoint
            let endpoint = '/Procurement/Master/Inventories?isActive=true';
            if (itemName && itemName.trim()) {
                endpoint += `&itemName=${encodeURIComponent(itemName.trim())}`;
            }
            if (purchaseRequestType) {
                endpoint += `&purchaseRequestType=${encodeURIComponent(purchaseRequestType)}`;
            }
            if (purchaseRequestTypeCategory) {
                endpoint += `&purchaseRequestTypeCategory=${encodeURIComponent(purchaseRequestTypeCategory)}`;
            }
            if (purchaseRequestSubType) {
                endpoint += `&purchaseRequestSubType=${encodeURIComponent(purchaseRequestSubType)}`;
            }

            // Use apiHelper to call the API
            const data = await apiCall('Procurement', endpoint, 'GET');
            let items = data.data || data;

            if (!Array.isArray(items)) {
                items = [];
            }

            // Apply client-side filtering for all search fields
            if (items.length > 0 && (itemId || itemName || prodPool || coa)) {
                items = items.filter(item => {
                    const invItemId = (item.ItemID || item.itemID || item.ItemId || item.itemId || '').toString().toLowerCase();
                    const invItemName = (item.ItemName || item.itemName || item.Item || item.item || '').toString().toLowerCase();
                    const invPoolName = (item.PoolName || item.poolName || item.Pool || item.pool || '').toString().toLowerCase();
                    const invCOAId = (item.mstPROItemCOAId || item.mstPROItemCOAID || item.MstPROItemCOAId || item.mstproitemcoaid || item.COAId || item.coaId || '').toString().toLowerCase();

                    // Filter by Item ID
                    if (itemId && itemId.trim()) {
                        const searchItemId = itemId.trim().toLowerCase();
                        if (!invItemId.includes(searchItemId)) {
                            return false;
                        }
                    }

                    // Filter by Item Name
                    if (itemName && itemName.trim()) {
                        const searchItemName = itemName.trim().toLowerCase();
                        if (!invItemName.includes(searchItemName)) {
                            return false;
                        }
                    }

                    // Filter by Prod Pool
                    if (prodPool && prodPool.trim()) {
                        const searchProdPool = prodPool.trim().toLowerCase();
                        if (!invPoolName.includes(searchProdPool)) {
                            return false;
                        }
                    }

                    // Filter by COA
                    if (coa && coa.trim()) {
                        const searchCOA = coa.trim().toLowerCase();
                        if (!invCOAId.includes(searchCOA)) {
                            return false;
                        }
                    }

                    return true;
                });
            }

            if (items.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center">No items found</td>
                    </tr>
                `;
                return;
            }

            // Clear loading state and populate table
            tableBody.innerHTML = '';

            items.forEach(item => {
                // Handle case variations for property access
                const invItemId = item.ItemID || item.itemID || item.ItemId || item.itemId || '';
                const invItemName = item.ItemName || item.itemName || item.Item || item.item || '';
                const poolName = item.PoolName || item.poolName || item.Pool || item.pool || '-';
                const coaId = item.mstPROItemCOAId || item.mstPROItemCOAID || item.MstPROItemCOAId || item.mstproitemcoaid || item.COAId || item.coaId || '-';
                // Get ID (mstPROInventoryItemID) - this is the primary key from mstPROPurchaseItemInventory
                const mstPROInventoryItemID = item.ID || item.id || item.Id || null;
                // Get mstPROPurchaseItemUnitId for auto-select unit
                const mstPROPurchaseItemUnitId = item.mstPROPurchaseItemUnitId || item.mstPROPurchaseItemUnitID || item.MstPROPurchaseItemUnitId || null;

                // Create row and cells using DOM methods to avoid template literal issues
                const row = document.createElement('tr');

                // Action cell with button
                const actionCell = document.createElement('td');
                actionCell.style.textAlign = 'center';
                const selectBtn = document.createElement('button');
                selectBtn.type = 'button';
                selectBtn.className = 'btn btn-sm btn-primary action-select-btn action-btn';
                selectBtn.title = 'Select';
                selectBtn.style.width = '32px';
                selectBtn.style.height = '32px';
                selectBtn.style.padding = '0';
                selectBtn.style.display = 'flex';
                selectBtn.style.alignItems = 'center';
                selectBtn.style.justifyContent = 'center';
                selectBtn.style.margin = '0 auto';
                selectBtn.innerHTML = '<i class="bx bx-pointer" style="font-size: 16px; line-height: 1;"></i>';
                selectBtn.addEventListener('click', () => {
                    window.selectItem(invItemId, invItemName, mstPROInventoryItemID || null, mstPROPurchaseItemUnitId || null);
                });

                // Add hover effect: change from solid to outline on hover
                selectBtn.addEventListener('mouseenter', function() {
                    this.className = this.className.replace(/\bbtn-primary\b/g, 'btn-outline-primary');
                });
                selectBtn.addEventListener('mouseleave', function() {
                    this.className = this.className.replace(/\bbtn-outline-primary\b/g, 'btn-primary');
                });

                actionCell.appendChild(selectBtn);

                // Other cells
                const itemIdCell = document.createElement('td');
                itemIdCell.textContent = invItemId;

                const itemNameCell = document.createElement('td');
                itemNameCell.textContent = invItemName;

                const poolCell = document.createElement('td');
                poolCell.textContent = poolName;

                const coaCell = document.createElement('td');
                coaCell.textContent = coaId;

                // Append cells to row
                row.appendChild(actionCell);
                row.appendChild(itemIdCell);
                row.appendChild(itemNameCell);
                row.appendChild(poolCell);
                row.appendChild(coaCell);

                tableBody.appendChild(row);
            });

            // Store items for pagination
            if (!this.wizard.allChooseItems) {
                this.wizard.allChooseItems = [];
            }
            this.wizard.allChooseItems = items;

            // Update pagination with rows per page
            const rowsPerPageSelect = document.getElementById('chooseItemRowsPerPage');
            const rowsPerPage = rowsPerPageSelect ? parseInt(rowsPerPageSelect.value) : 10;
            if (this.wizard.updateChooseItemPagination) {
                this.wizard.updateChooseItemPagination(items.length, 1, rowsPerPage);
            }
            if (this.wizard.updateChooseItemTableVisibility) {
                this.wizard.updateChooseItemTableVisibility(1, rowsPerPage);
            }

        } catch (error) {
            console.error('Error loading items:', error);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-danger">
                        <i class="icon-base bx bx-error-circle me-2"></i>
                        Failed to load items. Please try again.
                        <br>
                        <small>${this.wizard.escapeHtml ? this.wizard.escapeHtml(error.message) : error.message}</small>
                    </td>
                </tr>
            `;
        }
    }

    /**
     * Load sites from API for site selection modal
     */
    async loadSites() {
        const tbody = document.getElementById('chooseSiteTableBody');
        const emptyMessage = document.getElementById('chooseSiteEmpty');

        if (!tbody) return;

        // Get search filters
        const searchSonumb = document.getElementById('searchSonumb')?.value || '';
        const searchSiteID = document.getElementById('searchSiteID')?.value || '';
        const searchSiteName = document.getElementById('searchSiteName')?.value || '';
        const searchOperator = document.getElementById('searchOperator')?.value || '';

        // Get rows per page
        const rowsPerPageSelect = document.getElementById('siteRowsPerPage');
        const rowsPerPage = rowsPerPageSelect ? parseInt(rowsPerPageSelect.value) : 10;

        // Show loading
        tbody.innerHTML = '<tr><td colspan="5" class="text-center"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div> Loading sites...</td></tr>';
        if (emptyMessage) emptyMessage.style.display = 'none';

        try {
            // Build endpoint with filters
            let endpoint = '/Procurement/Master/STIPSites';
            const params = new URLSearchParams();
            if (searchSonumb) params.append('sonumb', searchSonumb);
            if (searchSiteID) params.append('siteId', searchSiteID);
            if (searchSiteName) params.append('siteName', searchSiteName);
            if (searchOperator) params.append('operatorName', searchOperator);
            const queryString = params.toString();
            if (queryString) {
                endpoint += '?' + queryString;
            }

            const data = await apiCall('Procurement', endpoint, 'GET');
            const sites = Array.isArray(data) ? data : (data.data || []);

            if (!sites || sites.length === 0) {
                tbody.innerHTML = '';
                if (emptyMessage) emptyMessage.style.display = 'block';
                if (this.wizard.updateSitePagination) {
                    this.wizard.updateSitePagination(0, 1, rowsPerPage);
                }
                return;
            }

            // Paginate
            const startIndex = (this.wizard.currentSitePage - 1) * rowsPerPage;
            const endIndex = startIndex + rowsPerPage;
            const paginatedSites = sites.slice(startIndex, endIndex);

            // Render table
            tbody.innerHTML = '';
            paginatedSites.forEach(site => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${this.wizard.escapeHtml ? this.wizard.escapeHtml(site.Sonumb || site.sonumb || '') : (site.Sonumb || site.sonumb || '')}</td>
                    <td>${this.wizard.escapeHtml ? this.wizard.escapeHtml(site.SiteID || site.siteID || site.SiteId || site.siteId || '') : (site.SiteID || site.siteID || site.SiteId || site.siteId || '')}</td>
                    <td>${this.wizard.escapeHtml ? this.wizard.escapeHtml(site.SiteName || site.siteName || '') : (site.SiteName || site.siteName || '')}</td>
                    <td>${this.wizard.escapeHtml ? this.wizard.escapeHtml(site.OperatorName || site.operatorName || '') : (site.OperatorName || site.operatorName || '')}</td>
                    <td style="text-align: center;">
                        <button type="button" class="btn btn-sm btn-primary select-site-btn action-btn"
                                data-sonumb="${this.wizard.escapeHtml ? this.wizard.escapeHtml(site.Sonumb || site.sonumb || '') : (site.Sonumb || site.sonumb || '')}"
                                data-site-id="${this.wizard.escapeHtml ? this.wizard.escapeHtml(site.SiteID || site.siteID || site.SiteId || site.siteId || '') : (site.SiteID || site.siteID || site.SiteId || site.siteId || '')}"
                                data-site-name="${this.wizard.escapeHtml ? this.wizard.escapeHtml(site.SiteName || site.siteName || '') : (site.SiteName || site.siteName || '')}"
                                style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                            <i class="bx bx-pointer" style="font-size: 16px; line-height: 1;"></i>
                        </button>
                    </td>
                `;

                // Add click event to select button
                const selectBtn = row.querySelector('.select-site-btn');
                if (selectBtn) {
                    selectBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();

                        const sonumb = selectBtn.getAttribute('data-sonumb');
                        const siteId = selectBtn.getAttribute('data-site-id');
                        const siteName = selectBtn.getAttribute('data-site-name');

                        // Get site ID from site object (use ID or sonumb as ID)
                        const id = site.ID || site.id || site.SonumbID || site.sonumbID || sonumb || null;

                        if (this.wizard.selectSite) {
                            this.wizard.selectSite(id, sonumb, siteId, siteName);
                        } else if (this.wizard.siteModule && this.wizard.siteModule.selectSite) {
                            this.wizard.siteModule.selectSite(id, sonumb, siteId, siteName);
                        } else {
                            console.error('selectSite function not available');
                        }
                    });

                    // Add hover effect: change from solid to outline on hover
                    selectBtn.addEventListener('mouseenter', function() {
                        this.className = this.className.replace(/\bbtn-primary\b/g, 'btn-outline-primary');
                    });
                    selectBtn.addEventListener('mouseleave', function() {
                        this.className = this.className.replace(/\bbtn-outline-primary\b/g, 'btn-primary');
                    });
                }

                tbody.appendChild(row);
            });

            // Update pagination
            const totalPages = Math.ceil(sites.length / rowsPerPage);
            if (this.wizard.updateSitePagination) {
                this.wizard.updateSitePagination(sites.length, this.wizard.currentSitePage, rowsPerPage);
            }

        } catch (error) {
            console.error('Error loading sites:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-danger">
                        <i class="icon-base bx bx-error-circle me-2"></i>
                        Failed to load sites. Please try again.
                    </td>
                </tr>
            `;
            if (emptyMessage) emptyMessage.style.display = 'block';
        }
    }

    /**
     * Get employee name by employee ID
     */
    async getEmployeeNameByEmployId(employId) {
        // Use shared cache if available
        if (this.sharedCache && this.sharedCache.getEmployeeNameByEmployId) {
            return this.sharedCache.getEmployeeNameByEmployId(employId);
        }

        // Fallback to local cache
        if (!employId || employId === '-') return '';

        // Check cache first
        const cacheKey = employId.trim().toLowerCase();
        if (this.employeeNameCache.has(cacheKey)) {
            return this.employeeNameCache.get(cacheKey);
        }

        // Check if lookup is already pending
        if (this.pendingEmployeeLookups.has(cacheKey)) {
            // Wait for pending lookup to complete
            return await this.pendingEmployeeLookups.get(cacheKey);
        }

        // Create promise for this lookup
        const lookupPromise = (async () => {
            try {
                // Call API to get employee by employ_id
                const endpoint = `/Procurement/Master/Employees?searchTerm=${encodeURIComponent(employId)}`;
                const data = await apiCall('Procurement', endpoint, 'GET');
                const employees = data.data || data;

                if (Array.isArray(employees) && employees.length > 0) {
                    // Find employee with exact matching Employ_Id (case-insensitive)
                    const employee = employees.find(emp => {
                        const empId = emp.Employ_Id || emp.employ_Id || emp.EmployId || emp.employeeId || '';
                        return empId.trim().toLowerCase() === employId.trim().toLowerCase();
                    });

                    if (employee) {
                        const name = employee.Name || employee.name || employee.Employ_Id || employee.employ_Id || '';
                        // Cache the result
                        this.employeeNameCache.set(cacheKey, name);
                        return name;
                    }
                }

                // Cache empty result to avoid repeated lookups
                this.employeeNameCache.set(cacheKey, '');
                return '';
            } catch (error) {
                console.error('Error getting employee name:', error);
                // Cache empty result on error
                this.employeeNameCache.set(cacheKey, '');
                return '';
            } finally {
                // Remove from pending lookups
                this.pendingEmployeeLookups.delete(cacheKey);
            }
        })();

        // Store pending lookup
        this.pendingEmployeeLookups.set(cacheKey, lookupPromise);

        return await lookupPromise;
    }

    /**
     * Load items from API
     */
    async loadItemsFromApi(itemId = null, itemName = null, prodPool = null, coa = null) {
        const tableBody = document.getElementById('chooseItemTableBody');
        if (!tableBody) return;

        // Show loading state
        tableBody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center">
                    <div class="spinner-border spinner-border-sm me-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    Loading items...
                </td>
            </tr>
        `;

        try {
            // Get PurchaseRequestType, Category, and PurchaseRequestSubType from Basic Information form
            const purchaseRequestTypeSelect = document.getElementById('PurchaseRequestType');
            const purchaseRequestTypeCategorySelect = document.getElementById('PurchaseRequestTypeCategory');
            const purchaseRequestSubTypeSelect = document.getElementById('PurchaseRequestSubType');
            const purchaseRequestType = purchaseRequestTypeSelect ? purchaseRequestTypeSelect.value : null;
            const purchaseRequestTypeCategory = purchaseRequestTypeCategorySelect ? purchaseRequestTypeCategorySelect.value : null;
            const purchaseRequestSubType = purchaseRequestSubTypeSelect ? purchaseRequestSubTypeSelect.value : null;

            // Build endpoint - API may only support itemName, so we'll load all and filter client-side
            let endpoint = '/Procurement/Master/Inventories?isActive=true';
            // Only use itemName in API call if provided (API likely supports this)
            // Note: We don't send itemId, prodPool, or coa to API as it may not support them
            // Instead, we'll filter client-side for all fields
            if (itemName && itemName.trim()) {
                endpoint += `&itemName=${encodeURIComponent(itemName.trim())}`;
            }
            if (purchaseRequestType) {
                endpoint += `&purchaseRequestType=${encodeURIComponent(purchaseRequestType)}`;
            }
            if (purchaseRequestTypeCategory) {
                endpoint += `&purchaseRequestTypeCategory=${encodeURIComponent(purchaseRequestTypeCategory)}`;
            }
            if (purchaseRequestSubType) {
                endpoint += `&purchaseRequestSubType=${encodeURIComponent(purchaseRequestSubType)}`;
            }

            // Use apiHelper to call the API
            const data = await apiCall('Procurement', endpoint, 'GET');
            let items = data.data || data;

            if (!Array.isArray(items)) {
                items = [];
            }

            // Apply client-side filtering for all search fields
            // This ensures all search fields work even if API doesn't support all parameters
            if (items.length > 0 && (itemId || itemName || prodPool || coa)) {
                items = items.filter(item => {
                    const invItemId = (item.ItemID || item.itemID || item.ItemId || item.itemId || '').toString().toLowerCase();
                    const invItemName = (item.ItemName || item.itemName || item.Item || item.item || '').toString().toLowerCase();
                    const invPoolName = (item.PoolName || item.poolName || item.Pool || item.pool || '').toString().toLowerCase();
                    const invCOAId = (item.mstPROItemCOAId || item.mstPROItemCOAID || item.MstPROItemCOAId || item.mstproitemcoaid || item.COAId || item.coaId || '').toString().toLowerCase();

                    // Filter by Item ID
                    if (itemId && itemId.trim()) {
                        const searchItemId = itemId.trim().toLowerCase();
                        if (!invItemId.includes(searchItemId)) {
                            return false;
                        }
                    }

                    // Filter by Item Name (always filter client-side for consistency)
                    if (itemName && itemName.trim()) {
                        const searchItemName = itemName.trim().toLowerCase();
                        if (!invItemName.includes(searchItemName)) {
                            return false;
                        }
                    }

                    // Filter by Prod Pool
                    if (prodPool && prodPool.trim()) {
                        const searchProdPool = prodPool.trim().toLowerCase();
                        if (!invPoolName.includes(searchProdPool)) {
                            return false;
                        }
                    }

                    // Filter by COA
                    if (coa && coa.trim()) {
                        const searchCOA = coa.trim().toLowerCase();
                        if (!invCOAId.includes(searchCOA)) {
                            return false;
                        }
                    }

                    return true;
                });
            }

            if (items.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center">No items found</td>
                    </tr>
                `;
                return;
            }

            // Clear loading state and populate table
            tableBody.innerHTML = '';

            items.forEach(item => {
                // Handle case variations for property access
                const invItemId = item.ItemID || item.itemID || item.ItemId || item.itemId || '';
                const invItemName = item.ItemName || item.itemName || item.Item || item.item || '';
                const poolName = item.PoolName || item.poolName || item.Pool || item.pool || '-';
                const coaId = item.mstPROItemCOAId || item.mstPROItemCOAID || item.MstPROItemCOAId || item.mstproitemcoaid || item.COAId || item.coaId || '-';
                // Get ID (mstPROInventoryItemID) - this is the primary key from mstPROPurchaseItemInventory
                const mstPROInventoryItemID = item.ID || item.id || item.Id || null;
                // Get mstPROPurchaseItemUnitId for auto-select unit
                const mstPROPurchaseItemUnitId = item.mstPROPurchaseItemUnitId || item.mstPROPurchaseItemUnitID || item.MstPROPurchaseItemUnitId || null;

                // Create row and cells using DOM methods to avoid template literal issues
                const row = document.createElement('tr');

                // Action cell with button
                const actionCell = document.createElement('td');
                actionCell.style.textAlign = 'center';
                const selectBtn = document.createElement('button');
                selectBtn.type = 'button';
                selectBtn.className = 'btn btn-sm btn-primary action-select-btn action-btn';
                selectBtn.title = 'Select';
                selectBtn.style.width = '32px';
                selectBtn.style.height = '32px';
                selectBtn.style.padding = '0';
                selectBtn.style.display = 'flex';
                selectBtn.style.alignItems = 'center';
                selectBtn.style.justifyContent = 'center';
                selectBtn.style.margin = '0 auto';
                selectBtn.innerHTML = '<i class="bx bx-pointer" style="font-size: 16px; line-height: 1;"></i>';
                selectBtn.addEventListener('click', () => {
                    window.selectItem(invItemId, invItemName, mstPROInventoryItemID || null, mstPROPurchaseItemUnitId || null);
                });

                // Add hover effect: change from solid to outline on hover
                selectBtn.addEventListener('mouseenter', function() {
                    this.className = this.className.replace(/\bbtn-primary\b/g, 'btn-outline-primary');
                });
                selectBtn.addEventListener('mouseleave', function() {
                    this.className = this.className.replace(/\bbtn-outline-primary\b/g, 'btn-primary');
                });

                actionCell.appendChild(selectBtn);

                // Other cells
                const itemIdCell = document.createElement('td');
                itemIdCell.textContent = invItemId;

                const itemNameCell = document.createElement('td');
                itemNameCell.textContent = invItemName;

                const poolCell = document.createElement('td');
                poolCell.textContent = poolName;

                const coaCell = document.createElement('td');
                coaCell.textContent = coaId;

                // Append cells to row
                row.appendChild(actionCell);
                row.appendChild(itemIdCell);
                row.appendChild(itemNameCell);
                row.appendChild(poolCell);
                row.appendChild(coaCell);

                tableBody.appendChild(row);
            });

            // Store items for pagination
            if (!this.wizard.allChooseItems) {
                this.wizard.allChooseItems = [];
            }
            this.wizard.allChooseItems = items;

            // Update pagination with rows per page
            const rowsPerPageSelect = document.getElementById('chooseItemRowsPerPage');
            const rowsPerPage = rowsPerPageSelect ? parseInt(rowsPerPageSelect.value) : 10;
            if (this.wizard.itemsModule && this.wizard.itemsModule.updateChooseItemPagination) {
                this.wizard.itemsModule.updateChooseItemPagination(items.length, 1, rowsPerPage);
                this.wizard.itemsModule.updateChooseItemTableVisibility(1, rowsPerPage);
            }

        } catch (error) {
            console.error('Error loading items:', error);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-danger">
                        <i class="icon-base bx bx-error-circle me-2"></i>
                        Failed to load items. Please try again.
                        <br>
                        <small>${this.wizard.escapeHtml ? this.wizard.escapeHtml(error.message) : error.message}</small>
                    </td>
                </tr>
            `;
        }
    }

    /**
     * Load units from API
     */
    async loadUnitsFromApi() {
        const unitHiddenInput = document.getElementById('unit');
        const unitDropdownItems = document.getElementById('unitDropdownItems');
        const unitSelectedText = document.getElementById('unitSelectedText');
        const unitSearchInput = document.getElementById('unitSearchInput');

        if (!unitHiddenInput || !unitDropdownItems) return;

        // Show loading state
        unitDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Loading units...</div>';

        try {
            // Call the API to get units
            const endpoint = '/Procurement/Master/Units?isActive=true';
            const data = await apiCall('Procurement', endpoint, 'GET');
            const units = data.data || data;

            if (!Array.isArray(units) || units.length === 0) {
                unitDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No units found</div>';
                console.warn('No units found');
                return;
            }

            // Store all units for filtering
            if (!this.wizard.allUnits) {
                this.wizard.allUnits = [];
            }
            this.wizard.allUnits = units;

            // Render units (initially show first 5)
            if (this.wizard.itemsModule && this.wizard.itemsModule.renderUnitDropdown) {
                this.wizard.itemsModule.renderUnitDropdown(units, '');
            }

            // Setup search functionality
            if (unitSearchInput) {
                // Remove existing event listeners by cloning
                const newSearchInput = unitSearchInput.cloneNode(true);
                unitSearchInput.parentNode.replaceChild(newSearchInput, unitSearchInput);

                newSearchInput.addEventListener('input', (e) => {
                    const searchTerm = e.target.value.toLowerCase().trim();
                    if (this.wizard.itemsModule && this.wizard.itemsModule.renderUnitDropdown) {
                        this.wizard.itemsModule.renderUnitDropdown(units, searchTerm);
                    }
                });

                // Prevent dropdown from closing when clicking on search input
                newSearchInput.addEventListener('click', (e) => {
                    e.stopPropagation();
                });
            }

            // Focus search input when dropdown opens
            const unitDropdownBtn = document.getElementById('unitDropdownBtn');
            if (unitDropdownBtn) {
                unitDropdownBtn.addEventListener('shown.bs.dropdown', () => {
                    const searchInput = document.getElementById('unitSearchInput');
                    if (searchInput) {
                        setTimeout(() => {
                            searchInput.focus();
                        }, 100);
                    }
                });
            }

        } catch (error) {
            console.error('Error loading units:', error);
            unitDropdownItems.innerHTML = '<div class="px-3 py-2 text-danger text-center">Error loading units</div>';
        }
    }

    /**
     * Load currencies from API
     */
    async loadCurrenciesFromApi() {
        const currencyHiddenInput = document.getElementById('currency');
        const currencyDropdownItems = document.getElementById('currencyDropdownItems');
        const currencySelectedText = document.getElementById('currencySelectedText');
        const currencySearchInput = document.getElementById('currencySearchInput');

        if (!currencyHiddenInput || !currencyDropdownItems) return;

        // Show loading state
        currencyDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Loading currencies...</div>';

        try {
            // Call the API to get currencies
            const endpoint = '/Procurement/Master/Currencies?isActive=true';
            const data = await apiCall('Procurement', endpoint, 'GET');
            const currencies = data.data || data;

            if (!Array.isArray(currencies) || currencies.length === 0) {
                currencyDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No currencies found</div>';
                console.warn('No currencies found');
                return;
            }

            // Store all currencies for filtering
            if (!this.wizard.allCurrencies) {
                this.wizard.allCurrencies = [];
            }
            this.wizard.allCurrencies = currencies;

            // Create callback function for currency selection
            const self = this;
            const currencySelectCallback = (item, value, text) => {
                // Remove validation error when currency is selected
                const currencyDropdownBtn = document.getElementById('currencyDropdownBtn');
                if (currencyDropdownBtn) {
                    currencyDropdownBtn.classList.remove('is-invalid');
                    if (self.wizard.validationModule && self.wizard.validationModule.hideFieldError) {
                        self.wizard.validationModule.hideFieldError(currencyDropdownBtn);
                    }
                }
                // Re-render dropdown with cleared search
                if (self.wizard.renderDropdownWithSearch) {
                    self.wizard.renderDropdownWithSearch({
                        items: currencies,
                        searchTerm: '',
                        dropdownItemsId: 'currencyDropdownItems',
                        hiddenInputId: 'currency',
                        selectedTextId: 'currencySelectedText',
                        searchInputId: 'currencySearchInput',
                        dropdownBtnId: 'currencyDropdownBtn',
                        getValue: (currency) => currency.CurrencyCode || currency.currencyCode || '',
                        getText: (currency) => currency.CurrencyCode || currency.currencyCode || '',
                        getSearchableText: (currency) => (currency.CurrencyCode || currency.currencyCode || '').toString(),
                        limit: 5,
                        onItemSelect: currencySelectCallback
                    });
                }
            };

            // Render currencies (initially show first 5)
            if (this.wizard.renderDropdownWithSearch) {
                this.wizard.renderDropdownWithSearch({
                    items: currencies,
                    searchTerm: '',
                    dropdownItemsId: 'currencyDropdownItems',
                    hiddenInputId: 'currency',
                    selectedTextId: 'currencySelectedText',
                    searchInputId: 'currencySearchInput',
                    dropdownBtnId: 'currencyDropdownBtn',
                    getValue: (currency) => currency.CurrencyCode || currency.currencyCode || '',
                    getText: (currency) => currency.CurrencyCode || currency.currencyCode || '',
                    getSearchableText: (currency) => (currency.CurrencyCode || currency.currencyCode || '').toString(),
                    limit: 5,
                    onItemSelect: currencySelectCallback
                });
            }

            // Setup search functionality
            if (currencySearchInput) {
                const newSearchInput = currencySearchInput.cloneNode(true);
                currencySearchInput.parentNode.replaceChild(newSearchInput, currencySearchInput);

                newSearchInput.addEventListener('input', (e) => {
                    const searchTerm = e.target.value.toLowerCase().trim();
                    const self = this;
                    const currencySelectCallback = (item, value, text) => {
                        // Remove validation error when currency is selected
                        const currencyDropdownBtn = document.getElementById('currencyDropdownBtn');
                        if (currencyDropdownBtn) {
                            currencyDropdownBtn.classList.remove('is-invalid');
                            if (self.wizard.validationModule && self.wizard.validationModule.hideFieldError) {
                                self.wizard.validationModule.hideFieldError(currencyDropdownBtn);
                            }
                        }
                        // Re-render dropdown with cleared search
                        if (self.wizard.renderDropdownWithSearch) {
                            self.wizard.renderDropdownWithSearch({
                                items: currencies,
                                searchTerm: '',
                                dropdownItemsId: 'currencyDropdownItems',
                                hiddenInputId: 'currency',
                                selectedTextId: 'currencySelectedText',
                                searchInputId: 'currencySearchInput',
                                dropdownBtnId: 'currencyDropdownBtn',
                                getValue: (currency) => currency.CurrencyCode || currency.currencyCode || '',
                                getText: (currency) => currency.CurrencyCode || currency.currencyCode || '',
                                getSearchableText: (currency) => (currency.CurrencyCode || currency.currencyCode || '').toString(),
                                limit: 5,
                                onItemSelect: currencySelectCallback
                            });
                        }
                    };
                    if (this.wizard.renderDropdownWithSearch) {
                        this.wizard.renderDropdownWithSearch({
                            items: currencies,
                            searchTerm: searchTerm,
                            dropdownItemsId: 'currencyDropdownItems',
                            hiddenInputId: 'currency',
                            selectedTextId: 'currencySelectedText',
                            searchInputId: 'currencySearchInput',
                            dropdownBtnId: 'currencyDropdownBtn',
                            getValue: (currency) => currency.CurrencyCode || currency.currencyCode || '',
                            getText: (currency) => currency.CurrencyCode || currency.currencyCode || '',
                            getSearchableText: (currency) => (currency.CurrencyCode || currency.currencyCode || '').toString(),
                            limit: 5,
                            onItemSelect: currencySelectCallback
                        });
                    }
                });

                newSearchInput.addEventListener('click', (e) => {
                    e.stopPropagation();
                });
            }

            // Focus search input when dropdown opens
            const currencyDropdownBtn = document.getElementById('currencyDropdownBtn');
            if (currencyDropdownBtn) {
                currencyDropdownBtn.addEventListener('shown.bs.dropdown', () => {
                    const searchInput = document.getElementById('currencySearchInput');
                    if (searchInput) {
                        setTimeout(() => {
                            searchInput.focus();
                        }, 100);
                    }
                });
            }

            // Auto-fill: Select 'IDR' currency if available
            const idrCurrency = currencies.find(c =>
                (c.CurrencyCode || c.currencyCode || '').toUpperCase() === 'IDR'
            );
            if (idrCurrency && currencyHiddenInput && currencySelectedText) {
                const idrValue = idrCurrency.CurrencyCode || idrCurrency.currencyCode || '';
                currencyHiddenInput.value = idrValue;
                currencySelectedText.textContent = idrValue;
            } else if (currencies.length > 0 && currencyHiddenInput && currencySelectedText) {
                // If IDR not found, select first currency as fallback
                const firstCurrency = currencies[0];
                const firstCurrencyValue = firstCurrency.CurrencyCode || firstCurrency.currencyCode || '';
                if (firstCurrencyValue) {
                    currencyHiddenInput.value = firstCurrencyValue;
                    currencySelectedText.textContent = firstCurrencyValue;
                }
            }

        } catch (error) {
            console.error('Error loading currencies:', error);
            currencyDropdownItems.innerHTML = '<div class="px-3 py-2 text-danger text-center">Error loading currencies</div>';
        }
    }

    /**
     * Fill approval form with PR data
     * Delegates to ProcurementWizardApproval module
     */
    async fillApprovalForm(pr) {
        if (this.wizard.approvalModule && typeof this.wizard.approvalModule.fillApprovalForm === 'function') {
            return this.wizard.approvalModule.fillApprovalForm(pr);
        }
        console.warn('ProcurementWizardApproval module or fillApprovalForm method not available');
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.ProcurementWizardAPI = ProcurementWizardAPI;
}

