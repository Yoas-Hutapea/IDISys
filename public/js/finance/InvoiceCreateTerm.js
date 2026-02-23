/**
 * InvoiceCreateTerm Module
 * Handles Term of Payment and Period of Payment management
 */
class InvoiceCreateTerm {
    constructor(managerInstance) {
        this.manager = managerInstance;
        this.utils = null;
        this.selectedTermNumber = null;
        this.selectedTermValue = null;
        this.selectedPeriods = [];

        if (window.InvoiceCreateUtils) {
            this.utils = new InvoiceCreateUtils();
        }
    }

    collectSubmittedPositions(invoices) {
        const submitted = new Set();
        if (!Array.isArray(invoices)) {
            return submitted;
        }

        invoices.forEach(inv => {
            const termPos = inv.termPosition ?? inv.TermPosition ?? null;
            if (termPos == null) {
                return;
            }

            if (typeof termPos === 'number' && termPos > 0) {
                submitted.add(termPos);
                return;
            }

            if (typeof termPos === 'string') {
                const matches = termPos.match(/\d+/g);
                if (matches) {
                    matches.forEach(match => {
                        const value = parseInt(match, 10);
                        if (value > 0) {
                            submitted.add(value);
                        }
                    });
                }
            }
        });

        return submitted;
    }

    /**
     * Load Term of Payment Grid (Optimized - uses pre-fetched existingInvoices and optional amortizationsData from database)
     * @param {Array|null} existingInvoices - pre-fetched invoice details for eligibility
     * @param {Object|null} amortizationsData - optional { termOfPayment: [] } from getAmortizations (avoids duplicate API call)
     */
    async loadTermOfPaymentGridOptimized(existingInvoices = null, amortizationsData = null) {
        try {
            const tbody = document.getElementById('tblTermOfPaymentBody');
            if (!tbody) return;

            tbody.innerHTML = '';

            const selectedPO = this.manager.poModule?.selectedPO || null;
            if (!selectedPO) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No PO selected</td></tr>';
                return;
            }

            const poNumber = selectedPO.purchOrderID || selectedPO.PurchOrderID || '';
            let termAmortizations = [];

            const dataPayload = amortizationsData && typeof amortizationsData === 'object' ? (amortizationsData.data || amortizationsData) : amortizationsData;
            if (dataPayload && Array.isArray(dataPayload.termOfPayment) && dataPayload.termOfPayment.length > 0) {
                termAmortizations = dataPayload.termOfPayment;
            } else if (poNumber && this.manager && this.manager.apiModule) {
                try {
                    const fetched = await this.manager.apiModule.getAmortizations(poNumber);
                    const payload = fetched && typeof fetched === 'object' ? (fetched.data || fetched) : fetched;
                    termAmortizations = (payload && Array.isArray(payload.termOfPayment)) ? payload.termOfPayment : [];
                } catch (error) {
                    console.warn('Failed to load amortizations from API, falling back to TOPDescription:', error);
                }
            }

            if (termAmortizations.length === 0) {
                const topDescription = selectedPO.topDescription || selectedPO.TOPDescription || '';
                if (topDescription) {
                    const termValues = topDescription.split('|').map(v => parseFloat(v.trim())).filter(v => !isNaN(v));
                    const poAmount = parseFloat(selectedPO.poAmount || selectedPO.POAmount || 0);
                    termAmortizations = termValues.map((termValue, index) => ({
                        periodNumber: index + 1,
                        termValue: termValue,
                        invoiceAmount: (poAmount * termValue) / 100,
                        status: 'Available',
                        isCanceled: false,
                        invoiceNumber: null
                    }));
                }
            }

            if (termAmortizations.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No Term of Payment available</td></tr>';
                return;
            }

            if (termAmortizations.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No term of payment found</td></tr>';
                return;
            }

            // Total amount = Item List total (so 80% term shows 80% of this value)
            const parseCurrency = this.utils ? this.utils.parseCurrency.bind(this.utils) : this.parseCurrency.bind(this);
            let totalAmount = 0;
            const itemListTotalEl = document.getElementById('txtItemListTotalAmount');
            if (itemListTotalEl && itemListTotalEl.value) {
                totalAmount = parseCurrency(itemListTotalEl.value);
            }
            if (totalAmount <= 0) {
                const poDetailItems = this.manager.poModule?.poDetailItems || [];
                if (poDetailItems.length > 0) {
                    totalAmount = poDetailItems.reduce((sum, item) => sum + (parseFloat(item.amount || item.Amount || 0) || 0), 0);
                }
            }
            if (totalAmount <= 0) {
                totalAmount = parseFloat(selectedPO.poAmount || selectedPO.POAmount || 0) || 0;
            }

            // Check existing invoices to determine eligible term number
            let submittedTerms = new Set();
            let eligibleTermNumber = 1;
            
            // Use pre-fetched existingInvoices if available, otherwise fetch
            let invoices = existingInvoices;
            if (!invoices && poNumber && this.manager && this.manager.apiModule) {
                try {
                    invoices = await this.manager.apiModule.getInvoiceDetails(poNumber);
                } catch (error) {
                    console.warn('Failed to check existing invoices for eligible term:', error);
                    invoices = [];
                }
            }
            
            if (invoices && invoices.length > 0) {
                submittedTerms = this.collectSubmittedPositions(invoices);
                if (submittedTerms.size > 0) {
                    const maxTermPosition = Math.max(...submittedTerms);
                    eligibleTermNumber = maxTermPosition + 1;
                }
            }

            const formatCurrency = this.utils ? this.utils.formatCurrency.bind(this.utils) : this.formatCurrency.bind(this);

            // When some terms are already paid: remaining to pay = totalAmount - sum(paid); unpaid terms get (remaining * termValue / sumUnpaidTermValues)
            let sumSubmitted = 0;
            let sumUnpaidTermValues = 0;
            termAmortizations.forEach((amort) => {
                const termNumber = parseInt(amort.periodNumber, 10) || 0;
                const termValue = parseFloat(amort.termValue || 0) || 0;
                const isCanceled = amort.isCanceled || false;
                const hasInvoice = (amort.invoiceNumber != null && amort.invoiceNumber !== '') || submittedTerms.has(termNumber);
                if (hasInvoice || isCanceled) {
                    sumSubmitted += parseFloat(amort.invoiceAmount) || 0;
                } else {
                    sumUnpaidTermValues += termValue;
                }
            });
            const remainingToPay = Math.max(0, totalAmount - sumSubmitted);

            // Generate rows for each term. Submitted/Canceled: DB value; unpaid: remainingToPay * (termValue / sumUnpaidTermValues)
            termAmortizations.forEach((amort) => {
                const termNumber = parseInt(amort.periodNumber, 10) || 0;
                const termValue = parseFloat(amort.termValue || 0) || 0;
                const termLabel = `Termin ${termNumber}`;
                const isCanceled = amort.isCanceled || false;
                const hasInvoice = (amort.invoiceNumber != null && amort.invoiceNumber !== '') || submittedTerms.has(termNumber);

                let status = '';
                let statusClass = '';
                let isEligible = false;

                if (isCanceled) {
                    status = 'Canceled';
                    statusClass = 'text-danger';
                    isEligible = false;
                } else if (hasInvoice) {
                    status = 'Submitted';
                    statusClass = 'text-success';
                    isEligible = false;
                } else {
                    isEligible = termNumber === eligibleTermNumber;
                    status = isEligible ? 'Eligible to Submit Invoice' : 'Not Eligible to Submit Invoice';
                    statusClass = isEligible ? 'text-success' : 'text-danger';
                }

                const storedAmount = parseFloat(amort.invoiceAmount) || 0;
                let invoiceAmount = storedAmount;
                if (hasInvoice || isCanceled) {
                    invoiceAmount = storedAmount;
                } else if (totalAmount > 0 && sumUnpaidTermValues > 0) {
                    invoiceAmount = (remainingToPay * termValue) / sumUnpaidTermValues;
                }

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-1">
                            ${!hasInvoice && !isCanceled ? `
                                <button class="btn btn-sm btn-icon btn-primary btn-choose-term"
                                        data-term-number="${termNumber}"
                                        data-term-value="${termValue}"
                                        data-is-eligible="${isEligible}"
                                        title="Choose Term">
                                    <i class="icon-base bx bx-pointer text-white"></i>
                                </button>
                            ` : `
                                <span class="badge ${isCanceled ? 'bg-label-danger' : 'bg-label-success'}">${isCanceled ? 'Canceled' : 'Submitted'}</span>
                            `}
                            <button class="btn btn-sm btn-icon btn-outline-info btn-approval-log"
                                    data-term-number="${termNumber}"
                                    title="Show Approval Log">
                                <i class="icon-base bx bx-history"></i>
                            </button>
                        </div>
                    </td>
                    <td class="text-center">${termLabel}</td>
                    <td class="text-center">${termValue}%</td>
                    <td class="text-center ${statusClass}">${status}</td>
                    <td class="text-center">${formatCurrency(invoiceAmount)}</td>
                `;
                tbody.appendChild(row);
            });

            // Setup event handlers
            const self = this;
            $(document).off('click', '.btn-choose-term').on('click', '.btn-choose-term', function() {
                const termNumber = parseInt($(this).data('term-number')) || 0;
                const termValue = parseFloat($(this).data('term-value')) || 0;
                const isEligible = $(this).data('is-eligible') === true;
                self.selectTermOfPayment(termNumber, termValue, isEligible);
            });

            $('.btn-approval-log').on('click', function() {
                Swal.fire({
                    icon: 'info',
                    title: 'Approval Log',
                    text: 'Approval log feature will be implemented soon'
                });
            });

        } catch (error) {
            const tbody = document.getElementById('tblTermOfPaymentBody');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-danger">Error loading Term of Payment</td></tr>';
            }
        }
    }

    /**
     * Load Term of Payment Grid (Original - for backward compatibility)
     */
    async loadTermOfPaymentGrid() {
        try {
            const tbody = document.getElementById('tblTermOfPaymentBody');
            if (!tbody) return;

            tbody.innerHTML = '';

            const selectedPO = this.manager.poModule?.selectedPO || null;
            if (!selectedPO) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No PO selected</td></tr>';
                return;
            }

            // Load amortizations from API (database)
            const poNumber = selectedPO.purchOrderID || selectedPO.PurchOrderID || '';
            let termAmortizations = [];

            if (poNumber && this.manager && this.manager.apiModule) {
                try {
                    const amortizationsData = await this.manager.apiModule.getAmortizations(poNumber);
                    termAmortizations = amortizationsData?.termOfPayment || [];
                } catch (error) {
                    console.warn('Failed to load amortizations from API, falling back to TOPDescription:', error);
                    // Fallback: use TOPDescription if API fails
                    const topDescription = selectedPO.topDescription || selectedPO.TOPDescription || '';
                    if (topDescription) {
                        const termValues = topDescription.split('|').map(v => parseFloat(v.trim())).filter(v => !isNaN(v));
                        const poAmount = parseFloat(selectedPO.poAmount || selectedPO.POAmount || 0);
                        termAmortizations = termValues.map((termValue, index) => ({
                            periodNumber: index + 1,
                            termValue: termValue,
                            invoiceAmount: (poAmount * termValue) / 100,
                            status: 'Available',
                            isCanceled: false,
                            invoiceNumber: null
                        }));
                    }
                }
            } else {
                // Fallback: use TOPDescription if no API module
                const topDescription = selectedPO.topDescription || selectedPO.TOPDescription || '';
                if (!topDescription) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No Term of Payment available</td></tr>';
                    return;
                }
                const termValues = topDescription.split('|').map(v => parseFloat(v.trim())).filter(v => !isNaN(v));
                const poAmount = parseFloat(selectedPO.poAmount || selectedPO.POAmount || 0);
                termAmortizations = termValues.map((termValue, index) => ({
                    periodNumber: index + 1,
                    termValue: termValue,
                    invoiceAmount: (poAmount * termValue) / 100,
                    status: 'Available',
                    isCanceled: false,
                    invoiceNumber: null
                }));
            }

            if (termAmortizations.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No term of payment found</td></tr>';
                return;
            }

            const parseCurrency = this.utils ? this.utils.parseCurrency.bind(this.utils) : this.parseCurrency.bind(this);
            let totalAmount = 0;
            const itemListTotalEl = document.getElementById('txtItemListTotalAmount');
            if (itemListTotalEl && itemListTotalEl.value) {
                totalAmount = parseCurrency(itemListTotalEl.value);
            }
            if (totalAmount <= 0) {
                const poDetailItems = this.manager.poModule?.poDetailItems || [];
                if (poDetailItems.length > 0) {
                    totalAmount = poDetailItems.reduce((sum, item) => sum + (parseFloat(item.amount || item.Amount || 0) || 0), 0);
                }
            }
            if (totalAmount <= 0) {
                totalAmount = parseFloat(selectedPO.poAmount || selectedPO.POAmount || 0) || 0;
            }

            // Check existing invoices to determine eligible term number
            let submittedTerms = new Set();
            let eligibleTermNumber = 1;
            
            if (poNumber && this.manager && this.manager.apiModule) {
                try {
                    const existingInvoices = await this.manager.apiModule.getInvoiceDetails(poNumber);
                    
                    if (existingInvoices && existingInvoices.length > 0) {
                        submittedTerms = this.collectSubmittedPositions(existingInvoices);
                        if (submittedTerms.size > 0) {
                            const maxTermPosition = Math.max(...submittedTerms);
                            eligibleTermNumber = maxTermPosition + 1;
                        }
                    }
                } catch (error) {
                    console.warn('Failed to check existing invoices for eligible term:', error);
                    eligibleTermNumber = 1;
                }
            }

            const formatCurrency = this.utils ? this.utils.formatCurrency.bind(this.utils) : this.formatCurrency.bind(this);

            let sumSubmitted = 0;
            let sumUnpaidTermValues = 0;
            termAmortizations.forEach((amort) => {
                const termNumber = parseInt(amort.periodNumber, 10) || 0;
                const termValue = parseFloat(amort.termValue || 0) || 0;
                const isCanceled = amort.isCanceled || false;
                const hasInvoice = (amort.invoiceNumber != null && amort.invoiceNumber !== '') || submittedTerms.has(termNumber);
                if (hasInvoice || isCanceled) {
                    sumSubmitted += parseFloat(amort.invoiceAmount) || 0;
                } else {
                    sumUnpaidTermValues += termValue;
                }
            });
            const remainingToPay = Math.max(0, totalAmount - sumSubmitted);

            // Submitted/Canceled: DB value; unpaid: remainingToPay * (termValue / sumUnpaidTermValues)
            termAmortizations.forEach((amort) => {
                const termNumber = parseInt(amort.periodNumber, 10) || 0;
                const termValue = parseFloat(amort.termValue || 0) || 0;
                const termLabel = `Termin ${termNumber}`;
                const isCanceled = amort.isCanceled || false;
                const hasInvoice = (amort.invoiceNumber != null && amort.invoiceNumber !== '') || submittedTerms.has(termNumber);

                let status = '';
                let statusClass = '';
                let isEligible = false;

                if (isCanceled) {
                    status = 'Canceled';
                    statusClass = 'text-danger';
                    isEligible = false;
                } else if (hasInvoice) {
                    status = 'Submitted';
                    statusClass = 'text-success';
                    isEligible = false;
                } else {
                    isEligible = termNumber === eligibleTermNumber;
                    status = isEligible ? 'Eligible to Submit Invoice' : 'Not Eligible to Submit Invoice';
                    statusClass = isEligible ? 'text-success' : 'text-danger';
                }

                const storedAmount = parseFloat(amort.invoiceAmount) || 0;
                let invoiceAmount = storedAmount;
                if (hasInvoice || isCanceled) {
                    invoiceAmount = storedAmount;
                } else if (totalAmount > 0 && sumUnpaidTermValues > 0) {
                    invoiceAmount = (remainingToPay * termValue) / sumUnpaidTermValues;
                }

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-1">
                            ${!hasInvoice ? `
                                <button class="btn btn-sm btn-icon btn-primary btn-choose-term"
                                        data-term-number="${termNumber}"
                                        data-term-value="${termValue}"
                                        data-is-eligible="${isEligible}"
                                        title="Choose Term">
                                    <i class="icon-base bx bx-pointer text-white"></i>
                                </button>
                            ` : `
                                <span class="badge bg-label-success">Submitted</span>
                            `}
                            <button class="btn btn-sm btn-icon btn-outline-info btn-approval-log"
                                    data-term-number="${termNumber}"
                                    title="Show Approval Log">
                                <i class="icon-base bx bx-history"></i>
                            </button>
                        </div>
                    </td>
                    <td class="text-center">${termLabel}</td>
                    <td class="text-center">${termValue}%</td>
                    <td class="text-center ${statusClass}">${status}</td>
                    <td class="text-center">${formatCurrency(invoiceAmount)}</td>
                `;
                tbody.appendChild(row);
            });

            // Setup event handlers
            const self = this;
            $(document).off('click', '.btn-choose-term').on('click', '.btn-choose-term', function() {
                const termNumber = parseInt($(this).data('term-number')) || 0;
                const termValue = parseFloat($(this).data('term-value')) || 0;
                const isEligible = $(this).data('is-eligible') === true;
                self.selectTermOfPayment(termNumber, termValue, isEligible);
            });

            $('.btn-approval-log').on('click', function() {
                Swal.fire({
                    icon: 'info',
                    title: 'Approval Log',
                    text: 'Approval log feature will be implemented soon'
                });
            });

        } catch (error) {
            const tbody = document.getElementById('tblTermOfPaymentBody');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-danger">Error loading Term of Payment</td></tr>';
            }
        }
    }

    /**
     * Check and Load Period of Payment (Optimized - uses pre-fetched billingTypes)
     */
    async checkAndLoadPeriodOfPaymentOptimized(additionalData, billingTypes) {
        try {
            if (!additionalData) {
                return false;
            }

            // Check if StartPeriod or EndPeriod exists
            const startPeriod = additionalData.startPeriod || additionalData.StartPeriod;
            const endPeriod = additionalData.endPeriod || additionalData.EndPeriod;

            if (!startPeriod && !endPeriod) {
                return false;
            }

            // Get Period and BillingTypeID
            const period = additionalData.period || additionalData.Period || 0;
            const billingTypeID = additionalData.billingTypeID || additionalData.BillingTypeID;

            if (!billingTypeID || period <= 0) {
                return false;
            }

            // Get BillingType from pre-fetched data or fetch if not available
            let billingType = null;
            if (billingTypes) {
                billingType = billingTypes.find(bt => (bt.id || bt.ID) === billingTypeID);
            }

            // Fallback: fetch if not in pre-fetched data
            if (!billingType && this.manager && this.manager.apiModule) {
                const allBillingTypes = await this.manager.apiModule.getBillingTypes();
                billingType = allBillingTypes.find(bt => (bt.id || bt.ID) === billingTypeID);
            }

            if (!billingType) {
                console.warn('BillingType not found for ID:', billingTypeID);
                return false;
            }

            const totalMonthPeriod = billingType.totalMonthPeriod || billingType.TotalMonthPeriod || 1;

            // Store PurchaseRequestAdditional data for later use
            if (this.manager && this.manager.poModule) {
                this.manager.poModule.purchaseRequestAdditional = {
                    startPeriod: startPeriod,
                    endPeriod: endPeriod,
                    period: period,
                    billingTypeID: billingTypeID,
                    totalMonthPeriod: totalMonthPeriod
                };
            }

            // Generate amortization and load Period of Payment grid
            await this.loadPeriodOfPaymentGrid(startPeriod, endPeriod, period, totalMonthPeriod);

            return true;
        } catch (error) {
            console.error('Failed to check Period of Payment:', error);
            return false;
        }
    }

    /**
     * Check and Load Period of Payment (Original - for backward compatibility)
     */
    async checkAndLoadPeriodOfPayment(prNumber) {
        try {
            if (!this.manager || !this.manager.apiModule) {
                return false;
            }

            // Get PurchaseRequestAdditional by PRNumber
            const additionalData = await this.manager.apiModule.getPRAdditional(prNumber);

            if (!additionalData) {
                return false;
            }

            // Check if StartPeriod or EndPeriod exists
            const startPeriod = additionalData.startPeriod || additionalData.StartPeriod;
            const endPeriod = additionalData.endPeriod || additionalData.EndPeriod;

            if (!startPeriod && !endPeriod) {
                return false;
            }

            // Get Period and BillingTypeID
            const period = additionalData.period || additionalData.Period || 0;
            const billingTypeID = additionalData.billingTypeID || additionalData.BillingTypeID;

            if (!billingTypeID || period <= 0) {
                return false;
            }

            // Get BillingType to get TotalMonthPeriod
            const billingTypes = await this.manager.apiModule.getBillingTypes();
            const billingType = billingTypes.find(bt => (bt.id || bt.ID) === billingTypeID);

            if (!billingType) {
                console.warn('BillingType not found for ID:', billingTypeID);
                return false;
            }

            const totalMonthPeriod = billingType.totalMonthPeriod || billingType.TotalMonthPeriod || 1;

            // Store PurchaseRequestAdditional data for later use
            if (this.manager && this.manager.poModule) {
                this.manager.poModule.purchaseRequestAdditional = {
                    startPeriod: startPeriod,
                    endPeriod: endPeriod,
                    period: period,
                    billingTypeID: billingTypeID,
                    totalMonthPeriod: totalMonthPeriod
                };
            }

            // Generate amortization and load Period of Payment grid
            await this.loadPeriodOfPaymentGrid(startPeriod, endPeriod, period, totalMonthPeriod);

            return true;
        } catch (error) {
            console.error('Failed to check Period of Payment:', error);
            return false;
        }
    }

    /**
     * Load Period of Payment grid from pre-fetched amortization data (same as Cancel Period).
     * Used when Create Invoice has already fetched getAmortizations and periodOfPayment has items.
     */
    async loadPeriodOfPaymentGridFromAmortizations(amortizationsData, existingInvoices = null) {
        try {
            const tbody = document.getElementById('tblPeriodOfPaymentBody');
            if (!tbody) return;

            tbody.innerHTML = '';

            const selectedPO = this.manager.poModule?.selectedPO || null;
            if (!selectedPO) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No PO selected</td></tr>';
                return;
            }

            const dataPayload = amortizationsData && typeof amortizationsData === 'object' ? (amortizationsData.data || amortizationsData) : amortizationsData;
            const rawPeriodAmortizations = (dataPayload && Array.isArray(dataPayload.periodOfPayment)) ? dataPayload.periodOfPayment : [];

            if (rawPeriodAmortizations.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No amortization periods found</td></tr>';
                return;
            }

            const periodAmortizations = rawPeriodAmortizations.map(amort => ({
                ...amort,
                startDate: amort.startDate ? new Date(amort.startDate) : null,
                endDate: amort.endDate ? new Date(amort.endDate) : null
            }));

            const termValue = 100;
            const formatCurrency = this.utils ? this.utils.formatCurrency.bind(this.utils) : this.formatCurrency.bind(this);
            const formatDate = this.utils ? this.utils.formatDate.bind(this.utils) : this.formatDate.bind(this);

            periodAmortizations.forEach((amort) => {
                const periodNumber = parseInt(amort.periodNumber, 10) || 0;
                const isCanceled = Boolean(amort.isCanceled);
                const hasInvoice = amort.invoiceNumber != null && amort.invoiceNumber !== '';

                let canSelect = false;
                if (hasInvoice || isCanceled) {
                    canSelect = false;
                } else if (periodNumber === 1) {
                    canSelect = true;
                } else {
                    const prevAmort = periodAmortizations.find(a => (parseInt(a.periodNumber, 10) || 0) === periodNumber - 1);
                    const prevHasInvoice = prevAmort && (prevAmort.invoiceNumber != null && prevAmort.invoiceNumber !== '');
                    canSelect = prevHasInvoice;
                }

                let status = 'Invoice Not Submitted Yet';
                let statusClass = 'text-warning';
                if (isCanceled) {
                    status = 'Canceled';
                    statusClass = 'text-danger';
                } else if (hasInvoice) {
                    status = 'Invoice Submitted';
                    statusClass = 'text-success';
                }

                const startDateObj = amort.startDate instanceof Date ? amort.startDate : (amort.startDate ? new Date(amort.startDate) : null);
                const endDateObj = amort.endDate instanceof Date ? amort.endDate : (amort.endDate ? new Date(amort.endDate) : null);
                const startDateStr = startDateObj && !isNaN(startDateObj.getTime()) ? startDateObj.toISOString().split('T')[0] : '';
                const endDateStr = endDateObj && !isNaN(endDateObj.getTime()) ? endDateObj.toISOString().split('T')[0] : '';

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-1">
                            ${!hasInvoice && !isCanceled ? `
                                <input type="checkbox"
                                       class="form-check-input period-checkbox"
                                       id="chkPeriod_${periodNumber}"
                                       data-period-number="${periodNumber}"
                                       data-start-date="${startDateStr}"
                                       data-end-date="${endDateStr}"
                                       data-term-value="${termValue}"
                                       data-has-invoice="false"
                                       ${canSelect ? '' : 'disabled'}
                                       title="${canSelect ? 'Select Period' : 'Please select previous periods first'}">
                            ` : `
                                <span class="badge ${isCanceled ? 'bg-label-danger' : 'bg-label-success'}">${isCanceled ? 'Canceled' : 'Invoice Submitted'}</span>
                            `}
                            <button class="btn btn-sm btn-icon btn-outline-info btn-approval-log-period"
                                    data-period-number="${periodNumber}"
                                    title="Show Approval Log">
                                <i class="icon-base bx bx-history"></i>
                            </button>
                        </div>
                    </td>
                    <td class="text-center">Period ${periodNumber}</td>
                    <td class="text-center">${termValue}%</td>
                    <td class="text-center">${formatDate(startDateObj)}</td>
                    <td class="text-center">${formatDate(endDateObj)}</td>
                    <td class="text-center ${statusClass}">${status}</td>
                    <td class="text-center">${formatCurrency(amort.invoiceAmount || 0)}</td>
                `;
                tbody.appendChild(row);
            });

            const self = this;
            const totalPeriods = periodAmortizations.length;
            $(document).off('change', '.period-checkbox').on('change', '.period-checkbox', function() {
                const periodNumber = parseInt($(this).data('period-number')) || 0;
                const isChecked = $(this).is(':checked');

                if (isChecked) {
                    let allPreviousSelected = true;
                    for (let i = 1; i < periodNumber; i++) {
                        const prevCheckbox = $(`#chkPeriod_${i}`);
                        if (prevCheckbox.length > 0 && !prevCheckbox.is(':checked')) {
                            allPreviousSelected = false;
                            break;
                        }
                    }

                    if (!allPreviousSelected) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Invalid Selection',
                            text: `Please select Period ${periodNumber - 1} first before selecting Period ${periodNumber}.`
                        });
                        $(this).prop('checked', false);
                        return;
                    }

                    if (!self.selectedPeriods.includes(periodNumber)) {
                        self.selectedPeriods.push(periodNumber);
                    }
                    self.selectedPeriods.sort((a, b) => a - b);

                    // Show Invoice Basic Information, Amount Summary, Document Checklist, Submit Buttons
                    self.updatePeriodOfPaymentInvoice();

                    // Enable next period checkbox so user can select multiple in sequence
                    const nextCheckbox = $(`#chkPeriod_${periodNumber + 1}`);
                    if (nextCheckbox.length > 0) {
                        nextCheckbox.prop('disabled', false);
                    }
                } else {
                    self.selectedPeriods = self.selectedPeriods.filter(p => p !== periodNumber);
                    // Uncheck and disable all subsequent periods (keep sequence)
                    for (let i = periodNumber + 1; i <= totalPeriods; i++) {
                        const nextCheckbox = $(`#chkPeriod_${i}`);
                        if (nextCheckbox.length > 0) {
                            nextCheckbox.prop('checked', false);
                            nextCheckbox.prop('disabled', true);
                        }
                    }
                    self.selectedPeriods.sort((a, b) => a - b);

                    if (self.selectedPeriods.length > 0) {
                        self.updatePeriodOfPaymentInvoice();
                    } else {
                        const currentInvoiceId = self.manager && self.manager.currentInvoiceId ? self.manager.currentInvoiceId : null;
                        if (!currentInvoiceId) {
                            $('#invoiceInformationSection').hide();
                            $('#documentChecklistSection').hide();
                            $('#submitButtonsSection').hide();
                        }
                    }
                }
            });

            $('.btn-approval-log-period').on('click', function() {
                Swal.fire({ icon: 'info', title: 'Approval Log', text: 'Approval log feature will be implemented soon' });
            });
        } catch (error) {
            console.error('Error loading period of payment grid from amortizations:', error);
            const tbody = document.getElementById('tblPeriodOfPaymentBody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-danger">Error loading periods</td></tr>';
        }
    }

    /**
     * Load Period of Payment Grid
     */
    async loadPeriodOfPaymentGrid(startPeriod, endPeriod, period, totalMonthPeriod) {
        try {
            const tbody = document.getElementById('tblPeriodOfPaymentBody');
            if (!tbody) return;

            tbody.innerHTML = '';

            const selectedPO = this.manager.poModule?.selectedPO || null;
            if (!selectedPO) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No PO selected</td></tr>';
                return;
            }

            // Parse dates
            const startDate = new Date(startPeriod);
            const endDate = new Date(endPeriod);

            if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-danger">Invalid date format</td></tr>';
                return;
            }

            // Get PO Amount
            const poAmount = parseFloat(selectedPO.poAmount || selectedPO.POAmount || 0);

            // For Period Of Payment Invoice, each period uses Term Value = 100%
            const termValue = 100;
            const invoiceAmount = poAmount;

            // Load amortizations from API (database)
            const poNumber = selectedPO.purchOrderID || selectedPO.PurchOrderID || '';
            let periodAmortizations = [];

            if (poNumber && this.manager && this.manager.apiModule) {
                try {
                    const amortizationsData = await this.manager.apiModule.getAmortizations(poNumber);
                    const rawPeriodAmortizations = amortizationsData?.periodOfPayment || [];

                    // Parse date strings to Date objects
                    periodAmortizations = rawPeriodAmortizations.map(amort => ({
                        ...amort,
                        startDate: amort.startDate ? new Date(amort.startDate) : null,
                        endDate: amort.endDate ? new Date(amort.endDate) : null
                    }));
                } catch (error) {
                    console.warn('Failed to load amortizations from API, falling back to generated:', error);
                    // Fallback: use generated amortizations if API fails
                    const amortizations = this.generateAmortization(startDate, endDate, period, totalMonthPeriod);
                    periodAmortizations = amortizations.map((amort, index) => ({
                        periodNumber: index + 1,
                        startDate: amort.startDate,
                        endDate: amort.endDate,
                        termValue: 100,
                        invoiceAmount: poAmount,
                        status: 'Available',
                        isCanceled: false,
                        invoiceNumber: null
                    }));
                }
            } else {
                // Fallback: use generated amortizations if no API module
                const amortizations = this.generateAmortization(startDate, endDate, period, totalMonthPeriod);
                periodAmortizations = amortizations.map((amort, index) => ({
                    periodNumber: index + 1,
                    startDate: amort.startDate,
                    endDate: amort.endDate,
                    termValue: 100,
                    invoiceAmount: poAmount,
                    status: 'Available',
                    isCanceled: false,
                    invoiceNumber: null
                }));
            }

            if (periodAmortizations.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No amortization periods found</td></tr>';
                return;
            }

            const formatCurrency = this.utils ? this.utils.formatCurrency.bind(this.utils) : this.formatCurrency.bind(this);
            const formatDate = this.utils ? this.utils.formatDate.bind(this.utils) : this.formatDate.bind(this);

            // Generate rows for each amortization period from database
            periodAmortizations.forEach((amort) => {
                const periodNumber = parseInt(amort.periodNumber, 10) || 0;
                const isCanceled = Boolean(amort.isCanceled);
                const hasInvoice = amort.invoiceNumber != null && amort.invoiceNumber !== '';

                // Can't select if canceled or already has invoice
                let canSelect = false;
                if (hasInvoice || isCanceled) {
                    canSelect = false;
                } else if (periodNumber === 1) {
                    canSelect = true;
                } else {
                    // Check if previous period has invoice (sequential requirement)
                    const prevAmort = periodAmortizations.find(a => (parseInt(a.periodNumber, 10) || 0) === periodNumber - 1);
                    const prevHasInvoice = prevAmort && (prevAmort.invoiceNumber != null && prevAmort.invoiceNumber !== '');
                    canSelect = prevHasInvoice;
                }

                // Status: Priority: Canceled > Invoice Submitted > Available
                let status = 'Invoice Not Submitted Yet';
                let statusClass = 'text-warning';
                if (isCanceled) {
                    status = 'Canceled';
                    statusClass = 'text-danger';
                } else if (hasInvoice) {
                    status = 'Invoice Submitted';
                    statusClass = 'text-success';
                }

                // Ensure startDate and endDate are Date objects
                const startDateObj = amort.startDate instanceof Date ? amort.startDate : (amort.startDate ? new Date(amort.startDate) : null);
                const endDateObj = amort.endDate instanceof Date ? amort.endDate : (amort.endDate ? new Date(amort.endDate) : null);

                // Format dates for data attributes (YYYY-MM-DD)
                const startDateStr = startDateObj && !isNaN(startDateObj.getTime()) ? startDateObj.toISOString().split('T')[0] : '';
                const endDateStr = endDateObj && !isNaN(endDateObj.getTime()) ? endDateObj.toISOString().split('T')[0] : '';

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="text-center">
                        <div class="d-flex justify-content-center gap-1">
                            ${!hasInvoice && !isCanceled ? `
                                <input type="checkbox"
                                       class="form-check-input period-checkbox"
                                       id="chkPeriod_${periodNumber}"
                                       data-period-number="${periodNumber}"
                                       data-start-date="${startDateStr}"
                                       data-end-date="${endDateStr}"
                                       data-term-value="${termValue}"
                                       data-has-invoice="false"
                                       ${canSelect ? '' : 'disabled'}
                                       title="${canSelect ? 'Select Period' : 'Please select previous periods first'}">
                            ` : `
                                <span class="badge ${isCanceled ? 'bg-label-danger' : 'bg-label-success'}">${isCanceled ? 'Canceled' : 'Invoice Submitted'}</span>
                            `}
                            <button class="btn btn-sm btn-icon btn-outline-info btn-approval-log-period"
                                    data-period-number="${periodNumber}"
                                    title="Show Approval Log">
                                <i class="icon-base bx bx-history"></i>
                            </button>
                        </div>
                    </td>
                    <td class="text-center">Period ${periodNumber}</td>
                    <td class="text-center">${termValue}%</td>
                    <td class="text-center">${formatDate(startDateObj)}</td>
                    <td class="text-center">${formatDate(endDateObj)}</td>
                    <td class="text-center ${statusClass}">${status}</td>
                    <td class="text-center">${formatCurrency(amort.invoiceAmount || invoiceAmount)}</td>
                `;
                tbody.appendChild(row);
            });

            // Setup event handlers for checkboxes
            const self = this;
            const totalPeriods = periodAmortizations.length; // Store total periods for use in event handler
            $(document).off('change', '.period-checkbox').on('change', '.period-checkbox', function() {
                const periodNumber = parseInt($(this).data('period-number')) || 0;
                const isChecked = $(this).is(':checked');

                if (isChecked) {
                    // Check if previous periods are selected
                    let allPreviousSelected = true;
                    for (let i = 1; i < periodNumber; i++) {
                        const prevCheckbox = $(`#chkPeriod_${i}`);
                        if (prevCheckbox.length > 0 && !prevCheckbox.is(':checked')) {
                            allPreviousSelected = false;
                            break;
                        }
                    }

                    if (!allPreviousSelected) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Invalid Selection',
                            text: `Please select Period ${periodNumber - 1} first before selecting Period ${periodNumber}.`
                        });
                        $(this).prop('checked', false);
                        return;
                    }

                    // Add period to selected periods array
                    if (!self.selectedPeriods.includes(periodNumber)) {
                        self.selectedPeriods.push(periodNumber);
                        self.selectedPeriods.sort((a, b) => a - b);
                    }

                    // Update invoice information for multiple periods
                    self.updatePeriodOfPaymentInvoice();

                    // Enable next period checkbox if exists
                    const nextCheckbox = $(`#chkPeriod_${periodNumber + 1}`);
                    if (nextCheckbox.length > 0) {
                        nextCheckbox.prop('disabled', false);
                    }
                } else {
                    // Remove period from selected periods array
                    self.selectedPeriods = self.selectedPeriods.filter(p => p !== periodNumber);

                    // Uncheck this period and disable all subsequent periods
                    for (let i = periodNumber + 1; i <= totalPeriods; i++) {
                        const nextCheckbox = $(`#chkPeriod_${i}`);
                        if (nextCheckbox.length > 0) {
                            nextCheckbox.prop('checked', false);
                            nextCheckbox.prop('disabled', true);
                            self.selectedPeriods = self.selectedPeriods.filter(p => p !== i);
                        }
                    }

                    // Update invoice information
                    if (self.selectedPeriods.length > 0) {
                        self.updatePeriodOfPaymentInvoice();
                    } else {
                        const currentInvoiceId = self.manager.currentInvoiceId || null;
                        if (!currentInvoiceId) {
                            $('#invoiceInformationSection').hide();
                            $('#documentChecklistSection').hide();
                            $('#submitButtonsSection').hide();
                        }
                    }
                }
            });

            $('.btn-approval-log-period').on('click', function() {
                Swal.fire({
                    icon: 'info',
                    title: 'Approval Log',
                    text: 'Approval log feature will be implemented soon'
                });
            });

        } catch (error) {
            const tbody = document.getElementById('tblPeriodOfPaymentBody');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-danger">Error loading Period of Payment</td></tr>';
            }
        }
    }

    /**
     * Generate Amortization
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
     * Select Term of Payment
     */
    selectTermOfPayment(termNumber, termValue, isEligible) {
        // Check if term is eligible
        if (!isEligible) {
            Swal.fire({
                icon: 'warning',
                title: 'Not Eligible',
                text: 'This term is not eligible to submit invoice. Please select an eligible term.',
                confirmButtonText: 'OK'
            });
            return;
        }

        // Set Term Position and Term Invoice
        $('#txtTermPosition').val(termNumber);
        $('#txtTermInvoice').val(`${termValue}%`);

        // Store selected term values
        this.selectedTermNumber = termNumber;
        this.selectedTermValue = termValue;

        // Update current term value in PO module for recalculation
        if (this.manager.poModule) {
            this.manager.poModule.currentTermValue = termValue;
        }

        // Recalculate Qty Invoice and Amount Invoice for all items
        if (this.manager && this.manager.formModule && this.manager.poModule) {
            this.manager.formModule.recalculateInvoiceItems(termValue, this.manager.poModule.poDetailItems);
        }

        // Set default Invoice Date and Tax Date to today
        const today = new Date().toISOString().split('T')[0];
        $('#txtInvoiceDate').val(today);
        $('#txtTaxDate').val(today);

        // Hide Term of Payment section
        $('#termOfPaymentSection').hide();

        // Show Invoice Information, Document Checklist, and Submit Buttons sections
        $('#invoiceInformationSection').show();
        $('#documentChecklistSection').show();
        $('#submitButtonsSection').show();

        // Load document checklist
        const selectedPO = this.manager.poModule?.selectedPO || null;
        const workTypeID = selectedPO ? (selectedPO.workTypeID || 0) : 0;
        const termOfPaymentID = selectedPO ? (selectedPO.seqTOPID || selectedPO.SeqTOPID || 0) : 0;

        if (!workTypeID) {
            Swal.fire({
                icon: 'warning',
                title: 'WorkType Not Found',
                text: 'WorkType is required to load document checklist. Please ensure Purchase Order has valid PurchaseType and PurchaseSubType mapping.'
            });
            return;
        }

        // Use termValue (30, 50, 20) as termNumber for API lookup
        if (this.manager && this.manager.documentModule) {
            this.manager.documentModule.loadDocumentChecklist(workTypeID, termOfPaymentID, termValue);
        }

        Swal.fire({
            icon: 'success',
            title: 'Term Selected',
            text: `Term ${termNumber} (${termValue}%) has been selected`,
            timer: 2000,
            showConfirmButton: false
        });
    }

    /**
     * Update Period of Payment Invoice
     */
    updatePeriodOfPaymentInvoice() {
        if (this.selectedPeriods.length === 0) {
            return;
        }

        const termValue = 100; // Fixed 100% for period payment

        // Set Term Position: "Period 1, Period 2, Period 3" etc.
        const termPositionText = this.selectedPeriods.map(p => `Period ${p}`).join(', ');
        $('#txtTermPosition').val(termPositionText);

        // Set Term Invoice: "100%" only
        $('#txtTermInvoice').val(`${termValue}%`);

        // Store selected period values
        this.selectedTermNumber = this.selectedPeriods.length > 0 ? this.selectedPeriods[0] : null;
        this.selectedTermValue = termValue;

        // Update current term value in PO module
        if (this.manager.poModule) {
            this.manager.poModule.currentTermValue = termValue;
        }

        // Recalculate Qty Invoice and Amount Invoice for all items
        const selectedPO = this.manager.poModule?.selectedPO || null;
        const poDetailItems = this.manager.poModule?.poDetailItems || [];

        if (selectedPO && this.manager && this.manager.formModule) {
            const numberOfPeriods = this.selectedPeriods.length;
            const formatCurrency = this.utils ? this.utils.formatCurrency.bind(this.utils) : this.formatCurrency.bind(this);
            const parseCurrency = this.utils ? this.utils.parseCurrency.bind(this.utils) : this.parseCurrency.bind(this);

            $('.qty-invoice').each(function() {
                const lineIndex = $(this).data('line');
                const item = poDetailItems[lineIndex];
                if (!item) return;

                const qtyPO = parseFloat($(this).data('qty-po')) || 0;
                const unitPrice = parseFloat($(this).data('unit-price')) || 0;

                // Calculate Qty Invoice = Qty PO × number of periods (each period = 100%)
                const qtyInvoice = qtyPO * numberOfPeriods;
                // Calculate Amount Invoice = Qty Invoice × Price
                const amountInvoice = qtyInvoice * unitPrice;

                // Update Qty Invoice input
                $(this).val(qtyInvoice.toFixed(2));
                // Update Amount Invoice display
                $(`.line-amount-invoice[data-line="${lineIndex}"]`).text(formatCurrency(amountInvoice));
            });

            // Calculate Invoice Amount from line items
            let totalInvoiceAmount = 0;
            $('.line-amount-invoice').each(function() {
                totalInvoiceAmount += parseCurrency($(this).text());
            });
            $('#txtInvoiceAmount').val(formatCurrency(totalInvoiceAmount));

            // Recalculate tax and total amount
            this.manager.formModule.calculateTax();
        }

        // Set default Invoice Date and Tax Date to today
        const today = new Date().toISOString().split('T')[0];
        $('#txtInvoiceDate').val(today);
        $('#txtTaxDate').val(today);

        // Show Invoice Information, Document Checklist, and Submit Buttons sections
        $('#invoiceInformationSection').show();
        $('#documentChecklistSection').show();
        $('#submitButtonsSection').show();

        // Load document checklist
        const workTypeID = selectedPO ? (selectedPO.workTypeID || 0) : 0;
        const termOfPaymentID = selectedPO ? (selectedPO.seqTOPID || selectedPO.SeqTOPID || 0) : 0;

        if (!workTypeID) {
            Swal.fire({
                icon: 'warning',
                title: 'WorkType Not Found',
                text: 'WorkType is required to load document checklist. Please ensure Purchase Order has valid PurchaseType and PurchaseSubType mapping.'
            });
            return;
        }

        // Use termValue (100) for API lookup
        if (this.manager && this.manager.documentModule) {
            this.manager.documentModule.loadDocumentChecklist(workTypeID, termOfPaymentID, termValue);
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

    parseCurrency(value) {
        if (!value) return 0;
        const cleaned = value.toString().replace(/[^\d]/g, '');
        return parseFloat(cleaned) || 0;
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.InvoiceCreateTerm = InvoiceCreateTerm;
}

