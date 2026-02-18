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
            </div>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-secondary" onclick="grnManager.backToList()">
                            <i class="icon-base bx bx-arrow-back me-1"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-primary" onclick="grnManager.submitGRN()">
                            <i class="icon-base bx bx-check me-1"></i>Save Good Receive Note
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
