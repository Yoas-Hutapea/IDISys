/**
 * ApprovalPOListApproval Module
 * Handles bulk approval and submit approval functionality for Approval PO List
 */
class ApprovalPOListApproval {
    constructor(managerInstance) {
        this.manager = managerInstance;
    }

    /**
     * Process Bulk Approval
     */
    async processBulkApproval() {
        if (!this.manager || !this.manager.selectedPONumbers || this.manager.selectedPONumbers.size === 0) {
            this.showAlertModal('Please select at least one Purchase Order', 'warning');
            return;
        }

        // Show bulk approval modal with Decision and Remarks
        const result = await this.showBulkApprovalModal();
        if (!result) {
            return;
        }

        try {
            const poNumbers = Array.from(this.manager.selectedPONumbers);
            
            // Use API module if available
            if (!this.manager || !this.manager.apiModule) {
                throw new Error('API module not available');
            }
            
            const response = await this.manager.apiModule.submitBulkApproval(
                poNumbers,
                result.statusID,
                result.decision,
                result.remarks,
                result.activity
            );
            
            this.showAlertModal(response.message || 'Purchase Orders processed successfully', 'success', () => {
                // Reload page to Approval PO List
                window.location.href = '/Procurement/PurchaseOrder/Approval';
            });
        } catch (error) {
            console.error('Error processing approval:', error);
            const errorMessage = error.response?.data?.error || error.message || error;
            this.showAlertModal('Failed to process approval: ' + errorMessage, 'danger');
        }
    }
    
    /**
     * Show Bulk Approval Modal
     */
    showBulkApprovalModal() {
        return new Promise((resolve) => {
            const modalId = 'bulkApprovalPOModal';
            let existingModal = document.getElementById(modalId);
            
            if (existingModal) {
                existingModal.remove();
            }
            
            const poCount = this.manager.selectedPONumbers ? this.manager.selectedPONumbers.size : 0;
            const modalHtml = `
                <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="bulkApprovalPOModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="bulkApprovalPOModalLabel">Bulk Approval Purchase Order</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-3">You are about to process <strong>${poCount}</strong> Purchase Order(s).</p>
                                <form id="bulkApprovalPOForm">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Decision <span class="text-danger">*</span></label>
                                        <select class="form-select" id="bulk-approval-decision" required>
                                            <option value="" disabled selected>Select Decision</option>
                                            <option value="Approve">Approve</option>
                                            <option value="Reject">Reject</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Remarks <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="bulk-approval-remarks" rows="4" placeholder="Enter remarks" required></textarea>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="submitBulkApprovalBtn">
                                    <i class="icon-base bx bx-check me-2"></i>Process All
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modal = new bootstrap.Modal(document.getElementById(modalId));
            
            const submitBtn = document.getElementById('submitBulkApprovalBtn');
            const cancelBtn = document.querySelector(`#${modalId} .btn-label-secondary`);
            const form = document.getElementById('bulkApprovalPOForm');
            const decisionSelect = document.getElementById('bulk-approval-decision');
            
            if (submitBtn) {
                submitBtn.addEventListener('click', () => {
                    if (form && !form.checkValidity()) {
                        form.reportValidity();
                        return;
                    }
                    
                    const decision = decisionSelect?.value || '';
                    const remarks = document.getElementById('bulk-approval-remarks')?.value || '';
                    
                    if (!decision) {
                        this.showAlertModal('Please select Decision', 'warning');
                        return;
                    }
                    
                    if (!remarks.trim()) {
                        this.showAlertModal('Please fill in Remarks field', 'warning');
                        return;
                    }
                    
                    const statusID = decision === 'Approve' ? 10 : 11;
                    const activity = decision === 'Approve' ? 'Approve Purchase Order' : 'Reject Purchase Order';
                    
                    modal.hide();
                    resolve({
                        decision: decision,
                        remarks: remarks.trim(),
                        statusID: statusID,
                        activity: activity
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
     * Submit Approval (single PO)
     */
    async submitApproval() {
        // Validate form first
        if (!this.validateApprovalForm()) {
            return;
        }
        
        const decision = document.getElementById('approval-decision')?.value || '';
        const remarks = document.getElementById('approval-remarks')?.value || '';
        
        // Show confirmation modal
        const confirmed = await this.showConfirmModal(
            'Submit Approval',
            `Are you sure you want to ${decision.toLowerCase()} this Purchase Order?`
        );
        
        if (!confirmed) {
            return;
        }
        
        try {
            const poNumber = this.manager.currentPONumber || '';
            if (!poNumber) {
                throw new Error('Purchase Order number not found');
            }
            
            // Note: mstApprovalStatusID is no longer needed - backend determines next status based on current PO status
            // Status 9 -> 10 if Approve, Status 10 -> 11 if Approve, or 11 if Reject
            const activity = decision === 'Approve' ? 'Approve Purchase Order' : 'Reject Purchase Order';
            
            // Use API module if available
            if (!this.manager || !this.manager.apiModule) {
                throw new Error('API module not available');
            }
            
            const response = await this.manager.apiModule.submitApproval(poNumber, decision, remarks, activity);
            
            this.showAlertModal('Purchase Order approval processed successfully', 'success', () => {
                // Reload page to Approval PO List
                window.location.href = '/Procurement/PurchaseOrder/Approval';
            });
        } catch (error) {
            console.error('Error submitting approval:', error);
            const errorMessage = error.response?.data?.error || error.message || error;
            this.showAlertModal('Failed to submit approval: ' + errorMessage, 'danger');
        }
    }

    /**
     * Validate Approval Form
     */
    validateApprovalForm() {
        // Clear previous errors
        const decisionSelect = document.getElementById('approval-decision');
        const remarksTextarea = document.getElementById('approval-remarks');
        const decisionError = document.getElementById('approval-decision-error');
        const remarksError = document.getElementById('approval-remarks-error');
        
        // Remove error styling
        if (decisionSelect) {
            decisionSelect.classList.remove('is-invalid');
        }
        if (remarksTextarea) {
            remarksTextarea.classList.remove('is-invalid');
        }
        if (decisionError) {
            decisionError.style.display = 'none';
        }
        if (remarksError) {
            remarksError.style.display = 'none';
        }
        
        let isValid = true;
        let firstErrorField = null;
        
        // Validate Decision
        const decision = decisionSelect?.value || '';
        if (!decision || decision.trim() === '') {
            isValid = false;
            if (decisionSelect) {
                decisionSelect.classList.add('is-invalid');
            }
            if (decisionError) {
                decisionError.style.display = 'block';
            }
            if (!firstErrorField) {
                firstErrorField = decisionSelect;
            }
        }
        
        // Validate Remarks
        const remarks = remarksTextarea?.value || '';
        if (!remarks.trim()) {
            isValid = false;
            if (remarksTextarea) {
                remarksTextarea.classList.add('is-invalid');
            }
            if (remarksError) {
                remarksError.style.display = 'block';
            }
            if (!firstErrorField) {
                firstErrorField = remarksTextarea;
            }
        }
        
        // Scroll to first error field
        if (!isValid && firstErrorField) {
            firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            // Focus on the field
            setTimeout(() => {
                firstErrorField.focus();
            }, 300);
        }
        
        return isValid;
    }

    /**
     * Bind Approval Form Validation
     */
    bindApprovalFormValidation() {
        // Remove error styling when user starts typing/selecting
        const decisionSelect = document.getElementById('approval-decision');
        const remarksTextarea = document.getElementById('approval-remarks');
        
        if (decisionSelect && !decisionSelect.hasAttribute('data-validation-bound')) {
            decisionSelect.setAttribute('data-validation-bound', 'true');
            decisionSelect.addEventListener('change', function() {
                if (this.value && this.value.trim() !== '') {
                    this.classList.remove('is-invalid');
                    const errorDiv = document.getElementById('approval-decision-error');
                    if (errorDiv) {
                        errorDiv.style.display = 'none';
                    }
                }
            });
        }
        
        if (remarksTextarea && !remarksTextarea.hasAttribute('data-validation-bound')) {
            remarksTextarea.setAttribute('data-validation-bound', 'true');
            remarksTextarea.addEventListener('input', function() {
                if (this.value && this.value.trim() !== '') {
                    this.classList.remove('is-invalid');
                    const errorDiv = document.getElementById('approval-remarks-error');
                    if (errorDiv) {
                        errorDiv.style.display = 'none';
                    }
                }
            });
        }
    }

    /**
     * Toggle Select All
     */
    toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('#approvalPOTableBody .row-checkbox');
        
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
        const btn = this.manager.cachedElements?.processBulkApprovalBtn;
        if (btn) {
            btn.disabled = this.manager.selectedPONumbers.size === 0;
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
}

// Make available globally
if (typeof window !== 'undefined') {
    window.ApprovalPOListApproval = ApprovalPOListApproval;
}

