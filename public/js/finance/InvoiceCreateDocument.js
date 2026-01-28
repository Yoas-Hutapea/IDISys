/**
 * InvoiceCreateDocument Module
 * Handles document checklist management
 */
class InvoiceCreateDocument {
    constructor(managerInstance) {
        this.manager = managerInstance;
        this.utils = null;
        
        if (window.InvoiceCreateUtils) {
            this.utils = new InvoiceCreateUtils();
        }
    }

    /**
     * Load Document Checklist
     */
    async loadDocumentChecklist(workTypeID, termOfPaymentID, termNumber) {
        try {
            const tbody = document.getElementById('tblDocumentChecklistBody');
            if (!tbody) return;
            
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div class="mt-2">Loading document checklist...</div>
                    </td>
                </tr>
            `;

            if (!this.manager || !this.manager.apiModule) {
                throw new Error('API module not available');
            }

            // Step 1: Get all document checklist mappings
            const allMappings = await this.manager.apiModule.getDocumentChecklistMappings();
            
            // Step 2: Filter by mstFINInvoiceWorkTypeID and TermNumber (term value: 30, 50, 20)
            // Note: termNumber here is the term value (30, 50, 20), not position (1, 2, 3)
            const searchWorkTypeID = parseInt(workTypeID) || 0;
            const searchTermNumber = parseFloat(termNumber) || 0; // termNumber is actually termValue (30, 50, 20)
            
            const filteredMappings = allMappings.filter(item => {
                const itemWorkTypeID = parseInt(item.mstFINInvoiceWorkTypeID || item.mstFinInvoiceWorkTypeId || 0);
                const itemTermNumber = parseFloat(item.termNumber || item.TermNumber || 0);
                
                return itemWorkTypeID === searchWorkTypeID && 
                       itemTermNumber === searchTermNumber;
            });

            tbody.innerHTML = '';
            
            if (filteredMappings.length > 0) {
                // Step 3: Get all Master Document Checklists
                const allMasterChecklists = await this.manager.apiModule.getMasterDocumentChecklists();
                
                const escapeHtml = this.utils ? this.utils.escapeHtml.bind(this.utils) : this.escapeHtml.bind(this);
                
                // Step 4: Process each mapping
                for (let index = 0; index < filteredMappings.length; index++) {
                    const mapping = filteredMappings[index];
                    const documentChecklistID = mapping.mstFINInvoiceDocumentChecklistID || mapping.mstFinInvoiceDocumentChecklistId || 0;
                    
                    // Step 5: Find Master Document Checklist by mstFINInvoiceDocumentChecklistID
                    const masterChecklist = allMasterChecklists.find(mc => 
                        parseInt(mc.id || mc.ID || 0) === parseInt(documentChecklistID)
                    );
                    
                    if (!masterChecklist) {
                        continue;
                    }
                    
                    // Step 6: Use data from Master Document Checklist
                    const documentName = masterChecklist.documentName || masterChecklist.DocumentName || '';
                    const fileName = masterChecklist.fileName || masterChecklist.FileName || '';
                    const filePath = masterChecklist.filePath || masterChecklist.FilePath || '';
                    
                    // Get mapping-specific data (IsMandatory, IsAuto)
                    const isMandatory = mapping.isMandatory !== undefined ? mapping.isMandatory : (mapping.IsMandatory !== undefined ? mapping.IsMandatory : false);
                    const isAuto = mapping.isAuto !== undefined ? mapping.isAuto : (mapping.IsAuto !== undefined ? mapping.IsAuto : false);
                    const mandatoryName = isMandatory ? 'Yes' : 'No';
                    const autoName = isAuto ? 'Auto' : 'Manual';
                    
                    // DocumentNumber and Remarks are empty initially
                    const documentNumber = '';
                    const remarks = '';
                    
                    const row = document.createElement('tr');
                    row.setAttribute('data-checklist-index', index);
                    row.innerHTML = `
                        <td class="text-center">${escapeHtml(documentName)}</td>
                        <td class="text-center">
                            <input type="text" class="form-control form-control-sm document-number-input" 
                                   id="txtDocumentNumber_${index}" 
                                   data-checklist-id="${documentChecklistID}"
                                   data-checklist-index="${index}"
                                   value="${escapeHtml(documentNumber)}" 
                                   placeholder="Enter document number">
                        </td>
                        <td class="text-center">
                            <input type="text" class="form-control form-control-sm" 
                                   id="txtRemarks_${index}" 
                                   data-checklist-id="${documentChecklistID}"
                                   value="${escapeHtml(remarks)}" 
                                   placeholder="Enter remarks">
                        </td>
                        <td class="text-center">${mandatoryName}</td>
                        <td class="text-center">${autoName}</td>
                        <td class="text-center">
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <div id="docFileInput_${index}">
                                    ${isAuto ? 
                                        (filePath ? 
                                            `<a href="#" class="btn btn-sm btn-outline-primary btn-download-document" data-file-path="${escapeHtml(filePath)}" data-file-name="${escapeHtml(fileName || documentName)}" title="Download Document">
                                                <i class="icon-base bx bx-download"></i> Download
                                             </a>` :
                                            '<span class="badge bg-label-info">Auto Generated</span>'
                                        ) : 
                                        `<input type="file" class="form-control form-control-sm document-file-input" id="txtUpload_${index}" data-checklist-id="${documentChecklistID}" data-checklist-index="${index}" accept="application/pdf">`
                                    }
                                </div>
                                <span id="docStatus_${index}" class="badge bg-label-success" style="display: none;">
                                    <i class="icon-base bx bx-check-circle me-1"></i> Filled
                                </span>
                                <button type="button" id="btnDeleteDocument_${index}" class="btn btn-sm btn-outline-danger btn-delete-document" data-checklist-index="${index}" style="display: none;" title="Delete Document">
                                    <i class="icon-base bx bx-trash"></i> Delete
                                </button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(row);
                    
                    // For Auto documents with filePath, mark as filled immediately
                    if (isAuto && filePath) {
                        const statusBadge = $(`#docStatus_${index}`);
                        statusBadge.removeClass('bg-label-secondary').addClass('bg-label-success');
                        statusBadge.html('<i class="icon-base bx bx-check-circle me-1"></i> Filled');
                        statusBadge.show();
                        const deleteBtn = $(`#btnDeleteDocument_${index}`);
                        if (deleteBtn.length) deleteBtn.hide();
                    }
                }
                
                // Setup event handlers
                this.setupDocumentEventHandlers();
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No document checklist available for this Work Type and Term of Payment</td>
                    </tr>
                `;
            }
        } catch (error) {
            const tbody = document.getElementById('tblDocumentChecklistBody');
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-4 text-danger">
                            <i class="icon-base bx bx-error-circle"></i>
                            <div class="mt-2">Failed to load document checklist</div>
                        </td>
                    </tr>
                `;
            }
        }
    }

    /**
     * Setup Document Event Handlers
     */
    setupDocumentEventHandlers() {
        const self = this;
        
        // Setup download document event handlers
        $(document).off('click', '.btn-download-document').on('click', '.btn-download-document', function(e) {
            e.preventDefault();
            const filePath = $(this).data('file-path');
            const fileName = $(this).data('file-name');
            if (filePath) {
                // TODO: Implement download functionality
                Swal.fire({
                    icon: 'info',
                    title: 'Download Document',
                    text: `Downloading: ${fileName}`
                });
            }
        });
        
        // Setup event handlers for document number input
        $(document).off('input', '.document-number-input').on('input', '.document-number-input', function() {
            const checklistIndex = $(this).data('checklist-index');
            const fileInput = $(`#txtUpload_${checklistIndex}`);
            const hasFile = fileInput.length > 0 && fileInput[0].files.length > 0;
            
            // Update status: filled only if file exists (not based on document number)
            self.updateDocumentStatus(checklistIndex, hasFile);
        });
        
        // Setup event handlers for file input
        $(document).off('change', '.document-file-input').on('change', '.document-file-input', function() {
            const checklistIndex = $(this).data('checklist-index');
            const hasFile = this.files.length > 0;
            
            // Update status: filled only if file exists
            self.updateDocumentStatus(checklistIndex, hasFile);
        });
        
        // Setup event handlers for delete document button
        $(document).off('click', '.btn-delete-document').on('click', '.btn-delete-document', function() {
            const checklistIndex = $(this).data('checklist-index');
            
            Swal.fire({
                icon: 'question',
                title: 'Delete Document',
                text: 'Are you sure you want to delete this document?',
                showCancelButton: true,
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Clear document number
                    const documentNumberInput = $(`#txtDocumentNumber_${checklistIndex}`);
                    documentNumberInput.val('');
                    
                    // Clear file input
                    const fileInput = $(`#txtUpload_${checklistIndex}`);
                    if (fileInput.length > 0) {
                        fileInput.val('');
                    }
                    
                    // Clear remarks
                    const remarksInput = $(`#txtRemarks_${checklistIndex}`);
                    remarksInput.val('');
                    
                    // Update status to not filled
                    self.updateDocumentStatus(checklistIndex, false);
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted',
                        text: 'Document has been deleted',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            });
        });
    }

    /**
     * Update Document Status
     */
    updateDocumentStatus(checklistIndex, isFilled) {
        const statusBadge = $(`#docStatus_${checklistIndex}`);
        const fileInputContainer = $(`#docFileInput_${checklistIndex}`);
        const deleteButton = $(`#btnDeleteDocument_${checklistIndex}`);
        
        // Check if this is an Auto document
        const downloadButton = fileInputContainer.find('.btn-download-document');
        const autoGeneratedBadge = fileInputContainer.find('.badge.bg-label-info');
        const isAutoDocument = downloadButton.length > 0 || autoGeneratedBadge.length > 0;
        
        if (isFilled) {
            if (statusBadge.length) {
                statusBadge.removeClass('bg-label-secondary').addClass('bg-label-success');
                statusBadge.html('<i class="icon-base bx bx-check-circle me-1"></i> Filled');
                statusBadge.show();
            }
            
            if (isAutoDocument) {
                // For Auto documents, keep download button visible
                if (deleteButton.length) deleteButton.hide();
            } else {
                // For Manual documents, hide file input and show delete button
                if (fileInputContainer.length) fileInputContainer.hide();
                if (deleteButton.length) deleteButton.show();
            }
        } else {
            if (statusBadge.length) {
                statusBadge.hide();
            }
            // Show file input/button download
            if (fileInputContainer.length) fileInputContainer.show();
            // Hide delete button
            if (deleteButton.length) deleteButton.hide();
        }
    }

    /**
     * Get Document Items for submission
     */
    getDocumentItems() {
        const documentItems = [];
        
        $('#tblDocumentChecklistBody tr').each(function() {
            const row = $(this);
            const documentNumberInput = row.find('input[id^="txtDocumentNumber_"]');
            const remarksInput = row.find('input[id^="txtRemarks_"]');
            const fileInput = row.find('input[type="file"]');
            const downloadLink = row.find('.btn-download-document');
            const mandatoryCell = row.find('td').eq(3); // Document Mandatory column
            const documentTypeCell = row.find('td').eq(4); // Document Type column
            
            // Get checklist ID from document number input
            const checklistId = documentNumberInput.length > 0 ? (documentNumberInput.data('checklist-id') || 0) : 0;
            
            if (checklistId > 0) {
                const documentNumber = documentNumberInput.length > 0 ? (documentNumberInput.val() || null) : null;
                const remark = remarksInput.length > 0 ? (remarksInput.val() || null) : null;
                const isMandatory = mandatoryCell.text().trim() === 'Yes';
                const documentType = documentTypeCell.text().trim() || null; // "Auto" or "Manual"
                
                // Handle file upload if exists (for Manual documents)
                let fileSize = null;
                let filePath = null;
                
                if (fileInput.length > 0 && fileInput[0].files.length > 0) {
                    const file = fileInput[0].files[0];
                    fileSize = file.size.toString();
                    filePath = file.name; // File name, actual path will be set after upload
                } else if (downloadLink.length > 0) {
                    // For Auto documents, get filePath from download link
                    filePath = downloadLink.data('file-path') || null;
                }
                
                // Add document item if there's document number, file, or it's an auto document with filePath
                if (documentNumber || (fileInput.length > 0 && fileInput[0].files.length > 0) || (documentType === 'Auto' && filePath)) {
                    documentItems.push({
                        documentNumber: documentNumber,
                        remark: remark,
                        isMandatory: isMandatory,
                        documentType: documentType,
                        fileSize: fileSize,
                        filePath: filePath
                    });
                }
            }
        });
        
        return documentItems;
    }

    /**
     * Validate Mandatory Documents
     */
    validateMandatoryDocuments() {
        const mandatoryChecklistItems = [];
        
        $('#tblDocumentChecklistBody tr').each(function() {
            const row = $(this);
            const mandatoryCell = row.find('td').eq(3); // Document Mandatory column
            const documentNameCell = row.find('td').eq(0); // Document Name column
            const fileInput = row.find('input[type="file"]');
            const documentNumberInput = row.find('input[id^="txtDocumentNumber_"]');
            
            if (mandatoryCell.text().trim() === 'Yes') {
                const documentName = documentNameCell.text().trim();
                const documentNumber = documentNumberInput.val() || '';
                const hasFile = fileInput.length > 0 && fileInput[0].files.length > 0;
                
                if (!documentNumber && !hasFile) {
                    mandatoryChecklistItems.push(documentName);
                }
            }
        });
        
        return mandatoryChecklistItems;
    }

    // Fallback methods if utils not available
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.InvoiceCreateDocument = InvoiceCreateDocument;
}

