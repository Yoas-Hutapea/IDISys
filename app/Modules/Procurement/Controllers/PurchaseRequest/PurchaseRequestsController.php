<?php

namespace App\Modules\Procurement\Controllers\PurchaseRequest;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseRequestsController extends Controller
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
}
