<form id="filterForm" class="dark-mode-compatible">
    <div class="row g-3">
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">PO Period</label>
                <div class="row g-2">
                    <div class="col-6">
                        <input type="date" class="form-control bg-body text-body" id="poStartDate" name="poStartDate" placeholder="Start Date">
                    </div>
                    <div class="col-1 d-flex align-items-center justify-content-center"><span class="text-muted">to</span></div>
                    <div class="col-5">
                        <input type="date" class="form-control bg-body text-body" id="poEndDate" name="poEndDate" placeholder="End Date">
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Purchase Order Number</label>
                <input type="text" class="form-control bg-body text-body" id="poNumber" name="poNumber" placeholder="PO Number">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Purchase Type</label>
                <div class="dropdown" id="purchTypeDropdownContainer">
                    <button class="form-select text-start bg-body text-body" type="button" id="purchTypeDropdownBtn" data-bs-toggle="dropdown">
                        <span id="purchTypeSelectedText">Select Purchase Type</span>
                    </button>
                    <input type="hidden" id="purchType" name="purchType">
                    <ul class="dropdown-menu w-100" id="purchTypeDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                        <li class="px-3 py-2"><input type="text" class="form-control form-control-sm" id="purchTypeSearchInput" placeholder="Search..." autocomplete="off"></li>
                        <li><hr class="dropdown-divider"></li>
                        <li id="purchTypeDropdownItems"><div class="px-3 py-2 text-muted text-center">Loading...</div></li>
                    </ul>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Purchase Sub Type</label>
                <div class="dropdown" id="purchSubTypeDropdownContainer">
                    <button class="form-select text-start bg-body text-body" type="button" id="purchSubTypeDropdownBtn" data-bs-toggle="dropdown">
                        <span id="purchSubTypeSelectedText">Select Purchase Sub Type</span>
                    </button>
                    <input type="hidden" id="purchSubType" name="purchSubType">
                    <ul class="dropdown-menu w-100" id="purchSubTypeDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                        <li class="px-3 py-2"><input type="text" class="form-control form-control-sm" id="purchSubTypeSearchInput" placeholder="Search..." autocomplete="off"></li>
                        <li><hr class="dropdown-divider"></li>
                        <li id="purchSubTypeDropdownItems"><div class="px-3 py-2 text-muted text-center">Loading...</div></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Purchase Name</label>
                <input type="text" class="form-control bg-body text-body" id="purchName" name="purchName" placeholder="Purchase Name">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Company</label>
                <input type="text" class="form-control bg-body text-body" id="company" name="company" placeholder="Company">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Vendor</label>
                <input type="text" class="form-control bg-body text-body" id="vendorName" name="vendorName" placeholder="Vendor Name">
            </div>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-12">
            <div class="d-flex justify-content-center gap-3">
                <button type="button" class="btn btn-primary" onclick="grnManager.search()">
                    <i class="icon-base bx bx-search me-2"></i>Search
                </button>
                <button type="button" class="btn btn-secondary" onclick="grnManager.resetFilter()">
                    <i class="icon-base bx bx-refresh me-2"></i>Reset
                </button>
            </div>
        </div>
    </div>
</form>
