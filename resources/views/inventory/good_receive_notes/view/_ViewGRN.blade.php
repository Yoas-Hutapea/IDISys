@php
    $grnViewMode = $grnViewMode ?? 'default';
    $isApprovalMode = $grnViewMode === 'approval';
@endphp

<div id="viewGRNContent" class="p-4">
    <div id="viewGRNLoading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="mt-3">Loading Purchase Order details...</div>
    </div>

    <div id="viewGRNData" style="display: none;">
        <form id="grnForm">
            <div class="row g-4">
                @include('inventory.good_receive_notes.view.partials._PurchaseInformationPartial')
                @include('inventory.good_receive_notes.view.partials._AdditionalInformationPartial')
                @include('inventory.good_receive_notes.view.partials._VendorInformationPartial')
                @include('inventory.good_receive_notes.view.partials._DetailPurchaseRequestPartial')
                @if (!$isApprovalMode)
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">Supporting Document</h6>
                                    <small class="text-muted">Manage and upload supporting documents for this GR.</small>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" id="grnAddDocumentBtn">
                                    <i class="icon-base bx bx-plus me-1"></i>Add Document
                                </button>
                            </div>
                            <div class="card-body">
                                <input type="file" id="grn-supporting-documents" class="d-none" multiple>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover" id="grnDocumentTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="120">Action</th>
                                                <th>File Name</th>
                                                <th>File Size (kb)</th>
                                            </tr>
                                        </thead>
                                        <tbody id="grnDocumentTableBody">
                                            <tr id="grnDocumentEmptyRow">
                                                <td colspan="3" class="text-center text-muted py-3">No documents selected</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    @include('inventory.good_receive_notes.approval.view.partials._SupportingDocumentsPartial')
                    @include('inventory.good_receive_notes.approval.view.partials._ApprovalActionPartial')
                @endif
            </div>
            @if (!$isApprovalMode)
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-center gap-2">
                            <button type="button" class="btn btn-secondary" onclick="grnManager.backToList()">
                                <i class="icon-base bx bx-arrow-back me-1"></i>Cancel
                            </button>
                            <button type="button" class="btn btn-outline-primary" onclick="grnManager.saveGRN()">
                                <i class="icon-base bx bx-check me-1"></i>Save Good Receive Note
                            </button>
                            <button type="button" class="btn btn-primary" onclick="grnManager.submitFinalGRN()">
                                <i class="icon-base bx bx-send me-1"></i>Submit Good Receive Note
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </form>
    </div>
</div>
