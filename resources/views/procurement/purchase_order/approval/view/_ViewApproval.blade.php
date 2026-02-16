<div id="viewApprovalContent" class="p-4">
    <div id="viewApprovalLoading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div class="mt-3">Loading Purchase Order details...</div>
    </div>

    <div id="viewApprovalData" style="display: none;">
        <form id="approvalPOForm">

            <div class="row g-4">
                @include('procurement.purchase_order.approval.view.partials._PurchaseInformationPartial')
                @include('procurement.purchase_order.approval.view.partials._AdditionalInformationPartial')
                @include('procurement.purchase_order.approval.view.partials._VendorInformationPartial')
                @include('procurement.purchase_order.approval.view.partials._DetailPurchaseRequestPartial')
                @include('procurement.purchase_order.approval.view.partials._ApprovalAssignmentPartial')
                @include('procurement.purchase_order.approval.view.partials._SupportingDocumentsPartial')

                <div class="col-12">
                    <div class="card">
                        <div class="card-header" style="border-bottom: 1px solid #e9ecef;">
                            <h6 class="card-title mb-0">Approval</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Decision <span class="text-danger">*</span></label>
                                    <select class="form-select" id="approval-decision" required>
                                        <option value="" disabled selected>Select Decision</option>
                                        <option value="Approve">Approve</option>
                                        <option value="Reject">Reject</option>
                                    </select>
                                    <div class="invalid-feedback" id="approval-decision-error" style="display: none;">
                                        Please fill this information
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Remarks <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="approval-remarks" rows="4" placeholder="Enter remarks" required></textarea>
                                    <div class="invalid-feedback" id="approval-remarks-error" style="display: none;">
                                        Please fill this information
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-secondary" onclick="approvalPOManager.backToList()">
                            <i class="icon-base bx bx-arrow-back me-1"></i>
                            Cancel
                        </button>
                        <button type="button" class="btn btn-primary" onclick="approvalPOManager.submitApproval()">
                            <i class="icon-base bx bx-check me-1"></i>
                            Submit Approval
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
