{{-- Invoice Create Scripts Partial --}}
<script src="{{ asset('js/procurement/ProcurementSharedCache.js') }}"></script>

<script src="{{ asset('js/finance/InvoiceCreateUtils.js') }}"></script>
<script src="{{ asset('js/finance/InvoiceCreateAPI.js') }}"></script>
<script src="{{ asset('js/finance/InvoiceCreateForm.js') }}"></script>
<script src="{{ asset('js/finance/InvoiceCreatePO.js') }}"></script>
<script src="{{ asset('js/finance/InvoiceCreateTerm.js') }}"></script>
<script src="{{ asset('js/finance/InvoiceCreateDocument.js') }}"></script>
<script src="{{ asset('js/finance/InvoiceCreateSave.js') }}"></script>

<script>
    'use strict';

    class InvoiceCreateManager {
        constructor() {
            this.currentInvoiceId = null;
            this.selectedPeriods = [];

            if (typeof InvoiceCreateAPI !== 'undefined') {
                this.apiModule = new InvoiceCreateAPI();
            }
            if (typeof InvoiceCreateForm !== 'undefined') {
                this.formModule = new InvoiceCreateForm(this);
            }
            if (typeof InvoiceCreatePO !== 'undefined') {
                this.poModule = new InvoiceCreatePO(this);
            }
            if (typeof InvoiceCreateTerm !== 'undefined') {
                this.termModule = new InvoiceCreateTerm(this);
            }
            if (typeof InvoiceCreateDocument !== 'undefined') {
                this.documentModule = new InvoiceCreateDocument(this);
            }
            if (typeof InvoiceCreateSave !== 'undefined') {
                this.saveModule = new InvoiceCreateSave(this);
            }

            this.init();
        }

        async init() {
            if (this.formModule) {
                this.formModule.setupValidation();
                this.formModule.setupEventHandlers();
            }

            this.bindEvents();

            if (this.formModule) {
                await this.formModule.loadTaxList();
            }

            const urlParams = new URLSearchParams(window.location.search);
            this.currentInvoiceId = urlParams.get('id');

            if (this.currentInvoiceId) {
                if (this.saveModule) {
                    await this.saveModule.loadInvoiceData(this.currentInvoiceId);
                }
            }

            @if (!empty($currentUserName))
                window.currentUserName = @json($currentUserName);
            @endif
            @if (!empty($currentUserEmail))
                window.currentUserEmail = @json($currentUserEmail);
            @endif

            if (this.formModule) {
                this.formModule.loadCurrentUserInfo();
            }

            if (this.poModule) {
                this.poModule.initializeChoosePOTable();
            }
        }

        bindEvents() {
            window.invoiceCreateManager = this;

            if (typeof selectPO === 'undefined') {
                window.selectPO = (poNumber) => {
                    if (window.invoiceCreateManager && window.invoiceCreateManager.poModule && window.invoiceCreateManager.poModule.selectPO) {
                        return window.invoiceCreateManager.poModule.selectPO(poNumber);
                    }
                };
            }

            if (typeof openChoosePOModal === 'undefined') {
                window.openChoosePOModal = () => {
                    if (window.invoiceCreateManager && window.invoiceCreateManager.poModule && window.invoiceCreateManager.poModule.openChoosePOModal) {
                        return window.invoiceCreateManager.poModule.openChoosePOModal();
                    }
                };
            }

            if (typeof submitInvoice === 'undefined') {
                window.submitInvoice = async () => {
                    if (window.invoiceCreateManager && window.invoiceCreateManager.saveModule && window.invoiceCreateManager.saveModule.submitInvoice) {
                        return await window.invoiceCreateManager.saveModule.submitInvoice();
                    }
                };
            }

            $('#btnSearchPO').on('click', () => {
                if (this.poModule && this.poModule.openChoosePOModal) {
                    this.poModule.openChoosePOModal();
                }
            });

            $('#invoiceForm').on('submit', async (e) => {
                e.preventDefault();
                e.stopPropagation();

                if (!e.target.checkValidity()) {
                    $(e.target).addClass('was-validated');
                    return;
                }

                const selectedPO = this.poModule?.selectedPO || null;
                if (!selectedPO) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Warning',
                        text: 'Please select a Purchase Order first'
                    });
                    return;
                }

                if (this.saveModule && this.saveModule.submitInvoice) {
                    await this.saveModule.submitInvoice();
                }
            });
        }

        async openChoosePOModal() {
            if (this.poModule && this.poModule.openChoosePOModal) {
                return await this.poModule.openChoosePOModal();
            }
        }

        async selectPO(poNumber) {
            if (this.poModule && this.poModule.selectPO) {
                return await this.poModule.selectPO(poNumber);
            }
        }

        calculateTax() {
            if (this.formModule && this.formModule.calculateTax) {
                return this.formModule.calculateTax();
            }
        }

        calculateInvoiceAmount() {
            if (this.formModule && this.formModule.calculateInvoiceAmount) {
                return this.formModule.calculateInvoiceAmount();
            }
        }

        async loadTermOfPaymentGrid() {
            if (this.termModule && this.termModule.loadTermOfPaymentGrid) {
                return await this.termModule.loadTermOfPaymentGrid();
            }
        }

        selectTermOfPayment(termNumber, termValue, isEligible) {
            if (this.termModule && this.termModule.selectTermOfPayment) {
                return this.termModule.selectTermOfPayment(termNumber, termValue, isEligible);
            }
        }

        async loadPeriodOfPaymentGrid() {
            if (this.termModule && this.termModule.loadPeriodOfPaymentGrid) {
                return await this.termModule.loadPeriodOfPaymentGrid();
            }
        }

        selectPeriodOfPayment(periodNumber, termValue, isEligible) {
            if (this.termModule && this.termModule.selectPeriodOfPayment) {
                return this.termModule.selectPeriodOfPayment(periodNumber, termValue, isEligible);
            }
        }

        async submitInvoice() {
            if (this.saveModule && this.saveModule.submitInvoice) {
                return await this.saveModule.submitInvoice();
            }
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        window.invoiceCreateManager = new InvoiceCreateManager();
    });
</script>
