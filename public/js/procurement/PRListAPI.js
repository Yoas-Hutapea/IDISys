/**
 * PRListAPI Module
 * Handles all API calls for PR List with caching to prevent duplicate API calls
 */
class PRListAPI {
    constructor() {
        // Use shared cache if available, otherwise create local cache
        this.sharedCache = window.procurementSharedCache || null;
        
        // Cache for API responses (for PR-specific data)
        this.cache = new Map();
        this.cacheTimeout = 5 * 60 * 1000; // 5 minutes cache timeout
        
        // Local cache for purchase types and sub types (fallback if shared cache not available)
        this.purchaseTypesCache = null;
        this.purchaseSubTypesCache = new Map(); // key: typeId, value: subTypes array
        
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
     * Get PR list with filters
     */
    async getPRList(filters) {
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
            if (!isNaN(statusId)) {
                queryParams.append('statusPR', statusId.toString());
            }
        }
        if (filters.region) {
            const regionId = parseInt(filters.region);
            if (!isNaN(regionId) && regionId > 0) {
                queryParams.append('regionID', regionId.toString());
            }
        }

        const endpoint = `/Procurement/PurchaseRequest/PurchaseRequests?${queryParams.toString()}`;
        const cacheKey = `prList_${queryParams.toString()}`;

        return await this.getCachedData(cacheKey, async () => {
            const response = await apiCall('Procurement', endpoint, 'GET');
            return response.data || response;
        });
    }

    /**
     * Get PR details by PR Number
     */
    async getPRDetails(prNumber) {
        const cacheKey = `prDetails_${prNumber}`;
        
        return await this.getCachedData(cacheKey, async () => {
            const prData = await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequests/${encodeURIComponent(prNumber)}`, 'GET');
            return prData.data || prData;
        });
    }

    /**
     * Get PR items by PR Number
     */
    async getPRItems(prNumber) {
        const cacheKey = `prItems_${prNumber}`;
        
        return await this.getCachedData(cacheKey, async () => {
            const itemsData = await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequestItems/${encodeURIComponent(prNumber)}/items`, 'GET');
            return itemsData.data || itemsData;
        });
    }

    /**
     * Get PR documents by PR Number
     */
    async getPRDocuments(prNumber) {
        const cacheKey = `prDocuments_${prNumber}`;
        
        return await this.getCachedData(cacheKey, async () => {
            const documentsData = await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequestDocuments/${encodeURIComponent(prNumber)}/documents`, 'GET');
            return documentsData.data || documentsData;
        });
    }

    /**
     * Get PR additional data by PR Number
     */
    async getPRAdditional(prNumber) {
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
     * Get purchase sub types by type ID (with shared caching)
     */
    async getPurchaseSubTypes(typeId) {
        // Use shared cache if available
        if (this.sharedCache && this.sharedCache.getPurchaseSubTypes) {
            return this.sharedCache.getPurchaseSubTypes(typeId);
        }
        
        // Fallback to local cache
        if (this.purchaseSubTypesCache.has(typeId)) {
            return this.purchaseSubTypesCache.get(typeId);
        }

        const cacheKey = `purchaseSubTypes_${typeId}`;
        
        const subTypes = await this.getCachedData(cacheKey, async () => {
            const subTypesData = await apiCall('Procurement', `/Procurement/Master/PurchaseTypes/SubTypes?isActive=true&mstPROPurchaseTypeID=${typeId}`, 'GET');
            return Array.isArray(subTypesData) ? subTypesData : (subTypesData?.data || []);
        });

        this.purchaseSubTypesCache.set(typeId, subTypes);
        return subTypes;
    }

    /**
     * Clear cache for specific PR (when PR is edited/updated)
     */
    clearPRCache(prNumber) {
        const keysToDelete = [];
        for (const key of this.cache.keys()) {
            if (key.includes(prNumber)) {
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
        this.purchaseTypesCache = null;
        this.purchaseSubTypesCache.clear();
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.PRListAPI = PRListAPI;
}

