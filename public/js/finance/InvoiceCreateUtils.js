/**
 * InvoiceCreateUtils Module
 * Utility functions for Invoice Create page
 */
class InvoiceCreateUtils {
    constructor() {
        // No initialization needed
    }

    /**
     * Format currency to Indonesian Rupiah format
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
     * Parse currency string to number
     */
    parseCurrency(value) {
        if (!value) return 0;
        // Remove all non-digit characters
        // Format Indonesia: Rp30.000.000 (dot for thousand separator)
        // We need to remove all dots and other non-digit characters to get the actual number
        // Example: "Rp30.000.000" -> "30000000" -> 30000000
        const cleaned = value.toString().replace(/[^\d]/g, '');
        return parseFloat(cleaned) || 0;
    }

    /**
     * Format date to en-GB format (DD/MM/YYYY)
     */
    formatDate(date) {
        if (!date) return '';
        const d = new Date(date);
        if (isNaN(d.getTime())) return '';
        return d.toLocaleDateString('en-GB');
    }

    /**
     * Escape HTML to prevent XSS
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
    window.InvoiceCreateUtils = InvoiceCreateUtils;
}

