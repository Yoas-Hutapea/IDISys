/**
 * Procurement Wizard Summary Module
 * Handles Summary step: updating summary tables for items, documents, and additional information
 */

class ProcurementWizardSummary {
    constructor(wizardInstance) {
        this.wizard = wizardInstance;
    }

    /**
     * Update summary step
     */
    updateSummaryStep() {
        // Update basic information in summary
        const basicInfoFields = [
            { id: 'Applicant', summaryId: 'summary-applicant' },
            { id: 'Company', summaryId: 'summary-company' },
            { id: 'PurchaseRequestType', summaryId: 'summary-type' },
            { id: 'PurchaseRequestSubType', summaryId: 'summary-subtype' },
            { id: 'PurchaseRequestTypeCategory', summaryId: 'summary-category' },
            { id: 'PurchaseRequestDate', summaryId: 'summary-date' },
            { id: 'Remarks', summaryId: 'summary-remarks' }
        ];
        
        basicInfoFields.forEach(field => {
            const sourceField = document.getElementById(field.id);
            const summaryField = document.getElementById(field.summaryId);
            
            if (sourceField && summaryField) {
                if (sourceField.type === 'hidden') {
                    // For hidden fields, try to get text from associated display element
                    if (field.id === 'Applicant') {
                        const textElement = document.getElementById('applicantSelectedText');
                        summaryField.textContent = textElement?.textContent || '-';
                    } else if (field.id === 'Company') {
                        const textElement = document.getElementById('companySelectedText');
                        summaryField.textContent = textElement?.textContent || '-';
                    } else if (field.id === 'PurchaseRequestType') {
                        const textElement = document.getElementById('purchaseRequestTypeSelectedText');
                        summaryField.textContent = textElement?.textContent || '-';
                    } else if (field.id === 'PurchaseRequestSubType') {
                        const textElement = document.getElementById('purchaseRequestSubTypeSelectedText');
                        summaryField.textContent = textElement?.textContent || '-';
                    } else {
                        summaryField.textContent = sourceField.value || '-';
                    }
                } else {
                    summaryField.textContent = sourceField.value || '-';
                }
            }
        });
        
        // Update items table in summary
        this.updateSummaryItemsTable();
        
        // Update documents table in summary
        this.updateSummaryDocumentsTable();
        
        // Update additional information in summary
        this.updateSummaryAdditionalTable();
    }

    /**
     * Update summary items table
     */
    updateSummaryItemsTable() {
        // Find the Items table in summary
        const itemDetailsCard = Array.from(document.querySelectorAll('#summary .card')).find(card => {
            const header = card.querySelector('.card-header h6');
            return header && (header.textContent.includes('Detail Purchase Request') || header.textContent.includes('Item Purchase Request'));
        });
        
        const summaryItemsTbody = itemDetailsCard?.querySelector('.table-responsive tbody');
        if (!summaryItemsTbody) return;
        
        // Clear existing rows
        summaryItemsTbody.innerHTML = '';
        
        // Get items from Detail table
        const itemRows = document.querySelectorAll('#itemDetailTable tbody tr');
        
        if (itemRows.length === 0) {
            summaryItemsTbody.innerHTML = '<tr><td colspan="7" class="text-center">No items added</td></tr>';
            return;
        }
        
        const escapeHtml = this.wizard.escapeHtml || ((text) => {
            if (text == null) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        });
        
        itemRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length < 9) return;
            
            // Extract item data (skip first column which is Action button)
            const itemId = cells[1]?.textContent?.trim() || '';
            const itemName = cells[2]?.textContent?.trim() || '';
            const description = cells[3]?.textContent?.trim() || '';
            const unit = cells[4]?.textContent?.trim() || '';
            const quantity = cells[6]?.textContent?.trim() || '';
            const unitPrice = cells[7]?.textContent?.trim() || '';
            const amount = cells[8]?.textContent?.trim() || '';
            
            // Create summary row
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>${escapeHtml(itemId)}</td>
                <td>${escapeHtml(itemName)}</td>
                <td>${escapeHtml(description)}</td>
                <td>${escapeHtml(unit)}</td>
                <td>${escapeHtml(quantity)}</td>
                <td>${escapeHtml(unitPrice)}</td>
                <td>${escapeHtml(amount)}</td>
            `;
            summaryItemsTbody.appendChild(newRow);
        });
    }

    /**
     * Update summary documents table
     */
    updateSummaryDocumentsTable() {
        // Find the Documents table in summary
        const documentsCard = Array.from(document.querySelectorAll('#summary .card')).find(card => {
            const header = card.querySelector('.card-header h6');
            return header && header.textContent.includes('Supporting Documents');
        });
        
        const summaryDocumentsTbody = documentsCard?.querySelector('.table-responsive tbody');
        if (!summaryDocumentsTbody) return;
        
        // Clear existing rows
        summaryDocumentsTbody.innerHTML = '';
        
        // Get documents from Document table
        const documentRows = document.querySelectorAll('#documentTable tbody tr');
        
        if (documentRows.length === 0) {
            summaryDocumentsTbody.innerHTML = '<tr><td colspan="2" class="text-center">No documents uploaded</td></tr>';
            return;
        }
        
        const escapeHtml = this.wizard.escapeHtml || ((text) => {
            if (text == null) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        });
        
        documentRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length < 3) return;
            
            // Extract document data (skip first column which is action button)
            const fileName = cells[1]?.textContent?.trim() || '';
            const fileSize = cells[2]?.textContent?.trim() || '';
            
            // Create summary row
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>${escapeHtml(fileName)}</td>
                <td>${escapeHtml(fileSize)}</td>
            `;
            summaryDocumentsTbody.appendChild(newRow);
        });
    }
    
    /**
     * Update summary additional table
     */
    updateSummaryAdditionalTable() {
        const additionalSection = document.getElementById('review-additional-section');
        const additionalBody = document.getElementById('review-additional-body');
        
        if (!additionalSection || !additionalBody) return;
        
        // Check if current Type ID/Sub Type ID shows Additional section
        const typeId = this.wizard.currentPurchaseRequestTypeID;
        const subTypeId = this.wizard.currentPurchaseRequestSubTypeID;
        
        // Determine which section should be visible
        const shouldShowBillingTypeSection = typeId === 6 && subTypeId === 2;
        const shouldShowSonumbSection = 
            (typeId === 8 && subTypeId === 4) ||
            (typeId === 2 && (subTypeId === 1 || subTypeId === 3)) ||
            (typeId === 4 && subTypeId === 3) ||
            (typeId === 3 && (subTypeId === 4 || subTypeId === 5));
        const shouldShowSubscribeSection = typeId === 5 || typeId === 7;
        
        // Hide section if no Additional section should be shown
        if (!shouldShowBillingTypeSection && !shouldShowSonumbSection && !shouldShowSubscribeSection) {
            additionalSection.style.display = 'none';
            return;
        }
        
        // Show section
        additionalSection.style.display = 'block';
        additionalBody.innerHTML = '';
        
        // Helper function to get field value
        const getFieldValue = (fieldId) => {
            const field = document.getElementById(fieldId);
            if (!field) return '-';
            if (field.type === 'hidden') {
                // For hidden fields, try to get text from associated display element
                if (fieldId === 'BillingType') {
                    const textElement = document.getElementById('billingTypeSelectedText');
                    return textElement?.textContent || '-';
                } else if (fieldId === 'SubscribeBillingType') {
                    const textElement = document.getElementById('subscribeBillingTypeSelectedText');
                    return textElement?.textContent || '-';
                }
                return field.value || '-';
            }
            return field.value || '-';
        };
        
        // Helper function to format date
        const formatDate = (dateValue) => {
            if (!dateValue || dateValue === '-') return '-';
            try {
                const date = new Date(dateValue);
                if (isNaN(date.getTime())) return dateValue;
                return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            } catch {
                return dateValue;
            }
        };
        
        const escapeHtml = this.wizard.escapeHtml || ((text) => {
            if (text == null) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        });
        
        // Build summary HTML based on active section
        let summaryHTML = '<div class="row g-3">';
        
        // Billing Type Section (Type ID 6, Sub Type ID 2)
        if (shouldShowBillingTypeSection) {
            const billingType = escapeHtml(getFieldValue('BillingType'));
            const startPeriod = escapeHtml(formatDate(getFieldValue('StartPeriod')));
            const period = escapeHtml(getFieldValue('Period'));
            const endPeriod = escapeHtml(formatDate(getFieldValue('EndPeriod')));
            
            summaryHTML += `
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Billing Type</label>
                    <input type="text" class="form-control" value="${billingType}" disabled>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Period</label>
                    <input type="text" class="form-control" value="${period}" disabled>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Start Period</label>
                    <input type="text" class="form-control" value="${startPeriod}" disabled>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">End Period</label>
                    <input type="text" class="form-control" value="${endPeriod}" disabled>
                </div>
            `;
        }
        // Sonumb Section (Type ID 2,3,4,8)
        else if (shouldShowSonumbSection) {
            const sonumb = escapeHtml(getFieldValue('Sonumb'));
            const siteName = escapeHtml(getFieldValue('SiteName'));
            const siteID = escapeHtml(getFieldValue('SiteID'));
            
            summaryHTML += `
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Sonumb</label>
                    <input type="text" class="form-control" value="${sonumb}" disabled>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Site Name</label>
                    <input type="text" class="form-control" value="${siteName}" disabled>
                </div>
                ${siteID && siteID !== '-' ? `
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Site ID</label>
                    <input type="text" class="form-control" value="${siteID}" disabled>
                </div>
                ` : ''}
            `;
        }
        // Subscribe Section (Type ID 5 or 7)
        else if (shouldShowSubscribeSection) {
            const subscribeSonumb = escapeHtml(getFieldValue('SubscribeSonumb'));
            const subscribeSiteName = escapeHtml(getFieldValue('SubscribeSiteName'));
            const subscribeSiteID = escapeHtml(getFieldValue('SubscribeSiteID'));
            const subscribeBillingType = escapeHtml(getFieldValue('SubscribeBillingType'));
            const subscribeStartPeriod = escapeHtml(formatDate(getFieldValue('SubscribeStartPeriod')));
            const subscribePeriod = escapeHtml(getFieldValue('SubscribePeriod'));
            const subscribeEndPeriod = escapeHtml(formatDate(getFieldValue('SubscribeEndPeriod')));
            
            summaryHTML += `
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Sonumb</label>
                    <input type="text" class="form-control" value="${subscribeSonumb}" disabled>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Site Name</label>
                    <input type="text" class="form-control" value="${subscribeSiteName}" disabled>
                </div>
                ${subscribeSiteID && subscribeSiteID !== '-' ? `
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Site ID</label>
                    <input type="text" class="form-control" value="${subscribeSiteID}" disabled>
                </div>
                ` : ''}
                ${subscribeBillingType && subscribeBillingType !== '-' ? `
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Billing Type</label>
                    <input type="text" class="form-control" value="${subscribeBillingType}" disabled>
                </div>
                ` : ''}
                ${subscribeStartPeriod && subscribeStartPeriod !== '-' ? `
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Start Period</label>
                    <input type="text" class="form-control" value="${subscribeStartPeriod}" disabled>
                </div>
                ` : ''}
                ${subscribePeriod && subscribePeriod !== '-' ? `
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Period</label>
                    <input type="text" class="form-control" value="${subscribePeriod}" disabled>
                </div>
                ` : ''}
                ${subscribeEndPeriod && subscribeEndPeriod !== '-' ? `
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">End Period</label>
                    <input type="text" class="form-control" value="${subscribeEndPeriod}" disabled>
                </div>
                ` : ''}
            `;
        }
        
        summaryHTML += '</div>';
        additionalBody.innerHTML = summaryHTML;
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.ProcurementWizardSummary = ProcurementWizardSummary;
}

