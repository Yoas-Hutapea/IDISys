/**
 * InvoiceCreateForm Module
 * Handles form validation, calculation, and tax management
 */
class InvoiceCreateForm {
    constructor(managerInstance) {
        this.manager = managerInstance;
        this.utils = null;
        if (window.InvoiceCreateUtils) {
            this.utils = new InvoiceCreateUtils();
        }
    }

    /**
     * Load Tax list into dropdown
     */
    async loadTaxList() {
        try {
            if (!this.manager || !this.manager.apiModule) {
                throw new Error('API module not available');
            }
            
            const taxList = await this.manager.apiModule.getTaxList();
            
            const select = document.getElementById('ddlTax');
            if (!select) return;
            
            select.innerHTML = '<option value="">-- Select Tax Code --</option>';
            taxList.forEach(tax => {
                const option = document.createElement('option');
                const taxId = tax.id || tax.ID;
                option.value = taxId;
                option.textContent = tax.taxName || tax.TaxName || '';
                option.setAttribute('data-tax-value', tax.taxValue || tax.TaxValue || 0);
                option.setAttribute('data-tax-id', taxId); // Store tax ID for AC13 check (TaxCode = 5 for PPN 12%)
                select.appendChild(option);
            });
        } catch (error) {
            console.error('Failed to load tax list:', error);
        }
    }

    /**
     * Load current user info for PIC Name and Email
     */
    loadCurrentUserInfo() {
        // AC16: Get current user info from ViewData (server-side) - set as default value (freetext, user can edit)
        try {
            const currentUserName = window.currentUserName || '';
            const currentUserEmail = window.currentUserEmail || '';
            
            if (currentUserName) {
                $('#txtPICName').val(currentUserName);
            }
            if (currentUserEmail) {
                $('#txtPICEmail').val(currentUserEmail);
            }
        } catch (error) {
            console.error('Failed to load current user info:', error);
        }
    }

    /**
     * Calculate Tax (AC13, AC14, AC15)
     */
    calculateTax() {
        // AC15: Amount DPP, Tax Amount, dan Total Amount otomatis tercalculate saat memilih Tax Code (Label Disabled, cannot editable)
        const formatCurrency = this.utils ? this.utils.formatCurrency.bind(this.utils) : this.formatCurrency.bind(this);
        const parseCurrency = this.utils ? this.utils.parseCurrency.bind(this.utils) : this.parseCurrency.bind(this);
        
        const invoiceAmount = parseCurrency($('#txtInvoiceAmount').val());
        const selectedTaxId = $('#ddlTax').val();
        
        // Reset fields if no tax selected
        if (!selectedTaxId) {
            $('#txtDPPAmount').val('');
            $('#txtTaxAmount').val('');
            $('#txtTotalAmount').val(formatCurrency(invoiceAmount));
            return;
        }
        
        // Get selected tax option
        const selectedOption = $('#ddlTax option:selected');
        const taxValueRaw = parseFloat(selectedOption.attr('data-tax-value') || 0);
        const taxCodeId = parseInt(selectedOption.attr('data-tax-id') || 0);
        
        // Tax Value from mstTax is stored as decimal (0.12 for 12%, 0.11 for 11%, etc.)
        const taxValue = taxValueRaw;
        
        let dppAmount = 0;
        let taxAmount = 0;
        let totalAmount = invoiceAmount;
        
        // AC13: Schema Perhitungan apabila Mitra memilih PPN (12%) - TaxCode ID = 5
        // AC14: Schema Perhitungan apabila Mitra memilih PPN selain daripada PPN (12%) atau selain TaxCode = 5
        if (taxCodeId === 5 && taxValue === 0.12) {
            // AC13: PPN 12% (TaxCode = 5)
            // Amount DPP = (11/12) * Invoice Amount
            // Tax Amount = Amount DPP * 12%
            // Total Amount = Invoice Amount + Tax Amount
            dppAmount = (11 / 12) * invoiceAmount;
            taxAmount = dppAmount * taxValue; // taxValue = 0.12 (12%)
            totalAmount = invoiceAmount + taxAmount;
        } else {
            // AC14: PPN selain 12% (Include 1%, 11%, PPnbm12%) atau selain TaxCode = 5
            // Amount DPP = Amount Invoice
            // Tax Amount = Amount DPP * Tax Code (percentage)
            // Total Amount = Amount Invoice + Tax Amount
            dppAmount = invoiceAmount;
            taxAmount = dppAmount * taxValue; // taxValue is decimal (0.11 for 11%, 0.01 for 1%, etc.)
            totalAmount = invoiceAmount + taxAmount;
        }
        
        // AC15: Auto fill DPP Amount, Tax Amount, dan Total Amount (disabled, cannot editable)
        $('#txtDPPAmount').val(formatCurrency(dppAmount));
        $('#txtTaxAmount').val(formatCurrency(taxAmount));
        $('#txtTotalAmount').val(formatCurrency(totalAmount));
    }

    /**
     * Calculate Invoice Amount from line items
     */
    calculateInvoiceAmount() {
        const formatCurrency = this.utils ? this.utils.formatCurrency.bind(this.utils) : this.formatCurrency.bind(this);
        const parseCurrency = this.utils ? this.utils.parseCurrency.bind(this.utils) : this.parseCurrency.bind(this);
        
        let total = 0;
        $('.line-amount-invoice').each(function() {
            total += parseCurrency($(this).text());
        });
        
        $('#txtInvoiceAmount').val(formatCurrency(total));
        this.calculateTax();
    }

    /**
     * Recalculate Invoice Items based on term value
     */
    recalculateInvoiceItems(termValue, poDetailItems) {
        const formatCurrency = this.utils ? this.utils.formatCurrency.bind(this.utils) : this.formatCurrency.bind(this);
        
        // Recalculate Qty Invoice and Amount Invoice for all items based on term value
        $('.qty-invoice').each(function() {
            const lineIndex = $(this).data('line');
            const item = poDetailItems[lineIndex];
            if (!item) return;
            
            const qtyPO = parseFloat($(this).data('qty-po')) || 0;
            const unitPrice = parseFloat($(this).data('unit-price')) || 0;
            
            // Calculate Qty Invoice = Qty PO * (Term Value / 100)
            const qtyInvoice = qtyPO * (termValue / 100);
            // Calculate Amount Invoice = Qty Invoice * Price
            const amountInvoice = qtyInvoice * unitPrice;
            
            // Update Qty Invoice input
            $(this).val(qtyInvoice.toFixed(2));
            // Update Amount Invoice display
            $(`.line-amount-invoice[data-line="${lineIndex}"]`).text(formatCurrency(amountInvoice));
        });
        
        // Recalculate total invoice amount
        this.calculateInvoiceAmount();
    }

    /**
     * Setup form validation
     */
    setupValidation() {
        // AC19: Tax Number validation - only 16 or 17 characters
        $('#txtTaxNumber').on('input', function() {
            const value = $(this).val();
            if (value.length > 0 && value.length !== 16 && value.length !== 17) {
                this.setCustomValidity('Tax Number must be exactly 16 or 17 characters.');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // AC18: Invoice Date validation - cannot be > current date
        $('#txtInvoiceDate').on('change', function() {
            const selectedDateStr = $(this).val();
            if (!selectedDateStr) {
                this.setCustomValidity('');
                return;
            }
            
            // Compare dates as strings (YYYY-MM-DD) to avoid timezone issues
            const today = new Date();
            const todayStr = today.getFullYear() + '-' + 
                            String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                            String(today.getDate()).padStart(2, '0');
            
            if (selectedDateStr > todayStr) {
                this.setCustomValidity('Invoice Date cannot be greater than current date.');
            } else {
                this.setCustomValidity('');
            }
        });

        // AC18: Set Invoice Date max to today
        const today = new Date().toISOString().split('T')[0];
        $('#txtInvoiceDate').attr('max', today);
    }

    /**
     * Setup form event handlers
     */
    setupEventHandlers() {
        // Tax change triggers calculation
        $('#ddlTax').on('change', () => {
            this.calculateTax();
        });

        // Invoice amount change triggers calculation
        $('#txtInvoiceAmount').on('input', () => {
            this.calculateTax();
        });
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

    parseCurrency(value) {
        if (!value) return 0;
        const cleaned = value.toString().replace(/[^\d]/g, '');
        return parseFloat(cleaned) || 0;
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.InvoiceCreateForm = InvoiceCreateForm;
}

