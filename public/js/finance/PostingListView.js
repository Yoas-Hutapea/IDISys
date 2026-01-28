/**
 * PostingListView Module
 * Handles view posting functionality - reuses InvoiceDetailManager when available
 */
class PostingListView {
    constructor(managerInstance) {
        this.manager = managerInstance;
        this.currentInvoiceId = null;
        
        // Try to use InvoiceDetailManager if available
        if (typeof InvoiceDetailManager !== 'undefined') {
            this.detailManager = new InvoiceDetailManager();
        }
    }

    /**
     * View posting - show invoice detail for posting
     */
    viewPosting(id) {
        // Hide list section and show view posting section
        const listSection = document.getElementById('listSection');
        const viewPostingSection = document.getElementById('viewPostingSection');
        
        if (listSection) listSection.style.display = 'none';
        if (viewPostingSection) viewPostingSection.style.display = 'block';
        
        // Load invoice data for posting
        this.loadPostingData(id);
    }

    /**
     * Back to list
     */
    backToList() {
        // Hide view posting section and show list section
        const listSection = document.getElementById('listSection');
        const viewPostingSection = document.getElementById('viewPostingSection');
        
        if (viewPostingSection) viewPostingSection.style.display = 'none';
        if (listSection) listSection.style.display = 'block';
        
        // Clear posting data
        const viewPostingLoading = document.getElementById('viewPostingLoading');
        const viewPostingData = document.getElementById('viewPostingData');
        
        if (viewPostingLoading) viewPostingLoading.style.display = 'block';
        if (viewPostingData) viewPostingData.style.display = 'none';
        
        // Clear current invoice ID
        this.currentInvoiceId = null;
        if (window.currentPostingInvoiceId) {
            delete window.currentPostingInvoiceId;
        }
    }

    /**
     * Load posting data - uses InvoiceDetailManager if available
     */
    async loadPostingData(id) {
        try {
            // Show loading
            const viewPostingLoading = document.getElementById('viewPostingLoading');
            const viewPostingData = document.getElementById('viewPostingData');
            
            if (viewPostingLoading) viewPostingLoading.style.display = 'block';
            if (viewPostingData) viewPostingData.style.display = 'none';
            
            // Store invoice ID globally for posting action
            this.currentInvoiceId = id;
            window.currentPostingInvoiceId = id;
            
            // Use InvoiceDetailManager if available (reuses existing logic)
            if (this.detailManager) {
                await this.detailManager.init(id, {
                    loadingElementId: 'viewPostingLoading',
                    dataElementId: 'viewPostingData'
                });
            } else {
                // Fallback: Load data manually
                await this.loadPostingDataManual(id);
            }
        } catch (error) {
            console.error('Error loading posting data:', error);
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'Failed to load invoice data'
                });
            }
            
            this.backToList();
        }
    }

    /**
     * Manual loading (fallback if InvoiceDetailManager not available)
     */
    async loadPostingDataManual(id) {
        if (!this.manager || !this.manager.apiModule) {
            throw new Error('API module not available');
        }
        
        // Load invoice data
        const invoice = await this.manager.apiModule.getInvoiceById(id);
        
        if (!invoice) {
            throw new Error('Invoice not found');
        }
        
        // Populate invoice information
        await this.populatePostingInvoiceInformation(invoice);
        
        // Load PO detail if available
        const purchOrderID = invoice.purchOrderID || invoice.PurchOrderID;
        if (purchOrderID) {
            await this.loadPostingPODetail(purchOrderID, invoice);
        }
        
        // Load Invoice Detail Items
        await this.loadPostingInvoiceDetailItems(id);
        
        // Load Document Checklist
        await this.loadPostingDocumentChecklist(invoice);
        
        // Hide loading and show data
        const viewPostingLoading = document.getElementById('viewPostingLoading');
        const viewPostingData = document.getElementById('viewPostingData');
        
        if (viewPostingLoading) viewPostingLoading.style.display = 'none';
        if (viewPostingData) viewPostingData.style.display = 'block';
    }

    /**
     * Populate posting invoice information
     */
    async populatePostingInvoiceInformation(invoice) {
        if (!this.manager || !this.manager.utilsModule) return;
        
        const utils = this.manager.utilsModule;
        const invoiceNumber = invoice.invoiceNumber || invoice.InvoiceNumber || '';
        const purchOrderID = invoice.purchOrderID || invoice.PurchOrderID || '';
        
        const singleTermPos = invoice.termPosition || invoice.TermPosition || 0;
        let termPositionText = singleTermPos > 0 ? `Period ${singleTermPos}` : '';
        
        // Check for multiple periods
        if (invoiceNumber && purchOrderID && this.manager.apiModule) {
            try {
                const invoiceDetails = await this.manager.apiModule.getInvoiceDetails(purchOrderID);
                
                if (invoiceDetails && invoiceDetails.length > 0) {
                    const matchingInvoices = invoiceDetails.filter(inv => {
                        const invNumber = inv.invoiceNumber || inv.InvoiceNumber || '';
                        return invNumber && invNumber.trim() === invoiceNumber.trim();
                    });
                    
                    if (matchingInvoices.length > 1) {
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
            } catch (error) {
                console.warn('Failed to load invoice details for term position:', error);
            }
        }
        
        // Populate fields
        $('#detailTermPosition').val(termPositionText);
        $('#detailInvoiceNumber').val(invoiceNumber);
        $('#detailInvoiceDate').val(utils.formatDate(invoice.invoiceDate || invoice.InvoiceDate));
        
        const taxCodeId = invoice.taxCode || invoice.TaxCode || '';
        const taxName = utils.getTaxName(taxCodeId);
        $('#detailTaxCode').val(taxName);
        $('#detailTaxNumber').val(invoice.taxNumber || invoice.TaxNumber || '');
        $('#detailTaxDate').val(utils.formatDate(invoice.taxDate || invoice.TaxDate));
        $('#detailPICName').val(invoice.picName || invoice.PICName || '');
        $('#detailPICEmail').val(invoice.emailAddress || invoice.EmailAddress || '');
        
        $('#detailTermInvoice').val(invoice.termValue ? `${invoice.termValue}%` : '');
        $('#detailInvoiceAmount').val(utils.formatCurrency(invoice.invoiceAmount || invoice.InvoiceAmount || 0));
        $('#detailDPPAmount').val(utils.formatCurrency(invoice.dppAmount || invoice.DPPAmount || 0));
        $('#detailTaxAmount').val(utils.formatCurrency(invoice.taxAmount || invoice.TaxAmount || 0));
        $('#detailTotalAmount').val(utils.formatCurrency(invoice.totalAmount || invoice.TotalAmount || 0));
    }

    /**
     * Load posting PO detail
     */
    async loadPostingPODetail(purchOrderID, invoice) {
        if (!this.manager || !this.manager.apiModule || !this.manager.utilsModule) return;
        
        try {
            const po = await this.manager.apiModule.getPODetails(purchOrderID);
            const utils = this.manager.utilsModule;
            
            if (po) {
                $('#detailPurchOrderID').val(purchOrderID);
                $('#detailPurchOrderName').val(po.purchOrderName || po.PurchOrderName || '');
                $('#detailPODate').val(utils.formatDate(po.poDate || po.PODate));
                $('#detailPOAmount').val(utils.formatCurrency(po.poAmount || po.POAmount || 0));
                
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
                const formattedPurchaseType = utils.formatPurchaseType(po.purchType || po.PurchType || '');
                const formattedPurchaseSubType = utils.formatPurchaseSubType(po.purchSubType || po.PurchSubType || '');
                $('#detailPurchType').val(formattedPurchaseType);
                $('#detailPurchSubType').val(formattedPurchaseSubType);
                $('#detailTermOfPayment').val(po.topDescription || po.TOPDescription || '');
                $('#detailCompany').val(po.companyName || po.CompanyName || '');
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

                // Load vendor information
                const vendorID = po.mstVendorVendorID || po.MstVendorVendorID;
                if (vendorID) {
                    await this.loadPostingVendorInformation(vendorID, purchOrderID);
                }
                
                // Check for Period of Payment
                const prNumber = po.prNumber || po.PRNumber || po.trxPROPurchaseRequestNumber || po.TrxPROPurchaseRequestNumber || '';
                let hasPeriodPayment = false;
                if (prNumber) {
                    hasPeriodPayment = await this.checkAndLoadPostingPeriodOfPayment(prNumber, po, invoice);
                }
                
                // If not Period of Payment, load Term of Payment grid
                if (!hasPeriodPayment) {
                    await this.loadPostingTermOfPaymentGrid(po, invoice);
                }
            }
        } catch (error) {
            console.error('Failed to load PO detail:', error);
        }
    }

    /**
     * Load posting vendor information
     */
    async loadPostingVendorInformation(vendorID, purchOrderID) {
        if (!this.manager || !this.manager.apiModule || !this.manager.utilsModule) return;
        
        try {
            const vendors = await this.manager.apiModule.getVendors();
            const vendor = vendors.find(v => (v.vendorID || v.VendorID) === vendorID);
            
            if (vendor && (vendor.id || vendor.ID)) {
                const vendorId = vendor.id || vendor.ID;
                const vendorData = await this.manager.apiModule.getVendorById(vendorId);
                const utils = this.manager.utilsModule;
                
                if (vendorData) {
                    $('#detailVendorName').val(vendorData.vendorName || vendorData.VendorName || '');
                    $('#detailContactPerson').val(vendorData.cp || vendorData.CP || '');
                    $('#detailEmailAddress').val(vendorData.cpEmail || vendorData.CPEmail || vendorData.emailCorresspondence || vendorData.EmailCorresspondence || '');
                    $('#detailPhoneNumber').val(vendorData.cpPhone || vendorData.CPPhone || vendorData.telephone || vendorData.Telephone || '');
                    $('#detailNPWP').val(vendorData.npwp || vendorData.NPWP || '');
                    $('#detailBank').val(vendorData.bankName || vendorData.BankName || '');
                    $('#detailAccountName').val(vendorData.recipientName || vendorData.RecipientName || '');
                    $('#detailAccountNumber').val(vendorData.bankAccount || vendorData.BankAccount || '');
                    
                    if (purchOrderID) {
                        try {
                            const poItems = await this.manager.apiModule.getPOItems(purchOrderID);
                            if (poItems && poItems.length > 0) {
                                const firstItem = poItems[0];
                                const currencyCode = firstItem.currencyCode || firstItem.CurrencyCode || '';
                                $('#detailCurrency').val(currencyCode);
                            }
                        } catch (error) {
                            // Ignore error
                        }
                    }
                    
                    const isActive = vendorData.isActive !== undefined ? vendorData.isActive : (vendorData.IsActive !== undefined ? vendorData.IsActive : false);
                    $('#detailVendorStatus').val(isActive ? 'Active' : 'Inactive');
                }
            }
        } catch (error) {
            console.error('Failed to load vendor information:', error);
        }
    }

    /**
     * Load posting invoice detail items
     */
    async loadPostingInvoiceDetailItems(invoiceID) {
        if (!this.manager || !this.manager.apiModule || !this.manager.utilsModule) return;
        
        try {
            const items = await this.manager.apiModule.getInvoiceItems(invoiceID);
            const utils = this.manager.utilsModule;
            
            const tbody = document.getElementById('tblItemListBody');
            if (!tbody) return;
            tbody.innerHTML = '';
            
            if (items.length > 0) {
                let poItems = [];
                const invoice = await this.manager.apiModule.getInvoiceById(invoiceID);
                const purchOrderID = invoice.purchOrderID || invoice.PurchOrderID;
                
                if (purchOrderID) {
                    try {
                        poItems = await this.manager.apiModule.getPOItems(purchOrderID);
                    } catch (error) {
                        // Ignore error
                    }
                }
                
                items.forEach(item => {
                    const itemID = item.mstPROPurchaseItemInventoryItemID || item.MstPROPurchaseItemInventoryItemID || item.itemID || item.ItemID || '';
                    const poItem = poItems.find(po => 
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
                        <td class="text-center">${utils.formatCurrency(item.pricePO || item.PricePO || 0)}</td>
                        <td class="text-center">${utils.formatCurrency(item.lineAmountPO || item.LineAmountPO || 0)}</td>
                        <td class="text-center">${item.quantityInvoice || item.QuantityInvoice || 0}</td>
                        <td class="text-center">${utils.formatCurrency(item.lineAmountInvoice || item.LineAmountInvoice || 0)}</td>
                    `;
                    tbody.appendChild(row);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted">No items available</td></tr>';
            }
        } catch (error) {
            console.error('Failed to load invoice detail items:', error);
            const tbody = document.getElementById('tblItemListBody');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-danger">Failed to load items</td></tr>';
            }
        }
    }

    /**
     * Load posting document checklist
     */
    async loadPostingDocumentChecklist(invoice) {
        if (!this.manager || !this.manager.apiModule || !this.manager.utilsModule) return;
        
        try {
            const workTypeID = invoice.workTypeID || invoice.WorkTypeID || 1;
            const termValue = invoice.termValue || invoice.TermValue || 0;
            const invoiceId = invoice.invoiceHeaderID || invoice.InvoiceHeaderID || invoice.id || invoice.ID;
            
            const allMappings = await this.manager.apiModule.getInvoiceWorkTypeTOPDocumentChecklists();
            const searchWorkTypeID = parseInt(workTypeID) || 0;
            const searchTermNumber = parseFloat(termValue) || 0;
            
            const filteredMappings = allMappings.filter(item => {
                const itemWorkTypeID = parseInt(item.mstFINInvoiceWorkTypeID || item.mstFinInvoiceWorkTypeId || 0);
                const itemTermNumber = parseFloat(item.termNumber || item.TermNumber || 0);
                
                return itemWorkTypeID === searchWorkTypeID && 
                       itemTermNumber === searchTermNumber;
            });

            const allMasterChecklists = await this.manager.apiModule.getInvoiceDocumentChecklists();
            
            let actualDocuments = [];
            if (invoiceId) {
                try {
                    actualDocuments = await this.manager.apiModule.getInvoiceDocuments(invoiceId);
                } catch (docError) {
                    console.warn('Failed to load actual documents:', docError);
                    actualDocuments = [];
                }
            }
            
            const tbody = document.getElementById('tblDocumentChecklistBody');
            if (!tbody) return;
            tbody.innerHTML = '';
            
            if (filteredMappings.length > 0) {
                filteredMappings.forEach(mapping => {
                    const documentChecklistID = mapping.mstFINInvoiceDocumentChecklistID || mapping.mstFinInvoiceDocumentChecklistId || 0;
                    const masterChecklist = allMasterChecklists.find(mc => 
                        parseInt(mc.id || mc.ID || 0) === parseInt(documentChecklistID)
                    );
                    
                    if (masterChecklist) {
                        const documentName = masterChecklist.documentName || masterChecklist.DocumentName || '';
                        const isMandatory = mapping.isMandatory !== undefined ? mapping.isMandatory : (mapping.IsMandatory !== undefined ? mapping.IsMandatory : false);
                        const isAuto = mapping.isAuto !== undefined ? mapping.isAuto : (mapping.IsAuto !== undefined ? mapping.IsAuto : false);
                        const mandatoryName = isMandatory ? 'Yes' : 'No';
                        const autoName = isAuto ? 'Auto' : 'Manual';
                        
                        const checklistIndex = filteredMappings.findIndex(m => 
                            (m.mstFINInvoiceDocumentChecklistID || m.mstFinInvoiceDocumentChecklistId || 0) === documentChecklistID
                        );
                        const matchedDoc = actualDocuments[checklistIndex] || null;
                        
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
        } catch (error) {
            console.error('Failed to load document checklist:', error);
            const tbody = document.getElementById('tblDocumentChecklistBody');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-danger">Failed to load document checklist</td></tr>';
            }
        }
    }

    /**
     * Check and load Period of Payment
     */
    async checkAndLoadPostingPeriodOfPayment(prNumber, po, invoice) {
        if (!this.manager || !this.manager.apiModule || !this.manager.utilsModule) return false;
        
        try {
            const additionalData = await this.manager.apiModule.getPRAdditional(prNumber);
            
            if (!additionalData) {
                return false;
            }
            
            const startPeriod = additionalData.startPeriod || additionalData.StartPeriod;
            const endPeriod = additionalData.endPeriod || additionalData.EndPeriod;
            
            if (!startPeriod && !endPeriod) {
                return false;
            }
            
            const period = additionalData.period || additionalData.Period || 0;
            const billingTypeID = additionalData.billingTypeID || additionalData.BillingTypeID;
            
            if (!billingTypeID || period <= 0) {
                return false;
            }
            
            const billingTypes = await this.manager.apiModule.getBillingTypes();
            const billingType = billingTypes.find(bt => (bt.id || bt.ID) === billingTypeID);
            
            if (!billingType) {
                console.warn('BillingType not found for ID:', billingTypeID);
                return false;
            }
            
            const totalMonthPeriod = billingType.totalMonthPeriod || billingType.TotalMonthPeriod || 1;
            
            await this.loadPostingPeriodOfPaymentGrid(startPeriod, endPeriod, period, totalMonthPeriod, po, invoice);
            
            return true;
        } catch (error) {
            console.error('Failed to check Period of Payment:', error);
            return false;
        }
    }

    /**
     * Load posting Period of Payment grid
     */
    async loadPostingPeriodOfPaymentGrid(startPeriod, endPeriod, period, totalMonthPeriod, po, invoice) {
        if (!this.manager || !this.manager.apiModule || !this.manager.utilsModule) return;
        
        try {
            const tbody = document.getElementById('tblDetailPeriodOfPaymentBody');
            if (!tbody) return;
            tbody.innerHTML = '';
            
            if (!po) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No PO data available</td></tr>';
                return;
            }
            
            const startDate = new Date(startPeriod);
            const endDate = new Date(endPeriod);
            
            if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-danger">Invalid date format</td></tr>';
                return;
            }
            
            const utils = this.manager.utilsModule;
            const poAmount = parseFloat(po.poAmount || po.POAmount || 0);
            const termValue = 100;
            const invoiceAmount = poAmount;
            
            const amortizations = this.generatePostingAmortization(startDate, endDate, period, totalMonthPeriod);
            
            if (amortizations.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No amortization periods generated</td></tr>';
                return;
            }
            
            const currentInvoiceNumber = invoice.invoiceNumber || invoice.InvoiceNumber || '';
            const poNumber = po.purchOrderID || po.PurchOrderID || '';
            const submittedPeriods = new Set();
            const currentInvoicePeriods = new Set();
            
            if (poNumber) {
                try {
                    const existingInvoices = await this.manager.apiModule.getInvoiceDetails(poNumber);
                    
                    if (existingInvoices && existingInvoices.length > 0) {
                        existingInvoices.forEach(inv => {
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
                } catch (error) {
                    console.warn('Failed to check existing invoices for submitted periods:', error);
                }
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
                    <td class="text-center">${utils.formatDate(amort.startDate)}</td>
                    <td class="text-center">${utils.formatDate(amort.endDate)}</td>
                    <td class="text-center ${statusClass}">${status}</td>
                    <td class="text-center">${utils.formatCurrency(invoiceAmount)}</td>
                `;
                tbody.appendChild(row);
            });
            
            // Show Period of Payment section, hide Term of Payment section
            $('#detailTermOfPaymentSection').hide();
            $('#detailPeriodOfPaymentSection').show();
        } catch (error) {
            console.error('Error loading Period of Payment:', error);
            const tbody = document.getElementById('tblDetailPeriodOfPaymentBody');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-danger">Error loading Period of Payment</td></tr>';
            }
        }
    }

    /**
     * Generate posting amortization
     */
    generatePostingAmortization(startDate, endDate, period, totalMonthPeriod) {
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
     * Load posting Term of Payment grid (fallback if not Period of Payment)
     */
    async loadPostingTermOfPaymentGrid(po, invoice) {
        if (!this.manager || !this.manager.apiModule || !this.manager.utilsModule) return;
        
        try {
            const tbody = document.getElementById('tblDetailTermOfPaymentBody');
            if (!tbody) return;
            tbody.innerHTML = '';
            
            if (!po) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">No PO data available</td></tr>';
                return;
            }
            
            const topDescription = po.topDescription || po.TOPDescription || '';
            if (!topDescription) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">No Term of Payment available</td></tr>';
                return;
            }
            
            const utils = this.manager.utilsModule;
            const termValues = topDescription.split('|').map(v => parseFloat(v.trim())).filter(v => !isNaN(v));
            
            if (termValues.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">Invalid Term of Payment format</td></tr>';
                return;
            }
            
            const poAmount = parseFloat(po.poAmount || po.POAmount || 0);
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                               'July', 'August', 'September', 'October', 'November', 'December'];
            const currentTermPosition = invoice.termPosition || invoice.TermPosition || 0;
            const poNumber = po.purchOrderID || po.PurchOrderID || '';
            const submittedTerms = new Set();
            
            if (poNumber) {
                try {
                    const existingInvoices = await this.manager.apiModule.getInvoiceDetails(poNumber);
                    
                    if (existingInvoices && existingInvoices.length > 0) {
                        existingInvoices.forEach(inv => {
                            const termPos = inv.termPosition || inv.TermPosition || 0;
                            if (termPos > 0) {
                                submittedTerms.add(termPos);
                            }
                        });
                    }
                } catch (error) {
                    console.warn('Failed to check existing invoices for submitted terms:', error);
                }
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
                    <td class="text-center">${utils.formatCurrency(invoiceAmount)}</td>
                `;
                tbody.appendChild(row);
            });
            
            // Show Term of Payment section, hide Period of Payment section
            $('#detailTermOfPaymentSection').show();
            $('#detailPeriodOfPaymentSection').hide();
        } catch (error) {
            console.error('Error loading Term of Payment:', error);
            const tbody = document.getElementById('tblDetailTermOfPaymentBody');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-danger">Error loading Term of Payment</td></tr>';
            }
        }
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.PostingListView = PostingListView;
}

