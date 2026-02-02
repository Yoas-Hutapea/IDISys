{{-- Invoice Detail Scripts Partial --}}
<script src="{{ asset('js/procurement/ProcurementSharedCache.js') }}"></script>
<script src="{{ asset('js/finance/InvoiceDetailAPI.js') }}"></script>
<script src="{{ asset('js/finance/InvoiceDetailManager.js') }}"></script>

<script>
    'use strict';

    document.addEventListener('DOMContentLoaded', async function () {
        const urlParams = new URLSearchParams(window.location.search);
        const invoiceId = urlParams.get('id');

        if (!invoiceId) {
            const loadingSection = document.getElementById('loadingSection');
            if (loadingSection) {
                loadingSection.innerHTML = `
                    <div class="alert alert-danger" role="alert">
                        <i class="icon-base bx bx-error-circle"></i>
                        Invoice ID is required
                    </div>
                `;
            }
            return;
        }

        if (typeof InvoiceDetailManager !== 'undefined') {
            const manager = new InvoiceDetailManager();
            await manager.init(invoiceId);
        } else {
            console.error('InvoiceDetailManager not found');
            const loadingSection = document.getElementById('loadingSection');
            if (loadingSection) {
                loadingSection.innerHTML = `
                    <div class="alert alert-danger" role="alert">
                        <i class="icon-base bx bx-error-circle"></i>
                        Failed to initialize Invoice Detail Manager
                    </div>
                `;
            }
        }
    });
</script>
