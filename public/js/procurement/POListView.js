/**
 * POListView Module
 * Handles view PO details functionality, history modal, and document download
 */
class POListView {
    constructor(managerInstance) {
        this.manager = managerInstance;
        this.viewPOItems = [];
        this.viewPRDocuments = [];
        this.viewPRData = null;
        this.viewPOAdditional = null;
        this.allPurchaseTypes = null;
    }

    /**
     * View PO details
     */
    async viewPO(poNumber) {
        try {
            // Show loading state
            this.showViewPOLoading();
            
            // Hide list and filter sections
            this.hideListSection();
            
            // Load PO data using API module
            if (!this.manager || !this.manager.apiModule) {
                throw new Error('API module not available');
            }

            const po = await this.manager.apiModule.getPODetails(poNumber);
            
            if (!po) {
                this.showError('Purchase Order not found');
                this.showListSection();
                return;
            }
            
            // Load PO items and PR details if needed
            const prNumber = po.prNumber || po.PRNumber || '';
            await this.loadPODetailsForView(poNumber, prNumber);
            
            // Populate view with PO data
            await this.populateViewPO(po);
            
            // Show view section
            this.showViewPOSection();
            
        } catch (error) {
            console.error('Error loading PO for view:', error);
            this.showError('Failed to load Purchase Order: ' + error.message);
            this.showListSection();
        }
    }

    /**
     * Load PO details (items, PR documents, PR data, additional) using API module
     */
    async loadPODetailsForView(poNumber, prNumber) {
        if (!this.manager || !this.manager.apiModule) {
            console.error('API module not available');
            return;
        }

        // Load PurchaseTypes and PurchaseSubTypes for formatting
        // Use shared cache to prevent duplicate API calls
        // First, check if manager's tableModule already has purchase types loaded (to share cache)
        if (!this.allPurchaseTypes && this.manager && this.manager.tableModule && this.manager.tableModule.allPurchaseTypes) {
            this.allPurchaseTypes = this.manager.tableModule.allPurchaseTypes;
        }
        
        // Load if not already loaded
        if (!this.allPurchaseTypes && this.manager && this.manager.apiModule) {
            try {
                // Use API module which uses shared cache (ProcurementSharedCache)
                // This will prevent duplicate API calls even if called from multiple places
                this.allPurchaseTypes = await this.manager.apiModule.getPurchaseTypes();
                
                // Share with tableModule if available
                if (this.manager && this.manager.tableModule) {
                    this.manager.tableModule.allPurchaseTypes = this.allPurchaseTypes;
                }
            } catch (error) {
                console.error('Error loading purchase types:', error);
                this.allPurchaseTypes = [];
            }
        }

        // Load PO items
        try {
            const items = await this.manager.apiModule.getPOItems(poNumber);
            this.viewPOItems = Array.isArray(items) ? items : [];
        } catch (error) {
            console.error('Error loading PO items:', error);
            this.viewPOItems = [];
        }

        // Load PR documents and approval data if PR Number exists
        if (prNumber) {
            try {
                const documents = await this.manager.apiModule.getPRDocuments(prNumber);
                this.viewPRDocuments = Array.isArray(documents) ? documents : [];
            } catch (error) {
                console.error('Error loading PR documents:', error);
                this.viewPRDocuments = [];
            }

            try {
                const pr = await this.manager.apiModule.getPRDetails(prNumber);
                this.viewPRData = pr || null;
                
                // Only load additional data if Type ID and SubType ID require Additional Section
                let shouldLoadAdditional = false;
                let typeId = null;
                let subTypeId = null;
                
                if (pr) {
                    // Get Type ID and Sub Type ID from PR data
                    const purchReqType = pr.purchReqType || pr.PurchReqType || '';
                    const purchReqSubType = pr.purchReqSubType || pr.PurchReqSubType || '';
                    
                    // Find Type ID
                    if (this.allPurchaseTypes && purchReqType) {
                        let type = null;
                        
                        // First, try to find by matching PurchaseRequestType or formatted display
                        type = this.allPurchaseTypes.find(t => {
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
                                type = this.allPurchaseTypes.find(t => 
                                    parseInt(t.ID || t.id || '0', 10) === typeIdInt
                                );
                            }
                        }
                        
                        if (type) {
                            typeId = parseInt(type.ID || type.id || '0', 10);
                        }
                    }
                    
                    // Load Purchase Sub Types if Type ID is found
                    if (typeId && purchReqSubType) {
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
                    
                    // Check if Additional Section is required
                    shouldLoadAdditional = this.requiresAdditionalSection(typeId, subTypeId);
                }
                
                // Load additional data only if required
                if (shouldLoadAdditional) {
                    try {
                        const additional = await this.manager.apiModule.getPRAdditional(prNumber);
                        this.viewPOAdditional = additional || null;
                    } catch (error) {
                        // If 404 error or "not found" message, it's okay - additional data doesn't exist for this PR
                        const errorMessage = error.message || error.toString() || '';
                        if (errorMessage.includes('not found') || errorMessage.includes('404') || 
                            (error.statusCode === 404) || (error.response && error.response.status === 404)) {
                            console.log('Additional data not found for PR:', prNumber, '- This is expected if PR does not have Additional data yet');
                            this.viewPOAdditional = null;
                        } else {
                            console.error('Error loading additional data:', error);
                            this.viewPOAdditional = null;
                        }
                    }
                } else {
                    // Don't load additional data if not required
                    this.viewPOAdditional = null;
                    console.log('Additional Section not required for Type ID:', typeId, 'SubType ID:', subTypeId, '- Skipping API call');
                }
            } catch (error) {
                console.error('Error loading PR data:', error);
                this.viewPRData = null;
                this.viewPOAdditional = null;
            }
        } else {
            this.viewPRDocuments = [];
            this.viewPRData = null;
            this.viewPOAdditional = null;
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
    async getAdditionalInformationSummaryHTML(po) {
        // Check if additional data exists
        if (!this.viewPOAdditional) {
            return '';
        }
        
        const additional = this.viewPOAdditional;
        
        // Get Type ID and Sub Type ID from PO data by looking up from master data
        let typeId = null;
        let subTypeId = null;
        
        const purchType = po.purchType || po.PurchType || '';
        const purchSubType = po.purchSubType || po.PurchSubType || '';
        
        // Load Purchase Types if not already loaded
        // Use shared cache to prevent duplicate API calls
        // First, check if manager's tableModule already has purchase types loaded (to share cache)
        if (!this.allPurchaseTypes && this.manager && this.manager.tableModule && this.manager.tableModule.allPurchaseTypes) {
            this.allPurchaseTypes = this.manager.tableModule.allPurchaseTypes;
        }
        
        // Load if not already loaded
        if (!this.allPurchaseTypes && purchType && this.manager && this.manager.apiModule) {
            try {
                // Use API module which uses shared cache (ProcurementSharedCache)
                // This will prevent duplicate API calls even if called from multiple places
                this.allPurchaseTypes = await this.manager.apiModule.getPurchaseTypes();
                
                // Share with tableModule if available
                if (this.manager && this.manager.tableModule) {
                    this.manager.tableModule.allPurchaseTypes = this.allPurchaseTypes;
                }
            } catch (error) {
                console.error('Error loading purchase types:', error);
            }
        }
        
        // Find Type ID
        // purchType might be formatted display (e.g., "General Category") or ID
        if (this.allPurchaseTypes && purchType) {
            let type = null;
            
            // First, try to find by matching PurchaseRequestType or formatted display
            type = this.allPurchaseTypes.find(t => {
                const typeValue = t.PurchaseRequestType || t.purchaseRequestType || '';
                const category = t.Category || t.category || '';
                const formattedDisplay = category && typeValue !== category 
                    ? `${typeValue} ${category}` 
                    : typeValue;
                return typeValue === purchType || formattedDisplay === purchType;
            });
            
            // If not found, try to parse as ID
            if (!type) {
                const typeIdInt = parseInt(purchType.trim(), 10);
                if (!isNaN(typeIdInt) && typeIdInt > 0) {
                    type = this.allPurchaseTypes.find(t => 
                        parseInt(t.ID || t.id || '0', 10) === typeIdInt
                    );
                }
            }
            
            if (type) {
                typeId = parseInt(type.ID || type.id || '0', 10);
            }
        }
        
        // Load Purchase Sub Types if Type ID is found
        if (typeId && purchSubType && this.manager && this.manager.apiModule) {
            try {
                const subTypes = await this.manager.apiModule.getPurchaseSubTypes(typeId);
                
                // purchSubType might be formatted display or ID
                let subType = null;
                
                // First, try to find by matching PurchaseRequestSubType
                subType = subTypes.find(st => 
                    (st.PurchaseRequestSubType || st.purchaseRequestSubType) === purchSubType
                );
                
                // If not found, try to parse as ID
                if (!subType) {
                    const subTypeIdInt = parseInt(purchSubType.trim(), 10);
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
     * Populate view PO with data
     */
    async populateViewPO(po) {
        const container = document.getElementById('viewPOSummaryContainer');
        if (!container) return;

        // Get employee names for approval fields from PR data
        const pr = this.viewPRData;
        let reviewedByName = '';
        let approvedByName = '';
        let confirmedByName = '';
        let applicantName = '';
        let poAuthorName = '';
        let prRequestorName = '';

        // Get employee names using shared cache or employee cache module
        let employeeCache = null;
        if (window.procurementSharedCache && window.procurementSharedCache.getEmployeeNameByEmployId) {
            employeeCache = window.procurementSharedCache;
        } else if (this.manager && this.manager.employeeCacheModule && this.manager.employeeCacheModule.getEmployeeNameByEmployId) {
            employeeCache = this.manager.employeeCacheModule;
        } else if (window.prListManager && window.prListManager.employeeCacheModule && window.prListManager.employeeCacheModule.getEmployeeNameByEmployId) {
            employeeCache = window.prListManager.employeeCacheModule;
        }

        // Get PO Author name
        const poAuthorId = po.poAuthor || po.POAuthor || '';
        if (poAuthorId && poAuthorId !== '-' && employeeCache) {
            poAuthorName = await employeeCache.getEmployeeNameByEmployId(poAuthorId);
        }

        // Get PR Requestor name
        const prRequestorId = po.prRequestor || po.PRRequestor || '';
        if (prRequestorId && prRequestorId !== '-' && employeeCache) {
            prRequestorName = await employeeCache.getEmployeeNameByEmployId(prRequestorId);
        }

        if (pr && employeeCache) {
            reviewedByName = pr.reviewedBy ? await employeeCache.getEmployeeNameByEmployId(pr.reviewedBy) : '';
            approvedByName = pr.approvedBy ? await employeeCache.getEmployeeNameByEmployId(pr.approvedBy) : '';
            confirmedByName = pr.confirmedBy ? await employeeCache.getEmployeeNameByEmployId(pr.confirmedBy) : '';
            applicantName = pr.applicant ? await employeeCache.getEmployeeNameByEmployId(pr.applicant) : '';
        }

        // Format currency
        const formatCurrency = (amount) => {
            if (amount === null || amount === undefined || amount === '') {
                return '-';
            }
            const numAmount = parseFloat(amount);
            if (isNaN(numAmount) || numAmount === 0) {
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
        };

        // Format date
        const formatDate = (date) => {
            if (!date) return '-';
            const dateObj = typeof date === 'string' ? new Date(date) : date;
            if (isNaN(dateObj.getTime())) return '-';
            return new Intl.DateTimeFormat('id-ID', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            }).format(dateObj);
        };

        // Format items table with additional columns
        const itemsTableRows = this.viewPOItems && this.viewPOItems.length > 0
            ? this.viewPOItems.map(item => {
                const itemId = item.mstPROPurchaseItemInventoryItemID || item.mstPROInventoryItemID || item.MstPROPurchaseItemInventoryItemID || item.MstPROInventoryItemID || item.ItemID || item.itemID || '-';
                const itemName = item.ItemName || item.itemName || '-';
                const description = item.ItemDescription || item.itemDescription || '-';
                const unit = item.ItemUnit || item.itemUnit || '-';
                const qty = item.ItemQty || item.itemQty || 0;
                const currencyCode = item.ItemCurrency || item.itemCurrency || item.CurrencyCode || item.currencyCode || '-';
                const unitPrice = item.UnitPrice ?? item.unitPrice ?? null;
                const amount = item.Amount ?? item.amount ?? null;
                
                // Format currency with NaN/0 handling
                const formatCurrencySafe = (amount) => {
                    if (amount === null || amount === undefined || amount === '') return '-';
                    const numAmount = parseFloat(amount);
                    if (isNaN(numAmount)) {
                        return new Intl.NumberFormat('id-ID', {
                            style: 'currency',
                            currency: 'IDR',
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 0
                        }).format(0);
                    }
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
                };
                
                return `
                    <tr>
                        <td>${this.escapeHtml(itemId)}</td>
                        <td>${this.escapeHtml(itemName)}</td>
                        <td>${this.escapeHtml(description)}</td>
                        <td>${this.escapeHtml(unit)}</td>
                        <td class="text-end">${parseFloat(qty).toLocaleString('id-ID')}</td>
                        <td>${this.escapeHtml(currencyCode)}</td>
                        <td class="text-end">${formatCurrencySafe(unitPrice)}</td>
                        <td class="text-end">${formatCurrency(amount)}</td>
                    </tr>
                `;
            }).join('')
            : '<tr><td colspan="8" class="text-center text-muted">No items</td></tr>';

        // Format documents table with separate download column
        const documentsTableRows = this.viewPRDocuments && this.viewPRDocuments.length > 0
            ? this.viewPRDocuments.map(doc => {
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
            }).join('')
            : '<tr><td colspan="3" class="text-center text-muted">No documents</td></tr>';

        // Calculate total amount
        const totalAmount = this.viewPOItems && this.viewPOItems.length > 0
            ? this.viewPOItems.reduce((sum, item) => sum + (parseFloat(item.Amount || item.amount || 0) || 0), 0)
            : (po.poAmount || po.POAmount || 0);

        // Get additional information HTML (async)
        const additionalHTML = await this.getAdditionalInformationSummaryHTML(po);

        // Get purchase type and sub type formatted (from table module if available)
        let formattedPurchaseType = po.purchType || po.PurchType || '-';
        let formattedPurchaseSubType = po.purchSubType || po.PurchSubType || '-';
        
        if (this.manager && this.manager.tableModule) {
            formattedPurchaseType = this.manager.tableModule.formatPurchaseType(po.purchType || po.PurchType || '', po, false);
            formattedPurchaseSubType = this.manager.tableModule.formatPurchaseSubType(po.purchSubType || po.PurchSubType || '', po, false);
        }

        // Populate summary HTML
        container.innerHTML = `
            <div class="row g-4 p-4">
                <!-- Purchase Information -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header" style="border-bottom: 1px solid #e9ecef;">
                            <h6 class="card-title mb-0">Purchase Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold">Purchase Order Number</label>
                                    <input type="text" class="form-control" value="${this.escapeHtml(po.purchOrderID || po.PurchOrderID || '-')}" disabled>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold">PO Date</label>
                                    <input type="text" class="form-control" value="${formatDate(po.poDate || po.PODate)}" disabled>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold">Purchase Order Name</label>
                                    <input type="text" class="form-control" value="${this.escapeHtml(po.purchOrderName || po.PurchOrderName || '-')}" disabled>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold">PO Author</label>
                                    <input type="text" class="form-control" value="${this.escapeHtml(poAuthorName || po.poAuthor || po.POAuthor || '-')}" disabled>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold">Purchase Type</label>
                                    <input type="text" class="form-control" value="${this.escapeHtml(formattedPurchaseType)}" disabled>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold">PO Amount</label>
                                    <input type="text" class="form-control" value="${formatCurrency(po.poAmount || po.POAmount)}" disabled>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold">Purchase Sub Type</label>
                                    <input type="text" class="form-control" value="${this.escapeHtml(formattedPurchaseSubType)}" disabled>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold">Status</label>
                                    <input type="text" class="form-control" value="${this.escapeHtml(po.approvalStatus || po.ApprovalStatus || '-')}" disabled>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold">PR Number</label>
                                    <input type="text" class="form-control" value="${this.escapeHtml(po.prNumber || po.PRNumber || '-')}" disabled>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold">Company</label>
                                    <input type="text" class="form-control" value="${this.escapeHtml(po.companyName || po.CompanyName || '-')}" disabled>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold">PR Requestor</label>
                                    <input type="text" class="form-control" value="${this.escapeHtml(prRequestorName || po.prRequestor || po.PRRequestor || '-')}" disabled>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold">PR Date</label>
                                    <input type="text" class="form-control" value="${formatDate(po.prDate || po.PRDate)}" disabled>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                ${additionalHTML}

                <!-- Vendor Information -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header" style="border-bottom: 1px solid #e9ecef;">
                            <h6 class="card-title mb-0">Vendor Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold">Vendor Type</label>
                                    <input type="text" class="form-control" value="${this.escapeHtml(po.vendorType || po.VendorType || '-')}" disabled>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold">Vendor Name</label>
                                    <input type="text" class="form-control" value="${this.escapeHtml(po.mstVendorVendorName || po.MstVendorVendorName || '-')}" disabled>
                                </div>
                                <div class="col-sm-6 vendor-contract-field">
                                    <label class="form-label fw-semibold">Core Business</label>
                                    <input type="text" class="form-control" value="${this.escapeHtml(po.coreBusiness || po.CoreBusiness || '-')}" disabled>
                                </div>
                                <div class="col-sm-6 vendor-contract-field">
                                    <label class="form-label fw-semibold">Sub Core Business</label>
                                    <input type="text" class="form-control" value="${this.escapeHtml(po.subCoreBusiness || po.SubCoreBusiness || '-')}" disabled>
                                </div>
                                <div class="col-sm-6 vendor-contract-field">
                                    <label class="form-label fw-semibold">Contract Number</label>
                                    <input type="text" class="form-control" value="${this.escapeHtml(po.contractNumber || po.ContractNumber || '-')}" disabled>
                                </div>
                                <div class="col-sm-6 vendor-contract-field">
                                    <label class="form-label fw-semibold">Contract Period</label>
                                    <input type="text" class="form-control" value="${this.escapeHtml(po.contractPeriod || po.ContractPeriod || '-')}" disabled>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold">TOP</label>
                                    <input type="text" class="form-control" value="${this.escapeHtml(po.topDescription || po.TOPDescription || po.TopDescription || '-')}" disabled>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">TOP Description</label>
                                    <textarea class="form-control" rows="3" disabled>${this.escapeHtml(po.descriptionVendor || po.DescriptionVendor || '-')}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detail Purchase Request -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header" style="border-bottom: 1px solid #e9ecef;">
                            <h6 class="card-title mb-0">Detail Purchase Request</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <style>
                                    /* Right-align numeric columns: Unit Price (7) and Amount (8) */
                                    .table.numeric th:nth-child(7),
                                    .table.numeric th:nth-child(8),
                                    .table.numeric td:nth-child(7),
                                    .table.numeric td:nth-child(8) {
                                        text-align: right;
                                    }
                                </style>
                                <table class="table table-striped table-hover numeric">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Item ID</th>
                                            <th>Item Name</th>
                                            <th>Description</th>
                                            <th>UoM</th>
                                            <th>Quantity</th>
                                            <th>Currency</th>
                                            <th>Unit Price</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${itemsTableRows}
                                    </tbody>
                                </table>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12 text-end">
                                    <label class="form-label fw-semibold me-2">Amount Total:</label>
                                    <input type="text" class="form-control d-inline-block text-end" value="${formatCurrency(totalAmount)}" disabled style="width: 200px; text-align: right;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Approval Assignment Summary -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header" style="border-bottom: 1px solid #e9ecef;">
                            <h6 class="card-title mb-0">Approval Assignment</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold">Requestor</label>
                                    <input type="text" class="form-control" value="${this.escapeHtml(prRequestorName || (pr ? (pr.requestor || pr.Requestor || '-') : (po.prRequestor || po.PRRequestor || '-')))}" disabled>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold">Reviewed by</label>
                                    <input type="text" class="form-control" value="${this.escapeHtml(reviewedByName || (pr ? (pr.reviewedBy || pr.ReviewedBy || '-') : '-'))}" disabled>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold">Applicant</label>
                                    <input type="text" class="form-control" value="${this.escapeHtml(applicantName || (pr ? (pr.applicant || pr.Applicant || '-') : '-'))}" disabled>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold">Approved by</label>
                                    <input type="text" class="form-control" value="${this.escapeHtml(approvedByName || (pr ? (pr.approvedBy || pr.ApprovedBy || '-') : '-'))}" disabled>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Confirmed by</label>
                                    <input type="text" class="form-control" value="${this.escapeHtml(confirmedByName || (pr ? (pr.confirmedBy || pr.ConfirmedBy || '-') : '-'))}" disabled>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Supporting Documents Summary -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header" style="border-bottom: 1px solid #e9ecef;">
                            <h6 class="card-title mb-0">Supporting Documents</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>File Name</th>
                                            <th>File Size (kb)</th>
                                            <th width="120" class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${documentsTableRows}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Hide/Show vendor contract fields based on Vendor Type
        const vendorType = po.vendorType || po.VendorType || '';
        const isNonContract = vendorType && vendorType.toLowerCase().trim() === 'non contract';
        
        // Use setTimeout to ensure DOM is ready after innerHTML is set
        setTimeout(() => {
            const contractFields = container.querySelectorAll('.vendor-contract-field');
            contractFields.forEach(field => {
                if (isNonContract) {
                    field.style.display = 'none';
                } else {
                    field.style.display = '';
                }
            });
        }, 0);
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
        const viewPOSection = document.getElementById('viewPOSection');
        
        if (filterSection) filterSection.style.display = 'block';
        if (listSection) listSection.style.display = 'block';
        if (viewPOSection) viewPOSection.style.display = 'none';
    }

    showViewPOSection() {
        const viewPOSection = document.getElementById('viewPOSection');
        if (viewPOSection) {
            viewPOSection.style.display = 'block';
        }
    }

    showViewPOLoading() {
        const viewPOSection = document.getElementById('viewPOSection');
        const viewPOSummaryContainer = document.getElementById('viewPOSummaryContainer');
        
        if (viewPOSummaryContainer) {
            viewPOSummaryContainer.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-3">Loading Purchase Order details...</div>
                </div>
            `;
        }
        
        if (viewPOSection) {
            viewPOSection.style.display = 'block';
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
            link.download = fileName;
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
     * View history
     */
    viewHistory(poNumber) {
        // Open history modal or navigate to history page
        this.showHistoryModal(poNumber);
    }

    /**
     * Show history modal
     */
    async showHistoryModal(poNumber) {
        // Pagination state
        let historyData = [];
        let currentPage = 1;
        const rowsPerPage = 10;

        // Create modal HTML with loading state
        const modalHtml = `
            <div class="modal fade" id="historyModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">History - ${this.escapeHtml(poNumber)}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div id="historyLoading" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <div class="mt-3">Loading approval history...</div>
                            </div>
                            <div id="historyContent" style="display: none;">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>PIC</th>
                                                <th>Position PIC</th>
                                                <th>Activity</th>
                                                <th>Decision</th>
                                                <th>Remark</th>
                                                <th>Activity Date</th>
                                            </tr>
                                        </thead>
                                        <tbody id="historyTableBody">
                                            <!-- History data will be populated here -->
                                        </tbody>
                                    </table>
                                </div>
                                <div id="historyPagination" class="d-flex justify-content-between align-items-center mt-3">
                                    <div id="historyPaginationInfo" class="text-muted"></div>
                                    <nav>
                                        <ul class="pagination pagination-sm mb-0" id="historyPaginationControls">
                                            <!-- Pagination controls will be populated here -->
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                            <div id="historyError" style="display: none;" class="alert alert-danger">
                                <p id="historyErrorMessage"></p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal if any
        const existingModal = document.getElementById('historyModal');
        if (existingModal) {
            existingModal.remove();
        }

        // Add modal to DOM
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('historyModal'));
        modal.show();

        // Function to render table rows for current page
        const renderTable = () => {
            const tbody = document.getElementById('historyTableBody');
            if (!tbody) return;

            const totalPages = Math.ceil(historyData.length / rowsPerPage);
            const startIndex = (currentPage - 1) * rowsPerPage;
            const endIndex = Math.min(startIndex + rowsPerPage, historyData.length);
            const pageData = historyData.slice(startIndex, endIndex);

            if (pageData.length > 0) {
                const self = this;
                tbody.innerHTML = pageData.map(item => {
                    // Format Activity Date
                    const activityDate = item.createdDate ? new Date(item.createdDate).toLocaleString('en-US', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    }) : '-';

                    // Get badge class based on decision
                    let badgeClass = 'bg-label-secondary';
                    if (item.decision) {
                        const decision = item.decision.toLowerCase();
                        if (decision === 'approved' || decision === 'confirm' || decision === 'confirmed') {
                            badgeClass = 'bg-label-success';
                        } else if (decision === 'reject' || decision === 'rejected') {
                            badgeClass = 'bg-label-danger';
                        } else if (decision === 'pending') {
                            badgeClass = 'bg-label-warning';
                        }
                    }

                    // Get data for each column
                    const pic = item.employeeName || item.mstEmployeeID || '-'; // PIC
                    const positionPIC = item.positionName || item.mstEmployeePositionID || '-'; // Position PIC
                    const activity = item.activity || '-'; // Activity
                    const decision = item.decision || '-'; // Decision
                    const remark = item.remark || '-'; // Remark
                    
                    return `
                        <tr>
                            <td>${self.escapeHtml(pic)}</td>
                            <td>${self.escapeHtml(positionPIC)}</td>
                            <td>${self.escapeHtml(activity)}</td>
                            <td><span class="badge ${badgeClass}">${self.escapeHtml(decision)}</span></td>
                            <td>${self.escapeHtml(remark)}</td>
                            <td>${self.escapeHtml(activityDate)}</td>
                        </tr>
                    `;
                }).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">No data available</td></tr>';
            }

            // Update pagination info
            const paginationInfo = document.getElementById('historyPaginationInfo');
            if (paginationInfo) {
                if (historyData.length > 0) {
                    paginationInfo.textContent = `Showing ${startIndex + 1} to ${endIndex} of ${historyData.length} entries`;
                } else {
                    paginationInfo.textContent = '';
                }
            }

            // Update pagination controls
            const paginationControls = document.getElementById('historyPaginationControls');
            if (paginationControls && totalPages > 1) {
                let paginationHtml = '';

                // Previous button
                paginationHtml += `
                    <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                        <a class="page-link" href="#" data-page="${currentPage - 1}" ${currentPage === 1 ? 'tabindex="-1" aria-disabled="true"' : ''}>Previous</a>
                    </li>
                `;

                // Page numbers
                const maxVisiblePages = 5;
                let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
                let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
                
                if (endPage - startPage < maxVisiblePages - 1) {
                    startPage = Math.max(1, endPage - maxVisiblePages + 1);
                }

                if (startPage > 1) {
                    paginationHtml += `
                        <li class="page-item">
                            <a class="page-link" href="#" data-page="1">1</a>
                        </li>
                    `;
                    if (startPage > 2) {
                        paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                }

                for (let i = startPage; i <= endPage; i++) {
                    paginationHtml += `
                        <li class="page-item ${i === currentPage ? 'active' : ''}">
                            <a class="page-link" href="#" data-page="${i}">${i}</a>
                        </li>
                    `;
                }

                if (endPage < totalPages) {
                    if (endPage < totalPages - 1) {
                        paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                    paginationHtml += `
                        <li class="page-item">
                            <a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a>
                        </li>
                    `;
                }

                // Next button
                paginationHtml += `
                    <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                        <a class="page-link" href="#" data-page="${currentPage + 1}" ${currentPage === totalPages ? 'tabindex="-1" aria-disabled="true"' : ''}>Next</a>
                    </li>
                `;

                paginationControls.innerHTML = paginationHtml;

                // Attach event listeners
                paginationControls.querySelectorAll('a.page-link').forEach(link => {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        const page = parseInt(link.getAttribute('data-page'));
                        if (page && page !== currentPage && page >= 1 && page <= totalPages) {
                            currentPage = page;
                            renderTable();
                        }
                    });
                });
            } else if (paginationControls) {
                paginationControls.innerHTML = '';
            }
        };

        try {
            // Fetch approval history from API using API module
            if (!this.manager || !this.manager.apiModule) {
                throw new Error('API module not available');
            }

            historyData = await this.manager.apiModule.getApprovalHistory(poNumber);

            // Hide loading, show content
            const loadingEl = document.getElementById('historyLoading');
            const contentEl = document.getElementById('historyContent');
            const errorEl = document.getElementById('historyError');

            if (loadingEl) loadingEl.style.display = 'none';
            if (errorEl) errorEl.style.display = 'none';

            if (historyData && Array.isArray(historyData) && historyData.length > 0) {
                currentPage = 1;
                renderTable();
                if (contentEl) contentEl.style.display = 'block';
            } else {
                // No history data
                const tbody = document.getElementById('historyTableBody');
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center">No approval history found</td></tr>';
                }
                const paginationInfo = document.getElementById('historyPaginationInfo');
                if (paginationInfo) paginationInfo.textContent = '';
                const paginationControls = document.getElementById('historyPaginationControls');
                if (paginationControls) paginationControls.innerHTML = '';
                if (contentEl) contentEl.style.display = 'block';
            }
        } catch (error) {
            console.error('Error loading approval history:', error);
            
            // Hide loading, show error
            const loadingEl = document.getElementById('historyLoading');
            const contentEl = document.getElementById('historyContent');
            const errorEl = document.getElementById('historyError');
            const errorMsgEl = document.getElementById('historyErrorMessage');

            if (loadingEl) loadingEl.style.display = 'none';
            if (contentEl) contentEl.style.display = 'none';
            if (errorEl) errorEl.style.display = 'block';
            if (errorMsgEl) {
                errorMsgEl.textContent = `Failed to load approval history: ${error.message || 'Unknown error'}`;
            }
        }
    }

    /**
     * Print PO document
     */
    printPODocument(poNumber, poId) {
        try {
            // Encode parameters
            const params = {
                ID: poId,
                PurchOrderID: poNumber
            };
            const paramEncrypted = btoa(JSON.stringify(params));
            
            // Open document in new window
            // Route: Procurement/PurchaseOrder/Document/DownloadDocument (based on SubAreaRouteConvention)
            const url = `/Procurement/PurchaseOrder/Document/DownloadDocument?paramEncrypted=${encodeURIComponent(paramEncrypted)}`;
            window.open(url, '_blank');
        } catch (error) {
            console.error('Error printing PO document:', error);
            this.showError('Failed to open PO document: ' + error.message);
        }
    }

    /**
     * Escape HTML
     */
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Show error
     */
    showError(message) {
        // You can implement a toast/alert system here
        alert(message);
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.POListView = POListView;
}

