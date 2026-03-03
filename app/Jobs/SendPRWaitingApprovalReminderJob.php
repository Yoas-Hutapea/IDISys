<?php

namespace App\Jobs;

use App\Mail\PRWaitingApprovalReminderMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * Send PR Waiting Approval reminder email to the PIC (ReviewedBy or ApprovedBy)
 * when PR is in status 1 (Waiting Approval Diketahui) or 2 (Waiting Approval Disetujui).
 */
class SendPRWaitingApprovalReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int 1 = remind Reviewer (ReviewedBy), 2 = remind Approver (ApprovedBy) */
    public function __construct(
        public string $prNumber,
        public int $statusId
    ) {}

    public function handle(): void
    {
        if (! Schema::hasTable('trxPROPurchaseRequest')) {
            return;
        }

        $pr = DB::table('trxPROPurchaseRequest')
            ->where('PurchaseRequestNumber', $this->prNumber)
            ->first();

        if (! $pr) {
            return;
        }

        $employeeId = null;
        $statusLabel = '';
        $recipientRole = '';

        if ($this->statusId === 1) {
            $employeeId = trim((string) ($pr->ReviewedBy ?? ''));
            $statusLabel = 'Waiting Approval Diketahui';
            $recipientRole = 'Approval Diketahui (Review PR)';
        } elseif ($this->statusId === 2) {
            $employeeId = trim((string) ($pr->ApprovedBy ?? ''));
            $statusLabel = 'Waiting Approval Disetujui';
            $recipientRole = 'Approval Disetujui (Approve PR)';
        } else {
            return;
        }

        if ($employeeId === '') {
            return;
        }

        $email = $this->getEmployeeEmail($employeeId);
        $recipientName = $this->getEmployeeName($employeeId);
        if ($recipientName === '') {
            $recipientName = $employeeId;
        }

        $testTo = $this->getTestNotificationEmail();
        if ($testTo !== null) {
            Mail::to($testTo)->send(new PRWaitingApprovalReminderMail(
                prNumber: $this->prNumber,
                statusLabel: $statusLabel,
                recipientRole: $recipientRole,
                recipientName: $recipientName
            ));
            return;
        }

        if ($email === null || $email === '') {
            return;
        }

        Mail::to($email)->send(new PRWaitingApprovalReminderMail(
            prNumber: $this->prNumber,
            statusLabel: $statusLabel,
            recipientRole: $recipientRole,
            recipientName: $recipientName
        ));
    }

    private function getEmployeeEmail(string $employeeId): ?string
    {
        if (! Schema::hasTable('mstEmployee')) {
            return null;
        }

        $employee = DB::table('mstEmployee')
            ->where('Employ_Id', $employeeId)
            ->orWhere('Employ_Id_TBGSYS', $employeeId)
            ->first();

        if (! $employee) {
            return null;
        }

        $email = $employee->Email ?? $employee->email ?? null;
        return $email !== null && trim((string) $email) !== '' ? trim((string) $email) : null;
    }

    private function getEmployeeName(string $employeeId): string
    {
        if (! Schema::hasTable('mstEmployee')) {
            return '';
        }

        $employee = DB::table('mstEmployee')
            ->where('Employ_Id', $employeeId)
            ->orWhere('Employ_Id_TBGSYS', $employeeId)
            ->first();

        if (! $employee) {
            return '';
        }

        $name = $employee->name ?? $employee->Name ?? $employee->nick_name ?? '';
        return trim((string) $name);
    }

    private function getTestNotificationEmail(): ?string
    {
        $testTo = config('mail.test_to');
        if (is_string($testTo) && trim($testTo) !== '') {
            return trim($testTo);
        }
        $appUrl = strtolower((string) (config('app.url') ?? ''));
        if (str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1')) {
            return 'yoas.hutapea@ideanet.net.id';
        }
        return null;
    }
}
