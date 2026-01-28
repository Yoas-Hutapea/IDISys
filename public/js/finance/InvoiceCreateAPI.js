/**
 * InvoiceCreateAPI Module
 * Handles all API calls for Invoice Create with caching to prevent duplicate API calls
 */
class InvoiceCreateAPI {
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
     * Get PO list for selection (with status ID = 11)
     */
    async getPOList() {
        const cacheKey = 'poList_status11';
        
        return await this.getCachedData(cacheKey, async () => {
            const gridRequest = {
                mstApprovalStatusID: 11, // Filter: only PO with approval status ID = 11
                Start: 0,
                Length: 0 // Get all records (0 = no pagination limit)
            };
            
            const response = await apiCall('Procurement', '/Procurement/PurchaseOrder/PurchaseOrders/Grid', 'POST', gridRequest);
            const responseData = response.data || response;
            return Array.isArray(responseData) 
                ? responseData 
                : (responseData.data && Array.isArray(responseData.data) 
                    ? responseData.data 
                    : []);
        });
    }

    /**
     * Get PO details by PO Number
     */
    async getPODetails(poNumber) {
        const cacheKey = `poDetails_${poNumber}`;
        
        return await this.getCachedData(cacheKey, async () => {
            const encodedPONumber = encodeURIComponent(poNumber);
            const response = await apiCall('Procurement', `/Procurement/PurchaseOrder/PurchaseOrders/${encodedPONumber}`, 'GET');
            return response.data || response;
        });
    }

    /**
     * Get PO items by PO Number
     */
    async getPOItems(poNumber) {
        const cacheKey = `poItems_${poNumber}`;
        
        return await this.getCachedData(cacheKey, async () => {
            const encodedPONumber = encodeURIComponent(poNumber);
            const response = await apiCall('Procurement', `/Procurement/PurchaseOrder/PurchaseOrders/${encodedPONumber}/items`, 'GET');
            return Array.isArray(response) ? response : (response.data || response.Data || []);
        });
    }

    /**
     * Get Tax list
     */
    async getTaxList() {
        const cacheKey = 'taxList_active';
        
        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', '/Finance/Master/Taxes?isActive=true', 'GET');
            return Array.isArray(response) ? response : (response.data || response.Data || []);
        }, true); // Use master data cache
    }

    /**
     * Get Vendor details by Vendor ID
     */
    async getVendorDetails(vendorID) {
        const cacheKey = `vendorDetails_${vendorID}`;
        
        return await this.getCachedData(cacheKey, async () => {
            // First, get vendor list to find the ID from VendorID
            const listResponse = await apiCall('Procurement', '/Procurement/Master/Vendors?isActive=true', 'GET');
            const vendorList = Array.isArray(listResponse) ? listResponse : (listResponse.data || listResponse.Data || []);
            const vendor = vendorList.find(v => (v.vendorID || v.VendorID) === vendorID);
            
            if (vendor && (vendor.id || vendor.ID)) {
                const vendorId = vendor.id || vendor.ID;
                const response = await apiCall('Procurement', `/Procurement/Master/Vendors/${vendorId}`, 'GET');
                return response.data || response;
            }
            throw new Error(`Vendor with VendorID '${vendorID}' not found`);
        });
    }

    /**
     * Get Purchase Types (with shared caching if available)
     */
    async getPurchaseTypes() {
        // Use shared cache if available
        if (this.sharedCache && this.sharedCache.getPurchaseTypes) {
            return this.sharedCache.getPurchaseTypes();
        }
        
        const cacheKey = 'purchaseTypes_all';
        
        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', '/Procurement/Master/PurchaseTypes/List?isActive=true', 'GET');
            return Array.isArray(response) ? response : (response.data || response.Data || []);
        }, true); // Use master data cache
    }

    /**
     * Get Purchase Sub Types by Type ID (with shared caching if available)
     */
    async getPurchaseSubTypes(typeId) {
        if (!typeId) return [];
        
        // Use shared cache if available
        if (this.sharedCache && this.sharedCache.getPurchaseSubTypes) {
            return this.sharedCache.getPurchaseSubTypes(typeId);
        }
        
        const cacheKey = `purchaseSubTypes_${typeId}`;
        
        return await this.getCachedData(cacheKey, async () => {
            const endpoint = typeId 
                ? `/Procurement/Master/PurchaseTypes/SubTypes?mstPROPurchaseTypeID=${typeId}&isActive=true`
                : '/Procurement/Master/PurchaseTypes/SubTypes?isActive=true';
            const response = await apiCall('Procurement', endpoint, 'GET');
            return Array.isArray(response) ? response : (response.data || response.Data || []);
        }, true); // Use master data cache
    }

    /**
     * Get WorkType PurchaseType mappings
     */
    async getWorkTypeMappings() {
        const cacheKey = 'workTypeMappings_all';
        
        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', '/Finance/EBilling/InvoiceWorkTypePurchaseTypes?isActive=true', 'GET');
            return Array.isArray(response) ? response : (response.data || response.Data || []);
        }, true); // Use master data cache
    }

    /**
     * Get PurchaseRequestAdditional by PR Number
     */
    async getPRAdditional(prNumber) {
        const cacheKey = `prAdditional_${prNumber}`;
        
        return await this.getCachedData(cacheKey, async () => {
            try {
                const encodedPRNumber = encodeURIComponent(prNumber);
                const response = await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequestAdditional/${encodedPRNumber}`, 'GET');
                return response.data || response;
            } catch (error) {
                // 404 is expected if additional data doesn't exist
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
     * Get STIP Sites by sonumb
     */
    async getSTIPSites(sonumb) {
        const cacheKey = `stipSites_${sonumb}`;
        
        return await this.getCachedData(cacheKey, async () => {
            const queryParams = new URLSearchParams();
            queryParams.append('sonumb', sonumb);
            
            const response = await apiCall('Procurement', `/Procurement/Master/STIPSites?${queryParams.toString()}`, 'GET');
            return Array.isArray(response) ? response : (response.data || response.Data || []);
        });
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
     * Get existing invoices for PO Number (Details endpoint for term positions)
     * This method uses the same endpoint as getInvoiceDetails but with different cache key
     * to allow separate caching if needed
     */
    async getExistingInvoices(poNumber) {
        const cacheKey = `existingInvoices_${poNumber}`;
        
        return await this.getCachedData(cacheKey, async () => {
            const encodedPONumber = encodeURIComponent(poNumber);
            // Use Details endpoint to get all detail records (trxFINInvoice) not just header
            const response = await apiCall('Procurement', `/Finance/Invoice/Invoices/Details?purchOrderID=${encodedPONumber}`, 'GET');
            return Array.isArray(response) ? response : (response.data || response.Data || []);
        });
    }

    /**
     * Get invoice details (for checking term positions)
     * Alias for getExistingInvoices - uses same endpoint but different cache key
     */
    async getInvoiceDetails(poNumber) {
        // Use same endpoint as getExistingInvoices but with different cache key
        // This allows both methods to share the same data but with separate cache entries
        return await this.getExistingInvoices(poNumber);
    }

    /**
     * Get amortizations for a Purchase Order
     */
    async getAmortizations(poNumber) {
        const cacheKey = `amortizations_${poNumber}`;
        
        return await this.getCachedData(cacheKey, async () => {
            const endpoint = `/Procurement/PurchaseOrder/CancelPeriod/${encodeURIComponent(poNumber)}/Amortizations`;
            const response = await apiCall('Procurement', endpoint, 'GET');
            return response.data || response;
        });
    }

    /**
     * Get Invoice by ID
     */
    async getInvoiceById(invoiceId) {
        const cacheKey = `invoice_${invoiceId}`;
        
        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', `/Finance/Invoice/Invoices/${invoiceId}`, 'GET');
            return response.data || response;
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
     * Get Document Checklist mappings
     */
    async getDocumentChecklistMappings() {
        const cacheKey = 'documentChecklistMappings_all';
        
        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', '/Finance/EBilling/InvoiceWorkTypeTOPDocumentChecklists?isActive=true', 'GET');
            return Array.isArray(response) ? response : (response.data || response.Data || []);
        }, true); // Use master data cache
    }

    /**
     * Get Master Document Checklists
     */
    async getMasterDocumentChecklists() {
        const cacheKey = 'masterDocumentChecklists_all';
        
        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', '/Finance/EBilling/InvoiceDocumentChecklists?isActive=true', 'GET');
            return Array.isArray(response) ? response : (response.data || response.Data || []);
        }, true); // Use master data cache
    }

    /**
     * Save Invoice (POST)
     */
    async saveInvoice(formData) {
        // Don't cache save operations
        const response = await apiCall('Procurement', '/Finance/Invoice/Invoices', 'POST', formData);
        return response.data || response;
    }

    /**
     * Update Invoice (PUT)
     */
    async updateInvoice(invoiceId, formData) {
        // Don't cache update operations
        const response = await apiCall('Procurement', `/Finance/Invoice/Invoices/${invoiceId}`, 'PUT', formData);
        return response.data || response;
    }

    /**
     * Clear cache for specific PO (when PO is changed)
     */
    clearPOCache(poNumber) {
        const keysToDelete = [];
        for (const key of this.cache.keys()) {
            if (key.includes(poNumber)) {
                keysToDelete.push(key);
            }
        }
        keysToDelete.forEach(key => this.cache.delete(key));
    }

    /**
     * Clear all cache
     */
    clearAllCache() {
        this.cache.clear();
        this.masterDataCache.clear();
        this.pendingCalls.clear();
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.InvoiceCreateAPI = InvoiceCreateAPI;
}

