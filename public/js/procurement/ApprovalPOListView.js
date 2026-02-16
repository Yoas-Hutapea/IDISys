/**
 * ApprovalPOListView Module
 * Handles view approval PO details functionality and document download
 * 
 * NOTE: This is a large module. Functions are organized by feature:
 * - View Approval PO: viewApproval, populateApprovalView, loadPODetailsForView
 * - Documents: populateDocumentsTable, downloadDocument
 * - Additional Information: getAdditionalInformationSummaryHTML, requiresAdditionalSection
 */
class ApprovalPOListView {
    constructor(managerInstance) {
        this.manager = managerInstance;
        this.viewPOItems = [];
        this.viewPRDocuments = [];
        this.viewPRData = null;
        this.viewPOAdditional = null;
        this.allPurchaseTypes = null;
        this.allPurchaseSubTypes = null;
        this.currentPONumber = null;
        if (typeof window !== 'undefined') {
            if (typeof window.__viewedDocuments === 'undefined') {
                try { window.__viewedDocuments = new Set(); } catch (e) { window.__viewedDocuments = []; }
            }
        }
    }

    /**
     * View Approval PO details
     */
    async viewApproval(poNumber) {
        const listSection = document.getElementById('listSection');
        const viewSection = document.getElementById('viewApprovalSection');
        const loadingDiv = document.getElementById('viewApprovalLoading');
        const dataDiv = document.getElementById('viewApprovalData');
        
        if (listSection) listSection.style.display = 'none';
        if (viewSection) viewSection.style.display = 'block';
        
        // Store current PO number
        this.currentPONumber = poNumber;
        if (this.manager) {
            this.manager.currentPONumber = poNumber;
        }
        
        // Reset validation flags and clear any previous errors
        const decisionSelect = document.getElementById('approval-decision');
        const remarksTextarea = document.getElementById('approval-remarks');
        if (decisionSelect) {
            decisionSelect.removeAttribute('data-validation-bound');
            decisionSelect.classList.remove('is-invalid');
        }
        if (remarksTextarea) {
            remarksTextarea.removeAttribute('data-validation-bound');
            remarksTextarea.classList.remove('is-invalid');
        }
        const decisionError = document.getElementById('approval-decision-error');
        const remarksError = document.getElementById('approval-remarks-error');
        if (decisionError) decisionError.style.display = 'none';
        if (remarksError) remarksError.style.display = 'none';
        
        // Show loading state
        if (loadingDiv) loadingDiv.style.display = 'block';
        if (dataDiv) dataDiv.style.display = 'none';
        
        try {
            // Load PO data using API module
            if (!this.manager || !this.manager.apiModule) {
                throw new Error('API module not available');
            }

            const poData = await this.manager.apiModule.getPODetails(poNumber);
            
            if (!poData) {
                throw new Error('Purchase Order not found');
            }
            
            // Load PO items and PR details if needed
            const prNumber = poData.prNumber || poData.PRNumber || '';
            await this.loadPODetailsForView(poNumber, prNumber);
            
            // Populate view with PO data
            await this.populateApprovalView(poData);
            
            // Hide loading, show data
            if (loadingDiv) loadingDiv.style.display = 'none';
            if (dataDiv) dataDiv.style.display = 'block';
            
            // Bind validation event listeners after form is shown
            if (this.manager && this.manager.approvalModule && this.manager.approvalModule.bindApprovalFormValidation) {
                this.manager.approvalModule.bindApprovalFormValidation();
            }
            
        } catch (error) {
            console.error('Error loading PO details:', error);
            this.showError('Failed to load Purchase Order details: ' + error.message);
            if (loadingDiv) loadingDiv.style.display = 'none';
            this.backToList();
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
            if (!Array.isArray(this.viewPOItems)) this.viewPOItems = [];
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
                        if (this.manager) {
                            this.manager.viewPOAdditional = this.viewPOAdditional;
                        }
                    } catch (error) {
                        // If 404 error or "not found" message, it's okay - additional data doesn't exist for this PR
                        const errorMessage = error.message || error.toString() || '';
                        if (errorMessage.includes('not found') || errorMessage.includes('404') || 
                            (error.statusCode === 404) || (error.response && error.response.status === 404)) {
                            console.log('Additional data not found for PR:', prNumber, '- This is expected if PR does not have Additional data yet');
                            this.viewPOAdditional = null;
                            if (this.manager) {
                                this.manager.viewPOAdditional = null;
                            }
                        } else {
                            console.error('Error loading additional data:', error);
                            this.viewPOAdditional = null;
                            if (this.manager) {
                                this.manager.viewPOAdditional = null;
                            }
                        }
                    }
                } else {
                    // Don't load additional data if not required
                    this.viewPOAdditional = null;
                    if (this.manager) {
                        this.manager.viewPOAdditional = null;
                    }
                    if (typeId || subTypeId) {
                        console.log('Additional Section not required for Type ID:', typeId, 'SubType ID:', subTypeId, '- Skipping API call');
                    }
                }
            } catch (error) {
                console.error('Error loading PR data:', error);
                this.viewPRData = null;
                this.viewPOAdditional = null;
                if (this.manager) {
                    this.manager.viewPOAdditional = null;
                }
            }
        } else {
            this.viewPRDocuments = [];
            this.viewPRData = null;
            this.viewPOAdditional = null;
            if (this.manager) {
                this.manager.viewPOAdditional = null;
            }
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
     * Populate Approval View with data
     */
    async populateApprovalView(po) {
        // Title
        const poNumber = po.purchOrderID || po.PurchOrderID || '';
        const poNumberDisplay = document.getElementById('approval-po-number-display');
        if (poNumberDisplay) poNumberDisplay.textContent = poNumber || '[Nomor PO]';

        // Get employee IDs
        const pr = this.viewPRData;
        const poAuthorId = po.poAuthor || po.POAuthor || '';
        const requestorId = pr ? (pr.requestor || pr.Requestor || '') : (po.prRequestor || po.PRRequestor || '');
        const applicantId = pr ? (pr.applicant || pr.Applicant || '') : '';
        const reviewedById = pr ? (pr.reviewedBy || pr.ReviewedBy || '') : '';
        const approvedById = pr ? (pr.approvedBy || pr.ApprovedBy || '') : '';
        const confirmedById = pr ? (pr.confirmedBy || pr.ConfirmedBy || '') : '';

        // Get employee names asynchronously using shared cache
        let employeeCache = null;
        if (window.procurementSharedCache && window.procurementSharedCache.getEmployeeNameByEmployId) {
            employeeCache = window.procurementSharedCache;
        } else if (this.manager && this.manager.employeeCacheModule && this.manager.employeeCacheModule.getEmployeeNameByEmployId) {
            employeeCache = this.manager.employeeCacheModule;
        }
        
        const employeeIds = [];
        if (poAuthorId) employeeIds.push(poAuthorId);
        if (requestorId) employeeIds.push(requestorId);
        if (applicantId) employeeIds.push(applicantId);
        if (reviewedById) employeeIds.push(reviewedById);
        if (approvedById) employeeIds.push(approvedById);
        if (confirmedById) employeeIds.push(confirmedById);
        
        let poAuthorName = '';
        let requestorName = '';
        let applicantName = '';
        let reviewedByName = '';
        let approvedByName = '';
        let confirmedByName = '';
        
        if (employeeCache && employeeIds.length > 0) {
            if (employeeCache.batchGetEmployeeNames) {
                const nameMap = await employeeCache.batchGetEmployeeNames(employeeIds);
                poAuthorName = poAuthorId ? (nameMap.get(poAuthorId.trim().toLowerCase()) || '') : '';
                requestorName = requestorId ? (nameMap.get(requestorId.trim().toLowerCase()) || '') : '';
                applicantName = applicantId ? (nameMap.get(applicantId.trim().toLowerCase()) || '') : '';
                reviewedByName = reviewedById ? (nameMap.get(reviewedById.trim().toLowerCase()) || '') : '';
                approvedByName = approvedById ? (nameMap.get(approvedById.trim().toLowerCase()) || '') : '';
                confirmedByName = confirmedById ? (nameMap.get(confirmedById.trim().toLowerCase()) || '') : '';
            } else {
                const [poAuthName, reqName, appName, revName, apprName, confName] = await Promise.all([
                    poAuthorId ? employeeCache.getEmployeeNameByEmployId(poAuthorId) : Promise.resolve(''),
                    requestorId ? employeeCache.getEmployeeNameByEmployId(requestorId) : Promise.resolve(''),
                    applicantId ? employeeCache.getEmployeeNameByEmployId(applicantId) : Promise.resolve(''),
                    reviewedById ? employeeCache.getEmployeeNameByEmployId(reviewedById) : Promise.resolve(''),
                    approvedById ? employeeCache.getEmployeeNameByEmployId(approvedById) : Promise.resolve(''),
                    confirmedById ? employeeCache.getEmployeeNameByEmployId(confirmedById) : Promise.resolve('')
                ]);
                poAuthorName = poAuthName;
                requestorName = reqName;
                applicantName = appName;
                reviewedByName = revName;
                approvedByName = apprName;
                confirmedByName = confName;
            }
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

        // Format items table
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

        // Format documents table (View button + Viewed/Not Viewed badges, same as Receive PR)
        const documentsTableRows = this.viewPRDocuments && this.viewPRDocuments.length > 0
            ? this.viewPRDocuments.map(doc => {
                const docId = doc.id || doc.ID || 0;
                const fileName = doc.fileName || doc.FileName || '-';
                const filePath = doc.filePath || doc.FilePath || '';
                const fileSize = doc.fileSize || doc.FileSize || '-';
                const escapedFileName = this.escapeHtml(fileName);
                const escapedFilePath = this.escapeHtml(filePath);
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
            }).join('')
            : '<tr><td colspan="3" class="text-center text-muted">No documents</td></tr>';

        // Calculate total amount
        const totalAmount = this.viewPOItems && this.viewPOItems.length > 0
            ? this.viewPOItems.reduce((sum, item) => sum + (parseFloat(item.Amount || item.amount || 0) || 0), 0)
            : (po.poAmount || po.POAmount || 0);

        // Purchase Information
        this.setValue('approval-po-number', poNumber);
        this.setValue('approval-po-date', formatDate(po.poDate || po.PODate));
        this.setValue('approval-po-name', po.purchOrderName || po.PurchOrderName || '');
        // PO Author - show name or ID
        this.setValue('approval-po-author', poAuthorName || poAuthorId || '');
        // Purchase Type and Sub Type - format with Category
        // Use tableModule if available for formatting
        let formattedPurchaseType = po.purchType || po.PurchType || '';
        let formattedPurchaseSubType = po.purchSubType || po.PurchSubType || '';
        
        if (this.manager && this.manager.tableModule) {
            formattedPurchaseType = this.manager.tableModule.formatPurchaseType(po.purchType || po.PurchType || '', po, false);
            
            // For Purchase Sub Type, we need to load sub types first if not already loaded
            // Get Type ID from Purchase Type
            let typeId = null;
            const purchType = po.purchType || po.PurchType || '';
            
            if (purchType && this.allPurchaseTypes) {
                // First, try to parse as ID directly (most common case)
                const typeIdInt = parseInt(purchType.toString().trim(), 10);
                if (!isNaN(typeIdInt) && typeIdInt > 0) {
                    // Check if this ID exists in allPurchaseTypes
                    const type = this.allPurchaseTypes.find(t => 
                        parseInt(t.ID || t.id || '0', 10) === typeIdInt
                    );
                    if (type) {
                        typeId = typeIdInt;
                    }
                } else {
                    // If not a number, try to find by matching PurchaseRequestType or formatted display
                    const type = this.allPurchaseTypes.find(t => {
                        const typeValue = t.PurchaseRequestType || t.purchaseRequestType || '';
                        const category = t.Category || t.category || '';
                        const formattedDisplay = category && typeValue !== category 
                            ? `${typeValue} ${category}` 
                            : typeValue;
                        return typeValue === purchType || formattedDisplay === purchType;
                    });
                    
                    if (type) {
                        typeId = parseInt(type.ID || type.id || '0', 10);
                    }
                }
            }
            
            // Load Purchase Sub Types if Type ID is found and not already loaded
            if (typeId && this.manager && this.manager.apiModule) {
                const purchSubType = po.purchSubType || po.PurchSubType || '';
                if (purchSubType) {
                    try {
                        // Load sub types for this type ID (will use cache)
                        const subTypes = await this.manager.apiModule.getPurchaseSubTypes(typeId);
                        
                        // Store in tableModule for formatting
                        if (this.manager.tableModule) {
                            if (!this.manager.tableModule.allPurchaseSubTypes) {
                                this.manager.tableModule.allPurchaseSubTypes = new Map();
                            }
                            if (this.manager.tableModule.allPurchaseSubTypes instanceof Map) {
                                this.manager.tableModule.allPurchaseSubTypes.set(typeId, subTypes);
                            } else {
                                // If it's an array, merge with existing
                                if (!Array.isArray(this.manager.tableModule.allPurchaseSubTypes)) {
                                    this.manager.tableModule.allPurchaseSubTypes = [];
                                }
                                // Add sub types that are not already in the array
                                subTypes.forEach(st => {
                                    const exists = this.manager.tableModule.allPurchaseSubTypes.find(
                                        existing => parseInt(existing.ID || existing.id || '0', 10) === parseInt(st.ID || st.id || '0', 10)
                                    );
                                    if (!exists) {
                                        this.manager.tableModule.allPurchaseSubTypes.push(st);
                                    }
                                });
                            }
                        }
                    } catch (error) {
                        console.error('Error loading purchase sub types for formatting:', error);
                    }
                }
            }
            
            // Now format Purchase Sub Type (sub types should be loaded)
            formattedPurchaseSubType = this.manager.tableModule.formatPurchaseSubType(po.purchSubType || po.PurchSubType || '', po, false);
        }
        
        this.setValue('approval-purch-type', formattedPurchaseType);
        this.setValue('approval-purch-sub-type', formattedPurchaseSubType);
        this.setValue('approval-status', po.approvalStatus || po.ApprovalStatus || '');
        this.setValue('approval-pr-number', po.prNumber || po.PRNumber || '');
        this.setValue('approval-company', po.companyName || po.CompanyName || '');
        this.setValue('approval-po-amount', formatCurrency(po.poAmount || po.POAmount));
        // PR Requestor - prioritize name, fallback to ID only if name is not available
        const prRequestorDisplay = (requestorName && requestorName.trim() !== '') ? requestorName : (requestorId || '-');
        this.setValue('approval-pr-requestor', prRequestorDisplay);
        this.setValue('approval-pr-date', formatDate(po.prDate || po.PRDate));

        // Vendor Information
        this.setValue('approval-vendor-type', po.vendorType || po.VendorType || '');
        this.setValue('approval-vendor-name', po.mstVendorVendorName || po.MstVendorVendorName || '');
        this.setValue('approval-core-business', po.coreBusiness || po.CoreBusiness || '');
        this.setValue('approval-sub-core-business', po.subCoreBusiness || po.SubCoreBusiness || '');
        this.setValue('approval-contract-number', po.contractNumber || po.ContractNumber || '');
        this.setValue('approval-contract-period', po.contractPeriod || po.ContractPeriod || '');
        this.setValue('approval-top', po.topDescription || po.TOPDescription || po.TopDescription || '');
        this.setValue('approval-top-description', po.descriptionVendor || po.DescriptionVendor || '');
        
        // Hide/Show vendor contract fields based on Vendor Type
        const vendorType = po.vendorType || po.VendorType || '';
        const isNonContract = vendorType && vendorType.toLowerCase().trim() === 'non contract';
        const contractFields = document.querySelectorAll('.vendor-contract-field');
        contractFields.forEach(field => {
            if (isNonContract) {
                field.style.display = 'none';
            } else {
                field.style.display = '';
            }
        });

        // Approval Assignment - prioritize names, fallback to IDs only if names are not available
        const approvalRequestorDisplay = (requestorName && requestorName.trim() !== '') ? requestorName : (requestorId || '-');
        const reviewedByDisplay = (reviewedByName && reviewedByName.trim() !== '') ? reviewedByName : (reviewedById || '-');
        const applicantDisplay = (applicantName && applicantName.trim() !== '') ? applicantName : (applicantId || '-');
        const approvedByDisplay = (approvedByName && approvedByName.trim() !== '') ? approvedByName : (approvedById || '-');
        const confirmedByDisplay = (confirmedByName && confirmedByName.trim() !== '') ? confirmedByName : (confirmedById || '-');
        
        this.setValue('approval-approval-requestor', approvalRequestorDisplay);
        this.setValue('approval-reviewed-by', reviewedByDisplay);
        this.setValue('approval-applicant', applicantDisplay);
        this.setValue('approval-approved-by', approvedByDisplay);
        this.setValue('approval-confirmed-by', confirmedByDisplay);

        // Amount Total
        this.setValue('approval-amount-total', formatCurrency(totalAmount));

        // Populate Items Table
        const itemsTbody = document.getElementById('approval-items-tbody');
        if (itemsTbody) {
            itemsTbody.innerHTML = itemsTableRows;
        }

        // Populate Documents Table
        const documentsTbody = document.getElementById('approval-documents-tbody');
        if (documentsTbody) {
            documentsTbody.innerHTML = documentsTableRows;
        }
        
        // Get additional information HTML (async)
        const additionalHTML = await this.getAdditionalInformationSummaryHTML(po);
        
        // Inject Additional section HTML if exists
        const additionalSectionContainer = document.getElementById('approval-additional-section-container');
        if (additionalSectionContainer) {
            if (additionalHTML) {
                additionalSectionContainer.innerHTML = additionalHTML;
                additionalSectionContainer.style.display = 'block';
            } else {
                additionalSectionContainer.innerHTML = '';
                additionalSectionContainer.style.display = 'none';
            }
        } else if (additionalHTML) {
            // If container doesn't exist, try to inject before Items Table section
            // This is a fallback - ideally the container should exist in the HTML partial
            console.warn('Additional section container not found. Additional section HTML generated but not displayed.');
        }
    }

    /**
     * Get Additional Information Summary HTML
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
        if (!this.allPurchaseTypes && purchType && this.manager && this.manager.apiModule) {
            try {
                this.allPurchaseTypes = await this.manager.apiModule.getPurchaseTypes();
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
     * Open document preview in modal (same as Receive PR). View button opens preview; Download in modal triggers download.
     */
    async downloadDocument(documentId, fileName) {
        try {
            if (!documentId || documentId === 0) {
                alert('Invalid document ID');
                return;
            }

            const button = typeof this === 'object' && this.nodeName === 'BUTTON' ? this : null;
            const filePath = button?.getAttribute('data-file-path') || '';
            const fileFileName = button?.getAttribute('data-file-name') || fileName || 'document';

            if (typeof API_URLS === 'undefined') {
                alert('API configuration not found');
                return;
            }
            if (typeof window.normalizedApiUrls === 'undefined') {
                window.normalizedApiUrls = {};
            }
            for (const key in API_URLS) {
                if (API_URLS.hasOwnProperty(key)) {
                    window.normalizedApiUrls[key.toLowerCase()] = API_URLS[key];
                }
            }
            const apiType = 'procurement';
            const baseUrl = window.normalizedApiUrls[apiType];
            if (!baseUrl) {
                const availableKeys = Object.keys(window.normalizedApiUrls).join(', ');
                alert(`API configuration not found for Procurement. Available keys: ${availableKeys}`);
                return;
            }
            const endpoint = `/Procurement/PurchaseRequest/PurchaseRequestDocuments/${documentId}/download`;
            const downloadUrl = baseUrl + endpoint;
            const previewUrl = downloadUrl + (downloadUrl.indexOf('?') >= 0 ? '&' : '?') + 'preview=1';

            if (typeof window.__currentDocumentPreview === 'undefined') {
                window.__currentDocumentPreview = { id: null, fileName: '', filePath: '', downloadUrl: null };
            }
            if (typeof window.__viewedDocuments === 'undefined') {
                window.__viewedDocuments = new Set();
            }

            window.__currentDocumentPreview.id = documentId;
            window.__currentDocumentPreview.fileName = fileFileName;
            window.__currentDocumentPreview.filePath = filePath;
            window.__currentDocumentPreview.downloadUrl = downloadUrl;

            try {
                if (window.__viewedDocuments && typeof window.__viewedDocuments.add === 'function') {
                    window.__viewedDocuments.add(String(documentId));
                } else if (Array.isArray(window.__viewedDocuments)) {
                    if (!window.__viewedDocuments.includes(String(documentId))) {
                        window.__viewedDocuments.push(String(documentId));
                    }
                }
                const idStr = String(documentId);
                const viewedEls = document.querySelectorAll('.doc-viewed-badge[data-doc-id="' + idStr + '"]');
                const notViewedEls = document.querySelectorAll('.doc-not-viewed-badge[data-doc-id="' + idStr + '"]');
                viewedEls.forEach(e => { e.style.display = 'inline-block'; });
                notViewedEls.forEach(e => { e.style.display = 'none'; });
            } catch (e) {}

            const filenameEl = document.getElementById('documentPreviewFilename');
            const frame = document.getElementById('documentPreviewFrame');
            const modalEl = document.getElementById('documentPreviewModal');
            if (filenameEl) filenameEl.textContent = window.__currentDocumentPreview.fileName;
            if (frame) frame.src = previewUrl;

            if (modalEl && typeof bootstrap !== 'undefined') {
                try {
                    if (modalEl.parentNode !== document.body) {
                        document.body.appendChild(modalEl);
                    }
                } catch (e) {}
                const modalInstance = new bootstrap.Modal(modalEl);
                modalInstance.show();
            } else if (previewUrl) {
                window.open(previewUrl, '_blank');
            }

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
                    } catch (err) {
                        console.error('Error downloading document from modal:', err);
                        alert('Failed to download document: ' + (err.message || 'Unknown error'));
                    }
                };
            }
        } catch (error) {
            console.error('Error opening document preview:', error);
            alert('Failed to open document: ' + error.message);
        }
    }

    /**
     * Back to list
     */
    backToList() {
        const listSection = document.getElementById('listSection');
        const viewSection = document.getElementById('viewApprovalSection');
        
        if (listSection) listSection.style.display = 'block';
        if (viewSection) viewSection.style.display = 'none';
    }

    /**
     * Set value to element
     */
    setValue(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            element.value = value || '';
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
        alert(message);
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.ApprovalPOListView = ApprovalPOListView;
}

