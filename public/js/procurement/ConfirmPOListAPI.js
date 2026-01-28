/**
 * ConfirmPOListAPI Module
 * Handles all API calls for Confirm PO List with caching to prevent duplicate API calls
 */
class ConfirmPOListAPI {
    constructor() {
        // Use shared cache if available, otherwise create local cache
        this.sharedCache = window.procurementSharedCache || null;
        
        // Cache for API responses (for Confirm PO-specific data)
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
     */
    async getPOItems(poNumber) {
        // Use shared cache if available
        if (this.sharedCache && this.sharedCache.getCachedData) {
            const cacheKey = `poItems_${poNumber}`;
            return await this.sharedCache.getCachedData(cacheKey, async () => {
                const itemsData = await apiCall('Procurement', `/Procurement/PurchaseOrder/PurchaseOrders/${encodeURIComponent(poNumber)}/items`, 'GET');
                return itemsData.data || itemsData;
            });
        }
        
        // Fallback to local cache
        const cacheKey = `poItems_${poNumber}`;
        return await this.getCachedData(cacheKey, async () => {
            const itemsData = await apiCall('Procurement', `/Procurement/PurchaseOrder/PurchaseOrders/${encodeURIComponent(poNumber)}/items`, 'GET');
            return itemsData.data || itemsData;
        });
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
                    // If 404 error or "not found" message, it's okay - additional data doesn't exist for this PR
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
                // If 404 error or "not found" message, it's okay - additional data doesn't exist for this PR
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
     * Get units for edit item modal
     */
    async getUnits() {
        const cacheKey = 'units_all';
        return await this.getCachedData(cacheKey, async () => {
            const data = await apiCall('Procurement', '/Procurement/Master/Units?isActive=true', 'GET');
            return data.data || data;
        });
    }

    /**
     * Submit bulk confirm
     */
    async submitBulkConfirm(poNumbers, decision, remarks) {
        const endpoint = `/Procurement/PurchaseOrder/Confirm/Bulk`;
        const payload = {
            PoNumbers: poNumbers,
            Decision: decision,
            Remarks: remarks
        };
        const response = await apiCall('Procurement', endpoint, 'POST', payload);
        return response;
    }

    /**
     * Submit confirm (single PO with item updates)
     */
    async submitConfirm(poNumber, decision, remarks, updatedItems, deletedItemIds) {
        const endpoint = `/Procurement/PurchaseOrder/Confirm/Submit`;
        const payload = {
            poNumber: poNumber,
            decision: decision,
            remarks: remarks.trim(),
            updatedItems: updatedItems,
            deletedItemIds: deletedItemIds
        };
        const response = await apiCall('Procurement', endpoint, 'POST', payload);
        return response;
    }

    /**
     * Clear cache for specific PO (when PO is edited/updated)
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
    window.ConfirmPOListAPI = ConfirmPOListAPI;
}

