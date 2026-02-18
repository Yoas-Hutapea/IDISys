{{--
    Detail Good Receive Note: kolom sesuai tabel GRN.
    Cell yang bisa diisi: ActualReceived (qty diterima, auto-update Amount), Remark, TanggalTerima.
--}}
<div class="col-12">
    <div class="card">
        <div class="card-header" style="border-bottom: 1px solid #e9ecef;">
            <h6 class="card-title mb-0">Detail Good Receive Note</h6>
        </div>
        <div class="card-body">
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
                            <th>Currency</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Amount</th>
                            <th>Remark</th>
                            <th>Tanggal Terima</th>
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
