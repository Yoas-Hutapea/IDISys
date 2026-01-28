/**
 * ReceiveListAPI Module
 * Handles all API calls for Receive List with caching to prevent duplicate API calls
 */
class ReceiveListAPI {
    constructor() {
        // Use shared cache if available, otherwise create local cache
        this.sharedCache = window.procurementSharedCache || null;
        
        // Cache for API responses (for Receive-specific data)
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
     * Get PR details by PR Number (with shared caching)
     */
    async getPRDetails(prNumber) {
        // Use shared cache if available
        if (this.sharedCache && this.sharedCache.getCachedData) {
            const cacheKey = `prDetails_${prNumber}`;
            return await this.sharedCache.getCachedData(cacheKey, async () => {
                const prData = await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequests/${encodeURIComponent(prNumber)}`, 'GET');
                return prData.data || prData;
            });
        }
        
        // Fallback to local cache
        const cacheKey = `prDetails_${prNumber}`;
        return await this.getCachedData(cacheKey, async () => {
            const prData = await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequests/${encodeURIComponent(prNumber)}`, 'GET');
            return prData.data || prData;
        });
    }

    /**
     * Get PR items by PR Number (with shared caching)
     */
    async getPRItems(prNumber) {
        // Use shared cache if available
        if (this.sharedCache && this.sharedCache.getCachedData) {
            const cacheKey = `prItems_${prNumber}`;
            return await this.sharedCache.getCachedData(cacheKey, async () => {
                const itemsData = await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequestItems/${encodeURIComponent(prNumber)}/items`, 'GET');
                return itemsData.data || itemsData;
            });
        }
        
        // Fallback to local cache
        const cacheKey = `prItems_${prNumber}`;
        return await this.getCachedData(cacheKey, async () => {
            const itemsData = await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequestItems/${encodeURIComponent(prNumber)}/items`, 'GET');
            return itemsData.data || itemsData;
        });
    }

    /**
     * Get PR documents by PR Number (with shared caching)
     */
    async getPRDocuments(prNumber) {
        // Use shared cache if available
        if (this.sharedCache && this.sharedCache.getCachedData) {
            const cacheKey = `prDocuments_${prNumber}`;
            return await this.sharedCache.getCachedData(cacheKey, async () => {
                const documentsData = await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequestDocuments/${encodeURIComponent(prNumber)}/documents`, 'GET');
                return documentsData.data || documentsData;
            });
        }
        
        // Fallback to local cache
        const cacheKey = `prDocuments_${prNumber}`;
        return await this.getCachedData(cacheKey, async () => {
            const documentsData = await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequestDocuments/${encodeURIComponent(prNumber)}/documents`, 'GET');
            return documentsData.data || documentsData;
        });
    }

    /**
     * Get PR additional data by PR Number (with shared caching)
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
     * Submit receive (single PR)
     */
    async submitReceive(prNumber, dto) {
        const endpoint = `/Procurement/PurchaseRequest/PurchaseRequestReceives/${encodeURIComponent(prNumber)}`;
        return await apiCall('Procurement', endpoint, 'POST', dto);
    }

    /**
     * Submit bulk receive
     */
    async submitBulkReceive(dto) {
        const endpoint = `/Procurement/PurchaseRequest/PurchaseRequestReceives/Bulk`;
        return await apiCall('Procurement', endpoint, 'POST', dto);
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
        
        // Also clear from shared cache if available
        if (this.sharedCache && this.sharedCache.clearCacheEntry) {
            this.sharedCache.clearCacheEntry(`prDetails_${prNumber}`);
            this.sharedCache.clearCacheEntry(`prItems_${prNumber}`);
            this.sharedCache.clearCacheEntry(`prDocuments_${prNumber}`);
            this.sharedCache.clearCacheEntry(`prAdditional_${prNumber}`);
        }
    }

    /**
     * Clear all cache
     */
    clearAllCache() {
        this.cache.clear();
        this.purchaseTypesCache = null;
        this.purchaseSubTypesCache.clear();
        this.pendingCalls.clear();
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.ReceiveListAPI = ReceiveListAPI;
}

