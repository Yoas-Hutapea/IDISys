<div class="table-responsive">
    <table class="table table-striped table-hover" id="updateBulkyPriceItemTable">
        <thead class="table-light dark:table-dark">
            <tr>
                <th width="50">
                    <input type="checkbox" id="selectAllItems" onclick="updateBulkyPriceManager.toggleSelectAllItems()">
                </th>
                <th>Purchase Order Number</th>
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
        <tbody id="updateBulkyPriceItemTableBody">
            <tr>
                <td colspan="10" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-2">Loading data...</div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
