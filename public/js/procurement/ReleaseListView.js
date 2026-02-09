/**
 * ReleaseListView Module
 * Handles view release functionality, submit release, and bulk release
 */
class ReleaseListView {
    constructor(managerInstance) {
        this.manager = managerInstance;
        this.viewPRItems = [];
        this.viewPRDocuments = [];
        this.viewPRAdditional = null;
        this.currentPRNumber = null;
        this.allPurchaseTypes = null;
        // Ensure global viewed documents set exists
        if (typeof window !== 'undefined') {
            if (typeof window.__viewedDocuments === 'undefined') {
                try {
                    window.__viewedDocuments = new Set();
                } catch (e) {
                    window.__viewedDocuments = [];
                }
            }
        }
    }

    /**
     * View release details
     */
    async viewRelease(prNumber) {
        // Store currentPRNumber in both viewModule and manager for backward compatibility
        this.currentPRNumber = prNumber;
        if (this.manager) {
            this.manager.currentPRNumber = prNumber;
        }
        
        try {
            this.showViewReleaseLoading();
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
            await this.populateViewRelease(pr);
            this.showViewReleaseSection();

        } catch (error) {
            console.error('Error loading release:', error);
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
        // Note: viewReleaseSection is controlled by showViewReleaseLoading() and showViewReleaseSection()
    }

    /**
     * Show list section
     */
    showListSection() {
        const filterSection = document.querySelector('.filter-section');
        const listSection = document.getElementById('listSection');
        const viewReleaseSection = document.getElementById('viewReleaseSection');
        const bulkyReleaseSection = document.getElementById('bulkyReleaseSection');
        
        if (filterSection) filterSection.style.display = 'block';
        if (listSection) listSection.style.display = 'block';
        if (viewReleaseSection) viewReleaseSection.style.display = 'none';
        if (bulkyReleaseSection) bulkyReleaseSection.style.display = 'none';
    }

    /**
     * Show view release section
     */
    showViewReleaseSection() {
        const viewReleaseSection = document.getElementById('viewReleaseSection');
        if (viewReleaseSection) {
            viewReleaseSection.style.display = 'block';
        }
    }

    /**
     * Show view release loading
     */
    showViewReleaseLoading() {
        const viewReleaseSection = document.getElementById('viewReleaseSection');
        const loadingDiv = document.getElementById('viewReleaseLoading');
        const dataDiv = document.getElementById('viewReleaseData');
        
        if (loadingDiv) loadingDiv.style.display = 'block';
        if (dataDiv) dataDiv.style.display = 'none';
        
        if (viewReleaseSection) {
            viewReleaseSection.style.display = 'block';
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
     * Populate view release with PR data
     */
    async populateViewRelease(pr) {
        const loadingDiv = document.getElementById('viewReleaseLoading');
        const dataDiv = document.getElementById('viewReleaseData');
        
        if (loadingDiv) loadingDiv.style.display = 'none';
        if (dataDiv) dataDiv.style.display = 'block';
        
        // Reload TOP dropdown after PR is selected to apply filter based on StartPeriod/EndPeriod
        if (typeof window.loadTermOfPayments === 'function') {
            setTimeout(() => {
                window.loadTermOfPayments();
            }, 500); // Small delay to ensure form is rendered
        }

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
                    
                    // Determine viewed status
                    const viewedFlag = (typeof window !== 'undefined' && window.__viewedDocuments && typeof window.__viewedDocuments.has === 'function')
                        ? window.__viewedDocuments.has(String(docId))
                        : (Array.isArray(window.__viewedDocuments) ? window.__viewedDocuments.includes(String(docId)) : false);

                    return `
                        <tr>
                            <td>
                                ${escapedFileName}
                                <span class="badge bg-success ms-2 doc-viewed-badge" data-doc-id="${docId}" style="display:${viewedFlag ? 'inline-block' : 'none'}">Viewed</span>
                                <span class="badge bg-secondary ms-2 doc-not-viewed-badge" data-doc-id="${docId}" style="display:${viewedFlag ? 'none' : 'inline-block'}">Not Viewed</span>
                            </td>
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

        // Load and populate approval log
        await this.loadApprovalLog(pr.purchReqNumber || pr.PurchReqNumber || this.currentPRNumber);

        // Populate Additional Section
        await this.populateAdditionalSection(pr);

        // Reset release form
        const releaseDecision = document.getElementById('bulkyReleaseDecision');
        const releaseRemarks = document.getElementById('remarkApproval');
        if (releaseDecision) releaseDecision.value = '';
        if (releaseRemarks) releaseRemarks.value = '';
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
     * Load approval log with DataTables pagination
     */
    async loadApprovalLog(prNumber) {
        try {
            if (!this.manager || !this.manager.apiModule) {
                console.error('API module not available');
                return;
            }

            const approvalLogs = await this.manager.apiModule.getApprovalLog(prNumber);
            const logs = Array.isArray(approvalLogs) ? approvalLogs : [];

            const approvalLogTable = document.getElementById('viewApprovalLogTable');
            if (!approvalLogTable) {
                console.error('Approval log table not found');
                return;
            }

            // Check if DataTables is available
            const isDataTablesAvailable = typeof $ !== 'undefined' && typeof $.fn.DataTable !== 'undefined';

            if (isDataTablesAvailable) {
                // Destroy existing DataTable if it exists
                if ($.fn.DataTable.isDataTable('#viewApprovalLogTable')) {
                    $('#viewApprovalLogTable').DataTable().destroy();
                }
            }

            if (logs && logs.length > 0) {
                // Get employee names using shared cache (batch lookup for efficiency)
                let employeeCache = null;
                if (window.procurementSharedCache && window.procurementSharedCache.batchGetEmployeeNames) {
                    employeeCache = window.procurementSharedCache;
                } else if (this.manager && this.manager.employeeCacheModule && this.manager.employeeCacheModule.batchGetEmployeeNames) {
                    employeeCache = this.manager.employeeCacheModule;
                }

                // Collect all employee IDs for batch lookup
                const employeeIds = logs
                    .map(log => log.mstEmployeeID || log.MstEmployeeID || log.EmployeeID || log.employeeID || '')
                    .filter(id => id && id !== '-');

                // Batch lookup all employee names at once (optimized)
                let employeeNameMap = new Map();
                if (employeeCache && employeeCache.batchGetEmployeeNames && employeeIds.length > 0) {
                    employeeNameMap = await employeeCache.batchGetEmployeeNames(employeeIds);
                }

                if (isDataTablesAvailable) {
                    // Prepare data for DataTables
                    const tableData = logs.map(log => {
                        const date = log.CreatedDate || log.createdDate || log.Date || log.date || '-';
                        const activity = log.Activity || log.activity || '-';
                        const decision = log.Decision || log.decision || '-';
                        const employeeId = log.mstEmployeeID || log.MstEmployeeID || log.EmployeeID || log.employeeID || '';
                        const positionId = log.mstEmployeePositionID || log.MstEmployeePositionID || log.PositionID || log.positionID || '-';
                        const remark = log.Remark || log.remark || '-';
                        
                        // Get employee name from batch lookup map
                        const employeeName = employeeId ? (employeeNameMap.get(employeeId.trim().toLowerCase()) || '') : '';
                        const displayName = employeeName || employeeId || '-';
                        
                        return [
                            this.formatDate(date),
                            this.escapeHtml(activity),
                            this.escapeHtml(decision),
                            this.escapeHtml(displayName),
                            this.escapeHtml(positionId),
                            this.escapeHtml(remark)
                        ];
                    });

                    // Initialize DataTable with pagination (use existing thead)
                    $(approvalLogTable).DataTable({
                        data: tableData,
                        columns: [
                            null, // Date
                            null, // Activity
                            null, // Decision
                            null, // Employee
                            null, // Position
                            null  // Remark
                        ],
                        paging: true,
                        pageLength: 5,
                        lengthMenu: [[5, 10, 25, 50, 100], [5, 10, 25, 50, 100]],
                        searching: true,
                        ordering: true,
                        order: [[0, 'desc']], // Sort by date descending (newest first)
                        info: true,
                        autoWidth: false,
                        responsive: true,
                        language: {
                            emptyTable: 'No approval log',
                            search: 'Search:',
                            lengthMenu: 'Show _MENU_ entries',
                            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                            infoEmpty: 'Showing 0 to 0 of 0 entries',
                            infoFiltered: '(filtered from _MAX_ total entries)',
                            paginate: {
                                first: 'First',
                                last: 'Last',
                                next: 'Next',
                                previous: 'Previous'
                            }
                        },
                        destroy: true // Allow re-initialization
                    });
                } else {
                    // Fallback: render without DataTables (no pagination)
                    const approvalLogTbody = document.getElementById('view-approval-log-tbody');
                    if (approvalLogTbody) {
                        const logRows = await Promise.all(logs.map(async (log) => {
                            const date = log.CreatedDate || log.createdDate || log.Date || log.date || '-';
                            const activity = log.Activity || log.activity || '-';
                            const decision = log.Decision || log.decision || '-';
                            const employeeId = log.mstEmployeeID || log.MstEmployeeID || log.EmployeeID || log.employeeID || '';
                            const positionId = log.mstEmployeePositionID || log.MstEmployeePositionID || log.PositionID || log.positionID || '-';
                            const remark = log.Remark || log.remark || '-';
                            
                            // Get employee name from batch lookup map
                            const employeeName = employeeId ? (employeeNameMap.get(employeeId.trim().toLowerCase()) || '') : '';
                            const displayName = employeeName || employeeId || '-';
                            
                            return `
                                <tr>
                                    <td>${this.formatDate(date)}</td>
                                    <td>${this.escapeHtml(activity)}</td>
                                    <td>${this.escapeHtml(decision)}</td>
                                    <td>${this.escapeHtml(displayName)}</td>
                                    <td>${this.escapeHtml(positionId)}</td>
                                    <td>${this.escapeHtml(remark)}</td>
                                </tr>
                            `;
                        }));
                        approvalLogTbody.innerHTML = logRows.join('');
                    }
                }
            } else {
                if (isDataTablesAvailable) {
                    // Initialize DataTable with empty data (use existing thead)
                    $(approvalLogTable).DataTable({
                        data: [],
                        columns: [
                            null, // Date
                            null, // Activity
                            null, // Decision
                            null, // Employee
                            null, // Position
                            null  // Remark
                        ],
                        paging: false,
                        searching: false,
                        ordering: false,
                        info: false,
                        language: {
                            emptyTable: 'No approval log'
                        },
                        destroy: true
                    });
                } else {
                    // Fallback: show empty message
                    const approvalLogTbody = document.getElementById('view-approval-log-tbody');
                    if (approvalLogTbody) {
                        approvalLogTbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No approval log</td></tr>';
                    }
                }
            }
        } catch (error) {
            console.error('Error loading approval log:', error);
            const approvalLogTable = document.getElementById('viewApprovalLogTable');
            if (approvalLogTable) {
                // Destroy existing DataTable if it exists
                if (typeof $ !== 'undefined' && typeof $.fn.DataTable !== 'undefined' && $.fn.DataTable.isDataTable('#viewApprovalLogTable')) {
                    $('#viewApprovalLogTable').DataTable().destroy();
                }
                
                // Show error message
                const tbody = approvalLogTable.querySelector('tbody');
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading approval log</td></tr>';
                }
            }
        }
    }

    /**
     * Custom validation function for Single Release form
     */
    validateSingleReleaseForm(form) {
        if (!form) {
            return false;
        }
        
        // Get decision value - Single Release uses 'bulkyReleaseDecision'
        const decisionSelect = form.querySelector('#bulkyReleaseDecision');
        if (!decisionSelect) {
            return false;
        }
        
        const decision = decisionSelect.value || '';
        
        // Get vendor type
        const vendorTypeContract = form.querySelector('#vendorTypeContract');
        const vendorTypeNonContract = form.querySelector('#vendorTypeNonContract');
        const vendorType = vendorTypeContract?.checked ? 'Contract' : (vendorTypeNonContract?.checked ? 'Non Contract' : '');
        
        // Helper function to show error message below field
        const showFieldError = (fieldId, fieldName) => {
            // Find the field group container
            let fieldGroup = null;
            const fieldElement = document.getElementById(fieldId);
            if (fieldElement) {
                // For hidden inputs, find the dropdown container
                if (fieldElement.type === 'hidden') {
                    const dropdownBtnId = fieldId + 'DropdownBtn';
                    const dropdownBtn = document.getElementById(dropdownBtnId);
                    if (dropdownBtn) {
                        fieldGroup = dropdownBtn.closest('.col-md-6') || dropdownBtn.closest('.col-12');
                    }
                } else {
                    fieldGroup = fieldElement.closest('.col-md-6') || fieldElement.closest('.col-12');
                }
            }
            
            if (fieldGroup) {
                // Remove existing error message if any
                const existingError = fieldGroup.querySelector('.field-error-message');
                if (existingError) {
                    existingError.remove();
                }
                
                // Create error message element
                const errorDiv = document.createElement('div');
                errorDiv.className = 'field-error-message text-danger small mt-1';
                errorDiv.textContent = 'Please fill this information';
                errorDiv.style.display = 'block';
                
                // Insert error message after the field/dropdown container
                const fieldContainer = fieldElement?.closest('.dropdown') || fieldElement?.parentElement || fieldGroup;
                if (fieldContainer && fieldContainer.parentElement === fieldGroup) {
                    fieldGroup.appendChild(errorDiv);
                } else {
                    // If fieldContainer is not direct child, append to fieldGroup
                    fieldGroup.appendChild(errorDiv);
                }
            }
        };
        
        // Helper function to focus and scroll to field (with error message)
        const focusField = (fieldId, fieldName) => {
            const fieldElement = document.getElementById(fieldId);
            if (fieldElement) {
                // Show error message
                showFieldError(fieldId, fieldName);
                
                // For hidden inputs (dropdowns), focus to the dropdown button
                if (fieldElement.type === 'hidden') {
                    const dropdownBtnId = fieldId + 'DropdownBtn';
                    const dropdownBtn = document.getElementById(dropdownBtnId);
                    if (dropdownBtn) {
                        dropdownBtn.focus();
                        dropdownBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        // Trigger HTML5 validation on hidden input
                        fieldElement.setCustomValidity('Please select a value');
                        fieldElement.reportValidity();
                    } else {
                        fieldElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                } else {
                    fieldElement.focus();
                    fieldElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    // Trigger HTML5 validation
                    if (fieldElement.reportValidity) {
                        fieldElement.reportValidity();
                    }
                }
            }
        };
        
        // Validate Decision (always required)
        if (!decision || decision.trim() === '') {
            decisionSelect.focus();
            decisionSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
            // Show error message for Decision
            const decisionGroup = decisionSelect.closest('.col-md-6') || decisionSelect.closest('.col-12');
            if (decisionGroup) {
                const existingError = decisionGroup.querySelector('.field-error-message');
                if (existingError) existingError.remove();
                const errorDiv = document.createElement('div');
                errorDiv.className = 'field-error-message text-danger small mt-1';
                errorDiv.textContent = 'Please fill this information';
                decisionGroup.appendChild(errorDiv);
            }
            if (decisionSelect.reportValidity) {
                decisionSelect.reportValidity();
            }
            return false;
        }
        
        // If Decision is Reject, only Decision is required
        if (decision === 'Reject') {
            return true;
        }
        
        // For Release, validate Vendor Type
        if (!vendorType || vendorType === '') {
            const firstRadio = vendorTypeContract || vendorTypeNonContract;
            if (firstRadio) {
                firstRadio.focus();
                firstRadio.scrollIntoView({ behavior: 'smooth', block: 'center' });
                // Show error message for Vendor Type
                const vendorTypeGroup = firstRadio.closest('.col-md-6') || firstRadio.closest('.col-12');
                if (vendorTypeGroup) {
                    const existingError = vendorTypeGroup.querySelector('.field-error-message');
                    if (existingError) existingError.remove();
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'field-error-message text-danger small mt-1';
                    errorDiv.textContent = 'Please fill this information';
                    vendorTypeGroup.appendChild(errorDiv);
                }
                // Trigger validation on radio group
                if (vendorTypeContract) {
                    vendorTypeContract.setCustomValidity('Please select Vendor Type');
                    vendorTypeContract.reportValidity();
                }
            }
            return false;
        }
        
        // Clear any previous custom validity on vendor type radios
        if (vendorTypeContract) vendorTypeContract.setCustomValidity('');
        if (vendorTypeNonContract) vendorTypeNonContract.setCustomValidity('');
        
        // Helper function to check if dropdown field is empty
        const isDropdownEmpty = (hiddenInputId, selectedTextId) => {
            const hiddenInput = document.getElementById(hiddenInputId);
            const selectedText = document.getElementById(selectedTextId);
            
            if (!hiddenInput) {
                return true;
            }
            
            const inputValue = hiddenInput.value || '';
            if (inputValue.trim() === '') {
                return true;
            }
            
            if (selectedText) {
                const text = (selectedText.textContent || selectedText.innerText || '').trim();
                const lowerText = text.toLowerCase();
                if (lowerText.startsWith('select') && inputValue.trim() === '') {
                    return true;
                }
            }
            
            return false;
        };
        
        // Helper function to check if field value is empty
        const isEmpty = (field) => {
            if (!field) return true;
            const value = field.value || '';
            return value.trim() === '';
        };
        
        // Validate fields based on Vendor Type (in order from top to bottom)
        // 1. Select Vendor (required for Release - always visible when Release)
        if (isDropdownEmpty('selectVendor', 'selectVendorSelectedText')) {
            focusField('selectVendor', 'Select Vendor');
            return false;
        }
        
        // 2. Core Business (required only for Contract)
        if (vendorType === 'Contract') {
            if (isDropdownEmpty('coreBusiness', 'coreBusinessSelectedText')) {
                focusField('coreBusiness', 'Core Business');
                return false;
            }
        }
        
        // 3. Sub Core Business (required only for Contract)
        if (vendorType === 'Contract') {
            if (isDropdownEmpty('subCoreBusiness', 'subCoreBusinessSelectedText')) {
                focusField('subCoreBusiness', 'Sub Core Business');
                return false;
            }
        }
        
        // 4. Contract No (required only for Contract)
        if (vendorType === 'Contract') {
            if (isDropdownEmpty('contractNo', 'contractNoSelectedText')) {
                focusField('contractNo', 'Contract No');
                return false;
            }
        }
        
        // 5. Contract Period (required only for Contract)
        if (vendorType === 'Contract') {
            const contractPeriod = form.querySelector('#contractPeriod');
            if (isEmpty(contractPeriod)) {
                focusField('contractPeriod', 'Contract Period');
                return false;
            }
        }
        
        // 6. Top Type (required for Release - always visible when Release)
        if (isDropdownEmpty('topType', 'topTypeSelectedText')) {
            focusField('topType', 'Top Type');
            return false;
        }
        
        // 7. Description (required for Release - always visible when Release)
        const description = form.querySelector('#description');
        if (isEmpty(description)) {
            focusField('description', 'Description');
            return false;
        }
        
        // 8. Remark Approval (required for Release - always visible when Release)
        const remarkApproval = form.querySelector('#remarkApproval');
        if (isEmpty(remarkApproval)) {
            focusField('remarkApproval', 'Remark Approval');
            return false;
        }
        
        return true;
    }

    /**
     * Custom validation function for Bulky Release form
     */
    validateBulkyReleaseForm(form) {
        if (!form) {
            return false;
        }
        
        // Get decision value
        const decisionSelect = form.querySelector('#bulkyPageReleaseDecision');
        if (!decisionSelect) {
            return false;
        }
        
        const decision = decisionSelect.value || '';
        
        // Get vendor type
        const vendorTypeContract = form.querySelector('#bulkyPageVendorTypeContract');
        const vendorTypeNonContract = form.querySelector('#bulkyPageVendorTypeNonContract');
        const vendorType = vendorTypeContract?.checked ? 'Contract' : (vendorTypeNonContract?.checked ? 'Non Contract' : '');
        
        // Helper function to show error message below field
        const showFieldError = (fieldId, fieldName) => {
            // Find the field group container
            let fieldGroup = null;
            const fieldElement = document.getElementById(fieldId);
            if (fieldElement) {
                // For hidden inputs, find the dropdown container
                if (fieldElement.type === 'hidden') {
                    const dropdownBtnId = fieldId + 'DropdownBtn';
                    const dropdownBtn = document.getElementById(dropdownBtnId);
                    if (dropdownBtn) {
                        fieldGroup = dropdownBtn.closest('.col-md-6') || dropdownBtn.closest('.col-12');
                    }
                } else {
                    fieldGroup = fieldElement.closest('.col-md-6') || fieldElement.closest('.col-12');
                }
            }
            
            if (fieldGroup) {
                // Remove existing error message if any
                const existingError = fieldGroup.querySelector('.field-error-message');
                if (existingError) {
                    existingError.remove();
                }
                
                // Create error message element
                const errorDiv = document.createElement('div');
                errorDiv.className = 'field-error-message text-danger small mt-1';
                errorDiv.textContent = 'Please fill this information';
                errorDiv.style.display = 'block';
                
                // Insert error message after the field/dropdown container
                const fieldContainer = fieldElement?.closest('.dropdown') || fieldElement?.parentElement || fieldGroup;
                if (fieldContainer && fieldContainer.parentElement === fieldGroup) {
                    fieldGroup.appendChild(errorDiv);
                } else {
                    // If fieldContainer is not direct child, append to fieldGroup
                    fieldGroup.appendChild(errorDiv);
                }
            }
        };
        
        // Helper function to focus and scroll to field (with error message)
        const focusField = (fieldId, fieldName) => {
            const fieldElement = document.getElementById(fieldId);
            if (fieldElement) {
                // Show error message
                showFieldError(fieldId, fieldName);
                
                // For hidden inputs (dropdowns), focus to the dropdown button
                if (fieldElement.type === 'hidden') {
                    const dropdownBtnId = fieldId + 'DropdownBtn';
                    const dropdownBtn = document.getElementById(dropdownBtnId);
                    if (dropdownBtn) {
                        dropdownBtn.focus();
                        dropdownBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        // Trigger HTML5 validation on hidden input
                        fieldElement.setCustomValidity('Please select a value');
                        fieldElement.reportValidity();
                    } else {
                        fieldElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                } else {
                    fieldElement.focus();
                    fieldElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    // Trigger HTML5 validation
                    if (fieldElement.reportValidity) {
                        fieldElement.reportValidity();
                    }
                }
            }
        };
        
        // Validate Decision (always required)
        if (!decision || decision.trim() === '') {
            decisionSelect.focus();
            decisionSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
            // Show error message for Decision
            const decisionGroup = decisionSelect.closest('.col-md-6') || decisionSelect.closest('.col-12');
            if (decisionGroup) {
                const existingError = decisionGroup.querySelector('.field-error-message');
                if (existingError) existingError.remove();
                const errorDiv = document.createElement('div');
                errorDiv.className = 'field-error-message text-danger small mt-1';
                errorDiv.textContent = 'Please fill this information';
                decisionGroup.appendChild(errorDiv);
            }
            if (decisionSelect.reportValidity) {
                decisionSelect.reportValidity();
            }
            return false;
        }
        
        // If Decision is Reject, only Decision is required
        if (decision === 'Reject') {
            return true;
        }
        
        // For Release, validate Vendor Type
        if (!vendorType || vendorType === '') {
            const firstRadio = vendorTypeContract || vendorTypeNonContract;
            if (firstRadio) {
                firstRadio.focus();
                firstRadio.scrollIntoView({ behavior: 'smooth', block: 'center' });
                // Show error message for Vendor Type
                const vendorTypeGroup = firstRadio.closest('.col-md-6') || firstRadio.closest('.col-12');
                if (vendorTypeGroup) {
                    const existingError = vendorTypeGroup.querySelector('.field-error-message');
                    if (existingError) existingError.remove();
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'field-error-message text-danger small mt-1';
                    errorDiv.textContent = 'Please fill this information';
                    vendorTypeGroup.appendChild(errorDiv);
                }
                // Trigger validation on radio group
                if (vendorTypeContract) {
                    vendorTypeContract.setCustomValidity('Please select Vendor Type');
                    vendorTypeContract.reportValidity();
                }
            }
            return false;
        }
        
        // Clear any previous custom validity on vendor type radios
        if (vendorTypeContract) vendorTypeContract.setCustomValidity('');
        if (vendorTypeNonContract) vendorTypeNonContract.setCustomValidity('');
        
        // Helper function to check if dropdown field is empty
        const isDropdownEmpty = (hiddenInputId, selectedTextId) => {
            const hiddenInput = document.getElementById(hiddenInputId);
            const selectedText = document.getElementById(selectedTextId);
            
            // Check hidden input value first (most reliable)
            if (!hiddenInput) {
                return true;
            }
            
            const inputValue = hiddenInput.value || '';
            if (inputValue.trim() === '') {
                return true;
            }
            
            // Also check if selected text shows "Select ..." (indicates not selected)
            if (selectedText) {
                const text = (selectedText.textContent || selectedText.innerText || '').trim();
                const lowerText = text.toLowerCase();
                if (lowerText.startsWith('select') && inputValue.trim() === '') {
                    return true;
                }
            }
            
            return false;
        };
        
        // Helper function to check if field value is empty
        const isEmpty = (field) => {
            if (!field) return true;
            const value = field.value || '';
            return value.trim() === '';
        };
        
        // Validate fields based on Vendor Type (in order from top to bottom)
        // 1. Select Vendor (required for Release - always visible when Release)
        if (isDropdownEmpty('bulkyPageSelectVendor', 'bulkyPageSelectVendorSelectedText')) {
            focusField('bulkyPageSelectVendor', 'Select Vendor');
            return false;
        }
        
        // 2. Core Business (required only for Contract)
        if (vendorType === 'Contract') {
            if (isDropdownEmpty('bulkyPageCoreBusiness', 'bulkyPageCoreBusinessSelectedText')) {
                focusField('bulkyPageCoreBusiness', 'Core Business');
                return false;
            }
        }
        
        // 3. Sub Core Business (required only for Contract)
        if (vendorType === 'Contract') {
            if (isDropdownEmpty('bulkyPageSubCoreBusiness', 'bulkyPageSubCoreBusinessSelectedText')) {
                focusField('bulkyPageSubCoreBusiness', 'Sub Core Business');
                return false;
            }
        }
        
        // 4. Contract No (required only for Contract)
        if (vendorType === 'Contract') {
            if (isDropdownEmpty('bulkyPageContractNo', 'bulkyPageContractNoSelectedText')) {
                focusField('bulkyPageContractNo', 'Contract No');
                return false;
            }
        }
        
        // 5. Contract Period (required only for Contract)
        if (vendorType === 'Contract') {
            const contractPeriod = form.querySelector('#bulkyPageContractPeriod');
            if (isEmpty(contractPeriod)) {
                focusField('bulkyPageContractPeriod', 'Contract Period');
                return false;
            }
        }
        
        // 6. Top Type (required for Release - always visible when Release)
        if (isDropdownEmpty('bulkyPageTopType', 'bulkyPageTopTypeSelectedText')) {
            focusField('bulkyPageTopType', 'Top Type');
            return false;
        }
        
        // 7. Description (required for Release - always visible when Release)
        const description = form.querySelector('#bulkyPageDescription');
        if (isEmpty(description)) {
            focusField('bulkyPageDescription', 'Description');
            return false;
        }
        
        // 8. Remark Approval (required for Release - always visible when Release)
        const remarkApproval = form.querySelector('#bulkyPageRemarkApproval');
        if (isEmpty(remarkApproval)) {
            focusField('bulkyPageRemarkApproval', 'Remark Approval');
            return false;
        }
        
        return true;
    }

    /**
     * Submit release (single PR)
     */
    async submitRelease() {
        if (!this.currentPRNumber) {
            this.showAlertModal('No Purchase Request selected', 'warning');
            return;
        }

        const form = document.getElementById('bulkyReleaseForm');
        if (!form) {
            return;
        }

        // Custom validation - must pass before proceeding
        const isValid = this.validateSingleReleaseForm(form);
        if (!isValid) {
            // Validation failed, stop submission
            return;
        }

        const formData = new FormData(form);
        const decision = formData.get('decision');
        const vendorType = formData.get('vendorType');
        const vendorId = formData.get('vendorId');
        const coreBusiness = formData.get('coreBusiness');
        const subCoreBusiness = formData.get('subCoreBusiness');
        const contractNo = formData.get('contractNo');
        // Get contractPeriod from disabled field directly (FormData doesn't include disabled fields)
        const contractPeriodInput = form.querySelector('#contractPeriod') || form.querySelector('#bulkyPageContractPeriod');
        const contractPeriod = contractPeriodInput ? contractPeriodInput.value : formData.get('contractPeriod');
        // Get description from disabled field directly (FormData doesn't include disabled fields)
        const descriptionInput = form.querySelector('#description') || form.querySelector('#bulkyPageDescription');
        const description = descriptionInput ? descriptionInput.value : formData.get('description');
        const topType = formData.get('topType');
        const remarkApproval = formData.get('remarkApproval');

        if (decision !== 'Release' && decision !== 'Reject') {
            this.showAlertModal('Invalid decision selected', 'warning');
            return;
        }

        // Show confirmation modal
        const confirmed = await this.showConfirmModal(
            `Are you sure you want to ${decision.toUpperCase()} this Purchase Request?`,
            `This action will ${decision.toLowerCase()} the Purchase Request ${this.currentPRNumber}. This action cannot be undone.`
        );

        if (confirmed) {
            // Get submit button and set loading state
            const submitBtn = document.getElementById('submitReleaseBtn');
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
                    decision: decision,
                    vendorType: vendorType,
                    vendorId: vendorId,
                    coreBusiness: coreBusiness,
                    subCoreBusiness: subCoreBusiness,
                    contractNo: contractNo,
                    contractPeriod: contractPeriod,
                    topType: topType,
                    description: description,
                    remarkApproval: remarkApproval || ''
                };

                if (!this.manager || !this.manager.apiModule) {
                    throw new Error('API module not available');
                }

                const response = await this.manager.apiModule.submitRelease(this.currentPRNumber, dto);
                
                // Extract response data
                const responseData = response.data || response;
                const poNumber = responseData?.poNumber || responseData?.PONumber || null;

                if (decision === 'Release') {
                    // For Release: Show success modal with PO Number (like Create PR)
                    const successMessage = 'Purchase Request has been released successfully';
                    this.showSuccessModal(
                        'PR Released Successfully!',
                        successMessage,
                        poNumber,
                        () => {
                            // Reload page to Release PR List
                            window.location.href = '/Procurement/PurchaseRequest/Release';
                        }
                    );
                } else {
                    // For Reject: Show regular alert modal (no PO Number)
                    const successMessage = 'Purchase Request has been rejected successfully';
                    this.showAlertModal(
                        successMessage,
                        'success',
                        () => {
                            // Reload page to Release PR List
                            window.location.href = '/Procurement/PurchaseRequest/Release';
                        }
                    );
                }

            } catch (error) {
                console.error('Error submitting release:', error);
                this.showAlertModal('Failed to submit release: ' + (error.message || 'Unknown error'), 'danger');
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
     * Submit bulk release
     */
    async submitBulkyRelease() {
        if (!this.manager || !this.manager.selectedPRNumbers || this.manager.selectedPRNumbers.size === 0) {
            this.showAlertModal('Please select at least one Purchase Request', 'warning');
            return;
        }

        // Find the correct form - look for form with bulkyPageReleaseDecision (bulky page form)
        const decisionSelect = document.getElementById('bulkyPageReleaseDecision');
        const form = decisionSelect ? decisionSelect.closest('form') : document.getElementById('bulkyReleaseForm');
        
        if (!form) {
            this.showAlertModal('Form not found', 'error');
            return;
        }

        // Custom validation - must pass before proceeding
        const isValid = this.validateBulkyReleaseForm(form);
        if (!isValid) {
            // Validation failed, stop submission
            return;
        }

        const formData = new FormData(form);
        const decision = formData.get('decision');
        const vendorType = formData.get('vendorType');
        const vendorId = formData.get('vendorId');
        const coreBusiness = formData.get('coreBusiness');
        const subCoreBusiness = formData.get('subCoreBusiness');
        const contractNo = formData.get('contractNo');
        // Get contractPeriod from disabled field directly (FormData doesn't include disabled fields)
        const contractPeriodInput = form.querySelector('#contractPeriod') || form.querySelector('#bulkyPageContractPeriod');
        const contractPeriod = contractPeriodInput ? contractPeriodInput.value : formData.get('contractPeriod');
        // Get description from disabled field directly (FormData doesn't include disabled fields)
        const descriptionInput = form.querySelector('#description') || form.querySelector('#bulkyPageDescription');
        const description = descriptionInput ? descriptionInput.value : formData.get('description');
        const topType = formData.get('topType');
        const remarkApproval = formData.get('remarkApproval');

        if (decision !== 'Release' && decision !== 'Reject') {
            this.showAlertModal('Invalid decision selected', 'warning');
            return;
        }

        // Show confirmation
        const confirmed = await this.showConfirmModal(
            `Are you sure you want to ${decision.toUpperCase()} ${this.manager.selectedPRNumbers.size} Purchase Request(s)?`,
            `This action will ${decision.toLowerCase()} the selected Purchase Requests. This action cannot be undone.`
        );

        if (confirmed) {
            // Get submit button and set loading state
            const submitBtn = document.getElementById('submitBulkyReleaseBtn');
            let originalText = '';
            let buttonWasDisabled = false;
            if (submitBtn) {
                originalText = submitBtn.innerHTML;
                buttonWasDisabled = submitBtn.disabled;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="icon-base bx bx-loader-circle me-2 spin"></i>Submitting...';
            }

            try {
                const prNumbers = Array.from(this.manager.selectedPRNumbers);
                
                const dto = {
                    prNumbers: prNumbers,
                    Decision: decision,
                    VendorType: vendorType,
                    VendorId: vendorId,
                    CoreBusiness: coreBusiness,
                    SubCoreBusiness: subCoreBusiness,
                    ContractNo: contractNo,
                    ContractPeriod: contractPeriod,
                    TopType: topType,
                    Description: description,
                    RemarkApproval: remarkApproval || ''
                };

                if (!this.manager || !this.manager.apiModule) {
                    throw new Error('API module not available');
                }

                const response = await this.manager.apiModule.submitBulkRelease(dto);
                
                // Handle response - check for failed PRs
                const result = response.data || response;
                let message = '';
                
                if (result && typeof result === 'object') {
                    const successCount = result.successCount || result.SuccessCount || 0;
                    const failedCount = result.failedCount || result.FailedCount || 0;
                    const failedPRs = result.failedPRNumbers || result.FailedPRNumbers || [];
                    const resultMessage = result.message || result.Message || '';
                    
                    if (failedCount > 0) {
                        message = `${successCount} Purchase Request(s) ${decision.toLowerCase()}ed successfully. `;
                        message += `${failedCount} Purchase Request(s) failed.`;
                        if (failedPRs.length > 0) {
                            message += `\n\nFailed PR Numbers:\n${failedPRs.join(', ')}`;
                        }
                        this.showAlertModal(message, 'warning', () => {
                            // Reload page to Release PR List
                            window.location.href = '/Procurement/PurchaseRequest/Release';
                        });
                    } else {
                        message = resultMessage || 
                            (decision === 'Release'
                                ? `${successCount} Purchase Request(s) have been released successfully`
                                : `${successCount} Purchase Request(s) have been rejected successfully`);
                        
                        // For Release decision, check if we have PR-PO mapping to show in popup
                        const prToPOMapping = result.prToPOMapping || result.PRToPOMapping;
                        
                        if (decision === 'Release' && prToPOMapping && Object.keys(prToPOMapping).length > 0) {
                            // Show success modal first, then show PR-PO mapping popup
                            this.showAlertModal(
                                message,
                                'success',
                                () => {
                                    // Show PR-PO mapping popup after user clicks OK
                                    this.showPRPOMappingPopup(prToPOMapping);
                                }
                            );
                        } else {
                            // For Reject or if no mapping, just reload page
                            this.showAlertModal(
                                message,
                                'success',
                                () => {
                                    // Reload page to Release PR List
                                    window.location.href = '/Procurement/PurchaseRequest/Release';
                                }
                            );
                        }
                    }
                } else {
                    // Fallback: result is not an object or is null/undefined
                    const successMessage = decision === 'Release'
                        ? `${prNumbers.length} Purchase Request(s) have been released successfully`
                        : `${prNumbers.length} Purchase Request(s) have been rejected successfully`;
                    
                    this.showAlertModal(
                        successMessage,
                        'success',
                        () => {
                            // Reload page to Release PR List
                            window.location.href = '/Procurement/PurchaseRequest/Release';
                        }
                    );
                }
            } catch (error) {
                    console.error('Error processing bulk release:', error);
                    this.showAlertModal('Failed to process bulk release: ' + (error.message || 'Unknown error'), 'danger');
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
     * Show success modal with PO Number
     */
    showSuccessModal(title, message, poNumber, onClose = null) {
        if (typeof Swal === 'undefined') {
            // Fallback to basic alert if SweetAlert2 is not loaded
            const fullMessage = poNumber ? `${message}\n\nPO Number: ${poNumber}` : message;
            window.alert(`${title}\n\n${fullMessage}`);
            if (onClose && typeof onClose === 'function') {
                onClose();
            }
            return;
        }

        let htmlContent = `<div style="text-align: center;">${this.escapeHtml(message)}</div>`;
        if (poNumber) {
            htmlContent += `<div style="text-align: center; margin-top: 1rem;"><strong>PO Number: <span style="color: #696cff;">${this.escapeHtml(poNumber)}</span></strong></div>`;
        }

        Swal.fire({
            title: title,
            html: htmlContent,
            icon: 'success',
            confirmButtonColor: '#696cff',
            confirmButtonText: 'OK',
            allowOutsideClick: false,
            allowEscapeKey: false,
            backdrop: true,
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
     * Show PR-PO mapping popup
     */
    showPRPOMappingPopup(prToPOMapping) {
        if (typeof Swal === 'undefined') {
            // Fallback to basic alert if SweetAlert2 is not loaded
            let message = 'PR Number | PO Number\n';
            message += '-------------------\n';
            for (const [prNumber, poNumber] of Object.entries(prToPOMapping)) {
                message += `${prNumber} | ${poNumber}\n`;
            }
            window.alert(message);
            // Reload page after showing alert
            window.location.href = '/Procurement/PurchaseRequest/Release';
            return;
        }

        // Build table HTML
        let tableHTML = `
            <div style="max-height: 400px; overflow-y: auto;">
                <table class="table table-bordered table-striped" style="margin-bottom: 0;">
                    <thead style="position: sticky; top: 0; background-color: #f8f9fa; z-index: 10;">
                        <tr>
                            <th style="text-align: center; font-weight: bold;">PR Number</th>
                            <th style="text-align: center; font-weight: bold;">PO Number</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        // Sort by PR Number for consistent display
        const sortedEntries = Object.entries(prToPOMapping).sort((a, b) => a[0].localeCompare(b[0]));

        for (const [prNumber, poNumber] of sortedEntries) {
            tableHTML += `
                <tr>
                    <td style="text-align: center;">${this.escapeHtml(prNumber)}</td>
                    <td style="text-align: center; color: #696cff; font-weight: bold;">${this.escapeHtml(poNumber)}</td>
                </tr>
            `;
        }

        tableHTML += `
                    </tbody>
                </table>
            </div>
        `;

        Swal.fire({
            title: 'Bulky Release - PR and PO Numbers',
            html: tableHTML,
            icon: 'success',
            confirmButtonColor: '#696cff',
            confirmButtonText: 'OK',
            width: '600px',
            allowOutsideClick: false,
            allowEscapeKey: true,
            backdrop: true,
            animation: true,
            customClass: {
                confirmButton: 'btn btn-primary',
                popup: 'pr-po-mapping-popup'
            }
        }).then((result) => {
            // Reload page to Release PR List after user clicks OK
            window.location.href = '/Procurement/PurchaseRequest/Release';
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

            // Ensure global preview state exists
            if (typeof window.__currentDocumentPreview === 'undefined') {
                window.__currentDocumentPreview = {
                    id: null,
                    fileName: '',
                    filePath: '',
                    downloadUrl: null
                };
            }
            if (typeof window.__viewedDocuments === 'undefined') {
                window.__viewedDocuments = new Set();
            }

            // Save current preview info
            window.__currentDocumentPreview.id = documentId;
            window.__currentDocumentPreview.fileName = fileName || 'document';
            window.__currentDocumentPreview.filePath = filePath;
            window.__currentDocumentPreview.downloadUrl = downloadUrl;

            // Mark document as viewed and update UI badge
            try {
                if (typeof window.__viewedDocuments === 'undefined') {
                    try { window.__viewedDocuments = new Set(); } catch (e) { window.__viewedDocuments = []; }
                }
                if (window.__viewedDocuments && typeof window.__viewedDocuments.add === 'function') {
                    window.__viewedDocuments.add(String(documentId));
                } else if (Array.isArray(window.__viewedDocuments)) {
                    if (!window.__viewedDocuments.includes(String(documentId))) {
                        window.__viewedDocuments.push(String(documentId));
                    }
                }

                // Update badge display for this document row
                const idStr = String(documentId);
                const viewedEls = document.querySelectorAll('.doc-viewed-badge[data-doc-id="' + idStr + '"]');
                const notViewedEls = document.querySelectorAll('.doc-not-viewed-badge[data-doc-id="' + idStr + '"]');
                viewedEls.forEach(e => { e.style.display = 'inline-block'; });
                notViewedEls.forEach(e => { e.style.display = 'none'; });
            } catch (e) {
                // ignore UI update errors
            }

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

            // Define global helper for downloading from modal if not present
            if (typeof window.downloadDocumentFromModal === 'undefined') {
                window.downloadDocumentFromModal = function () {
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
                        alert('Failed to download document: ' + (error.message || 'Unknown error'));
                    }
                };
            }
        } catch (error) {
            console.error('Error opening document preview:', error);
            this.showAlertModal('Failed to open document: ' + (error.message || 'Unknown error'), 'danger');
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
     * Format date
     */
    formatDate(dateValue) {
        if (!dateValue || dateValue === '-') return '-';
        try {
            const date = new Date(dateValue);
            if (isNaN(date.getTime())) return dateValue;
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        } catch {
            return dateValue;
        }
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
        const tbody = document.getElementById('releaseTableBody');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="12" class="text-center py-4">
                        <div class="text-danger">${this.escapeHtml(message)}</div>
                    </td>
                </tr>
            `;
        }
    }

    /**
     * Show success message
     */
    showSuccessMessage(message) {
        const existingAlert = document.querySelector('.alert-success');
        if (existingAlert) {
            existingAlert.remove();
        }

        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success alert-dismissible fade show';
        alertDiv.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="icon-base bx bx-check-circle me-2"></i>
                <span>${this.escapeHtml(message)}</span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;

        const container = document.querySelector('.release-list-container');
        if (container) {
            container.insertBefore(alertDiv, container.firstChild);
        } else {
            document.body.insertBefore(alertDiv, document.body.firstChild);
        }

        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 3000);
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.ReleaseListView = ReleaseListView;
}