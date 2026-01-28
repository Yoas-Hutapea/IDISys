/**
 * PostingListAPI Module
 * Handles all API calls for Posting List with caching to prevent duplicate API calls
 */
class PostingListAPI {
    constructor() {
        // Use shared cache if available (from procurement modules)
        this.sharedCache = window.procurementSharedCache || null;
        
        // Cache for API responses
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
     * Get Invoice list for posting (filtered by status 13 and isPosting = false)
     */
    async getPostingInvoiceList(filters) {
        const params = new URLSearchParams();
        
        if (filters.invoiceNumber) params.append('invoiceNumber', filters.invoiceNumber.trim());
        if (filters.requestNumber) params.append('requestNumber', filters.requestNumber.trim());
        if (filters.purchOrderID) params.append('purchOrderID', filters.purchOrderID.trim());
        if (filters.companyID) params.append('companyID', filters.companyID);
        if (filters.workTypeID) params.append('workTypeID', filters.workTypeID);
        if (filters.statusID) params.append('statusID', filters.statusID || '13'); // Default to status 13
        if (filters.startDate) params.append('startDate', filters.startDate);
        if (filters.endDate) params.append('endDate', filters.endDate);

        const endpoint = `/Finance/Invoice/Invoices?${params.toString()}`;
        const cacheKey = `postingInvoiceList_${params.toString()}`;

        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', endpoint, 'GET');
            const rawData = Array.isArray(response) ? response : (response.data || response.Data || []);
            
            // Filter by isPosting = false (only show invoices that haven't been posted)
            return rawData.filter(item => {
                const isPosting = item.isPosting || item.IsPosting || false;
                return !isPosting;
            });
        });
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
        const cacheKey = 'purchaseSubTypes_active';
        
        // Try shared cache first
        if (this.sharedCache && this.sharedCache.getPurchaseSubTypes) {
            // Get all sub types (without filter)
            return await this.sharedCache.getPurchaseSubTypes(null);
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
    async getInvoiceDetails(poNumber) {
        const cacheKey = `invoiceDetails_${poNumber}`;
        
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
     * Get Vendors list
     */
    async getVendors() {
        const cacheKey = 'vendors_active';
        
        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', '/Procurement/Master/Vendors?isActive=true', 'GET');
            return Array.isArray(response) ? response : (response.data || response.Data || []);
        }, true); // Use master data cache
    }

    /**
     * Get Vendor by ID
     */
    async getVendorById(vendorId) {
        const cacheKey = `vendor_${vendorId}`;
        
        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', `/Procurement/Master/Vendors/${vendorId}`, 'GET');
            return response.data || response;
        });
    }

    /**
     * Get PR Additional by PR Number
     */
    async getPRAdditional(prNumber) {
        const cacheKey = `prAdditional_${prNumber}`;
        
        return await this.getCachedData(cacheKey, async () => {
            const encodedPRNumber = encodeURIComponent(prNumber);
            const response = await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequestAdditional/${encodedPRNumber}`, 'GET');
            return response.data || response;
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

    /**
     * Post invoice
     */
    async postInvoice(invoiceId) {
        return await apiCall('Procurement', `/Finance/Invoice/Invoices/${invoiceId}/Post`, 'POST');
    }

    /**
     * Reject invoice
     */
    async rejectInvoice(invoiceId) {
        return await apiCall('Procurement', `/Finance/Invoice/Invoices/${invoiceId}/Reject`, 'POST');
    }

    /**
     * Bulk post invoices
     */
    async bulkPostInvoices(invoiceIDs, remarks) {
        const dto = {
            InvoiceHeaderIDs: invoiceIDs,
            Remark: remarks
        };
        return await apiCall('Procurement', '/Finance/Invoice/Invoices/BulkPost', 'POST', dto);
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.PostingListAPI = PostingListAPI;
}

