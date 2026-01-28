/**
 * InvoiceCreateSave Module
 * Handles invoice submission, saving, and loading invoice data
 */
class InvoiceCreateSave {
    constructor(managerInstance) {
        this.manager = managerInstance;
        this.utils = null;
        
        if (window.InvoiceCreateUtils) {
            this.utils = new InvoiceCreateUtils();
        }
    }

    /**
     * Submit Invoice (with validation)
     */
    async submitInvoice() {
        // Trigger form validation
        const form = document.getElementById('invoiceForm');
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            Swal.fire({
                icon: 'warning',
                title: 'Validation Error',
                text: 'Please fill in all required fields'
            });
            return;
        }

        // AC18: Validate Invoice Date - cannot be > current date
        const invoiceDate = $('#txtInvoiceDate').val();
        if (invoiceDate) {
            const today = new Date();
            const todayStr = today.getFullYear() + '-' + 
                            String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                            String(today.getDate()).padStart(2, '0');
            
            if (invoiceDate > todayStr) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Validation Error',
                    text: 'Invoice Date cannot be greater than current date'
                });
                $('#txtInvoiceDate').focus();
                return;
            }
        }

        // AC19: Validate Tax Number - must be 16 or 17 characters
        const taxNumber = $('#txtTaxNumber').val();
        if (taxNumber && taxNumber.length !== 16 && taxNumber.length !== 17) {
            Swal.fire({
                icon: 'warning',
                title: 'Validation Error',
                text: 'Tax Number must be exactly 16 or 17 characters'
            });
            $('#txtTaxNumber').focus();
            return;
        }

        // Validate that PO is selected
        const selectedPO = this.manager.poModule?.selectedPO || null;
        if (!selectedPO) {
            Swal.fire({
                icon: 'warning',
                title: 'Validation Error',
                text: 'Please select a Purchase Order first'
            });
            return;
        }

        // Validate that Term of Payment is selected
        const termPosition = $('#txtTermPosition').val();
        const termInvoice = $('#txtTermInvoice').val();
        if (!termPosition || !termInvoice) {
            Swal.fire({
                icon: 'warning',
                title: 'Validation Error',
                text: 'Please select a Term of Payment first'
            });
            return;
        }

        // Validate mandatory document checklist items
        if (this.manager && this.manager.documentModule) {
            const mandatoryChecklistItems = this.manager.documentModule.validateMandatoryDocuments();
            
            if (mandatoryChecklistItems.length > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Validation Error',
                    html: `Please fill in mandatory document checklist items:<br><ul>${mandatoryChecklistItems.map(item => `<li>${item}</li>`).join('')}</ul>`
                });
                return;
            }
        }

        // Show confirmation dialog (consistent with ReceiveList and ReleaseList)
        const result = await Swal.fire({
            title: 'Submit Invoice',
            text: 'Are you sure you want to submit this invoice?',
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
        });

        if (result.isConfirmed) {
            await this.saveInvoice();
        }
    }

    /**
     * Save Invoice
     */
    async saveInvoice() {
        try {
            if (!this.manager || !this.manager.apiModule) {
                throw new Error('API module not available');
            }
            
            const selectedPO = this.manager.poModule?.selectedPO || null;
            const poDetailItems = this.manager.poModule?.poDetailItems || [];
            const currentInvoiceId = this.manager.currentInvoiceId || null;
            
            // Get WorkTypeID from mapping
            const workTypeID = selectedPO ? (selectedPO.workTypeID || 0) : 0;
            
            if (!workTypeID) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Validation Error',
                    text: 'WorkType is required. Please ensure Purchase Order has valid PurchaseType and PurchaseSubType mapping.'
                });
                return;
            }

            // Get TermOfPaymentID from selected PO
            const termOfPaymentID = selectedPO ? (selectedPO.seqTOPID || selectedPO.SeqTOPID || 0) : 0;

            // Collect document checklist items
            let documentItems = [];
            if (this.manager && this.manager.documentModule) {
                documentItems = this.manager.documentModule.getDocumentItems();
            }

            // AC3: Check if this is a revision of rejected invoice (status 15 -> 13)
            let statusIDForRevision = null;
            if (currentInvoiceId) {
                try {
                    const currentInvoiceData = await this.manager.apiModule.getInvoiceById(currentInvoiceId);
                    const currentStatusID = currentInvoiceData.statusID || currentInvoiceData.StatusID || currentInvoiceData.mstApprovalStatusID || currentInvoiceData.MstApprovalStatusID || null;
                    
                    // AC3: If current status is 15 (Rejected Posting Invoice), update to 13
                    if (currentStatusID === 15) {
                        statusIDForRevision = 13;
                    }
                } catch (error) {
                    console.warn('Failed to check current invoice status:', error);
                }
            }

            // Check if this is Period Of Payment Invoice with multiple periods
            const selectedPeriods = this.manager.termModule?.selectedPeriods || [];
            
            if (selectedPeriods && selectedPeriods.length > 0) {
                // Period Of Payment Invoice: Create multiple invoices (one for each period)
                await this.savePeriodOfPaymentInvoices(selectedPO, poDetailItems, workTypeID, termOfPaymentID, documentItems, statusIDForRevision, selectedPeriods);
            } else {
                // Term Of Payment Invoice: Create single invoice
                await this.saveTermOfPaymentInvoice(selectedPO, poDetailItems, workTypeID, termOfPaymentID, documentItems, statusIDForRevision, currentInvoiceId);
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Failed to save invoice'
            });
        }
    }

    /**
     * Save Period Of Payment Invoices (multiple invoices)
     */
    async savePeriodOfPaymentInvoices(selectedPO, poDetailItems, workTypeID, termOfPaymentID, documentItems, statusIDForRevision, selectedPeriods) {
        const poAmount = parseFloat(selectedPO.poAmount || selectedPO.POAmount || 0);
        const termValue = 100; // Fixed 100% for period payment
        const taxCode = $('#ddlTax').val();
        const selectedTaxOption = $('#ddlTax option:selected');
        const taxValueRaw = parseFloat(selectedTaxOption.attr('data-tax-value') || 0);
        const taxValue = taxValueRaw;
        
        // Each period = full PO amount
        const amountPerPeriod = poAmount;
        
        // Calculate tax amounts for one period
        let dppAmountPerPeriod = 0;
        let taxAmountPerPeriod = 0;
        let totalAmountPerPeriod = amountPerPeriod;
        
        if (taxValue > 0) {
            dppAmountPerPeriod = amountPerPeriod / (1 + taxValue);
            taxAmountPerPeriod = amountPerPeriod - dppAmountPerPeriod;
            totalAmountPerPeriod = amountPerPeriod + taxAmountPerPeriod;
        } else {
            dppAmountPerPeriod = amountPerPeriod;
            taxAmountPerPeriod = 0;
            totalAmountPerPeriod = amountPerPeriod;
        }
        
        // Collect detail items for one period (Qty Invoice = Qty PO, not Qty PO * number of periods)
        const detailItemsPerPeriod = [];
        $('.qty-invoice').each(function() {
            const lineIndex = $(this).data('line');
            const item = poDetailItems[lineIndex];
            const qtyPO = parseFloat($(this).data('qty-po')) || 0;
            const price = item.unitPrice || item.UnitPrice || 0;
            const qtyInvoice = qtyPO; // For period payment: Qty Invoice = Qty PO (per period)
            const amount = qtyInvoice * price;

            detailItemsPerPeriod.push({
                lineNumber: lineIndex + 1,
                itemID: item.mstPROPurchaseItemInventoryItemID || item.MstPROPurchaseItemInventoryItemID || item.mstPROInventoryItemID || item.MstPROInventoryItemID || '',
                itemName: item.itemName || item.ItemName || '',
                unitID: 0,
                currencyCode: item.currencyCode || item.CurrencyCode || 'IDR',
                pricePO: price,
                quantityPO: qtyPO,
                lineAmountPO: qtyPO * price,
                quantityInvoice: qtyInvoice,
                lineAmountInvoice: amount,
                description: item.itemDescription || item.ItemDescription || ''
            });
        });
        
        // Create invoice for each selected period
        const invoiceNumber = $('#txtInvoiceNumber').val() || null;
        const createdInvoices = [];
        const errors = [];
        
        for (let i = 0; i < selectedPeriods.length; i++) {
            const periodNumber = selectedPeriods[i];
            
            try {
                const formData = {
                    requestNumber: null, // Will be auto-generated by backend
                    invoiceNumber: invoiceNumber, // Same Invoice Number for all periods
                    statusID: statusIDForRevision, // AC3: Set to 13 if revising rejected invoice (status 15)
                    bastNumber: $('#txtBASTNumber').val() || null,
                    purchOrderID: selectedPO ? (selectedPO.purchOrderID || selectedPO.PurchOrderID || '') : '',
                    companyID: selectedPO ? (selectedPO.companyID || selectedPO.CompanyID || null) : null,
                    workTypeID: workTypeID,
                    productTypeID: selectedPO ? (selectedPO.productTypeID || selectedPO.ProductTypeID || 0) : 0,
                    vendorID: selectedPO ? (selectedPO.vendorID || selectedPO.VendorID || selectedPO.mstVendorVendorID || selectedPO.MstVendorVendorID || null) : null,
                    trxFINInvoiceTOPID: termOfPaymentID,
                    termPosition: periodNumber, // Use period number as termPosition
                    termValue: termValue, // 100%
                    termOfPaymentID: termOfPaymentID,
                    invoiceAmount: amountPerPeriod,
                    taxCode: taxCode,
                    taxValue: taxValue,
                    taxAmount: taxAmountPerPeriod,
                    dppAmount: dppAmountPerPeriod,
                    totalAmount: totalAmountPerPeriod,
                    taxNumber: $('#txtTaxNumber').val(),
                    invoiceDate: $('#txtInvoiceDate').val(),
                    taxDate: $('#txtTaxDate').val(),
                    picName: $('#txtPICName').val(),
                    emailAddress: $('#txtPICEmail').val(),
                    creditNoteNumber: $('#txtCreditNoteNumber').val() || null,
                    creditNoteAmount: $('#txtCreditNoteAmount').val() ? parseFloat($('#txtCreditNoteAmount').val()) : null,
                    sonumber: $('#txtSONumber').val() || null,
                    siteID: $('#txtSiteID').val() || null,
                    isQR: false,
                    detailItems: detailItemsPerPeriod,
                    documentItems: documentItems
                };
                
                const response = await this.manager.apiModule.saveInvoice(formData);
                createdInvoices.push(response);
            } catch (error) {
                errors.push(`Period ${periodNumber}: ${error.message || 'Failed to create invoice'}`);
            }
        }
        
        if (errors.length > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Partial Success',
                html: `${createdInvoices.length} invoice(s) created successfully.<br>Errors:<br>${errors.join('<br>')}`
            });
        } else {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: `${selectedPeriods.length} invoice(s) created successfully for Period ${selectedPeriods.join(', ')}`,
                timer: 3000,
                showConfirmButton: false
            });
        }
        
        setTimeout(() => {
            window.location.href = '/Finance/EBilling/Invoice/List';
        }, 3000);
    }

    /**
     * Save Term Of Payment Invoice (single invoice)
     */
    async saveTermOfPaymentInvoice(selectedPO, poDetailItems, workTypeID, termOfPaymentID, documentItems, statusIDForRevision, currentInvoiceId) {
        const parseCurrency = this.utils ? this.utils.parseCurrency.bind(this.utils) : this.parseCurrency.bind(this);
        
        // Collect detail items
        const detailItems = [];
        $('.qty-invoice').each(function() {
            const lineIndex = $(this).data('line');
            const item = poDetailItems[lineIndex];
            const qty = parseFloat($(this).val()) || 0;
            const price = item.unitPrice || item.UnitPrice || 0;
            const qtyPO = item.itemQty || item.ItemQty || 0;
            const amount = qty * price;

            detailItems.push({
                lineNumber: lineIndex + 1,
                itemID: item.mstPROPurchaseItemInventoryItemID || item.MstPROPurchaseItemInventoryItemID || item.mstPROInventoryItemID || item.MstPROInventoryItemID || '',
                itemName: item.itemName || item.ItemName || '',
                unitID: 0,
                currencyCode: item.currencyCode || item.CurrencyCode || 'IDR',
                pricePO: price,
                quantityPO: qtyPO,
                lineAmountPO: qtyPO * price,
                quantityInvoice: qty,
                lineAmountInvoice: amount,
                description: item.itemDescription || item.ItemDescription || ''
            });
        });

        const invoiceAmount = parseCurrency($('#txtInvoiceAmount').val());
        const taxCode = $('#ddlTax').val();
        const selectedTaxOption = $('#ddlTax option:selected');
        const taxValueRaw = parseFloat(selectedTaxOption.attr('data-tax-value') || 0);
        const taxValue = taxValueRaw;
        const taxAmount = parseCurrency($('#txtTaxAmount').val());
        const dppAmount = parseCurrency($('#txtDPPAmount').val());
        const totalAmount = parseCurrency($('#txtTotalAmount').val());

        // Get Term Position and Term Value from selected term
        const termPosition = parseInt($('#txtTermPosition').val()) || 0;
        const termInvoiceText = $('#txtTermInvoice').val() || '';
        const termValue = parseFloat(termInvoiceText.replace('%', '')) || 0;

        const formData = {
            // AC26: Request Number will be auto-generated by backend on create
            requestNumber: currentInvoiceId ? ($('#txtRequestNumber').val() || null) : null,
            invoiceNumber: $('#txtInvoiceNumber').val() || null,
            bastNumber: $('#txtBASTNumber').val() || null,
            purchOrderID: selectedPO ? (selectedPO.purchOrderID || selectedPO.PurchOrderID || '') : '',
            statusID: statusIDForRevision, // AC3: Set to 13 if revising rejected invoice (status 15)
            companyID: selectedPO ? (selectedPO.companyID || selectedPO.CompanyID || null) : null,
            workTypeID: workTypeID,
            productTypeID: selectedPO ? (selectedPO.productTypeID || selectedPO.ProductTypeID || 0) : 0,
            vendorID: selectedPO ? (selectedPO.vendorID || selectedPO.VendorID || selectedPO.mstVendorVendorID || selectedPO.MstVendorVendorID || null) : null,
            trxFINInvoiceTOPID: termOfPaymentID,
            termPosition: termPosition,
            termValue: termValue,
            termOfPaymentID: termOfPaymentID,
            invoiceAmount: invoiceAmount,
            taxCode: taxCode,
            taxValue: taxValue,
            taxAmount: taxAmount,
            dppAmount: dppAmount,
            totalAmount: totalAmount,
            taxNumber: $('#txtTaxNumber').val(),
            invoiceDate: $('#txtInvoiceDate').val(),
            taxDate: $('#txtTaxDate').val(),
            picName: $('#txtPICName').val(),
            emailAddress: $('#txtPICEmail').val(),
            creditNoteNumber: $('#txtCreditNoteNumber').val() || null,
            creditNoteAmount: $('#txtCreditNoteAmount').val() ? parseFloat($('#txtCreditNoteAmount').val()) : null,
            sonumber: $('#txtSONumber').val() || null,
            siteID: $('#txtSiteID').val() || null,
            isQR: false,
            detailItems: detailItems,
            documentItems: documentItems
        };

        let response;
        if (currentInvoiceId) {
            response = await this.manager.apiModule.updateInvoice(currentInvoiceId, formData);
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: 'Invoice updated successfully',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            response = await this.manager.apiModule.saveInvoice(formData);
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: 'Invoice created successfully',
                timer: 2000,
                showConfirmButton: false
            });
        }

        setTimeout(() => {
            window.location.href = '/Finance/EBilling/Invoice/List';
        }, 2000);
    }

    /**
     * Load Invoice Data for editing
     */
    async loadInvoiceData(id) {
        try {
            if (!this.manager || !this.manager.apiModule) {
                throw new Error('API module not available');
            }
            
            const data = await this.manager.apiModule.getInvoiceById(id);
            
            // Check if this is a rejected invoice (status 15) - AC2: Enable editing for rejected invoices
            const statusID = data.statusID || data.StatusID || data.mstApprovalStatusID || data.MstApprovalStatusID || null;
            const isRejected = statusID === 15; // Status 15 = Rejected Posting Invoice

            const formatCurrency = this.utils ? this.utils.formatCurrency.bind(this.utils) : this.formatCurrency.bind(this);
            
            // Populate form with invoice data
            $('#txtInvoiceNumber').val(data.invoiceNumber || data.InvoiceNumber || '');
            $('#txtBASTNumber').val(data.bastNumber || data.BASTNumber || '');
            $('#txtRequestNumber').val(data.requestNumber || data.RequestNumber || '');
            $('#txtInvoiceAmount').val(formatCurrency(data.invoiceAmount || data.InvoiceAmount || 0));
            $('#ddlTax').val(data.taxCode || data.TaxCode || '');
            $('#txtDPPAmount').val(formatCurrency(data.dppAmount || data.DPPAmount || 0));
            $('#txtTaxAmount').val(formatCurrency(data.taxAmount || data.TaxAmount || 0));
            $('#txtTotalAmount').val(formatCurrency(data.totalAmount || data.TotalAmount || 0));
            $('#txtTaxNumber').val(data.taxNumber || data.TaxNumber || '');
            $('#txtInvoiceDate').val(data.invoiceDate ? new Date(data.invoiceDate).toISOString().split('T')[0] : '');
            $('#txtTaxDate').val(data.taxDate ? new Date(data.taxDate).toISOString().split('T')[0] : '');
            $('#txtPICName').val(data.picName || data.PICName || '');
            $('#txtPICEmail').val(data.emailAddress || data.EmailAddress || '');
            $('#txtCreditNoteNumber').val(data.creditNoteNumber || data.CreditNoteNumber || '');
            $('#txtCreditNoteAmount').val(data.creditNoteAmount || data.CreditNoteAmount || '');
            $('#txtSONumber').val(data.sonumber || data.SONumber || '');
            $('#txtSiteID').val(data.siteID || data.SiteID || '');
            
            // Set Term Position and Term Invoice for revised invoice
            const termValue = data.termValue || data.TermValue || 0;
            const termPosition = data.termPosition || data.TermPosition || 0;
            if (termPosition > 0) {
                $('#txtTermPosition').val(termPosition);
                $('#txtTermInvoice').val(`${termValue}%`);
                
                // Check if this is Period of Payment (multiple periods with same invoice number)
                const invoiceNumber = data.invoiceNumber || data.InvoiceNumber || '';
                const purchOrderID = data.purchOrderID || data.PurchOrderID || '';
                
                if (invoiceNumber && purchOrderID) {
                    // Run async check without blocking
                    (async () => {
                        try {
                            const invoiceDetails = await this.manager.apiModule.getInvoiceDetails(purchOrderID);
                            
                            if (invoiceDetails && invoiceDetails.length > 0) {
                                const matchingInvoices = invoiceDetails.filter(inv => {
                                    const invNumber = inv.invoiceNumber || inv.InvoiceNumber || '';
                                    return invNumber === invoiceNumber;
                                });
                                
                                if (matchingInvoices.length > 1) {
                                    // Multiple periods - Period of Payment
                                    const periodNumbers = [];
                                    matchingInvoices.forEach(inv => {
                                        const termPos = inv.termPosition || inv.TermPosition || 0;
                                        if (termPos > 0 && !periodNumbers.includes(termPos)) {
                                            periodNumbers.push(termPos);
                                        }
                                    });
                                    
                                    if (periodNumbers.length > 0) {
                                        periodNumbers.sort((a, b) => a - b);
                                        const termPositionText = periodNumbers.map(p => `Period ${p}`).join(', ');
                                        $('#txtTermPosition').val(termPositionText);
                                        $('#txtTermInvoice').val(`${termValue}%`);
                                    }
                                }
                            }
                        } catch (error) {
                            console.warn('Failed to load invoice details for term position:', error);
                        }
                    })();
                }
            }
            
            // AC2: Make InvoiceBasicInformationPartial fields editable for rejected invoices
            if (isRejected) {
                $('#txtInvoiceNumber').prop('readonly', false).removeClass('readonly-field');
                $('#txtInvoiceDate').prop('readonly', false).removeClass('readonly-field');
                $('#ddlTax').prop('disabled', false);
                $('#txtTaxNumber').prop('readonly', false).removeClass('readonly-field');
                $('#txtTaxDate').prop('readonly', false).removeClass('readonly-field');
                $('#txtPICName').prop('readonly', false).removeClass('readonly-field');
                $('#txtPICEmail').prop('readonly', false).removeClass('readonly-field');
            }

            // Load PO data if available
            if (data.purchOrderID || data.PurchOrderID) {
                if (this.manager && this.manager.poModule) {
                    await this.manager.poModule.selectPO(data.purchOrderID || data.PurchOrderID);
                    
                    // After PO is loaded, load document checklist
                    const workTypeID = data.workTypeID || data.WorkTypeID || 0;
                    const termValue = data.termValue || data.TermValue || 0;
                    if (workTypeID && termValue) {
                        const termOfPaymentID = this.manager.poModule.selectedPO ? (this.manager.poModule.selectedPO.seqTOPID || this.manager.poModule.selectedPO.SeqTOPID || 0) : 0;
                        if (this.manager && this.manager.documentModule) {
                            await this.manager.documentModule.loadDocumentChecklist(workTypeID, termOfPaymentID, termValue);
                            
                            // Load existing documents into checklist
                            try {
                                const existingDocuments = await this.manager.apiModule.getInvoiceDocuments(id);
                                
                                if (existingDocuments && existingDocuments.length > 0) {
                                    // Populate document checklist with existing documents
                                    $('#tblDocumentChecklistBody tr').each(function(index) {
                                        const row = $(this);
                                        const doc = existingDocuments[index];
                                        if (doc) {
                                            const documentNumberInput = row.find('input[id^="txtDocumentNumber_"]');
                                            const remarksInput = row.find('input[id^="txtRemarks_"]');
                                            
                                            if (documentNumberInput.length > 0) {
                                                documentNumberInput.val(doc.documentNumber || doc.DocumentNumber || '');
                                            }
                                            if (remarksInput.length > 0) {
                                                remarksInput.val(doc.remark || doc.Remark || '');
                                            }
                                            
                                            // AC2: Make document checklist fields editable for rejected invoices
                                            if (isRejected) {
                                                documentNumberInput.prop('readonly', false).removeClass('readonly-field');
                                                remarksInput.prop('readonly', false).removeClass('readonly-field');
                                                
                                                // Enable file input if it's a manual document
                                                const fileInput = row.find('input[type="file"]');
                                                if (fileInput.length > 0) {
                                                    fileInput.prop('disabled', false);
                                                }
                                            }
                                        }
                                    });
                                }
                            } catch (docError) {
                                console.warn('Failed to load existing documents:', docError);
                            }
                        }
                    }
                    
                    // For revised invoice (status 15), show Invoice Information, Document Checklist, and Submit Buttons sections
                    if (isRejected) {
                        $('#invoiceInformationSection').show();
                        $('#documentChecklistSection').show();
                        $('#submitButtonsSection').show();
                    }
                }
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Failed to load invoice data'
            });
        }
    }

    // Fallback methods if utils not available
    parseCurrency(value) {
        if (!value) return 0;
        const cleaned = value.toString().replace(/[^\d]/g, '');
        return parseFloat(cleaned) || 0;
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('id-ID', { 
            style: 'currency', 
            currency: 'IDR', 
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount || 0);
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.InvoiceCreateSave = InvoiceCreateSave;
}

