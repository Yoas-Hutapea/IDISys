<div id="add-item-detail" class="content" style="display: none;">

    <div id="addItemForm">
        <div class="row g-4">
            <div class="col-12">
                <label class="form-label" for="itemId">Item ID <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="text" class="form-control" id="itemId" name="itemId" placeholder="Choose Item" required readonly>
                    <button class="btn btn-outline-primary" type="button" id="searchItemBtn">
                        <i class="icon-base bx bx-search"></i>
                    </button>
                </div>
                <!-- Hidden field to store mstPROPurchaseItemInventoryItemID (ID from mstPROPurchaseItemInventory) -->
                <input type="hidden" id="mstPROPurchaseItemInventoryItemID" name="mstPROPurchaseItemInventoryItemID">
                <!-- Backward compatibility: keep old field name -->
                <input type="hidden" id="mstPROInventoryItemID" name="mstPROInventoryItemID">
            </div>

            <div class="col-12">
                <label class="form-label" for="itemName">Item Name</label>
                <input type="text" class="form-control" id="itemName" name="itemName" placeholder="Item Name" disabled>
            </div>

            <div class="col-12">
                <label class="form-label" for="description">Description</label>
                <input type="text" class="form-control" id="description" name="description" placeholder="Description">
            </div>

            <div class="col-md-6">
                <label class="form-label" for="unit">Unit <span class="text-danger">*</span></label>
                <div class="dropdown" id="unitDropdownContainer">
                    <button class="form-select text-start" type="button" id="unitDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                        <span id="unitSelectedText">Select Unit</span>
                    </button>
                    <input type="hidden" id="unit" name="unit" required>
                    <ul class="dropdown-menu w-100" id="unitDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                        <li class="px-3 py-2">
                            <input type="text" class="form-control form-control-sm" id="unitSearchInput" placeholder="Search unit..." autocomplete="off">
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li id="unitDropdownItems">
                            <div class="px-3 py-2 text-muted text-center">Loading units...</div>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="currency">Currency</label>
                <div class="dropdown" id="currencyDropdownContainer">
                    <button class="form-select text-start" type="button" id="currencyDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                        <span id="currencySelectedText">Select Currency</span>
                    </button>
                    <input type="hidden" id="currency" name="currency">
                    <ul class="dropdown-menu w-100" id="currencyDropdownMenu" style="max-height: 300px; overflow-y: auto;">
                        <li class="px-3 py-2">
                            <input type="text" class="form-control form-control-sm" id="currencySearchInput" placeholder="Search currency..." autocomplete="off">
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li id="currencyDropdownItems">
                            <div class="px-3 py-2 text-muted text-center">Loading currencies...</div>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="quantity">Qty <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="quantity" name="quantity" placeholder="Qty Item" required>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="unitPrice">Unit Price <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="unitPrice" name="unitPrice" placeholder="Price" required>
            </div>

            <div class="col-12">
                <label class="form-label" for="amount">Amount</label>
                <input type="text" class="form-control" id="amount" name="amount" placeholder="Amount Item" disabled>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="row mt-4">
            <div class="col-12 d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-label-secondary" id="backToDetailBtn">
                    <i class="icon-base bx bx-left-arrow-alt scaleX-n1-rtl icon-sm ms-sm-n2 me-sm-2"></i>
                    <span class="align-middle d-sm-inline-block d-none">Back</span>
                </button>
                <button type="button" class="btn btn-primary" id="addItemBtn">
                    <i class="icon-base bx bx-plus me-2"></i>
                    <span class="align-middle d-sm-inline-block d-none">Add</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Include Choose Item Partial -->
@include('procurement.purchase_request.create.partials._ChooseItemPartial')
