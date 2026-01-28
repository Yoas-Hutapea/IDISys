/**
 * ReceiveListFilter Module
 * Handles filter functionality, search, and reset
 */
class ReceiveListFilter {
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
        // Initialize date range info after a short delay to ensure default dates are set
        setTimeout(() => {
            this.updateDateRangeInfo();
        }, 200);
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
                // Update date range info when date inputs change
                if (input.id === 'startDate' || input.id === 'endDate') {
                    this.updateDateRangeInfo();
                }
                this.debouncedSearch();
            });
        });
        
        // Also listen for change events on date inputs (for when date is set programmatically)
        const startDateInput = document.getElementById('startDate');
        const endDateInput = document.getElementById('endDate');
        if (startDateInput) {
            startDateInput.addEventListener('change', () => this.updateDateRangeInfo());
        }
        if (endDateInput) {
            endDateInput.addEventListener('change', () => this.updateDateRangeInfo());
        }
    }

    /**
     * Toggle filter visibility
     */
    toggleFilter() {
        const filterContent = document.getElementById('filter-content');
        const chevron = document.getElementById('filter-chevron');
        const header = document.querySelector('.filter-header');
        
        if (filterContent && chevron && header) {
            this.filterCollapsed = !this.filterCollapsed;
            
            if (this.filterCollapsed) {
                filterContent.classList.remove('show');
                chevron.classList.remove('bx-chevron-up');
                chevron.classList.add('bx-chevron-down');
                header.setAttribute('aria-expanded', 'false');
            } else {
                filterContent.classList.add('show');
                chevron.classList.remove('bx-chevron-down');
                chevron.classList.add('bx-chevron-up');
                header.setAttribute('aria-expanded', 'true');
            }
        }
    }

    /**
     * Debounced search
     */
    debouncedSearch() {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
            this.updateDateRangeInfo(); // Update date range info when filter changes
            if (this.manager && this.manager.searchReceive) {
                this.manager.searchReceive();
            }
        }, 500);
    }

    /**
     * Get filter values from form
     */
    getFilterValues() {
        const form = document.getElementById('filterForm');
        if (!form) return {};

        const formData = new FormData(form);
        return {
            startDate: formData.get('startDate') || '',
            endDate: formData.get('endDate') || '',
            requestNumber: formData.get('requestNumber') || '',
            prNumber: formData.get('prNumber') || '',
            prType: formData.get('prType') || '',
            prSubType: formData.get('prSubType') || '',
            prName: formData.get('prName') || '',
            statusPR: formData.get('statusPR') || '',
            source: formData.get('source') || ''
        };
    }

    /**
     * Build filter object for DataTable
     */
    buildFilter() {
        const filters = this.getFilterValues();
        const filter = {};
        
        if (filters.startDate) filter.fromDate = filters.startDate;
        if (filters.endDate) filter.toDate = filters.endDate;
        if (filters.prNumber && filters.prNumber.trim()) filter.purchReqNum = filters.prNumber.trim();
        if (filters.prType && filters.prType.trim()) filter.purchReqType = filters.prType.trim();
        if (filters.prSubType && filters.prSubType.trim()) filter.purchReqSubType = filters.prSubType.trim();
        if (filters.prName && filters.prName.trim()) filter.purchReqName = filters.prName.trim();
        if (filters.statusPR) {
            const statusId = parseInt(filters.statusPR);
            if (!isNaN(statusId) && statusId > 0) filter.statusPR = statusId;
        }
        if (filters.source && filters.source.trim()) filter.source = filters.source.trim().toUpperCase();

        return filter;
    }

    /**
     * Reset filter
     */
    async resetFilter() {
        const form = document.getElementById('filterForm');
        if (form) {
            form.reset();
            // Reset default date range to current month
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
            
            const startDateInput = document.getElementById('startDate');
            const endDateInput = document.getElementById('endDate');
            if (startDateInput) {
                startDateInput.value = formatDate(firstDay);
            }
            if (endDateInput) {
                endDateInput.value = formatDate(lastDay);
            }
        }
        
        // Clear selected PRs
        if (this.manager && this.manager.selectedPRNumbers) {
            this.manager.selectedPRNumbers.clear();
        }
        if (this.manager && this.manager.updateProcessButton) {
            this.manager.updateProcessButton();
        }
        
        // Reset table to show all records
        if (this.manager && this.manager.tableModule && this.manager.tableModule.refreshTable) {
            await this.manager.tableModule.refreshTable();
        } else if (this.manager && this.manager.dataTable) {
            this.manager.dataTable.ajax.reload();
        }
        
        // Update date range info
        this.updateDateRangeInfo();
        
        // Show success message using console (or can be implemented with toast notification)
        console.log('Filter has been reset');
    }

    /**
     * Format date from YYYY-MM-DD to compact format (30 Nov 2025)
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
        const dateRangeInfo = document.getElementById('dateRangeInfo');
        if (!dateRangeInfo) return;
        
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
                    dateRangeInfo.textContent = ` ${formatted}`;
                } else {
                    // Same month but different days, show range with year on both dates
                    const startFormatted = this.formatDateCompact(startDate, true);
                    const endFormatted = this.formatDateCompact(endDate, true);
                    dateRangeInfo.textContent = ` ${startFormatted} s/d ${endFormatted}`;
                }
            }
            // If same year, show: "30 Nov 2025 s/d 30 Des 2025"
            else if (startYear === endYear) {
                const startFormatted = this.formatDateCompact(startDate, true);
                const endFormatted = this.formatDateCompact(endDate, true);
                dateRangeInfo.textContent = ` ${startFormatted} s/d ${endFormatted}`;
            }
            // Different years, show: "30 Nov 2024 s/d 30 Des 2025"
            else {
                const startFormatted = this.formatDateCompact(startDate, true);
                const endFormatted = this.formatDateCompact(endDate, true);
                dateRangeInfo.textContent = ` ${startFormatted} s/d ${endFormatted}`;
            }
        } else if (startDate) {
            const startFormatted = this.formatDateCompact(startDate, true);
            dateRangeInfo.textContent = ` ${startFormatted}`;
        } else if (endDate) {
            const endFormatted = this.formatDateCompact(endDate, true);
            dateRangeInfo.textContent = ` ${endFormatted}`;
        } else {
            dateRangeInfo.textContent = '';
        }
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.ReceiveListFilter = ReceiveListFilter;
}

