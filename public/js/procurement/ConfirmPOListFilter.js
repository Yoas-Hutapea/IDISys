/**
 * ConfirmPOListFilter Module
 * Handles filter functionality, search, and reset for Confirm PO List
 */
class ConfirmPOListFilter {
    constructor(managerInstance) {
        this.manager = managerInstance;
        this.debounceTimer = null;
        this.cachedElements = this.cacheElements();
    }

    cacheElements() {
        return {
            filterForm: document.getElementById('filterForm'),
            filterContent: document.getElementById('filter-content'),
            filterChevron: document.getElementById('filter-chevron')
        };
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        // Filter form submission
        if (this.cachedElements.filterForm) {
            this.cachedElements.filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.debouncedSearch();
            });

            // Add event listeners for date inputs
            const poStartDateInput = document.getElementById('poStartDate');
            const poEndDateInput = document.getElementById('poEndDate');
            if (poStartDateInput) {
                poStartDateInput.addEventListener('change', () => this.updateDateRangeInfo());
                poStartDateInput.addEventListener('input', () => this.updateDateRangeInfo());
            }
            if (poEndDateInput) {
                poEndDateInput.addEventListener('change', () => this.updateDateRangeInfo());
                poEndDateInput.addEventListener('input', () => this.updateDateRangeInfo());
            }
        }
    }

    toggleFilter() {
        const content = this.cachedElements.filterContent;
        const chevron = this.cachedElements.filterChevron;
        const header = document.querySelector('.filter-header');
        
        if (content && chevron && header) {
            const isCollapsed = !content.classList.contains('show');
            
            if (isCollapsed) {
                content.classList.add('show');
                chevron.classList.remove('bx-chevron-down');
                chevron.classList.add('bx-chevron-up');
                header.setAttribute('aria-expanded', 'true');
                if (this.manager) {
                    this.manager.filterCollapsed = false;
                }
            } else {
                content.classList.remove('show');
                chevron.classList.remove('bx-chevron-up');
                chevron.classList.add('bx-chevron-down');
                header.setAttribute('aria-expanded', 'false');
                if (this.manager) {
                    this.manager.filterCollapsed = true;
                }
            }
        }
    }

    debouncedSearch() {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
            this.updateDateRangeInfo(); // Update date range info when filter changes
            if (this.manager && this.manager.search) {
                this.manager.search();
            }
        }, 300);
    }

    buildFilter() {
        const formData = new FormData(this.cachedElements.filterForm);
        const poNumber = formData.get('poNumber');
        const prNumber = formData.get('prNumber');
        const purchType = formData.get('purchType');
        const purchSubType = formData.get('purchSubType');
        const purchName = formData.get('purchName');
        const company = formData.get('company');
        const vendorName = formData.get('vendorName');
        const poStartDate = formData.get('poStartDate');
        const poEndDate = formData.get('poEndDate');

        const filter = {};
        
        // Filter for Confirm PO List: show PO with status 8 (normal) and status 12 (rejected - can be re-confirmed)
        filter.mstApprovalStatusIDs = [8, 12];
        
        if (poNumber && poNumber.trim()) filter.poNumber = poNumber.trim();
        if (prNumber && prNumber.trim()) filter.prNumber = prNumber.trim();
        if (purchType && purchType.trim()) filter.purchType = purchType.trim();
        if (purchSubType && purchSubType.trim()) filter.purchSubType = purchSubType.trim();
        if (purchName && purchName.trim()) filter.purchName = purchName.trim();
        if (company && company.trim()) filter.company = company.trim();
        if (vendorName && vendorName.trim()) filter.vendorName = vendorName.trim();
        if (poStartDate) filter.poStartDate = poStartDate;
        if (poEndDate) filter.poEndDate = poEndDate;

        return filter;
    }

    resetFilter() {
        if (this.cachedElements.filterForm) {
            this.cachedElements.filterForm.reset();
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
            
            const poStartDateInput = document.getElementById('poStartDate');
            const poEndDateInput = document.getElementById('poEndDate');
            if (poStartDateInput) {
                poStartDateInput.value = formatDate(firstDay);
            }
            if (poEndDateInput) {
                poEndDateInput.value = formatDate(lastDay);
            }
        }
        
        // Update date range info
        this.updateDateRangeInfo();
        
        // Reload table
        if (this.manager && this.manager.confirmPODataTable) {
            this.manager.confirmPODataTable.ajax.reload();
        }
    }

    // Format date from YYYY-MM-DD to compact format (30 Nov 2025)
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
    
    // Update date range info in header with compact format
    updateDateRangeInfo() {
        const dateRangeInfo = document.getElementById('dateRangeInfo');
        if (!dateRangeInfo) return;
        
        const form = this.cachedElements.filterForm;
        if (!form) return;
        
        const formData = new FormData(form);
        const poStartDate = formData.get('poStartDate');
        const poEndDate = formData.get('poEndDate');
        
        if (poStartDate && poEndDate) {
            const startDateObj = new Date(poStartDate + 'T00:00:00');
            const endDateObj = new Date(poEndDate + 'T00:00:00');
            
            const startYear = startDateObj.getFullYear();
            const endYear = endDateObj.getFullYear();
            const startMonth = startDateObj.getMonth();
            const endMonth = endDateObj.getMonth();
            
            // If same year and month, show: "1 Des 2025 s/d 31 Des 2025"
            if (startYear === endYear && startMonth === endMonth) {
                const startDay = startDateObj.getDate();
                const endDay = endDateObj.getDate();
                // Check if same day
                if (startDay === endDay) {
                    // Same day, show only one date
                    const formatted = this.formatDateCompact(poStartDate, true);
                    dateRangeInfo.textContent = ` ${formatted}`;
                } else {
                    // Same month but different days, show range with year on both dates
                    const startFormatted = this.formatDateCompact(poStartDate, true);
                    const endFormatted = this.formatDateCompact(poEndDate, true);
                    dateRangeInfo.textContent = ` ${startFormatted} s/d ${endFormatted}`;
                }
            }
            // If same year, show: "30 Nov 2025 s/d 30 Des 2025"
            else if (startYear === endYear) {
                const startFormatted = this.formatDateCompact(poStartDate, true);
                const endFormatted = this.formatDateCompact(poEndDate, true);
                dateRangeInfo.textContent = ` ${startFormatted} s/d ${endFormatted}`;
            }
            // Different years, show: "30 Nov 2024 s/d 30 Des 2025"
            else {
                const startFormatted = this.formatDateCompact(poStartDate, true);
                const endFormatted = this.formatDateCompact(poEndDate, true);
                dateRangeInfo.textContent = ` ${startFormatted} s/d ${endFormatted}`;
            }
        } else if (poStartDate) {
            const startFormatted = this.formatDateCompact(poStartDate, true);
            dateRangeInfo.textContent = ` ${startFormatted}`;
        } else if (poEndDate) {
            const endFormatted = this.formatDateCompact(poEndDate, true);
            dateRangeInfo.textContent = ` ${endFormatted}`;
        } else {
            dateRangeInfo.textContent = '';
        }
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.ConfirmPOListFilter = ConfirmPOListFilter;
}

