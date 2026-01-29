<?php

namespace App\Modules\Procurement\Controllers\PurchaseRequest;

use App\Http\Controllers\Controller;
use App\Models\MstEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurchaseRequestReleasesController extends Controller
{
    public function grid(Request $request)
    {
        $draw = (int) $request->input('draw', 0);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        $baseQuery = $this->baseQuery();
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
        $prStatusId = $decisionNormalized === 'Release' ? 4 : 5;
        $poStatusId = $decisionNormalized === 'Release' ? 8 : 5;

        DB::table('trxPROPurchaseRequest')
            ->where('PurchaseRequestNumber', $decodedNumber)
            ->update([
                'mstApprovalStatusID' => $prStatusId,
                'UpdatedBy' => $employeeId !== '' ? $employeeId : 'System',
                'UpdatedDate' => $now,
            ]);

        $logData = [
            'mstEmployeeID' => $employeeId !== '' ? $employeeId : 'System',
            'mstEmployeePositionName' => $positionName,
            'trxPROPurchaseRequestNumber' => $decodedNumber,
            'Activity' => (string) $request->input('Activity'),
            'mstApprovalStatusID' => (string) ($decisionNormalized === 'Release' ? $poStatusId : $prStatusId),
            'Remark' => (string) ($request->input('RemarkApproval') ?? $request->input('Remark')),
            'Decision' => $decisionNormalized,
            'CreatedBy' => $employeeId !== '' ? $employeeId : 'System',
            'CreatedDate' => $now,
            'IsActive' => true,
        ];

        if ($decisionNormalized === 'Release') {
            $this->insertLogIfAvailable('logPROPurchaseOrder', $logData);
        } else {
            $this->insertLogIfAvailable('logPROPurchaseRequest', $logData);
        }

        return response()->json([
            'message' => 'Release submitted',
            'mstApprovalStatusID' => $prStatusId,
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
        $prStatusId = $decisionNormalized === 'Release' ? 4 : 5;
        $poStatusId = $decisionNormalized === 'Release' ? 8 : 5;

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
                    'mstApprovalStatusID' => $prStatusId,
                    'UpdatedBy' => $employeeId !== '' ? $employeeId : 'System',
                    'UpdatedDate' => $now,
                ]);

            $logData = [
                'mstEmployeeID' => $employeeId !== '' ? $employeeId : 'System',
                'mstEmployeePositionName' => $positionName,
                'trxPROPurchaseRequestNumber' => $decodedNumber,
                'Activity' => (string) $request->input('Activity'),
                'mstApprovalStatusID' => (string) ($decisionNormalized === 'Release' ? $poStatusId : $prStatusId),
                'Remark' => (string) ($request->input('RemarkApproval') ?? $request->input('Remark')),
                'Decision' => $decisionNormalized,
                'CreatedBy' => $employeeId !== '' ? $employeeId : 'System',
                'CreatedDate' => $now,
                'IsActive' => true,
            ];

            if ($decisionNormalized === 'Release') {
                $this->insertLogIfAvailable('logPROPurchaseOrder', $logData);
            } else {
                $this->insertLogIfAvailable('logPROPurchaseRequest', $logData);
            }

            $updated++;
        }

        return response()->json([
            'message' => 'Bulk release submitted',
            'updated' => $updated,
            'notFound' => $notFound,
        ]);
    }

    private function baseQuery()
    {
        $itemTotals = DB::table('trxPROPurchaseRequestItem')
            ->select('trxPROPurchaseRequestNumber', DB::raw('SUM(Amount) as TotalAmount'))
            ->where('IsActive', true)
            ->groupBy('trxPROPurchaseRequestNumber');

        $query = DB::table('trxPROPurchaseRequest as pr')
            ->leftJoinSub($itemTotals, 'items', function ($join) {
                $join->on('items.trxPROPurchaseRequestNumber', '=', 'pr.PurchaseRequestNumber');
            })
            ->leftJoin('mstPROPurchaseType as type', 'pr.mstPurchaseTypeID', '=', 'type.ID')
            ->leftJoin('mstPROPurchaseSubType as subType', 'pr.mstPurchaseSubTypeID', '=', 'subType.ID')
            ->leftJoin('mstApprovalStatus as status', 'pr.mstApprovalStatusID', '=', 'status.ID')
            ->where('pr.IsActive', true)
            ->where('pr.mstApprovalStatusID', 7)
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
                'pr.mstApprovalStatusID',
                'pr.CreatedDate as createdDate',
                'type.PurchaseRequestType as purchaseRequestType',
                'type.Category as purchaseRequestCategory',
                'subType.PurchaseRequestSubType as purchaseRequestSubType',
                'status.ApprovalStatus as approvalStatus',
                DB::raw('COALESCE(items.TotalAmount, 0) as totalAmount'),
            ]);

        $employeeId = $this->getCurrentEmployeeId();
        if ($employeeId !== '') {
            $query->where('pr.UpdatedBy', $employeeId);
        }

        return $query;
    }

    private function applyFilters($query, Request $request)
    {
        $fromDate = $request->input('fromDate') ?? $request->input('startDate');
        $toDate = $request->input('toDate') ?? $request->input('endDate');
        $purchReqNum = $request->input('purchReqNum') ?? $request->input('prNumber');
        $purchReqName = $request->input('purchReqName') ?? $request->input('prName');
        $purchReqType = $request->input('purchReqType') ?? $request->input('prType');
        $purchReqSubType = $request->input('purchReqSubType') ?? $request->input('prSubType');
        $statusPR = $request->input('statusPR');
        $company = $request->input('company');
        $regional = $request->input('regional');
        $typeSPK = $request->input('typeSPK');
        $year = $request->input('year');

        if ($fromDate) {
            $query->whereDate('pr.CreatedDate', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('pr.CreatedDate', '<=', $toDate);
        }
        if ($purchReqNum) {
            $query->where('pr.PurchaseRequestNumber', 'like', '%' . $purchReqNum . '%');
        }
        if ($purchReqName) {
            $query->where('pr.PurchaseRequestName', 'like', '%' . $purchReqName . '%');
        }
        if ($purchReqType) {
            if (is_numeric($purchReqType)) {
                $query->where('pr.mstPurchaseTypeID', (string) $purchReqType);
            } else {
                $query->where(function ($q) use ($purchReqType) {
                    $q->where('type.PurchaseRequestType', 'like', '%' . $purchReqType . '%')
                        ->orWhere('type.Category', 'like', '%' . $purchReqType . '%');
                });
            }
        }
        if ($purchReqSubType) {
            if (is_numeric($purchReqSubType)) {
                $query->where('pr.mstPurchaseSubTypeID', (string) $purchReqSubType);
            } else {
                $query->where('subType.PurchaseRequestSubType', 'like', '%' . $purchReqSubType . '%');
            }
        }
        if ($statusPR !== null && $statusPR !== '') {
            $query->where('pr.mstApprovalStatusID', (int) $statusPR);
        }
        if ($company) {
            $query->where('pr.Company', 'like', '%' . $company . '%');
        }
        if ($year && Schema::hasColumn('trxPROPurchaseRequest', 'Year')) {
            $query->where('pr.Year', $year);
        }
        if ($regional && Schema::hasColumn('trxPROPurchaseRequest', 'Regional')) {
            $query->where('pr.Regional', 'like', '%' . $regional . '%');
        }
        if ($typeSPK && Schema::hasColumn('trxPROPurchaseRequest', 'TypeSPK')) {
            $query->where('pr.TypeSPK', 'like', '%' . $typeSPK . '%');
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

        return [
            'purchReqNumber' => $row->purchReqNumber ?? null,
            'purchReqName' => $row->purchReqName ?? null,
            'purchReqType' => $typeDisplay,
            'purchReqSubType' => $row->purchaseRequestSubType ?? null,
            'approvalStatus' => $row->approvalStatus ?? null,
            'mstApprovalStatusID' => $row->mstApprovalStatusID ?? null,
            'pic' => $row->reviewedBy ?? null,
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
            'pic' => 'pr.ReviewedBy',
            'totalAmount' => 'totalAmount',
            'company' => 'pr.Company',
            'requestor' => 'pr.Requestor',
            'applicant' => 'pr.Applicant',
            'createdDate' => 'pr.CreatedDate',
            default => null,
        };
    }

    private function normalizeDecision(string $decision): string
    {
        $decision = trim($decision);
        if ($decision === '') {
            return 'Release';
        }

        $lower = strtolower($decision);
        if ($lower === 'reject' || $lower === 'rejected') {
            return 'Reject';
        }
        if ($lower === 'release' || $lower === 'released') {
            return 'Release';
        }

        return ucfirst($decision);
    }

    private function insertLogIfAvailable(string $table, array $data): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        DB::table($table)->insert($data);
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
