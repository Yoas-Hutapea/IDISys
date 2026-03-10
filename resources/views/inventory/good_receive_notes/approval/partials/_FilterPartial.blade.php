<form id="grnApprovalFilterForm" class="dark-mode-compatible">
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label fw-semibold text-body">GR Number</label>
            <input type="text" class="form-control bg-body text-body" id="grNumber" name="grNumber" placeholder="GR Number">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold text-body">PO Number</label>
            <input type="text" class="form-control bg-body text-body" id="poNumber" name="poNumber" placeholder="PO Number">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold text-body">Status</label>
            <select class="form-select bg-body text-body" id="approvalStatus" name="approvalStatus">
                <option value="" selected>All</option>
                <option value="waiting">Waiting Approval</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold text-body">Created Date</label>
            <div class="row g-2">
                <div class="col-6">
                    <input type="date" class="form-control bg-body text-body" id="createdStartDate" name="createdStartDate">
                </div>
                <div class="col-6">
                    <input type="date" class="form-control bg-body text-body" id="createdEndDate" name="createdEndDate">
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-12">
            <div class="d-flex justify-content-center gap-3">
                <button type="button" class="btn btn-primary" onclick="grnApprovalManager.search()">
                    <i class="icon-base bx bx-search me-2"></i>Search
                </button>
                <button type="button" class="btn btn-secondary" onclick="grnApprovalManager.resetFilter()">
                    <i class="icon-base bx bx-refresh me-2"></i>Reset
                </button>
            </div>
        </div>
    </div>
</form>
