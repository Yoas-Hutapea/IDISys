<div class="list-partial-content">
    <div class="table-responsive">
        <table class="table table-striped table-hover" id="approvalPOTable">
            <thead class="table-light dark:table-dark">
                <tr>
                    <th width="50">
                        <input type="checkbox" id="selectAll" onclick="approvalPOManager.toggleSelectAll()">
                    </th>
                    <th width="120">Action</th>
                    <th>Purchase Order Number</th>
                    <th>Purchase Order Name</th>
                    <th>Purchase Type</th>
                    <th>Purchase Sub Type</th>
                    <th>Status</th>
                    <th>PO Amount</th>
                    <th>PO Date</th>
                    <th>PR Number</th>
                    <th>Vendor Name</th>
                    <th>Company</th>
                </tr>
            </thead>
            <tbody id="approvalPOTableBody">
                <tr>
                    <td colspan="12" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div class="mt-2">Loading data...</div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
