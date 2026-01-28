/**
 * Procurement Wizard Approval Module
 * Handles Approval step: PIC selection, history modal, and validation
 */

class ProcurementWizardApproval {
    constructor(wizardInstance) {
        this.wizard = wizardInstance;
    }

    /**
     * Show approval PIC history modal
     */
    async showApprovalPICHistoryModal(prNumber, assignApproval) {
        try {
            // Update modal title
            const modalTitle = document.getElementById('historyModalTitle');
            if (modalTitle) {
                modalTitle.textContent = `${assignApproval} PIC History - ${prNumber}`;
            }
            
            // Show loading state
            const tbody = document.getElementById('approvalPICHistoryTableBody');
            const emptyMessage = document.getElementById('approvalPICHistoryEmpty');
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            Loading history...
                        </td>
                    </tr>
                `;
            }
            if (emptyMessage) {
                emptyMessage.style.display = 'none';
            }
            
            // Show modal
            const modalElement = document.getElementById('approvalPICHistoryModal');
            if (modalElement) {
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
                const modal = new bootstrap.Modal(modalElement);
                
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
                
                modal.show();
            }
            
            // Load history from API
            const historyData = await apiCall(
                'Procurement',
                `/Procurement/PurchaseRequest/PurchaseRequests/${encodeURIComponent(prNumber)}/approval-pic-log/${encodeURIComponent(assignApproval)}`,
                'GET'
            );
            
            const history = Array.isArray(historyData) ? historyData : (historyData.data || []);
            
            // Update table
            if (tbody) {
                if (history.length === 0) {
                    tbody.innerHTML = '';
                    if (emptyMessage) {
                        emptyMessage.style.display = 'block';
                    }
                } else {
                    const escapeHtml = this.wizard.escapeHtml || ((text) => {
                        if (text == null) return '';
                        const div = document.createElement('div');
                        div.textContent = text;
                        return div.innerHTML;
                    });
                    
                    tbody.innerHTML = history.map((item, index) => {
                        const changeDate = item.changeDate || item.ChangeDate || '';
                        const formattedDate = changeDate ? new Date(changeDate).toLocaleString('en-US', {
                            year: 'numeric',
                            month: '2-digit',
                            day: '2-digit',
                            hour: '2-digit',
                            minute: '2-digit'
                        }) : '';
                        
                        return `
                            <tr>
                                <td class="text-center">${index + 1}</td>
                                <td class="text-center">${escapeHtml(item.modifiedBy || item.ModifiedBy || '')}</td>
                                <td>${escapeHtml(item.modifiedByPosition || item.ModifiedByPosition || '')}</td>
                                <td>${escapeHtml(item.employeeName || item.EmployeeName || item.mstEmployeeID || item.MstEmployeeID || '')}</td>
                                <td>${escapeHtml(item.mstEmployeePositionName || item.MstEmployeePositionName || '')}</td>
                                <td class="text-center">${escapeHtml(formattedDate)}</td>
                            </tr>
                        `;
                    }).join('');
                    
                    if (emptyMessage) {
                        emptyMessage.style.display = 'none';
                    }
                }
            }
        } catch (error) {
            console.error('Error loading approval PIC history:', error);
            const tbody = document.getElementById('approvalPICHistoryTableBody');
            const escapeHtml = this.wizard.escapeHtml || ((text) => {
                if (text == null) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            });
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-danger">
                            Error loading history: ${escapeHtml(error.message || 'Unknown error')}
                        </td>
                    </tr>
                `;
            }
        }
    }

    /**
     * Open approval PIC history modal
     */
    async openApprovalPICHistoryModal(assignApproval) {
        // Map assignApproval to display value
        const assignApprovalMap = {
            'AssignApproval1': 'Approval 1',
            'AssignApproval2': 'Approval 2',
            'AssignApproval3': 'Approval 3',
            'AssignApproval4': 'Approval 4',
            'AssignApproval5': 'Approval 5'
        };
        const assignApprovalValue = assignApprovalMap[assignApproval] || assignApproval;
        
        // Get PR Number from localStorage or URL
        const draft = JSON.parse(localStorage.getItem('procurementDraft') || '{}');
        const prNumber = draft.purchReqNumber;
        
        if (!prNumber) {
            if (this.wizard.showValidationMessage) {
                this.wizard.showValidationMessage('PR Number not found. Please save the PR first.');
            }
            return;
        }
        
        // Show modal and load history
        await this.showApprovalPICHistoryModal(prNumber, assignApprovalValue);
    }

    /**
     * Fill approval form with PR data
     */
    async fillApprovalForm(pr) {
        if (!pr) return;

        try {
            // Get employee IDs from PR data
            const requestorId = pr.requestor || pr.Requestor || '';
            const applicantId = pr.applicant || pr.Applicant || '';
            const reviewedById = pr.reviewedBy || pr.ReviewedBy || '';
            const approvedById = pr.approvedBy || pr.ApprovedBy || '';
            const confirmedById = pr.confirmedBy || pr.ConfirmedBy || '';

            // Get employee cache for batch lookup (optimized - single API call)
            let employeeCache = null;
            if (window.procurementSharedCache && window.procurementSharedCache.batchGetEmployeeNames) {
                employeeCache = window.procurementSharedCache;
            } else if (this.wizard.apiModule && this.wizard.apiModule.sharedCache && this.wizard.apiModule.sharedCache.batchGetEmployeeNames) {
                employeeCache = this.wizard.apiModule.sharedCache;
            } else if (this.wizard.apiModule && this.wizard.apiModule.batchGetEmployeeNames) {
                employeeCache = this.wizard.apiModule;
            }

            // Collect all employee IDs for batch lookup
            const employeeIds = [];
            if (requestorId) employeeIds.push(requestorId);
            if (applicantId) employeeIds.push(applicantId);
            if (reviewedById) employeeIds.push(reviewedById);
            if (approvedById) employeeIds.push(approvedById);
            if (confirmedById) employeeIds.push(confirmedById);

            // Batch lookup all employee names at once (optimized - single API call)
            let nameMap = new Map();
            if (employeeCache && employeeCache.batchGetEmployeeNames && employeeIds.length > 0) {
                nameMap = await employeeCache.batchGetEmployeeNames(employeeIds);
            } else if (employeeIds.length > 0) {
                // Fallback: individual lookups (less efficient)
                let getEmployeeName;
                if (this.wizard.getEmployeeNameByEmployId) {
                    getEmployeeName = this.wizard.getEmployeeNameByEmployId.bind(this.wizard);
                } else if (this.wizard.apiModule && this.wizard.apiModule.getEmployeeNameByEmployId) {
                    getEmployeeName = this.wizard.apiModule.getEmployeeNameByEmployId.bind(this.wizard.apiModule);
                } else {
                    getEmployeeName = async () => '';
                }

                const employeeLookups = employeeIds.map(async (employeeId) => {
                    try {
                        const name = await getEmployeeName(employeeId);
                        return { employeeId, name };
                    } catch {
                        return { employeeId, name: '' };
                    }
                });
                
                const results = await Promise.all(employeeLookups);
                results.forEach(({ employeeId, name }) => {
                    if (name) {
                        nameMap.set(employeeId.trim().toLowerCase(), name);
                    }
                });
            }
            
            // Get names from map
            const requestorName = requestorId ? (nameMap.get(requestorId.trim().toLowerCase()) || '') : '';
            const applicantName = applicantId ? (nameMap.get(applicantId.trim().toLowerCase()) || '') : '';
            const reviewedByName = reviewedById ? (nameMap.get(reviewedById.trim().toLowerCase()) || '') : '';
            const approvedByName = approvedById ? (nameMap.get(approvedById.trim().toLowerCase()) || '') : '';
            const confirmedByName = confirmedById ? (nameMap.get(confirmedById.trim().toLowerCase()) || '') : '';

            // Fill requestor field (disabled, display only)
            const requestorField = document.getElementById('requestor');
            if (requestorField) {
                requestorField.value = requestorName || requestorId || '';
            }

            // Fill applicantApproval field (disabled, display only)
            const applicantApprovalField = document.getElementById('applicantApproval');
            if (applicantApprovalField) {
                applicantApprovalField.value = applicantName || applicantId || '';
            }

            // Fill reviewedBy field and hidden ID
            const reviewedByField = document.getElementById('reviewedBy');
            const reviewedByIdField = document.getElementById('reviewedById');
            if (reviewedByField) {
                reviewedByField.value = reviewedByName || reviewedById || '';
            }
            if (reviewedByIdField) {
                reviewedByIdField.value = reviewedById || '';
            }

            // Fill approvedBy field and hidden ID
            const approvedByField = document.getElementById('approvedBy');
            const approvedByIdField = document.getElementById('approvedById');
            if (approvedByField) {
                approvedByField.value = approvedByName || approvedById || '';
            }
            if (approvedByIdField) {
                approvedByIdField.value = approvedById || '';
            }

            // Fill confirmedBy field and hidden ID
            const confirmedByField = document.getElementById('confirmedBy');
            const confirmedByIdField = document.getElementById('confirmedById');
            if (confirmedByField) {
                confirmedByField.value = confirmedByName || confirmedById || '';
            }
            if (confirmedByIdField) {
                confirmedByIdField.value = confirmedById || '';
            }

        } catch (error) {
            console.error('Error filling approval form:', error);
            throw error;
        }
    }

    /**
     * Set Assign Approval fields to readonly (for status 5 - Rejected)
     * Note: ReviewedBy, ApprovedBy, and ConfirmedBy remain editable for status 5
     */
    setAssignApprovalReadonlyForRejected() {
        // Only make requestor and applicantApproval disabled
        const disabledFields = [
            'requestor',
            'applicantApproval'
        ];
        
        // Set requestor and applicantApproval to disabled
        disabledFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.disabled = true;
            }
        });
        
        // Keep ReviewedBy, ApprovedBy, and ConfirmedBy fields as readonly (not disabled)
        // This allows them to be changed via employee popup while preventing direct text input
        const readonlyFields = [
            'reviewedBy',
            'approvedBy',
            'confirmedBy'
        ];
        
        readonlyFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                // Ensure readonly attribute is set (not disabled)
                field.setAttribute('readonly', 'readonly');
                field.readOnly = true;
                field.disabled = false; // Make sure it's not disabled
            }
        });
        
        // Enable search and refresh buttons for ReviewedBy, ApprovedBy, and ConfirmedBy
        // This allows users to change the values via employee popup
        const enabledButtons = [
            'searchReviewedByBtn',
            'searchApprovedByBtn',
            'searchConfirmedByBtn',
            'viewReviewedByHistoryBtn',
            'viewApprovedByHistoryBtn',
            'viewConfirmedByHistoryBtn'
        ];
        
        enabledButtons.forEach(buttonId => {
            const button = document.getElementById(buttonId);
            if (button) {
                button.disabled = false;
                button.classList.remove('opacity-50');
            }
        });
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.ProcurementWizardApproval = ProcurementWizardApproval;
}

