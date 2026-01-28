/**
 * InvoiceListAPI Module
 * Handles all API calls for Invoice List with caching to prevent duplicate API calls
 */
class InvoiceListAPI {
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
     * Get Invoice list with filters
     */
    async getInvoiceList(filters) {
        const params = new URLSearchParams();
        
        if (filters.invoiceNumber) params.append('invoiceNumber', filters.invoiceNumber.trim());
        if (filters.requestNumber) params.append('requestNumber', filters.requestNumber.trim());
        if (filters.purchOrderID) params.append('purchOrderID', filters.purchOrderID.trim());
        if (filters.purchaseTypeID) params.append('purchaseTypeID', filters.purchaseTypeID);
        if (filters.purchaseSubTypeID) params.append('purchaseSubTypeID', filters.purchaseSubTypeID);
        if (filters.companyID) params.append('companyID', filters.companyID);
        if (filters.workTypeID) params.append('workTypeID', filters.workTypeID);
        if (filters.statusID) params.append('statusID', filters.statusID);
        if (filters.startDate) params.append('startDate', filters.startDate);
        if (filters.endDate) params.append('endDate', filters.endDate);

        const endpoint = `/Finance/Invoice/Invoices?${params.toString()}`;
        const cacheKey = `invoiceList_${params.toString()}`;

        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', endpoint, 'GET');
            return Array.isArray(response) ? response : (response.data || response.Data || []);
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
     * Get Purchase Sub Types by Purchase Type ID (uses shared cache if available)
     */
    async getPurchaseSubTypes(purchaseTypeID) {
        const cacheKey = `purchaseSubTypes_${purchaseTypeID}_active`;
        
        // Try shared cache first
        if (this.sharedCache && this.sharedCache.getPurchaseSubTypes) {
            return await this.sharedCache.getPurchaseSubTypes(purchaseTypeID);
        }
        
        // Fallback to local cache
        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', `/Procurement/Master/PurchaseTypes/SubTypes?isActive=true&mstPROPurchaseTypeID=${purchaseTypeID}`, 'GET');
            return Array.isArray(response) ? response : (response.data || response.Data || []);
        }, true); // Use master data cache
    }

    /**
     * Get Work Types
     */
    async getWorkTypes() {
        const cacheKey = 'workTypes_active';
        
        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', '/Finance/EBilling/InvoiceWorkTypes?isActive=true', 'GET');
            return Array.isArray(response) ? response : (response.data || response.Data || []);
        }, true); // Use master data cache
    }

    /**
     * Get Companies
     */
    async getCompanies() {
        const cacheKey = 'companies_active';
        
        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', '/Procurement/Master/Companies?isActive=true', 'GET');
            return Array.isArray(response) ? response : (response.data || response.Data || []);
        }, true); // Use master data cache
    }

    /**
     * Get Approval Statuses
     */
    async getApprovalStatuses() {
        const cacheKey = 'approvalStatuses_active';
        
        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', '/Procurement/Master/ApprovalStatuses?isActive=true', 'GET');
            return Array.isArray(response) ? response : (response.data || response.Data || []);
        }, true); // Use master data cache
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
}

// Make available globally
if (typeof window !== 'undefined') {
    window.InvoiceListAPI = InvoiceListAPI;
}

