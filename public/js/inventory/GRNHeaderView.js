class GRNHeaderView extends GRNListView {
    constructor(manager) {
        super(manager);
        this.currentGRNumber = null;
        this.currentDocuments = [];
    }

    async viewGRN(grNumber, poNumber) {
        this.currentGRNumber = grNumber;
        await super.viewGRN(poNumber);
        this.setReadOnlyMode();
        await this.loadSupportingDocuments(grNumber);
    }

    setReadOnlyMode() {
        const selectors = ['.grn-actual-received', '.grn-tanggal-terima', '.grn-remark'];
        selectors.forEach((selector) => {
            document.querySelectorAll(`#viewGRNSection ${selector}`).forEach((el) => {
                el.disabled = true;
                el.classList.add('bg-light');
            });
        });
    }

    async loadSupportingDocuments(grNumber) {
        const tbody = document.getElementById('grnReadOnlyDocumentsTbody');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Loading documents...</td></tr>';

        try {
            const documents = this.manager?.apiModule?.getGRDocuments
                ? await this.manager.apiModule.getGRDocuments(grNumber)
                : [];
            this.currentDocuments = Array.isArray(documents) ? documents : [];
            this.renderSupportingDocuments();
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="3" class="text-center text-danger">${this.escapeHtml(e.message || 'Failed to load documents')}</td></tr>`;
        }
    }

    renderSupportingDocuments() {
        const tbody = document.getElementById('grnReadOnlyDocumentsTbody');
        if (!tbody) return;

        if (!Array.isArray(this.currentDocuments) || this.currentDocuments.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No documents</td></tr>';
            return;
        }

        tbody.innerHTML = this.currentDocuments.map((doc) => {
            const docId = doc.id || doc.ID || 0;
            const fileName = this.escapeHtml(doc.fileName || doc.FileName || '-');
            const fileSize = this.escapeHtml(doc.fileSize || doc.FileSize || '-');
            return `
                <tr>
                    <td>${fileName}</td>
                    <td>${fileSize}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-primary" title="View" onclick="grnHeaderManager.viewModule.openDocumentPreview(${docId})">
                            <i class="icon-base bx bx-show"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    openDocumentPreview(documentId) {
        const selectedDocument = (this.currentDocuments || []).find((doc) => String(doc.id || doc.ID || '') === String(documentId));
        if (!selectedDocument) {
            this.showError('Document not found.');
            return;
        }

        const fileName = selectedDocument.fileName || selectedDocument.FileName || 'document';
        const previewEndpoint = `/Inventory/GoodReceiveNotes/Documents/${encodeURIComponent(documentId)}/download?preview=1`;
        const downloadEndpoint = `/Inventory/GoodReceiveNotes/Documents/${encodeURIComponent(documentId)}/download`;
        const previewUrl = this.buildApiUrl('inventory', previewEndpoint);
        const downloadUrl = this.buildApiUrl('inventory', downloadEndpoint);

        window.__grnReadOnlyDocumentPreview = {
            id: documentId,
            fileName,
            downloadUrl
        };

        const filenameEl = document.getElementById('grnReadOnlyDocumentPreviewFilename');
        const frame = document.getElementById('grnReadOnlyDocumentPreviewFrame');
        const modalEl = document.getElementById('grnReadOnlyDocumentPreviewModal');
        if (filenameEl) filenameEl.textContent = fileName;
        if (frame) {
            frame.src = '';
            frame.src = previewUrl;
        }

        if (modalEl && typeof bootstrap !== 'undefined') {
            if (modalEl.parentNode !== document.body) {
                document.body.appendChild(modalEl);
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

    backToList() {
        this.currentGRNumber = null;
        this.currentDocuments = [];
        super.backToList();
    }
}

if (typeof window !== 'undefined') window.GRNHeaderView = GRNHeaderView;
