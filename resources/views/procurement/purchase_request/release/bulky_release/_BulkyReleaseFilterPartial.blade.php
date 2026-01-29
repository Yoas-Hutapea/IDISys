{{--
    Filter Partial for Bulky Release Purchase Request
    Similar to PR List Filter Partial
    Supports dark mode with proper Bootstrap classes
--}}

<form id="bulkyReleaseFilterForm" class="dark-mode-compatible">
    <div class="row g-3">
        <!-- Left Column -->
        <div class="col-md-6">
            <!-- Period -->
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Period</label>
                <div class="row g-2">
                    <div class="col-6">
                        <input type="date" class="form-control bg-body text-body" id="bulkyStartDate" name="startDate" placeholder="Start Date">
                    </div>
                    <div class="col-1 d-flex align-items-center justify-content-center">
                        <span class="text-muted">to</span>
                    </div>
                    <div class="col-5">
                        <input type="date" class="form-control bg-body text-body" id="bulkyEndDate" name="endDate" placeholder="End Date">
                    </div>
                </div>
            </div>

            <!-- Purchase Request Name -->
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Purchase Request Name</label>
                <input type="text" class="form-control bg-body text-body" id="bulkyPRName" name="prName" placeholder="Purchase Request Name">
            </div>

            <!-- Year -->
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Year</label>
                <select class="form-select bg-body text-body" id="bulkyYear" name="year">
                    <option value="" disabled selected>Select Year</option>
                    @php
                        $currentYear = now()->year;
                    @endphp
                    @for ($y = $currentYear; $y >= $currentYear - 5; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <!-- Company -->
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Company</label>
                <select class="form-select bg-body text-body" id="bulkyCompany" name="company">
                    <option value="" disabled selected>Select Company</option>
                    <option value="TB">TB</option>
                    <option value="BK">BK</option>
                    <option value="IT">IT</option>
                </select>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-md-6">
            <!-- Purchase Request Type -->
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Purchase Request Type</label>
                <select class="form-select bg-body text-body" id="bulkyPRType" name="prType">
                    <option value="" disabled selected>Select Purchase Type</option>
                    <option value="General">General</option>
                    <option value="Regional">Regional</option>
                    <option value="Goods">Goods</option>
                    <option value="Services">Services</option>
                    <option value="Works">Works</option>
                    <option value="Consultancy">Consultancy</option>
                </select>
            </div>

            <!-- Regional -->
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Regional</label>
                <select class="form-select bg-body text-body" id="bulkyRegional" name="regional">
                    <option value="" disabled selected>Select Regional</option>
                    <option value="Jakarta">Jakarta</option>
                    <option value="Surabaya">Surabaya</option>
                    <option value="Bandung">Bandung</option>
                    <option value="Medan">Medan</option>
                    <option value="Semarang">Semarang</option>
                    <option value="Makassar">Makassar</option>
                </select>
            </div>

            <!-- Purchase Request Number -->
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Purchase Request Number</label>
                <input type="text" class="form-control bg-body text-body" id="bulkyPRNumber" name="prNumber" placeholder="PR Number">
            </div>

            <!-- Type SPK -->
            <div class="mb-3">
                <label class="form-label fw-semibold text-body">Type SPK</label>
                <select class="form-select bg-body text-body" id="bulkyTypeSPK" name="typeSPK">
                    <option value="" disabled selected>Select Type SPK</option>
                    <option value="Contract">Contract</option>
                    <option value="Non Contract">Non Contract</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Filter Action Buttons -->
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-center gap-3 mt-4">
                <button type="button" class="btn btn-primary" onclick="releaseListManager.searchBulkyRelease()">
                    <i class="icon-base bx bx-search me-2"></i>
                    Search
                </button>
                <button type="button" class="btn btn-secondary" onclick="releaseListManager.resetBulkyReleaseFilter()">
                    <i class="icon-base bx bx-refresh me-2"></i>
                    Reset
                </button>
            </div>
        </div>
    </div>
</form>



