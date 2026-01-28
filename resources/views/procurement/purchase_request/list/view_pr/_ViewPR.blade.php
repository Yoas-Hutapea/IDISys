{{-- View PR for Purchase Request List --}}
<div id="viewPRContent" class="p-4">
    <div id="viewPRLoading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div class="mt-3">Loading Purchase Request details...</div>
    </div>

    <div id="viewPRData" style="display: none;">
        <div class="row g-4">
            @include('procurement.purchase_request.list.view_pr.partials._PurchaseInformationPartial')
            @include('procurement.purchase_request.list.view_pr.partials._AdditionalInformationPartial')
            @include('procurement.purchase_request.list.view_pr.partials._DetailPurchaseRequestPartial')
            @include('procurement.purchase_request.list.view_pr.partials._ApprovalAssignmentPartial')
            @include('procurement.purchase_request.list.view_pr.partials._SupportingDocumentsPartial')
        </div>
    </div>
</div>
