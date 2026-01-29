<?php

namespace App\Modules\Procurement\Controllers\PurchaseRequest;

use App\Http\Controllers\Controller;
use App\Models\MstEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurchaseRequestApprovalsController extends Controller
{
    public function store(string $prNumber, Request $request)
    {
        $decodedNumber = urldecode($prNumber);
        $now = now();
        $employeeId = $this->getCurrentEmployeeId();
        $positionName = $this->getCurrentPositionName();

        $purchaseRequest = DB::table('trxPROPurchaseRequest')
            ->where('PurchaseRequestNumber', $decodedNumber)
            ->first();

        if (!$purchaseRequest) {
            return response()->json(['message' => 'Purchase Request not found'], 404);
        }

        $currentStatus = (int) ($purchaseRequest->mstApprovalStatusID ?? 0);
        $decision = (string) ($request->input('Decision') ?? '');
        $approvalStatusId = (int) ($request->input('mstApprovalStatusID') ?? $currentStatus);

        $decisionNormalized = $this->normalizeDecision($decision);
        $nextStatus = $this->resolveNextStatus($decisionNormalized, $currentStatus, $approvalStatusId);

        $updateData = [
            'mstApprovalStatusID' => $nextStatus,
            'UpdatedBy' => $employeeId,
            'UpdatedDate' => $now,
        ];

        if ($decisionNormalized === 'Approved') {
            if ($currentStatus === 1) {
                $updateData['ReviewedBy'] = $employeeId;
            } elseif ($currentStatus === 2) {
                $updateData['ApprovedBy'] = $employeeId;
            } elseif ($currentStatus === 3) {
                $updateData['ConfirmedBy'] = $employeeId;
            }
        }

        DB::table('trxPROPurchaseRequest')
            ->where('PurchaseRequestNumber', $decodedNumber)
            ->update($updateData);

        $this->insertApprovalLogIfAvailable([
            'mstEmployeeID' => $employeeId !== '' ? $employeeId : 'System',
            'mstEmployeePositionName' => $positionName,
            'trxPROPurchaseRequestNumber' => $decodedNumber,
            'Activity' => (string) $request->input('Activity'),
            'mstApprovalStatusID' => (string) ($approvalStatusId ?: $currentStatus),
            'Remark' => (string) $request->input('Remark'),
            'Decision' => $decisionNormalized,
            'CreatedBy' => $employeeId !== '' ? $employeeId : 'System',
            'CreatedDate' => $now,
            'IsActive' => true,
        ]);

        return response()->json([
            'message' => 'Approval submitted',
            'nextStatus' => $nextStatus,
        ]);
    }

    public function history(string $prNumber)
    {
        $decodedNumber = urldecode($prNumber);

        if (!Schema::hasTable('logPROPurchaseRequest')) {
            return response()->json([]);
        }

        $logs = DB::table('logPROPurchaseRequest')
            ->where('trxPROPurchaseRequestNumber', $decodedNumber)
            ->where('IsActive', true)
            ->orderByDesc('CreatedDate')
            ->get([
                'CreatedDate',
                'Activity',
                'Decision',
                'mstEmployeeID',
                'mstEmployeePositionName',
                'Remark',
            ])
            ->map(function ($row) {
                return [
                    'CreatedDate' => $row->CreatedDate,
                    'Activity' => $row->Activity,
                    'Decision' => $row->Decision,
                    'mstEmployeeID' => $row->mstEmployeeID,
                    'mstEmployeePositionID' => $row->mstEmployeePositionName,
                    'Remark' => $row->Remark,
                ];
            });

        return response()->json($logs);
    }

    private function normalizeDecision(string $decision): string
    {
        $decision = trim($decision);
        if ($decision === '') {
            return 'Approved';
        }

        $lower = strtolower($decision);
        if ($lower === 'reject' || $lower === 'rejected') {
            return 'Rejected';
        }
        if ($lower === 'reviewed' || $lower === 'approved' || $lower === 'confirmed') {
            return 'Approved';
        }

        return ucfirst($decision);
    }

    private function resolveNextStatus(string $decision, int $currentStatus, int $statusFromRequest): int
    {
        if ($decision === 'Rejected') {
            return 5;
        }

        if ($decision === 'Approved') {
            return match ($currentStatus) {
                1 => 2,
                2 => 3,
                3 => 4,
                default => max(1, $statusFromRequest),
            };
        }

        if ($statusFromRequest > 0) {
            return $statusFromRequest;
        }

        return $currentStatus > 0 ? $currentStatus : 1;
    }

    private function insertApprovalLogIfAvailable(array $data): void
    {
        if (!Schema::hasTable('logPROPurchaseRequest')) {
            return;
        }

        DB::table('logPROPurchaseRequest')->insert($data);
    }

    private function getCurrentEmployeeId(): string
    {
        $employee = session('employee');
        if ($employee instanceof MstEmployee && !empty($employee->Employ_Id)) {
            return $employee->Employ_Id;
        }

        return (string) optional(Auth::user())->Username;
    }

    private function getCurrentPositionName(): string
    {
        $employee = session('employee');
        if ($employee instanceof MstEmployee) {
            $positionName = $employee->PositionName ?? $employee->JobTitleName ?? '';
            return (string) $positionName;
        }

        return '';
    }
}
