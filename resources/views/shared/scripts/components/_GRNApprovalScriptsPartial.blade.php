<script src="{{ asset('js/procurement/ProcurementSharedCache.js') }}"></script>
<script src="{{ asset('js/procurement/ProcurementWizardUtils.js') }}"></script>
<script src="{{ asset('js/inventory/GRNApprovalAPI.js') }}"></script>
<script src="{{ asset('js/inventory/GRNApprovalFilter.js') }}"></script>
<script src="{{ asset('js/inventory/GRNApprovalTable.js') }}"></script>
<script src="{{ asset('js/inventory/GRNListView.js') }}"></script>
<script>
'use strict';

window.toggleGRNApprovalFilter = function() {
    const content = document.getElementById('grn-approval-filter-content');
    const chevron = document.getElementById('grn-approval-filter-chevron');
    if (!content || !chevron) return;
    const show = !content.classList.contains('show');
    content.classList.toggle('show', show);
    chevron.classList.toggle('bx-chevron-down', !show);
    chevron.classList.toggle('bx-chevron-up', show);
};

class GRNApprovalManager {
    constructor() {
        this.dataTable = null;
        this.currentGRNumber = null;
        this.currentPONumber = null;
        this.currentApprovalStatus = '';
        this.currentDocuments = [];
        this.viewedDocumentIds = new Set();
        this.apiModule = typeof GRNApprovalAPI !== 'undefined' ? new GRNApprovalAPI() : null;
        this.filterModule = typeof GRNApprovalFilter !== 'undefined' ? new GRNApprovalFilter(this) : null;
        this.tableModule = typeof GRNApprovalTable !== 'undefined' ? new GRNApprovalTable(this) : null;
        this.viewModule = typeof GRNListView !== 'undefined' ? new GRNListView(this) : null;
        this.init();
    }

    async init() {
        if (this.filterModule && this.filterModule.init) this.filterModule.init();
        if (this.tableModule && this.tableModule.initializeDataTable) {
            await this.tableModule.initializeDataTable();
            this.dataTable = this.tableModule.dataTable || null;
        }
    }

    search() {
        if (this.dataTable) this.dataTable.ajax.reload();
    }

    resetFilter() {
        if (this.filterModule && this.filterModule.resetFilter) this.filterModule.resetFilter();
    }

    async viewApproval(grNumber, poNumber, approvalStatus = '') {
        this.currentGRNumber = grNumber;
        this.currentPONumber = poNumber;
        this.currentApprovalStatus = (approvalStatus || '').toLowerCase();
        this.viewedDocumentIds = new Set();
        if (this.viewModule && this.viewModule.viewGRN) {
            await this.viewModule.viewGRN(poNumber);
        }

        this.setCreateActionsVisible(false);
        this.setGrDetailReadOnly(true);
        await this.loadSupportingDocuments(grNumber);
        this.prepareApprovalAction();
    }

    setCreateActionsVisible(isVisible) {
        document.querySelectorAll('#viewGRNSection .btn[onclick="grnManager.saveGRN()"], #viewGRNSection .btn[onclick="grnManager.submitFinalGRN()"]').forEach(btn => {
            btn.style.display = isVisible ? '' : 'none';
        });

        const addDocumentBtn = document.getElementById('grnAddDocumentBtn');
        const docCard = addDocumentBtn ? addDocumentBtn.closest('.card') : null;
        if (docCard) {
            docCard.style.display = isVisible ? '' : 'none';
        }
    }

    setGrDetailReadOnly(isReadOnly) {
        const selectors = ['.grn-actual-received', '.grn-tanggal-terima', '.grn-remark'];
        selectors.forEach((selector) => {
            document.querySelectorAll(`#viewGRNSection ${selector}`).forEach((el) => {
                el.disabled = !!isReadOnly;
                if (isReadOnly) {
                    el.classList.add('bg-light');
                } else {
                    el.classList.remove('bg-light');
                }
            });
        });
    }

    prepareApprovalAction() {
        const actionSection = document.getElementById('approvalActionSection');
        const actionSelect = document.getElementById('approvalAction');
        const remarksTextarea = document.getElementById('approvalRemarks');
        const submitBtn = document.getElementById('submitApprovalBtn');
        if (!actionSection || !actionSelect || !remarksTextarea || !submitBtn) return;

        const isWaiting = this.currentApprovalStatus === '' || this.currentApprovalStatus === 'waiting approval';
        actionSection.style.display = 'block';
        actionSelect.value = '';
        remarksTextarea.value = '';
        actionSelect.disabled = !isWaiting;
        remarksTextarea.disabled = !isWaiting;
        submitBtn.disabled = !isWaiting;

        if (!isWaiting) {
            const finalDecision = this.currentApprovalStatus === 'approved' ? 'Approved' : 'Rejected';
            remarksTextarea.value = `This GR has already been ${finalDecision.toLowerCase()}.`;
        }
    }

    async loadSupportingDocuments(grNumber) {
        const tbody = document.getElementById('view-documents-tbody');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Loading documents...</td></tr>';

        try {
            const documents = this.apiModule && this.apiModule.getGRDocuments
                ? await this.apiModule.getGRDocuments(grNumber)
                : [];
            this.currentDocuments = Array.isArray(documents) ? documents : [];
            this.renderSupportingDocuments();
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="3" class="text-center text-danger">${this.escapeHtml(e.message || 'Failed to load documents')}</td></tr>`;
        }
    }

    renderSupportingDocuments() {
        const tbody = document.getElementById('view-documents-tbody');
        if (!tbody) return;

        if (!Array.isArray(this.currentDocuments) || this.currentDocuments.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No documents</td></tr>';
            return;
        }

        tbody.innerHTML = this.currentDocuments.map((doc) => {
            const docId = doc.id || doc.ID || 0;
            const fileName = this.escapeHtml(doc.fileName || doc.FileName || '-');
            const fileSize = this.escapeHtml(doc.fileSize || doc.FileSize || '-');
            const isViewed = this.viewedDocumentIds.has(String(docId));
            return `
                <tr>
                    <td>
                        ${fileName}
                        <span class="badge bg-success ms-2 doc-viewed-badge" data-doc-id="${docId}" style="display:${isViewed ? 'inline-block' : 'none'}">Viewed</span>
                        <span class="badge bg-secondary ms-2 doc-not-viewed-badge" data-doc-id="${docId}" style="display:${isViewed ? 'none' : 'inline-block'}">Not Viewed</span>
                    </td>
                    <td>${fileSize}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-primary" title="View" onclick="downloadDocument(${docId})">
                            <i class="icon-base bx bx-show"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    async openDocumentPreview(documentId) {
        const selectedDocument = (this.currentDocuments || []).find((doc) => String(doc.id || doc.ID || '') === String(documentId));
        if (!selectedDocument) {
            this.showErrorModal('Document not found.');
            return;
        }

        const fileName = selectedDocument.fileName || selectedDocument.FileName || 'document';
        const endpoint = `/Inventory/GoodReceiveNotes/Documents/${encodeURIComponent(documentId)}/download?preview=1`;
        const previewUrl = this.buildApiUrl('inventory', endpoint);
        const downloadUrl = this.buildApiUrl('inventory', `/Inventory/GoodReceiveNotes/Documents/${encodeURIComponent(documentId)}/download`);

        window.__currentDocumentPreview = {
            id: documentId,
            fileName,
            downloadUrl
        };

        const filenameEl = document.getElementById('documentPreviewFilename');
        const frame = document.getElementById('documentPreviewFrame');
        const modalEl = document.getElementById('documentPreviewModal');
        if (filenameEl) filenameEl.textContent = fileName;
        if (frame) {
            frame.src = '';
            frame.src = previewUrl;
        }

        // Mark as viewed after user opens preview and update badge.
        this.viewedDocumentIds.add(String(documentId));
        const viewedEls = document.querySelectorAll('.doc-viewed-badge[data-doc-id="' + String(documentId) + '"]');
        const notViewedEls = document.querySelectorAll('.doc-not-viewed-badge[data-doc-id="' + String(documentId) + '"]');
        viewedEls.forEach((el) => { el.style.display = 'inline-block'; });
        notViewedEls.forEach((el) => { el.style.display = 'none'; });

        if (modalEl && typeof bootstrap !== 'undefined') {
            try {
                if (modalEl.parentNode !== document.body) {
                    document.body.appendChild(modalEl);
                }
            } catch (e) {
                console.warn('Could not append modal to body:', e);
            }

            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        } else {
            window.open(previewUrl, '_blank');
        }
    }

    buildApiUrl(apiType, endpoint) {
        if (typeof API_URLS === 'undefined') return endpoint;
        if (typeof window.normalizedApiUrls === 'undefined') {
            window.normalizedApiUrls = {};
        }
        for (const key in API_URLS) {
            if (Object.prototype.hasOwnProperty.call(API_URLS, key)) {
                window.normalizedApiUrls[key.toLowerCase()] = API_URLS[key];
            }
        }
        const base = window.normalizedApiUrls[(apiType || '').toLowerCase()] || '';
        return base ? `${base}${endpoint}` : endpoint;
    }

    async submitDecision(decision) {
        if (!this.currentGRNumber) return;
        if (!decision || (decision !== 'approve' && decision !== 'reject')) {
            this.showErrorModal('Please select an action (Approve or Reject).');
            return;
        }

        if (decision === 'approve' && Array.isArray(this.currentDocuments) && this.currentDocuments.length > 0) {
            const unviewedCount = this.currentDocuments.filter((doc) => {
                const docId = doc.id || doc.ID || 0;
                return !this.viewedDocumentIds.has(String(docId));
            }).length;
            if (unviewedCount > 0) {
                this.showErrorModal('You must view all supporting documents before approving.');
                return;
            }
        }

        const remark = (document.getElementById('approvalRemarks')?.value || '').trim();
        if (!remark) {
            const remarksError = document.getElementById('approvalRemarksError');
            if (remarksError) {
                remarksError.textContent = 'Please Filled up the Remarks field';
                remarksError.style.display = 'block';
            } else {
                this.showErrorModal('Please Filled up the Remarks field');
            }
            return;
        }

        const confirmTitle = decision === 'approve'
            ? `Are you sure you want to APPROVE this Good Receive Note?`
            : `Are you sure you want to REJECT this Good Receive Note?`;
        const confirmText = decision === 'approve'
            ? `This action will approve the Good Receive Note ${this.currentGRNumber}. This action cannot be undone.`
            : `This action will reject the Good Receive Note ${this.currentGRNumber}. This action cannot be undone.`;

        const confirmed = await this.showConfirmModal(confirmTitle, confirmText);
        if (!confirmed) return;

        try {
            const submitBtn = document.getElementById('submitApprovalBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="icon-base bx bx-loader-circle me-2 spin"></i>Submitting...';
            }

            const res = await this.apiModule.submitApproval(this.currentGRNumber, decision, remark);
            if (res && res.success !== false) {
                if (typeof Swal !== 'undefined') {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: res.message || 'Approval action saved.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
                this.backToList();
            } else {
                throw new Error(res?.message || 'Failed to submit decision.');
            }
        } catch (e) {
            this.showErrorModal(e.message || 'Failed to submit decision.');
        } finally {
            const submitBtn = document.getElementById('submitApprovalBtn');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="icon-base bx bx-check me-2"></i><span class="align-middle d-sm-inline-block d-none">Submit Approval</span>';
            }
        }
    }

    backToList() {
        document.getElementById('viewGRNSection').style.display = 'none';
        document.getElementById('grnListSection').style.display = 'block';
        const remarkEl = document.getElementById('approvalRemarks');
        const actionEl = document.getElementById('approvalAction');
        if (remarkEl) remarkEl.value = '';
        if (actionEl) actionEl.value = '';
        this.currentGRNumber = null;
        this.currentPONumber = null;
        this.currentApprovalStatus = '';
        this.currentDocuments = [];
        this.viewedDocumentIds = new Set();
        if (this.dataTable) this.dataTable.ajax.reload();
    }

    showConfirmModal(title, message) {
        return new Promise((resolve) => {
            if (typeof Swal === 'undefined') {
                resolve(window.confirm(`${title}\n\n${message}`));
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
            }).then((result) => resolve(result.isConfirmed));
        });
    }

    showErrorModal(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'error', title: 'Error', text: message });
            return;
        }
        alert(message);
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text ?? '';
        return div.innerHTML;
    }

    // Keep compatibility with shared GR view buttons.
    saveGRN() {}
    submitFinalGRN() {}
}

document.addEventListener('DOMContentLoaded', function() {
    window.grnApprovalManager = new GRNApprovalManager();
    window.grnManager = window.grnApprovalManager;
    window.submitApproval = function() {
        const actionValue = (document.getElementById('approvalAction')?.value || '').trim();
        const actionError = document.getElementById('approvalActionError');
        if (actionError) {
            actionError.style.display = 'none';
            actionError.textContent = '';
        }
        if (!actionValue) {
            if (actionError) {
                actionError.textContent = 'Please select an action (Approve or Reject)';
                actionError.style.display = 'block';
            }
            return;
        }
        window.grnApprovalManager.submitDecision(actionValue);
    };
    window.downloadDocument = function(documentId) {
        window.grnApprovalManager.openDocumentPreview(documentId);
    };
    window.downloadDocumentFromModal = function() {
        const info = window.__currentDocumentPreview;
        if (!info || !info.downloadUrl) return;
        const link = document.createElement('a');
        link.href = info.downloadUrl;
        link.download = info.fileName || 'document';
        link.target = '_blank';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };
});
</script>
