/**
 * PostingListAction Module
 * Handles posting actions: submit posting, bulk post, checkbox management
 */
class PostingListAction {
    constructor(managerInstance) {
        this.manager = managerInstance;
    }

    /**
     * Setup posting action form listeners
     */
    setupPostingActionFormListeners() {
        // Clear error when decision is selected
        $(document).on('change', '#postingDecision', function() {
            const decisionError = document.getElementById('postingDecisionError');
            if (decisionError) {
                decisionError.style.display = 'none';
                decisionError.textContent = '';
            }
        });

        // Clear error when remarks are entered
        $(document).on('input', '#postingRemarks', function() {
            const remarksError = document.getElementById('postingRemarksError');
            if (remarksError) {
                remarksError.style.display = 'none';
                remarksError.textContent = '';
            }
        });
    }

    /**
     * Submit posting (single invoice)
     */
    async submitPosting() {
        const invoiceId = window.currentPostingInvoiceId;
        if (!invoiceId) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Invoice ID not found'
                });
            }
            return;
        }

        // Get form values
        const decisionSelect = document.getElementById('postingDecision');
        const remarksTextarea = document.getElementById('postingRemarks');
        const decisionError = document.getElementById('postingDecisionError');
        const remarksError = document.getElementById('postingRemarksError');

        // Clear previous errors
        if (decisionError) {
            decisionError.style.display = 'none';
            decisionError.textContent = '';
        }
        if (remarksError) {
            remarksError.style.display = 'none';
            remarksError.textContent = '';
        }

        let hasError = false;

        // Validate Decision
        const decision = decisionSelect ? decisionSelect.value : '';
        if (!decision || decision.trim() === '') {
            if (decisionError) {
                decisionError.textContent = 'Please select a decision (Post or Reject)';
                decisionError.style.display = 'block';
            }
            if (decisionSelect) {
                decisionSelect.focus();
            }
            hasError = true;
        }

        // Validate Remarks
        const remarks = remarksTextarea ? remarksTextarea.value.trim() : '';
        if (!remarks || remarks === '') {
            if (remarksError) {
                remarksError.textContent = 'Please fill the Remarks Field';
                remarksError.style.display = 'block';
            }
            if (remarksTextarea && !hasError) {
                remarksTextarea.focus();
            }
            hasError = true;
        }

        if (hasError) {
            return;
        }

        // Confirm action
        const actionText = decision === 'Post' ? 'post' : 'reject';
        if (typeof Swal === 'undefined') {
            console.error('Swal is not available');
            return;
        }

        const result = await Swal.fire({
            title: `${decision} Invoice?`,
            html: `Are you sure you want to ${actionText} this invoice?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: decision === 'Post' ? '#28a745' : '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: `Yes, ${decision} it!`,
            cancelButtonText: 'Cancel'
        });

        if (result.isConfirmed) {
            try {
                if (!this.manager || !this.manager.apiModule) {
                    throw new Error('API module not available');
                }

                if (decision === 'Post') {
                    // Post invoice
                    await this.manager.apiModule.postInvoice(invoiceId);
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Invoice posted successfully',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    // Reject invoice
                    await this.manager.apiModule.rejectInvoice(invoiceId);
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Invoice rejected successfully',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
                
                // Reload list and go back
                setTimeout(() => {
                    if (this.manager && this.manager.tableModule && this.manager.tableModule.loadData) {
                        this.manager.tableModule.loadData();
                    }
                    if (this.manager && this.manager.viewModule && this.manager.viewModule.backToList) {
                        this.manager.viewModule.backToList();
                    }
                }, 2000);
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || `Failed to ${actionText} invoice`
                });
            }
        }
    }

    /**
     * Toggle select all checkbox
     */
    toggleSelectAll(checkbox) {
        const isChecked = checkbox.checked;
        const checkboxes = document.querySelectorAll('#postingTable tbody .row-checkbox');
        
        checkboxes.forEach(cb => {
            cb.checked = isChecked;
            const invoiceID = parseInt(cb.getAttribute('data-invoice-id'));
            if (!isNaN(invoiceID)) {
                if (this.manager && this.manager.tableModule) {
                    this.manager.tableModule.toggleRowSelection(invoiceID, isChecked);
                }
            }
        });
        
        this.updateProcessButton();
        this.updateSelectAllCheckbox();
    }

    /**
     * Toggle row selection (called from table module)
     */
    toggleRowSelection(invoiceID, isSelected) {
        // This is handled by table module, but we update UI here
        this.updateProcessButton();
        this.updateSelectAllCheckbox();
    }

    /**
     * Update select all checkbox state
     */
    updateSelectAllCheckbox() {
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        if (!selectAllCheckbox) return;
        
        const checkboxes = document.querySelectorAll('#postingTable tbody .row-checkbox');
        if (checkboxes.length === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
            return;
        }
        
        const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
        const totalCount = checkboxes.length;
        
        if (checkedCount === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        } else if (checkedCount === totalCount) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = true;
        } else {
            selectAllCheckbox.indeterminate = true;
            selectAllCheckbox.checked = false;
        }
    }

    /**
     * Update process button state
     */
    updateProcessButton() {
        const processBtn = document.getElementById('processBulkPostBtn');
        if (processBtn && this.manager && this.manager.tableModule) {
            const selectedInvoiceIDs = this.manager.tableModule.selectedInvoiceIDs;
            const selectedCount = selectedInvoiceIDs ? selectedInvoiceIDs.size : 0;
            processBtn.disabled = selectedCount === 0;
        }
    }

    /**
     * Process bulk post
     */
    async processBulkPost() {
        if (!this.manager || !this.manager.tableModule) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Selection',
                    text: 'Please select at least one invoice to process'
                });
            }
            return;
        }

        const selectedInvoiceIDs = this.manager.tableModule.getSelectedInvoiceIDs();
        
        if (selectedInvoiceIDs.length === 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Selection',
                    text: 'Please select at least one invoice to process'
                });
            }
            return;
        }

        const modalResult = await this.showBulkPostingModal(selectedInvoiceIDs.length);
        
        if (modalResult.confirmed) {
            try {
                if (!this.manager || !this.manager.apiModule) {
                    throw new Error('API module not available');
                }

                const decision = modalResult.decision;
                const remarks = modalResult.remarks;

                if (decision === 'Post') {
                    const response = await this.manager.apiModule.bulkPostInvoices(selectedInvoiceIDs, remarks);
                    
                    if (response && response.successCount !== undefined) {
                        const successCount = response.successCount || 0;
                        const failedCount = response.failedCount || 0;
                        const totalCount = response.totalCount || selectedInvoiceIDs.length;
                        
                        let message = `Successfully posted ${successCount} out of ${totalCount} invoice(s)`;
                        if (failedCount > 0) {
                            message += `\n${failedCount} invoice(s) failed to post`;
                        }
                        
                        Swal.fire({
                            icon: successCount > 0 ? 'success' : 'error',
                            title: successCount > 0 ? 'Success' : 'Error',
                            text: message,
                            timer: 3000,
                            showConfirmButton: true
                        });
                        
                        // Clear selection and reload data
                        if (this.manager.tableModule) {
                            this.manager.tableModule.clearSelectedInvoiceIDs();
                        }
                        this.updateProcessButton();
                        
                        if (this.manager.tableModule && this.manager.tableModule.loadData) {
                            await this.manager.tableModule.loadData();
                        }
                    } else {
                        throw new Error(response.message || 'Failed to post invoices');
                    }
                } else {
                    // Handle bulk reject if needed
                    Swal.fire({
                        icon: 'info',
                        title: 'Info',
                        text: 'Bulk reject functionality is not yet implemented'
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'Failed to process invoices'
                });
            }
        }
    }

    /**
     * Show bulk posting modal
     */
    showBulkPostingModal(selectedCount) {
        return new Promise((resolve) => {
            const modalId = 'bulkPostingModal';
            let existingModal = document.getElementById(modalId);
            
            if (existingModal) {
                existingModal.remove();
            }
            
            const modalHtml = `
                <div class="modal fade" id="${modalId}" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title">Bulk Process Invoices</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body py-4">
                                <p class="mb-3">You are about to process <strong>${selectedCount}</strong> Invoice(s).</p>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Posting Decision<span class="text-danger">*</span></label>
                                    <select class="form-select" id="bulkPostingDecision">
                                        <option value="" disabled selected>Select Decision</option>
                                        <option value="Post">Post</option>
                                        <option value="Reject">Reject</option>
                                    </select>
                                    <div id="bulkPostingDecisionError" class="text-danger mt-1" style="display: none;"></div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Remarks<span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="bulkPostingRemarks" rows="4" placeholder="Enter remarks"></textarea>
                                    <div id="bulkPostingRemarksError" class="text-danger mt-1" style="display: none;"></div>
                                </div>
                            </div>
                            <div class="modal-footer border-0 justify-content-center gap-2">
                                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                                    Cancel
                                </button>
                                <button type="button" class="btn btn-primary" id="confirmBulkPostingBtn">
                                    <i class="bx bx-check me-2"></i>Submit
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modal = new bootstrap.Modal(document.getElementById(modalId));
            
            const confirmBtn = document.getElementById('confirmBulkPostingBtn');
            const cancelBtn = document.querySelector(`#${modalId} .btn-label-secondary`);
            const decisionSelect = document.getElementById('bulkPostingDecision');
            const remarksTextarea = document.getElementById('bulkPostingRemarks');
            
            // Add event listeners to clear errors when user starts typing/selecting
            if (decisionSelect) {
                decisionSelect.addEventListener('change', () => {
                    const decisionError = document.getElementById('bulkPostingDecisionError');
                    if (decisionError) {
                        decisionError.style.display = 'none';
                        decisionError.textContent = '';
                    }
                });
            }
            if (remarksTextarea) {
                remarksTextarea.addEventListener('input', () => {
                    const remarksError = document.getElementById('bulkPostingRemarksError');
                    if (remarksError) {
                        remarksError.style.display = 'none';
                        remarksError.textContent = '';
                    }
                });
            }
            
            if (confirmBtn) {
                confirmBtn.addEventListener('click', () => {
                    const decisionSelect = document.getElementById('bulkPostingDecision');
                    const remarksTextarea = document.getElementById('bulkPostingRemarks');
                    const decisionError = document.getElementById('bulkPostingDecisionError');
                    const remarksError = document.getElementById('bulkPostingRemarksError');
                    
                    // Clear previous error messages
                    if (decisionError) {
                        decisionError.style.display = 'none';
                        decisionError.textContent = '';
                    }
                    if (remarksError) {
                        remarksError.style.display = 'none';
                        remarksError.textContent = '';
                    }
                    
                    let hasError = false;
                    
                    // Validate Decision
                    const decision = decisionSelect ? decisionSelect.value : '';
                    if (!decision || decision.trim() === '') {
                        if (decisionError) {
                            decisionError.textContent = 'Please select an action (Post or Reject)';
                            decisionError.style.display = 'block';
                        }
                        if (decisionSelect) {
                            decisionSelect.focus();
                        }
                        hasError = true;
                    }
                    
                    // Validate Remarks
                    const remarks = remarksTextarea ? remarksTextarea.value.trim() : '';
                    if (!remarks || remarks === '') {
                        if (remarksError) {
                            remarksError.textContent = 'Please fill the Remarks Field';
                            remarksError.style.display = 'block';
                        }
                        if (remarksTextarea && !hasError) {
                            remarksTextarea.focus();
                        }
                        hasError = true;
                    }
                    
                    if (hasError) {
                        return;
                    }
                    
                    modal.hide();
                    resolve({ confirmed: true, decision, remarks });
                });
            }
            
            if (cancelBtn) {
                cancelBtn.addEventListener('click', () => {
                    modal.hide();
                    resolve({ confirmed: false });
                });
            }
            
            const modalElement = document.getElementById(modalId);
            modalElement.addEventListener('hidden.bs.modal', () => {
                modalElement.remove();
                resolve({ confirmed: false });
            });
            
            modal.show();
        });
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.PostingListAction = PostingListAction;
}

