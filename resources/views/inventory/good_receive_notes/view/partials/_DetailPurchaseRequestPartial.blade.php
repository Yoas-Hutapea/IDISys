{{--
    Detail Good Receive Note: kolom sesuai tabel GRN.
    Cell yang bisa diisi: ActualReceived (qty diterima, auto-update Amount), Receive Date, Remark.
--}}
<div class="col-12">
    <div class="card">
        <div class="card-header" style="border-bottom: 1px solid #e9ecef;">
            <h6 class="card-title mb-0">Detail Good Receive Note</h6>
        </div>
        <div class="card-body">
            <style>
                #grn-items-table { table-layout: auto; }
                /* Amount column - lebar cukup agar nilai tidak ter-hidden */
                #grn-items-table th:nth-child(11),
                #grn-items-table td:nth-child(11) {
                    min-width: 140px;
                    white-space: nowrap;
                    text-align: right !important;
                }
                /* Receive Date column (12) */
                #grn-items-table th:nth-child(12),
                #grn-items-table td:nth-child(12) {
                    min-width: 130px;
                }
                /* Remark column (13) - lebar cukup dan teks boleh wrap */
                #grn-items-table th:nth-child(13),
                #grn-items-table td:nth-child(13) {
                    min-width: 220px;
                    max-width: 320px;
                    white-space: normal;
                    word-wrap: break-word;
                }
                #grn-items-table td:nth-child(13) input.form-control,
                #grn-items-table td:nth-child(13) textarea.form-control {
                    min-width: 200px;
                    width: 100%;
                }
            </style>
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="grn-items-table">
                    <thead class="table-light">
                        <tr>
                            <th>PO Number</th>
                            <th>Item ID</th>
                            <th>Item Name</th>
                            <th>Description</th>
                            <th>UoM</th>
                            <th class="text-end">Item Qty</th>
                            <th class="text-end">Actual Received</th>
                            <th class="text-end">Qty Remain</th>
                            <th>Currency</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Amount</th>
                            <th>Receive Date</th>
                            <th>Remark</th>
                        </tr>
                    </thead>
                    <tbody id="grn-items-tbody">
                    </tbody>
                </table>
            </div>
            <div class="row mt-3">
                <div class="col-12 text-end">
                    <label class="form-label fw-semibold me-2">Amount Total:</label>
                    <input type="text" class="form-control d-inline-block text-end" id="grn-amount-total" value="0" disabled style="width: 200px;">
                </div>
            </div>
        </div>
    </div>
</div>
