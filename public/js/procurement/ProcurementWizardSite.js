/**
 * Procurement Wizard Site Module
 * Handles Site selection modal: search, pagination, and selection
 */

class ProcurementWizardSite {
    constructor(wizardInstance) {
        this.wizard = wizardInstance;
    }

    /**
     * Initialize choose site modal
     */
    initializeChooseSiteModal() {
        const searchSTIPSonumbBtn = document.getElementById('searchSTIPSonumbBtn');
        if (searchSTIPSonumbBtn) {
            searchSTIPSonumbBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.openChooseSiteModal();
            });
        }
        
        // Initialize Subscribe Sonumb search button
        const searchSubscribeSonumbBtn = document.getElementById('searchSubscribeSonumbBtn');
        if (searchSubscribeSonumbBtn) {
            searchSubscribeSonumbBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.openChooseSiteModal('subscribe');
            });
        }

        // Initialize search filters
        const searchSonumb = document.getElementById('searchSonumb');
        const searchSiteID = document.getElementById('searchSiteID');
        const searchSiteName = document.getElementById('searchSiteName');
        const searchOperator = document.getElementById('searchOperator');

        if (searchSonumb) {
            searchSonumb.addEventListener('input', () => {
                this.debounceSearch(() => this.loadSites());
            });
        }
        if (searchSiteID) {
            searchSiteID.addEventListener('input', () => {
                this.debounceSearch(() => this.loadSites());
            });
        }
        if (searchSiteName) {
            searchSiteName.addEventListener('input', () => {
                this.debounceSearch(() => this.loadSites());
            });
        }
        if (searchOperator) {
            searchOperator.addEventListener('input', () => {
                this.debounceSearch(() => this.loadSites());
            });
        }

        // Initialize rows per page
        const siteRowsPerPage = document.getElementById('siteRowsPerPage');
        if (siteRowsPerPage) {
            siteRowsPerPage.addEventListener('change', () => {
                if (!this.wizard.currentSitePage) {
                    this.wizard.currentSitePage = 1;
                }
                this.wizard.currentSitePage = 1;
                this.loadSites();
            });
        }
        
        // Use event delegation for select site button clicks (handles dynamically created rows)
        // Handles both .select-site-btn (from API module) and .action-select-btn (from Site module)
        const chooseSiteModal = document.getElementById('chooseSiteModal');
        if (chooseSiteModal) {
            chooseSiteModal.addEventListener('click', (e) => {
                const selectBtn = e.target.closest('.select-site-btn, .action-select-btn');
                if (selectBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Get data from attributes (for .select-site-btn from API module)
                    let sonumb = selectBtn.getAttribute('data-sonumb');
                    let siteId = selectBtn.getAttribute('data-site-id');
                    let siteName = selectBtn.getAttribute('data-site-name');
                    let id = selectBtn.getAttribute('data-id') || null;
                    
                    // If data attributes not found, try to get from table row (for .action-select-btn from Site module)
                    if (!sonumb || !siteId || !siteName) {
                        const row = selectBtn.closest('tr');
                        if (row) {
                            const cells = row.querySelectorAll('td');
                            if (cells.length >= 4) {
                                // Assuming: [0]=action, [1]=sonumb, [2]=siteId, [3]=siteName, [4]=operator
                                if (!sonumb) sonumb = cells[1]?.textContent?.trim() || '';
                                if (!siteId) siteId = cells[2]?.textContent?.trim() || '';
                                if (!siteName) siteName = cells[3]?.textContent?.trim() || '';
                            }
                        }
                    }
                    
                    // Use siteId as ID if id is not available
                    if (!id) {
                        id = siteId || null;
                    }
                    
                    if (this.selectSite) {
                        this.selectSite(id, sonumb, siteId, siteName);
                    } else if (this.wizard && this.wizard.selectSite) {
                        this.wizard.selectSite(id, sonumb, siteId, siteName);
                    } else {
                        console.error('selectSite function not available');
                    }
                }
            });
        }
    }

    /**
     * Open choose site modal
     */
    openChooseSiteModal(mode = 'normal') {
        const modalElement = document.getElementById('chooseSiteModal');
        if (modalElement) {
            // Store mode for selectSite function
            this.wizard.currentSiteModalMode = mode;
            
            // Dispose any existing modal instance first
            const existingModal = bootstrap.Modal.getInstance(modalElement);
            if (existingModal) {
                existingModal.dispose();
            }
            
            // Ensure modal is appended to body
            if (modalElement.parentNode !== document.body) {
                document.body.appendChild(modalElement);
            }
            
            // Create and show new modal instance
            const bootstrapModal = new bootstrap.Modal(modalElement);
            
            // Add event handler to fix z-index if backdrop covers modal
            const handleShown = function() {
                const backdrop = document.querySelector('.modal-backdrop:last-child');
                if (backdrop && modalElement) {
                    const backdropZIndex = parseInt(window.getComputedStyle(backdrop).zIndex) || 1040;
                    modalElement.style.zIndex = (backdropZIndex + 10).toString();
                }
                modalElement.removeEventListener('shown.bs.modal', handleShown);
            };
            modalElement.addEventListener('shown.bs.modal', handleShown);
            
            bootstrapModal.show();
            
            // Reset search filters
            const searchSonumb = document.getElementById('searchSonumb');
            const searchSiteID = document.getElementById('searchSiteID');
            const searchSiteName = document.getElementById('searchSiteName');
            const searchOperator = document.getElementById('searchOperator');
            
            if (searchSonumb) searchSonumb.value = '';
            if (searchSiteID) searchSiteID.value = '';
            if (searchSiteName) searchSiteName.value = '';
            if (searchOperator) searchOperator.value = '';
            
            // Reset pagination
            if (!this.wizard.currentSitePage) {
                this.wizard.currentSitePage = 1;
            }
            this.wizard.currentSitePage = 1;
            
            // Load sites
            this.loadSites();
            
            // Clean up event listeners when modal is hidden
            const handleHidden = function cleanup() {
                modalElement.removeEventListener('hidden.bs.modal', cleanup);
                const modalInstance = bootstrap.Modal.getInstance(modalElement);
                if (modalInstance) {
                    modalInstance.dispose();
                }
            };
            modalElement.addEventListener('hidden.bs.modal', handleHidden, { once: true });
        }
    }

    /**
     * Load sites from API
     */
    async loadSites() {
        const tbody = document.getElementById('chooseSiteTableBody');
        const emptyMessage = document.getElementById('chooseSiteEmpty');
        
        if (!tbody) return;

        // Get search filters
        const searchSonumb = document.getElementById('searchSonumb')?.value || '';
        const searchSiteID = document.getElementById('searchSiteID')?.value || '';
        const searchSiteName = document.getElementById('searchSiteName')?.value || '';
        const searchOperator = document.getElementById('searchOperator')?.value || '';
        
        // Get rows per page
        const rowsPerPageSelect = document.getElementById('siteRowsPerPage');
        const rowsPerPage = rowsPerPageSelect ? parseInt(rowsPerPageSelect.value) : 10;
        
        // Show loading
        tbody.innerHTML = '<tr><td colspan="5" class="text-center"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div> Loading sites...</td></tr>';
        if (emptyMessage) emptyMessage.style.display = 'none';

        try {
            // Build endpoint with filters
            let endpoint = '/Procurement/Master/STIPSites';
            const params = new URLSearchParams();
            if (searchSonumb) params.append('sonumb', searchSonumb);
            if (searchSiteID) params.append('siteId', searchSiteID);
            if (searchSiteName) params.append('siteName', searchSiteName);
            if (searchOperator) params.append('operatorName', searchOperator);
            const queryString = params.toString();
            if (queryString) {
                endpoint += '?' + queryString;
            }

            const data = await apiCall('Procurement', endpoint, 'GET');
            const sites = Array.isArray(data) ? data : (data.data || []);

            if (!sites || sites.length === 0) {
                tbody.innerHTML = '';
                if (emptyMessage) emptyMessage.style.display = 'block';
                this.updateSitePagination(0, 1, rowsPerPage);
                return;
            }

            // Paginate
            if (!this.wizard.currentSitePage) {
                this.wizard.currentSitePage = 1;
            }
            const startIndex = (this.wizard.currentSitePage - 1) * rowsPerPage;
            const endIndex = startIndex + rowsPerPage;
            const paginatedSites = sites.slice(startIndex, endIndex);

            // Render table
            tbody.innerHTML = '';
            const escapeHtml = this.wizard.escapeHtml || ((text) => {
                if (text == null) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            });
            
            paginatedSites.forEach((site) => {
                const row = document.createElement('tr');
                
                // Handle both camelCase and PascalCase property names
                const siteId = site.id || site.Id || '';
                const soNumber = site.soNumber || site.SONumber || '';
                const siteID = site.siteID || site.SiteID || '';
                const siteName = site.siteName || site.SiteName || '';
                const operator = site.operator || site.Operator || '';
                
                // Create action button
                const actionCell = document.createElement('td');
                actionCell.className = 'text-center';
                actionCell.style.textAlign = 'center';
                const selectBtn = document.createElement('button');
                selectBtn.type = 'button';
                selectBtn.className = 'btn btn-sm btn-primary action-select-btn action-btn';
                selectBtn.title = 'Select';
                selectBtn.style.width = '32px';
                selectBtn.style.height = '32px';
                selectBtn.style.padding = '0';
                selectBtn.style.display = 'flex';
                selectBtn.style.alignItems = 'center';
                selectBtn.style.justifyContent = 'center';
                selectBtn.style.margin = '0 auto';
                selectBtn.innerHTML = '<i class="bx bx-pointer" style="font-size: 16px; line-height: 1;"></i>';
                selectBtn.addEventListener('click', () => {
                    this.selectSite(siteId, soNumber, siteID, siteName);
                });
                
                // Add hover effect: change from solid to outline on hover
                selectBtn.addEventListener('mouseenter', function() {
                    this.className = this.className.replace(/\bbtn-primary\b/g, 'btn-outline-primary');
                });
                selectBtn.addEventListener('mouseleave', function() {
                    this.className = this.className.replace(/\bbtn-outline-primary\b/g, 'btn-primary');
                });
                
                actionCell.appendChild(selectBtn);
                row.appendChild(actionCell);
                
                // Add other cells
                row.innerHTML += `
                    <td>${escapeHtml(soNumber)}</td>
                    <td>${escapeHtml(siteID)}</td>
                    <td>${escapeHtml(siteName)}</td>
                    <td>${escapeHtml(operator)}</td>
                `;
                
                tbody.appendChild(row);
            });

            // Update pagination
            this.updateSitePagination(sites.length, this.wizard.currentSitePage, rowsPerPage);

        } catch (error) {
            console.error('Error loading sites:', error);
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading sites: ' + (error.message || 'Unknown error') + '</td></tr>';
            if (emptyMessage) emptyMessage.style.display = 'none';
        }
    }

    /**
     * Select site
     */
    selectSite(id, sonumb, siteId, siteName) {
        const mode = this.wizard.currentSiteModalMode || 'normal';
        
        if (mode === 'subscribe') {
            // Set Subscribe Sonumb fields
            const subscribeSonumbField = document.getElementById('SubscribeSonumb');
            const subscribeSonumbIdField = document.getElementById('SubscribeSonumbId');
            const subscribeSiteNameField = document.getElementById('SubscribeSiteName');
            const subscribeSiteIDField = document.getElementById('SubscribeSiteID');

            if (subscribeSonumbField) {
                subscribeSonumbField.value = sonumb;
                // Remove error message and invalid class when sonumb is selected
                subscribeSonumbField.classList.remove('is-invalid');
                if (this.wizard.validationModule && this.wizard.validationModule.hideFieldError) {
                    this.wizard.validationModule.hideFieldError(subscribeSonumbField);
                }
            }
            if (subscribeSonumbIdField) {
                subscribeSonumbIdField.value = id;
                subscribeSonumbIdField.classList.remove('is-invalid');
                // Trigger change event to clear validation errors
                subscribeSonumbIdField.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (subscribeSiteNameField) {
                subscribeSiteNameField.value = siteName ? `${siteName} - ${siteId}` : '';
                subscribeSiteNameField.classList.remove('is-invalid');
                if (this.wizard.validationModule && this.wizard.validationModule.hideFieldError) {
                    this.wizard.validationModule.hideFieldError(subscribeSiteNameField);
                }
            }
            if (subscribeSiteIDField) {
                subscribeSiteIDField.value = siteId || '';
            }
        } else {
            // Set normal Sonumb fields
            const sonumbField = document.getElementById('Sonumb');
            const sonumbIdField = document.getElementById('SonumbId');
            const siteNameField = document.getElementById('SiteName');
            const siteIDField = document.getElementById('SiteID');

            if (sonumbField) {
                sonumbField.value = sonumb;
                // Remove error message and invalid class when sonumb is selected
                sonumbField.classList.remove('is-invalid');
                if (this.wizard.validationModule && this.wizard.validationModule.hideFieldError) {
                    this.wizard.validationModule.hideFieldError(sonumbField);
                }
            }
            if (sonumbIdField) {
                sonumbIdField.value = id;
                sonumbIdField.classList.remove('is-invalid');
                // Trigger change event to clear validation errors
                sonumbIdField.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (siteNameField) {
                siteNameField.value = siteName ? `${siteName} - ${siteId}` : '';
                siteNameField.classList.remove('is-invalid');
                if (this.wizard.validationModule && this.wizard.validationModule.hideFieldError) {
                    this.wizard.validationModule.hideFieldError(siteNameField);
                }
            }
            if (siteIDField) {
                siteIDField.value = siteId || '';
            }
        }

        // Validate Item ID with new Sonumb if item is already selected
        if (this.wizard.validateItemIdWithSonumb) {
            this.wizard.validateItemIdWithSonumb(sonumb, mode);
        }

        // Close modal
        const modal = document.getElementById('chooseSiteModal');
        if (modal) {
            const bootstrapModal = bootstrap.Modal.getInstance(modal);
            if (bootstrapModal) {
                bootstrapModal.hide();
            }
        }
    }

    /**
     * Update site pagination
     */
    updateSitePagination(totalItems, currentPage, rowsPerPage) {
        const paginationInfo = document.getElementById('sitePaginationInfo');
        const pagination = document.getElementById('sitePagination');

        if (paginationInfo) {
            const start = totalItems === 0 ? 0 : (currentPage - 1) * rowsPerPage + 1;
            const end = Math.min(currentPage * rowsPerPage, totalItems);
            paginationInfo.textContent = `Showing ${start} to ${end} of ${totalItems} entries`;
        }

        if (pagination) {
            const totalPages = Math.ceil(totalItems / rowsPerPage);
            pagination.innerHTML = '';

            // Previous button
            const prevLi = document.createElement('li');
            prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
            const prevLink = document.createElement('a');
            prevLink.className = 'page-link';
            prevLink.href = '#';
            prevLink.innerHTML = '&lt;';
            if (currentPage > 1) {
                prevLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.goToSitePage(currentPage - 1);
                });
            }
            prevLi.appendChild(prevLink);
            pagination.appendChild(prevLi);

            // Page numbers
            const maxPages = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxPages / 2));
            let endPage = Math.min(totalPages, startPage + maxPages - 1);
            if (endPage - startPage < maxPages - 1) {
                startPage = Math.max(1, endPage - maxPages + 1);
            }

            for (let i = startPage; i <= endPage; i++) {
                const pageLi = document.createElement('li');
                pageLi.className = `page-item ${i === currentPage ? 'active' : ''}`;
                const pageLink = document.createElement('a');
                pageLink.className = 'page-link';
                pageLink.href = '#';
                pageLink.textContent = i.toString();
                pageLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.goToSitePage(i);
                });
                pageLi.appendChild(pageLink);
                pagination.appendChild(pageLi);
            }

            // Next button
            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
            const nextLink = document.createElement('a');
            nextLink.className = 'page-link';
            nextLink.href = '#';
            nextLink.innerHTML = '&gt;';
            if (currentPage < totalPages) {
                nextLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.goToSitePage(currentPage + 1);
                });
            }
            nextLi.appendChild(nextLink);
            pagination.appendChild(nextLi);
        }
    }

    /**
     * Go to site page
     */
    goToSitePage(page) {
        if (!this.wizard.currentSitePage) {
            this.wizard.currentSitePage = 1;
        }
        this.wizard.currentSitePage = page;
        this.loadSites();
    }

    /**
     * Debounce search
     */
    debounceSearch(callback) {
        if (this.wizard.siteSearchDebounceTimer) {
            clearTimeout(this.wizard.siteSearchDebounceTimer);
        }
        this.wizard.siteSearchDebounceTimer = setTimeout(callback, 300);
    }

    /**
     * Validate Item ID with Sonumb when Sonumb changes
     */
    async validateItemIdWithSonumb(sonumbValue, mode = 'normal') {
        if (!sonumbValue) {
            return; // No Sonumb value, skip validation
        }

        // Get current PR Number (if exists)
        const draft = JSON.parse(localStorage.getItem('procurementDraft') || '{}');
        const currentPRNumber = draft.purchReqNumber || null;

        // Get all items from grid view
        const itemDetailTable = document.querySelector('#itemDetailTable tbody');
        if (!itemDetailTable) {
            return; // No table found, skip validation
        }

        const rows = itemDetailTable.querySelectorAll('tr');
        if (rows.length === 0) {
            return; // No items in grid, skip validation
        }

        // Get Item ID from add item form (if any)
        const itemIdField = document.getElementById('itemId');
        const mstPROPurchaseItemInventoryItemIDField = document.getElementById('mstPROPurchaseItemInventoryItemID') || document.getElementById('mstPROInventoryItemID');
        const formItemId = (mstPROPurchaseItemInventoryItemIDField && mstPROPurchaseItemInventoryItemIDField.value) || 
                          (itemIdField && itemIdField.value) || 
                          '';

        // Collect all Item IDs to validate (from grid view + form)
        const itemIdsToValidate = new Set();
        
        // Add Item ID from form if exists
        if (formItemId && formItemId.trim()) {
            itemIdsToValidate.add(formItemId.trim());
        }

        // Add Item IDs from grid view
        rows.forEach(row => {
            const itemDataAttr = row.getAttribute('data-item-data');
            if (itemDataAttr) {
                try {
                    const itemData = JSON.parse(itemDataAttr);
                    const existingItemId = itemData.mstPROPurchaseItemInventoryItemID || itemData.mstPROInventoryItemID || itemData.itemID || itemData.itemId || '';
                    if (existingItemId && existingItemId.trim()) {
                        itemIdsToValidate.add(existingItemId.trim());
                    }
                } catch (e) {
                    console.error('Error parsing item data:', e);
                }
            }
        });

        if (itemIdsToValidate.size === 0) {
            return; // No Item IDs to validate, skip
        }

        // Validate each Item ID with new Sonumb
        const conflictingItems = []; // Store conflicting items with their details

        for (const itemId of itemIdsToValidate) {
            try {
                // Call API to check if ItemID + Sonumb is already used in other PR
                const checkEndpoint = `/Procurement/PurchaseRequest/PurchaseRequestItems/check-itemid-sonumb?itemId=${encodeURIComponent(itemId)}&sonumb=${encodeURIComponent(sonumbValue)}${currentPRNumber ? `&excludePRNumber=${encodeURIComponent(currentPRNumber)}` : ''}`;
                const checkResult = await apiCall('Procurement', checkEndpoint, 'GET');

                if (checkResult.isUsed || checkResult.data?.isUsed) {
                    // Get SO Number from API response
                    const conflictingSONumber = checkResult.soNumber || checkResult.SONumber || checkResult.data?.soNumber || checkResult.data?.SONumber || sonumbValue;
                    
                    // Find rows in grid view with this Item ID
                    const conflictingRows = [];
                    rows.forEach(row => {
                        const itemDataAttr = row.getAttribute('data-item-data');
                        if (itemDataAttr) {
                            try {
                                const itemData = JSON.parse(itemDataAttr);
                                const existingItemId = itemData.mstPROPurchaseItemInventoryItemID || itemData.mstPROInventoryItemID || itemData.itemID || itemData.itemId || '';
                                if (existingItemId && existingItemId.trim().toLowerCase() === itemId.toLowerCase()) {
                                    conflictingRows.push(row);
                                }
                            } catch (e) {
                                console.error('Error parsing item data:', e);
                            }
                        }
                    });

                    conflictingItems.push({
                        itemId: itemId,
                        soNumber: conflictingSONumber,
                        isInForm: formItemId && formItemId.trim().toLowerCase() === itemId.toLowerCase(),
                        rows: conflictingRows
                    });
                }
            } catch (error) {
                console.error('Error validating Item ID with Sonumb:', error);
                // Continue with other Item IDs even if one fails
            }
        }

        // Show dialog for each conflicting item
        if (conflictingItems.length > 0) {
            // Process conflicts one by one
            for (const conflict of conflictingItems) {
                const { itemId, soNumber, isInForm, rows: conflictingRows } = conflict;
                
                // Show SweetAlert2 dialog with options
                if (typeof Swal !== 'undefined') {
                    const result = await Swal.fire({
                        title: 'Item ID Conflict',
                        html: `Item ID '<strong>${this.wizard.escapeHtml ? this.wizard.escapeHtml(itemId) : itemId}</strong>' is Already Used for Another SO Number.<br><br>Please Select Another SO Number or Select Another Item if you want to use SO Number '<strong>${this.wizard.escapeHtml ? this.wizard.escapeHtml(soNumber) : soNumber}</strong>'.`,
                        icon: 'warning',
                        showCancelButton: false,
                        showDenyButton: true,
                        confirmButtonText: 'Change SO Number',
                        denyButtonText: 'Delete Current Item from Item Detail',
                        confirmButtonColor: '#696cff',
                        denyButtonColor: '#dc3545',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        customClass: {
                            popup: 'swal2-validation',
                            htmlContainer: 'swal2-html-container'
                        }
                    });

                    if (result.isConfirmed) {
                        // User chose "Change SO Number" - open site modal to select new SO Number
                        // Clear current sonumb selection first
                        if (mode === 'subscribe') {
                            const subscribeSonumbField = document.getElementById('SubscribeSonumb');
                            const subscribeSonumbIdField = document.getElementById('SubscribeSonumbId');
                            const subscribeSiteNameField = document.getElementById('SubscribeSiteName');
                            const subscribeSiteIDField = document.getElementById('SubscribeSiteID');
                            if (subscribeSonumbField) subscribeSonumbField.value = '';
                            if (subscribeSonumbIdField) subscribeSonumbIdField.value = '';
                            if (subscribeSiteNameField) subscribeSiteNameField.value = '';
                            if (subscribeSiteIDField) subscribeSiteIDField.value = '';
                        } else {
                            const sonumbField = document.getElementById('Sonumb');
                            const sonumbIdField = document.getElementById('SonumbId');
                            const siteNameField = document.getElementById('SiteName');
                            const siteIDField = document.getElementById('SiteID');
                            if (sonumbField) sonumbField.value = '';
                            if (sonumbIdField) sonumbIdField.value = '';
                            if (siteNameField) siteNameField.value = '';
                            if (siteIDField) siteIDField.value = '';
                        }
                        
                        // Open site modal to select new SO Number
                        this.openChooseSiteModal(mode);
                        
                        // Clear form fields if item is in form
                        if (isInForm) {
                            if (itemIdField) {
                                itemIdField.disabled = false;
                                itemIdField.value = '';
                                itemIdField.classList.remove('is-invalid');
                            }
                            const itemNameField = document.getElementById('itemName');
                            if (itemNameField) {
                                itemNameField.disabled = false;
                                itemNameField.value = '';
                            }
                            const descriptionField = document.getElementById('description');
                            if (descriptionField) {
                                descriptionField.disabled = false;
                                descriptionField.value = '';
                            }
                            if (mstPROPurchaseItemInventoryItemIDField) {
                                mstPROPurchaseItemInventoryItemIDField.value = '';
                            }
                        }
                    } else if (result.isDenied) {
                        // User chose "Delete Current Item from Item Detail" - remove items from grid
                        conflictingRows.forEach(row => {
                            row.remove();
                        });

                        // If item is in form, clear form fields
                        if (isInForm) {
                            if (itemIdField) {
                                itemIdField.disabled = false;
                                itemIdField.value = '';
                                itemIdField.classList.remove('is-invalid');
                            }
                            const itemNameField = document.getElementById('itemName');
                            if (itemNameField) {
                                itemNameField.disabled = false;
                                itemNameField.value = '';
                            }
                            const descriptionField = document.getElementById('description');
                            if (descriptionField) {
                                descriptionField.disabled = false;
                                descriptionField.value = '';
                            }
                            if (mstPROPurchaseItemInventoryItemIDField) {
                                mstPROPurchaseItemInventoryItemIDField.value = '';
                            }
                        }

                        // Update amount total and pagination
                        if (this.wizard.updateAmountTotal) {
                            this.wizard.updateAmountTotal();
                        }
                        const rowsPerPageSelect = document.getElementById('rowsPerPage');
                        const rowsPerPage = rowsPerPageSelect ? parseInt(rowsPerPageSelect.value) : 10;
                        if (this.wizard.getTotalDetailItems) {
                            const totalItems = this.wizard.getTotalDetailItems();
                            const activePage = document.querySelector('#detail .paginate_button.active .page-link');
                            let currentPage = 1;
                            if (activePage) {
                                currentPage = parseInt(activePage.getAttribute('data-page')) || 1;
                            }
                            // If current page becomes empty after deletion, go to previous page
                            if (totalItems > 0 && currentPage > Math.ceil(totalItems / rowsPerPage)) {
                                currentPage = Math.ceil(totalItems / rowsPerPage);
                            }
                            if (this.wizard.updateDetailPagination) {
                                this.wizard.updateDetailPagination(totalItems, currentPage || 1, rowsPerPage);
                            }
                        }
                    }
                } else {
                    // Fallback if SweetAlert2 is not available
                    if (this.wizard.showValidationMessage) {
                        this.wizard.showValidationMessage(`Item ID '${itemId}' is Already Used for Another SO Number. Please Select Another SO Number or Select Another Item if you want to use SO Number '${soNumber}'.`);
                    }
                }
            }
        }
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.ProcurementWizardSite = ProcurementWizardSite;
}

