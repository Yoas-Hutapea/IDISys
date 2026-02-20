<div class="col-12">
    <div class="card">
        <div class="card-header" style="border-bottom: 1px solid #e9ecef;">
            <h6 class="card-title mb-0">Invoice Basic Information</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label form-label-required">Term Position</label>
                    <input type="text" class="form-control readonly-field" id="txtTermPosition" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label form-label-required">Invoice Number</label>
                    <input type="text" class="form-control" id="txtInvoiceNumber" maxlength="50" required>
                    <div class="invalid-feedback">Invoice Number is required.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label form-label-required">Invoice Date</label>
                    <input type="date" class="form-control" id="txtInvoiceDate" max="" required>
                    <div class="invalid-feedback">Invoice Date is required and cannot be greater than current date.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label form-label-required">Tax Code</label>
                    <select class="form-select" id="ddlTax" required>
                        <option value="">-- Select Tax Code --</option>
                    </select>
                    <div class="invalid-feedback">Tax Code is required.</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label form-label-required">Tax Number</label>
                    <input type="text" class="form-control" id="txtTaxNumber" maxlength="17" required>
                    <div class="invalid-feedback">Tax Number is required and must be 16 or 17 characters.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label form-label-required">Tax Date</label>
                    <input type="date" class="form-control" id="txtTaxDate" required>
                    <div class="invalid-feedback">Tax Date is required.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label form-label-required">PIC Name</label>
                    <input type="text" class="form-control" id="txtPICName" maxlength="100" required>
                    <div class="invalid-feedback">PIC Name is required.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label form-label-required">Email Address</label>
                    <input type="email" class="form-control" id="txtPICEmail" maxlength="255" required>
                    <div class="invalid-feedback">Email Address is required and must be a valid email format.</div>
                </div>
            </div>
            </div>
        </div>
    </div>
</div>
