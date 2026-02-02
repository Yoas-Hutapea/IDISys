<div class="col-12" id="postingActionSection">
    <div class="card">
        <div class="card-header" style="border-bottom: 1px solid #e9ecef;">
            <h6 class="card-title mb-0">Posting Action</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Posting Decision<span class="text-danger">*</span></label>
                    <select class="form-select" id="postingDecision" name="postingDecision">
                        <option value="" disabled selected>Select Decision</option>
                        <option value="Post">Approve</option>
                        <option value="Reject">Reject</option>
                    </select>
                    <div id="postingDecisionError" class="text-danger mt-1" style="display: none;"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 mb-3">
                    <label class="form-label fw-semibold">Remarks <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="postingRemarks" name="postingRemarks" rows="4" placeholder="Enter remarks for posting"></textarea>
                    <div id="postingRemarksError" class="text-danger mt-1" style="display: none;"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-center">
                        <button class="btn btn-primary" id="submitPostingBtn" onclick="submitPosting()">
                            <i class="icon-base bx bx-check me-2"></i>
                            Submit
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
