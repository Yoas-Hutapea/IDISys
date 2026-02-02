/**
 * ConfirmPOListView Module
 * Handles view confirm PO details functionality, edit items, delete items, and document download
 *
 * NOTE: This is a large module. Functions are organized by feature:
 * - View Confirm PO: viewConfirm, populateConfirmView, loadPODetailsForView
 * - Items Management: populateItemsTable, editItem, deleteItem, updateAmountTotal
 * - Documents: populateDocumentsTable, downloadDocument
 * - Additional Information: getAdditionalInformationSummaryHTML, requiresAdditionalSection
 */
class ConfirmPOListView {
    constructor(managerInstance) {
        this.manager = managerInstance;
        this.viewPOItems = [];
        this.viewPRDocuments = [];
        this.viewPRData = null;
        this.viewPOAdditional = null;
        this.allPurchaseTypes = null;
        this.deletedItemIds = new Set(); // Track deleted item IDs
        this.currentPONumber = null; // Track current PO number
        this.originalItemsData = []; // Store original items data from API
    }

    /**
     * View Confirm PO details
     */
    async viewConfirm(poNumber) {
        const listSection = document.getElementById('listSection');
        const viewSection = document.getElementById('viewConfirmSection');
        const loadingDiv = document.getElementById('viewConfirmLoading');
        const dataDiv = document.getElementById('viewConfirmData');

        if (listSection) listSection.style.display = 'none';
        if (viewSection) viewSection.style.display = 'block';

        // Reset deleted items and store current PO number
        this.deletedItemIds.clear();
        this.currentPONumber = poNumber;
        if (this.manager) {
            this.manager.deletedItemIds = this.deletedItemIds;
            this.manager.currentPONumber = poNumber;
        }

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

            // Populate view with data from correct sources
            await this.populateConfirmView(poData, this.viewPRData, this.viewPOItems, this.viewPRDocuments);

            // Hide loading, show data
            if (loadingDiv) loadingDiv.style.display = 'none';
            if (dataDiv) dataDiv.style.display = 'block';

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

            // Store original items data for tracking edits and deletes
            this.originalItemsData = [...this.viewPOItems];
            if (this.manager) {
                this.manager.originalItemsData = this.originalItemsData;
            }
        } catch (error) {
            console.error('Error loading PO items:', error);
            this.viewPOItems = [];
            this.originalItemsData = [];
            if (this.manager) {
                this.manager.originalItemsData = [];
            }
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
     * Populate Confirm View with data
     * NOTE: This function is very similar to POListView.populateViewPO but adapted for Confirm PO view
     * It uses different element IDs (confirm-* instead of view-*)
     */
    async populateConfirmView(poData, prData, itemsData, documentsData) {
        // Title
        const poNumber = poData.purchOrderID || poData.PurchOrderID || '';
        const poNumberDisplay = document.getElementById('confirm-po-number-display');
        if (poNumberDisplay) poNumberDisplay.textContent = poNumber || '[Nomor PO]';

        // Purchase Information (from trxPROPurchaseOrder and trxPROPurchaseRequest)
        this.setValue('confirm-po-number', poNumber);

        // Get employee IDs
        const requestorId = prData?.requestor || prData?.Requestor || poData.prRequestor || poData.PRRequestor || '';
        const applicantId = prData?.applicant || prData?.Applicant || '';
        const reviewedById = prData?.reviewedBy || prData?.ReviewedBy || '';
        const confirmedById = prData?.confirmedBy || prData?.ConfirmedBy || '';
        const approvedById = prData?.approvedBy || prData?.ApprovedBy || '';

        // Get employee names asynchronously using shared cache
        let employeeCache = null;
        if (window.procurementSharedCache && window.procurementSharedCache.getEmployeeNameByEmployId) {
            employeeCache = window.procurementSharedCache;
        } else if (this.manager && this.manager.employeeCacheModule && this.manager.employeeCacheModule.getEmployeeNameByEmployId) {
            employeeCache = this.manager.employeeCacheModule;
        }

        const employeeIds = [];
        if (requestorId) employeeIds.push(requestorId);
        if (applicantId) employeeIds.push(applicantId);
        if (reviewedById) employeeIds.push(reviewedById);
        if (confirmedById) employeeIds.push(confirmedById);
        if (approvedById) employeeIds.push(approvedById);

        let requestorName = '';
        let applicantName = '';
        let reviewedByName = '';
        let confirmedByName = '';
        let approvedByName = '';

        if (employeeCache && employeeIds.length > 0) {
            if (employeeCache.batchGetEmployeeNames) {
                const nameMap = await employeeCache.batchGetEmployeeNames(employeeIds);
                requestorName = requestorId ? (nameMap.get(requestorId.trim().toLowerCase()) || '') : '';
                applicantName = applicantId ? (nameMap.get(applicantId.trim().toLowerCase()) || '') : '';
                reviewedByName = reviewedById ? (nameMap.get(reviewedById.trim().toLowerCase()) || '') : '';
                confirmedByName = confirmedById ? (nameMap.get(confirmedById.trim().toLowerCase()) || '') : '';
                approvedByName = approvedById ? (nameMap.get(approvedById.trim().toLowerCase()) || '') : '';
            } else {
                const [reqName, appName, revName, confName, apprName] = await Promise.all([
                    requestorId ? employeeCache.getEmployeeNameByEmployId(requestorId) : Promise.resolve(''),
                    applicantId ? employeeCache.getEmployeeNameByEmployId(applicantId) : Promise.resolve(''),
                    reviewedById ? employeeCache.getEmployeeNameByEmployId(reviewedById) : Promise.resolve(''),
                    confirmedById ? employeeCache.getEmployeeNameByEmployId(confirmedById) : Promise.resolve(''),
                    approvedById ? employeeCache.getEmployeeNameByEmployId(approvedById) : Promise.resolve('')
                ]);
                requestorName = reqName;
                applicantName = appName;
                reviewedByName = revName;
                confirmedByName = confName;
                approvedByName = apprName;
            }
        }

        // Requestor from PR (trxPROPurchaseRequest) - show name or ID
        this.setValue('confirm-requestor', requestorName || requestorId || '');
        // Applicant from PR (trxPROPurchaseRequest) - show name or ID
        this.setValue('confirm-applicant', applicantName || applicantId || '');

        // Purchase Type and Sub Type from PO (trxPROPurchaseOrder) - format with Category
        // Use tableModule if available for formatting
        let formattedPurchaseType = poData.purchType || poData.PurchType || '';
        let formattedPurchaseSubType = poData.purchSubType || poData.PurchSubType || '';

        if (this.manager && this.manager.tableModule) {
            formattedPurchaseType = this.manager.tableModule.formatPurchaseType(poData.purchType || poData.PurchType || '', poData, false);

            // For Purchase Sub Type, we need to load sub types first if not already loaded
            // Get Type ID from Purchase Type
            let typeId = null;
            const purchType = poData.purchType || poData.PurchType || '';

            if (purchType) {
                // First, try to parse as ID directly (most common case)
                const typeIdInt = parseInt(purchType.toString().trim(), 10);
                if (!isNaN(typeIdInt) && typeIdInt > 0) {
                    // Check if this ID exists in allPurchaseTypes
                    if (this.allPurchaseTypes) {
                        const type = this.allPurchaseTypes.find(t =>
                            parseInt(t.ID || t.id || '0', 10) === typeIdInt
                        );
                        if (type) {
                            typeId = typeIdInt;
                        }
                    } else {
                        // If purchase types not loaded yet, assume purchType is the ID
                        typeId = typeIdInt;
                    }
                } else if (this.allPurchaseTypes) {
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
                const purchSubType = poData.purchSubType || poData.PurchSubType || '';
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
            formattedPurchaseSubType = this.manager.tableModule.formatPurchaseSubType(poData.purchSubType || poData.PurchSubType || '', poData, false);
        }

        this.setValue('confirm-purch-type', formattedPurchaseType);
        this.setValue('confirm-purch-sub-type', formattedPurchaseSubType);
        // Company from PR (trxPROPurchaseRequest) or PO
        const company = prData?.company || prData?.Company || poData.companyName || poData.CompanyName || '';
        this.setValue('confirm-company', company);
        // PO Name from PO (trxPROPurchaseOrder)
        this.setValue('confirm-po-name', poData.purchOrderName || poData.PurchOrderName || '');
        // Remarks from PO (trxPROPurchaseOrder) or PR
        const remarks = poData.description || poData.Description || prData?.remark || prData?.Remark || '';
        this.setValue('confirm-remarks', remarks);

        // Assign Approval (from trxPROPurchaseRequest) - show names or IDs
        this.setValue('confirm-approval-requestor', requestorName || requestorId || '');
        this.setValue('confirm-approval-applicant', applicantName || applicantId || '');
        // ReviewedBy, ConfirmedBy, ApprovedBy from PR (trxPROPurchaseRequest) - show names or IDs
        this.setValue('confirm-reviewed-by', reviewedByName || reviewedById || '');
        this.setValue('confirm-confirmed-by', confirmedByName || confirmedById || '');
        this.setValue('confirm-approved-by', approvedByName || approvedById || '');

        // Vendor Information (from trxPROPurchaseOrderAssignVendor via PurchaseOrderService.GetByPONumberAsync)
        // Vendor Type, Core Business, Sub Core Business, Contract Number, Contract Period, TOP, TOP Description
        // These come from trxPROPurchaseOrderAssignVendor table
        this.setValue('confirm-vendor-type', poData.vendorType || poData.VendorType || '');
        this.setValue('confirm-vendor-name', poData.mstVendorVendorName || poData.MstVendorVendorName || '');
        this.setValue('confirm-core-business', poData.coreBusiness || poData.CoreBusiness || '');
        this.setValue('confirm-sub-core-business', poData.subCoreBusiness || poData.SubCoreBusiness || '');
        this.setValue('confirm-contract-number', poData.contractNumber || poData.ContractNumber || '');
        this.setValue('confirm-contract-period', poData.contractPeriod || poData.ContractPeriod || '');
        // TOP from trxPROPurchaseOrderAssignVendor (via TOPDescription from mstFINInvoiceTOP)
        // TOP Description from trxPROPurchaseOrderAssignVendor.DescriptionVendor
        this.setValue('confirm-top', poData.topDescription || poData.TOPDescription || poData.TopDescription || '');
        this.setValue('confirm-top-description', poData.descriptionVendor || poData.DescriptionVendor || '');

        // Hide/Show vendor contract fields based on Vendor Type
        const vendorType = poData.vendorType || poData.VendorType || '';
        const isNonContract = vendorType && vendorType.toLowerCase().trim() === 'non contract';
        const contractFields = document.querySelectorAll('.vendor-contract-field');
        contractFields.forEach(field => {
            if (isNonContract) {
                field.style.display = 'none';
            } else {
                field.style.display = '';
            }
        });

        // Get additional information HTML (async)
        const additionalHTML = await this.getAdditionalInformationSummaryHTML(poData);

        // Inject Additional section HTML if exists
        const additionalSectionContainer = document.getElementById('confirm-additional-section-container');
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

        // Populate Items Table (from trxPROPurchaseOrderItem)
        this.populateItemsTable(itemsData);

        // Populate Documents Table (from PR documents)
        this.populateDocumentsTable(documentsData);
    }

    setValue(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            element.value = value || '';
        }
    }

    /**
     * Populate Items Table
     */
    populateItemsTable(items) {
        const tbody = document.getElementById('confirm-items-tbody');
        if (!tbody) return;

        tbody.innerHTML = '';
        // Don't clear deletedItemIds here - it should persist until submit or new view

        if (!items || items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">No items</td></tr>';
            const amountTotalEl = document.getElementById('confirm-amount-total');
            if (amountTotalEl) {
                amountTotalEl.value = '0';
            }
            return;
        }

        // Format currency helper - for display in table (Unit Price and Amount)
        const formatCurrency = (amount) => {
            if (amount === null || amount === undefined || amount === '') {
                return '0';
            }
            // Remove all thousand separators (dots and commas) before parsing
            const cleanAmount = String(amount).replace(/[.,]/g, '');
            const numAmount = parseFloat(cleanAmount);
            if (isNaN(numAmount) || numAmount === 0) {
                return '0';
            }
            // Check if it's a whole number
            if (numAmount % 1 === 0) {
                // Whole number: format without decimals
                return numAmount.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 0});
            } else {
                // Decimal number: format with up to 2 decimals
                return numAmount.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 2});
            }
        };

        // Calculate total amount
        let totalAmount = 0;
        items.forEach(item => {
            // Remove all thousand separators before parsing
            const amountStr = String(item.amount || item.Amount || 0);
            const cleanAmount = amountStr.replace(/[.,]/g, '');
            const amount = parseFloat(cleanAmount) || 0;
            totalAmount += amount;
        });

        const amountTotalEl = document.getElementById('confirm-amount-total');
        if (amountTotalEl) {
            // Format total amount - if 0 or NaN, show 0
            if (isNaN(totalAmount) || totalAmount === 0) {
                amountTotalEl.value = '0';
            } else {
                // Check if it's a whole number
                if (totalAmount % 1 === 0) {
                    // Whole number: format without decimals
                    amountTotalEl.value = totalAmount.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 0});
                } else {
                    // Decimal number: format with up to 2 decimals
                    amountTotalEl.value = totalAmount.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 2});
                }
            }
        }

        // Populate all items (no pagination), excluding deleted items
        items.forEach(item => {
            const itemDbId = item.ID || item.id || item.Id || null; // Database ID for tracking

            // Skip items that are marked for deletion
            if (itemDbId && this.deletedItemIds.has(itemDbId.toString())) {
                return;
            }

            const itemId = item.mstPROPurchaseItemInventoryItemID || item.mstPROInventoryItemID || item.MstPROPurchaseItemInventoryItemID || item.MstPROInventoryItemID || item.ItemID || item.itemID || '-';
            const itemName = item.ItemName || item.itemName || '-';
            const description = item.ItemDescription || item.itemDescription || '-';
            const unit = item.ItemUnit || item.itemUnit || '-';
            const qty = item.ItemQty || item.itemQty || 0;
            const currencyCode = item.CurrencyCode || item.currencyCode || '-';
            const unitPrice = item.UnitPrice ?? item.unitPrice ?? null;
            const amount = item.Amount ?? item.amount ?? null;

            const row = document.createElement('tr');
            if (itemDbId) {
                row.setAttribute('data-item-id', itemDbId.toString());
            }
            row.innerHTML = `
                <td>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-sm btn-outline-primary" title="Edit" onclick="confirmPOManager.viewModule.editItem(this)" data-item-id="${itemDbId || ''}">
                            <i class="icon-base bx bx-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmPOManager.viewModule.deleteItem(this)" data-item-id="${itemDbId || ''}">
                            <i class="icon-base bx bx-trash"></i>
                        </button>
                    </div>
                </td>
                <td>${this.escapeHtml(itemId)}</td>
                <td>${this.escapeHtml(itemName)}</td>
                <td class="item-description">${this.escapeHtml(description)}</td>
                <td class="item-unit">${this.escapeHtml(unit)}</td>
                <td class="text-end item-qty">${parseFloat(qty).toLocaleString('id-ID')}</td>
                <td>${this.escapeHtml(currencyCode)}</td>
                <td class="text-end item-unit-price">${formatCurrency(unitPrice)}</td>
                <td class="text-end item-amount">${formatCurrency(amount)}</td>
            `;
            // Store original data in data attributes for editing
            if (itemDbId) {
                row.setAttribute('data-original-description', description || '');
                row.setAttribute('data-original-unit', unit || '');
                row.setAttribute('data-original-qty', qty.toString());
                row.setAttribute('data-original-unit-price', (unitPrice || 0).toString());
                row.setAttribute('data-original-amount', (amount || 0).toString());
            }
            tbody.appendChild(row);
        });
    }

    /**
     * Populate Documents Table
     */
    populateDocumentsTable(documents) {
        const tbody = document.getElementById('confirm-documents-tbody');
        if (!tbody) return;

        tbody.innerHTML = '';

        if (!documents || documents.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No documents</td></tr>';
            return;
        }

        // Populate all documents (no pagination)
        documents.forEach(doc => {
            const docId = doc.id || doc.ID || 0;
            const fileName = doc.fileName || doc.FileName || '-';
            const filePath = doc.filePath || doc.FilePath || '';
            const fileSize = doc.fileSize || doc.FileSize || '-';
            const escapedFileName = this.escapeHtml(fileName);
            const escapedFilePath = this.escapeHtml(filePath);

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${escapedFileName}</td>
                <td>${this.escapeHtml(fileSize)}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-primary" title="Download" onclick="downloadDocument.call(this, ${docId})" data-file-name="${escapedFileName}" data-file-path="${escapedFilePath}">
                        <i class="icon-base bx bx-download"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    /**
     * Update Amount Total
     */
    updateAmountTotal() {
        const tbody = document.getElementById('confirm-items-tbody');
        if (!tbody) return;

        let totalAmount = 0;
        const rows = tbody.querySelectorAll('tr[data-item-id]');
        rows.forEach(row => {
            const amountText = row.querySelector('.item-amount')?.textContent?.trim() || '';
            // Parse amount - remove all thousand separators (dots and commas) before parsing
            // If text is "0" or empty, treat as 0
            if (amountText === '0' || amountText === '') {
                totalAmount += 0;
            } else {
                const cleanAmount = amountText.replace(/[.,]/g, '');
                const amount = parseFloat(cleanAmount) || 0;
                totalAmount += amount;
            }
        });

        const amountTotalEl = document.getElementById('confirm-amount-total');
        if (amountTotalEl) {
            // Format total amount - if 0 or NaN, show 0
            if (isNaN(totalAmount) || totalAmount === 0) {
                amountTotalEl.value = '0';
            } else {
                // Check if it's a whole number
                if (totalAmount % 1 === 0) {
                    // Whole number: format without decimals
                    amountTotalEl.value = totalAmount.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 0});
                } else {
                    // Decimal number: format with up to 2 decimals
                    amountTotalEl.value = totalAmount.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 2});
                }
            }
        }
    }

    /**
     * Edit Item
     */
    async editItem(button) {
        const row = button.closest('tr');
        if (!row) {
            this.showError('Item row not found');
            return;
        }

        const itemId = row.getAttribute('data-item-id');
        if (!itemId) {
            this.showError('Item ID not found. This item cannot be edited.');
            return;
        }

        // Get Item ID and Item Name from row (first and second column after Action)
        const cells = row.querySelectorAll('td');
        const displayItemId = cells.length > 1 ? cells[1].textContent?.trim() || '' : '';
        const displayItemName = cells.length > 2 ? cells[2].textContent?.trim() || '' : '';

        // Get current values from row
        const description = row.getAttribute('data-original-description') || row.querySelector('.item-description')?.textContent?.trim() || '';
        const unit = row.getAttribute('data-original-unit') || row.querySelector('.item-unit')?.textContent?.trim() || '';
        const qtyText = row.querySelector('.item-qty')?.textContent?.trim() || '0';
        // Parse qty - remove all thousand separators (both . and ,)
        const qty = parseFloat(qtyText.replace(/[.,]/g, '')) || 0;
        const unitPriceText = row.querySelector('.item-unit-price')?.textContent?.trim() || '';
        // Parse unitPrice - remove all thousand separators (both . and ,)
        const unitPrice = parseFloat(unitPriceText.replace(/[.,]/g, '')) || 0;

        // Show edit modal (async)
        await this.showEditItemModal(itemId, displayItemId, displayItemName, description, unit, qty, unitPrice, row);
    }

    /**
     * Load Units for Edit Modal
     */
    async loadUnitsForEditModal(selectedUnit) {
        const unitSelect = document.getElementById('edit-item-unit');
        if (!unitSelect) return;

        try {
            // Use API module if available
            let units = [];
            if (this.manager && this.manager.apiModule && this.manager.apiModule.getUnits) {
                units = await this.manager.apiModule.getUnits();
            } else {
                // Fallback: direct API call
                const endpoint = '/Procurement/Master/Units?isActive=true';
                const data = await apiCall('Procurement', endpoint, 'GET');
                units = data.data || data;
            }

            if (!Array.isArray(units) || units.length === 0) {
                unitSelect.innerHTML = '<option value="" disabled selected>Select UoM</option>';
                console.warn('No units found');
                return;
            }

            // Clear existing options
            unitSelect.innerHTML = '';

            // Add default option
            const selectOption = document.createElement('option');
            selectOption.value = '';
            selectOption.disabled = true;
            selectOption.selected = true;
            selectOption.textContent = 'Select UoM';
            unitSelect.appendChild(selectOption);

            const normalizedSelected = (selectedUnit || '').toString().trim().toUpperCase();
            let matchedSelection = false;

            // Add units from API
            units.forEach(unit => {
                const option = document.createElement('option');
                // Use UnitId as value, and display Unit (if available) or UnitId
                const unitValue = unit.UnitId || unit.unitId || '';
                const unitDisplay = unit.Unit || unit.unit || unit.UnitId || unit.unitId || '';
                option.value = unitValue;
                option.textContent = unitDisplay;

                // Auto-select if matches current unit (case-insensitive)
                const normalizedDisplay = (unitDisplay || '').toString().trim().toUpperCase();
                const normalizedValue = (unitValue || '').toString().trim().toUpperCase();
                if (normalizedSelected && (normalizedDisplay === normalizedSelected || normalizedValue === normalizedSelected)) {
                    option.selected = true;
                    selectOption.selected = false;
                    matchedSelection = true;
                }

                unitSelect.appendChild(option);
            });

            if (!matchedSelection && normalizedSelected) {
                // Fallback: try select by value directly
                const directMatch = Array.from(unitSelect.options).find(opt => {
                    return opt.value && opt.value.toString().trim().toUpperCase() === normalizedSelected;
                });
                if (directMatch) {
                    directMatch.selected = true;
                    selectOption.selected = false;
                }
            }

        } catch (error) {
            console.error('Error loading units:', error);
            unitSelect.innerHTML = '<option value="">Select UoM</option>';
            const errorOption = document.createElement('option');
            errorOption.value = '';
            errorOption.textContent = 'Error loading units';
            errorOption.disabled = true;
            unitSelect.appendChild(errorOption);
        }
    }

    /**
     * Show Edit Item Modal
     */
    async showEditItemModal(itemId, displayItemId, displayItemName, description, unit, qty, unitPrice, row) {
        const modalId = 'editItemModal';
        let existingModal = document.getElementById(modalId);

        if (existingModal) {
            existingModal.remove();
        }

        // Format unit price - if 0 or NaN, show 0
        const formattedUnitPrice = (isNaN(unitPrice) || unitPrice === 0) ? '0' : unitPrice.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 2});

        // Normalize unit for comparison (trim and uppercase)
        const normalizedUnit = (unit || '').toString().trim().toUpperCase();

        const modalHtml = `
            <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="editItemModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editItemModalLabel">Edit Item</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="editItemForm">
                                <input type="hidden" id="edit-item-id" value="${itemId}">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Item ID</label>
                                        <input type="text" class="form-control" id="edit-item-item-id" value="${this.escapeHtml(displayItemId)}" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Item Name</label>
                                        <input type="text" class="form-control" id="edit-item-name" value="${this.escapeHtml(displayItemName)}" disabled>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="edit-item-description" rows="3" required>${this.escapeHtml(description)}</textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">UoM <span class="text-danger">*</span></label>
                                        <select class="form-select" id="edit-item-unit" required>
                                            <option value="">Loading units...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="edit-item-qty" value="${qty > 0 ? qty.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 3}) : ''}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Unit Price <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="edit-item-unit-price" value="${formattedUnitPrice}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Amount</label>
                                        <input type="text" class="form-control" id="edit-item-amount" disabled>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="saveEditItemBtn">
                                <i class="icon-base bx bx-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = new bootstrap.Modal(document.getElementById(modalId));

        // Load units from API
        await this.loadUnitsForEditModal(normalizedUnit);

        // Format number inputs
        const qtyInput = document.getElementById('edit-item-qty');
        const unitPriceInput = document.getElementById('edit-item-unit-price');

        // Initialize amount calculation
        const amountField = document.getElementById('edit-item-amount');
        const calculateAmount = () => {
            // Parse qty - remove thousand separators and parse
            let qtyValue = qtyInput.value.replace(/[.,]/g, '');
            const qty = parseFloat(qtyValue) || 0;

            // Parse unitPrice - remove thousand separators and parse
            let unitPriceValue = unitPriceInput.value.replace(/[.,]/g, '');
            const unitPrice = parseFloat(unitPriceValue) || 0;

            const amount = qty * unitPrice;
            if (amountField) {
                // If amount is 0 or NaN, show 0
                if (isNaN(amount) || amount === 0) {
                    amountField.value = '0';
                } else {
                    // Check if amount is a whole number (no decimal part)
                    const isWholeNumber = amount % 1 === 0;
                    if (isWholeNumber) {
                        // Whole number - format as integer with thousand separator (no decimal places)
                        amountField.value = Math.round(amount).toLocaleString('id-ID');
                    } else {
                        // Has decimal - format with up to 2 decimal places
                        amountField.value = amount.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 2});
                    }
                }
            }
        };

        // Calculate initial amount after a short delay to ensure DOM is ready
        setTimeout(() => {
            calculateAmount();
        }, 100);

        // Format quantity input (max 3 decimal places, with thousand separator)
        qtyInput.addEventListener('input', (e) => {
            let value = e.target.value.replace(/[.,]/g, '');
            const validPattern = /^\d*\.?\d{0,3}$/;
            if (validPattern.test(value) || value === '') {
                if (value === '') {
                    e.target.value = '';
                } else {
                    // Format with thousand separator but preserve decimal places
                    const parts = value.split('.');
                    const intPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    const decPart = parts.length > 1 ? ',' + parts[1] : '';
                    e.target.value = intPart + decPart;
                }
                calculateAmount();
            } else {
                e.target.value = e.target.getAttribute('data-prev-value') || '';
            }
            e.target.setAttribute('data-prev-value', e.target.value.replace(/[.,]/g, ''));
        });

        // Format unit price input (max 2 decimal places, with thousand separator)
        unitPriceInput.addEventListener('input', (e) => {
            let value = e.target.value.replace(/[.,]/g, '');
            const validPattern = /^\d*\.?\d{0,2}$/;
            if (validPattern.test(value) || value === '') {
                if (value === '') {
                    e.target.value = '';
                } else {
                    // Format with thousand separator but preserve decimal places
                    const parts = value.split('.');
                    const intPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    const decPart = parts.length > 1 ? ',' + parts[1] : '';
                    e.target.value = intPart + decPart;
                }
                calculateAmount();
            } else {
                e.target.value = e.target.getAttribute('data-prev-value') || '';
            }
            e.target.setAttribute('data-prev-value', e.target.value.replace(/[.,]/g, ''));
        });

        // Save button handler
        const saveBtn = document.getElementById('saveEditItemBtn');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                const form = document.getElementById('editItemForm');
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                const newDescription = document.getElementById('edit-item-description').value.trim();
                const newUnit = document.getElementById('edit-item-unit').value.trim();
                // Parse qty - remove all thousand separators (both . and ,)
                const newQty = parseFloat(qtyInput.value.replace(/[.,]/g, '')) || 0;
                // Parse unitPrice - remove all thousand separators (both . and ,)
                const newUnitPrice = parseFloat(unitPriceInput.value.replace(/[.,]/g, '')) || 0;
                const newAmount = newQty * newUnitPrice;

                // Format unit price and amount - if 0 or NaN, show 0
                let formattedUnitPrice = '0';
                if (!isNaN(newUnitPrice) && newUnitPrice !== 0) {
                    const isWholeNumber = newUnitPrice % 1 === 0;
                    formattedUnitPrice = isWholeNumber
                        ? Math.round(newUnitPrice).toLocaleString('id-ID')
                        : newUnitPrice.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 2});
                }

                let formattedAmount = '0';
                if (!isNaN(newAmount) && newAmount !== 0) {
                    const isWholeNumber = newAmount % 1 === 0;
                    formattedAmount = isWholeNumber
                        ? Math.round(newAmount).toLocaleString('id-ID')
                        : newAmount.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 2});
                }

                // Update row
                row.querySelector('.item-description').textContent = newDescription;
                row.querySelector('.item-unit').textContent = newUnit;
                row.querySelector('.item-qty').textContent = newQty.toLocaleString('id-ID');
                row.querySelector('.item-unit-price').textContent = formattedUnitPrice;
                row.querySelector('.item-amount').textContent = formattedAmount;

                // Update data attributes
                row.setAttribute('data-original-description', newDescription);
                row.setAttribute('data-original-unit', newUnit);
                row.setAttribute('data-original-qty', newQty.toString());
                row.setAttribute('data-original-unit-price', newUnitPrice.toString());
                row.setAttribute('data-original-amount', newAmount.toString());

                // Recalculate total
                this.updateAmountTotal();

                modal.hide();
            });
        }

        // Clean up modal when hidden
        const modalElement = document.getElementById(modalId);
        modalElement.addEventListener('hidden.bs.modal', () => {
            modalElement.remove();
        });

        modal.show();
    }

    /**
     * Delete Item
     */
    deleteItem(button) {
        const row = button.closest('tr');
        if (!row) return;

        const itemId = row.getAttribute('data-item-id');
        if (!itemId) {
            this.showError('Item ID not found');
            return;
        }

        // Show confirmation modal
        this.showDeleteItemConfirmationModal(() => {
            // Add to deleted items set
            this.deletedItemIds.add(itemId.toString());
            if (this.manager) {
                this.manager.deletedItemIds = this.deletedItemIds;
            }

            // Remove row from table
            row.remove();

            // Recalculate total
            this.updateAmountTotal();
        });
    }

    /**
     * Show Delete Item Confirmation Modal
     */
    showDeleteItemConfirmationModal(callback) {
        const modalId = 'deleteItemConfirmationModal';
        let existingModal = document.getElementById(modalId);

        if (existingModal) {
            existingModal.remove();
        }

        const modalHtml = `
            <div class="modal fade" id="${modalId}" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header border-0 pb-0">
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center py-4">
                            <div class="mb-3">
                                <i class="icon-base bx bx-trash text-danger" style="font-size: 4rem;"></i>
                            </div>
                            <h5 class="mb-3">Are You Sure Want to delete this item?</h5>
                            <p class="text-muted mb-0">This action cannot be undone. The item will be deleted when you submit the confirmation.</p>
                        </div>
                        <div class="modal-footer border-0 justify-content-center gap-2">
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                Cancel
                            </button>
                            <button type="button" class="btn btn-danger" id="confirmDeleteItemBtn">
                                <i class="icon-base bx bx-trash me-2"></i>Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = new bootstrap.Modal(document.getElementById(modalId));

        const confirmBtn = document.getElementById('confirmDeleteItemBtn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => {
                modal.hide();
                if (callback) callback();
            });
        }

        // Clean up modal when hidden
        const modalElement = document.getElementById(modalId);
        modalElement.addEventListener('hidden.bs.modal', () => {
            modalElement.remove();
        });

        modal.show();
    }

    /**
     * Download Document
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
            const fileFileName = button?.getAttribute('data-file-name') || fileName || 'document';

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
            link.download = fileFileName;
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
        // Use shared cache to prevent duplicate API calls
        if (!this.allPurchaseTypes && purchType && this.manager && this.manager.apiModule) {
            try {
                // Use API module which uses shared cache (ProcurementSharedCache)
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
     * Back to List
     */
    backToList() {
        const listSection = document.getElementById('listSection');
        const viewSection = document.getElementById('viewConfirmSection');

        if (listSection) listSection.style.display = 'block';
        if (viewSection) viewSection.style.display = 'none';
    }

    /**
     * Show Error
     */
    showError(message) {
        alert(message);
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
}

// Make available globally
if (typeof window !== 'undefined') {
    window.ConfirmPOListView = ConfirmPOListView;
}
