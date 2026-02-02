<div class="col-12">
    <div class="card">
        <div class="card-header" style="border-bottom: 1px solid #e9ecef;">
            <h6 class="card-title mb-0">Detail Purchase Request</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="approval-items-table">
                    <thead class="table-light">
                        <tr>
                            <th>Item ID</th>
                            <th>Item Name</th>
                            <th>Description</th>
                            <th>UoM</th>
                            <th class="text-end">Quantity</th>
                            <th>Currency</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody id="approval-items-tbody">
                        <!-- Items will be dynamically populated -->
                    </tbody>
                </table>
            </div>
            <div class="row mt-3">
                <div class="col-12 text-end">
                    <label class="form-label fw-semibold me-2">Amount Total:</label>
                    <input type="text" class="form-control d-inline-block" id="approval-amount-total" value="0" disabled style="width: 200px;">
                </div>
            </div>
        </div>
    </div>
</div>
