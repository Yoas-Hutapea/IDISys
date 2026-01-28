/**
 * Procurement Wizard Validation Module
 * Handles validation for all wizard steps
 */

class ProcurementWizardValidation {
    constructor(wizardInstance) {
        this.wizard = wizardInstance;
    }

    /**
     * Show error message below a field
     */
    showFieldError(fieldElement, errorMessage) {
        if (!fieldElement || !errorMessage) return;

        // Remove existing error message for this field
        this.hideFieldError(fieldElement);

        // Create error message element
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback d-block';
        errorDiv.textContent = errorMessage;
        errorDiv.setAttribute('data-field-error', 'true');

        // Find the parent container (usually .col-12 or form-group)
        let parent = fieldElement.closest('.col-12') || fieldElement.closest('.form-group') || fieldElement.parentElement;
        
        // For dropdown buttons, find the parent container
        if (fieldElement.classList.contains('btn') || fieldElement.id && fieldElement.id.includes('DropdownBtn')) {
            parent = fieldElement.closest('.col-12') || fieldElement.parentElement;
        }

        if (parent) {
            // Insert error message after the field or its container
            parent.appendChild(errorDiv);
        }
    }

    /**
     * Hide error message below a field
     */
    hideFieldError(fieldElement) {
        if (!fieldElement) return;

        // Find parent container
        let parent = fieldElement.closest('.col-12') || fieldElement.closest('.form-group') || fieldElement.parentElement;
        
        if (fieldElement.classList.contains('btn') || (fieldElement.id && fieldElement.id.includes('DropdownBtn'))) {
            parent = fieldElement.closest('.col-12') || fieldElement.parentElement;
        }

        if (parent) {
            // Remove existing error messages
            const existingErrors = parent.querySelectorAll('[data-field-error="true"]');
            existingErrors.forEach(error => error.remove());
        }
    }

    /**
     * Clear all field errors in a step
     */
    clearAllFieldErrors(stepContent) {
        if (!stepContent) return;
        
        // Remove all error messages
        const allErrors = stepContent.querySelectorAll('[data-field-error="true"]');
        allErrors.forEach(error => error.remove());
        
        // Remove is-invalid class from all fields
        const invalidFields = stepContent.querySelectorAll('.is-invalid');
        invalidFields.forEach(field => field.classList.remove('is-invalid'));
    }

    /**
     * Validate Approval step
     * Note: Reviewed by, Approved by, and Confirmed by are not validated here.
     * Validation will be done in Summary step (validateAllWizardSteps) before submission.
     */
    validateApprovalStep() {
        // Approval step fields (Reviewed by, Approved by, Confirmed by) are optional during navigation
        // Validation will be enforced in Summary step before submission
        return true;
    }

    /**
     * Validate all wizard steps
     */
    validateAllWizardSteps() {
        // Clear all previous field errors first
        const allSteps = ['basic-information', 'additional', 'assign-approval', 'detail', 'document'];
        allSteps.forEach(stepId => {
            const stepContent = document.getElementById(stepId);
            if (stepContent) {
                this.clearAllFieldErrors(stepContent);
            }
        });

        const errors = [];
        const firstInvalidField = { step: null, field: null, fieldId: null };
        
        // Step 1: Validate Basic Information
        const applicantField = document.getElementById('Applicant');
        const companyField = document.getElementById('Company');
        const purchReqTypeField = document.getElementById('PurchaseRequestType');
        const purchReqSubTypeField = document.getElementById('PurchaseRequestSubType');
        const purchReqNameField = document.getElementById('PurchaseRequestName');
        const remarksField = document.getElementById('Remarks');
        
        const getFieldValue = (field) => {
            if (!field) return '';
            if (field.type === 'hidden') {
                return field.value || '';
            }
            if (field.tagName === 'SELECT') {
                const value = field.value || '';
                const selectedOption = field.options[field.selectedIndex];
                if (selectedOption && selectedOption.disabled && value === '') {
                    return '';
                }
                return value;
            }
            return field.value || '';
        };
        
        const applicantValue = getFieldValue(applicantField);
        const companyValue = getFieldValue(companyField);
        const purchReqTypeValue = getFieldValue(purchReqTypeField);
        const purchReqSubTypeValue = getFieldValue(purchReqSubTypeField);
        const purchReqNameValue = getFieldValue(purchReqNameField);
        const remarksValue = getFieldValue(remarksField);
        
        if (!applicantValue || applicantValue.trim() === '') {
            errors.push('Basic Information: Applicant is required');
            const applicantDropdownBtn = document.getElementById('applicantDropdownBtn');
            if (applicantDropdownBtn) {
                applicantDropdownBtn.classList.add('is-invalid');
                this.showFieldError(applicantDropdownBtn, 'Applicant is required');
            }
            if (!firstInvalidField.step) {
                firstInvalidField.step = 'basic-information';
                firstInvalidField.field = applicantDropdownBtn || applicantField;
                firstInvalidField.fieldId = 'Applicant';
            }
        }
        if (!companyValue || companyValue.trim() === '') {
            errors.push('Basic Information: Company is required');
            const companyDropdownBtn = document.getElementById('companyDropdownBtn');
            if (companyDropdownBtn) {
                companyDropdownBtn.classList.add('is-invalid');
                this.showFieldError(companyDropdownBtn, 'Company is required');
            }
            if (!firstInvalidField.step) {
                firstInvalidField.step = 'basic-information';
                firstInvalidField.field = companyDropdownBtn || companyField;
                firstInvalidField.fieldId = 'Company';
            }
        }
        if (!purchReqTypeValue || purchReqTypeValue.trim() === '') {
            errors.push('Basic Information: Purchase Request Type is required');
            const purchReqTypeDropdownBtn = document.getElementById('purchaseRequestTypeDropdownBtn');
            if (purchReqTypeDropdownBtn) {
                purchReqTypeDropdownBtn.classList.add('is-invalid');
                this.showFieldError(purchReqTypeDropdownBtn, 'Purchase Request Type is required');
            }
            if (!firstInvalidField.step) {
                firstInvalidField.step = 'basic-information';
                firstInvalidField.field = purchReqTypeDropdownBtn || purchReqTypeField;
                firstInvalidField.fieldId = 'PurchaseRequestType';
            }
        }
        if (!purchReqSubTypeValue || purchReqSubTypeValue.trim() === '') {
            errors.push('Basic Information: Purchase Request Sub Type is required');
            const purchReqSubTypeDropdownBtn = document.getElementById('purchaseRequestSubTypeDropdownBtn');
            if (purchReqSubTypeDropdownBtn) {
                purchReqSubTypeDropdownBtn.classList.add('is-invalid');
                this.showFieldError(purchReqSubTypeDropdownBtn, 'Purchase Request Sub Type is required');
            }
            if (!firstInvalidField.step) {
                firstInvalidField.step = 'basic-information';
                firstInvalidField.field = purchReqSubTypeDropdownBtn || purchReqSubTypeField;
                firstInvalidField.fieldId = 'PurchaseRequestSubType';
            }
        }
        if (!purchReqNameValue || purchReqNameValue.trim() === '') {
            errors.push('Basic Information: Purchase Request Name is required');
            if (purchReqNameField) {
                purchReqNameField.classList.add('is-invalid');
                this.showFieldError(purchReqNameField, 'Purchase Request Name is required');
            }
            if (!firstInvalidField.step) {
                firstInvalidField.step = 'basic-information';
                firstInvalidField.field = purchReqNameField;
                firstInvalidField.fieldId = 'PurchaseRequestName';
            }
        }
        if (!remarksValue || remarksValue.trim() === '') {
            errors.push('Basic Information: Remarks is required');
            if (remarksField) {
                remarksField.classList.add('is-invalid');
                this.showFieldError(remarksField, 'Remarks is required');
            }
            if (!firstInvalidField.step) {
                firstInvalidField.step = 'basic-information';
                firstInvalidField.field = remarksField;
                firstInvalidField.fieldId = 'Remarks';
            }
        }
        
        // Step 2: Validate Detail (Item Purchase Request)
        const itemRows = document.querySelectorAll('#itemDetailTable tbody tr');
        if (itemRows.length === 0) {
            errors.push('Item Purchase Request: At least one item is required');
            if (!firstInvalidField.step) {
                firstInvalidField.step = 'detail';
                firstInvalidField.field = null;
                firstInvalidField.fieldId = 'addDetailBtn';
            }
        } else {
            itemRows.forEach((row, index) => {
                const itemDataAttr = row.getAttribute('data-item-data');
                let itemData = null;
                if (itemDataAttr) {
                    try {
                        itemData = JSON.parse(itemDataAttr);
                    } catch (e) {
                        console.error('Error parsing item data:', e);
                    }
                }
                
                const itemId = itemData?.itemID || itemData?.itemId || row.querySelector('td:nth-child(2)')?.textContent?.trim() || '';
                const itemName = itemData?.itemName || row.querySelector('td:nth-child(3)')?.textContent?.trim() || '';
                const qtyValue = itemData?.itemQty || row.querySelector('td:nth-child(7)')?.textContent?.trim() || '';
                const unitPriceValue = itemData?.unitPrice || row.querySelector('td:nth-child(8)')?.textContent?.trim() || '';
                
                const qtyStr = qtyValue.toString().replace(/,/g, '');
                const unitPriceStr = unitPriceValue.toString().replace(/,/g, '');
                const qty = qtyStr === '' ? 0 : parseFloat(qtyStr);
                const unitPrice = unitPriceStr === '' ? 0 : parseFloat(unitPriceStr);
                
                if (!itemId || itemId.trim() === '' || itemId === '-') {
                    errors.push(`Item Purchase Request: Item ${index + 1} - Item ID is required`);
                    if (!firstInvalidField.step) {
                        firstInvalidField.step = 'detail';
                        firstInvalidField.field = row;
                        firstInvalidField.fieldId = `item-${index + 1}`;
                    }
                }
                if (!itemName || itemName.trim() === '' || itemName === '-') {
                    errors.push(`Item Purchase Request: Item ${index + 1} - Item Name is required`);
                    if (!firstInvalidField.step) {
                        firstInvalidField.step = 'detail';
                        firstInvalidField.field = row;
                        firstInvalidField.fieldId = `item-${index + 1}`;
                    }
                }
                if (isNaN(qty) || qty <= 0) {
                    errors.push(`Item Purchase Request: Item ${index + 1} - Quantity must be greater than 0`);
                    if (!firstInvalidField.step) {
                        firstInvalidField.step = 'detail';
                        firstInvalidField.field = row;
                        firstInvalidField.fieldId = `item-${index + 1}`;
                    }
                }
                if (isNaN(unitPrice) || unitPrice < 0) {
                    errors.push(`Item Purchase Request: Item ${index + 1} - Unit Price must be greater than or equal to 0`);
                    if (!firstInvalidField.step) {
                        firstInvalidField.step = 'detail';
                        firstInvalidField.field = row;
                        firstInvalidField.fieldId = `item-${index + 1}`;
                    }
                }
            });
        }
        
        // Step 3: Validate Assign Approval
        const reviewedByIdField = document.getElementById('reviewedById');
        const approvedByIdField = document.getElementById('approvedById');
        const confirmedByIdField = document.getElementById('confirmedById');
        
        let reviewedByIdValue = '';
        let approvedByIdValue = '';
        let confirmedByIdValue = '';
        
        if (reviewedByIdField) {
            reviewedByIdValue = reviewedByIdField.value?.trim() || '';
        }
        if (approvedByIdField) {
            approvedByIdValue = approvedByIdField.value?.trim() || '';
        }
        if (confirmedByIdField) {
            confirmedByIdValue = confirmedByIdField.value?.trim() || '';
        }
        
        if (!reviewedByIdValue || reviewedByIdValue === '') {
            errors.push('Assign Approval: Reviewed by is required');
            const reviewedByField = document.getElementById('reviewedBy');
            if (reviewedByField) {
                reviewedByField.classList.add('is-invalid');
                this.showFieldError(reviewedByField, 'Reviewed by is required');
            }
            if (!firstInvalidField.step) {
                firstInvalidField.step = 'assign-approval';
                firstInvalidField.field = reviewedByField;
                firstInvalidField.fieldId = 'reviewedBy';
            }
        }
        if (!approvedByIdValue || approvedByIdValue === '') {
            errors.push('Assign Approval: Approved by is required');
            const approvedByField = document.getElementById('approvedBy');
            if (approvedByField) {
                approvedByField.classList.add('is-invalid');
                this.showFieldError(approvedByField, 'Approved by is required');
            }
            if (!firstInvalidField.step) {
                firstInvalidField.step = 'assign-approval';
                firstInvalidField.field = approvedByField;
                firstInvalidField.fieldId = 'approvedBy';
            }
        }
        if (!confirmedByIdValue || confirmedByIdValue === '') {
            errors.push('Assign Approval: Confirmed by is required');
            const confirmedByField = document.getElementById('confirmedBy');
            if (confirmedByField) {
                confirmedByField.classList.add('is-invalid');
                this.showFieldError(confirmedByField, 'Confirmed by is required');
            }
            if (!firstInvalidField.step) {
                firstInvalidField.step = 'assign-approval';
                firstInvalidField.field = confirmedByField;
                firstInvalidField.fieldId = 'confirmedBy';
            }
        }
        
        // Step 4: Validate Document
        const documentRows = document.querySelectorAll('#documentTable tbody tr');
        if (documentRows.length === 0) {
            errors.push('Supporting Document: At least one document is required');
            if (!firstInvalidField.step) {
                firstInvalidField.step = 'document';
                firstInvalidField.field = null;
                firstInvalidField.fieldId = 'addDocumentBtn';
            }
        } else {
            documentRows.forEach((row, index) => {
                const fileName = row.querySelector('td:nth-child(2)')?.textContent?.trim() || '';
                const fileSize = row.querySelector('td:nth-child(3)')?.textContent?.trim() || '';
                
                if (!fileName || fileName === '-' || fileName === '') {
                    errors.push(`Supporting Document: Document ${index + 1} - File Name is required`);
                    if (!firstInvalidField.step) {
                        firstInvalidField.step = 'document';
                        firstInvalidField.field = row;
                        firstInvalidField.fieldId = `document-${index + 1}`;
                    }
                }
                if (!fileSize || fileSize === '-' || fileSize === '') {
                    errors.push(`Supporting Document: Document ${index + 1} - File Size is required`);
                    if (!firstInvalidField.step) {
                        firstInvalidField.step = 'document';
                        firstInvalidField.field = row;
                        firstInvalidField.fieldId = `document-${index + 1}`;
                    }
                }
            });
        }
        
        if (errors.length > 0) {
            const basicInfoErrors = errors.filter(e => e.startsWith('Basic Information:'));
            const detailErrors = errors.filter(e => e.startsWith('Item Purchase Request:'));
            const approvalErrors = errors.filter(e => e.startsWith('Assign Approval:'));
            const documentErrors = errors.filter(e => e.startsWith('Supporting Document:'));
            
            let message = '<div style="text-align: left; font-size: 14px;">';
            message += '<p style="margin-bottom: 1rem; font-weight: 600; color: #495057; font-size: 15px;">Please complete all required fields before submitting:</p>';
            
            if (basicInfoErrors.length > 0) {
                message += '<div style="margin-bottom: 1rem; padding: 0.75rem; background-color: #f8f9fa; border-left: 3px solid #dc3545; border-radius: 4px;">';
                message += '<strong style="color: #dc3545; display: block; margin-bottom: 0.5rem;">• Basic Information</strong>';
                message += '<ul style="margin: 0; padding-left: 1.25rem; list-style-type: disc;">';
                basicInfoErrors.forEach(e => {
                    message += '<li style="margin-bottom: 0.25rem; color: #495057;">' + this.wizard.escapeHtml(e.replace('Basic Information: ', '')) + '</li>';
                });
                message += '</ul></div>';
            }
            
            if (detailErrors.length > 0) {
                message += '<div style="margin-bottom: 1rem; padding: 0.75rem; background-color: #f8f9fa; border-left: 3px solid #dc3545; border-radius: 4px;">';
                message += '<strong style="color: #dc3545; display: block; margin-bottom: 0.5rem;">• Item Purchase Request</strong>';
                message += '<ul style="margin: 0; padding-left: 1.25rem; list-style-type: disc;">';
                detailErrors.forEach(e => {
                    message += '<li style="margin-bottom: 0.25rem; color: #495057;">' + this.wizard.escapeHtml(e.replace('Item Purchase Request: ', '')) + '</li>';
                });
                message += '</ul></div>';
            }
            
            if (approvalErrors.length > 0) {
                message += '<div style="margin-bottom: 1rem; padding: 0.75rem; background-color: #f8f9fa; border-left: 3px solid #dc3545; border-radius: 4px;">';
                message += '<strong style="color: #dc3545; display: block; margin-bottom: 0.5rem;">• Assign Approval</strong>';
                message += '<ul style="margin: 0; padding-left: 1.25rem; list-style-type: disc;">';
                approvalErrors.forEach(e => {
                    message += '<li style="margin-bottom: 0.25rem; color: #495057;">' + this.wizard.escapeHtml(e.replace('Assign Approval: ', '')) + '</li>';
                });
                message += '</ul></div>';
            }
            
            if (documentErrors.length > 0) {
                message += '<div style="margin-bottom: 1rem; padding: 0.75rem; background-color: #f8f9fa; border-left: 3px solid #dc3545; border-radius: 4px;">';
                message += '<strong style="color: #dc3545; display: block; margin-bottom: 0.5rem;">• Supporting Document</strong>';
                message += '<ul style="margin: 0; padding-left: 1.25rem; list-style-type: disc;">';
                documentErrors.forEach(e => {
                    message += '<li style="margin-bottom: 0.25rem; color: #495057;">' + this.wizard.escapeHtml(e.replace('Supporting Document: ', '')) + '</li>';
                });
                message += '</ul></div>';
            }
            
            message += '</div>';
            
            return {
                isValid: false,
                message: message,
                firstInvalidField: firstInvalidField
            };
        }
        
        return {
            isValid: true,
            message: '',
            firstInvalidField: null
        };
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.ProcurementWizardValidation = ProcurementWizardValidation;
}

