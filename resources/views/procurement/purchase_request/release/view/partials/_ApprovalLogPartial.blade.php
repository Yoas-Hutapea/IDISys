{{--
    Approval Log Partial for Release View
    Shows approval log table with history
--}}

<div class="col-12">
    <div class="card">
        <div class="card-header" style="border-bottom: 1px solid #e9ecef;">
            <h6 class="card-title mb-0">Approval Log</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="viewApprovalLogTable" class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Activity</th>
                            <th>Decision</th>
                            <th>Employee</th>
                            <th>Position</th>
                            <th>Remark</th>
                        </tr>
                    </thead>
                    <tbody id="view-approval-log-tbody">
                        <!-- Approval log will be dynamically populated -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


