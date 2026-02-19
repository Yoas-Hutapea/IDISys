{{-- List Partial for Purchase Order List - thead & tbody rata tengah (sama seperti Approval PO) --}}
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
            /* Purchase Order Name, Purchase Type, Purchase Sub Type, Status, PO Amount, PO Date, PR Number, Vendor Name, Company, PO Author - center */
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
            .list-partial-content .table th:nth-child(11),
            .list-partial-content .table td:nth-child(11),
            .list-partial-content .table th:nth-child(12),
            .list-partial-content .table td:nth-child(12),
            .list-partial-content .dataTables_scrollHead .table th:nth-child(3),
            .list-partial-content .dataTables_scrollHead .table th:nth-child(4),
            .list-partial-content .dataTables_scrollHead .table th:nth-child(5),
            .list-partial-content .dataTables_scrollHead .table th:nth-child(6),
            .list-partial-content .dataTables_scrollHead .table th:nth-child(7),
            .list-partial-content .dataTables_scrollHead .table th:nth-child(8),
            .list-partial-content .dataTables_scrollHead .table th:nth-child(9),
            .list-partial-content .dataTables_scrollHead .table th:nth-child(10),
            .list-partial-content .dataTables_scrollHead .table th:nth-child(11),
            .list-partial-content .dataTables_scrollHead .table th:nth-child(12),
            .list-partial-content .dataTables_scrollBody .table td:nth-child(3),
            .list-partial-content .dataTables_scrollBody .table td:nth-child(4),
            .list-partial-content .dataTables_scrollBody .table td:nth-child(5),
            .list-partial-content .dataTables_scrollBody .table td:nth-child(6),
            .list-partial-content .dataTables_scrollBody .table td:nth-child(7),
            .list-partial-content .dataTables_scrollBody .table td:nth-child(8),
            .list-partial-content .dataTables_scrollBody .table td:nth-child(9),
            .list-partial-content .dataTables_scrollBody .table td:nth-child(10),
            .list-partial-content .dataTables_scrollBody .table td:nth-child(11),
            .list-partial-content .dataTables_scrollBody .table td:nth-child(12) {
                text-align: center !important;
            }
            /* PO Author (kolom 12) - employee-name center */
            .list-partial-content .table td:nth-child(12) .employee-name,
            .list-partial-content .dataTables_scrollBody .table td:nth-child(12) .employee-name {
                text-align: center !important;
            }
            .list-partial-content .table td .employee-name,
            .list-partial-content .dataTables_scrollBody .table td .employee-name {
                display: inline-block;
                width: 160px;
                max-width: 30vw;
                white-space: nowrap;
                overflow: visible;
                text-overflow: clip;
                margin: 0 auto;
            }
        </style>
        <table class="table table-striped table-hover list-partial-table" id="poTable">
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
                    <td colspan="12" class="text-center py-4 align-middle">
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

{{-- Modal preview dokumen PO (sama seperti Confirm PO Supporting Documents) --}}
<div class="modal fade" id="documentPreviewModal" tabindex="-1" aria-labelledby="documentPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-fullscreen-md-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="documentPreviewModalLabel">
                    <span id="documentPreviewFilename">Preview Document</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3 px-3">
                <div style="height:70vh;">
                    <iframe id="documentPreviewFrame" src="" style="width:100%;height:100%;border:0;" frameborder="0"></iframe>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary d-flex align-items-center" id="documentPreviewDownloadBtn" onclick="documentPreviewDownloadBtnClick()">
                    <i class="bx bx-download me-2"></i>
                    <span>Download</span>
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
