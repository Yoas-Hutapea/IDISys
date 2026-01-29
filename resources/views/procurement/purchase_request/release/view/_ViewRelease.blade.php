{{--
    View Release for Purchase Request Release
    Shows PR summary with release form
--}}

<div id="viewReleaseContent" class="p-4">
    <!-- Loading state -->
    <div id="viewReleaseLoading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div class="mt-3">Loading Purchase Request details...</div>
    </div>

    <!-- Content will be dynamically populated -->
    <div id="viewReleaseData" style="display: none;">
        <div class="row g-4">
            <!-- Purchase Information Section -->
            @include('procurement.purchase_request.release.view.partials._PurchaseInformationPartial')

            <!-- Additional Information Section -->
            @include('procurement.purchase_request.release.view.partials._AdditionalInformationPartial')

            <!-- Detail Purchase Request Section -->
            @include('procurement.purchase_request.release.view.partials._DetailPurchaseRequestPartial')

            <!-- Approval Assignment Section -->
            @include('procurement.purchase_request.release.view.partials._ApprovalAssignmentPartial')

            <!-- Supporting Documents Section -->
            @include('procurement.purchase_request.release.view.partials._SupportingDocumentsPartial')

            <!-- Approval Log Section -->
            @include('procurement.purchase_request.release.view.partials._ApprovalLogPartial')

            <!-- Release Action Section -->
            @include('procurement.purchase_request.release.view.partials._ReleaseActionPartial')
        </div>
    </div>
</div>


