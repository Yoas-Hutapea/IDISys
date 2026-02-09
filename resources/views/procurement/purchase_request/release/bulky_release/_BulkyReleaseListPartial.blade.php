{{--
    List Partial for Bulky Release Purchase Request
    Similar to Receive PR List with checkbox column
    Contains all columns like Receive PR List: checkbox, Purchase Request Number, Purchase Request Name, Type, Sub Type, Status, PIC, Amount PR, Company, Requestor, Applicant, Request Date
--}}

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
            .list-partial-content .table th:nth-child(8),
            .list-partial-content .table td:nth-child(8) { text-align: right; }
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
        <table class="table table-striped table-hover" id="bulkyReleaseTable">
            <thead class="table-light dark:table-dark">
                <tr>
                    <th width="50">
                        <input type="checkbox" id="bulkySelectAllCheckbox" title="Select All" onchange="releaseListManager.toggleBulkySelectAll(this.checked)">
                    </th>
                    <th>Purchase Request Number</th>
                    <th>Purchase Request Name</th>
                    <th>Purchase Request Type</th>
                    <th>Purchase Request Sub Type</th>
                    <th>Status</th>
                    <th>PIC</th>
                    <th>Amount PR</th>
                    <th>Company</th>
                    <th>Requestor</th>
                    <th>Applicant</th>
                    <th>Request Date</th>
                </tr>
            </thead>
            <tbody id="bulkyReleaseTableBody">
                <!-- Data will be dynamically loaded from API -->
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



