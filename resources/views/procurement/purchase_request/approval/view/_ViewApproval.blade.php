{{--
    View Approval for Purchase Request Approval
    Shows PR summary with remarks and approve/reject dropdown
--}}

<div id="viewApprovalContent" class="p-4">
    <!-- Loading state -->
    <div id="viewApprovalLoading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div class="mt-3">Loading Purchase Request details...</div>
    </div>

    <!-- Content will be dynamically populated -->
    <div id="viewApprovalData" style="display: none;">
        <div class="row g-4">
            <!-- Purchase Information Section -->
            @include('procurement.purchase_request.approval.view.partials._PurchaseInformationPartial')

            <!-- Additional Information Section -->
            @include('procurement.purchase_request.approval.view.partials._AdditionalInformationPartial')

            <!-- Detail Purchase Request Section -->
            @include('procurement.purchase_request.approval.view.partials._DetailPurchaseRequestPartial')

            <!-- Approval Assignment Section -->
            @include('procurement.purchase_request.approval.view.partials._ApprovalAssignmentPartial')

            <!-- Supporting Documents Section -->
            @include('procurement.purchase_request.approval.view.partials._SupportingDocumentsPartial')

            <!-- Approval Action Section -->
            @include('procurement.purchase_request.approval.view.partials._ApprovalActionPartial')
        </div>
    </div>
</div>


