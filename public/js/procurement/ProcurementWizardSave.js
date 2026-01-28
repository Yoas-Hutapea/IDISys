/**
 * Procurement Wizard Save Module
 * Handles all save operations: saveDraft, submitPR, and all save helper methods
 */

class ProcurementWizardSave {
    constructor(wizardInstance) {
        this.wizard = wizardInstance;
    }

    /**
     * Save draft based on current step
     */
    async saveDraft() {
        const currentStepId = this.wizard.stepOrder[this.wizard.currentStepIndex];
        if (currentStepId === 'detail') {
            await this.saveDraftItems();
        } else {
            await this.saveDraftBasicInfo();
        }
    }

    /**
     * Save basic information draft
     */
    async saveDraftBasicInfo(skipRedirect = false) {
        if (!this.wizard.cachedElements.form) return false;
        
        const formData = new FormData(this.wizard.cachedElements.form);
        
        // Get current logged-in user employee ID from config (for storing)
        const currentUserEmployeeID = (window.ProcurementConfig && window.ProcurementConfig.currentUserEmployeeID) || '';
        
        // Get values directly from DOM elements (important for disabled/readonly fields)
        const applicantField = document.getElementById('Applicant');
        const companyField = document.getElementById('Company');
        const purchReqTypeField = document.getElementById('PurchaseRequestType');
        const purchReqSubTypeField = document.getElementById('PurchaseRequestSubType');
        const purchReqNameField = document.getElementById('PurchaseRequestName');
        const remarksField = document.getElementById('Remarks');
        
        // Helper to get value from field
        const getFieldValue = (field) => {
            if (!field) return '';
            if (field.tagName === 'SELECT') {
                return field.value || '';
            }
            return field.value || '';
        };
        
        // Gather form data
        const payload = {
            Requestor: currentUserEmployeeID || '',
            Applicant: getFieldValue(applicantField) || formData.get('Applicant')?.toString().trim() || '',
            Company: (getFieldValue(companyField) || formData.get('Company')?.toString().trim() || '').toUpperCase(),
            PurchReqType: getFieldValue(purchReqTypeField) || formData.get('PurchaseRequestType')?.toString().trim() || '',
            PurchReqSubType: getFieldValue(purchReqSubTypeField) || formData.get('PurchaseRequestSubType')?.toString().trim() || '',
            PurchReqName: getFieldValue(purchReqNameField) || formData.get('PurchaseRequestName')?.toString().trim() || '',
            Remark: getFieldValue(remarksField) || formData.get('Remarks')?.toString().trim() || '',
            mstApprovalStatusID: 6 // Draft
        };
        
        // Validate required fields
        const requiredFields = ['Requestor', 'Applicant', 'Company', 'PurchReqType', 'PurchReqSubType', 'PurchReqName', 'Remark'];
        const missingFields = requiredFields.filter(field => !payload[field] || payload[field].trim() === '');
        
        if (missingFields.length > 0) {
            this.wizard.showValidationMessage('Please fill in all required fields in Basic Information.');
            return false;
        }
        
        if (!currentUserEmployeeID || currentUserEmployeeID.trim() === '') {
            this.wizard.showValidationMessage('Requestor (Employee ID) is required. Please ensure you are logged in correctly.');
            return false;
        }
        
        // Show loading state
        const saveBtn = document.getElementById('saveDraftBtn');
        let originalText = '';
        let buttonWasDisabled = false;
        if (!skipRedirect && saveBtn) {
            originalText = saveBtn.innerHTML;
            buttonWasDisabled = saveBtn.disabled;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="icon-base bx bx-loader-circle me-2 spin"></i>Saving...';
        }
        
        try {
            let draft = JSON.parse(localStorage.getItem('procurementDraft') || '{}');
            let prNumber = draft.purchReqNumber;
            
            let result;
            if (prNumber) {
                try {
                    const updateData = await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequests/${encodeURIComponent(prNumber)}`, 'PUT', payload);
                    result = updateData.data || updateData;
                } catch (updateError) {
                    if (updateError.message && updateError.message.includes('not found')) {
                        localStorage.removeItem('procurementDraft');
                        localStorage.removeItem('procurementDraftItemsSaved');
                        localStorage.removeItem('procurementDraftApprovalSaved');
                        localStorage.removeItem('procurementDraftDocumentsSaved');
                        
                        const createData = await apiCall('Procurement', '/Procurement/PurchaseRequest/PurchaseRequests', 'POST', payload);
                        result = createData.data || createData;
                        prNumber = result.purchReqNumber || result.PurchReqNumber;
                    } else {
                        throw updateError;
                    }
                }
            } else {
                const createData = await apiCall('Procurement', '/Procurement/PurchaseRequest/PurchaseRequests', 'POST', payload);
                result = createData.data || createData;
                prNumber = result.purchReqNumber || result.PurchReqNumber;
            }
            
            localStorage.setItem('procurementDraft', JSON.stringify({
                id: result.id || result.ID,
                purchReqNumber: prNumber || result.purchReqNumber || result.PurchReqNumber,
                company: result.company || result.Company,
                createdDate: result.createdDate || result.CreatedDate,
                currentStep: this.wizard.stepOrder[this.wizard.currentStepIndex],
                currentStepIndex: this.wizard.currentStepIndex,
                timestamp: new Date().toISOString()
            }));
            
            const finalPrNumber = prNumber || result.purchReqNumber || result.PurchReqNumber;
            
            if (!skipRedirect) {
                this.showDraftSavedMessage(finalPrNumber);
                setTimeout(() => {
                    window.location.href = '/Procurement/PurchaseRequest/List';
                }, 1500);
            }
            
            return true;
        } catch (error) {
            console.error('Error saving draft:', error);
            this.wizard.showValidationMessage('Error saving draft: ' + error.message);
            return false;
        } finally {
            if (!skipRedirect && saveBtn) {
                saveBtn.disabled = buttonWasDisabled;
                saveBtn.innerHTML = originalText;
            }
        }
    }

    /**
     * Show draft saved message
     */
    showDraftSavedMessage(prNumber) {
        this.wizard.removeExistingAlerts();
        const message = prNumber 
            ? `<strong>Draft Saved!</strong> Your procurement request has been saved. PR Number: ${prNumber}`
            : '<strong>Draft Saved!</strong> Your procurement request has been saved as draft.';
        this.wizard.createAlert('success', message, 5000);
    }

    /**
     * Get items from table
     */
    getItemsFromTable() {
        const items = [];
        const rows = document.querySelectorAll('#itemDetailTable tbody tr');
        
        rows.forEach(row => {
            const itemDataAttr = row.getAttribute('data-item-data');
            if (itemDataAttr) {
                try {
                    const itemData = JSON.parse(itemDataAttr);
                    items.push({
                        id: itemData.id || itemData.ID || itemData.Id || null,
                        mstPROPurchaseItemInventoryItemID: itemData.mstPROPurchaseItemInventoryItemID || itemData.mstPROInventoryItemID || itemData.itemID || itemData.itemId || null,
                        ItemName: itemData.itemName || '',
                        ItemDescription: itemData.itemDescription || '',
                        ItemUnit: itemData.itemUnit || '',
                        ItemQty: itemData.itemQty || 1,
                        CurrencyCode: itemData.currencyCode || 'IDR',
                        UnitPrice: itemData.unitPrice || 0,
                        Amount: itemData.amount || (itemData.itemQty * itemData.unitPrice)
                    });
                } catch (e) {
                    console.error('Error parsing item data:', e);
                }
            } else {
                const cells = row.querySelectorAll('td');
                if (cells.length < 5) return;
                
                const itemId = cells[1]?.textContent?.trim() || '';
                const itemName = cells[2]?.textContent?.trim() || '';
                const description = cells[3]?.textContent?.trim() || '';
                const unit = cells[4]?.textContent?.trim() || '';
                
                if (itemId && itemName) {
                    items.push({
                        mstPROPurchaseItemInventoryItemID: itemId || null,
                        ItemName: itemName,
                        ItemDescription: description,
                        ItemUnit: unit,
                        ItemQty: 1,
                        CurrencyCode: 'IDR',
                        UnitPrice: 0,
                        Amount: 0
                    });
                }
            }
        });
        
        return items;
    }

    /**
     * Save items only (without saving basic info first)
     */
    async saveDraftItemsOnly(prNumber) {
        const itemRows = document.querySelectorAll('#itemDetailTable tbody tr');
        if (itemRows.length === 0) {
            return;
        }

        const items = this.getItemsFromTable();
        if (items.length === 0) {
            return;
        }

        if (!prNumber) {
            throw new Error('Purchase Request Number not found. Please save basic information first.');
        }

        const savingKey = `savingItems_${prNumber}`;
        if (sessionStorage.getItem(savingKey) === 'true') {
            console.warn('Items save already in progress for PR Number:', prNumber);
            return;
        }

        try {
            sessionStorage.setItem(savingKey, 'true');

            // Get Sonumb value from Additional section
            let sonumbValue = null;
            const sonumbSection = document.getElementById('sonumbSection');
            const subscribeSection = document.getElementById('subscribeSection');
            const isSonumbSectionVisible = sonumbSection && sonumbSection.style.display !== 'none';
            const isSubscribeSectionVisible = subscribeSection && subscribeSection.style.display !== 'none';
            
            if (isSonumbSectionVisible) {
                const sonumbField = document.getElementById('Sonumb');
                if (sonumbField && sonumbField.value) {
                    sonumbValue = sonumbField.value.trim();
                }
            } else if (isSubscribeSectionVisible) {
                const subscribeSonumbField = document.getElementById('SubscribeSonumb');
                if (subscribeSonumbField && subscribeSonumbField.value) {
                    sonumbValue = subscribeSonumbField.value.trim();
                }
            }
            
            const formattedItems = items.map(item => {
                const itemQty = parseFloat(item.ItemQty) || 0;
                const unitPrice = parseFloat(item.UnitPrice) || 0;
                const amount = parseFloat(item.Amount) || 0;
                const itemId = item.id || item.ID || item.Id || null;
                
                const formattedItem = {
                    ID: itemId ? parseInt(itemId) : null,
                    trxPROPurchaseRequestNumber: prNumber || '',
                    mstPROPurchaseItemInventoryItemID: (item.mstPROPurchaseItemInventoryItemID || item.mstPROInventoryItemID) ? (item.mstPROPurchaseItemInventoryItemID || item.mstPROInventoryItemID).toString() : null,
                    ItemName: (item.ItemName || '').toString(),
                    ItemDescription: (item.ItemDescription || '').toString(),
                    ItemUnit: (item.ItemUnit || '').toString(),
                    ItemQty: isNaN(itemQty) ? 0 : itemQty,
                    CurrencyCode: (item.CurrencyCode || 'IDR').toString(),
                    UnitPrice: isNaN(unitPrice) ? 0 : unitPrice,
                    Amount: isNaN(amount) ? 0 : amount,
                    Sonumb: sonumbValue || null
                };
                
                if (!formattedItem.trxPROPurchaseRequestNumber) {
                    throw new Error('trxPROPurchaseRequestNumber is required for each item');
                }
                if (!formattedItem.ItemName) {
                    throw new Error('ItemName is required for each item');
                }
                
                return formattedItem;
            });

            const payload = {
                trxPROPurchaseRequestNumber: prNumber || '',
                Items: formattedItems
            };
            
            if (!payload.trxPROPurchaseRequestNumber) {
                throw new Error('trxPROPurchaseRequestNumber is required');
            }
            if (!payload.Items || payload.Items.length === 0) {
                throw new Error('Items array is required and cannot be empty');
            }

            await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequestItems/${encodeURIComponent(prNumber)}/items/bulk`, 'POST', payload);
            
            localStorage.setItem('procurementDraftItemsSaved', 'true');
        } catch (error) {
            console.error('Error saving items:', error);
            throw error;
        } finally {
            sessionStorage.removeItem(savingKey);
        }
    }

    /**
     * Save Additional data only
     */
    async saveDraftAdditionalOnly(prNumber) {
        if (!prNumber) {
            console.warn('Purchase Request Number not found. Skipping Additional data save.');
            return;
        }

        const savingKey = `savingAdditional_${prNumber}`;
        if (sessionStorage.getItem(savingKey) === 'true') {
            console.warn('Additional save already in progress for PR Number:', prNumber);
            return;
        }

        try {
            sessionStorage.setItem(savingKey, 'true');

            const additionalData = {
                PurchaseRequestNumber: prNumber
            };

            const billingTypeSection = document.getElementById('billingTypeSection');
            const sonumbSection = document.getElementById('sonumbSection');
            const subscribeSection = document.getElementById('subscribeSection');
            
            const typeId = this.wizard.currentPurchaseRequestTypeID;
            const subTypeId = this.wizard.currentPurchaseRequestSubTypeID;
            
            const shouldShowBillingTypeSection = typeId === 6 && subTypeId === 2;
            const shouldShowSonumbSection = 
                (typeId === 8 && subTypeId === 4) ||
                (typeId === 2 && (subTypeId === 1 || subTypeId === 3)) ||
                (typeId === 4 && subTypeId === 3) ||
                (typeId === 3 && (subTypeId === 4 || subTypeId === 5));
            const shouldShowSubscribeSection = typeId === 5 || typeId === 7;

            // Billing Type Section
            if (shouldShowBillingTypeSection && billingTypeSection) {
                const billingTypeID = document.getElementById('BillingType')?.value ? parseInt(document.getElementById('BillingType').value) : null;
                const startPeriod = document.getElementById('StartPeriod')?.value || null;
                const period = document.getElementById('Period')?.value ? parseInt(document.getElementById('Period').value) : null;
                const endPeriod = document.getElementById('EndPeriod')?.value || null;

                if (billingTypeID || startPeriod || period || endPeriod) {
                    additionalData.BillingTypeID = billingTypeID;
                    additionalData.StartPeriod = startPeriod ? new Date(startPeriod).toISOString().split('T')[0] : null;
                    additionalData.Period = period;
                    additionalData.EndPeriod = endPeriod ? new Date(endPeriod).toISOString().split('T')[0] : null;
                }
            }

            // Sonumb Section
            if (shouldShowSonumbSection && sonumbSection) {
                const sonumb = document.getElementById('Sonumb')?.value?.trim() || null;
                const sonumbIdField = document.getElementById('SonumbId');
                const sonumbId = sonumbIdField?.value && sonumbIdField.value.trim() !== '' ? parseInt(sonumbIdField.value) : null;
                const siteName = document.getElementById('SiteName')?.value?.trim() || null;
                const siteID = document.getElementById('SiteID')?.value?.trim() || null;

                // Only include fields that have values
                // For SonumbId: only send if it has a valid value (not null/empty)
                // This prevents foreign key constraint violation when updating with null
                if (sonumb || siteName || siteID) {
                    additionalData.Sonumb = sonumb || null;
                    additionalData.SiteName = siteName || null;
                    additionalData.SiteID = siteID || null;
                }
                
                // Only include SonumbId if it has a valid value
                // If SonumbId is null/empty, don't send it to avoid foreign key constraint violation
                if (sonumbId !== null && !isNaN(sonumbId) && sonumbId > 0) {
                    additionalData.SonumbId = sonumbId;
                }
            }

            // Subscribe Section
            if (shouldShowSubscribeSection && subscribeSection) {
                const subscribeBillingTypeID = document.getElementById('SubscribeBillingType')?.value ? parseInt(document.getElementById('SubscribeBillingType').value) : null;
                const subscribeStartPeriod = document.getElementById('SubscribeStartPeriod')?.value || null;
                const subscribePeriod = document.getElementById('SubscribePeriod')?.value ? parseInt(document.getElementById('SubscribePeriod').value) : null;
                const subscribeEndPeriod = document.getElementById('SubscribeEndPeriod')?.value || null;
                const subscribeSonumb = document.getElementById('SubscribeSonumb')?.value?.trim() || null;
                const subscribeSonumbIdField = document.getElementById('SubscribeSonumbId');
                const subscribeSonumbId = subscribeSonumbIdField?.value && subscribeSonumbIdField.value.trim() !== '' ? parseInt(subscribeSonumbIdField.value) : null;
                const subscribeSiteName = document.getElementById('SubscribeSiteName')?.value?.trim() || null;
                const subscribeSiteID = document.getElementById('SubscribeSiteID')?.value?.trim() || null;

                // Only include fields that have values
                if (subscribeBillingTypeID || subscribeStartPeriod || subscribePeriod || subscribeEndPeriod || subscribeSonumb || subscribeSiteName || subscribeSiteID) {
                    additionalData.BillingTypeID = subscribeBillingTypeID;
                    additionalData.StartPeriod = subscribeStartPeriod ? new Date(subscribeStartPeriod).toISOString().split('T')[0] : null;
                    additionalData.Period = subscribePeriod;
                    additionalData.EndPeriod = subscribeEndPeriod ? new Date(subscribeEndPeriod).toISOString().split('T')[0] : null;
                    additionalData.Sonumb = subscribeSonumb || null;
                    additionalData.SiteName = subscribeSiteName || null;
                    additionalData.SiteID = subscribeSiteID || null;
                }
                
                // Only include SubscribeSonumbId if it has a valid value
                // If SubscribeSonumbId is null/empty, don't send it to avoid foreign key constraint violation
                if (subscribeSonumbId !== null && !isNaN(subscribeSonumbId) && subscribeSonumbId > 0) {
                    additionalData.SonumbId = subscribeSonumbId;
                }
            }

            const hasData = Object.keys(additionalData).some(key => 
                key !== 'PurchaseRequestNumber' && additionalData[key] !== null && additionalData[key] !== undefined && additionalData[key] !== ''
            );

            if (!hasData) {
                console.log('No Additional data to save');
                return;
            }

            await apiCall('Procurement', '/Procurement/PurchaseRequest/PurchaseRequestAdditional', 'POST', additionalData);
            
            console.log('Additional data saved successfully');
        } catch (error) {
            console.error('Error saving Additional data:', error);
        } finally {
            sessionStorage.removeItem(savingKey);
        }
    }

    /**
     * Save documents only
     */
    async saveDraftDocumentsOnly(prNumber) {
        const documentRows = document.querySelectorAll('#documentTable tbody tr');
        const hasDocuments = documentRows.length > 0;

        if (!prNumber) {
            throw new Error('Purchase Request Number not found. Please save basic information first.');
        }

        const savingKey = `savingDocuments_${prNumber}`;
        if (sessionStorage.getItem(savingKey) === 'true') {
            console.warn('Documents save already in progress for PR Number:', prNumber);
            return;
        }

        try {
            sessionStorage.setItem(savingKey, 'true');

            // Upload new files
            const filesToUpload = [];
            documentRows.forEach(row => {
                const fileName = row.querySelector('td:nth-child(2)')?.textContent?.trim() || '';
                if (fileName) {
                    const file = this.wizard.documentFiles.get(fileName);
                    if (file) {
                        filesToUpload.push({ fileName, file });
                    }
                }
            });

            for (const { fileName, file } of filesToUpload) {
                try {
                    const formData = new FormData();
                    formData.append('file', file);
                    await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequestDocuments/${encodeURIComponent(prNumber)}/documents/upload`, 'POST', formData);
                } catch (error) {
                    console.error(`Error uploading file "${fileName}":`, error);
                    throw new Error(`Failed to upload file "${fileName}": ${error.message}`);
                }
            }

            // Collect all documents
            const documents = [];
            if (hasDocuments) {
                documentRows.forEach(row => {
                    const fileName = row.querySelector('td:nth-child(2)')?.textContent?.trim() || '';
                    const fileSize = row.querySelector('td:nth-child(3)')?.textContent?.trim() || '';
                    
                    if (fileName) {
                        const documentDataAttr = row.getAttribute('data-document-data');
                        let documentId = null;
                        if (documentDataAttr) {
                            try {
                                const documentData = JSON.parse(documentDataAttr);
                                documentId = documentData.id || null;
                            } catch (e) {
                                console.error('Error parsing document data:', e);
                            }
                        }
                        
                        const documentDto = {
                            ID: documentId ? parseInt(documentId) : null,
                            trxPROPurchaseRequestNumber: prNumber || '',
                            FileName: fileName,
                            FileSize: fileSize
                        };
                        
                        documents.push(documentDto);
                    }
                });
            }

            const payload = {
                trxPROPurchaseRequestNumber: prNumber || '',
                Documents: documents
            };
            
            if (!payload.trxPROPurchaseRequestNumber) {
                throw new Error('trxPROPurchaseRequestNumber is required');
            }

            await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequestDocuments/${encodeURIComponent(prNumber)}/documents/bulk`, 'POST', payload);
            
            localStorage.setItem('procurementDraftDocumentsSaved', 'true');
        } catch (error) {
            console.error('Error saving documents:', error);
            throw error;
        } finally {
            sessionStorage.removeItem(savingKey);
        }
    }

    /**
     * Save approval only
     */
    async saveDraftApprovalOnly(prNumber) {
        if (!prNumber) {
            throw new Error('Purchase Request Number not found. Please save basic information first.');
        }

        const reviewedByIdInput = document.getElementById('reviewedById');
        const approvedByIdInput = document.getElementById('approvedById');
        const confirmedByIdInput = document.getElementById('confirmedById');
        
        const reviewedByField = document.getElementById('reviewedBy');
        const approvedByField = document.getElementById('approvedBy');
        const confirmedByField = document.getElementById('confirmedBy');
        
        let reviewedBy = reviewedByIdInput?.value?.trim() || 
                   reviewedByField?.getAttribute('data-employee-id')?.trim() || null;
        
        let approvedBy = approvedByIdInput?.value?.trim() || 
                   approvedByField?.getAttribute('data-employee-id')?.trim() || null;
        
        let confirmedBy = confirmedByIdInput?.value?.trim() || 
                    confirmedByField?.getAttribute('data-employee-id')?.trim() || null;
        
        const form = document.getElementById('assignApprovalForm');
        if (form) {
            const formData = new FormData(form);
            if (!reviewedBy) reviewedBy = formData.get('reviewedById')?.toString().trim() || null;
            if (!approvedBy) approvedBy = formData.get('approvedById')?.toString().trim() || null;
            if (!confirmedBy) confirmedBy = formData.get('confirmedById')?.toString().trim() || null;
        }

        const payload = {
            trxPROPurchaseRequestNumber: prNumber,
            ReviewedBy: reviewedBy,
            ApprovedBy: approvedBy,
            ConfirmedBy: confirmedBy
        };

        try {
            await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequests/${encodeURIComponent(prNumber)}/approval`, 'PUT', payload);
            localStorage.setItem('procurementDraftApprovalSaved', 'true');
        } catch (error) {
            console.error('Error saving approval:', error);
            throw error;
        }
    }

    /**
     * Save draft items (with basic info)
     */
    async saveDraftItems() {
        const saveBtn = document.getElementById('saveDraftBtn');
        if (!saveBtn) return;
        
        const originalText = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="icon-base bx bx-loader-circle me-2 spin"></i>Saving...';

        try {
            let draft = JSON.parse(localStorage.getItem('procurementDraft') || '{}');
            let prNumber = draft.purchReqNumber;
            
            const saved = await this.saveDraftBasicInfo();
            if (!saved) {
                if (!prNumber) {
                    this.wizard.showValidationMessage('Failed to save basic information. Please try again.');
                    return;
                }
                console.warn('Basic info save failed, but PR Number exists. Continuing with existing PR Number.');
            } else {
                draft = JSON.parse(localStorage.getItem('procurementDraft') || '{}');
                prNumber = draft.purchReqNumber || prNumber;
            }
            
            if (!prNumber) {
                this.wizard.showValidationMessage('Failed to create purchase request. Please try again.');
                return;
            }
            
            const additionalStep = document.getElementById('additional');
            if (additionalStep && additionalStep.style.display !== 'none') {
                try {
                    await this.saveDraftAdditionalOnly(prNumber);
                } catch (error) {
                    console.error('Error saving Additional data:', error);
                    console.warn('Continuing save despite Additional save error');
                }
            }
            
            const itemRows = document.querySelectorAll('#itemDetailTable tbody tr');
            if (itemRows.length > 0) {
                await this.saveDraftItemsOnly(prNumber);
                
                const items = this.getItemsFromTable();
                const additionalInfo = (additionalStep && additionalStep.style.display !== 'none') ? ', additional data, ' : ', ';
                this.wizard.createAlert('success', `<strong>Draft Saved!</strong> Basic information${additionalInfo}and ${items.length} item(s) have been saved. PR Number: ${prNumber}`, 5000);
            } else {
                const additionalInfo = (additionalStep && additionalStep.style.display !== 'none') ? ' and additional data' : '';
                this.wizard.createAlert('success', `<strong>Draft Saved!</strong> Basic information${additionalInfo} has been saved. PR Number: ${prNumber}`, 5000);
            }
        } catch (error) {
            console.error('Error saving draft:', error);
            this.wizard.showValidationMessage('Error saving draft: ' + error.message);
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    }

    /**
     * Save draft documents (with basic info and items)
     */
    async saveDraftDocuments() {
        const saveBtn = document.getElementById('saveDraftDocumentBtn');
        if (!saveBtn) {
            console.error('saveDraftDocumentBtn button not found!');
            return;
        }
        
        const originalText = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="icon-base bx bx-loader-circle me-2 spin"></i>Saving...';

        try {
            let draft = JSON.parse(localStorage.getItem('procurementDraft') || '{}');
            let prNumber = draft.purchReqNumber;
            
            const saved = await this.saveDraftBasicInfo();
            if (!saved) {
                if (!prNumber) {
                    this.wizard.showValidationMessage('Failed to save basic information. Please try again.');
                    return;
                }
                console.warn('Basic info save failed, but PR Number exists. Continuing with existing PR Number.');
            } else {
                draft = JSON.parse(localStorage.getItem('procurementDraft') || '{}');
                prNumber = draft.purchReqNumber || prNumber;
            }
            
            if (!prNumber) {
                this.wizard.showValidationMessage('Failed to create purchase request. Please try again.');
                return;
            }
            
            const itemRows = document.querySelectorAll('#itemDetailTable tbody tr');
            if (itemRows.length > 0) {
                try {
                    await this.saveDraftItemsOnly(prNumber);
                } catch (error) {
                    console.error('Error saving items:', error);
                    this.wizard.showValidationMessage('Error saving items: ' + error.message);
                    return;
                }
            }
            
            let hasApprovalData = false;
            try {
                const reviewedByIdInput = document.getElementById('reviewedById');
                const approvedByIdInput = document.getElementById('approvedById');
                const confirmedByIdInput = document.getElementById('confirmedById');
                
                const reviewedBy = reviewedByIdInput?.value?.trim() || null;
                const approvedBy = approvedByIdInput?.value?.trim() || null;
                const confirmedBy = confirmedByIdInput?.value?.trim() || null;
                
                hasApprovalData = !!(reviewedBy || approvedBy || confirmedBy);
                
                if (hasApprovalData) {
                    await this.saveDraftApprovalOnly(prNumber);
                }
            } catch (error) {
                console.error('Error saving approval:', error);
                this.wizard.showValidationMessage('Error saving approval: ' + error.message);
                return;
            }
            
            try {
                await this.saveDraftAdditionalOnly(prNumber);
            } catch (error) {
                console.error('Error saving Additional data:', error);
                console.warn('Continuing save despite Additional save error');
            }
            
            const documentRows = document.querySelectorAll('#documentTable tbody tr');
            let hasDocuments = false;
            let documentsCount = 0;
            
            if (documentRows.length > 0) {
                hasDocuments = true;
                documentsCount = documentRows.length;
                
                try {
                    await this.saveDraftDocumentsOnly(prNumber);
                } catch (error) {
                    console.error('Error saving documents:', error);
                    this.wizard.showValidationMessage('Error saving documents: ' + error.message);
                    return;
                }
            }

            const itemCount = itemRows.length > 0 ? this.getItemsFromTable().length : 0;
            let messageParts = [];
            messageParts.push('Basic information');
            if (itemCount > 0) {
                messageParts.push(`${itemCount} item(s)`);
            }
            if (hasApprovalData) {
                messageParts.push('approval information');
            }
            if (hasDocuments) {
                messageParts.push(`${documentsCount} document(s)`);
            }
            messageParts.push(`have been saved. PR Number: ${prNumber}`);
            
            const message = `<strong>Draft Saved!</strong> ${messageParts.join(', ')}`;
            this.wizard.createAlert('success', message, 5000);
            
            setTimeout(() => {
                window.location.href = '/Procurement/PurchaseRequest/List';
            }, 1500);
        } catch (error) {
            console.error('Error saving draft documents:', error);
            this.wizard.showValidationMessage('Error saving draft: ' + error.message);
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    }

    /**
     * Save draft approval (with basic info and items)
     */
    async saveDraftApproval() {
        const saveBtn = document.getElementById('saveDraftApprovalBtn');
        if (!saveBtn) {
            console.error('saveDraftApprovalBtn button not found!');
            return;
        }
        
        const originalText = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="icon-base bx bx-loader-circle me-2 spin"></i>Saving...';

        try {
            let draft = JSON.parse(localStorage.getItem('procurementDraft') || '{}');
            let prNumber = draft.purchReqNumber;
            
            const saved = await this.saveDraftBasicInfo();
            if (!saved) {
                if (!prNumber) {
                    this.wizard.showValidationMessage('Failed to save basic information. Please try again.');
                    return;
                }
                console.warn('Basic info save failed, but PR Number exists. Continuing with existing PR Number.');
            } else {
                draft = JSON.parse(localStorage.getItem('procurementDraft') || '{}');
                prNumber = draft.purchReqNumber || prNumber;
            }
            
            if (!prNumber) {
                this.wizard.showValidationMessage('Failed to create purchase request. Please try again.');
                return;
            }
            
            const itemRows = document.querySelectorAll('#itemDetailTable tbody tr');
            if (itemRows.length > 0) {
                try {
                    await this.saveDraftItemsOnly(prNumber);
                } catch (error) {
                    console.error('Error saving items:', error);
                    this.wizard.showValidationMessage('Error saving items: ' + error.message);
                    return;
                }
            }
            
            try {
                await this.saveDraftAdditionalOnly(prNumber);
            } catch (error) {
                console.error('Error saving Additional data:', error);
                console.warn('Continuing save despite Additional save error');
            }
            
            try {
                await this.saveDraftApprovalOnly(prNumber);
            } catch (error) {
                console.error('Error saving approval:', error);
                this.wizard.showValidationMessage('Error saving approval: ' + error.message);
                return;
            }

            const itemCount = itemRows.length > 0 ? this.getItemsFromTable().length : 0;
            let messageParts = [];
            messageParts.push('Basic information');
            if (itemCount > 0) {
                messageParts.push(`${itemCount} item(s)`);
            }
            messageParts.push('approval information');
            messageParts.push(`have been saved. PR Number: ${prNumber}`);
            
            const message = `<strong>Draft Saved!</strong> ${messageParts.join(', ')}`;
            this.wizard.createAlert('success', message, 5000);
            
            setTimeout(() => {
                window.location.href = '/Procurement/PurchaseRequest/List';
            }, 1500);
        } catch (error) {
            console.error('Error saving draft approval:', error);
            this.wizard.showValidationMessage('Error saving draft: ' + error.message);
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    }

    /**
     * Save draft additional (with basic info)
     */
    async saveDraftAdditional() {
        const saveBtn = document.getElementById('saveDraftAdditionalBtn');
        if (!saveBtn) return;

        const originalText = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="icon-base bx bx-loader-circle me-2 spin"></i>Saving...';

        try {
            let draft = JSON.parse(localStorage.getItem('procurementDraft') || '{}');
            let prNumber = draft.purchReqNumber;
            
            const saved = await this.saveDraftBasicInfo(true);
            if (!saved) {
                if (!prNumber) {
                    this.wizard.showValidationMessage('Failed to save basic information. Please try again.');
                    return;
                }
                console.warn('Basic info save failed, but PR Number exists. Continuing with existing PR Number.');
            } else {
                draft = JSON.parse(localStorage.getItem('procurementDraft') || '{}');
                prNumber = draft.purchReqNumber || prNumber;
            }
            
            if (!prNumber) {
                this.wizard.showValidationMessage('Failed to create purchase request. Please try again.');
                return;
            }

            await this.saveDraftAdditionalOnly(prNumber);

            this.wizard.createAlert('success', `<strong>Draft Saved!</strong> Basic information and additional data have been saved. PR Number: ${prNumber}`, 5000);
            
            setTimeout(() => {
                window.location.href = '/Procurement/PurchaseRequest/List';
            }, 2000);
        } catch (error) {
            console.error('Error saving Additional data:', error);
            this.wizard.showValidationMessage('Error saving Additional data: ' + error.message);
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    }

    /**
     * Save all data: Basic Info + Items + Approval + Documents
     */
    async saveDraftAll() {
        const saveBtn = document.getElementById('saveDraftBtn');
        if (!saveBtn) return;
        
        const originalText = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="icon-base bx bx-loader-circle me-2 spin"></i>Saving...';

        try {
            let draft = JSON.parse(localStorage.getItem('procurementDraft') || '{}');
            let prNumber = draft.purchReqNumber;
            
            if (!prNumber) {
                const saved = await this.saveDraftBasicInfo(true);
                if (!saved) {
                    this.wizard.showValidationMessage('Failed to create purchase request. Please try again.');
                    return;
                }
                
                draft = JSON.parse(localStorage.getItem('procurementDraft') || '{}');
                prNumber = draft.purchReqNumber;
                
                if (!prNumber) {
                    this.wizard.showValidationMessage('Failed to create purchase request. Please try again.');
                    return;
                }
            }

            const itemsSaved = localStorage.getItem('procurementDraftItemsSaved') === 'true';
            if (!itemsSaved) {
                await this.saveDraftItemsOnly(prNumber);
            }

            const approvalSaved = localStorage.getItem('procurementDraftApprovalSaved') === 'true';
            if (!approvalSaved) {
                await this.saveDraftApprovalOnly(prNumber);
            }

            const additionalSaved = localStorage.getItem('procurementDraftAdditionalSaved') === 'true';
            if (!additionalSaved) {
                await this.saveDraftAdditionalOnly(prNumber);
            }

            const documentsSaved = localStorage.getItem('procurementDraftDocumentsSaved') === 'true';
            if (!documentsSaved) {
                await this.saveDraftDocumentsOnly(prNumber);
            }

            const itemCount = document.querySelectorAll('#itemDetailTable tbody tr').length > 0 ? this.getItemsFromTable().length : 0;
            const docCount = document.querySelectorAll('#documentTable tbody tr').length;
            
            let successMessage = 'Your Purchase Request has been saved successfully.';
            if (itemCount > 0 && docCount > 0) {
                successMessage = `Basic information, ${itemCount} item(s), approval information, and ${docCount} document(s) have been saved.`;
            } else if (itemCount > 0) {
                successMessage = `Basic information, ${itemCount} item(s), and approval information have been saved.`;
            } else if (docCount > 0) {
                successMessage = `Basic information, approval information, and ${docCount} document(s) have been saved.`;
            } else {
                successMessage = 'Basic information and approval information have been saved.';
            }
            
            this.wizard.createAlert('success', `<strong>Draft Saved!</strong> ${successMessage} PR Number: ${prNumber}`, 5000);
            
            setTimeout(() => {
                window.location.href = '/Procurement/PurchaseRequest/List';
            }, 1500);
        } catch (error) {
            console.error('Error saving draft:', error);
            this.wizard.showValidationMessage('Error saving draft: ' + error.message);
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    }

    /**
     * Submit PR: Save all and update status to Submitted
     */
    async submitPR() {
        const submitBtn = document.getElementById('submitPRBtn');
        if (!submitBtn) {
            console.error('submitPRBtn button not found!');
            return;
        }
        
        const confirmCheckbox = document.getElementById('confirmSubmission');
        if (!confirmCheckbox || !confirmCheckbox.checked) {
            if (typeof Swal !== 'undefined') {
                await Swal.fire({
                    title: 'Confirmation Required',
                    html: 'Please confirm that all information provided is accurate and complete before submitting.',
                    icon: 'warning',
                    confirmButtonColor: '#696cff',
                    confirmButtonText: 'OK',
                    allowOutsideClick: false,
                    allowEscapeKey: true,
                    animation: true,
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    }
                });
                
                if (confirmCheckbox) {
                    confirmCheckbox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    setTimeout(() => {
                        confirmCheckbox.focus();
                    }, 100);
                }
            } else {
                this.wizard.showValidationMessage('Please confirm that all information provided is accurate and complete before submitting.');
            }
            return;
        }
        
        // Validate all wizard steps
        if (this.wizard.validateAllWizardSteps && typeof this.wizard.validateAllWizardSteps === 'function') {
            const validationResult = this.wizard.validateAllWizardSteps();
            if (!validationResult.isValid) {
                if (typeof Swal !== 'undefined') {
                    await Swal.fire({
                        title: 'Validation Error',
                        html: validationResult.message,
                        icon: 'warning',
                        confirmButtonColor: '#696cff',
                        confirmButtonText: 'OK',
                        allowOutsideClick: false,
                        allowEscapeKey: true,
                        animation: true,
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        }
                    });
                    
                    if (validationResult.firstInvalidField && validationResult.firstInvalidField.step) {
                        this.wizard.navigateToInvalidField(validationResult.firstInvalidField);
                    }
                } else {
                    this.wizard.showValidationMessage(validationResult.message);
                }
                return;
            }
        }
        
        const originalText = submitBtn.innerHTML;
        const buttonWasDisabled = submitBtn.disabled;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="icon-base bx bx-loader-circle me-2 spin"></i>Submitting...';

        try {
            let draft = JSON.parse(localStorage.getItem('procurementDraft') || '{}');
            let prNumber = draft.purchReqNumber;
            
            const saved = await this.saveDraftBasicInfo(true);
            if (!saved) {
                if (!prNumber) {
                    this.wizard.showValidationMessage('Failed to save basic information. Please try again.');
                    return;
                }
                console.warn('Basic info save failed, but PR Number exists. Continuing with existing PR Number.');
            } else {
                draft = JSON.parse(localStorage.getItem('procurementDraft') || '{}');
                prNumber = draft.purchReqNumber || prNumber;
            }
            
            if (!prNumber) {
                this.wizard.showValidationMessage('Failed to create purchase request. Please try again.');
                return;
            }
            
            const itemRows = document.querySelectorAll('#itemDetailTable tbody tr');
            if (itemRows.length > 0) {
                try {
                    await this.saveDraftItemsOnly(prNumber);
                } catch (error) {
                    console.error('Step 2: Error saving items:', error);
                    this.wizard.showValidationMessage('Error saving items: ' + error.message);
                    return;
                }
            }
            
            try {
                await this.saveDraftApprovalOnly(prNumber);
            } catch (error) {
                console.error('Step 3: Error saving approval:', error);
                this.wizard.showValidationMessage('Error saving approval: ' + error.message);
                return;
            }
            
            try {
                await this.saveDraftAdditionalOnly(prNumber);
            } catch (error) {
                console.error('Step 3.5: Error saving Additional data:', error);
                console.warn('Continuing submission despite Additional save error');
            }
            
            const documentRows = document.querySelectorAll('#documentTable tbody tr');
            if (documentRows.length > 0) {
                try {
                    await this.saveDraftDocumentsOnly(prNumber);
                } catch (error) {
                    console.error('Step 4: Error saving documents:', error);
                    this.wizard.showValidationMessage('Error saving documents: ' + error.message);
                    return;
                }
            }
            
            // Update status to Submitted
            const formData = new FormData(this.wizard.cachedElements.form);
            const currentUserEmployeeID = (window.ProcurementConfig && window.ProcurementConfig.currentUserEmployeeID) || '';
            
            const applicantField = document.getElementById('Applicant');
            const companyField = document.getElementById('Company');
            const purchReqTypeField = document.getElementById('PurchaseRequestType');
            const purchReqSubTypeField = document.getElementById('PurchaseRequestSubType');
            const purchReqNameField = document.getElementById('PurchaseRequestName');
            const remarksField = document.getElementById('Remarks');
            
            const getFieldValue = (field) => {
                if (!field) return '';
                if (field.tagName === 'SELECT') {
                    return field.value || '';
                }
                return field.value || '';
            };
            
            const applicantValue = getFieldValue(applicantField) || formData.get('Applicant')?.toString().trim() || '';
            const companyValue = (getFieldValue(companyField) || formData.get('Company')?.toString().trim() || '').toUpperCase();
            const purchReqTypeValue = getFieldValue(purchReqTypeField) || formData.get('PurchaseRequestType')?.toString().trim() || '';
            const purchReqSubTypeValue = getFieldValue(purchReqSubTypeField) || formData.get('PurchaseRequestSubType')?.toString().trim() || '';
            const purchReqNameValue = getFieldValue(purchReqNameField) || formData.get('PurchaseRequestName')?.toString().trim() || '';
            const remarkValue = getFieldValue(remarksField) || formData.get('Remarks')?.toString().trim() || '';
            
            const updatePayload = {
                Requestor: currentUserEmployeeID || '',
                Applicant: applicantValue,
                Company: companyValue,
                PurchReqType: purchReqTypeValue,
                PurchReqSubType: purchReqSubTypeValue,
                PurchReqName: purchReqNameValue,
                Remark: remarkValue,
                mstApprovalStatusID: 1 // Submitted
            };
            
            if (!applicantValue || !companyValue || !purchReqTypeValue || !purchReqSubTypeValue || !purchReqNameValue || !remarkValue) {
                const missingFields = [];
                if (!applicantValue) missingFields.push('Applicant');
                if (!companyValue) missingFields.push('Company');
                if (!purchReqTypeValue) missingFields.push('Purchase Request Type');
                if (!purchReqSubTypeValue) missingFields.push('Purchase Request Sub Type');
                if (!purchReqNameValue) missingFields.push('Purchase Request Name');
                if (!remarkValue) missingFields.push('Remarks');
                
                this.wizard.showValidationMessage(`Please fill in all required fields: ${missingFields.join(', ')}`);
                submitBtn.disabled = buttonWasDisabled;
                submitBtn.innerHTML = originalText;
                return;
            }
            
            try {
                await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequests/${encodeURIComponent(prNumber)}`, 'PUT', updatePayload);
            } catch (error) {
                console.error('Error submitting PR:', error);
                this.wizard.showValidationMessage('Error submitting PR: ' + error.message);
                return;
            }
            
            const itemCount = itemRows.length > 0 ? this.getItemsFromTable().length : 0;
            const docCount = documentRows.length;
            
            let successMessage = 'Your Purchase Request has been submitted successfully.';
            if (itemCount > 0 && docCount > 0) {
                successMessage = `Basic information, ${itemCount} item(s), approval information, and ${docCount} document(s) have been saved.`;
            } else if (itemCount > 0) {
                successMessage = `Basic information, ${itemCount} item(s), and approval information have been saved.`;
            } else if (docCount > 0) {
                successMessage = `Basic information, approval information, and ${docCount} document(s) have been saved.`;
            } else {
                successMessage = 'Basic information and approval information have been saved.';
            }
            
            this.wizard.showSuccessModal(
                'PR Submitted Successfully!',
                successMessage,
                prNumber,
                () => {
                    window.location.href = '/Procurement/PurchaseRequest/List';
                }
            );
        } catch (error) {
            console.error('Error submitting PR:', error);
            this.wizard.showValidationMessage('Error submitting PR: ' + error.message);
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.ProcurementWizardSave = ProcurementWizardSave;
}

