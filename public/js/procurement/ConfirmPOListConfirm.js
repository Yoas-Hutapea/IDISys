/**
 * ConfirmPOListConfirm Module
 * Handles bulk confirm and submit confirm functionality for Confirm PO List
 */
class ConfirmPOListConfirm {
    constructor(managerInstance) {
        this.manager = managerInstance;
    }

    /**
     * Process Bulk Confirm
     */
    async processBulkConfirm() {
        if (!this.manager || !this.manager.selectedPONumbers || this.manager.selectedPONumbers.size === 0) {
            this.showAlertModal('Please select at least one Purchase Order', 'warning');
            return;
        }

        // Show bulk confirm modal with Decision and Remarks
        const result = await this.showBulkConfirmModal();
        if (!result) {
            return;
        }

        try {
            const poNumbers = Array.from(this.manager.selectedPONumbers);
            
            // Use API module if available
            if (!this.manager || !this.manager.apiModule) {
                throw new Error('API module not available');
            }
            
            const response = await this.manager.apiModule.submitBulkConfirm(
                poNumbers,
                result.decision,
                result.remarks
            );
            
            this.showAlertModal('Purchase Orders confirmed successfully', 'success', () => {
                this.manager.selectedPONumbers.clear();
                if (this.manager && this.manager.tableModule && this.manager.tableModule.refreshTable) {
                    this.manager.tableModule.refreshTable();
                } else if (this.manager && this.manager.refreshTable) {
                    this.manager.refreshTable();
                }
            });
        } catch (error) {
            console.error('Error confirming PO:', error);
            this.showAlertModal('Failed to confirm Purchase Orders: ' + (error.message || error), 'danger');
        }
    }
    
    /**
     * Show Bulk Confirm Modal
     */
    showBulkConfirmModal() {
        return new Promise((resolve) => {
            const modalId = 'bulkConfirmPOModal';
            let existingModal = document.getElementById(modalId);
            
            if (existingModal) {
                existingModal.remove();
            }
            
            const poCount = this.manager.selectedPONumbers ? this.manager.selectedPONumbers.size : 0;
            const modalHtml = `
                <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="bulkConfirmPOModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="bulkConfirmPOModalLabel">Bulk Confirm Purchase Order</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-3">You are about to confirm <strong>${poCount}</strong> Purchase Order(s).</p>
                                <form id="bulkConfirmPOForm">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Decision <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="bulk-confirm-decision" value="Confirm" disabled>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Remarks <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="bulk-confirm-remarks" rows="4" placeholder="Enter remarks" required></textarea>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="submitBulkConfirmBtn">
                                    <i class="icon-base bx bx-check me-2"></i>Confirm All
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modal = new bootstrap.Modal(document.getElementById(modalId));
            
            const submitBtn = document.getElementById('submitBulkConfirmBtn');
            const cancelBtn = document.querySelector(`#${modalId} .btn-label-secondary`);
            const form = document.getElementById('bulkConfirmPOForm');
            
            if (submitBtn) {
                submitBtn.addEventListener('click', () => {
                    if (form && !form.checkValidity()) {
                        form.reportValidity();
                        return;
                    }
                    
                    const decision = document.getElementById('bulk-confirm-decision')?.value || 'Confirm';
                    const remarks = document.getElementById('bulk-confirm-remarks')?.value || '';
                    
                    if (!remarks.trim()) {
                        this.showAlertModal('Please fill in Remarks field', 'warning');
                        return;
                    }
                    
                    modal.hide();
                    resolve({
                        decision: decision,
                        remarks: remarks.trim()
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
        });
    }

    /**
     * Submit Confirm (single PO with item updates)
     */
    async submitConfirm() {
        const decision = document.getElementById('confirm-decision')?.value || 'Confirm';
        const remarksTextarea = document.getElementById('confirm-decision-remarks');
        const remarks = remarksTextarea?.value || '';

        // Clear any previous error messages
        const clearErrorMessages = () => {
            const remarksError = document.getElementById('confirm-decision-remarks-error');
            if (remarksError) remarksError.style.display = 'none';
            if (remarksTextarea) remarksTextarea.classList.remove('is-invalid');
        };

        // Clear previous errors at start of validation
        clearErrorMessages();

        // Helper function to focus and scroll to field (no popup/alert)
        const focusAndScrollToField = (field) => {
            if (field) {
                field.focus();
                field.scrollIntoView({ behavior: 'smooth', block: 'center' });
                // Add highlight class for visual feedback
                field.classList.add('is-invalid');
                
                // Show error message below field
                const errorElementId = field.id + '-error';
                const errorElement = document.getElementById(errorElementId);
                if (errorElement) {
                    errorElement.style.display = 'block';
                }
                
                // Remove highlight and error message after user starts typing
                const removeInvalid = () => {
                    field.classList.remove('is-invalid');
                    if (errorElement) {
                        errorElement.style.display = 'none';
                    }
                };
                field.addEventListener('input', removeInvalid, { once: true });
                field.addEventListener('change', removeInvalid, { once: true });
                setTimeout(removeInvalid, 5000);
            }
        };

        // Validate Remarks (required field)
        if (!remarksTextarea || !remarks || remarks.trim() === '') {
            focusAndScrollToField(remarksTextarea);
            return;
        }
        
        // Show confirmation modal
        const confirmed = await this.showConfirmModal(
            'Confirm Purchase Order',
            'Are you sure you want to confirm this Purchase Order?'
        );
        
        if (!confirmed) {
            return;
        }
        
        try {
            const poNumber = document.getElementById('confirm-po-number')?.value || 
                            (this.manager && this.manager.currentPONumber) || 
                            (this.manager && this.manager.viewModule && this.manager.viewModule.currentPONumber) || '';
            if (!poNumber) {
                throw new Error('Purchase Order number not found');
            }
            
            // Collect updated items from table
            const tbody = document.getElementById('confirm-items-tbody');
            const updatedItems = [];
            if (tbody) {
                const rows = tbody.querySelectorAll('tr[data-item-id]');
                rows.forEach(row => {
                    const itemId = row.getAttribute('data-item-id');
                    if (!itemId) return;
                    
                    const description = row.getAttribute('data-original-description') || '';
                    const unit = row.getAttribute('data-original-unit') || '';
                    const qty = parseFloat(row.getAttribute('data-original-qty') || '0') || 0;
                    const unitPrice = parseFloat(row.getAttribute('data-original-unit-price') || '0') || 0;
                    const amount = parseFloat(row.getAttribute('data-original-amount') || '0') || 0;
                    
                    updatedItems.push({
                        ID: parseInt(itemId),
                        ItemDescription: description,
                        ItemUnit: unit,
                        ItemQty: qty,
                        UnitPrice: unitPrice,
                        Amount: amount
                    });
                });
            }
            
            // Prepare deleted item IDs
            const deletedItemIds = [];
            if (this.manager && this.manager.viewModule && this.manager.viewModule.deletedItemIds) {
                deletedItemIds.push(...Array.from(this.manager.viewModule.deletedItemIds).map(id => parseInt(id)));
            } else if (this.manager && this.manager.deletedItemIds) {
                deletedItemIds.push(...Array.from(this.manager.deletedItemIds).map(id => parseInt(id)));
            }
            
            // Use API module if available
            if (!this.manager || !this.manager.apiModule) {
                throw new Error('API module not available');
            }
            
            const response = await this.manager.apiModule.submitConfirm(
                poNumber,
                decision,
                remarks.trim(),
                updatedItems,
                deletedItemIds
            );
            
            this.showAlertModal('Purchase Order confirmed successfully', 'success', () => {
                // Reload page to Confirm PO List
                window.location.href = '/Procurement/PurchaseOrder/Confirm';
            });
        } catch (error) {
            console.error('Error submitting confirmation:', error);
            this.showAlertModal('Failed to submit confirmation: ' + (error.message || error), 'danger');
        }
    }
    
    /**
     * Show Confirm Modal
     */
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

    /**
     * Show Alert Modal
     */
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

    /**
     * Toggle Select All
     */
    toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        if (!selectAll || !this.manager) return;
        
        const checkboxes = document.querySelectorAll('#confirmPOTableBody .row-checkbox');
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            const poNumber = checkbox.dataset.poNumber;
            if (selectAll.checked) {
                this.manager.selectedPONumbers.add(poNumber);
            } else {
                this.manager.selectedPONumbers.delete(poNumber);
            }
        });
        
        this.updateProcessButtonState();
    }

    /**
     * Toggle Select PO
     */
    toggleSelectPO(poNumber) {
        if (!this.manager || !this.manager.selectedPONumbers) return;
        
        if (this.manager.selectedPONumbers.has(poNumber)) {
            this.manager.selectedPONumbers.delete(poNumber);
        } else {
            this.manager.selectedPONumbers.add(poNumber);
        }
        this.updateProcessButtonState();
    }

    /**
     * Update Process Button State
     */
    updateProcessButtonState() {
        if (!this.manager) return;
        
        const btn = this.manager.cachedElements?.processBulkConfirmBtn || 
                   document.getElementById('processBulkConfirmBtn');
        if (btn) {
            btn.disabled = !this.manager.selectedPONumbers || this.manager.selectedPONumbers.size === 0;
        }
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.ConfirmPOListConfirm = ConfirmPOListConfirm;
}

