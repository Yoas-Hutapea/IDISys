<div class="col-12">
    <div class="card">
        <div class="card-header" style="border-bottom: 1px solid #e9ecef;">
            <h6 class="card-title mb-0">Purchase Order Information</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Purchase Order Number <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="txtPurchOrderID" placeholder="Select Purchase Order Number" readonly>
                        <button class="btn btn-primary" type="button" id="btnSearchPO" title="Click to search PO Number">
                            <i class="icon-base bx bx-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">PO Date</label>
                    <input type="text" class="form-control" id="txtPODate" readonly disabled>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Purchase Order Name</label>
                    <input type="text" class="form-control" id="txtPurchOrderName" readonly disabled>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">PO Author</label>
                    <input type="text" class="form-control" id="txtPOAuthor" readonly disabled>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Purchase Type</label>
                    <input type="text" class="form-control" id="txtPurchType" readonly disabled>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">PO Amount</label>
                    <input type="text" class="form-control" id="txtPOAmount" readonly disabled>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Purchase Sub Type</label>
                    <input type="text" class="form-control" id="txtPurchSubType" readonly disabled>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">PR Number</label>
                    <input type="text" class="form-control" id="txtPRNumber" readonly disabled>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Company</label>
                    <input type="text" class="form-control" id="txtCompany" readonly disabled>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">PR Requestor</label>
                    <input type="text" class="form-control" id="txtPRRequestor" readonly disabled>
                </div>
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">PR Date</label>
                    <input type="text" class="form-control" id="txtPRDate" readonly disabled>
                </div>
                {{-- Hidden for save: Site ID and Sonumb (filled from Additional/STIP when applicable) --}}
                <input type="hidden" id="txtSiteID" value="">
                <input type="hidden" id="txtSONumber" value="">
            </div>
        </div>
    </div>
</div>
