{{-- List Partial for Approval PO - aligned with List PO grid (centered columns, right-align amount) --}}
<div class="list-partial-content">
    <div class="table-responsive">
        <style>
            /* Table layout - apply to main table and DataTables scroll clone */
            .list-partial-content #approvalPOTable,
            .list-partial-content .dataTables_scrollHead #approvalPOTable,
            .list-partial-content .dataTables_scrollBody #approvalPOTable {
                table-layout: auto;
                width: auto;
                min-width: 100%;
            }
            /* Rata tengah: semua header dan cell (termasuk checkbox, PO Name, Status, PO Amount kolom teks, PO Date, PR Number, Vendor, Company) */
            .list-partial-content #approvalPOTable th,
            .list-partial-content #approvalPOTable td,
            .list-partial-content .dataTables_scrollHead #approvalPOTable th,
            .list-partial-content .dataTables_scrollBody #approvalPOTable th,
            .list-partial-content .dataTables_scrollBody #approvalPOTable td {
                white-space: nowrap;
                vertical-align: middle !important;
                text-align: center !important;
            }
            /* Kolom 1 (checkbox) dan 2 (Action) - lebar tetap */
            .list-partial-content #approvalPOTable th:nth-child(1),
            .list-partial-content .dataTables_scrollHead #approvalPOTable th:nth-child(1) { width: 50px; min-width: 50px; text-align: center !important; }
            .list-partial-content #approvalPOTable th:nth-child(2),
            .list-partial-content #approvalPOTable td:nth-child(2),
            .list-partial-content .dataTables_scrollHead #approvalPOTable th:nth-child(2),
            .list-partial-content .dataTables_scrollBody #approvalPOTable td:nth-child(2) { width: 120px; min-width: 120px; text-align: center !important; }
            /* Kolom 8 (PO Amount) - rata kanan */
            .list-partial-content #approvalPOTable th:nth-child(8),
            .list-partial-content #approvalPOTable td:nth-child(8),
            .list-partial-content .dataTables_scrollHead #approvalPOTable th:nth-child(8),
            .list-partial-content .dataTables_scrollBody #approvalPOTable td:nth-child(8) { text-align: right !important; }
            .list-partial-content #approvalPOTable td .employee-name {
                display: inline-block;
                width: 160px;
                max-width: 30vw;
                white-space: nowrap;
                overflow: visible;
                text-overflow: clip;
                margin: 0 auto;
            }
        </style>
        <table class="table table-striped table-hover list-partial-table" id="approvalPOTable">
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
