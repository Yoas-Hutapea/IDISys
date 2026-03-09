<div id="viewGRNContent" class="p-4">
    <div id="viewGRNLoading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="mt-3">Loading Good Receive Note details...</div>
    </div>

    <div id="viewGRNData" style="display: none;">
        <div class="row g-4">
            @include('inventory.good_receive_notes.view.partials._PurchaseInformationPartial')
            @include('inventory.good_receive_notes.view.partials._AdditionalInformationPartial')
            @include('inventory.good_receive_notes.view.partials._VendorInformationPartial')
            @include('inventory.good_receive_notes.view.partials._DetailPurchaseRequestPartial')

            <div class="col-12">
                <div class="card">
                    <div class="card-header" style="border-bottom: 1px solid #e9ecef;">
                        <h6 class="card-title mb-0">Supporting Documents</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>File Name</th>
                                        <th>File Size (kb)</th>
                                        <th width="120" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="grnReadOnlyDocumentsTbody">
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No documents</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="grnReadOnlyDocumentPreviewModal" tabindex="-1" aria-labelledby="grnReadOnlyDocumentPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-fullscreen-md-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="grnReadOnlyDocumentPreviewModalLabel">
                    <span id="grnReadOnlyDocumentPreviewFilename">Preview Document</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3 px-3">
                <div style="height:70vh;">
                    <iframe id="grnReadOnlyDocumentPreviewFrame" src="" style="width:100%;height:100%;border:0;" frameborder="0"></iframe>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary d-flex align-items-center" onclick="grnHeaderDownloadDocumentFromModal()">
                    <i class="bx bx-download me-2"></i>
                    <span>Download</span>
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
