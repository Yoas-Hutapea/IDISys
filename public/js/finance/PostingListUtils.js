/**
 * PostingListUtils Module
 * Utility functions for formatting and helper methods
 */
class PostingListUtils {
    constructor() {
        // These will be populated by API module
        this.allPurchaseTypes = null;
        this.allPurchaseSubTypes = null;
        this.allTaxes = null;
    }

    /**
     * Set master data for formatting
     */
    setMasterData(purchaseTypes, purchaseSubTypes, taxes) {
        this.allPurchaseTypes = purchaseTypes;
        this.allPurchaseSubTypes = purchaseSubTypes;
        this.allTaxes = taxes;
    }

    /**
     * Format currency
     */
    formatCurrency(amount) {
        return new Intl.NumberFormat('id-ID', { 
            style: 'currency', 
            currency: 'IDR', 
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount || 0);
    }

    /**
     * Format date
     */
    formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';
        return date.toLocaleDateString('en-GB');
    }

    /**
     * Get tax name by ID
     */
    getTaxName(taxId) {
        if (!taxId && taxId !== 0) return '-';
        
        const taxIdNum = typeof taxId === 'string' ? parseInt(taxId.trim(), 10) : parseInt(taxId, 10);
        
        if (isNaN(taxIdNum) || taxIdNum <= 0 || !this.allTaxes) {
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

    /**
     * Format purchase type
     */
    formatPurchaseType(purchType) {
        if (!purchType && purchType !== 0) return '-';
        
        const purchTypeStr = typeof purchType === 'number' ? purchType.toString() : String(purchType || '');
        
        if (purchTypeStr.trim() === '') return '-';
        
        if (purchTypeStr.includes(' ') && isNaN(parseInt(purchTypeStr.trim()))) {
            return purchTypeStr;
        }
        
        const typeId = parseInt(purchTypeStr.trim(), 10);
        if (!isNaN(typeId) && typeId > 0 && this.allPurchaseTypes) {
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

    /**
     * Format purchase sub type
     */
    formatPurchaseSubType(purchSubType) {
        if (!purchSubType && purchSubType !== 0) return '-';
        
        const purchSubTypeStr = typeof purchSubType === 'number' ? purchSubType.toString() : String(purchSubType || '');
        
        if (purchSubTypeStr.trim() === '') return '-';
        
        if (isNaN(parseInt(purchSubTypeStr.trim()))) {
            return purchSubTypeStr;
        }
        
        const subTypeId = parseInt(purchSubTypeStr.trim(), 10);
        if (!isNaN(subTypeId) && subTypeId > 0 && this.allPurchaseSubTypes) {
            const subType = this.allPurchaseSubTypes.find(st => 
                parseInt(st.ID || st.id || '0', 10) === subTypeId
            );
            if (subType) {
                return subType.PurchaseRequestSubType || subType.purchaseRequestSubType || '';
            }
        }
        
        return purchSubTypeStr;
    }

    /**
     * Populate dropdown
     */
    populateDropdown(dropdownId, data, valueField, textField, includeAll = false) {
        const dropdown = $(`#${dropdownId}`);
        if (!dropdown.length) return;
        
        dropdown.empty();
        
        if (includeAll) {
            dropdown.append($('<option></option>').val('').text('All'));
        }
        
        data.forEach(item => {
            const value = item[valueField] || item[valueField.toLowerCase()] || '';
            const text = item[textField] || item[textField.toLowerCase()] || '';
            if (value && text) {
                dropdown.append($('<option></option>').val(value).text(text));
            }
        });
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.PostingListUtils = PostingListUtils;
}

