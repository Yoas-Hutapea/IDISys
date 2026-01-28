/**
 * InvoiceListFilter Module
 * Handles filter functionality, search, and reset
 */
class InvoiceListFilter {
    constructor(managerInstance) {
        this.manager = managerInstance;
        this.debounceTimer = null;
        this.filterCollapsed = true; // Default collapsed
    }

    /**
     * Initialize filter functionality
     */
    init() {
        this.bindEvents();
    }

    /**
     * Bind filter events
     */
    bindEvents() {
        const filterForm = document.getElementById('filterForm');
        if (!filterForm) return;

        // Filter form submission with debouncing
        filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            this.debouncedSearch();
        });

        // Add input debouncing for real-time search
        const searchInputs = filterForm.querySelectorAll('input, select');
        searchInputs.forEach(input => {
            input.addEventListener('input', () => {
                this.debouncedSearch();
            });
        });

        // Update date range info when dates change
        const startDateInput = document.getElementById('filterStartDate');
        const endDateInput = document.getElementById('filterEndDate');
        if (startDateInput) {
            startDateInput.addEventListener('change', () => {
                this.updateDateRangeInfo();
            });
        }
        if (endDateInput) {
            endDateInput.addEventListener('change', () => {
                this.updateDateRangeInfo();
            });
        }

        // Initial update of date range info
        this.updateDateRangeInfo();
    }

    /**
     * Toggle filter visibility
     */
    toggleFilter() {
        const filterContent = document.getElementById('filter-content');
        const filterChevron = document.getElementById('filter-chevron');
        
        if (filterContent && filterChevron) {
            const isExpanded = filterContent.classList.contains('show');
            
            if (isExpanded) {
                filterContent.classList.remove('show');
                filterChevron.classList.remove('bx-chevron-up');
                filterChevron.classList.add('bx-chevron-down');
            } else {
                filterContent.classList.add('show');
                filterChevron.classList.remove('bx-chevron-down');
                filterChevron.classList.add('bx-chevron-up');
            }
        }
    }

    /**
     * Debounced search
     */
    debouncedSearch() {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
            if (this.manager && this.manager.tableModule && this.manager.tableModule.loadData) {
                this.manager.tableModule.loadData();
            } else if (this.manager && this.manager.applyFilter) {
                this.manager.applyFilter();
            }
        }, 500);
    }

    /**
     * Get filter values from form
     */
    getFilterValues() {
        // Use native JavaScript to get values (works with both select and hidden inputs)
        const getValue = (id) => {
            const element = document.getElementById(id);
            return element ? (element.value || '') : '';
        };

        return {
            invoiceNumber: getValue('filterInvoiceNumber') || '',
            requestNumber: getValue('filterRequestNumber') || '',
            purchOrderID: getValue('filterPurchOrderID') || '',
            purchaseTypeID: getValue('filterPurchaseType') || '',
            purchaseSubTypeID: getValue('filterPurchaseSubType') || '',
            workTypeID: getValue('filterWorkType') || '',
            statusID: getValue('filterStatus') || '',
            startDate: getValue('filterStartDate') || '',
            endDate: getValue('filterEndDate') || ''
        };
    }

    /**
     * Build filter object for API
     */
    buildFilter() {
        return this.getFilterValues();
    }

    /**
     * Reset filter
     */
    async resetFilter() {
        $('#filterInvoiceNumber').val('');
        $('#filterRequestNumber').val('');
        $('#filterPurchOrderID').val('');
        $('#filterPurchaseType').val('');
        $('#filterPurchaseSubType').val('');
        $('#filterWorkType').val('');
        $('#filterStatus').val('');
        
        // Reset date inputs to current month
        const now = new Date();
        const currentYear = now.getFullYear();
        const currentMonth = now.getMonth();
        
        const firstDay = new Date(currentYear, currentMonth, 1);
        const lastDay = new Date(currentYear, currentMonth + 1, 0);
        
        // Format to YYYY-MM-DD for input type="date"
        const formatDate = (date) => {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };
        
        $('#filterStartDate').val(formatDate(firstDay));
        $('#filterEndDate').val(formatDate(lastDay));
        
        // Update date range info
        this.updateDateRangeInfo();
        
        // Reload data
        if (this.manager && this.manager.tableModule && this.manager.tableModule.loadData) {
            await this.manager.tableModule.loadData();
        } else if (this.manager && this.manager.loadData) {
            await this.manager.loadData();
        }
    }

    /**
     * Format date to compact format (e.g., "1 Jan 2026")
     */
    formatDateCompact(dateString, includeYear = true) {
        if (!dateString) return '';
        
        const date = new Date(dateString + 'T00:00:00');
        if (isNaN(date.getTime())) return '';
        
        const months = [
            'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
            'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
        ];
        
        const day = date.getDate();
        const month = months[date.getMonth()];
        const year = date.getFullYear();
        
        return includeYear ? `${day} ${month} ${year}` : `${day} ${month}`;
    }

    /**
     * Update date range info in header with compact format
     */
    updateDateRangeInfo() {
        const dateRangeText = document.getElementById('dateRangeText');
        if (!dateRangeText) return;
        
        const filters = this.getFilterValues();
        const startDate = filters.startDate;
        const endDate = filters.endDate;
        
        if (startDate && endDate) {
            const startDateObj = new Date(startDate + 'T00:00:00');
            const endDateObj = new Date(endDate + 'T00:00:00');
            
            const startYear = startDateObj.getFullYear();
            const endYear = endDateObj.getFullYear();
            const startMonth = startDateObj.getMonth();
            const endMonth = endDateObj.getMonth();
            const startDay = startDateObj.getDate();
            const endDay = endDateObj.getDate();
            
            // If same year and month, show: "1 Des 2025 s/d 31 Des 2025"
            if (startYear === endYear && startMonth === endMonth) {
                // Check if same day
                if (startDay === endDay) {
                    // Same day, show only one date
                    const formatted = this.formatDateCompact(startDate, true);
                    dateRangeText.textContent = ` ${formatted}`;
                } else {
                    // Same month but different days, show range with year on both dates
                    const startFormatted = this.formatDateCompact(startDate, true);
                    const endFormatted = this.formatDateCompact(endDate, true);
                    dateRangeText.textContent = ` ${startFormatted} s/d ${endFormatted}`;
                }
            }
            // If same year, show: "30 Nov 2025 s/d 30 Des 2025"
            else if (startYear === endYear) {
                const startFormatted = this.formatDateCompact(startDate, true);
                const endFormatted = this.formatDateCompact(endDate, true);
                dateRangeText.textContent = ` ${startFormatted} s/d ${endFormatted}`;
            }
            // Different years, show: "30 Nov 2024 s/d 30 Des 2025"
            else {
                const startFormatted = this.formatDateCompact(startDate, true);
                const endFormatted = this.formatDateCompact(endDate, true);
                dateRangeText.textContent = ` ${startFormatted} s/d ${endFormatted}`;
            }
        } else if (startDate) {
            const startFormatted = this.formatDateCompact(startDate, true);
            dateRangeText.textContent = ` ${startFormatted}`;
        } else if (endDate) {
            const endFormatted = this.formatDateCompact(endDate, true);
            dateRangeText.textContent = ` ${endFormatted}`;
        } else {
            dateRangeText.textContent = '';
        }
    }

    /**
     * Populate dropdown with data
     */
    populateDropdown(dropdownId, data, valueField, textField, includeAll = false) {
        const dropdown = $(`#${dropdownId}`);
        if (!dropdown.length) return;
        
        dropdown.empty();
        
        if (includeAll) {
            dropdown.append($('<option></option>').val('').text('All'));
        }
        
        data.forEach(item => {
            const value = item[valueField] || item[valueField.toLowerCase()] || '';
            const text = item[textField] || item[textField.toLowerCase()] || '';
            if (value && text) {
                dropdown.append($('<option></option>').val(value).text(text));
            }
        });
    }

    /**
     * Load master data for filter dropdowns
     */
    async loadMasterData() {
        try {
            if (!this.manager || !this.manager.apiModule) return;
            
            // Load Purchase Types
            const purchaseTypes = await this.manager.apiModule.getPurchaseTypes();
            this.populateDropdown('filterPurchaseType', purchaseTypes, 'ID', 'PurchaseRequestType', true);
            
            // Load Work Types
            const workTypes = await this.manager.apiModule.getWorkTypes();
            this.populateDropdown('filterWorkType', workTypes, 'ID', 'WorkType', true);
            
            // TODO: Load Companies, Purchase Sub Types, Statuses when APIs are available
            // For now, leaving them empty as per original implementation
        } catch (error) {
            console.error('Error loading master data:', error);
        }
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.InvoiceListFilter = InvoiceListFilter;
}

