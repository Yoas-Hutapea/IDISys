{{--
    List Partial for Purchase Request Approval List
    Contains the table with approval data and pagination
    Supports dark mode with proper Bootstrap classes
--}}

<div class="list-partial-content">
    <div class="table-responsive">
        <table class="table table-striped table-hover" id="approvalTable">
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
        <tbody id="approvalTableBody">
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


