/**
 * POListFilter Module
 * Handles filter functionality, search, and reset for PO List
 */
class POListFilter {
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

            // Add input debouncing for real-time search and date range updates
            const searchInputs = this.cachedElements.filterForm.querySelectorAll('input, select');
            searchInputs.forEach(input => {
                input.addEventListener('input', () => {
                    // Update date range info when date inputs change
                    if (input.id === 'poStartDate' || input.id === 'poEndDate') {
                        this.updateDateRangeInfo();
                    }
                    this.debouncedSearch();
                });
            });
            
            // Also listen for change events on date inputs (for when date is set programmatically)
            const poStartDateInput = document.getElementById('poStartDate');
            const poEndDateInput = document.getElementById('poEndDate');
            if (poStartDateInput) {
                poStartDateInput.addEventListener('change', () => this.updateDateRangeInfo());
            }
            if (poEndDateInput) {
                poEndDateInput.addEventListener('change', () => this.updateDateRangeInfo());
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

    getFilterValues() {
        if (!this.cachedElements.filterForm) return {};
        
        const formData = new FormData(this.cachedElements.filterForm);
        return {
            poNumber: formData.get('poNumber') || '',
            prNumber: formData.get('prNumber') || '',
            purchType: formData.get('purchType') || '',
            purchSubType: formData.get('purchSubType') || '',
            purchName: formData.get('purchName') || '',
            company: formData.get('company') || '',
            vendorName: formData.get('vendorName') || '',
            poStartDate: formData.get('poStartDate') || '',
            poEndDate: formData.get('poEndDate') || ''
        };
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
        }
        
        // Update date range info
        this.updateDateRangeInfo();
        
        // Reload table
        if (this.manager && this.manager.poDataTable) {
            this.manager.poDataTable.ajax.reload();
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
            const startDay = startDateObj.getDate();
            const endDay = endDateObj.getDate();
            
            // If same year and month, show: "1 Des 2025 s/d 31 Des 2025"
            if (startYear === endYear && startMonth === endMonth) {
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
    window.POListFilter = POListFilter;
}

