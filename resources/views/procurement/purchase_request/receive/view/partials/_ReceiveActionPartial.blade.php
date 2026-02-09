{{--
    Receive Action Partial for Receive View
    Shows receive/rejection form with remarks
--}}

<div class="col-12" id="receiveActionSection">
    <div class="card">
        <div class="card-header" style="border-bottom: 1px solid #e9ecef;">
            <h6 class="card-title mb-0">Receive Action</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Decision<span class="text-danger">*</span></label>
                    <select class="form-select" id="receiveDecision" name="receiveDecision">
                        <option value="" disabled selected>Select Decision</option>
                        <option value="Receive">Approve</option>
                        <option value="Reject">Reject</option>
                    </select>
                    <div id="receiveDecisionError" class="text-danger mt-1" style="display: none;"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 mb-3">
                    <label class="form-label fw-semibold">Remarks <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="receiveRemarks" name="receiveRemarks" rows="4" placeholder="Enter remarks for receive"></textarea>
                    <div id="receiveRemarksError" class="text-danger mt-1" style="display: none;"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-center">
                        <button class="btn btn-primary" id="submitReceiveBtn" onclick="submitReceive()">
                            <i class="icon-base bx bx-check me-2"></i>
                            <span class="align-middle d-sm-inline-block d-none">Submit Receive</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


