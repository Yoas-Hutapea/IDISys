<div class="list-partial-content">
    <div class="table-responsive">
        <style>
            .list-partial-content .table {
                table-layout: auto;
                width: auto;
                min-width: 100%;
            }
            .list-partial-content .dataTables_scrollHead .table,
            .list-partial-content .dataTables_scrollBody .table {
                table-layout: auto;
                width: auto;
                min-width: 100%;
            }
            .list-partial-content .table th,
            .list-partial-content .table td,
            .list-partial-content .dataTables_scrollHead .table th,
            .list-partial-content .dataTables_scrollBody .table th,
            .list-partial-content .dataTables_scrollBody .table td {
                white-space: nowrap;
                vertical-align: middle !important;
                text-align: center !important;
            }
            .list-partial-content .table th:nth-child(1),
            .list-partial-content .table td:nth-child(1),
            .list-partial-content .dataTables_scrollHead .table th:nth-child(1),
            .list-partial-content .dataTables_scrollBody .table td:nth-child(1) {
                width: 120px;
                min-width: 120px;
                text-align: center !important;
            }
        </style>
        <table class="table table-striped table-hover list-partial-table" id="grnHeaderTable">
            <thead class="table-light dark:table-dark">
                <tr>
                    <th width="120">Action</th>
                    <th>GR Number</th>
                    <th>PO Number</th>
                    <th>Remark</th>
                    <th>Created By</th>
                    <th>Created Date</th>
                    <th>Updated By</th>
                    <th>Updated Date</th>
                </tr>
            </thead>
            <tbody id="grnHeaderTableBody">
                <tr>
                    <td colspan="8" class="text-center py-4 align-middle">
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
