{{-- List PO dengan mstApprovalStatusID = 11 (Fully Approved). Grid sama seperti Approval PO, tanpa checkbox. Thead & tbody rata tengah. --}}
<div class="list-partial-content">
    <div class="table-responsive">
        <style>
            /* Table layout - apply to main table and DataTables scroll clone */
            .list-partial-content #grnPOTable,
            .list-partial-content .dataTables_scrollHead #grnPOTable,
            .list-partial-content .dataTables_scrollBody #grnPOTable {
                table-layout: auto;
                width: auto;
                min-width: 100%;
            }
            /* Rata tengah: semua header dan cell (thead & tbody) */
            .list-partial-content #grnPOTable th,
            .list-partial-content #grnPOTable td,
            .list-partial-content .dataTables_scrollHead #grnPOTable th,
            .list-partial-content .dataTables_scrollBody #grnPOTable th,
            .list-partial-content .dataTables_scrollBody #grnPOTable td {
                white-space: nowrap;
                vertical-align: middle !important;
                text-align: center !important;
            }
            /* Kolom 1 (Action) - lebar tetap */
            .list-partial-content #grnPOTable th:nth-child(1),
            .list-partial-content #grnPOTable td:nth-child(1),
            .list-partial-content .dataTables_scrollHead #grnPOTable th:nth-child(1),
            .list-partial-content .dataTables_scrollBody #grnPOTable td:nth-child(1) {
                width: 120px;
                min-width: 120px;
                text-align: center !important;
            }
            /* Kolom 6 (PO Amount) - rata kanan */
            .list-partial-content #grnPOTable th:nth-child(6),
            .list-partial-content #grnPOTable td:nth-child(6),
            .list-partial-content .dataTables_scrollHead #grnPOTable th:nth-child(6),
            .list-partial-content .dataTables_scrollBody #grnPOTable td:nth-child(6) {
                text-align: right !important;
            }
        </style>
        <table class="table table-striped table-hover list-partial-table" id="grnPOTable">
            <thead class="table-light dark:table-dark">
                <tr>
                    <th width="120">Action</th>
                    <th>Purchase Order Number</th>
                    <th>Purchase Order Name</th>
                    <th>Purchase Type</th>
                    <th>Purchase Sub Type</th>
                    <th>PO Amount</th>
                    <th>PO Date</th>
                    <th>PR Number</th>
                    <th>Vendor Name</th>
                    <th>Company</th>
                </tr>
            </thead>
            <tbody id="grnPOTableBody">
                <tr>
                    <td colspan="10" class="text-center py-4 align-middle">
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
