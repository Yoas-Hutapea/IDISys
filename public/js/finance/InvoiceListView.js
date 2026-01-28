/**
 * InvoiceListView Module
 * Handles view invoice details functionality
 * Similar to PRListView - shows view section in list page without navigation
 */
class InvoiceListView {
    constructor(managerInstance) {
        this.manager = managerInstance;
        this.detailManager = null; // Will use InvoiceDetailManager for loading data
    }

    /**
     * View Invoice details
     * Shows view section in list page (like PRListView)
     */
    async viewInvoice(invoiceId) {
        try {
            // Show loading state
            this.showViewInvoiceLoading();
            
            // Hide list and filter sections
            this.hideListSection();
            
            // Initialize InvoiceDetailManager if not already initialized
            if (!this.detailManager) {
                if (typeof InvoiceDetailManager !== 'undefined') {
                    this.detailManager = new InvoiceDetailManager();
                } else {
                    // Fallback: Load InvoiceDetailManager dynamically
                    console.warn('InvoiceDetailManager not found, loading scripts...');
                    await this.loadDetailManagerScripts();
                    if (typeof InvoiceDetailManager !== 'undefined') {
                        this.detailManager = new InvoiceDetailManager();
                    } else {
                        throw new Error('InvoiceDetailManager not available');
                    }
                }
            }
            
            // Load invoice detail using InvoiceDetailManager
            // Pass ViewInvoice section element IDs
            await this.detailManager.init(invoiceId, {
                loadingElementId: 'viewInvoiceLoading',
                dataElementId: 'viewInvoiceData'
            });
            
            // Hide loading, show data
            const viewInvoiceLoading = document.getElementById('viewInvoiceLoading');
            const viewInvoiceData = document.getElementById('viewInvoiceData');
            
            if (viewInvoiceLoading) {
                viewInvoiceLoading.style.display = 'none';
            }
            if (viewInvoiceData) {
                viewInvoiceData.style.display = 'block';
            }
            
            // Show view section
            this.showViewInvoiceSection();
            
        } catch (error) {
            console.error('Error loading invoice for view:', error);
            this.showError('Failed to load Invoice: ' + error.message);
            this.showListSection();
        }
    }

    /**
     * Load Detail Manager scripts dynamically if needed
     */
    async loadDetailManagerScripts() {
        return new Promise((resolve, reject) => {
            // Check if scripts are already loaded
            if (typeof InvoiceDetailAPI !== 'undefined' && typeof InvoiceDetailManager !== 'undefined') {
                resolve();
                return;
            }
            
            // Load scripts
            const scripts = [
                '/js/finance/InvoiceDetailAPI.js',
                '/js/finance/InvoiceDetailManager.js'
            ];
            
            let loadedCount = 0;
            scripts.forEach(src => {
                const script = document.createElement('script');
                script.src = src;
                script.onload = () => {
                    loadedCount++;
                    if (loadedCount === scripts.length) {
                        resolve();
                    }
                };
                script.onerror = () => {
                    reject(new Error(`Failed to load script: ${src}`));
                };
                document.head.appendChild(script);
            });
        });
    }

    /**
     * Load Invoice details for view (if view section is implemented)
     */
    async loadInvoiceDetailsForView(invoiceId) {
        if (!this.manager || !this.manager.apiModule) {
            console.error('API module not available');
            return;
        }

        // Load invoice data
        try {
            const invoice = await this.manager.apiModule.getInvoiceDetails(invoiceId);
            this.viewInvoiceData = invoice || null;
        } catch (error) {
            console.error('Error loading invoice data:', error);
            this.viewInvoiceData = null;
        }

        // Load invoice items
        try {
            const items = await this.manager.apiModule.getInvoiceItems(invoiceId);
            this.viewInvoiceItems = Array.isArray(items) ? items : [];
        } catch (error) {
            console.error('Error loading invoice items:', error);
            this.viewInvoiceItems = [];
        }

        // Load invoice documents
        try {
            const documents = await this.manager.apiModule.getInvoiceDocuments(invoiceId);
            this.viewInvoiceDocuments = Array.isArray(documents) ? documents : [];
        } catch (error) {
            console.error('Error loading invoice documents:', error);
            this.viewInvoiceDocuments = [];
        }

        // Load PO details if available
        if (this.viewInvoiceData) {
            const purchOrderID = this.viewInvoiceData.purchOrderID || this.viewInvoiceData.PurchOrderID || '';
            if (purchOrderID) {
                try {
                    const po = await this.manager.apiModule.getPODetails(purchOrderID);
                    this.viewPOData = po || null;

                    // Load PO items
                    if (po) {
                        try {
                            const poItems = await this.manager.apiModule.getPOItems(purchOrderID);
                            this.viewPOItems = Array.isArray(poItems) ? poItems : [];
                        } catch (error) {
                            console.error('Error loading PO items:', error);
                            this.viewPOItems = [];
                        }
                    }
                } catch (error) {
                    console.error('Error loading PO data:', error);
                    this.viewPOData = null;
                    this.viewPOItems = [];
                }
            }
        }
    }

    /**
     * Show/hide sections (for future view section implementation)
     */
    hideListSection() {
        const filterSection = document.querySelector('.filter-section');
        const listSection = document.getElementById('listSection');
        
        if (filterSection) filterSection.style.display = 'none';
        if (listSection) listSection.style.display = 'none';
    }

    showListSection() {
        const filterSection = document.querySelector('.filter-section');
        const listSection = document.getElementById('listSection');
        const viewInvoiceSection = document.getElementById('viewInvoiceSection');
        
        if (filterSection) filterSection.style.display = 'block';
        if (listSection) listSection.style.display = 'block';
        if (viewInvoiceSection) viewInvoiceSection.style.display = 'none';
    }

    showViewInvoiceSection() {
        const viewInvoiceSection = document.getElementById('viewInvoiceSection');
        if (viewInvoiceSection) {
            viewInvoiceSection.style.display = 'block';
        }
    }

    showViewInvoiceLoading() {
        const viewInvoiceSection = document.getElementById('viewInvoiceSection');
        const viewInvoiceLoading = document.getElementById('viewInvoiceLoading');
        const viewInvoiceData = document.getElementById('viewInvoiceData');
        
        if (viewInvoiceLoading) {
            viewInvoiceLoading.style.display = 'block';
        }
        if (viewInvoiceData) {
            viewInvoiceData.style.display = 'none';
        }
        
        if (viewInvoiceSection) {
            viewInvoiceSection.style.display = 'block';
        }
    }

    /**
     * Back to list
     */
    backToList() {
        this.showListSection();
    }

    /**
     * Download document
     */
    async downloadDocument(documentId, fileName) {
        try {
            if (!documentId || documentId === 0) {
                this.showError('Invalid document ID');
                return;
            }

            // Get FilePath from button's data attribute (if available)
            const button = typeof this === 'object' && this.nodeName === 'BUTTON' ? this : null;
            const filePath = button?.getAttribute('data-file-path') || '';

            // If FilePath is available, use apiStorage (like ARSystem)
            let downloadUrl = null;
            if (filePath && typeof apiStorage === 'function') {
                try {
                    downloadUrl = apiStorage('Procurement', filePath);
                } catch (error) {
                    console.warn('Error using apiStorage, falling back to download endpoint:', error);
                }
            }

            // Fallback: Use download endpoint if FilePath is not available
            if (!downloadUrl) {
                // Normalize API_URLS like apiHelper.js does
                if (typeof API_URLS === 'undefined') {
                    this.showError('API configuration not found');
                    return;
                }

                // Normalize API URLs (handle case-insensitive keys)
                if (typeof window.normalizedApiUrls === 'undefined') {
                    window.normalizedApiUrls = {};
                }
                for (const key in API_URLS) {
                    if (API_URLS.hasOwnProperty(key)) {
                        window.normalizedApiUrls[key.toLowerCase()] = API_URLS[key];
                    }
                }

                // Get API base URL (case-insensitive)
                const apiType = 'procurement';
                const baseUrl = window.normalizedApiUrls[apiType];
                if (!baseUrl) {
                    const availableKeys = Object.keys(window.normalizedApiUrls).join(', ');
                    this.showError(`API configuration not found for Procurement. Available keys: ${availableKeys}`);
                    return;
                }

                // Construct download URL using API base URL
                const endpoint = `/Finance/Invoice/Invoices/${documentId}/Documents/download`;
                downloadUrl = baseUrl + endpoint;
            }
            
            // Create a temporary anchor element to trigger download
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = fileName || 'document';
            link.target = '_blank';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } catch (error) {
            console.error('Error downloading document:', error);
            this.showError('Failed to download document: ' + error.message);
        }
    }

    /**
     * Utility functions
     */
    escapeHtml(text) {
        if (text == null) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatCurrency(amount) {
        if (amount === null || amount === undefined || amount === '') {
            return '-';
        }
        const numAmount = parseFloat(amount);
        if (isNaN(numAmount) || numAmount === 0) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(0);
        }
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(numAmount);
    }

    formatDate(date) {
        if (!date) return '-';
        const dateObj = typeof date === 'string' ? new Date(date) : date;
        if (isNaN(dateObj.getTime())) return '-';
        return new Intl.DateTimeFormat('id-ID', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        }).format(dateObj);
    }

    showError(message) {
        if (this.manager && this.manager.showError) {
            this.manager.showError(message);
        } else {
            console.error(message);
            alert(message);
        }
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.InvoiceListView = InvoiceListView;
}

