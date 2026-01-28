/**
 * InvoiceDetailAPI Module
 * Handles all API calls for Invoice Detail page with caching to prevent duplicate API calls
 */
class InvoiceDetailAPI {
    constructor() {
        // Use shared cache if available (from procurement modules)
        this.sharedCache = window.procurementSharedCache || null;
        
        // Cache for API responses (for Invoice-specific data)
        this.cache = new Map();
        this.cacheTimeout = 5 * 60 * 1000; // 5 minutes cache timeout
        
        // Master data cache (longer timeout - 30 minutes)
        this.masterDataCache = new Map();
        this.masterDataCacheTimeout = 30 * 60 * 1000; // 30 minutes
        
        // Pending API calls to prevent duplicate requests
        this.pendingCalls = new Map();
    }

    /**
     * Get cached data or fetch from API
     */
    async getCachedData(cacheKey, fetchFunction, useMasterDataCache = false) {
        const cache = useMasterDataCache ? this.masterDataCache : this.cache;
        const timeout = useMasterDataCache ? this.masterDataCacheTimeout : this.cacheTimeout;
        
        // Check cache first
        const cached = cache.get(cacheKey);
        if (cached && Date.now() - cached.timestamp < timeout) {
            return cached.data;
        }

        // Check if request is already pending
        if (this.pendingCalls.has(cacheKey)) {
            return await this.pendingCalls.get(cacheKey);
        }

        // Create promise for this request
        const requestPromise = (async () => {
            try {
                const data = await fetchFunction();
                // Cache the result
                cache.set(cacheKey, {
                    data: data,
                    timestamp: Date.now()
                });
                return data;
            } catch (error) {
                console.error(`Error fetching data for ${cacheKey}:`, error);
                throw error;
            } finally {
                // Remove from pending calls
                this.pendingCalls.delete(cacheKey);
            }
        })();

        // Store pending request
        this.pendingCalls.set(cacheKey, requestPromise);

        return await requestPromise;
    }

    /**
     * Get Invoice details by ID
     */
    async getInvoiceDetails(invoiceId) {
        const cacheKey = `invoiceDetails_${invoiceId}`;
        
        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', `/Finance/Invoice/Invoices/${invoiceId}`, 'GET');
            return response.data || response;
        });
    }

    /**
     * Get Invoice items by Invoice ID
     */
    async getInvoiceItems(invoiceId) {
        const cacheKey = `invoiceItems_${invoiceId}`;
        
        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', `/Finance/Invoice/Invoices/${invoiceId}/Items`, 'GET');
            return Array.isArray(response) ? response : (response.data || response.Data || []);
        });
    }

    /**
     * Get Invoice documents by Invoice ID
     */
    async getInvoiceDocuments(invoiceId) {
        const cacheKey = `invoiceDocuments_${invoiceId}`;
        
        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', `/Finance/Invoice/Invoices/${invoiceId}/Documents`, 'GET');
            return Array.isArray(response) ? response : (response.data || response.Data || []);
        });
    }

    /**
     * Get Invoice details by PO Number
     */
    async getInvoiceDetailsByPO(poNumber) {
        const cacheKey = `invoiceDetailsByPO_${poNumber}`;
        
        return await this.getCachedData(cacheKey, async () => {
            const encodedPONumber = encodeURIComponent(poNumber);
            const response = await apiCall('Procurement', `/Finance/Invoice/Invoices/Details?purchOrderID=${encodedPONumber}`, 'GET');
            return Array.isArray(response) ? response : (response.data || response.Data || []);
        });
    }

    /**
     * Get PO details by PO Number (uses shared cache if available)
     */
    async getPODetails(poNumber) {
        const cacheKey = `poDetails_${poNumber}`;
        
        // Try shared cache first
        if (this.sharedCache && this.sharedCache.getPODetails) {
            return await this.sharedCache.getPODetails(poNumber);
        }
        
        // Fallback to local cache
        return await this.getCachedData(cacheKey, async () => {
            const encodedPONumber = encodeURIComponent(poNumber);
            const response = await apiCall('Procurement', `/Procurement/PurchaseOrder/PurchaseOrders/${encodedPONumber}`, 'GET');
            return response.data || response;
        });
    }

    /**
     * Get PO items by PO Number (uses shared cache if available)
     */
    async getPOItems(poNumber) {
        const cacheKey = `poItems_${poNumber}`;
        
        // Try shared cache first
        if (this.sharedCache && this.sharedCache.getPOItems) {
            return await this.sharedCache.getPOItems(poNumber);
        }
        
        // Fallback to local cache
        return await this.getCachedData(cacheKey, async () => {
            const encodedPONumber = encodeURIComponent(poNumber);
            const response = await apiCall('Procurement', `/Procurement/PurchaseOrder/PurchaseOrders/${encodedPONumber}/items`, 'GET');
            return Array.isArray(response) ? response : (response.data || response.Data || []);
        });
    }

    /**
     * Get Vendor list (uses shared cache if available)
     */
    async getVendors() {
        const cacheKey = 'vendors_active';
        
        // Try shared cache first
        if (this.sharedCache && this.sharedCache.getVendors) {
            return await this.sharedCache.getVendors();
        }
        
        // Fallback to local cache
        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', '/Procurement/Master/Vendors?isActive=true', 'GET');
            return Array.isArray(response) ? response : (response.data || response.Data || []);
        }, true); // Use master data cache
    }

    /**
     * Get Vendor details by Vendor ID (uses shared cache if available)
     * Note: vendorID from PO is usually VendorID (string), not the actual ID (number)
     * We need to lookup from vendor list first to get the correct ID
     */
    async getVendorDetails(vendorID) {
        const cacheKey = `vendorDetails_${vendorID}`;
        
        // Try shared cache first
        if (this.sharedCache && this.sharedCache.getVendorDetails) {
            return await this.sharedCache.getVendorDetails(vendorID);
        }
        
        // Fallback to local cache
        return await this.getCachedData(cacheKey, async () => {
            // Always lookup from vendor list first to get the correct ID
            // This prevents calling API with vendorID (string) when we should use id (number)
            const vendors = await this.getVendors();
            const vendor = vendors.find(v => {
                const vID = v.vendorID || v.VendorID;
                // Compare as strings to handle both string and number vendorID
                return String(vID) === String(vendorID);
            });
            
            if (vendor && (vendor.id || vendor.ID)) {
                const vendorId = vendor.id || vendor.ID;
                const response = await apiCall('Procurement', `/Procurement/Master/Vendors/${vendorId}`, 'GET');
                return response.data || response;
            }
            
            // If not found in list, try direct call as fallback (in case vendorID is actually the ID)
            try {
                const response = await apiCall('Procurement', `/Procurement/Master/Vendors/${vendorID}`, 'GET');
                return response.data || response;
            } catch (error) {
                throw new Error(`Vendor with VendorID '${vendorID}' not found`);
            }
        });
    }

    /**
     * Get PR Additional data by PR Number
     */
    async getPRAdditional(prNumber) {
        const cacheKey = `prAdditional_${prNumber}`;
        
        return await this.getCachedData(cacheKey, async () => {
            try {
                const encodedPRNumber = encodeURIComponent(prNumber);
                const response = await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequestAdditional/${encodedPRNumber}`, 'GET');
                return response.data || response;
            } catch (error) {
                // If 404 error, return null (PR Additional doesn't exist for this PR)
                const errorMessage = error.message || error.toString() || '';
                if (errorMessage.includes('not found') || errorMessage.includes('404') || 
                    (error.statusCode === 404) || (error.response && error.response.status === 404)) {
                    return null;
                }
                throw error;
            }
        });
    }

    /**
     * Get Purchase Types (uses shared cache if available)
     */
    async getPurchaseTypes() {
        const cacheKey = 'purchaseTypes_active';
        
        // Try shared cache first
        if (this.sharedCache && this.sharedCache.getPurchaseTypes) {
            return await this.sharedCache.getPurchaseTypes();
        }
        
        // Fallback to local cache
        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', '/Procurement/Master/PurchaseTypes/List?isActive=true', 'GET');
            return Array.isArray(response) ? response : (response.data || response.Data || []);
        }, true); // Use master data cache
    }

    /**
     * Get Purchase Sub Types (uses shared cache if available)
     */
    async getPurchaseSubTypes() {
        const cacheKey = 'purchaseSubTypes_all_active';
        
        // Try shared cache first
        if (this.sharedCache && this.sharedCache.getPurchaseSubTypes) {
            // Note: Shared cache might require typeID, so we'll use local cache for all sub types
        }
        
        // Fallback to local cache
        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', '/Procurement/Master/PurchaseTypes/SubTypes?isActive=true', 'GET');
            return Array.isArray(response) ? response : (response.data || response.Data || []);
        }, true); // Use master data cache
    }

    /**
     * Get Taxes
     */
    async getTaxes() {
        const cacheKey = 'taxes_active';
        
        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', '/Finance/Master/Taxes?isActive=true', 'GET');
            return Array.isArray(response) ? response : (response.data || response.Data || []);
        }, true); // Use master data cache
    }

    /**
     * Get Billing Types
     */
    async getBillingTypes() {
        const cacheKey = 'billingTypes_active';
        
        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', '/Procurement/Master/BillingTypes?isActive=true', 'GET');
            return Array.isArray(response) ? response : (response.data || response.Data || []);
        }, true); // Use master data cache
    }

    /**
     * Get Invoice Work Type TOP Document Checklists
     */
    async getInvoiceWorkTypeTOPDocumentChecklists() {
        const cacheKey = 'invoiceWorkTypeTOPDocumentChecklists_active';
        
        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', '/Finance/EBilling/InvoiceWorkTypeTOPDocumentChecklists?isActive=true', 'GET');
            return Array.isArray(response) ? response : (response.data || response.Data || []);
        }, true); // Use master data cache
    }

    /**
     * Get Invoice Document Checklists
     */
    async getInvoiceDocumentChecklists() {
        const cacheKey = 'invoiceDocumentChecklists_active';
        
        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', '/Finance/EBilling/InvoiceDocumentChecklists?isActive=true', 'GET');
            return Array.isArray(response) ? response : (response.data || response.Data || []);
        }, true); // Use master data cache
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.InvoiceDetailAPI = InvoiceDetailAPI;
}

