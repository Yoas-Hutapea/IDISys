<!-- Menu -->
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo justify-content-center">
        <a href="/" class="app-brand-link">
            <span class="app-brand-logo demo">
                <img id="sidebar-logo" src="{{ asset('assets/media/logos/idi-logos-lightmode.png') }}" alt="IDEANET">
            </span>
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <i class="icon-base bx bx-chevron-left"></i>
        </a>
    </div>

    <!-- Sidebar Search -->
    <div class="sidebar-search-container px-3 py-2">
        <div class="input-group input-group-sm">
            <span class="input-group-text bg-body border-end-0">
                <i class="icon-base bx bx-search"></i>
            </span>
            <input type="text"
                   class="form-control border-start-0"
                   id="sidebarSearchInput"
                   placeholder="Search menu..."
                   autocomplete="off">
            <button class="btn btn-outline-secondary border-start-0 d-none"
                    type="button"
                    id="sidebarSearchClear"
                    title="Clear search">
                <i class="icon-base bx bx-x"></i>
            </button>
        </div>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1" id="menuInner">
        <!-- Dashboards -->
        <li class="menu-item active">
            <a href="/" class="menu-link">
                <i class="menu-icon icon-base bx bx-home-smile"></i>
                <div data-i18n="Dashboard">Dashboard</div>
            </a>
        </li>

        <!-- Marketing -->
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base bx bx-credit-card"></i>
                <div data-i18n="Marketing">Marketing</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link">
                        <div data-i18n="All Marketing">All Marketing</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link">
                        <div data-i18n="Campaigns">Campaigns</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link">
                        <div data-i18n="Leads">Leads</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Procurement -->
        <li class="menu-item">
            <a href="javascript:void(0)" class="menu-link menu-toggle">
                <i class="menu-icon icon-base bx bx-grid"></i>
                <div data-i18n="Procurement">Procurement</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="javascript:void(0)" class="menu-link menu-toggle">
                        <div data-i18n="Purchase Request">Purchase Request</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item">
                            <a href="/Procurement/PurchaseRequest/Create" class="menu-link">
                                <div data-i18n="Create PR">Create PR</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="/Procurement/PurchaseRequest/Approval" class="menu-link">
                                <div data-i18n="Approval PR">Approval PR</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="/Procurement/PurchaseRequest/List" class="menu-link">
                                <div data-i18n="PR List">PR List</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="/Procurement/PurchaseRequest/Receive" class="menu-link">
                                <div data-i18n="Receive PR">Receive PR</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="/Procurement/PurchaseRequest/Release" class="menu-link">
                                <div data-i18n="Release PR">Release PR</div>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="menu-item">
                    <a href="javascript:void(0)" class="menu-link menu-toggle">
                        <div data-i18n="Purchase Order">Purchase Order</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item">
                            <a href="/Procurement/PurchaseOrder/List" class="menu-link">
                                <div data-i18n="PO List">PO List</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="/Procurement/PurchaseOrder/Confirm" class="menu-link">
                                <div data-i18n="Confirm PO">Confirm PO</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="/Procurement/PurchaseOrder/Approval" class="menu-link">
                                <div data-i18n="Approval PO">Approval PO</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="/Procurement/PurchaseOrder/CancelPeriod" class="menu-link">
                                <div data-i18n="Cancel Period">Cancel Period</div>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </li>

        <!-- Vendor Management -->
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base bx bx-group"></i>
                <div data-i18n="Vendor Management">Vendor Management</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="/Finance/VendorManagement/VendorData" class="menu-link">
                        <div data-i18n="Vendor Data">Vendor Data</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="/Finance/VendorManagement/VendorContract" class="menu-link">
                        <div data-i18n="Vendor Contract">Vendor Contract</div>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Finance AP -->
        <li class="menu-item">
            <a href="javascript:void(0)" class="menu-link menu-toggle">
                <i class="menu-icon icon-base bx bx-grid"></i>
                <div data-i18n="Finance AP">Finance AP</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="javascript:void(0)" class="menu-link menu-toggle">
                        <div data-i18n="Expense & Advance">Expense & Advance</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item">
                            <a href="javascript:void(0)" class="menu-link">
                                <div data-i18n="Expense Request">Expense Request</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="javascript:void(0)" class="menu-link">
                                <div data-i18n="Advance Request">Advance Request</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="javascript:void(0)" class="menu-link">
                                <div data-i18n="Approval">Approval</div>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="menu-item">
                    <a href="javascript:void(0)" class="menu-link menu-toggle">
                        <div data-i18n="E-Billing">E-Billing</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item">
                            <a href="/Finance/EBilling/MasterDocumentChecklist" class="menu-link">
                                <div data-i18n="Master Document Checklist">Master Document Checklist</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="/Finance/EBilling/MasterWorkType" class="menu-link">
                                <div data-i18n="Master Work Type">Master Work Type</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="/Finance/EBilling/MasterWorkTypePurchaseType" class="menu-link">
                                <div data-i18n="Master Work Type to Purchase Type">Master Work Type to Purchase Type</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="/Finance/EBilling/MasterWorkTypeTOPDocumentChecklist" class="menu-link">
                                <div data-i18n="Master Work Type to TOP to Document Checklist">Master Work Type to TOP to Document Checklist</div>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="menu-item">
                    <a href="javascript:void(0)" class="menu-link menu-toggle">
                        <div data-i18n="Invoice">Invoice</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item">
                            <a href="/Finance/EBilling/Invoice/Create" class="menu-link">
                                <div data-i18n="Create Invoice">Create Invoice</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="/Finance/EBilling/Invoice/Posting" class="menu-link">
                                <div data-i18n="Posting Invoice">Posting Invoice</div>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="/Finance/EBilling/Invoice/List" class="menu-link">
                                <div data-i18n="List Invoice">List Invoice</div>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </li>
        <!-- Finance AR -->
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base bx bx-group"></i>
                <div data-i18n="Finance AR">Finance AR</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link">
                        <div data-i18n="Invoice">Invoice</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="javascript:void(0);" class="menu-link">
                        <div data-i18n="List Invoice">List Invoice</div>
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</aside>
<!-- / Menu -->
