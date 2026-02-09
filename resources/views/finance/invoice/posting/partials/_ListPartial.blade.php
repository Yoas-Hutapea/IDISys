<div class="list-partial-content">
    <div class="table-responsive">
        <style>
            .list-partial-content .table {
                table-layout: auto;
                width: auto;
                min-width: 100%;
            }
            .list-partial-content .table th,
            .list-partial-content .table td {
                white-space: nowrap;
                vertical-align: middle;
                text-align: center;
            }
            .list-partial-content .table th:nth-child(1) { width: 50px; }
            .list-partial-content .table th:nth-child(2) { width: 120px; }
            .list-partial-content .table th:nth-child(18),
            .list-partial-content .table td:nth-child(18) { text-align: right; }
            .list-partial-content .table th:nth-child(7),
            .list-partial-content .table td:nth-child(7),
            .list-partial-content .table td .employee-name { text-align: center !important; }
            .list-partial-content .table td .employee-name {
                display: inline-block;
                width: 180px;
                max-width: 30vw;
                white-space: nowrap;
                overflow: visible;
                text-overflow: clip;
                margin: 0 auto;
            }
            .list-partial-content .table th:nth-child(10),
            .list-partial-content .table td:nth-child(10),
            .list-partial-content .table th:nth-child(11),
            .list-partial-content .table td:nth-child(11) {
                text-align: center !important;
            }
        </style>
        <table class="table table-striped table-hover" id="postingTable">
            <thead class="table-light dark:table-dark">
                <tr>
                    <th width="50">
                        <input type="checkbox" id="selectAllCheckbox" title="Select All" onchange="toggleSelectAll(this)">
                    </th>
                    <th width="120" class="text-center">Action</th>
                    <th class="text-center">Request Number</th>
                    <th class="text-center">Invoice Number</th>
                    <th class="text-center">Invoice Date</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">PIC</th>
                    <th class="text-center">PO Number</th>
                    <th class="text-center">Term Paid Value</th>
                    <th class="text-center">Period Month</th>
                    <th class="text-center">Purchase Type</th>
                    <th class="text-center">Purchase Sub Type</th>
                    <th class="text-center">SONumber</th>
                    <th class="text-center">Site ID</th>
                    <th class="text-center">Site Name</th>
                    <th class="text-center">PO Date</th>
                    <th class="text-center">Company</th>
                    <th class="text-center">Invoice Amount</th>
                    <th class="text-center">Work Type</th>
                </tr>
            </thead>
            <tbody id="postingTableBody">
                <tr>
                    <td colspan="19" class="text-center py-4">
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
