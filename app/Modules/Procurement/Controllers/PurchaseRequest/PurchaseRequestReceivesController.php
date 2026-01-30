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
    public function grid(Request $request)
    {
        if (!$this->isProcurementUser()) {
            return response()->json([
                'draw' => (int) $request->input('draw', 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $draw = (int) $request->input('draw', 0);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        $baseQuery = $this->applyReceiveConstraints($this->baseQuery());
        $recordsTotal = (clone $baseQuery)->count('pr.ID');

        $filteredQuery = $this->applyFilters($baseQuery, $request);
        $recordsFiltered = (clone $filteredQuery)->count('pr.ID');

        $order = $request->input('order.0', []);
        $columns = $request->input('columns', []);
        $orderColumnIndex = $order['column'] ?? null;
        $orderDir = $order['dir'] ?? 'desc';

        if ($orderColumnIndex !== null && isset($columns[$orderColumnIndex]['data'])) {
            $columnKey = $columns[$orderColumnIndex]['data'];
            $orderColumn = $this->mapOrderColumn($columnKey);
            if ($orderColumn) {
                $filteredQuery->orderBy($orderColumn, $orderDir === 'asc' ? 'asc' : 'desc');
            }
        } else {
            $filteredQuery->orderBy('pr.CreatedDate', 'desc');
        }

        $rows = $filteredQuery
            ->skip($start)
            ->take($length)
            ->get();

        $data = $rows->map(fn ($row) => $this->mapRow($row))->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

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

    private function baseQuery()
    {
        $itemTotals = DB::table('trxPROPurchaseRequestItem')
            ->select('trxPROPurchaseRequestNumber', DB::raw('SUM(Amount) as TotalAmount'))
            ->where('IsActive', true)
            ->groupBy('trxPROPurchaseRequestNumber');

        return DB::table('trxPROPurchaseRequest as pr')
            ->leftJoinSub($itemTotals, 'items', function ($join) {
                $join->on('items.trxPROPurchaseRequestNumber', '=', 'pr.PurchaseRequestNumber');
            })
            ->leftJoin('mstPROPurchaseType as type', 'pr.mstPurchaseTypeID', '=', 'type.ID')
            ->leftJoin('mstPROPurchaseSubType as subType', 'pr.mstPurchaseSubTypeID', '=', 'subType.ID')
            ->leftJoin('mstApprovalStatus as status', 'pr.mstApprovalStatusID', '=', 'status.ID')
            ->where('pr.IsActive', true)
            ->select([
                'pr.ID',
                'pr.PurchaseRequestNumber as purchReqNumber',
                'pr.PurchaseRequestName as purchReqName',
                'pr.Requestor as requestor',
                'pr.Applicant as applicant',
                'pr.Company as company',
                'pr.mstPurchaseTypeID',
                'pr.mstPurchaseSubTypeID',
                'pr.Remark as remark',
                'pr.ReviewedBy as reviewedBy',
                'pr.ApprovedBy as approvedBy',
                'pr.ConfirmedBy as confirmedBy',
                'pr.UpdatedBy as updatedBy',
                'pr.mstApprovalStatusID',
                'pr.CreatedDate as createdDate',
                'type.PurchaseRequestType as purchaseRequestType',
                'type.Category as purchaseRequestCategory',
                'subType.PurchaseRequestSubType as purchaseRequestSubType',
                'status.ApprovalStatus as approvalStatus',
                DB::raw('COALESCE(items.TotalAmount, 0) as totalAmount'),
            ]);
    }

    private function applyReceiveConstraints($query)
    {
        return $query
            ->where('pr.mstApprovalStatusID', 4)
            ->whereNotExists(function ($subQuery) {
                $subQuery
                    ->select(DB::raw(1))
                    ->from('trxPROPurchaseOrder as po')
                    ->whereColumn('po.trxPROPurchaseRequestNumber', 'pr.PurchaseRequestNumber');
            });
    }

    private function applyFilters($query, Request $request)
    {
        $fromDate = $request->input('fromDate') ?? $request->input('startDate');
        $toDate = $request->input('toDate') ?? $request->input('endDate');
        $purchReqNum = $request->input('purchReqNum') ?? $request->input('prNumber');
        $purchReqName = $request->input('purchReqName') ?? $request->input('prName');
        $purchReqType = $request->input('purchReqType') ?? $request->input('prType');
        $purchReqSubType = $request->input('purchReqSubType') ?? $request->input('prSubType');
        $source = $request->input('source');

        if ($fromDate) {
            $query->whereDate('pr.CreatedDate', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('pr.CreatedDate', '<=', $toDate);
        }
        if ($purchReqNum) {
            $query->where('pr.PurchaseRequestNumber', 'like', '%' . trim((string) $purchReqNum) . '%');
        }
        if ($purchReqName) {
            $query->where('pr.PurchaseRequestName', 'like', '%' . trim((string) $purchReqName) . '%');
        }
        if ($purchReqType) {
            $query->where('pr.mstPurchaseTypeID', trim((string) $purchReqType));
        }
        if ($purchReqSubType) {
            $query->where('pr.mstPurchaseSubTypeID', trim((string) $purchReqSubType));
        }
        if ($source) {
            $query->where('pr.Company', strtoupper(trim((string) $source)));
        }

        return $query;
    }

    private function mapRow($row): array
    {
        $type = $row->purchaseRequestType ?? '';
        $category = $row->purchaseRequestCategory ?? '';
        $typeDisplay = $type;
        if ($category && $category !== $type) {
            $typeDisplay = trim($type . ' ' . $category);
        }

        $statusId = $row->mstApprovalStatusID ?? null;
        $pic = null;
        if ($statusId === 1) {
            $pic = $row->reviewedBy ?? null;
        } elseif ($statusId === 2) {
            $pic = $row->approvedBy ?? null;
        } elseif ($statusId === 3) {
            $pic = $row->confirmedBy ?? null;
        } elseif ($statusId === 7) {
            $pic = $row->updatedBy ?? null;
        }

        return [
            'purchReqNumber' => $row->purchReqNumber ?? null,
            'purchReqName' => $row->purchReqName ?? null,
            'purchReqType' => $typeDisplay,
            'purchReqSubType' => $row->purchaseRequestSubType ?? null,
            'approvalStatus' => $row->approvalStatus ?? null,
            'mstApprovalStatusID' => $row->mstApprovalStatusID ?? null,
            'pic' => $pic,
            'totalAmount' => $row->totalAmount ?? 0,
            'company' => $row->company ?? null,
            'requestor' => $row->requestor ?? null,
            'applicant' => $row->applicant ?? null,
            'createdDate' => $row->createdDate ?? null,
        ];
    }

    private function mapOrderColumn(?string $columnKey): ?string
    {
        return match ($columnKey) {
            'purchReqNumber' => 'pr.PurchaseRequestNumber',
            'purchReqName' => 'pr.PurchaseRequestName',
            'purchReqType' => 'type.PurchaseRequestType',
            'purchReqSubType' => 'subType.PurchaseRequestSubType',
            'approvalStatus' => 'status.ApprovalStatus',
            'pic' => 'pr.ConfirmedBy',
            'totalAmount' => 'totalAmount',
            'company' => 'pr.Company',
            'requestor' => 'pr.Requestor',
            'applicant' => 'pr.Applicant',
            'createdDate' => 'pr.CreatedDate',
            default => null,
        };
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

    private function isProcurementUser(): bool
    {
        $positionName = $this->getCurrentPositionName();
        if ($positionName === '') {
            return false;
        }

        return strcasecmp($positionName, 'Procurement Staff') === 0
            || strcasecmp($positionName, 'Procurement Team Leader') === 0;
    }
}
