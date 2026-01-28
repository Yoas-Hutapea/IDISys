/**
 * Procurement Wizard Documents Module
 * Handles Documents step: file upload, validation, table management, and pagination
 */

class ProcurementWizardDocuments {
    constructor(wizardInstance) {
        this.wizard = wizardInstance;
    }

    /**
     * Open file upload dialog
     */
    openFileUploadDialog() {
        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.multiple = true;
        fileInput.accept = '.pdf,.doc,.docx,.xls,.xlsx,.jpg,.png';
        fileInput.style.display = 'none';
        
        document.body.appendChild(fileInput);
        fileInput.click();
        
        fileInput.addEventListener('change', (e) => {
            const files = Array.from(e.target.files);
            if (files.length > 0) {
                if (this.wizard.validateAndAddDocuments) {
                    this.wizard.validateAndAddDocuments(files);
                }
            }
            document.body.removeChild(fileInput);
        });
    }

    /**
     * Validate and add documents
     */
    validateAndAddDocuments(files) {
        // AC25: Max size validation (2MB = 2 * 1024 * 1024 bytes)
        const maxSize = 2 * 1024 * 1024;
        
        // AC26: Allowed file formats
        const allowedFormats = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/jpeg',
            'image/jpg',
            'image/png'
        ];
        
        const allowedExtensions = ['.pdf', '.doc', '.docx', '.xls', '.xlsx', '.jpg', '.jpeg', '.png'];
        
        const validFiles = [];
        const invalidFiles = [];
        
        files.forEach(file => {
            let isValid = true;
            let errorMessage = '';
            
            // Check file size (AC25)
            if (file.size > maxSize) {
                isValid = false;
                const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
                errorMessage = `File "${file.name}" exceeds maximum size of 2MB (Current: ${fileSizeMB}MB)`;
            }
            
            // Check file format (AC26)
            if (isValid) {
                const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
                const isValidFormat = allowedFormats.includes(file.type) || 
                                     allowedExtensions.includes(fileExtension);
                
                if (!isValidFormat) {
                    isValid = false;
                    errorMessage = `File "${file.name}" format is not supported. Allowed formats: Word, XLSX, PDF, JPG, PNG`;
                }
            }
            
            if (isValid) {
                validFiles.push(file);
            } else {
                invalidFiles.push({ file, error: errorMessage });
            }
        });
        
        // Show error messages for invalid files
        if (invalidFiles.length > 0) {
            if (this.wizard.showFileValidationModal) {
                this.wizard.showFileValidationModal(invalidFiles);
            }
        }
        
        // Add valid files to table
        if (validFiles.length > 0) {
            this.addDocumentsToTable(validFiles);
        }
    }

    /**
     * Add documents to table
     */
    addDocumentsToTable(files) {
        if (!this.wizard.cachedElements || !this.wizard.cachedElements.documentTable) return;
        
        files.forEach(file => {
            // Store File object in Map for later upload
            if (this.wizard.documentFiles) {
                this.wizard.documentFiles.set(file.name, file);
            }
            
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td style="text-align: center;">
                    <button type="button" class="btn btn-sm btn-danger action-btn" title="Delete" onclick="deleteDocument(this)" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                        <i class="bx bx-trash" style="font-size: 16px; line-height: 1;"></i>
                    </button>
                </td>
                <td>${file.name}</td>
                <td>${(file.size / 1024).toFixed(2)} KB</td>
            `;
            
            // Add hover effect for Delete button
            const deleteBtn = newRow.querySelector('button[title="Delete"]');
            if (deleteBtn) {
                deleteBtn.addEventListener('mouseenter', function() {
                    this.className = this.className.replace(/\bbtn-danger\b/g, 'btn-outline-danger');
                });
                deleteBtn.addEventListener('mouseleave', function() {
                    this.className = this.className.replace(/\bbtn-outline-danger\b/g, 'btn-danger');
                });
            }
            this.wizard.cachedElements.documentTable.appendChild(newRow);
        });
        
        // Update pagination after adding documents
        const documentRowsPerPageSelect = document.getElementById('documentRowsPerPage');
        const rowsPerPage = documentRowsPerPageSelect ? parseInt(documentRowsPerPageSelect.value) : 10;
        const totalItems = this.getTotalDocumentItems();
        if (this.wizard.updateDocumentPagination) {
            this.wizard.updateDocumentPagination(totalItems, 1, rowsPerPage);
        }
        
        if (this.wizard.createAlert) {
            this.wizard.createAlert('success', `<strong>Documents Added!</strong> ${files.length} document(s) have been added to your procurement request.`, 3000);
        }
    }

    /**
     * Create document table row from document object (from API)
     */
    createDocumentTableRow(doc) {
        const row = document.createElement('tr');
        const fileName = doc.fileName || doc.FileName || '';
        const fileSize = doc.fileSize || doc.FileSize || '';
        const documentId = doc.id || doc.ID || doc.Id || null;
        
        // Store document data in data attribute
        if (documentId) {
            const documentData = {
                id: documentId,
                fileName: fileName,
                fileSize: fileSize
            };
            row.setAttribute('data-document-data', JSON.stringify(documentData));
        }
        
        const escapeHtml = this.wizard.escapeHtml || ((text) => {
            if (text == null) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        });
        
        row.innerHTML = `
            <td style="text-align: center;">
                <button type="button" class="btn btn-sm btn-danger action-btn delete-document-btn" title="Delete" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                    <i class="bx bx-trash" style="font-size: 16px; line-height: 1;"></i>
                </button>
            </td>
            <td>${escapeHtml(fileName)}</td>
            <td>${escapeHtml(fileSize)}</td>
        `;
        
        // Add delete event listener
        const deleteBtn = row.querySelector('.delete-document-btn');
        
        // Add hover effect for Delete button
        if (deleteBtn) {
            deleteBtn.addEventListener('mouseenter', function() {
                this.className = this.className.replace(/\bbtn-danger\b/g, 'btn-outline-danger');
            });
            deleteBtn.addEventListener('mouseleave', function() {
                this.className = this.className.replace(/\bbtn-outline-danger\b/g, 'btn-danger');
            });
        }
        if (deleteBtn) {
            deleteBtn.addEventListener('click', async () => {
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
                        
                        // If document is already saved, mark documents as not saved
                        if (documentData && documentData.id) {
                            localStorage.removeItem('procurementDraftDocumentsSaved');
                        }
                        
                        // Remove File object from Map
                        if (this.wizard.documentFiles) {
                            this.wizard.documentFiles.delete(fileName);
                        }
                        
                        row.remove();
                        // Update pagination after removing document
                        const documentRowsPerPageSelect = document.getElementById('documentRowsPerPage');
                        const rowsPerPage = documentRowsPerPageSelect ? parseInt(documentRowsPerPageSelect.value) : 10;
                        const totalItems = this.getTotalDocumentItems();
                        if (this.wizard.updateDocumentPagination) {
                            this.wizard.updateDocumentPagination(totalItems, 1, rowsPerPage);
                        }
                    }
                } else {
                    // Fallback to browser confirm
                    if (confirm(`Are you sure you want to delete "${fileName}"?`)) {
                        // Remove File object from Map
                        if (this.wizard.documentFiles) {
                            this.wizard.documentFiles.delete(fileName);
                        }
                        
                        row.remove();
                        // Update pagination after removing document
                        const documentRowsPerPageSelect = document.getElementById('documentRowsPerPage');
                        const rowsPerPage = documentRowsPerPageSelect ? parseInt(documentRowsPerPageSelect.value) : 10;
                        const totalItems = this.getTotalDocumentItems();
                        if (this.wizard.updateDocumentPagination) {
                            this.wizard.updateDocumentPagination(totalItems, 1, rowsPerPage);
                        }
                    }
                }
            });
        }
        
        return row;
    }

    /**
     * Update document pagination
     */
    updateDocumentPagination(totalItems, currentPage = 1, rowsPerPage = 10) {
        const paginationInfo = document.querySelector('#document .dataTables_info');
        const paginationContainer = document.querySelector('#document .dataTables_paginate ul');
        
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
        
        // Generate pagination buttons (similar pattern to detail pagination)
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
                    if (this.wizard.handleDocumentPageChange) {
                        this.wizard.handleDocumentPageChange(page, rowsPerPage);
                    }
                }
            });
        });
        
        // Update visible rows
        this.updateDocumentTableVisibility(currentPage, rowsPerPage);
    }

    /**
     * Update document table visibility based on pagination
     */
    updateDocumentTableVisibility(currentPage, rowsPerPage) {
        const rows = document.querySelectorAll('#documentTable tbody tr');
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
     * Handle document page change
     */
    handleDocumentPageChange(page, rowsPerPage) {
        if (this.wizard.getTotalDocumentItems) {
            this.updateDocumentPagination(this.wizard.getTotalDocumentItems(), page, rowsPerPage);
        }
    }

    /**
     * Get total document items
     */
    getTotalDocumentItems() {
        return document.querySelectorAll('#documentTable tbody tr').length;
    }

    /**
     * Update document pagination
     */
    updateDocumentPagination(totalItems, currentPage = 1, rowsPerPage = 10) {
        const paginationInfo = document.querySelector('#document .dataTables_info');
        const paginationContainer = document.querySelector('#document .dataTables_paginate ul');
        
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
                    this.handleDocumentPageChange(page, rowsPerPage);
                }
            });
        });
        
        // Update visible rows
        this.updateDocumentTableVisibility(currentPage, rowsPerPage);
    }

    /**
     * Update document table visibility
     */
    updateDocumentTableVisibility(currentPage, rowsPerPage) {
        const rows = document.querySelectorAll('#documentTable tbody tr');
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
     * Handle document page change
     */
    handleDocumentPageChange(page, rowsPerPage) {
        this.updateDocumentPagination(this.getTotalDocumentItems(), page, rowsPerPage);
    }

    /**
     * Load and fill documents from API
     */
    async loadAndFillDocuments(prNumber) {
        if (!prNumber) return;

        const documentTable = document.querySelector('#documentTable tbody');
        if (!documentTable) {
            console.warn('Document table not found');
            return;
        }

        try {
            // Call API to get documents
            const endpoint = `/Procurement/PurchaseRequest/PurchaseRequestDocuments/${encodeURIComponent(prNumber)}/documents`;
            const documentsData = await apiCall('Procurement', endpoint, 'GET');
            const documents = documentsData.data || documentsData;

            if (!documents || !Array.isArray(documents) || documents.length === 0) {
                console.log('No documents found for PR:', prNumber);
                documentTable.innerHTML = '';
                // Update pagination
                if (this.updateDocumentPagination) {
                    this.updateDocumentPagination(0, 1, 10);
                }
                return;
            }

            // Clear existing documents
            documentTable.innerHTML = '';

            // Add each document to the table
            documents.forEach(doc => {
                const row = this.createDocumentTableRow(doc);
                documentTable.appendChild(row);
            });

            // Update pagination
            const documentRowsPerPageSelect = document.getElementById('documentRowsPerPage');
            const rowsPerPage = documentRowsPerPageSelect ? parseInt(documentRowsPerPageSelect.value) : 10;
            const totalItems = this.getTotalDocumentItems();
            if (this.updateDocumentPagination) {
                this.updateDocumentPagination(totalItems, 1, rowsPerPage);
            }

        } catch (error) {
            console.error('Error loading documents:', error);
            // If 404 error or "not found" message, it's okay - documents don't exist for this PR yet
            const errorMessage = error.message || error.toString() || '';
            if (errorMessage.includes('not found') || errorMessage.includes('404') || 
                (error.statusCode === 404) || (error.response && error.response.status === 404)) {
                console.log('Documents not found for PR:', prNumber, '- This is expected if PR does not have documents yet');
                documentTable.innerHTML = '';
                // Update pagination
                if (this.updateDocumentPagination) {
                    this.updateDocumentPagination(0, 1, 10);
                }
            } else {
                throw error; // Re-throw other errors
            }
        }
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.ProcurementWizardDocuments = ProcurementWizardDocuments;
}

