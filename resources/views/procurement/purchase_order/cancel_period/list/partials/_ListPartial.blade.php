{{-- List Partial for Cancel Period - aligned with List PO grid (rata tengah thead/tbody, right-align amount) --}}
<div class="list-partial-content">
    <div class="table-responsive">
        <style>
            /* Table layout - main table and DataTables scroll clone */
            .list-partial-content #poTable,
            .list-partial-content .dataTables_scrollHead #poTable,
            .list-partial-content .dataTables_scrollBody #poTable {
                table-layout: auto;
                width: auto;
                min-width: 100%;
            }
            /* Rata tengah: semua header dan cell thead/tbody */
            .list-partial-content #poTable th,
            .list-partial-content #poTable td,
            .list-partial-content .dataTables_scrollHead #poTable th,
            .list-partial-content .dataTables_scrollBody #poTable th,
            .list-partial-content .dataTables_scrollBody #poTable td {
                white-space: nowrap;
                vertical-align: middle !important;
                text-align: center !important;
            }
            /* Pastikan thead th rata tengah dengan tbody (override Bootstrap/DataTables) */
            .list-partial-content #poTable thead th,
            .list-partial-content #poTable thead tr th,
            .list-partial-content .dataTables_scrollHead #poTable thead th,
            .list-partial-content .dataTables_scrollHead #poTable thead tr th {
                vertical-align: middle !important;
                text-align: center !important;
            }
            /* Kolom 7 di thead tetap rata kanan */
            .list-partial-content #poTable thead th:nth-child(7),
            .list-partial-content .dataTables_scrollHead #poTable thead th:nth-child(7) { text-align: right !important; }
            /* Kolom 1 (Action) */
            .list-partial-content #poTable th:nth-child(1),
            .list-partial-content #poTable td:nth-child(1),
            .list-partial-content .dataTables_scrollHead #poTable th:nth-child(1),
            .list-partial-content .dataTables_scrollBody #poTable td:nth-child(1) { width: 120px; min-width: 120px; text-align: center !important; }
            /* Kolom 7 (PO Amount) - rata kanan */
            .list-partial-content #poTable th:nth-child(7),
            .list-partial-content #poTable td:nth-child(7),
            .list-partial-content .dataTables_scrollHead #poTable th:nth-child(7),
            .list-partial-content .dataTables_scrollBody #poTable td:nth-child(7) { text-align: right !important; }
            .list-partial-content #poTable td .employee-name,
            .list-partial-content .dataTables_scrollBody #poTable td .employee-name {
                display: inline-block;
                width: 160px;
                max-width: 30vw;
                white-space: nowrap;
                overflow: visible;
                text-overflow: clip;
                margin: 0 auto;
            }
            /* Action buttons: icon, cursor and size like Approval PO list */
            .list-partial-content #poTable .action-btn,
            .list-partial-content .dataTables_scrollBody #poTable .action-btn {
                cursor: pointer;
                min-width: 38px;
                min-height: 38px;
                padding: 0.5rem 0.75rem;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 0.375rem;
                font-size: 0.875rem;
                line-height: 1.5;
            }
            .list-partial-content #poTable .action-btn i,
            .list-partial-content .dataTables_scrollBody #poTable .action-btn i {
                font-size: 1rem;
                line-height: 1;
            }
        </style>
        <table class="table table-striped table-hover list-partial-table" id="poTable">
            <thead class="table-light dark:table-dark">
                <tr>
                    <th width="120" class="text-center align-middle">Action</th>
                    <th class="text-center align-middle">Purchase Order Number</th>
                    <th class="text-center align-middle">Purchase Order Name</th>
                    <th class="text-center align-middle">Purchase Type</th>
                    <th class="text-center align-middle">Purchase Sub Type</th>
                    <th class="text-center align-middle">Status</th>
                    <th class="text-end align-middle">PO Amount</th>
                    <th class="text-center align-middle">PO Date</th>
                    <th class="text-center align-middle">PR Number</th>
                    <th class="text-center align-middle">Vendor Name</th>
                    <th class="text-center align-middle">Company</th>
                    <th class="text-center align-middle">PO Author</th>
                    <th class="text-center align-middle">PR Requestor</th>
                </tr>
            </thead>
            <tbody id="poTableBody">
                <tr>
                    <td colspan="13" class="text-center py-4">
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
