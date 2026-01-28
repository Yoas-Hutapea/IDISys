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
        
        // Get element IDs from options or use defaults
        const loadingElementId = options.loadingElementId || 'loadingSection';
        const dataElementId = options.dataElementId || 'dataSection';
        
        if (!this.invoiceId) {
            this.showError('Invoice ID is required');
            return;
        }

        try {
            // Step 1: Load master data in parallel (these are independent)
            await this.loadMasterDataParallel();

            // Step 2: Load invoice detail first (we need this to get PO number)
            this.invoice = await this.apiModule.getInvoiceDetails(this.invoiceId);
            
            if (!this.invoice) {
                this.showError('Invoice not found');
                return;
            }

            const purchOrderID = this.invoice.purchOrderID || this.invoice.PurchOrderID || '';

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

        // PO Details
        if (purchOrderID && results[resultIndex].status === 'fulfilled') {
            this.poData = results[resultIndex].value || null;
        }
        resultIndex++;

        // PO Items
        if (purchOrderID && results[resultIndex].status === 'fulfilled') {
            this.poItems = results[resultIndex].value || [];
        }
        resultIndex++;

        // Invoice Details By PO
        if (purchOrderID && results[resultIndex].status === 'fulfilled') {
            this.invoiceDetailsByPO = results[resultIndex].value || [];
        }

        // Now load PR Additional and Vendor Details in parallel (after we have PO data)
        const secondaryPromises = [];
        
        // Get PR number from PO data
        if (this.poData) {
            const prNumber = this.poData.prNumber || this.poData.PRNumber || this.poData.trxPROPurchaseRequestNumber || this.poData.TrxPROPurchaseRequestNumber || '';
            if (prNumber) {
                secondaryPromises.push(this.apiModule.getPRAdditional(prNumber));
            }
            
            // Load vendor details
            const vendorID = this.poData.mstVendorVendorID || this.poData.MstVendorVendorID;
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
     * Populate all UI sections
     */
    async populateAllSections() {
        // Populate Invoice Information
        this.populateInvoiceInformation();

        // Populate PO Information
        if (this.poData) {
            this.populatePOInformation();

            // Populate Vendor Information
            if (this.vendorData) {
                this.populateVendorInformation();
            }

            // Check and load Period of Payment or Term of Payment
            const prNumber = this.poData.prNumber || this.poData.PRNumber || '';
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

        const invoiceNumber = invoice.invoiceNumber || invoice.InvoiceNumber || '';
        const purchOrderID = invoice.purchOrderID || invoice.PurchOrderID || '';
        
        // Get term position text from invoiceDetailsByPO
        let termPositionText = '';
        if (invoiceNumber && purchOrderID && this.invoiceDetailsByPO.length > 0) {
            const matchingInvoices = this.invoiceDetailsByPO.filter(inv => {
                const invNumber = inv.invoiceNumber || inv.InvoiceNumber || '';
                return invNumber && invNumber.trim() === invoiceNumber.trim();
            });
            
            if (matchingInvoices.length > 0) {
                const periodNumbers = [];
                matchingInvoices.forEach(inv => {
                    const termPos = inv.termPosition || inv.TermPosition || 0;
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
            const singleTermPos = invoice.termPosition || invoice.TermPosition || 0;
            if (singleTermPos > 0) {
                termPositionText = `Period ${singleTermPos}`;
            }
        }

        $('#detailTermPosition').val(termPositionText);
        $('#detailInvoiceNumber').val(invoiceNumber);
        $('#detailInvoiceDate').val(this.formatDate(invoice.invoiceDate || invoice.InvoiceDate));
        
        // Format Tax Code
        const taxCodeId = invoice.taxCode || invoice.TaxCode || '';
        const taxName = this.getTaxName(taxCodeId);
        $('#detailTaxCode').val(taxName);
        $('#detailTaxNumber').val(invoice.taxNumber || invoice.TaxNumber || '');
        $('#detailTaxDate').val(this.formatDate(invoice.taxDate || invoice.TaxDate));
        $('#detailPICName').val(invoice.picName || invoice.PICName || '');
        $('#detailPICEmail').val(invoice.emailAddress || invoice.EmailAddress || '');
        
        // Invoice Amount Summary
        $('#detailTermInvoice').val(invoice.termValue ? `${invoice.termValue}%` : '');
        $('#detailInvoiceAmount').val(this.formatCurrency(invoice.invoiceAmount || invoice.InvoiceAmount || 0));
        $('#detailDPPAmount').val(this.formatCurrency(invoice.dppAmount || invoice.DPPAmount || 0));
        $('#detailTaxAmount').val(this.formatCurrency(invoice.taxAmount || invoice.TaxAmount || 0));
        $('#detailTotalAmount').val(this.formatCurrency(invoice.totalAmount || invoice.TotalAmount || 0));
    }

    /**
     * Populate PO Information section
     */
    populatePOInformation() {
        const po = this.poData;
        const invoice = this.invoice;
        if (!po) return;

        const purchOrderID = invoice.purchOrderID || invoice.PurchOrderID || '';
        
        $('#detailPurchOrderID').val(purchOrderID);
        $('#detailPurchOrderName').val(po.purchOrderName || po.PurchOrderName || '');
        $('#detailPODate').val(this.formatDate(po.poDate || po.PODate));
        $('#detailPOAmount').val(this.formatCurrency(po.poAmount || po.POAmount || 0));
        
        // Site field
        const siteName = po.mstSiteName || po.MstSiteName || invoice.siteName || invoice.SiteName || '';
        const siteID = po.mstSiteID || po.MstSiteID || invoice.siteID || invoice.SiteID || '';
        const siteValue = siteName || siteID;
        if (siteValue) {
            $('#detailSiteFieldWrapper').show();
            $('#detailSite').val(siteValue);
        } else {
            $('#detailSiteFieldWrapper').hide();
        }
        
        $('#detailStatusPO').val(po.approvalStatus || po.ApprovalStatus || '');
        const formattedPurchaseType = this.formatPurchaseType(po.purchType || po.PurchType || '');
        const formattedPurchaseSubType = this.formatPurchaseSubType(po.purchSubType || po.PurchSubType || '');
        $('#detailPurchType').val(formattedPurchaseType);
        $('#detailPurchSubType').val(formattedPurchaseSubType);
        $('#detailTermOfPayment').val(po.topDescription || po.TOPDescription || '');
        $('#detailCompany').val(po.companyName || po.CompanyName || '');
        
        // Work Type, Product Type, Sonumb
        $('#detailWorkType').val(invoice.workType || invoice.WorkType || po.workType || po.WorkType || '');
        
        const productTypeValue = invoice.product || invoice.Product || po.productType || po.ProductType || '';
        if (productTypeValue) {
            $('#detailProductTypeFieldWrapper').show();
            $('#detailProductType').val(productTypeValue);
        } else {
            $('#detailProductTypeFieldWrapper').hide();
        }
        
        const sonumberValue = invoice.sonumber || invoice.SONumber || po.sonumber || po.SONumber || '';
        if (sonumberValue) {
            $('#detailSonumbFieldWrapper').show();
            $('#detailSONumber').val(sonumberValue);
        } else {
            $('#detailSonumbFieldWrapper').hide();
        }
    }

    /**
     * Populate Vendor Information section
     */
    populateVendorInformation() {
        const vendor = this.vendorData;
        if (!vendor) return;

        $('#detailVendorName').val(vendor.vendorName || vendor.VendorName || '');
        $('#detailContactPerson').val(vendor.cp || vendor.CP || '');
        $('#detailEmailAddress').val(vendor.cpEmail || vendor.CPEmail || vendor.emailCorresspondence || vendor.EmailCorresspondence || '');
        $('#detailPhoneNumber').val(vendor.cpPhone || vendor.CPPhone || vendor.telephone || vendor.Telephone || '');
        $('#detailNPWP').val(vendor.npwp || vendor.NPWP || '');
        $('#detailBank').val(vendor.bankName || vendor.BankName || '');
        $('#detailAccountName').val(vendor.recipientName || vendor.RecipientName || '');
        $('#detailAccountNumber').val(vendor.bankAccount || vendor.BankAccount || '');
        
        // Currency from PO items
        if (this.poItems && this.poItems.length > 0) {
            const firstItem = this.poItems[0];
            const currencyCode = firstItem.currencyCode || firstItem.CurrencyCode || '';
            $('#detailCurrency').val(currencyCode);
        }
        
        const isActive = vendor.isActive !== undefined ? vendor.isActive : (vendor.IsActive !== undefined ? vendor.IsActive : false);
        $('#detailVendorStatus').val(isActive ? 'Active' : 'Inactive');
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

        $('#detailTermOfPaymentSection').hide();
        $('#detailPeriodOfPaymentSection').show();

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
        if (!po) return;

        $('#detailTermOfPaymentSection').show();
        $('#detailPeriodOfPaymentSection').hide();
        
        this.loadTermOfPaymentGrid(po, invoice);
    }

    /**
     * Load Term of Payment grid
     */
    loadTermOfPaymentGrid(po, invoice) {
        const tbody = document.getElementById('tblDetailTermOfPaymentBody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        const topDescription = po.topDescription || po.TOPDescription || '';
        if (!topDescription) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">No Term of Payment available</td></tr>';
            return;
        }
        
        const termValues = topDescription.split('|').map(v => parseFloat(v.trim())).filter(v => !isNaN(v));
        if (termValues.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">Invalid Term of Payment format</td></tr>';
            return;
        }
        
        const poAmount = parseFloat(po.poAmount || po.POAmount || 0);
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                           'July', 'August', 'September', 'October', 'November', 'December'];
        const currentTermPosition = invoice.termPosition || invoice.TermPosition || 0;
        
        // Get submitted terms from invoiceDetailsByPO
        const submittedTerms = new Set();
        if (this.invoiceDetailsByPO && this.invoiceDetailsByPO.length > 0) {
            this.invoiceDetailsByPO.forEach(inv => {
                const termPos = inv.termPosition || inv.TermPosition || 0;
                if (termPos > 0) {
                    submittedTerms.add(termPos);
                }
            });
        }
        
        termValues.forEach((termValue, index) => {
            const termNumber = index + 1;
            const termMonth = monthNames[index] || `Term ${termNumber}`;
            const invoiceAmount = (poAmount * termValue) / 100;
            const hasInvoice = submittedTerms.has(termNumber);
            const isCurrentTerm = currentTermPosition === termNumber;
            
            let status = '';
            let statusClass = '';
            if (isCurrentTerm) {
                status = 'Current Invoice';
                statusClass = 'text-success fw-bold';
            } else if (hasInvoice) {
                status = 'Submitted';
                statusClass = 'text-success';
            } else {
                status = 'Not Submitted';
                statusClass = 'text-muted';
            }
            
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
     * Load Period of Payment grid
     */
    loadPeriodOfPaymentGrid(startPeriod, endPeriod, period, totalMonthPeriod, po, invoice) {
        const tbody = document.getElementById('tblDetailPeriodOfPaymentBody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        const startDate = new Date(startPeriod);
        const endDate = new Date(endPeriod);
        
        if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-danger">Invalid date format</td></tr>';
            return;
        }
        
        const poAmount = parseFloat(po.poAmount || po.POAmount || 0);
        const termValue = 100;
        const invoiceAmount = poAmount;
        const currentInvoiceNumber = invoice.invoiceNumber || invoice.InvoiceNumber || '';
        
        const amortizations = this.generateAmortization(startDate, endDate, period, totalMonthPeriod);
        if (amortizations.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No amortization periods generated</td></tr>';
            return;
        }
        
        // Get submitted periods from invoiceDetailsByPO
        const submittedPeriods = new Set();
        const currentInvoicePeriods = new Set();
        if (this.invoiceDetailsByPO && this.invoiceDetailsByPO.length > 0) {
            this.invoiceDetailsByPO.forEach(inv => {
                const termPos = inv.termPosition || inv.TermPosition || 0;
                const invNumber = inv.invoiceNumber || inv.InvoiceNumber || '';
                
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
            
            let status = '';
            let statusClass = '';
            if (isCurrentInvoicePeriod) {
                status = 'Current Invoice';
                statusClass = 'text-success fw-bold';
            } else if (hasInvoice) {
                status = 'Submitted';
                statusClass = 'text-success';
            } else {
                status = 'Not Submitted';
                statusClass = 'text-muted';
            }
            
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
     * Populate Invoice Items section
     */
    populateInvoiceItems() {
        const tbody = document.getElementById('tblItemListBody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        if (this.invoiceItems.length > 0) {
            this.invoiceItems.forEach(item => {
                const itemID = item.mstPROPurchaseItemInventoryItemID || item.MstPROPurchaseItemInventoryItemID || item.itemID || item.ItemID || '';
                const poItem = this.poItems.find(po => 
                    (po.mstPROPurchaseItemInventoryItemID || po.MstPROPurchaseItemInventoryItemID || po.mstPROInventoryItemID || po.MstPROInventoryItemID) === itemID
                );
                const unitName = poItem ? (poItem.itemUnit || poItem.ItemUnit || '') : (item.itemUnit || item.ItemUnit || item.unit || item.Unit || '');
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="text-center">${itemID}</td>
                    <td class="text-center">${item.itemName || item.ItemName || ''}</td>
                    <td class="text-center">${unitName}</td>
                    <td class="text-center">${item.itemDescription || item.ItemDescription || item.description || item.Description || ''}</td>
                    <td class="text-center">${item.quantityPO || item.QuantityPO || 0}</td>
                    <td class="text-center">${this.formatCurrency(item.pricePO || item.PricePO || 0)}</td>
                    <td class="text-center">${this.formatCurrency(item.lineAmountPO || item.LineAmountPO || 0)}</td>
                    <td class="text-center">${item.quantityInvoice || item.QuantityInvoice || 0}</td>
                    <td class="text-center">${this.formatCurrency(item.lineAmountInvoice || item.LineAmountInvoice || 0)}</td>
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

        const tbody = document.getElementById('tblDocumentChecklistBody');
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

