{{--
    Single Release Page Form Partial for Purchase Request Release
    Contains form for single release with vendor and contract information
    This is a separate partial for Single Release to avoid conflicts with _BulkyReleaseActionPartial
--}}

<div class="card">
    <div class="card-header">
        <h6 class="card-title mb-0">Action</h6>
    </div>
    <div class="card-body">
        <form id="bulkyReleaseForm">
            <div class="row g-3">
                <!-- Decision -->
                <div class="col-md-6" id="decisionGroup">
                    <label class="form-label fw-semibold">Decision <span class="text-danger">*</span></label>
                    <select class="form-select" id="bulkyReleaseDecision" name="decision" required>
                        <option value="" disabled selected>Select Decision</option>
                        <option value="Release">Release</option>
                        <option value="Reject">Reject</option>
                    </select>
                </div>

                <!-- Vendor Type -->
                <div class="col-md-6" id="vendorTypeGroup">
                    <label class="form-label fw-semibold">Vendor Type <span class="text-danger">*</span></label>
                    <div class="mt-2">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="vendorType" id="vendorTypeContract" value="Contract" checked required>
                            <label class="form-check-label" for="vendorTypeContract">Contract</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="vendorType" id="vendorTypeNonContract" value="Non Contract" required>
                            <label class="form-check-label" for="vendorTypeNonContract">Non Contract</label>
                        </div>
                    </div>
                </div>

                <!-- Select Vendor -->
                <div class="col-md-6" id="selectVendorGroup">
                    <label class="form-label fw-semibold">Select Vendor <span class="text-danger">*</span></label>
                    <div class="dropdown" id="selectVendorDropdownContainer">
                        <button class="form-select text-start custom-dropdown-toggle" type="button" id="selectVendorDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                            <span id="selectVendorSelectedText">Select Vendor</span>
                        </button>
                        <input type="hidden" id="selectVendor" name="vendorId" required>
                        <ul class="dropdown-menu w-100 custom-dropdown-menu" id="selectVendorDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                            <li class="px-3 py-2">
                                <input type="text" class="form-control form-control-sm custom-dropdown-search-input" id="selectVendorSearchInput" placeholder="Search vendor..." autocomplete="off">
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li id="selectVendorDropdownItems">
                                <div class="px-3 py-2 text-muted text-center">Loading vendors...</div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Core Business -->
                <div class="col-md-6" id="coreBusinessGroup">
                    <label class="form-label fw-semibold">Core Business <span class="text-danger">*</span></label>
                    <div class="dropdown" id="coreBusinessDropdownContainer">
                        <button class="form-select text-start custom-dropdown-toggle" type="button" id="coreBusinessDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                            <span id="coreBusinessSelectedText">Select Core Business</span>
                        </button>
                        <input type="hidden" id="coreBusiness" name="coreBusiness" required>
                        <ul class="dropdown-menu w-100 custom-dropdown-menu" id="coreBusinessDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                            <li class="px-3 py-2">
                                <input type="text" class="form-control form-control-sm custom-dropdown-search-input" id="coreBusinessSearchInput" placeholder="Search core business..." autocomplete="off">
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li id="coreBusinessDropdownItems">
                                <div class="px-3 py-2 text-muted text-center">Select Core Business</div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Sub Core Business -->
                <div class="col-md-6" id="subCoreBusinessGroup">
                    <label class="form-label fw-semibold">Sub Core Business <span class="text-danger">*</span></label>
                    <div class="dropdown" id="subCoreBusinessDropdownContainer">
                        <button class="form-select text-start custom-dropdown-toggle" type="button" id="subCoreBusinessDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                            <span id="subCoreBusinessSelectedText">Select Sub Core Business</span>
                        </button>
                        <input type="hidden" id="subCoreBusiness" name="subCoreBusiness" required>
                        <ul class="dropdown-menu w-100 custom-dropdown-menu" id="subCoreBusinessDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                            <li class="px-3 py-2">
                                <input type="text" class="form-control form-control-sm custom-dropdown-search-input" id="subCoreBusinessSearchInput" placeholder="Search sub core business..." autocomplete="off">
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li id="subCoreBusinessDropdownItems">
                                <div class="px-3 py-2 text-muted text-center">Select Sub Core Business</div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Contract No (only shown when Vendor Type is Contract) -->
                <div class="col-md-6" id="contractNoGroup" style="display: block;">
                    <label class="form-label fw-semibold">Contract No <span class="text-danger">*</span></label>
                    <div class="dropdown" id="contractNoDropdownContainer">
                        <button class="form-select text-start custom-dropdown-toggle" type="button" id="contractNoDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                            <span id="contractNoSelectedText">Select Contract Number</span>
                        </button>
                        <input type="hidden" id="contractNo" name="contractNo" required>
                        <ul class="dropdown-menu w-100 custom-dropdown-menu" id="contractNoDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                            <li class="px-3 py-2">
                                <input type="text" class="form-control form-control-sm custom-dropdown-search-input" id="contractNoSearchInput" placeholder="Search contract number..." autocomplete="off">
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li id="contractNoDropdownItems">
                                <div class="px-3 py-2 text-muted text-center">Select Contract Number</div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Contract Period (only shown when Vendor Type is Contract) -->
                <div class="col-md-6" id="contractPeriodGroup" style="display: block;">
                    <label class="form-label fw-semibold">Contract Period <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="contractPeriod" name="contractPeriod" disabled placeholder="Will be auto-filled from contract number">
                </div>

                <!-- Top Type -->
                <div class="col-md-6" id="topTypeGroup">
                    <label class="form-label fw-semibold">Top Type <span class="text-danger">*</span></label>
                    <div class="dropdown" id="topTypeDropdownContainer">
                        <button class="form-select text-start custom-dropdown-toggle" type="button" id="topTypeDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                            <span id="topTypeSelectedText">Select Top Type</span>
                        </button>
                        <input type="hidden" id="topType" name="topType" required>
                        <ul class="dropdown-menu w-100 custom-dropdown-menu" id="topTypeDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                            <li class="px-3 py-2">
                                <input type="text" class="form-control form-control-sm custom-dropdown-search-input" id="topTypeSearchInput" placeholder="Search top type..." autocomplete="off">
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li id="topTypeDropdownItems">
                                <div class="px-3 py-2 text-muted text-center">Loading term of payment...</div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Description -->
                <div class="col-12" id="descriptionGroup">
                    <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="description" name="description" rows="4" placeholder="Will be auto-filled from Top Type" disabled required></textarea>
                </div>

                <!-- Remark Approval -->
                <div class="col-12" id="remarkApprovalGroup">
                    <label class="form-label fw-semibold">Remark Approval</label>
                    <textarea class="form-control" id="remarkApproval" name="remarkApproval" rows="3" placeholder="Enter remark approval"></textarea>
                </div>
            </div>
        </form>
    </div>
</div>

    @include('procurement.purchase_request.release.bulky_release._SharedReleaseDataLoader')

<script>
    (function() {
        'use strict';
        
        // Function to toggle field visibility based on Decision and Vendor Type
        function toggleBulkyReleaseFields() {
            // Find the form container - use the closest form to handle multiple instances
            const form = document.getElementById('bulkyReleaseForm');
            if (!form) return;
            
            const decisionSelect = form.querySelector('#bulkyReleaseDecision');
            const vendorTypeContract = form.querySelector('#vendorTypeContract');
            const vendorTypeNonContract = form.querySelector('#vendorTypeNonContract');
            
            // Check if elements exist
            if (!decisionSelect || !vendorTypeContract || !vendorTypeNonContract) {
                return;
            }
            
            const decision = decisionSelect.value;
            const isContract = vendorTypeContract.checked;
            const isNonContract = vendorTypeNonContract.checked;
            
            // Get all field groups within the form
            const vendorTypeGroup = form.querySelector('#vendorTypeGroup');
            const selectVendorGroup = form.querySelector('#selectVendorGroup');
            const coreBusinessGroup = form.querySelector('#coreBusinessGroup');
            const subCoreBusinessGroup = form.querySelector('#subCoreBusinessGroup');
            const contractNoGroup = form.querySelector('#contractNoGroup');
            const contractPeriodGroup = form.querySelector('#contractPeriodGroup');
            const topTypeGroup = form.querySelector('#topTypeGroup');
            const descriptionGroup = form.querySelector('#descriptionGroup');
            const remarkApprovalGroup = form.querySelector('#remarkApprovalGroup');
            
            // Get form inputs for required attribute management
            const selectVendor = form.querySelector('#selectVendor');
            const coreBusiness = form.querySelector('#coreBusiness');
            const subCoreBusiness = form.querySelector('#subCoreBusiness');
            const contractNoInput = form.querySelector('#contractNo');
            const contractPeriodInput = form.querySelector('#contractPeriod');
            const topType = form.querySelector('#topType');
            const description = form.querySelector('#description');
            
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
        window.toggleBulkyReleaseFields = toggleBulkyReleaseFields;
        
        // Flag to prevent multiple initializations
        let bulkyReleaseFieldsInitialized = false;
        
        // Function to initialize and set initial state
        function initializeBulkyReleaseFields() {
            // Prevent multiple initializations
            if (bulkyReleaseFieldsInitialized) {
                return true;
            }
            
            const form = document.getElementById('bulkyReleaseForm');
            if (!form) return false;
            
            const decisionSelect = form.querySelector('#bulkyReleaseDecision');
            const vendorTypeRadios = form.querySelectorAll('input[name="vendorType"]');
            
            // Check if elements exist
            if (!decisionSelect || vendorTypeRadios.length === 0) {
                return false;
            }
            
            // Set initial state
            toggleBulkyReleaseFields();
            
            bulkyReleaseFieldsInitialized = true;
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
            loadCoreBusiness(vendorCategoryId);
        }, 300);
        
        // Debounced handler for core business change (triggers API call)
        const debouncedCoreBusinessChange = debounceFunc(function(coreBusinessId) {
            const coreBusinessIdInt = parseInt(coreBusinessId, 10);
            if (!isNaN(coreBusinessIdInt) && coreBusinessIdInt > 0) {
                loadSubCoreBusiness(coreBusinessIdInt);
            }
        }, 300);
        
        // Use event delegation for dynamic content (only attach once)
        // This will work even if elements are added after page load
        if (!window.bulkyReleaseFieldsEventDelegationAttached) {
            document.addEventListener('change', function(e) {
                // Check if the changed element is within the bulky release form
                const form = e.target.closest('#bulkyReleaseForm');
                if (!form) return;
                
                // Check if the changed element is the decision dropdown
                if (e.target && e.target.id === 'bulkyReleaseDecision') {
                    toggleBulkyReleaseFields();
                }
                // Check if the changed element is a vendor type radio
                if (e.target && e.target.name === 'vendorType') {
                    toggleBulkyReleaseFields();
                }
                // Check if the changed element is the vendor hidden input
                if (e.target && e.target.id === 'selectVendor') {
                    const selectedVendorId = e.target.value;
                    if (selectedVendorId && vendorDataMap.has(selectedVendorId)) {
                        const vendorData = vendorDataMap.get(selectedVendorId);
                        // Use debounced handler to prevent multiple API calls
                        debouncedVendorChange(vendorData.vendorCategoryId);
                    } else {
                        // Clear Core Business if no vendor selected
                        const coreBusinessDropdownItems = document.getElementById('coreBusinessDropdownItems');
                        const coreBusinessSelectedText = document.getElementById('coreBusinessSelectedText');
                        const coreBusinessHiddenInput = document.getElementById('coreBusiness');
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
                        const subCoreBusinessDropdownItems = document.getElementById('subCoreBusinessDropdownItems');
                        const subCoreBusinessSelectedText = document.getElementById('subCoreBusinessSelectedText');
                        const subCoreBusinessHiddenInput = document.getElementById('subCoreBusiness');
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
                // Check if the changed element is the core business hidden input
                if (e.target && e.target.id === 'coreBusiness') {
                    const selectedCoreBusinessId = e.target.value;
                    if (selectedCoreBusinessId && selectedCoreBusinessId !== '') {
                        const coreBusinessIdInt = parseInt(selectedCoreBusinessId, 10);
                        if (!isNaN(coreBusinessIdInt) && coreBusinessIdInt > 0) {
                            // Use debounced handler to prevent multiple API calls
                            debouncedCoreBusinessChange(selectedCoreBusinessId);
                        } else {
                            // Clear Sub Core Business if invalid
                            const subCoreBusinessDropdownItems = document.getElementById('subCoreBusinessDropdownItems');
                            const subCoreBusinessSelectedText = document.getElementById('subCoreBusinessSelectedText');
                            const subCoreBusinessHiddenInput = document.getElementById('subCoreBusiness');
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
                            const contractNoDropdownItems = document.getElementById('contractNoDropdownItems');
                            const contractNoSelectedText = document.getElementById('contractNoSelectedText');
                            const contractNoHiddenInput = document.getElementById('contractNo');
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
                        const subCoreBusinessDropdownItems = document.getElementById('subCoreBusinessDropdownItems');
                        const subCoreBusinessSelectedText = document.getElementById('subCoreBusinessSelectedText');
                        const subCoreBusinessHiddenInput = document.getElementById('subCoreBusiness');
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
                        const contractNoDropdownItems = document.getElementById('contractNoDropdownItems');
                        const contractNoSelectedText = document.getElementById('contractNoSelectedText');
                        const contractNoHiddenInput = document.getElementById('contractNo');
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
                // Check if the changed element is the sub core business hidden input
                if (e.target && e.target.id === 'subCoreBusiness') {
                    const selectedSubCoreBusinessId = e.target.value;
                    if (selectedSubCoreBusinessId && selectedSubCoreBusinessId !== '') {
                        const subCoreBusinessIdInt = parseInt(selectedSubCoreBusinessId, 10);
                        if (!isNaN(subCoreBusinessIdInt) && subCoreBusinessIdInt > 0) {
                            // Load Contracts based on selected Sub Core Business ID
                            loadContracts(subCoreBusinessIdInt);
                        } else {
                            // Clear Contract No if invalid
                            const contractNoDropdownItems = document.getElementById('contractNoDropdownItems');
                            const contractNoSelectedText = document.getElementById('contractNoSelectedText');
                            const contractNoHiddenInput = document.getElementById('contractNo');
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
                        const contractNoDropdownItems = document.getElementById('contractNoDropdownItems');
                        const contractNoSelectedText = document.getElementById('contractNoSelectedText');
                        const contractNoHiddenInput = document.getElementById('contractNo');
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
                // Check if the changed element is the contract number hidden input
                if (e.target && e.target.id === 'contractNo') {
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
                                const contractPeriodInput = document.getElementById('contractPeriod');
                                if (contractPeriodInput) {
                                    contractPeriodInput.value = year.toString().padStart(4, '0');
                                }
                            }
                        }
                    } else {
                        // Clear contract period if no contract selected
                        const contractPeriodInput = document.getElementById('contractPeriod');
                        if (contractPeriodInput) {
                            contractPeriodInput.value = '';
                        }
                    }
                }
                // Check if the changed element is the top type hidden input
                if (e.target && e.target.id === 'topType') {
                    const selectedTopDescription = e.target.value;
                    if (selectedTopDescription && selectedTopDescription !== '' && topDataMap.has(selectedTopDescription)) {
                        const topData = topDataMap.get(selectedTopDescription);
                        // Auto-fill description with TOPRemarks
                        const descriptionTextarea = document.getElementById('description');
                        if (descriptionTextarea) {
                            descriptionTextarea.value = topData.topRemarks || '';
                        }
                    } else {
                        // Clear description if no TOP selected
                        const descriptionTextarea = document.getElementById('description');
                        if (descriptionTextarea) {
                            descriptionTextarea.value = '';
                        }
                    }
                }
            });
            window.bulkyReleaseFieldsEventDelegationAttached = true;
        }
        
        // Flag to prevent multiple simultaneous calls
        let coreBusinessLoading = false;
        let subCoreBusinessLoading = false;
        let contractsLoading = false;
        
        // Use shared data maps from shared loader
        const vendorDataMap = window.sharedReleaseData ? window.sharedReleaseData.vendorDataMap : new Map();
        const topDataMap = window.sharedReleaseData ? window.sharedReleaseData.topDataMap : new Map();
        
        // Store core business data with ID
        const coreBusinessDataMap = new Map();
        
        // Store sub core business data with ID
        const subCoreBusinessDataMap = new Map();
        
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
                getSearchableText
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

        // Function to load vendors from API using shared loader
        async function loadVendors() {
            const vendorHiddenInput = document.getElementById('selectVendor');
            const vendorDropdownItems = document.getElementById('selectVendorDropdownItems');
            const vendorSelectedText = document.getElementById('selectVendorSelectedText');
            const vendorSearchInput = document.getElementById('selectVendorSearchInput');
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
                    dropdownItemsId: 'selectVendorDropdownItems',
                    hiddenInputId: 'selectVendor',
                    selectedTextId: 'selectVendorSelectedText',
                    searchInputId: 'selectVendorSearchInput',
                    dropdownBtnId: 'selectVendorDropdownBtn',
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
                            dropdownItemsId: 'selectVendorDropdownItems',
                            hiddenInputId: 'selectVendor',
                            selectedTextId: 'selectVendorSelectedText',
                            searchInputId: 'selectVendorSearchInput',
                            dropdownBtnId: 'selectVendorDropdownBtn',
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
                const vendorDropdownBtn = document.getElementById('selectVendorDropdownBtn');
                if (vendorDropdownBtn) {
                    vendorDropdownBtn.addEventListener('shown.bs.dropdown', () => {
                        const searchInput = document.getElementById('selectVendorSearchInput');
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
        
        // Function to load Core Business based on selected vendor's VendorCategory
        async function loadCoreBusiness(vendorCategoryId) {
            const coreBusinessHiddenInput = document.getElementById('coreBusiness');
            const coreBusinessDropdownItems = document.getElementById('coreBusinessDropdownItems');
            const coreBusinessSelectedText = document.getElementById('coreBusinessSelectedText');
            const coreBusinessSearchInput = document.getElementById('coreBusinessSearchInput');
            if (!coreBusinessHiddenInput || !coreBusinessDropdownItems) return;
            
            // Prevent multiple simultaneous calls
            if (coreBusinessLoading) return;
            
            coreBusinessLoading = true;
            
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
                    coreBusinessLoading = false;
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
                coreBusinessDataMap.clear();
                
                // Store core business data with ID for later use
                coreBusinesses.forEach(cb => {
                    const coreBusinessId = cb.ID || cb.id || 0;
                    const coreBusinessIdInt = typeof coreBusinessId === 'number' ? coreBusinessId : parseInt(coreBusinessId, 10) || 0;
                    const coreBusinessName = cb.CoreBusiness || cb.coreBusiness || '';
                    
                    // Store core business data with ID for later use
                    coreBusinessDataMap.set(coreBusinessIdInt.toString(), {
                        id: coreBusinessIdInt,
                        name: coreBusinessName
                    });
                });
                
                // Clear Sub Core Business when Core Business is reloaded
                const subCoreBusinessDropdownItems = document.getElementById('subCoreBusinessDropdownItems');
                const subCoreBusinessSelectedText = document.getElementById('subCoreBusinessSelectedText');
                const subCoreBusinessHiddenInput = document.getElementById('subCoreBusiness');
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
                const contractNoDropdownItems = document.getElementById('contractNoDropdownItems');
                const contractNoSelectedText = document.getElementById('contractNoSelectedText');
                const contractNoHiddenInput = document.getElementById('contractNo');
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
                    dropdownItemsId: 'coreBusinessDropdownItems',
                    hiddenInputId: 'coreBusiness',
                    selectedTextId: 'coreBusinessSelectedText',
                    searchInputId: 'coreBusinessSearchInput',
                    dropdownBtnId: 'coreBusinessDropdownBtn',
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
                            dropdownItemsId: 'coreBusinessDropdownItems',
                            hiddenInputId: 'coreBusiness',
                            selectedTextId: 'coreBusinessSelectedText',
                            searchInputId: 'coreBusinessSearchInput',
                            dropdownBtnId: 'coreBusinessDropdownBtn',
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
                const coreBusinessDropdownBtn = document.getElementById('coreBusinessDropdownBtn');
                if (coreBusinessDropdownBtn) {
                    coreBusinessDropdownBtn.addEventListener('shown.bs.dropdown', () => {
                        const searchInput = document.getElementById('coreBusinessSearchInput');
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
                coreBusinessLoading = false;
            }
        }
        
        // Function to load Sub Core Business based on selected Core Business ID
        async function loadSubCoreBusiness(coreBusinessId) {
            const subCoreBusinessHiddenInput = document.getElementById('subCoreBusiness');
            const subCoreBusinessDropdownItems = document.getElementById('subCoreBusinessDropdownItems');
            const subCoreBusinessSelectedText = document.getElementById('subCoreBusinessSelectedText');
            const subCoreBusinessSearchInput = document.getElementById('subCoreBusinessSearchInput');
            if (!subCoreBusinessHiddenInput || !subCoreBusinessDropdownItems) return;
            
            // Prevent multiple simultaneous calls
            if (subCoreBusinessLoading) return;
            
            subCoreBusinessLoading = true;
            
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
                    subCoreBusinessLoading = false;
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
                subCoreBusinessDataMap.clear();
                
                // Store sub core business data with ID for later use
                subCoreBusinesses.forEach(scb => {
                    const subCoreBusinessId = scb.ID || scb.id || 0;
                    const subCoreBusinessIdInt = typeof subCoreBusinessId === 'number' ? subCoreBusinessId : parseInt(subCoreBusinessId, 10) || 0;
                    const subCoreBusinessName = scb.SubCoreBusiness || scb.subCoreBusiness || '';
                    
                    // Store sub core business data with ID for later use
                    subCoreBusinessDataMap.set(subCoreBusinessIdInt.toString(), {
                        id: subCoreBusinessIdInt,
                        name: subCoreBusinessName
                    });
                });
                
                // Render dropdown with search
                renderDropdownWithSearch({
                    items: subCoreBusinesses,
                    searchTerm: '',
                    dropdownItemsId: 'subCoreBusinessDropdownItems',
                    hiddenInputId: 'subCoreBusiness',
                    selectedTextId: 'subCoreBusinessSelectedText',
                    searchInputId: 'subCoreBusinessSearchInput',
                    dropdownBtnId: 'subCoreBusinessDropdownBtn',
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
                            dropdownItemsId: 'subCoreBusinessDropdownItems',
                            hiddenInputId: 'subCoreBusiness',
                            selectedTextId: 'subCoreBusinessSelectedText',
                            searchInputId: 'subCoreBusinessSearchInput',
                            dropdownBtnId: 'subCoreBusinessDropdownBtn',
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
                const subCoreBusinessDropdownBtn = document.getElementById('subCoreBusinessDropdownBtn');
                if (subCoreBusinessDropdownBtn) {
                    subCoreBusinessDropdownBtn.addEventListener('shown.bs.dropdown', () => {
                        const searchInput = document.getElementById('subCoreBusinessSearchInput');
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
                subCoreBusinessLoading = false;
            }
        }
        
        // Function to load Contracts based on selected Sub Core Business ID
        async function loadContracts(subCoreBusinessId) {
            const contractNoHiddenInput = document.getElementById('contractNo');
            const contractNoDropdownItems = document.getElementById('contractNoDropdownItems');
            const contractNoSelectedText = document.getElementById('contractNoSelectedText');
            const contractNoSearchInput = document.getElementById('contractNoSearchInput');
            if (!contractNoHiddenInput || !contractNoDropdownItems) return;
            
            // Prevent multiple simultaneous calls
            if (contractsLoading) return;
            
            contractsLoading = true;
            
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
                    contractsLoading = false;
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
                    dropdownItemsId: 'contractNoDropdownItems',
                    hiddenInputId: 'contractNo',
                    selectedTextId: 'contractNoSelectedText',
                    searchInputId: 'contractNoSearchInput',
                    dropdownBtnId: 'contractNoDropdownBtn',
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
                            dropdownItemsId: 'contractNoDropdownItems',
                            hiddenInputId: 'contractNo',
                            selectedTextId: 'contractNoSelectedText',
                            searchInputId: 'contractNoSearchInput',
                            dropdownBtnId: 'contractNoDropdownBtn',
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
                const contractNoDropdownBtn = document.getElementById('contractNoDropdownBtn');
                if (contractNoDropdownBtn) {
                    contractNoDropdownBtn.addEventListener('shown.bs.dropdown', () => {
                        const searchInput = document.getElementById('contractNoSearchInput');
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
                contractsLoading = false;
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
                
                // Check if additionalData exists and StartPeriod and EndPeriod are not NULL
                if (additionalData && 
                    additionalData.startPeriod != null && 
                    additionalData.endPeriod != null &&
                    additionalData.StartPeriod != null && 
                    additionalData.EndPeriod != null) {
                    return true;
                }
                
                // Also check with camelCase properties
                if (additionalData && 
                    (additionalData.startPeriod != null || additionalData.StartPeriod != null) && 
                    (additionalData.endPeriod != null || additionalData.EndPeriod != null)) {
                    return true;
                }
                
                return false;
            } catch (error) {
                // If error is 404, PR doesn't have additional data
                if (error.message && error.message.includes('404')) {
                    return false;
                }
                console.error('Error checking PR periods:', error);
                return false;
            }
        }

        // Function to load Term of Payment (TOP) from API using shared loader
        // Expose globally so it can be called after PR is selected
        window.loadTermOfPayments = async function loadTermOfPayments() {
            const topTypeHiddenInput = document.getElementById('topType');
            const topTypeDropdownItems = document.getElementById('topTypeDropdownItems');
            const topTypeSelectedText = document.getElementById('topTypeSelectedText');
            const topTypeSearchInput = document.getElementById('topTypeSearchInput');
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
                
                // Check if PR has StartPeriod and EndPeriod not NULL
                // Get PR Number from releaseListManager
                let shouldFilterTOP = false;
                if (typeof window.releaseListManager !== 'undefined' && window.releaseListManager.currentPRNumber) {
                    const prNumber = window.releaseListManager.currentPRNumber;
                    shouldFilterTOP = await checkPRHasPeriods(prNumber);
                    console.log(`Checking PR ${prNumber} for periods. Should filter TOP: ${shouldFilterTOP}`);
                }
                
                // Filter TOP: if PR has periods, only show TOP with ID 759, 760, 761
                if (shouldFilterTOP) {
                    const originalCount = tops.length;
                    tops = tops.filter(top => {
                        const topId = parseInt(top.ID || top.id || 0, 10);
                        return topId === 759 || topId === 760 || topId === 761;
                    });
                    console.log(`Filtered TOP from ${originalCount} to ${tops.length} (IDs: 759, 760, 761)`);
                } else {
                    console.log('No filter applied - showing all TOPs');
                }
                
                if (tops.length === 0) {
                    topTypeDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No term of payment found</div>';
                    return;
                }
                
                // Render dropdown with search
                renderDropdownWithSearch({
                    items: tops,
                    searchTerm: '',
                    dropdownItemsId: 'topTypeDropdownItems',
                    hiddenInputId: 'topType',
                    selectedTextId: 'topTypeSelectedText',
                    searchInputId: 'topTypeSearchInput',
                    dropdownBtnId: 'topTypeDropdownBtn',
                    getValue: (top) => top.TOPDescription || top.topDescription || '',
                    getText: (top) => top.TOPDescription || top.topDescription || '',
                    getSearchableText: (top) => (top.TOPDescription || top.topDescription || '').toString()
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
                            dropdownItemsId: 'topTypeDropdownItems',
                            hiddenInputId: 'topType',
                            selectedTextId: 'topTypeSelectedText',
                            searchInputId: 'topTypeSearchInput',
                            dropdownBtnId: 'topTypeDropdownBtn',
                            getValue: (top) => top.TOPDescription || top.topDescription || '',
                            getText: (top) => top.TOPDescription || top.topDescription || '',
                            getSearchableText: (top) => (top.TOPDescription || top.topDescription || '').toString()
                        });
                    });

                    newSearchInput.addEventListener('click', (e) => {
                        e.stopPropagation();
                    });
                }

                // Focus search input when dropdown opens
                const topTypeDropdownBtn = document.getElementById('topTypeDropdownBtn');
                if (topTypeDropdownBtn) {
                    topTypeDropdownBtn.addEventListener('shown.bs.dropdown', () => {
                        const searchInput = document.getElementById('topTypeSearchInput');
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
            ? debounce(initializeBulkyReleaseFields, 200)
            : (function() {
                let timeout;
                return function() {
                    clearTimeout(timeout);
                    timeout = setTimeout(initializeBulkyReleaseFields, 200);
                };
            })();
        
        // Flag to prevent multiple initialization calls
        let initializeOnceCalled = false;
        
        // Function to initialize once DOM is ready
        function initializeOnce() {
            // Prevent multiple calls
            if (initializeOnceCalled) return;
            initializeOnceCalled = true;
            
            if (initializeBulkyReleaseFields()) {
                // Load vendors and TOPs after successful initialization
                // Shared loader will prevent duplicate API calls
                loadVendors();
                loadTermOfPayments();
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
            const observer = new MutationObserver(function(mutations) {
                let shouldInitialize = false;
                mutations.forEach(function(mutation) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            // Check if the added node or its children contain the form
                            const form = node.querySelector ? node.querySelector('#bulkyReleaseForm') : null;
                            if (form || (node.id === 'bulkyReleaseForm')) {
                                shouldInitialize = true;
                            }
                        }
                    });
                });
                
                // Use debounced initialization to prevent multiple calls
                // Call initializeOnce which will handle loading vendors and TOPs with proper flags
                if (shouldInitialize) {
                    // Use debounced version to prevent rapid multiple calls
                    debouncedInitialize();
                    // Also call initializeOnce to load vendors and TOPs if needed
                    initializeOnce();
                    debouncedInitialize();
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


