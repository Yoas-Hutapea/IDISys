<div id="summary" class="content" style="display: none;">
    <div class="content-header mb-4">
        <h6 class="mb-0">Review & Submit</h6>
        <small>Review all information before submitting the procurement request.</small>
    </div>
    <div class="row g-4">
        <!-- Basic Information Summary -->
        <div class="col-12">
            <div class="card">
                <div class="card-header" style="border-bottom: 1px solid #e9ecef;">
                    <h6 class="card-title mb-0">Purchase Information</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Requestor</label>
                            <input type="text" class="form-control" id="review-requestor" value="Jody Frans Ebeneazer Rumahorbo" disabled>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Purchase Request Type</label>
                            <input type="text" class="form-control" id="review-purchase-request-type" value="General" disabled>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Applicant</label>
                            <input type="text" class="form-control" id="review-applicant" value="Yahman" disabled>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Purchase Request Sub Type</label>
                            <input type="text" class="form-control" id="review-purchase-request-sub-type" value="General" disabled>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Company</label>
                            <input type="text" class="form-control" id="review-company" value="Information Technology" disabled>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Purchase Request Name</label>
                            <input type="text" class="form-control" id="review-purchase-request-name" value="-" disabled>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Remarks</label>
                            <textarea class="form-control" id="review-remarks" rows="3" disabled>-</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Information Summary -->
        <div class="col-12" id="review-additional-section" style="display: none;">
            <div class="card">
                <div class="card-header" style="border-bottom: 1px solid #e9ecef;">
                    <h6 class="card-title mb-0">Additional Information</h6>
                </div>
                <div class="card-body" id="review-additional-body">
                    <!-- Content will be dynamically populated based on active Additional section -->
                </div>
            </div>
        </div>

        <!-- Item Details Summary -->
        <div class="col-12">
            <div class="card">
                <div class="card-header" style="border-bottom: 1px solid #e9ecef;">
                    <h6 class="card-title mb-0">Detail Purchase Request</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Item ID</th>
                                    <th>Item Name</th>
                                    <th>Description</th>
                                    <th>UoM</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Items will be dynamically populated from Detail table -->
                            </tbody>
                        </table>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12 text-end">
                            <label class="form-label fw-semibold me-2">Amount Total:</label>
                            <input type="text" class="form-control d-inline-block" id="review-amount-total" value="30,500,000" disabled style="width: 200px;">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approval Assignment Summary -->
        <div class="col-12">
            <div class="card">
                <div class="card-header" style="border-bottom: 1px solid #e9ecef;">
                    <h6 class="card-title mb-0">Approval Assignment</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Requestor</label>
                            <input type="text" class="form-control" id="review-approval-requestor" value="Jody Frans Ebeneazer Rumahorbo" disabled>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Reviewed by</label>
                            <input type="text" class="form-control" id="review-reviewed-by" value="Yahman" disabled>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Applicant</label>
                            <input type="text" class="form-control" id="review-approval-applicant" value="Ahmad Sujai" disabled>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Approved by</label>
                            <input type="text" class="form-control" id="review-approved-by" value="Yulia Verawati Setiawan" disabled>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Confirmed by</label>
                            <input type="text" class="form-control" id="review-confirmed-by" value="Saleh" disabled>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Supporting Documents Summary -->
        <div class="col-12">
            <div class="card">
                <div class="card-header" style="border-bottom: 1px solid #e9ecef;">
                    <h6 class="card-title mb-0">Supporting Documents</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>File Name</th>
                                    <th>File Size (kb)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Documents will be dynamically populated from Document table -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmation and Navigation -->
        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="confirmSubmission" name="confirmSubmission">
                <label class="form-check-label" for="confirmSubmission">
                    I confirm that all information provided is accurate and complete.
                </label>
            </div>
        </div>
        <div class="col-12 d-flex justify-content-center gap-2">
            <button class="btn btn-label-secondary btn-prev">
                <i class="icon-base bx bx-left-arrow-alt scaleX-n1-rtl icon-sm ms-sm-n2 me-sm-2"></i>
                <span class="align-middle d-sm-inline-block d-none">Back</span>
            </button>
            <button class="btn btn-primary" id="submitPRBtn" disabled>
                <i class="icon-base bx bx-check me-2"></i>
                <span class="align-middle d-sm-inline-block d-none">Submit PR</span>
            </button>
        </div>
    </div>
</div>
