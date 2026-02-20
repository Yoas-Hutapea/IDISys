/**
 * InvoiceCreatePO Module
 * Handles PO selection, loading, and data population
 */
class InvoiceCreatePO {
    constructor(managerInstance) {
        this.manager = managerInstance;
        this.utils = null;
        this.choosePOTable = null;
        this.selectedPO = null;
        this.poDetailItems = [];
        this.itemsSource = 'po'; // 'grn' = from Good Receive Notes (actual received), 'po' = from PO items
        this.purchaseRequestAdditional = null;
        this.currentTermValue = null;

        if (window.InvoiceCreateUtils) {
            this.utils = new InvoiceCreateUtils();
        }
    }

    /**
     * Initialize Choose PO DataTable
     */
    initializeChoosePOTable() {
        this.choosePOTable = $('#tblChoosePO').DataTable({
            processing: true,
            serverSide: false,
            responsive: true,
            data: [],
            order: [[1, 'desc']],
            paging: true,
            lengthMenu: [[10, 25, 50], [10, 25, 50]],
            columns: [
                {
                    data: 'poNumber',
                    name: 'PO Number',
                    className: 'text-center'
                },
                {
                    data: 'poDate',
                    name: 'PO Date',
                    className: 'text-center',
                    render: (data) => {
                        if (!data) return '';
                        return new Date(data).toLocaleDateString('en-GB');
                    }
                },
                {
                    data: 'vendorName',
                    name: 'Vendor',
                    className: 'text-center'
                },
                {
                    data: 'amount',
                    name: 'Amount',
                    className: 'text-center',
                    render: (data) => {
                        const formatCurrency = this.utils ? this.utils.formatCurrency.bind(this.utils) : this.formatCurrency.bind(this);
                        return formatCurrency(data || 0);
                    }
                },
                {
                    data: null,
                    orderable: false,
                    className: 'text-center',
                    render: (data, type, row) => {
                        const poNumber = row.poNumber || row.poID;
                        return `<button class="btn btn-sm btn-primary" onclick="window.invoiceCreateManager?.poModule?.selectPO('${poNumber}')">Select</button>`;
                    }
                }
            ]
        });
    }

    /**
     * Open Choose PO Modal
     */
    async openChoosePOModal() {
        await this.loadPOList();
        const modalElement = document.getElementById('modalChoosePO');
        if (!modalElement) {
            return;
        }

        const existingModal = bootstrap.Modal.getInstance(modalElement);
        if (existingModal) {
            existingModal.dispose();
        }

        if (modalElement.parentNode !== document.body) {
            document.body.appendChild(modalElement);
        }

        const bootstrapModal = new bootstrap.Modal(modalElement);
        const handleShown = function() {
            const backdrop = document.querySelector('.modal-backdrop:last-child');
            if (backdrop) {
                const backdropZIndex = parseInt(window.getComputedStyle(backdrop).zIndex, 10) || 1040;
                modalElement.style.zIndex = (backdropZIndex + 10).toString();
            }
            modalElement.removeEventListener('shown.bs.modal', handleShown);
        };
        modalElement.addEventListener('shown.bs.modal', handleShown);

        bootstrapModal.show();
    }

    /**
     * Load PO List
     */
    async loadPOList() {
        try {
            if (!this.manager || !this.manager.apiModule) {
                throw new Error('API module not available');
            }

            const rawData = await this.manager.apiModule.getPOList();

            if (!this.choosePOTable) {
                this.initializeChoosePOTable();
            }

            this.choosePOTable.clear();
            if (rawData.length > 0) {
                const mappedData = rawData.map(item => ({
                    poNumber: item.purchOrderID || item.PurchOrderID || '',
                    poID: item.id || item.ID || '',
                    poDate: item.poDate || item.PODate || null,
                    vendorName: item.mstVendorVendorName || item.MstVendorVendorName || '',
                    amount: item.poAmount || item.POAmount || 0,
                    poName: item.purchOrderName || item.PurchOrderName || '',
                    purchaseType: item.purchType || item.PurchType || '',
                    purchaseSubType: item.purchSubType || item.PurchSubType || '',
                    company: item.companyName || item.CompanyName || ''
                }));
                this.choosePOTable.rows.add(mappedData);
            }
            this.choosePOTable.draw();
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Failed to load PO list'
            });
        }
    }

    /**
     * Select PO and load all related data (OPTIMIZED with parallel API calls)
     */
    async selectPO(poNumber) {
        try {
            if (!this.manager || !this.manager.apiModule) {
                throw new Error('API module not available');
            }

            // Show loading indicator
            const loadingToast = Swal.fire({
                title: 'Loading PO Data...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // PHASE 1: Get PO Details (must be first)
            const poData = await this.manager.apiModule.getPODetails(poNumber);

            this.selectedPO = poData;

            // Invalidate cached GRN/PO items so we always load fresh (e.g. after new GRN saved)
            if (this.manager.apiModule.invalidatePOItemsCache) {
                this.manager.apiModule.invalidatePOItemsCache(poNumber);
            }
            this.purchaseRequestAdditional = null; // Reset PurchaseRequestAdditional
            if (this.manager.termModule) {
                this.manager.termModule.selectedPeriods = []; // Reset selected periods
            }

            // Get PR Number and Vendor ID for parallel calls
            const prNumber = poData.prNumber || poData.PRNumber || poData.trxPROPurchaseRequestNumber || poData.TrxPROPurchaseRequestNumber || '';
            const vendorID = poData.mstVendorVendorID || poData.MstVendorVendorID;

            // PHASE 2: Load critical data in parallel (GRN items, PO Items, Vendor, Amortizations, Master Data)
            const [
                grnItemsResult,
                poItemsResult,
                vendorResult,
                purchaseTypesResult,
                workTypeMappingsResult,
                billingTypesResult,
                existingInvoicesResult,
                amortizationsResult
            ] = await Promise.allSettled([
                // Prefer GRN items (actual received) so we bill what was received
                this.manager.apiModule.getGRNItemsForInvoice ? this.manager.apiModule.getGRNItemsForInvoice(poNumber) : Promise.resolve({ items: [], source: 'po' }),
                // PO Items (fallback when no GRN data)
                this.manager.apiModule.getPOItems(poNumber),
                // Load Vendor Information (if vendorID exists)
                vendorID ? this.manager.apiModule.getVendorDetails(vendorID).catch(() => null) : Promise.resolve(null),
                // Load Purchase Types (master data - cached)
                this.manager.apiModule.getPurchaseTypes(),
                // Load Work Type Mappings (master data - cached)
                this.manager.apiModule.getWorkTypeMappings(),
                // Load Billing Types (master data - cached)
                this.manager.apiModule.getBillingTypes(),
                // Load Existing Invoices (for term eligibility)
                this.manager.apiModule.getExistingInvoices(poNumber),
                // Amortizations from trxPROPurchaseOrderAmortization (for Term/Period grid like Cancel Period)
                this.manager.apiModule.getAmortizations ? this.manager.apiModule.getAmortizations(poNumber) : Promise.resolve(null)
            ]);

            // Single source: Inventory API returns GRN items if any, else PO items (same backend). Fallback: Procurement PO items.
            const grnPayload = grnItemsResult.status === 'fulfilled' && grnItemsResult.value ? grnItemsResult.value : { items: [], source: 'po' };
            const grnItems = Array.isArray(grnPayload.items) ? grnPayload.items : (Array.isArray(grnPayload) ? grnPayload : []);
            const poItemsRaw = poItemsResult.status === 'fulfilled' ? poItemsResult.value : null;
            const poItems = Array.isArray(poItemsRaw) ? poItemsRaw : (poItemsRaw && (poItemsRaw.data || poItemsRaw.Data) ? (poItemsRaw.data || poItemsRaw.Data) : []);

            const itemsToUse = grnItems.length > 0 ? grnItems : poItems;
            const sourceToUse = itemsToUse.length > 0 ? (grnItems.length > 0 ? (grnPayload.source || 'grn') : 'po') : 'po';

            this.poDetailItems = itemsToUse;
            this.itemsSource = sourceToUse;

            // Populate Currency in Vendor section from first item
            this.populateCurrencyFromPOItems(this.poDetailItems);

            const existingInvoices = existingInvoicesResult.status === 'fulfilled' ? existingInvoicesResult.value : null;
            if (this.poDetailItems.length > 0) {
                this.loadPODetailOptimized(poNumber, existingInvoices, this.poDetailItems).catch(err => console.error('Error loading PO detail:', err));
            } else {
                const tbody = document.getElementById('tblPODetailBody');
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted">No items found</td></tr>';
                    const qtyHeader = document.querySelector('#tblPODetail thead th:nth-child(5)');
                    if (qtyHeader) qtyHeader.textContent = 'Qty PO';
                }
            }

            // Process Vendor result
            if (vendorResult.status === 'fulfilled' && vendorResult.value) {
                this.populateVendorInformationFromDetail(vendorResult.value);
            } else {
                this.populateVendorInformation(poData); // Use basic vendor name from PO
            }

            // Process Purchase Types and load names
            if (purchaseTypesResult.status === 'fulfilled') {
                await this.loadPurchaseTypeAndSubTypeNamesOptimized(poData, purchaseTypesResult.value);
            }

            // Process Work Type Mappings
            if (workTypeMappingsResult.status === 'fulfilled') {
                await this.loadWorkTypeFromMappingOptimized(poData, workTypeMappingsResult.value);
            }

            // Populate PO Information (with PO Author and PR Requestor as employee names)
            await this.populatePOInformation(poData);

            // PHASE 3: Load PR-dependent data (if PR Number exists)
            let prAdditionalResult = null;
            let hasPeriodPayment = false;

            if (prNumber) {
                try {
                    // Get PR Additional (needed for STIP and Period Payment)
                    prAdditionalResult = await this.manager.apiModule.getPRAdditional(prNumber);
                    this.purchaseRequestAdditional = prAdditionalResult;
                } catch (error) {
                    console.warn('Failed to load PR Additional:', error);
                    prAdditionalResult = null;
                }

                // PHASE 4: Load STIP and Period Payment in parallel (after PR Additional)
                if (prAdditionalResult) {
                    const sonumb = prAdditionalResult.sonumb || prAdditionalResult.Sonumb || '';

                    const [stipSites, periodPaymentResult] = await Promise.allSettled([
                        // Load STIP Sites if sonumb exists
                        sonumb ? this.manager.apiModule.getSTIPSites(sonumb).catch(() => null) : Promise.resolve(null),
                        // Check Period of Payment
                        this.manager.termModule && this.manager.termModule.checkAndLoadPeriodOfPaymentOptimized
                            ? this.manager.termModule.checkAndLoadPeriodOfPaymentOptimized(prAdditionalResult, billingTypesResult.status === 'fulfilled' ? billingTypesResult.value : null)
                            : Promise.resolve(false)
                    ]);

                    // Process STIP result
                    if (stipSites.status === 'fulfilled' && stipSites.value && sonumb) {
                        this.populateSTIPData(sonumb, stipSites.value);
                    }

                    // Process Period Payment result
                    if (periodPaymentResult.status === 'fulfilled') {
                        hasPeriodPayment = periodPaymentResult.value || false;
                    }
                }
            }

            // Reset sections visibility when new PO is selected (only if not revised invoice)
            const currentInvoiceId = this.manager.currentInvoiceId || null;
            if (!currentInvoiceId) {
                $('#invoiceInformationSection').hide();
                $('#documentChecklistSection').hide();
                $('#submitButtonsSection').hide();
            }

            // When items are from GRN, recalc amortization so Term/Period amounts use GRN total (e.g. 27M) not PR (32.4M)
            if (this.itemsSource === 'grn' && poNumber && this.manager.apiModule && this.manager.apiModule.recalcAmortizationFromGRN) {
                await this.manager.apiModule.recalcAmortizationFromGRN(poNumber);
            }

            // Show/hide Term or Period of Payment sections from amortization data (same logic as Cancel Period)
            let amortizationsData = amortizationsResult.status === 'fulfilled' && amortizationsResult.value ? amortizationsResult.value : null;
            if (this.itemsSource === 'grn' && poNumber && this.manager.apiModule) {
                try {
                    amortizationsData = await this.manager.apiModule.getAmortizations(poNumber);
                } catch (e) {
                    console.warn('Refetch amortizations after GRN recalc:', e);
                }
            }
            const dataPayload = amortizationsData && typeof amortizationsData === 'object' ? (amortizationsData.data || amortizationsData) : null;
            const periodList = (dataPayload && Array.isArray(dataPayload.periodOfPayment)) ? dataPayload.periodOfPayment : [];
            const termList = (dataPayload && Array.isArray(dataPayload.termOfPayment)) ? dataPayload.termOfPayment : [];
            const hasPeriodFromAmort = periodList.length > 0;
            const hasTermFromAmort = termList.length > 0;

            if (this.manager.termModule) {
                if (hasPeriodFromAmort) {
                    $('#termOfPaymentSection').hide();
                    $('#periodOfPaymentSection').show();
                    this.manager.termModule.loadPeriodOfPaymentGridFromAmortizations(dataPayload, existingInvoices).catch(err => console.error('Error loading period grid:', err));
                } else {
                    $('#termOfPaymentSection').show();
                    $('#periodOfPaymentSection').hide();
                    this.manager.termModule.loadTermOfPaymentGridOptimized(existingInvoices, dataPayload).catch(err => console.error('Error loading term grid:', err));
                }
            } else {
                // Fallback when no term module: use PR Additional flag
                if (hasPeriodPayment) {
                    $('#termOfPaymentSection').hide();
                    $('#periodOfPaymentSection').show();
                } else {
                    $('#termOfPaymentSection').show();
                    $('#periodOfPaymentSection').hide();
                }
            }

            // Inject Additional Information section (from trxPROPurchaseRequestAdditional, same as Confirm PO)
            try {
                const additionalHTML = await this.getAdditionalInformationSummaryHTML(this.selectedPO || poData);
                const additionalContainer = document.getElementById('invoice-additional-section-container');
                if (additionalContainer) {
                    if (additionalHTML) {
                        additionalContainer.innerHTML = additionalHTML;
                        additionalContainer.style.display = 'block';
                        if (this.purchaseRequestAdditional) {
                            const add = this.purchaseRequestAdditional;
                            const sonumb = add.sonumb || add.Sonumb || '';
                            const siteID = add.siteID || add.SiteID || '';
                            $('#txtSONumber').val(sonumb);
                            $('#txtSiteID').val(siteID);
                        }
                    } else {
                        additionalContainer.innerHTML = '';
                        additionalContainer.style.display = 'none';
                        $('#txtSONumber').val('');
                        $('#txtSiteID').val('');
                    }
                }
            } catch (err) {
                console.warn('Additional Information section:', err);
                const additionalContainer = document.getElementById('invoice-additional-section-container');
                if (additionalContainer) {
                    additionalContainer.innerHTML = '';
                    additionalContainer.style.display = 'none';
                }
            }

            // Close loading indicator
            Swal.close();

            // Close modal
            const modalElement = document.getElementById('modalChoosePO');
            if (modalElement) {
                const modalInstance = bootstrap.Modal.getInstance(modalElement);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }
        } catch (error) {
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Failed to load PO detail'
            });
        }
    }

    /**
     * Load Vendor Information
     */
    async loadVendorInformation(vendorID) {
        try {
            if (!this.manager || !this.manager.apiModule) {
                throw new Error('API module not available');
            }

            const vendorData = await this.manager.apiModule.getVendorDetails(vendorID);
            this.populateVendorInformationFromDetail(vendorData);
        } catch (error) {
            // Fallback to basic vendor information from PO
            const poData = this.selectedPO || {};
            this.populateVendorInformation(poData);
        }
    }

    /**
     * Populate PO Information fields (same structure as View PO Purchase Information).
     * PO Author and PR Requestor are resolved to employee names via ProcurementSharedCache.
     */
    async populatePOInformation(poData) {
        const formatCurrency = this.utils ? this.utils.formatCurrency.bind(this.utils) : this.formatCurrency.bind(this);
        const formatDate = this.utils ? this.utils.formatDate.bind(this.utils) : this.formatDate.bind(this);

        let poAuthorName = poData.poAuthor || poData.POAuthor || '-';
        let prRequestorName = poData.prRequestor || poData.PRRequestor || '-';

        const employeeCache = (typeof window !== 'undefined' && window.procurementSharedCache && window.procurementSharedCache.getEmployeeNameByEmployId)
            ? window.procurementSharedCache
            : null;

        if (employeeCache) {
            const poAuthorId = poData.poAuthor || poData.POAuthor || '';
            const prRequestorId = poData.prRequestor || poData.PRRequestor || '';
            try {
                const [authorName, requestorName] = await Promise.all([
                    poAuthorId && poAuthorId !== '-' ? employeeCache.getEmployeeNameByEmployId(poAuthorId) : Promise.resolve(poAuthorName),
                    prRequestorId && prRequestorId !== '-' ? employeeCache.getEmployeeNameByEmployId(prRequestorId) : Promise.resolve(prRequestorName)
                ]);
                if (authorName) poAuthorName = authorName;
                if (requestorName) prRequestorName = requestorName;
            } catch (e) {
                console.warn('Resolve employee names for PO Author / PR Requestor:', e);
            }
        }

        // PO Amount: use sum of loaded items (GRN or PO) so it reflects 27M when GRN, 30M when PO — not PR/header 32.4M
        let displayPOAmount = parseFloat(poData.poAmount || poData.POAmount || 0) || 0;
        if (this.poDetailItems && this.poDetailItems.length > 0) {
            const sumFromItems = this.poDetailItems.reduce((sum, item) => {
                const amt = parseFloat(item.amount || item.Amount || 0) || 0;
                return sum + amt;
            }, 0);
            if (sumFromItems > 0) {
                displayPOAmount = sumFromItems;
            }
        }

        $('#txtPurchOrderID').val(poData.purchOrderID || poData.PurchOrderID || '');
        $('#txtPODate').val(formatDate(poData.poDate || poData.PODate) || '-');
        $('#txtPurchOrderName').val(poData.purchOrderName || poData.PurchOrderName || '-');
        $('#txtPOAuthor').val(poAuthorName);
        $('#txtPurchType').val(poData.purchType || poData.PurchType || '-');
        $('#txtPOAmount').val(formatCurrency(displayPOAmount));
        $('#txtPurchSubType').val(poData.purchSubType || poData.PurchSubType || '-');
        $('#txtPRNumber').val(poData.prNumber || poData.PRNumber || '-');
        $('#txtCompany').val(poData.companyName || poData.CompanyName || '-');
        $('#txtPRRequestor').val(prRequestorName);
        $('#txtPRDate').val(formatDate(poData.prDate || poData.PRDate) || '-');

        // Store additional data in selectedPO for later use (logic, Vendor, Term, etc.)
        if (this.selectedPO) {
            this.selectedPO.companyID = poData.companyID || poData.CompanyID || this.selectedPO.companyID;
            this.selectedPO.vendorID = poData.mstVendorVendorID || poData.MstVendorVendorID || this.selectedPO.vendorID;
            this.selectedPO.workTypeID = poData.workTypeID || poData.WorkTypeID || this.selectedPO.workTypeID;
            this.selectedPO.productTypeID = poData.productTypeID || poData.ProductTypeID || this.selectedPO.productTypeID;
            this.selectedPO.topDescription = poData.topDescription || poData.TOPDescription || this.selectedPO.topDescription;
            this.selectedPO.poAmount = displayPOAmount;
            this.selectedPO.seqTOPID = poData.seqTOPID || poData.SeqTOPID || this.selectedPO.seqTOPID;
            this.selectedPO.purchaseType = poData.purchType || poData.PurchType || '';
            this.selectedPO.purchaseSubType = poData.purchSubType || poData.PurchSubType || '';
            this.selectedPO.purchaseTypeID = poData.purchaseTypeID || poData.PurchaseTypeID || poData.mstPROPurchaseTypeID || poData.MstPROPurchaseTypeID || null;
            this.selectedPO.purchaseSubTypeID = poData.purchaseSubTypeID || poData.PurchaseSubTypeID || poData.mstPROPurchaseSubTypeID || poData.MstPROPurchaseSubTypeID || null;
        }
    }

    /**
     * Populate Vendor Information (basic from PO)
     */
    populateVendorInformation(poData) {
        $('#txtVendorName').val(poData.mstVendorVendorName || poData.MstVendorVendorName || '');
    }

    /**
     * Populate Vendor Information from Detail API
     */
    populateVendorInformationFromDetail(vendorData) {
        $('#txtVendorName').val(vendorData.vendorName || vendorData.VendorName || '');
        $('#txtContactPerson').val(vendorData.cp || vendorData.CP || '');
        $('#txtEmailAddress').val(vendorData.cpEmail || vendorData.CPEmail || vendorData.emailCorresspondence || vendorData.EmailCorresspondence || '');
        $('#txtPhoneNumber').val(vendorData.cpPhone || vendorData.CPPhone || vendorData.telephone || vendorData.Telephone || '');
        $('#txtNPWP').val(vendorData.npwp || vendorData.NPWP || '');
        $('#txtBank').val(vendorData.bankName || vendorData.BankName || '');
        $('#txtAccountName').val(vendorData.recipientName || vendorData.RecipientName || '');
        $('#txtAccountNumber').val(vendorData.bankAccount || vendorData.BankAccount || '');

        // Vendor Status
        const isActive = vendorData.isActive !== undefined ? vendorData.isActive : (vendorData.IsActive !== undefined ? vendorData.IsActive : false);
        $('#txtVendorStatus').val(isActive ? 'Active' : 'Inactive');
    }

    /**
     * Populate Currency from PO Items
     */
    populateCurrencyFromPOItems(poItems) {
        if (poItems && poItems.length > 0) {
            const firstItem = poItems[0];
            const currencyCode = firstItem.currencyCode || firstItem.CurrencyCode || '';
            $('#txtCurrency').val(currencyCode);
        }
    }

    /**
     * Load Purchase Type and Sub Type Names (Optimized - uses pre-fetched purchaseTypes)
     */
    async loadPurchaseTypeAndSubTypeNamesOptimized(poData, purchaseTypes) {
        try {
            if (!this.manager || !this.manager.apiModule) {
                return;
            }

            // Get PurchaseTypeID and PurchaseSubTypeID from PO data
            let purchaseTypeID = poData.purchaseTypeID || poData.PurchaseTypeID || poData.mstPROPurchaseTypeID || poData.MstPROPurchaseTypeID;
            let purchaseSubTypeID = poData.purchaseSubTypeID || poData.PurchaseSubTypeID || poData.mstPROPurchaseSubTypeID || poData.MstPROPurchaseSubTypeID;

            // If IDs are not available, try to get from PurchaseType and PurchaseSubType strings
            if (!purchaseTypeID) {
                const purchaseTypeValue = poData.purchType || poData.PurchType || poData.purchaseType || poData.PurchaseType || '';
                if (purchaseTypeValue && /^\d+$/.test(purchaseTypeValue.trim())) {
                    purchaseTypeID = purchaseTypeValue.trim();
                }
            }

            if (!purchaseSubTypeID) {
                const purchaseSubTypeValue = poData.purchSubType || poData.PurchSubType || poData.purchaseSubType || poData.PurchaseSubType || '';
                if (purchaseSubTypeValue && /^\d+$/.test(purchaseSubTypeValue.trim())) {
                    purchaseSubTypeID = purchaseSubTypeValue.trim();
                }
            }

            // If we have PurchaseTypeID, lookup the name from pre-fetched data
            if (purchaseTypeID && purchaseTypes) {
                try {
                    const purchaseTypeIDInt = parseInt(purchaseTypeID);
                    const purchaseType = purchaseTypes.find(pt => (pt.id || pt.ID) === purchaseTypeIDInt);

                    if (purchaseType) {
                        // Format: PurchaseRequestType + Category (if different)
                        let formattedType = purchaseType.purchaseRequestType || purchaseType.PurchaseRequestType || '';
                        const category = purchaseType.category || purchaseType.Category || '';

                        if (category && category.trim() !== '' && category.trim() !== formattedType.trim()) {
                            if (formattedType && formattedType.trim() !== '') {
                                formattedType = `${formattedType} ${category}`;
                            } else {
                                formattedType = category;
                            }
                        }

                        // Update poData with formatted name
                        const finalFormattedType = formattedType || purchaseType.purchaseRequestType || purchaseType.PurchaseRequestType || '';
                        poData.purchType = finalFormattedType;
                        poData.PurchType = finalFormattedType;

                        // Update UI
                        $('#txtPurchType').val(finalFormattedType);

                        // Store ID and formatted name for later use
                        if (this.selectedPO) {
                            this.selectedPO.purchaseTypeID = purchaseTypeIDInt;
                            this.selectedPO.mstPROPurchaseTypeID = purchaseTypeIDInt;
                            this.selectedPO.purchType = finalFormattedType;
                            this.selectedPO.PurchType = finalFormattedType;
                        }
                    }
                } catch (error) {
                    console.warn('Failed to lookup PurchaseType name:', error);
                }
            }

            // If we have PurchaseSubTypeID, lookup the name from pre-fetched data
            if (purchaseSubTypeID && purchaseTypeID) {
                try {
                    // Get Purchase Sub Types (will use cache)
                    const purchaseSubTypes = await this.manager.apiModule.getPurchaseSubTypes(purchaseTypeID);
                    const purchaseSubTypeIDInt = parseInt(purchaseSubTypeID);
                    const purchaseSubType = purchaseSubTypes.find(pst => (pst.id || pst.ID) === purchaseSubTypeIDInt);

                    if (purchaseSubType) {
                        const formattedSubType = purchaseSubType.purchaseRequestSubType || purchaseSubType.PurchaseRequestSubType || '';

                        // Update poData with formatted name
                        poData.purchSubType = formattedSubType;
                        poData.PurchSubType = formattedSubType;

                        // Update UI
                        $('#txtPurchSubType').val(formattedSubType);

                        // Store ID and formatted name for later use
                        if (this.selectedPO) {
                            this.selectedPO.purchaseSubTypeID = purchaseSubTypeIDInt;
                            this.selectedPO.mstPROPurchaseSubTypeID = purchaseSubTypeIDInt;
                            this.selectedPO.purchSubType = formattedSubType;
                            this.selectedPO.PurchSubType = formattedSubType;
                        }
                    }
                } catch (error) {
                    console.warn('Failed to lookup PurchaseSubType name:', error);
                }
            }
        } catch (error) {
            console.error('Failed to load PurchaseType and PurchaseSubType names:', error);
        }
    }

    /**
     * Load Purchase Type and Sub Type Names (Original - for backward compatibility)
     */
    async loadPurchaseTypeAndSubTypeNames(poData) {
        try {
            if (!this.manager || !this.manager.apiModule) {
                return;
            }

            // Get PurchaseTypeID and PurchaseSubTypeID from PO data
            let purchaseTypeID = poData.purchaseTypeID || poData.PurchaseTypeID || poData.mstPROPurchaseTypeID || poData.MstPROPurchaseTypeID;
            let purchaseSubTypeID = poData.purchaseSubTypeID || poData.PurchaseSubTypeID || poData.mstPROPurchaseSubTypeID || poData.MstPROPurchaseSubTypeID;

            // If IDs are not available, try to get from PurchaseType and PurchaseSubType strings (which might be IDs)
            if (!purchaseTypeID) {
                const purchaseTypeValue = poData.purchType || poData.PurchType || poData.purchaseType || poData.PurchaseType || '';
                if (purchaseTypeValue && /^\d+$/.test(purchaseTypeValue.trim())) {
                    purchaseTypeID = purchaseTypeValue.trim();
                }
            }

            if (!purchaseSubTypeID) {
                const purchaseSubTypeValue = poData.purchSubType || poData.PurchSubType || poData.purchaseSubType || poData.PurchaseSubType || '';
                if (purchaseSubTypeValue && /^\d+$/.test(purchaseSubTypeValue.trim())) {
                    purchaseSubTypeID = purchaseSubTypeValue.trim();
                }
            }

            // If we have PurchaseTypeID, lookup the name
            if (purchaseTypeID) {
                try {
                    const purchaseTypes = await this.manager.apiModule.getPurchaseTypes();
                    const purchaseTypeIDInt = parseInt(purchaseTypeID);
                    const purchaseType = purchaseTypes.find(pt => (pt.id || pt.ID) === purchaseTypeIDInt);

                    if (purchaseType) {
                        // Format: PurchaseRequestType + Category (if different)
                        let formattedType = purchaseType.purchaseRequestType || purchaseType.PurchaseRequestType || '';
                        const category = purchaseType.category || purchaseType.Category || '';

                        if (category && category.trim() !== '' && category.trim() !== formattedType.trim()) {
                            if (formattedType && formattedType.trim() !== '') {
                                formattedType = `${formattedType} ${category}`;
                            } else {
                                formattedType = category;
                            }
                        }

                        // Update poData with formatted name
                        const finalFormattedType = formattedType || purchaseType.purchaseRequestType || purchaseType.PurchaseRequestType || '';
                        poData.purchType = finalFormattedType;
                        poData.PurchType = finalFormattedType;

                        // Store ID and formatted name for later use
                        if (this.selectedPO) {
                            this.selectedPO.purchaseTypeID = purchaseTypeIDInt;
                            this.selectedPO.mstPROPurchaseTypeID = purchaseTypeIDInt;
                            this.selectedPO.purchType = finalFormattedType;
                            this.selectedPO.PurchType = finalFormattedType;
                        }
                    }
                } catch (error) {
                    console.warn('Failed to lookup PurchaseType name:', error);
                }
            }

            // If we have PurchaseSubTypeID, lookup the name
            if (purchaseSubTypeID) {
                try {
                    const currentPurchaseTypeID = purchaseTypeID || (this.selectedPO ? (this.selectedPO.purchaseTypeID || this.selectedPO.mstPROPurchaseTypeID) : null);
                    const purchaseSubTypes = await this.manager.apiModule.getPurchaseSubTypes(currentPurchaseTypeID);
                    const purchaseSubTypeIDInt = parseInt(purchaseSubTypeID);
                    const purchaseSubType = purchaseSubTypes.find(pst => (pst.id || pst.ID) === purchaseSubTypeIDInt);

                    if (purchaseSubType) {
                        const formattedSubType = purchaseSubType.purchaseRequestSubType || purchaseSubType.PurchaseRequestSubType || '';

                        // Update poData with formatted name
                        poData.purchSubType = formattedSubType;
                        poData.PurchSubType = formattedSubType;

                        // Store ID and formatted name for later use
                        if (this.selectedPO) {
                            this.selectedPO.purchaseSubTypeID = purchaseSubTypeIDInt;
                            this.selectedPO.mstPROPurchaseSubTypeID = purchaseSubTypeIDInt;
                            this.selectedPO.purchSubType = formattedSubType;
                            this.selectedPO.PurchSubType = formattedSubType;
                        }
                    }
                } catch (error) {
                    console.warn('Failed to lookup PurchaseSubType name:', error);
                }
            }
        } catch (error) {
            console.error('Failed to load PurchaseType and PurchaseSubType names:', error);
        }
    }

    /**
     * Load Work Type from Mapping (Optimized - uses pre-fetched workTypeMappings)
     */
    async loadWorkTypeFromMappingOptimized(poData, allMappings) {
        try {
            if (!this.manager || !this.manager.apiModule) {
                return;
            }

            const fallbackWorkType = poData.purchType || poData.PurchType || poData.purchaseType || poData.PurchaseType || '';

            // Get PurchaseTypeID and PurchaseSubTypeID from PO data
            let purchaseTypeID = poData.purchaseTypeID || poData.PurchaseTypeID || poData.mstPROPurchaseTypeID || poData.MstPROPurchaseTypeID;
            let purchaseSubTypeID = poData.purchaseSubTypeID || poData.PurchaseSubTypeID || poData.mstPROPurchaseSubTypeID || poData.MstPROPurchaseSubTypeID;

            // If IDs are not available, try to lookup from PurchaseType and PurchaseSubType strings
            if (!purchaseTypeID || !purchaseSubTypeID) {
                const purchaseTypeName = poData.purchType || poData.PurchType || poData.purchaseType || poData.PurchaseType || '';
                const purchaseSubTypeName = poData.purchSubType || poData.PurchSubType || poData.purchaseSubType || poData.PurchaseSubType || '';

                if (purchaseTypeName && !purchaseTypeID) {
                    try {
                        const purchaseTypes = await this.manager.apiModule.getPurchaseTypes();
                        const purchaseType = purchaseTypes.find(pt =>
                            (pt.purchaseRequestType || pt.PurchaseRequestType || '').trim() === purchaseTypeName.trim() ||
                            (pt.id || pt.ID || 0).toString() === purchaseTypeName.trim()
                        );
                        if (purchaseType) {
                            purchaseTypeID = purchaseType.id || purchaseType.ID;
                        }
                    } catch (error) {
                        console.warn('Failed to lookup PurchaseTypeID:', error);
                    }
                }

                if (purchaseSubTypeName && purchaseTypeID && !purchaseSubTypeID) {
                    try {
                        const purchaseSubTypes = await this.manager.apiModule.getPurchaseSubTypes(purchaseTypeID);
                        const purchaseSubType = purchaseSubTypes.find(pst =>
                            (pst.purchaseRequestSubType || pst.PurchaseRequestSubType || '').trim() === purchaseSubTypeName.trim() ||
                            (pst.id || pst.ID || 0).toString() === purchaseSubTypeName.trim()
                        );
                        if (purchaseSubType) {
                            purchaseSubTypeID = purchaseSubType.id || purchaseSubType.ID;
                        }
                    } catch (error) {
                        console.warn('Failed to lookup PurchaseSubTypeID:', error);
                    }
                }
            }

            // If we still don't have IDs, cannot proceed
            if (!purchaseTypeID || !purchaseSubTypeID) {
                console.warn('PurchaseTypeID or PurchaseSubTypeID not found, cannot load WorkType from mapping');
                $('#txtWorkType').val(fallbackWorkType);
                return;
            }

            // Convert to integers
            purchaseTypeID = parseInt(purchaseTypeID);
            purchaseSubTypeID = parseInt(purchaseSubTypeID);

            if (isNaN(purchaseTypeID) || isNaN(purchaseSubTypeID)) {
                console.warn('Invalid PurchaseTypeID or PurchaseSubTypeID');
                $('#txtWorkType').val(fallbackWorkType);
                return;
            }

            // Filter by PurchaseTypeID and PurchaseSubTypeID from pre-fetched mappings
            if (!allMappings) {
                allMappings = await this.manager.apiModule.getWorkTypeMappings();
            }

            const matchedMapping = allMappings.find(mapping => {
                const mappingPurchaseTypeID = parseInt(mapping.mstPROPurchaseTypeID || mapping.mstProPurchaseTypeId || 0);
                const mappingPurchaseSubTypeID = parseInt(mapping.mstPROPurchaseSubTypeID || mapping.mstProPurchaseSubTypeId || 0);
                return mappingPurchaseTypeID === purchaseTypeID && mappingPurchaseSubTypeID === purchaseSubTypeID;
            });

            if (matchedMapping) {
                // Get WorkType from mapping
                const workType = matchedMapping.workType
                    || matchedMapping.WorkType
                    || matchedMapping.workTypeDescription
                    || matchedMapping.WorkTypeDescription
                    || matchedMapping.description
                    || matchedMapping.Description
                    || '';
                $('#txtWorkType').val(workType);

                // Store WorkTypeID for later use
                if (this.selectedPO) {
                    this.selectedPO.workTypeID = matchedMapping.mstFINInvoiceWorkTypeID || matchedMapping.mstFinInvoiceWorkTypeId || this.selectedPO.workTypeID;
                }
            } else {
                // No mapping found
                $('#txtWorkType').val(fallbackWorkType);
                console.warn(`No WorkType mapping found for PurchaseTypeID=${purchaseTypeID} and PurchaseSubTypeID=${purchaseSubTypeID}`);
            }
        } catch (error) {
            console.error('Failed to load WorkType from mapping:', error);
            const fallbackWorkType = poData?.purchType || poData?.PurchType || poData?.purchaseType || poData?.PurchaseType || '';
            $('#txtWorkType').val(fallbackWorkType);
        }
    }

    /**
     * Load Work Type from Mapping (Original - for backward compatibility)
     */
    async loadWorkTypeFromMapping(poData) {
        try {
            if (!this.manager || !this.manager.apiModule) {
                return;
            }

            const fallbackWorkType = poData.purchType || poData.PurchType || poData.purchaseType || poData.PurchaseType || '';

            // Get PurchaseTypeID and PurchaseSubTypeID from PO data
            let purchaseTypeID = poData.purchaseTypeID || poData.PurchaseTypeID || poData.mstPROPurchaseTypeID || poData.MstPROPurchaseTypeID;
            let purchaseSubTypeID = poData.purchaseSubTypeID || poData.PurchaseSubTypeID || poData.mstPROPurchaseSubTypeID || poData.MstPROPurchaseSubTypeID;

            // If IDs are not available, try to lookup from PurchaseType and PurchaseSubType strings
            if (!purchaseTypeID || !purchaseSubTypeID) {
                const purchaseTypeName = poData.purchType || poData.PurchType || poData.purchaseType || poData.PurchaseType || '';
                const purchaseSubTypeName = poData.purchSubType || poData.PurchSubType || poData.purchaseSubType || poData.PurchaseSubType || '';

                if (purchaseTypeName && !purchaseTypeID) {
                    try {
                        const purchaseTypes = await this.manager.apiModule.getPurchaseTypes();
                        const purchaseType = purchaseTypes.find(pt =>
                            (pt.purchaseRequestType || pt.PurchaseRequestType || '').trim() === purchaseTypeName.trim() ||
                            (pt.id || pt.ID || 0).toString() === purchaseTypeName.trim()
                        );
                        if (purchaseType) {
                            purchaseTypeID = purchaseType.id || purchaseType.ID;
                        }
                    } catch (error) {
                        console.warn('Failed to lookup PurchaseTypeID:', error);
                    }
                }

                if (purchaseSubTypeName && purchaseTypeID && !purchaseSubTypeID) {
                    try {
                        const purchaseSubTypes = await this.manager.apiModule.getPurchaseSubTypes(purchaseTypeID);
                        const purchaseSubType = purchaseSubTypes.find(pst =>
                            (pst.purchaseRequestSubType || pst.PurchaseRequestSubType || '').trim() === purchaseSubTypeName.trim() ||
                            (pst.id || pst.ID || 0).toString() === purchaseSubTypeName.trim()
                        );
                        if (purchaseSubType) {
                            purchaseSubTypeID = purchaseSubType.id || purchaseSubType.ID;
                        }
                    } catch (error) {
                        console.warn('Failed to lookup PurchaseSubTypeID:', error);
                    }
                }
            }

            // If we still don't have IDs, cannot proceed
            if (!purchaseTypeID || !purchaseSubTypeID) {
                console.warn('PurchaseTypeID or PurchaseSubTypeID not found, cannot load WorkType from mapping');
                $('#txtWorkType').val(fallbackWorkType);
                return;
            }

            // Convert to integers
            purchaseTypeID = parseInt(purchaseTypeID);
            purchaseSubTypeID = parseInt(purchaseSubTypeID);

            if (isNaN(purchaseTypeID) || isNaN(purchaseSubTypeID)) {
                console.warn('Invalid PurchaseTypeID or PurchaseSubTypeID');
                $('#txtWorkType').val(fallbackWorkType);
                return;
            }

            // Get all WorkType PurchaseType mappings
            const allMappings = await this.manager.apiModule.getWorkTypeMappings();

            // Filter by PurchaseTypeID and PurchaseSubTypeID
            const matchedMapping = allMappings.find(mapping => {
                const mappingPurchaseTypeID = parseInt(mapping.mstPROPurchaseTypeID || mapping.mstProPurchaseTypeId || 0);
                const mappingPurchaseSubTypeID = parseInt(mapping.mstPROPurchaseSubTypeID || mapping.mstProPurchaseSubTypeId || 0);
                return mappingPurchaseTypeID === purchaseTypeID && mappingPurchaseSubTypeID === purchaseSubTypeID;
            });

            if (matchedMapping) {
                // Get WorkType from mapping
                const workType = matchedMapping.workType
                    || matchedMapping.WorkType
                    || matchedMapping.workTypeDescription
                    || matchedMapping.WorkTypeDescription
                    || matchedMapping.description
                    || matchedMapping.Description
                    || '';
                $('#txtWorkType').val(workType);

                // Store WorkTypeID for later use
                if (this.selectedPO) {
                    this.selectedPO.workTypeID = matchedMapping.mstFINInvoiceWorkTypeID || matchedMapping.mstFinInvoiceWorkTypeId || this.selectedPO.workTypeID;
                }
            } else {
                // No mapping found
                $('#txtWorkType').val(fallbackWorkType);
                console.warn(`No WorkType mapping found for PurchaseTypeID=${purchaseTypeID} and PurchaseSubTypeID=${purchaseSubTypeID}`);
            }
        } catch (error) {
            console.error('Failed to load WorkType from mapping:', error);
            const fallbackWorkType = poData?.purchType || poData?.PurchType || poData?.purchaseType || poData?.PurchaseType || '';
            $('#txtWorkType').val(fallbackWorkType);
        }
    }

    /**
     * Populate STIP Data (Helper method for optimized loading)
     */
    populateSTIPData(sonumb, stipSites) {
        try {
            // Show fields since sonumb is found
            $('#siteFieldWrapper').show();
            $('#productTypeFieldWrapper').show();
            $('#sonumbFieldWrapper').show();

            // Update txtSONumber field with sonumb
            $('#txtSONumber').val(sonumb);

            if (stipSites && stipSites.length > 0) {
                // Use first result (or find by SONumber if multiple)
                let stipSiteData = stipSites.find(stip =>
                    (stip.sonumber || stip.SONumber || '') === sonumb ||
                    (stip.stipNumber || stip.STIPNumber || '') === sonumb
                );

                // If not found by exact match, use first result
                if (!stipSiteData && stipSites.length > 0) {
                    stipSiteData = stipSites[0];
                }

                if (stipSiteData) {
                    // Update Site field
                    const siteName = stipSiteData.siteName || stipSiteData.SiteName || '';
                    const siteID = stipSiteData.siteID || stipSiteData.SiteID || '';

                    if (siteName || siteID) {
                        if (siteID && siteName) {
                            $('#txtSite').val(`${siteID} - ${siteName}`);
                        } else if (siteName) {
                            $('#txtSite').val(siteName);
                        } else if (siteID) {
                            $('#txtSite').val(siteID);
                        }

                        // Also update hidden SiteID field if exists
                        if (siteID) {
                            $('#txtSiteID').val(siteID);
                        }
                    }

                    // Update ProductType field
                    const product = stipSiteData.product || stipSiteData.Product || '';
                    if (product) {
                        $('#txtProductType').val(product);

                        // Store ProductID for later use if needed
                        if (this.selectedPO) {
                            this.selectedPO.productTypeID = stipSiteData.productID || stipSiteData.ProductID || this.selectedPO.productTypeID;
                        }
                    }
                }
            } else {
                console.warn('No STIP sites found for sonumb:', sonumb);
            }
        } catch (error) {
            console.error('Failed to populate STIP data:', error);
        }
    }

    /**
     * Load Site and ProductType from STIP (Original - for backward compatibility)
     */
    async loadSiteAndProductTypeFromSTIP(poData) {
        try {
            if (!this.manager || !this.manager.apiModule) {
                return;
            }

            // Hide fields initially (will show if sonumb found)
            $('#siteFieldWrapper').hide();
            $('#productTypeFieldWrapper').hide();
            $('#sonumbFieldWrapper').hide();

            // Get PRNumber from PO data
            const prNumber = poData.prNumber || poData.PRNumber || poData.trxPROPurchaseRequestNumber || poData.TrxPROPurchaseRequestNumber || '';

            if (!prNumber) {
                console.warn('PRNumber not found in PO data, cannot load STIP data');
                return;
            }

            // Get PurchaseRequestAdditional by PRNumber
            const additionalData = await this.manager.apiModule.getPRAdditional(prNumber);

            if (!additionalData) {
                console.warn('PurchaseRequestAdditional not found for PRNumber:', prNumber);
                return;
            }

            // Get Sonumb (STIPNumber) from PurchaseRequestAdditional
            const sonumb = additionalData.sonumb || additionalData.Sonumb || '';

            if (!sonumb) {
                console.warn('Sonumb not found in PurchaseRequestAdditional');
                return;
            }

            // Show fields since sonumb is found
            $('#siteFieldWrapper').show();
            $('#productTypeFieldWrapper').show();
            $('#sonumbFieldWrapper').show();

            // Update txtSONumber field with sonumb
            $('#txtSONumber').val(sonumb);

            // Get STIP data using STIPSites endpoint
            try {
                const stipSites = await this.manager.apiModule.getSTIPSites(sonumb);

                if (stipSites && stipSites.length > 0) {
                    // Use first result (or find by SONumber if multiple)
                    let stipSiteData = stipSites.find(stip =>
                        (stip.sonumber || stip.SONumber || '') === sonumb ||
                        (stip.stipNumber || stip.STIPNumber || '') === sonumb
                    );

                    // If not found by exact match, use first result
                    if (!stipSiteData && stipSites.length > 0) {
                        stipSiteData = stipSites[0];
                    }

                    if (stipSiteData) {
                        // Update Site field
                        const siteName = stipSiteData.siteName || stipSiteData.SiteName || '';
                        const siteID = stipSiteData.siteID || stipSiteData.SiteID || '';

                        if (siteName || siteID) {
                            if (siteID && siteName) {
                                $('#txtSite').val(`${siteID} - ${siteName}`);
                            } else if (siteName) {
                                $('#txtSite').val(siteName);
                            } else if (siteID) {
                                $('#txtSite').val(siteID);
                            }

                            // Also update hidden SiteID field if exists
                            if (siteID) {
                                $('#txtSiteID').val(siteID);
                            }
                        }

                        // Update ProductType field
                        const product = stipSiteData.product || stipSiteData.Product || '';
                        if (product) {
                            $('#txtProductType').val(product);

                            // Store ProductID for later use if needed
                            if (this.selectedPO) {
                                this.selectedPO.productTypeID = stipSiteData.productID || stipSiteData.ProductID || this.selectedPO.productTypeID;
                            }
                        }
                    }
                } else {
                    console.warn('No STIP sites found for sonumb:', sonumb);
                }
            } catch (error) {
                console.warn('Failed to load STIP data from STIPSites:', error);
            }
        } catch (error) {
            console.error('Failed to load Site and ProductType from STIP:', error);
        }
    }

    /**
     * Get Next Term Value
     */
    async getNextTermValue(poNumber) {
        try {
            if (!this.manager || !this.manager.apiModule) {
                return null;
            }

            // Get existing invoices for this PO
            const existingInvoices = await this.manager.apiModule.getExistingInvoices(poNumber);

            // Get TOPDescription from PO data
            const topDescription = this.selectedPO ? (this.selectedPO.topDescription || this.selectedPO.TOPDescription || '') : '';
            if (!topDescription) {
                return null;
            }

            // Parse TOPDescription (e.g., "30|70" becomes [30, 70])
            const termValues = topDescription.split('|').map(v => parseFloat(v.trim())).filter(v => !isNaN(v));

            if (termValues.length === 0) {
                return null;
            }

            // If no existing invoices, use first term (termPosition = 1)
            if (!existingInvoices || existingInvoices.length === 0) {
                return termValues[0]; // First term value (30%)
            }

            // Get the highest termPosition from existing invoices
            const maxTermPosition = Math.max(...existingInvoices.map(inv => inv.termPosition || inv.TermPosition || 0));

            // Next term position = maxTermPosition + 1
            const nextTermPosition = maxTermPosition + 1;

            // Get term value for next term position (index is termPosition - 1)
            if (nextTermPosition <= termValues.length) {
                return termValues[nextTermPosition - 1];
            }

            // If next term position exceeds available terms, return null or last term
            return null;
        } catch (error) {
            console.error('Failed to get next term value:', error);
            return null;
        }
    }

    /**
     * Load PO Detail Items (Optimized - uses pre-fetched existingInvoices).
     * @param {string} poNumber
     * @param {Array|null} existingInvoices
     * @param {Array|null} itemsOverride - if provided, use for rendering and set this.poDetailItems (avoids race)
     */
    async loadPODetailOptimized(poNumber, existingInvoices = null, itemsOverride = null) {
        try {
            if (!this.manager || !this.manager.apiModule) {
                throw new Error('API module not available');
            }

            const itemsToRender = Array.isArray(itemsOverride) && itemsOverride.length > 0 ? itemsOverride : this.poDetailItems;
            if (itemsToRender.length > 0 && itemsOverride) {
                this.poDetailItems = itemsOverride;
            }

            let invoices = existingInvoices;
            if (!invoices) {
                invoices = await this.manager.apiModule.getExistingInvoices(poNumber);
            }

            const tbody = document.getElementById('tblPODetailBody');
            if (!tbody) return;

            tbody.innerHTML = '';

            if (itemsToRender.length > 0) {
                const formatCurrency = this.utils ? this.utils.formatCurrency.bind(this.utils) : this.formatCurrency.bind(this);
                const isGRNSource = this.itemsSource === 'grn';
                const qtyHeader = document.querySelector('#tblPODetail thead th:nth-child(5)');
                if (qtyHeader) qtyHeader.textContent = isGRNSource ? 'Qty Received' : 'Qty PO';

                // Get term value for calculation (only if not period payment)
                if (!this.purchaseRequestAdditional) {
                    this.currentTermValue = this.getNextTermValueFromInvoices(invoices);
                } else {
                    // For period payment, term value is not applicable (amount is divided by period count)
                    this.currentTermValue = null;
                }

                itemsToRender.forEach((item, index) => {
                    const itemQty = parseFloat(item.itemQty || item.ItemQty || 0) || 0;
                    const unitPrice = parseFloat(item.unitPrice || item.UnitPrice || 0) || 0;
                    const amount = parseFloat(item.amount || item.Amount || 0) || 0;

                    // Calculate Qty Invoice and Amount Invoice
                    let qtyInvoice = itemQty;
                    let amountInvoice = amount;

                    if (this.currentTermValue !== null && this.currentTermValue > 0) {
                        // Calculate based on term value percentage
                        // Qty Invoice = Qty PO * (Term Value / 100)
                        qtyInvoice = itemQty * (this.currentTermValue / 100);
                        // Amount Invoice = Qty Invoice * Price
                        amountInvoice = qtyInvoice * unitPrice;
                    } else if (this.purchaseRequestAdditional && this.purchaseRequestAdditional.period > 0) {
                        // For period payment: Term Value = 100% for each period
                        // Qty Invoice = Qty PO * (100 / 100) = Qty PO
                        qtyInvoice = itemQty;
                        // Amount Invoice = Qty Invoice * Price = Qty PO * Price
                        amountInvoice = qtyInvoice * unitPrice;
                    }

                    const qtyInvoiceNum = Number(qtyInvoice);
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="text-center">${this.escapeHtml(item.mstPROPurchaseItemInventoryItemID || item.MstPROPurchaseItemInventoryItemID || item.mstPROInventoryItemID || item.MstPROInventoryItemID || '')}</td>
                        <td class="text-center">${this.escapeHtml(item.itemName || item.ItemName || '')}</td>
                        <td class="text-center">${this.escapeHtml(item.itemUnit || item.ItemUnit || '')}</td>
                        <td class="text-center">${this.escapeHtml(item.itemDescription || item.ItemDescription || '')}</td>
                        <td class="text-center">${itemQty}</td>
                        <td class="text-center">${formatCurrency(unitPrice)}</td>
                        <td class="text-center">${formatCurrency(amount)}</td>
                        <td class="text-center">
                            <input type="number" class="form-control form-control-sm qty-invoice"
                                   data-line="${index}"
                                   data-unit-price="${unitPrice}"
                                   data-qty-po="${itemQty}"
                                   value="${typeof qtyInvoiceNum === 'number' && !isNaN(qtyInvoiceNum) ? qtyInvoiceNum.toFixed(2) : '0.00'}"
                                   min="0"
                                   max="${itemQty}"
                                   step="0.01"
                                   readonly>
                        </td>
                        <td class="text-center line-amount-invoice" data-line="${index}">
                            ${formatCurrency(amountInvoice)}
                        </td>
                    `;
                    tbody.appendChild(row);
                });

                // Setup event handlers for quantity changes (if not readonly)
                const self = this;
                $('.qty-invoice').on('input', function() {
                    if (self.manager && self.manager.formModule) {
                        const lineIndex = $(this).data('line');
                        const item = self.poDetailItems[lineIndex];
                        const qty = parseFloat($(this).val()) || 0;
                        const price = item.unitPrice || item.UnitPrice || 0;
                        const amount = qty * price;

                        const formatCurrency = self.utils ? self.utils.formatCurrency.bind(self.utils) : self.formatCurrency.bind(self);
                        $(`.line-amount-invoice[data-line="${lineIndex}"]`).text(formatCurrency(amount));
                        self.manager.formModule.calculateInvoiceAmount();
                    }
                });

                // Populate Currency from PO items
                this.populateCurrencyFromPOItems(this.poDetailItems);

                // Calculate total invoice amount
                if (this.manager && this.manager.formModule) {
                    this.manager.formModule.calculateInvoiceAmount();
                }
            } else {
                tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted">No items found</td></tr>';
                const qtyHeaderReset = document.querySelector('#tblPODetail thead th:nth-child(5)');
                if (qtyHeaderReset) qtyHeaderReset.textContent = 'Qty PO';
            }
        } catch (error) {
            console.error('Failed to load PO detail:', error);
        }
    }

    /**
     * Get Next Term Value from existing invoices (Helper method)
     */
    getNextTermValueFromInvoices(existingInvoices) {
        try {
            // Get TOPDescription from PO data
            const topDescription = this.selectedPO ? (this.selectedPO.topDescription || this.selectedPO.TOPDescription || '') : '';
            if (!topDescription) {
                return null;
            }

            // Parse TOPDescription (e.g., "30|70" becomes [30, 70])
            const termValues = topDescription.split('|').map(v => parseFloat(v.trim())).filter(v => !isNaN(v));

            if (termValues.length === 0) {
                return null;
            }

            // If no existing invoices, use first term (termPosition = 1)
            if (!existingInvoices || existingInvoices.length === 0) {
                return termValues[0]; // First term value (30%)
            }

            // Get the highest termPosition from existing invoices
            const maxTermPosition = Math.max(...existingInvoices.map(inv => inv.termPosition || inv.TermPosition || 0));

            // Next term position = maxTermPosition + 1
            const nextTermPosition = maxTermPosition + 1;

            // Get term value for next term position (index is termPosition - 1)
            if (nextTermPosition <= termValues.length) {
                return termValues[nextTermPosition - 1];
            }

            // If next term position exceeds available terms, return null or last term
            return null;
        } catch (error) {
            console.error('Failed to get next term value:', error);
            return null;
        }
    }

    /**
     * Load PO Detail Items (Original - for backward compatibility)
     */
    async loadPODetail(poNumber) {
        try {
            if (!this.manager || !this.manager.apiModule) {
                throw new Error('API module not available');
            }

            this.poDetailItems = await this.manager.apiModule.getPOItems(poNumber);

            const tbody = document.getElementById('tblPODetailBody');
            if (!tbody) return;

            tbody.innerHTML = '';

            if (this.poDetailItems.length > 0) {
                const formatCurrency = this.utils ? this.utils.formatCurrency.bind(this.utils) : this.formatCurrency.bind(this);

                // Get term value for calculation (only if not period payment)
                if (!this.purchaseRequestAdditional) {
                    this.currentTermValue = await this.getNextTermValue(poNumber);
                } else {
                    // For period payment, term value is not applicable (amount is divided by period count)
                    this.currentTermValue = null;
                }

                this.poDetailItems.forEach((item, index) => {
                    const itemQty = parseFloat(item.itemQty || item.ItemQty || 0) || 0;
                    const unitPrice = parseFloat(item.unitPrice || item.UnitPrice || 0) || 0;
                    const amount = parseFloat(item.amount || item.Amount || 0) || 0;

                    // Calculate Qty Invoice and Amount Invoice
                    let qtyInvoice = itemQty;
                    let amountInvoice = amount;

                    if (this.currentTermValue !== null && this.currentTermValue > 0) {
                        // Calculate based on term value percentage
                        // Qty Invoice = Qty PO * (Term Value / 100)
                        qtyInvoice = itemQty * (this.currentTermValue / 100);
                        // Amount Invoice = Qty Invoice * Price
                        amountInvoice = qtyInvoice * unitPrice;
                    } else if (this.purchaseRequestAdditional && this.purchaseRequestAdditional.period > 0) {
                        // For period payment: Term Value = 100% for each period
                        // Qty Invoice = Qty PO * (100 / 100) = Qty PO
                        qtyInvoice = itemQty;
                        // Amount Invoice = Qty Invoice * Price = Qty PO * Price
                        amountInvoice = qtyInvoice * unitPrice;
                    }

                    const qtyInvoiceNum = Number(qtyInvoice);
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="text-center">${this.escapeHtml(item.mstPROPurchaseItemInventoryItemID || item.MstPROPurchaseItemInventoryItemID || item.mstPROInventoryItemID || item.MstPROInventoryItemID || '')}</td>
                        <td class="text-center">${this.escapeHtml(item.itemName || item.ItemName || '')}</td>
                        <td class="text-center">${this.escapeHtml(item.itemUnit || item.ItemUnit || '')}</td>
                        <td class="text-center">${this.escapeHtml(item.itemDescription || item.ItemDescription || '')}</td>
                        <td class="text-center">${itemQty}</td>
                        <td class="text-center">${formatCurrency(unitPrice)}</td>
                        <td class="text-center">${formatCurrency(amount)}</td>
                        <td class="text-center">
                            <input type="number" class="form-control form-control-sm qty-invoice"
                                   data-line="${index}"
                                   data-unit-price="${unitPrice}"
                                   data-qty-po="${itemQty}"
                                   value="${typeof qtyInvoiceNum === 'number' && !isNaN(qtyInvoiceNum) ? qtyInvoiceNum.toFixed(2) : '0.00'}"
                                   min="0"
                                   max="${itemQty}"
                                   step="0.01"
                                   readonly>
                        </td>
                        <td class="text-center line-amount-invoice" data-line="${index}">
                            ${formatCurrency(amountInvoice)}
                        </td>
                    `;
                    tbody.appendChild(row);
                });

                // Setup event handlers for quantity changes (if not readonly)
                const self = this;
                $('.qty-invoice').on('input', function() {
                    if (self.manager && self.manager.formModule) {
                        const lineIndex = $(this).data('line');
                        const item = self.poDetailItems[lineIndex];
                        const qty = parseFloat($(this).val()) || 0;
                        const price = item.unitPrice || item.UnitPrice || 0;
                        const amount = qty * price;

                        const formatCurrency = self.utils ? self.utils.formatCurrency.bind(self.utils) : self.formatCurrency.bind(self);
                        $(`.line-amount-invoice[data-line="${lineIndex}"]`).text(formatCurrency(amount));
                        self.manager.formModule.calculateInvoiceAmount();
                    }
                });

                // Populate Currency from PO items
                this.populateCurrencyFromPOItems(this.poDetailItems);

                // Calculate total invoice amount
                if (this.manager && this.manager.formModule) {
                    this.manager.formModule.calculateInvoiceAmount();
                }
            } else {
                tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted">No items found</td></tr>';
            }
        } catch (error) {
            console.error('Failed to load PO detail:', error);
        }
    }

    // Fallback methods if utils not available
    formatCurrency(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount || 0);
    }

    formatDate(date) {
        if (!date) return '';
        const d = new Date(date);
        if (isNaN(d.getTime())) return '';
        return d.toLocaleDateString('en-GB');
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Whether Type ID and SubType ID require Additional Section (from trxPROPurchaseRequestAdditional).
     * Same logic as Confirm PO.
     */
    requiresAdditionalSection(typeId, subTypeId) {
        if (!typeId) return false;
        if (typeId === 5 || typeId === 7) return true;
        if (typeId === 6 && subTypeId === 2) return true;
        if (typeId === 8 && subTypeId === 4) return true;
        if (typeId === 2 && (subTypeId === 1 || subTypeId === 3)) return true;
        if (typeId === 4 && subTypeId === 3) return true;
        if (typeId === 3 && (subTypeId === 4 || subTypeId === 5)) return true;
        return false;
    }

    /**
     * Build Additional Information HTML from trxPROPurchaseRequestAdditional (same as Confirm PO).
     */
    async getAdditionalInformationSummaryHTML(po) {
        if (!this.purchaseRequestAdditional) return '';
        const additional = this.purchaseRequestAdditional;

        let typeId = po.purchaseTypeID || po.PurchaseTypeID || po.mstPROPurchaseTypeID || null;
        let subTypeId = po.purchaseSubTypeID || po.PurchaseSubTypeID || po.mstPROPurchaseSubTypeID || null;
        const purchType = po.purchType || po.PurchType || '';
        const purchSubType = po.purchSubType || po.PurchSubType || '';

        if ((!typeId || !subTypeId) && this.manager && this.manager.apiModule) {
            try {
                if (!typeId && purchType) {
                    const types = await this.manager.apiModule.getPurchaseTypes();
                    const type = types.find(t =>
                        (t.PurchaseRequestType || t.purchaseRequestType || '') === purchType ||
                        parseInt(t.ID || t.id || '0', 10) === parseInt(purchType, 10)
                    );
                    if (type) typeId = parseInt(type.ID || type.id || '0', 10);
                }
                if (typeId && !subTypeId && purchSubType) {
                    const subTypes = await this.manager.apiModule.getPurchaseSubTypes(typeId);
                    const sub = subTypes.find(st =>
                        (st.PurchaseRequestSubType || st.purchaseRequestSubType || '') === purchSubType ||
                        parseInt(st.ID || st.id || '0', 10) === parseInt(purchSubType, 10)
                    );
                    if (sub) subTypeId = parseInt(sub.ID || sub.id || '0', 10);
                }
            } catch (e) {
                console.warn('Resolve type/subType for additional:', e);
            }
        }
        typeId = typeId ? parseInt(typeId, 10) : null;
        subTypeId = subTypeId ? parseInt(subTypeId, 10) : null;

        const shouldShowBillingTypeSection = typeId === 6 && subTypeId === 2;
        const shouldShowSonumbSection =
            (typeId === 8 && subTypeId === 4) ||
            (typeId === 2 && (subTypeId === 1 || subTypeId === 3)) ||
            (typeId === 4 && subTypeId === 3) ||
            (typeId === 3 && (subTypeId === 4 || subTypeId === 5));
        const shouldShowSubscribeSection = typeId === 5 || typeId === 7;

        if (!shouldShowBillingTypeSection && !shouldShowSonumbSection && !shouldShowSubscribeSection) return '';

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

        let summaryHTML = `
            <div class="col-12">
                <div class="card">
                    <div class="card-header" style="border-bottom: 1px solid #e9ecef;">
                        <h6 class="card-title mb-0">Additional Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
        `;

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
        } else if (shouldShowSonumbSection) {
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
        } else if (shouldShowSubscribeSection) {
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
}

// Make available globally
if (typeof window !== 'undefined') {
    window.InvoiceCreatePO = InvoiceCreatePO;
}


