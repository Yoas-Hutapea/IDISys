<div class="col-12">
    <div class="card">
        <div class="card-header" style="border-bottom: 1px solid #e9ecef;">
            <h6 class="card-title mb-0">Purchase Order Information</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label form-label-required">Purchase Order Number</label>
                    <div class="input-group">
                        <input type="text" class="form-control readonly-field" id="txtPurchOrderID" placeholder="Select Purchase Order Number" readonly>
                        <button class="btn btn-primary" type="button" id="btnSearchPO" title="Click to search PO Number">
                            <i class="icon-base bx bx-search"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Purchase Order Name</label>
                    <textarea class="form-control readonly-field" id="txtPurchOrderName" rows="3" readonly></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">PO Date</label>
                    <input type="text" class="form-control readonly-field" id="txtPurchDate" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Amount PO</label>
                    <input type="text" class="form-control readonly-field" id="txtPOAmount" readonly>
                </div>
                <div class="mb-3" id="siteFieldWrapper">
                    <label class="form-label">Site</label>
                    <input type="text" class="form-control readonly-field" id="txtSite" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status PO</label>
                    <input type="text" class="form-control readonly-field" id="txtStatusPO" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Purchase Type</label>
                    <input type="text" class="form-control readonly-field" id="txtPurchType" readonly>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Purchase Sub Type</label>
                    <input type="text" class="form-control readonly-field" id="txtPurchSubType" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Term of Payment</label>
                    <input type="text" class="form-control readonly-field" id="txtTermOfPayment" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Company</label>
                    <input type="text" class="form-control readonly-field" id="txtCompany" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Work Type</label>
                    <input type="text" class="form-control readonly-field" id="txtWorkType" readonly>
                </div>
                <div class="mb-3" id="productTypeFieldWrapper">
                    <label class="form-label">Product Type</label>
                    <input type="text" class="form-control readonly-field" id="txtProductType" readonly>
                </div>
                <div class="mb-3" id="sonumbFieldWrapper">
                    <label class="form-label">Sonumb</label>
                    <input type="text" class="form-control readonly-field" id="txtSONumber" readonly>
                </div>
            </div>
        </div>
        </div>
    </div>
</div>
