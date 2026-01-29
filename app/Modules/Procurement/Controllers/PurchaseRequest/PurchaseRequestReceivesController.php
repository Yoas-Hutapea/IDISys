<?php

namespace App\Modules\Procurement\Controllers\PurchaseRequest;

use App\Http\Controllers\Controller;
use App\Models\MstEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurchaseRequestReceivesController extends Controller
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

        $decision = (string) ($request->input('Decision') ?? '');
        $decisionNormalized = $this->normalizeDecision($decision);
        $statusId = $this->resolveStatusId(
            $decisionNormalized,
            (int) ($request->input('mstApprovalStatusID') ?? 0)
        );

        DB::table('trxPROPurchaseRequest')
            ->where('PurchaseRequestNumber', $decodedNumber)
            ->update([
                'mstApprovalStatusID' => $statusId,
                'UpdatedBy' => $employeeId !== '' ? $employeeId : 'System',
                'UpdatedDate' => $now,
            ]);

        $this->insertReceiveLogIfAvailable([
            'mstEmployeeID' => $employeeId !== '' ? $employeeId : 'System',
            'mstEmployeePositionName' => $positionName,
            'trxPROPurchaseRequestNumber' => $decodedNumber,
            'Activity' => (string) $request->input('Activity'),
            'mstApprovalStatusID' => (string) $statusId,
            'Remark' => (string) $request->input('Remark'),
            'Decision' => $decisionNormalized,
            'CreatedBy' => $employeeId !== '' ? $employeeId : 'System',
            'CreatedDate' => $now,
            'IsActive' => true,
        ]);

        return response()->json([
            'message' => 'Receive submitted',
            'statusId' => $statusId,
        ]);
    }

    public function bulk(Request $request)
    {
        $prNumbers = $request->input('prNumbers', []);
        if (!is_array($prNumbers)) {
            $prNumbers = [];
        }

        $decision = (string) ($request->input('Decision') ?? '');
        $decisionNormalized = $this->normalizeDecision($decision);
        $statusId = $this->resolveStatusId(
            $decisionNormalized,
            (int) ($request->input('mstApprovalStatusID') ?? 0)
        );

        $now = now();
        $employeeId = $this->getCurrentEmployeeId();
        $positionName = $this->getCurrentPositionName();
        $updated = 0;
        $notFound = [];

        foreach ($prNumbers as $number) {
            $decodedNumber = urldecode((string) $number);
            if ($decodedNumber === '') {
                continue;
            }

            $exists = DB::table('trxPROPurchaseRequest')
                ->where('PurchaseRequestNumber', $decodedNumber)
                ->exists();

            if (!$exists) {
                $notFound[] = $decodedNumber;
                continue;
            }

            DB::table('trxPROPurchaseRequest')
                ->where('PurchaseRequestNumber', $decodedNumber)
                ->update([
                    'mstApprovalStatusID' => $statusId,
                    'UpdatedBy' => $employeeId !== '' ? $employeeId : 'System',
                    'UpdatedDate' => $now,
                ]);

            $this->insertReceiveLogIfAvailable([
                'mstEmployeeID' => $employeeId !== '' ? $employeeId : 'System',
                'mstEmployeePositionName' => $positionName,
                'trxPROPurchaseRequestNumber' => $decodedNumber,
                'Activity' => (string) $request->input('Activity'),
                'mstApprovalStatusID' => (string) $statusId,
                'Remark' => (string) $request->input('Remark'),
                'Decision' => $decisionNormalized,
                'CreatedBy' => $employeeId !== '' ? $employeeId : 'System',
                'CreatedDate' => $now,
                'IsActive' => true,
            ]);

            $updated++;
        }

        return response()->json([
            'message' => 'Bulk receive submitted',
            'updated' => $updated,
            'notFound' => $notFound,
        ]);
    }

    private function normalizeDecision(string $decision): string
    {
        $decision = trim($decision);
        if ($decision === '') {
            return 'Receive';
        }

        $lower = strtolower($decision);
        if ($lower === 'reject' || $lower === 'rejected') {
            return 'Reject';
        }

        return ucfirst($decision);
    }

    private function resolveStatusId(string $decision, int $statusFromRequest): int
    {
        if ($decision === 'Reject') {
            return 5;
        }

        if ($decision === 'Receive') {
            return 7;
        }

        return $statusFromRequest > 0 ? $statusFromRequest : 7;
    }

    private function insertReceiveLogIfAvailable(array $data): void
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
