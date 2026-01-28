/**
 * Procurement Wizard Basic Information Module
 * Handles Basic Information step validation, form filling, and readonly state
 */

class ProcurementWizardBasicInfo {
    constructor(wizardInstance) {
        this.wizard = wizardInstance;
    }

    /**
     * Validate Basic Information step
     */
    validateBasicInformationStep() {
        const stepContent = this.wizard.stepContents['basic-information'];
        if (!stepContent) return true;

        // Clear previous validation errors
        if (this.wizard.validationModule && this.wizard.validationModule.clearAllFieldErrors) {
            this.wizard.validationModule.clearAllFieldErrors(stepContent);
        }

        const requiredFields = stepContent.querySelectorAll('[required]');
        let isValid = true;
        let firstInvalidField = null;

        // Field labels mapping for error messages
        const fieldLabels = {
            'Applicant': 'Applicant',
            'Company': 'Company',
            'PurchaseRequestType': 'Purchase Request Type',
            'PurchaseRequestSubType': 'Purchase Request Sub Type',
            'PurchaseRequestName': 'Purchase Request Name',
            'Remarks': 'Remarks'
        };

        // Validate regular required fields
        requiredFields.forEach(field => {
            const fieldValue = field.value || '';
            const isEmpty = !fieldValue.trim();
            
            // Only show validation error if field is empty AND not disabled
            if (isEmpty && !field.disabled) {
                field.classList.add('is-invalid');
                const fieldName = field.id || field.name || '';
                const fieldLabel = fieldLabels[fieldName] || fieldName;
                const errorMessage = `${fieldLabel} is required`;
                
                // Show error message below field
                if (this.wizard.validationModule && this.wizard.validationModule.showFieldError) {
                    this.wizard.validationModule.showFieldError(field, errorMessage);
                }
                
                isValid = false;
                if (!firstInvalidField) {
                    firstInvalidField = field;
                }
            } else {
                field.classList.remove('is-invalid');
                // Hide error message if field is valid
                if (this.wizard.validationModule && this.wizard.validationModule.hideFieldError) {
                    this.wizard.validationModule.hideFieldError(field);
                }
            }
        });

        // Validate custom dropdowns (hidden inputs with dropdown buttons)
        const customDropdowns = [
            { inputId: '#Applicant', btnId: '#applicantDropdownBtn', label: 'Applicant' },
            { inputId: '#Company', btnId: '#companyDropdownBtn', label: 'Company' },
            { inputId: '#PurchaseRequestType', btnId: '#purchaseRequestTypeDropdownBtn', label: 'Purchase Request Type' },
            { inputId: '#PurchaseRequestSubType', btnId: '#purchaseRequestSubTypeDropdownBtn', label: 'Purchase Request Sub Type' }
        ];

        customDropdowns.forEach(({ inputId, btnId, label }) => {
            const hiddenInput = stepContent.querySelector(inputId);
            const dropdownBtn = stepContent.querySelector(btnId);
            if (hiddenInput && dropdownBtn) {
                // Check if field is required (either has required attribute or is in required list)
                const isRequired = hiddenInput.hasAttribute('required') || 
                                 Array.from(requiredFields).some(f => f.id === hiddenInput.id);
                
                if (isRequired) {
                    const isEmpty = !hiddenInput.value || hiddenInput.value.trim() === '';
                    if (isEmpty) {
                        dropdownBtn.classList.add('is-invalid');
                        const errorMessage = `${label} is required`;
                        
                        // Show error message below dropdown button
                        if (this.wizard.validationModule && this.wizard.validationModule.showFieldError) {
                            this.wizard.validationModule.showFieldError(dropdownBtn, errorMessage);
                        }
                        
                        isValid = false;
                        if (!firstInvalidField) {
                            firstInvalidField = dropdownBtn;
                        }
                    } else {
                        dropdownBtn.classList.remove('is-invalid');
                        // Hide error message if field is valid
                        if (this.wizard.validationModule && this.wizard.validationModule.hideFieldError) {
                            this.wizard.validationModule.hideFieldError(dropdownBtn);
                        }
                    }
                }
            }
        });

        if (!isValid) {
            // Focus on first invalid field
            if (this.wizard.focusFirstInvalidField) {
                this.wizard.focusFirstInvalidField(stepContent);
            }
        }

        return isValid;
    }

    /**
     * Fill Basic Information form from PR data
     */
    async fillBasicInformationForm(pr) {
        // Fill Basic Information form fields
        const requestorField = document.getElementById('Requestor');
        const applicantField = document.getElementById('Applicant');
        const companyField = document.getElementById('Company');
        const purchReqTypeField = document.getElementById('PurchaseRequestType');
        const purchReqSubTypeField = document.getElementById('PurchaseRequestSubType');
        const purchReqNameField = document.getElementById('PurchaseRequestName');
        const remarksField = document.getElementById('Remarks');
        
        // Set Requestor field - always display name (from config), not employee ID
        if (requestorField) {
            const currentUserFullName = (window.ProcurementConfig && window.ProcurementConfig.currentUserFullName) || '';
            requestorField.value = currentUserFullName || '';
        }
        
        // Set simple text fields immediately
        if (purchReqNameField) purchReqNameField.value = pr.purchReqName || pr.PurchReqName || '';
        if (remarksField) remarksField.value = pr.remark || pr.Remark || '';
        
        // Wait for dropdowns to be populated before setting values
        await new Promise(resolve => {
            const checkApplicant = () => {
                if (this.wizard.allApplicants && this.wizard.allApplicants.length > 0) {
                    const applicantValue = pr.applicant || pr.Applicant || '';
                    if (applicantValue && applicantField) {
                        const applicant = this.wizard.allApplicants.find(a => a.value === applicantValue || a.text === applicantValue);
                        if (applicant) {
                            applicantField.value = applicant.value;
                            const applicantSelectedText = document.getElementById('applicantSelectedText');
                            if (applicantSelectedText) {
                                applicantSelectedText.textContent = applicant.text;
                            }
                            applicantField.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }
                    resolve();
                } else {
                    setTimeout(checkApplicant, 100);
                }
            };
            checkApplicant();
        });
        
        await new Promise(resolve => {
            const checkCompany = () => {
                if (this.wizard.allCompanies && this.wizard.allCompanies.length > 0) {
                    const companyValue = pr.company || pr.Company || '';
                    if (companyValue && companyField) {
                        const company = this.wizard.allCompanies.find(c => 
                            (c.CompanyID || c.companyID) == companyValue || 
                            (c.Company || c.company) === companyValue
                        );
                        if (company) {
                            const companyId = company.CompanyID || company.companyID;
                            const companyText = company.Company || company.company;
                            companyField.value = companyId;
                            const companySelectedText = document.getElementById('companySelectedText');
                            if (companySelectedText) {
                                companySelectedText.textContent = companyText;
                            }
                            companyField.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }
                    resolve();
                } else {
                    setTimeout(checkCompany, 100);
                }
            };
            checkCompany();
        });
        
        // Wait for Purchase Request Type dropdown to be populated
        await new Promise(resolve => {
            const checkType = async () => {
                if (this.wizard.allPurchaseTypes && this.wizard.allPurchaseTypes.length > 0) {
                    const purchReqTypeValue = pr.purchReqType || pr.PurchReqType || '';
                    if (purchReqTypeValue && purchReqTypeField) {
                        // Try to find by matching the display text first, then by ID
                        let type = null;
                        
                        type = this.wizard.allPurchaseTypes.find(t => {
                            const typeValue = t.PurchaseRequestType || t.purchaseRequestType || '';
                            const category = t.Category || t.category || '';
                            const formattedDisplay = category && typeValue !== category 
                                ? `${typeValue} ${category}` 
                                : typeValue;
                            return typeValue === purchReqTypeValue || formattedDisplay === purchReqTypeValue;
                        });
                        
                        // If not found, try to parse as ID
                        if (!type) {
                            const typeIdInt = parseInt(purchReqTypeValue.trim(), 10);
                            if (!isNaN(typeIdInt) && typeIdInt > 0) {
                                type = this.wizard.allPurchaseTypes.find(t => 
                                    parseInt(t.ID || t.id || '0', 10) === typeIdInt
                                );
                            }
                        }
                        
                        if (type) {
                            const typeValue = type.PurchaseRequestType || type.purchaseRequestType || '';
                            const category = type.Category || type.category || '';
                            let displayText = typeValue;
                            if (category && typeValue !== category) {
                                displayText = `${typeValue} ${category}`;
                            }
                            const typeId = type.ID || type.id || '';
                            purchReqTypeField.value = typeId.toString();
                            
                            const categoryHiddenInput = document.getElementById('PurchaseRequestTypeCategory');
                            if (categoryHiddenInput) {
                                categoryHiddenInput.value = category || '';
                            }
                            
                            const typeSelectedText = document.getElementById('purchaseRequestTypeSelectedText');
                            if (typeSelectedText) {
                                typeSelectedText.textContent = displayText;
                            }
                            purchReqTypeField.dispatchEvent(new Event('change', { bubbles: true }));
                            
                            this.wizard.currentPurchaseRequestTypeID = parseInt(typeId, 10);
                            this.wizard.currentPurchaseRequestSubTypeID = null;
                            if (this.wizard.checkAndToggleAdditionalStep) {
                                this.wizard.checkAndToggleAdditionalStep();
                            }
                            
                            // Load sub-types for this type
                            if (typeId && this.wizard.loadPurchaseSubTypesFromApi) {
                                await this.wizard.loadPurchaseSubTypesFromApi(parseInt(typeId, 10));
                                // Wait for sub-types to load
                                await new Promise(subResolve => {
                                    const checkSubType = () => {
                                        if (this.wizard.allPurchaseSubTypes && this.wizard.allPurchaseSubTypes.length > 0) {
                                            const purchReqSubTypeValue = pr.purchReqSubType || pr.PurchReqSubType || '';
                                            if (purchReqSubTypeValue && purchReqSubTypeField) {
                                                let subType = null;
                                                
                                                subType = this.wizard.allPurchaseSubTypes.find(st => 
                                                    (st.PurchaseRequestSubType || st.purchaseRequestSubType) === purchReqSubTypeValue
                                                );
                                                
                                                if (!subType) {
                                                    const subTypeIdInt = parseInt(purchReqSubTypeValue.trim(), 10);
                                                    if (!isNaN(subTypeIdInt) && subTypeIdInt > 0) {
                                                        subType = this.wizard.allPurchaseSubTypes.find(st => 
                                                            parseInt(st.ID || st.id || '0', 10) === subTypeIdInt
                                                        );
                                                    }
                                                }
                                                
                                                if (subType) {
                                                    const subTypeValue = subType.PurchaseRequestSubType || subType.purchaseRequestSubType || '';
                                                    const subTypeId = subType.ID || subType.id || '';
                                                    purchReqSubTypeField.value = subTypeId.toString();
                                                    const subTypeSelectedText = document.getElementById('purchaseRequestSubTypeSelectedText');
                                                    if (subTypeSelectedText) {
                                                        subTypeSelectedText.textContent = subTypeValue;
                                                    }
                                                    this.wizard.currentPurchaseRequestSubTypeID = parseInt(subTypeId, 10);
                                                    purchReqSubTypeField.dispatchEvent(new Event('change', { bubbles: true }));
                                                    
                                                    if (this.wizard.checkAndToggleAdditionalStep) {
                                                        this.wizard.checkAndToggleAdditionalStep();
                                                    }
                                                }
                                            }
                                            subResolve();
                                        } else {
                                            setTimeout(checkSubType, 100);
                                        }
                                    };
                                    checkSubType();
                                });
                            }
                        }
                    }
                    resolve();
                } else {
                    setTimeout(checkType, 100);
                }
            };
            checkType();
        });
    }

    /**
     * Set all Basic Information fields to readonly/disabled (for status 5 - Rejected)
     */
    setBasicInformationReadonly() {
        // Disable Requestor field
        const requestorField = document.getElementById('Requestor');
        if (requestorField) {
            requestorField.disabled = true;
        }
        
        // Disable Applicant dropdown
        const applicantDropdownBtn = document.getElementById('applicantDropdownBtn');
        if (applicantDropdownBtn) {
            applicantDropdownBtn.disabled = true;
        }
        const applicantHiddenInput = document.getElementById('Applicant');
        if (applicantHiddenInput) {
            applicantHiddenInput.disabled = true;
        }
        
        // Disable Company dropdown
        const companyDropdownBtn = document.getElementById('companyDropdownBtn');
        if (companyDropdownBtn) {
            companyDropdownBtn.disabled = true;
        }
        const companyHiddenInput = document.getElementById('Company');
        if (companyHiddenInput) {
            companyHiddenInput.disabled = true;
        }
        
        // Disable Purchase Request Type dropdown
        const purchaseRequestTypeDropdownBtn = document.getElementById('purchaseRequestTypeDropdownBtn');
        if (purchaseRequestTypeDropdownBtn) {
            purchaseRequestTypeDropdownBtn.disabled = true;
        }
        const purchaseRequestTypeHiddenInput = document.getElementById('PurchaseRequestType');
        if (purchaseRequestTypeHiddenInput) {
            purchaseRequestTypeHiddenInput.disabled = true;
        }
        
        // Disable Purchase Request Sub Type dropdown
        const purchaseRequestSubTypeDropdownBtn = document.getElementById('purchaseRequestSubTypeDropdownBtn');
        if (purchaseRequestSubTypeDropdownBtn) {
            purchaseRequestSubTypeDropdownBtn.disabled = true;
        }
        const purchaseRequestSubTypeHiddenInput = document.getElementById('PurchaseRequestSubType');
        if (purchaseRequestSubTypeHiddenInput) {
            purchaseRequestSubTypeHiddenInput.disabled = true;
        }
        
        // Ensure PurchaseRequestName remains editable
        const purchReqNameField = document.getElementById('PurchaseRequestName');
        if (purchReqNameField) {
            purchReqNameField.disabled = false;
        }
        
        // Ensure Remarks remains editable
        const remarksField = document.getElementById('Remarks');
        if (remarksField) {
            remarksField.disabled = false;
        }
    }

    /**
     * Helper function to check if Type ID and SubType ID require Additional Section
     */
    requiresAdditionalSection(typeId, subTypeId) {
        if (!typeId) return false;
        
        // Type ID 5 or 7: Subscribe section (no Sub Type required)
        if (typeId === 5 || typeId === 7) return true;
        
        // Type ID 6 && Sub Type ID 2: Billing Type section
        if (typeId === 6 && subTypeId === 2) return true;
        
        // Sonumb Section conditions
        if (typeId === 8 && subTypeId === 4) return true;
        if (typeId === 2 && (subTypeId === 1 || subTypeId === 3)) return true;
        if (typeId === 4 && subTypeId === 3) return true;
        if (typeId === 3 && (subTypeId === 4 || subTypeId === 5)) return true;
        
        return false;
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.ProcurementWizardBasicInfo = ProcurementWizardBasicInfo;
}

