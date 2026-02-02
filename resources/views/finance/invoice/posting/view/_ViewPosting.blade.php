<div id="viewPostingContent" class="p-4">
    <div id="viewPostingLoading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div class="mt-3">Loading Invoice details...</div>
    </div>

    <div id="viewPostingData" style="display: none;">
        <div class="row g-4">
            @include('finance.invoice.posting.view.partials._POInformationPartial')
            @include('finance.invoice.posting.view.partials._VendorInformationPartial')
            @include('finance.invoice.posting.view.partials._ItemListPartial')
            @include('finance.invoice.posting.view.partials._TermOfPaymentPartial')
            @include('finance.invoice.posting.view.partials._PeriodOfPaymentPartial')
            @include('finance.invoice.posting.view.partials._InvoiceBasicInformationPartial')
            @include('finance.invoice.posting.view.partials._InvoiceAmountSummaryPartial')
            @include('finance.invoice.posting.view.partials._DocumentChecklistPartial')
            @include('finance.invoice.posting.view.partials._PostingActionPartial')
        </div>
    </div>
</div>
