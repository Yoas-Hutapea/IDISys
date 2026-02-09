/**
 * Procurement Wizard Items Module
 * Handles Items/Detail step: form display, table management, pagination, and amount calculation
 */

class ProcurementWizardItems {
    constructor(wizardInstance) {
        this.wizard = wizardInstance;
    }

    /**
     * Show add item form
     */
    showAddItemForm(skipReset = false) {
        const detailContainer = document.getElementById('detail');
        if (!detailContainer) return;

        // Hide all direct children of detail container except form containers
        Array.from(detailContainer.children).forEach(child => {
            if (child.id !== 'add-item-detail' && child.id !== 'choose-item') {
                if (child.style.display !== 'none') {
                    child.setAttribute('data-original-display', child.style.display || '');
                    child.style.display = 'none';
                }
            }
        });

        // Show the form and hide choose item table
        this.toggleFormElements(true, false);
        
        // Update quantity field based on period field
        if (this.wizard.updateQuantityFieldBasedOnPeriod) {
            this.wizard.updateQuantityFieldBasedOnPeriod();
        }
        
        // Reset form container and clear validation classes (only if not skipping reset)
        if (!skipReset) {
            const formContainer = document.getElementById('addItemForm');
            if (formContainer) {
                formContainer.querySelectorAll('input, select, textarea').forEach(field => {
                    if (field.type === 'checkbox' || field.type === 'radio') {
                        field.checked = false;
                    } else {
                        field.value = '';
                    }
                    field.classList.remove('is-invalid', 'is-valid');
                });
                // Ensure itemNameField remains disabled per AC13
                const itemNameField = document.getElementById('itemName');
                if (itemNameField) {
                    itemNameField.disabled = true;
                }
                
                // Reset unit dropdown
                const unitSelectedText = document.getElementById('unitSelectedText');
                if (unitSelectedText) {
                    unitSelectedText.textContent = 'Select Unit';
                }
            }
        } else {
            // Only clear validation classes, don't reset values
            const formContainer = document.getElementById('addItemForm');
            if (formContainer) {
                formContainer.querySelectorAll('.is-invalid, .is-valid').forEach(field => {
                    field.classList.remove('is-invalid', 'is-valid');
                });
            }
        }
        
        if (this.wizard.initializeAmountCalculation) {
            this.wizard.initializeAmountCalculation();
        }
        // Load units and currencies when showing the add item form
        if (this.wizard.loadUnitsFromApi) {
            this.wizard.loadUnitsFromApi();
        }
        if (this.wizard.loadCurrenciesFromApi) {
            this.wizard.loadCurrenciesFromApi();
        }
    }

    /**
     * Show choose item table
     */
    showChooseItemTable() {
        this.toggleFormElements(false, true);
        // Load items from API when showing the choose item table
        if (this.wizard.loadItemsFromApi) {
            this.wizard.loadItemsFromApi();
        }
        // Add Enter key listeners to search inputs
        this.setupSearchInputListeners();
    }

    /**
     * Setup search input listeners for Enter key
     */
    setupSearchInputListeners() {
        const searchInputs = ['searchItemId', 'searchItemName', 'searchProdPool', 'searchCOA'];
        const self = this;
        searchInputs.forEach(inputId => {
            const input = document.getElementById(inputId);
            if (input) {
                // Remove existing listeners by cloning
                const newInput = input.cloneNode(true);
                input.parentNode.replaceChild(newInput, input);
                
                // Add Enter key listener
                newInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        if (self.wizard.searchItems) {
                            self.wizard.searchItems();
                        }
                    }
                });
            }
        });
    }

    /**
     * Create table row from form data
     */
    createTableRow(formData) {
        const itemId = formData.get('itemId') || '';
        const itemName = formData.get('itemName') || '';
        const description = formData.get('description') || '';
        const unit = formData.get('unit') || '';
        // Remove commas before parsing
        const quantityValue = (formData.get('quantity') || '0').toString().replace(/,/g, '');
        const unitPriceValue = (formData.get('unitPrice') || '0').toString().replace(/,/g, '');
        const quantity = parseFloat(quantityValue) || 0;
        const unitPrice = parseFloat(unitPriceValue) || 0;
        const currency = formData.get('currency') || 'IDR';
        const amount = quantity * unitPrice;
        const mstPROPurchaseItemInventoryItemID = formData.get('mstPROPurchaseItemInventoryItemID') || formData.get('mstPROInventoryItemID') || itemId || null;

        // Store full item data in data attributes
        const itemData = {
            mstPROPurchaseItemInventoryItemID: mstPROPurchaseItemInventoryItemID,
            itemID: itemId,
            itemId: itemId,
            itemName: itemName,
            itemDescription: description,
            itemUnit: unit,
            itemQty: quantity,
            currencyCode: currency,
            unitPrice: unitPrice,
            amount: amount
        };

        const newRow = document.createElement('tr');
        newRow.setAttribute('data-item-data', JSON.stringify(itemData));
        const escapeHtml = this.wizard.escapeHtml || ((text) => {
            if (text == null) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        });
        const formatNumber = this.wizard.formatNumberWithComma || ((value, decimals) => {
            if (typeof ProcurementWizardUtils !== 'undefined') {
                return ProcurementWizardUtils.formatNumberWithComma(value, decimals);
            }
            return value;
        });
        newRow.innerHTML = `
            <td style="text-align: center;">
                <div class="d-flex gap-1 justify-content-center">
                    <button type="button" class="btn btn-sm btn-primary action-btn" title="Edit" onclick="editRow(this)" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;">
                        <i class="bx bx-edit" style="font-size: 16px; line-height: 1;"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger action-btn" title="Delete" onclick="deleteRow(this)" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;">
                        <i class="bx bx-trash" style="font-size: 16px; line-height: 1;"></i>
                    </button>
                </div>
            </td>
            <td>${escapeHtml(itemId)}</td>
            <td>${escapeHtml(itemName)}</td>
            <td>${escapeHtml(description)}</td>
            <td>${escapeHtml(unit)}</td>
            <td>${escapeHtml(currency)}</td>
            <td>${escapeHtml(formatNumber(quantity, 3))}</td>
            <td class="text-end">${escapeHtml(formatNumber(unitPrice, 2))}</td>
            <td class="text-end">${escapeHtml(formatNumber(amount, 2))}</td>
        `;
        
        // Add hover effect for Edit and Delete buttons
        const editBtn = newRow.querySelector('button[title="Edit"]');
        const deleteBtn = newRow.querySelector('button[title="Delete"]');
        
        if (editBtn) {
            editBtn.addEventListener('mouseenter', function() {
                this.className = this.className.replace(/\bbtn-primary\b/g, 'btn-outline-primary');
            });
            editBtn.addEventListener('mouseleave', function() {
                this.className = this.className.replace(/\bbtn-outline-primary\b/g, 'btn-primary');
            });
        }
        
        if (deleteBtn) {
            deleteBtn.addEventListener('mouseenter', function() {
                this.className = this.className.replace(/\bbtn-danger\b/g, 'btn-outline-danger');
            });
            deleteBtn.addEventListener('mouseleave', function() {
                this.className = this.className.replace(/\bbtn-outline-danger\b/g, 'btn-danger');
            });
        }
        
        return newRow;
    }

    /**
     * Create table row from item object (from API)
     */
    createTableRowFromItem(item) {
        const itemId = item.id || item.ID || item.Id || null;
        const mstPROPurchaseItemInventoryItemID = item.mstPROPurchaseItemInventoryItemID || item.mstPROInventoryItemID || item.MstPROPurchaseItemInventoryItemID || item.MstPROInventoryItemID || null;
        const itemIdForDisplay = mstPROPurchaseItemInventoryItemID || item.itemID || item.ItemID || item.itemId || item.ItemId || '';
        const itemName = item.itemName || item.ItemName || '';
        const description = item.itemDescription || item.ItemDescription || item.itemName || item.ItemName || '';
        const unit = item.itemUnit || item.ItemUnit || '';
        const quantity = parseFloat(item.itemQty || item.ItemQty || item.quantity || item.Quantity || '0');
        const unitPrice = parseFloat(item.unitPrice || item.UnitPrice || '0');
        const currency = item.currencyCode || item.CurrencyCode || 'IDR';
        const amount = parseFloat(item.amount || item.Amount || quantity * unitPrice);

        const itemData = {
            id: itemId,
            mstPROPurchaseItemInventoryItemID: mstPROPurchaseItemInventoryItemID,
            itemID: itemIdForDisplay,
            itemId: itemIdForDisplay,
            itemName: itemName,
            itemDescription: description,
            itemUnit: unit,
            itemQty: quantity,
            currencyCode: currency,
            unitPrice: unitPrice,
            amount: amount
        };

        const newRow = document.createElement('tr');
        newRow.setAttribute('data-item-data', JSON.stringify(itemData));
        const escapeHtml = this.wizard.escapeHtml || ((text) => {
            if (text == null) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        });
        const formatNumber = this.wizard.formatNumberWithComma || ((value, decimals) => {
            if (typeof ProcurementWizardUtils !== 'undefined') {
                return ProcurementWizardUtils.formatNumberWithComma(value, decimals);
            }
            return value;
        });
        newRow.innerHTML = `
            <td style="text-align: center;">
                <div class="d-flex gap-1 justify-content-center">
                    <button type="button" class="btn btn-sm btn-primary action-btn" title="Edit" onclick="editRow(this)" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;">
                        <i class="bx bx-edit" style="font-size: 16px; line-height: 1;"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger action-btn" title="Delete" onclick="deleteRow(this)" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;">
                        <i class="bx bx-trash" style="font-size: 16px; line-height: 1;"></i>
                    </button>
                </div>
            </td>
            <td>${escapeHtml(itemIdForDisplay)}</td>
            <td>${escapeHtml(itemName)}</td>
            <td>${escapeHtml(description)}</td>
            <td>${escapeHtml(unit)}</td>
            <td>${escapeHtml(currency)}</td>
            <td>${escapeHtml(formatNumber(quantity, 3))}</td>
            <td class="text-end">${escapeHtml(formatNumber(unitPrice, 2))}</td>
            <td class="text-end">${escapeHtml(formatNumber(amount, 2))}</td>
        `;
        
        // Add hover effect for Edit and Delete buttons
        const editBtn = newRow.querySelector('button[title="Edit"]');
        const deleteBtn = newRow.querySelector('button[title="Delete"]');
        
        if (editBtn) {
            editBtn.addEventListener('mouseenter', function() {
                this.className = this.className.replace(/\bbtn-primary\b/g, 'btn-outline-primary');
            });
            editBtn.addEventListener('mouseleave', function() {
                this.className = this.className.replace(/\bbtn-outline-primary\b/g, 'btn-primary');
            });
        }
        
        if (deleteBtn) {
            deleteBtn.addEventListener('mouseenter', function() {
                this.className = this.className.replace(/\bbtn-danger\b/g, 'btn-outline-danger');
            });
            deleteBtn.addEventListener('mouseleave', function() {
                this.className = this.className.replace(/\bbtn-outline-danger\b/g, 'btn-danger');
            });
        }
        
        return newRow;
    }

    /**
     * Update amount total
     */
    updateAmountTotal() {
        if (!this.wizard.cachedElements || !this.wizard.cachedElements.amountTotal) return;
        
        const rows = document.querySelectorAll('#itemDetailTable tbody tr');
        let total = 0;
        
        rows.forEach(row => {
            const itemDataAttr = row.getAttribute('data-item-data');
            if (itemDataAttr) {
                try {
                    const itemData = JSON.parse(itemDataAttr);
                    total += parseFloat(itemData.amount) || 0;
                } catch (e) {
                    console.error('Error parsing item data:', e);
                }
            }
        });
        
        // Format with comma as thousand separator
        const formatNumber = this.wizard.formatNumberWithComma || ((value, decimals) => {
            if (typeof ProcurementWizardUtils !== 'undefined') {
                return ProcurementWizardUtils.formatNumberWithComma(value, decimals);
            }
            return value;
        });
        this.wizard.cachedElements.amountTotal.value = formatNumber(total, 2);
    }

    /**
     * Get items from table
     */
    getItemsFromTable() {
        const items = [];
        const rows = document.querySelectorAll('#itemDetailTable tbody tr');
        
        rows.forEach(row => {
            const itemDataAttr = row.getAttribute('data-item-data');
            if (itemDataAttr) {
                try {
                    const itemData = JSON.parse(itemDataAttr);
                    items.push({
                        id: itemData.id || itemData.ID || itemData.Id || null,
                        mstPROPurchaseItemInventoryItemID: itemData.mstPROPurchaseItemInventoryItemID || itemData.mstPROInventoryItemID || itemData.itemID || itemData.itemId || null,
                        ItemName: itemData.itemName || '',
                        ItemDescription: itemData.itemDescription || '',
                        ItemUnit: itemData.itemUnit || '',
                        ItemQty: itemData.itemQty || 1,
                        CurrencyCode: itemData.currencyCode || 'IDR',
                        UnitPrice: itemData.unitPrice || 0,
                        Amount: itemData.amount || (itemData.itemQty * itemData.unitPrice)
                    });
                } catch (e) {
                    console.error('Error parsing item data:', e);
                }
            } else {
                // Fallback: Extract from table cells
                const cells = row.querySelectorAll('td');
                if (cells.length < 5) return;
                
                const itemId = cells[1]?.textContent?.trim() || '';
                const itemName = cells[2]?.textContent?.trim() || '';
                const description = cells[3]?.textContent?.trim() || '';
                const unit = cells[4]?.textContent?.trim() || '';
                
                if (itemId && itemName) {
                    items.push({
                        mstPROPurchaseItemInventoryItemID: itemId || null,
                        ItemName: itemName,
                        ItemDescription: description,
                        ItemUnit: unit,
                        ItemQty: 1,
                        CurrencyCode: 'IDR',
                        UnitPrice: 0,
                        Amount: 0
                    });
                }
            }
        });
        
        return items;
    }

    /**
     * Show detail table
     */
    showDetailTable() {
        const detailContainer = document.getElementById('detail');
        if (!detailContainer) {
            console.error('detail container not found');
            return;
        }

        // Ensure detail container itself is visible
        if (detailContainer.style.display === 'none') {
            detailContainer.style.display = '';
        }

        // Initialize pagination for detail table
        const rowsPerPageSelect = document.getElementById('rowsPerPage');
        const rowsPerPage = rowsPerPageSelect ? parseInt(rowsPerPageSelect.value) : 10;
        const totalItems = this.getTotalDetailItems();
        if (this.wizard.updateDetailPagination) {
            this.wizard.updateDetailPagination(totalItems, 1, rowsPerPage);
        }

        // Update amount total when showing detail table
        this.updateAmountTotal();

        // Hide the form containers
        const addItemForm = document.getElementById('add-item-detail');
        const chooseItemTable = document.getElementById('choose-item');
        
        if (addItemForm) {
            addItemForm.style.display = 'none';
        }
        
        if (chooseItemTable) {
            chooseItemTable.style.display = 'none';
        }

        // Show all detail view elements
        Array.from(detailContainer.children).forEach(child => {
            if (child.id !== 'add-item-detail' && child.id !== 'choose-item') {
                const originalDisplay = child.getAttribute('data-original-display');
                if (originalDisplay !== null) {
                    child.style.display = originalDisplay || '';
                    child.removeAttribute('data-original-display');
                } else {
                    child.style.display = '';
                }
            }
        });
    }

    /**
     * Toggle detail elements visibility
     */
    toggleDetailElements(show) {
        const detailContainer = document.getElementById('detail');
        if (!detailContainer) return;

        const detailViewElements = Array.from(detailContainer.children).filter(child => {
            return child.id !== 'add-item-detail' && child.id !== 'choose-item';
        });

        detailViewElements.forEach(el => {
            el.style.display = show ? '' : 'none';
        });
    }

    /**
     * Toggle form elements visibility
     */
    toggleFormElements(showAddForm, showChooseTable) {
        const addItemForm = document.getElementById('add-item-detail');
        const chooseItemTable = document.getElementById('choose-item');
        
        if (addItemForm) addItemForm.style.display = showAddForm ? 'block' : 'none';
        if (chooseItemTable) chooseItemTable.style.display = showChooseTable ? 'block' : 'none';
    }

    /**
     * Initialize amount calculation for quantity and unit price inputs
     */
    initializeAmountCalculation() {
        const quantityInput = document.getElementById('quantity');
        const unitPriceInput = document.getElementById('unitPrice');
        const amountInput = document.getElementById('amount');
        
        if (!quantityInput || !unitPriceInput || !amountInput) return;
        
        // Helper function to remove formatting (remove commas)
        const parseNumber = (value) => {
            if (!value) return 0;
            const cleaned = value.toString().replace(/,/g, '');
            return parseFloat(cleaned) || 0;
        };
        
        const formatNumber = this.wizard.formatNumberWithComma || ((value, decimals) => {
            if (typeof ProcurementWizardUtils !== 'undefined') {
                return ProcurementWizardUtils.formatNumberWithComma(value, decimals);
            }
            return value;
        });
        
        const calculateAmount = () => {
            const quantity = parseNumber(quantityInput.value);
            const unitPrice = parseNumber(unitPriceInput.value);
            const amount = quantity * unitPrice;
            amountInput.value = formatNumber(amount, 2);
        };
        
        // Prevent minus character input
        const preventMinusInput = (input) => {
            input.addEventListener('keydown', (e) => {
                if (e.key === '-' || e.key === 'Minus') {
                    e.preventDefault();
                    return false;
                }
            });
            
            input.addEventListener('paste', (e) => {
                const paste = (e.clipboardData || window.clipboardData).getData('text');
                if (paste.includes('-')) {
                    e.preventDefault();
                    return false;
                }
            });
            
            input.addEventListener('input', (e) => {
                let value = e.target.value;
                if (value.includes('-')) {
                    value = value.replace(/-/g, '');
                    e.target.value = value;
                }
                if (parseNumber(value) < 0) {
                    e.target.value = '';
                }
            });
        };
        
        // Handle quantity input with max 3 decimal places
        quantityInput.addEventListener('input', (e) => {
            const cursorPos = e.target.selectionStart;
            let value = e.target.value;
            
            const cleaned = value.replace(/,/g, '');
            const validPattern = /^\d*\.?\d*$/;
            if (cleaned === '') {
                e.target.value = '';
                calculateAmount();
                return;
            }
            
            // Remove validation error when quantity is filled
            if (cleaned.trim() !== '' && parseFloat(cleaned) > 0) {
                quantityInput.classList.remove('is-invalid');
                if (this.wizard.validationModule && this.wizard.validationModule.hideFieldError) {
                    this.wizard.validationModule.hideFieldError(quantityInput);
                }
            }
            
            if (validPattern.test(cleaned)) {
                const parts = cleaned.split('.');
                if (parts.length === 2 && parts[1].length > 3) {
                    e.target.value = parts[0] + '.' + parts[1].substring(0, 3);
                } else {
                    e.target.value = cleaned;
                }
                
                setTimeout(() => {
                    if (e.target.type !== 'number' && typeof e.target.setSelectionRange === 'function') {
                        e.target.setSelectionRange(cursorPos, cursorPos);
                    }
                }, 0);
            } else {
                const prevValue = e.target.getAttribute('data-prev-value') || '';
                e.target.value = prevValue.replace(/,/g, '');
                setTimeout(() => {
                    if (e.target.type !== 'number' && typeof e.target.setSelectionRange === 'function') {
                        e.target.setSelectionRange(cursorPos - 1, cursorPos - 1);
                    }
                }, 0);
                return;
            }
            
            e.target.setAttribute('data-prev-value', e.target.value);
            calculateAmount();
        });
        
        // Handle unit price input with thousand separator
        unitPriceInput.addEventListener('input', (e) => {
            const cursorPos = e.target.selectionStart;
            let value = e.target.value;
            
            const cleaned = value.replace(/,/g, '');
            const validPattern = /^\d*\.?\d*$/;
            if (cleaned === '') {
                e.target.value = '';
                calculateAmount();
                return;
            }
            
            // Remove validation error when unit price is filled
            if (cleaned.trim() !== '' && !isNaN(parseFloat(cleaned))) {
                unitPriceInput.classList.remove('is-invalid');
                if (this.wizard.validationModule && this.wizard.validationModule.hideFieldError) {
                    this.wizard.validationModule.hideFieldError(unitPriceInput);
                }
            }
            
            if (validPattern.test(cleaned)) {
                const parts = cleaned.split('.');
                let finalValue = cleaned;
                if (parts.length === 2 && parts[1].length > 2) {
                    finalValue = parts[0] + '.' + parts[1].substring(0, 2);
                }
                
                const num = parseFloat(finalValue);
                if (!isNaN(num)) {
                    const formatted = formatNumber(num, 2);
                    
                    const digitsBeforeCursor = cleaned.substring(0, cursorPos).replace(/[^0-9]/g, '').length;
                    let newCursorPos = 0;
                    let digitCount = 0;
                    for (let i = 0; i < formatted.length; i++) {
                        if (formatted[i].match(/[0-9]/)) {
                            digitCount++;
                            if (digitCount === digitsBeforeCursor) {
                                newCursorPos = i + 1;
                                break;
                            }
                        }
                    }
                    if (newCursorPos === 0) {
                        newCursorPos = formatted.length;
                    }
                    
                    e.target.value = formatted;
                    
                    setTimeout(() => {
                        if (e.target.type !== 'number' && typeof e.target.setSelectionRange === 'function') {
                            e.target.setSelectionRange(newCursorPos, newCursorPos);
                        }
                    }, 0);
                } else {
                    e.target.value = finalValue;
                    setTimeout(() => {
                        if (e.target.type !== 'number' && typeof e.target.setSelectionRange === 'function') {
                            e.target.setSelectionRange(cursorPos, cursorPos);
                        }
                    }, 0);
                }
            } else {
                const prevValue = e.target.getAttribute('data-prev-value') || '';
                e.target.value = prevValue.replace(/,/g, '');
                setTimeout(() => {
                    if (e.target.type !== 'number' && typeof e.target.setSelectionRange === 'function') {
                        e.target.setSelectionRange(cursorPos - 1, cursorPos - 1);
                    }
                }, 0);
                return;
            }
            
            e.target.setAttribute('data-prev-value', e.target.value);
            calculateAmount();
        });
        
        // Apply prevent minus to both inputs
        preventMinusInput(quantityInput);
        preventMinusInput(unitPriceInput);
        
        // Format on blur for quantity
        quantityInput.addEventListener('blur', () => {
            const value = parseNumber(quantityInput.value);
            if (!isNaN(value) && value > 0) {
                quantityInput.value = formatNumber(value, 3);
            }
        });
    }

    /**
     * Update detail pagination
     */
    updateDetailPagination(totalItems, currentPage = 1, rowsPerPage = 10) {
        const paginationInfo = document.querySelector('#detail .dataTables_info');
        const paginationContainer = document.querySelector('#detail .dataTables_paginate ul');
        
        if (!paginationInfo || !paginationContainer) return;
        
        if (totalItems === 0) {
            paginationInfo.textContent = 'No entries';
            paginationContainer.innerHTML = '';
            return;
        }
        
        const totalPages = Math.ceil(totalItems / rowsPerPage);
        const startItem = (currentPage - 1) * rowsPerPage + 1;
        const endItem = Math.min(currentPage * rowsPerPage, totalItems);
        
        paginationInfo.textContent = `Showing ${startItem} to ${endItem} of ${totalItems} entries`;
        
        // Generate pagination buttons
        let paginationHTML = '';
        
        // Previous button
        paginationHTML += `
            <li class="paginate_button previous ${currentPage === 1 ? 'disabled' : ''}">
                <a href="#" class="page-link" data-page="${currentPage - 1}">
                    <i class="icon-base bx bx-chevron-left"></i>
                </a>
            </li>
        `;
        
        // Page number buttons
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        
        if (endPage - startPage < maxVisiblePages - 1) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        if (startPage > 1) {
            paginationHTML += `
                <li class="paginate_button">
                    <a href="#" class="page-link" data-page="1">1</a>
                </li>
            `;
            if (startPage > 2) {
                paginationHTML += `<li class="paginate_button disabled"><span class="page-link">...</span></li>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `
                <li class="paginate_button ${i === currentPage ? 'active' : ''}">
                    <a href="#" class="page-link" data-page="${i}">${i}</a>
                </li>
            `;
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHTML += `<li class="paginate_button disabled"><span class="page-link">...</span></li>`;
            }
            paginationHTML += `
                <li class="paginate_button">
                    <a href="#" class="page-link" data-page="${totalPages}">${totalPages}</a>
                </li>
            `;
        }
        
        // Next button
        paginationHTML += `
            <li class="paginate_button next ${currentPage === totalPages ? 'disabled' : ''}">
                <a href="#" class="page-link" data-page="${currentPage + 1}">
                    <i class="icon-base bx bx-chevron-right"></i>
                </a>
            </li>
        `;
        
        paginationContainer.innerHTML = paginationHTML;
        
        // Add click event listeners to pagination buttons
        paginationContainer.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = parseInt(link.getAttribute('data-page'));
                if (page >= 1 && page <= totalPages && !link.closest('.paginate_button').classList.contains('disabled')) {
                    if (this.wizard.handleDetailPageChange) {
                        this.wizard.handleDetailPageChange(page, rowsPerPage);
                    }
                }
            });
        });
        
        // Update visible rows
        this.updateDetailTableVisibility(currentPage, rowsPerPage);
    }

    /**
     * Update detail table visibility based on pagination
     */
    updateDetailTableVisibility(currentPage, rowsPerPage) {
        const rows = document.querySelectorAll('#itemDetailTable tbody tr');
        const startIndex = (currentPage - 1) * rowsPerPage;
        const endIndex = startIndex + rowsPerPage;
        
        rows.forEach((row, index) => {
            if (index >= startIndex && index < endIndex) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    /**
     * Handle detail page change
     */
    handleDetailPageChange(page, rowsPerPage) {
        if (this.wizard.getTotalDetailItems) {
            this.updateDetailPagination(this.wizard.getTotalDetailItems(), page, rowsPerPage);
        }
    }

    /**
     * Get total detail items
     */
    getTotalDetailItems() {
        return document.querySelectorAll('#itemDetailTable tbody tr').length;
    }

    /**
     * Update choose item pagination
     */
    updateChooseItemPagination(totalItems, currentPage = 1, rowsPerPage = 10) {
        const paginationInfo = document.querySelector('#choose-item .dataTables_info');
        const paginationContainer = document.querySelector('#choose-item .dataTables_paginate ul');
        
        if (!paginationInfo || !paginationContainer) return;
        
        if (totalItems === 0) {
            paginationInfo.textContent = 'No entries';
            paginationContainer.innerHTML = '';
            return;
        }
        
        const totalPages = Math.ceil(totalItems / rowsPerPage);
        const startItem = (currentPage - 1) * rowsPerPage + 1;
        const endItem = Math.min(currentPage * rowsPerPage, totalItems);
        
        paginationInfo.textContent = `Showing ${startItem} to ${endItem} of ${totalItems} entries`;
        
        // Generate pagination buttons (similar to detail pagination)
        let paginationHTML = '';
        
        paginationHTML += `
            <li class="paginate_button previous ${currentPage === 1 ? 'disabled' : ''}">
                <a href="#" class="page-link" data-page="${currentPage - 1}">
                    <i class="icon-base bx bx-chevron-left"></i>
                </a>
            </li>
        `;
        
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        
        if (endPage - startPage < maxVisiblePages - 1) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        if (startPage > 1) {
            paginationHTML += `
                <li class="paginate_button">
                    <a href="#" class="page-link" data-page="1">1</a>
                </li>
            `;
            if (startPage > 2) {
                paginationHTML += `<li class="paginate_button disabled"><span class="page-link">...</span></li>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `
                <li class="paginate_button ${i === currentPage ? 'active' : ''}">
                    <a href="#" class="page-link" data-page="${i}">${i}</a>
                </li>
            `;
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHTML += `<li class="paginate_button disabled"><span class="page-link">...</span></li>`;
            }
            paginationHTML += `
                <li class="paginate_button">
                    <a href="#" class="page-link" data-page="${totalPages}">${totalPages}</a>
                </li>
            `;
        }
        
        paginationHTML += `
            <li class="paginate_button next ${currentPage === totalPages ? 'disabled' : ''}">
                <a href="#" class="page-link" data-page="${currentPage + 1}">
                    <i class="icon-base bx bx-chevron-right"></i>
                </a>
            </li>
        `;
        
        paginationContainer.innerHTML = paginationHTML;
        
        // Add click event listeners
        paginationContainer.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = parseInt(link.getAttribute('data-page'));
                if (page >= 1 && page <= totalPages && !link.closest('.paginate_button').classList.contains('disabled')) {
                    if (this.wizard.handleChooseItemPageChange) {
                        this.wizard.handleChooseItemPageChange(page, rowsPerPage);
                    }
                }
            });
        });
    }

    /**
     * Update choose item table visibility
     */
    updateChooseItemTableVisibility(currentPage, rowsPerPage) {
        const rows = document.querySelectorAll('#chooseItemTableBody tr');
        const startIndex = (currentPage - 1) * rowsPerPage;
        const endIndex = startIndex + rowsPerPage;
        
        rows.forEach((row, index) => {
            if (index >= startIndex && index < endIndex) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    /**
     * Handle choose item page change
     */
    handleChooseItemPageChange(page, rowsPerPage) {
        if (this.wizard.getTotalChooseItems) {
            const totalItems = this.wizard.getTotalChooseItems();
            this.updateChooseItemPagination(totalItems, page, rowsPerPage);
            this.updateChooseItemTableVisibility(page, rowsPerPage);
        }
    }

    /**
     * Get total choose items
     */
    getTotalChooseItems() {
        return document.querySelectorAll('#chooseItemTableBody tr').length;
    }

    /**
     * Render unit dropdown
     */
    renderUnitDropdown(units, searchTerm = '') {
        const unitDropdownItems = document.getElementById('unitDropdownItems');
        const unitHiddenInput = document.getElementById('unit');
        const unitSelectedText = document.getElementById('unitSelectedText');
        
        if (!unitDropdownItems) return;

        // Filter units based on search term
        let filteredUnits = units;
        if (searchTerm) {
            filteredUnits = units.filter(unit => {
                const unitText = (unit.Unit || unit.unit || unit.UnitId || unit.unitId || '').toLowerCase();
                const unitId = (unit.UnitId || unit.unitId || '').toString().toLowerCase();
                return unitText.includes(searchTerm) || unitId.includes(searchTerm);
            });
        }

        // Clear existing items
        unitDropdownItems.innerHTML = '';

        if (filteredUnits.length === 0) {
            unitDropdownItems.innerHTML = '<div class="px-3 py-2 text-muted text-center">No units found</div>';
            return;
        }

        // Limit display to 5 items
        const displayUnits = filteredUnits.slice(0, 5);
        const hasMore = filteredUnits.length > 5;

        // Render units
        displayUnits.forEach(unit => {
            const unitId = unit.UnitId || unit.unitId;
            const unitText = unit.Unit || unit.unit || unit.UnitId || unit.unitId;
            
            const li = document.createElement('li');
            li.className = 'dropdown-item';
            li.style.cursor = 'pointer';
            li.textContent = unitText;
            
            li.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                // Set selected value
                if (unitHiddenInput) unitHiddenInput.value = unitId;
                if (unitSelectedText) unitSelectedText.textContent = unitText;
                
                // Clear search input
                const searchInput = document.getElementById('unitSearchInput');
                if (searchInput) {
                    searchInput.value = '';
                }
                
                // Re-render dropdown with cleared search
                if (this.wizard.allUnits) {
                    this.renderUnitDropdown(this.wizard.allUnits, '');
                }
                
                // Close dropdown
                const dropdown = bootstrap.Dropdown.getInstance(document.getElementById('unitDropdownBtn'));
                if (dropdown) {
                    dropdown.hide();
                }
                
                // Remove validation error when unit is selected
                const unitDropdownBtn = document.getElementById('unitDropdownBtn');
                if (unitDropdownBtn) {
                    unitDropdownBtn.classList.remove('is-invalid');
                    if (this.wizard.validationModule && this.wizard.validationModule.hideFieldError) {
                        this.wizard.validationModule.hideFieldError(unitDropdownBtn);
                    }
                }
                
                // Trigger change event for validation
                if (unitHiddenInput) {
                    unitHiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
            
            unitDropdownItems.appendChild(li);
        });

        // Show "more" indicator if there are more items
        if (hasMore) {
            const moreLi = document.createElement('li');
            moreLi.className = 'px-3 py-2 text-muted text-center small';
            moreLi.textContent = `+${filteredUnits.length - 5} more (use search to find)`;
            unitDropdownItems.appendChild(moreLi);
        }
    }

    /**
     * Show choose item table
     */
    showChooseItemTable() {
        if (this.wizard.toggleFormElements) {
            this.wizard.toggleFormElements(false, true);
        }
        // Load items from API when showing the choose item table
        if (this.wizard.apiModule && this.wizard.apiModule.loadItemsFromApi) {
            this.wizard.apiModule.loadItemsFromApi();
        }
        // Add Enter key listeners to search inputs
        this.setupSearchInputListeners();
    }

    /**
     * Setup search input listeners for Enter key
     */
    setupSearchInputListeners() {
        // Add Enter key listeners to all search inputs
        const searchInputs = ['searchItemId', 'searchItemName', 'searchProdPool', 'searchCOA'];
        const self = this;
        searchInputs.forEach(inputId => {
            const input = document.getElementById(inputId);
            if (input) {
                // Remove existing listeners by cloning
                const newInput = input.cloneNode(true);
                input.parentNode.replaceChild(newInput, input);
                
                // Add Enter key listener
                newInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        self.searchItems();
                    }
                });
            }
        });
    }

    /**
     * Search items
     */
    async searchItems() {
        // Prevent multiple simultaneous searches
        if (this.wizard.isSearching) {
            return;
        }
        
        this.wizard.isSearching = true;
        
        try {
            // Get all search values
            const itemId = document.getElementById('searchItemId')?.value?.trim() || null;
            const itemName = document.getElementById('searchItemName')?.value?.trim() || null;
            const prodPool = document.getElementById('searchProdPool')?.value?.trim() || null;
            const coa = document.getElementById('searchCOA')?.value?.trim() || null;
            
            // Load items with all search parameters
            if (this.wizard.apiModule && this.wizard.apiModule.loadItemsFromApi) {
                await this.wizard.apiModule.loadItemsFromApi(itemId, itemName, prodPool, coa);
            }
        } finally {
            // Always reset isSearching flag
            this.wizard.isSearching = false;
        }
    }

    /**
     * Update choose item pagination
     */
    updateChooseItemPagination(totalItems, currentPage = 1, rowsPerPage = 10) {
        const paginationInfo = document.querySelector('#choose-item .dataTables_info');
        const paginationContainer = document.querySelector('#choose-item .dataTables_paginate ul');
        
        if (!paginationInfo || !paginationContainer) return;
        
        if (totalItems === 0) {
            paginationInfo.textContent = 'No entries';
            paginationContainer.innerHTML = '';
            return;
        }
        
        const totalPages = Math.ceil(totalItems / rowsPerPage);
        const startItem = (currentPage - 1) * rowsPerPage + 1;
        const endItem = Math.min(currentPage * rowsPerPage, totalItems);
        
        paginationInfo.textContent = `Showing ${startItem} to ${endItem} of ${totalItems} entries`;
        
        // Generate pagination buttons
        let paginationHTML = '';
        
        // Previous button
        paginationHTML += `
            <li class="paginate_button previous ${currentPage === 1 ? 'disabled' : ''}">
                <a href="#" class="page-link" data-page="${currentPage - 1}">
                    <i class="icon-base bx bx-chevron-left"></i>
                </a>
            </li>
        `;
        
        // Page number buttons
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        
        if (endPage - startPage < maxVisiblePages - 1) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        if (startPage > 1) {
            paginationHTML += `
                <li class="paginate_button">
                    <a href="#" class="page-link" data-page="1">1</a>
                </li>
            `;
            if (startPage > 2) {
                paginationHTML += `<li class="paginate_button disabled"><span class="page-link">...</span></li>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `
                <li class="paginate_button ${i === currentPage ? 'active' : ''}">
                    <a href="#" class="page-link" data-page="${i}">${i}</a>
                </li>
            `;
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHTML += `<li class="paginate_button disabled"><span class="page-link">...</span></li>`;
            }
            paginationHTML += `
                <li class="paginate_button">
                    <a href="#" class="page-link" data-page="${totalPages}">${totalPages}</a>
                </li>
            `;
        }
        
        // Next button
        paginationHTML += `
            <li class="paginate_button next ${currentPage === totalPages ? 'disabled' : ''}">
                <a href="#" class="page-link" data-page="${currentPage + 1}">
                    <i class="icon-base bx bx-chevron-right"></i>
                </a>
            </li>
        `;
        
        paginationContainer.innerHTML = paginationHTML;
        
        // Add click event listeners to pagination buttons
        paginationContainer.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = parseInt(link.getAttribute('data-page'));
                if (page >= 1 && page <= totalPages && !link.closest('.paginate_button').classList.contains('disabled')) {
                    this.handleChooseItemPageChange(page, rowsPerPage);
                }
            });
        });
    }

    /**
     * Handle choose item page change
     */
    handleChooseItemPageChange(page, rowsPerPage) {
        const totalItems = this.getTotalChooseItems();
        this.updateChooseItemPagination(totalItems, page, rowsPerPage);
        this.updateChooseItemTableVisibility(page, rowsPerPage);
    }

    /**
     * Get total choose items
     */
    getTotalChooseItems() {
        return document.querySelectorAll('#chooseItemTableBody tr').length;
    }

    /**
     * Update choose item table visibility
     */
    updateChooseItemTableVisibility(currentPage, rowsPerPage) {
        const rows = document.querySelectorAll('#chooseItemTableBody tr');
        const startIndex = (currentPage - 1) * rowsPerPage;
        const endIndex = startIndex + rowsPerPage;
        
        rows.forEach((row, index) => {
            if (index >= startIndex && index < endIndex) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    /**
     * Update detail pagination
     */
    updateDetailPagination(totalItems, currentPage = 1, rowsPerPage = 10) {
        const paginationInfo = document.querySelector('#detail .dataTables_info');
        const paginationContainer = document.querySelector('#detail .dataTables_paginate ul');
        
        if (!paginationInfo || !paginationContainer) return;
        
        if (totalItems === 0) {
            paginationInfo.textContent = 'No entries';
            paginationContainer.innerHTML = '';
            return;
        }
        
        const totalPages = Math.ceil(totalItems / rowsPerPage);
        const startItem = (currentPage - 1) * rowsPerPage + 1;
        const endItem = Math.min(currentPage * rowsPerPage, totalItems);
        
        paginationInfo.textContent = `Showing ${startItem} to ${endItem} of ${totalItems} entries`;
        
        // Generate pagination buttons
        let paginationHTML = '';
        
        // Previous button
        paginationHTML += `
            <li class="paginate_button previous ${currentPage === 1 ? 'disabled' : ''}">
                <a href="#" class="page-link" data-page="${currentPage - 1}">
                    <i class="icon-base bx bx-chevron-left"></i>
                </a>
            </li>
        `;
        
        // Page number buttons
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        
        if (endPage - startPage < maxVisiblePages - 1) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        if (startPage > 1) {
            paginationHTML += `
                <li class="paginate_button">
                    <a href="#" class="page-link" data-page="1">1</a>
                </li>
            `;
            if (startPage > 2) {
                paginationHTML += `<li class="paginate_button disabled"><span class="page-link">...</span></li>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `
                <li class="paginate_button ${i === currentPage ? 'active' : ''}">
                    <a href="#" class="page-link" data-page="${i}">${i}</a>
                </li>
            `;
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHTML += `<li class="paginate_button disabled"><span class="page-link">...</span></li>`;
            }
            paginationHTML += `
                <li class="paginate_button">
                    <a href="#" class="page-link" data-page="${totalPages}">${totalPages}</a>
                </li>
            `;
        }
        
        // Next button
        paginationHTML += `
            <li class="paginate_button next ${currentPage === totalPages ? 'disabled' : ''}">
                <a href="#" class="page-link" data-page="${currentPage + 1}">
                    <i class="icon-base bx bx-chevron-right"></i>
                </a>
            </li>
        `;
        
        paginationContainer.innerHTML = paginationHTML;
        
        // Add click event listeners to pagination buttons
        paginationContainer.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = parseInt(link.getAttribute('data-page'));
                if (page >= 1 && page <= totalPages && !link.closest('.paginate_button').classList.contains('disabled')) {
                    this.handleDetailPageChange(page, rowsPerPage);
                }
            });
        });
        
        // Update visible rows
        this.updateDetailTableVisibility(currentPage, rowsPerPage);
    }

    /**
     * Update detail table visibility
     */
    updateDetailTableVisibility(currentPage, rowsPerPage) {
        const rows = document.querySelectorAll('#itemDetailTable tbody tr');
        const startIndex = (currentPage - 1) * rowsPerPage;
        const endIndex = startIndex + rowsPerPage;
        
        rows.forEach((row, index) => {
            if (index >= startIndex && index < endIndex) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    /**
     * Handle detail page change
     */
    handleDetailPageChange(page, rowsPerPage) {
        this.updateDetailPagination(this.getTotalDetailItems(), page, rowsPerPage);
    }

    /**
     * Get total detail items
     */
    getTotalDetailItems() {
        return document.querySelectorAll('#itemDetailTable tbody tr').length;
    }

    /**
     * Load and fill items from API
     */
    async loadAndFillItems(prNumber) {
        if (!prNumber) return;

        const itemDetailTable = document.querySelector('#itemDetailTable tbody');
        if (!itemDetailTable) {
            console.warn('Item detail table not found');
            return;
        }

        try {
            // Call API to get items
            const endpoint = `/Procurement/PurchaseRequest/PurchaseRequestItems/${encodeURIComponent(prNumber)}/items`;
            const itemsData = await apiCall('Procurement', endpoint, 'GET');
            const items = itemsData.data || itemsData;

            if (!items || !Array.isArray(items) || items.length === 0) {
                console.log('No items found for PR:', prNumber);
                itemDetailTable.innerHTML = '';
                // Update pagination and total
                if (this.updateAmountTotal) {
                    this.updateAmountTotal();
                } else if (this.wizard.updateAmountTotal) {
                    this.wizard.updateAmountTotal();
                }
                if (this.updateDetailPagination) {
                    this.updateDetailPagination(0, 1, 10);
                }
                return;
            }

            // Clear existing items
            itemDetailTable.innerHTML = '';

            // Add each item to the table
            items.forEach(item => {
                const row = this.createTableRowFromItem(item);
                itemDetailTable.appendChild(row);
            });

            // Update amount total
            if (this.updateAmountTotal) {
                this.updateAmountTotal();
            } else if (this.wizard.updateAmountTotal) {
                this.wizard.updateAmountTotal();
            }

            // Update pagination
            const rowsPerPageSelect = document.getElementById('rowsPerPage');
            const rowsPerPage = rowsPerPageSelect ? parseInt(rowsPerPageSelect.value) : 10;
            const totalItems = this.getTotalDetailItems();
            if (this.updateDetailPagination) {
                this.updateDetailPagination(totalItems, 1, rowsPerPage);
            }

        } catch (error) {
            console.error('Error loading items:', error);
            // If 404 error or "not found" message, it's okay - items don't exist for this PR yet
            const errorMessage = error.message || error.toString() || '';
            if (errorMessage.includes('not found') || errorMessage.includes('404') || 
                (error.statusCode === 404) || (error.response && error.response.status === 404)) {
                console.log('Items not found for PR:', prNumber, '- This is expected if PR does not have items yet');
                itemDetailTable.innerHTML = '';
                // Update pagination and total
                if (this.updateAmountTotal) {
                    this.updateAmountTotal();
                } else if (this.wizard.updateAmountTotal) {
                    this.wizard.updateAmountTotal();
                }
                if (this.updateDetailPagination) {
                    this.updateDetailPagination(0, 1, 10);
                }
            } else {
                throw error; // Re-throw other errors
            }
        }
    }

    /**
     * Add new item to table from form
     */
    async addNewItemToTable(buttonElement = null) {
        // Find the addItemForm container
        let formContainer = null;
        
        // First, try to find container relative to the button (if provided)
        if (buttonElement) {
            const addItemDetail = buttonElement.closest('#add-item-detail');
            if (addItemDetail) {
                formContainer = addItemDetail.querySelector('#addItemForm');
            }
        }
        
        // If not found, try getElementById
        if (!formContainer) {
            formContainer = document.getElementById('addItemForm');
        }
        
        // If not found directly, try finding it within the add-item-detail container
        if (!formContainer) {
            const addItemDetailContainer = document.getElementById('add-item-detail');
            if (addItemDetailContainer) {
                formContainer = addItemDetailContainer.querySelector('#addItemForm');
            }
        }
        
        if (!formContainer) {
            console.error('addItemForm container not found');
            return;
        }
        
        // Clear previous validation errors
        formContainer.querySelectorAll('.is-invalid').forEach(field => {
            field.classList.remove('is-invalid');
            if (this.wizard.validationModule && this.wizard.validationModule.hideFieldError) {
                this.wizard.validationModule.hideFieldError(field);
            }
        });
        
        // Clear errors for dropdown buttons
        const dropdownButtons = formContainer.querySelectorAll('[id$="DropdownBtn"]');
        dropdownButtons.forEach(btn => {
            btn.classList.remove('is-invalid');
            if (this.wizard.validationModule && this.wizard.validationModule.hideFieldError) {
                this.wizard.validationModule.hideFieldError(btn);
            }
        });
        
        let isValid = true;
        let firstInvalidField = null;
        
        // Field labels for error messages
        const fieldLabels = {
            'itemId': 'Item ID',
            'unit': 'Unit',
            'quantity': 'Qty',
            'unitPrice': 'Unit Price'
        };
        
        // Validate Item ID - must have both itemId input and hidden field mstPROPurchaseItemInventoryItemID
        const itemIdInput = formContainer.querySelector('#itemId');
        const mstPROPurchaseItemInventoryItemID = formContainer.querySelector('#mstPROPurchaseItemInventoryItemID');
        if (itemIdInput && itemIdInput.hasAttribute('required')) {
            const itemIdValue = itemIdInput.value?.trim() || '';
            const hiddenValue = mstPROPurchaseItemInventoryItemID?.value?.trim() || '';
            if (!itemIdValue || !hiddenValue) {
                itemIdInput.classList.add('is-invalid');
                const fieldLabel = fieldLabels['itemId'] || 'Item ID';
                const errorMessage = `${fieldLabel} is required`;
                if (this.wizard.validationModule && this.wizard.validationModule.showFieldError) {
                    this.wizard.validationModule.showFieldError(itemIdInput, errorMessage);
                }
                isValid = false;
                if (!firstInvalidField) {
                    firstInvalidField = itemIdInput;
                }
            } else {
                itemIdInput.classList.remove('is-invalid');
                if (this.wizard.validationModule && this.wizard.validationModule.hideFieldError) {
                    this.wizard.validationModule.hideFieldError(itemIdInput);
                }
            }
        }
        
        // Only check other required fields within this container
        const requiredFields = formContainer.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (field.id === 'itemId') return;
            
            const isEmpty = !field.value.trim();
            if (isEmpty) {
                field.classList.add('is-invalid');
                const fieldName = field.id || field.name || '';
                const fieldLabel = fieldLabels[fieldName] || fieldName;
                const errorMessage = `${fieldLabel} is required`;
                if (this.wizard.validationModule && this.wizard.validationModule.showFieldError) {
                    this.wizard.validationModule.showFieldError(field, errorMessage);
                }
                isValid = false;
                if (!firstInvalidField) {
                    firstInvalidField = field;
                }
            } else {
                field.classList.remove('is-invalid');
                if (this.wizard.validationModule && this.wizard.validationModule.hideFieldError) {
                    this.wizard.validationModule.hideFieldError(field);
                }
            }
        });
        
        // Validate custom dropdowns (hidden inputs with dropdown buttons)
        const customDropdowns = [
            { inputId: '#unit', btnId: '#unitDropdownBtn', label: 'Unit' },
            { inputId: '#currency', btnId: '#currencyDropdownBtn', label: 'Currency' }
        ];
        
        customDropdowns.forEach(({ inputId, btnId, label }) => {
            const hiddenInput = formContainer.querySelector(inputId);
            const dropdownBtn = formContainer.querySelector(btnId);
            if (hiddenInput && dropdownBtn) {
                const isEmpty = !hiddenInput.value || hiddenInput.value.trim() === '';
                if (isEmpty && hiddenInput.hasAttribute('required')) {
                    dropdownBtn.classList.add('is-invalid');
                    const errorMessage = `${label} is required`;
                    if (this.wizard.validationModule && this.wizard.validationModule.showFieldError) {
                        this.wizard.validationModule.showFieldError(dropdownBtn, errorMessage);
                    }
                    isValid = false;
                    if (!firstInvalidField) {
                        firstInvalidField = dropdownBtn;
                    }
                } else {
                    dropdownBtn.classList.remove('is-invalid');
                    if (this.wizard.validationModule && this.wizard.validationModule.hideFieldError) {
                        this.wizard.validationModule.hideFieldError(dropdownBtn);
                    }
                }
            }
        });
        
        if (!isValid) {
            // Focus on first invalid field
            if (this.wizard.focusFirstInvalidField) {
                this.wizard.focusFirstInvalidField(formContainer);
            }
            return;
        }
        
        // Validate quantity - must not be 0
        const quantityInput = formContainer.querySelector('#quantity');
        if (quantityInput) {
            const parseNumber = (value) => {
                if (!value) return 0;
                const cleaned = value.toString().replace(/,/g, '');
                return parseFloat(cleaned) || 0;
            };
            
            const quantityValue = parseNumber(quantityInput.value);
            if (quantityValue === 0) {
                quantityInput.classList.add('is-invalid');
                const errorMessage = 'Qty must be greater than 0';
                if (this.wizard.validationModule && this.wizard.validationModule.showFieldError) {
                    this.wizard.validationModule.showFieldError(quantityInput, errorMessage);
                }
                quantityInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(() => quantityInput.focus(), 100);
                return;
            }
            quantityInput.classList.remove('is-invalid');
            if (this.wizard.validationModule && this.wizard.validationModule.hideFieldError) {
                this.wizard.validationModule.hideFieldError(quantityInput);
            }
        }
        
        // Manually collect form data from inputs within the container
        const formData = new Map();
        formContainer.querySelectorAll('input, select, textarea').forEach(field => {
            if (field.name) {
                formData.set(field.name, field.value);
            }
        });
        
        // Get ItemID and Description for duplicate validation
        const newItemId = formData.get('mstPROPurchaseItemInventoryItemID') || formData.get('mstPROInventoryItemID') || formData.get('itemId') || '';
        const newDescription = (formData.get('description') || '').trim();
        
        if (this.wizard.cachedElements && this.wizard.cachedElements.itemDetailTable) {
            // Check if we're editing an existing row
            const editingRow = this.wizard.cachedElements.itemDetailTable.querySelector('tr[data-editing="true"]');
            
            // Check if Sonumb section is active
            const sonumbSection = document.getElementById('sonumbSection');
            const subscribeSection = document.getElementById('subscribeSection');
            const isSonumbSectionVisible = sonumbSection && sonumbSection.style.display !== 'none';
            const isSubscribeSectionVisible = subscribeSection && subscribeSection.style.display !== 'none';
            const isSonumbSectionActive = isSonumbSectionVisible || isSubscribeSectionVisible;
            
            // Validate duplicate for non-Sonumb section
            if (newItemId && !isSonumbSectionActive) {
                const existingRows = this.wizard.cachedElements.itemDetailTable.querySelectorAll('tbody tr:not([data-editing="true"])');
                let isDuplicate = false;
                
                existingRows.forEach(row => {
                    const itemDataAttr = row.getAttribute('data-item-data');
                    if (itemDataAttr) {
                        try {
                            const itemData = JSON.parse(itemDataAttr);
                            const existingItemId = itemData.mstPROPurchaseItemInventoryItemID || itemData.mstPROInventoryItemID || itemData.itemID || itemData.itemId || '';
                            
                            const existingDescription = (itemData.itemDescription || '').trim();
                            if (existingItemId === newItemId && existingDescription === newDescription) {
                                isDuplicate = true;
                            }
                        } catch (e) {
                            console.error('Error parsing item data:', e);
                        }
                    }
                });
                
                if (isDuplicate) {
                    const descriptionField = formContainer.querySelector('#description');
                    if (descriptionField) {
                        descriptionField.classList.add('is-invalid');
                        const errorMessage = 'Item with the same Item ID and Description already exists. Please use a different Description or update the existing item.';
                        if (this.wizard.validationModule && this.wizard.validationModule.showFieldError) {
                            this.wizard.validationModule.showFieldError(descriptionField, errorMessage);
                        }
                        descriptionField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        setTimeout(() => descriptionField.focus(), 100);
                    }
                    if (this.wizard.showValidationMessage) {
                        this.wizard.showValidationMessage('Item with the same Item ID and Description already exists. Please use a different Description or update the existing item.');
                    }
                    return;
                }
            }
            
            // Additional check for Sonumb section: Check if ItemID + Sonumb is used in other PR
            if (isSonumbSectionActive && newItemId) {
                let sonumbValue = null;
                if (isSonumbSectionVisible) {
                    const sonumbField = document.getElementById('Sonumb');
                    if (sonumbField && sonumbField.value) {
                        sonumbValue = sonumbField.value.trim();
                    }
                } else if (isSubscribeSectionVisible) {
                    const subscribeSonumbField = document.getElementById('SubscribeSonumb');
                    if (subscribeSonumbField && subscribeSonumbField.value) {
                        sonumbValue = subscribeSonumbField.value.trim();
                    }
                }
                
                if (sonumbValue) {
                    try {
                        const draft = JSON.parse(localStorage.getItem('procurementDraft') || '{}');
                        const currentPRNumber = draft.purchReqNumber || null;
                        
                        const checkEndpoint = `/Procurement/PurchaseRequest/PurchaseRequestItems/check-itemid-sonumb?itemId=${encodeURIComponent(newItemId)}&sonumb=${encodeURIComponent(sonumbValue)}${currentPRNumber ? `&excludePRNumber=${encodeURIComponent(currentPRNumber)}` : ''}`;
                        const checkResult = await apiCall('Procurement', checkEndpoint, 'GET');
                        
                        if (checkResult.isUsed || checkResult.data?.isUsed) {
                            const itemIdField = formContainer.querySelector('#itemId');
                            if (itemIdField) {
                                itemIdField.classList.add('is-invalid');
                                const errorMessage = 'This Item ID is already used in another SO Number';
                                if (this.wizard.validationModule && this.wizard.validationModule.showFieldError) {
                                    this.wizard.validationModule.showFieldError(itemIdField, errorMessage);
                                }
                                itemIdField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                setTimeout(() => itemIdField.focus(), 100);
                            }
                            if (this.wizard.showValidationMessage) {
                                this.wizard.showValidationMessage('This Item ID is already used in another SO Number');
                            }
                            return;
                        }
                    } catch (error) {
                        console.error('Error checking ItemID + Sonumb usage:', error);
                    }
                }
            }
            
            if (editingRow) {
                // When editing, replace the editing row with the new row
                editingRow.replaceWith(this.createTableRow(formData));
                if (this.wizard.createAlert) {
                    this.wizard.createAlert('success', '<strong>Item Updated!</strong> Item has been updated successfully.', 3000);
                }
            } else {
                // Add new row
                this.wizard.cachedElements.itemDetailTable.appendChild(this.createTableRow(formData));
                if (this.wizard.createAlert) {
                    this.wizard.createAlert('success', '<strong>Item Added!</strong> New item has been added to your purchase request.', 3000);
                }
            }
            
            if (this.wizard.updateAmountTotal) {
                this.wizard.updateAmountTotal();
            }
            
            // Reset form
            formContainer.querySelectorAll('input, select, textarea').forEach(field => {
                if (field.type === 'checkbox' || field.type === 'radio') {
                    field.checked = false;
                } else {
                    field.value = '';
                }
                field.classList.remove('is-invalid', 'is-valid');
            });
            
            // Update pagination
            const rowsPerPageSelect = document.getElementById('rowsPerPage');
            const rowsPerPage = rowsPerPageSelect ? parseInt(rowsPerPageSelect.value) : 10;
            if (this.wizard.getTotalDetailItems) {
                const totalItems = this.wizard.getTotalDetailItems();
                const newItemPage = Math.ceil(totalItems / rowsPerPage);
                if (this.wizard.updateDetailPagination) {
                    this.wizard.updateDetailPagination(totalItems, newItemPage, rowsPerPage);
                }
            }
            
            // Show detail table
            this.showDetailTable();
        } else {
            console.error('itemDetailTable tbody not found');
        }
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.ProcurementWizardItems = ProcurementWizardItems;
}

