<?php

namespace App\Modules\Procurement\Controllers\PurchaseRequest;

use App\Http\Controllers\Controller;
use App\Models\MstEmployee;
use App\Services\DocumentCounterService;
use Carbon\Carbon;
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

        $payload = $this->extractReleasePayload($request);
        $poNumber = null;

        DB::transaction(function () use (
            $decodedNumber,
            $now,
            $employeeId,
            $positionName,
            $decisionNormalized,
            $prStatusId,
            $poStatusId,
            $payload,
            $purchaseRequest,
            &$poNumber
        ) {
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
                'Activity' => (string) ($payload['Activity'] ?? ''),
                'mstApprovalStatusID' => (string) ($decisionNormalized === 'Release' ? $poStatusId : $prStatusId),
                'Remark' => (string) ($payload['RemarkApproval'] ?? $payload['Remark'] ?? ''),
                'Decision' => $decisionNormalized,
                'CreatedBy' => $employeeId !== '' ? $employeeId : 'System',
                'CreatedDate' => $now,
                'IsActive' => true,
            ];

            if ($decisionNormalized === 'Release') {
                $this->insertLogIfAvailable('logPROPurchaseOrder', $logData);
                $poNumber = $this->createPurchaseOrderForRelease(
                    $purchaseRequest,
                    $payload,
                    $employeeId !== '' ? $employeeId : 'System',
                    $poStatusId
                );
            } else {
                $this->insertLogIfAvailable('logPROPurchaseRequest', $logData);
            }
        });

        return response()->json([
            'message' => 'Release submitted',
            'mstApprovalStatusID' => $prStatusId,
            'PONumber' => $poNumber,
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
        $prToPOMapping = [];
        $payload = $this->extractReleasePayload($request);

        $prRowsByNumber = [];
        $poNumbersByCompany = [];
        if ($decisionNormalized === 'Release') {
            $prRows = DB::table('trxPROPurchaseRequest')
                ->whereIn('PurchaseRequestNumber', $prNumbers)
                ->get()
                ->keyBy('PurchaseRequestNumber');
            $prRowsByNumber = $prRows->all();

            $prNumbersByCompany = [];
            foreach ($prRows as $row) {
                $companyCode = $row->Company ?? 'IDI';
                if (!isset($prNumbersByCompany[$companyCode])) {
                    $prNumbersByCompany[$companyCode] = [];
                }
                $prNumbersByCompany[$companyCode][] = $row->PurchaseRequestNumber;
            }

            foreach ($prNumbersByCompany as $companyCode => $numbers) {
                $poNumbersByCompany[$companyCode] = $this->generateBulkPONumbers($companyCode, count($numbers));
            }
        }

        foreach ($prNumbers as $number) {
            $decodedNumber = urldecode((string) $number);
            if ($decodedNumber === '') {
                continue;
            }

            $purchaseRequest = null;
            if ($decisionNormalized === 'Release' && isset($prRowsByNumber[$decodedNumber])) {
                $purchaseRequest = $prRowsByNumber[$decodedNumber];
            } else {
                $purchaseRequest = DB::table('trxPROPurchaseRequest')
                    ->where('PurchaseRequestNumber', $decodedNumber)
                    ->first();
            }

            if (!$purchaseRequest) {
                $notFound[] = $decodedNumber;
                continue;
            }

            DB::transaction(function () use (
                $decodedNumber,
                $now,
                $employeeId,
                $positionName,
                $decisionNormalized,
                $prStatusId,
                $poStatusId,
                $payload,
                $purchaseRequest,
                &$poNumbersByCompany,
                &$prToPOMapping
            ) {
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
                    'Activity' => (string) ($payload['Activity'] ?? ''),
                    'mstApprovalStatusID' => (string) ($decisionNormalized === 'Release' ? $poStatusId : $prStatusId),
                    'Remark' => (string) ($payload['RemarkApproval'] ?? $payload['Remark'] ?? ''),
                    'Decision' => $decisionNormalized,
                    'CreatedBy' => $employeeId !== '' ? $employeeId : 'System',
                    'CreatedDate' => $now,
                    'IsActive' => true,
                ];

                if ($decisionNormalized === 'Release') {
                    $this->insertLogIfAvailable('logPROPurchaseOrder', $logData);

                    $companyCode = $purchaseRequest->Company ?? 'IDI';
                    $preGenerated = null;
                    if (isset($poNumbersByCompany[$companyCode]) && count($poNumbersByCompany[$companyCode]) > 0) {
                        $preGenerated = array_shift($poNumbersByCompany[$companyCode]);
                    }

                    $poNumber = $this->createPurchaseOrderForRelease(
                        $purchaseRequest,
                        $payload,
                        $employeeId !== '' ? $employeeId : 'System',
                        $poStatusId,
                        $preGenerated
                    );

                    if ($poNumber) {
                        $prToPOMapping[$decodedNumber] = $poNumber;
                    }
                } else {
                    $this->insertLogIfAvailable('logPROPurchaseRequest', $logData);
                }
            });

            $updated++;
        }

        return response()->json([
            'message' => 'Bulk release submitted',
            'successCount' => $updated,
            'failedCount' => count($notFound),
            'failedPRNumbers' => $notFound,
            'PRToPOMapping' => $prToPOMapping,
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
                'pr.UpdatedBy as updatedBy',
                'pr.mstApprovalStatusID',
                'pr.CreatedDate as createdDate',
                'type.PurchaseRequestType as purchaseRequestType',
                'type.Category as purchaseRequestCategory',
                'subType.PurchaseRequestSubType as purchaseRequestSubType',
                'status.ApprovalStatus as approvalStatus',
                DB::raw('COALESCE(items.TotalAmount, 0) as totalAmount'),
            ]);

        $allowedUpdatedBy = $this->getCurrentUserIdentifiers();
        if (empty($allowedUpdatedBy)) {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereIn('pr.UpdatedBy', $allowedUpdatedBy);
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
            'pic' => $row->updatedBy ?? null,
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

    private function extractReleasePayload(Request $request): array
    {
        return [
            'Decision' => (string) ($request->input('Decision') ?? $request->input('decision') ?? ''),
            'VendorType' => (string) ($request->input('VendorType') ?? $request->input('vendorType') ?? ''),
            'VendorId' => (string) ($request->input('VendorId') ?? $request->input('vendorId') ?? ''),
            'CoreBusiness' => (string) ($request->input('CoreBusiness') ?? $request->input('coreBusiness') ?? ''),
            'SubCoreBusiness' => (string) ($request->input('SubCoreBusiness') ?? $request->input('subCoreBusiness') ?? ''),
            'ContractNo' => (string) ($request->input('ContractNo') ?? $request->input('contractNo') ?? ''),
            'ContractPeriod' => (string) ($request->input('ContractPeriod') ?? $request->input('contractPeriod') ?? ''),
            'TopType' => (string) ($request->input('TopType') ?? $request->input('topType') ?? ''),
            'Description' => (string) ($request->input('Description') ?? $request->input('description') ?? ''),
            'RemarkApproval' => (string) ($request->input('RemarkApproval') ?? $request->input('remarkApproval') ?? ''),
            'Remark' => (string) ($request->input('Remark') ?? $request->input('remark') ?? ''),
            'Activity' => (string) ($request->input('Activity') ?? $request->input('activity') ?? ''),
        ];
    }

    private function createPurchaseOrderForRelease(object $purchaseRequest, array $payload, string $employeeId, int $poStatusId, ?string $preGeneratedPONumber = null): ?string
    {
        if (!Schema::hasTable('trxPROPurchaseOrder')) {
            return null;
        }

        $companyCode = $purchaseRequest->Company ?? 'IDI';
        $poNumber = $preGeneratedPONumber ?: $this->generatePONumber($companyCode);
        if ($poNumber === '') {
            return null;
        }

        $prNumber = (string) ($purchaseRequest->PurchaseRequestNumber ?? '');
        $itemsQuery = DB::table('trxPROPurchaseRequestItem')->where('trxPROPurchaseRequestNumber', $prNumber);
        if (Schema::hasColumn('trxPROPurchaseRequestItem', 'IsActive')) {
            $itemsQuery->where('IsActive', true);
        }
        $prItems = $itemsQuery->get();
        $totalAmount = $prItems->sum(function ($item) {
            return (float) ($item->Amount ?? 0);
        });

        $vendorId = $payload['VendorId'] !== '' ? $payload['VendorId'] : null;
        $vendorName = null;
        if ($vendorId && Schema::hasTable('mstVendor')) {
            $vendorName = DB::table('mstVendor')->where('VendorID', $vendorId)->value('VendorName');
        }

        $companyName = null;
        if (Schema::hasTable('mstCompany')) {
            if (Schema::hasColumn('mstCompany', 'CompanyName')) {
                $companyName = DB::table('mstCompany')->where('CompanyID', $companyCode)->value('CompanyName');
            } elseif (Schema::hasColumn('mstCompany', 'Company')) {
                $companyName = DB::table('mstCompany')->where('CompanyID', $companyCode)->value('Company');
            }
        }

        [$startPeriod, $endPeriod] = $this->parseContractPeriod($payload['ContractPeriod'] ?? '');

        $now = now();
        $poData = [
            'PurchaseOrderNumber' => $poNumber,
            'PurchaseOrderName' => $purchaseRequest->PurchaseRequestName ?? null,
            'PurchaseType' => $purchaseRequest->mstPurchaseTypeID ?? null,
            'PurchaseSubType' => $purchaseRequest->mstPurchaseSubTypeID ?? null,
            'PurchaseOrderAmount' => $totalAmount,
            'PurchaseOrderDate' => $now,
            'PurchaseOrderAuthor' => $employeeId,
            'trxPROPurchaseRequestNumber' => $prNumber,
            'PurchaseRequestRequestor' => $purchaseRequest->Requestor ?? null,
            'PurchaseRequestDate' => $purchaseRequest->CreatedDate ?? null,
            'mstVendorVendorID' => $vendorId,
            'mstVendorVendorName' => $vendorName,
            'CompanyID' => $companyCode,
            'CompanyName' => $companyName,
            'StartPeriod' => $startPeriod,
            'EndPeriod' => $endPeriod,
            'IsGenerate' => false,
            'CreatedDate' => $now,
            'CreatedBy' => $employeeId,
            'UpdatedBy' => $employeeId,
            'UpdatedDate' => $now,
            'mstApprovalStatusID' => $poStatusId,
            'IsActive' => true,
        ];

        DB::table('trxPROPurchaseOrder')->insert($this->filterDataByColumns('trxPROPurchaseOrder', $poData));

        if (Schema::hasTable('trxPROPurchaseOrderItem')) {
            foreach ($prItems as $item) {
                $itemData = [
                    'trxPROPurchaseOrderNumber' => $poNumber,
                    'mstPROPurchaseItemInventoryItemID' => $item->mstPROPurchaseItemInventoryItemID ?? null,
                    'ItemName' => $item->ItemName ?? null,
                    'ItemDescription' => $item->ItemDescription ?? null,
                    'ItemUnit' => $item->ItemUnit ?? null,
                    'ItemQty' => $item->ItemQty ?? null,
                    'CurrencyCode' => $item->CurrencyCode ?? null,
                    'UnitPrice' => $item->UnitPrice ?? null,
                    'Amount' => $item->Amount ?? null,
                    'CreatedBy' => $employeeId,
                    'CreatedDate' => $now,
                    'IsActive' => true,
                ];
                DB::table('trxPROPurchaseOrderItem')->insert($this->filterDataByColumns('trxPROPurchaseOrderItem', $itemData));
            }
        }

        $seqTOP = $this->resolveTopId($payload['TopType'] ?? '');
        $this->createPurchaseOrderAssignVendor($poNumber, $payload, $employeeId, $seqTOP);
        $this->generateAmortizationSchedule($purchaseRequest, $poNumber, $totalAmount, $seqTOP, $employeeId);

        return $poNumber;
    }

    private function createPurchaseOrderAssignVendor(string $poNumber, array $payload, string $employeeId, ?int $seqTOP): void
    {
        if (!Schema::hasTable('trxPROPurchaseOrderAssignVendor')) {
            return;
        }

        $now = now();
        $vendorId = $payload['VendorId'] !== '' ? $payload['VendorId'] : null;
        $vendorType = $payload['VendorType'] !== '' ? $payload['VendorType'] : null;
        $contractNo = $payload['ContractNo'] !== '' ? $payload['ContractNo'] : null;
        $contractPeriod = $payload['ContractPeriod'] !== '' ? $payload['ContractPeriod'] : null;
        $description = $payload['Description'] !== '' ? $payload['Description'] : null;

        $coreBusinessId = is_numeric($payload['CoreBusiness'] ?? null) ? (int) $payload['CoreBusiness'] : null;
        $subCoreBusinessId = is_numeric($payload['SubCoreBusiness'] ?? null) ? (int) $payload['SubCoreBusiness'] : null;

        $assignData = [
            'trxPROPurchaseOrderNumber' => $poNumber,
            'mstVendorVendorID' => $vendorId,
            'VendorType' => $vendorType,
            'CoreBusinessID' => $coreBusinessId,
            'SubCoreBusinessID' => $subCoreBusinessId,
            'ContractNumber' => $contractNo,
            'SeqTOP' => $seqTOP,
            'ContractPeriod' => $contractPeriod,
            'DescriptionVendor' => $description,
            'CreatedBy' => $employeeId,
            'CreatedDate' => $now,
            'UpdatedBy' => $employeeId,
            'UpdatedDate' => $now,
            'IsActive' => true,
        ];

        if (Schema::hasColumn('trxPROPurchaseOrderAssignVendor', 'mstPROCoreBusinessID')) {
            $assignData['mstPROCoreBusinessID'] = $coreBusinessId;
        }
        if (Schema::hasColumn('trxPROPurchaseOrderAssignVendor', 'mstPROCoreBusinessSubCoreID')) {
            $assignData['mstPROCoreBusinessSubCoreID'] = $subCoreBusinessId;
        }

        DB::table('trxPROPurchaseOrderAssignVendor')->insert($this->filterDataByColumns('trxPROPurchaseOrderAssignVendor', $assignData));
    }

    private function generateAmortizationSchedule(object $purchaseRequest, string $poNumber, float $poAmount, ?int $seqTOP, string $employeeId): void
    {
        if (!Schema::hasTable('trxPROPurchaseOrderAmortization')) {
            return;
        }

        try {
            $now = now();
            $prNumber = (string) ($purchaseRequest->PurchaseRequestNumber ?? '');
            $top = $seqTOP ? $this->getTopDefinition($seqTOP) : null;
            $termValues = $this->buildTermValues($top);

            $isTermOfPayment = count($termValues) > 1 || (count($termValues) === 1 && (float) $termValues[0] !== 100.0);
            if ($isTermOfPayment && count($termValues) > 0) {
                foreach ($termValues as $index => $termValue) {
                    $invoiceAmount = ($poAmount * (float) $termValue) / 100;
                    $amortData = [
                        'PurchaseOrderNumber' => $poNumber,
                        'PeriodNumber' => $index + 1,
                        'AmortizationType' => 'Term',
                        'TermValue' => $termValue,
                        'StartDate' => null,
                        'EndDate' => null,
                        'InvoiceAmount' => $invoiceAmount,
                        'Status' => 'Available',
                        'IsCanceled' => false,
                        'CreatedBy' => $employeeId,
                        'CreatedDate' => $now,
                        'IsActive' => true,
                    ];
                    DB::table('trxPROPurchaseOrderAmortization')->insert($this->filterDataByColumns('trxPROPurchaseOrderAmortization', $amortData));
                }
            }

            $prAdditional = null;
            if (Schema::hasTable('trxPROPurchaseRequestAdditional')) {
                $prAdditionalQuery = DB::table('trxPROPurchaseRequestAdditional')
                    ->where('trxPROPurchaseRequestNumber', $prNumber);
                if (Schema::hasColumn('trxPROPurchaseRequestAdditional', 'IsActive')) {
                    $prAdditionalQuery->where('IsActive', true);
                }
                $prAdditional = $prAdditionalQuery->first();
            }

            if ($prAdditional && !empty($prAdditional->StartPeriod) && !empty($prAdditional->EndPeriod) && !empty($prAdditional->Period) && !empty($prAdditional->BillingTypeID)) {
                $billingType = null;
                if (Schema::hasTable('mstPROPurchaseRequestBillingType')) {
                    $billingType = DB::table('mstPROPurchaseRequestBillingType')
                        ->where('ID', $prAdditional->BillingTypeID)
                        ->where('IsActive', true)
                        ->first();
                }

                $totalMonthPeriod = $billingType->TotalMonthPeriod ?? null;
                if ($totalMonthPeriod) {
                    $startPeriod = Carbon::parse($prAdditional->StartPeriod);
                    $endPeriod = Carbon::parse($prAdditional->EndPeriod);
                    $periodCount = (int) $prAdditional->Period;

                    $periods = $this->generatePeriodAmortizations($startPeriod, $endPeriod, $periodCount, (int) $totalMonthPeriod);
                    foreach ($periods as $period) {
                        $amortData = [
                            'PurchaseOrderNumber' => $poNumber,
                            'PeriodNumber' => $period['PeriodNumber'],
                            'AmortizationType' => 'Period',
                            'TermValue' => 100,
                            'StartDate' => $period['StartDate'],
                            'EndDate' => $period['EndDate'],
                            'InvoiceAmount' => $poAmount,
                            'Status' => 'Available',
                            'IsCanceled' => false,
                            'CreatedBy' => $employeeId,
                            'CreatedDate' => $now,
                            'IsActive' => true,
                        ];
                        DB::table('trxPROPurchaseOrderAmortization')->insert($this->filterDataByColumns('trxPROPurchaseOrderAmortization', $amortData));
                    }
                }
            }
        } catch (\Throwable $error) {
            // Skip amortization failures to avoid blocking release flow
            return;
        }
    }

    private function generatePeriodAmortizations(Carbon $startPeriod, Carbon $endPeriod, int $periodCount, int $totalMonthPeriod): array
    {
        $amortizations = [];
        $currentStart = $startPeriod->copy();
        $finalEnd = $endPeriod->copy();

        for ($i = 0; $i < $periodCount; $i++) {
            if ($i === 0) {
                $currentEnd = $currentStart->copy()->addMonths($totalMonthPeriod)->startOfMonth()->subDay();
            } else {
                $currentStart = $amortizations[$i - 1]['EndDate']->copy()->addDay()->startOfMonth();
                $currentEnd = $currentStart->copy()->addMonths($totalMonthPeriod)->startOfMonth()->subDay();
            }

            if ($i === $periodCount - 1) {
                $currentEnd = $finalEnd->copy();
            } elseif ($currentEnd->greaterThan($finalEnd)) {
                $currentEnd = $finalEnd->copy();
            }

            $amortizations[] = [
                'PeriodNumber' => $i + 1,
                'StartDate' => $currentStart->copy(),
                'EndDate' => $currentEnd->copy(),
            ];

            if ($currentEnd->greaterThanOrEqualTo($finalEnd)) {
                break;
            }
        }

        return $amortizations;
    }

    private function resolveTopId(string $topDescription): ?int
    {
        $topDescription = trim($topDescription);
        if ($topDescription === '' || !Schema::hasTable('mstFINInvoiceTOP')) {
            return null;
        }

        $top = DB::table('mstFINInvoiceTOP')
            ->where('TOPDescription', $topDescription)
            ->where('IsActive', true)
            ->first();

        return $top ? (int) $top->ID : null;
    }

    private function getTopDefinition(int $seqTOP): ?object
    {
        if (!Schema::hasTable('mstFINInvoiceTOP')) {
            return null;
        }

        return DB::table('mstFINInvoiceTOP')
            ->where('ID', $seqTOP)
            ->where('IsActive', true)
            ->first();
    }

    private function buildTermValues(?object $top): array
    {
        if (!$top) {
            return [];
        }

        $termValues = [];
        $topCount = (int) ($top->TOPCount ?? 0);
        if ($topCount > 1) {
            $terms = [
                $top->Term1 ?? null,
                $top->Term2 ?? null,
                $top->Term3 ?? null,
                $top->Term4 ?? null,
                $top->Term5 ?? null,
                $top->Term6 ?? null,
                $top->Term7 ?? null,
            ];
            foreach ($terms as $term) {
                if ($term === null) {
                    continue;
                }
                $value = (float) $term;
                if ($value > 0) {
                    $termValues[] = $value;
                }
            }
            return $termValues;
        }

        $listOfTerm = trim((string) ($top->ListOfTerm ?? ''));
        if ($listOfTerm === '') {
            return [];
        }

        $parts = preg_split('/[|;, ]+/', $listOfTerm) ?: [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            if (is_numeric($part)) {
                $value = (float) $part;
                if ($value > 0) {
                    $termValues[] = $value;
                }
            }
        }

        return $termValues;
    }

    private function parseContractPeriod(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return [null, null];
        }

        if (preg_match('/^\d{4}$/', $value)) {
            return [null, null];
        }

        $parts = preg_split('/\s+to\s+|\\s*-\\s*/i', $value) ?: [];
        if (count($parts) >= 2) {
            try {
                $start = Carbon::parse(trim($parts[0]));
                $end = Carbon::parse(trim($parts[count($parts) - 1]));
                return [$start, $end];
            } catch (\Throwable $error) {
                return [null, null];
            }
        }

        return [null, null];
    }

    private function generatePONumber(string $companyCode): string
    {
        $service = app(DocumentCounterService::class);
        return $service->generateNumber('PO', ['COMPANY' => $companyCode]);
    }

    private function generateBulkPONumbers(string $companyCode, int $quantity): array
    {
        $service = app(DocumentCounterService::class);
        return $service->generateBulkNumbers('PO', ['COMPANY' => $companyCode], $quantity);
    }

    private function filterDataByColumns(string $table, array $data): array
    {
        static $columnsCache = [];
        if (!isset($columnsCache[$table])) {
            $columnsCache[$table] = Schema::getColumnListing($table);
        }
        $columns = $columnsCache[$table];
        $filtered = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $columns, true)) {
                $filtered[$key] = $value;
            }
        }
        return $filtered;
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

    private function getCurrentUserIdentifiers(): array
    {
        $employee = session('employee');
        $ids = [];

        if ($employee instanceof MstEmployee) {
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
