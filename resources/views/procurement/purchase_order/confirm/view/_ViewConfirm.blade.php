<div id="viewConfirmContent" class="p-4">
    <div id="viewConfirmLoading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div class="mt-3">Loading Purchase Order details...</div>
    </div>

    <div id="viewConfirmData" style="display: none;">
        <form id="confirmPOForm">
            <div class="mb-4">
                <h4 class="fw-semibold">Detail Purchase Order <span id="confirm-po-number-display">[Nomor PO]</span></h4>
            </div>

            <div class="row g-4">
                @include('procurement.purchase_order.confirm.view.partials._PurchaseInformationPartial')
                @include('procurement.purchase_order.confirm.view.partials._AdditionalInformationPartial')
                @include('procurement.purchase_order.confirm.view.partials._DetailPurchaseOrderPartial')
                @include('procurement.purchase_order.confirm.view.partials._ApprovalAssignmentPartial')
                @include('procurement.purchase_order.confirm.view.partials._VendorInformationPartial')
                @include('procurement.purchase_order.confirm.view.partials._SupportingDocumentsPartial')
                @include('procurement.purchase_order.confirm.view.partials._ConfirmActionPartial')
            </div>

            <div class="d-flex justify-content-center gap-2 mt-4 mb-4">
                <button type="button" class="btn btn-label-secondary" onclick="confirmPOManager.backToList()">
                    <i class="icon-base bx bx-arrow-back me-1"></i>
                    Back
                </button>
                <button type="button" class="btn btn-primary" onclick="confirmPOManager.submitConfirm()">
                    <i class="icon-base bx bx-check me-1"></i>
                    Confirm PO
                </button>
            </div>
        </form>
    </div>
</div>
