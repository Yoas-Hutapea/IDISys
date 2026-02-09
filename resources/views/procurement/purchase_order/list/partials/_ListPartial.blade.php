{{-- List Partial for Purchase Order List --}}
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
            .list-partial-content .table th:nth-child(1) { width: 120px; }
            .list-partial-content .table th:nth-child(7),
            .list-partial-content .table td:nth-child(7) { text-align: right; }
            .list-partial-content .table td .employee-name {
                display: inline-block;
                width: 160px;
                max-width: 30vw;
                white-space: nowrap;
                overflow: visible;
                text-overflow: clip;
                margin: 0 auto;
            }
        </style>
        <table class="table table-striped table-hover" id="poTable">
            <thead class="table-light dark:table-dark">
                <tr>
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
                    <th>PO Author</th>
                </tr>
            </thead>
            <tbody id="poTableBody">
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
