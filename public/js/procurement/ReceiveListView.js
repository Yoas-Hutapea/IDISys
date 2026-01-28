/**
 * ReceiveListView Module
 * Handles view receive functionality, submit receive, and bulk receive
 */
class ReceiveListView {
    constructor(managerInstance) {
        this.manager = managerInstance;
        this.viewPRItems = [];
        this.viewPRDocuments = [];
        this.viewPRAdditional = null;
        this.currentPRNumber = null;
    }

    /**
     * View receive details
     */
    async viewReceive(prNumber) {
        // Store currentPRNumber in both viewModule and manager for backward compatibility
        this.currentPRNumber = prNumber;
        if (this.manager) {
            this.manager.currentPRNumber = prNumber;
        }
        
        try {
            this.showViewReceiveLoading();
            this.hideListSection();
            
            // Load PR data using API module
            if (!this.manager || !this.manager.apiModule) {
                throw new Error('API module not available');
            }

            const pr = await this.manager.apiModule.getPRDetails(prNumber);
            
            if (!pr) {
                this.showError('Purchase Request not found');
                this.showListSection();
                return;
            }
            
            await this.loadPRDetailsForView(prNumber, pr);
            await this.populateViewReceive(pr);
            this.showViewReceiveSection();

        } catch (error) {
            console.error('Error loading receive:', error);
            this.showError('Failed to load Purchase Request: ' + error.message);
            this.showListSection();
        }
    }

    /**
     * Hide list section
     */
    hideListSection() {
        const filterSection = document.querySelector('.filter-section');
        const listSection = document.getElementById('listSection');
        
        if (filterSection) filterSection.style.display = 'none';
        if (listSection) listSection.style.display = 'none';
    }

    /**
     * Show list section
     */
    showListSection() {
        const filterSection = document.querySelector('.filter-section');
        const listSection = document.getElementById('listSection');
        const viewReceiveSection = document.getElementById('viewReceiveSection');
        
        if (filterSection) filterSection.style.display = 'block';
        if (listSection) listSection.style.display = 'block';
        if (viewReceiveSection) viewReceiveSection.style.display = 'none';
    }

    /**
     * Show view receive section
     */
    showViewReceiveSection() {
        const viewReceiveSection = document.getElementById('viewReceiveSection');
        if (viewReceiveSection) {
            viewReceiveSection.style.display = 'block';
        }
    }

    /**
     * Show view receive loading
     */
    showViewReceiveLoading() {
        const viewReceiveSection = document.getElementById('viewReceiveSection');
        const loadingDiv = document.getElementById('viewReceiveLoading');
        const dataDiv = document.getElementById('viewReceiveData');
        
        if (loadingDiv) loadingDiv.style.display = 'block';
        if (dataDiv) dataDiv.style.display = 'none';
        
        if (viewReceiveSection) {
            viewReceiveSection.style.display = 'block';
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
        // Type ID 8 && Sub Type ID 4
        if (typeId === 8 && subTypeId === 4) return true;
        // Type ID 2 && (Sub Type ID 1 || 3)
        if (typeId === 2 && (subTypeId === 1 || subTypeId === 3)) return true;
        // Type ID 4 && Sub Type ID 3
        if (typeId === 4 && subTypeId === 3) return true;
        // Type ID 3 && (Sub Type ID 4 || 5)
        if (typeId === 3 && (subTypeId === 4 || subTypeId === 5)) return true;
        
        return false;
    }

    /**
     * Load PR details (items, documents, additional) using API module
     */
    async loadPRDetailsForView(prNumber, pr = null) {
        if (!this.manager || !this.manager.apiModule) {
            console.error('API module not available');
            return;
        }

        // Load items
        try {
            const items = await this.manager.apiModule.getPRItems(prNumber);
            this.viewPRItems = Array.isArray(items) ? items : [];
        } catch (error) {
            console.error('Error loading items:', error);
            this.viewPRItems = [];
        }

        // Load documents
        try {
            const documents = await this.manager.apiModule.getPRDocuments(prNumber);
            this.viewPRDocuments = Array.isArray(documents) ? documents : [];
        } catch (error) {
            console.error('Error loading documents:', error);
            this.viewPRDocuments = [];
        }

        // Only load additional data if Type ID and SubType ID require Additional Section
        let shouldLoadAdditional = false;
        let typeId = null;
        let subTypeId = null;
        
        if (pr) {
            // First, try to get Type ID and SubType ID directly from PR data (preferred method)
            const directTypeID = pr.mstPurchaseTypeID || pr.MstPurchaseTypeID;
            const directSubTypeID = pr.mstPurchaseSubTypeID || pr.MstPurchaseSubTypeID;
            
            if (directTypeID) {
                const typeIdInt = parseInt(directTypeID.toString().trim(), 10);
                if (!isNaN(typeIdInt) && typeIdInt > 0) {
                    typeId = typeIdInt;
                }
            }
            
            if (directSubTypeID) {
                const subTypeIdInt = parseInt(directSubTypeID.toString().trim(), 10);
                if (!isNaN(subTypeIdInt) && subTypeIdInt > 0) {
                    subTypeId = subTypeIdInt;
                }
            }
            
            // Fallback: If Type ID or SubType ID not found directly, try lookup from formatted strings
            if (!typeId || !subTypeId) {
                const purchReqType = pr.purchReqType || pr.PurchReqType || '';
                const purchReqSubType = pr.purchReqSubType || pr.PurchReqSubType || '';
                
                // Load Purchase Types using API module (with shared caching)
                if (purchReqType) {
                    try {
                        const allPurchaseTypes = await this.manager.apiModule.getPurchaseTypes();
                        
                        // Find Type ID if not already found
                        if (!typeId && allPurchaseTypes && purchReqType) {
                            let type = null;
                            
                            // First, try to find by matching PurchaseRequestType or formatted display
                            type = allPurchaseTypes.find(t => {
                                const typeValue = t.PurchaseRequestType || t.purchaseRequestType || '';
                                const category = t.Category || t.category || '';
                                const formattedDisplay = category && typeValue !== category 
                                    ? `${typeValue} ${category}` 
                                    : typeValue;
                                return typeValue === purchReqType || formattedDisplay === purchReqType;
                            });
                            
                            // If not found, try to parse as ID
                            if (!type) {
                                const typeIdInt = parseInt(purchReqType.trim(), 10);
                                if (!isNaN(typeIdInt) && typeIdInt > 0) {
                                    type = allPurchaseTypes.find(t => 
                                        parseInt(t.ID || t.id || '0', 10) === typeIdInt
                                    );
                                }
                            }
                            
                            if (type) {
                                typeId = parseInt(type.ID || type.id || '0', 10);
                            }
                        }
                        
                        // Load Purchase Sub Types if Type ID is found and SubType ID not found
                        if (typeId && !subTypeId && purchReqSubType) {
                            try {
                                const subTypes = await this.manager.apiModule.getPurchaseSubTypes(typeId);
                                
                                // purchReqSubType might be formatted display or ID
                                let subType = null;
                                
                                // First, try to find by matching PurchaseRequestSubType
                                subType = subTypes.find(st => 
                                    (st.PurchaseRequestSubType || st.purchaseRequestSubType) === purchReqSubType
                                );
                                
                                // If not found, try to parse as ID
                                if (!subType) {
                                    const subTypeIdInt = parseInt(purchReqSubType.trim(), 10);
                                    if (!isNaN(subTypeIdInt) && subTypeIdInt > 0) {
                                        subType = subTypes.find(st => 
                                            parseInt(st.ID || st.id || '0', 10) === subTypeIdInt
                                        );
                                    }
                                }
                                
                                if (subType) {
                                    subTypeId = parseInt(subType.ID || subType.id || '0', 10);
                                }
                            } catch (error) {
                                console.error('Error loading purchase sub types:', error);
                            }
                        }
                    } catch (error) {
                        console.error('Error loading purchase types:', error);
                    }
                }
            }
            
            // Check if Additional Section is required
            shouldLoadAdditional = this.requiresAdditionalSection(typeId, subTypeId);
        }
        
        // Load additional data only if required (using API module with caching)
        if (shouldLoadAdditional) {
            try {
                const additional = await this.manager.apiModule.getPRAdditional(prNumber);
                this.viewPRAdditional = additional || null;
            } catch (error) {
                // If 404 error or "not found" message, it's okay - additional data doesn't exist for this PR
                const errorMessage = error.message || error.toString() || '';
                if (errorMessage.includes('not found') || errorMessage.includes('404') || 
                    (error.statusCode === 404) || (error.response && error.response.status === 404)) {
                    console.log('Additional data not found for PR:', prNumber, '- This is expected if PR does not have Additional data yet');
                    this.viewPRAdditional = null;
                } else {
                    console.error('Error loading additional data:', error);
                    this.viewPRAdditional = null;
                }
            }
        } else {
            // Don't load additional data if not required
            this.viewPRAdditional = null;
            console.log('Additional Section not required for Type ID:', typeId, 'SubType ID:', subTypeId, '- Skipping API call');
        }
    }

    /**
     * Populate view receive with PR data
     */
    async populateViewReceive(pr) {
        const loadingDiv = document.getElementById('viewReceiveLoading');
        const dataDiv = document.getElementById('viewReceiveData');
        
        if (loadingDiv) loadingDiv.style.display = 'none';
        if (dataDiv) dataDiv.style.display = 'block';

        // Get employee names using shared cache or employee cache module
        const requestorId = pr.requestor || pr.Requestor || '';
        const applicantId = pr.applicant || pr.Applicant || '';
        const reviewedById = pr.reviewedBy || pr.ReviewedBy || '';
        const approvedById = pr.approvedBy || pr.ApprovedBy || '';
        const confirmedById = pr.confirmedBy || pr.ConfirmedBy || '';
        
        // Use shared cache or employee cache module if available
        let employeeCache = null;
        if (window.procurementSharedCache && window.procurementSharedCache.getEmployeeNameByEmployId) {
            employeeCache = window.procurementSharedCache;
        } else if (this.manager && this.manager.employeeCacheModule && this.manager.employeeCacheModule.getEmployeeNameByEmployId) {
            employeeCache = this.manager.employeeCacheModule;
        } else if (window.prListManager && window.prListManager.employeeCacheModule && window.prListManager.employeeCacheModule.getEmployeeNameByEmployId) {
            employeeCache = window.prListManager.employeeCacheModule;
        }
        
        const requestorName = requestorId && employeeCache ? await employeeCache.getEmployeeNameByEmployId(requestorId) : '';
        const applicantName = applicantId && employeeCache ? await employeeCache.getEmployeeNameByEmployId(applicantId) : '';
        const reviewedByName = reviewedById && employeeCache ? await employeeCache.getEmployeeNameByEmployId(reviewedById) : '';
        const approvedByName = approvedById && employeeCache ? await employeeCache.getEmployeeNameByEmployId(approvedById) : '';
        const confirmedByName = confirmedById && employeeCache ? await employeeCache.getEmployeeNameByEmployId(confirmedById) : '';

        // Calculate total amount
        const totalAmount = this.viewPRItems && this.viewPRItems.length > 0
            ? this.viewPRItems.reduce((sum, item) => sum + (parseFloat(item.Amount || item.amount || 0) || 0), 0)
            : 0;

        // Populate basic information - use .value for input/textarea elements
        const requestorEl = document.getElementById('view-requestor');
        const applicantEl = document.getElementById('view-applicant');
        const companyEl = document.getElementById('view-company');
        const purchReqTypeEl = document.getElementById('view-purchase-request-type');
        const purchReqSubTypeEl = document.getElementById('view-purchase-request-sub-type');
        const purchReqNameEl = document.getElementById('view-purchase-request-name');
        const remarksEl = document.getElementById('view-remarks');
        const amountTotalEl = document.getElementById('view-amount-total');
        
        if (requestorEl) requestorEl.value = requestorName || requestorId || '-';
        if (applicantEl) applicantEl.value = applicantName || applicantId || '-';
        if (companyEl) companyEl.value = pr.company || pr.Company || '-';
        if (purchReqTypeEl) purchReqTypeEl.value = pr.purchReqType || pr.PurchReqType || '-';
        if (purchReqSubTypeEl) purchReqSubTypeEl.value = pr.purchReqSubType || pr.PurchReqSubType || '-';
        if (purchReqNameEl) purchReqNameEl.value = pr.purchReqName || pr.PurchReqName || '-';
        if (remarksEl) remarksEl.value = pr.remark || pr.Remark || '-';
        if (amountTotalEl) amountTotalEl.value = this.formatCurrency(totalAmount);

        // Populate approval assignment - use .value for input elements
        const approvalRequestorEl = document.getElementById('view-approval-requestor');
        const approvalApplicantEl = document.getElementById('view-approval-applicant');
        const reviewedByEl = document.getElementById('view-reviewed-by');
        const approvedByEl = document.getElementById('view-approved-by');
        const confirmedByEl = document.getElementById('view-confirmed-by');
        
        if (approvalRequestorEl) approvalRequestorEl.value = requestorName || requestorId || '-';
        if (approvalApplicantEl) approvalApplicantEl.value = applicantName || applicantId || '-';
        if (reviewedByEl) reviewedByEl.value = reviewedByName || reviewedById || '-';
        if (approvedByEl) approvedByEl.value = approvedByName || approvedById || '-';
        if (confirmedByEl) confirmedByEl.value = confirmedByName || confirmedById || '-';

        // Populate items table
        const itemsTbody = document.getElementById('view-items-tbody');
        if (itemsTbody) {
            if (this.viewPRItems && this.viewPRItems.length > 0) {
                itemsTbody.innerHTML = this.viewPRItems.map(item => {
                    const itemId = item.mstPROPurchaseItemInventoryItemID || item.mstPROInventoryItemID || item.MstPROPurchaseItemInventoryItemID || item.MstPROInventoryItemID || item.ItemID || item.itemID || '-';
                    const itemName = item.ItemName || item.itemName || '-';
                    const description = item.ItemDescription || item.itemDescription || '-';
                    const uom = item.ItemUnit || item.itemUnit || '-';
                    const quantity = item.ItemQty || item.itemQty || item.Quantity || item.quantity || '-';
                    const currencyCode = item.ItemCurrency || item.itemCurrency || item.CurrencyCode || item.currencyCode || '-';
                    const unitPrice = item.ItemUnitPrice ?? item.itemUnitPrice ?? item.UnitPrice ?? item.unitPrice ?? null;
                    const amount = item.Amount ?? item.amount ?? null;
                    
                    return `
                        <tr>
                            <td>${this.escapeHtml(itemId)}</td>
                            <td>${this.escapeHtml(itemName)}</td>
                            <td>${this.escapeHtml(description)}</td>
                            <td>${this.escapeHtml(uom)}</td>
                            <td>${this.escapeHtml(quantity)}</td>
                            <td>${this.escapeHtml(currencyCode)}</td>
                            <td>${this.formatCurrency(unitPrice)}</td>
                            <td>${this.formatCurrency(amount)}</td>
                        </tr>
                    `;
                }).join('');
            } else {
                itemsTbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No items</td></tr>';
            }
        }

        // Populate documents table
        const documentsTbody = document.getElementById('view-documents-tbody');
        if (documentsTbody) {
            if (this.viewPRDocuments && this.viewPRDocuments.length > 0) {
                documentsTbody.innerHTML = this.viewPRDocuments.map(doc => {
                    const docId = doc.id || doc.ID || 0;
                    const fileName = doc.fileName || doc.FileName || '-';
                    const filePath = doc.filePath || doc.FilePath || '';
                    const fileSize = doc.fileSize || doc.FileSize || '-';
                    const escapedFileName = this.escapeHtml(fileName);
                    const escapedFilePath = this.escapeHtml(filePath);
                    
                    return `
                        <tr>
                            <td>${escapedFileName}</td>
                            <td>${this.escapeHtml(fileSize)}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary" title="Download" onclick="downloadDocument.call(this, ${docId})" data-file-name="${escapedFileName}" data-file-path="${escapedFilePath}">
                                    <i class="icon-base bx bx-download"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');
            } else {
                documentsTbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No documents</td></tr>';
            }
        }

        // Populate Additional Section
        await this.populateAdditionalSection(pr);

        // Reset receive form
        const receiveDecision = document.getElementById('receiveDecision');
        const receiveRemarks = document.getElementById('receiveRemarks');
        if (receiveDecision) {
            receiveDecision.value = '';
            // Clear error message when user selects a decision
            receiveDecision.addEventListener('change', () => {
                const decisionError = document.getElementById('receiveDecisionError');
                if (decisionError) {
                    decisionError.style.display = 'none';
                    decisionError.textContent = '';
                }
            });
        }
        if (receiveRemarks) {
            receiveRemarks.value = '';
            // Clear error message when user starts typing
            receiveRemarks.addEventListener('input', () => {
                const remarksError = document.getElementById('receiveRemarksError');
                if (remarksError) {
                    remarksError.style.display = 'none';
                    remarksError.textContent = '';
                }
            });
        }
    }

    /**
     * Get additional information summary HTML
     */
    async getAdditionalInformationSummaryHTML(pr) {
        // Check if additional data exists
        if (!this.viewPRAdditional) {
            return '';
        }
        
        const additional = this.viewPRAdditional;
        
        // Get Type ID and Sub Type ID from PR data by looking up from master data
        let typeId = null;
        let subTypeId = null;
        
        const purchReqType = pr.purchReqType || pr.PurchReqType || '';
        const purchReqSubType = pr.purchReqSubType || pr.PurchReqSubType || '';
        
        // Load Purchase Types using API module (with caching)
        if (purchReqType && this.manager && this.manager.apiModule) {
            try {
                const allPurchaseTypes = await this.manager.apiModule.getPurchaseTypes();
                
                // Find Type ID
                if (allPurchaseTypes && purchReqType) {
                    let type = null;
                    
                    type = allPurchaseTypes.find(t => {
                        const typeValue = t.PurchaseRequestType || t.purchaseRequestType || '';
                        const category = t.Category || t.category || '';
                        const formattedDisplay = category && typeValue !== category 
                            ? `${typeValue} ${category}` 
                            : typeValue;
                        return typeValue === purchReqType || formattedDisplay === purchReqType;
                    });
                    
                    if (!type) {
                        const typeIdInt = parseInt(purchReqType.trim(), 10);
                        if (!isNaN(typeIdInt) && typeIdInt > 0) {
                            type = allPurchaseTypes.find(t => 
                                parseInt(t.ID || t.id || '0', 10) === typeIdInt
                            );
                        }
                    }
                    
                    if (type) {
                        typeId = parseInt(type.ID || type.id || '0', 10);
                    }
                }
                
                // Load Purchase Sub Types if Type ID is found
                if (typeId && purchReqSubType && this.manager && this.manager.apiModule) {
                    try {
                        const subTypes = await this.manager.apiModule.getPurchaseSubTypes(typeId);
                        
                        let subType = null;
                        
                        subType = subTypes.find(st => 
                            (st.PurchaseRequestSubType || st.purchaseRequestSubType) === purchReqSubType
                        );
                        
                        if (!subType) {
                            const subTypeIdInt = parseInt(purchReqSubType.trim(), 10);
                            if (!isNaN(subTypeIdInt) && subTypeIdInt > 0) {
                                subType = subTypes.find(st => 
                                    parseInt(st.ID || st.id || '0', 10) === subTypeIdInt
                                );
                            }
                        }
                        
                        if (subType) {
                            subTypeId = parseInt(subType.ID || subType.id || '0', 10);
                        }
                    } catch (error) {
                        console.error('Error loading purchase sub types:', error);
                    }
                }
            } catch (error) {
                console.error('Error loading purchase types:', error);
            }
        }
        
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
            return '';
        }
        
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
        
        // Build summary HTML based on active section
        let summaryHTML = '<div class="row g-3">';
        
        // Billing Type Section (Type ID 6, Sub Type ID 2)
        if (shouldShowBillingTypeSection) {
            const billingTypeName = additional.billingTypeName || additional.BillingTypeName || '-';
            const startPeriod = formatDate(additional.startPeriod || additional.StartPeriod);
            const period = additional.period || additional.Period || '-';
            const endPeriod = formatDate(additional.endPeriod || additional.EndPeriod);
            
            summaryHTML += `
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Billing Type</label>
                    <input type="text" class="form-control" value="${this.escapeHtml(billingTypeName)}" disabled>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Period</label>
                    <input type="text" class="form-control" value="${this.escapeHtml(period)}" disabled>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Start Period</label>
                    <input type="text" class="form-control" value="${this.escapeHtml(startPeriod)}" disabled>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">End Period</label>
                    <input type="text" class="form-control" value="${this.escapeHtml(endPeriod)}" disabled>
                </div>
            `;
        }
        // Sonumb Section (Type ID 2,3,4,8)
        else if (shouldShowSonumbSection) {
            const sonumb = additional.sonumb || additional.Sonumb || '-';
            const siteName = additional.siteName || additional.SiteName || '-';
            const siteID = additional.siteID || additional.SiteID || '-';
            
            summaryHTML += `
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Sonumb</label>
                    <input type="text" class="form-control" value="${this.escapeHtml(sonumb)}" disabled>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Site Name</label>
                    <input type="text" class="form-control" value="${this.escapeHtml(siteName)}" disabled>
                </div>
                ${siteID && siteID !== '-' ? `
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Site ID</label>
                    <input type="text" class="form-control" value="${this.escapeHtml(siteID)}" disabled>
                </div>
                ` : ''}
            `;
        }
        // Subscribe Section (Type ID 5 or 7)
        else if (shouldShowSubscribeSection) {
            const subscribeSonumb = additional.sonumb || additional.Sonumb || '-';
            const subscribeSiteName = additional.siteName || additional.SiteName || '-';
            const subscribeSiteID = additional.siteID || additional.SiteID || '-';
            const subscribeBillingTypeName = additional.billingTypeName || additional.BillingTypeName || '-';
            const subscribeStartPeriod = formatDate(additional.startPeriod || additional.StartPeriod);
            const subscribePeriod = additional.period || additional.Period || '-';
            const subscribeEndPeriod = formatDate(additional.endPeriod || additional.EndPeriod);
            
            summaryHTML += `
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Sonumb</label>
                    <input type="text" class="form-control" value="${this.escapeHtml(subscribeSonumb)}" disabled>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Site Name</label>
                    <input type="text" class="form-control" value="${this.escapeHtml(subscribeSiteName)}" disabled>
                </div>
                ${subscribeSiteID && subscribeSiteID !== '-' ? `
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Site ID</label>
                    <input type="text" class="form-control" value="${this.escapeHtml(subscribeSiteID)}" disabled>
                </div>
                ` : ''}
                ${subscribeBillingTypeName && subscribeBillingTypeName !== '-' ? `
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Billing Type</label>
                    <input type="text" class="form-control" value="${this.escapeHtml(subscribeBillingTypeName)}" disabled>
                </div>
                ` : ''}
                ${subscribeStartPeriod && subscribeStartPeriod !== '-' ? `
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Start Period</label>
                    <input type="text" class="form-control" value="${this.escapeHtml(subscribeStartPeriod)}" disabled>
                </div>
                ` : ''}
                ${subscribePeriod && subscribePeriod !== '-' ? `
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Period</label>
                    <input type="text" class="form-control" value="${this.escapeHtml(subscribePeriod)}" disabled>
                </div>
                ` : ''}
                ${subscribeEndPeriod && subscribeEndPeriod !== '-' ? `
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">End Period</label>
                    <input type="text" class="form-control" value="${this.escapeHtml(subscribeEndPeriod)}" disabled>
                </div>
                ` : ''}
            `;
        }
        
        summaryHTML += '</div>';
        
        return summaryHTML;
    }

    /**
     * Populate additional section
     */
    async populateAdditionalSection(pr) {
        const additionalSection = document.getElementById('view-additional-section');
        const additionalBody = document.getElementById('view-additional-body');
        
        if (!additionalSection || !additionalBody) return;
        
        // Get additional information HTML
        const additionalHTML = await this.getAdditionalInformationSummaryHTML(pr);
        
        if (additionalHTML && additionalHTML.trim() !== '') {
            additionalBody.innerHTML = additionalHTML;
            additionalSection.style.display = 'block';
        } else {
            additionalSection.style.display = 'none';
        }
    }

    /**
     * Submit receive (single PR)
     */
    async submitReceive() {
        if (!this.currentPRNumber) {
            this.showAlertModal('No Purchase Request selected', 'warning');
            return;
        }

        const decisionSelect = document.getElementById('receiveDecision');
        const remarksTextarea = document.getElementById('receiveRemarks');

        // Clear previous error messages
        const decisionError = document.getElementById('receiveDecisionError');
        const remarksError = document.getElementById('receiveRemarksError');
        if (decisionError) {
            decisionError.style.display = 'none';
            decisionError.textContent = '';
        }
        if (remarksError) {
            remarksError.style.display = 'none';
            remarksError.textContent = '';
        }

        let hasError = false;

        // Validate Decision first (topmost field)
        if (!decisionSelect || !decisionSelect.value || decisionSelect.value.trim() === '') {
            if (decisionError) {
                decisionError.textContent = 'Please select an action (Receive or Reject)';
                decisionError.style.display = 'block';
            }
            if (decisionSelect) {
                decisionSelect.focus();
                decisionSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            hasError = true;
        }

        const decision = decisionSelect ? decisionSelect.value.trim() : '';

        if (!hasError && decision !== 'Receive' && decision !== 'Reject') {
            if (decisionError) {
                decisionError.textContent = 'Please select an action (Receive or Reject)';
                decisionError.style.display = 'block';
            }
            if (decisionSelect) {
                decisionSelect.focus();
                decisionSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            hasError = true;
        }

        // Validate Remarks (required field)
        if (!remarksTextarea || !remarksTextarea.value || remarksTextarea.value.trim() === '') {
            if (remarksError) {
                remarksError.textContent = 'Please fill the Remarks Field';
                remarksError.style.display = 'block';
            }
            if (remarksTextarea) {
                remarksTextarea.focus();
                remarksTextarea.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            hasError = true;
        }

        if (hasError) {
            return;
        }

        const remarks = remarksTextarea.value.trim();

        // Determine status ID and activity based on decision
        const statusID = decision === 'Receive' ? 7 : 5; // 7 for Receive, 5 for Reject
        const activity = decision === 'Receive' ? 'Receive Purchase Request' : 'Reject Purchase Request';
        const actionText = decision === 'Receive' ? 'RECEIVE' : 'REJECT';
        const successMessage = decision === 'Receive' 
            ? 'Purchase Request has been received successfully'
            : 'Purchase Request has been rejected successfully';

        // Show confirmation modal
        const confirmed = await this.showConfirmModal(
            `Are you sure you want to ${actionText} this Purchase Request?`,
            `This action will ${decision.toLowerCase()} the Purchase Request ${this.currentPRNumber}. This action cannot be undone.`
        );

        if (confirmed) {
            // Get submit button and set loading state
            const submitBtn = document.getElementById('submitReceiveBtn');
            let originalText = '';
            let buttonWasDisabled = false;
            if (submitBtn) {
                originalText = submitBtn.innerHTML;
                buttonWasDisabled = submitBtn.disabled;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="icon-base bx bx-loader-circle me-2 spin"></i>Submitting...';
            }

            try {
                const dto = {
                    trxPROPurchaseRequestNumber: this.currentPRNumber,
                    mstApprovalStatusID: statusID,
                    Remark: remarks,
                    Decision: decision,
                    Activity: activity
                };

                if (!this.manager || !this.manager.apiModule) {
                    throw new Error('API module not available');
                }

                await this.manager.apiModule.submitReceive(this.currentPRNumber, dto);

                this.showAlertModal(
                    successMessage,
                    'success',
                    () => {
                        // Reload page to Receive PR List
                        window.location.href = '/Procurement/PurchaseRequest/Receive';
                    }
                );

            } catch (error) {
                console.error('Error submitting receive:', error);
                this.showAlertModal('Failed to submit receive: ' + (error.message || 'Unknown error'), 'danger');
            } finally {
                // Restore button state
                if (submitBtn) {
                    submitBtn.disabled = buttonWasDisabled;
                    submitBtn.innerHTML = originalText;
                }
            }
        }
    }

    /**
     * Process bulk receive
     */
    async processBulkReceive() {
        if (!this.manager || !this.manager.selectedPRNumbers || this.manager.selectedPRNumbers.size === 0) {
            this.showAlertModal('Please select at least one Purchase Request', 'warning');
            return;
        }

        // Show bulk receive modal
        const result = await this.showBulkReceiveModal();
        
        if (result && result.confirmed) {
            const { decision, remarks } = result;
            
            if (decision !== 'Receive' && decision !== 'Reject') {
                this.showAlertModal('Invalid decision selected', 'warning');
                return;
            }

            try {
                const prNumbers = Array.from(this.manager.selectedPRNumbers);
                const statusID = decision === 'Receive' ? 7 : 5; // 7 for Receive, 5 for Reject
                const activity = decision === 'Receive' ? 'Receive Purchase Request' : 'Reject Purchase Request';
                const successMessage = decision === 'Receive'
                    ? `${prNumbers.length} Purchase Request(s) have been received successfully`
                    : `${prNumbers.length} Purchase Request(s) have been rejected successfully`;

                const dto = {
                    prNumbers: prNumbers,
                    mstApprovalStatusID: statusID,
                    Remark: remarks || '',
                    Decision: decision,
                    Activity: activity
                };

                if (!this.manager || !this.manager.apiModule) {
                    throw new Error('API module not available');
                }

                await this.manager.apiModule.submitBulkReceive(dto);

                this.showAlertModal(
                    successMessage,
                    'success',
                    () => {
                        // Reload page to Receive PR List
                        window.location.href = '/Procurement/PurchaseRequest/Receive';
                    }
                );

            } catch (error) {
                console.error('Error processing bulk receive:', error);
                this.showAlertModal('Failed to process bulk receive: ' + (error.message || 'Unknown error'), 'danger');
            }
        }
    }

    /**
     * Show bulk receive modal
     */
    showBulkReceiveModal() {
        return new Promise((resolve) => {
            const modalId = 'bulkReceiveModal';
            let existingModal = document.getElementById(modalId);
            
            if (existingModal) {
                existingModal.remove();
            }
            
            const selectedCount = this.manager && this.manager.selectedPRNumbers ? this.manager.selectedPRNumbers.size : 0;
            
            const modalHtml = `
                <div class="modal fade" id="${modalId}" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title">Bulk Process Purchase Requests</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body py-4">
                                <p class="mb-3">You are about to process <strong>${selectedCount}</strong> Purchase Request(s).</p>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Decision<span class="text-danger">*</span></label>
                                    <select class="form-select" id="bulkReceiveDecision">
                                        <option value="" disabled selected>Select Decision</option>
                                        <option value="Receive">Receive</option>
                                        <option value="Reject">Reject</option>
                                    </select>
                                    <div id="bulkReceiveDecisionError" class="text-danger mt-1" style="display: none;"></div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Remarks<span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="bulkReceiveRemarks" rows="4" placeholder="Enter remarks"></textarea>
                                    <div id="bulkReceiveRemarksError" class="text-danger mt-1" style="display: none;"></div>
                                </div>
                            </div>
                            <div class="modal-footer border-0 justify-content-center gap-2">
                                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                    Cancel
                                </button>
                                <button type="button" class="btn btn-primary" id="confirmBulkReceiveBtn">
                                    <i class="icon-base bx bx-check me-2"></i>Submit
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modal = new bootstrap.Modal(document.getElementById(modalId));
            
            const confirmBtn = document.getElementById('confirmBulkReceiveBtn');
            const cancelBtn = document.querySelector(`#${modalId} .btn-label-secondary`);
            const decisionSelect = document.getElementById('bulkReceiveDecision');
            const remarksTextarea = document.getElementById('bulkReceiveRemarks');
            
            // Add event listeners to clear errors when user starts typing/selecting
            if (decisionSelect) {
                decisionSelect.addEventListener('change', () => {
                    const decisionError = document.getElementById('bulkReceiveDecisionError');
                    if (decisionError) {
                        decisionError.style.display = 'none';
                        decisionError.textContent = '';
                    }
                });
            }
            if (remarksTextarea) {
                remarksTextarea.addEventListener('input', () => {
                    const remarksError = document.getElementById('bulkReceiveRemarksError');
                    if (remarksError) {
                        remarksError.style.display = 'none';
                        remarksError.textContent = '';
                    }
                });
            }
            
            if (confirmBtn) {
                confirmBtn.addEventListener('click', () => {
                    const decisionSelect = document.getElementById('bulkReceiveDecision');
                    const remarksTextarea = document.getElementById('bulkReceiveRemarks');
                    const decisionError = document.getElementById('bulkReceiveDecisionError');
                    const remarksError = document.getElementById('bulkReceiveRemarksError');
                    
                    // Clear previous error messages
                    if (decisionError) {
                        decisionError.style.display = 'none';
                        decisionError.textContent = '';
                    }
                    if (remarksError) {
                        remarksError.style.display = 'none';
                        remarksError.textContent = '';
                    }
                    
                    let hasError = false;
                    
                    // Validate Decision
                    const decision = decisionSelect ? decisionSelect.value : '';
                    if (!decision || decision.trim() === '') {
                        if (decisionError) {
                            decisionError.textContent = 'Please select an action (Receive or Reject)';
                            decisionError.style.display = 'block';
                        }
                        if (decisionSelect) {
                            decisionSelect.focus();
                        }
                        hasError = true;
                    }
                    
                    // Validate Remarks
                    const remarks = remarksTextarea ? remarksTextarea.value.trim() : '';
                    if (!remarks || remarks === '') {
                        if (remarksError) {
                            remarksError.textContent = 'Please fill the Remarks Field';
                            remarksError.style.display = 'block';
                        }
                        if (remarksTextarea && !hasError) {
                            remarksTextarea.focus();
                        }
                        hasError = true;
                    }
                    
                    if (hasError) {
                        return;
                    }
                    
                    modal.hide();
                    resolve({ confirmed: true, decision, remarks });
                });
            }
            
            if (cancelBtn) {
                cancelBtn.addEventListener('click', () => {
                    modal.hide();
                    resolve({ confirmed: false });
                });
            }
            
            const modalElement = document.getElementById(modalId);
            modalElement.addEventListener('hidden.bs.modal', () => {
                modalElement.remove();
                resolve({ confirmed: false });
            });
            
            modal.show();
        });
    }

    /**
     * Show confirm modal
     */
    showConfirmModal(title, message) {
        return new Promise((resolve) => {
            if (typeof Swal === 'undefined') {
                // Fallback to basic confirm if SweetAlert2 is not loaded
                const confirmed = window.confirm(`${title}\n\n${message}`);
                resolve(confirmed);
                return;
            }

            Swal.fire({
                title: title,
                text: message,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#696cff',
                cancelButtonColor: '#a8aaae',
                confirmButtonText: '<i class="icon-base bx bx-check me-2"></i>Confirm',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                allowOutsideClick: false,
                allowEscapeKey: true,
                animation: true,
                customClass: {
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-label-secondary'
                }
            }).then((result) => {
                resolve(result.isConfirmed);
            });
        });
    }

    /**
     * Show alert modal
     */
    showAlertModal(message, type = 'info', onClose = null) {
        if (typeof Swal === 'undefined') {
            // Fallback to basic alert if SweetAlert2 is not loaded
            window.alert(message);
            if (onClose && typeof onClose === 'function') {
                onClose();
            }
            return;
        }

        // Map type to SweetAlert2 icon
        const iconMap = {
            'success': 'success',
            'warning': 'warning',
            'danger': 'error',
            'error': 'error',
            'info': 'info'
        };

        const icon = iconMap[type] || 'info';

        Swal.fire({
            title: message,
            icon: icon,
            confirmButtonColor: '#696cff',
            confirmButtonText: 'OK',
            allowOutsideClick: false,
            allowEscapeKey: true,
            animation: true,
            customClass: {
                confirmButton: 'btn btn-primary'
            }
        }).then((result) => {
            if (onClose && typeof onClose === 'function') {
                onClose();
            }
        });
    }

    /**
     * Back to list
     */
    backToList() {
        this.currentPRNumber = null;
        if (this.manager) {
            this.manager.currentPRNumber = null;
        }
        this.showListSection();
    }

    /**
     * Download document
     */
    async downloadDocument(documentId, fileName) {
        try {
            if (!documentId || documentId === 0) {
                this.showAlertModal('Invalid document ID', 'warning');
                return;
            }

            // Get FilePath from button's data attribute (if available)
            const button = typeof this === 'object' && this.nodeName === 'BUTTON' ? this : null;
            const filePath = button?.getAttribute('data-file-path') || '';

            // If FilePath is available, use apiStorage (like ARSystem)
            let downloadUrl = null;
            if (filePath && typeof apiStorage === 'function') {
                try {
                    downloadUrl = apiStorage('Procurement', filePath);
                } catch (error) {
                    console.warn('Error using apiStorage, falling back to download endpoint:', error);
                }
            }

            // Fallback: Use download endpoint if FilePath is not available
            if (!downloadUrl) {
                // Normalize API_URLS like apiHelper.js does
                if (typeof API_URLS === 'undefined') {
                    this.showAlertModal('API configuration not found', 'warning');
                    return;
                }

                // Normalize API URLs (handle case-insensitive keys)
                if (typeof window.normalizedApiUrls === 'undefined') {
                    window.normalizedApiUrls = {};
                }
                for (const key in API_URLS) {
                    if (API_URLS.hasOwnProperty(key)) {
                        window.normalizedApiUrls[key.toLowerCase()] = API_URLS[key];
                    }
                }

                // Get API base URL (case-insensitive)
                const apiType = 'procurement';
                const baseUrl = window.normalizedApiUrls[apiType];
                if (!baseUrl) {
                    const availableKeys = Object.keys(window.normalizedApiUrls).join(', ');
                    this.showAlertModal(`API configuration not found for Procurement. Available keys: ${availableKeys}`, 'warning');
                    return;
                }

                // Construct download URL using API base URL
                const endpoint = `/Procurement/PurchaseRequest/PurchaseRequestDocuments/${documentId}/download`;
                downloadUrl = baseUrl + endpoint;
            }
            
            // Create a temporary anchor element to trigger download
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = fileName || 'document';
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } catch (error) {
            console.error('Error downloading document:', error);
            this.showAlertModal('Failed to download document: ' + (error.message || 'Unknown error'), 'danger');
        }
    }

    /**
     * Format currency
     */
    formatCurrency(amount) {
        // Handle null, undefined, empty string
        if (amount === null || amount === undefined || amount === '') return '-';
        // Convert to number and handle NaN - show as Rp 0
        const numAmount = parseFloat(amount);
        if (isNaN(numAmount)) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(0);
        }
        // Handle 0 - show as Rp 0
        if (numAmount === 0) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(0);
        }
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(numAmount);
    }

    /**
     * Escape HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Show error message
     */
    showError(message) {
        const tbody = document.getElementById('receiveTableBody');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="13" class="text-center py-4">
                        <div class="text-danger">${this.escapeHtml(message)}</div>
                    </td>
                </tr>
            `;
        }
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.ReceiveListView = ReceiveListView;
}
