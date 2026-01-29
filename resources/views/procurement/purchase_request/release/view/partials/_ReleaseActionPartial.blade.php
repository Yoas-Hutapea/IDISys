{{--
    Release Action Partial for Release View
    Shows release form with submit button
--}}

<div class="col-12" id="releaseActionSection">
    @include('procurement.purchase_request.release.view.partials._SingleReleasePageFormPartial')
    
    <div class="row mt-3">
        <div class="col-12">
            <div class="d-flex justify-content-center">
                <button class="btn btn-primary" id="submitReleaseBtn" onclick="releaseListManager.submitRelease()">
                    <i class="icon-base bx bx-check me-2"></i>
                    <span class="align-middle d-sm-inline-block d-none">Submit Release</span>
                </button>
            </div>
        </div>
    </div>
</div>


