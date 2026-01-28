/**
 * PostingListFilter Module
 * Handles filter functionality, search, and reset
 */
class PostingListFilter {
    constructor(managerInstance) {
        this.manager = managerInstance;
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
            workTypeID: getValue('filterWorkType') || '',
            statusID: getValue('filterStatus') || '13', // Default to status 13
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
        $('#filterWorkType').val('');
        $('#filterStatus').val('13'); // Reset to default status 13
        
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
     * Load master data for filter dropdowns
     */
    async loadMasterData() {
        try {
            if (!this.manager || !this.manager.apiModule) return;
            
            // Load Work Types
            const workTypes = await this.manager.apiModule.getWorkTypes();
            if (this.manager.utilsModule) {
                this.manager.utilsModule.populateDropdown('filterWorkType', workTypes, 'ID', 'WorkType', true);
            }
        } catch (error) {
            console.error('Error loading master data:', error);
        }
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.PostingListFilter = PostingListFilter;
}

