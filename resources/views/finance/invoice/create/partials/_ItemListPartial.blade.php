<div class="col-12">
    <div class="card">
        <div class="card-header" style="border-bottom: 1px solid #e9ecef;">
            <h6 class="card-title mb-0">Item List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
            <table class="table table-striped table-hover" id="tblPODetail">
                <thead class="table-light dark:table-dark">
                    <tr>
                        <th class="text-center">Item ID</th>
                        <th class="text-center">Item Name</th>
                        <th class="text-center">Unit</th>
                        <th class="text-center">Description</th>
                        <th class="text-center">Qty PO</th>
                        <th class="text-center">Price</th>
                        <th class="text-center">Amount</th>
                        <th class="text-center">Qty Invoice</th>
                        <th class="text-center">Amount Invoice</th>
                    </tr>
                </thead>
                <tbody id="tblPODetailBody">
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">No PO selected</td>
                    </tr>
                </tbody>
            </table>
            </div>
            <div class="row mt-3">
                <div class="col-12 text-end">
                    <label class="form-label fw-semibold me-2">Total Amount:</label>
                    <input type="text" class="form-control d-inline-block text-end" id="txtItemListTotalAmount" value="0" disabled style="width: 200px;">
                </div>
            </div>
        </div>
    </div>
</div>
