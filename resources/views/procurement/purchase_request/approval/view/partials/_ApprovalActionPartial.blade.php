{{--
    Approval Action Partial for Purchase Request Approval
    Shows approval/rejection form with remarks
--}}

<div class="col-12" id="approvalActionSection">
    <div class="card">
        <div class="card-header" style="border-bottom: 1px solid #e9ecef;">
            <h6 class="card-title mb-0">Approval Action</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Action<span class="text-danger">*</span></label>
                    <select class="form-select" id="approvalAction" name="approvalAction">
                        <option value="" disabled selected>Select Action</option>
                        <option value="1">Approve</option>
                        <option value="5">Reject</option>
                    </select>
                    <div id="approvalActionError" class="text-danger mt-1" style="display: none;"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 mb-3">
                    <label class="form-label fw-semibold">Remarks <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="approvalRemarks" name="approvalRemarks" rows="4" placeholder="Enter remarks for approval/rejection"></textarea>
                    <div id="approvalRemarksError" class="text-danger mt-1" style="display: none;"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-center">
                        <button class="btn btn-primary" id="submitApprovalBtn" onclick="submitApproval()">
                            <i class="icon-base bx bx-check me-2"></i>
                            <span class="align-middle d-sm-inline-block d-none">Submit Approval</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


