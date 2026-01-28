@php
    $employee = session('employee');
    $user = auth()->user();
    $currentUserFullName = $employee?->name ?? $employee?->nick_name ?? $user?->Username ?? 'User';
@endphp

<div id="assign-approval" class="content" style="display: none;">
    <div class="content-header mb-4">
        <h6 class="mb-0">Assign Approval</h6>
        <small>Assign approval workflow for this procurement request.</small>
    </div>

    <form id="assignApprovalForm">
        <div class="row g-4">
            <div class="col-12">
                <label class="form-label" for="requestor">Requestor</label>
                <input type="text" class="form-control" id="requestor" name="requestor" value="{{ $currentUserFullName }}" disabled>
            </div>

            <div class="col-12">
                <label class="form-label" for="applicantApproval">Applicant</label>
                <input type="text" class="form-control" id="applicantApproval" name="applicantApproval" disabled>
            </div>

            <div class="col-12">
                <label class="form-label" for="reviewedBy">Reviewed by</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="reviewedBy" name="reviewedBy" placeholder="Select employee" readonly>
                    <input type="hidden" id="reviewedById" name="reviewedById">
                    <button class="btn btn-outline-primary" type="button" id="searchReviewedByBtn">
                        <i class="icon-base bx bx-search"></i>
                    </button>
                    <button class="btn btn-outline-info" type="button" id="viewReviewedByHistoryBtn" title="View History">
                        <i class="icon-base bx bx-history"></i>
                    </button>
                </div>
            </div>

            <div class="col-12">
                <label class="form-label" for="approvedBy">Approved by</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="approvedBy" name="approvedBy" placeholder="Select employee" readonly>
                    <input type="hidden" id="approvedById" name="approvedById">
                    <button class="btn btn-outline-primary" type="button" id="searchApprovedByBtn">
                        <i class="icon-base bx bx-search"></i>
                    </button>
                    <button class="btn btn-outline-info" type="button" id="viewApprovedByHistoryBtn" title="View History">
                        <i class="icon-base bx bx-history"></i>
                    </button>
                </div>
            </div>

            <div class="col-12">
                <label class="form-label" for="confirmedBy">Confirmed by</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="confirmedBy" name="confirmedBy" placeholder="Select employee" readonly>
                    <input type="hidden" id="confirmedById" name="confirmedById">
                    <button class="btn btn-outline-primary" type="button" id="searchConfirmedByBtn">
                        <i class="icon-base bx bx-search"></i>
                    </button>
                    <button class="btn btn-outline-info" type="button" id="viewConfirmedByHistoryBtn" title="View History">
                        <i class="icon-base bx bx-history"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="row mt-4">
            <div class="col-12 d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-label-secondary btn-prev">
                    <i class="icon-base bx bx-left-arrow-alt scaleX-n1-rtl icon-sm ms-sm-n2 me-sm-2"></i>
                    <span class="align-middle d-sm-inline-block d-none">Back</span>
                </button>
                <button type="button" class="btn btn-success" id="saveDraftApprovalBtn">
                    <i class="icon-base bx bx-save me-2"></i>
                    <span class="align-middle d-sm-inline-block d-none">Save Draft</span>
                </button>
                <button type="button" class="btn btn-outline-dark btn-next">
                    <span class="align-middle d-sm-inline-block d-none me-sm-2">Continue</span>
                    <i class="icon-base bx bx-right-arrow-alt scaleX-n1-rtl icon-sm me-sm-n2"></i>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Modal for Approval PIC History -->
<div class="modal fade" id="approvalPICHistoryModal" tabindex="-1" aria-labelledby="approvalPICHistoryModalLabel" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approvalPICHistoryModalLabel">
                    <i class="icon-base bx bx-history me-2"></i>
                    <span id="historyModalTitle">Approval PIC History</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover" id="approvalPICHistoryTable">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th class="text-center">Modified By</th>
                                <th class="text-center">Position</th>
                                <th class="text-center">PIC Review</th>
                                <th class="text-center">PIC Position</th>
                                <th class="text-center">Change Date</th>
                            </tr>
                        </thead>
                        <tbody id="approvalPICHistoryTableBody">
                            <tr>
                                <td colspan="6" class="text-center">
                                    <div class="spinner-border spinner-border-sm" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    Loading history...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div id="approvalPICHistoryEmpty" class="text-center text-muted" style="display: none;">
                    <i class="icon-base bx bx-info-circle fs-1"></i>
                    <p class="mt-2">No history found for this approval assignment.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
