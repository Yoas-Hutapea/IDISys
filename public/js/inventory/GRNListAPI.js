/**
 * GRN List API - list PO status 11 (Fully Approved), PO detail & items via Procurement, save via Inventory.
 */
class GRNListAPI {
    constructor() {
        this.sharedCache = window.procurementSharedCache || null;
        this.cache = new Map();
        this.cacheTimeout = 5 * 60 * 1000;
    }

    async getCachedData(cacheKey, fetchFn) {
        const cached = this.cache.get(cacheKey);
        if (cached && Date.now() - cached.timestamp < this.cacheTimeout) return cached.data;
        const data = await fetchFn();
        this.cache.set(cacheKey, { data, timestamp: Date.now() });
        return data;
    }

    async getPODetails(poNumber) {
        const key = `poDetails_${poNumber}`;
        return this.getCachedData(key, async () => {
            const res = await apiCall('Procurement', `/Procurement/PurchaseOrder/PurchaseOrders/${encodeURIComponent(poNumber)}`, 'GET');
            return res.data || res;
        });
    }

    async getPOItems(poNumber) {
        const key = `poItems_${poNumber}`;
        return this.getCachedData(key, async () => {
            const res = await apiCall('Procurement', `/Procurement/PurchaseOrder/PurchaseOrders/${encodeURIComponent(poNumber)}/items`, 'GET');
            return Array.isArray(res) ? res : (res.data || res || []);
        });
    }

    async getGRNLines(poNumber) {
        try {
            const res = await apiCall('Inventory', `/Inventory/GoodReceiveNotes/Lines/${encodeURIComponent(poNumber)}`, 'GET');
            return res.data || res || [];
        } catch (e) {
            return [];
        }
    }

    async getPRDetails(prNumber) {
        if (this.sharedCache && typeof this.sharedCache.getCachedData === 'function') {
            const cacheKey = `prDetails_${prNumber}`;
            return await this.sharedCache.getCachedData(cacheKey, async () => {
                const res = await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequests/${encodeURIComponent(prNumber)}`, 'GET');
                return res.data || res;
            });
        }
        const key = `prDetails_${prNumber}`;
        return this.getCachedData(key, async () => {
            const res = await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequests/${encodeURIComponent(prNumber)}`, 'GET');
            return res.data || res;
        });
    }

    async getPRAdditional(prNumber) {
        if (this.sharedCache && typeof this.sharedCache.getCachedData === 'function') {
            const cacheKey = `prAdditional_${prNumber}`;
            return await this.sharedCache.getCachedData(cacheKey, async () => {
                try {
                    const res = await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequestAdditional/${encodeURIComponent(prNumber)}`, 'GET');
                    return res.data || res;
                } catch (e) {
                    const msg = (e.message || e.toString() || '').toLowerCase();
                    if (msg.includes('not found') || msg.includes('404')) return null;
                    throw e;
                }
            });
        }
        const key = `prAdditional_${prNumber}`;
        return this.getCachedData(key, async () => {
            try {
                const res = await apiCall('Procurement', `/Procurement/PurchaseRequest/PurchaseRequestAdditional/${encodeURIComponent(prNumber)}`, 'GET');
                return res.data || res;
            } catch (e) {
                const msg = (e.message || e.toString() || '').toLowerCase();
                if (msg.includes('not found') || msg.includes('404')) return null;
                throw e;
            }
        });
    }

    async saveGRN(poNumber, lines) {
        const res = await apiCall('Inventory', '/Inventory/GoodReceiveNotes/Save', 'POST', { poNumber, lines });
        return res;
    }

    async getPurchaseTypes() {
        if (this.sharedCache && typeof this.sharedCache.getPurchaseTypes === 'function') {
            return this.sharedCache.getPurchaseTypes();
        }
        const res = await apiCall('Procurement', '/Procurement/Master/PurchaseTypes/List?isActive=true', 'GET');
        return Array.isArray(res) ? res : (res?.data || []);
    }

    async getPurchaseSubTypes(typeId) {
        if (this.sharedCache && typeof this.sharedCache.getPurchaseSubTypes === 'function') {
            return this.sharedCache.getPurchaseSubTypes(typeId);
        }
        const res = await apiCall('Procurement', `/Procurement/Master/PurchaseTypes/SubTypes?isActive=true&mstPROPurchaseTypeID=${typeId}`, 'GET');
        return Array.isArray(res) ? res : (res?.data || []);
    }
}

if (typeof window !== 'undefined') window.GRNListAPI = GRNListAPI;
