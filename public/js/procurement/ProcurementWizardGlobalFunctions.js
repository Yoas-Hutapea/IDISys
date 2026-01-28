/**
 * Procurement Wizard Global Functions
 * Global functions for onclick handlers and other global operations
 */

// Global function to edit item row
window.editRow = async function(button) {
    const row = button.closest('tr');
    const itemDataStr = row.getAttribute('data-item-data');
    
    if (!itemDataStr) {
        console.error('Item data not found in row');
        return;
    }
    
    try {
        const itemData = JSON.parse(itemDataStr);
        
        // Show add item form first (this will load dropdowns if needed)
        if (window.procurementWizard) {
            if (window.procurementWizard.showAddItemForm) {
                window.procurementWizard.showAddItemForm(true);
            }
            
            // Ensure dropdowns are loaded before setting values
            const unitField = document.getElementById('unit');
            const currencyField = document.getElementById('currency');
            
            // Load dropdowns if they're empty
            if (unitField && (!window.procurementWizard.allUnits || window.procurementWizard.allUnits.length === 0)) {
                if (window.procurementWizard.loadUnitsFromApi) {
                    await window.procurementWizard.loadUnitsFromApi();
                }
            }
            if (currencyField && (!window.procurementWizard.allCurrencies || window.procurementWizard.allCurrencies.length === 0)) {
                if (window.procurementWizard.loadCurrenciesFromApi) {
                    await window.procurementWizard.loadCurrenciesFromApi();
                }
            }
            
            // Wait a bit more to ensure dropdowns are fully populated
            await new Promise(resolve => setTimeout(resolve, 200));
        }
        
        // Fill form with item data
        const itemIdField = document.getElementById('itemId');
        const itemNameField = document.getElementById('itemName');
        const descriptionField = document.getElementById('description');
        const unitField = document.getElementById('unit');
        const currencyField = document.getElementById('currency');
        const quantityField = document.getElementById('quantity');
        const unitPriceField = document.getElementById('unitPrice');
        const mstPROPurchaseItemInventoryItemIDField = document.getElementById('mstPROPurchaseItemInventoryItemID') || document.getElementById('mstPROInventoryItemID');
        
        if (itemIdField) {
            itemIdField.value = itemData.itemId || itemData.itemID || '';
            // Remove validation error when item ID is set
            if (itemIdField.value) {
                itemIdField.classList.remove('is-invalid');
                if (window.procurementWizard && window.procurementWizard.validationModule && window.procurementWizard.validationModule.hideFieldError) {
                    window.procurementWizard.validationModule.hideFieldError(itemIdField);
                }
            }
        }
        if (itemNameField) itemNameField.value = itemData.itemName || '';
        if (descriptionField) descriptionField.value = itemData.itemDescription || '';
        
        // Set unit - dropdown should already be populated
        if (unitField) {
            const unitValue = itemData.itemUnit || '';
            const unitSelectedText = document.getElementById('unitSelectedText');
            
            if (unitValue && window.procurementWizard && window.procurementWizard.allUnits) {
                // Find unit from allUnits array
                const unit = window.procurementWizard.allUnits.find(u => {
                    const unitId = (u.UnitId || u.unitId || '').toString();
                    const unitText = (u.Unit || u.unit || u.UnitId || u.unitId || '').toString();
                    return unitId === unitValue || unitText === unitValue;
                });
                
                if (unit) {
                    const unitId = unit.UnitId || unit.unitId;
                    const unitText = unit.Unit || unit.unit || unit.UnitId || unit.unitId;
                    unitField.value = unitId;
                    if (unitSelectedText) {
                        unitSelectedText.textContent = unitText;
                    }
                    
                    // Remove validation error when unit is set
                    const unitDropdownBtn = document.getElementById('unitDropdownBtn');
                    if (unitDropdownBtn) {
                        unitDropdownBtn.classList.remove('is-invalid');
                        if (window.procurementWizard && window.procurementWizard.validationModule && window.procurementWizard.validationModule.hideFieldError) {
                            window.procurementWizard.validationModule.hideFieldError(unitDropdownBtn);
                        }
                    }
                } else if (unitValue) {
                    // If unit not found in list, set value and text directly
                    unitField.value = unitValue;
                    if (unitSelectedText) {
                        unitSelectedText.textContent = unitValue;
                    }
                    
                    // Remove validation error when unit is set
                    const unitDropdownBtn = document.getElementById('unitDropdownBtn');
                    if (unitDropdownBtn) {
                        unitDropdownBtn.classList.remove('is-invalid');
                        if (window.procurementWizard && window.procurementWizard.validationModule && window.procurementWizard.validationModule.hideFieldError) {
                            window.procurementWizard.validationModule.hideFieldError(unitDropdownBtn);
                        }
                    }
                }
            } else if (unitValue) {
                // Fallback: set value directly
                unitField.value = unitValue;
                if (unitSelectedText) {
                    unitSelectedText.textContent = unitValue;
                }
                
                // Remove validation error when unit is set
                const unitDropdownBtn = document.getElementById('unitDropdownBtn');
                if (unitDropdownBtn) {
                    unitDropdownBtn.classList.remove('is-invalid');
                    if (window.procurementWizard && window.procurementWizard.validationModule && window.procurementWizard.validationModule.hideFieldError) {
                        window.procurementWizard.validationModule.hideFieldError(unitDropdownBtn);
                    }
                }
            }
        }
        
        // Set currency - dropdown should already be populated
        if (currencyField) {
            const currencyValue = itemData.currencyCode || 'IDR';
            const currencySelectedText = document.getElementById('currencySelectedText');
            
            if (currencyValue && window.procurementWizard && window.procurementWizard.allCurrencies) {
                // Find currency from allCurrencies array
                const currency = window.procurementWizard.allCurrencies.find(c => {
                    const currencyCode = (c.CurrencyCode || c.currencyCode || '').toString();
                    return currencyCode === currencyValue || currencyCode.toUpperCase() === currencyValue.toUpperCase();
                });
                
                if (currency) {
                    const currencyCode = currency.CurrencyCode || currency.currencyCode || '';
                    currencyField.value = currencyCode;
                    if (currencySelectedText) {
                        currencySelectedText.textContent = currencyCode;
                    }
                } else if (currencyValue) {
                    // If currency not found in list, set value and text directly
                    currencyField.value = currencyValue;
                    if (currencySelectedText) {
                        currencySelectedText.textContent = currencyValue;
                    }
                }
            } else if (currencyValue) {
                // Fallback: set value directly
                currencyField.value = currencyValue;
                if (currencySelectedText) {
                    currencySelectedText.textContent = currencyValue;
                }
            }
            
            // Remove validation error when currency is set (currency is optional, but clear error if set)
            const currencyDropdownBtn = document.getElementById('currencyDropdownBtn');
            if (currencyDropdownBtn && currencyValue) {
                currencyDropdownBtn.classList.remove('is-invalid');
                if (window.procurementWizard && window.procurementWizard.validationModule && window.procurementWizard.validationModule.hideFieldError) {
                    window.procurementWizard.validationModule.hideFieldError(currencyDropdownBtn);
                }
            }
        }
        
        // Set quantity with formatting
        if (quantityField && itemData.itemQty !== undefined && itemData.itemQty !== null) {
            const quantity = parseFloat(itemData.itemQty) || 0;
            if (window.procurementWizard && window.procurementWizard.formatNumberWithComma) {
                quantityField.value = window.procurementWizard.formatNumberWithComma(quantity, 3);
            } else if (typeof ProcurementWizardUtils !== 'undefined') {
                quantityField.value = ProcurementWizardUtils.formatNumberWithComma(quantity, 3);
            } else {
                quantityField.value = quantity.toString();
            }
            
            // Remove validation error when quantity is set (even if disabled)
            if (quantity > 0) {
                quantityField.classList.remove('is-invalid');
                if (window.procurementWizard && window.procurementWizard.validationModule && window.procurementWizard.validationModule.hideFieldError) {
                    window.procurementWizard.validationModule.hideFieldError(quantityField);
                }
            }
        }
        
        // Set unitPrice with formatting
        if (unitPriceField && itemData.unitPrice !== undefined && itemData.unitPrice !== null) {
            const unitPrice = parseFloat(itemData.unitPrice) || 0;
            if (window.procurementWizard && window.procurementWizard.formatNumberWithComma) {
                unitPriceField.value = window.procurementWizard.formatNumberWithComma(unitPrice, 2);
            } else if (typeof ProcurementWizardUtils !== 'undefined') {
                unitPriceField.value = ProcurementWizardUtils.formatNumberWithComma(unitPrice, 2);
            } else {
                unitPriceField.value = unitPrice.toString();
            }
            
            // Remove validation error when unit price is set
            if (!isNaN(unitPrice)) {
                unitPriceField.classList.remove('is-invalid');
                if (window.procurementWizard && window.procurementWizard.validationModule && window.procurementWizard.validationModule.hideFieldError) {
                    window.procurementWizard.validationModule.hideFieldError(unitPriceField);
                }
            }
        }
        
        // Set amount with formatting
        const amountField = document.getElementById('amount');
        if (amountField && itemData.amount !== undefined && itemData.amount !== null) {
            const amount = parseFloat(itemData.amount) || 0;
            if (window.procurementWizard && window.procurementWizard.formatNumberWithComma) {
                amountField.value = window.procurementWizard.formatNumberWithComma(amount, 2);
            } else if (typeof ProcurementWizardUtils !== 'undefined') {
                amountField.value = ProcurementWizardUtils.formatNumberWithComma(amount, 2);
            } else {
                amountField.value = amount.toString();
            }
        }
        
        if (mstPROPurchaseItemInventoryItemIDField) {
            mstPROPurchaseItemInventoryItemIDField.value = itemData.mstPROPurchaseItemInventoryItemID || itemData.mstPROInventoryItemID || '';
        }
        
        // Calculate amount (this will recalculate if quantity or unitPrice changed)
        if (window.procurementWizard && window.procurementWizard.initializeAmountCalculation) {
            window.procurementWizard.initializeAmountCalculation();
        }
        
        // Mark row for editing (will be replaced when item is added)
        row.setAttribute('data-editing', 'true');
        
        // Scroll to form
        const addItemDetail = document.getElementById('add-item-detail');
        if (addItemDetail) {
            addItemDetail.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    } catch (error) {
        console.error('Error parsing item data:', error);
        alert('Error loading item data for editing');
    }
};

// Global function to delete item row
window.deleteRow = async function(button) {
    const row = button.closest('tr');
    // Get item information for display in confirmation
    const itemId = row.querySelector('td:nth-child(2)')?.textContent?.trim() || '';
    const itemName = row.querySelector('td:nth-child(3)')?.textContent?.trim() || '';
    const itemInfo = itemName ? `${itemName}${itemId ? ` (${itemId})` : ''}` : (itemId || 'this item');
    
    // Use SweetAlert2 for confirmation
    if (typeof Swal !== 'undefined') {
        const result = await Swal.fire({
            title: 'Delete Item',
            text: `Are you sure you want to delete "${itemInfo}"?`,
            icon: 'question',
            showCancelButton: true,
            reverseButtons: true,
            confirmButtonColor: '#696cff',
            cancelButtonColor: '#a8aaae',
            confirmButtonText: '<i class="icon-base bx bx-check me-2"></i>Delete',
            cancelButtonText: 'Cancel',
            allowOutsideClick: false,
            allowEscapeKey: true,
            animation: true,
            customClass: {
                confirmButton: 'btn btn-danger',
                cancelButton: 'btn btn-label-secondary'
            }
        });
        
        if (result.isConfirmed) {
            row.remove();
            if (window.procurementWizard) {
                if (window.procurementWizard.updateAmountTotal) {
                    window.procurementWizard.updateAmountTotal();
                }
                
                // Update pagination after deleting item
                const rowsPerPageSelect = document.getElementById('rowsPerPage');
                const rowsPerPage = rowsPerPageSelect ? parseInt(rowsPerPageSelect.value) : 10;
                if (window.procurementWizard.getTotalDetailItems) {
                    const totalItems = window.procurementWizard.getTotalDetailItems();
                    // Get current page from pagination
                    const activePage = document.querySelector('#detail .paginate_button.active .page-link');
                    let currentPage = 1;
                    if (activePage) {
                        currentPage = parseInt(activePage.getAttribute('data-page')) || 1;
                    }
                    // If current page becomes empty after deletion, go to previous page
                    if (totalItems > 0 && currentPage > Math.ceil(totalItems / rowsPerPage)) {
                        currentPage = Math.ceil(totalItems / rowsPerPage);
                    }
                    if (window.procurementWizard.updateDetailPagination) {
                        window.procurementWizard.updateDetailPagination(totalItems, currentPage || 1, rowsPerPage);
                    }
                }
            }
        }
    } else {
        // Fallback to browser confirm if SweetAlert2 is not loaded
        if (confirm(`Are you sure you want to delete "${itemInfo}"?`)) {
            row.remove();
            if (window.procurementWizard) {
                if (window.procurementWizard.updateAmountTotal) {
                    window.procurementWizard.updateAmountTotal();
                }
                
                // Update pagination after deleting item
                const rowsPerPageSelect = document.getElementById('rowsPerPage');
                const rowsPerPage = rowsPerPageSelect ? parseInt(rowsPerPageSelect.value) : 10;
                if (window.procurementWizard.getTotalDetailItems) {
                    const totalItems = window.procurementWizard.getTotalDetailItems();
                    const activePage = document.querySelector('#detail .paginate_button.active .page-link');
                    let currentPage = 1;
                    if (activePage) {
                        currentPage = parseInt(activePage.getAttribute('data-page')) || 1;
                    }
                    if (totalItems > 0 && currentPage > Math.ceil(totalItems / rowsPerPage)) {
                        currentPage = Math.ceil(totalItems / rowsPerPage);
                    }
                    if (window.procurementWizard.updateDetailPagination) {
                        window.procurementWizard.updateDetailPagination(totalItems, currentPage || 1, rowsPerPage);
                    }
                }
            }
        }
    }
};

// Global function to delete document
window.deleteDocument = async function(button) {
    const row = button.closest('tr');
    const fileName = row.querySelector('td:nth-child(2)')?.textContent?.trim() || 'this document';
    
    // Use SweetAlert2 for confirmation
    if (typeof Swal !== 'undefined') {
        const result = await Swal.fire({
            title: 'Delete Document',
            text: `Are you sure you want to delete "${fileName}"?`,
            icon: 'question',
            showCancelButton: true,
            reverseButtons: true,
            confirmButtonColor: '#696cff',
            cancelButtonColor: '#a8aaae',
            confirmButtonText: '<i class="icon-base bx bx-check me-2"></i>Delete',
            cancelButtonText: 'Cancel',
            allowOutsideClick: false,
            allowEscapeKey: true,
            animation: true,
            customClass: {
                confirmButton: 'btn btn-danger',
                cancelButton: 'btn btn-label-secondary'
            }
        });
        
        if (result.isConfirmed) {
            // Check if document is already saved (has ID)
            const documentDataAttr = row.getAttribute('data-document-data');
            let documentData = null;
            if (documentDataAttr) {
                try {
                    documentData = JSON.parse(documentDataAttr);
                } catch (e) {
                    console.error('Error parsing document data:', e);
                }
            }
            
            // If document is already saved, mark documents as not saved so backend will sync on next save
            if (documentData && documentData.id) {
                localStorage.removeItem('procurementDraftDocumentsSaved');
            }
            
            // Remove File object from Map
            if (window.procurementWizard && window.procurementWizard.documentFiles) {
                window.procurementWizard.documentFiles.delete(fileName);
            }
            
            row.remove();
            // Update pagination after removing document
            if (window.procurementWizard) {
                const documentRowsPerPageSelect = document.getElementById('documentRowsPerPage');
                const rowsPerPage = documentRowsPerPageSelect ? parseInt(documentRowsPerPageSelect.value) : 10;
                if (window.procurementWizard.getTotalDocumentItems) {
                    const totalItems = window.procurementWizard.getTotalDocumentItems();
                    if (window.procurementWizard.updateDocumentPagination) {
                        window.procurementWizard.updateDocumentPagination(totalItems, 1, rowsPerPage);
                    }
                }
            }
        }
    } else {
        // Fallback to browser confirm if SweetAlert2 is not loaded
        if (confirm(`Are you sure you want to delete "${fileName}"?`)) {
            // Remove File object from Map
            if (window.procurementWizard && window.procurementWizard.documentFiles) {
                window.procurementWizard.documentFiles.delete(fileName);
            }
            
            row.remove();
            // Update pagination after removing document
            if (window.procurementWizard) {
                const documentRowsPerPageSelect = document.getElementById('documentRowsPerPage');
                const rowsPerPage = documentRowsPerPageSelect ? parseInt(documentRowsPerPageSelect.value) : 10;
                if (window.procurementWizard.getTotalDocumentItems) {
                    const totalItems = window.procurementWizard.getTotalDocumentItems();
                    if (window.procurementWizard.updateDocumentPagination) {
                        window.procurementWizard.updateDocumentPagination(totalItems, 1, rowsPerPage);
                    }
                }
            }
        }
    }
};

// Global function to select user from modal
window.selectUser = function(employeeId, employeeName, targetFieldId = null) {
    let targetField = null;
    let targetHiddenField = null;
    
    // If targetFieldId is provided, use it
    if (targetFieldId) {
        targetField = document.getElementById(targetFieldId);
        // Find corresponding hidden input for employee ID (e.g., reviewedBy -> reviewedById)
        const hiddenFieldId = targetFieldId + 'Id';
        targetHiddenField = document.getElementById(hiddenFieldId);
        
        // If hidden field not found, try to find it in the same input-group
        if (!targetHiddenField && targetField) {
            const inputGroup = targetField.closest('.input-group');
            if (inputGroup) {
                targetHiddenField = inputGroup.querySelector(`input[type="hidden"][id="${hiddenFieldId}"]`) || 
                                   inputGroup.querySelector(`input[type="hidden"][name="${hiddenFieldId}"]`);
            }
        }
    }
    
    // Otherwise, try to find the active field
    if (!targetField) {
        targetField = document.activeElement.closest('.input-group')?.querySelector('input[type="text"]');
        if (targetField && targetField.id) {
            targetHiddenField = document.getElementById(targetField.id + 'Id');
        }
    }
    
    // If still not found, get from modal's data attribute
    if (!targetField) {
        const modal = document.getElementById('userSearchModal');
        if (modal) {
            const fieldIdFromModal = modal.getAttribute('data-target-field');
            if (fieldIdFromModal) {
                targetField = document.getElementById(fieldIdFromModal);
                targetHiddenField = document.getElementById(fieldIdFromModal + 'Id');
            }
        }
    }
    
    if (targetField) {
        // Set the display name in the visible input field
        targetField.value = employeeName || '';
        
        // Remove error message and invalid class when employee is selected
        targetField.classList.remove('is-invalid');
        if (window.procurementWizard && window.procurementWizard.validationModule && window.procurementWizard.validationModule.hideFieldError) {
            window.procurementWizard.validationModule.hideFieldError(targetField);
        }
        
        // Set the employee ID in the hidden input field
        if (targetHiddenField) {
            targetHiddenField.value = employeeId || '';
            // Verify the value was set correctly
            if (targetHiddenField.value !== employeeId) {
                console.error('WARNING: Hidden field value mismatch!', {
                    expected: employeeId,
                    actual: targetHiddenField.value
                });
            }
            
            // Remove error from hidden field as well
            targetHiddenField.classList.remove('is-invalid');
            if (window.procurementWizard && window.procurementWizard.validationModule && window.procurementWizard.validationModule.hideFieldError) {
                window.procurementWizard.validationModule.hideFieldError(targetHiddenField);
            }
            
            // Trigger change event to clear validation errors via event listener
            targetHiddenField.dispatchEvent(new Event('change', { bubbles: true }));
        } else {
            // If hidden field doesn't exist, create it or store in data attribute
            targetField.setAttribute('data-employee-id', employeeId || '');
            console.warn('Hidden field not found! Expected:', targetFieldId + 'Id');
        }
        
        // Also trigger change event on visible field to ensure error is cleared
        targetField.dispatchEvent(new Event('change', { bubbles: true }));
    } else {
        console.error('Target field not found!', { targetFieldId, employeeId, employeeName });
    }
    
    const modal = bootstrap.Modal.getInstance(document.getElementById('userSearchModal'));
    if (modal) modal.hide();
};

// Global function to select item from choose item table
window.selectItem = async function(itemId, itemName, mstPROInventoryItemID, mstPROPurchaseItemUnitId) {
    // Show loading state
    const itemIdField = document.getElementById('itemId');
    const itemNameField = document.getElementById('itemName');
    const descriptionField = document.getElementById('description');
    const mstPROPurchaseItemInventoryItemIDField = document.getElementById('mstPROPurchaseItemInventoryItemID') || document.getElementById('mstPROInventoryItemID');
    
    if (!itemIdField || !itemNameField || !descriptionField) return;
    
    // Show loading in fields
    itemIdField.disabled = true;
    itemIdField.value = 'Loading...';
    itemNameField.disabled = true;
    itemNameField.value = 'Loading...';
    descriptionField.disabled = true;
    descriptionField.value = 'Loading...';
    
    try {
        // Get PurchaseRequestType and PurchaseRequestSubType from Basic Information form
        const purchaseRequestTypeSelect = document.getElementById('PurchaseRequestType');
        const purchaseRequestSubTypeSelect = document.getElementById('PurchaseRequestSubType');
        const purchaseRequestType = purchaseRequestTypeSelect ? purchaseRequestTypeSelect.value : null;
        const purchaseRequestSubType = purchaseRequestSubTypeSelect ? purchaseRequestSubTypeSelect.value : null;
        
        // Build endpoint with filters
        let endpoint = `/Procurement/Master/Inventories?itemName=${encodeURIComponent(itemName)}`;
        if (purchaseRequestType) {
            endpoint += `&purchaseRequestType=${encodeURIComponent(purchaseRequestType)}`;
        }
        if (purchaseRequestSubType) {
            endpoint += `&purchaseRequestSubType=${encodeURIComponent(purchaseRequestSubType)}`;
        }
        
        // Use apiHelper to call the API
        const data = await apiCall('Procurement', endpoint, 'GET');
        const result = data.data || data;
        
        // Find the matching item (in case multiple items are returned)
        // Priority: Find by ItemID match first, then by itemName, then by ID
        let item = null;
        if (Array.isArray(result) && result.length > 0) {
            // First, try to find by ItemID match (this is the most accurate)
            if (itemId) {
                item = result.find(i => {
                    const invItemID = i.ItemID || i.itemID || i.ItemId || i.itemId || '';
                    return invItemID.toString().trim().toLowerCase() === itemId.toString().trim().toLowerCase();
                });
            }
            
            // If not found by ItemID, try to find by itemName
            if (!item && itemName) {
                item = result.find(i => {
                    const invItemName = i.ItemName || i.itemName || i.Item || i.item || '';
                    return invItemName.toString().trim().toLowerCase() === itemName.toString().trim().toLowerCase();
                });
            }
            
            // If still not found and mstPROInventoryItemID provided, try by ID
            if (!item && mstPROInventoryItemID) {
                item = result.find(i => (i.ID || i.id || i.Id) === mstPROInventoryItemID);
            }
            
            // Last resort: use first item
            if (!item) {
                item = result[0];
            }
        } else if (result) {
            item = result;
        }
        
        if (item) {
            // Get Item ID from API response
            const itemIdFromApi = item.ItemID || item.itemID || item.ItemId || item.itemId || itemId;
            
            // Check if Sonumb section is active and validate ItemID + Sonumb usage
            const sonumbSection = document.getElementById('sonumbSection');
            const subscribeSection = document.getElementById('subscribeSection');
            const isSonumbSectionVisible = sonumbSection && sonumbSection.style.display !== 'none';
            const isSubscribeSectionVisible = subscribeSection && subscribeSection.style.display !== 'none';
            const isSonumbSectionActive = isSonumbSectionVisible || isSubscribeSectionVisible;
            
            // Check for duplicate Item ID in grid view (for Sonumb section)
            if (isSonumbSectionActive && itemIdFromApi) {
                const itemDetailTable = document.querySelector('#itemDetailTable tbody');
                if (itemDetailTable) {
                    // Exclude row that is being edited (if any)
                    const existingRows = itemDetailTable.querySelectorAll('tr:not([data-editing="true"])');
                    let isDuplicate = false;
                    
                    existingRows.forEach(row => {
                        const itemDataAttr = row.getAttribute('data-item-data');
                        if (itemDataAttr) {
                            try {
                                const itemData = JSON.parse(itemDataAttr);
                                const existingItemId = itemData.mstPROPurchaseItemInventoryItemID || itemData.mstPROInventoryItemID || itemData.itemID || itemData.itemId || '';
                                
                                // For Sonumb section: ItemID must be unique (no duplicate ItemID allowed)
                                if (existingItemId && existingItemId.toString().trim().toLowerCase() === itemIdFromApi.toString().trim().toLowerCase()) {
                                    isDuplicate = true;
                                }
                            } catch (e) {
                                console.error('Error parsing item data:', e);
                            }
                        }
                    });
                    
                    if (isDuplicate) {
                        // Item ID already exists in grid view
                        itemIdField.disabled = false;
                        itemIdField.value = '';
                        itemNameField.disabled = false;
                        itemNameField.value = '';
                        descriptionField.disabled = false;
                        descriptionField.value = '';
                        if (mstPROPurchaseItemInventoryItemIDField) {
                            mstPROPurchaseItemInventoryItemIDField.value = '';
                        }
                        
                        // Add invalid class to itemIdField
                        itemIdField.classList.add('is-invalid');
                        itemIdField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        setTimeout(() => itemIdField.focus(), 100);
                        
                        if (window.procurementWizard && window.procurementWizard.showValidationMessage) {
                            window.procurementWizard.showValidationMessage('Item ID already exists. For Sonumb section, each Item ID can only be used once.');
                        } else {
                            alert('Item ID already exists. For Sonumb section, each Item ID can only be used once.');
                        }
                        return;
                    }
                }
            }
            
            if (isSonumbSectionActive) {
                // Get Sonumb value
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
                
                // If Sonumb is filled, check if ItemID + Sonumb is already used in other PR
                if (sonumbValue && itemIdFromApi) {
                    try {
                        // Get current PR Number (if exists)
                        const draft = JSON.parse(localStorage.getItem('procurementDraft') || '{}');
                        const currentPRNumber = draft.purchReqNumber || null;
                        
                        // Call API to check if ItemID + Sonumb is already used
                        const checkEndpoint = `/Procurement/PurchaseRequest/PurchaseRequestItems/check-itemid-sonumb?itemId=${encodeURIComponent(itemIdFromApi)}&sonumb=${encodeURIComponent(sonumbValue)}${currentPRNumber ? `&excludePRNumber=${encodeURIComponent(currentPRNumber)}` : ''}`;
                        const checkResult = await apiCall('Procurement', checkEndpoint, 'GET');
                        
                        if (checkResult.isUsed || checkResult.data?.isUsed) {
                            // ItemID + Sonumb already used in other PR with status != 5
                            itemIdField.disabled = false;
                            itemIdField.value = '';
                            itemNameField.disabled = false;
                            itemNameField.value = '';
                            descriptionField.disabled = false;
                            descriptionField.value = '';
                            if (mstPROPurchaseItemInventoryItemIDField) {
                                mstPROPurchaseItemInventoryItemIDField.value = '';
                            }
                            
                            if (window.procurementWizard && window.procurementWizard.showValidationMessage) {
                                window.procurementWizard.showValidationMessage('This Item ID is already used in another SO Number');
                            } else {
                                alert('This Item ID is already used in another SO Number');
                            }
                            return;
                        }
                    } catch (error) {
                        console.error('Error checking ItemID + Sonumb usage:', error);
                        // Continue with item selection even if check fails (to avoid blocking user)
                    }
                }
            }
            
            // Populate form fields
            itemIdField.value = itemIdFromApi;
            
            // Remove validation error when item is selected
            itemIdField.classList.remove('is-invalid');
            if (window.procurementWizard && window.procurementWizard.validationModule && window.procurementWizard.validationModule.hideFieldError) {
                window.procurementWizard.validationModule.hideFieldError(itemIdField);
            }
            
            // Get Item Name (handle case variations)
            const itemNameFromApi = item.ItemName || item.itemName || item.Item || item.item || '';
            itemNameField.value = itemNameFromApi || itemName || '';
            descriptionField.value = itemNameFromApi || itemName || ''; // Item description shows item name as per requirement
            
            // mstPROInventoryItemID should store the ItemID value (string), not the auto increment ID
            let mstPROInventoryItemIDValue = null;
            if (itemIdFromApi) {
                // Store ItemID (string) directly - this is what user wants
                mstPROInventoryItemIDValue = itemIdFromApi.toString().trim();
            }
            
            // Store mstPROInventoryItemID (should be ItemID string value, not auto increment ID)
            if (mstPROPurchaseItemInventoryItemIDField && mstPROInventoryItemIDValue) {
                mstPROPurchaseItemInventoryItemIDField.value = mstPROInventoryItemIDValue;
            }
            
            // Auto-select unit based on mstPROPurchaseItemUnitId from item
            const unitIdFromItem = item.mstPROPurchaseItemUnitId || item.mstPROPurchaseItemUnitID || item.MstPROPurchaseItemUnitId || mstPROPurchaseItemUnitId;
            if (unitIdFromItem && window.procurementWizard) {
                // Ensure units are loaded
                if (!window.procurementWizard.allUnits || window.procurementWizard.allUnits.length === 0) {
                    if (window.procurementWizard.loadUnitsFromApi) {
                        await window.procurementWizard.loadUnitsFromApi();
                    }
                }
                
                // Wait a bit for units to be fully loaded
                await new Promise(resolve => setTimeout(resolve, 100));
                
                // Find unit by ID
                const unit = window.procurementWizard.allUnits.find(u => {
                    const unitId = (u.ID || u.id || u.Id || 0);
                    return unitId === parseInt(unitIdFromItem, 10);
                });
                
                if (unit) {
                    const unitField = document.getElementById('unit');
                    const unitSelectedText = document.getElementById('unitSelectedText');
                    const unitDropdownBtn = document.getElementById('unitDropdownBtn');
                    const unitId = unit.UnitId || unit.unitId;
                    const unitText = unit.Unit || unit.unit || unit.UnitId || unit.unitId;
                    
                    if (unitField) {
                        unitField.value = unitId || '';
                    }
                    if (unitSelectedText) {
                        unitSelectedText.textContent = unitText || 'Select Unit';
                    }
                    
                    // Remove validation error when unit is auto-selected
                    if (unitDropdownBtn) {
                        unitDropdownBtn.classList.remove('is-invalid');
                        if (window.procurementWizard && window.procurementWizard.validationModule && window.procurementWizard.validationModule.hideFieldError) {
                            window.procurementWizard.validationModule.hideFieldError(unitDropdownBtn);
                        }
                    }
                    
                    // Trigger change event for validation
                    if (unitField) {
                        unitField.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            }
            
            // Trigger amount calculation if quantity and unit price are already set
            const quantityField = document.getElementById('quantity');
            const unitPriceField = document.getElementById('unitPrice');
            if (quantityField && quantityField.value && unitPriceField && unitPriceField.value) {
                const amountField = document.getElementById('amount');
                if (amountField && window.procurementWizard && window.procurementWizard.initializeAmountCalculation) {
                    window.procurementWizard.initializeAmountCalculation();
                }
            }
        } else {
            // Fallback: use provided values if API doesn't return item
            itemIdField.value = itemId || '';
            itemNameField.value = itemName || '';
            descriptionField.value = itemName || '';
            if (mstPROPurchaseItemInventoryItemIDField && mstPROInventoryItemID) {
                mstPROPurchaseItemInventoryItemIDField.value = mstPROInventoryItemID;
                // Also update old field for backward compatibility
                const oldField = document.getElementById('mstPROInventoryItemID');
                if (oldField) oldField.value = mstPROInventoryItemID;
            }
        }
        
        // Re-enable fields
        itemIdField.disabled = false;
        // itemNameField should remain disabled (not editable) per AC13
        itemNameField.disabled = true;
        descriptionField.disabled = false;
        
        // Show add item form without resetting (skipReset = true) to preserve the filled data
        if (window.procurementWizard && window.procurementWizard.showAddItemForm) {
            window.procurementWizard.showAddItemForm(true);
        }
        
    } catch (error) {
        console.error('Error fetching item details:', error);
        
        // Fallback: use provided values on error
        itemIdField.value = itemId || '';
        itemNameField.value = itemName || '';
        descriptionField.value = itemName || '';
        if (mstPROPurchaseItemInventoryItemIDField && mstPROInventoryItemID) {
            mstPROPurchaseItemInventoryItemIDField.value = mstPROInventoryItemID;
        }
        
        // Re-enable fields
        itemIdField.disabled = false;
        // itemNameField should remain disabled (not editable) per AC13
        itemNameField.disabled = true;
        descriptionField.disabled = false;
        
        // Show error message
        if (window.procurementWizard && window.procurementWizard.showValidationMessage) {
            window.procurementWizard.showValidationMessage('Failed to fetch item details. Please try again.');
        }
        
        // Show add item form without resetting (skipReset = true) to preserve the filled fallback values
        if (window.procurementWizard && window.procurementWizard.showAddItemForm) {
            window.procurementWizard.showAddItemForm(true);
        }
    }
};

// Global function to cancel create PR
window.cancelCreatePR = function() {
    if (window.procurementWizard && window.procurementWizard.cancelCreatePR) {
        window.procurementWizard.cancelCreatePR();
    } else {
        // Fallback if wizard not initialized
        if (confirm('Are you sure you want to cancel the request? The entered data will be lost!')) {
            window.location.href = '/Procurement/PurchaseRequest/List';
        }
    }
};

// Add CSS for loading spinner
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    .spin {
        animation: spin 1s linear infinite;
    }
`;
document.head.appendChild(style);

// Initialize the wizard when DOM is ready
// Use a function to handle initialization
function initializeProcurementWizard() {
    if (typeof ProcurementWizard !== 'undefined') {
        try {
            window.procurementWizard = new ProcurementWizard();
            console.log('ProcurementWizard initialized successfully');
        } catch (error) {
            console.error('Error initializing ProcurementWizard:', error);
        }
    } else {
        // If class not found, wait a bit and try again (in case scripts are still loading)
        console.warn('ProcurementWizard class not found. Retrying in 100ms...');
        setTimeout(() => {
            if (typeof ProcurementWizard !== 'undefined') {
                try {
                    window.procurementWizard = new ProcurementWizard();
                    console.log('ProcurementWizard initialized successfully (retry)');
                } catch (error) {
                    console.error('Error initializing ProcurementWizard (retry):', error);
                }
            } else {
                console.error('ProcurementWizard class not found after retry. Make sure all required modules are loaded.');
            }
        }, 100);
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeProcurementWizard);
} else {
    // DOM is already ready, initialize immediately
    initializeProcurementWizard();
}

