<?php

namespace App\Modules\Procurement\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TrxProPurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderController extends Controller
{
    public function grid(Request $request)
    {
        $statusIds = $request->input('mstApprovalStatusIDs', []);
        $statusIds = is_array($statusIds) ? $statusIds : [];

        $query = TrxProPurchaseOrder::query()->where('IsActive', true);
        if (!empty($statusIds)) {
            $allowedStatuses = $this->filterAllowedStatuses($statusIds);
            if (empty($allowedStatuses)) {
                return response()->json([
                    'data' => [],
                    'RecordsTotal' => 0,
                    'RecordsFiltered' => 0,
                    'draw' => (int) $request->input('draw', 1),
                ]);
            }
            $query->whereIn('mstApprovalStatusID', $allowedStatuses);

            if (in_array(8, $allowedStatuses, true) || in_array(12, $allowedStatuses, true)) {
                $employeeId = $this->getCurrentEmployeeId();
                if ($employeeId !== '') {
                    $query->where('CreatedBy', $employeeId);
                }
            }
        }

        $total = $query->count();

        $length = (int) $request->input('length', $total);
        $start = (int) $request->input('start', 0);

        $data = $query
            ->orderByDesc('ID')
            ->skip($start)
            ->take($length > 0 ? $length : $total)
            ->get();

        return response()->json([
            'data' => $data,
            'RecordsTotal' => $total,
            'RecordsFiltered' => $total,
            'draw' => (int) $request->input('draw', 1),
        ]);
    }

    private function filterAllowedStatuses(array $statusIds): array
    {
        $positionName = $this->getPositionName();

        $isAccountPayableManager = $positionName !== '' &&
            strcasecmp($positionName, 'Account Payable, Treasury & Revenue Assurance Manager') === 0;
        $isFinanceDivisionHead = $positionName !== '' &&
            strcasecmp($positionName, 'Finance & Treasury Division Head') === 0;

        $allowed = [];
        foreach ($statusIds as $statusId) {
            if ($statusId == 9 || $statusId == 10) {
                if ($isAccountPayableManager && $statusId == 9) {
                    $allowed[] = 9;
                } elseif ($isFinanceDivisionHead && $statusId == 10) {
                    $allowed[] = 10;
                }
                continue;
            }
            $allowed[] = (int) $statusId;
        }

        return array_values(array_unique($allowed));
    }

    private function getPositionName(): string
    {
        $employee = session('employee');
        return $employee?->PositionName ?? $employee?->JobTitleName ?? '';
    }

    private function getCurrentEmployeeId(): string
    {
        $employee = session('employee');
        if ($employee && !empty($employee->Employ_Id)) {
            return $employee->Employ_Id;
        }

        return (string) optional(Auth::user())->Username;
    }
}
