/**
 * InvoiceDetailManager Module
 * Orchestrates all modules for Invoice Detail page with optimized parallel API calls
 */
class InvoiceDetailManager {
    constructor() {
        // Initialize API module
        if (typeof InvoiceDetailAPI !== 'undefined') {
            this.apiModule = new InvoiceDetailAPI();
        }
        
        this.invoiceId = null;
        this.invoice = null;
        this.poData = null;
        this.vendorData = null;
        this.poItems = [];
        this.invoiceItems = [];
        this.grnItems = [];
        this.amortizations = null;
        this.invoiceDocuments = [];
        this.invoiceDetailsByPO = [];
        this.prAdditional = null;
        this.billingTypes = [];
        this.allPurchaseTypes = [];
        this.allPurchaseSubTypes = [];
        this.allTaxes = [];
        this.documentChecklistMappings = [];
        this.masterDocumentChecklists = [];
    }

    /**
     * Initialize and load invoice detail with optimized parallel API calls
     * @param {number|string} invoiceId - Invoice ID to load
     * @param {Object} options - Optional configuration
     * @param {string} options.loadingElementId - ID of loading element (default: 'loadingSection')
     * @param {string} options.dataElementId - ID of data element (default: 'dataSection')
     */
    async init(invoiceId, options = {}) {
        this.invoiceId = invoiceId;
        
        // Get element IDs from options or use defaults (used to scope population to View Invoice section)
        const loadingElementId = options.loadingElementId || 'loadingSection';
        const dataElementId = options.dataElementId || 'dataSection';
        this.dataElementId = dataElementId;
        
        if (!this.invoiceId) {
            this.showError('Invoice ID is required');
            return;
        }

        try {
            // Step 1: Load master data in parallel (these are independent)
            await this.loadMasterDataParallel();

            // Step 2: Load invoice detail first (we need this to get PO number)
            const rawInvoice = await this.apiModule.getInvoiceDetails(this.invoiceId);
            this.invoice = rawInvoice && typeof rawInvoice === 'object' ? rawInvoice : null;
            
            if (!this.invoice) {
                this.showError('Invoice not found');
                return;
            }

            const purchOrderID = String(this.getProp(this.invoice, 'purchOrderID', 'PurchOrderID', 'PurchaseOrderNumber', 'trxPROPurchaseOrderNumber') || '').trim();

            // Step 3: Load all dependent data in parallel (after we have invoice and PO number)
            await this.loadDependentDataParallel(purchOrderID);

            // Step 4: Populate all UI sections
            await this.populateAllSections();

            // Step 5: Show data section (use provided element IDs or defaults)
            const loadingElement = document.getElementById(loadingElementId);
            const dataElement = document.getElementById(dataElementId);
            
            if (loadingElement) {
                loadingElement.style.display = 'none';
            }
            if (dataElement) {
                dataElement.style.display = 'block';
            }
        } catch (error) {
            console.error('Error loading invoice detail:', error);
            this.showError(error.message || 'Failed to load invoice detail');
        }
    }

    /**
     * Load all master data in parallel
     */
    async loadMasterDataParallel() {
        const [
            purchaseTypesResult,
            purchaseSubTypesResult,
            taxesResult,
            billingTypesResult,
            documentChecklistMappingsResult,
            masterDocumentChecklistsResult
        ] = await Promise.allSettled([
            this.apiModule.getPurchaseTypes(),
            this.apiModule.getPurchaseSubTypes(),
            this.apiModule.getTaxes(),
            this.apiModule.getBillingTypes(),
            this.apiModule.getInvoiceWorkTypeTOPDocumentChecklists(),
            this.apiModule.getInvoiceDocumentChecklists()
        ]);

        // Process results
        if (purchaseTypesResult.status === 'fulfilled') {
            this.allPurchaseTypes = purchaseTypesResult.value || [];
        }
        if (purchaseSubTypesResult.status === 'fulfilled') {
            this.allPurchaseSubTypes = purchaseSubTypesResult.value || [];
        }
        if (taxesResult.status === 'fulfilled') {
            this.allTaxes = taxesResult.value || [];
        }
        if (billingTypesResult.status === 'fulfilled') {
            this.billingTypes = billingTypesResult.value || [];
        }
        if (documentChecklistMappingsResult.status === 'fulfilled') {
            this.documentChecklistMappings = documentChecklistMappingsResult.value || [];
        }
        if (masterDocumentChecklistsResult.status === 'fulfilled') {
            this.masterDocumentChecklists = masterDocumentChecklistsResult.value || [];
        }
    }

    /**
     * Load all dependent data in parallel (after invoice is loaded)
     */
    async loadDependentDataParallel(purchOrderID) {
        const promises = [];

        // Always load these
        promises.push(this.apiModule.getInvoiceItems(this.invoiceId));
        promises.push(this.apiModule.getInvoiceDocuments(this.invoiceId));

        // Load PO-related data if PO number exists
        if (purchOrderID) {
            promises.push(this.apiModule.getPODetails(purchOrderID));
            promises.push(this.apiModule.getPOItems(purchOrderID));
            promises.push(this.apiModule.getInvoiceDetailsByPO(purchOrderID));
            promises.push(this.apiModule.getGRNItemsForInvoice(purchOrderID));
            promises.push(this.apiModule.getAmortizations(purchOrderID));
        }

        // Execute all in parallel
        const results = await Promise.allSettled(promises);

        // Process results
        let resultIndex = 0;
        
        // Invoice Items
        if (results[resultIndex].status === 'fulfilled') {
            this.invoiceItems = results[resultIndex].value || [];
        }
        resultIndex++;

        // Invoice Documents
        if (results[resultIndex].status === 'fulfilled') {
            this.invoiceDocuments = results[resultIndex].value || [];
        }
        resultIndex++;

        // PO Details (normalize: API may return { data: po } or raw row)
        if (purchOrderID && results[resultIndex].status === 'fulfilled') {
            const raw = results[resultIndex].value;
            this.poData = raw && typeof raw === 'object' ? (raw.data ?? raw.Data ?? raw) : null;
        }
        resultIndex++;

        // PO Items (ensure array)
        if (purchOrderID && results[resultIndex].status === 'fulfilled') {
            const raw = results[resultIndex].value;
            this.poItems = Array.isArray(raw) ? raw : (raw?.data ?? raw?.Data ?? []) || [];
        }
        resultIndex++;

        // Invoice Details By PO
        if (purchOrderID && results[resultIndex].status === 'fulfilled') {
            const raw = results[resultIndex].value;
            this.invoiceDetailsByPO = Array.isArray(raw) ? raw : (raw?.data ?? raw?.Data ?? []) || [];
        }
        resultIndex++;

        // GRN items for PO (trxInventoryGoodReceiveNote)
        if (purchOrderID && results[resultIndex].status === 'fulfilled') {
            const raw = results[resultIndex].value;
            this.grnItems = Array.isArray(raw) ? raw : [];
        }
        resultIndex++;

        // Amortizations (trxPROPurchaseOrderAmortization - for Term/Period status)
        if (purchOrderID && results[resultIndex].status === 'fulfilled') {
            const raw = results[resultIndex].value;
            this.amortizations = raw && typeof raw === 'object' ? raw : null;
        }

        // Now load PR Additional and Vendor Details in parallel (after we have PO data)
        const secondaryPromises = [];
        
        if (this.poData) {
            const prNumber = this.getProp(this.poData, 'prNumber', 'PRNumber', 'trxPROPurchaseRequestNumber', 'TrxPROPurchaseRequestNumber', 'PurchaseRequestNumber');
            if (prNumber) {
                secondaryPromises.push(this.apiModule.getPRAdditional(prNumber));
            }
            
            const vendorID = this.getProp(this.poData, 'mstVendorVendorID', 'MstVendorVendorID', 'VendorID', 'vendorID', 'VendorId');
            if (vendorID) {
                secondaryPromises.push(this.loadVendorDetailsOptimized(vendorID));
            }
        }

        // Execute secondary promises in parallel
        if (secondaryPromises.length > 0) {
            const secondaryResults = await Promise.allSettled(secondaryPromises);
            let secondaryIndex = 0;
            
            // PR Additional
            if (this.poData) {
                const prNumber = this.poData.prNumber || this.poData.PRNumber || this.poData.trxPROPurchaseRequestNumber || this.poData.TrxPROPurchaseRequestNumber || '';
                if (prNumber && secondaryResults[secondaryIndex].status === 'fulfilled') {
                    this.prAdditional = secondaryResults[secondaryIndex].value || null;
                }
                secondaryIndex++;
            }
            
            // Vendor Details
            if (this.poData && secondaryResults[secondaryIndex]) {
                if (secondaryResults[secondaryIndex].status === 'fulfilled') {
                    this.vendorData = secondaryResults[secondaryIndex].value || null;
                }
            }
        }
    }

    /**
     * Load vendor details (optimized - tries direct ID first, then searches in list)
     */
    async loadVendorDetailsOptimized(vendorID) {
        try {
            // Try direct API call first (vendorID might be the actual ID)
            return await this.apiModule.getVendorDetails(vendorID);
        } catch (error) {
            // If direct call fails, we might need to search in vendor list
            // For now, return null and let the UI handle it
            console.warn('Could not load vendor details directly, may need vendor list lookup');
            return null;
        }
    }

    /**
     * Get the container element for View/Detail section (so we only touch elements inside it)
     */
    getDataContainer() {
        const id = this.dataElementId || 'dataSection';
        return document.getElementById(id) || document.body;
    }

    /**
     * Get first defined value from object with multiple possible keys (supports DB column names)
     */
    getProp(obj, ...keys) {
        if (!obj || typeof obj !== 'object') return '';
        for (const k of keys) {
            const v = obj[k];
            if (v !== undefined && v !== null && v !== '') return v;
        }
        return '';
    }

    /**
     * Get numeric value from object by key; tries exact keys then case-insensitive match (for DB column names)
     */
    getPropNumber(obj, ...keyVariants) {
        if (!obj || typeof obj !== 'object') return undefined;
        for (const key of keyVariants) {
            const v = obj[key];
            if (v !== undefined && v !== null && v !== '') return Number(v);
        }
        const lowerKeys = keyVariants.map(k => String(k).toLowerCase());
        for (const k of Object.keys(obj)) {
            if (lowerKeys.includes(k.toLowerCase())) {
                const v = obj[k];
                if (v !== undefined && v !== null && v !== '') return Number(v);
            }
        }
        return undefined;
    }

    /**
     * Populate all UI sections
     */
    async populateAllSections() {
        // Populate Invoice Information
        this.populateInvoiceInformation();

        // Populate PO Information (always: at least PO number from invoice; full section when poData exists)
        this.populatePOInformation();

        // Populate Vendor Information
        if (this.vendorData) {
            this.populateVendorInformation();
        }

        // Term / Period of Payment sections
        if (this.poData) {
            const prNumber = this.getProp(this.poData, 'prNumber', 'PRNumber', 'trxPROPurchaseRequestNumber', 'TrxPROPurchaseRequestNumber');
            if (prNumber && this.prAdditional) {
                const hasPeriodPayment = this.checkAndLoadPeriodOfPayment();
                if (hasPeriodPayment) {
                    this.populatePeriodOfPayment();
                } else {
                    this.populateTermOfPayment();
                }
            } else {
                this.populateTermOfPayment();
            }
        } else {
            this.populateTermOfPayment();
        }

        // Populate Invoice Items
        this.populateInvoiceItems();

        // Populate Document Checklist
        this.populateDocumentChecklist();
    }

    /**
     * Populate Invoice Information section
     */
    populateInvoiceInformation() {
        const invoice = this.invoice;
        if (!invoice) return;

        const container = this.getDataContainer();
        const $scope = typeof $ !== 'undefined' ? $(container) : null;

        const invoiceNumber = this.getProp(invoice, 'invoiceNumber', 'InvoiceNumber', 'InvoiceNo');
        const purchOrderID = this.getProp(invoice, 'purchOrderID', 'PurchOrderID', 'PurchaseOrderNumber', 'trxPROPurchaseOrderNumber');
        
        let termPositionText = '';
        if (invoiceNumber && purchOrderID && this.invoiceDetailsByPO.length > 0) {
            const matchingInvoices = this.invoiceDetailsByPO.filter(inv => {
                const invNumber = this.getProp(inv, 'invoiceNumber', 'InvoiceNumber');
                return invNumber && String(invNumber).trim() === String(invoiceNumber).trim();
            });
            
            if (matchingInvoices.length > 0) {
                const periodNumbers = [];
                matchingInvoices.forEach(inv => {
                    const termPos = parseInt(this.getProp(inv, 'termPosition', 'TermPosition'), 10) || 0;
                    if (termPos > 0 && !periodNumbers.includes(termPos)) {
                        periodNumbers.push(termPos);
                    }
                });
                
                if (periodNumbers.length > 0) {
                    periodNumbers.sort((a, b) => a - b);
                    termPositionText = periodNumbers.map(p => `Period ${p}`).join(', ');
                }
            }
        }
        
        if (!termPositionText) {
            const singleTermPos = parseInt(this.getProp(invoice, 'termPosition', 'TermPosition'), 10) || 0;
            if (singleTermPos > 0) {
                termPositionText = `Period ${singleTermPos}`;
            }
        }

        const setVal = (id, val) => {
            if ($scope && $scope.length) $scope.find('#' + id).val(val);
            else { const el = container.querySelector('#' + id); if (el) el.value = val; }
        };
        setVal('detailTermPosition', termPositionText);
        setVal('detailInvoiceNumber', invoiceNumber);
        setVal('detailInvoiceDate', this.formatDate(this.getProp(invoice, 'invoiceDate', 'InvoiceDate', 'CreatedDate')));
        
        const taxCodeId = this.getProp(invoice, 'taxCode', 'TaxCode', 'mstTaxID');
        setVal('detailTaxCode', this.getTaxName(taxCodeId));
        setVal('detailTaxNumber', this.getProp(invoice, 'taxNumber', 'TaxNumber'));
        setVal('detailTaxDate', this.formatDate(this.getProp(invoice, 'taxDate', 'TaxDate')));
        setVal('detailPICName', this.getProp(invoice, 'picName', 'PICName', 'PicName'));
        setVal('detailPICEmail', this.getProp(invoice, 'emailAddress', 'EmailAddress'));
        
        const termVal = this.getProp(invoice, 'termValue', 'TermValue');
        setVal('detailTermInvoice', termVal ? `${termVal}%` : '');
        setVal('detailInvoiceAmount', this.formatCurrency(this.getProp(invoice, 'invoiceAmount', 'InvoiceAmount') || 0));
        setVal('detailDPPAmount', this.formatCurrency(this.getProp(invoice, 'dppAmount', 'DPPAmount') || 0));
        setVal('detailTaxAmount', this.formatCurrency(this.getProp(invoice, 'taxAmount', 'TaxAmount') || 0));
        setVal('detailTotalAmount', this.formatCurrency(this.getProp(invoice, 'totalAmount', 'TotalAmount') || 0));
    }

    /**
     * Populate PO Information section (always run: at least PO number from invoice)
     */
    populatePOInformation() {
        const po = this.poData;
        const invoice = this.invoice;
        if (!invoice) return;

        const container = this.getDataContainer();
        const $scope = typeof $ !== 'undefined' ? $(container) : null;
        const setVal = (id, val) => {
            if ($scope && $scope.length) $scope.find('#' + id).val(val);
            else { const el = container.querySelector('#' + id); if (el) el.value = val; }
        };
        const setVisible = (id, visible) => {
            const el = container.querySelector('#' + id);
            if (el) el.style.display = visible ? '' : 'none';
        };

        const purchOrderID = String(this.getProp(invoice, 'purchOrderID', 'PurchOrderID', 'PurchaseOrderNumber', 'trxPROPurchaseOrderNumber') || '').trim();
        setVal('detailPurchOrderID', purchOrderID);

        if (po) {
            setVal('detailPurchOrderName', this.getProp(po, 'purchOrderName', 'PurchOrderName', 'PurchaseOrderName', 'trxPROPurchaseOrderName'));
            setVal('detailPODate', this.formatDate(this.getProp(po, 'poDate', 'PODate', 'PurchaseOrderDate')));
            setVal('detailPOAmount', this.formatNumberNoCurrency(this.getProp(po, 'poAmount', 'POAmount', 'Amount') || 0));
            
            const siteName = this.getProp(po, 'mstSiteName', 'MstSiteName');
            const siteID = this.getProp(po, 'mstSiteID', 'MstSiteID');
            const siteFromInv = this.getProp(invoice, 'siteName', 'SiteName', 'siteID', 'SiteID');
            const siteValue = siteName || siteID || siteFromInv;
            setVisible('detailSiteFieldWrapper', !!siteValue);
            if (siteValue) setVal('detailSite', siteValue);
            
            setVal('detailStatusPO', this.getProp(po, 'approvalStatus', 'ApprovalStatus', 'Status'));
            const purchTypeFormatted = this.formatPurchaseType(this.getProp(po, 'purchType', 'PurchType', 'PurchaseType'));
            setVal('detailPurchType', purchTypeFormatted);
            setVal('detailPurchSubType', this.formatPurchaseSubType(this.getProp(po, 'purchSubType', 'PurchSubType', 'PurchaseSubType')));
            setVal('detailTermOfPayment', this.getProp(po, 'topDescription', 'TOPDescription', 'TermOfPayment'));
            setVal('detailCompany', this.getProp(po, 'companyName', 'CompanyName', 'Company'));
        }

        // Work Type = same value as Purchase Type
        const workTypeDisplay = (po && this.getProp(po, 'purchType', 'PurchType', 'PurchaseType') != null)
            ? this.formatPurchaseType(this.getProp(po, 'purchType', 'PurchType', 'PurchaseType'))
            : (this.getProp(invoice, 'workType', 'WorkType') || (po ? this.getProp(po, 'workType', 'WorkType') : ''));
        setVal('detailWorkType', workTypeDisplay);
        
        const productTypeValue = this.getProp(invoice, 'product', 'Product', 'productType', 'ProductType') || (po ? this.getProp(po, 'productType', 'ProductType') : '');
        setVisible('detailProductTypeFieldWrapper', !!productTypeValue);
        if (productTypeValue) setVal('detailProductType', productTypeValue);
        
        const sonumberValue = this.getProp(invoice, 'sonumber', 'SONumber', 'SoNumber') || (po ? this.getProp(po, 'sonumber', 'SONumber') : '');
        setVisible('detailSonumbFieldWrapper', !!sonumberValue);
        if (sonumberValue) setVal('detailSONumber', sonumberValue);
    }

    /**
     * Populate Vendor Information section
     */
    populateVendorInformation() {
        const vendor = this.vendorData;
        if (!vendor) return;

        const container = this.getDataContainer();
        const $scope = typeof $ !== 'undefined' ? $(container) : null;
        const setVal = (id, val) => {
            if ($scope && $scope.length) $scope.find('#' + id).val(val);
            else { const el = container.querySelector('#' + id); if (el) el.value = val; }
        };

        setVal('detailVendorName', vendor.vendorName || vendor.VendorName || '');
        setVal('detailContactPerson', vendor.cp || vendor.CP || '');
        setVal('detailEmailAddress', vendor.cpEmail || vendor.CPEmail || vendor.emailCorresspondence || vendor.EmailCorresspondence || '');
        setVal('detailPhoneNumber', vendor.cpPhone || vendor.CPPhone || vendor.telephone || vendor.Telephone || '');
        setVal('detailNPWP', vendor.npwp || vendor.NPWP || '');
        setVal('detailBank', vendor.bankName || vendor.BankName || '');
        setVal('detailAccountName', vendor.recipientName || vendor.RecipientName || '');
        setVal('detailAccountNumber', vendor.bankAccount || vendor.BankAccount || '');
        
        // Currency RP field removed per requirement
        const isActive = vendor.isActive !== undefined ? vendor.isActive : (vendor.IsActive !== undefined ? vendor.IsActive : false);
        setVal('detailVendorStatus', isActive ? 'Active' : 'Inactive');
    }

    /**
     * Check and load Period of Payment
     */
    checkAndLoadPeriodOfPayment() {
        if (!this.prAdditional) return false;
        
        const startPeriod = this.prAdditional.startPeriod || this.prAdditional.StartPeriod;
        const endPeriod = this.prAdditional.endPeriod || this.prAdditional.EndPeriod;
        
        if (!startPeriod && !endPeriod) return false;
        
        const period = this.prAdditional.period || this.prAdditional.Period || 0;
        const billingTypeID = this.prAdditional.billingTypeID || this.prAdditional.BillingTypeID;
        
        if (!billingTypeID || period <= 0) return false;
        
        const billingType = this.billingTypes.find(bt => (bt.id || bt.ID) === billingTypeID);
        if (!billingType) return false;
        
        return true;
    }

    /**
     * Populate Period of Payment section
     */
    populatePeriodOfPayment() {
        const po = this.poData;
        const invoice = this.invoice;
        if (!po || !this.prAdditional) return;

        const container = this.getDataContainer();
        const termSection = container.querySelector('#detailTermOfPaymentSection');
        const periodSection = container.querySelector('#detailPeriodOfPaymentSection');
        if (termSection) termSection.style.display = 'none';
        if (periodSection) periodSection.style.display = '';

        const startPeriod = this.prAdditional.startPeriod || this.prAdditional.StartPeriod;
        const endPeriod = this.prAdditional.endPeriod || this.prAdditional.EndPeriod;
        const period = this.prAdditional.period || this.prAdditional.Period || 0;
        const billingTypeID = this.prAdditional.billingTypeID || this.prAdditional.BillingTypeID;
        
        const billingType = this.billingTypes.find(bt => (bt.id || bt.ID) === billingTypeID);
        if (!billingType) return;
        
        const totalMonthPeriod = billingType.totalMonthPeriod || billingType.TotalMonthPeriod || 1;
        
        this.loadPeriodOfPaymentGrid(startPeriod, endPeriod, period, totalMonthPeriod, po, invoice);
    }

    /**
     * Populate Term of Payment section
     */
    populateTermOfPayment() {
        const po = this.poData;
        const invoice = this.invoice;
        const container = this.getDataContainer();
        const termSection = container.querySelector('#detailTermOfPaymentSection');
        const periodSection = container.querySelector('#detailPeriodOfPaymentSection');
        if (termSection) termSection.style.display = '';
        if (periodSection) periodSection.style.display = 'none';

        if (!po) {
            const tbody = container.querySelector('#tblDetailTermOfPaymentBody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">No PO data available</td></tr>';
            return;
        }
        
        this.loadTermOfPaymentGrid(po, invoice);
    }

    /**
     * Load Term of Payment grid.
     * Uses trxPROPurchaseOrderAmortization when available (Status from DB); otherwise falls back to TOP description.
     */
    loadTermOfPaymentGrid(po, invoice) {
        const container = this.getDataContainer();
        const tbody = container.querySelector('#tblDetailTermOfPaymentBody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        const currentInvoiceNumber = this.getProp(invoice, 'invoiceNumber', 'InvoiceNumber', 'InvoiceNo');
        
        const termRows = this.amortizations && this.amortizations.termOfPayment && this.amortizations.termOfPayment.length > 0
            ? this.amortizations.termOfPayment
            : null;
        
        if (termRows && termRows.length > 0) {
            termRows.forEach((amort) => {
                const periodNumber = amort.periodNumber ?? amort.period ?? 0;
                const termValue = amort.termValue ?? amort.term ?? 0;
                const invoiceAmount = amort.invoiceAmount ?? amort.InvoiceAmount ?? 0;
                const statusFromDb = amort.status ?? amort.Status ?? '';
                const amortInvoiceNumber = amort.invoiceNumber ?? amort.InvoiceNumber ?? '';
                const isCurrent = currentInvoiceNumber && String(amortInvoiceNumber).trim() === String(currentInvoiceNumber).trim();
                const statusClass = isCurrent ? 'text-success fw-bold' : (statusFromDb ? 'text-success' : 'text-muted');
                const statusText = statusFromDb || (isCurrent ? 'Current Invoice' : '');
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="text-center">Term ${periodNumber}</td>
                    <td class="text-center">${termValue}%</td>
                    <td class="text-center ${statusClass}">${statusText}</td>
                    <td class="text-center">${this.formatCurrency(invoiceAmount)}</td>
                `;
                tbody.appendChild(row);
            });
            return;
        }
        
        const topDescription = this.getProp(po, 'topDescription', 'TOPDescription');
        if (!topDescription) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">No Term of Payment available</td></tr>';
            return;
        }
        
        const termValues = topDescription.split('|').map(v => parseFloat(String(v).trim())).filter(v => !isNaN(v));
        if (termValues.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">Invalid Term of Payment format</td></tr>';
            return;
        }
        
        const poAmount = parseFloat(this.getProp(po, 'poAmount', 'POAmount') || 0);
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                           'July', 'August', 'September', 'October', 'November', 'December'];
        const currentTermPosition = parseInt(this.getProp(invoice, 'termPosition', 'TermPosition'), 10) || 0;
        
        const submittedTerms = new Set();
        if (this.invoiceDetailsByPO && this.invoiceDetailsByPO.length > 0) {
            this.invoiceDetailsByPO.forEach(inv => {
                const termPos = parseInt(this.getProp(inv, 'termPosition', 'TermPosition'), 10) || 0;
                if (termPos > 0) submittedTerms.add(termPos);
            });
        }
        
        termValues.forEach((termValue, index) => {
            const termNumber = index + 1;
            const termMonth = monthNames[index] || `Term ${termNumber}`;
            const invoiceAmount = (poAmount * termValue) / 100;
            const hasInvoice = submittedTerms.has(termNumber);
            const isCurrentTerm = currentTermPosition === termNumber;
            let status = isCurrentTerm ? 'Current Invoice' : (hasInvoice ? 'Submitted' : 'Not Submitted');
            const statusClass = isCurrentTerm ? 'text-success fw-bold' : (hasInvoice ? 'text-success' : 'text-muted');
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="text-center">${termMonth}</td>
                <td class="text-center">${termValue}%</td>
                <td class="text-center ${statusClass}">${status}</td>
                <td class="text-center">${this.formatCurrency(invoiceAmount)}</td>
            `;
            tbody.appendChild(row);
        });
    }

    /**
     * Load Period of Payment grid.
     * Uses trxPROPurchaseOrderAmortization when available (Status from DB); otherwise generates from PR dates.
     */
    loadPeriodOfPaymentGrid(startPeriod, endPeriod, period, totalMonthPeriod, po, invoice) {
        const container = this.getDataContainer();
        const tbody = container.querySelector('#tblDetailPeriodOfPaymentBody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        const currentInvoiceNumber = this.getProp(invoice, 'invoiceNumber', 'InvoiceNumber', 'InvoiceNo');
        
        const periodRows = this.amortizations && this.amortizations.periodOfPayment && this.amortizations.periodOfPayment.length > 0
            ? this.amortizations.periodOfPayment
            : null;
        
        if (periodRows && periodRows.length > 0) {
            periodRows.forEach((amort) => {
                const periodNumber = amort.periodNumber ?? amort.period ?? 0;
                const termValue = amort.termValue ?? amort.term ?? 100;
                const invoiceAmount = amort.invoiceAmount ?? amort.InvoiceAmount ?? 0;
                const statusFromDb = amort.status ?? amort.Status ?? '';
                const amortInvoiceNumber = amort.invoiceNumber ?? amort.InvoiceNumber ?? '';
                const isCurrent = currentInvoiceNumber && String(amortInvoiceNumber).trim() === String(currentInvoiceNumber).trim();
                const statusClass = isCurrent ? 'text-success fw-bold' : (statusFromDb ? 'text-success' : 'text-muted');
                const statusText = statusFromDb || (isCurrent ? 'Current Invoice' : '');
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="text-center">Period ${periodNumber}</td>
                    <td class="text-center">${termValue}%</td>
                    <td class="text-center">${this.formatDate(amort.startDate)}</td>
                    <td class="text-center">${this.formatDate(amort.endDate)}</td>
                    <td class="text-center ${statusClass}">${statusText}</td>
                    <td class="text-center">${this.formatCurrency(invoiceAmount)}</td>
                `;
                tbody.appendChild(row);
            });
            return;
        }
        
        const startDate = new Date(startPeriod);
        const endDate = new Date(endPeriod);
        
        if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-danger">Invalid date format</td></tr>';
            return;
        }
        
        const poAmount = parseFloat(this.getProp(po, 'poAmount', 'POAmount') || 0);
        const termValue = 100;
        const invoiceAmount = poAmount;
        
        const amortizations = this.generateAmortization(startDate, endDate, period, totalMonthPeriod);
        if (amortizations.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No amortization periods generated</td></tr>';
            return;
        }
        
        const submittedPeriods = new Set();
        const currentInvoicePeriods = new Set();
        if (this.invoiceDetailsByPO && this.invoiceDetailsByPO.length > 0) {
            this.invoiceDetailsByPO.forEach(inv => {
                const termPos = parseInt(this.getProp(inv, 'termPosition', 'TermPosition'), 10) || 0;
                const invNumber = this.getProp(inv, 'invoiceNumber', 'InvoiceNumber');
                if (termPos > 0) {
                    submittedPeriods.add(termPos);
                    if (currentInvoiceNumber && invNumber === currentInvoiceNumber) {
                        currentInvoicePeriods.add(termPos);
                    }
                }
            });
        }
        
        amortizations.forEach((amort, index) => {
            const periodNumber = index + 1;
            const hasInvoice = submittedPeriods.has(periodNumber);
            const isCurrentInvoicePeriod = currentInvoicePeriods.has(periodNumber);
            let status = isCurrentInvoicePeriod ? 'Current Invoice' : (hasInvoice ? 'Submitted' : 'Not Submitted');
            const statusClass = isCurrentInvoicePeriod ? 'text-success fw-bold' : (hasInvoice ? 'text-success' : 'text-muted');
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="text-center">Period ${periodNumber}</td>
                <td class="text-center">${termValue}%</td>
                <td class="text-center">${this.formatDate(amort.startDate)}</td>
                <td class="text-center">${this.formatDate(amort.endDate)}</td>
                <td class="text-center ${statusClass}">${status}</td>
                <td class="text-center">${this.formatCurrency(invoiceAmount)}</td>
            `;
            tbody.appendChild(row);
        });
    }

    /**
     * Generate amortization periods
     */
    generateAmortization(startDate, endDate, period, totalMonthPeriod) {
        const amortizations = [];
        let currentStart = new Date(startDate);
        const finalEnd = new Date(endDate);
        
        for (let i = 0; i < period; i++) {
            let currentEnd;
            
            if (i === 0) {
                currentEnd = new Date(currentStart);
                currentEnd.setMonth(currentEnd.getMonth() + totalMonthPeriod);
                currentEnd.setDate(0);
            } else {
                currentStart = new Date(amortizations[i - 1].endDate);
                currentStart.setDate(currentStart.getDate() + 1);
                currentStart.setDate(1);
                
                currentEnd = new Date(currentStart);
                currentEnd.setMonth(currentEnd.getMonth() + totalMonthPeriod);
                currentEnd.setDate(0);
            }
            
            if (i === period - 1) {
                currentEnd = new Date(finalEnd);
            } else {
                if (currentEnd > finalEnd) {
                    currentEnd = new Date(finalEnd);
                }
            }
            
            amortizations.push({
                periodNumber: i + 1,
                startDate: new Date(currentStart),
                endDate: new Date(currentEnd)
            });
            
            if (currentEnd >= finalEnd) {
                break;
            }
        }
        
        return amortizations;
    }

    /**
     * Populate Invoice Items section.
     * Uses GRN items (trxInventoryGoodReceiveNote) for the PO when available; otherwise invoice items.
     */
    populateInvoiceItems() {
        const container = this.getDataContainer();
        const tbody = container.querySelector('#tblItemListBody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        const useGRN = this.grnItems && this.grnItems.length > 0;
        const items = useGRN ? this.grnItems : this.invoiceItems;
        
        if (items.length > 0) {
            items.forEach(grnOrInvItem => {
                const itemID = this.getProp(grnOrInvItem, 'mstPROPurchaseItemInventoryItemID', 'MstPROPurchaseItemInventoryItemID', 'mstPROInventoryItemID', 'ItemID', 'itemID');
                const itemName = this.getProp(grnOrInvItem, 'itemName', 'ItemName');
                const unitName = this.getProp(grnOrInvItem, 'itemUnit', 'ItemUnit', 'Unit', 'unit');
                const desc = this.getProp(grnOrInvItem, 'itemDescription', 'ItemDescription', 'description', 'Description');
                let qtyPO = parseFloat(this.getProp(grnOrInvItem, 'itemQty', 'ItemQty', 'ActualReceived', 'quantityPO', 'QuantityPO') || 0) || 0;
                let pricePO = parseFloat(this.getProp(grnOrInvItem, 'unitPrice', 'UnitPrice', 'pricePO', 'PricePO') || 0) || 0;
                let lineAmountPO = parseFloat(this.getProp(grnOrInvItem, 'amount', 'Amount', 'lineAmountPO', 'LineAmountPO') || 0) || 0;
                const getQtyInv = (obj) => this.getPropNumber(obj, 'QuantityInvoice', 'quantityInvoice', 'QtyInvoice', 'qtyInvoice', 'quantity_invoice');
                const getLineAmountInv = (obj) => this.getPropNumber(obj, 'LineAmountInvoice', 'lineAmountInvoice', 'AmountInvoice', 'amountInvoice', 'line_amount_invoice');
                let qtyInv;
                let lineAmountInv;
                if (useGRN && this.invoiceItems && this.invoiceItems.length > 0) {
                    const invItem = this.invoiceItems.find(inv => {
                        const id = this.getProp(inv, 'mstPROPurchaseItemInventoryItemID', 'MstPROPurchaseItemInventoryItemID', 'ItemID', 'itemID', 'mstPROInventoryItemID');
                        return String(id || '') === String(itemID || '');
                    });
                    if (invItem) {
                        qtyInv = getQtyInv(invItem);
                        lineAmountInv = getLineAmountInv(invItem);
                    } else {
                        qtyInv = undefined;
                        lineAmountInv = undefined;
                    }
                } else {
                    qtyInv = getQtyInv(grnOrInvItem);
                    lineAmountInv = getLineAmountInv(grnOrInvItem);
                }
                if ((lineAmountInv == null || lineAmountInv === 0 || isNaN(lineAmountInv)) && qtyInv != null && pricePO) lineAmountInv = qtyInv * pricePO;
                if ((qtyInv == null || qtyInv === 0 || qtyInv === '' || isNaN(qtyInv)) && lineAmountInv != null && lineAmountInv > 0 && pricePO) qtyInv = lineAmountInv / pricePO;
                const qtyInvDisplay = (qtyInv != null && !isNaN(qtyInv) && qtyInv !== '') ? (Number(qtyInv) % 1 === 0 ? Number(qtyInv) : Number(qtyInv).toFixed(2)) : '0';
                const lineAmountInvNum = (lineAmountInv != null && !isNaN(lineAmountInv)) ? Number(lineAmountInv) : 0;

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="text-center">${itemID}</td>
                    <td class="text-center">${itemName}</td>
                    <td class="text-center">${unitName}</td>
                    <td class="text-center">${desc}</td>
                    <td class="text-center">${qtyPO}</td>
                    <td class="text-center">${this.formatNumberNoCurrency(pricePO)}</td>
                    <td class="text-center">${this.formatNumberNoCurrency(lineAmountPO)}</td>
                    <td class="text-center">${qtyInvDisplay}</td>
                    <td class="text-center">${this.formatNumberNoCurrency(lineAmountInvNum)}</td>
                `;
                tbody.appendChild(row);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted">No items available</td></tr>';
        }
    }

    /**
     * Populate Document Checklist section
     */
    populateDocumentChecklist() {
        const invoice = this.invoice;
        if (!invoice) return;

        const workTypeID = invoice.workTypeID || invoice.WorkTypeID || 1;
        const termValue = invoice.termValue || invoice.TermValue || 0;
        const invoiceId = invoice.invoiceHeaderID || invoice.InvoiceHeaderID || invoice.id || invoice.ID;
        
        const searchWorkTypeID = parseInt(workTypeID) || 0;
        const searchTermNumber = parseFloat(termValue) || 0;
        
        const filteredMappings = this.documentChecklistMappings.filter(item => {
            const itemWorkTypeID = parseInt(item.mstFINInvoiceWorkTypeID || item.mstFinInvoiceWorkTypeId || 0);
            const itemTermNumber = parseFloat(item.termNumber || item.TermNumber || 0);
            
            return itemWorkTypeID === searchWorkTypeID && 
                   itemTermNumber === searchTermNumber;
        });

        const container = this.getDataContainer();
        const tbody = container.querySelector('#tblDocumentChecklistBody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        if (filteredMappings.length > 0) {
            filteredMappings.forEach((mapping, index) => {
                const documentChecklistID = mapping.mstFINInvoiceDocumentChecklistID || mapping.mstFinInvoiceDocumentChecklistId || 0;
                const masterChecklist = this.masterDocumentChecklists.find(mc => 
                    parseInt(mc.id || mc.ID || 0) === parseInt(documentChecklistID)
                );
                
                if (masterChecklist) {
                    const documentName = masterChecklist.documentName || masterChecklist.DocumentName || '';
                    const isMandatory = mapping.isMandatory !== undefined ? mapping.isMandatory : (mapping.IsMandatory !== undefined ? mapping.IsMandatory : false);
                    const isAuto = mapping.isAuto !== undefined ? mapping.isAuto : (mapping.IsAuto !== undefined ? mapping.IsAuto : false);
                    const mandatoryName = isMandatory ? 'Yes' : 'No';
                    const autoName = isAuto ? 'Auto' : 'Manual';
                    
                    const matchedDoc = this.invoiceDocuments[index] || null;
                    const documentNumber = matchedDoc ? (matchedDoc.documentNumber || matchedDoc.DocumentNumber || '-') : '-';
                    const remark = matchedDoc ? (matchedDoc.remark || matchedDoc.Remark || '-') : '-';
                    const filePath = matchedDoc ? (matchedDoc.filePath || matchedDoc.FilePath || '') : '';
                    const fileName = filePath ? filePath.split('/').pop() || filePath.split('\\').pop() : '-';
                    
                    let fileCell = '-';
                    if (filePath) {
                        fileCell = `<a href="${filePath}" target="_blank" class="text-primary" title="Download file">${fileName}</a>`;
                    }
                    
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="text-center">${documentName}</td>
                        <td class="text-center">${documentNumber}</td>
                        <td class="text-center">${remark}</td>
                        <td class="text-center">${mandatoryName}</td>
                        <td class="text-center">${autoName}</td>
                        <td class="text-center">${fileCell}</td>
                    `;
                    tbody.appendChild(row);
                }
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No document checklist available</td></tr>';
        }
    }

    /**
     * Utility functions
     */
    formatCurrency(amount) {
        return new Intl.NumberFormat('id-ID', { 
            style: 'currency', 
            currency: 'IDR', 
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount);
    }

    /** Format number without "Rp" (e.g. 30.000.000) for display where currency prefix is hidden */
    formatNumberNoCurrency(amount) {
        if (amount == null || amount === '' || isNaN(Number(amount))) return '0';
        return new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(Number(amount));
    }

    formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-GB');
    }

    getTaxName(taxId) {
        if (!taxId && taxId !== 0) return '-';
        
        const taxIdNum = typeof taxId === 'string' ? parseInt(taxId.trim(), 10) : parseInt(taxId, 10);
        
        if (isNaN(taxIdNum) || taxIdNum <= 0 || !this.allTaxes.length) {
            return taxId || '-';
        }
        
        const tax = this.allTaxes.find(t => {
            const tId = t.id || t.ID || 0;
            return parseInt(tId, 10) === taxIdNum;
        });
        
        if (tax) {
            return tax.taxName || tax.TaxName || taxId;
        }
        
        return taxId || '-';
    }

    formatPurchaseType(purchType) {
        if (!purchType && purchType !== 0) return '-';
        
        const purchTypeStr = typeof purchType === 'number' ? purchType.toString() : String(purchType || '');
        
        if (purchTypeStr.trim() === '') return '-';
        
        if (purchTypeStr.includes(' ') && isNaN(parseInt(purchTypeStr.trim()))) {
            return purchTypeStr;
        }
        
        const typeId = parseInt(purchTypeStr.trim(), 10);
        if (!isNaN(typeId) && typeId > 0 && this.allPurchaseTypes.length) {
            const type = this.allPurchaseTypes.find(t => 
                parseInt(t.ID || t.id || '0', 10) === typeId
            );
            if (type) {
                const prType = type.PurchaseRequestType || type.purchaseRequestType || '';
                const category = type.Category || type.category || '';
                if (!category) {
                    return prType;
                }
                if (prType === category) {
                    return prType;
                }
                return `${prType} ${category}`;
            }
        }
        
        return purchTypeStr;
    }

    formatPurchaseSubType(purchSubType) {
        if (!purchSubType && purchSubType !== 0) return '-';
        
        const purchSubTypeStr = typeof purchSubType === 'number' ? purchSubType.toString() : String(purchSubType || '');
        
        if (purchSubTypeStr.trim() === '') return '-';
        
        if (isNaN(parseInt(purchSubTypeStr.trim()))) {
            return purchSubTypeStr;
        }
        
        const subTypeId = parseInt(purchSubTypeStr.trim(), 10);
        if (!isNaN(subTypeId) && subTypeId > 0 && this.allPurchaseSubTypes.length) {
            const subType = this.allPurchaseSubTypes.find(st => 
                parseInt(st.ID || st.id || '0', 10) === subTypeId
            );
            if (subType) {
                return subType.PurchaseRequestSubType || subType.purchaseRequestSubType || '';
            }
        }
        
        return purchSubTypeStr;
    }

    showError(message) {
        console.error(message);
        alert(message);
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.InvoiceDetailManager = InvoiceDetailManager;
}

