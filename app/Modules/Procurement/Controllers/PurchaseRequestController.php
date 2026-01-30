<?php

namespace App\Modules\Procurement\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Procurement\Services\TaskToDoService;
use App\Models\MstEmployee;
use App\Models\TrxProPurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseRequestController extends Controller
{
    public function __construct(private readonly TaskToDoService $taskToDoService)
    {
    }

    public function approvals()
    {
        $userIdentifiers = $this->getCurrentUserIdentifiers();
        if (empty($userIdentifiers)) {
            return response()->json([]);
        }

        $query = $this->taskToDoService
            ->getPurchaseRequestsByStatusQuery([1, 2, 3]);

        $query->where(function ($statusQuery) use ($userIdentifiers) {
            $statusQuery
                ->where(function ($reviewQuery) use ($userIdentifiers) {
                    $reviewQuery
                        ->where('mstApprovalStatusID', 1)
                        ->whereIn('ReviewedBy', $userIdentifiers);
                })
                ->orWhere(function ($approveQuery) use ($userIdentifiers) {
                    $approveQuery
                        ->where('mstApprovalStatusID', 2)
                        ->whereIn('ApprovedBy', $userIdentifiers);
                })
                ->orWhere(function ($confirmQuery) use ($userIdentifiers) {
                    $confirmQuery
                        ->where('mstApprovalStatusID', 3)
                        ->whereIn('ConfirmedBy', $userIdentifiers);
                });
        });

        return response()->json($query->get());
    }

    public function receives()
    {
        if (!$this->isProcurementUser()) {
            return response()->json([]);
        }

        $purchaseRequests = $this->taskToDoService->getPurchaseRequestsByStatus([4]);
        if ($purchaseRequests->isEmpty()) {
            return response()->json([]);
        }

        $prNumbers = $purchaseRequests
            ->pluck('PurchaseRequestNumber')
            ->filter(fn ($number) => !empty($number))
            ->values();

        if ($prNumbers->isEmpty()) {
            return response()->json($purchaseRequests);
        }

        $prNumbersWithPO = TrxProPurchaseOrder::query()
            ->whereIn('trxPROPurchaseRequestNumber', $prNumbers)
            ->pluck('trxPROPurchaseRequestNumber')
            ->filter(fn ($number) => !empty($number))
            ->all();

        if (empty($prNumbersWithPO)) {
            return response()->json($purchaseRequests);
        }

        $filtered = $purchaseRequests
            ->filter(fn ($pr) => !in_array($pr->PurchaseRequestNumber, $prNumbersWithPO, true))
            ->values();

        return response()->json($filtered);
    }

    public function releases()
    {
        $employeeId = $this->getCurrentEmployeeId();
        if ($employeeId === '') {
            return response()->json([]);
        }

        $query = $this->taskToDoService
            ->getPurchaseRequestsByStatusQuery([7])
            ->where('UpdatedBy', $employeeId);

        return response()->json($query->get());
    }

    public function index(Request $request)
    {
        $statusId = $request->query('statusPR');
        $statusId = is_numeric($statusId) ? (int) $statusId : null;

        if ($statusId === 5) {
            $employeeId = $this->getCurrentEmployeeId();
            if ($employeeId === '') {
                return response()->json([]);
            }

            $query = $this->taskToDoService
                ->getPurchaseRequestsByStatusQuery([5])
                ->where('CreatedBy', $employeeId);

            return response()->json($query->get());
        }

        return response()->json(
            $this->taskToDoService->getPurchaseRequestsByStatusSingle($statusId)
        );
    }

    private function getEmployeeFromSession(): ?MstEmployee
    {
        $employee = session('employee');
        return $employee instanceof MstEmployee ? $employee : null;
    }

    private function getCurrentEmployeeId(): string
    {
        $employee = $this->getEmployeeFromSession();
        if ($employee && !empty($employee->Employ_Id)) {
            return $employee->Employ_Id;
        }

        return (string) optional(Auth::user())->Username;
    }

    private function getCurrentUserIdentifiers(): array
    {
        $employee = $this->getEmployeeFromSession();
        $ids = [];

        if ($employee) {
            if (!empty($employee->Employ_Id)) {
                $ids[] = $employee->Employ_Id;
            }
            if (!empty($employee->Employ_Id_TBGSYS)) {
                $ids[] = $employee->Employ_Id_TBGSYS;
            }
        }

        $username = (string) optional(Auth::user())->Username;
        if ($username !== '') {
            $ids[] = $username;
        }

        return array_values(array_unique(array_filter($ids, fn ($id) => $id !== '')));
    }

    private function isProcurementUser(): bool
    {
        $employee = $this->getEmployeeFromSession();
        $positionName = $employee?->PositionName ?? $employee?->JobTitleName ?? '';

        return $positionName !== '' && (
            strcasecmp($positionName, 'Procurement Staff') === 0 ||
            strcasecmp($positionName, 'Procurement Team Leader') === 0
        );
    }
}
