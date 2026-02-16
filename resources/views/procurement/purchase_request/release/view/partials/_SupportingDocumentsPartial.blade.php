{{--
    Supporting Documents Partial for Release View
    Shows supporting documents table with download actions
--}}

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
                    <tbody id="view-documents-tbody">
                        <!-- Documents will be dynamically populated -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Document preview modal -->
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
                    <!-- Use iframe to preview PDFs and many file types -->
                    <iframe id="documentPreviewFrame" src="" style="width:100%;height:100%;border:0;" frameborder="0"></iframe>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary d-flex align-items-center" id="documentPreviewDownloadBtn" onclick="downloadDocumentFromModal()">
                    <i class="bx bx-download me-2"></i>
                    <span>Download</span>
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>