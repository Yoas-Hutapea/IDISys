{{--
    Bulky Release Action Partial for Purchase Request Release
    Contains form for bulk release with vendor and contract information
    This is a separate partial for BulkyReleasePage to avoid conflicts with ViewReleasePartial
--}}

<div class="card">
    <div class="card-header">
        <h6 class="card-title mb-0">Action</h6>
    </div>
    <div class="card-body">
        <form id="bulkyReleaseForm">
            <div class="row g-3">
                <!-- Decision -->
                <div class="col-md-6" id="bulkyPageDecisionGroup">
                    <label class="form-label fw-semibold">Decision <span class="text-danger">*</span></label>
                    <select class="form-select" id="bulkyPageReleaseDecision" name="decision" required>
                        <option value="" disabled selected>Select Decision</option>
                        <option value="Release">Release</option>
                        <option value="Reject">Reject</option>
                    </select>
                </div>

                <!-- Vendor Type -->
                <div class="col-md-6" id="bulkyPageVendorTypeGroup">
                    <label class="form-label fw-semibold">Vendor Type <span class="text-danger">*</span></label>
                    <div class="mt-2">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="vendorType" id="bulkyPageVendorTypeContract" value="Contract" checked required>
                            <label class="form-check-label" for="bulkyPageVendorTypeContract">Contract</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="vendorType" id="bulkyPageVendorTypeNonContract" value="Non Contract" required>
                            <label class="form-check-label" for="bulkyPageVendorTypeNonContract">Non Contract</label>
                        </div>
                    </div>
                </div>

                <!-- Select Vendor -->
                <div class="col-md-6" id="bulkyPageSelectVendorGroup">
                    <label class="form-label fw-semibold">Select Vendor <span class="text-danger">*</span></label>
                    <div class="dropdown" id="bulkyPageSelectVendorDropdownContainer">
                        <button class="form-select text-start custom-dropdown-toggle" type="button" id="bulkyPageSelectVendorDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                            <span id="bulkyPageSelectVendorSelectedText">Select Vendor</span>
                        </button>
                        <input type="hidden" id="bulkyPageSelectVendor" name="vendorId" required>
                        <ul class="dropdown-menu w-100 custom-dropdown-menu" id="bulkyPageSelectVendorDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                            <li class="px-3 py-2">
                                <input type="text" class="form-control form-control-sm custom-dropdown-search-input" id="bulkyPageSelectVendorSearchInput" placeholder="Search vendor..." autocomplete="off">
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li id="bulkyPageSelectVendorDropdownItems">
                                <div class="px-3 py-2 text-muted text-center">Loading vendors...</div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Core Business -->
                <div class="col-md-6" id="bulkyPageCoreBusinessGroup">
                    <label class="form-label fw-semibold">Core Business <span class="text-danger">*</span></label>
                    <div class="dropdown" id="bulkyPageCoreBusinessDropdownContainer">
                        <button class="form-select text-start custom-dropdown-toggle" type="button" id="bulkyPageCoreBusinessDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                            <span id="bulkyPageCoreBusinessSelectedText">Select Core Business</span>
                        </button>
                        <input type="hidden" id="bulkyPageCoreBusiness" name="coreBusiness" required>
                        <ul class="dropdown-menu w-100 custom-dropdown-menu" id="bulkyPageCoreBusinessDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                            <li class="px-3 py-2">
                                <input type="text" class="form-control form-control-sm custom-dropdown-search-input" id="bulkyPageCoreBusinessSearchInput" placeholder="Search core business..." autocomplete="off">
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li id="bulkyPageCoreBusinessDropdownItems">
                                <div class="px-3 py-2 text-muted text-center">Select Core Business</div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Sub Core Business -->
                <div class="col-md-6" id="bulkyPageSubCoreBusinessGroup">
                    <label class="form-label fw-semibold">Sub Core Business <span class="text-danger">*</span></label>
                    <div class="dropdown" id="bulkyPageSubCoreBusinessDropdownContainer">
                        <button class="form-select text-start custom-dropdown-toggle" type="button" id="bulkyPageSubCoreBusinessDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                            <span id="bulkyPageSubCoreBusinessSelectedText">Select Sub Core Business</span>
                        </button>
                        <input type="hidden" id="bulkyPageSubCoreBusiness" name="subCoreBusiness" required>
                        <ul class="dropdown-menu w-100 custom-dropdown-menu" id="bulkyPageSubCoreBusinessDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                            <li class="px-3 py-2">
                                <input type="text" class="form-control form-control-sm custom-dropdown-search-input" id="bulkyPageSubCoreBusinessSearchInput" placeholder="Search sub core business..." autocomplete="off">
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li id="bulkyPageSubCoreBusinessDropdownItems">
                                <div class="px-3 py-2 text-muted text-center">Select Sub Core Business</div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Contract No (only shown when Vendor Type is Contract) -->
                <div class="col-md-6" id="bulkyPageContractNoGroup" style="display: block;">
                    <label class="form-label fw-semibold">Contract No <span class="text-danger">*</span></label>
                    <div class="dropdown" id="bulkyPageContractNoDropdownContainer">
                        <button class="form-select text-start custom-dropdown-toggle" type="button" id="bulkyPageContractNoDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                            <span id="bulkyPageContractNoSelectedText">Select Contract Number</span>
                        </button>
                        <input type="hidden" id="bulkyPageContractNo" name="contractNo" required>
                        <ul class="dropdown-menu w-100 custom-dropdown-menu" id="bulkyPageContractNoDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                            <li class="px-3 py-2">
                                <input type="text" class="form-control form-control-sm custom-dropdown-search-input" id="bulkyPageContractNoSearchInput" placeholder="Search contract number..." autocomplete="off">
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li id="bulkyPageContractNoDropdownItems">
                                <div class="px-3 py-2 text-muted text-center">Select Contract Number</div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Contract Period (only shown when Vendor Type is Contract) -->
                <div class="col-md-6" id="bulkyPageContractPeriodGroup" style="display: block;">
                    <label class="form-label fw-semibold">Contract Period <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="bulkyPageContractPeriod" name="contractPeriod" disabled placeholder="Will be auto-filled from contract number">
                </div>

                <!-- Top Type -->
                <div class="col-md-6" id="bulkyPageTopTypeGroup">
                    <label class="form-label fw-semibold">Top Type <span class="text-danger">*</span></label>
                    <div class="dropdown" id="bulkyPageTopTypeDropdownContainer">
                        <button class="form-select text-start custom-dropdown-toggle" type="button" id="bulkyPageTopTypeDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                            <span id="bulkyPageTopTypeSelectedText">Select Top Type</span>
                        </button>
                        <input type="hidden" id="bulkyPageTopType" name="topType" required>
                        <ul class="dropdown-menu w-100 custom-dropdown-menu" id="bulkyPageTopTypeDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                            <li class="px-3 py-2">
                                <input type="text" class="form-control form-control-sm custom-dropdown-search-input" id="bulkyPageTopTypeSearchInput" placeholder="Search top type..." autocomplete="off">
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li id="bulkyPageTopTypeDropdownItems">
                                <div class="px-3 py-2 text-muted text-center">Loading term of payment...</div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Description -->
                <div class="col-12" id="bulkyPageDescriptionGroup">
                    <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="bulkyPageDescription" name="description" rows="4" placeholder="Will be auto-filled from Top Type" disabled required></textarea>
                </div>

                <!-- Remark Approval -->
                <div class="col-12" id="bulkyPageRemarkApprovalGroup">
                    <label class="form-label fw-semibold">Remark Approval</label>
                    <textarea class="form-control" id="bulkyPageRemarkApproval" name="remarkApproval" rows="3" placeholder="Enter remark approval"></textarea>
                </div>
            </div>
        </form>
    </div>
</div>

    @include('procurement.purchase_request.release.bulky_release._SharedReleaseDataLoader')

<script>
    (function() {
        'use strict';

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
                getTitle
            } = config;

            const dropdownItems = document.getElementById(dropdownItemsId);
            const hiddenInput = document.getElementById(hiddenInputId);
            const selectedText = document.getElementById(selectedTextId);

            if (!dropdownItems) return;

            // Filter items based on search term
            let filteredItems = items;
            if (searchTerm) {
                filteredItems = items.filter(item => {
                    const searchableText = getSearchableText ? getSearchableText(item) : getText(item);
                    return searchableText.toLowerCase().includes(searchTerm.toLowerCase());
                });
            }

            // Clear existing items
            dropdownItems.innerHTML = '';

            if (filteredItems.length === 0) {
                dropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No items found</div>';
                return;
            }

            // Render all items (scroll bar will appear if more than 5 items due to max-height: 300px)
            filteredItems.forEach(item => {
                const value = getValue(item);
                const text = getText(item);

                const li = document.createElement('li');
                li.className = 'dropdown-item custom-dropdown-item';
                li.style.cursor = 'pointer';
                li.textContent = text;
                if (typeof getTitle === 'function') {
                    const titleText = getTitle(item);
                    if (titleText) li.setAttribute('title', titleText);
                }

                li.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    // Set selected value
                    if (hiddenInput) hiddenInput.value = value;
                    if (selectedText) selectedText.textContent = text;

                    // Clear search input
                    const searchInput = document.getElementById(searchInputId);
                    if (searchInput) {
                        searchInput.value = '';
                    }

                    // Re-render dropdown with cleared search
                    renderDropdownWithSearch({
                        ...config,
                        searchTerm: ''
                    });

                    // Close dropdown
                    const dropdown = bootstrap.Dropdown.getInstance(document.getElementById(dropdownBtnId));
                    if (dropdown) {
                        dropdown.hide();
                    }

                    // Trigger change event for validation and cascading
                    if (hiddenInput) {
                        hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });

                dropdownItems.appendChild(li);
            });
        }

        // Function to toggle field visibility based on Decision and Vendor Type for Bulky Page
        function toggleBulkyPageReleaseFields() {
            // Find the form container - look for form with bulkyPageReleaseDecision inside
            const decisionSelect = document.getElementById('bulkyPageReleaseDecision');
            if (!decisionSelect) return;
            const form = decisionSelect.closest('form');
            if (!form || form.id !== 'bulkyReleaseForm') return;

            const vendorTypeContract = form.querySelector('#bulkyPageVendorTypeContract');
            const vendorTypeNonContract = form.querySelector('#bulkyPageVendorTypeNonContract');

            // Check if elements exist
            if (!decisionSelect || !vendorTypeContract || !vendorTypeNonContract) {
                return;
            }

            const decision = decisionSelect.value;
            const isContract = vendorTypeContract.checked;
            const isNonContract = vendorTypeNonContract.checked;

            // Get all field groups within the form
            const vendorTypeGroup = form.querySelector('#bulkyPageVendorTypeGroup');
            const selectVendorGroup = form.querySelector('#bulkyPageSelectVendorGroup');
            const coreBusinessGroup = form.querySelector('#bulkyPageCoreBusinessGroup');
            const subCoreBusinessGroup = form.querySelector('#bulkyPageSubCoreBusinessGroup');
            const contractNoGroup = form.querySelector('#bulkyPageContractNoGroup');
            const contractPeriodGroup = form.querySelector('#bulkyPageContractPeriodGroup');
            const topTypeGroup = form.querySelector('#bulkyPageTopTypeGroup');
            const descriptionGroup = form.querySelector('#bulkyPageDescriptionGroup');
            const remarkApprovalGroup = form.querySelector('#bulkyPageRemarkApprovalGroup');

            // Get form inputs for required attribute management
            const selectVendor = form.querySelector('#bulkyPageSelectVendor');
            const coreBusiness = form.querySelector('#bulkyPageCoreBusiness');
            const subCoreBusiness = form.querySelector('#bulkyPageSubCoreBusiness');
            const contractNoInput = form.querySelector('#bulkyPageContractNo');
            const contractPeriodInput = form.querySelector('#bulkyPageContractPeriod');
            const topType = form.querySelector('#bulkyPageTopType');
            const description = form.querySelector('#bulkyPageDescription');

            if (decision === 'Reject') {
                // When Reject: Show only Decision and Remark Approval
                if (vendorTypeGroup) vendorTypeGroup.style.display = 'none';
                if (selectVendorGroup) selectVendorGroup.style.display = 'none';
                if (coreBusinessGroup) coreBusinessGroup.style.display = 'none';
                if (subCoreBusinessGroup) subCoreBusinessGroup.style.display = 'none';
                if (contractNoGroup) contractNoGroup.style.display = 'none';
                if (contractPeriodGroup) contractPeriodGroup.style.display = 'none';
                if (topTypeGroup) topTypeGroup.style.display = 'none';
                if (descriptionGroup) descriptionGroup.style.display = 'none';
                if (remarkApprovalGroup) remarkApprovalGroup.style.display = 'block';

                // Remove required attributes
                if (selectVendor) selectVendor.removeAttribute('required');
                if (coreBusiness) coreBusiness.removeAttribute('required');
                if (subCoreBusiness) subCoreBusiness.removeAttribute('required');
                if (contractNoInput) contractNoInput.removeAttribute('required');
                if (contractPeriodInput) contractPeriodInput.removeAttribute('required');
                if (topType) topType.removeAttribute('required');
                if (description) description.removeAttribute('required');
            } else if (decision === 'Release') {
                // When Release: Show fields based on Vendor Type
                if (remarkApprovalGroup) remarkApprovalGroup.style.display = 'block';

                if (isNonContract) {
                    // Non Contract: Show Decision, Vendor Type, Select Vendor, Top Type, Description, Remark Approval
                    if (vendorTypeGroup) vendorTypeGroup.style.display = 'block';
                    if (selectVendorGroup) selectVendorGroup.style.display = 'block';
                    if (coreBusinessGroup) coreBusinessGroup.style.display = 'none';
                    if (subCoreBusinessGroup) subCoreBusinessGroup.style.display = 'none';
                    if (contractNoGroup) contractNoGroup.style.display = 'none';
                    if (contractPeriodGroup) contractPeriodGroup.style.display = 'none';
                    if (topTypeGroup) topTypeGroup.style.display = 'block';
                    if (descriptionGroup) descriptionGroup.style.display = 'block';

                    // Set required attributes
                    if (selectVendor) selectVendor.setAttribute('required', 'required');
                    if (topType) topType.setAttribute('required', 'required');
                    if (description) description.setAttribute('required', 'required');

                    // Remove required attributes for hidden fields
                    if (coreBusiness) coreBusiness.removeAttribute('required');
                    if (subCoreBusiness) subCoreBusiness.removeAttribute('required');
                    if (contractNoInput) contractNoInput.removeAttribute('required');
                    if (contractPeriodInput) contractPeriodInput.removeAttribute('required');

                    // Clear hidden field values
                    if (coreBusiness) coreBusiness.value = '';
                    if (subCoreBusiness) subCoreBusiness.value = '';
                    if (contractNoInput) contractNoInput.value = '';
                    if (contractPeriodInput) contractPeriodInput.value = '';
                } else if (isContract) {
                    // Contract: Show all fields
                    if (vendorTypeGroup) vendorTypeGroup.style.display = 'block';
                    if (selectVendorGroup) selectVendorGroup.style.display = 'block';
                    if (coreBusinessGroup) coreBusinessGroup.style.display = 'block';
                    if (subCoreBusinessGroup) subCoreBusinessGroup.style.display = 'block';
                    if (contractNoGroup) contractNoGroup.style.display = 'block';
                    if (contractPeriodGroup) contractPeriodGroup.style.display = 'block';
                    if (topTypeGroup) topTypeGroup.style.display = 'block';
                    if (descriptionGroup) descriptionGroup.style.display = 'block';

                    // Set required attributes for all fields
                    if (selectVendor) selectVendor.setAttribute('required', 'required');
                    if (coreBusiness) coreBusiness.setAttribute('required', 'required');
                    if (subCoreBusiness) subCoreBusiness.setAttribute('required', 'required');
                    if (contractNoInput) contractNoInput.setAttribute('required', 'required');
                    if (contractPeriodInput) contractPeriodInput.setAttribute('required', 'required');
                    if (topType) topType.setAttribute('required', 'required');
                    if (description) description.setAttribute('required', 'required');
                }
            } else {
                // When no decision selected: Show fields based on Vendor Type
                if (remarkApprovalGroup) remarkApprovalGroup.style.display = 'block';

                if (isNonContract) {
                    // Non Contract: Show Vendor Type, Select Vendor, Top Type, Description, Remark Approval
                    if (vendorTypeGroup) vendorTypeGroup.style.display = 'block';
                    if (selectVendorGroup) selectVendorGroup.style.display = 'block';
                    if (coreBusinessGroup) coreBusinessGroup.style.display = 'none';
                    if (subCoreBusinessGroup) subCoreBusinessGroup.style.display = 'none';
                    if (contractNoGroup) contractNoGroup.style.display = 'none';
                    if (contractPeriodGroup) contractPeriodGroup.style.display = 'none';
                    if (topTypeGroup) topTypeGroup.style.display = 'block';
                    if (descriptionGroup) descriptionGroup.style.display = 'block';

                    // Set required attributes
                    if (selectVendor) selectVendor.setAttribute('required', 'required');
                    if (topType) topType.setAttribute('required', 'required');
                    if (description) description.setAttribute('required', 'required');

                    // Remove required attributes for hidden fields
                    if (coreBusiness) coreBusiness.removeAttribute('required');
                    if (subCoreBusiness) subCoreBusiness.removeAttribute('required');
                    if (contractNoInput) contractNoInput.removeAttribute('required');
                    if (contractPeriodInput) contractPeriodInput.removeAttribute('required');

                    // Clear hidden field values
                    if (coreBusiness) coreBusiness.value = '';
                    if (subCoreBusiness) subCoreBusiness.value = '';
                    if (contractNoInput) contractNoInput.value = '';
                    if (contractPeriodInput) contractPeriodInput.value = '';
                } else if (isContract) {
                    // Contract: Show all fields
                    if (vendorTypeGroup) vendorTypeGroup.style.display = 'block';
                    if (selectVendorGroup) selectVendorGroup.style.display = 'block';
                    if (coreBusinessGroup) coreBusinessGroup.style.display = 'block';
                    if (subCoreBusinessGroup) subCoreBusinessGroup.style.display = 'block';
                    if (contractNoGroup) contractNoGroup.style.display = 'block';
                    if (contractPeriodGroup) contractPeriodGroup.style.display = 'block';
                    if (topTypeGroup) topTypeGroup.style.display = 'block';
                    if (descriptionGroup) descriptionGroup.style.display = 'block';

                    // Set required attributes for all fields
                    if (selectVendor) selectVendor.setAttribute('required', 'required');
                    if (coreBusiness) coreBusiness.setAttribute('required', 'required');
                    if (subCoreBusiness) subCoreBusiness.setAttribute('required', 'required');
                    if (contractNoInput) contractNoInput.setAttribute('required', 'required');
                    if (contractPeriodInput) contractPeriodInput.setAttribute('required', 'required');
                    if (topType) topType.setAttribute('required', 'required');
                    if (description) description.setAttribute('required', 'required');
                }
            }
        }

        // Expose function globally for manual initialization
        window.toggleBulkyPageReleaseFields = toggleBulkyPageReleaseFields;

        // Flag to prevent multiple initializations
        let bulkyPageFieldsInitialized = false;

        // Function to initialize and set initial state
        function initializeBulkyPageReleaseFields() {
            // Prevent multiple initializations
            if (bulkyPageFieldsInitialized) {
                return true;
            }

            const decisionSelect = document.getElementById('bulkyPageReleaseDecision');
            if (!decisionSelect) return false;
            const form = decisionSelect.closest('form');
            if (!form || form.id !== 'bulkyReleaseForm') return false;

            const vendorTypeRadios = form.querySelectorAll('input[name="vendorType"]');

            // Check if elements exist
            if (!decisionSelect || vendorTypeRadios.length === 0) {
                return false;
            }

            // Set initial state
            toggleBulkyPageReleaseFields();

            bulkyPageFieldsInitialized = true;
            return true;
        }

        // Create debounced handlers for API calls to prevent multiple rapid calls
        const debounceFunc = typeof debounce !== 'undefined' ? debounce : function(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        };

        // Debounced handler for vendor change (triggers API call)
        const debouncedVendorChange = debounceFunc(function(vendorCategoryId) {
            loadBulkyPageCoreBusiness(vendorCategoryId);
        }, 300);

        // Debounced handler for core business change (triggers API call)
        const debouncedCoreBusinessChange = debounceFunc(function(coreBusinessId) {
            const coreBusinessIdInt = parseInt(coreBusinessId, 10);
            if (!isNaN(coreBusinessIdInt) && coreBusinessIdInt > 0) {
                loadBulkyPageSubCoreBusiness(coreBusinessIdInt);
            }
        }, 300);

        // Use event delegation for dynamic content (only attach once for bulky page)
        if (!window.bulkyPageReleaseFieldsEventDelegationAttached) {
            document.addEventListener('change', function(e) {
                // Check if the changed element is the decision dropdown for bulky page
                if (e.target && e.target.id === 'bulkyPageReleaseDecision') {
                    toggleBulkyPageReleaseFields();
                }
                // Check if the changed element is a vendor type radio within bulky page form
                if (e.target && e.target.name === 'vendorType') {
                    const form = e.target.closest('form');
                    if (form && form.id === 'bulkyReleaseForm' && form.querySelector('#bulkyPageReleaseDecision')) {
                        toggleBulkyPageReleaseFields();
                    }
                }
                // Check if the changed element is the vendor hidden input for bulky page
                if (e.target && e.target.id === 'bulkyPageSelectVendor') {
                    const selectedVendorId = e.target.value;
                    if (selectedVendorId && bulkyPageVendorDataMap.has(selectedVendorId)) {
                        const vendorData = bulkyPageVendorDataMap.get(selectedVendorId);
                        // Use debounced handler to prevent multiple API calls
                        debouncedVendorChange(vendorData.vendorCategoryId);
                    } else {
                        // Clear Core Business if no vendor selected
                        const coreBusinessDropdownItems = document.getElementById('bulkyPageCoreBusinessDropdownItems');
                        const coreBusinessSelectedText = document.getElementById('bulkyPageCoreBusinessSelectedText');
                        const coreBusinessHiddenInput = document.getElementById('bulkyPageCoreBusiness');
                        if (coreBusinessDropdownItems) {
                            coreBusinessDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Select Core Business</div>';
                        }
                        if (coreBusinessSelectedText) {
                            coreBusinessSelectedText.textContent = 'Select Core Business';
                        }
                        if (coreBusinessHiddenInput) {
                            coreBusinessHiddenInput.value = '';
                        }
                        // Clear Sub Core Business
                        const subCoreBusinessDropdownItems = document.getElementById('bulkyPageSubCoreBusinessDropdownItems');
                        const subCoreBusinessSelectedText = document.getElementById('bulkyPageSubCoreBusinessSelectedText');
                        const subCoreBusinessHiddenInput = document.getElementById('bulkyPageSubCoreBusiness');
                        if (subCoreBusinessDropdownItems) {
                            subCoreBusinessDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Select Sub Core Business</div>';
                        }
                        if (subCoreBusinessSelectedText) {
                            subCoreBusinessSelectedText.textContent = 'Select Sub Core Business';
                        }
                        if (subCoreBusinessHiddenInput) {
                            subCoreBusinessHiddenInput.value = '';
                        }
                    }
                }
                // Check if the changed element is the core business hidden input for bulky page
                if (e.target && e.target.id === 'bulkyPageCoreBusiness') {
                    const selectedCoreBusinessId = e.target.value;
                    if (selectedCoreBusinessId && selectedCoreBusinessId !== '') {
                        const coreBusinessIdInt = parseInt(selectedCoreBusinessId, 10);
                        if (!isNaN(coreBusinessIdInt) && coreBusinessIdInt > 0) {
                            // Use debounced handler to prevent multiple API calls
                            debouncedCoreBusinessChange(selectedCoreBusinessId);
                        } else {
                            // Clear Sub Core Business if invalid
                            const subCoreBusinessDropdownItems = document.getElementById('bulkyPageSubCoreBusinessDropdownItems');
                            const subCoreBusinessSelectedText = document.getElementById('bulkyPageSubCoreBusinessSelectedText');
                            const subCoreBusinessHiddenInput = document.getElementById('bulkyPageSubCoreBusiness');
                            if (subCoreBusinessDropdownItems) {
                                subCoreBusinessDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Select Sub Core Business</div>';
                            }
                            if (subCoreBusinessSelectedText) {
                                subCoreBusinessSelectedText.textContent = 'Select Sub Core Business';
                            }
                            if (subCoreBusinessHiddenInput) {
                                subCoreBusinessHiddenInput.value = '';
                            }
                            // Clear Contract No
                            const contractNoDropdownItems = document.getElementById('bulkyPageContractNoDropdownItems');
                            const contractNoSelectedText = document.getElementById('bulkyPageContractNoSelectedText');
                            const contractNoHiddenInput = document.getElementById('bulkyPageContractNo');
                            if (contractNoDropdownItems) {
                                contractNoDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Select Contract Number</div>';
                            }
                            if (contractNoSelectedText) {
                                contractNoSelectedText.textContent = 'Select Contract Number';
                            }
                            if (contractNoHiddenInput) {
                                contractNoHiddenInput.value = '';
                            }
                        }
                    } else {
                        // Clear Sub Core Business if no core business selected
                        const subCoreBusinessDropdownItems = document.getElementById('bulkyPageSubCoreBusinessDropdownItems');
                        const subCoreBusinessSelectedText = document.getElementById('bulkyPageSubCoreBusinessSelectedText');
                        const subCoreBusinessHiddenInput = document.getElementById('bulkyPageSubCoreBusiness');
                        if (subCoreBusinessDropdownItems) {
                            subCoreBusinessDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Select Sub Core Business</div>';
                        }
                        if (subCoreBusinessSelectedText) {
                            subCoreBusinessSelectedText.textContent = 'Select Sub Core Business';
                        }
                        if (subCoreBusinessHiddenInput) {
                            subCoreBusinessHiddenInput.value = '';
                        }
                        // Clear Contract No
                        const contractNoDropdownItems = document.getElementById('bulkyPageContractNoDropdownItems');
                        const contractNoSelectedText = document.getElementById('bulkyPageContractNoSelectedText');
                        const contractNoHiddenInput = document.getElementById('bulkyPageContractNo');
                        if (contractNoDropdownItems) {
                            contractNoDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Select Contract Number</div>';
                        }
                        if (contractNoSelectedText) {
                            contractNoSelectedText.textContent = 'Select Contract Number';
                        }
                        if (contractNoHiddenInput) {
                            contractNoHiddenInput.value = '';
                        }
                    }
                }
                // Check if the changed element is the sub core business hidden input for bulky page
                if (e.target && e.target.id === 'bulkyPageSubCoreBusiness') {
                    const selectedSubCoreBusinessId = e.target.value;
                    if (selectedSubCoreBusinessId && selectedSubCoreBusinessId !== '') {
                        const subCoreBusinessIdInt = parseInt(selectedSubCoreBusinessId, 10);
                        if (!isNaN(subCoreBusinessIdInt) && subCoreBusinessIdInt > 0) {
                            // Load Contracts based on selected Sub Core Business ID
                            loadBulkyPageContracts(subCoreBusinessIdInt);
                        } else {
                            // Clear Contract No if invalid
                            const contractNoDropdownItems = document.getElementById('bulkyPageContractNoDropdownItems');
                            const contractNoSelectedText = document.getElementById('bulkyPageContractNoSelectedText');
                            const contractNoHiddenInput = document.getElementById('bulkyPageContractNo');
                            if (contractNoDropdownItems) {
                                contractNoDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Select Contract Number</div>';
                            }
                            if (contractNoSelectedText) {
                                contractNoSelectedText.textContent = 'Select Contract Number';
                            }
                            if (contractNoHiddenInput) {
                                contractNoHiddenInput.value = '';
                            }
                        }
                    } else {
                        // Clear Contract No if no sub core business selected
                        const contractNoDropdownItems = document.getElementById('bulkyPageContractNoDropdownItems');
                        const contractNoSelectedText = document.getElementById('bulkyPageContractNoSelectedText');
                        const contractNoHiddenInput = document.getElementById('bulkyPageContractNo');
                        if (contractNoDropdownItems) {
                            contractNoDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Select Contract Number</div>';
                        }
                        if (contractNoSelectedText) {
                            contractNoSelectedText.textContent = 'Select Contract Number';
                        }
                        if (contractNoHiddenInput) {
                            contractNoHiddenInput.value = '';
                        }
                    }
                }
                // Check if the changed element is the contract number hidden input for bulky page
                if (e.target && e.target.id === 'bulkyPageContractNo') {
                    const selectedContractNumber = e.target.value;
                    if (selectedContractNumber && selectedContractNumber !== '') {
                        // Extract year from contract number format: xxx/xxx/xxx/2024
                        const parts = selectedContractNumber.split('/');
                        if (parts.length > 0) {
                            const lastPart = parts[parts.length - 1].trim();
                            // Check if last part is a valid year (4 digits)
                            const year = parseInt(lastPart, 10);
                            if (!isNaN(year) && year >= 1900 && year <= 2100) {
                                // Set the contract period input field - ensure 4 digits format
                                const contractPeriodInput = document.getElementById('bulkyPageContractPeriod');
                                if (contractPeriodInput) {
                                    contractPeriodInput.value = year.toString().padStart(4, '0');
                                }
                            }
                        }
                    } else {
                        // Clear contract period if no contract selected
                        const contractPeriodInput = document.getElementById('bulkyPageContractPeriod');
                        if (contractPeriodInput) {
                            contractPeriodInput.value = '';
                        }
                    }
                }
                // Check if the changed element is the top type hidden input for bulky page
                if (e.target && e.target.id === 'bulkyPageTopType') {
                    const selectedTopId = e.target.value;
                    if (selectedTopId && selectedTopId !== '' && bulkyPageTopDataMap.has(selectedTopId)) {
                        const topData = bulkyPageTopDataMap.get(selectedTopId);
                        // Auto-fill description with TOPRemarks
                        const descriptionTextarea = document.getElementById('bulkyPageDescription');
                        if (descriptionTextarea) {
                            descriptionTextarea.value = topData.topRemarks || '';
                        }
                    } else {
                        // Clear description if no TOP selected
                        const descriptionTextarea = document.getElementById('bulkyPageDescription');
                        if (descriptionTextarea) {
                            descriptionTextarea.value = '';
                        }
                    }
                }
            });
            window.bulkyPageReleaseFieldsEventDelegationAttached = true;
        }

        // Flag to prevent multiple simultaneous calls for Bulky Page (use global flags to prevent conflicts)
        if (!window.bulkyPageCoreBusinessLoading) {
            window.bulkyPageCoreBusinessLoading = false;
        }
        if (!window.bulkyPageSubCoreBusinessLoading) {
            window.bulkyPageSubCoreBusinessLoading = false;
        }
        if (!window.bulkyPageContractsLoading) {
            window.bulkyPageContractsLoading = false;
        }

        // Use shared data maps from shared loader
        const bulkyPageVendorDataMap = window.sharedReleaseData ? window.sharedReleaseData.vendorDataMap : new Map();
        const bulkyPageTopDataMap = window.sharedReleaseData ? window.sharedReleaseData.topDataMap : new Map();

        // Store core business data with ID for Bulky Page
        const bulkyPageCoreBusinessDataMap = new Map();

        // Store sub core business data with ID for Bulky Page
        const bulkyPageSubCoreBusinessDataMap = new Map();

        // Function to load vendors from API for Bulky Page using shared loader
        async function loadBulkyPageVendors() {
            const vendorHiddenInput = document.getElementById('bulkyPageSelectVendor');
            const vendorDropdownItems = document.getElementById('bulkyPageSelectVendorDropdownItems');
            const vendorSelectedText = document.getElementById('bulkyPageSelectVendorSelectedText');
            const vendorSearchInput = document.getElementById('bulkyPageSelectVendorSearchInput');
            if (!vendorHiddenInput || !vendorDropdownItems) return;

            // Show loading state
            vendorDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Loading vendors...</div>';

            try {
                // Use shared loader - this will prevent duplicate API calls
                const vendors = await window.loadSharedVendors();

                if (!Array.isArray(vendors) || vendors.length === 0) {
                    vendorDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No vendors found</div>';
                    return;
                }

                // Render dropdown with search
                renderDropdownWithSearch({
                    items: vendors,
                    searchTerm: '',
                    dropdownItemsId: 'bulkyPageSelectVendorDropdownItems',
                    hiddenInputId: 'bulkyPageSelectVendor',
                    selectedTextId: 'bulkyPageSelectVendorSelectedText',
                    searchInputId: 'bulkyPageSelectVendorSearchInput',
                    dropdownBtnId: 'bulkyPageSelectVendorDropdownBtn',
                    getValue: (vendor) => vendor.VendorID || vendor.vendorID || '',
                    getText: (vendor) => vendor.VendorName || vendor.vendorName || vendor.VendorID || vendor.vendorID || '',
                    getSearchableText: (vendor) => (vendor.VendorName || vendor.vendorName || vendor.VendorID || vendor.vendorID || '').toString()
                });

                // Setup search functionality
                if (vendorSearchInput) {
                    const newSearchInput = vendorSearchInput.cloneNode(true);
                    vendorSearchInput.parentNode.replaceChild(newSearchInput, vendorSearchInput);

                    newSearchInput.addEventListener('input', (e) => {
                        const searchTerm = e.target.value.toLowerCase().trim();
                        renderDropdownWithSearch({
                            items: vendors,
                            searchTerm: searchTerm,
                            dropdownItemsId: 'bulkyPageSelectVendorDropdownItems',
                            hiddenInputId: 'bulkyPageSelectVendor',
                            selectedTextId: 'bulkyPageSelectVendorSelectedText',
                            searchInputId: 'bulkyPageSelectVendorSearchInput',
                            dropdownBtnId: 'bulkyPageSelectVendorDropdownBtn',
                            getValue: (vendor) => vendor.VendorID || vendor.vendorID || '',
                            getText: (vendor) => vendor.VendorName || vendor.vendorName || vendor.VendorID || vendor.vendorID || '',
                            getSearchableText: (vendor) => (vendor.VendorName || vendor.vendorName || vendor.VendorID || vendor.vendorID || '').toString()
                        });
                    });

                    newSearchInput.addEventListener('click', (e) => {
                        e.stopPropagation();
                    });
                }

                // Focus search input when dropdown opens
                const vendorDropdownBtn = document.getElementById('bulkyPageSelectVendorDropdownBtn');
                if (vendorDropdownBtn) {
                    vendorDropdownBtn.addEventListener('shown.bs.dropdown', () => {
                        const searchInput = document.getElementById('bulkyPageSelectVendorSearchInput');
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

        // Function to load Core Business based on selected vendor's VendorCategory for Bulky Page
        async function loadBulkyPageCoreBusiness(vendorCategoryId) {
            const coreBusinessHiddenInput = document.getElementById('bulkyPageCoreBusiness');
            const coreBusinessDropdownItems = document.getElementById('bulkyPageCoreBusinessDropdownItems');
            const coreBusinessSelectedText = document.getElementById('bulkyPageCoreBusinessSelectedText');
            const coreBusinessSearchInput = document.getElementById('bulkyPageCoreBusinessSearchInput');
            if (!coreBusinessHiddenInput || !coreBusinessDropdownItems) return;

            // Prevent multiple simultaneous calls (use global flags)
            if (window.bulkyPageCoreBusinessLoading) return;

            window.bulkyPageCoreBusinessLoading = true;

            // Show loading state
            coreBusinessDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Loading core business...</div>';

            try {
                // If no vendorCategoryId or invalid, clear the dropdown
                if (!vendorCategoryId || vendorCategoryId <= 0) {
                    coreBusinessDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Select Core Business</div>';
                    if (coreBusinessSelectedText) {
                        coreBusinessSelectedText.textContent = 'Select Core Business';
                    }
                    if (coreBusinessHiddenInput) {
                        coreBusinessHiddenInput.value = '';
                    }
                    window.bulkyPageCoreBusinessLoading = false;
                    return;
                }

                // Wait for apiCall to be available or use fetch as fallback
                let data;
                const endpoint = `/Procurement/Master/CoreBusinesses?vendorCategoryId=${vendorCategoryId}`;

                if (typeof apiCall === 'function') {
                    data = await apiCall('Procurement', endpoint, 'GET');
                } else {
                    // Fallback: use fetch directly
                    let attempts = 0;
                    while (typeof apiCall !== 'function' && attempts < 10) {
                        await new Promise(resolve => setTimeout(resolve, 100));
                        attempts++;
                    }

                    if (typeof apiCall === 'function') {
                        data = await apiCall('Procurement', endpoint, 'GET');
                    } else {
                        if (typeof API_URLS === 'undefined') {
                            throw new Error('API configuration not found');
                        }
                        const apiType = 'procurement';
                        const baseUrl = API_URLS[apiType] || API_URLS['Procurement'] || '/api/Procurement';
                        const fullUrl = baseUrl + endpoint;

                        const response = await fetch(fullUrl, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json'
                            },
                            credentials: 'include'
                        });

                        const responseData = await response.json();

                        // Handle ApiResponse<T> wrapper format from StandardResponseFilter
                        if (responseData && typeof responseData === 'object' && 'statusCode' in responseData) {
                            if (responseData.isError === true || !response.ok) {
                                const errorMessage = responseData.message || responseData.Message || 'An error occurred';
                                throw new Error(errorMessage);
                            }
                            data = responseData.data || responseData.Data || responseData;
                        } else if (!response.ok) {
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        } else {
                            data = responseData.data || responseData.Data || responseData;
                        }
                    }
                }

                const coreBusinesses = data.data || data;

                if (!Array.isArray(coreBusinesses) || coreBusinesses.length === 0) {
                    coreBusinessDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No core business found</div>';
                    return;
                }

                // Clear existing data
                bulkyPageCoreBusinessDataMap.clear();

                // Store core business data with ID for later use
                coreBusinesses.forEach(cb => {
                    const coreBusinessId = cb.ID || cb.id || 0;
                    const coreBusinessIdInt = typeof coreBusinessId === 'number' ? coreBusinessId : parseInt(coreBusinessId, 10) || 0;
                    const coreBusinessName = cb.CoreBusiness || cb.coreBusiness || '';

                    // Store core business data with ID for later use
                    bulkyPageCoreBusinessDataMap.set(coreBusinessIdInt.toString(), {
                        id: coreBusinessIdInt,
                        name: coreBusinessName
                    });
                });

                // Clear Sub Core Business when Core Business is reloaded
                const subCoreBusinessDropdownItems = document.getElementById('bulkyPageSubCoreBusinessDropdownItems');
                const subCoreBusinessSelectedText = document.getElementById('bulkyPageSubCoreBusinessSelectedText');
                const subCoreBusinessHiddenInput = document.getElementById('bulkyPageSubCoreBusiness');
                if (subCoreBusinessDropdownItems) {
                    subCoreBusinessDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Select Sub Core Business</div>';
                }
                if (subCoreBusinessSelectedText) {
                    subCoreBusinessSelectedText.textContent = 'Select Sub Core Business';
                }
                if (subCoreBusinessHiddenInput) {
                    subCoreBusinessHiddenInput.value = '';
                }
                // Clear Contract No when Core Business is reloaded
                const contractNoDropdownItems = document.getElementById('bulkyPageContractNoDropdownItems');
                const contractNoSelectedText = document.getElementById('bulkyPageContractNoSelectedText');
                const contractNoHiddenInput = document.getElementById('bulkyPageContractNo');
                if (contractNoDropdownItems) {
                    contractNoDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Select Contract Number</div>';
                }
                if (contractNoSelectedText) {
                    contractNoSelectedText.textContent = 'Select Contract Number';
                }
                if (contractNoHiddenInput) {
                    contractNoHiddenInput.value = '';
                }

                // Render dropdown with search
                renderDropdownWithSearch({
                    items: coreBusinesses,
                    searchTerm: '',
                    dropdownItemsId: 'bulkyPageCoreBusinessDropdownItems',
                    hiddenInputId: 'bulkyPageCoreBusiness',
                    selectedTextId: 'bulkyPageCoreBusinessSelectedText',
                    searchInputId: 'bulkyPageCoreBusinessSearchInput',
                    dropdownBtnId: 'bulkyPageCoreBusinessDropdownBtn',
                    getValue: (cb) => {
                        const id = cb.ID || cb.id || 0;
                        const idInt = typeof id === 'number' ? id : parseInt(id, 10) || 0;
                        return idInt.toString();
                    },
                    getText: (cb) => cb.CoreBusiness || cb.coreBusiness || '',
                    getSearchableText: (cb) => (cb.CoreBusiness || cb.coreBusiness || '').toString()
                });

                // Setup search functionality
                if (coreBusinessSearchInput) {
                    const newSearchInput = coreBusinessSearchInput.cloneNode(true);
                    coreBusinessSearchInput.parentNode.replaceChild(newSearchInput, coreBusinessSearchInput);

                    newSearchInput.addEventListener('input', (e) => {
                        const searchTerm = e.target.value.toLowerCase().trim();
                        renderDropdownWithSearch({
                            items: coreBusinesses,
                            searchTerm: searchTerm,
                            dropdownItemsId: 'bulkyPageCoreBusinessDropdownItems',
                            hiddenInputId: 'bulkyPageCoreBusiness',
                            selectedTextId: 'bulkyPageCoreBusinessSelectedText',
                            searchInputId: 'bulkyPageCoreBusinessSearchInput',
                            dropdownBtnId: 'bulkyPageCoreBusinessDropdownBtn',
                            getValue: (cb) => {
                                const id = cb.ID || cb.id || 0;
                                const idInt = typeof id === 'number' ? id : parseInt(id, 10) || 0;
                                return idInt.toString();
                            },
                            getText: (cb) => cb.CoreBusiness || cb.coreBusiness || '',
                            getSearchableText: (cb) => (cb.CoreBusiness || cb.coreBusiness || '').toString()
                        });
                    });

                    newSearchInput.addEventListener('click', (e) => {
                        e.stopPropagation();
                    });
                }

                // Focus search input when dropdown opens
                const coreBusinessDropdownBtn = document.getElementById('bulkyPageCoreBusinessDropdownBtn');
                if (coreBusinessDropdownBtn) {
                    coreBusinessDropdownBtn.addEventListener('shown.bs.dropdown', () => {
                        const searchInput = document.getElementById('bulkyPageCoreBusinessSearchInput');
                        if (searchInput) {
                            setTimeout(() => {
                                searchInput.focus();
                            }, 100);
                        }
                    });
                }

            } catch (error) {
                coreBusinessDropdownItems.innerHTML = '<div class="px-3 py-2 text-danger text-center">Error loading core business</div>';
            } finally {
                window.bulkyPageCoreBusinessLoading = false;
            }
        }

        // Function to load Sub Core Business based on selected Core Business ID for Bulky Page
        async function loadBulkyPageSubCoreBusiness(coreBusinessId) {
            const subCoreBusinessHiddenInput = document.getElementById('bulkyPageSubCoreBusiness');
            const subCoreBusinessDropdownItems = document.getElementById('bulkyPageSubCoreBusinessDropdownItems');
            const subCoreBusinessSelectedText = document.getElementById('bulkyPageSubCoreBusinessSelectedText');
            const subCoreBusinessSearchInput = document.getElementById('bulkyPageSubCoreBusinessSearchInput');
            if (!subCoreBusinessHiddenInput || !subCoreBusinessDropdownItems) return;

            // Prevent multiple simultaneous calls (use global flags)
            if (window.bulkyPageSubCoreBusinessLoading) return;

            window.bulkyPageSubCoreBusinessLoading = true;

            // Show loading state
            subCoreBusinessDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Loading sub core business...</div>';

            try {
                // If no coreBusinessId or invalid, clear the dropdown
                if (!coreBusinessId || coreBusinessId <= 0) {
                    subCoreBusinessDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Select Sub Core Business</div>';
                    if (subCoreBusinessSelectedText) {
                        subCoreBusinessSelectedText.textContent = 'Select Sub Core Business';
                    }
                    if (subCoreBusinessHiddenInput) {
                        subCoreBusinessHiddenInput.value = '';
                    }
                    window.bulkyPageSubCoreBusinessLoading = false;
                    return;
                }

                // Wait for apiCall to be available or use fetch as fallback
                let data;
                const endpoint = `/Procurement/Master/SubCoreBusinesses?coreBusinessId=${coreBusinessId}`;

                if (typeof apiCall === 'function') {
                    data = await apiCall('Procurement', endpoint, 'GET');
                } else {
                    // Fallback: use fetch directly
                    let attempts = 0;
                    while (typeof apiCall !== 'function' && attempts < 10) {
                        await new Promise(resolve => setTimeout(resolve, 100));
                        attempts++;
                    }

                    if (typeof apiCall === 'function') {
                        data = await apiCall('Procurement', endpoint, 'GET');
                    } else {
                        if (typeof API_URLS === 'undefined') {
                            throw new Error('API configuration not found');
                        }
                        const apiType = 'procurement';
                        const baseUrl = API_URLS[apiType] || API_URLS['Procurement'] || '/api/Procurement';
                        const fullUrl = baseUrl + endpoint;

                        const response = await fetch(fullUrl, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json'
                            },
                            credentials: 'include'
                        });

                        const responseData = await response.json();

                        // Handle ApiResponse<T> wrapper format from StandardResponseFilter
                        if (responseData && typeof responseData === 'object' && 'statusCode' in responseData) {
                            if (responseData.isError === true || !response.ok) {
                                const errorMessage = responseData.message || responseData.Message || 'An error occurred';
                                throw new Error(errorMessage);
                            }
                            data = responseData.data || responseData.Data || responseData;
                        } else if (!response.ok) {
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        } else {
                            data = responseData.data || responseData.Data || responseData;
                        }
                    }
                }

                const subCoreBusinesses = data.data || data;

                if (!Array.isArray(subCoreBusinesses) || subCoreBusinesses.length === 0) {
                    subCoreBusinessDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No sub core business found</div>';
                    return;
                }

                // Clear existing data
                bulkyPageSubCoreBusinessDataMap.clear();

                // Store sub core business data with ID for later use
                subCoreBusinesses.forEach(scb => {
                    const subCoreBusinessId = scb.ID || scb.id || 0;
                    const subCoreBusinessIdInt = typeof subCoreBusinessId === 'number' ? subCoreBusinessId : parseInt(subCoreBusinessId, 10) || 0;
                    const subCoreBusinessName = scb.SubCoreBusiness || scb.subCoreBusiness || '';

                    // Store sub core business data with ID for later use
                    bulkyPageSubCoreBusinessDataMap.set(subCoreBusinessIdInt.toString(), {
                        id: subCoreBusinessIdInt,
                        name: subCoreBusinessName
                    });
                });

                // Render dropdown with search
                renderDropdownWithSearch({
                    items: subCoreBusinesses,
                    searchTerm: '',
                    dropdownItemsId: 'bulkyPageSubCoreBusinessDropdownItems',
                    hiddenInputId: 'bulkyPageSubCoreBusiness',
                    selectedTextId: 'bulkyPageSubCoreBusinessSelectedText',
                    searchInputId: 'bulkyPageSubCoreBusinessSearchInput',
                    dropdownBtnId: 'bulkyPageSubCoreBusinessDropdownBtn',
                    getValue: (scb) => {
                        const id = scb.ID || scb.id || 0;
                        const idInt = typeof id === 'number' ? id : parseInt(id, 10) || 0;
                        return idInt.toString();
                    },
                    getText: (scb) => scb.SubCoreBusiness || scb.subCoreBusiness || '',
                    getSearchableText: (scb) => (scb.SubCoreBusiness || scb.subCoreBusiness || '').toString()
                });

                // Setup search functionality
                if (subCoreBusinessSearchInput) {
                    const newSearchInput = subCoreBusinessSearchInput.cloneNode(true);
                    subCoreBusinessSearchInput.parentNode.replaceChild(newSearchInput, subCoreBusinessSearchInput);

                    newSearchInput.addEventListener('input', (e) => {
                        const searchTerm = e.target.value.toLowerCase().trim();
                        renderDropdownWithSearch({
                            items: subCoreBusinesses,
                            searchTerm: searchTerm,
                            dropdownItemsId: 'bulkyPageSubCoreBusinessDropdownItems',
                            hiddenInputId: 'bulkyPageSubCoreBusiness',
                            selectedTextId: 'bulkyPageSubCoreBusinessSelectedText',
                            searchInputId: 'bulkyPageSubCoreBusinessSearchInput',
                            dropdownBtnId: 'bulkyPageSubCoreBusinessDropdownBtn',
                            getValue: (scb) => {
                                const id = scb.ID || scb.id || 0;
                                const idInt = typeof id === 'number' ? id : parseInt(id, 10) || 0;
                                return idInt.toString();
                            },
                            getText: (scb) => scb.SubCoreBusiness || scb.subCoreBusiness || '',
                            getSearchableText: (scb) => (scb.SubCoreBusiness || scb.subCoreBusiness || '').toString()
                        });
                    });

                    newSearchInput.addEventListener('click', (e) => {
                        e.stopPropagation();
                    });
                }

                // Focus search input when dropdown opens
                const subCoreBusinessDropdownBtn = document.getElementById('bulkyPageSubCoreBusinessDropdownBtn');
                if (subCoreBusinessDropdownBtn) {
                    subCoreBusinessDropdownBtn.addEventListener('shown.bs.dropdown', () => {
                        const searchInput = document.getElementById('bulkyPageSubCoreBusinessSearchInput');
                        if (searchInput) {
                            setTimeout(() => {
                                searchInput.focus();
                            }, 100);
                        }
                    });
                }

            } catch (error) {
                subCoreBusinessDropdownItems.innerHTML = '<div class="px-3 py-2 text-danger text-center">Error loading sub core business</div>';
            } finally {
                window.bulkyPageSubCoreBusinessLoading = false;
            }
        }

        // Function to load Contracts based on selected Sub Core Business ID for Bulky Page
        async function loadBulkyPageContracts(subCoreBusinessId) {
            const contractNoHiddenInput = document.getElementById('bulkyPageContractNo');
            const contractNoDropdownItems = document.getElementById('bulkyPageContractNoDropdownItems');
            const contractNoSelectedText = document.getElementById('bulkyPageContractNoSelectedText');
            const contractNoSearchInput = document.getElementById('bulkyPageContractNoSearchInput');
            if (!contractNoHiddenInput || !contractNoDropdownItems) return;

            // Prevent multiple simultaneous calls (use global flags)
            if (window.bulkyPageContractsLoading) return;

            window.bulkyPageContractsLoading = true;

            // Show loading state
            contractNoDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Loading contracts...</div>';

            try {
                // If no subCoreBusinessId or invalid, clear the dropdown
                if (!subCoreBusinessId || subCoreBusinessId <= 0) {
                    contractNoDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Select Contract Number</div>';
                    if (contractNoSelectedText) {
                        contractNoSelectedText.textContent = 'Select Contract Number';
                    }
                    if (contractNoHiddenInput) {
                        contractNoHiddenInput.value = '';
                    }
                    window.bulkyPageContractsLoading = false;
                    return;
                }

                // Wait for apiCall to be available or use fetch as fallback
                let data;
                const endpoint = `/Procurement/Master/VendorContracts?subCoreBusinessId=${subCoreBusinessId}`;

                if (typeof apiCall === 'function') {
                    data = await apiCall('Procurement', endpoint, 'GET');
                } else {
                    // Fallback: use fetch directly
                    let attempts = 0;
                    while (typeof apiCall !== 'function' && attempts < 10) {
                        await new Promise(resolve => setTimeout(resolve, 100));
                        attempts++;
                    }

                    if (typeof apiCall === 'function') {
                        data = await apiCall('Procurement', endpoint, 'GET');
                    } else {
                        if (typeof API_URLS === 'undefined') {
                            throw new Error('API configuration not found');
                        }
                        const apiType = 'procurement';
                        const baseUrl = API_URLS[apiType] || API_URLS['Procurement'] || '/api/Procurement';
                        const fullUrl = baseUrl + endpoint;

                        const response = await fetch(fullUrl, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json'
                            },
                            credentials: 'include'
                        });

                        const responseData = await response.json();

                        // Handle ApiResponse<T> wrapper format from StandardResponseFilter
                        if (responseData && typeof responseData === 'object' && 'statusCode' in responseData) {
                            if (responseData.isError === true || !response.ok) {
                                const errorMessage = responseData.message || responseData.Message || 'An error occurred';
                                throw new Error(errorMessage);
                            }
                            data = responseData.data || responseData.Data || responseData;
                        } else if (!response.ok) {
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        } else {
                            data = responseData.data || responseData.Data || responseData;
                        }
                    }
                }

                const contracts = data.data || data;

                if (!Array.isArray(contracts) || contracts.length === 0) {
                    contractNoDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No contracts found</div>';
                    return;
                }

                // Render dropdown with search
                renderDropdownWithSearch({
                    items: contracts,
                    searchTerm: '',
                    dropdownItemsId: 'bulkyPageContractNoDropdownItems',
                    hiddenInputId: 'bulkyPageContractNo',
                    selectedTextId: 'bulkyPageContractNoSelectedText',
                    searchInputId: 'bulkyPageContractNoSearchInput',
                    dropdownBtnId: 'bulkyPageContractNoDropdownBtn',
                    getValue: (contract) => contract.ContractNumber || contract.contractNumber || '',
                    getText: (contract) => contract.ContractNumber || contract.contractNumber || '',
                    getSearchableText: (contract) => (contract.ContractNumber || contract.contractNumber || '').toString()
                });

                // Setup search functionality
                if (contractNoSearchInput) {
                    const newSearchInput = contractNoSearchInput.cloneNode(true);
                    contractNoSearchInput.parentNode.replaceChild(newSearchInput, contractNoSearchInput);

                    newSearchInput.addEventListener('input', (e) => {
                        const searchTerm = e.target.value.toLowerCase().trim();
                        renderDropdownWithSearch({
                            items: contracts,
                            searchTerm: searchTerm,
                            dropdownItemsId: 'bulkyPageContractNoDropdownItems',
                            hiddenInputId: 'bulkyPageContractNo',
                            selectedTextId: 'bulkyPageContractNoSelectedText',
                            searchInputId: 'bulkyPageContractNoSearchInput',
                            dropdownBtnId: 'bulkyPageContractNoDropdownBtn',
                            getValue: (contract) => contract.ContractNumber || contract.contractNumber || '',
                            getText: (contract) => contract.ContractNumber || contract.contractNumber || '',
                            getSearchableText: (contract) => (contract.ContractNumber || contract.contractNumber || '').toString()
                        });
                    });

                    newSearchInput.addEventListener('click', (e) => {
                        e.stopPropagation();
                    });
                }

                // Focus search input when dropdown opens
                const contractNoDropdownBtn = document.getElementById('bulkyPageContractNoDropdownBtn');
                if (contractNoDropdownBtn) {
                    contractNoDropdownBtn.addEventListener('shown.bs.dropdown', () => {
                        const searchInput = document.getElementById('bulkyPageContractNoSearchInput');
                        if (searchInput) {
                            setTimeout(() => {
                                searchInput.focus();
                            }, 100);
                        }
                    });
                }

            } catch (error) {
                contractNoDropdownItems.innerHTML = '<div class="px-3 py-2 text-danger text-center">Error loading contracts</div>';
            } finally {
                window.bulkyPageContractsLoading = false;
            }
        }

        // Helper function to check if PR has StartPeriod and EndPeriod not NULL
        async function checkPRHasPeriods(prNumber) {
            if (!prNumber) return false;

            try {
                const endpoint = `/Procurement/PurchaseRequest/PurchaseRequestAdditional/${encodeURIComponent(prNumber)}`;
                let responseData;

                if (typeof apiCall === 'function') {
                    responseData = await apiCall('Procurement', endpoint, 'GET');
                } else {
                    if (typeof API_URLS === 'undefined') {
                        return false;
                    }
                    const apiType = 'procurement';
                    const baseUrl = API_URLS[apiType] || API_URLS['Procurement'] || '/api/Procurement';
                    const fullUrl = baseUrl + endpoint;

                    const response = await fetch(fullUrl, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json'
                        },
                        credentials: 'include'
                    });

                    if (!response.ok) {
                        // If 404, PR doesn't have additional data
                        if (response.status === 404) {
                            return false;
                        }
                        return false;
                    }

                    const data = await response.json();

                    // Handle ApiResponse<T> wrapper format
                    if (data && typeof data === 'object' && 'statusCode' in data) {
                        if (data.isError === true) {
                            return false;
                        }
                        responseData = data.data || data.Data || data;
                    } else {
                        responseData = data;
                    }
                }

                // responseData might already be the data object, or wrapped in another data property
                const additionalData = responseData.data || responseData;

                // Normalize to match bulky release cache logic
                const start = additionalData?.startPeriod ?? additionalData?.StartPeriod ?? null;
                const end = additionalData?.endPeriod ?? additionalData?.EndPeriod ?? null;

                return start != null && end != null;
            } catch (error) {
                // If error is 404, PR doesn't have additional data
                if (error.message && error.message.includes('404')) {
                    return false;
                }
                console.error('Error checking PR periods:', error);
                return false;
            }
        }

        // Function to load Term of Payment (TOP) from API for Bulky Page using shared loader
        // Expose globally so it can be called after PRs are selected
        window.loadBulkyPageTermOfPayments = async function loadBulkyPageTermOfPayments() {
            const topTypeHiddenInput = document.getElementById('bulkyPageTopType');
            const topTypeDropdownItems = document.getElementById('bulkyPageTopTypeDropdownItems');
            const topTypeSelectedText = document.getElementById('bulkyPageTopTypeSelectedText');
            const topTypeSearchInput = document.getElementById('bulkyPageTopTypeSearchInput');
            if (!topTypeHiddenInput || !topTypeDropdownItems) return;

            // Show loading state
            topTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">Loading term of payment...</div>';

            try {
                // Use shared loader - this will prevent duplicate API calls
                let tops = await window.loadSharedTermOfPayments();

                if (!Array.isArray(tops) || tops.length === 0) {
                    topTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No term of payment found</div>';
                    return;
                }

                // Check if all selected PRs have StartPeriod and EndPeriod not NULL
                let shouldFilterTOP = false;
                if (typeof window.releaseListManager !== 'undefined' && window.releaseListManager.selectedPRNumbers && window.releaseListManager.selectedPRNumbers.size > 0) {
                    const selectedPRNumbers = Array.from(window.releaseListManager.selectedPRNumbers);
                    const periodChecks = await Promise.all(selectedPRNumbers.map(prNumber => checkPRHasPeriods(prNumber)));
                    shouldFilterTOP = periodChecks.every(hasPeriod => hasPeriod === true);
                }
                // Filter TOP: if all PRs have periods, only show TOP with ID 765, 766, 767, 768
                if (shouldFilterTOP) {
                    const allowedTopIds = [765, 766, 767, 768];
                    tops = tops.filter(top => {
                        const topId = parseInt(top.ID || top.id || 0, 10);
                        return allowedTopIds.indexOf(topId) !== -1;
                    });
                }

                if (tops.length === 0) {
                    topTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No term of payment found</div>';
                    return;
                }

                // Render dropdown with search
                // Use TOP ID as value; label = TOPDescription only; TOPRemarks as tooltip on hover
                const topGetValue = (top) => String(top.ID ?? top.id ?? '');
                const topGetText = (top) => (top.TOPDescription || top.topDescription || '').trim() || '-';
                const topGetTitle = (top) => (top.TOPRemarks || top.topRemarks || '').trim();
                const topGetSearchableText = (top) => [top.TOPRemarks, top.topRemarks, top.TOPDescription, top.topDescription].filter(Boolean).join(' ');
                renderDropdownWithSearch({
                    items: tops,
                    searchTerm: '',
                    dropdownItemsId: 'bulkyPageTopTypeDropdownItems',
                    hiddenInputId: 'bulkyPageTopType',
                    selectedTextId: 'bulkyPageTopTypeSelectedText',
                    searchInputId: 'bulkyPageTopTypeSearchInput',
                    dropdownBtnId: 'bulkyPageTopTypeDropdownBtn',
                    getValue: topGetValue,
                    getText: topGetText,
                    getSearchableText: topGetSearchableText,
                    getTitle: topGetTitle
                });

                // Setup search functionality
                if (topTypeSearchInput) {
                    const newSearchInput = topTypeSearchInput.cloneNode(true);
                    topTypeSearchInput.parentNode.replaceChild(newSearchInput, topTypeSearchInput);

                    newSearchInput.addEventListener('input', (e) => {
                        const searchTerm = e.target.value.toLowerCase().trim();
                        renderDropdownWithSearch({
                            items: tops,
                            searchTerm: searchTerm,
                            dropdownItemsId: 'bulkyPageTopTypeDropdownItems',
                            hiddenInputId: 'bulkyPageTopType',
                            selectedTextId: 'bulkyPageTopTypeSelectedText',
                            searchInputId: 'bulkyPageTopTypeSearchInput',
                            dropdownBtnId: 'bulkyPageTopTypeDropdownBtn',
                            getValue: topGetValue,
                            getText: topGetText,
                            getSearchableText: topGetSearchableText,
                            getTitle: topGetTitle
                        });
                    });

                    newSearchInput.addEventListener('click', (e) => {
                        e.stopPropagation();
                    });
                }

                // Focus search input when dropdown opens
                const topTypeDropdownBtn = document.getElementById('bulkyPageTopTypeDropdownBtn');
                if (topTypeDropdownBtn) {
                    topTypeDropdownBtn.addEventListener('shown.bs.dropdown', () => {
                        const searchInput = document.getElementById('bulkyPageTopTypeSearchInput');
                        if (searchInput) {
                            setTimeout(() => {
                                searchInput.focus();
                            }, 100);
                        }
                    });
                }

            } catch (error) {
                topTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-danger text-center">Error loading term of payment</div>';
            }
        }

        // Debounced initialization function to prevent multiple calls
        const debouncedInitialize = typeof debounce !== 'undefined'
            ? debounce(initializeBulkyPageReleaseFields, 200)
            : (function() {
                let timeout;
                return function() {
                    clearTimeout(timeout);
                    timeout = setTimeout(initializeBulkyPageReleaseFields, 200);
                };
            })();

        // Flag to prevent multiple initialization calls (use global flag to prevent conflicts with other partials)
        if (!window.bulkyPageFormInitialized) {
            window.bulkyPageFormInitialized = false;
        }

        // Function to initialize once DOM is ready
        function initializeOnce() {
            // Prevent multiple calls using global flag
            if (window.bulkyPageFormInitialized) return;
            window.bulkyPageFormInitialized = true;

            if (initializeBulkyPageReleaseFields()) {
                // Load vendors and TOPs after successful initialization
                // Shared loader will prevent duplicate API calls
                loadBulkyPageVendors();
                loadBulkyPageTermOfPayments();
            }
        }

        // Initialize when DOM is ready (only once)
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                initializeOnce();
            }, { once: true }); // Use once option to ensure it only fires once
        } else {
            // DOM already loaded, try to initialize
            initializeOnce();
        }

        // Use MutationObserver to detect when form is added to DOM (with debouncing)
        if (typeof MutationObserver !== 'undefined') {
            // Debounced function for MutationObserver to prevent rapid multiple calls
            const debouncedObserverCallback = debounceFunc(function() {
                // Only call initializeOnce - it has global flag protection to prevent multiple calls
                // and will handle both field visibility and loading vendors/TOPs
                initializeOnce();
            }, 300);

            const observer = new MutationObserver(function(mutations) {
                let shouldInitialize = false;
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            // Check if the added node or its children contain the bulky page decision select
                            const decisionSelect = node.querySelector ? node.querySelector('#bulkyPageReleaseDecision') : null;
                            if (decisionSelect || (node.id === 'bulkyPageReleaseDecision')) {
                                shouldInitialize = true;
                            }
                        }
                    });
                });

                // Use debounced callback to prevent multiple calls
                if (shouldInitialize) {
                    debouncedObserverCallback();
                }
            });

            // Start observing
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    })();
</script>



