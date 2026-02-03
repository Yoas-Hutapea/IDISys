<?php

namespace App\Modules\Finance\Controllers\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\DocumentCounterService;

class InvoicesController extends Controller
{
    public function __construct(private readonly DocumentCounterService $documentCounter)
    {
    }
    public function index(Request $request)
    {
        $table = $this->resolveTable([
            'trxFINInvoice',
            'trxFINInvoices',
            'trxFINInvoiceHeader',
        ]);

        if (!$table) {
            return response()->json([]);
        }

        $invAlias = 'inv';
        $query = DB::table($table . ' as ' . $invAlias);

        $invoiceNumberCol = $this->resolveColumn($table, ['InvoiceNumber', 'InvoiceNo']);
        if ($invoiceNumberCol && $request->filled('invoiceNumber')) {
            $query->where($invAlias . '.' . $invoiceNumberCol, 'like', '%' . $request->query('invoiceNumber') . '%');
        }

        $requestNumberCol = $this->resolveColumn($table, ['RequestNumber', 'PurchaseRequestNumber', 'trxPROPurchaseRequestNumber']);
        if ($requestNumberCol && $request->filled('requestNumber')) {
            $query->where($invAlias . '.' . $requestNumberCol, 'like', '%' . $request->query('requestNumber') . '%');
        }

        $invPurchOrderCol = $this->resolveColumn($table, ['PurchOrderID', 'PurchaseOrderNumber', 'trxPROPurchaseOrderNumber']);
        if ($invPurchOrderCol && $request->filled('purchOrderID')) {
            $query->where($invAlias . '.' . $invPurchOrderCol, 'like', '%' . $request->query('purchOrderID') . '%');
        }

        $poTable = Schema::hasTable('trxPROPurchaseOrder') ? 'trxPROPurchaseOrder' : null;
        $poAlias = 'po';
        $poNumberCol = $poTable ? $this->resolveColumn($poTable, ['PurchaseOrderNumber', 'PurchOrderID', 'trxPROPurchaseOrderNumber']) : null;
        $poJoinEnabled = $poTable && $invPurchOrderCol && $poNumberCol;
        if ($poJoinEnabled) {
            $query->leftJoin($poTable . ' as ' . $poAlias, $invAlias . '.' . $invPurchOrderCol, '=', $poAlias . '.' . $poNumberCol);
        }

        $purchaseTypeIdCol = $poJoinEnabled ? $this->resolveColumn($poTable, ['PurchaseTypeID', 'mstPROPurchaseTypeID']) : null;
        if ($purchaseTypeIdCol && $request->filled('purchaseTypeID')) {
            $query->where($poAlias . '.' . $purchaseTypeIdCol, $request->query('purchaseTypeID'));
        } else {
            $this->applyExactFilter($query, $table, $request->query('purchaseTypeID'), ['PurchaseTypeID', 'mstPROPurchaseTypeID']);
        }

        $purchaseSubTypeIdCol = $poJoinEnabled ? $this->resolveColumn($poTable, ['PurchaseSubTypeID', 'mstPROPurchaseSubTypeID']) : null;
        if ($purchaseSubTypeIdCol && $request->filled('purchaseSubTypeID')) {
            $query->where($poAlias . '.' . $purchaseSubTypeIdCol, $request->query('purchaseSubTypeID'));
        } else {
            $this->applyExactFilter($query, $table, $request->query('purchaseSubTypeID'), ['PurchaseSubTypeID', 'mstPROPurchaseSubTypeID']);
        }

        $companyIdCol = $this->resolveColumn($table, ['CompanyID', 'mstCompanyID']);
        if ($companyIdCol && $request->filled('companyID')) {
            $query->where($invAlias . '.' . $companyIdCol, $request->query('companyID'));
        } elseif ($poJoinEnabled) {
            $poCompanyCol = $this->resolveColumn($poTable, ['CompanyID', 'Company', 'CompanyCode']);
            if ($poCompanyCol && $request->filled('companyID')) {
                $query->where($poAlias . '.' . $poCompanyCol, $request->query('companyID'));
            }
        }

        $workTypeIdCol = $this->resolveColumn($table, ['WorkTypeID', 'mstFINInvoiceWorkTypeID']);
        if ($workTypeIdCol && $request->filled('workTypeID')) {
            $query->where($invAlias . '.' . $workTypeIdCol, $request->query('workTypeID'));
        }

        $statusIdCol = $this->resolveColumn($table, ['StatusID', 'mstApprovalStatusID', 'ApprovalStatusID']);
        if ($statusIdCol && $request->filled('statusID')) {
            $query->where($invAlias . '.' . $statusIdCol, $request->query('statusID'));
        }

        $dateColumn = $this->resolveColumn($table, ['InvoiceDate', 'CreatedDate']);
        if ($dateColumn) {
            if ($request->filled('startDate')) {
                $query->whereDate($invAlias . '.' . $dateColumn, '>=', $request->query('startDate'));
            }
            if ($request->filled('endDate')) {
                $query->whereDate($invAlias . '.' . $dateColumn, '<=', $request->query('endDate'));
            }
        }

        $prAdditionalTable = Schema::hasTable('trxPROPurchaseRequestAdditional') ? 'trxPROPurchaseRequestAdditional' : null;
        $prAlias = 'pradd';
        if ($poJoinEnabled && $prAdditionalTable) {
            $poPrCol = $this->resolveColumn($poTable, ['trxPROPurchaseRequestNumber', 'PurchaseRequestNumber']);
            $prCol = $this->resolveColumn($prAdditionalTable, ['trxPROPurchaseRequestNumber', 'PurchaseRequestNumber']);
            if ($poPrCol && $prCol) {
                $query->leftJoin($prAdditionalTable . ' as ' . $prAlias, $poAlias . '.' . $poPrCol, '=', $prAlias . '.' . $prCol);
            }
        }

        $workTypeTable = $this->resolveTable(['mstFINInvoiceWorkType', 'mstFINInvoiceWorkTypes']);
        $workTypeAlias = 'worktype';
        $workTypeTableIdCol = $workTypeTable ? $this->resolveColumn($workTypeTable, ['ID', 'Id', 'WorkTypeID', 'mstFINInvoiceWorkTypeID']) : null;
        if ($workTypeTable && $workTypeTableIdCol && $workTypeIdCol) {
            $query->leftJoin($workTypeTable . ' as ' . $workTypeAlias, $invAlias . '.' . $workTypeIdCol, '=', $workTypeAlias . '.' . $workTypeTableIdCol);
        }

        $statusTable = $this->resolveTable(['mstApprovalStatus']);
        $statusAlias = 'status';
        $statusTableIdCol = $statusTable ? $this->resolveColumn($statusTable, ['ID', 'Id']) : null;
        if ($statusTable && $statusTableIdCol && $statusIdCol) {
            $query->leftJoin($statusTable . ' as ' . $statusAlias, $invAlias . '.' . $statusIdCol, '=', $statusAlias . '.' . $statusTableIdCol);
        }

        $companyTable = $this->resolveTable(['mstCompany']);
        $companyAlias = 'comp';
        $companyTableIdCol = $companyTable ? $this->resolveColumn($companyTable, ['CompanyID', 'CompanyCode', 'ID']) : null;
        if ($companyTable && $companyTableIdCol) {
            $joinCompanyCol = $companyIdCol ? ($invAlias . '.' . $companyIdCol) : ($poJoinEnabled ? $poAlias . '.' . ($this->resolveColumn($poTable, ['CompanyID', 'CompanyCode']) ?? '') : null);
            if ($joinCompanyCol && !str_ends_with($joinCompanyCol, '.')) {
                $query->leftJoin($companyTable . ' as ' . $companyAlias, $joinCompanyCol, '=', $companyAlias . '.' . $companyTableIdCol);
            }
        }

        $selects = [];
        $selects[] = $this->selectOrNull($table, $invAlias, ['ID', 'Id', 'InvoiceID', 'trxFINInvoiceID'], 'invoiceHeaderID');
        $selects[] = $this->selectOrNull($table, $invAlias, ['RequestNumber', 'PurchaseRequestNumber', 'trxPROPurchaseRequestNumber'], 'requestNumber');
        $selects[] = $this->selectOrNull($table, $invAlias, ['InvoiceNumber', 'InvoiceNo'], 'invoiceNumber');
        $selects[] = $this->selectOrNull($table, $invAlias, ['InvoiceDate', 'CreatedDate'], 'invoiceDate');
        $selects[] = $this->selectOrNull($table, $invAlias, ['StatusID', 'mstApprovalStatusID', 'ApprovalStatusID'], 'statusID');
        $selects[] = $this->selectOrNull($table, $invAlias, ['PICName', 'PicName'], 'picName');
        $selects[] = $this->selectOrNull($table, $invAlias, ['PurchOrderID', 'PurchaseOrderNumber', 'trxPROPurchaseOrderNumber'], 'purchOrderID');
        $selects[] = $this->selectOrNull($table, $invAlias, ['TermValue', 'termValue'], 'termValue');
        $selects[] = $this->selectOrNull($table, $invAlias, ['TermPosition', 'termPosition'], 'termPosition');
        $selects[] = $this->selectOrNull($table, $invAlias, ['InvoiceAmount', 'invoiceAmount'], 'invoiceAmount');
        $selects[] = $this->selectOrNull($table, $invAlias, ['SONumber', 'SoNumber', 'sonumber'], 'sonumber');
        $selects[] = $this->selectOrNull($table, $invAlias, ['SiteID', 'SiteId', 'siteID'], 'siteID');
        $selects[] = $this->selectOrNull($table, $invAlias, ['MonthPeriod', 'monthPeriod'], 'monthPeriod');
        $selects[] = $this->selectOrNull($table, $invAlias, ['CompanyID', 'mstCompanyID'], 'companyID');
        $selects[] = $this->selectOrNull($table, $invAlias, ['WorkTypeID', 'mstFINInvoiceWorkTypeID'], 'workTypeID');
        $selects[] = $this->selectOrNull($table, $invAlias, ['ProductTypeID', 'ProcuctTypeID'], 'productTypeID');

        if ($poJoinEnabled) {
            $selects[] = $this->selectOrNull($poTable, $poAlias, ['PurchaseOrderDate', 'PODate'], 'poReleaseDate');
            $selects[] = $this->selectOrNull($poTable, $poAlias, ['PurchaseType', 'PurchType'], 'purchaseType');
            $selects[] = $this->selectOrNull($poTable, $poAlias, ['PurchaseSubType', 'PurchSubType'], 'purchaseSubType');
            $selects[] = $this->selectOrNull($poTable, $poAlias, ['PurchaseTypeID', 'mstPROPurchaseTypeID'], 'purchaseTypeID');
            $selects[] = $this->selectOrNull($poTable, $poAlias, ['PurchaseSubTypeID', 'mstPROPurchaseSubTypeID'], 'purchaseSubTypeID');
            $selects[] = $this->selectOrNull($poTable, $poAlias, ['CompanyName', 'Company'], 'poCompanyName');
        } else {
            $selects[] = DB::raw('NULL as poReleaseDate');
            $selects[] = DB::raw('NULL as purchaseType');
            $selects[] = DB::raw('NULL as purchaseSubType');
            $selects[] = DB::raw('NULL as purchaseTypeID');
            $selects[] = DB::raw('NULL as purchaseSubTypeID');
            $selects[] = DB::raw('NULL as poCompanyName');
        }

        if ($prAdditionalTable) {
            $selects[] = $this->selectOrNull($prAdditionalTable, $prAlias, ['SiteName'], 'siteName');
            $selects[] = $this->selectOrNull($prAdditionalTable, $prAlias, ['SiteID', 'SiteId'], 'prSiteID');
            $selects[] = $this->selectOrNull($prAdditionalTable, $prAlias, ['StartPeriod', 'startPeriod'], 'startPeriod');
            $selects[] = $this->selectOrNull($prAdditionalTable, $prAlias, ['EndPeriod', 'endPeriod'], 'endPeriod');
        } else {
            $selects[] = DB::raw('NULL as siteName');
            $selects[] = DB::raw('NULL as prSiteID');
            $selects[] = DB::raw('NULL as startPeriod');
            $selects[] = DB::raw('NULL as endPeriod');
        }

        if ($workTypeTable) {
            $selects[] = $this->selectOrNull($workTypeTable, $workTypeAlias, ['WorkType', 'WorkTypeDescription', 'Description'], 'workType');
        } else {
            $selects[] = DB::raw('NULL as workType');
        }

        if ($statusTable) {
            $selects[] = $this->selectOrNull($statusTable, $statusAlias, ['ApprovalStatus', 'StatusName'], 'workflowStatus');
        } else {
            $selects[] = DB::raw('NULL as workflowStatus');
        }

        if ($companyTable) {
            $selects[] = $this->selectOrNull($companyTable, $companyAlias, ['CompanyName', 'Company'], 'companyName');
            $selects[] = $this->selectOrNull($companyTable, $companyAlias, ['CompanyCode'], 'companyCode');
        } else {
            $selects[] = DB::raw('NULL as companyName');
            $selects[] = DB::raw('NULL as companyCode');
        }

        $rows = $query->select($selects)->get();

        $data = $rows->map(function ($row) {
            $termValue = $row->termValue ?? null;
            $companyName = $row->companyName ?? $row->poCompanyName ?? $row->companyCode ?? null;

            $monthPeriod = $row->monthPeriod ?? null;
            if (!$monthPeriod && ($row->startPeriod || $row->endPeriod)) {
                $monthPeriod = trim((string) ($row->startPeriod ?? ''));
            }

            return [
                'invoiceHeaderID' => $row->invoiceHeaderID ?? null,
                'InvoiceHeaderID' => $row->invoiceHeaderID ?? null,
                'requestNumber' => $row->requestNumber ?? null,
                'RequestNumber' => $row->requestNumber ?? null,
                'invoiceNumber' => $row->invoiceNumber ?? null,
                'InvoiceNumber' => $row->invoiceNumber ?? null,
                'invoiceDate' => $row->invoiceDate ?? null,
                'InvoiceDate' => $row->invoiceDate ?? null,
                'statusID' => $row->statusID ?? null,
                'StatusID' => $row->statusID ?? null,
                'workflowStatus' => $row->workflowStatus ?? null,
                'WorkflowStatus' => $row->workflowStatus ?? null,
                'statusUser' => $row->workflowStatus ?? null,
                'StatusUser' => $row->workflowStatus ?? null,
                'picName' => $row->picName ?? null,
                'PICName' => $row->picName ?? null,
                'purchOrderID' => $row->purchOrderID ?? null,
                'PurchOrderID' => $row->purchOrderID ?? null,
                'poReleaseDate' => $row->poReleaseDate ?? null,
                'POReleaseDate' => $row->poReleaseDate ?? null,
                'purchaseType' => $row->purchaseType ?? null,
                'PurchaseType' => $row->purchaseType ?? null,
                'purchaseSubType' => $row->purchaseSubType ?? null,
                'PurchaseSubType' => $row->purchaseSubType ?? null,
                'purchaseTypeID' => $row->purchaseTypeID ?? null,
                'PurchaseTypeID' => $row->purchaseTypeID ?? null,
                'purchaseSubTypeID' => $row->purchaseSubTypeID ?? null,
                'PurchaseSubTypeID' => $row->purchaseSubTypeID ?? null,
                'termPaidValue' => $termValue ?? null,
                'TermPaidValue' => $termValue ?? null,
                'termValue' => $termValue ?? null,
                'TermValue' => $termValue ?? null,
                'termPosition' => $row->termPosition ?? null,
                'TermPosition' => $row->termPosition ?? null,
                'monthPeriod' => $monthPeriod,
                'MonthPeriod' => $monthPeriod,
                'sonumber' => $row->sonumber ?? null,
                'SONumber' => $row->sonumber ?? null,
                'siteID' => $row->siteID ?? $row->prSiteID ?? null,
                'SiteID' => $row->siteID ?? $row->prSiteID ?? null,
                'siteName' => $row->siteName ?? null,
                'SiteName' => $row->siteName ?? null,
                'companyID' => $row->companyID ?? null,
                'CompanyID' => $row->companyID ?? null,
                'company' => $companyName,
                'Company' => $companyName,
                'workTypeID' => $row->workTypeID ?? null,
                'WorkTypeID' => $row->workTypeID ?? null,
                'workType' => $row->workType ?? null,
                'WorkType' => $row->workType ?? null,
                'productTypeID' => $row->productTypeID ?? null,
                'ProductTypeID' => $row->productTypeID ?? null,
                'invoiceAmount' => $row->invoiceAmount ?? null,
                'InvoiceAmount' => $row->invoiceAmount ?? null,
            ];
        });

        return response()->json($data);
    }

    private function selectOrNull(string $table, string $alias, array $candidates, string $as)
    {
        $column = $this->resolveColumn($table, $candidates);
        if ($column) {
            return DB::raw($alias . '.' . $column . ' as ' . $as);
        }
        return DB::raw('NULL as ' . $as);
    }

    public function show(int $id)
    {
        $table = $this->resolveTable([
            'trxFINInvoice',
            'trxFINInvoices',
            'trxFINInvoiceHeader',
        ]);

        if (!$table) {
            return response()->json(null, 404);
        }

        $idColumn = $this->resolveColumn($table, ['ID', 'Id', 'InvoiceID', 'trxFINInvoiceID']);
        if (!$idColumn) {
            return response()->json(null, 404);
        }

        $row = DB::table($table)->where($idColumn, $id)->first();
        return response()->json($row);
    }

    public function store(Request $request)
    {
        $table = $this->resolveTable([
            'trxFINInvoice',
            'trxFINInvoices',
            'trxFINInvoiceHeader',
        ]);

        if (!$table) {
            return response()->json(['message' => 'Invoice table not found'], 404);
        }

        $detailTable = $table;
        $headerTable = Schema::hasTable('trxFINInvoiceHeader') ? 'trxFINInvoiceHeader' : null;
        if ($headerTable && $detailTable === $headerTable && Schema::hasTable('trxFINInvoice')) {
            $detailTable = 'trxFINInvoice';
        }

        $requestNumber = trim((string) $request->input('requestNumber'));
        $invoiceNumber = trim((string) $request->input('invoiceNumber'));
        $purchOrderId = trim((string) $request->input('purchOrderID'));
        $companyId = $request->input('companyID');

        $seqTop = $this->resolveSeqTopFromPO($purchOrderId);
        $siteId = $this->resolveSiteIdFromPO($purchOrderId);

        $existingHeader = null;
        $isPeriodScenario = false;
        if ($headerTable && $invoiceNumber !== '') {
            $headerInvoiceColumn = $this->resolveColumn($headerTable, ['InvoiceNumber', 'InvoiceNo']);
            if ($headerInvoiceColumn) {
                $existingHeader = DB::table($headerTable)->where($headerInvoiceColumn, $invoiceNumber)->first();
                $isPeriodScenario = $existingHeader !== null;
            }
        }

        if ($existingHeader) {
            $headerRequestColumn = $this->resolveColumn($headerTable, ['RequestNumber', 'PurchaseRequestNumber', 'trxPROPurchaseRequestNumber']);
            if ($headerRequestColumn && isset($existingHeader->{$headerRequestColumn})) {
                $requestNumber = trim((string) $existingHeader->{$headerRequestColumn});
            }
            $headerCompanyColumn = $this->resolveColumn($headerTable, ['CompanyID', 'mstCompanyID']);
            if ($headerCompanyColumn && isset($existingHeader->{$headerCompanyColumn})) {
                $companyId = $existingHeader->{$headerCompanyColumn};
            }
        }

        if (($companyId === null || $companyId === '') && $purchOrderId !== '') {
            $companyId = $this->resolveCompanyIdFromPO($purchOrderId);
        }

        if ($requestNumber === '') {
            if ($companyId === null || $companyId === '') {
                return response()->json(['message' => 'CompanyID is required to generate Request Number'], 422);
            }
            $requestNumber = $this->generateInvoiceRequestNumber((string) $companyId);
        }

        if ($companyId !== null && $companyId !== '') {
            $request->merge(['companyID' => $companyId]);
        }
        if (!$request->filled('statusID')) {
            $request->merge(['statusID' => 13]);
        }
        if ($requestNumber !== '') {
            $request->merge(['requestNumber' => $requestNumber]);
        }
        if ($seqTop !== null) {
            $request->merge(['trxFINInvoiceTOPID' => $seqTop]);
            if (!$request->filled('termOfPaymentID')) {
                $request->merge(['termOfPaymentID' => $seqTop]);
            }
        }
        if ($siteId !== null && $siteId !== '' && !$request->filled('siteID')) {
            $request->merge(['siteID' => $siteId]);
        }

        if ($headerTable) {
            $headerIdColumn = $this->resolveColumn($headerTable, ['ID', 'Id', 'InvoiceID', 'trxFINInvoiceID']);
            $headerData = $this->buildInvoiceData($headerTable, $request);
            $this->setAuditColumns($headerTable, $headerData, $existingHeader === null);

            if ($existingHeader && $headerIdColumn && isset($existingHeader->{$headerIdColumn})) {
                $headerUpdate = [];
                $termPositionColumn = $this->resolveColumn($headerTable, ['TermPosition', 'termPosition']);
                if ($termPositionColumn) {
                    $currentTermPosition = (int) ($existingHeader->{$termPositionColumn} ?? 0);
                    $incomingTermPosition = (int) ($request->input('termPosition') ?? 0);
                    if ($incomingTermPosition > $currentTermPosition) {
                        $headerUpdate[$termPositionColumn] = $incomingTermPosition;
                    }
                }

                if (!empty($headerUpdate)) {
                    $this->setAuditColumns($headerTable, $headerUpdate, false);
                    DB::table($headerTable)->where($headerIdColumn, $existingHeader->{$headerIdColumn})->update($headerUpdate);
                }
            } elseif ($existingHeader === null) {
                DB::table($headerTable)->insert($headerData);
            }
        }

        $data = $this->buildInvoiceData($detailTable, $request);
        $this->setAuditColumns($detailTable, $data, true);

        $idColumn = $this->resolveColumn($detailTable, ['ID', 'Id', 'InvoiceID', 'trxFINInvoiceID']);
        if ($idColumn) {
            $newId = DB::table($detailTable)->insertGetId($data, $idColumn);
            $this->syncDetailItems($newId, $request->input('detailItems', []));
            $this->syncDocumentItems($newId, $request->input('documentItems', []));

            $termPositionInput = $request->input('termPosition');
            $termPositions = $this->parseTermPositions($termPositionInput);
            $amortizationType = null;
            if (is_string($termPositionInput) && stripos($termPositionInput, 'period') !== false) {
                $amortizationType = 'Period';
            } elseif (is_numeric($termPositionInput)) {
                $amortizationType = 'Term';
            }
            foreach ($termPositions as $termPosition) {
                $this->updateAmortizationStatusForInvoice(
                    $purchOrderId,
                    $termPosition,
                    $newId,
                    $invoiceNumber,
                    $amortizationType
                );
            }

            if ($headerTable && $isPeriodScenario && $invoiceNumber !== '') {
                $headerInvoiceColumn = $this->resolveColumn($headerTable, ['InvoiceNumber', 'InvoiceNo']);
                $detailInvoiceColumn = $this->resolveColumn($detailTable, ['InvoiceNumber', 'InvoiceNo']);
                if ($headerInvoiceColumn && $detailInvoiceColumn) {
                    $totals = DB::table($detailTable)
                        ->where($detailInvoiceColumn, $invoiceNumber)
                        ->selectRaw('SUM(InvoiceAmount) as InvoiceAmount')
                        ->selectRaw('SUM(TaxAmount) as TaxAmount')
                        ->selectRaw('SUM(DPPAmount) as DPPAmount')
                        ->selectRaw('SUM(TotalAmount) as TotalAmount')
                        ->first();

                    $headerUpdate = [];
                    if ($totals) {
                        $this->setIfColumn($headerTable, $headerUpdate, ['InvoiceAmount'], $totals->InvoiceAmount);
                        $this->setIfColumn($headerTable, $headerUpdate, ['TaxAmount'], $totals->TaxAmount);
                        $this->setIfColumn($headerTable, $headerUpdate, ['DPPAmount'], $totals->DPPAmount);
                        $this->setIfColumn($headerTable, $headerUpdate, ['TotalAmount'], $totals->TotalAmount);
                    }

                    if (!empty($headerUpdate)) {
                        $headerIdColumn = $this->resolveColumn($headerTable, ['ID', 'Id', 'InvoiceID', 'trxFINInvoiceID']);
                        if ($headerIdColumn && $existingHeader && isset($existingHeader->{$headerIdColumn})) {
                            $this->setAuditColumns($headerTable, $headerUpdate, false);
                            DB::table($headerTable)->where($headerIdColumn, $existingHeader->{$headerIdColumn})->update($headerUpdate);
                        } elseif ($headerInvoiceColumn) {
                            $this->setAuditColumns($headerTable, $headerUpdate, false);
                            DB::table($headerTable)->where($headerInvoiceColumn, $invoiceNumber)->update($headerUpdate);
                        }
                    }
                }
            }

            $row = DB::table($detailTable)->where($idColumn, $newId)->first();
            return response()->json($row);
        }

        DB::table($detailTable)->insert($data);
        return response()->json($data);
    }

    public function update(int $id, Request $request)
    {
        $table = $this->resolveTable([
            'trxFINInvoice',
            'trxFINInvoices',
            'trxFINInvoiceHeader',
        ]);

        if (!$table) {
            return response()->json(['message' => 'Invoice table not found'], 404);
        }

        $idColumn = $this->resolveColumn($table, ['ID', 'Id', 'InvoiceID', 'trxFINInvoiceID']);
        if (!$idColumn) {
            return response()->json(['message' => 'Invoice ID column not found'], 404);
        }

        $data = $this->buildInvoiceData($table, $request);
        $this->setAuditColumns($table, $data, false);

        DB::table($table)->where($idColumn, $id)->update($data);
        $this->syncDetailItems($id, $request->input('detailItems', []), true);
        $this->syncDocumentItems($id, $request->input('documentItems', []), true);

        $row = DB::table($table)->where($idColumn, $id)->first();
        return response()->json($row);
    }

    public function items(int $id)
    {
        $table = $this->resolveTable([
            'trxFINInvoiceItem',
            'trxFINInvoiceItems',
        ]);

        if (!$table) {
            return response()->json([]);
        }

        $invoiceColumn = $this->resolveColumn($table, ['trxFINInvoiceID', 'InvoiceID']);
        if (!$invoiceColumn) {
            return response()->json([]);
        }

        return response()->json(DB::table($table)->where($invoiceColumn, $id)->get());
    }

    public function documents(int $id)
    {
        $table = $this->resolveTable([
            'trxFINInvoiceDocument',
            'trxFINInvoiceDocuments',
        ]);

        if (!$table) {
            return response()->json([]);
        }

        $invoiceColumn = $this->resolveColumn($table, ['trxFINInvoiceID', 'InvoiceID']);
        if (!$invoiceColumn) {
            return response()->json([]);
        }

        return response()->json(DB::table($table)->where($invoiceColumn, $id)->get());
    }

    public function detailsByPurchOrderID(Request $request)
    {
        $purchOrderID = $request->query('purchOrderID');
        if (!$purchOrderID) {
            return response()->json(['message' => 'purchOrderID is required'], 400);
        }

        $table = $this->resolveTable([
            'trxFINInvoice',
            'trxFINInvoices',
            'trxFINInvoiceHeader',
        ]);

        if (!$table) {
            return response()->json([]);
        }

        $poColumn = $this->resolveColumn($table, ['PurchOrderID', 'PurchaseOrderNumber', 'trxPROPurchaseOrderNumber']);
        if (!$poColumn) {
            return response()->json([]);
        }

        return response()->json(DB::table($table)->where($poColumn, $purchOrderID)->get());
    }

    public function postInvoice(int $id)
    {
        return $this->updatePostingStatus($id, 'Post');
    }

    public function rejectInvoice(int $id)
    {
        return $this->updatePostingStatus($id, 'Reject');
    }

    public function bulkPost(Request $request)
    {
        $invoiceIds = $request->input('invoiceIds', []);
        if (!is_array($invoiceIds) || empty($invoiceIds)) {
            return response()->json(['message' => 'invoiceIds is required'], 400);
        }

        $successCount = 0;
        $failedCount = 0;
        foreach ($invoiceIds as $invoiceId) {
            $result = $this->applyPostingStatus($invoiceId, 'Post');
            if ($result) {
                $successCount++;
            } else {
                $failedCount++;
            }
        }

        return response()->json([
            'successCount' => $successCount,
            'failedCount' => $failedCount,
            'totalCount' => count($invoiceIds),
        ]);
    }

    private function updatePostingStatus(int $id, string $action)
    {
        $result = $this->applyPostingStatus($id, $action);
        if (!$result) {
            return response()->json(['success' => false, 'message' => 'Invoice not found or status not updated'], 404);
        }

        return response()->json(['success' => true]);
    }

    private function applyPostingStatus(int $id, string $action): bool
    {
        $table = $this->resolveTable([
            'trxFINInvoice',
            'trxFINInvoices',
            'trxFINInvoiceHeader',
        ]);

        if (!$table) {
            return false;
        }

        $idColumn = $this->resolveColumn($table, ['ID', 'Id', 'InvoiceID', 'trxFINInvoiceID']);
        if (!$idColumn) {
            return false;
        }

        $statusColumn = $this->resolveColumn($table, ['StatusID', 'mstApprovalStatusID', 'ApprovalStatusID', 'Status']);
        $updateData = [];

        if ($statusColumn) {
            if ($statusColumn === 'Status') {
                $updateData[$statusColumn] = $action === 'Post' ? 'Posted' : 'Rejected';
            } else {
                $updateData[$statusColumn] = $action === 'Post' ? 1 : 2;
            }
        }

        $this->setAuditColumns($table, $updateData, false);

        return DB::table($table)->where($idColumn, $id)->update($updateData) > 0;
    }

    private function buildInvoiceData(string $table, Request $request): array
    {
        $data = [];

        $this->setIfColumn($table, $data, ['RequestNumber', 'PurchaseRequestNumber'], $request->input('requestNumber'));
        $this->setIfColumn($table, $data, ['InvoiceNumber', 'InvoiceNo'], $request->input('invoiceNumber'));
        $this->setIfColumn($table, $data, ['BastNumber', 'BASTNumber'], $request->input('bastNumber'));
        $this->setIfColumn($table, $data, ['PurchOrderID', 'PurchaseOrderNumber', 'trxPROPurchaseOrderNumber'], $request->input('purchOrderID'));
        $this->setIfColumn($table, $data, ['CompanyID', 'mstCompanyID'], $request->input('companyID'));
        $this->setIfColumn($table, $data, ['WorkTypeID', 'mstFINInvoiceWorkTypeID'], $request->input('workTypeID'));
        $this->setIfColumn($table, $data, ['ProductTypeID', 'ProcuctTypeID'], $request->input('productTypeID', 0));
        $this->setIfColumn($table, $data, ['VendorID', 'mstVendorVendorID'], $request->input('vendorID'));
        $this->setIfColumn($table, $data, ['trxFINInvoiceTOPID', 'mstFINInvoiceTOPID'], $request->input('trxFINInvoiceTOPID'));
        $this->setIfColumn($table, $data, ['TermPosition'], $request->input('termPosition'));
        $this->setIfColumn($table, $data, ['TermValue'], $request->input('termValue'));
        $this->setIfColumn($table, $data, ['TermOfPaymentID'], $request->input('termOfPaymentID'));
        $this->setIfColumn($table, $data, ['InvoiceAmount'], $request->input('invoiceAmount'));
        $this->setIfColumn($table, $data, ['mstTaxID', 'TaxCode'], $request->input('taxCode'));
        $this->setIfColumn($table, $data, ['mstTaxTaxValue', 'TaxValue'], $request->input('taxValue'));
        $this->setIfColumn($table, $data, ['TaxAmount'], $request->input('taxAmount'));
        $this->setIfColumn($table, $data, ['DPPAmount'], $request->input('dppAmount'));
        $this->setIfColumn($table, $data, ['TotalAmount'], $request->input('totalAmount'));
        $this->setIfColumn($table, $data, ['TaxNumber'], $request->input('taxNumber'));
        $this->setIfColumn($table, $data, ['InvoiceDate'], $request->input('invoiceDate'));
        $this->setIfColumn($table, $data, ['TaxDate'], $request->input('taxDate'));
        $this->setIfColumn($table, $data, ['PICName', 'PicName'], $request->input('picName'));
        $this->setIfColumn($table, $data, ['EmailAddress', 'PICEmail', 'Email'], $request->input('emailAddress'));
        $this->setIfColumn($table, $data, ['CreditNoteNumber'], $request->input('creditNoteNumber'));
        $this->setIfColumn($table, $data, ['CreditNoteAmount'], $request->input('creditNoteAmount'));
        $this->setIfColumn($table, $data, ['SONumber', 'SoNumber'], $request->input('sonumber'));
        $this->setIfColumn($table, $data, ['SiteID', 'SiteId'], $request->input('siteID'));
        $this->setIfColumn($table, $data, ['IsQR', 'isQR'], $this->normalizeBool($request->input('isQR')));

        if ($request->filled('statusID')) {
            $this->setIfColumn($table, $data, ['StatusID', 'mstApprovalStatusID', 'ApprovalStatusID'], $request->input('statusID'));
        }

        return $data;
    }

    private function syncDetailItems(int $invoiceId, array $detailItems, bool $replace = false): void
    {
        $table = $this->resolveTable([
            'trxFINInvoiceItem',
            'trxFINInvoiceItems',
        ]);

        if (!$table) {
            return;
        }

        $invoiceColumn = $this->resolveColumn($table, ['trxFINInvoiceID', 'InvoiceID']);
        if (!$invoiceColumn) {
            return;
        }

        if ($replace) {
            DB::table($table)->where($invoiceColumn, $invoiceId)->delete();
        }

        foreach ($detailItems as $item) {
            $data = [];
            $data[$invoiceColumn] = $invoiceId;

            $this->setIfColumn($table, $data, ['LineNumber'], $item['lineNumber'] ?? null);
            $this->setIfColumn($table, $data, ['ItemID', 'mstPROInventoryItemID'], $item['itemID'] ?? null);
            $this->setIfColumn($table, $data, ['ItemName'], $item['itemName'] ?? null);
            $this->setIfColumn($table, $data, ['UnitID'], $item['unitID'] ?? null);
            $this->setIfColumn($table, $data, ['CurrencyCode'], $item['currencyCode'] ?? null);
            $this->setIfColumn($table, $data, ['PricePO'], $item['pricePO'] ?? null);
            $this->setIfColumn($table, $data, ['QuantityPO'], $item['quantityPO'] ?? null);
            $this->setIfColumn($table, $data, ['LineAmountPO'], $item['lineAmountPO'] ?? null);
            $this->setIfColumn($table, $data, ['QuantityInvoice'], $item['quantityInvoice'] ?? null);
            $this->setIfColumn($table, $data, ['LineAmountInvoice'], $item['lineAmountInvoice'] ?? null);
            $this->setIfColumn($table, $data, ['Description'], $item['description'] ?? null);

            $this->setAuditColumns($table, $data, true);
            DB::table($table)->insert($data);
        }
    }

    private function syncDocumentItems(int $invoiceId, array $documentItems, bool $replace = false): void
    {
        $table = $this->resolveTable([
            'trxFINInvoiceDocument',
            'trxFINInvoiceDocuments',
        ]);

        if (!$table) {
            return;
        }

        $invoiceColumn = $this->resolveColumn($table, ['trxFINInvoiceID', 'InvoiceID']);
        if (!$invoiceColumn) {
            return;
        }

        if ($replace) {
            DB::table($table)->where($invoiceColumn, $invoiceId)->delete();
        }

        foreach ($documentItems as $item) {
            $data = [];
            $data[$invoiceColumn] = $invoiceId;

            $this->setIfColumn($table, $data, ['DocumentNumber'], $item['documentNumber'] ?? null);
            $this->setIfColumn($table, $data, ['Remark', 'Remarks'], $item['remark'] ?? null);
            $this->setIfColumn($table, $data, ['IsMandatory'], $this->normalizeBool($item['isMandatory'] ?? null));
            $this->setIfColumn($table, $data, ['DocumentType'], $item['documentType'] ?? null);
            $this->setIfColumn($table, $data, ['FileSize'], $item['fileSize'] ?? null);
            $this->setIfColumn($table, $data, ['FilePath', 'FileName'], $item['filePath'] ?? null);

            $this->setAuditColumns($table, $data, true);
            DB::table($table)->insert($data);
        }
    }

    private function applyTextFilter($query, string $table, $value, array $candidates): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $column = $this->resolveColumn($table, $candidates);
        if ($column) {
            $query->where($column, 'like', '%' . $value . '%');
        }
    }

    private function applyExactFilter($query, string $table, $value, array $candidates): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $column = $this->resolveColumn($table, $candidates);
        if ($column) {
            $query->where($column, $value);
        }
    }

    private function resolveTable(array $candidates): ?string
    {
        foreach ($candidates as $table) {
            if (Schema::hasTable($table)) {
                return $table;
            }
        }
        return null;
    }

    private function resolveColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }
        return null;
    }

    private function setIfColumn(string $table, array &$data, array $candidates, $value): void
    {
        $column = $this->resolveColumn($table, $candidates);
        if ($column !== null) {
            $data[$column] = $value;
        }
    }

    private function setAuditColumns(string $table, array &$data, bool $isCreate): void
    {
        $user = Auth::user();
        $employee = session('employee');
        $userIdentifier = $employee->Employ_Id ?? $employee->EmployId ?? $user->username ?? $user->email ?? null;

        if ($isCreate) {
            $this->setIfColumn($table, $data, ['CreatedBy', 'createdBy'], $userIdentifier);
            $this->setIfColumn($table, $data, ['CreatedDate', 'createdDate'], now());
        }

        $this->setIfColumn($table, $data, ['UpdatedBy', 'updatedBy'], $userIdentifier);
        $this->setIfColumn($table, $data, ['UpdatedDate', 'updatedDate'], now());
    }

    private function normalizeBool($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return $normalized === null ? null : ($normalized ? 1 : 0);
    }

    private function generateInvoiceRequestNumber(string $companyId): string
    {
        $context = [
            'COMPANY' => $companyId,
        ];

        try {
            return $this->documentCounter->generateNumber('APPO', $context);
        } catch (\Throwable $e) {
            return $this->documentCounter->generateNumber('Invoice', $context);
        }
    }

    private function resolveCompanyIdFromPO(string $purchOrderId): ?string
    {
        if (!Schema::hasTable('trxPROPurchaseOrder')) {
            return null;
        }

        $query = DB::table('trxPROPurchaseOrder')->where('PurchaseOrderNumber', $purchOrderId);

        $companyIdColumn = $this->resolveColumn('trxPROPurchaseOrder', ['CompanyID', 'CompanyId', 'Company', 'CompanyCode']);
        if ($companyIdColumn) {
            $row = $query->first([$companyIdColumn]);
            if ($row && isset($row->{$companyIdColumn})) {
                $value = trim((string) $row->{$companyIdColumn});
                return $value !== '' ? $value : null;
            }
        }

        return null;
    }

    private function resolveSeqTopFromPO(string $purchOrderId): ?int
    {
        if ($purchOrderId === '' || !Schema::hasTable('trxPROPurchaseOrderAssignVendor')) {
            return null;
        }

        $table = 'trxPROPurchaseOrderAssignVendor';
        $poColumn = $this->resolveColumn($table, ['trxPROPurchaseOrderNumber', 'PurchaseOrderNumber', 'PurchOrderID']);
        $seqTopColumn = $this->resolveColumn($table, ['SeqTOP', 'seqTOP', 'TrxFINInvoiceTOPID', 'trxFINInvoiceTOPID']);
        if (!$poColumn || !$seqTopColumn) {
            return null;
        }

        $row = DB::table($table)->where($poColumn, $purchOrderId)->first([$seqTopColumn]);
        if ($row && isset($row->{$seqTopColumn})) {
            $value = $row->{$seqTopColumn};
            return is_numeric($value) ? (int) $value : null;
        }

        return null;
    }

    private function resolveSiteIdFromPO(string $purchOrderId): ?string
    {
        if ($purchOrderId === '' || !Schema::hasTable('trxPROPurchaseOrder')) {
            return null;
        }

        $poTable = 'trxPROPurchaseOrder';
        $poNumberColumn = $this->resolveColumn($poTable, ['PurchaseOrderNumber', 'PurchOrderID', 'trxPROPurchaseOrderNumber']);
        $prNumberColumn = $this->resolveColumn($poTable, ['trxPROPurchaseRequestNumber', 'PurchaseRequestNumber']);
        if (!$poNumberColumn || !$prNumberColumn) {
            return null;
        }

        $poRow = DB::table($poTable)->where($poNumberColumn, $purchOrderId)->first([$prNumberColumn]);
        if (!$poRow || !isset($poRow->{$prNumberColumn})) {
            return null;
        }

        $prNumber = trim((string) $poRow->{$prNumberColumn});
        if ($prNumber === '' || !Schema::hasTable('trxPROPurchaseRequestAdditional')) {
            return null;
        }

        $prTable = 'trxPROPurchaseRequestAdditional';
        $prColumn = $this->resolveColumn($prTable, ['trxPROPurchaseRequestNumber', 'PurchaseRequestNumber']);
        $siteColumn = $this->resolveColumn($prTable, ['SiteID', 'SiteId']);
        if (!$prColumn || !$siteColumn) {
            return null;
        }

        $prRow = DB::table($prTable)->where($prColumn, $prNumber)->first([$siteColumn]);
        if ($prRow && isset($prRow->{$siteColumn})) {
            $value = trim((string) $prRow->{$siteColumn});
            return $value !== '' ? $value : null;
        }

        return null;
    }

    private function updateAmortizationStatusForInvoice(string $purchOrderId, int $periodNumber, int $invoiceId, string $invoiceNumber, ?string $amortizationType = null): void
    {
        if ($purchOrderId === '' || $periodNumber <= 0 || !Schema::hasTable('trxPROPurchaseOrderAmortization')) {
            return;
        }

        $table = 'trxPROPurchaseOrderAmortization';
        $poColumn = Schema::hasColumn($table, 'trxPROPurchaseOrderNumber')
            ? 'trxPROPurchaseOrderNumber'
            : (Schema::hasColumn($table, 'PurchaseOrderNumber') ? 'PurchaseOrderNumber' : null);
        $periodColumn = Schema::hasColumn($table, 'PeriodNumber')
            ? 'PeriodNumber'
            : (Schema::hasColumn($table, 'Period') ? 'Period' : null);

        if (!$poColumn || !$periodColumn) {
            return;
        }

        $baseQuery = DB::table($table)
            ->where($poColumn, $purchOrderId)
            ->where($periodColumn, $periodNumber);

        if (Schema::hasColumn($table, 'IsCanceled')) {
            $baseQuery->where(function ($q) {
                $q->whereNull('IsCanceled')->orWhere('IsCanceled', false);
            });
        }

        $amortizationTypeColumn = Schema::hasColumn($table, 'AmortizationType') ? 'AmortizationType' : null;
        $candidateTypes = [];
        if ($amortizationTypeColumn) {
            $candidateTypes = $amortizationType ? [$amortizationType] : ['Period', 'Term'];
        }

        $targetRow = null;
        $targetType = null;
        if ($amortizationTypeColumn) {
            foreach ($candidateTypes as $type) {
                $row = (clone $baseQuery)->where($amortizationTypeColumn, $type)->first();
                if ($row) {
                    $targetRow = $row;
                    $targetType = $type;
                    break;
                }
            }
        } else {
            $targetRow = $baseQuery->first();
        }

        if (!$targetRow) {
            return;
        }

        $update = [];
        $statusColumn = $this->resolveColumn($table, ['Status', 'status']);
        if ($statusColumn) {
            $update[$statusColumn] = 'Invoice Submitted';
        }
        $invoiceIdColumn = $this->resolveColumn($table, ['trxFINInvoiceID', 'InvoiceID']);
        if ($invoiceIdColumn) {
            $update[$invoiceIdColumn] = $invoiceId;
        }
        $invoiceNumberColumn = $this->resolveColumn($table, ['InvoiceNumber', 'InvoiceNo']);
        if ($invoiceNumberColumn) {
            $update[$invoiceNumberColumn] = $invoiceNumber !== '' ? $invoiceNumber : null;
        }
        $updatedDateColumn = $this->resolveColumn($table, ['UpdatedDate', 'updatedDate']);
        if ($updatedDateColumn) {
            $update[$updatedDateColumn] = now();
        }
        $updatedByColumn = $this->resolveColumn($table, ['UpdatedBy', 'updatedBy']);
        if ($updatedByColumn) {
            $employeeId = $this->getCurrentEmployeeId();
            $update[$updatedByColumn] = $employeeId;
        }

        if (empty($update)) {
            return;
        }

        $query = DB::table($table)
            ->where($poColumn, $purchOrderId)
            ->where($periodColumn, $periodNumber);
        if ($amortizationTypeColumn && $targetType) {
            $query->where($amortizationTypeColumn, $targetType);
        }

        $query->update($update);
    }

    private function parseTermPositions($termPositionInput): array
    {
        $positions = [];

        if (is_array($termPositionInput)) {
            foreach ($termPositionInput as $value) {
                if (is_numeric($value)) {
                    $positions[] = (int) $value;
                    continue;
                }
                if (is_string($value)) {
                    preg_match_all('/\d+/', $value, $matches);
                    foreach ($matches[0] ?? [] as $match) {
                        $positions[] = (int) $match;
                    }
                }
            }
        } elseif (is_numeric($termPositionInput)) {
            $positions[] = (int) $termPositionInput;
        } elseif (is_string($termPositionInput)) {
            preg_match_all('/\d+/', $termPositionInput, $matches);
            foreach ($matches[0] ?? [] as $match) {
                $positions[] = (int) $match;
            }
        }

        $positions = array_values(array_unique(array_filter($positions, function ($value) {
            return is_int($value) && $value > 0;
        })));

        return $positions;
    }

    private function getCurrentEmployeeId(): ?string
    {
        $user = Auth::user();
        $employee = session('employee');
        return $employee->Employ_Id ?? $employee->EmployId ?? $user->username ?? $user->email ?? null;
    }
}
