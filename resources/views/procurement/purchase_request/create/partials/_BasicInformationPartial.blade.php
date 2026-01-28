@php
    $employee = session('employee');
    $user = auth()->user();
    $currentUserFullName = $employee?->name ?? $employee?->nick_name ?? $user?->Username ?? 'User';
@endphp

<div id="basic-information" class="content" style="display: block;">
    <div class="content-header mb-4">
        <h6 class="mb-0">Basic Information</h6>
        <small>Enter the basic procurement details.</small>
    </div>
    <div class="row g-6">
        <div class="col-12">
            <label class="form-label" for="Requestor">Requestor</label>
            <input type="text" class="form-control" id="Requestor" name="Requestor" value="{{ $currentUserFullName }}" disabled>
        </div>
        <div class="col-12">
            <label class="form-label" for="Applicant">Applicant <span class="text-danger">*</span></label>
            <div class="dropdown" id="applicantDropdownContainer">
                <button class="form-select text-start" type="button" id="applicantDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <span id="applicantSelectedText">Select Applicant</span>
                </button>
                <input type="hidden" id="Applicant" name="Applicant" required>
                <ul class="dropdown-menu w-100" id="applicantDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                    <li class="px-3 py-2">
                        <input type="text" class="form-control form-control-sm" id="applicantSearchInput" placeholder="Search applicant..." autocomplete="off">
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li id="applicantDropdownItems">
                        <div class="px-3 py-2 text-muted text-center">Loading applicants...</div>
                    </li>
                </ul>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label" for="Company">Company <span class="text-danger">*</span></label>
            <div class="dropdown" id="companyDropdownContainer">
                <button class="form-select text-start" type="button" id="companyDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <span id="companySelectedText">Select Company</span>
                </button>
                <input type="hidden" id="Company" name="Company" required>
                <ul class="dropdown-menu w-100" id="companyDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                    <li class="px-3 py-2">
                        <input type="text" class="form-control form-control-sm" id="companySearchInput" placeholder="Search company..." autocomplete="off">
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li id="companyDropdownItems">
                        <div class="px-3 py-2 text-muted text-center">Loading companies...</div>
                    </li>
                </ul>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label" for="PurchaseRequestType">Purchase Request Type <span class="text-danger">*</span></label>
            <div class="dropdown" id="purchaseRequestTypeDropdownContainer">
                <button class="form-select text-start" type="button" id="purchaseRequestTypeDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <span id="purchaseRequestTypeSelectedText">Select Purchase Request Type</span>
                </button>
                <input type="hidden" id="PurchaseRequestType" name="PurchaseRequestType" required>
                <input type="hidden" id="PurchaseRequestTypeCategory" name="PurchaseRequestTypeCategory">
                <ul class="dropdown-menu w-100" id="purchaseRequestTypeDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                    <li class="px-3 py-2">
                        <input type="text" class="form-control form-control-sm" id="purchaseRequestTypeSearchInput" placeholder="Search purchase request type..." autocomplete="off">
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li id="purchaseRequestTypeDropdownItems">
                        <div class="px-3 py-2 text-muted text-center">Loading purchase request types...</div>
                    </li>
                </ul>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label" for="PurchaseRequestSubType">Purchase Request Sub Type <span class="text-danger">*</span></label>
            <div class="dropdown" id="purchaseRequestSubTypeDropdownContainer">
                <button class="form-select text-start" type="button" id="purchaseRequestSubTypeDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <span id="purchaseRequestSubTypeSelectedText">Select Purchase Request Sub Type</span>
                </button>
                <input type="hidden" id="PurchaseRequestSubType" name="PurchaseRequestSubType" required>
                <ul class="dropdown-menu w-100" id="purchaseRequestSubTypeDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                    <li class="px-3 py-2">
                        <input type="text" class="form-control form-control-sm" id="purchaseRequestSubTypeSearchInput" placeholder="Search purchase request sub type..." autocomplete="off">
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li id="purchaseRequestSubTypeDropdownItems">
                        <div class="px-3 py-2 text-muted text-center">Loading purchase request sub types...</div>
                    </li>
                </ul>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label" for="PurchaseRequestName">Purchase Request Name <span class="text-danger">*</span></label>
            <input type="text" id="PurchaseRequestName" name="PurchaseRequestName" class="form-control" placeholder="Text input" required>
        </div>
        <div class="col-12">
            <label class="form-label" for="Remarks">Remarks <span class="text-danger">*</span></label>
            <textarea id="Remarks" name="Remarks" class="form-control" rows="4" placeholder="Text input" required></textarea>
        </div>
        <div class="col-12 d-flex justify-content-center gap-2">
            <button class="btn btn-label-secondary btn-prev" disabled>
                <i class="icon-base bx bx-left-arrow-alt scaleX-n1-rtl icon-sm ms-sm-n2 me-sm-2"></i>
                <span class="align-middle d-sm-inline-block d-none">Back</span>
            </button>
            <button class="btn btn-outline-dark btn-next">
                <span class="align-middle d-sm-inline-block d-none me-sm-2">Continue</span>
                <i class="icon-base bx bx-right-arrow-alt scaleX-n1-rtl icon-sm me-sm-n2"></i>
            </button>
        </div>
    </div>
</div>
