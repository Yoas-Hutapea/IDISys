<div id="viewInvoiceContent" class="p-4">
    <div id="viewInvoiceLoading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div class="mt-3">Loading Invoice details...</div>
    </div>

    <div id="viewInvoiceData" style="display: none;">
        <div class="row g-4">
            @include('finance.invoice.list.view_invoice.partials._POInformationPartial')
            @include('finance.invoice.list.view_invoice.partials._VendorInformationPartial')
            @include('finance.invoice.list.view_invoice.partials._ItemListPartial')
            @include('finance.invoice.list.view_invoice.partials._TermOfPaymentPartial')
            @include('finance.invoice.list.view_invoice.partials._PeriodOfPaymentPartial')
            @include('finance.invoice.list.view_invoice.partials._InvoiceBasicInformationPartial')
            @include('finance.invoice.list.view_invoice.partials._InvoiceAmountSummaryPartial')
            @include('finance.invoice.list.view_invoice.partials._DocumentChecklistPartial')
        </div>
    </div>
</div>
