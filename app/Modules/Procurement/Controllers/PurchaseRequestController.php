<?php

namespace App\Modules\Procurement\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Procurement\Services\TaskToDoService;
use App\Models\MstEmployee;
use Illuminate\Http\Request;

class PurchaseRequestController extends Controller
{
    public function __construct(private readonly TaskToDoService $taskToDoService)
    {
    }

    public function approvals()
    {
        if (!$this->isProcurementUser() && !$this->isDepartmentHead()) {
            return response()->json([]);
        }

        $query = $this->taskToDoService
            ->getPurchaseRequestsByStatusQuery([1, 2, 3]);

        $allowedCreatedBy = $this->getAllowedCreatedByIds();
        if (!empty($allowedCreatedBy)) {
            $query->whereIn('CreatedBy', $allowedCreatedBy);
        }

        return response()->json($query->get());
    }

    public function receives()
    {
        if (!$this->isProcurementUser()) {
            return response()->json([]);
        }

        return response()->json(
            $this->taskToDoService->getPurchaseRequestsByStatus([4])
        );
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

        return (string) optional(auth()->user())->Username;
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

    private function isDepartmentHead(): bool
    {
        $employee = $this->getEmployeeFromSession();
        $jobTitle = $employee?->JobTitleName ?? '';

        return $jobTitle !== '' && (
            stripos($jobTitle, 'Dept Head') !== false ||
            stripos($jobTitle, 'Department Head') !== false
        );
    }

    private function getAllowedCreatedByIds(): array
    {
        if ($this->isProcurementUser()) {
            return [];
        }

        $employee = $this->getEmployeeFromSession();
        $currentId = $this->getCurrentEmployeeId();
        if ($currentId === '') {
            return [];
        }

        if (!$this->isDepartmentHead()) {
            return [$currentId];
        }

        $ids = [$currentId];
        $subordinates = MstEmployee::query()
            ->where('Report_Code', $currentId)
            ->get();

        foreach ($subordinates as $sub) {
            if (!empty($sub->Employ_Id)) {
                $ids[] = $sub->Employ_Id;
            }
            if (!empty($sub->Employ_Id_TBGSYS)) {
                $ids[] = $sub->Employ_Id_TBGSYS;
            }
        }

        return array_values(array_unique($ids));
    }
}
