<script>
    'use strict';

    // Update Bulky Price Manager
    class UpdateBulkyPriceManager {
        constructor() {
            this.poDataTable = null;
            this.itemDataTable = null;
            this.selectedItems = new Set();
            this.selectedPO = null;
            this.updatedItems = new Map(); // Map<itemId, { unitPrice, amount }> - Track items with updated prices (not yet saved)
            this.allPurchaseTypes = null; // Store purchase types for lookup
            this.allPurchaseSubTypes = null; // Store purchase sub types for lookup
            this.cachedElements = this.cacheElements();
            this.init();
        }

        cacheElements() {
            return {
                filterForm: document.getElementById('updateBulkyPriceFilterForm'),
                poTable: document.getElementById('updateBulkyPricePOTable'),
                itemTable: document.getElementById('updateBulkyPriceItemTable')
            };
        }

        async init() {
            this.bindEvents();
            // Load PurchaseTypes and PurchaseSubTypes before initializing DataTable
            await this.loadPurchaseTypesForFormatting();
            this.initializePOTable();
            this.initializeItemTable();
            this.updateDateRangeInfo(); // Initial update
        }

        bindEvents() {
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

            // Expose global functions
            window.updateBulkyPriceManager = this;
        }

        async search() {
            this.updateDateRangeInfo(); // Update date range info when filter changes
            if (this.poDataTable) {
                this.poDataTable.ajax.reload();
            }
        }

        async resetFilter() {
            const form = this.cachedElements.filterForm;
            if (form) {
                form.reset();
                // Reset default date range to current month
                const now = new Date();
                const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                const poStartDateInput = document.getElementById('poStartDate');
                const poEndDateInput = document.getElementById('poEndDate');
                if (poStartDateInput) {
                    poStartDateInput.value = firstDay.toISOString().split('T')[0];
                }
                if (poEndDateInput) {
                    poEndDateInput.value = lastDay.toISOString().split('T')[0];
                }
            }
            if (this.poDataTable) {
                this.poDataTable.ajax.reload();
            }
            this.updateDateRangeInfo(); // Update date range info after reset
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

            const form = document.getElementById('updateBulkyPriceFilterForm');
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

        initializePOTable() {
            if (typeof CreateTable === 'undefined' || typeof ColumnBuilder === 'undefined') {
                console.error('CreateTable or ColumnBuilder helper not found. Make sure DataTableHelper.js is loaded.');
                return;
            }

            const self = this;

            // Build filter function for GetGrid
            const buildFilter = () => {
                const formData = new FormData(this.cachedElements.filterForm);
                const poNumber = formData.get('poNumber');
                const prNumber = formData.get('prNumber');
                const purchType = formData.get('purchType');
                const purchSubType = formData.get('purchSubType');
                const purchName = formData.get('purchName');
                const company = formData.get('company');
                const vendorName = formData.get('vendorName');

                const filter = {};

                // Filter for Update Bulky Price PO List: only show PO with status 8 (Waiting Confirm Purchase Order)
                filter.mstApprovalStatusIDs = [8];

                if (poNumber && poNumber.trim()) filter.poNumber = poNumber.trim();
                if (prNumber && prNumber.trim()) filter.prNumber = prNumber.trim();
                if (purchType && purchType.trim()) filter.purchType = purchType.trim();
                if (purchSubType && purchSubType.trim()) filter.purchSubType = purchSubType.trim();
                if (purchName && purchName.trim()) filter.purchName = purchName.trim();
                if (company && company.trim()) filter.company = company.trim();
                if (vendorName && vendorName.trim()) filter.vendorName = vendorName.trim();

                return filter;
            };

            // Initialize DataTable
            this.poDataTable = CreateTable({
                apiType: 'Procurement',
                endpoint: '/Procurement/PurchaseOrder/PurchaseOrders/Grid',
                tableId: '#updateBulkyPricePOTable',
                columns: ColumnBuilder([
                    { data: 'purchOrderID', title: 'Purchase Order Number' },
                    { data: 'purchOrderName', title: 'Purchase Order Name' },
                    {
                        data: 'purchType',
                        title: 'Purchase Type',
                        render: (data, type, row) => {
                            return self.formatPurchaseType(data, row);
                        }
                    },
                    {
                        data: 'purchSubType',
                        title: 'Purchase Sub Type',
                        render: (data, type, row) => {
                            return self.formatPurchaseSubType(data, row);
                        }
                    },
                    { data: 'approvalStatus', title: 'Status' },
                    { data: 'poAmount', title: 'PO Amount', type: 'currency' },
                    { data: 'poDate', title: 'PO Date', type: 'datetime' },
                    { data: 'prNumber', title: 'PR Number' },
                    { data: 'mstVendorVendorName', title: 'Vendor Name' },
                    { data: 'companyName', title: 'Company' }
                ]),
                filter: buildFilter.bind(this),
                order: [[6, 'desc']], // Order by PO Date (column index 6) descending
                serverSide: true,
                fnDrawCallback: (settings) => {
                    // Bind row click event to select PO
                    $('#updateBulkyPricePOTable tbody tr').off('click').on('click', function() {
                        const row = self.poDataTable.row(this);
                        const data = row.data();
                        if (data) {
                            self.selectPO(data.purchOrderID || data.PurchOrderID || '');
                        }
                    });
                }
            });

            // Load PurchaseTypes and PurchaseSubTypes for formatting
            this.loadPurchaseTypesForFormatting();

            // Update PurchaseTypes and PurchaseSubTypes after DataTable draws
            this.poDataTable.on('draw', () => {
                this.loadPurchaseTypesForFormatting();
            });
        }

        initializeItemTable() {
            if (typeof CreateTable === 'undefined' || typeof ColumnBuilder === 'undefined') {
                console.error('CreateTable or ColumnBuilder helper not found. Make sure DataTableHelper.js is loaded.');
                return;
            }

            const self = this;

            // Build filter function for GetGrid
            const buildFilter = () => {
                const filter = {};

                // Filter for Update Bulky Price Item List: only show items from PO with status 8 (Waiting Confirm Purchase Order)
                filter.mstApprovalStatusIDs = [8];

                if (this.selectedPO) filter.poNumber = this.selectedPO;
                // Filter inputs can be added later if needed
                const poNumberFilter = document.getElementById('filterPONumberItem')?.value;
                const itemIDFilter = document.getElementById('filterItemID')?.value;
                const descriptionFilter = document.getElementById('filterDescription')?.value;

                if (poNumberFilter && poNumberFilter.trim()) filter.poNumberFilter = poNumberFilter.trim();
                if (itemIDFilter && itemIDFilter.trim()) filter.itemIDFilter = itemIDFilter.trim();
                if (descriptionFilter && descriptionFilter.trim()) filter.descriptionFilter = descriptionFilter.trim();

                return filter;
            };

            // Initialize DataTable
            this.itemDataTable = CreateTable({
                apiType: 'Procurement',
                endpoint: '/Procurement/PurchaseOrder/PurchaseOrderItems/Grid',
                tableId: '#updateBulkyPriceItemTable',
                columns: ColumnBuilder([
                    {
                        // Checkbox column - use ID field from DTO (PurchaseOrderItemListDto.ID)
                        data: 'id',
                        title: '',
                        orderable: false,
                        searchable: false,
                        width: '50px',
                        className: 'text-center',
                        render: (data, type, row) => {
                            // ID from DTO is mapped to 'id' in JSON response
                            const itemId = data || row.id || row.ID || '';
                            if (!itemId && itemId !== 0) return '';
                            const itemIdStr = itemId.toString();
                            const isSelected = self.selectedItems.has(itemIdStr);
                            return `<input type="checkbox" class="item-checkbox" data-item-id="${self.escapeHtml(itemIdStr)}" ${isSelected ? 'checked' : ''} onchange="updateBulkyPriceManager.toggleSelectItem('${self.escapeHtml(itemIdStr)}')">`;
                        }
                    },
                    { data: 'trxPROPurchaseOrderNumber', title: 'Purchase Order Number' },
                    { data: 'mstPROPurchaseItemInventoryItemID', title: 'Item ID' },
                    { data: 'itemName', title: 'Item Name' },
                    { data: 'itemDescription', title: 'Description' },
                    { data: 'itemUnit', title: 'UoM' },
                    { data: 'itemQty', title: 'Quantity', type: 'number' },
                    { data: 'currencyCode', title: 'Currency' },
                    { data: 'unitPrice', title: 'Unit Price', type: 'currency' },
                    { data: 'amount', title: 'Amount', type: 'currency' }
                ]),
                filter: buildFilter.bind(this),
                order: [[1, 'asc']], // Order by Purchase Order Number (index 1 after checkbox column)
                serverSide: true,
                fnDrawCallback: (settings) => {
                    // After each draw, update UI to show unsaved changes
                    self.updateItemTableUI();
                }
            });
        }

        escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        async loadPurchaseTypesForFormatting() {
            // Load PurchaseTypes if not already loaded
            if (!this.allPurchaseTypes) {
                try {
                    const typesData = await apiCall('Procurement', '/Procurement/Master/PurchaseTypes/List?isActive=true', 'GET');
                    this.allPurchaseTypes = Array.isArray(typesData) ? typesData : (typesData?.data || []);
                } catch (error) {
                    console.error('Error loading purchase types:', error);
                    this.allPurchaseTypes = [];
                }
            }

            // Load PurchaseSubTypes if not already loaded
            if (!this.allPurchaseSubTypes) {
                try {
                    // Load all sub types (we'll filter by type ID when needed)
                    const subTypesData = await apiCall('Procurement', '/Procurement/Master/PurchaseTypes/SubTypes?isActive=true', 'GET');
                    this.allPurchaseSubTypes = Array.isArray(subTypesData) ? subTypesData : (subTypesData?.data || []);
                } catch (error) {
                    console.error('Error loading purchase sub types:', error);
                    this.allPurchaseSubTypes = [];
                }
            }
        }

        // Format PurchaseType with Category (same as PR List)
        // Returns escaped HTML string for safe display
        formatPurchaseType(purchType, row, escapeHtml = true) {
            // Handle null/undefined
            if (!purchType && purchType !== 0) {
                return escapeHtml ? this.escapeHtml('-') : '-';
            }

            // Convert to string if it's a number
            const purchTypeStr = typeof purchType === 'number' ? purchType.toString() : String(purchType || '');

            if (purchTypeStr.trim() === '') {
                return escapeHtml ? this.escapeHtml('-') : '-';
            }

            // Check if it's already formatted (contains space and not just a number)
            if (purchTypeStr.includes(' ') && isNaN(parseInt(purchTypeStr.trim()))) {
                return escapeHtml ? this.escapeHtml(purchTypeStr) : purchTypeStr;
            }

            // If it's a number (ID), try to format it
            const typeId = parseInt(purchTypeStr.trim(), 10);
            if (!isNaN(typeId) && typeId > 0 && this.allPurchaseTypes) {
                const type = this.allPurchaseTypes.find(t =>
                    parseInt(t.ID || t.id || '0', 10) === typeId
                );
                if (type) {
                    const prType = type.PurchaseRequestType || type.purchaseRequestType || '';
                    const category = type.Category || type.category || '';
                    if (!category) {
                        return escapeHtml ? this.escapeHtml(prType) : prType;
                    }
                    // Jika PurchaseRequestType dan Category sama, cukup tampilkan salah satu
                    if (prType === category) {
                        return escapeHtml ? this.escapeHtml(prType) : prType;
                    }
                    const formatted = `${prType} ${category}`;
                    return escapeHtml ? this.escapeHtml(formatted) : formatted;
                }
            }

            // If not found or invalid, return as is (might be ID that hasn't been loaded yet)
            return escapeHtml ? this.escapeHtml(purchTypeStr) : purchTypeStr;
        }

        // Format PurchaseSubType (same as PR List)
        // Returns escaped HTML string for safe display
        formatPurchaseSubType(purchSubType, row, escapeHtml = true) {
            // Handle null/undefined
            if (!purchSubType && purchSubType !== 0) {
                return escapeHtml ? this.escapeHtml('-') : '-';
            }

            // Convert to string if it's a number
            const purchSubTypeStr = typeof purchSubType === 'number' ? purchSubType.toString() : String(purchSubType || '');

            if (purchSubTypeStr.trim() === '') {
                return escapeHtml ? this.escapeHtml('-') : '-';
            }

            // Check if it's already formatted (not just a number)
            if (isNaN(parseInt(purchSubTypeStr.trim()))) {
                return escapeHtml ? this.escapeHtml(purchSubTypeStr) : purchSubTypeStr;
            }

            // If it's a number (ID), try to format it
            const subTypeId = parseInt(purchSubTypeStr.trim(), 10);
            if (!isNaN(subTypeId) && subTypeId > 0 && this.allPurchaseSubTypes) {
                const subType = this.allPurchaseSubTypes.find(st =>
                    parseInt(st.ID || st.id || '0', 10) === subTypeId
                );
                if (subType) {
                    const formatted = subType.PurchaseRequestSubType || subType.purchaseRequestSubType || '';
                    return escapeHtml ? this.escapeHtml(formatted) : formatted;
                }
            }

            // If not found or invalid, return as is (might be ID that hasn't been loaded yet)
            return escapeHtml ? this.escapeHtml(purchSubTypeStr) : purchSubTypeStr;
        }

        selectPO(poNumber) {
            this.selectedPO = poNumber;
            if (this.itemDataTable) {
                this.itemDataTable.ajax.reload();
            }
        }

        toggleSelectAllItems() {
            const selectAll = document.getElementById('selectAllItems');
            const checkboxes = document.querySelectorAll('#updateBulkyPriceItemTableBody .item-checkbox');

            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
                const itemId = checkbox.dataset.itemId;
                if (selectAll.checked) {
                    this.selectedItems.add(itemId);
                } else {
                    this.selectedItems.delete(itemId);
                }
            });

            this.updateActionButtonState();
        }

        updateActionButtonState() {
            const btn = document.getElementById('updateBulkyPriceActionBtn');
            if (btn) {
                btn.disabled = this.selectedItems.size === 0;
            }
        }

        toggleSelectItem(itemId) {
            if (this.selectedItems.has(itemId)) {
                this.selectedItems.delete(itemId);
            } else {
                this.selectedItems.add(itemId);
            }
            this.updateActionButtonState();
        }

        filterItemTable() {
            if (this.itemDataTable) {
                this.itemDataTable.ajax.reload();
            }
        }

        async updateBulkyPrice() {
            if (this.selectedItems.size === 0) {
                this.showAlertModal('Please select at least one item', 'warning');
                return;
            }

            // Show modal for bulk price update
            const result = await this.showUpdateBulkyPriceModal();
            if (!result) {
                return;
            }

            // Update unit price in UI (not yet saved to database)
            const itemIds = Array.from(this.selectedItems);
            const unitPrice = result.unitPrice;

            // Get all rows from DataTable to access item data
            const tableData = this.itemDataTable.rows({ search: 'applied' }).data().toArray();

            let updatedCount = 0;
            for (const itemId of itemIds) {
                // Find the item in table data
                const itemData = tableData.find(row => {
                    const rowId = (row.id || row.ID || '').toString();
                    return rowId === itemId.toString();
                });

                if (itemData) {
                    // Calculate new amount: UnitPrice * Quantity
                    const quantity = parseFloat(itemData.itemQty || itemData.ItemQty || 0);
                    const newAmount = unitPrice * quantity;

                    // Store updated values (not yet saved)
                    this.updatedItems.set(itemId.toString(), {
                        unitPrice: unitPrice,
                        amount: newAmount,
                        quantity: quantity
                    });

                    updatedCount++;
                }
            }

            // Update UI table to show new prices
            this.updateItemTableUI();

            // Clear selection
            this.selectedItems.clear();
            this.updateActionButtonState();

            // Update select all checkbox
            const selectAll = document.getElementById('selectAllItems');
            if (selectAll) {
                selectAll.checked = false;
            }

            this.showAlertModal(`Unit prices updated for ${updatedCount} item(s). Click Submit to save changes.`, 'success');
        }

        updateItemTableUI() {
            // Update the displayed values in the table
            if (this.itemDataTable) {
                const self = this;

                // Format number (no currency symbol)
                const formatCurrency = (value) => {
                    if (value === null || value === undefined || value === '') return '0';
                    const numValue = parseFloat(value);
                    if (isNaN(numValue)) return '0';
                    return numValue.toLocaleString('id-ID', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 2
                    });
                };

                // Format date from YYYY-MM-DD to compact format (30 Nov 2025)
                const formatDateCompact = (dateString, includeYear = true) => {
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
                };

                // Use DataTable's draw callback to update UI after each draw
                const table = this.itemDataTable;
                const rows = table.rows({ search: 'applied' }).nodes();

                $(rows).each((index, row) => {
                    const rowData = table.row(row).data();
                    if (rowData) {
                        const itemId = (rowData.id || rowData.ID || '').toString();
                        const updatedData = self.updatedItems.get(itemId);

                        // Get jQuery row object (needed in both if and else blocks)
                        const $row = $(row);

                        if (updatedData) {
                            // Update the row data in DataTable's internal data
                            rowData.unitPrice = updatedData.unitPrice;
                            rowData.amount = updatedData.amount;

                            // Update the displayed values in the table cells
                            const cells = $row.find('td');

                            // Column indices: Checkbox(0), PO Number(1), Item ID(2), Item Name(3),
                            // Description(4), UoM(5), Quantity(6), Currency(7), Unit Price(8), Amount(9)
                            const unitPriceCell = cells.eq(8);
                            const amountCell = cells.eq(9);

                            if (unitPriceCell.length) {
                                unitPriceCell.html(`<span class="text-warning fw-semibold" title="Unsaved change">${formatCurrency(updatedData.unitPrice)}</span>`);
                            }
                            if (amountCell.length) {
                                amountCell.html(`<span class="text-warning fw-semibold" title="Unsaved change">${formatCurrency(updatedData.amount)}</span>`);
                            }

                            // Add visual indicator that this row has unsaved changes
                            $row.addClass('table-warning');
                        } else {
                            // Remove warning class if item is no longer in updatedItems
                            $row.removeClass('table-warning');
                        }
                    }
                });
            }
        }

        showUpdateBulkyPriceModal() {
            return new Promise((resolve) => {
                const modalId = 'updateBulkyPriceModal';
                let existingModal = document.getElementById(modalId);

                if (existingModal) {
                    existingModal.remove();
                }

                const itemCount = this.selectedItems.size;
                const modalHtml = `
                    <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="updateBulkyPriceModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="updateBulkyPriceModalLabel">Update Bulky Price</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="mb-3">You are about to update unit price for <strong>${itemCount}</strong> item(s).</p>
                                    <form id="updateBulkyPriceForm">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Unit Price <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="bulky-unit-price" placeholder="Enter unit price" required>
                                            <small class="form-text text-muted">Enter the new unit price for all selected items</small>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary" id="submitUpdateBulkyPriceModalBtn">
                                        <i class="icon-base bx bx-check me-2"></i>Update
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                document.body.insertAdjacentHTML('beforeend', modalHtml);
                const modal = new bootstrap.Modal(document.getElementById(modalId));

                const submitBtn = document.getElementById('submitUpdateBulkyPriceModalBtn');
                const cancelBtn = document.querySelector(`#${modalId} .btn-label-secondary`);
                const form = document.getElementById('updateBulkyPriceForm');
                const unitPriceInput = document.getElementById('bulky-unit-price');

                // Format input with thousand separator
                if (unitPriceInput) {
                    // Format on input
                    unitPriceInput.addEventListener('input', function(e) {
                        let value = e.target.value.replace(/[^\d]/g, ''); // Remove all non-digits
                        if (value) {
                            // Add thousand separators
                            value = parseFloat(value).toLocaleString('id-ID', {
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            });
                            e.target.value = value;
                        }
                    });

                    // Format on blur (when user leaves the field)
                    unitPriceInput.addEventListener('blur', function(e) {
                        let value = e.target.value.replace(/[^\d]/g, '');
                        if (value) {
                            value = parseFloat(value).toLocaleString('id-ID', {
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            });
                            e.target.value = value;
                        }
                    });

                    // Remove separators on focus for easier editing
                    unitPriceInput.addEventListener('focus', function(e) {
                        let value = e.target.value.replace(/[^\d]/g, '');
                        e.target.value = value;
                    });
                }

                if (submitBtn) {
                    submitBtn.addEventListener('click', () => {
                        if (form && !form.checkValidity()) {
                            form.reportValidity();
                            return;
                        }

                        // Get numeric value (remove thousand separators)
                        const rawValue = unitPriceInput?.value.replace(/[^\d]/g, '') || '0';
                        const unitPrice = parseFloat(rawValue);

                        if (isNaN(unitPrice) || unitPrice < 0) {
                            this.showAlertModal('Please enter a valid unit price', 'warning');
                            return;
                        }

                        modal.hide();
                        resolve({
                            unitPrice: unitPrice
                        });
                    });
                }

                if (cancelBtn) {
                    cancelBtn.addEventListener('click', () => {
                        modal.hide();
                        resolve(null);
                    });
                }

                // Clean up modal when hidden
                const modalElement = document.getElementById(modalId);
                modalElement.addEventListener('hidden.bs.modal', () => {
                    modalElement.remove();
                    if (!submitBtn || !submitBtn.hasAttribute('data-resolved')) {
                        resolve(null);
                    }
                });

                modal.show();

                // Focus on input when modal is shown
                setTimeout(() => {
                    if (unitPriceInput) {
                        unitPriceInput.focus();
                    }
                }, 300);
            });
        }

        showAlertModal(message, type = 'info', onClose = null) {
            if (typeof Swal === 'undefined') {
                // Fallback to basic alert if SweetAlert2 is not loaded
                window.alert(message);
                if (onClose && typeof onClose === 'function') {
                    onClose();
                }
                return;
            }

            // Map type to SweetAlert2 icon
            const iconMap = {
                'success': 'success',
                'warning': 'warning',
                'danger': 'error',
                'error': 'error',
                'info': 'info'
            };

            const icon = iconMap[type] || 'info';

            Swal.fire({
                title: message,
                icon: icon,
                confirmButtonColor: '#696cff',
                confirmButtonText: 'OK',
                allowOutsideClick: false,
                allowEscapeKey: true,
                animation: true,
                customClass: {
                    confirmButton: 'btn btn-primary'
                }
            }).then((result) => {
                if (onClose && typeof onClose === 'function') {
                    onClose();
                }
            });
        }

        editItem(itemId) {
            // Open edit modal for item
            console.log('Edit item:', itemId);
        }

        deleteItem(itemId) {
            if (confirm('Are you sure you want to delete this item?')) {
                // Delete item
                console.log('Delete item:', itemId);
            }
        }

        async submit() {
            if (this.updatedItems.size === 0) {
                this.showAlertModal('No items have been updated. Please update unit prices first.', 'warning');
                return;
            }

            // Show confirmation modal
            const confirmed = await this.showConfirmModal(
                'Submit Changes',
                `Are you sure you want to save ${this.updatedItems.size} item update(s) to database?`
            );

            if (!confirmed) {
                return;
            }

            try {
                // Build array of items to update
                const itemsToUpdate = [];

                for (const [itemId, updatedData] of this.updatedItems.entries()) {
                    itemsToUpdate.push({
                        Id: parseInt(itemId), // Capital I to match DTO property name
                        UnitPrice: updatedData.unitPrice,
                        Amount: updatedData.amount
                    });
                }

                const endpoint = `/Procurement/PurchaseOrder/UpdateBulkyPrice/Submit`;
                const payload = {
                    items: itemsToUpdate
                };

                const response = await apiCall('Procurement', endpoint, 'POST', payload);

                this.showAlertModal(`Successfully saved ${itemsToUpdate.length} item update(s) to database.`, 'success', () => {
                    // Clear updated items
                    this.updatedItems.clear();

                    // Reload table to show saved data
                    if (this.itemDataTable) {
                        this.itemDataTable.ajax.reload();
                    }
                });
            } catch (error) {
                console.error('Error saving prices:', error);
                const errorMessage = error.response?.data?.error || error.message || error;
                this.showAlertModal('Failed to save changes: ' + errorMessage, 'danger');
            }
        }

        showConfirmModal(title, message) {
            return new Promise((resolve) => {
                if (typeof Swal === 'undefined') {
                    // Fallback to basic confirm if SweetAlert2 is not loaded
                    const confirmed = window.confirm(`${title}\n\n${message}`);
                    resolve(confirmed);
                    return;
                }

                Swal.fire({
                    title: title,
                    text: message,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#696cff',
                    cancelButtonColor: '#a8aaae',
                    confirmButtonText: '<i class="icon-base bx bx-check me-2"></i>Confirm',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    allowOutsideClick: false,
                    allowEscapeKey: true,
                    animation: true,
                    customClass: {
                        confirmButton: 'btn btn-primary',
                        cancelButton: 'btn btn-label-secondary'
                    }
                }).then((result) => {
                    resolve(result.isConfirmed);
                });
            });
        }

        back() {
            if (typeof confirmPOManager !== 'undefined') {
                confirmPOManager.backToConfirmList();
            }
        }

    }

    // Initialize manager lazily when section is shown
    // Global function for toggle filter
    function toggleUpdateBulkyPriceFilter() {
        const filterContent = document.getElementById('updateBulkyPriceFilterContent');
        const chevron = document.getElementById('updateBulkyPriceFilterChevron');
        const header = document.querySelector('[onclick="toggleUpdateBulkyPriceFilter()"]');

        if (filterContent && chevron && header) {
            const isCollapsed = !filterContent.classList.contains('show');

            if (isCollapsed) {
                filterContent.classList.add('show');
                filterContent.style.display = 'block';
                chevron.classList.remove('bx-chevron-down');
                chevron.classList.add('bx-chevron-up');
                header.setAttribute('aria-expanded', 'true');
            } else {
                filterContent.classList.remove('show');
                filterContent.style.display = 'none';
                chevron.classList.remove('bx-chevron-up');
                chevron.classList.add('bx-chevron-down');
                header.setAttribute('aria-expanded', 'false');
            }
        }
    }

    let updateBulkyPriceManagerInstance = null;

    function getUpdateBulkyPriceManager() {
        if (!updateBulkyPriceManagerInstance) {
            updateBulkyPriceManagerInstance = new UpdateBulkyPriceManager();
            // Also set window.updateBulkyPriceManager for direct access
            window.updateBulkyPriceManager = updateBulkyPriceManagerInstance;
        }
        return updateBulkyPriceManagerInstance;
    }

    // Initialize on page load if section is visible
    document.addEventListener('DOMContentLoaded', () => {
        const updateSection = document.getElementById('updateBulkyPriceSection');
        if (updateSection && updateSection.style.display !== 'none') {
            getUpdateBulkyPriceManager();
        }
    });

    // Expose global function to get manager instance
    if (typeof window.getUpdateBulkyPriceManager === 'undefined') {
        window.getUpdateBulkyPriceManager = getUpdateBulkyPriceManager;
    }
</script>
