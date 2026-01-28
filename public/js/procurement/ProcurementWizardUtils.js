/**
 * Procurement Wizard Utility Functions
 * Common utility functions used across the Procurement Wizard
 */

// Add CSS for dropdown scrollbar styling
if (typeof document !== 'undefined' && !document.getElementById('procurement-dropdown-scrollbar-style')) {
    const dropdownScrollbarStyle = document.createElement('style');
    dropdownScrollbarStyle.id = 'procurement-dropdown-scrollbar-style';
    dropdownScrollbarStyle.textContent = `
        /* Custom scrollbar for dropdown menus in Procurement Wizard */
        .dropdown-menu {
            scrollbar-width: thin; /* Firefox */
            scrollbar-color: #a8aaae #f5f5f9; /* Firefox: thumb and track */
        }
        
        .dropdown-menu::-webkit-scrollbar {
            width: 8px; /* Width of the scrollbar */
        }
        
        .dropdown-menu::-webkit-scrollbar-track {
            background: #f5f5f9; /* Background of the scrollbar track */
            border-radius: 4px;
        }
        
        .dropdown-menu::-webkit-scrollbar-thumb {
            background: #a8aaae; /* Color of the scrollbar thumb */
            border-radius: 4px;
        }
        
        .dropdown-menu::-webkit-scrollbar-thumb:hover {
            background: #878a92; /* Color when hovering over the scrollbar thumb */
        }
        
        /* Ensure dropdown items container is scrollable and has visible scrollbar */
        .dropdown-menu[id$="DropdownMenu"],
        .dropdown-menu[id$="dropdownMenu"] {
            max-height: 300px !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
        }
        
        /* Make scrollbar always visible when content overflows */
        .dropdown-menu[id$="DropdownMenu"]:hover,
        .dropdown-menu[id$="dropdownMenu"]:hover {
            scrollbar-color: #878a92 #f5f5f9; /* Darker on hover for better visibility */
        }
    `;
    document.head.appendChild(dropdownScrollbarStyle);
}

class ProcurementWizardUtils {
    /**
     * Escape HTML to prevent XSS attacks
     * @param {string} text - Text to escape
     * @returns {string} Escaped HTML
     */
    static escapeHtml(text) {
        if (text == null) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Format number with comma as thousand separator
     * @param {number|string} value - Number to format
     * @param {number} maxDecimals - Maximum decimal places (default: 2)
     * @returns {string} Formatted number string
     */
    static formatNumberWithComma(value, maxDecimals = 2) {
        if (isNaN(value) || value === null || value === undefined) return '';
        const num = parseFloat(value);
        if (isNaN(num)) return '';
        
        // Format with comma as thousand separator
        const parts = num.toString().split('.');
        let integerPart = parts[0];
        const decimalPart = parts[1] || '';
        
        // Add thousand separator (comma)
        integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        
        // Handle decimal part
        if (maxDecimals > 0 && decimalPart) {
            const limitedDecimal = decimalPart.substring(0, maxDecimals);
            return integerPart + '.' + limitedDecimal;
        } else if (maxDecimals > 0 && num % 1 !== 0) {
            // If number has decimal but decimal part is empty, format with maxDecimals
            return integerPart + '.' + num.toFixed(maxDecimals).split('.')[1];
        }
        
        return integerPart;
    }

    /**
     * Parse number by removing formatting (commas)
     * @param {string} value - Formatted number string
     * @returns {number} Parsed number
     */
    static parseNumber(value) {
        if (!value) return 0;
        const cleaned = value.toString().replace(/,/g, '');
        return parseFloat(cleaned) || 0;
    }

    /**
     * Debounce function to limit function calls
     * @param {Function} func - Function to debounce
     * @param {number} wait - Wait time in milliseconds
     * @returns {Function} Debounced function
     */
    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Format date to ISO string (YYYY-MM-DD)
     * @param {Date|string} date - Date to format
     * @returns {string} Formatted date string
     */
    static formatDateToISO(date) {
        if (!date) return null;
        const d = date instanceof Date ? date : new Date(date);
        if (isNaN(d.getTime())) return null;
        return d.toISOString().split('T')[0];
    }

    /**
     * Get selected option text from custom dropdown
     * @param {HTMLElement} fieldElement - Hidden input element
     * @returns {string} Selected text
     */
    static getSelectedOptionText(fieldElement) {
        if (!fieldElement) return '';
        
        // For custom dropdowns (hidden input), get text from selectedText element
        if (fieldElement.type === 'hidden') {
            const fieldId = fieldElement.id;
            let selectedTextId = '';
            
            if (fieldId === 'Applicant') {
                selectedTextId = 'applicantSelectedText';
            } else if (fieldId === 'Company') {
                selectedTextId = 'companySelectedText';
            } else if (fieldId === 'PurchaseRequestType') {
                selectedTextId = 'purchaseRequestTypeSelectedText';
            } else if (fieldId === 'PurchaseRequestSubType') {
                selectedTextId = 'purchaseRequestSubTypeSelectedText';
            }
            
            if (selectedTextId) {
                const selectedTextElement = document.getElementById(selectedTextId);
                if (selectedTextElement) {
                    return selectedTextElement.textContent || '';
                }
            }
            return fieldElement.value || '';
        }
        
        // For regular select elements
        if (fieldElement.tagName === 'SELECT') {
            const selectedOption = fieldElement.options[fieldElement.selectedIndex];
            if (selectedOption && selectedOption.value) {
                return selectedOption.textContent || selectedOption.text || selectedOption.value;
            }
        }
        
        return '';
    }

    /**
     * Show validation message (toast/alert)
     * @param {string} message - Message to show
     */
    static showValidationMessage(message) {
        // Use SweetAlert2 if available, otherwise use alert
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: message,
                confirmButtonText: 'OK'
            });
        } else {
            alert(message);
        }
    }

    /**
     * Create alert message
     * @param {string} type - Alert type (success, error, warning, info)
     * @param {string} message - Message to display
     * @param {number} autoHide - Auto hide after milliseconds (null = don't auto hide)
     */
    static createAlert(type, message, autoHide = null) {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.procurement-alert');
        existingAlerts.forEach(alert => alert.remove());

        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show procurement-alert`;
        alertDiv.setAttribute('role', 'alert');
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

        // Insert at top of form
        const form = document.getElementById('procurement-wizard-form');
        if (form) {
            form.insertBefore(alertDiv, form.firstChild);
        } else {
            document.body.insertBefore(alertDiv, document.body.firstChild);
        }

        // Auto hide if specified
        if (autoHide) {
            setTimeout(() => {
                alertDiv.remove();
            }, autoHide);
        }
    }

    /**
     * Generic function to render dropdown with search
     * @param {Object} config - Configuration object
     * @param {Array} config.items - Array of items to display
     * @param {string} config.searchTerm - Search term to filter items
     * @param {string} config.dropdownItemsId - ID of dropdown items container
     * @param {string} config.hiddenInputId - ID of hidden input to store selected value
     * @param {string} config.selectedTextId - ID of element to display selected text
     * @param {string} config.searchInputId - ID of search input field
     * @param {string} config.dropdownBtnId - ID of dropdown button
     * @param {Function} config.getValue - Function to get value from item
     * @param {Function} config.getText - Function to get text from item
     * @param {Function} config.getSearchableText - Optional function to get searchable text from item
     * @param {number} config.limit - Maximum number of items to display initially (default: 10, but all items are shown with scrollbar)
     * @param {Function} config.onItemSelect - Optional callback when item is selected
     */
    static renderDropdownWithSearch(config) {
        const {
            items,
            searchTerm = '',
            dropdownItemsId,
            hiddenInputId,
            selectedTextId,
            searchInputId,
            dropdownBtnId,
            getValue,
            getText,
            getSearchableText,
            limit = 10 // Increased default limit, but all items will be shown with scrollbar
        } = config;

        const dropdownItems = document.getElementById(dropdownItemsId);
        const hiddenInput = document.getElementById(hiddenInputId);
        const selectedText = document.getElementById(selectedTextId);
        
        if (!dropdownItems) return;

        // Filter items based on search term
        let filteredItems = items;
        if (searchTerm) {
            filteredItems = items.filter(item => {
                const searchableText = getSearchableText ? getSearchableText(item) : getText(item);
                return searchableText.toLowerCase().includes(searchTerm.toLowerCase());
            });
        }

        // Clear existing items
        dropdownItems.innerHTML = '';

        if (filteredItems.length === 0) {
            dropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No items found</div>';
            return;
        }

        // Ensure dropdown menu has scrollbar styling
        const dropdownMenu = dropdownItems.closest('.dropdown-menu');
        if (dropdownMenu) {
            dropdownMenu.style.maxHeight = '300px';
            dropdownMenu.style.overflowY = 'auto';
            dropdownMenu.style.overflowX = 'hidden';
        }

        // Render ALL filtered items (not limited) - user can scroll to see all
        // This allows users to scroll through all items even if they forget the exact name
        filteredItems.forEach(item => {
            const value = getValue(item);
            const text = getText(item);
            
            const li = document.createElement('li');
            li.className = 'dropdown-item';
            li.style.cursor = 'pointer';
            li.textContent = text;
            
            // Add hover effect for better UX
            li.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f5f5f9';
            });
            li.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
            
            li.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                // Set selected value
                if (hiddenInput) hiddenInput.value = value;
                if (selectedText) selectedText.textContent = text;
                
                // Clear search input
                const searchInput = document.getElementById(searchInputId);
                if (searchInput) {
                    searchInput.value = '';
                }
                
                // Re-render dropdown with cleared search
                if (config.onItemSelect) {
                    config.onItemSelect(item, value, text);
                } else {
                    // Default re-render using static method
                    ProcurementWizardUtils.renderDropdownWithSearch({
                        ...config,
                        searchTerm: ''
                    });
                }
                
                // Close dropdown
                const dropdown = bootstrap.Dropdown.getInstance(document.getElementById(dropdownBtnId));
                if (dropdown) {
                    dropdown.hide();
                }
                
                // Trigger change event for validation
                if (hiddenInput) {
                    hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
            
            dropdownItems.appendChild(li);
        });

        // Show item count indicator if there are many items (helpful for user to know they can scroll)
        if (filteredItems.length > limit) {
            const infoLi = document.createElement('li');
            infoLi.className = 'px-3 py-2 text-muted text-center small border-top';
            infoLi.style.fontSize = '0.75rem';
            infoLi.style.backgroundColor = '#f8f9fa';
            infoLi.innerHTML = `<i class="icon-base bx bx-info-circle me-1"></i>Showing ${filteredItems.length} item(s) - scroll to see all`;
            dropdownItems.appendChild(infoLi);
        }
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.ProcurementWizardUtils = ProcurementWizardUtils;
}

