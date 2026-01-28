/**
 * Helper serbaguna untuk AJAX call dari dalam ASP.NET Core MVC View.
 * Otomatis menyertakan Anti-Forgery Token untuk keamanan.
 *
 * PENTING: Variabel global API_URLS harus sudah didefinisikan di View (.cshtml)
 * oleh server-side rendering.
 *
 * @param {'RAS' | 'RA' | 'BAPS' | 'Procurement'} apiType - Kunci modul API (contoh: 'RAS').
 * @param {string} endpoint - Endpoint spesifik (contoh: '/invoices/123').
 * @param {string} [method='GET'] - Metode HTTP.
 * @param {object|null} [data=null] - Body data untuk dikirim.
 * @returns {Promise<any>}
 */
// Use window object to avoid redeclaration conflict with DataTableHelper.js
if (typeof window.normalizedApiUrls === 'undefined') {
    window.normalizedApiUrls = {};
}

// Store active requests for cancellation support
if (typeof window.activeApiRequests === 'undefined') {
    window.activeApiRequests = new Map();
}

/**
 * Cancel previous API request with the same key
 * @param {string} requestKey - Unique key for the request
 */
function cancelPreviousRequest(requestKey) {
    if (window.activeApiRequests.has(requestKey)) {
        const controller = window.activeApiRequests.get(requestKey);
        if (controller && !controller.signal.aborted) {
            controller.abort();
            console.log('Cancelled previous request:', requestKey);
        }
        window.activeApiRequests.delete(requestKey);
    }
}

/**
 * Create a unique key for API request tracking
 * @param {string} apiType - API type
 * @param {string} endpoint - Endpoint
 * @param {string} method - HTTP method
 * @returns {string} Unique request key
 */
function createRequestKey(apiType, endpoint, method) {
    return `${apiType.toLowerCase()}-${endpoint}-${method.toUpperCase()}`;
}

async function apiCall(apiType, endpoint, method = 'GET', data = null, options = {}) {
    // Validate that configuration from server is available
    if (typeof API_URLS === 'undefined') {
        throw new Error('API_URLS configuration not found. Please ensure it is rendered from Razor View.');
    }

    if (typeof API_URLS !== 'undefined') {
        for (const key in API_URLS) {
            if (API_URLS.hasOwnProperty(key)) {
                window.normalizedApiUrls[key.toLowerCase()] = API_URLS[key];
            }
        }
    }

    apiType = apiType.toLowerCase();
    const baseUrl = window.normalizedApiUrls[apiType];
    if (!baseUrl) {
        throw new Error("Invalid API type: " + apiType);
    }

    const fullUrl = baseUrl + endpoint;
    const upperCaseMethod = method.toUpperCase();

    // Create request key for cancellation tracking
    const requestKey = createRequestKey(apiType, endpoint, method);

    // Cancel previous request with same key if cancelPrevious option is true (default: true for GET requests)
    // But only if the previous request is still pending (not completed)
    const shouldCancelPrevious = options.cancelPrevious !== false && (method.toUpperCase() === 'GET' || options.cancelPrevious === true);
    if (shouldCancelPrevious) {
        // Only cancel if there's an active request that hasn't completed
        const existingController = window.activeApiRequests.get(requestKey);
        if (existingController && !existingController.signal.aborted) {
            cancelPreviousRequest(requestKey);
        }
    }

    // Create new AbortController for this request
    const controller = new AbortController();
    if (shouldCancelPrevious) {
        window.activeApiRequests.set(requestKey, controller);
    }

    const headers = {
        'Accept': 'application/json'
    };

    // Konfigurasi dasar fetch
    const config = {
        method: upperCaseMethod,
        headers: headers,
        // "credentials: 'include'" biasanya tidak diperlukan jika API berada di domain yang sama,
        // karena browser akan otomatis mengirim cookie. Namun, menambahkannya tidak masalah.
        credentials: 'include',
        signal: controller.signal // Add abort signal
    };

    // Untuk metode selain GET, kita perlu mengirim Content-Type dan Anti-Forgery Token
    if (upperCaseMethod !== 'GET') {
        if (data && data instanceof FormData) {
            config.body = data;
        } else if (data) {
            // Jika data bukan FormData, set Content-Type menjadi 'application/json'
            headers['Content-Type'] = 'application/json';

            // Ambil Anti-Forgery Token dari form
            const tokenInput = document.querySelector('input[name="__RequestVerificationToken"]');
            if (tokenInput) {
                headers['RequestVerificationToken'] = tokenInput.value;
            } else {
                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                if (csrfMeta && csrfMeta.getAttribute('content')) {
                    headers['X-CSRF-TOKEN'] = csrfMeta.getAttribute('content');
                } else {
                    console.warn('CSRF token not found. Non-GET request may be rejected by the server.');
                }
            }

            // Jika data bukan FormData, lakukan JSON.stringify
            config.body = JSON.stringify(data);
        }
    }

    try {
        const response = await fetch(fullUrl, config);

        // Handle 401 Unauthorized - Session expired, redirect to login
        if (response.status === 401) {
            const currentPath = window.location.pathname;
            const returnUrl = encodeURIComponent(currentPath);
            const loginUrl = `/Account/Login?expired=true&returnUrl=${returnUrl}`;
            window.location.href = loginUrl;
            return; // Exit early, don't try to parse response
        }

        const responseData = await response.json();

        // Handle ApiResponse<T> wrapper format from StandardResponseFilter
        // ApiResponse structure: { statusCode, isError?, message, data?, errors? }
        if (responseData && typeof responseData === 'object' && 'statusCode' in responseData) {
            // This is an ApiResponse<T> wrapper
            if (responseData.isError === true || !response.ok) {
                // Error response
                const errorMessage = responseData.message || responseData.Message || 'An error occurred';
                const errorDetails = responseData.errors || responseData.Errors || [];

                if (errorDetails.length > 0) {
                    throw new Error(`${errorMessage}: ${errorDetails.join(', ')}`);
                }
                throw new Error(errorMessage);
            }

            // Success response - extract the Data property
            // Handle both camelCase (from JSON serializer) and PascalCase (from older API)
            return responseData.data || responseData.Data || responseData;
        }

        // Handle legacy response format (backward compatibility)
        if (!response.ok) {
            const errorMessage = responseData.message || responseData.Message || `HTTP error! Status: ${response.status}`;
            throw new Error(errorMessage);
        }

        if (response.status === 204) {
            return null;
        }

        // Legacy format: try to extract data if it exists, otherwise return full response
        return responseData.data || responseData.Data || responseData;
    } catch (error) {
        // Don't log error if request was aborted (this is expected behavior)
        if (error.name === 'AbortError') {
            console.log('Request aborted:', requestKey);
            // Return null instead of undefined to make it easier to check
            return null;
        }
        console.error("API call to " + fullUrl + " failed: " + error.message);
        throw error;
    } finally {
        // Clean up request tracking
        if (window.activeApiRequests.has(requestKey)) {
            window.activeApiRequests.delete(requestKey);
        }
    }

}

function apiStorage(apiType,filePath) { //disini
    if (typeof API_URLS === 'undefined') {
        throw new Error('API_URLS configuration not found. Please ensure it is rendered from Razor View.');
    }

    apiType = apiType.toLowerCase();
    const baseUrl = window.normalizedApiUrls[apiType];
    if (!baseUrl) {
        throw new Error("Invalid API type: " + apiType);
    }
    const origin = new URL(baseUrl).origin;
    return `${origin}/storage${filePath}`;
}

/**
 * Helper function untuk logout menggunakan endpoint Account/Logout dari AccountController.
 * Fungsi ini akan membuat form POST dengan Anti-Forgery Token dan submit secara dinamis.
 *
 * Menggunakan fungsi Logout yang sudah ada di AccountController.cs yang menangani:
 * - Logout via WCF service
 * - Menghapus cache Redis
 * - Sign out dari authentication
 * - Menghapus cookie IDEANET.AuthCookie
 * - Menghapus TempData
 * - Redirect ke Home
 *
 * @returns {void}
 */
function logout() {
    // Cari Anti-Forgery Token dari form yang ada di halaman
    const tokenInput = document.querySelector('input[name="__RequestVerificationToken"]');

    if (!tokenInput) {
        console.error('Anti-Forgery Token not found. Cannot perform logout.');
        // Fallback: redirect to login page if token is not found
        window.location.href = '/Account/Login';
        return;
    }

    // Buat form POST secara dinamis
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/Account/Logout';

    // Tambahkan Anti-Forgery Token
    const tokenField = document.createElement('input');
    tokenField.type = 'hidden';
    tokenField.name = '__RequestVerificationToken';
    tokenField.value = tokenInput.value;
    form.appendChild(tokenField);

    // Tambahkan form ke body dan submit
    document.body.appendChild(form);
    form.submit();
}

/**
 * Utility function untuk debounce - mencegah function dipanggil terlalu sering
 * @param {Function} func - Function yang akan di-debounce
 * @param {number} wait - Delay dalam milliseconds
 * @param {boolean} immediate - Jika true, panggil function segera, lalu debounce
 * @returns {Function} Debounced function
 */
function debounce(func, wait = 300, immediate = false) {
    let timeout;
    return function executedFunction(...args) {
        const context = this;
        const later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
}

/**
 * Utility function untuk throttle - membatasi function dipanggil maksimal sekali per interval
 * @param {Function} func - Function yang akan di-throttle
 * @param {number} limit - Interval dalam milliseconds
 * @returns {Function} Throttled function
 */
function throttle(func, limit = 300) {
    let inThrottle;
    return function(...args) {
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Export functions to window for global access
if (typeof window !== 'undefined') {
    window.debounce = debounce;
    window.throttle = throttle;
}

