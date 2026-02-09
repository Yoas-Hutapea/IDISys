/**
 * TaskToDoAPI Module
 * Handles all API calls for Task To Do dashboard with caching to prevent duplicate API calls
 */
class TaskToDoAPI {
    constructor() {
        // Use shared cache if available, otherwise create local cache
        this.sharedCache = window.procurementSharedCache || null;

        // Cache for API responses (for task-specific data)
        this.cache = new Map();
        this.cacheTimeout = 2 * 60 * 1000; // 2 minutes cache timeout (shorter than other caches since tasks change frequently)

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
     * Get approval tasks (status 1-3)
     */
    async getApprovalTasks() {
        const cacheKey = 'taskToDo_approval';

        return await this.getCachedData(cacheKey, async () => {
            const endpoint = `/Procurement/PurchaseRequest/PurchaseRequestApprovals`;
            const response = await apiCall('Procurement', endpoint, 'GET');
            const data = response.data || response;
            return Array.isArray(data) ? data : [];
        });
    }

    /**
     * Get receive tasks (status 4)
     */
    async getReceiveTasks() {
        const cacheKey = 'taskToDo_receive';

        return await this.getCachedData(cacheKey, async () => {
            try {
                const endpoint = `/Procurement/PurchaseRequest/PurchaseRequestReceives`;
                const response = await apiCall('Procurement', endpoint, 'GET');
                const data = response.data || response;
                return Array.isArray(data) ? data : [];
            } catch (error) {
                console.warn('Error loading receive tasks:', error);
                return [];
            }
        });
    }

    /**
     * Get rejected tasks (status 5) for current user
     * Note: Even if user is Procurement Staff or Procurement Team Leader (who can see all PRs in list),
     * rejected tasks should only show PRs where CreatedBy matches current user's EmployeeID
     */
    async getRejectedTasks(currentUserEmployeeID, currentUserIdentifiers = []) {
        const normalizedUserIds = new Set();
        if (currentUserEmployeeID && currentUserEmployeeID.trim() !== '') {
            normalizedUserIds.add(currentUserEmployeeID.trim().toLowerCase());
        }
        if (Array.isArray(currentUserIdentifiers)) {
            currentUserIdentifiers.forEach(id => {
                if (id && id.toString().trim() !== '') {
                    normalizedUserIds.add(id.toString().trim().toLowerCase());
                }
            });
        }

        const cacheKey = `taskToDo_rejected_${Array.from(normalizedUserIds).join('|') || 'anonymous'}`;

        return await this.getCachedData(cacheKey, async () => {
            try {
                const endpoint = `/Procurement/PurchaseRequest/PurchaseRequests?statusPR=5`;
                const response = await apiCall('Procurement', endpoint, 'GET');
                const data = response.data || response;
                const allRejectedTasks = Array.isArray(data) ? data : [];

                // Filter to only show rejected PRs where:
                // 1. Status is 5 (Rejected)
                // 2. Requestor/Applicant/CreatedBy matches current user's identifiers
                const rejectedTasks = allRejectedTasks.filter(task => {
                    const statusIDRaw = task.mstApprovalStatusID || task.MstApprovalStatusID;
                    const statusID = parseInt(statusIDRaw, 10);
                    if (statusID !== 5) return false;

                    if (normalizedUserIds.size === 0) {
                        return false;
                    }

                    const taskIds = [
                        task.CreatedBy, task.createdBy,
                        task.Requestor, task.requestor,
                        task.Applicant, task.applicant,
                        task.PIC, task.pic
                    ]
                        .map(id => (id || '').toString().trim().toLowerCase())
                        .filter(id => id !== '');

                    return taskIds.some(id => normalizedUserIds.has(id));
                });

                return rejectedTasks;
            } catch (error) {
                console.warn('Error loading rejected tasks:', error);
                return [];
            }
        });
    }

    /**
     * Get release tasks (status 7)
     */
    async getReleaseTasks() {
        const cacheKey = 'taskToDo_release';

        return await this.getCachedData(cacheKey, async () => {
            try {
                const endpoint = `/Procurement/PurchaseRequest/PurchaseRequestReleases`;
                const response = await apiCall('Procurement', endpoint, 'GET');
                const data = response.data || response;
                return Array.isArray(data) ? data : [];
            } catch (error) {
                console.warn('Error loading release tasks:', error);
                return [];
            }
        });
    }

    /**
     * Get PO tasks by status IDs (used by confirm/approval tasks)
     * Note: Keep confirm (8,12) separate from approval (9,10) to avoid CreatedBy filter
     */
    async getPOTasksByStatus(statusIds, cacheKey) {
        return await this.getCachedData(cacheKey, async () => {
            try {
                const poRequest = {
                    mstApprovalStatusIDs: statusIds,
                    length: 1000, // Get up to 1000 records for counting
                    start: 0,
                    draw: 1
                };
                const response = await apiCall('Procurement', '/Procurement/PurchaseOrder/PurchaseOrders/Grid', 'POST', poRequest);
                const data = response.data || response;

                // First, try to get data array
                if (data.data && Array.isArray(data.data) && data.data.length > 0) {
                    return data.data;
                }
                if (Array.isArray(data)) {
                    return data;
                }

                // If no data array but we have count, use count to create placeholder tasks
                const count = data.recordsFiltered || data.recordsTotal || data.RecordsFiltered || data.RecordsTotal || 0;
                if (count > 0) {
                    const statusId = Array.isArray(statusIds) && statusIds.length > 0 ? statusIds[0] : null;
                    return Array.from({ length: count }, () => ({
                        mstApprovalStatusID: statusId
                    }));
                }

                return [];
            } catch (error) {
                console.warn('Error loading PO tasks:', error);
                return [];
            }
        });
    }

    /**
     * Get confirm PO tasks (status 8 or 12) for current user
     * Uses cached data from getPOTasks() to avoid duplicate API calls
     */
    async getConfirmPOTasks(currentUserEmployeeID) {
        try {
            // Fetch confirm PO tasks only (status 8 or 12)
            const cacheKey = `taskToDo_PO_confirm_${currentUserEmployeeID || 'anonymous'}`;
            const confirmPOTasks = await this.getPOTasksByStatus([8, 12], cacheKey);

            // Filter by CreatedBy when available: Only show PO tasks where CreatedBy matches current user's EmployeeID
            if (currentUserEmployeeID && currentUserEmployeeID.trim() !== '') {
                const hasCreatedByField = confirmPOTasks.some(task =>
                    Object.prototype.hasOwnProperty.call(task, 'CreatedBy') ||
                    Object.prototype.hasOwnProperty.call(task, 'createdBy')
                );
                if (!hasCreatedByField) {
                    // Backend already filters by CreatedBy for status 8/12,
                    // so allow tasks through when field is not included in response.
                    return confirmPOTasks;
                }
                return confirmPOTasks.filter(task => {
                    const taskCreatedBy = task.CreatedBy || task.createdBy || '';
                    return taskCreatedBy === currentUserEmployeeID;
                });
            }

            return [];
        } catch (error) {
            console.warn('Error loading confirm PO tasks:', error);
            return [];
        }
    }

    /**
     * Get approval PO tasks (status 9 and 10)
     * Uses cached data from getPOTasks() to avoid duplicate API calls
     */
    async getApprovalPOTasks(currentUserEmployeeID) {
        try {
            // Fetch approval PO tasks only (status 9 or 10)
            const cacheKey = `taskToDo_PO_approval_${currentUserEmployeeID || 'anonymous'}`;
            return await this.getPOTasksByStatus([9, 10], cacheKey);
        } catch (error) {
            console.warn('Error loading approval PO tasks:', error);
            return [];
        }
    }

    /**
     * Get all tasks (combines all task types)
     */
    async getAllTasks(currentUserEmployeeID, currentUserIdentifiers = []) {
        try {
            // Fetch all task types in parallel for better performance
            const [
                approvalTasks,
                receiveTasks,
                rejectedTasks,
                releaseTasks,
                confirmPOTasks,
                approvalPOTasks
            ] = await Promise.all([
                this.getApprovalTasks(),
                this.getReceiveTasks(),
                this.getRejectedTasks(currentUserEmployeeID, currentUserIdentifiers),
                this.getReleaseTasks(),
                this.getConfirmPOTasks(currentUserEmployeeID),
                this.getApprovalPOTasks(currentUserEmployeeID)
            ]);

            // Combine all tasks
            return [
                ...approvalTasks,
                ...receiveTasks,
                ...rejectedTasks,
                ...releaseTasks,
                ...confirmPOTasks,
                ...approvalPOTasks
            ];
        } catch (error) {
            console.error('Error loading all tasks:', error);
            return [];
        }
    }

    /**
     * Clear all cache
     */
    clearAllCache() {
        this.cache.clear();
        this.pendingCalls.clear();
    }

    /**
     * Clear specific cache by key
     */
    clearCache(cacheKey) {
        this.cache.delete(cacheKey);
        this.pendingCalls.delete(cacheKey);
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.TaskToDoAPI = TaskToDoAPI;
}

