<div id="choose-item" class="content" style="display: none;">
    <div class="content-header mb-4">
        <h6 class="mb-0">Choose Item</h6>
        <small>Select an item from the available options.</small>
    </div>

    <!-- Search Section -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small text-muted">Item ID</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" placeholder="Search Item ID..." id="searchItemId">
                        <button class="btn btn-outline-primary" type="button" id="searchItemIdBtn">
                            <i class="icon-base bx bx-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Item Name</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" placeholder="Search Item Name..." id="searchItemName">
                        <button class="btn btn-outline-primary" type="button" id="searchItemNameBtn">
                            <i class="icon-base bx bx-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Prod Pool</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" placeholder="Search Prod Pool..." id="searchProdPool">
                        <button class="btn btn-outline-primary" type="button" id="searchProdPoolBtn">
                            <i class="icon-base bx bx-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">COA</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" placeholder="Search COA..." id="searchCOA">
                        <button class="btn btn-outline-primary" type="button" id="searchCOABtn">
                            <i class="icon-base bx bx-search"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-12">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="clearSearchBtn">
                        <i class="icon-base bx bx-x me-1"></i>Clear All
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Controls Section -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="d-flex align-items-center">
                <label class="form-label me-2 mb-0">Rows per page:</label>
                <select class="form-select form-select-sm" style="width: auto;" id="chooseItemRowsPerPage">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
    </div>

    <!-- DataTable -->
    <div class="row">
        <div class="col-12">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="chooseItemTable">
                    <thead class="table-light">
                        <tr>
                            <th width="80">Action</th>
                            <th>Item ID</th>
                            <th>Item Name</th>
                            <th>Prod Pool</th>
                            <th>COA</th>
                        </tr>
                    </thead>
                    <tbody id="chooseItemTableBody">
                        <!-- Items will be dynamically loaded from API -->
                        <tr>
                            <td colspan="5" class="text-center">
                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                Loading items...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="row mt-3">
        <div class="col-md-6">
            <div class="dataTables_info">
                Showing 1 to 5 of 37.001 entries
            </div>
        </div>
        <div class="col-md-6">
            <div class="dataTables_paginate paging_simple_numbers">
                <ul class="pagination justify-content-end mb-0">
                    <li class="paginate_button previous disabled">
                        <a href="#" class="page-link">
                            <i class="icon-base bx bx-chevron-left"></i>
                        </a>
                    </li>
                    <li class="paginate_button active">
                        <a href="#" class="page-link">1</a>
                    </li>
                    <li class="paginate_button">
                        <a href="#" class="page-link">2</a>
                    </li>
                    <li class="paginate_button">
                        <a href="#" class="page-link">3</a>
                    </li>
                    <li class="paginate_button">
                        <a href="#" class="page-link">4</a>
                    </li>
                    <li class="paginate_button">
                        <a href="#" class="page-link">5</a>
                    </li>
                    <li class="paginate_button next">
                        <a href="#" class="page-link">
                            <i class="icon-base bx bx-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <div class="row mt-4">
        <div class="col-12 d-flex justify-content-center">
            <button type="button" class="btn btn-label-secondary" id="backToAddItemBtn">
                <i class="icon-base bx bx-left-arrow-alt scaleX-n1-rtl icon-sm ms-sm-n2 me-sm-2"></i>
                <span class="align-middle d-sm-inline-block d-none">Back</span>
            </button>
        </div>
    </div>
</div>
