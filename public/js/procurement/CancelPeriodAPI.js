/**
 * CancelPeriodAPI Module
 * Handles all API calls for Cancel Period with caching to prevent duplicate API calls
 */
class CancelPeriodAPI {
    constructor() {
        // Use shared cache if available, otherwise create local cache
        this.sharedCache = window.procurementSharedCache || null;
        
        // Cache for API responses
        this.cache = new Map();
        this.cacheTimeout = 5 * 60 * 1000; // 5 minutes cache timeout
        
        // Local cache for purchase types and companies (fallback if shared cache not available)
        this.purchaseTypesCache = null;
        this.companiesCache = null;
        
        // Pending API calls to prevent duplicate requests
        this.pendingCalls = new Map();
    }

    /**
     * Get cached data or fetch from API
     */
    async getCachedData(cacheKey, fetchFunction) {
        // Check cache first
        const cached = this.cache.get(cacheKey);
        if (cached && Date.now() - cached.timestamp < this.cacheTimeout) {
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
                this.cache.set(cacheKey, {
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
     * Get PO list with filters
     */
    async getPOList(filters = {}) {
        const cacheKey = `poList_${JSON.stringify(filters)}`;
        return await this.getCachedData(cacheKey, async () => {
            const params = new URLSearchParams();
            if (filters.purchOrderID) params.append('purchOrderID', filters.purchOrderID);
            if (filters.requestNumber) params.append('requestNumber', filters.requestNumber);
            if (filters.purchaseTypeID) params.append('purchaseTypeID', filters.purchaseTypeID);
            if (filters.companyID) params.append('companyID', filters.companyID);
            if (filters.statusID) params.append('statusID', filters.statusID);
            if (filters.startDate) params.append('poStartDate', filters.startDate);
            if (filters.endDate) params.append('poEndDate', filters.endDate);

            const response = await apiCall('Procurement', `/Procurement/PurchaseOrder/PurchaseOrders?${params.toString()}`, 'GET');
            return Array.isArray(response) ? response : (response.data || response.Data || []);
        });
    }

    /**
     * Get PO details by PO Number (with shared caching)
     */
    async getPODetails(poNumber) {
        // Use shared cache if available
        if (this.sharedCache && this.sharedCache.getCachedData) {
            const cacheKey = `poDetails_${poNumber}`;
            return await this.sharedCache.getCachedData(cacheKey, async () => {
                const poData = await apiCall('Procurement', `/Procurement/PurchaseOrder/PurchaseOrders/${encodeURIComponent(poNumber)}`, 'GET');
                return poData.data || poData;
            });
        }
        
        // Fallback to local cache
        const cacheKey = `poDetails_${poNumber}`;
        return await this.getCachedData(cacheKey, async () => {
            const poData = await apiCall('Procurement', `/Procurement/PurchaseOrder/PurchaseOrders/${encodeURIComponent(poNumber)}`, 'GET');
            return poData.data || poData;
        });
    }

    /**
     * Get PO items by PO Number (with shared caching)
     * Uses the same endpoint as Invoice Create
     */
    async getPOItems(poNumber) {
        // Use shared cache if available
        if (this.sharedCache && this.sharedCache.getCachedData) {
            const cacheKey = `poItems_${poNumber}`;
            return await this.sharedCache.getCachedData(cacheKey, async () => {
                // Use the same endpoint as Invoice Create
                const encodedPONumber = encodeURIComponent(poNumber);
                const itemsData = await apiCall('Procurement', `/Procurement/PurchaseOrder/PurchaseOrders/${encodedPONumber}/items`, 'GET');
                return Array.isArray(itemsData) ? itemsData : (itemsData.data || itemsData.Data || []);
            });
        }
        
        // Fallback to local cache
        const cacheKey = `poItems_${poNumber}`;
        return await this.getCachedData(cacheKey, async () => {
            // Use the same endpoint as Invoice Create
            const encodedPONumber = encodeURIComponent(poNumber);
            const itemsData = await apiCall('Procurement', `/Procurement/PurchaseOrder/PurchaseOrders/${encodedPONumber}/items`, 'GET');
            return Array.isArray(itemsData) ? itemsData : (itemsData.data || itemsData.Data || []);
        });
    }

    /**
     * Get vendor information by Vendor ID (with shared caching)
     * Uses the same flow as Invoice Create: get vendor list, find by VendorID, then get detail
     */
    async getVendorDetails(vendorID) {
        // Use shared cache if available
        if (this.sharedCache && this.sharedCache.getCachedData) {
            const cacheKey = `vendorDetails_${vendorID}`;
            return await this.sharedCache.getCachedData(cacheKey, async () => {
                // First, get vendor list to find the ID from VendorID
                const listResponse = await apiCall('Procurement', '/Procurement/Master/Vendors?isActive=true', 'GET');
                const vendorList = Array.isArray(listResponse) ? listResponse : (listResponse.data || listResponse.Data || []);
                const vendor = vendorList.find(v => (v.vendorID || v.VendorID) === vendorID);
                
                if (vendor && (vendor.id || vendor.ID)) {
                    // Use existing GetById endpoint
                    const vendorId = vendor.id || vendor.ID;
                    const response = await apiCall('Procurement', `/Procurement/Master/Vendors/${vendorId}`, 'GET');
                    return response.data || response;
                } else {
                    throw new Error(`Vendor with VendorID '${vendorID}' not found`);
                }
            });
        }
        
        // Fallback to local cache
        const cacheKey = `vendorDetails_${vendorID}`;
        return await this.getCachedData(cacheKey, async () => {
            // First, get vendor list to find the ID from VendorID
            const listResponse = await apiCall('Procurement', '/Procurement/Master/Vendors?isActive=true', 'GET');
            const vendorList = Array.isArray(listResponse) ? listResponse : (listResponse.data || listResponse.Data || []);
            const vendor = vendorList.find(v => (v.vendorID || v.VendorID) === vendorID);
            
            if (vendor && (vendor.id || vendor.ID)) {
                // Use existing GetById endpoint
                const vendorId = vendor.id || vendor.ID;
                const response = await apiCall('Procurement', `/Procurement/Master/Vendors/${vendorId}`, 'GET');
                return response.data || response;
            } else {
                throw new Error(`Vendor with VendorID '${vendorID}' not found`);
            }
        });
    }

    /**
     * Get PR additional data by PR Number (for period of payment check)
     */
    async getPRAdditional(prNumber) {
        // Use shared cache if available
        if (this.sharedCache && this.sharedCache.getCachedData) {
            const cacheKey = `prAdditional_${prNumber}`;
            return await this.sharedCache.getCachedData(cacheKey, async () => {
                try {
                    const additionalData = await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequestAdditional/${encodeURIComponent(prNumber)}`, 'GET');
                    return additionalData.data || additionalData;
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
        
        // Fallback to local cache
        const cacheKey = `prAdditional_${prNumber}`;
        return await this.getCachedData(cacheKey, async () => {
            try {
                const additionalData = await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequestAdditional/${encodeURIComponent(prNumber)}`, 'GET');
                return additionalData.data || additionalData;
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
     * Get invoice details by PO Number (to check submitted terms/periods)
     */
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

    async getInvoiceDetails(poNumber) {
        const cacheKey = `invoiceDetails_${poNumber}`;
        return await this.getCachedData(cacheKey, async () => {
            try {
                const invoicesData = await apiCall('Procurement', `/Finance/Invoice/Invoices/Details?purchOrderID=${encodeURIComponent(poNumber)}`, 'GET');
                return Array.isArray(invoicesData) ? invoicesData : (invoicesData.data || invoicesData.Data || []);
            } catch (error) {
                // Return empty array if no invoices found
                return [];
            }
        });
    }

    /**
     * Get all purchase types (with shared caching)
     */
    async getPurchaseTypes() {
        // Use shared cache if available
        if (this.sharedCache && this.sharedCache.getPurchaseTypes) {
            return this.sharedCache.getPurchaseTypes();
        }
        
        // Fallback to local cache
        if (this.purchaseTypesCache) {
            return this.purchaseTypesCache;
        }

        const cacheKey = 'purchaseTypes_all';
        
        this.purchaseTypesCache = await this.getCachedData(cacheKey, async () => {
            const typesData = await apiCall('Procurement', '/Procurement/Master/PurchaseTypes/List?isActive=true', 'GET');
            return Array.isArray(typesData) ? typesData : (typesData?.data || []);
        });

        return this.purchaseTypesCache;
    }

    /**
     * Get purchase sub types for a specific type ID (with shared caching)
     */
    async getPurchaseSubTypes(typeId) {
        if (!typeId) return [];
        
        // Use shared cache if available
        if (this.sharedCache && this.sharedCache.getPurchaseSubTypes) {
            return this.sharedCache.getPurchaseSubTypes(typeId);
        }
        
        // Fallback: use local cache or fetch from API
        const cacheKey = `purchaseSubTypes_${typeId}`;
        
        return await this.getCachedData(cacheKey, async () => {
            const endpoint = `/Procurement/Master/PurchaseTypes/SubTypes?isActive=true&mstPROPurchaseTypeID=${encodeURIComponent(typeId)}`;
            const subTypesData = await apiCall('Procurement', endpoint, 'GET');
            return Array.isArray(subTypesData) ? subTypesData : (subTypesData?.data || []);
        });
    }

    /**
     * Get all companies (with shared caching)
     */
    async getCompanies() {
        // Use shared cache if available (master data should be shared)
        if (this.sharedCache && this.sharedCache.getCachedData) {
            const cacheKey = 'companies_all';
            return await this.sharedCache.getCachedData(cacheKey, async () => {
                const companiesData = await apiCall('Procurement', '/Procurement/Master/Companies?isActive=true', 'GET');
                return Array.isArray(companiesData) ? companiesData : (companiesData?.data || []);
            }, true); // Use masterDataCache (30 minutes timeout)
        }
        
        // Fallback to local cache
        if (this.companiesCache) {
            return this.companiesCache;
        }

        const cacheKey = 'companies_all';
        
        this.companiesCache = await this.getCachedData(cacheKey, async () => {
            const companiesData = await apiCall('Procurement', '/Procurement/Master/Companies?isActive=true', 'GET');
            return Array.isArray(companiesData) ? companiesData : (companiesData?.data || []);
        });

        return this.companiesCache;
    }

    /**
     * Submit cancel period/termin
     */
    async submitCancelPeriod(dto) {
        const endpoint = `/Procurement/PurchaseOrder/CancelPeriod/Cancel`;
        const response = await apiCall('Procurement', endpoint, 'POST', dto);
        return response.data || response;
    }

    /**
     * Clear cache for specific PO (when PO is updated)
     */
    clearPOCache(poNumber) {
        const keysToDelete = [];
        for (const key of this.cache.keys()) {
            if (key.includes(poNumber)) {
                keysToDelete.push(key);
            }
        }
        keysToDelete.forEach(key => this.cache.delete(key));
        
        // Also clear from shared cache if available
        if (this.sharedCache && this.sharedCache.clearCacheEntry) {
            this.sharedCache.clearCacheEntry(`poDetails_${poNumber}`);
            this.sharedCache.clearCacheEntry(`poItems_${poNumber}`);
            this.sharedCache.clearCacheEntry(`amortizations_${poNumber}`);
        }
        
        // Clear pending calls for this PO
        const pendingKeysToDelete = [];
        for (const key of this.pendingCalls.keys()) {
            if (key.includes(poNumber)) {
                pendingKeysToDelete.push(key);
            }
        }
        pendingKeysToDelete.forEach(key => this.pendingCalls.delete(key));
    }

    /**
     * Clear all cache
     */
    clearAllCache() {
        this.cache.clear();
        this.purchaseTypesCache = null;
        this.companiesCache = null;
        this.pendingCalls.clear();
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.CancelPeriodAPI = CancelPeriodAPI;
}

