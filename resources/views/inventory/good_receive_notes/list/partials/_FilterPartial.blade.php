<form id="filterForm" class="dark-mode-compatible">
    <div class="row g-3">
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">GR Number</label>
                <input type="text" class="form-control bg-body text-body" id="grNumber" name="grNumber" placeholder="GR Number">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">PO Number</label>
                <input type="text" class="form-control bg-body text-body" id="poNumber" name="poNumber" placeholder="PO Number">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Created By</label>
                <input type="text" class="form-control bg-body text-body" id="createdBy" name="createdBy" placeholder="Created By">
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Created Date</label>
                <div class="row g-2">
                    <div class="col-6">
                        <input type="date" class="form-control bg-body text-body" id="createdStartDate" name="createdStartDate" placeholder="Start Date">
                    </div>
                    <div class="col-1 d-flex align-items-center justify-content-center"><span class="text-muted">to</span></div>
                    <div class="col-5">
                        <input type="date" class="form-control bg-body text-body" id="createdEndDate" name="createdEndDate" placeholder="End Date">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-12">
            <div class="d-flex justify-content-center gap-3">
                <button type="button" class="btn btn-primary" onclick="grnManager.search()">
                    <i class="icon-base bx bx-search me-2"></i>Search
                </button>
                <button type="button" class="btn btn-secondary" onclick="grnManager.resetFilter()">
                    <i class="icon-base bx bx-refresh me-2"></i>Reset
                </button>
            </div>
        </div>
    </div>
</form>
