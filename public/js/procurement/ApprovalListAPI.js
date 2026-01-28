/**
 * ApprovalListAPI Module
 * Handles all API calls for Approval List with caching to prevent duplicate API calls
 */
class ApprovalListAPI {
    constructor() {
        // Use shared cache if available, otherwise create local cache
        this.sharedCache = window.procurementSharedCache || null;
        
        // Cache for API responses (for approval-specific data)
        this.cache = new Map();
        this.cacheTimeout = 5 * 60 * 1000; // 5 minutes cache timeout
        
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
     * Get approval list with filters
     */
    async getApprovalList(filters) {
        const queryParams = new URLSearchParams();
        
        if (filters.startDate) queryParams.append('fromDate', filters.startDate);
        if (filters.endDate) queryParams.append('toDate', filters.endDate);
        if (filters.requestNumber) queryParams.append('requestNumber', filters.requestNumber.trim());
        if (filters.prNumber) queryParams.append('purchReqNum', filters.prNumber.trim());
        if (filters.prType) queryParams.append('purchReqType', filters.prType.trim());
        if (filters.prSubType) queryParams.append('purchReqSubType', filters.prSubType.trim());
        if (filters.prName) queryParams.append('purchReqName', filters.prName.trim());
        if (filters.statusPR) {
            const statusId = parseInt(filters.statusPR);
            if (!isNaN(statusId) && statusId > 0) {
                queryParams.append('statusPR', statusId.toString());
            }
        }
        if (filters.source) queryParams.append('source', filters.source.trim().toUpperCase());

        const endpoint = `/Procurement/PurchaseRequest/PurchaseRequestApprovals?${queryParams.toString()}`;
        const cacheKey = `approvalList_${queryParams.toString()}`;

        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', endpoint, 'GET');
            return response.data || response;
        });
    }

    /**
     * Get PR details by PR Number (with shared cache via PRListAPI if available)
     */
    async getPRDetails(prNumber) {
        // Try to use PRListAPI if available (shared cache)
        if (window.prListManager && window.prListManager.apiModule && window.prListManager.apiModule.getPRDetails) {
            return window.prListManager.apiModule.getPRDetails(prNumber);
        }
        
        // Fallback to local cache
        const cacheKey = `prDetails_${prNumber}`;
        
        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequests/${encodeURIComponent(prNumber)}`, 'GET');
            return response.data || response;
        });
    }

    /**
     * Get PR items by PR Number (with shared cache via PRListAPI if available)
     */
    async getPRItems(prNumber) {
        // Try to use PRListAPI if available (shared cache)
        if (window.prListManager && window.prListManager.apiModule && window.prListManager.apiModule.getPRItems) {
            return window.prListManager.apiModule.getPRItems(prNumber);
        }
        
        // Fallback to local cache
        const cacheKey = `prItems_${prNumber}`;
        
        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequestItems/${encodeURIComponent(prNumber)}/items`, 'GET');
            return response.data || response;
        });
    }

    /**
     * Get PR documents by PR Number (with shared cache via PRListAPI if available)
     */
    async getPRDocuments(prNumber) {
        // Try to use PRListAPI if available (shared cache)
        if (window.prListManager && window.prListManager.apiModule && window.prListManager.apiModule.getPRDocuments) {
            return window.prListManager.apiModule.getPRDocuments(prNumber);
        }
        
        // Fallback to local cache
        const cacheKey = `prDocuments_${prNumber}`;
        
        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequestDocuments/${encodeURIComponent(prNumber)}/documents`, 'GET');
            return response.data || response;
        });
    }

    /**
     * Get PR additional data by PR Number (with shared cache via PRListAPI if available)
     */
    async getPRAdditional(prNumber) {
        // Try to use PRListAPI if available (shared cache)
        if (window.prListManager && window.prListManager.apiModule && window.prListManager.apiModule.getPRAdditional) {
            return window.prListManager.apiModule.getPRAdditional(prNumber);
        }
        
        // Fallback to local cache
        const cacheKey = `prAdditional_${prNumber}`;
        
        return await this.getCachedData(cacheKey, async () => {
            try {
                const response = await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequestAdditional/${encodeURIComponent(prNumber)}`, 'GET');
                return response.data || response;
            } catch (error) {
                // If 404 error, return null (additional data doesn't exist for this PR)
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
     * Get all purchase types (with shared caching)
     */
    async getPurchaseTypes() {
        // Use shared cache if available
        if (this.sharedCache && this.sharedCache.getPurchaseTypes) {
            return this.sharedCache.getPurchaseTypes();
        }
        
        // Fallback to PRListAPI if available
        if (window.prListManager && window.prListManager.apiModule && window.prListManager.apiModule.getPurchaseTypes) {
            return window.prListManager.apiModule.getPurchaseTypes();
        }
        
        // Fallback to direct API call
        const cacheKey = 'purchaseTypes_all';
        
        return await this.getCachedData(cacheKey, async () => {
            const typesData = await apiCall('Procurement', '/Procurement/Master/PurchaseTypes/List?isActive=true', 'GET');
            return Array.isArray(typesData) ? typesData : (typesData?.data || []);
        });
    }

    /**
     * Get purchase sub types by type ID (with shared caching)
     */
    async getPurchaseSubTypes(typeId) {
        // Use shared cache if available
        if (this.sharedCache && this.sharedCache.getPurchaseSubTypes) {
            return this.sharedCache.getPurchaseSubTypes(typeId);
        }
        
        // Fallback to PRListAPI if available
        if (window.prListManager && window.prListManager.apiModule && window.prListManager.apiModule.getPurchaseSubTypes) {
            return window.prListManager.apiModule.getPurchaseSubTypes(typeId);
        }
        
        // Fallback to direct API call
        const cacheKey = `purchaseSubTypes_${typeId}`;
        
        return await this.getCachedData(cacheKey, async () => {
            const subTypesData = await apiCall('Procurement', `/Procurement/Master/PurchaseTypes/SubTypes?isActive=true&mstPROPurchaseTypeID=${typeId}`, 'GET');
            return Array.isArray(subTypesData) ? subTypesData : (subTypesData?.data || []);
        });
    }

    /**
     * Submit approval action
     */
    async submitApproval(prNumber, dto) {
        const endpoint = `/Procurement/PurchaseRequest/PurchaseRequestApprovals/${encodeURIComponent(prNumber)}`;
        
        // Don't cache POST requests
        const response = await apiCall('Procurement', endpoint, 'POST', dto);
        
        // Clear cache for this PR after approval
        this.clearPRCache(prNumber);
        
        return response.data || response;
    }

    /**
     * Clear cache for specific PR (when PR is approved/updated)
     */
    clearPRCache(prNumber) {
        const keysToDelete = [];
        for (const key of this.cache.keys()) {
            if (key.includes(prNumber)) {
                keysToDelete.push(key);
            }
        }
        keysToDelete.forEach(key => this.cache.delete(key));
        
        // Also clear from shared cache if available
        if (window.prListManager && window.prListManager.apiModule && window.prListManager.apiModule.clearPRCache) {
            window.prListManager.apiModule.clearPRCache(prNumber);
        }
    }

    /**
     * Clear all cache
     */
    clearAllCache() {
        this.cache.clear();
        this.pendingCalls.clear();
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.ApprovalListAPI = ApprovalListAPI;
}

