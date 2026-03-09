class GRNApprovalAPI {
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

    async getPRAdditional(prNumber) {
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

    async submitApproval(grNumber, decision, remark) {
        return apiCall(
            'Inventory',
            `/Inventory/GoodReceiveNotes/Headers/${encodeURIComponent(grNumber)}/Approval`,
            'PUT',
            { decision, remark }
        );
    }

    async getGRDocuments(grNumber) {
        const key = `grDocuments_${grNumber}`;
        return this.getCachedData(key, async () => {
            const res = await apiCall(
                'Inventory',
                `/Inventory/GoodReceiveNotes/Documents/${encodeURIComponent(grNumber)}/documents`,
                'GET'
            );
            return Array.isArray(res) ? res : (res.data || res || []);
        });
    }
}

if (typeof window !== 'undefined') window.GRNApprovalAPI = GRNApprovalAPI;
