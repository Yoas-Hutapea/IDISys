/**
 * ProcurementSharedCache Module
 * Shared cache for API responses across ProcurementWizard and PRList modules
 * Prevents duplicate API calls when both modules are used on the same page
 */
class ProcurementSharedCache {
    constructor() {
        // Shared cache for API responses (5 minutes timeout)
        this.cache = new Map();
        this.cacheTimeout = 5 * 60 * 1000; // 5 minutes
        
        // Shared cache for master data (longer timeout - 30 minutes)
        this.masterDataCache = new Map();
        this.masterDataCacheTimeout = 30 * 60 * 1000; // 30 minutes
        
        // Pending API calls to prevent duplicate requests
        this.pendingCalls = new Map();
        
        // Employee name cache (shared across modules)
        this.employeeNameCache = new Map();
        this.pendingEmployeeLookups = new Map();
        
        // Full employee list cache (for batch lookups)
        this.allEmployeesCache = null;
        this.allEmployeesCacheTimestamp = null;
        this.allEmployeesCacheTimeout = 30 * 60 * 1000; // 30 minutes
        this.pendingAllEmployeesFetch = null;
        
        // Purchase Types cache (shared)
        this.purchaseTypesCache = null;
        this.purchaseTypesCacheTimestamp = null;
        
        // Purchase Sub Types cache (shared) - key: typeId, value: { data, timestamp }
        this.purchaseSubTypesCache = new Map();
    }

    /**
     * Get cached data or fetch from API (with shared cache)
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
     * Get employee name by employ_id with shared caching
     */
    async getEmployeeNameByEmployId(employId) {
        if (!employId || employId === '-') return '';
        
        // Check cache first
        const cacheKey = employId.trim().toLowerCase();
        if (this.employeeNameCache.has(cacheKey)) {
            return this.employeeNameCache.get(cacheKey);
        }
        
        // Check if lookup is already pending
        if (this.pendingEmployeeLookups.has(cacheKey)) {
            return await this.pendingEmployeeLookups.get(cacheKey);
        }
        
        // Create promise for this lookup
        const lookupPromise = (async () => {
            try {
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
     * Get all employees from API (with caching)
     * This is more efficient than individual lookups when dealing with many employee IDs
     */
    async getAllEmployees() {
        // Check cache first
        if (this.allEmployeesCache && this.allEmployeesCacheTimestamp) {
            const age = Date.now() - this.allEmployeesCacheTimestamp;
            if (age < this.allEmployeesCacheTimeout) {
                return this.allEmployeesCache;
            }
        }
        
        // Check if fetch is already pending
        if (this.pendingAllEmployeesFetch) {
            return await this.pendingAllEmployeesFetch;
        }
        
        // Create promise for fetching all employees
        const fetchPromise = (async () => {
            try {
                // Fetch all employees without searchTerm to get complete list
                const endpoint = '/Procurement/Master/Employees';
                const data = await apiCall('Procurement', endpoint, 'GET');
                const employees = data.data || data;
                const result = Array.isArray(employees) ? employees : [];
                
                // Cache the result
                this.allEmployeesCache = result;
                this.allEmployeesCacheTimestamp = Date.now();
                
                // Pre-populate employee name cache from the full list
                result.forEach(emp => {
                    const empId = emp.Employ_Id || emp.employ_Id || emp.EmployId || emp.employeeId || '';
                    if (empId) {
                        const cacheKey = empId.trim().toLowerCase();
                        const name = emp.name || emp.Name || '';
                        if (name && !this.employeeNameCache.has(cacheKey)) {
                            this.employeeNameCache.set(cacheKey, name);
                        }
                    }
                });
                
                return result;
            } catch (error) {
                console.error('Error fetching all employees:', error);
                throw error;
            } finally {
                // Remove from pending fetches
                this.pendingAllEmployeesFetch = null;
            }
        })();
        
        // Store pending fetch
        this.pendingAllEmployeesFetch = fetchPromise;
        
        return await fetchPromise;
    }

    /**
     * Batch get employee names (for multiple employee IDs at once)
     * Optimized: Fetches all employees once instead of making individual API calls
     */
    async batchGetEmployeeNames(employeeIds) {
        if (!employeeIds || employeeIds.length === 0) {
            return new Map();
        }
        
        // Get unique employee IDs
        const uniqueIds = [...new Set(employeeIds.filter(id => id && id !== '-'))];
        if (uniqueIds.length === 0) {
            return new Map();
        }
        
        // Check cache first for already cached names
        const nameMap = new Map();
        const uncachedIds = [];
        
        uniqueIds.forEach(employeeId => {
            const cacheKey = employeeId.trim().toLowerCase();
            if (this.employeeNameCache.has(cacheKey)) {
                const name = this.employeeNameCache.get(cacheKey);
                if (name) {
                    nameMap.set(cacheKey, name);
                }
            } else {
                uncachedIds.push(employeeId);
            }
        });
        
        // If all IDs are cached, return immediately
        if (uncachedIds.length === 0) {
            return nameMap;
        }
        
        // For uncached IDs, fetch all employees once and look up from the full list
        // This is much more efficient than making individual API calls
        try {
            const allEmployees = await this.getAllEmployees();
        
            // Build lookup map from all employees
            const employeeLookup = new Map();
            allEmployees.forEach(emp => {
                const empId = emp.Employ_Id || emp.employ_Id || emp.EmployId || emp.employeeId || '';
                if (empId) {
                    const cacheKey = empId.trim().toLowerCase();
                    const name = emp.name || emp.Name || '';
                    employeeLookup.set(cacheKey, name);
                    
                    // Cache the name
                    if (name && !this.employeeNameCache.has(cacheKey)) {
                        this.employeeNameCache.set(cacheKey, name);
                    }
                }
            });
            
            // Look up uncached IDs from the full employee list
            uncachedIds.forEach(employeeId => {
                const cacheKey = employeeId.trim().toLowerCase();
                const name = employeeLookup.get(cacheKey);
            if (name) {
                    nameMap.set(cacheKey, name);
                } else {
                    // Cache empty result to avoid repeated lookups
                    this.employeeNameCache.set(cacheKey, '');
                }
            });
        } catch (error) {
            console.error('Error in batchGetEmployeeNames:', error);
            // On error, cache empty results for uncached IDs to avoid repeated failed lookups
            uncachedIds.forEach(employeeId => {
                const cacheKey = employeeId.trim().toLowerCase();
                if (!this.employeeNameCache.has(cacheKey)) {
                    this.employeeNameCache.set(cacheKey, '');
            }
        });
        }
        
        return nameMap;
    }

    /**
     * Get Purchase Types with shared caching
     */
    async getPurchaseTypes() {
        // Check cache first
        if (this.purchaseTypesCache && this.purchaseTypesCacheTimestamp) {
            const age = Date.now() - this.purchaseTypesCacheTimestamp;
            if (age < this.masterDataCacheTimeout) {
                return this.purchaseTypesCache;
            }
        }
        
        // Check if request is already pending
        const cacheKey = 'purchaseTypes';
        if (this.pendingCalls.has(cacheKey)) {
            return await this.pendingCalls.get(cacheKey);
        }
        
        // Create promise for this request
        // IMPORTANT: Create promise first, then immediately store it in pendingCalls
        // to prevent race condition where two calls happen simultaneously
        let requestPromise;
        requestPromise = (async () => {
            try {
                const endpoint = '/Procurement/Master/PurchaseTypes/List?isActive=true';
                const data = await apiCall('Procurement', endpoint, 'GET');
                const types = data.data || data;
                const result = Array.isArray(types) ? types : [];
                
                // Cache the result
                this.purchaseTypesCache = result;
                this.purchaseTypesCacheTimestamp = Date.now();
                
                return result;
            } catch (error) {
                console.error('Error fetching purchase types:', error);
                throw error;
            } finally {
                // Remove from pending calls
                this.pendingCalls.delete(cacheKey);
            }
        })();
        
        // Store pending request IMMEDIATELY to prevent race condition
        // This must be done right after creating the promise, before any await
        this.pendingCalls.set(cacheKey, requestPromise);
        
        return await requestPromise;
    }

    /**
     * Get Purchase Sub Types with shared caching
     */
    async getPurchaseSubTypes(typeId) {
        if (!typeId) return [];
        
        // Check cache first
        const cached = this.purchaseSubTypesCache.get(typeId);
        if (cached && Date.now() - cached.timestamp < this.masterDataCacheTimeout) {
            return cached.data;
        }
        
        // Check if request is already pending
        const cacheKey = `purchaseSubTypes_${typeId}`;
        if (this.pendingCalls.has(cacheKey)) {
            return await this.pendingCalls.get(cacheKey);
        }
        
        // Create promise for this request
        const requestPromise = (async () => {
            try {
                const endpoint = `/Procurement/Master/PurchaseTypes/SubTypes?isActive=true&mstPROPurchaseTypeID=${encodeURIComponent(typeId)}`;
                const data = await apiCall('Procurement', endpoint, 'GET');
                const subTypes = data.data || data;
                const result = Array.isArray(subTypes) ? subTypes : [];
                
                // Cache the result
                this.purchaseSubTypesCache.set(typeId, {
                    data: result,
                    timestamp: Date.now()
                });
                
                return result;
            } catch (error) {
                console.error('Error fetching purchase sub types:', error);
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
     * Clear all caches
     */
    clearCache() {
        this.cache.clear();
        this.masterDataCache.clear();
        this.pendingCalls.clear();
        this.employeeNameCache.clear();
        this.pendingEmployeeLookups.clear();
        this.allEmployeesCache = null;
        this.allEmployeesCacheTimestamp = null;
        this.pendingAllEmployeesFetch = null;
        this.purchaseTypesCache = null;
        this.purchaseTypesCacheTimestamp = null;
        this.purchaseSubTypesCache.clear();
    }

    /**
     * Clear specific cache entry
     */
    clearCacheEntry(cacheKey) {
        this.cache.delete(cacheKey);
        this.masterDataCache.delete(cacheKey);
        this.pendingCalls.delete(cacheKey);
    }
}

// Create global shared cache instance
if (typeof window !== 'undefined') {
    if (!window.procurementSharedCache) {
        window.procurementSharedCache = new ProcurementSharedCache();
    }
}

