<style>
    .update-bulky-price-container {
        contain: layout style;
    }

    .filter-section {
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 0.375rem;
        contain: layout;
        transition: all 0.3s ease;
        margin-bottom: 1.5rem;
    }

    [data-bs-theme="dark"] .filter-section {
        background-color: #2b2b2b;
        border-color: #495057;
    }

    .filter-header {
        background-color: #2352a1;
        color: white;
        padding: 0.75rem 1rem;
        border-radius: 0.375rem 0.375rem 0 0;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background-color 0.2s ease;
    }

    .filter-header:hover {
        background-color: #0b5ed7;
    }

    .filter-content {
        padding: 1.5rem;
        contain: layout;
        display: none;
    }

    .filter-content.show {
        display: block;
    }

    .list-section {
        background-color: white;
        border-radius: 0.375rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        contain: layout;
        margin-bottom: 1.5rem;
    }

    [data-bs-theme="dark"] .list-section {
        background-color: #2b2b2b;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.3);
    }

    .list-header {
        background-color: #f8f9fa;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e9ecef;
        border-radius: 0.375rem 0.375rem 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        contain: layout;
    }

    [data-bs-theme="dark"] .list-header {
        background-color: #3a3a3a;
        border-bottom-color: #495057;
    }

    .list-content {
        padding: 1.5rem;
    }

    #updateBulkyPricePOTable .action-btn,
    #updateBulkyPriceItemTable .action-btn {
        min-width: 38px;
        min-height: 38px;
        padding: 0.5rem 0.75rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        line-height: 1.5;
    }

    #updateBulkyPricePOTable .action-btn i,
    #updateBulkyPriceItemTable .action-btn i {
        font-size: 1rem;
        line-height: 1;
    }

    #updateBulkyPriceItemTable tbody tr.table-warning {
        background-color: #fff3cd !important;
    }

    [data-bs-theme="dark"] #updateBulkyPriceItemTable tbody tr.table-warning {
        background-color: #664d03 !important;
    }
</style>

<div class="p-4 update-bulky-price-container">
    <section class="filter-section" aria-labelledby="update-bulky-price-filter-heading">
        <header class="filter-header" onclick="toggleUpdateBulkyPriceFilter()" role="button" tabindex="0" aria-expanded="false" aria-controls="updateBulkyPriceFilterContent">
            <span id="update-bulky-price-filter-heading">
                <i class="icon-base bx bx-filter me-2" aria-hidden="true"></i>
                Filter
            </span>
            <i class="icon-base bx bx-chevron-down" id="updateBulkyPriceFilterChevron" aria-hidden="true"></i>
        </header>
        <div class="filter-content" id="updateBulkyPriceFilterContent" role="region" aria-labelledby="update-bulky-price-filter-heading">
            @include('procurement.purchase_order.confirm.update_bulky_price._FilterPartial')
        </div>
    </section>

    <section class="list-section" aria-labelledby="po-list-heading">
        <header class="list-header">
            <div class="d-flex align-items-center">
                <h5 id="po-list-heading" class="fw-semibold mb-0">
                    List Purchase Order<span id="dateRangeInfo" class="text-muted ms-2" style="font-size: 0.9em; font-weight: normal;"></span>
                </h5>
            </div>
        </header>
        <div class="list-content">
            @include('procurement.purchase_order.confirm.update_bulky_price._POListPartial')
        </div>
    </section>

    <section class="list-section" aria-labelledby="item-list-heading">
        <header class="list-header">
            <div class="d-flex align-items-center">
                <h5 id="item-list-heading" class="fw-semibold mb-0">List Item Purchase Order</h5>
            </div>
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-primary" id="updateBulkyPriceActionBtn" onclick="updateBulkyPriceManager.updateBulkyPrice()" disabled>
                    <i class="icon-base bx bx-edit me-1"></i>
                    Update Bulky Price
                </button>
            </div>
        </header>
        <div class="list-content">
            @include('procurement.purchase_order.confirm.update_bulky_price._ItemListPartial')
        </div>
    </section>

    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-center gap-2">
                <button class="btn btn-secondary" onclick="updateBulkyPriceManager.back()">
                    <i class="icon-base bx bx-arrow-back me-1"></i>
                    Back
                </button>
                <button class="btn btn-primary" id="submitUpdateBulkyPriceBtn" onclick="updateBulkyPriceManager.submit()">
                    <i class="icon-base bx bx-check me-1"></i>
                    Submit
                </button>
            </div>
        </div>
    </div>
</div>
