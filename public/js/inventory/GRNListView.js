/**
 * GRN List View - load PO detail, items, Additional Information (trxPROPurchaseRequestAdditional), Vendor lengkap. Amount = UnitPrice * ActualReceived.
 */
class GRNListView {
    constructor(manager) {
        this.manager = manager;
        this.currentPONumber = null;
        this.viewPOAdditional = null;
    }

    async viewGRN(poNumber) {
        this.currentPONumber = poNumber;
        this.viewPOAdditional = null;
        document.getElementById('grnListSection').style.display = 'none';
        document.getElementById('viewGRNSection').style.display = 'block';
        document.getElementById('viewGRNLoading').style.display = 'block';
        document.getElementById('viewGRNData').style.display = 'none';

        try {
            const [po, items, grnLines] = await Promise.all([
                this.manager.apiModule.getPODetails(poNumber),
                this.manager.apiModule.getPOItems(poNumber),
                this.manager.apiModule.getGRNLines(poNumber)
            ]);

            if (!po) {
                this.showError('PO not found');
                this.backToList();
                return;
            }

            const prNumber = po.prNumber || po.trxPROPurchaseRequestNumber || '';
            const purchType = po.purchType || po.PurchaseType || '';
            const purchSubType = po.purchSubType || po.PurchaseSubType || '';

            if ((!this.manager.tableModule.allPurchaseTypes || !this.manager.tableModule.allPurchaseTypes.length) && this.manager.apiModule) {
                try {
                    const types = await this.manager.apiModule.getPurchaseTypes();
                    this.manager.tableModule.allPurchaseTypes = types || [];
                } catch (e) {
                    this.manager.tableModule.allPurchaseTypes = [];
                }
            }

            let typeId = null;
            if (purchType && this.manager.tableModule.allPurchaseTypes && this.manager.tableModule.allPurchaseTypes.length) {
                const typeIdInt = parseInt(String(purchType).trim(), 10);
                if (!isNaN(typeIdInt) && typeIdInt > 0) {
                    const t = this.manager.tableModule.allPurchaseTypes.find(x => parseInt(x.ID || x.id || '0', 10) === typeIdInt);
                    if (t) typeId = typeIdInt;
                }
                if (typeId == null) {
                    const t = this.manager.tableModule.allPurchaseTypes.find(x => {
                        const v = x.PurchaseRequestType || x.purchaseRequestType || '';
                        const c = x.Category || x.category || '';
                        const fmt = c && v !== c ? `${v} ${c}` : v;
                        return v === purchType || fmt === purchType;
                    });
                    if (t) typeId = parseInt(t.ID || t.id || '0', 10);
                }
            }

            if (typeId && this.manager.apiModule) {
                try {
                    const subTypes = await this.manager.apiModule.getPurchaseSubTypes(typeId);
                    if (!this.manager.tableModule.allPurchaseSubTypes) this.manager.tableModule.allPurchaseSubTypes = new Map();
                    this.manager.tableModule.allPurchaseSubTypes.set(typeId, Array.isArray(subTypes) ? subTypes : []);
                } catch (e) {
                    console.error('Load sub types:', e);
                }
            }

            let subTypeId = null;
            if (typeId && this.manager.tableModule.allPurchaseSubTypes && this.manager.tableModule.allPurchaseSubTypes.has(typeId)) {
                const subTypes = this.manager.tableModule.allPurchaseSubTypes.get(typeId) || [];
                const subIdInt = parseInt(String(purchSubType).trim(), 10);
                if (!isNaN(subIdInt) && subIdInt > 0) {
                    const st = subTypes.find(s => parseInt(s.ID || s.id || '0', 10) === subIdInt);
                    if (st) subTypeId = subIdInt;
                }
                if (subTypeId == null) {
                    const st = subTypes.find(s => (s.PurchaseRequestSubType || s.purchaseRequestSubType) === purchSubType);
                    if (st) subTypeId = parseInt(st.ID || st.id || '0', 10);
                }
            }

            if (prNumber && this.requiresAdditionalSection(typeId, subTypeId)) {
                try {
                    const additional = await this.manager.apiModule.getPRAdditional(prNumber);
                    this.viewPOAdditional = additional || null;
                } catch (e) {
                    this.viewPOAdditional = null;
                }
            }

            this.populatePurchaseInfo(po);
            this.populateVendorInfo(po);
            const lineMap = {};
            (grnLines || []).forEach(l => { lineMap[l.mstPROPurchaseItemInventoryItemID || l.itemId] = l; });
            this.populateDetailTable(poNumber, items || [], lineMap);

            const additionalHTML = await this.getAdditionalInformationSummaryHTML(po);
            const additionalContainer = document.getElementById('grn-additional-section-container');
            if (additionalContainer) {
                if (additionalHTML) {
                    additionalContainer.innerHTML = additionalHTML;
                    additionalContainer.style.display = 'block';
                } else {
                    additionalContainer.innerHTML = '';
                    additionalContainer.style.display = 'none';
                }
            }

            document.getElementById('viewGRNLoading').style.display = 'none';
            document.getElementById('viewGRNData').style.display = 'block';
        } catch (e) {
            console.error(e);
            this.showError('Failed to load PO: ' + (e.message || ''));
            this.backToList();
        }
    }

    requiresAdditionalSection(typeId, subTypeId) {
        if (!typeId) return false;
        if (typeId === 5 || typeId === 7) return true;
        if (typeId === 6 && subTypeId === 2) return true;
        if (typeId === 8 && subTypeId === 4) return true;
        if (typeId === 2 && (subTypeId === 1 || subTypeId === 3)) return true;
        if (typeId === 4 && subTypeId === 3) return true;
        if (typeId === 3 && (subTypeId === 4 || subTypeId === 5)) return true;
        return false;
    }

    async getAdditionalInformationSummaryHTML(po) {
        if (!this.viewPOAdditional) return '';
        const additional = this.viewPOAdditional;
        let typeId = null;
        let subTypeId = null;
        const purchType = po.purchType || po.PurchaseType || '';
        const purchSubType = po.purchSubType || po.PurchaseSubType || '';

        if (this.manager.tableModule.allPurchaseTypes && purchType) {
            let type = this.manager.tableModule.allPurchaseTypes.find(t => {
                const v = t.PurchaseRequestType || t.purchaseRequestType || '';
                const c = t.Category || t.category || '';
                const fmt = c && v !== c ? `${v} ${c}` : v;
                return v === purchType || fmt === purchType;
            });
            if (!type) {
                const n = parseInt(String(purchType).trim(), 10);
                if (!isNaN(n) && n > 0) type = this.manager.tableModule.allPurchaseTypes.find(t => parseInt(t.ID || t.id || '0', 10) === n);
            }
            if (type) typeId = parseInt(type.ID || type.id || '0', 10);
        }

        if (typeId && purchSubType && this.manager.apiModule) {
            try {
                const subTypes = await this.manager.apiModule.getPurchaseSubTypes(typeId);
                let subType = subTypes.find(st => (st.PurchaseRequestSubType || st.purchaseRequestSubType) === purchSubType);
                if (!subType) {
                    const n = parseInt(String(purchSubType).trim(), 10);
                    if (!isNaN(n) && n > 0) subType = subTypes.find(st => parseInt(st.ID || st.id || '0', 10) === n);
                }
                if (subType) subTypeId = parseInt(subType.ID || subType.id || '0', 10);
            } catch (e) {}
        }

        const shouldShowBillingTypeSection = typeId === 6 && subTypeId === 2;
        const shouldShowSonumbSection =
            (typeId === 8 && subTypeId === 4) ||
            (typeId === 2 && (subTypeId === 1 || subTypeId === 3)) ||
            (typeId === 4 && subTypeId === 3) ||
            (typeId === 3 && (subTypeId === 4 || subTypeId === 5));
        const shouldShowSubscribeSection = typeId === 5 || typeId === 7;

        if (!shouldShowBillingTypeSection && !shouldShowSonumbSection && !shouldShowSubscribeSection) return '';

        const formatDate = (dateValue) => {
            if (!dateValue || dateValue === '-') return '-';
            try {
                const d = new Date(dateValue);
                if (isNaN(d.getTime())) return dateValue;
                return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            } catch (_) { return dateValue; }
        };

        let summaryHTML = `
                <div class="card">
                    <div class="card-header" style="border-bottom: 1px solid #e9ecef;">
                        <h6 class="card-title mb-0">Additional Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
        `;

        if (shouldShowBillingTypeSection) {
            summaryHTML += `
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Billing Type</label>
                    <input type="text" class="form-control" value="${this.escapeHtml(additional.billingTypeName || additional.BillingTypeName || '-')}" disabled>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Period</label>
                    <input type="text" class="form-control" value="${this.escapeHtml(additional.period || additional.Period || '-')}" disabled>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Start Period</label>
                    <input type="text" class="form-control" value="${this.escapeHtml(formatDate(additional.startPeriod || additional.StartPeriod))}" disabled>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">End Period</label>
                    <input type="text" class="form-control" value="${this.escapeHtml(formatDate(additional.endPeriod || additional.EndPeriod))}" disabled>
                </div>
            `;
        } else if (shouldShowSonumbSection) {
            summaryHTML += `
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Sonumb</label>
                    <input type="text" class="form-control" value="${this.escapeHtml(additional.sonumb || additional.Sonumb || '-')}" disabled>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Site Name</label>
                    <input type="text" class="form-control" value="${this.escapeHtml(additional.siteName || additional.SiteName || '-')}" disabled>
                </div>
                ${(additional.siteID || additional.SiteID) && (additional.siteID || additional.SiteID) !== '-' ? `
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Site ID</label>
                    <input type="text" class="form-control" value="${this.escapeHtml(additional.siteID || additional.SiteID)}" disabled>
                </div>
                ` : ''}
            `;
        } else if (shouldShowSubscribeSection) {
            summaryHTML += `
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Sonumb</label>
                    <input type="text" class="form-control" value="${this.escapeHtml(additional.sonumb || additional.Sonumb || '-')}" disabled>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Site Name</label>
                    <input type="text" class="form-control" value="${this.escapeHtml(additional.siteName || additional.SiteName || '-')}" disabled>
                </div>
                ${(additional.siteID || additional.SiteID) && (additional.siteID || additional.SiteID) !== '-' ? `
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Site ID</label>
                    <input type="text" class="form-control" value="${this.escapeHtml(additional.siteID || additional.SiteID)}" disabled>
                </div>
                ` : ''}
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Billing Type</label>
                    <input type="text" class="form-control" value="${this.escapeHtml(additional.billingTypeName || additional.BillingTypeName || '-')}" disabled>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Period</label>
                    <input type="text" class="form-control" value="${this.escapeHtml(additional.period || additional.Period || '-')}" disabled>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Start Period</label>
                    <input type="text" class="form-control" value="${this.escapeHtml(formatDate(additional.startPeriod || additional.StartPeriod))}" disabled>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">End Period</label>
                    <input type="text" class="form-control" value="${this.escapeHtml(formatDate(additional.endPeriod || additional.EndPeriod))}" disabled>
                </div>
            `;
        }

        summaryHTML += `
                        </div>
                    </div>
                </div>
        `;
        return summaryHTML;
    }

    populatePurchaseInfo(po) {
        const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val ?? ''; };
        const fmtDate = v => !v ? '' : (new Date(v)).toLocaleDateString('en-GB');
        const fmtCur = v => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Number(v) || 0);
        set('grn-po-number', po.purchOrderID || po.PurchOrderID);
        set('grn-po-date', fmtDate(po.poDate || po.PurchaseOrderDate));
        set('grn-po-name', po.purchOrderName || po.PurchaseOrderName);
        set('grn-po-author', po.poAuthor || po.PurchaseOrderAuthor);
        const purchTypeDisplay = this.getPurchaseTypeDisplayName(po.purchType || po.PurchaseType, po.purchSubType || po.PurchaseSubType);
        const purchSubTypeDisplay = this.getPurchaseSubTypeDisplayName(po.purchSubType || po.PurchaseSubType, po);
        set('grn-purch-type', purchTypeDisplay);
        set('grn-po-amount', fmtCur(po.poAmount || po.PurchaseOrderAmount));
        set('grn-purch-sub-type', purchSubTypeDisplay);
        set('grn-status', po.approvalStatus || po.ApprovalStatus);
        set('grn-pr-number', po.prNumber || po.trxPROPurchaseRequestNumber);
        set('grn-company', po.companyName || po.CompanyName);
        set('grn-pr-requestor', po.prRequestor || po.PurchaseRequestRequestor);
    }

    getPurchaseTypeDisplayName(purchType, purchSubType) {
        if (purchType == null) return '';
        if (this.manager && this.manager.tableModule && typeof this.manager.tableModule.formatPurchaseType === 'function') {
            return this.manager.tableModule.formatPurchaseType(purchType, {}, false) || '';
        }
        return String(purchType ?? '');
    }

    getPurchaseSubTypeDisplayName(purchSubType, po) {
        if (purchSubType == null) return '';
        if (this.manager && this.manager.tableModule && typeof this.manager.tableModule.formatPurchaseSubType === 'function') {
            return this.manager.tableModule.formatPurchaseSubType(purchSubType, po || {}, false) || '';
        }
        return String(purchSubType ?? '');
    }

    populateVendorInfo(po) {
        const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val ?? ''; };
        set('grn-vendor-type', po.vendorType || po.VendorType || '');
        set('grn-vendor-name', po.mstVendorVendorName || po.MstVendorVendorName || '');
        set('grn-core-business', po.coreBusiness || po.CoreBusiness || '');
        set('grn-sub-core-business', po.subCoreBusiness || po.SubCoreBusiness || '');
        set('grn-contract-number', po.contractNumber || po.ContractNumber || '');
        set('grn-contract-period', po.contractPeriod || po.ContractPeriod || '');
        set('grn-top', po.topDescription || po.TOPDescription || po.TopDescription || '');
        set('grn-top-description', po.descriptionVendor || po.DescriptionVendor || '');
        const vendorType = po.vendorType || po.VendorType || '';
        const isNonContract = vendorType && String(vendorType).toLowerCase().trim() === 'non contract';
        document.querySelectorAll('.vendor-contract-field').forEach(el => {
            el.style.display = isNonContract ? 'none' : '';
        });
    }

    populateDetailTable(poNumber, items, lineMap) {
        const tbody = document.getElementById('grn-items-tbody');
        tbody.innerHTML = '';
        const fmtNum = n => new Intl.NumberFormat('id-ID').format(Number(n) || 0);
        const fmtCur = n => new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0 }).format(Number(n) || 0);
        let total = 0;

        items.forEach((item, idx) => {
            const itemId = item.mstPROPurchaseItemInventoryItemID || item.ItemID || '';
            const qty = Number(item.ItemQty || item.itemQty || 0);
            const unitPrice = Number(item.UnitPrice || item.unitPrice || 0);
            const saved = lineMap[itemId] || {};
            let actualReceived = saved.actualReceived != null ? Number(saved.actualReceived) : qty;
            if (actualReceived > qty) actualReceived = qty;
            const amount = actualReceived * unitPrice;
            total += amount;
            const remark = saved.remark || '';
            let receiveDate = saved.tanggalTerima || '';
            if (receiveDate && receiveDate.length >= 10) {
                receiveDate = receiveDate.substring(0, 10);
            } else if (receiveDate) {
                try { receiveDate = new Date(receiveDate).toISOString().slice(0, 10); } catch (_) {}
            }

            const qtyRemain = Math.max(0, qty - actualReceived);
            const tr = document.createElement('tr');
            tr.dataset.itemId = itemId;
            tr.dataset.unitPrice = unitPrice;
            tr.dataset.itemQty = qty;
            tr.innerHTML = `
                <td>${this.escapeHtml(poNumber)}</td>
                <td>${this.escapeHtml(itemId)}</td>
                <td>${this.escapeHtml(item.ItemName || item.itemName || '')}</td>
                <td>${this.escapeHtml(item.ItemDescription || item.itemDescription || '')}</td>
                <td>${this.escapeHtml(item.ItemUnit || item.itemUnit || '')}</td>
                <td class="text-end">${fmtNum(qty)}</td>
                <td class="text-end"><input type="number" step="0.01" min="0" max="${qty}" class="form-control form-control-sm text-end grn-actual-received" value="${actualReceived}" data-idx="${idx}" placeholder="≤ ${qty}"></td>
                <td class="text-end grn-qty-remain-cell">${fmtNum(qtyRemain)}</td>
                <td>${this.escapeHtml(item.CurrencyCode || item.currencyCode || '')}</td>
                <td class="text-end">${fmtCur(unitPrice)}</td>
                <td class="text-end"><input type="text" class="form-control form-control-sm text-end grn-amount" readonly value="${fmtCur(amount)}" data-idx="${idx}"></td>
                <td><input type="date" class="form-control form-control-sm grn-tanggal-terima" value="${receiveDate}" data-idx="${idx}"></td>
                <td><input type="text" class="form-control form-control-sm grn-remark" value="${this.escapeHtml(remark)}" data-idx="${idx}" maxlength="255"></td>
            `;
            tbody.appendChild(tr);
        });

        tbody.querySelectorAll('.grn-actual-received').forEach(input => {
            input.addEventListener('input', () => this.recalcRowAmount(input));
            input.addEventListener('change', () => this.recalcRowAmount(input));
        });

        document.getElementById('grn-amount-total').value = fmtCur(total);
    }

    recalcRowAmount(input) {
        const tr = input.closest('tr');
        const itemQty = parseFloat(tr.dataset.itemQty || 0);
        const unitPrice = parseFloat(tr.dataset.unitPrice || 0);
        let actual = parseFloat(input.value) || 0;
        if (actual > itemQty) {
            actual = itemQty;
            input.value = itemQty;
            input.classList.add('is-invalid');
        } else {
            input.classList.remove('is-invalid');
        }
        const qtyRemain = Math.max(0, itemQty - actual);
        const qtyRemainCell = tr.querySelector('.grn-qty-remain-cell');
        if (qtyRemainCell) qtyRemainCell.textContent = new Intl.NumberFormat('id-ID').format(qtyRemain);
        const amount = actual * unitPrice;
        const amountInput = tr.querySelector('.grn-amount');
        if (amountInput) amountInput.value = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0 }).format(amount);
        let total = 0;
        document.querySelectorAll('#grn-items-tbody .grn-actual-received').forEach(inp => {
            const r = inp.closest('tr');
            const u = parseFloat(r.dataset.unitPrice || 0);
            const a = parseFloat(inp.value) || 0;
            total += a * u;
        });
        document.getElementById('grn-amount-total').value = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0 }).format(total);
    }

    async submitGRN() {
        if (!this.currentPONumber) return;
        const tbody = document.getElementById('grn-items-tbody');
        const fmtNum = n => new Intl.NumberFormat('id-ID').format(Number(n) || 0);
        for (const tr of tbody.querySelectorAll('tr')) {
            const itemQty = parseFloat(tr.dataset.itemQty || 0);
            const actualInput = tr.querySelector('.grn-actual-received');
            const actual = actualInput ? parseFloat(actualInput.value) || 0 : 0;
            if (actual > itemQty) {
                actualInput.classList.add('is-invalid');
                this.showError(`Actual Received (${fmtNum(actual)}) cannot exceed Item Qty (${fmtNum(itemQty)}). Please correct the row and try again.`);
                return;
            }
            actualInput.classList.remove('is-invalid');
        }
        const lines = [];
        tbody.querySelectorAll('tr').forEach(tr => {
            const itemId = tr.dataset.itemId;
            const actualInput = tr.querySelector('.grn-actual-received');
            const remarkInput = tr.querySelector('.grn-remark');
            const tanggalInput = tr.querySelector('.grn-tanggal-terima');
            lines.push({
                mstPROPurchaseItemInventoryItemID: itemId,
                actualReceived: actualInput ? parseFloat(actualInput.value) || 0 : 0,
                remark: remarkInput ? remarkInput.value : '',
                tanggalTerima: tanggalInput && tanggalInput.value ? tanggalInput.value : null
            });
        });

        try {
            const res = await this.manager.apiModule.saveGRN(this.currentPONumber, lines);
            if (res && res.success !== false) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'success', title: 'Saved', text: 'Good Receive Note saved.', timer: 2000, showConfirmButton: false });
                } else {
                    alert('Good Receive Note saved.');
                }
            } else {
                this.showError(res?.message || 'Save failed');
            }
        } catch (e) {
            console.error(e);
            this.showError(e.message || 'Save failed');
        }
    }

    backToList() {
        document.getElementById('viewGRNSection').style.display = 'none';
        document.getElementById('grnListSection').style.display = 'block';
        this.currentPONumber = null;
        if (this.manager.dataTable) this.manager.dataTable.ajax.reload();
    }

    showError(msg) {
        if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: msg });
        else alert(msg);
    }

    escapeHtml(t) {
        if (t == null) return '';
        const d = document.createElement('div');
        d.textContent = t;
        return d.innerHTML;
    }
}

if (typeof window !== 'undefined') window.GRNListView = GRNListView;
