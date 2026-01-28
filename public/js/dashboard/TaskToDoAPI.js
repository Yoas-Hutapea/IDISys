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
    async getRejectedTasks(currentUserEmployeeID) {
        const cacheKey = `taskToDo_rejected_${currentUserEmployeeID || 'anonymous'}`;
        
        return await this.getCachedData(cacheKey, async () => {
            try {
                const endpoint = `/Procurement/PurchaseRequest/PurchaseRequests?statusPR=5`;
                const response = await apiCall('Procurement', endpoint, 'GET');
                const data = response.data || response;
                const allRejectedTasks = Array.isArray(data) ? data : [];
                
                // Filter to only show rejected PRs where:
                // 1. Status is 5 (Rejected)
                // 2. CreatedBy matches current user's EmployeeID (only show PRs created by current user)
                const rejectedTasks = allRejectedTasks.filter(task => {
                    const statusID = task.mstApprovalStatusID || task.MstApprovalStatusID;
                    if (statusID !== 5) return false;
                    
                    // Filter by CreatedBy: Only show rejected PRs where CreatedBy matches current user's EmployeeID
                    if (currentUserEmployeeID && currentUserEmployeeID.trim() !== '') {
                        const taskCreatedBy = task.CreatedBy || task.createdBy || '';
                        return taskCreatedBy === currentUserEmployeeID;
                    } else {
                        return false;
                    }
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
     * Get all PO tasks (status 8, 9, 10, 12) in a single API call
     * This optimizes performance by reducing from 2 API calls to 1
     * Cache key is shared (no user-specific) since data is the same for all users
     */
    async getPOTasks() {
        const cacheKey = 'taskToDo_PO_all';
        
        return await this.getCachedData(cacheKey, async () => {
            try {
                // Fetch all PO statuses in one call: [8, 9, 10, 12]
                const poRequest = {
                    mstApprovalStatusIDs: [8, 9, 10, 12],
                    length: 1000, // Get up to 1000 records for counting
                    start: 0,
                    draw: 1
                };
                const response = await apiCall('Procurement', '/Procurement/PurchaseOrder/PurchaseOrders/Grid', 'POST', poRequest);
                const data = response.data || response;
                
                let allPOTasks = [];
                // First, try to get data array
                if (data.data && Array.isArray(data.data) && data.data.length > 0) {
                    allPOTasks = data.data;
                } else if (Array.isArray(data)) {
                    allPOTasks = data;
                } else {
                    // If no data array but we have count, use count to create placeholder tasks
                    const count = data.RecordsFiltered || data.RecordsTotal || 0;
                    if (count > 0) {
                        // We can't create placeholders here since we don't know which status they belong to
                        // Just return empty array and let the count be handled by the individual methods
                        allPOTasks = [];
                    }
                }
                
                return allPOTasks;
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
            // Get all PO tasks from cache (single API call shared across all users)
            const allPOTasks = await this.getPOTasks();
            
            // Filter for confirm PO tasks (status 8 or 12)
            const confirmPOTasks = allPOTasks.filter(task => {
                const statusID = task.mstApprovalStatusID || task.MstApprovalStatusID;
                return statusID === 8 || statusID === 12;
            });
            
            // Filter by CreatedBy: Only show PO tasks where CreatedBy matches current user's EmployeeID
            if (currentUserEmployeeID && currentUserEmployeeID.trim() !== '') {
                return confirmPOTasks.filter(task => {
                    const taskCreatedBy = task.CreatedBy || task.createdBy || '';
                    return taskCreatedBy === currentUserEmployeeID;
                });
            } else {
                return [];
            }
        } catch (error) {
            console.warn('Error loading confirm PO tasks:', error);
            return [];
        }
    }

    /**
     * Get approval PO tasks (status 9 and 10)
     * Uses cached data from getPOTasks() to avoid duplicate API calls
     */
    async getApprovalPOTasks() {
        try {
            // Get all PO tasks from cache (single API call shared across all users)
            const allPOTasks = await this.getPOTasks();
            
            // Filter for approval PO tasks (status 9 or 10)
            return allPOTasks.filter(task => {
                const statusID = task.mstApprovalStatusID || task.MstApprovalStatusID;
                return statusID === 9 || statusID === 10;
            });
        } catch (error) {
            console.warn('Error loading approval PO tasks:', error);
            return [];
        }
    }

    /**
     * Get all tasks (combines all task types)
     */
    async getAllTasks(currentUserEmployeeID) {
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
                this.getRejectedTasks(currentUserEmployeeID),
                this.getReleaseTasks(),
                this.getConfirmPOTasks(currentUserEmployeeID),
                this.getApprovalPOTasks()
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

