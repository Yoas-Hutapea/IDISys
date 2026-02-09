/**
 * ApprovalListView Module
 * Handles view approval functionality for Approval List
 */
class ApprovalListView {
    constructor(managerInstance) {
        this.manager = managerInstance;
        this.viewPRItems = [];
        this.viewPRDocuments = [];
        this.viewPRAdditional = null;
        this.currentPRNumber = null;
        // Ensure global viewed documents set exists
        if (typeof window !== 'undefined') {
            if (typeof window.__viewedDocuments === 'undefined') {
                try {
                    window.__viewedDocuments = new Set();
                } catch (e) {
                    // fallback for environments without Set
                    window.__viewedDocuments = [];
                }
            }
        }
    }

    /**
     * View approval details
     */
    async viewApproval(prNumber) {
        // Store currentPRNumber in both viewModule and manager for backward compatibility
        this.currentPRNumber = prNumber;
        if (this.manager) {
            this.manager.currentPRNumber = prNumber;
        }

        try {
            this.showViewApprovalLoading();
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

            // Check if status is 4 (Finish Purchase Request) - view only mode
            const statusID = pr.mstApprovalStatusID || pr.MstApprovalStatusID;
            const isViewOnly = statusID === 4;

            await this.loadPRDetailsForView(prNumber, pr);
            await this.populateViewApproval(pr, isViewOnly);
            this.showViewApprovalSection();

        } catch (error) {
            console.error('Error loading approval:', error);
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
        const viewApprovalSection = document.getElementById('viewApprovalSection');

        if (filterSection) filterSection.style.display = 'block';
        if (listSection) listSection.style.display = 'block';
        if (viewApprovalSection) viewApprovalSection.style.display = 'none';
    }

    /**
     * Show view approval section
     */
    showViewApprovalSection() {
        const viewApprovalSection = document.getElementById('viewApprovalSection');
        if (viewApprovalSection) {
            viewApprovalSection.style.display = 'block';
        }
    }

    /**
     * Show view approval loading
     */
    showViewApprovalLoading() {
        const viewApprovalSection = document.getElementById('viewApprovalSection');
        const loadingDiv = document.getElementById('viewApprovalLoading');
        const dataDiv = document.getElementById('viewApprovalData');

        if (loadingDiv) loadingDiv.style.display = 'block';
        if (dataDiv) dataDiv.style.display = 'none';

        if (viewApprovalSection) {
            viewApprovalSection.style.display = 'block';
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
     * Populate view approval with PR data
     */
    async populateViewApproval(pr, isViewOnly = false) {
        const loadingDiv = document.getElementById('viewApprovalLoading');
        const dataDiv = document.getElementById('viewApprovalData');

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
        if (window.procurementSharedCache && window.procurementSharedCache.batchGetEmployeeNames) {
            employeeCache = window.procurementSharedCache;
        } else if (this.manager && this.manager.employeeCacheModule && this.manager.employeeCacheModule.batchGetEmployeeNames) {
            employeeCache = this.manager.employeeCacheModule;
        } else if (window.prListManager && window.prListManager.employeeCacheModule && window.prListManager.employeeCacheModule.batchGetEmployeeNames) {
            employeeCache = window.prListManager.employeeCacheModule;
        }

        // Collect all employee IDs for batch lookup (optimized - single API call)
        const employeeIds = [];
        if (requestorId) employeeIds.push(requestorId);
        if (applicantId) employeeIds.push(applicantId);
        if (reviewedById) employeeIds.push(reviewedById);
        if (approvedById) employeeIds.push(approvedById);
        if (confirmedById) employeeIds.push(confirmedById);

        // Batch lookup all employee names at once
        let nameMap = new Map();
        if (employeeCache && employeeCache.batchGetEmployeeNames && employeeIds.length > 0) {
            nameMap = await employeeCache.batchGetEmployeeNames(employeeIds);
        }

        // Get names from map
        const requestorName = requestorId ? (nameMap.get(requestorId.trim().toLowerCase()) || '') : '';
        const applicantName = applicantId ? (nameMap.get(applicantId.trim().toLowerCase()) || '') : '';
        const reviewedByName = reviewedById ? (nameMap.get(reviewedById.trim().toLowerCase()) || '') : '';
        const approvedByName = approvedById ? (nameMap.get(approvedById.trim().toLowerCase()) || '') : '';
        const confirmedByName = confirmedById ? (nameMap.get(confirmedById.trim().toLowerCase()) || '') : '';

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

                    // Determine viewed status (supports Set or Array fallback)
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

        // Populate Additional Section
        await this.populateAdditionalSection(pr);

        // Hide/Show approval form based on viewOnly flag
        const approvalActionSection = document.getElementById('approvalActionSection');
        if (approvalActionSection) {
            if (isViewOnly) {
                // Hide approval form for status 4 (Finish Purchase Request)
                approvalActionSection.style.display = 'none';
            } else {
                // Show approval form for other statuses
                approvalActionSection.style.display = 'block';
                // Reset approval form
                const approvalAction = document.getElementById('approvalAction');
                const approvalRemarks = document.getElementById('approvalRemarks');
                if (approvalAction) {
                    approvalAction.value = '';
                    // Clear error message when user selects an action
                    approvalAction.addEventListener('change', () => {
                        const actionError = document.getElementById('approvalActionError');
                        if (actionError) {
                            actionError.style.display = 'none';
                            actionError.textContent = '';
                        }
                    });
                }
                if (approvalRemarks) {
                    approvalRemarks.value = '';
                    // Clear error message when user starts typing
                    approvalRemarks.addEventListener('input', () => {
                        const remarksError = document.getElementById('approvalRemarksError');
                        if (remarksError) {
                            remarksError.style.display = 'none';
                            remarksError.textContent = '';
                        }
                    });
                }
            }
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

        // Load Purchase Types using API module (with shared caching)
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
     * Submit approval action
     */
    async submitApproval() {
        // Get currentPRNumber from manager if not set in viewModule
        const prNumber = this.currentPRNumber || (this.manager && this.manager.currentPRNumber);

        if (!prNumber) {
            this.showAlertModal('No Purchase Request selected', 'warning');
            return;
        }

        // Clear previous error messages
        const actionError = document.getElementById('approvalActionError');
        const remarksError = document.getElementById('approvalRemarksError');
        if (actionError) {
            actionError.style.display = 'none';
            actionError.textContent = '';
        }
        if (remarksError) {
            remarksError.style.display = 'none';
            remarksError.textContent = '';
        }

        const actionSelect = document.getElementById('approvalAction');
        const remarksTextarea = document.getElementById('approvalRemarks');

        let hasError = false;

        if (!actionSelect || !actionSelect.value) {
            if (actionError) {
                actionError.textContent = 'Please select an action (Approve or Reject)';
                actionError.style.display = 'block';
            }
            hasError = true;
        }

        const remarks = remarksTextarea ? remarksTextarea.value.trim() : '';
        if (!remarks || remarks === '') {
            if (remarksError) {
                remarksError.textContent = 'Please Filled up the Remarks field';
                remarksError.style.display = 'block';
            }
            if (remarksTextarea) {
                remarksTextarea.focus();
            }
            hasError = true;
        }

        if (hasError) {
            return;
        }

        const statusId = parseInt(actionSelect.value);

        if (!statusId || (statusId !== 1 && statusId !== 5)) {
            this.showAlertModal('Invalid action selected', 'warning');
            return;
        }

        // If approving, ensure all supporting documents have been viewed via preview modal
        if (statusId === 1) {
            try {
                const docs = Array.isArray(this.viewPRDocuments) ? this.viewPRDocuments : [];
                if (docs.length > 0) {
                    const viewedSet = window.__viewedDocuments || new Set();
                    // Normalize viewed set to strings for reliable comparison
                    let viewedNormalized = new Set();
                    try {
                        if (typeof viewedSet.has === 'function') {
                            viewedNormalized = new Set(Array.from(viewedSet).map(String));
                        } else if (Array.isArray(viewedSet)) {
                            viewedNormalized = new Set(viewedSet.map(String));
                        }
                    } catch (e) {
                        viewedNormalized = new Set();
                    }

                    const unviewed = docs.filter(d => {
                        const id = d?.id || d?.ID || d?.Id || 0;
                        return !viewedNormalized.has(String(id));
                    });
                    if (unviewed.length > 0) {
                        this.showAlertModal('You must view all supporting documents before approving.', 'warning');
                        return;
                    }
                }
            } catch (e) {
                // If any error occurs during the check, prevent approving as a safety measure
                this.showAlertModal('Unable to verify document views. Please open the supporting documents before approving.', 'warning');
                return;
            }
        }

        // Show confirmation modal
        const action = statusId === 1 ? 'APPROVE' : 'REJECT';
        const actionText = statusId === 1 ? 'approve' : 'reject';
        const confirmed = await this.showConfirmModal(
            `Are you sure you want to ${action} this Purchase Request?`,
            `This action will ${actionText} the Purchase Request ${prNumber}. This action cannot be undone.`
        );

        if (confirmed) {
            // Get submit button and set loading state
            const submitBtn = document.getElementById('submitApprovalBtn');
            let originalText = '';
            let buttonWasDisabled = false;
            if (submitBtn) {
                originalText = submitBtn.innerHTML;
                buttonWasDisabled = submitBtn.disabled;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="icon-base bx bx-loader-circle me-2 spin"></i>Submitting...';
            }

            try {
                // Get current PR status to determine Decision and Activity
                if (!this.manager || !this.manager.apiModule) {
                    throw new Error('API module not available');
                }

                const pr = await this.manager.apiModule.getPRDetails(prNumber);
                const currentStatus = pr?.mstApprovalStatusID || pr?.MstApprovalStatusID || 0;

                // Determine Decision and Activity based on current status
                let decision = "Reject";
                let activity = null;
                let approvalStatusID = currentStatus; // Use current status for mstApprovalStatusID

                if (statusId === 1) {
                    // Approve action - determine based on current status
                    if (currentStatus === 1) {
                        // Status 1 -> 2: Review
                        decision = "Reviewed";
                        activity = "Reviewer Purchase Request";
                        approvalStatusID = 1; // Current status
                    } else if (currentStatus === 2) {
                        // Status 2 -> 3: Approve
                        decision = "Approved";
                        activity = "Approver Purchase Request";
                        approvalStatusID = 2; // Current status
                    } else if (currentStatus === 3) {
                        // Status 3 -> 4: Confirm
                        decision = "Confirmed";
                        activity = "Confirmer Purchase Request";
                        approvalStatusID = 3; // Current status
                    } else if (currentStatus === 4) {
                        // Status 4: Finish
                        decision = "Finished";
                        activity = "Finish Purchase Request";
                        approvalStatusID = 4; // Current status
                    } else {
                        // Default: Approved (for backward compatibility)
                        decision = "Approved";
                        activity = "Approver Purchase Request";
                        approvalStatusID = currentStatus || 1;
                    }
                } else if (statusId === 5) {
                    // Reject action
                    decision = "Reject";
                    activity = "Reject Purchase Request";
                    approvalStatusID = 5; // Reject status
                }

                const dto = {
                    trxPROPurchaseRequestNumber: prNumber,
                    mstApprovalStatusID: approvalStatusID, // Current status for tracking
                    Remark: remarks,
                    Decision: decision,
                    Activity: activity
                };

                await this.manager.apiModule.submitApproval(prNumber, dto);

                this.showAlertModal(
                    `Purchase Request has been ${actionText} successfully`,
                    'success',
                    () => {
                        // Reload page to Approval PR List
                        window.location.href = '/Procurement/PurchaseRequest/Approval';
                    }
                );

            } catch (error) {
                console.error('Error submitting approval:', error);
                this.showAlertModal('Failed to submit approval: ' + (error.message || 'Unknown error'), 'danger');
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

            // Mark document as viewed (used to prevent approve before viewing) and update UI badge
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
        const tbody = document.getElementById('approvalTableBody');
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
}

// Make available globally
if (typeof window !== 'undefined') {
    window.ApprovalListView = ApprovalListView;
}

