/**
 * Procurement Wizard Additional Module
 * Handles Additional step validation, billing type, sonumb, and subscribe sections
 */

class ProcurementWizardAdditional {
    constructor(wizardInstance) {
        this.wizard = wizardInstance;
    }

    /**
     * Validate Additional step
     */
    validateAdditionalStep() {
        // Clear previous validation errors
        const additionalStep = document.getElementById('additional');
        if (additionalStep && this.wizard.validationModule && this.wizard.validationModule.clearAllFieldErrors) {
            this.wizard.validationModule.clearAllFieldErrors(additionalStep);
        }

        // Check which section is visible
        const billingTypeSection = document.getElementById('billingTypeSection');
        const sonumbSection = document.getElementById('sonumbSection');
        const subscribeSection = document.getElementById('subscribeSection');
        const isBillingTypeVisible = billingTypeSection && billingTypeSection.style.display !== 'none';
        const isSonumbVisible = sonumbSection && sonumbSection.style.display !== 'none';
        const isSubscribeVisible = subscribeSection && subscribeSection.style.display !== 'none';

        // Validate Billing Type section (for Type ID 6 with Sub Type 2)
        if (isBillingTypeVisible) {
            const billingTypeField = document.getElementById('BillingType');
            const startPeriodField = document.getElementById('StartPeriod');
            const periodField = document.getElementById('Period');
            const endPeriodField = document.getElementById('EndPeriod');

            // Validate Billing Type
            if (!billingTypeField || !billingTypeField.value) {
                const billingTypeBtn = document.getElementById('billingTypeDropdownBtn');
                if (billingTypeBtn) {
                    billingTypeBtn.classList.add('is-invalid');
                    if (this.wizard.validationModule && this.wizard.validationModule.showFieldError) {
                        this.wizard.validationModule.showFieldError(billingTypeBtn, 'Billing Type is required');
                    }
                    billingTypeBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return false;
            }

            // Validate Start Period
            if (!startPeriodField || !startPeriodField.value) {
                if (startPeriodField) {
                    startPeriodField.classList.add('is-invalid');
                    if (this.wizard.validationModule && this.wizard.validationModule.showFieldError) {
                        this.wizard.validationModule.showFieldError(startPeriodField, 'Start Period is required');
                    }
                    startPeriodField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return false;
            }

            // Validate Period
            if (!periodField || !periodField.value || parseInt(periodField.value, 10) < 1) {
                if (periodField) {
                    periodField.classList.add('is-invalid');
                    if (this.wizard.validationModule && this.wizard.validationModule.showFieldError) {
                        this.wizard.validationModule.showFieldError(periodField, 'Period is required (minimum 1)');
                    }
                    periodField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return false;
            }

            // Validate End Period (auto-calculated, but should exist)
            if (!endPeriodField || !endPeriodField.value) {
                if (this.wizard.showValidationMessage) {
                    this.wizard.showValidationMessage('End Period is not calculated. Please check your inputs.');
                }
                return false;
            }
        }

        // Validate Sonumb section (for Type ID 4 or 8)
        // Note: Sonumb and Site Name are optional fields, no validation required
        if (isSonumbVisible) {
            // Sonumb and Site Name are optional - no validation needed
            // User can proceed without filling these fields
        }

        // Validate Subscribe section (for Type ID 5 or 7)
        if (isSubscribeVisible) {
            const subscribeSonumbField = document.getElementById('SubscribeSonumb');
            const subscribeBillingTypeField = document.getElementById('SubscribeBillingType');
            const subscribeStartPeriod = document.getElementById('SubscribeStartPeriod');
            const subscribePeriod = document.getElementById('SubscribePeriod');
            const subscribeEndPeriod = document.getElementById('SubscribeEndPeriod');

            // Note: Subscribe Sonumb and Subscribe Site Name are optional fields, no validation required
            // User can proceed without filling these fields

            // Validate Billing Type
            if (!subscribeBillingTypeField || !subscribeBillingTypeField.value) {
                const subscribeBillingTypeBtn = document.getElementById('subscribeBillingTypeDropdownBtn');
                if (subscribeBillingTypeBtn) {
                    subscribeBillingTypeBtn.classList.add('is-invalid');
                    if (this.wizard.validationModule && this.wizard.validationModule.showFieldError) {
                        this.wizard.validationModule.showFieldError(subscribeBillingTypeBtn, 'Subscribe Billing Type is required');
                    }
                    subscribeBillingTypeBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return false;
            }

            // Validate Start Period
            if (!subscribeStartPeriod || !subscribeStartPeriod.value) {
                if (subscribeStartPeriod) {
                    subscribeStartPeriod.classList.add('is-invalid');
                    if (this.wizard.validationModule && this.wizard.validationModule.showFieldError) {
                        this.wizard.validationModule.showFieldError(subscribeStartPeriod, 'Subscribe Start Period is required');
                    }
                    subscribeStartPeriod.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return false;
            }

            // Validate Period
            if (!subscribePeriod || !subscribePeriod.value || parseInt(subscribePeriod.value, 10) < 1) {
                if (subscribePeriod) {
                    subscribePeriod.classList.add('is-invalid');
                    if (this.wizard.validationModule && this.wizard.validationModule.showFieldError) {
                        this.wizard.validationModule.showFieldError(subscribePeriod, 'Subscribe Period is required (minimum 1)');
                    }
                    subscribePeriod.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return false;
            }

            // Validate End Period (auto-calculated, but should exist)
            if (!subscribeEndPeriod || !subscribeEndPeriod.value) {
                if (this.wizard.showValidationMessage) {
                    this.wizard.showValidationMessage('End Period is not calculated. Please check your inputs.');
                }
                return false;
            }
        }

        return true;
    }

    /**
     * Initialize Billing Type dropdown
     */
    initializeBillingTypeDropdown() {
        const billingTypeDropdownBtn = document.getElementById('billingTypeDropdownBtn');
        const billingTypeDropdownMenu = document.getElementById('billingTypeDropdownMenu');
        const billingTypeHiddenInput = document.getElementById('BillingType');
        const billingTypeSelectedText = document.getElementById('billingTypeSelectedText');
        const billingPeriodContainer = document.getElementById('billingPeriodContainer');
        const billingPeriodLabel = document.getElementById('billingPeriodLabel');

        if (!billingTypeDropdownBtn || !billingTypeDropdownMenu || !billingTypeHiddenInput || !billingTypeSelectedText) {
            return;
        }

        // Handle dropdown item selection
        const dropdownItems = billingTypeDropdownMenu.querySelectorAll('.dropdown-item');
        dropdownItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                const value = item.getAttribute('data-value');
                const text = item.getAttribute('data-text');

                if (value && text) {
                    billingTypeHiddenInput.value = value;
                    billingTypeSelectedText.textContent = text;
                    
                    // Remove error message and invalid class when billing type is selected
                    billingTypeDropdownBtn.classList.remove('is-invalid');
                    if (this.wizard.validationModule && this.wizard.validationModule.hideFieldError) {
                        this.wizard.validationModule.hideFieldError(billingTypeDropdownBtn);
                    }
                    
                    // Trigger change event to clear validation errors
                    billingTypeHiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                    
                    // Show billing period container and update label
                    if (billingPeriodContainer) {
                        billingPeriodContainer.style.display = 'block';
                    }
                    if (billingPeriodLabel) {
                        billingPeriodLabel.textContent = text;
                    }

                    // Close dropdown
                    const dropdown = bootstrap.Dropdown.getInstance(billingTypeDropdownBtn);
                    if (dropdown) {
                        dropdown.hide();
                    }

                    // Trigger End Period calculation if Start Period and Period are already filled
                    this.calculateEndPeriod();
                }
            });
        });
    }

    /**
     * Initialize Additional step listeners
     */
    initializeAdditionalStepListeners() {
        const startPeriodField = document.getElementById('StartPeriod');
        const periodField = document.getElementById('Period');
        const billingTypeField = document.getElementById('BillingType');

        // Add event listeners for Start Period, Period, and Billing Type changes
        if (startPeriodField) {
            startPeriodField.addEventListener('change', () => {
                this.calculateEndPeriod();
            });
        }

        if (periodField) {
            periodField.addEventListener('input', () => {
                this.calculateEndPeriod();
                if (this.wizard.updateQuantityFieldBasedOnPeriod) {
                    this.wizard.updateQuantityFieldBasedOnPeriod();
                }
                if (this.wizard.updateQuantityInGridItems) {
                    this.wizard.updateQuantityInGridItems();
                }
            });
            periodField.addEventListener('change', () => {
                this.calculateEndPeriod();
                if (this.wizard.updateQuantityFieldBasedOnPeriod) {
                    this.wizard.updateQuantityFieldBasedOnPeriod();
                }
                if (this.wizard.updateQuantityInGridItems) {
                    this.wizard.updateQuantityInGridItems();
                }
            });
        }

        // Add event listener for billing type change
        if (billingTypeField) {
            billingTypeField.addEventListener('change', () => {
                this.calculateEndPeriod();
            });
        }
    }

    /**
     * Calculate End Period based on Start Period, Period, and Billing Type
     */
    calculateEndPeriod() {
        const billingTypeField = document.getElementById('BillingType');
        const startPeriodField = document.getElementById('StartPeriod');
        const periodField = document.getElementById('Period');
        const endPeriodField = document.getElementById('EndPeriod');

        if (!billingTypeField || !startPeriodField || !periodField || !endPeriodField) {
            return;
        }

        const billingTypeID = billingTypeField.value;
        const startDateStr = startPeriodField.value;
        const periodValue = parseInt(periodField.value, 10);

        // Clear End Period if required fields are missing
        if (!billingTypeID || !startDateStr || !periodValue || periodValue < 1) {
            endPeriodField.value = '';
            return;
        }

        // Get billing type from cached data
        const billingType = this.wizard.billingTypes.find(bt => (bt.ID || bt.id) == billingTypeID);
        if (!billingType) {
            endPeriodField.value = '';
            return;
        }

        // Get TotalMonthPeriod from billing type data
        const monthsPerPeriod = billingType.TotalMonthPeriod || billingType.totalMonthPeriod || 0;
        if (monthsPerPeriod <= 0) {
            endPeriodField.value = '';
            return;
        }

        // Calculate total months: billing type multiplier * period
        const totalMonths = monthsPerPeriod * periodValue;

        // Calculate End Period: Start Period + total months, then set to last day of that month
        const startDate = new Date(startDateStr);
        const endDate = new Date(startDate);
        
        // Add totalMonths to get to the month after the end month, then subtract 1 month to get end month
        endDate.setMonth(endDate.getMonth() + totalMonths);
        endDate.setMonth(endDate.getMonth() - 1);
        
        // Set to the first day of the next month, then subtract 1 day to get last day of end month
        endDate.setMonth(endDate.getMonth() + 1, 1);
        endDate.setDate(endDate.getDate() - 1);

        // Format as YYYY-MM-DD
        const year = endDate.getFullYear();
        const month = String(endDate.getMonth() + 1).padStart(2, '0');
        const day = String(endDate.getDate()).padStart(2, '0');
        endPeriodField.value = `${year}-${month}-${day}`;
    }

    /**
     * Update Additional section visibility based on Type ID and Sub Type ID
     */
    updateAdditionalSectionVisibility(typeId, subTypeId) {
        const billingTypeSection = document.getElementById('billingTypeSection');
        const billingPeriodContainer = document.getElementById('billingPeriodContainer');
        const sonumbSection = document.getElementById('sonumbSection');
        const subscribeSection = document.getElementById('subscribeSection');
        const subscribePeriodContainer = document.getElementById('subscribePeriodContainer');
        const additionalDescription = document.getElementById('additionalDescription');
        
        // Hide all sections first
        if (billingTypeSection) billingTypeSection.style.display = 'none';
        if (billingPeriodContainer) billingPeriodContainer.style.display = 'none';
        if (sonumbSection) sonumbSection.style.display = 'none';
        if (subscribeSection) subscribeSection.style.display = 'none';
        if (subscribePeriodContainer) subscribePeriodContainer.style.display = 'none';
        
        // Helper function to check if Sonumb Section should be shown
        const shouldShowSonumbSection = () => {
            if (!subTypeId) return false;
            if (typeId === 8 && subTypeId === 4) return true;
            if (typeId === 2 && (subTypeId === 1 || subTypeId === 3)) return true;
            if (typeId === 4 && subTypeId === 3) return true;
            if (typeId === 3 && (subTypeId === 4 || subTypeId === 5)) return true;
            return false;
        };
        
        if (typeId === 6 && subTypeId === 2) {
            // Show Billing Type section
            if (billingTypeSection) billingTypeSection.style.display = 'block';
            
            // Check if billing type is already selected (for revision mode)
            const billingTypeHiddenInput = document.getElementById('BillingType');
            const billingTypeSelectedText = document.getElementById('billingTypeSelectedText');
            if (billingPeriodContainer && billingTypeHiddenInput && billingTypeHiddenInput.value) {
                billingPeriodContainer.style.display = 'block';
                if (billingTypeSelectedText && billingTypeSelectedText.textContent) {
                    const billingPeriodLabel = document.getElementById('billingPeriodLabel');
                    if (billingPeriodLabel) {
                        billingPeriodLabel.textContent = billingTypeSelectedText.textContent;
                    }
                }
                
                const periodField = document.getElementById('Period');
                if (periodField && periodField.value) {
                    setTimeout(() => {
                        this.calculateEndPeriod();
                    }, 100);
                }
            }
            
            if (sonumbSection) sonumbSection.style.display = 'none';
            if (subscribeSection) subscribeSection.style.display = 'none';
            if (additionalDescription) additionalDescription.textContent = 'Enter additional billing information.';
        } else if (typeId === 5 || typeId === 7) {
            // Show Subscribe section
            if (billingTypeSection) billingTypeSection.style.display = 'none';
            if (billingPeriodContainer) billingPeriodContainer.style.display = 'none';
            if (sonumbSection) sonumbSection.style.display = 'none';
            if (subscribeSection) subscribeSection.style.display = 'block';
            if (additionalDescription) additionalDescription.textContent = 'Enter additional subscribe information.';
            
            // Re-initialize Subscribe Billing Type dropdown when section is shown
            if (this.wizard.billingTypes && this.wizard.billingTypes.length > 0) {
                this.initializeSubscribeBillingTypeDropdown();
            }
            
            // Check if subscribe billing type is already selected (for revision mode)
            const subscribeBillingTypeHiddenInput = document.getElementById('SubscribeBillingType');
            const subscribeBillingTypeSelectedText = document.getElementById('subscribeBillingTypeSelectedText');
            if (subscribePeriodContainer && subscribeBillingTypeHiddenInput && subscribeBillingTypeHiddenInput.value) {
                subscribePeriodContainer.style.display = 'block';
                if (subscribeBillingTypeSelectedText && subscribeBillingTypeSelectedText.textContent) {
                    const subscribePeriodLabel = document.getElementById('subscribePeriodLabel');
                    if (subscribePeriodLabel) {
                        subscribePeriodLabel.textContent = subscribeBillingTypeSelectedText.textContent;
                    }
                }
                
                const subscribePeriodField = document.getElementById('SubscribePeriod');
                if (subscribePeriodField && subscribePeriodField.value) {
                    setTimeout(() => {
                        this.calculateSubscribeEndPeriod();
                    }, 100);
                }
            }
        } else if (shouldShowSonumbSection()) {
            // Show Sonumb section
            if (billingTypeSection) billingTypeSection.style.display = 'none';
            if (billingPeriodContainer) billingPeriodContainer.style.display = 'none';
            if (sonumbSection) sonumbSection.style.display = 'block';
            if (subscribeSection) subscribeSection.style.display = 'none';
            if (additionalDescription) additionalDescription.textContent = 'Enter additional site information.';
        } else {
            // Hide all sections
            if (billingTypeSection) billingTypeSection.style.display = 'none';
            if (billingPeriodContainer) billingPeriodContainer.style.display = 'none';
            if (sonumbSection) sonumbSection.style.display = 'none';
            if (subscribeSection) subscribeSection.style.display = 'none';
        }
        
        // Update quantity field based on period field visibility
        if (this.wizard.updateQuantityFieldBasedOnPeriod) {
            this.wizard.updateQuantityFieldBasedOnPeriod();
        }
    }

    /**
     * Set Additional fields to readonly (for status 5 - Rejected)
     * Note: StartPeriod and Period remain editable, EndPeriod stays readonly and auto-calculated
     * If Sonumb is null/empty, enable field for revision
     */
    setAdditionalReadonlyForRejected(prData = null) {
        // Disable BillingType dropdown (button and hidden input) - for Type ID 6 with Sub Type 2
        const billingTypeDropdownBtn = document.getElementById('billingTypeDropdownBtn');
        if (billingTypeDropdownBtn) {
            billingTypeDropdownBtn.disabled = true;
        }
        const billingTypeHiddenInput = document.getElementById('BillingType');
        if (billingTypeHiddenInput) {
            billingTypeHiddenInput.disabled = true;
        }
        
        // Keep StartPeriod and Period editable (not disabled)
        const startPeriodField = document.getElementById('StartPeriod');
        if (startPeriodField) {
            startPeriodField.disabled = false;
            startPeriodField.removeAttribute('readonly');
        }
        
        const periodField = document.getElementById('Period');
        if (periodField) {
            periodField.disabled = false;
            periodField.removeAttribute('readonly');
        }
        
        // Ensure EndPeriod stays readonly (already readonly in HTML, but ensure it)
        const endPeriodField = document.getElementById('EndPeriod');
        if (endPeriodField) {
            endPeriodField.setAttribute('readonly', 'readonly');
            endPeriodField.readOnly = true;
            endPeriodField.disabled = false; // Not disabled, just readonly
        }
        
        // Check SonumbId value - if null/empty, enable field for revision
        // Get from field value (already filled by loadAndFillAdditional)
        const sonumbIdField = document.getElementById('SonumbId');
        let sonumbIdValue = null;
        if (sonumbIdField && sonumbIdField.value) {
            sonumbIdValue = sonumbIdField.value.trim();
        }
        
        const isSonumbIdEmpty = !sonumbIdValue || sonumbIdValue === '' || sonumbIdValue === '0' || sonumbIdValue === 0 || isNaN(parseInt(sonumbIdValue));
        
        // Enable or disable Sonumb and SiteName fields based on SonumbId value - for Type ID 4 or 8
        // If SonumbId is null/empty, enable fields for revision
        // If SonumbId is not null, disable fields
        const sonumbField = document.getElementById('Sonumb');
        if (sonumbField) {
            sonumbField.disabled = !isSonumbIdEmpty; // Enable if empty, disable if has value
            if (isSonumbIdEmpty) {
                sonumbField.removeAttribute('readonly');
            } else {
                sonumbField.setAttribute('readonly', 'readonly');
            }
        }
        if (sonumbIdField) {
            sonumbIdField.disabled = !isSonumbIdEmpty; // Enable if empty, disable if has value
        }
        const searchSTIPSonumbBtn = document.getElementById('searchSTIPSonumbBtn');
        if (searchSTIPSonumbBtn) {
            searchSTIPSonumbBtn.disabled = !isSonumbIdEmpty; // Enable if empty, disable if has value
        }
        const siteNameField = document.getElementById('SiteName');
        if (siteNameField) {
            siteNameField.disabled = !isSonumbIdEmpty; // Enable if empty, disable if has value
            if (isSonumbIdEmpty) {
                siteNameField.removeAttribute('readonly');
            } else {
                siteNameField.setAttribute('readonly', 'readonly');
            }
        }
        const siteIdField = document.getElementById('SiteID');
        if (siteIdField) {
            siteIdField.disabled = !isSonumbIdEmpty; // Enable if empty, disable if has value
        }
        
        // Disable SubscribeBillingType dropdown - for Type ID 7
        const subscribeBillingTypeDropdownBtn = document.getElementById('subscribeBillingTypeDropdownBtn');
        if (subscribeBillingTypeDropdownBtn) {
            subscribeBillingTypeDropdownBtn.disabled = true;
        }
        const subscribeBillingTypeHiddenInput = document.getElementById('SubscribeBillingType');
        if (subscribeBillingTypeHiddenInput) {
            subscribeBillingTypeHiddenInput.disabled = true;
        }
        
        // Keep SubscribeStartPeriod and SubscribePeriod editable (not disabled)
        const subscribeStartPeriodField = document.getElementById('SubscribeStartPeriod');
        if (subscribeStartPeriodField) {
            subscribeStartPeriodField.disabled = false;
            subscribeStartPeriodField.removeAttribute('readonly');
        }
        
        const subscribePeriodField = document.getElementById('SubscribePeriod');
        if (subscribePeriodField) {
            subscribePeriodField.disabled = false;
            subscribePeriodField.removeAttribute('readonly');
        }
        
        // Ensure SubscribeEndPeriod stays readonly (already readonly in HTML, but ensure it)
        const subscribeEndPeriodField = document.getElementById('SubscribeEndPeriod');
        if (subscribeEndPeriodField) {
            subscribeEndPeriodField.setAttribute('readonly', 'readonly');
            subscribeEndPeriodField.readOnly = true;
            subscribeEndPeriodField.disabled = false; // Not disabled, just readonly
        }
        
        // Check SubscribeSonumbId value - if null/empty, enable field for revision
        // Get from field value (already filled by loadAndFillAdditional)
        const subscribeSonumbIdField = document.getElementById('SubscribeSonumbId');
        let subscribeSonumbIdValue = null;
        if (subscribeSonumbIdField && subscribeSonumbIdField.value) {
            subscribeSonumbIdValue = subscribeSonumbIdField.value.trim();
        }
        
        const isSubscribeSonumbIdEmpty = !subscribeSonumbIdValue || subscribeSonumbIdValue === '' || subscribeSonumbIdValue === '0' || subscribeSonumbIdValue === 0 || isNaN(parseInt(subscribeSonumbIdValue));
        
        // Enable or disable SubscribeSonumb and SubscribeSiteName fields based on SubscribeSonumbId value - for Type ID 7
        // If SubscribeSonumbId is null/empty, enable fields for revision
        // If SubscribeSonumbId is not null, disable fields
        const subscribeSonumbField = document.getElementById('SubscribeSonumb');
        if (subscribeSonumbField) {
            subscribeSonumbField.disabled = !isSubscribeSonumbIdEmpty; // Enable if empty, disable if has value
            if (isSubscribeSonumbIdEmpty) {
                subscribeSonumbField.removeAttribute('readonly');
            } else {
                subscribeSonumbField.setAttribute('readonly', 'readonly');
            }
        }
        if (subscribeSonumbIdField) {
            subscribeSonumbIdField.disabled = !isSubscribeSonumbIdEmpty; // Enable if empty, disable if has value
        }
        const searchSubscribeSonumbBtn = document.getElementById('searchSubscribeSonumbBtn');
        if (searchSubscribeSonumbBtn) {
            searchSubscribeSonumbBtn.disabled = !isSubscribeSonumbIdEmpty; // Enable if empty, disable if has value
        }
        const subscribeSiteNameField = document.getElementById('SubscribeSiteName');
        if (subscribeSiteNameField) {
            subscribeSiteNameField.disabled = !isSubscribeSonumbIdEmpty; // Enable if empty, disable if has value
            if (isSubscribeSonumbIdEmpty) {
                subscribeSiteNameField.removeAttribute('readonly');
            } else {
                subscribeSiteNameField.setAttribute('readonly', 'readonly');
            }
        }
        const subscribeSiteIdField = document.getElementById('SubscribeSiteID');
        if (subscribeSiteIdField) {
            subscribeSiteIdField.disabled = !isSubscribeSonumbIdEmpty; // Enable if empty, disable if has value
        }
    }

    /**
     * Initialize Subscribe Billing Type dropdown
     */
    initializeSubscribeBillingTypeDropdown() {
        const subscribeBillingTypeDropdownBtn = document.getElementById('subscribeBillingTypeDropdownBtn');
        const subscribeBillingTypeDropdownMenu = document.getElementById('subscribeBillingTypeDropdownMenu');
        const subscribeBillingTypeHiddenInput = document.getElementById('SubscribeBillingType');
        const subscribeBillingTypeSelectedText = document.getElementById('subscribeBillingTypeSelectedText');
        const subscribePeriodContainer = document.getElementById('subscribePeriodContainer');
        const subscribePeriodLabel = document.getElementById('subscribePeriodLabel');

        if (!subscribeBillingTypeDropdownBtn || !subscribeBillingTypeDropdownMenu || !subscribeBillingTypeHiddenInput || !subscribeBillingTypeSelectedText) {
            return;
        }

        // Clear existing content
        subscribeBillingTypeDropdownMenu.innerHTML = '';

        // Populate dropdown menu from cached billing types
        if (this.wizard.billingTypes && this.wizard.billingTypes.length > 0) {
            this.wizard.billingTypes.forEach(billingType => {
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
                subscribeBillingTypeDropdownMenu.appendChild(li);
            });
        } else {
            subscribeBillingTypeDropdownMenu.innerHTML = '<li><div class="px-3 py-2 text-muted text-center">Loading billing types...</div></li>';
        }

        // Remove existing event listeners by cloning and replacing
        const clonedMenu = subscribeBillingTypeDropdownMenu.cloneNode(true);
        subscribeBillingTypeDropdownMenu.parentNode.replaceChild(clonedMenu, subscribeBillingTypeDropdownMenu);
        
        const menuElement = document.getElementById('subscribeBillingTypeDropdownMenu');

        // Use event delegation for dropdown item selection
        menuElement.addEventListener('click', (e) => {
            const item = e.target.closest('.dropdown-item');
            if (!item) return;

            e.preventDefault();
            e.stopPropagation();

            const value = item.getAttribute('data-value');
            const text = item.getAttribute('data-text');

            if (value && text) {
                subscribeBillingTypeHiddenInput.value = value;
                subscribeBillingTypeSelectedText.textContent = text;
                
                // Remove error message and invalid class when billing type is selected
                subscribeBillingTypeDropdownBtn.classList.remove('is-invalid');
                if (this.wizard.validationModule && this.wizard.validationModule.hideFieldError) {
                    this.wizard.validationModule.hideFieldError(subscribeBillingTypeDropdownBtn);
                }
                
                // Trigger change event to clear validation errors
                subscribeBillingTypeHiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                
                // Show subscribe period container and update label
                if (subscribePeriodContainer) {
                    subscribePeriodContainer.style.display = 'block';
                }
                if (subscribePeriodLabel) {
                    subscribePeriodLabel.textContent = text;
                }

                // Close dropdown
                const dropdown = bootstrap.Dropdown.getInstance(subscribeBillingTypeDropdownBtn);
                if (dropdown) {
                    dropdown.hide();
                }

                // Trigger End Period calculation
                this.calculateSubscribeEndPeriod();
            }
        });
    }

    /**
     * Initialize Subscribe step listeners
     */
    initializeSubscribeStepListeners() {
        const subscribeStartPeriod = document.getElementById('SubscribeStartPeriod');
        const subscribePeriod = document.getElementById('SubscribePeriod');
        const subscribeBillingType = document.getElementById('SubscribeBillingType');

        // Add event listener for Start Period date change
        if (subscribeStartPeriod) {
            subscribeStartPeriod.addEventListener('change', () => {
                this.calculateSubscribeEndPeriod();
            });
        }

        // Add event listener for Period input
        if (subscribePeriod) {
            subscribePeriod.addEventListener('input', () => {
                this.calculateSubscribeEndPeriod();
                if (this.wizard.updateQuantityFieldBasedOnPeriod) {
                    this.wizard.updateQuantityFieldBasedOnPeriod();
                }
                if (this.wizard.updateQuantityInGridItems) {
                    this.wizard.updateQuantityInGridItems();
                }
            });
            subscribePeriod.addEventListener('change', () => {
                this.calculateSubscribeEndPeriod();
                if (this.wizard.updateQuantityFieldBasedOnPeriod) {
                    this.wizard.updateQuantityFieldBasedOnPeriod();
                }
                if (this.wizard.updateQuantityInGridItems) {
                    this.wizard.updateQuantityInGridItems();
                }
            });
        }

        // Add event listener for Billing Type change
        if (subscribeBillingType) {
            subscribeBillingType.addEventListener('change', () => {
                this.calculateSubscribeEndPeriod();
            });
        }
    }

    /**
     * Calculate Subscribe End Period
     */
    calculateSubscribeEndPeriod() {
        const billingTypeField = document.getElementById('SubscribeBillingType');
        const subscribeStartPeriod = document.getElementById('SubscribeStartPeriod');
        const subscribePeriod = document.getElementById('SubscribePeriod');
        const subscribeEndPeriod = document.getElementById('SubscribeEndPeriod');

        if (!billingTypeField || !subscribeStartPeriod || !subscribePeriod || !subscribeEndPeriod) {
            return;
        }

        const billingTypeID = billingTypeField.value;
        const startDateStr = subscribeStartPeriod.value;
        const periodValue = parseInt(subscribePeriod.value, 10);

        // Clear End Period if required fields are missing
        if (!billingTypeID || !startDateStr || !periodValue || periodValue < 1) {
            subscribeEndPeriod.value = '';
            return;
        }

        // Get billing type from cached data
        const billingType = this.wizard.billingTypes.find(bt => (bt.ID || bt.id) == billingTypeID);
        if (!billingType) {
            subscribeEndPeriod.value = '';
            return;
        }

        // Get TotalMonthPeriod from billing type data
        const monthsPerPeriod = billingType.TotalMonthPeriod || billingType.totalMonthPeriod || 0;
        if (monthsPerPeriod <= 0) {
            subscribeEndPeriod.value = '';
            return;
        }

        // Calculate total months: billing type multiplier * period
        const totalMonths = monthsPerPeriod * periodValue;

        // Calculate End Period: Start Period + total months, then set to last day of that month
        const startDate = new Date(startDateStr);
        const endDate = new Date(startDate);
        
        // Add totalMonths to get to the month after the end month, then subtract 1 month to get end month
        endDate.setMonth(endDate.getMonth() + totalMonths);
        endDate.setMonth(endDate.getMonth() - 1);
        
        // Set to the first day of the next month, then subtract 1 day to get last day of end month
        endDate.setMonth(endDate.getMonth() + 1, 1);
        endDate.setDate(endDate.getDate() - 1);

        // Format as YYYY-MM-DD
        const year = endDate.getFullYear();
        const month = String(endDate.getMonth() + 1).padStart(2, '0');
        const day = String(endDate.getDate()).padStart(2, '0');
        subscribeEndPeriod.value = `${year}-${month}-${day}`;
    }

    /**
     * Update quantity field based on period field visibility
     */
    updateQuantityFieldBasedOnPeriod() {
        const quantityField = document.getElementById('quantity');
        if (!quantityField) return;
        
        const hasPeriod = this.hasPeriodField();
        
        if (hasPeriod) {
            // Disable quantity field
            quantityField.disabled = true;
            quantityField.setAttribute('readonly', 'readonly');
            
            // Get period value from appropriate field
            let periodValue = null;
            
            // Check Billing Type Section (Type ID 6, Sub Type ID 2)
            if (this.wizard.currentPurchaseRequestTypeID === 6 && this.wizard.currentPurchaseRequestSubTypeID === 2) {
                const periodField = document.getElementById('Period');
                if (periodField && periodField.value) {
                    periodValue = parseInt(periodField.value, 10);
                }
            }
            // Check Subscribe Section (Type ID 5 or 7)
            else if (this.wizard.currentPurchaseRequestTypeID === 5 || this.wizard.currentPurchaseRequestTypeID === 7) {
                const subscribePeriodField = document.getElementById('SubscribePeriod');
                if (subscribePeriodField && subscribePeriodField.value) {
                    periodValue = parseInt(subscribePeriodField.value, 10);
                }
            }
            
            // Auto-fill quantity with period value
            if (periodValue && !isNaN(periodValue) && periodValue > 0) {
                if (this.wizard.formatNumberWithComma) {
                    quantityField.value = this.wizard.formatNumberWithComma(periodValue, 3);
                } else {
                    quantityField.value = periodValue;
                }
                
                // Remove validation error when quantity is auto-filled (even if disabled)
                quantityField.classList.remove('is-invalid');
                if (this.wizard.validationModule && this.wizard.validationModule.hideFieldError) {
                    this.wizard.validationModule.hideFieldError(quantityField);
                }
                
                // Trigger amount calculation if unit price is already set
                const unitPriceField = document.getElementById('unitPrice');
                if (unitPriceField && unitPriceField.value) {
                    setTimeout(() => {
                        if (this.wizard.initializeAmountCalculation) {
                            this.wizard.initializeAmountCalculation();
                        }
                    }, 100);
                }
            } else {
                // Clear quantity if period is not set
                quantityField.value = '';
            }
        } else {
            // Enable quantity field
            quantityField.disabled = false;
            quantityField.removeAttribute('readonly');
        }
    }

    /**
     * Check if current Type ID/Sub Type ID shows period field
     */
    hasPeriodField() {
        const typeId = this.wizard.currentPurchaseRequestTypeID;
        const subTypeId = this.wizard.currentPurchaseRequestSubTypeID;
        
        // Billing Type Section: Type ID 6 && Sub Type ID 2
        if (typeId === 6 && subTypeId === 2) {
            return true;
        }
        
        // Subscribe Section: Type ID 5 or 7
        if (typeId === 5 || typeId === 7) {
            return true;
        }
        
        return false;
    }

    /**
     * Initialize Subscribe Year dropdowns
     */
    initializeSubscribeYearDropdowns() {
        const startYearSelect = document.getElementById('SubscribeStartYear');
        const endYearSelect = document.getElementById('SubscribeEndYear');
        
        // Generate years (current year - 5 to current year + 10)
        const currentYear = new Date().getFullYear();
        const years = [];
        for (let i = currentYear - 5; i <= currentYear + 10; i++) {
            years.push(i);
        }
        
        // Populate Start Year dropdown
        if (startYearSelect) {
            startYearSelect.innerHTML = '<option value="">Year</option>';
            years.forEach(year => {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                startYearSelect.appendChild(option);
            });
        }
        
        // Populate End Year dropdown
        if (endYearSelect) {
            endYearSelect.innerHTML = '<option value="">Year</option>';
            years.forEach(year => {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                endYearSelect.appendChild(option);
            });
        }
    }

    /**
     * Update Subscribe Date based on year and month selection
     */
    updateSubscribeDate(type) {
        const yearSelect = document.getElementById(`Subscribe${type.charAt(0).toUpperCase() + type.slice(1)}Year`);
        const monthSelect = document.getElementById(`Subscribe${type.charAt(0).toUpperCase() + type.slice(1)}Month`);
        const dateInput = document.getElementById(`Subscribe${type.charAt(0).toUpperCase() + type.slice(1)}Date`);
        
        if (!yearSelect || !monthSelect || !dateInput) return;
        
        const year = yearSelect.value;
        const month = monthSelect.value;
        
        if (year && month) {
            // Set date to first day of selected month/year
            const date = new Date(parseInt(year), parseInt(month) - 1, 1);
            const yearStr = date.getFullYear();
            const monthStr = String(date.getMonth() + 1).padStart(2, '0');
            const dayStr = String(date.getDate()).padStart(2, '0');
            dateInput.value = `${yearStr}-${monthStr}-${dayStr}`;
        }
    }

    /**
     * Validate Subscribe Date Range
     */
    validateSubscribeDateRange() {
        const subscribeStartDate = document.getElementById('SubscribeStartDate');
        const subscribeEndDate = document.getElementById('SubscribeEndDate');
        const subscribeDateValidationError = document.getElementById('subscribeDateValidationError');

        if (!subscribeStartDate || !subscribeEndDate || !subscribeDateValidationError) {
            return;
        }

        if (!subscribeStartDate.value || !subscribeEndDate.value) {
            subscribeDateValidationError.style.display = 'none';
            subscribeStartDate.classList.remove('is-invalid');
            subscribeEndDate.classList.remove('is-invalid');
            return;
        }

        const startDate = new Date(subscribeStartDate.value);
        const endDate = new Date(subscribeEndDate.value);

        if (startDate > endDate) {
            subscribeDateValidationError.style.display = 'block';
            subscribeStartDate.classList.add('is-invalid');
            subscribeEndDate.classList.add('is-invalid');
        } else {
            subscribeDateValidationError.style.display = 'none';
            subscribeStartDate.classList.remove('is-invalid');
            subscribeEndDate.classList.remove('is-invalid');
        }
    }

    /**
     * Calculate Subscribe Qty based on billing type and date range
     */
    calculateSubscribeQty() {
        const billingTypeField = document.getElementById('SubscribeBillingType');
        const startDateField = document.getElementById('SubscribeStartDate');
        const endDateField = document.getElementById('SubscribeEndDate');
        const calculatedQtyField = document.getElementById('SubscribeCalculatedQty');

        if (!billingTypeField || !startDateField || !endDateField || !calculatedQtyField) {
            return;
        }

        const billingType = billingTypeField.value;
        const startDateStr = startDateField.value;
        const endDateStr = endDateField.value;

        if (!billingType || !startDateStr || !endDateStr) {
            calculatedQtyField.value = '0';
            return;
        }

        const startDate = new Date(startDateStr);
        const endDate = new Date(endDateStr);

        if (startDate > endDate) {
            calculatedQtyField.value = '0';
            return;
        }

        // Calculate months difference (inclusive of both start and end months)
        // Example: Jan 2025 to Dec 2025 = 12 months
        const monthsDiff = (endDate.getFullYear() - startDate.getFullYear()) * 12 + (endDate.getMonth() - startDate.getMonth()) + 1;

        // Calculate quantity based on billing type
        let quantity = 0;
        switch (billingType) {
            case 'Monthly':
                quantity = monthsDiff; // 1 per month, so 12 months = 12
                break;
            case 'Bimonthly':
                quantity = Math.floor(monthsDiff / 2); // 1 per 2 months, so 12 months = 6
                break;
            case 'Quarterly':
                quantity = Math.floor(monthsDiff / 4); // 1 per 4 months, so 12 months = 3
                break;
            case 'Semesterly':
                quantity = Math.floor(monthsDiff / 6); // 1 per 6 months, so 12 months = 2
                break;
            case 'Yearly':
                quantity = Math.floor(monthsDiff / 12); // 1 per 12 months, so 12 months = 1
                break;
            default:
                quantity = 0;
        }

        calculatedQtyField.value = quantity.toString();
    }

    /**
     * Update quantity in all grid view items when Period changes
     */
    updateQuantityInGridItems() {
        // Check if Additional section is visible and has Period field
        const hasPeriod = this.hasPeriodField();
        if (!hasPeriod) {
            return;
        }

        // Get period value from appropriate field
        let periodValue = null;
        
        // Check Billing Type Section (Type ID 6, Sub Type ID 2)
        if (this.wizard.currentPurchaseRequestTypeID === 6 && this.wizard.currentPurchaseRequestSubTypeID === 2) {
            const periodField = document.getElementById('Period');
            if (periodField && periodField.value) {
                periodValue = parseInt(periodField.value, 10);
            }
        }
        // Check Subscribe Section (Type ID 5 or 7)
        else if (this.wizard.currentPurchaseRequestTypeID === 5 || this.wizard.currentPurchaseRequestTypeID === 7) {
            const subscribePeriodField = document.getElementById('SubscribePeriod');
            if (subscribePeriodField && subscribePeriodField.value) {
                periodValue = parseInt(subscribePeriodField.value, 10);
            }
        }

        // If period value is not valid, skip update
        if (!periodValue || isNaN(periodValue) || periodValue <= 0) {
            return;
        }

        // Get all rows in the grid view
        const rows = document.querySelectorAll('#itemDetailTable tbody tr');
        if (rows.length === 0) {
            return;
        }

        let hasUpdated = false;

        // Update each row
        rows.forEach(row => {
            const itemDataAttr = row.getAttribute('data-item-data');
            if (!itemDataAttr) {
                return;
            }

            try {
                const itemData = JSON.parse(itemDataAttr);
                
                // Get current unit price
                const unitPrice = parseFloat(itemData.unitPrice || itemData.UnitPrice) || 0;
                
                // Update quantity with new period value
                const newQuantity = periodValue;
                itemData.itemQty = newQuantity;
                if (itemData.ItemQty !== undefined) {
                    itemData.ItemQty = newQuantity;
                }
                
                // Recalculate amount
                const newAmount = newQuantity * unitPrice;
                itemData.amount = newAmount;
                if (itemData.Amount !== undefined) {
                    itemData.Amount = newAmount;
                }
                
                // Update data attribute
                row.setAttribute('data-item-data', JSON.stringify(itemData));
                
                // Update displayed quantity and amount
                const cells = row.querySelectorAll('td');
                if (cells.length >= 8) {
                    // Update Qty column (index 6)
                    if (this.wizard.formatNumberWithComma) {
                        cells[6].textContent = this.wizard.formatNumberWithComma(newQuantity, 3);
                        // Update Amount column (index 8)
                        cells[8].textContent = this.wizard.formatNumberWithComma(newAmount, 2);
                    } else {
                        cells[6].textContent = newQuantity;
                        cells[8].textContent = newAmount;
                    }
                }
                
                hasUpdated = true;
            } catch (e) {
                console.error('Error updating item quantity in grid:', e);
            }
        });

        // Update total amount if any items were updated
        if (hasUpdated && this.wizard.updateAmountTotal) {
            this.wizard.updateAmountTotal();
        }
    }

    /**
     * Load and fill Additional data from API
     */
    async loadAndFillAdditional(prNumber) {
        if (!prNumber) return;

        // Check if Additional section is required based on Type ID and Sub Type ID
        const typeId = this.wizard.currentPurchaseRequestTypeID;
        const subTypeId = this.wizard.currentPurchaseRequestSubTypeID;
        
        if (!this.wizard.requiresAdditionalSection || !this.wizard.requiresAdditionalSection(typeId, subTypeId)) {
            console.log('Additional Section not required for Type ID:', typeId, 'SubType ID:', subTypeId, '- Skipping API call');
            return;
        }

        try {
            // Call API to get additional data
            const endpoint = `/Procurement/PurchaseRequest/PurchaseRequestAdditional/${encodeURIComponent(prNumber)}`;
            const additionalData = await apiCall('Procurement', endpoint, 'GET');
            const additional = additionalData.data || additionalData;

            if (!additional) {
                console.log('Additional data not found for PR:', prNumber);
                return;
            }

            // Determine which section is visible based on Type ID and Sub Type ID
            const isBillingTypeSection = typeId === 6 && subTypeId === 2;
            const isSonumbSection = (typeId === 8 && subTypeId === 4) ||
                                   (typeId === 2 && (subTypeId === 1 || subTypeId === 3)) ||
                                   (typeId === 4 && subTypeId === 3) ||
                                   (typeId === 3 && (subTypeId === 4 || subTypeId === 5));
            const isSubscribeSection = typeId === 5 || typeId === 7;

            // Fill Billing Type section (Type ID 6, Sub Type ID 2)
            if (isBillingTypeSection) {
                // Fill Billing Type
                const billingTypeField = document.getElementById('BillingType');
                const billingTypeSelectedText = document.getElementById('billingTypeSelectedText');
                const billingPeriodContainer = document.getElementById('billingPeriodContainer');
                const billingPeriodLabel = document.getElementById('billingPeriodLabel');
                
                if (billingTypeField && (additional.billingTypeID || additional.BillingTypeID)) {
                    const billingTypeId = additional.billingTypeID || additional.BillingTypeID;
                    if (billingTypeId && this.wizard.billingTypes && this.wizard.billingTypes.length > 0) {
                        const billingType = this.wizard.billingTypes.find(bt => 
                            (bt.ID || bt.id) == billingTypeId
                        );
                        if (billingType) {
                            billingTypeField.value = billingTypeId;
                            const billingTypeName = billingType.Name || billingType.name || '';
                            if (billingTypeSelectedText) {
                                billingTypeSelectedText.textContent = billingTypeName;
                            }
                            
                            // Show billing period container and update label
                            if (billingPeriodContainer) {
                                billingPeriodContainer.style.display = 'block';
                            }
                            if (billingPeriodLabel) {
                                billingPeriodLabel.textContent = billingTypeName;
                            }
                            
                            // Trigger change to update period container visibility
                            billingTypeField.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }
                }

                // Fill Start Period
                const startPeriodField = document.getElementById('StartPeriod');
                if (startPeriodField && (additional.startPeriod || additional.StartPeriod)) {
                    const startPeriod = additional.startPeriod || additional.StartPeriod;
                    if (startPeriod) {
                        // Format date if needed (handle different date formats)
                        let formattedDate = startPeriod;
                        if (typeof startPeriod === 'string') {
                            // If it's a date string, extract date part only
                            formattedDate = startPeriod.split('T')[0];
                        } else if (startPeriod instanceof Date) {
                            formattedDate = startPeriod.toISOString().split('T')[0];
                        } else if (startPeriod && typeof startPeriod === 'object' && startPeriod.toString) {
                            // Handle date-like objects
                            formattedDate = new Date(startPeriod).toISOString().split('T')[0];
                        }
                        startPeriodField.value = formattedDate;
                    }
                }

                // Fill Period
                const periodField = document.getElementById('Period');
                if (periodField && (additional.period || additional.Period)) {
                    periodField.value = additional.period || additional.Period || '';
                }

                // End Period is auto-calculated, but trigger calculation if needed
                if (startPeriodField && periodField && billingTypeField && billingTypeField.value) {
                    // Wait a bit for billing type change to process, then trigger calculation
                    setTimeout(() => {
                        if (this.wizard.calculateEndPeriod) {
                            this.wizard.calculateEndPeriod();
                        } else if (startPeriodField.value && periodField.value) {
                            // Fallback: trigger change events to trigger calculation
                            startPeriodField.dispatchEvent(new Event('change', { bubbles: true }));
                            periodField.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }, 100);
                }
            }

            // Fill Sonumb section
            if (isSonumbSection) {
                // Fill Sonumb
                const sonumbField = document.getElementById('Sonumb');
                const sonumbIdField = document.getElementById('SonumbId');
                const siteNameField = document.getElementById('SiteName');
                const siteIdField = document.getElementById('SiteID');
                const searchSTIPSonumbBtn = document.getElementById('searchSTIPSonumbBtn');

                if (sonumbField && (additional.sonumb || additional.Sonumb)) {
                    sonumbField.value = additional.sonumb || additional.Sonumb || '';
                }
                if (sonumbIdField && (additional.sonumbId || additional.SonumbId || additional.sonumbID || additional.SonumbID)) {
                    sonumbIdField.value = additional.sonumbId || additional.SonumbId || additional.sonumbID || additional.SonumbID || '';
                }
                if (siteNameField && (additional.siteName || additional.SiteName)) {
                    siteNameField.value = additional.siteName || additional.SiteName || '';
                }
                if (siteIdField && (additional.siteID || additional.SiteID || additional.siteId || additional.SiteId)) {
                    siteIdField.value = additional.siteID || additional.SiteID || additional.siteId || additional.SiteId || '';
                }
                
                // Check if this is revised PR (status 5) and handle Sonumb field enable/disable
                // If SonumbId is null/empty, enable fields for revision
                // If SonumbId is not null, disable fields
                const sonumbIdValue = additional.sonumbId || additional.SonumbId || additional.sonumbID || additional.SonumbID || null;
                const isSonumbIdEmpty = !sonumbIdValue || sonumbIdValue.toString().trim() === '' || sonumbIdValue === '0' || sonumbIdValue === 0;
                
                // Check if PR status is 5 (Revised/Rejected)
                const statusID = this.wizard.currentApprovalStatusID || null;
                const isRevised = statusID === 5;
                
                if (isRevised) {
                    // If SonumbId is null/empty, enable fields for revision
                    // If SonumbId is not null, disable fields
                    if (sonumbField) {
                        sonumbField.disabled = !isSonumbIdEmpty;
                        if (isSonumbIdEmpty) {
                            sonumbField.removeAttribute('readonly');
                        } else {
                            sonumbField.setAttribute('readonly', 'readonly');
                        }
                    }
                    if (sonumbIdField) {
                        sonumbIdField.disabled = !isSonumbIdEmpty;
                    }
                    if (searchSTIPSonumbBtn) {
                        searchSTIPSonumbBtn.disabled = !isSonumbIdEmpty;
                    }
                    if (siteNameField) {
                        siteNameField.disabled = !isSonumbIdEmpty;
                        if (isSonumbIdEmpty) {
                            siteNameField.removeAttribute('readonly');
                        } else {
                            siteNameField.setAttribute('readonly', 'readonly');
                        }
                    }
                    if (siteIdField) {
                        siteIdField.disabled = !isSonumbIdEmpty;
                    }
                }
            }

            // Fill Subscribe section (Type ID 5 or 7)
            // Note: Subscribe section uses the same fields as Billing Type section (BillingTypeID, StartPeriod, Period, EndPeriod, Sonumb, SonumbId, SiteName, SiteID)
            // but displays them in Subscribe fields
            if (isSubscribeSection) {
                // Fill Subscribe Sonumb (uses Sonumb field from API)
                const subscribeSonumbField = document.getElementById('SubscribeSonumb');
                const subscribeSonumbIdField = document.getElementById('SubscribeSonumbId');
                const subscribeSiteNameField = document.getElementById('SubscribeSiteName');
                const subscribeSiteIdField = document.getElementById('SubscribeSiteID');
                const searchSubscribeSonumbBtn = document.getElementById('searchSubscribeSonumbBtn');

                if (subscribeSonumbField && (additional.sonumb || additional.Sonumb)) {
                    subscribeSonumbField.value = additional.sonumb || additional.Sonumb || '';
                }
                if (subscribeSonumbIdField && (additional.sonumbId || additional.SonumbId || additional.sonumbID || additional.SonumbID)) {
                    subscribeSonumbIdField.value = additional.sonumbId || additional.SonumbId || additional.sonumbID || additional.SonumbID || '';
                }
                if (subscribeSiteNameField && (additional.siteName || additional.SiteName)) {
                    subscribeSiteNameField.value = additional.siteName || additional.SiteName || '';
                }
                if (subscribeSiteIdField && (additional.siteID || additional.SiteID || additional.siteId || additional.SiteId)) {
                    subscribeSiteIdField.value = additional.siteID || additional.SiteID || additional.siteId || additional.SiteId || '';
                }
                
                // Check if this is revised PR (status 5) and handle SubscribeSonumb field enable/disable
                // If SubscribeSonumbId is null/empty, enable fields for revision
                // If SubscribeSonumbId is not null, disable fields
                const subscribeSonumbIdValue = additional.sonumbId || additional.SonumbId || additional.sonumbID || additional.SonumbID || null;
                const isSubscribeSonumbIdEmpty = !subscribeSonumbIdValue || subscribeSonumbIdValue.toString().trim() === '' || subscribeSonumbIdValue === '0' || subscribeSonumbIdValue === 0;
                
                // Check if PR status is 5 (Revised/Rejected)
                const statusID = this.wizard.currentApprovalStatusID || null;
                const isRevised = statusID === 5;
                
                if (isRevised) {
                    // If SubscribeSonumbId is null/empty, enable fields for revision
                    // If SubscribeSonumbId is not null, disable fields
                    if (subscribeSonumbField) {
                        subscribeSonumbField.disabled = !isSubscribeSonumbIdEmpty;
                        if (isSubscribeSonumbIdEmpty) {
                            subscribeSonumbField.removeAttribute('readonly');
                        } else {
                            subscribeSonumbField.setAttribute('readonly', 'readonly');
                        }
                    }
                    if (subscribeSonumbIdField) {
                        subscribeSonumbIdField.disabled = !isSubscribeSonumbIdEmpty;
                    }
                    if (searchSubscribeSonumbBtn) {
                        searchSubscribeSonumbBtn.disabled = !isSubscribeSonumbIdEmpty;
                    }
                    if (subscribeSiteNameField) {
                        subscribeSiteNameField.disabled = !isSubscribeSonumbIdEmpty;
                        if (isSubscribeSonumbIdEmpty) {
                            subscribeSiteNameField.removeAttribute('readonly');
                        } else {
                            subscribeSiteNameField.setAttribute('readonly', 'readonly');
                        }
                    }
                    if (subscribeSiteIdField) {
                        subscribeSiteIdField.disabled = !isSubscribeSonumbIdEmpty;
                    }
                }

                // Fill Subscribe Billing Type (uses BillingTypeID field from API)
                const subscribeBillingTypeField = document.getElementById('SubscribeBillingType');
                const subscribeBillingTypeSelectedText = document.getElementById('subscribeBillingTypeSelectedText');
                const subscribePeriodContainer = document.getElementById('subscribePeriodContainer');
                const subscribePeriodLabel = document.getElementById('subscribePeriodLabel');
                
                if (subscribeBillingTypeField && (additional.billingTypeID || additional.BillingTypeID)) {
                    const subscribeBillingTypeId = additional.billingTypeID || additional.BillingTypeID;
                    if (subscribeBillingTypeId && this.wizard.billingTypes && this.wizard.billingTypes.length > 0) {
                        const billingType = this.wizard.billingTypes.find(bt => 
                            (bt.ID || bt.id) == subscribeBillingTypeId
                        );
                        if (billingType) {
                            subscribeBillingTypeField.value = subscribeBillingTypeId;
                            const billingTypeName = billingType.Name || billingType.name || '';
                            if (subscribeBillingTypeSelectedText) {
                                subscribeBillingTypeSelectedText.textContent = billingTypeName;
                            }
                            
                            // Show subscribe period container and update label
                            if (subscribePeriodContainer) {
                                subscribePeriodContainer.style.display = 'block';
                            }
                            if (subscribePeriodLabel) {
                                subscribePeriodLabel.textContent = billingTypeName;
                            }
                            
                            // Trigger change to update period container visibility
                            subscribeBillingTypeField.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }
                }

                // Fill Subscribe Start Period (uses StartPeriod field from API)
                const subscribeStartPeriodField = document.getElementById('SubscribeStartPeriod');
                if (subscribeStartPeriodField && (additional.startPeriod || additional.StartPeriod)) {
                    const subscribeStartPeriod = additional.startPeriod || additional.StartPeriod;
                    if (subscribeStartPeriod) {
                        // Format date if needed
                        let formattedDate = subscribeStartPeriod;
                        if (typeof subscribeStartPeriod === 'string') {
                            formattedDate = subscribeStartPeriod.split('T')[0];
                        } else if (subscribeStartPeriod instanceof Date) {
                            formattedDate = subscribeStartPeriod.toISOString().split('T')[0];
                        } else if (subscribeStartPeriod && typeof subscribeStartPeriod === 'object' && subscribeStartPeriod.toString) {
                            formattedDate = new Date(subscribeStartPeriod).toISOString().split('T')[0];
                        }
                        subscribeStartPeriodField.value = formattedDate;
                    }
                }

                // Fill Subscribe Period (uses Period field from API)
                const subscribePeriodField = document.getElementById('SubscribePeriod');
                if (subscribePeriodField && (additional.period || additional.Period)) {
                    subscribePeriodField.value = additional.period || additional.Period || '';
                }

                // Subscribe End Period is auto-calculated, but trigger calculation if needed
                if (subscribeStartPeriodField && subscribePeriodField && subscribeBillingTypeField && subscribeBillingTypeField.value) {
                    // Wait a bit for billing type change to process, then trigger calculation
                    setTimeout(() => {
                        if (this.wizard.calculateSubscribeEndPeriod) {
                            this.wizard.calculateSubscribeEndPeriod();
                        } else if (subscribeStartPeriodField.value && subscribePeriodField.value) {
                            // Fallback: trigger change events to trigger calculation
                            subscribeStartPeriodField.dispatchEvent(new Event('change', { bubbles: true }));
                            subscribePeriodField.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }, 100);
                }
            }

        } catch (error) {
            // If 404 error or "not found" message, it's okay - additional data doesn't exist for this PR
            const errorMessage = error.message || error.toString() || '';
            if (errorMessage.includes('not found') || errorMessage.includes('404') || 
                (error.statusCode === 404) || (error.response && error.response.status === 404)) {
                console.log('Additional data not found for PR:', prNumber, '- This is expected if PR does not have Additional data yet');
            } else {
                console.error('Error loading additional data:', error);
            }
        }
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.ProcurementWizardAdditional = ProcurementWizardAdditional;
}

