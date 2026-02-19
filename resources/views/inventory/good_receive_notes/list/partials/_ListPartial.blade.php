{{-- List PO dengan mstApprovalStatusID = 11 (Fully Approved). Grid sama seperti Approval PO, tanpa checkbox. Thead & tbody rata tengah. --}}
<div class="list-partial-content">
    <div class="table-responsive">
        <style>
            /* Table layout - main table dan DataTables scroll clone (selector seperti PR List) */
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
            /* Rata tengah: thead & tbody (semua kolom center) */
            .list-partial-content .table th,
            .list-partial-content .table td,
            .list-partial-content .dataTables_scrollHead .table th,
            .list-partial-content .dataTables_scrollBody .table th,
            .list-partial-content .dataTables_scrollBody .table td {
                white-space: nowrap;
                vertical-align: middle !important;
                text-align: center !important;
            }
            /* Kolom 1 (Action) - lebar tetap */
            .list-partial-content .table th:nth-child(1),
            .list-partial-content .table td:nth-child(1),
            .list-partial-content .dataTables_scrollHead .table th:nth-child(1),
            .list-partial-content .dataTables_scrollBody .table td:nth-child(1) {
                width: 120px;
                min-width: 120px;
                text-align: center !important;
            }
            /* Purchase Order Name, Purchase Type, Purchase Sub Type, PO Amount, PO Date, PR Number, Vendor Name, Company - center */
            .list-partial-content .table th:nth-child(3),
            .list-partial-content .table td:nth-child(3),
            .list-partial-content .table th:nth-child(4),
            .list-partial-content .table td:nth-child(4),
            .list-partial-content .table th:nth-child(5),
            .list-partial-content .table td:nth-child(5),
            .list-partial-content .table th:nth-child(6),
            .list-partial-content .table td:nth-child(6),
            .list-partial-content .table th:nth-child(7),
            .list-partial-content .table td:nth-child(7),
            .list-partial-content .table th:nth-child(8),
            .list-partial-content .table td:nth-child(8),
            .list-partial-content .table th:nth-child(9),
            .list-partial-content .table td:nth-child(9),
            .list-partial-content .table th:nth-child(10),
            .list-partial-content .table td:nth-child(10),
            .list-partial-content .dataTables_scrollHead .table th:nth-child(3),
            .list-partial-content .dataTables_scrollHead .table th:nth-child(4),
            .list-partial-content .dataTables_scrollHead .table th:nth-child(5),
            .list-partial-content .dataTables_scrollHead .table th:nth-child(6),
            .list-partial-content .dataTables_scrollHead .table th:nth-child(7),
            .list-partial-content .dataTables_scrollHead .table th:nth-child(8),
            .list-partial-content .dataTables_scrollHead .table th:nth-child(9),
            .list-partial-content .dataTables_scrollHead .table th:nth-child(10),
            .list-partial-content .dataTables_scrollBody .table td:nth-child(3),
            .list-partial-content .dataTables_scrollBody .table td:nth-child(4),
            .list-partial-content .dataTables_scrollBody .table td:nth-child(5),
            .list-partial-content .dataTables_scrollBody .table td:nth-child(6),
            .list-partial-content .dataTables_scrollBody .table td:nth-child(7),
            .list-partial-content .dataTables_scrollBody .table td:nth-child(8),
            .list-partial-content .dataTables_scrollBody .table td:nth-child(9),
            .list-partial-content .dataTables_scrollBody .table td:nth-child(10) {
                text-align: center !important;
            }
            /* employee-name jika ada (konsisten dengan PR List) */
            .list-partial-content .table td .employee-name,
            .list-partial-content .dataTables_scrollBody .table td .employee-name {
                display: inline-block;
                width: 160px;
                max-width: 30vw;
                white-space: nowrap;
                overflow: visible;
                text-overflow: clip;
                margin: 0 auto;
                text-align: center !important;
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
