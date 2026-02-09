{{--
    Detail Purchase Request Partial for Receive View
    Shows item details table with quantities and amounts
--}}

<div class="col-12">
    <div class="card">
        <div class="card-header" style="border-bottom: 1px solid #e9ecef;">
            <h6 class="card-title mb-0">Detail Purchase Request</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <style>
                    /* Right-align numeric columns: Unit Price (7) and Amount (8) */
                    .table.numeric th:nth-child(7),
                    .table.numeric th:nth-child(8),
                    .table.numeric td:nth-child(7),
                    .table.numeric td:nth-child(8) {
                        text-align: right;
                    }
                </style>
                <table class="table table-striped table-hover numeric">
                    <thead class="table-light">
                        <tr>
                            <th>Item ID</th>
                            <th>Item Name</th>
                            <th>Description</th>
                            <th>UoM</th>
                            <th>Quantity</th>
                            <th>Currency</th>
                            <th>Unit Price</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody id="view-items-tbody">
                        <!-- Items will be dynamically populated -->
                    </tbody>
                </table>
            </div>
            <div class="row mt-3">
                <div class="col-12 text-end">
                    <label class="form-label fw-semibold me-2">Amount Total:</label>
                    <input type="text" class="form-control d-inline-block text-end" id="view-amount-total" value="-" disabled style="width: 200px; text-align: right;">
                </div>
            </div>
        </div>
    </div>
</div>


