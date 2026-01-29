{{--
    Bulky Release Page for Purchase Request Release
    Contains form for bulk release and list of PRs with checkboxes (status 7)
--}}

<div class="p-4">
    <div class="row g-4">

        <!-- Filter Section -->
        <div class="col-12">
            <div class="card">
                <div class="card-header" style="background-color: #2352a1; color: white; cursor: pointer; padding: 0.75rem 1rem;" onclick="toggleBulkyReleaseFilter()" role="button" tabindex="0" aria-expanded="false" aria-controls="bulkyReleaseFilterContent">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0" style="color: white !important;">
                            <i class="icon-base bx bx-filter me-2" style="color: white !important;" aria-hidden="true"></i>
                            <span style="color: white !important;">Filter</span>
                        </h6>
                        <i class="icon-base bx bx-chevron-down" id="bulkyReleaseFilterChevron" style="color: white !important;" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="card-body" id="bulkyReleaseFilterContent" style="display: none;" role="region">
                    @include('procurement.purchase_request.release.bulky_release._BulkyReleaseFilterPartial')
                </div>
            </div>
        </div>

        <!-- List Purchase Request Section -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">List Purchase Request</h6>
                </div>
                <div class="card-body">
                    @include('procurement.purchase_request.release.bulky_release._BulkyReleaseListPartial')
                </div>
            </div>
        </div>

        <!-- Action Form Section -->
        <div class="col-12">
            @include('procurement.purchase_request.release.bulky_release._BulkyReleaseActionPartial')
        </div>

        <!-- Action Buttons Section -->
        <div class="col-12">
            <div class="d-flex justify-content-center gap-3">
                <button class="btn btn-primary" id="submitBulkyReleaseBtn" onclick="releaseListManager.submitBulkyRelease()">
                    <i class="icon-base bx bx-check me-2"></i>
                    <span class="align-middle d-sm-inline-block d-none">Submit Release</span>
                </button>
            </div>
        </div>
    </div>
</div>



