/**
 * CancelPeriodView Module
 * Handles view PO details, populate information, and submit cancel period/termin
 */
class CancelPeriodView {
    constructor(managerInstance) {
        this.manager = managerInstance;
        this.currentPO = null;
        this.allPurchaseTypes = null;
        this.allPurchaseSubTypes = null;
    }

    /**
     * Select PO and populate details
     */
    async selectPO(poNumber) {
        try {
            // Show loading state (same pattern as PRListView)
            this.showViewCancelPeriodLoading();
            
            // Hide list and filter sections
            this.hideListSection();

            // Get PO details
            if (!this.manager || !this.manager.apiModule) {
                throw new Error('API module not available');
            }

            const po = await this.manager.apiModule.getPODetails(poNumber);
            
            if (!po) {
                this.showError('PO not found');
                this.showListSection();
                return;
            }

            this.currentPO = po;
            
            // Load PurchaseTypes and PurchaseSubTypes for formatting (same as POListView)
            await this.loadPurchaseTypesForFormatting();
            
            // Populate PO Information (will format purchase types)
            await this.populatePOInformation(po);
            
            // Load vendor information
            const vendorID = po.mstVendorVendorID || po.MstVendorVendorID;
            if (vendorID) {
                await this.loadVendorInformation(vendorID);
            }
            
            // Load item list
            await this.loadItemList(poNumber);
            
            // Check if has Period of Payment
            const prNumber = po.prNumber || po.PRNumber || po.trxPROPurchaseRequestNumber || po.TrxPROPurchaseRequestNumber || '';
            let hasPeriodPayment = false;
            if (prNumber) {
                hasPeriodPayment = await this.checkAndLoadPeriodOfPayment(prNumber, po);
            }
            
            // Show/hide Term or Period of Payment sections
            if (hasPeriodPayment) {
                $('#cancelPeriodTermOfPaymentSection').hide();
                $('#cancelPeriodPeriodOfPaymentSection').show();
            } else {
                $('#cancelPeriodTermOfPaymentSection').show();
                $('#cancelPeriodPeriodOfPaymentSection').hide();
                await this.loadTermOfPaymentGrid(po);
            }
            
            // Hide loading and show data (same pattern as PRListView)
            this.showViewCancelPeriodData();
            
            // Show view section
            this.showViewCancelPeriodSection();
        } catch (error) {
            console.error('Error loading PO:', error);
            this.showError('Failed to load PO details: ' + (error.message || 'Unknown error'));
            this.showListSection();
        }
    }

    /**
     * Populate PO Information
     * Uses the same format as Invoice Create
     */
    async populatePOInformation(po) {
        $('#cancelPeriodPurchOrderID').val(po.purchOrderID || po.PurchOrderID || '');
        $('#cancelPeriodPurchOrderName').val(po.purchOrderName || po.PurchOrderName || '');
        // Use same date format as Invoice Create
        $('#cancelPeriodPODate').val(po.poDate ? new Date(po.poDate).toLocaleDateString('en-GB') : (po.PODate ? new Date(po.PODate).toLocaleDateString('en-GB') : ''));
        $('#cancelPeriodPOAmount').val(this.formatCurrency(po.poAmount || po.POAmount || 0));
        
        // Site, ProductType, and SONumber handling (same as Invoice Create)
        // Hide fields initially, will be shown if data exists
        $('#cancelPeriodSiteFieldWrapper').hide();
        $('#cancelPeriodProductTypeFieldWrapper').hide();
        $('#cancelPeriodSonumbFieldWrapper').hide();
        
        // Clear values
        $('#cancelPeriodSite').val('');
        $('#cancelPeriodProductType').val('');
        $('#cancelPeriodSONumber').val('');
        
        // Set Site if available (same as Invoice Create)
        const siteName = po.mstSiteName || po.MstSiteName || '';
        const siteID = po.mstSiteID || po.MstSiteID || '';
        if (siteName || siteID) {
            $('#cancelPeriodSiteFieldWrapper').show();
            $('#cancelPeriodSite').val(siteName || siteID);
        }
        
        $('#cancelPeriodStatusPO').val(po.approvalStatus || po.ApprovalStatus || '');
        // Format purchase type and sub type (convert ID to name)
        const formattedPurchType = this.formatPurchaseType(po.purchType || po.PurchType || '');
        // Load sub types for this purchase type if needed
        const purchTypeId = this.getPurchaseTypeId(po.purchType || po.PurchType || '');
        if (purchTypeId && this.manager && this.manager.apiModule) {
            await this.loadPurchaseSubTypesForType(purchTypeId);
        }
        const formattedPurchSubType = this.formatPurchaseSubType(po.purchSubType || po.PurchSubType || '', purchTypeId);
        $('#cancelPeriodPurchType').val(formattedPurchType);
        $('#cancelPeriodPurchSubType').val(formattedPurchSubType);
        $('#cancelPeriodTermOfPayment').val(po.topDescription || po.TOPDescription || '');
        $('#cancelPeriodCompany').val(po.companyName || po.CompanyName || '');
        // Work Type takes value from Purchase Type
        $('#cancelPeriodWorkType').val(formattedPurchType);
        
        // Set ProductType if available
        const productType = po.productType || po.ProductType || '';
        if (productType) {
            $('#cancelPeriodProductTypeFieldWrapper').show();
            $('#cancelPeriodProductType').val(productType);
        }
        
        // Set SONumber if available
        const soNumber = po.soNumber || po.SONumber || po.sonumber || '';
        if (soNumber) {
            $('#cancelPeriodSonumbFieldWrapper').show();
            $('#cancelPeriodSONumber').val(soNumber);
        }
    }

    /**
     * Load vendor information
     * Uses the same flow as Invoice Create with fallback to basic vendor info from PO
     */
    async loadVendorInformation(vendorID) {
        try {
            if (!this.manager || !this.manager.apiModule) {
                return;
            }

            const vendor = await this.manager.apiModule.getVendorDetails(vendorID);
            
            if (vendor) {
                this.populateVendorInformationFromDetail(vendor);
            }
        } catch (error) {
            console.error('Error loading vendor information:', error);
            // Fallback to basic vendor information from PO (same as Invoice Create)
            if (this.currentPO) {
                this.populateVendorInformation(this.currentPO);
            }
        }
    }

    /**
     * Populate vendor information from PO data (basic info)
     */
    populateVendorInformation(poData) {
        $('#cancelPeriodVendorName').val(poData.mstVendorVendorName || poData.MstVendorVendorName || '');
    }

    /**
     * Populate vendor information from vendor detail
     */
    populateVendorInformationFromDetail(vendorData) {
        $('#cancelPeriodVendorName').val(vendorData.vendorName || vendorData.VendorName || '');
        $('#cancelPeriodContactPerson').val(vendorData.cp || vendorData.CP || '');
        $('#cancelPeriodEmailAddress').val(vendorData.cpEmail || vendorData.CPEmail || vendorData.emailCorresspondence || vendorData.EmailCorresspondence || '');
        $('#cancelPeriodPhoneNumber').val(vendorData.cpPhone || vendorData.CPPhone || vendorData.telephone || vendorData.Telephone || '');
        $('#cancelPeriodNPWP').val(vendorData.npwp || vendorData.NPWP || '');
        $('#cancelPeriodBank').val(vendorData.bankName || vendorData.BankName || '');
        $('#cancelPeriodAccountName').val(vendorData.recipientName || vendorData.RecipientName || '');
        $('#cancelPeriodAccountNumber').val(vendorData.bankAccount || vendorData.BankAccount || '');
        
        // Vendor Status
        const isActive = vendorData.isActive !== undefined ? vendorData.isActive : (vendorData.IsActive !== undefined ? vendorData.IsActive : false);
        $('#cancelPeriodVendorStatus').val(isActive ? 'Active' : 'Inactive');
        
        // Currency will be populated from PO items if available
    }

    /**
     * Load item list
     * Uses the same format as Invoice Create
     */
    async loadItemList(poNumber) {
        try {
            if (!this.manager || !this.manager.apiModule) {
                return;
            }

            const items = await this.manager.apiModule.getPOItems(poNumber);
            
            const tbody = document.getElementById('cancelPeriodItemListBody');
            tbody.innerHTML = '';
            
            if (!items || items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted">No items found</td></tr>';
                return;
            }
            
            items.forEach((item, index) => {
                const itemQty = item.itemQty || item.ItemQty || 0;
                const unitPrice = item.unitPrice || item.UnitPrice || 0;
                const amount = item.amount || item.Amount || 0;
                
                // Qty Invoice and Amount Invoice (from invoice if exists, otherwise same as PO)
                const qtyInvoice = item.qtyInvoice || item.QtyInvoice || itemQty;
                const amountInvoice = item.amountInvoice || item.AmountInvoice || amount;
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="text-center">${this.escapeHtml(item.mstPROPurchaseItemInventoryItemID || item.MstPROPurchaseItemInventoryItemID || item.mstPROInventoryItemID || item.MstPROInventoryItemID || '')}</td>
                    <td class="text-center">${this.escapeHtml(item.itemName || item.ItemName || '')}</td>
                    <td class="text-center">${this.escapeHtml(item.itemUnit || item.ItemUnit || '')}</td>
                    <td class="text-center">${this.escapeHtml(item.itemDescription || item.ItemDescription || '')}</td>
                    <td class="text-center">${this.formatNumber(itemQty)}</td>
                    <td class="text-center">${this.formatCurrency(unitPrice)}</td>
                    <td class="text-center">${this.formatCurrency(amount)}</td>
                    <td class="text-center">${this.formatNumber(qtyInvoice)}</td>
                    <td class="text-center">${this.formatCurrency(amountInvoice)}</td>
                `;
                tbody.appendChild(row);
            });
            
            // Populate Currency from PO items (same as Invoice Create)
            this.populateCurrencyFromPOItems(items);
        } catch (error) {
            console.error('Error loading item list:', error);
            const tbody = document.getElementById('cancelPeriodItemListBody');
            tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-danger">Error loading items</td></tr>';
        }
    }

    /**
     * Populate Currency from PO items (same as Invoice Create)
     */
    populateCurrencyFromPOItems(poItems) {
        if (poItems && poItems.length > 0) {
            const firstItem = poItems[0];
            const currencyCode = firstItem.currencyCode || firstItem.CurrencyCode || '';
            if (currencyCode) {
                $('#cancelPeriodCurrency').val(currencyCode);
            }
        }
    }

    /**
     * Check and load period of payment
     * Uses the same logic as Invoice Create
     */
    async checkAndLoadPeriodOfPayment(prNumber, po) {
        try {
            if (!this.manager || !this.manager.apiModule) {
                return false;
            }

            // Get PurchaseRequestAdditional by PRNumber (same as Invoice Create)
            const prAdditional = await this.manager.apiModule.getPRAdditional(prNumber);
            
            if (!prAdditional) {
                return false;
            }
            
            // Check if StartPeriod or EndPeriod exists (same as Invoice Create)
            const startPeriod = prAdditional.startPeriod || prAdditional.StartPeriod;
            const endPeriod = prAdditional.endPeriod || prAdditional.EndPeriod;
            
            if (!startPeriod && !endPeriod) {
                return false;
            }
            
            // Get Period and BillingTypeID
            const period = prAdditional.period || prAdditional.Period || 0;
            const billingTypeID = prAdditional.billingTypeID || prAdditional.BillingTypeID;
            
            if (!billingTypeID || period <= 0) {
                return false;
            }
            
            // Get BillingType to get TotalMonthPeriod (same as Invoice Create)
            const billingTypesResponse = await apiCall('Procurement', '/Procurement/Master/BillingTypes?isActive=true', 'GET');
            const billingTypes = Array.isArray(billingTypesResponse) ? billingTypesResponse : (billingTypesResponse.data || billingTypesResponse.Data || []);
            const billingType = billingTypes.find(bt => (bt.id || bt.ID) === billingTypeID);
            
            if (!billingType) {
                console.warn('BillingType not found for ID:', billingTypeID);
                return false;
            }
            
            const totalMonthPeriod = billingType.totalMonthPeriod || billingType.TotalMonthPeriod || 1;
            
            // Generate amortization and load Period of Payment grid (same as Invoice Create)
            await this.loadPeriodOfPaymentGrid(startPeriod, endPeriod, period, totalMonthPeriod, po);
            
            return true;
        } catch (error) {
            console.error('Failed to check Period of Payment:', error);
            return false;
        }
    }

    /**
     * Load term of payment grid from database amortization table
     */
    async loadTermOfPaymentGrid(po) {
        try {
            const tbody = document.getElementById('cancelPeriodTermOfPaymentBody');
            tbody.innerHTML = '';
            
            if (!po) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No PO selected</td></tr>';
                return;
            }
            
            const poNumber = po.purchOrderID || po.PurchOrderID || '';
            if (!poNumber || !this.manager || !this.manager.apiModule) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No PO number available</td></tr>';
                return;
            }
            
            // Load amortizations from API
            const amortizationsData = await this.manager.apiModule.getAmortizations(poNumber);
            const termAmortizations = amortizationsData?.termOfPayment || [];
            
            if (termAmortizations.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No term of payment found</td></tr>';
                return;
            }
            
            // Get month names for Term of Payment display
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                               'July', 'August', 'September', 'October', 'November', 'December'];
            
            // Generate rows for each term from database
            termAmortizations.forEach((amort) => {
                const termNumber = amort.periodNumber;
                const termValue = amort.termValue || 0;
                const isCanceled = amort.isCanceled || false;
                const hasInvoice = amort.invoiceNumber != null && amort.invoiceNumber !== '';
                const isDisabled = hasInvoice || isCanceled; // Disable if already invoiced or canceled
                
                // Status: Priority: Canceled > Invoice Submitted > Available
                let status = 'Available';
                if (isCanceled) {
                    status = 'Canceled';
                } else if (hasInvoice) {
                    status = 'Invoice Submitted';
                }
                
                const termMonth = monthNames[termNumber - 1] || `Term ${termNumber}`;
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input cancel-term-checkbox cancel-checkbox" 
                               data-term-position="${termNumber}" 
                               ${isDisabled ? 'disabled' : ''}
                               ${isCanceled ? 'checked' : ''}
                               onchange="cancelPeriodManager.viewModule.handleTermCheckboxChange(${termNumber})">
                    </td>
                    <td class="text-center">${termMonth}</td>
                    <td class="text-center">${termValue}%</td>
                    <td class="text-center">
                        <span class="badge ${isCanceled ? 'bg-label-danger' : (hasInvoice ? 'bg-label-success' : 'bg-label-info')}">
                            ${this.escapeHtml(status)}
                        </span>
                    </td>
                    <td class="text-center">${this.formatCurrency(amort.invoiceAmount || 0)}</td>
                `;
                tbody.appendChild(row);
            });
        } catch (error) {
            console.error('Error loading term of payment grid:', error);
            const tbody = document.getElementById('cancelPeriodTermOfPaymentBody');
            tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-danger">Error loading Term of Payment</td></tr>';
        }
    }

    /**
     * Load period of payment grid from database amortization table
     */
    async loadPeriodOfPaymentGrid(startPeriod, endPeriod, period, totalMonthPeriod, po) {
        try {
            const tbody = document.getElementById('cancelPeriodPeriodOfPaymentBody');
            tbody.innerHTML = '';
            
            if (!po) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No PO selected</td></tr>';
                return;
            }
            
            const poNumber = po.purchOrderID || po.PurchOrderID || '';
            if (!poNumber || !this.manager || !this.manager.apiModule) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No PO number available</td></tr>';
                return;
            }
            
            // Load amortizations from API
            const amortizationsData = await this.manager.apiModule.getAmortizations(poNumber);
            const periodAmortizations = amortizationsData?.periodOfPayment || [];
            
            if (periodAmortizations.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No amortization periods found</td></tr>';
                return;
            }
            
            // Generate rows for each amortization period from database
            periodAmortizations.forEach((amort) => {
                const periodNumber = amort.periodNumber;
                const isCanceled = amort.isCanceled || false;
                const hasInvoice = amort.invoiceNumber != null && amort.invoiceNumber !== '';
                const isDisabled = hasInvoice || isCanceled; // Disable if already invoiced or canceled
                
                // Status: Priority: Canceled > Invoice Submitted > Available
                let status = 'Available';
                if (isCanceled) {
                    status = 'Canceled';
                } else if (hasInvoice) {
                    status = 'Invoice Submitted';
                }
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input cancel-period-checkbox cancel-checkbox" 
                               data-period-number="${periodNumber}" 
                               ${isDisabled ? 'disabled' : ''}
                               ${isCanceled ? 'checked' : ''}
                               onchange="cancelPeriodManager.viewModule.handlePeriodCheckboxChange(${periodNumber})">
                    </td>
                    <td class="text-center">Period ${periodNumber}</td>
                    <td class="text-center">${amort.termValue || 100}%</td>
                    <td class="text-center">${amort.startDate ? this.formatDate(amort.startDate) : '-'}</td>
                    <td class="text-center">${amort.endDate ? this.formatDate(amort.endDate) : '-'}</td>
                    <td class="text-center">
                        <span class="badge ${isCanceled ? 'bg-label-danger' : (hasInvoice ? 'bg-label-success' : 'bg-label-info')}">
                            ${this.escapeHtml(status)}
                        </span>
                    </td>
                    <td class="text-center">${this.formatCurrency(amort.invoiceAmount || 0)}</td>
                `;
                tbody.appendChild(row);
            });
        } catch (error) {
            console.error('Error loading period of payment grid:', error);
            const tbody = document.getElementById('cancelPeriodPeriodOfPaymentBody');
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-danger">Error loading periods</td></tr>';
        }
    }

    /**
     * Handle term checkbox change - sequential check from selected term
     * When checking a term, all subsequent terms are auto-checked
     * When unchecking a term (clicking the same checkbox that is already checked), all subsequent terms are auto-unchecked
     * When unchecking a term in the middle of a sequence, treat as changing the starting point
     */
    handleTermCheckboxChange(termPosition) {
        const checkbox = document.querySelector(`.cancel-term-checkbox[data-term-position="${termPosition}"]`);
        if (!checkbox || checkbox.disabled) return;
        
        const allCheckboxes = document.querySelectorAll('.cancel-term-checkbox:not(:disabled)');
        
        if (checkbox.checked) {
            // Auto-check all terms after this term (sequential)
            allCheckboxes.forEach(cb => {
                const cbTermPos = parseInt(cb.getAttribute('data-term-position'));
                if (cbTermPos > termPosition) {
                    cb.checked = true;
                }
            });
        } else {
            // User is unchecking this term
            // Find the last term position
            let lastTermPosition = termPosition;
            allCheckboxes.forEach(cb => {
                const cbTermPos = parseInt(cb.getAttribute('data-term-position'));
                if (cbTermPos > lastTermPosition) {
                    lastTermPosition = cbTermPos;
                }
            });
            
            // Special case: If this is the last term, treat as changing starting point
            // (user wants to check only the last term, so uncheck smaller terms)
            if (termPosition === lastTermPosition) {
                // User is clicking the last term - treat as changing starting point
                checkbox.checked = true;
                
                // Uncheck all terms smaller than the selected term
                allCheckboxes.forEach(cb => {
                    const cbTermPos = parseInt(cb.getAttribute('data-term-position'));
                    if (cbTermPos < termPosition) {
                        cb.checked = false;
                    } else if (cbTermPos === termPosition) {
                        // Ensure the last term is checked
                        cb.checked = true;
                    }
                });
                return;
            }
            
            // Check if there are checked terms after this one
            let hasCheckedAfter = false;
            allCheckboxes.forEach(cb => {
                const cbTermPos = parseInt(cb.getAttribute('data-term-position'));
                if (cbTermPos > termPosition && cb.checked) {
                    hasCheckedAfter = true;
                }
            });
            
            // Check if this is the starting point by finding the smallest checked term
            // Since this checkbox is now unchecked, we need to check all checkboxes including
            // treating this one as if it was still checked (since it was checked before the click)
            let smallestCheckedTerm = null;
            allCheckboxes.forEach(cb => {
                const cbTermPos = parseInt(cb.getAttribute('data-term-position'));
                // Treat current checkbox as checked for this check (since it was checked before)
                if (cb === checkbox || cb.checked) {
                    if (smallestCheckedTerm === null || cbTermPos < smallestCheckedTerm) {
                        smallestCheckedTerm = cbTermPos;
                    }
                }
            });
            
            // If this is the starting point (smallest checked term) and user clicks it again, uncheck all
            // Otherwise, treat as changing the starting point
            if (hasCheckedAfter && smallestCheckedTerm !== null && termPosition === smallestCheckedTerm) {
                // User is clicking the starting point checkbox again - uncheck all from this term to the end
                allCheckboxes.forEach(cb => {
                    const cbTermPos = parseInt(cb.getAttribute('data-term-position'));
                    if (cbTermPos >= termPosition) {
                        cb.checked = false;
                    }
                });
            } else if (hasCheckedAfter) {
                // There are checked terms after this one, but this is not the starting point
                // User is changing the starting point - check this term again and uncheck smaller terms
                checkbox.checked = true;
                
                // Uncheck all terms smaller than the selected term
                allCheckboxes.forEach(cb => {
                    const cbTermPos = parseInt(cb.getAttribute('data-term-position'));
                    if (cbTermPos < termPosition) {
                        cb.checked = false;
                    } else if (cbTermPos >= termPosition) {
                        // Ensure terms from selected to end are checked
                        cb.checked = true;
                    }
                });
            } else {
                // No checked terms after - user is unchecking from the starting point
                // Uncheck all terms from selected term to the end (sequential)
                allCheckboxes.forEach(cb => {
                    const cbTermPos = parseInt(cb.getAttribute('data-term-position'));
                    if (cbTermPos >= termPosition) {
                        cb.checked = false;
                    }
                });
            }
        }
    }

    /**
     * Handle period checkbox change - sequential check from selected period
     * When checking a period, all periods from selected to the last available period are auto-checked
     * If checking a period that is smaller than already checked periods, uncheck the smaller ones
     * When unchecking a period (clicking the same checkbox that is already checked), all subsequent periods are auto-unchecked
     */
    handlePeriodCheckboxChange(periodNumber) {
        const checkbox = document.querySelector(`.cancel-period-checkbox[data-period-number="${periodNumber}"]`);
        if (!checkbox || checkbox.disabled) return;
        
        const allCheckboxes = document.querySelectorAll('.cancel-period-checkbox');
        
        // Find the last available (not disabled) period number
        let lastAvailablePeriod = periodNumber;
        allCheckboxes.forEach(cb => {
            if (!cb.disabled) {
                const cbPeriodNum = parseInt(cb.getAttribute('data-period-number'));
                if (cbPeriodNum > lastAvailablePeriod) {
                    lastAvailablePeriod = cbPeriodNum;
                }
            }
        });
        if (checkbox.checked) {
            // User is checking this period
            // Check all periods from selected period to the last available period (sequential)
            allCheckboxes.forEach(cb => {
                if (cb.disabled) return; // Skip disabled checkboxes
                const cbPeriodNum = parseInt(cb.getAttribute('data-period-number'));
                if (cbPeriodNum >= periodNumber && cbPeriodNum <= lastAvailablePeriod) {
                    cb.checked = true;
                    // Update status badge to show Canceled
                    const row = cb.closest('tr');
                    if (row) {
                        const statusCell = row.querySelector('td:nth-child(6)');
                        if (statusCell) {
                            statusCell.innerHTML = '<span class="badge bg-label-danger">Canceled</span>';
                        }
                    }
                } else if (cbPeriodNum < periodNumber) {
                    // Uncheck periods that are smaller than the selected period
                    // This ensures that if we check period 4, period 3 gets unchecked
                    cb.checked = false;
                    // Update status badge to show Available
                    const row = cb.closest('tr');
                    if (row) {
                        const statusCell = row.querySelector('td:nth-child(6)');
                        if (statusCell) {
                            statusCell.innerHTML = '<span class="badge bg-label-info">Available</span>';
                        }
                    }
                }
            });
        } else {
            // User is unchecking this period
            // Special case: If this is the last available period, treat as changing starting point
            // (user wants to check only the last period, so uncheck smaller periods)
            if (periodNumber === lastAvailablePeriod) {
                // User is clicking the last period - treat as changing starting point
                checkbox.checked = true;
                
                // Uncheck all periods smaller than the selected period
                allCheckboxes.forEach(cb => {
                    if (cb.disabled) return;
                    const cbPeriodNum = parseInt(cb.getAttribute('data-period-number'));
                    if (cbPeriodNum < periodNumber) {
                        cb.checked = false;
                        // Update status badge to show Available
                        const row = cb.closest('tr');
                        if (row) {
                            const statusCell = row.querySelector('td:nth-child(6)');
                            if (statusCell) {
                                statusCell.innerHTML = '<span class="badge bg-label-info">Available</span>';
                            }
                        }
                    } else if (cbPeriodNum === periodNumber) {
                        // Ensure the last period is checked
                        cb.checked = true;
                        // Update status badge to show Canceled
                        const row = cb.closest('tr');
                        if (row) {
                            const statusCell = row.querySelector('td:nth-child(6)');
                            if (statusCell) {
                                statusCell.innerHTML = '<span class="badge bg-label-danger">Canceled</span>';
                            }
                        }
                    }
                });
                return;
            }
            
            // Check if there are checked periods after this one
            let hasCheckedAfter = false;
            allCheckboxes.forEach(cb => {
                if (cb.disabled) return;
                const cbPeriodNum = parseInt(cb.getAttribute('data-period-number'));
                if (cbPeriodNum > periodNumber && cb.checked) {
                    hasCheckedAfter = true;
                }
            });
            
            // Check if this is the starting point by finding the smallest checked period
            // Since this checkbox is now unchecked, we need to check all checkboxes including
            // treating this one as if it was still checked (since it was checked before the click)
            let smallestCheckedPeriod = null;
            allCheckboxes.forEach(cb => {
                if (cb.disabled) return;
                const cbPeriodNum = parseInt(cb.getAttribute('data-period-number'));
                // Treat current checkbox as checked for this check (since it was checked before)
                if (cb === checkbox || cb.checked) {
                    if (smallestCheckedPeriod === null || cbPeriodNum < smallestCheckedPeriod) {
                        smallestCheckedPeriod = cbPeriodNum;
                    }
                }
            });
            
            // If this is the starting point (smallest checked period) and user clicks it again, uncheck all
            // Otherwise, treat as changing the starting point
            if (hasCheckedAfter && smallestCheckedPeriod !== null && periodNumber === smallestCheckedPeriod) {
                // User is clicking the starting point checkbox again - uncheck all from this period to the end
                allCheckboxes.forEach(cb => {
                    if (cb.disabled) return; // Skip disabled checkboxes
                    const cbPeriodNum = parseInt(cb.getAttribute('data-period-number'));
                    if (cbPeriodNum >= periodNumber) {
                        cb.checked = false;
                        // Update status badge to show Available
                        const row = cb.closest('tr');
                        if (row) {
                            const statusCell = row.querySelector('td:nth-child(6)');
                            if (statusCell) {
                                statusCell.innerHTML = '<span class="badge bg-label-info">Available</span>';
                            }
                        }
                    }
                });
            } else if (hasCheckedAfter) {
                // There are checked periods after this one, but this is not the starting point
                // User is changing the starting point - check this period again and uncheck smaller periods
                checkbox.checked = true;
                
                // Uncheck all periods smaller than the selected period
                allCheckboxes.forEach(cb => {
                    if (cb.disabled) return;
                    const cbPeriodNum = parseInt(cb.getAttribute('data-period-number'));
                    if (cbPeriodNum < periodNumber) {
                        cb.checked = false;
                        // Update status badge to show Available
                        const row = cb.closest('tr');
                        if (row) {
                            const statusCell = row.querySelector('td:nth-child(6)');
                            if (statusCell) {
                                statusCell.innerHTML = '<span class="badge bg-label-info">Available</span>';
                            }
                        }
                    } else if (cbPeriodNum >= periodNumber && cbPeriodNum <= lastAvailablePeriod) {
                        // Ensure periods from selected to end are checked
                        cb.checked = true;
                        // Update status badge to show Canceled
                        const row = cb.closest('tr');
                        if (row) {
                            const statusCell = row.querySelector('td:nth-child(6)');
                            if (statusCell) {
                                statusCell.innerHTML = '<span class="badge bg-label-danger">Canceled</span>';
                            }
                        }
                    }
                });
            } else {
                // No checked periods after - user is unchecking from the starting point
                // Uncheck all periods from selected period to the end (sequential)
                allCheckboxes.forEach(cb => {
                    if (cb.disabled) return; // Skip disabled checkboxes
                    const cbPeriodNum = parseInt(cb.getAttribute('data-period-number'));
                    if (cbPeriodNum >= periodNumber) {
                        cb.checked = false;
                        // Update status badge to show Available
                        const row = cb.closest('tr');
                        if (row) {
                            const statusCell = row.querySelector('td:nth-child(6)');
                            if (statusCell) {
                                statusCell.innerHTML = '<span class="badge bg-label-info">Available</span>';
                            }
                        }
                    }
                });
            }
        }
    }

    /**
     * Submit cancel period/termin
     */
    async submitCancelPeriod() {
        if (!this.currentPO) {
            Swal.fire({
                icon: 'warning',
                title: 'Warning',
                text: 'Please select a PO first'
            });
            return;
        }
        
        // Get checked terms/periods
        const checkedTerms = [];
        const checkedPeriods = [];
        
        document.querySelectorAll('.cancel-term-checkbox:checked:not(:disabled)').forEach(cb => {
            checkedTerms.push(parseInt(cb.getAttribute('data-term-position')));
        });
        
        document.querySelectorAll('.cancel-period-checkbox:checked:not(:disabled)').forEach(cb => {
            checkedPeriods.push(parseInt(cb.getAttribute('data-period-number')));
        });
        
        if (checkedTerms.length === 0 && checkedPeriods.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Warning',
                text: 'Please select at least one term or period to cancel'
            });
            return;
        }
        
        // Confirm action
        const result = await Swal.fire({
            title: 'Confirm Cancel Period/Termin',
            text: `Are you sure you want to cancel ${checkedTerms.length > 0 ? checkedTerms.length + ' term(s)' : ''}${checkedTerms.length > 0 && checkedPeriods.length > 0 ? ' and ' : ''}${checkedPeriods.length > 0 ? checkedPeriods.length + ' period(s)' : ''}?`,
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
        
        if (!result.isConfirmed) {
            return;
        }
        
        try {
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait while canceling period/termin',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            const poNumber = this.currentPO.purchOrderID || this.currentPO.PurchOrderID || '';
            const requestData = {
                purchOrderID: poNumber,
                termPositions: checkedTerms,
                periodNumbers: checkedPeriods
            };
            
            // Call API to cancel period/termin
            if (!this.manager || !this.manager.apiModule) {
                throw new Error('API module not available');
            }

            const response = await this.manager.apiModule.submitCancelPeriod(requestData);
            
            if (response && response.success) {
                // Clear cache for this PO to ensure fresh data is loaded
                const poNumber = this.currentPO.purchOrderID || this.currentPO.PurchOrderID || '';
                if (poNumber && this.manager && this.manager.apiModule && this.manager.apiModule.clearPOCache) {
                    this.manager.apiModule.clearPOCache(poNumber);
                }
                
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Period/Termin canceled successfully',
                    timer: 2000,
                    showConfirmButton: false
                });
                
                // Reload PO data to refresh amortization status
                if (poNumber) {
                    await this.selectPO(poNumber);
                } else {
                    this.backToList();
                }
            } else {
                throw new Error(response?.message || 'Failed to cancel period/termin');
            }
        } catch (error) {
            console.error('Error canceling period:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Failed to cancel period/termin'
            });
        }
    }

    /**
     * Back to list
     */
    backToList() {
        // Hide view section and show list section (same pattern as ConfirmPO/ApprovalPO)
        this.showListSection();
        this.currentPO = null;
        
        // Clear all fields
        $('#cancelPeriodPurchOrderID').val('');
        $('#cancelPeriodPurchOrderName').val('');
        $('#cancelPeriodPODate').val('');
        $('#cancelPeriodPOAmount').val('');
        $('#cancelPeriodSite').val('');
        $('#cancelPeriodStatusPO').val('');
        $('#cancelPeriodPurchType').val('');
        $('#cancelPeriodPurchSubType').val('');
        $('#cancelPeriodTermOfPayment').val('');
        $('#cancelPeriodCompany').val('');
        $('#cancelPeriodWorkType').val('');
        $('#cancelPeriodProductType').val('');
        $('#cancelPeriodSONumber').val('');
        
        // Clear vendor fields
        $('#cancelPeriodVendorName').val('');
        $('#cancelPeriodContactPerson').val('');
        $('#cancelPeriodEmailAddress').val('');
        $('#cancelPeriodPhoneNumber').val('');
        $('#cancelPeriodNPWP').val('');
        $('#cancelPeriodBank').val('');
        $('#cancelPeriodAccountName').val('');
        $('#cancelPeriodAccountNumber').val('');
        $('#cancelPeriodCurrency').val('');
        $('#cancelPeriodVendorStatus').val('');
        
        // Clear tables
        document.getElementById('cancelPeriodItemListBody').innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted">No PO selected</td></tr>';
        document.getElementById('cancelPeriodTermOfPaymentBody').innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No PO selected</td></tr>';
        document.getElementById('cancelPeriodPeriodOfPaymentBody').innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No PO selected</td></tr>';
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Helper functions
    formatDate(date) {
        if (!date) return '';
        const d = new Date(date);
        if (isNaN(d.getTime())) return '';
        return d.toLocaleDateString('en-GB');
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
    }

    formatNumber(num) {
        return new Intl.NumberFormat('id-ID').format(num);
    }

    /**
     * Load PurchaseTypes and PurchaseSubTypes for formatting
     * Uses shared cache to prevent duplicate API calls
     */
    async loadPurchaseTypesForFormatting() {
        // First, check if manager's tableModule already has purchase types loaded (to share cache)
        if (!this.allPurchaseTypes && this.manager && this.manager.tableModule && this.manager.tableModule.allPurchaseTypes) {
            this.allPurchaseTypes = this.manager.tableModule.allPurchaseTypes;
        }
        
        // Load PurchaseTypes if not already loaded
        // Use shared cache to prevent duplicate API calls
        if (!this.allPurchaseTypes && this.manager && this.manager.apiModule) {
            try {
                // Use API module which uses shared cache (ProcurementSharedCache)
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
        
        // Initialize PurchaseSubTypes Map for efficiency
        if (!this.allPurchaseSubTypes) {
            this.allPurchaseSubTypes = new Map(); // key: typeId, value: subTypes array
        }
    }

    /**
     * Get Purchase Type ID from purchase type value (could be ID or formatted string)
     */
    getPurchaseTypeId(purchType) {
        if (!purchType && purchType !== 0) {
            return null;
        }
        
        const purchTypeStr = typeof purchType === 'number' ? purchType.toString() : String(purchType || '');
        
        if (purchTypeStr.trim() === '') {
            return null;
    }

        // If it's a number (ID), return it
        const typeId = parseInt(purchTypeStr.trim(), 10);
        if (!isNaN(typeId) && typeId > 0) {
            return typeId;
        }
        
        // If it's a formatted string, try to find ID from allPurchaseTypes
        if (this.allPurchaseTypes && Array.isArray(this.allPurchaseTypes)) {
            const type = this.allPurchaseTypes.find(t => {
                const prType = t.PurchaseRequestType || t.purchaseRequestType || '';
                const category = t.Category || t.category || '';
                const formattedDisplay = category && prType !== category 
                    ? `${prType} ${category}` 
                    : prType;
                return prType === purchTypeStr || formattedDisplay === purchTypeStr;
            });
            
            if (type) {
                return parseInt(type.ID || type.id || '0', 10);
            }
        }
        
        return null;
    }

    /**
     * Load Purchase Sub Types for a specific Type ID
     */
    async loadPurchaseSubTypesForType(typeId) {
        if (!typeId || !this.manager || !this.manager.apiModule) {
            return;
        }
        
        // Check if already cached
        if (this.allPurchaseSubTypes instanceof Map && this.allPurchaseSubTypes.has(typeId)) {
            return; // Already loaded
        }
        
        try {
            // Use API module which uses shared cache
            const subTypes = await this.manager.apiModule.getPurchaseSubTypes(typeId);
            
            // Cache in Map
            if (!this.allPurchaseSubTypes) {
                this.allPurchaseSubTypes = new Map();
            }
            if (this.allPurchaseSubTypes instanceof Map) {
                this.allPurchaseSubTypes.set(typeId, Array.isArray(subTypes) ? subTypes : []);
            }
        } catch (error) {
            console.error('Error loading purchase sub types:', error);
            // Initialize empty array for this typeId to prevent repeated API calls
            if (!this.allPurchaseSubTypes) {
                this.allPurchaseSubTypes = new Map();
            }
            if (this.allPurchaseSubTypes instanceof Map) {
                this.allPurchaseSubTypes.set(typeId, []);
            }
        }
    }

    /**
     * Format PurchaseType with Category
     * Returns formatted string for display
     */
    formatPurchaseType(purchType, escapeHtml = false) {
        // Handle null/undefined
        if (!purchType && purchType !== 0) {
            return '-';
        }
        
        // Convert to string if it's a number
        const purchTypeStr = typeof purchType === 'number' ? purchType.toString() : String(purchType || '');
        
        if (purchTypeStr.trim() === '') {
            return '-';
        }
        
        // Check if it's already formatted (contains space and not just a number)
        if (purchTypeStr.includes(' ') && isNaN(parseInt(purchTypeStr.trim()))) {
            return purchTypeStr;
        }
        
        // If it's a number (ID), try to format it
        const typeId = parseInt(purchTypeStr.trim(), 10);
        if (!isNaN(typeId) && typeId > 0) {
            // Check if allPurchaseTypes is loaded
            if (!this.allPurchaseTypes || !Array.isArray(this.allPurchaseTypes) || this.allPurchaseTypes.length === 0) {
                // Purchase types not loaded yet, return ID for now
                console.warn('Purchase types not loaded yet for typeId:', typeId);
                return purchTypeStr;
            }
            
            const type = this.allPurchaseTypes.find(t => {
                const tId = parseInt(t.ID || t.id || '0', 10);
                return tId === typeId;
            });
            
            if (type) {
                const prType = type.PurchaseRequestType || type.purchaseRequestType || '';
                const category = type.Category || type.category || '';
                if (!category) {
                    return prType;
                }
                // Jika PurchaseRequestType dan Category sama, cukup tampilkan salah satu
                if (prType === category) {
                    return prType;
                }
                return `${prType} ${category}`;
            } else {
                // Type ID not found in allPurchaseTypes
                console.warn('Purchase type not found for typeId:', typeId);
            }
        }
        
        // If not found or invalid, return as is
        return purchTypeStr;
    }

    /**
     * Format PurchaseSubType
     * Returns formatted string for display
     */
    formatPurchaseSubType(purchSubType, typeId = null, escapeHtml = false) {
        // Handle null/undefined
        if (!purchSubType && purchSubType !== 0) {
            return '-';
        }
        
        // Convert to string if it's a number
        const purchSubTypeStr = typeof purchSubType === 'number' ? purchSubType.toString() : String(purchSubType || '');
        
        if (purchSubTypeStr.trim() === '') {
            return '-';
        }
        
        // Check if it's already formatted (not just a number)
        if (isNaN(parseInt(purchSubTypeStr.trim()))) {
            return purchSubTypeStr;
        }
        
        // If it's a number (ID), try to format it
        const subTypeId = parseInt(purchSubTypeStr.trim(), 10);
        if (!isNaN(subTypeId) && subTypeId > 0) {
            // If we have typeId, try to find in cached sub types
            if (typeId && this.allPurchaseSubTypes instanceof Map && this.allPurchaseSubTypes.has(typeId)) {
                const subTypes = this.allPurchaseSubTypes.get(typeId);
                if (Array.isArray(subTypes)) {
                    const foundSubType = subTypes.find(st => 
                        parseInt(st.ID || st.id || '0', 10) === subTypeId
                    );
                    if (foundSubType) {
                        return foundSubType.PurchaseRequestSubType || foundSubType.purchaseRequestSubType || '';
                    }
                }
            }
            
            // Try to find in allPurchaseSubTypes (if loaded as array or in any cached type)
            if (this.allPurchaseSubTypes instanceof Map) {
                for (const subTypes of this.allPurchaseSubTypes.values()) {
                    if (Array.isArray(subTypes)) {
                        const foundSubType = subTypes.find(st => 
                            parseInt(st.ID || st.id || '0', 10) === subTypeId
                        );
                        if (foundSubType) {
                            return foundSubType.PurchaseRequestSubType || foundSubType.purchaseRequestSubType || '';
                        }
                    }
                }
            } else if (Array.isArray(this.allPurchaseSubTypes)) {
                const foundSubType = this.allPurchaseSubTypes.find(st => 
                    parseInt(st.ID || st.id || '0', 10) === subTypeId
                );
                if (foundSubType) {
                    return foundSubType.PurchaseRequestSubType || foundSubType.purchaseRequestSubType || '';
                }
            }
        }
        
        // If not found or invalid, return as is
        return purchSubTypeStr;
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Show/hide sections (same pattern as PRListView)
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
        const viewSection = document.getElementById('viewCancelPeriodSection');
        
        if (filterSection) filterSection.style.display = 'block';
        if (listSection) listSection.style.display = 'block';
        if (viewSection) viewSection.style.display = 'none';
    }

    showViewCancelPeriodSection() {
        const viewSection = document.getElementById('viewCancelPeriodSection');
        if (viewSection) {
            viewSection.style.display = 'block';
        }
    }

    showViewCancelPeriodLoading() {
        const viewSection = document.getElementById('viewCancelPeriodSection');
        const viewLoading = document.getElementById('viewCancelPeriodLoading');
        const viewData = document.getElementById('viewCancelPeriodData');
        
        if (viewLoading) {
            viewLoading.style.display = 'block';
        }
        if (viewData) {
            viewData.style.display = 'none';
        }
        
        if (viewSection) {
            viewSection.style.display = 'block';
        }
    }

    showViewCancelPeriodData() {
        const viewLoading = document.getElementById('viewCancelPeriodLoading');
        const viewData = document.getElementById('viewCancelPeriodData');
        
        if (viewLoading) {
            viewLoading.style.display = 'none';
        }
        if (viewData) {
            viewData.style.display = 'block';
        }
    }

    showError(message) {
        if (this.manager && this.manager.showError) {
            this.manager.showError(message);
        } else {
            console.error(message);
            alert(message);
        }
    }

    /**
     * Generate amortization periods (same as Invoice Create)
     */
    generateAmortization(startDate, endDate, period, totalMonthPeriod) {
        const amortizations = [];
        let currentStart = new Date(startDate);
        const finalEnd = new Date(endDate);
        
        if (isNaN(currentStart.getTime()) || isNaN(finalEnd.getTime())) {
            return amortizations;
        }
        
        for (let i = 0; i < period; i++) {
            let currentEnd;
            
            if (i === 0) {
                // First period: starts from StartPeriod date
                // End date is the last day of the month that is totalMonthPeriod months after StartPeriod's month
                currentEnd = new Date(currentStart);
                currentEnd.setMonth(currentEnd.getMonth() + totalMonthPeriod);
                currentEnd.setDate(0); // Set to last day of previous month (which is the end of the period)
            } else {
                // Subsequent periods: start from first day of the month after previous period end
                currentStart = new Date(amortizations[i - 1].endDate);
                currentStart.setDate(currentStart.getDate() + 1); // Next day after previous period end
                currentStart.setDate(1); // Set to first day of the month
                
                // End date is the last day of the month that is totalMonthPeriod months after current start
                currentEnd = new Date(currentStart);
                currentEnd.setMonth(currentEnd.getMonth() + totalMonthPeriod);
                currentEnd.setDate(0); // Set to last day of previous month
            }
            
            // If this is the last period, use the final end date
            if (i === period - 1) {
                currentEnd = new Date(finalEnd);
            } else {
                // Ensure we don't exceed the final end date
                if (currentEnd > finalEnd) {
                    currentEnd = new Date(finalEnd);
                }
            }
            
            amortizations.push({
                periodNumber: i + 1,
                startDate: new Date(currentStart),
                endDate: new Date(currentEnd)
            });
            
            // If we've reached or exceeded the final end date, stop
            if (currentEnd >= finalEnd) {
                break;
            }
        }
        
        return amortizations;
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.CancelPeriodView = CancelPeriodView;
}

