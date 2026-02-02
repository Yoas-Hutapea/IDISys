<?php

namespace App\Modules\Finance\Controllers\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InvoicesController extends Controller
{
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

        $query = DB::table($table);

        $this->applyTextFilter($query, $table, $request->query('invoiceNumber'), ['InvoiceNumber', 'InvoiceNo']);
        $this->applyTextFilter($query, $table, $request->query('requestNumber'), ['RequestNumber', 'PurchaseRequestNumber', 'trxPROPurchaseRequestNumber']);
        $this->applyTextFilter($query, $table, $request->query('purchOrderID'), ['PurchOrderID', 'PurchaseOrderNumber', 'trxPROPurchaseOrderNumber']);

        $this->applyExactFilter($query, $table, $request->query('purchaseTypeID'), ['PurchaseTypeID', 'mstPROPurchaseTypeID']);
        $this->applyExactFilter($query, $table, $request->query('purchaseSubTypeID'), ['PurchaseSubTypeID', 'mstPROPurchaseSubTypeID']);
        $this->applyExactFilter($query, $table, $request->query('companyID'), ['CompanyID', 'mstCompanyID']);
        $this->applyExactFilter($query, $table, $request->query('workTypeID'), ['WorkTypeID', 'mstFINInvoiceWorkTypeID']);
        $this->applyExactFilter($query, $table, $request->query('statusID'), ['StatusID', 'mstApprovalStatusID', 'ApprovalStatusID']);

        $dateColumn = $this->resolveColumn($table, ['InvoiceDate', 'CreatedDate']);
        if ($dateColumn) {
            if ($request->filled('startDate')) {
                $query->whereDate($dateColumn, '>=', $request->query('startDate'));
            }
            if ($request->filled('endDate')) {
                $query->whereDate($dateColumn, '<=', $request->query('endDate'));
            }
        }

        return response()->json($query->get());
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

        $data = $this->buildInvoiceData($table, $request);
        $this->setAuditColumns($table, $data, true);

        $idColumn = $this->resolveColumn($table, ['ID', 'Id', 'InvoiceID', 'trxFINInvoiceID']);
        if ($idColumn) {
            $newId = DB::table($table)->insertGetId($data, $idColumn);
            $this->syncDetailItems($newId, $request->input('detailItems', []));
            $this->syncDocumentItems($newId, $request->input('documentItems', []));

            $row = DB::table($table)->where($idColumn, $newId)->first();
            return response()->json($row);
        }

        DB::table($table)->insert($data);
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
        $this->setIfColumn($table, $data, ['TaxCode'], $request->input('taxCode'));
        $this->setIfColumn($table, $data, ['TaxValue'], $request->input('taxValue'));
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
}
