/**
 * PRListEmployeeCache Module
 * Handles employee name caching to prevent duplicate API calls
 */
class PRListEmployeeCache {
    constructor() {
        // Use shared cache if available, otherwise create local cache
        this.sharedCache = window.procurementSharedCache || null;
        
        // Local cache fallback (if shared cache not available)
        this.employeeNameCache = new Map(); // Cache for employee names (employeeId -> employeeName)
        this.pendingEmployeeLookups = new Map(); // Track pending lookups to avoid duplicate API calls
    }

    /**
     * Get employee name by employ_id with shared caching
     */
    async getEmployeeNameByEmployId(employId) {
        // Use shared cache if available
        if (this.sharedCache && this.sharedCache.getEmployeeNameByEmployId) {
            return this.sharedCache.getEmployeeNameByEmployId(employId);
        }
        
        // Fallback to local cache
        if (!employId || employId === '-') return '';
        
        // Check cache first
        const cacheKey = employId.trim().toLowerCase();
        if (this.employeeNameCache.has(cacheKey)) {
            return this.employeeNameCache.get(cacheKey);
        }
        
        // Check if lookup is already pending
        if (this.pendingEmployeeLookups.has(cacheKey)) {
            // Wait for pending lookup to complete
            return await this.pendingEmployeeLookups.get(cacheKey);
        }
        
        // Create promise for this lookup
        const lookupPromise = (async () => {
            try {
                // Call API to get employee by employ_id (searchTerm will search in Employ_Id using Contains)
                // We'll filter for exact match after getting results
                const endpoint = `/Procurement/Master/Employees?searchTerm=${encodeURIComponent(employId)}`;
                const data = await apiCall('Procurement', endpoint, 'GET');
                const employees = data.data || data;
                
                if (Array.isArray(employees) && employees.length > 0) {
                    // Find employee with exact matching Employ_Id (case-insensitive)
                    const employee = employees.find(emp => {
                        const empId = emp.Employ_Id || emp.employ_Id || emp.EmployId || emp.employeeId || '';
                        return empId.trim().toLowerCase() === employId.trim().toLowerCase();
                    });
                    
                    if (employee) {
                        const name = employee.name || employee.Name || '';
                        // Cache the result
                        this.employeeNameCache.set(cacheKey, name);
                        return name;
                    }
                }
                
                // Cache empty result to avoid repeated lookups
                this.employeeNameCache.set(cacheKey, '');
                return '';
            } catch (error) {
                console.error('Error fetching employee name:', error);
                // Cache empty result on error
                this.employeeNameCache.set(cacheKey, '');
                return '';
            } finally {
                // Remove from pending lookups
                this.pendingEmployeeLookups.delete(cacheKey);
            }
        })();
        
        // Store pending lookup
        this.pendingEmployeeLookups.set(cacheKey, lookupPromise);
        
        return await lookupPromise;
    }

    /**
     * Batch lookup multiple employee names (with shared caching)
     */
    async batchGetEmployeeNames(employeeIds) {
        // Use shared cache if available
        if (this.sharedCache && this.sharedCache.batchGetEmployeeNames) {
            return this.sharedCache.batchGetEmployeeNames(employeeIds);
        }
        
        // Fallback to local cache
        if (!employeeIds || employeeIds.length === 0) return new Map();
        
        // Remove duplicates and filter out invalid IDs
        const uniqueIds = [...new Set(employeeIds)].filter(id => id && id !== '-');
        
        if (uniqueIds.length === 0) return new Map();
        
        // Batch lookup all employee names
        const lookupPromises = uniqueIds.map(async (employeeId) => {
            const name = await this.getEmployeeNameByEmployId(employeeId);
            return { employeeId, name };
        });
        
        const results = await Promise.all(lookupPromises);
        
        // Create map of employee ID to name
        const nameMap = new Map();
        results.forEach(({ employeeId, name }) => {
            if (name) {
                nameMap.set(employeeId.trim().toLowerCase(), name);
            }
        });
        
        return nameMap;
    }

    /**
     * Clear cache
     */
    clearCache() {
        this.employeeNameCache.clear();
        this.pendingEmployeeLookups.clear();
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.PRListEmployeeCache = PRListEmployeeCache;
}

