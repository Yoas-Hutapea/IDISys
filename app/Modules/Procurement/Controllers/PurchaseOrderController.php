<?php

namespace App\Modules\Procurement\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurchaseOrderController extends Controller
{
    public function show(string $poNumber)
    {
        $decodedNumber = urldecode($poNumber);

        $po = DB::table('trxPROPurchaseOrder')
            ->where('PurchaseOrderNumber', $decodedNumber)
            ->where('IsActive', true)
            ->first();

        if (!$po) {
            return response()->json(['message' => 'Purchase Order not found'], 404);
        }

        $approvalStatus = null;
        if (!empty($po->mstApprovalStatusID)) {
            $approvalStatus = DB::table('mstApprovalStatus')
                ->where('ID', $po->mstApprovalStatusID)
                ->where('IsActive', true)
                ->value('ApprovalStatus');
        }

        $vendorType = null;
        $coreBusiness = null;
        $subCoreBusiness = null;
        $purchaseSubTypeName = null;
        $contractNumber = null;
        $contractPeriod = null;
        $topDescription = null;
        $descriptionVendor = null;

        if (Schema::hasTable('trxPROPurchaseOrderAssignVendor')) {
            $assignVendor = DB::table('trxPROPurchaseOrderAssignVendor')
                ->where('trxPROPurchaseOrderNumber', $decodedNumber)
                ->where('IsActive', true)
                ->first();

            if ($assignVendor) {
                $vendorType = $assignVendor->VendorType ?? null;
                $contractNumber = $assignVendor->ContractNumber ?? null;
                $contractPeriod = $assignVendor->ContractPeriod ?? null;
                $descriptionVendor = $assignVendor->DescriptionVendor ?? null;

                if (!empty($assignVendor->SeqTOP) && Schema::hasTable('mstFINInvoiceTOP')) {
                    $topDescription = DB::table('mstFINInvoiceTOP')
                        ->where('ID', $assignVendor->SeqTOP)
                        ->where('IsActive', true)
                        ->value('TOPDescription');
                }

                $assignVendorCoreBusinessId = $assignVendor->mstPROCoreBusinessID ?? $assignVendor->CoreBusinessID ?? null;
                $assignVendorSubCoreBusinessId = $assignVendor->mstPROCoreBusinessSubCoreID ?? $assignVendor->SubCoreBusinessID ?? null;

                if (!empty($assignVendorCoreBusinessId) && Schema::hasTable('mstPROCoreBusiness')) {
                    $coreBusiness = DB::table('mstPROCoreBusiness')
                        ->where('ID', $assignVendorCoreBusinessId)
                        ->value('CoreBusiness');
                }

                if (!empty($assignVendorSubCoreBusinessId) && Schema::hasTable('mstPROCoreBusinessSubCore')) {
                    $subCoreBusiness = DB::table('mstPROCoreBusinessSubCore')
                        ->where('ID', $assignVendorSubCoreBusinessId)
                        ->value('SubCoreBusiness');
                }
            }
        }

        $purchaseSubTypeId = null;
        if (Schema::hasColumn('trxPROPurchaseOrder', 'mstPROPurchaseSubTypeID')) {
            $purchaseSubTypeId = $po->mstPROPurchaseSubTypeID ?? null;
        }
        if (!$purchaseSubTypeId) {
            $purchaseSubTypeId = $po->PurchaseSubType ?? null;
        }

        if (!empty($purchaseSubTypeId) && Schema::hasTable('mstPROPurchaseSubType')) {
            $purchaseSubTypeName = DB::table('mstPROPurchaseSubType')
                ->where('ID', $purchaseSubTypeId)
                ->value('PurchaseRequestSubType');
        }

        $poCoreBusinessId = null;
        $poSubCoreBusinessId = null;
        if (Schema::hasColumn('trxPROPurchaseOrder', 'mstPROCoreBusinessID')) {
            $poCoreBusinessId = $po->mstPROCoreBusinessID ?? null;
        }
        if (Schema::hasColumn('trxPROPurchaseOrder', 'mstPROCoreBusinessSubCoreID')) {
            $poSubCoreBusinessId = $po->mstPROCoreBusinessSubCoreID ?? null;
        }

        if (!$coreBusiness && !empty($poCoreBusinessId) && Schema::hasTable('mstPROCoreBusiness')) {
            $coreBusiness = DB::table('mstPROCoreBusiness')
                ->where('ID', $poCoreBusinessId)
                ->value('CoreBusiness');
        }

        if (!$subCoreBusiness && !empty($poSubCoreBusinessId) && Schema::hasTable('mstPROCoreBusinessSubCore')) {
            $subCoreBusiness = DB::table('mstPROCoreBusinessSubCore')
                ->where('ID', $poSubCoreBusinessId)
                ->value('SubCoreBusiness');
        }

        $data = [
            'id' => $po->ID ?? null,
            'purchOrderID' => $po->PurchaseOrderNumber ?? null,
            'PurchOrderID' => $po->PurchaseOrderNumber ?? null,
            'purchOrderName' => $po->PurchaseOrderName ?? null,
            'PurchOrderName' => $po->PurchaseOrderName ?? null,
            'purchType' => $po->PurchaseType ?? null,
            'PurchType' => $po->PurchaseType ?? null,
            'purchSubType' => $purchaseSubTypeName ?? $po->PurchaseSubType ?? null,
            'PurchSubType' => $purchaseSubTypeName ?? $po->PurchaseSubType ?? null,
            'poAmount' => $po->PurchaseOrderAmount ?? 0,
            'POAmount' => $po->PurchaseOrderAmount ?? 0,
            'poDate' => $po->PurchaseOrderDate ?? null,
            'PODate' => $po->PurchaseOrderDate ?? null,
            'poAuthor' => $po->PurchaseOrderAuthor ?? null,
            'POAuthor' => $po->PurchaseOrderAuthor ?? null,
            'prNumber' => $po->trxPROPurchaseRequestNumber ?? null,
            'PRNumber' => $po->trxPROPurchaseRequestNumber ?? null,
            'prRequestor' => $po->PurchaseRequestRequestor ?? null,
            'PRRequestor' => $po->PurchaseRequestRequestor ?? null,
            'prDate' => $po->PurchaseRequestDate ?? null,
            'PRDate' => $po->PurchaseRequestDate ?? null,
            'mstVendorVendorName' => $po->mstVendorVendorName ?? null,
            'companyName' => $po->CompanyName ?? null,
            'mstApprovalStatusID' => $po->mstApprovalStatusID ?? null,
            'approvalStatus' => $approvalStatus,
            'vendorType' => $vendorType,
            'VendorType' => $vendorType,
            'coreBusiness' => $coreBusiness,
            'CoreBusiness' => $coreBusiness,
            'subCoreBusiness' => $subCoreBusiness,
            'SubCoreBusiness' => $subCoreBusiness,
            'contractNumber' => $contractNumber,
            'ContractNumber' => $contractNumber,
            'contractPeriod' => $contractPeriod,
            'ContractPeriod' => $contractPeriod,
            'topDescription' => $topDescription,
            'TOPDescription' => $topDescription,
            'descriptionVendor' => $descriptionVendor,
            'DescriptionVendor' => $descriptionVendor,
        ];

        return response()->json($data);
    }

    public function items(string $poNumber)
    {
        $decodedNumber = urldecode($poNumber);

        $itemsQuery = DB::table('trxPROPurchaseOrderItem')
            ->where('trxPROPurchaseOrderNumber', $decodedNumber);

        if (Schema::hasColumn('trxPROPurchaseOrderItem', 'IsActive')) {
            $itemsQuery->where('IsActive', true);
        }

        $items = $itemsQuery
            ->orderBy('ID')
            ->get()
            ->map(function ($item) {
                return [
                    'ID' => $item->ID ?? null,
                    'trxPROPurchaseOrderNumber' => $item->trxPROPurchaseOrderNumber ?? null,
                    'mstPROPurchaseItemInventoryItemID' => $item->mstPROPurchaseItemInventoryItemID ?? null,
                    'ItemName' => $item->ItemName ?? null,
                    'ItemDescription' => $item->ItemDescription ?? null,
                    'ItemUnit' => $item->ItemUnit ?? null,
                    'ItemQty' => $item->ItemQty ?? null,
                    'CurrencyCode' => $item->CurrencyCode ?? null,
                    'UnitPrice' => $item->UnitPrice ?? null,
                    'Amount' => $item->Amount ?? null,
                    'CreatedDate' => $item->CreatedDate ?? null,
                ];
            })
            ->values();

        return response()->json($items);
    }

    public function approvalHistory(string $poNumber)
    {
        $decodedNumber = urldecode($poNumber);

        if (!Schema::hasTable('logPROPurchaseOrder')) {
            return response()->json([]);
        }

        $po = DB::table('trxPROPurchaseOrder')
            ->where('PurchaseOrderNumber', $decodedNumber)
            ->first();

        $prNumber = $po?->trxPROPurchaseRequestNumber;
        if (!$prNumber) {
            return response()->json([]);
        }

        $historyQuery = DB::table('logPROPurchaseOrder')
            ->where('trxPROPurchaseRequestNumber', $prNumber);

        if (Schema::hasColumn('logPROPurchaseOrder', 'IsActive')) {
            $historyQuery->where('IsActive', true);
        }

        $history = $historyQuery
            ->orderBy('CreatedDate')
            ->get();

        if ($history->isEmpty()) {
            return response()->json([]);
        }

        $employeeIds = $history
            ->pluck('mstEmployeeID')
            ->filter(fn ($id) => !empty($id))
            ->unique()
            ->values();

        $employeeNames = [];
        if (!$employeeIds->isEmpty() && Schema::hasTable('mstEmployee')) {
            $employees = DB::table('mstEmployee')
                ->whereIn('Employ_Id', $employeeIds)
                ->orWhereIn('Employ_Id_TBGSYS', $employeeIds)
                ->get();

            foreach ($employees as $emp) {
                if (!empty($emp->Employ_Id)) {
                    $employeeNames[$emp->Employ_Id] = $emp->name ?? $emp->Employ_Id;
                }
                if (!empty($emp->Employ_Id_TBGSYS) && !isset($employeeNames[$emp->Employ_Id_TBGSYS])) {
                    $employeeNames[$emp->Employ_Id_TBGSYS] = $emp->name ?? $emp->Employ_Id_TBGSYS;
                }
            }
        }

        $data = $history->map(function ($row) use ($employeeNames) {
            $employeeId = $row->mstEmployeeID ?? null;
            return [
                'mstEmployeeID' => $employeeId,
                'employeeName' => $employeeId && isset($employeeNames[$employeeId]) ? $employeeNames[$employeeId] : $employeeId,
                'positionName' => $row->mstEmployeePositionName ?? null,
                'activity' => $row->Activity ?? null,
                'decision' => $row->Decision ?? null,
                'remark' => $row->Remark ?? null,
                'createdDate' => $row->CreatedDate ?? null,
            ];
        })->values();

        return response()->json($data);
    }

    public function grid(Request $request)
    {
        $draw = (int) $request->input('draw', 0);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        $baseQuery = $this->applyStatusFilters($this->baseQuery(), $request);
        $recordsTotal = (clone $baseQuery)->count('po.ID');

        $filteredQuery = $this->applyFilters($baseQuery, $request);
        $recordsFiltered = (clone $filteredQuery)->count('po.ID');

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
            $filteredQuery->orderBy('po.PurchaseOrderDate', 'desc');
        }

        $rows = $filteredQuery
            ->skip($start)
            ->take($length > 0 ? $length : $recordsFiltered)
            ->get();

        $data = $rows->map(fn ($row) => $this->mapRow($row))->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    private function baseQuery()
    {
        return DB::table('trxPROPurchaseOrder as po')
            ->leftJoin('mstPROPurchaseSubType as subType', 'po.PurchaseSubType', '=', 'subType.ID')
            ->leftJoin('mstApprovalStatus as status', 'po.mstApprovalStatusID', '=', 'status.ID')
            ->where('po.IsActive', true)
            ->select([
                'po.ID as id',
                'po.PurchaseOrderNumber as purchOrderID',
                'po.PurchaseOrderName as purchOrderName',
                'po.PurchaseType as purchType',
                'po.PurchaseSubType as purchSubType',
                'subType.PurchaseRequestSubType as purchaseSubTypeName',
                'po.PurchaseOrderAmount as poAmount',
                'po.PurchaseOrderDate as poDate',
                'po.PurchaseOrderAuthor as poAuthor',
                'po.trxPROPurchaseRequestNumber as prNumber',
                'po.PurchaseRequestRequestor as prRequestor',
                'po.mstVendorVendorName as mstVendorVendorName',
                'po.CompanyName as companyName',
                'po.mstApprovalStatusID as mstApprovalStatusID',
                'status.ApprovalStatus as approvalStatus',
            ]);
    }

    private function applyStatusFilters($query, Request $request)
    {
        $statusIds = $request->input('mstApprovalStatusIDs', []);
        $statusIds = is_array($statusIds) ? $statusIds : [];
        $singleStatusId = $request->input('mstApprovalStatusID');

        if ($singleStatusId !== null && $singleStatusId !== '') {
            $query->where('po.mstApprovalStatusID', (int) $singleStatusId);
        }

        if (!empty($statusIds)) {
            $allowedStatuses = $this->filterAllowedStatuses($statusIds);
            if (empty($allowedStatuses)) {
                return $query->whereRaw('1 = 0');
            }

            $query->whereIn('po.mstApprovalStatusID', $allowedStatuses);

            if (in_array(8, $allowedStatuses, true) || in_array(12, $allowedStatuses, true)) {
                $userIds = $this->getCurrentUserIdentifiers();
                if (empty($userIds)) {
                    return $query->whereRaw('1 = 0');
                }
                $query->whereIn('po.CreatedBy', $userIds);
            }
        }

        return $query;
    }

    private function applyFilters($query, Request $request)
    {
        $poNumber = $request->input('poNumber');
        $prNumber = $request->input('prNumber');
        $purchType = $request->input('purchType');
        $purchSubType = $request->input('purchSubType');
        $purchName = $request->input('purchName');
        $company = $request->input('company');
        $vendorName = $request->input('vendorName');
        $poStartDate = $request->input('poStartDate');
        $poEndDate = $request->input('poEndDate');

        if ($poNumber && trim($poNumber) !== '') {
            $query->where('po.PurchaseOrderNumber', trim($poNumber));
        }
        if ($prNumber && trim($prNumber) !== '') {
            $query->where('po.trxPROPurchaseRequestNumber', trim($prNumber));
        }
        if ($purchType && trim($purchType) !== '') {
            $query->where('po.PurchaseType', trim($purchType));
        }
        if ($purchSubType && trim($purchSubType) !== '') {
            $query->where('po.PurchaseSubType', trim($purchSubType));
        }
        if ($purchName && trim($purchName) !== '') {
            $query->where('po.PurchaseOrderName', 'like', '%' . trim($purchName) . '%');
        }
        if ($company && trim($company) !== '') {
            $query->where('po.CompanyName', trim($company));
        }
        if ($vendorName && trim($vendorName) !== '') {
            $query->where('po.mstVendorVendorName', 'like', '%' . trim($vendorName) . '%');
        }
        if ($poStartDate) {
            $query->whereDate('po.PurchaseOrderDate', '>=', $poStartDate);
        }
        if ($poEndDate) {
            $query->whereDate('po.PurchaseOrderDate', '<=', $poEndDate);
        }

        return $query;
    }

    private function mapRow($row): array
    {
        $subType = $row->purchaseSubTypeName ?? $row->purchSubType ?? null;

        return [
            'id' => $row->id ?? null,
            'purchOrderID' => $row->purchOrderID ?? null,
            'purchOrderName' => $row->purchOrderName ?? null,
            'purchType' => $row->purchType ?? null,
            'purchSubType' => $subType,
            'approvalStatus' => $row->approvalStatus ?? null,
            'mstApprovalStatusID' => $row->mstApprovalStatusID ?? null,
            'poAmount' => $row->poAmount ?? 0,
            'poDate' => $row->poDate ?? null,
            'prNumber' => $row->prNumber ?? null,
            'mstVendorVendorName' => $row->mstVendorVendorName ?? null,
            'companyName' => $row->companyName ?? null,
            'poAuthor' => $row->poAuthor ?? null,
            'prRequestor' => $row->prRequestor ?? null,
        ];
    }

    private function mapOrderColumn(?string $columnKey): ?string
    {
        return match ($columnKey) {
            'purchOrderID' => 'po.PurchaseOrderNumber',
            'purchOrderName' => 'po.PurchaseOrderName',
            'purchType' => 'po.PurchaseType',
            'purchSubType' => 'po.PurchaseSubType',
            'approvalStatus' => 'status.ApprovalStatus',
            'poAmount' => 'po.PurchaseOrderAmount',
            'poDate' => 'po.PurchaseOrderDate',
            'prNumber' => 'po.trxPROPurchaseRequestNumber',
            'mstVendorVendorName' => 'po.mstVendorVendorName',
            'companyName' => 'po.CompanyName',
            'poAuthor' => 'po.PurchaseOrderAuthor',
            'prRequestor' => 'po.PurchaseRequestRequestor',
            default => null,
        };
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

    private function getCurrentUserIdentifiers(): array
    {
        $employee = session('employee');
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
}
