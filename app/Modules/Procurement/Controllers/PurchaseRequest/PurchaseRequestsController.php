<?php

namespace App\Modules\Procurement\Controllers\PurchaseRequest;

use App\Http\Controllers\Controller;
use App\Models\MstEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\DocumentCounterService;
use Illuminate\Support\Facades\Auth;

class PurchaseRequestsController extends Controller
{
    public function __construct(private readonly DocumentCounterService $documentCounter)
    {
    }

    public function store(Request $request)
    {
        $payload = $this->normalizePayload($request->all());

        $prNumber = $this->generatePurchaseRequestNumber($payload['Company']);
        $now = now();
        $createdBy = $payload['Requestor'] ?: (string) optional(Auth::user())->Username ?: 'System';

        DB::table('trxPROPurchaseRequest')->insert([
            'PurchaseRequestNumber' => $prNumber,
            'Requestor' => $payload['Requestor'],
            'Applicant' => $payload['Applicant'],
            'Company' => $payload['Company'],
            'mstPurchaseTypeID' => $payload['PurchReqType'],
            'mstPurchaseSubTypeID' => $payload['PurchReqSubType'],
            'PurchaseRequestName' => $payload['PurchReqName'],
            'Remark' => $payload['Remark'],
            'mstApprovalStatusID' => $payload['mstApprovalStatusID'],
            'CreatedBy' => $createdBy,
            'CreatedDate' => $now,
            'UpdatedBy' => $createdBy,
            'UpdatedDate' => $now,
            'IsActive' => true,
        ]);

        return response()->json([
            'purchReqNumber' => $prNumber,
            'PurchReqNumber' => $prNumber,
            'createdDate' => $now,
            'CreatedDate' => $now,
            'company' => $payload['Company'],
            'Company' => $payload['Company'],
        ]);
    }

    public function update(string $prNumber, Request $request)
    {
        $decodedNumber = urldecode($prNumber);
        $payload = $this->normalizePayload($request->all());
        $now = now();
        $updatedBy = $payload['Requestor'] ?: (string) optional(Auth::user())->Username ?: 'System';

        $updated = DB::table('trxPROPurchaseRequest')
            ->where('PurchaseRequestNumber', $decodedNumber)
            ->update([
                'Requestor' => $payload['Requestor'],
                'Applicant' => $payload['Applicant'],
                'Company' => $payload['Company'],
                'mstPurchaseTypeID' => $payload['PurchReqType'],
                'mstPurchaseSubTypeID' => $payload['PurchReqSubType'],
                'PurchaseRequestName' => $payload['PurchReqName'],
                'Remark' => $payload['Remark'],
                'mstApprovalStatusID' => $payload['mstApprovalStatusID'],
                'UpdatedBy' => $updatedBy,
                'UpdatedDate' => $now,
            ]);

        if (!$updated) {
            return response()->json(['message' => 'Purchase Request not found'], 404);
        }

        return response()->json([
            'purchReqNumber' => $decodedNumber,
            'PurchReqNumber' => $decodedNumber,
        ]);
    }

    public function updateApproval(string $prNumber, Request $request)
    {
        $decodedNumber = urldecode($prNumber);
        $now = now();
        $updatedBy = (string) optional(Auth::user())->Username ?: 'System';

        $updated = DB::table('trxPROPurchaseRequest')
            ->where('PurchaseRequestNumber', $decodedNumber)
            ->update([
                'ReviewedBy' => $request->input('ReviewedBy'),
                'ApprovedBy' => $request->input('ApprovedBy'),
                'ConfirmedBy' => $request->input('ConfirmedBy'),
                'UpdatedBy' => $updatedBy,
                'UpdatedDate' => $now,
            ]);

        if (!$updated) {
            return response()->json(['message' => 'Purchase Request not found'], 404);
        }

        return response()->json(['message' => 'Approval updated']);
    }

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

    public function index(Request $request)
    {
        $query = $this->applyFilters($this->baseQuery(), $request)
            ->orderBy('pr.CreatedDate', 'desc');

        $rows = $query->get();

        return response()->json($rows->map(fn ($row) => $this->mapRow($row)));
    }

    public function show(string $prNumber)
    {
        $decodedNumber = urldecode($prNumber);
        $row = $this->baseQuery()
            ->where('pr.PurchaseRequestNumber', $decodedNumber)
            ->first();

        if (!$row) {
            return response()->json(['message' => 'Purchase Request not found'], 404);
        }

        $data = $this->mapRow($row);
        $data['mstPurchaseTypeID'] = $row->mstPurchaseTypeID ?? null;
        $data['mstPurchaseSubTypeID'] = $row->mstPurchaseSubTypeID ?? null;
        $data['remark'] = $row->remark ?? null;
        $data['reviewedBy'] = $row->reviewedBy ?? null;
        $data['approvedBy'] = $row->approvedBy ?? null;
        $data['confirmedBy'] = $row->confirmedBy ?? null;

        return response()->json($data);
    }

    public function items(string $prNumber)
    {
        $decodedNumber = urldecode($prNumber);
        $items = DB::table('trxPROPurchaseRequestItem')
            ->where('trxPROPurchaseRequestNumber', $decodedNumber)
            ->where('IsActive', true)
            ->select([
                'ID',
                'mstPROPurchaseItemInventoryItemID as ItemID',
                'ItemName',
                'ItemDescription',
                'ItemUnit',
                'ItemQty',
                'CurrencyCode',
                'UnitPrice',
                'Amount',
            ])
            ->get();

        return response()->json($items);
    }

    public function documents(string $prNumber)
    {
        $decodedNumber = urldecode($prNumber);
        $documents = DB::table('trxPROPurchaseRequestDocument')
            ->where('trxPROPurchaseRequestNumber', $decodedNumber)
            ->where('IsActive', true)
            ->select([
                'ID',
                'FileName',
                'FileSize',
                'FilePath',
            ])
            ->get();

        return response()->json($documents);
    }

    public function additional(string $prNumber)
    {
        $decodedNumber = urldecode($prNumber);
        $additional = DB::table('trxPROPurchaseRequestAdditional as add')
            ->leftJoin('mstPROPurchaseRequestBillingType as billing', 'add.BillingTypeID', '=', 'billing.ID')
            ->where('add.trxPROPurchaseRequestNumber', $decodedNumber)
            ->where('add.IsActive', true)
            ->select([
                'add.Sonumb as Sonumb',
                'add.SiteName',
                'add.SiteID',
                'add.BillingTypeID',
                'billing.Name as BillingTypeName',
                'add.StartPeriod',
                'add.Period',
                'add.EndPeriod',
            ])
            ->first();

        if (!$additional) {
            return response()->json(null);
        }

        return response()->json([
            'sonumb' => $additional->Sonumb,
            'siteName' => $additional->SiteName,
            'siteID' => $additional->SiteID,
            'billingTypeId' => $additional->BillingTypeID,
            'billingTypeName' => $additional->BillingTypeName,
            'startPeriod' => $additional->StartPeriod,
            'period' => $additional->Period,
            'endPeriod' => $additional->EndPeriod,
        ]);
    }

    public function saveItemsBulk(string $prNumber, Request $request)
    {
        $decodedNumber = urldecode($prNumber);
        $items = $request->input('Items', []);
        $now = now();
        $userId = (string) optional(Auth::user())->Username ?: 'System';

        $processedItemIds = [];

        foreach ($items as $item) {
            $id = $item['ID'] ?? $item['id'] ?? null;
            $data = [
                'trxPROPurchaseRequestNumber' => $decodedNumber,
                'mstPROPurchaseItemInventoryItemID' => (string) ($item['mstPROPurchaseItemInventoryItemID'] ?? $item['mstPROInventoryItemID'] ?? ''),
                'ItemName' => $item['ItemName'] ?? null,
                'ItemDescription' => $item['ItemDescription'] ?? null,
                'ItemUnit' => $item['ItemUnit'] ?? null,
                'ItemQty' => $item['ItemQty'] ?? 0,
                'CurrencyCode' => $item['CurrencyCode'] ?? 'IDR',
                'UnitPrice' => $item['UnitPrice'] ?? 0,
                'Amount' => $item['Amount'] ?? 0,
                'UpdatedBy' => $userId,
                'UpdatedDate' => $now,
                'IsActive' => true,
            ];

            if ($id) {
                DB::table('trxPROPurchaseRequestItem')->where('ID', $id)->update($data);
                $processedItemIds[] = (int) $id;
            } else {
                $data['CreatedBy'] = $userId;
                $data['CreatedDate'] = $now;
                $newId = DB::table('trxPROPurchaseRequestItem')->insertGetId($data);
                $processedItemIds[] = (int) $newId;
            }
        }

        $itemsToDeleteQuery = DB::table('trxPROPurchaseRequestItem')
            ->where('trxPROPurchaseRequestNumber', $decodedNumber);
        if (!empty($processedItemIds)) {
            $itemsToDeleteQuery->whereNotIn('ID', $processedItemIds);
        }
        $itemsToDeleteQuery->delete();

        DB::table('trxPROPurchaseRequestItem')
            ->where('trxPROPurchaseRequestNumber', $decodedNumber)
            ->where('IsActive', false)
            ->delete();

        return response()->json(['message' => 'Items saved']);
    }

    public function saveDocumentsBulk(string $prNumber, Request $request)
    {
        $decodedNumber = urldecode($prNumber);
        $documents = $request->input('Documents', []);
        $now = now();
        $userId = (string) optional(Auth::user())->Username ?: 'System';

        $processedDocumentIds = [];

        foreach ($documents as $doc) {
            $id = $doc['ID'] ?? $doc['id'] ?? null;
            $data = [
                'trxPROPurchaseRequestNumber' => $decodedNumber,
                'FileName' => $doc['FileName'] ?? null,
                'FileSize' => $doc['FileSize'] ?? null,
                'UpdatedBy' => $userId,
                'UpdatedDate' => $now,
                'IsActive' => true,
            ];

            if ($id) {
                DB::table('trxPROPurchaseRequestDocument')->where('ID', $id)->update($data);
                $processedDocumentIds[] = (int) $id;
            } else {
                $data['CreatedBy'] = $userId;
                $data['CreatedDate'] = $now;
                $newId = DB::table('trxPROPurchaseRequestDocument')->insertGetId($data);
                $processedDocumentIds[] = (int) $newId;
            }
        }

        $documentsToDeleteQuery = DB::table('trxPROPurchaseRequestDocument')
            ->where('trxPROPurchaseRequestNumber', $decodedNumber);
        if (!empty($processedDocumentIds)) {
            $documentsToDeleteQuery->whereNotIn('ID', $processedDocumentIds);
        }
        $documentsToDeleteQuery->delete();

        DB::table('trxPROPurchaseRequestDocument')
            ->where('trxPROPurchaseRequestNumber', $decodedNumber)
            ->where('IsActive', false)
            ->delete();

        return response()->json(['message' => 'Documents saved']);
    }

    public function uploadDocument(string $prNumber, Request $request)
    {
        $decodedNumber = urldecode($prNumber);
        if (!$request->hasFile('file')) {
            return response()->json(['message' => 'File not found'], 400);
        }

        $file = $request->file('file');
        $timestamp = now()->format('YmdHis');
        $filename = $timestamp . '_' . $file->getClientOriginalName();
        $path = "Procurement/PurchaseRequest/{$decodedNumber}/{$filename}";

        Storage::disk('public')->putFileAs(dirname($path), $file, basename($path));

        $now = now();
        $userId = (string) optional(Auth::user())->Username ?: 'System';

        $id = DB::table('trxPROPurchaseRequestDocument')->insertGetId([
            'trxPROPurchaseRequestNumber' => $decodedNumber,
            'FileName' => $file->getClientOriginalName(),
            'FileSize' => (string) round($file->getSize() / 1024),
            'FilePath' => '/' . $path,
            'CreatedBy' => $userId,
            'CreatedDate' => $now,
            'UpdatedBy' => $userId,
            'UpdatedDate' => $now,
            'IsActive' => true,
        ]);

        return response()->json(['id' => $id, 'filePath' => '/' . $path]);
    }

    public function saveAdditional(Request $request)
    {
        $prNumber = $request->input('PurchaseRequestNumber');
        if (!$prNumber) {
            return response()->json(['message' => 'PurchaseRequestNumber is required'], 400);
        }

        $now = now();
        $userId = (string) optional(Auth::user())->Username ?: 'System';

        $payload = [
            'trxPROPurchaseRequestNumber' => $prNumber,
            'BillingTypeID' => $request->input('BillingTypeID'),
            'StartPeriod' => $request->input('StartPeriod'),
            'Period' => $request->input('Period'),
            'EndPeriod' => $request->input('EndPeriod'),
            'Sonumb' => $request->input('Sonumb'),
            'SonumbId' => $request->input('SonumbId'),
            'SiteName' => $request->input('SiteName'),
            'SiteID' => $request->input('SiteID'),
            'UpdatedBy' => $userId,
            'UpdatedDate' => $now,
            'IsActive' => true,
        ];

        $existing = DB::table('trxPROPurchaseRequestAdditional')
            ->where('trxPROPurchaseRequestNumber', $prNumber)
            ->first();

        if ($existing) {
            DB::table('trxPROPurchaseRequestAdditional')
                ->where('ID', $existing->ID)
                ->update($payload);
        } else {
            $payload['CreatedBy'] = $userId;
            $payload['CreatedDate'] = $now;
            DB::table('trxPROPurchaseRequestAdditional')->insert($payload);
        }

        return response()->json(['message' => 'Additional data saved']);
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
                'pr.mstApprovalStatusID',
                'pr.CreatedDate as createdDate',
                'type.PurchaseRequestType as purchaseRequestType',
                'type.Category as purchaseRequestCategory',
                'subType.PurchaseRequestSubType as purchaseRequestSubType',
                'status.ApprovalStatus as approvalStatus',
                DB::raw('COALESCE(items.TotalAmount, 0) as totalAmount'),
            ]);
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
        $source = $request->input('source');

        if ($fromDate) {
            $query->whereDate('pr.CreatedDate', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('pr.CreatedDate', '<=', $toDate);
        }
        if ($purchReqNum) {
            $query->where('pr.PurchaseRequestNumber', trim((string) $purchReqNum));
        }
        if ($purchReqName) {
            $query->where('pr.PurchaseRequestName', 'like', '%' . $purchReqName . '%');
        }
        if ($purchReqType) {
            $query->where('pr.mstPurchaseTypeID', trim((string) $purchReqType));
        }
        if ($purchReqSubType) {
            $query->where('pr.mstPurchaseSubTypeID', trim((string) $purchReqSubType));
        }
        if ($statusPR !== null && $statusPR !== '') {
            $query->where('pr.mstApprovalStatusID', (int) $statusPR);
        }
        if ($source) {
            $query->where('pr.Company', strtoupper(trim((string) $source)));
        }

        $allowedCreatedBy = $this->getAllowedCreatedByIds();
        if ($allowedCreatedBy === null) {
            return $query;
        }
        if (empty($allowedCreatedBy)) {
            return $query->whereRaw('1 = 0');
        }
        $query->whereIn('pr.CreatedBy', $allowedCreatedBy);

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

    private function normalizePayload(array $data): array
    {
        return [
            'Requestor' => (string) ($data['Requestor'] ?? ''),
            'Applicant' => (string) ($data['Applicant'] ?? ''),
            'Company' => (string) ($data['Company'] ?? ''),
            'PurchReqType' => (string) ($data['PurchReqType'] ?? ''),
            'PurchReqSubType' => (string) ($data['PurchReqSubType'] ?? ''),
            'PurchReqName' => (string) ($data['PurchReqName'] ?? ''),
            'Remark' => (string) ($data['Remark'] ?? ''),
            'mstApprovalStatusID' => (int) ($data['mstApprovalStatusID'] ?? 6),
        ];
    }

    private function generatePurchaseRequestNumber(string $companyCode): string
    {
        return $this->documentCounter->generateNumber('PR', [
            'COMPANY' => $companyCode,
        ]);
    }

    private function getEmployeeFromSession(): ?MstEmployee
    {
        $employee = session('employee');
        return $employee instanceof MstEmployee ? $employee : null;
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

    private function getDepartmentHeadEmployeeId(): ?string
    {
        $employee = $this->getEmployeeFromSession();
        $jobTitle = $employee?->JobTitleName ?? '';

        if ($jobTitle !== '' && (
            stripos($jobTitle, 'Dept Head') !== false ||
            stripos($jobTitle, 'Department Head') !== false
        )) {
            return $employee?->Employ_Id ?? $employee?->Employ_Id_TBGSYS;
        }

        $currentIds = $this->getCurrentUserIdentifiers();
        if (empty($currentIds)) {
            return null;
        }

        $currentEmployee = MstEmployee::query()
            ->whereIn('Employ_Id', $currentIds)
            ->orWhereIn('Employ_Id_TBGSYS', $currentIds)
            ->first();

        if (!$currentEmployee) {
            return null;
        }

        $jobTitleName = $currentEmployee->JobTitleName ?? '';
        if ($jobTitleName === '') {
            return null;
        }

        if (stripos($jobTitleName, 'Dept Head') === false && stripos($jobTitleName, 'Department Head') === false) {
            return null;
        }

        return $currentEmployee->Employ_Id ?? $currentEmployee->Employ_Id_TBGSYS;
    }

    private function getAllowedCreatedByIds(): ?array
    {
        if ($this->isProcurementUser()) {
            return null;
        }

        $currentIds = $this->getCurrentUserIdentifiers();
        if (empty($currentIds)) {
            return [];
        }

        $departmentHeadId = $this->getDepartmentHeadEmployeeId();
        if ($departmentHeadId === null) {
            return $currentIds;
        }

        $subordinates = MstEmployee::query()
            ->where('Report_Code', $departmentHeadId)
            ->get();

        $ids = $currentIds;
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
