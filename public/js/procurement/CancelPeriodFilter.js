/**
 * CancelPeriodFilter Module
 * Handles filter functionality, search, and reset
 */
class CancelPeriodFilter {
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
            if (this.manager && this.manager.searchPO) {
                this.manager.searchPO();
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
        // Get values from hidden inputs (for dropdowns) or direct inputs
        const poNumberInput = form.querySelector('#poNumber');
        const purchTypeInput = form.querySelector('#purchType');
        const purchSubTypeInput = form.querySelector('#purchSubType');
        const purchNameInput = form.querySelector('#purchName');
        const companyInput = form.querySelector('#company');
        const vendorNameInput = form.querySelector('#vendorName');
        const poStartDateInput = form.querySelector('#poStartDate');
        const poEndDateInput = form.querySelector('#poEndDate');
        
        return {
            poNumber: (poNumberInput ? poNumberInput.value : formData.get('poNumber')) || '',
            prNumber: formData.get('prNumber') || '',
            purchType: (purchTypeInput ? purchTypeInput.value : formData.get('purchType')) || '',
            purchSubType: (purchSubTypeInput ? purchSubTypeInput.value : formData.get('purchSubType')) || '',
            purchName: (purchNameInput ? purchNameInput.value : formData.get('purchName')) || '',
            company: (companyInput ? companyInput.value : formData.get('company')) || '',
            vendorName: (vendorNameInput ? vendorNameInput.value : formData.get('vendorName')) || '',
            poStartDate: (poStartDateInput ? poStartDateInput.value : formData.get('poStartDate')) || '',
            poEndDate: (poEndDateInput ? poEndDateInput.value : formData.get('poEndDate')) || ''
        };
    }

    /**
     * Build filter object for DataTable Grid endpoint
     * Uses the same format as POListScriptsPartial
     * Filter for Cancel Period: show only PO with mstApprovalStatusID = 11
     */
    buildFilter() {
        const filters = this.getFilterValues();
        const filter = {};
        
        // Filter for Cancel Period List: show only PO with status 11
        filter.mstApprovalStatusIDs = [11];
        
        if (filters.poNumber && filters.poNumber.trim()) filter.poNumber = filters.poNumber.trim();
        if (filters.prNumber && filters.prNumber.trim()) filter.prNumber = filters.prNumber.trim();
        if (filters.purchType && filters.purchType.trim()) filter.purchType = filters.purchType.trim();
        if (filters.purchSubType && filters.purchSubType.trim()) filter.purchSubType = filters.purchSubType.trim();
        if (filters.purchName && filters.purchName.trim()) filter.purchName = filters.purchName.trim();
        if (filters.company && filters.company.trim()) filter.company = filters.company.trim();
        if (filters.vendorName && filters.vendorName.trim()) filter.vendorName = filters.vendorName.trim();
        if (filters.poStartDate) filter.poStartDate = filters.poStartDate;
        if (filters.poEndDate) filter.poEndDate = filters.poEndDate;

        return filter;
    }

    /**
     * Reset filter
     */
    async resetFilter() {
        const form = document.getElementById('filterForm');
        if (form) {
            form.reset();
        }
        
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
        
        const poStartDateInput = document.getElementById('poStartDate');
        const poEndDateInput = document.getElementById('poEndDate');
        if (poStartDateInput) poStartDateInput.value = formatDate(firstDay);
        if (poEndDateInput) poEndDateInput.value = formatDate(lastDay);

        // Reset text inputs
        const poNumberInput = document.getElementById('poNumber');
        const purchNameInput = document.getElementById('purchName');
        if (poNumberInput) poNumberInput.value = '';
        if (purchNameInput) purchNameInput.value = '';

        // Reset dropdown with search (hidden inputs and selected text)
        const dropdownFields = [
            { hiddenId: 'purchType', textId: 'purchTypeSelectedText', defaultText: 'Select Purchase Type', searchId: 'purchTypeSearchInput' },
            { hiddenId: 'purchSubType', textId: 'purchSubTypeSelectedText', defaultText: 'Select Purchase Sub Type', searchId: 'purchSubTypeSearchInput' },
            { hiddenId: 'company', textId: 'companySelectedText', defaultText: 'Select Company', searchId: 'companySearchInput' },
            { hiddenId: 'vendorName', textId: 'vendorSelectedText', defaultText: 'Select Vendor', searchId: 'vendorSearchInput' }
        ];

        dropdownFields.forEach(field => {
            const hiddenInput = document.getElementById(field.hiddenId);
            const selectedText = document.getElementById(field.textId);
            const searchInput = document.getElementById(field.searchId);
            
            if (hiddenInput) hiddenInput.value = '';
            if (selectedText) selectedText.textContent = field.defaultText;
            if (searchInput) searchInput.value = '';
        });

        // Close any open dropdowns
        const dropdowns = document.querySelectorAll('.dropdown-menu.show');
        dropdowns.forEach(dropdown => {
            const dropdownInstance = bootstrap.Dropdown.getInstance(dropdown.previousElementSibling);
            if (dropdownInstance) {
                dropdownInstance.hide();
            }
        });
        
        // Reset table to show all records
        if (this.manager && this.manager.tableModule && this.manager.tableModule.refreshTable) {
            await this.manager.tableModule.refreshTable();
        } else if (this.manager && this.manager.dataTable) {
            this.manager.dataTable.ajax.reload();
        }
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.CancelPeriodFilter = CancelPeriodFilter;
}

