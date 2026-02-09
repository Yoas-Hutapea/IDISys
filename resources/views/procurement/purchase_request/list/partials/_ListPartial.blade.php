{{-- List Partial for Purchase Request List --}}
<div class="list-partial-content">
    <div class="table-responsive">
        <style>
            /* Auto-fit columns to their content (like Excel AutoFit).
               Allow horizontal scrolling via .table-responsive when content exceeds container. */
            .list-partial-content .table {
                table-layout: auto;
                width: auto;
                min-width: 100%;
            }
            .list-partial-content .table th,
            .list-partial-content .table td {
                white-space: nowrap; /* keep values on single line so column width matches content */
                vertical-align: middle;
                text-align: center; /* center all cell values */
            }
            /* Keep action column reasonably small (fixed) */
            .list-partial-content .table th:nth-child(1) { width: 120px; }
            /* Right-align Amount PR column */
            .list-partial-content .table th:nth-child(8),
            .list-partial-content .table td:nth-child(8) { text-align: right; }
            /* Ensure PIC column content is truly centered and doesn't introduce layout shifts */
            .list-partial-content .table th:nth-child(7),
            .list-partial-content .table td:nth-child(7),
            .list-partial-content .table td .employee-name {
                text-align: center !important;
            }
            /* Make employee-name inline-block to center reliably and limit width to avoid pushing other columns.
               Apply to PIC, Requestor and Applicant columns. */
            .list-partial-content .table td .employee-name {
                display: inline-block;
                width: 180px;
                max-width: 30vw;
                white-space: nowrap;
                overflow: visible;
                text-overflow: clip;
                margin: 0 auto;
            }
            /* Center Requestor and Applicant columns explicitly */
            .list-partial-content .table th:nth-child(10),
            .list-partial-content .table td:nth-child(10),
            .list-partial-content .table th:nth-child(11),
            .list-partial-content .table td:nth-child(11),
            .list-partial-content .table td:nth-child(10) .employee-name,
            .list-partial-content .table td:nth-child(11) .employee-name {
                text-align: center !important;
            }
        </style>
        <table class="table table-striped table-hover" id="prTable">
        <thead class="table-light dark:table-dark">
            <tr>
                <th width="120">Action</th>
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
        <tbody id="prTableBody">
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
