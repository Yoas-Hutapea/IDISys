<div class="col-12">
    <div class="card">
        <div class="card-header" style="border-bottom: 1px solid #e9ecef;">
            <h6 class="card-title mb-0">Detail Purchase Order</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <style>
                    /* Right-align numeric columns: Quantity (6), Unit Price (8), Amount (9) */
                    #confirm-items-table.numeric th:nth-child(6),
                    #confirm-items-table.numeric th:nth-child(8),
                    #confirm-items-table.numeric th:nth-child(9),
                    #confirm-items-table.numeric td:nth-child(6),
                    #confirm-items-table.numeric td:nth-child(8),
                    #confirm-items-table.numeric td:nth-child(9) {
                        text-align: right;
                    }
                </style>
                <table class="table table-striped table-hover numeric" id="confirm-items-table">
                    <thead class="table-light">
                        <tr>
                            <th width="100">Action</th>
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
                    <tbody id="confirm-items-tbody">
                        <!-- Items will be dynamically populated -->
                    </tbody>
                </table>
            </div>
            <div class="row mt-3">
                <div class="col-12 text-end">
                    <label class="form-label fw-semibold me-2">Amount Total:</label>
                    <input type="text" class="form-control d-inline-block text-end" id="confirm-amount-total" value="0" disabled style="width: 200px; text-align: right;">
                </div>
            </div>
        </div>
    </div>
</div>
