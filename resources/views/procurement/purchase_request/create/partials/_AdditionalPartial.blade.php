<div id="additional" class="content" style="display: none;">
    <div class="content-header mb-4">
        <h6 class="mb-0">
            <i class="icon-base bx bx-grid-alt me-2"></i>Additional
        </h6>
        <small id="additionalDescription">Enter additional information.</small>
    </div>
    <div class="row g-6">
        <!-- Section for Type ID 6 with Sub Type 2: Billing Type -->
        <div class="col-12" id="billingTypeSection" style="display: none;">
            <label class="form-label" for="BillingType">Billing Type <span class="text-danger">*</span></label>
            <div class="dropdown" id="billingTypeDropdownContainer">
                <button class="form-select text-start" type="button" id="billingTypeDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <span id="billingTypeSelectedText">Select Billing Type</span>
                </button>
                <input type="hidden" id="BillingType" name="BillingType" required>
                <ul class="dropdown-menu w-100" id="billingTypeDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                    <li>
                        <div class="px-3 py-2 text-muted text-center">Loading billing types...</div>
                    </li>
                </ul>
            </div>
        </div>
        <div class="col-12" id="billingPeriodContainer" style="display: none;">
            <div class="border border-danger border-dashed rounded p-3">
                <label class="form-label text-danger fw-semibold mb-3" id="billingPeriodLabel">Monthly</label>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="StartPeriod">Start Period <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="date" id="StartPeriod" name="StartPeriod" class="form-control" placeholder="Start Date Period" required>
                            <span class="input-group-text">
                                <i class="icon-base bx bx-calendar"></i>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="Period">Period <span class="text-danger">*</span></label>
                        <input type="number" id="Period" name="Period" class="form-control" placeholder="Enter Period" min="1" required>
                        <small class="text-muted">Number of billing periods</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="EndPeriod">End Period</label>
                        <div class="input-group">
                            <input type="date" id="EndPeriod" name="EndPeriod" class="form-control" placeholder="End Date Period" readonly>
                            <span class="input-group-text">
                                <i class="icon-base bx bx-calendar"></i>
                            </span>
                        </div>
                        <small class="text-muted">Auto-calculated based on Start Period, Period, and Billing Type</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section for Type ID 4 or 8: Sonumb and Site Name -->
        <div class="col-12" id="sonumbSection" style="display: none;">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="Sonumb">Sonumb</label>
                    <div class="input-group">
                        <input type="text" id="Sonumb" name="Sonumb" class="form-control" placeholder="Choose Sonumb">
                        <button type="button" class="btn btn-outline-primary" id="searchSTIPSonumbBtn">
                            <i class="icon-base bx bx-search"></i>
                        </button>
                    </div>
                    <input type="hidden" id="SonumbId" name="SonumbId">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="SiteName">Site Name</label>
                    <input type="text" id="SiteName" name="SiteName" class="form-control" placeholder="Site Name - Site ID" readonly>
                    <input type="hidden" id="SiteID" name="SiteID">
                </div>
            </div>
        </div>

        <!-- Section for Type ID 7: Sonumb, Site Name, Billing Type, and Period with Year/Month/Date -->
        <div class="col-12" id="subscribeSection" style="display: none;">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="SubscribeSonumb">Sonumb</label>
                    <div class="input-group">
                        <input type="text" id="SubscribeSonumb" name="SubscribeSonumb" class="form-control" placeholder="Choose Sonumb">
                        <button type="button" class="btn btn-outline-primary" id="searchSubscribeSonumbBtn">
                            <i class="icon-base bx bx-search"></i>
                        </button>
                    </div>
                    <input type="hidden" id="SubscribeSonumbId" name="SubscribeSonumbId">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="SubscribeSiteName">Site Name</label>
                    <input type="text" id="SubscribeSiteName" name="SubscribeSiteName" class="form-control" placeholder="Site Name - Site ID" readonly>
                    <input type="hidden" id="SubscribeSiteID" name="SubscribeSiteID">
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-12">
                    <label class="form-label" for="SubscribeBillingType">Billing Type <span class="text-danger">*</span></label>
                    <div class="dropdown" id="subscribeBillingTypeDropdownContainer">
                        <button class="form-select text-start" type="button" id="subscribeBillingTypeDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                            <span id="subscribeBillingTypeSelectedText">Select Billing Type</span>
                        </button>
                        <input type="hidden" id="SubscribeBillingType" name="SubscribeBillingType" required>
                        <ul class="dropdown-menu w-100" id="subscribeBillingTypeDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                            <li>
                                <div class="px-3 py-2 text-muted text-center">Loading billing types...</div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-3" id="subscribePeriodContainer" style="display: none;">
                <div class="border border-danger border-dashed rounded p-3">
                    <label class="form-label text-danger fw-semibold mb-3" id="subscribePeriodLabel">Monthly</label>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="SubscribeStartPeriod">Start Period <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="date" id="SubscribeStartPeriod" name="SubscribeStartPeriod" class="form-control" placeholder="Start Date Period" required>
                                <span class="input-group-text">
                                    <i class="icon-base bx bx-calendar"></i>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="SubscribePeriod">Period <span class="text-danger">*</span></label>
                            <input type="number" id="SubscribePeriod" name="SubscribePeriod" class="form-control" placeholder="Enter Period" min="1" required>
                            <small class="text-muted">Number of billing periods</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="SubscribeEndPeriod">End Period</label>
                            <div class="input-group">
                                <input type="date" id="SubscribeEndPeriod" name="SubscribeEndPeriod" class="form-control" placeholder="End Date Period" readonly>
                                <span class="input-group-text">
                                    <i class="icon-base bx bx-calendar"></i>
                                </span>
                            </div>
                            <small class="text-muted">Auto-calculated based on Start Period, Period, and Billing Type</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 d-flex justify-content-center gap-2">
            <button class="btn btn-label-secondary btn-prev">
                <i class="icon-base bx bx-left-arrow-alt scaleX-n1-rtl icon-sm ms-sm-n2 me-sm-2"></i>
                <span class="align-middle d-sm-inline-block d-none">Back</span>
            </button>
            <button type="button" class="btn btn-success" id="saveDraftAdditionalBtn">
                <i class="icon-base bx bx-save me-2"></i>
                <span class="align-middle d-sm-inline-block d-none">Save Draft</span>
            </button>
            <button class="btn btn-outline-dark btn-next">
                <span class="align-middle d-sm-inline-block d-none me-sm-2">Continue</span>
                <i class="icon-base bx bx-right-arrow-alt scaleX-n1-rtl icon-sm me-sm-n2"></i>
            </button>
        </div>
    </div>
</div>

<!-- Modal for Choose Site -->
<div class="modal fade" id="chooseSiteModal" tabindex="-1" aria-labelledby="chooseSiteModalLabel" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="chooseSiteModalLabel">
                    <i class="icon-base bx bx-grid-alt me-2"></i>Choose Site
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Rows per page -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <label class="form-label me-2 mb-0">Rows per page:</label>
                            <select class="form-select form-select-sm" style="width: auto;" id="siteRowsPerPage">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Search filters -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control form-control-sm" id="searchSonumb" placeholder="Search Sonumb...">
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control form-control-sm" id="searchSiteID" placeholder="Search Site ID...">
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control form-control-sm" id="searchSiteName" placeholder="Search Site Name...">
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control form-control-sm" id="searchOperator" placeholder="Search Operator...">
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover" id="chooseSiteTable">
                        <thead>
                            <tr>
                                <th class="text-center" width="100">Action</th>
                                <th class="text-center">Sonumb</th>
                                <th class="text-center">Site ID</th>
                                <th class="text-center">Site Name</th>
                                <th class="text-center">Operator</th>
                            </tr>
                        </thead>
                        <tbody id="chooseSiteTableBody">
                            <tr>
                                <td colspan="5" class="text-center">
                                    <div class="spinner-border spinner-border-sm" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    Loading sites...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div id="sitePaginationInfo" class="text-muted"></div>
                    </div>
                    <div class="col-md-6">
                        <nav aria-label="Site pagination">
                            <ul class="pagination justify-content-end mb-0" id="sitePagination">
                            </ul>
                        </nav>
                    </div>
                </div>

                <div id="chooseSiteEmpty" class="text-center text-muted" style="display: none;">
                    <i class="icon-base bx bx-info-circle fs-1"></i>
                    <p class="mt-2">No sites found.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
