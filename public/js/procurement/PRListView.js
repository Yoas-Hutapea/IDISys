/**
 * PRListView Module
 * Handles view PR details functionality
 */
class PRListView {
    constructor(managerInstance) {
        this.manager = managerInstance;
        this.viewPRItems = [];
        this.viewPRDocuments = [];
        this.viewPRAdditional = null;
    }

    /**
     * View PR details
     */
    async viewPR(prNumber) {
        try {
            // Show loading state
            this.showViewPRLoading();
            
            // Hide list and filter sections
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
            
            // Load items, documents, and approval data
            await this.loadPRDetailsForView(prNumber, pr);
            
            // Populate summary with PR data
            await this.populateViewPRSummary(pr);
            
            // Show view section
            this.showViewPRSection();
            
        } catch (error) {
            console.error('Error loading PR for view:', error);
            this.showError('Failed to load Purchase Request: ' + error.message);
            this.showListSection();
        }
    }

    /**
     * Load PR details (items, documents, additional)
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
                
                // Load Purchase Types using API module (with caching)
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
                console.error('Error loading additional data:', error);
                this.viewPRAdditional = null;
            }
        } else {
            // Don't load additional data if not required
            this.viewPRAdditional = null;
            console.log('Additional Section not required for Type ID:', typeId, 'SubType ID:', subTypeId, '- Skipping API call');
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
                
                // Load Purchase Sub Types if Type ID is found
                if (typeId && purchReqSubType && this.manager && this.manager.apiModule) {
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
        let summaryHTML = `
            <!-- Additional Information Summary -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header" style="border-bottom: 1px solid #e9ecef;">
                        <h6 class="card-title mb-0">Additional Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
        `;
        
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
        
        summaryHTML += `
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        return summaryHTML;
    }

    /**
     * Populate view PR summary
     */
    async populateViewPRSummary(pr) {
        // Hide loading, show data
        const viewPRLoading = document.getElementById('viewPRLoading');
        const viewPRData = document.getElementById('viewPRData');
        
        if (viewPRLoading) {
            viewPRLoading.style.display = 'none';
        }
        if (viewPRData) {
            viewPRData.style.display = 'block';
        }

        // Get additional information HTML (async)
        const additionalHTML = await this.getAdditionalInformationSummaryHTML(pr);

        // Get employee names for approval fields using EmployeeCache module
        let reviewedByName = '';
        let approvedByName = '';
        let confirmedByName = '';
        let applicantName = '';
        let requestorName = '';

        if (this.manager && this.manager.employeeCacheModule) {
            const employeeIds = [];
            if (pr.reviewedBy) employeeIds.push(pr.reviewedBy);
            if (pr.approvedBy) employeeIds.push(pr.approvedBy);
            if (pr.confirmedBy) employeeIds.push(pr.confirmedBy);
            if (pr.applicant) employeeIds.push(pr.applicant);
            if (pr.requestor || pr.Requestor) employeeIds.push(pr.requestor || pr.Requestor);

            const nameMap = await this.manager.employeeCacheModule.batchGetEmployeeNames(employeeIds);
            
            reviewedByName = pr.reviewedBy ? (nameMap.get(pr.reviewedBy.trim().toLowerCase()) || '') : '';
            approvedByName = pr.approvedBy ? (nameMap.get(pr.approvedBy.trim().toLowerCase()) || '') : '';
            confirmedByName = pr.confirmedBy ? (nameMap.get(pr.confirmedBy.trim().toLowerCase()) || '') : '';
            applicantName = pr.applicant ? (nameMap.get(pr.applicant.trim().toLowerCase()) || '') : '';
            requestorName = (pr.requestor || pr.Requestor) ? (nameMap.get((pr.requestor || pr.Requestor).trim().toLowerCase()) || '') : '';
        }

        // Populate Purchase Information
        const requestorEl = document.getElementById('view-pr-requestor');
        const applicantEl = document.getElementById('view-pr-applicant');
        const companyEl = document.getElementById('view-pr-company');
        const purchReqTypeEl = document.getElementById('view-pr-purchase-request-type');
        const purchReqSubTypeEl = document.getElementById('view-pr-purchase-request-sub-type');
        const purchReqNameEl = document.getElementById('view-pr-purchase-request-name');
        const remarksEl = document.getElementById('view-pr-remarks');

        if (requestorEl) requestorEl.value = requestorName || pr.requestor || pr.Requestor || '-';
        if (applicantEl) applicantEl.value = applicantName || pr.applicant || pr.Applicant || '-';
        if (companyEl) companyEl.value = pr.company || pr.Company || '-';
        if (purchReqTypeEl) purchReqTypeEl.value = pr.purchReqType || pr.PurchReqType || '-';
        if (purchReqSubTypeEl) purchReqSubTypeEl.value = pr.purchReqSubType || pr.PurchReqSubType || '-';
        if (purchReqNameEl) purchReqNameEl.value = pr.purchReqName || pr.PurchReqName || '-';
        if (remarksEl) remarksEl.value = pr.remark || pr.Remark || '-';

        // Populate Additional Information
        const additionalSection = document.getElementById('view-pr-additional-section');
        const additionalBody = document.getElementById('view-pr-additional-body');
        if (additionalSection && additionalBody) {
            if (additionalHTML) {
                additionalBody.innerHTML = additionalHTML;
                additionalSection.style.display = 'block';
            } else {
                additionalSection.style.display = 'none';
            }
        }

        // Populate Items
        const itemsTbody = document.getElementById('view-pr-items-tbody');
        const totalAmount = this.viewPRItems && this.viewPRItems.length > 0
            ? this.viewPRItems.reduce((sum, item) => sum + (parseFloat(item.Amount || item.amount || 0) || 0), 0)
            : 0;

        if (itemsTbody) {
            if (this.viewPRItems && this.viewPRItems.length > 0) {
                itemsTbody.innerHTML = this.viewPRItems.map(item => {
                    const itemId = item.mstPROPurchaseItemInventoryItemID || item.mstPROInventoryItemID || item.MstPROPurchaseItemInventoryItemID || item.MstPROInventoryItemID || item.ItemID || item.itemID || '-';
                    const itemName = item.ItemName || item.itemName || '-';
                    const description = item.ItemDescription || item.itemDescription || '-';
                    const uom = item.ItemUnit || item.itemUnit || '-';
                    const quantity = item.ItemQty || item.itemQty || item.Quantity || item.quantity || '-';
                    const currency = item.ItemCurrency || item.itemCurrency || item.CurrencyCode || item.currencyCode || '-';
                    const unitPrice = item.ItemUnitPrice ?? item.itemUnitPrice ?? item.UnitPrice ?? item.unitPrice ?? null;
                    const amount = item.Amount ?? item.amount ?? null;
                    
                    return `
                        <tr>
                            <td>${this.escapeHtml(itemId)}</td>
                            <td>${this.escapeHtml(itemName)}</td>
                            <td>${this.escapeHtml(description)}</td>
                            <td>${this.escapeHtml(uom)}</td>
                            <td>${this.escapeHtml(quantity)}</td>
                            <td>${this.escapeHtml(currency)}</td>
                            <td>${this.formatCurrency(unitPrice)}</td>
                            <td>${this.formatCurrency(amount)}</td>
                        </tr>
                    `;
                }).join('');
            } else {
                itemsTbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No items</td></tr>';
            }
        }

        const amountTotalEl = document.getElementById('view-pr-amount-total');
        if (amountTotalEl) {
            amountTotalEl.value = this.formatCurrency(totalAmount);
        }

        // Populate Approval Assignment
        const approvalRequestorEl = document.getElementById('view-pr-approval-requestor');
        const reviewedByEl = document.getElementById('view-pr-reviewed-by');
        const approvalApplicantEl = document.getElementById('view-pr-approval-applicant');
        const approvedByEl = document.getElementById('view-pr-approved-by');
        const confirmedByEl = document.getElementById('view-pr-confirmed-by');

        if (approvalRequestorEl) approvalRequestorEl.value = requestorName || pr.requestor || pr.Requestor || '-';
        if (reviewedByEl) reviewedByEl.value = reviewedByName || pr.reviewedBy || pr.ReviewedBy || '-';
        if (approvalApplicantEl) approvalApplicantEl.value = applicantName || pr.applicant || pr.Applicant || '-';
        if (approvedByEl) approvedByEl.value = approvedByName || pr.approvedBy || pr.ApprovedBy || '-';
        if (confirmedByEl) confirmedByEl.value = confirmedByName || pr.confirmedBy || pr.ConfirmedBy || '-';

        // Populate Documents
        const documentsTbody = document.getElementById('view-pr-documents-tbody');
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
                                <button type="button" class="btn btn-sm btn-primary" title="View" onclick="downloadDocument.call(this, ${docId})" data-file-name="${escapedFileName}" data-file-path="${escapedFilePath}">
                                    <i class="icon-base bx bx-show"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');
            } else {
                documentsTbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No documents</td></tr>';
            }
        }
    }

    /**
     * Show/hide sections
     */
    hideListSection() {
        const filterSection = document.querySelector('.filter-section');
        const listSection = document.getElementById('listSection');
        
        if (filterSection) filterSection.style.display = 'none';
        if (listSection) listSection.style.display = 'none';
    }

    showListSection() {
        const filterSection = document.querySelector('.filter-section');
        const listSection = document.getElementById('listSection');
        const viewPRSection = document.getElementById('viewPRSection');
        
        if (filterSection) filterSection.style.display = 'block';
        if (listSection) listSection.style.display = 'block';
        if (viewPRSection) viewPRSection.style.display = 'none';
    }

    showViewPRSection() {
        const viewPRSection = document.getElementById('viewPRSection');
        if (viewPRSection) {
            viewPRSection.style.display = 'block';
        }
    }

    showViewPRLoading() {
        const viewPRSection = document.getElementById('viewPRSection');
        const viewPRLoading = document.getElementById('viewPRLoading');
        const viewPRData = document.getElementById('viewPRData');
        
        if (viewPRLoading) {
            viewPRLoading.style.display = 'block';
        }
        if (viewPRData) {
            viewPRData.style.display = 'none';
        }
        
        if (viewPRSection) {
            viewPRSection.style.display = 'block';
        }
    }

    /**
     * Back to list
     */
    backToList() {
        this.showListSection();
    }

    /**
     * Download document
     */
    async downloadDocument(documentId, fileName) {
        try {
            if (!documentId || documentId === 0) {
                this.showError('Invalid document ID');
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
                    this.showError('API configuration not found');
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
                    this.showError(`API configuration not found for Procurement. Available keys: ${availableKeys}`);
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
            link.target = '_blank';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } catch (error) {
            console.error('Error downloading document:', error);
            this.showError('Failed to download document: ' + error.message);
        }
    }

    /**
     * Utility functions
     */
    escapeHtml(text) {
        if (text == null) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatCurrency(value) {
        if (value == null || value === '' || isNaN(value)) return '-';
        const num = parseFloat(value);
        if (isNaN(num)) return '-';
        return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(num);
    }

    showError(message) {
        if (this.manager && this.manager.showError) {
            this.manager.showError(message);
        } else {
            console.error(message);
            alert(message);
        }
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.PRListView = PRListView;
}

// Global state for current previewed document
window.__currentDocumentPreview = {
    id: null,
    fileName: '',
    filePath: '',
    downloadUrl: null
};

/**
 * Global downloadDocument function kept for backward compatibility.
 * Instead of forcing immediate download, open a preview modal and allow user to download from there.
 * Called using .call(this, documentId) in many places where `this` is the button element.
 */
function downloadDocument(documentId, fileName) {
    try {
        const button = (this && this.nodeName === 'BUTTON') ? this : null;
        const fileNameAttr = (button && button.getAttribute('data-file-name')) || fileName || '';
        const filePath = (button && button.getAttribute('data-file-path')) || '';

        let downloadUrl = null;
        if (filePath && typeof apiStorage === 'function') {
            try {
                downloadUrl = apiStorage('Procurement', filePath);
            } catch (error) {
                console.warn('Error using apiStorage, falling back to download endpoint:', error);
            }
        }

        if (!downloadUrl) {
            if (typeof API_URLS === 'undefined') {
                alert('API configuration not found');
                return;
            }
            if (typeof window.normalizedApiUrls === 'undefined') {
                window.normalizedApiUrls = {};
                for (const key in API_URLS) {
                    if (API_URLS.hasOwnProperty(key)) {
                        window.normalizedApiUrls[key.toLowerCase()] = API_URLS[key];
                    }
                }
            }
            const apiType = 'procurement';
            const baseUrl = window.normalizedApiUrls[apiType];
            if (!baseUrl) {
                alert('API configuration not found for Procurement');
                return;
            }
            const endpoint = `/Procurement/PurchaseRequest/PurchaseRequestDocuments/${documentId}/download`;
            downloadUrl = baseUrl + endpoint;
        }

        // Save current preview info
        window.__currentDocumentPreview.id = documentId;
        window.__currentDocumentPreview.fileName = fileNameAttr || 'document';
        window.__currentDocumentPreview.filePath = filePath;
        window.__currentDocumentPreview.downloadUrl = downloadUrl;

        // Populate modal
        const filenameEl = document.getElementById('documentPreviewFilename');
        const frame = document.getElementById('documentPreviewFrame');
        const modalEl = document.getElementById('documentPreviewModal');
        if (filenameEl) filenameEl.textContent = window.__currentDocumentPreview.fileName;
        if (frame) frame.src = downloadUrl;

        // Show Bootstrap modal if available, otherwise open in new tab
        if (modalEl && typeof bootstrap !== 'undefined') {
            // Ensure modal is a direct child of body to avoid stacking-context/z-index issues
            try {
                if (modalEl.parentNode !== document.body) {
                    document.body.appendChild(modalEl);
                }
            } catch (e) {
                console.warn('Could not append modal to body:', e);
            }

            const modalInstance = new bootstrap.Modal(modalEl);
            modalInstance.show();
        } else if (downloadUrl) {
            window.open(downloadUrl, '_blank');
        }
    } catch (error) {
        console.error('Error opening document preview:', error);
        alert('Failed to open document: ' + error.message);
    }
}

/**
 * Trigger actual download from modal's Download button
 */
function downloadDocumentFromModal() {
    try {
        const info = window.__currentDocumentPreview;
        if (!info || !info.downloadUrl) {
            alert('No document selected');
            return;
        }
        const link = document.createElement('a');
        link.href = info.downloadUrl;
        link.download = info.fileName || 'document';
        link.target = '_blank';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } catch (error) {
        console.error('Error downloading document from modal:', error);
        alert('Failed to download document: ' + error.message);
    }
}

