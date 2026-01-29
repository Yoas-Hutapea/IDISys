{{--
    View Receive for Purchase Request Receive
    Shows PR summary with remarks and receive dropdown
--}}

<div id="viewReceiveContent" class="p-4">
    <!-- Loading state -->
    <div id="viewReceiveLoading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div class="mt-3">Loading Purchase Request details...</div>
    </div>

    <!-- Content will be dynamically populated -->
    <div id="viewReceiveData" style="display: none;">
        <div class="row g-4">
            <!-- Purchase Information Section -->
            @include('procurement.purchase_request.receive.view.partials._PurchaseInformationPartial')

            <!-- Additional Information Section -->
            @include('procurement.purchase_request.receive.view.partials._AdditionalInformationPartial')

            <!-- Detail Purchase Request Section -->
            @include('procurement.purchase_request.receive.view.partials._DetailPurchaseRequestPartial')

            <!-- Approval Assignment Section -->
            @include('procurement.purchase_request.receive.view.partials._ApprovalAssignmentPartial')

            <!-- Supporting Documents Section -->
            @include('procurement.purchase_request.receive.view.partials._SupportingDocumentsPartial')

            <!-- Receive Action Section -->
            @include('procurement.purchase_request.receive.view.partials._ReceiveActionPartial')
        </div>
    </div>
</div>


