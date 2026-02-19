<?php

namespace App\Jobs;

use App\Modules\Procurement\Controllers\PurchaseOrderController;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Send PO Released notification email after Approval PO 2 (status 11).
 * Runs in the background so the approval API can return immediately.
 */
class SendPOReleasedEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $poNumber,
        public string $remark
    ) {}

    public function handle(): void
    {
        $controller = app(PurchaseOrderController::class);
        $controller->runSendPOReleasedEmail($this->poNumber, $this->remark);
    }
}
