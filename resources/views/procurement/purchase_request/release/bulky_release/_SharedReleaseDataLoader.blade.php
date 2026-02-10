{{--
    Shared Data Loader for Release PR
    Prevents duplicate API calls when both Bulky Release and Single Release partials are loaded
    Uses Promise-based loading to ensure only one API call is made at a time
--}}

<script>
    (function() {
        'use strict';

        // Shared data storage - accessible by all partials
        if (!window.sharedReleaseData) {
            window.sharedReleaseData = {
                vendors: null,
                termOfPayments: null,
                vendorDataMap: new Map(),
                topDataMap: new Map()
            };
        }

        // Shared loading promises - prevents duplicate API calls
        if (!window.sharedReleaseDataLoaders) {
            window.sharedReleaseDataLoaders = {
                vendorsPromise: null,
                termOfPaymentsPromise: null,
                vendorsLoading: false,
                termOfPaymentsLoading: false
            };
        }

        /**
         * Shared function to load vendors from API
         * Returns a Promise that resolves with vendors array
         * If already loading, returns the existing promise
         * If already loaded, returns resolved promise with cached data
         */
        window.loadSharedVendors = async function() {
            const loaders = window.sharedReleaseDataLoaders;
            const data = window.sharedReleaseData;

            // If already loaded, return cached data
            if (data.vendors && data.vendors.length > 0) {
                return Promise.resolve(data.vendors);
            }

            // If currently loading, return the existing promise
            if (loaders.vendorsLoading && loaders.vendorsPromise) {
                return loaders.vendorsPromise;
            }

            // Start loading
            loaders.vendorsLoading = true;
            loaders.vendorsPromise = (async () => {
                try {
                    const endpoint = '/Procurement/Master/Vendors?isActive=true';
                    let responseData;

                    if (typeof apiCall === 'function') {
                        responseData = await apiCall('Procurement', endpoint, 'GET');
                    } else {
                        // Fallback: use fetch directly
                        let attempts = 0;
                        while (typeof apiCall !== 'function' && attempts < 10) {
                            await new Promise(resolve => setTimeout(resolve, 100));
                            attempts++;
                        }

                        if (typeof apiCall === 'function') {
                            responseData = await apiCall('Procurement', endpoint, 'GET');
                        } else {
                            if (typeof API_URLS === 'undefined') {
                                throw new Error('API configuration not found');
                            }
                            const apiType = 'procurement';
                            const baseUrl = API_URLS[apiType] || API_URLS['Procurement'] || '/api/Procurement';
                            const fullUrl = baseUrl + endpoint;

                            const response = await fetch(fullUrl, {
                                method: 'GET',
                                headers: {
                                    'Accept': 'application/json'
                                },
                                credentials: 'include'
                            });

                            const jsonData = await response.json();

                            // Handle ApiResponse<T> wrapper format from StandardResponseFilter
                            if (jsonData && typeof jsonData === 'object' && 'statusCode' in jsonData) {
                                if (jsonData.isError === true || !response.ok) {
                                    const errorMessage = jsonData.message || jsonData.Message || 'An error occurred';
                                    throw new Error(errorMessage);
                                }
                                responseData = jsonData.data || jsonData.Data || jsonData;
                            } else if (!response.ok) {
                                throw new Error(`HTTP error! Status: ${response.status}`);
                            } else {
                                responseData = jsonData.data || jsonData.Data || jsonData;
                            }
                        }
                    }

                    // Check if data exists
                    if (responseData === null || responseData === undefined) {
                        throw new Error('Vendors data is null or undefined');
                    }

                    const vendors = responseData.data || responseData;

                    if (!Array.isArray(vendors) || vendors.length === 0) {
                        data.vendors = [];
                        return [];
                    }

                    // Store vendors
                    data.vendors = vendors;

                    // Clear and rebuild vendor data map
                    data.vendorDataMap.clear();
                    vendors.forEach(vendor => {
                        const vendorId = vendor.VendorID || vendor.vendorID || '';
                        const vendorName = vendor.VendorName || vendor.vendorName || vendorId;
                        const vendorCategoryId = vendor.VendorCategory || vendor.vendorCategory || 0;
                        const vendorCategoryIdInt = typeof vendorCategoryId === 'number' ? vendorCategoryId : parseInt(vendorCategoryId, 10) || 0;

                        data.vendorDataMap.set(vendorId, {
                            vendorId: vendorId,
                            vendorName: vendorName,
                            vendorCategoryId: vendorCategoryIdInt
                        });
                    });

                    return vendors;
                } catch (error) {
                    throw error;
                } finally {
                    loaders.vendorsLoading = false;
                    loaders.vendorsPromise = null;
                }
            })();

            return loaders.vendorsPromise;
        };

        /**
         * Shared function to load Term of Payments from API
         * Returns a Promise that resolves with TOPs array
         * If already loading, returns the existing promise
         * If already loaded, returns resolved promise with cached data
         */
        window.loadSharedTermOfPayments = async function() {
            const loaders = window.sharedReleaseDataLoaders;
            const data = window.sharedReleaseData;

            // If already loaded, return cached data
            if (data.termOfPayments && data.termOfPayments.length > 0) {
                return Promise.resolve(data.termOfPayments);
            }

            // If currently loading, return the existing promise
            if (loaders.termOfPaymentsLoading && loaders.termOfPaymentsPromise) {
                return loaders.termOfPaymentsPromise;
            }

            // Start loading
            loaders.termOfPaymentsLoading = true;
            loaders.termOfPaymentsPromise = (async () => {
                try {
                    const endpoint = '/Finance/Master/TermOfPayments';
                    let responseData;

                    if (typeof apiCall === 'function') {
                        responseData = await apiCall('Procurement', endpoint, 'GET');
                    } else {
                        // Fallback: use fetch directly
                        let attempts = 0;
                        while (typeof apiCall !== 'function' && attempts < 10) {
                            await new Promise(resolve => setTimeout(resolve, 100));
                            attempts++;
                        }

                        if (typeof apiCall === 'function') {
                            responseData = await apiCall('Procurement', endpoint, 'GET');
                        } else {
                            if (typeof API_URLS === 'undefined') {
                                throw new Error('API configuration not found');
                            }
                            const apiType = 'procurement';
                            const baseUrl = API_URLS[apiType] || API_URLS['Procurement'] || '/api/Procurement';
                            const fullUrl = baseUrl + endpoint;

                            const response = await fetch(fullUrl, {
                                method: 'GET',
                                headers: {
                                    'Accept': 'application/json'
                                },
                                credentials: 'include'
                            });

                            const jsonData = await response.json();

                            // Handle ApiResponse<T> wrapper format from StandardResponseFilter
                            if (jsonData && typeof jsonData === 'object' && 'statusCode' in jsonData) {
                                if (jsonData.isError === true || !response.ok) {
                                    const errorMessage = jsonData.message || jsonData.Message || 'An error occurred';
                                    throw new Error(errorMessage);
                                }
                                responseData = jsonData.data || jsonData.Data || jsonData;
                            } else if (!response.ok) {
                                throw new Error(`HTTP error! Status: ${response.status}`);
                            } else {
                                responseData = jsonData.data || jsonData.Data || jsonData;
                            }
                        }
                    }

                    // Check if data exists
                    if (responseData === null || responseData === undefined) {
                        throw new Error('Term of Payments data is null or undefined');
                    }

                    const tops = responseData.data || responseData;

                    if (!Array.isArray(tops) || tops.length === 0) {
                        data.termOfPayments = [];
                        return [];
                    }

                    // Store TOPs
                    data.termOfPayments = tops;

                    // Clear and rebuild TOP data map (key by ID so duplicate TOPDescription get correct remarks)
                    data.topDataMap.clear();
                    tops.forEach(top => {
                        const topId = String(top.ID ?? top.id ?? '');
                        const topDescription = top.TOPDescription || top.topDescription || '';
                        const topRemarks = top.TOPRemarks || top.topRemarks || '';
                        data.topDataMap.set(topId, {
                            topDescription: topDescription,
                            topRemarks: topRemarks
                        });
                    });

                    return tops;
                } catch (error) {
                    throw error;
                } finally {
                    loaders.termOfPaymentsLoading = false;
                    loaders.termOfPaymentsPromise = null;
                }
            })();

            return loaders.termOfPaymentsPromise;
        };
    })();
</script>



