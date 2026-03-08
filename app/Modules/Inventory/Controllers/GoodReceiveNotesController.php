<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TrxInventoryGoodReceiveNote;
use App\Models\TrxInventoryGoodReceiveNoteHeader;
use App\Services\BaseService;
use App\Services\DocumentCounterService;
use App\Services\PurchaseOrderAmortizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;

/**
 * Good Receive Notes: simpan data penerimaan barang per PO item.
 * Menggunakan BaseService agar CreatedBy, CreatedDate, UpdatedBy, UpdatedDate, IsActive terisi otomatis (sama seperti PR).
 */
class GoodReceiveNotesController extends Controller
{
    private const TABLE_NAME = 'trxInventoryGoodReceiveNote';
    private const HEADER_TABLE_NAME = 'trxInventoryGoodReceiveNoteHeader';

    private BaseService $grnService;
    private BaseService $grnHeaderService;

    public function __construct(private readonly DocumentCounterService $documentCounter)
    {
        $this->grnService = new BaseService(new TrxInventoryGoodReceiveNote);
        $this->grnHeaderService = new BaseService(new TrxInventoryGoodReceiveNoteHeader);
    }

    /**
     * Simpan GRN lines untuk satu PO.
     * Body: { "poNumber": "...", "lines": [ { "mstPROPurchaseItemInventoryItemID", "actualReceived", "remark", "tanggalTerima" } ] }
     */
    public function save(Request $request)
    {
        if (!Schema::hasTable(self::TABLE_NAME)) {
            return response()->json(['success' => false, 'message' => 'Table ' . self::TABLE_NAME . ' not found.'], 500);
        }
        if (!Schema::hasTable(self::HEADER_TABLE_NAME)) {
            return response()->json(['success' => false, 'message' => 'Table ' . self::HEADER_TABLE_NAME . ' not found.'], 500);
        }

        $poNumber = trim((string) $request->input('poNumber', ''));
        $lines = $request->input('lines', []);
        $action = strtolower(trim((string) $request->input('action', 'save')));
        if (!in_array($action, ['save', 'submit'], true)) {
            $action = 'save';
        }
        $headerRemark = trim((string) $request->input('remark', ''));

        if ($poNumber === '') {
            return response()->json(['success' => false, 'message' => 'PO Number is required.'], 422);
        }

        if (!is_array($lines)) {
            return response()->json(['success' => false, 'message' => 'lines must be an array.'], 422);
        }

        $userId = (string) (optional(Auth::user())->Username ?? Auth::id() ?? $this->getCurrentEmployeeId() ?? 'System');

        $poItems = $this->getPOItems($poNumber);
        if (empty($poItems)) {
            return response()->json(['success' => false, 'message' => 'PO not found or has no items.'], 404);
        }

        $itemMap = [];
        foreach ($poItems as $item) {
            $itemId = trim((string) ($item->mstPROPurchaseItemInventoryItemID ?? $item->ItemID ?? ''));
            if ($itemId !== '') {
                $itemMap[$itemId] = $item;
            }
        }

        try {
            [$header, $savedLineCount] = DB::transaction(function () use ($poNumber, $lines, $itemMap, $userId, $headerRemark) {
                $header = $this->createOrUpdateHeader($poNumber, $headerRemark, $lines, $userId);
                $savedLineCount = 0;

                foreach ($lines as $line) {
                    $itemId = trim((string) ($line['mstPROPurchaseItemInventoryItemID'] ?? $line['itemId'] ?? ''));
                    if ($itemId === '' || !isset($itemMap[$itemId])) {
                        continue;
                    }

                    $item = $itemMap[$itemId];
                    $actualReceived = isset($line['actualReceived']) ? (float) $line['actualReceived'] : (float) ($item->ItemQty ?? 0);
                    $unitPrice = (float) ($item->UnitPrice ?? $item->unitPrice ?? 0);
                    $amount = $actualReceived * $unitPrice;
                    $remark = trim((string) ($line['remark'] ?? $line['Remark'] ?? ''));
                    $tanggalTerima = $line['tanggalTerima'] ?? $line['TanggalTerima'] ?? null;
                    if ($tanggalTerima !== null && $tanggalTerima !== '') {
                        try {
                            $tanggalTerima = \Illuminate\Support\Carbon::parse($tanggalTerima)->format('Y-m-d H:i:s');
                        } catch (\Throwable $e) {
                            $tanggalTerima = null;
                        }
                    } else {
                        $tanggalTerima = null;
                    }

                    $data = [
                        'trxPROPurchaseOrderNumber' => $poNumber,
                        'mstPROPurchaseItemInventoryItemID' => $itemId,
                        'ItemName' => $item->ItemName ?? $item->itemName ?? null,
                        'ItemDescription' => $item->ItemDescription ?? $item->itemDescription ?? null,
                        'ItemUnit' => $item->ItemUnit ?? $item->itemUnit ?? null,
                        'ItemQty' => $item->ItemQty ?? $item->itemQty ?? 0,
                        'ActualReceived' => $actualReceived,
                        'CurrencyCode' => $item->CurrencyCode ?? $item->currencyCode ?? null,
                        'UnitPrice' => $unitPrice,
                        'Amount' => $amount,
                        'Remark' => $remark,
                        'TanggalTerima' => $tanggalTerima,
                    ];

                    $existing = TrxInventoryGoodReceiveNote::query()
                        ->where('trxPROPurchaseOrderNumber', $poNumber)
                        ->where('mstPROPurchaseItemInventoryItemID', $itemId)
                        ->first();

                    if ($existing) {
                        $this->grnService->update($existing, $data, $userId);
                    } else {
                        $this->grnService->create($data, $userId);
                    }
                    $savedLineCount++;
                }
                return [$header, $savedLineCount];
            });
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Failed to save Good Receive Note.'], 500);
        }

        try {
            PurchaseOrderAmortizationService::recalculateFromGRN($poNumber);
        } catch (\Throwable $e) {
            // Don't fail GRN save if amortization recalc fails (e.g. table/column missing)
            report($e);
        }

        return response()->json([
            'success' => true,
            'message' => $action === 'submit' ? 'Good Receive Note submitted.' : 'Good Receive Note saved.',
            'goodReceiveNoteNumber' => $header->GoodReceiveNoteNumber ?? null,
            'savedLines' => $savedLineCount,
        ]);
    }

    public function headersGrid(Request $request)
    {
        if (!Schema::hasTable(self::HEADER_TABLE_NAME)) {
            return response()->json([
                'draw' => (int) $request->input('draw', 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $draw = (int) $request->input('draw', 0);
        $start = (int) ($request->input('start', 0));
        $length = (int) ($request->input('length', 10));

        $baseQuery = DB::table(self::HEADER_TABLE_NAME . ' as h')
            ->select([
                'h.ID as id',
                'h.GoodReceiveNoteNumber as goodReceiveNoteNumber',
                'h.trxPROPurchaseOrderNumber as poNumber',
                'h.Remark as remark',
                'h.CreatedBy as createdBy',
                'h.CreatedDate as createdDate',
                'h.UpdatedBy as updatedBy',
                'h.UpdatedDate as updatedDate',
            ]);

        if (Schema::hasColumn(self::HEADER_TABLE_NAME, 'IsActive')) {
            $baseQuery->where('h.IsActive', true);
        }

        $recordsTotal = (clone $baseQuery)->count('h.ID');

        $filteredQuery = $this->applyHeaderGridFilters($baseQuery, $request);
        $recordsFiltered = (clone $filteredQuery)->count('h.ID');

        $order = $request->input('order.0', []);
        $columns = $request->input('columns', []);
        $orderColumnIndex = $order['column'] ?? null;
        $orderDir = strtolower((string) ($order['dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($orderColumnIndex !== null && isset($columns[$orderColumnIndex]['data'])) {
            $orderColumn = $this->mapHeaderOrderColumn((string) $columns[$orderColumnIndex]['data']);
            if ($orderColumn !== null) {
                $filteredQuery->orderBy($orderColumn, $orderDir);
            } else {
                $filteredQuery->orderBy('h.CreatedDate', 'desc');
            }
        } else {
            $filteredQuery->orderBy('h.CreatedDate', 'desc');
        }

        $rows = $filteredQuery
            ->skip($start)
            ->take($length > 0 ? $length : $recordsFiltered)
            ->get();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    /**
     * Get existing GRN lines for a PO (untuk pre-fill form).
     */
    public function lines(string $poNumber)
    {
        $decoded = urldecode($poNumber);
        if (!Schema::hasTable(self::TABLE_NAME)) {
            return response()->json(['data' => []]);
        }

        $query = DB::table(self::TABLE_NAME)
            ->where('trxPROPurchaseOrderNumber', $decoded);
        if (Schema::hasColumn(self::TABLE_NAME, 'IsActive')) {
            $query->where('IsActive', true);
        }
        $rows = $query->get();

        $data = $rows->map(function ($row) {
            return [
                'mstPROPurchaseItemInventoryItemID' => $row->mstPROPurchaseItemInventoryItemID ?? null,
                'actualReceived' => $row->ActualReceived ?? null,
                'amount' => $row->Amount ?? null,
                'remark' => $row->Remark ?? null,
                'tanggalTerima' => $row->TanggalTerima ?? null,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    /**
     * Get items for Invoice Create: returns GRN items (trxInventoryGoodReceiveNote) when the PO
     * has any GRN lines; otherwise returns PO items from trxPROPurchaseOrderItem.
     * Shape is always compatible with invoice item list (ItemName, ItemQty, UnitPrice, Amount, etc.).
     */
    public function itemsForInvoice(string $poNumber)
    {
        $decoded = urldecode($poNumber);

        if (Schema::hasTable(self::TABLE_NAME)) {
            $orderColumn = Schema::hasColumn(self::TABLE_NAME, 'mstPROPurchaseItemInventoryItemID')
                ? 'mstPROPurchaseItemInventoryItemID'
                : 'ID';
            $rows = collect([]);

            foreach (['trxPROPurchaseOrderNumber', 'PurchaseOrderNumber'] as $candidate) {
                if (!Schema::hasColumn(self::TABLE_NAME, $candidate)) {
                    continue;
                }
                $query = DB::table(self::TABLE_NAME)->where($candidate, $decoded);
                if (Schema::hasColumn(self::TABLE_NAME, 'IsActive')) {
                    $query->where('IsActive', true);
                }
                $rows = $query->orderBy($orderColumn)->get();
                if ($rows->isNotEmpty()) {
                    break;
                }
            }

            if ($rows->isNotEmpty()) {
                $poItems = $this->getPOItems($decoded);
                $poQtyByItem = [];
                foreach ($poItems as $poItem) {
                    $itemId = $poItem->mstPROPurchaseItemInventoryItemID ?? $poItem->MstPROPurchaseItemInventoryItemID ?? null;
                    if ($itemId !== null) {
                        $poQtyByItem[$itemId] = (float) ($poItem->ItemQty ?? $poItem->itemQty ?? 0);
                    }
                }

                $data = $rows->map(function ($row) use ($poQtyByItem) {
                    $itemId = $row->mstPROPurchaseItemInventoryItemID ?? null;
                    $orderQty = $itemId !== null ? ($poQtyByItem[$itemId] ?? 0) : 0;
                    $actualReceived = (float) ($row->ActualReceived ?? 0);
                    return [
                        'mstPROPurchaseItemInventoryItemID' => $itemId,
                        'ItemName' => $row->ItemName ?? null,
                        'itemName' => $row->ItemName ?? null,
                        'ItemDescription' => $row->ItemDescription ?? null,
                        'itemDescription' => $row->ItemDescription ?? null,
                        'ItemUnit' => $row->ItemUnit ?? null,
                        'itemUnit' => $row->ItemUnit ?? null,
                        'ItemQty' => $actualReceived,
                        'itemQty' => $actualReceived,
                        'OrderQty' => $orderQty,
                        'orderQty' => $orderQty,
                        'ActualReceived' => $actualReceived,
                        'CurrencyCode' => $row->CurrencyCode ?? null,
                        'UnitPrice' => $row->UnitPrice ?? 0,
                        'unitPrice' => $row->UnitPrice ?? 0,
                        'Amount' => $row->Amount ?? 0,
                        'amount' => $row->Amount ?? 0,
                    ];
                })->values();

                return response()->json(['items' => $data, 'source' => 'grn']);
            }
        }

        // No GRN items for this PO: return items from trxPROPurchaseOrderItem (same shape for invoice list)
        $poItems = $this->getPOItems($decoded);
        $data = collect($poItems)->map(function ($item) {
            $qty = (float) ($item->ItemQty ?? $item->itemQty ?? 0);
            $unitPrice = (float) ($item->UnitPrice ?? $item->unitPrice ?? 0);
            $amount = (float) ($item->Amount ?? $item->amount ?? 0);
            if ($amount <= 0 && $qty > 0 && $unitPrice >= 0) {
                $amount = $qty * $unitPrice;
            }
            return [
                'mstPROPurchaseItemInventoryItemID' => $item->mstPROPurchaseItemInventoryItemID ?? $item->MstPROPurchaseItemInventoryItemID ?? null,
                'ItemName' => $item->ItemName ?? $item->itemName ?? null,
                'itemName' => $item->ItemName ?? $item->itemName ?? null,
                'ItemDescription' => $item->ItemDescription ?? $item->itemDescription ?? null,
                'itemDescription' => $item->ItemDescription ?? $item->itemDescription ?? null,
                'ItemUnit' => $item->ItemUnit ?? $item->itemUnit ?? null,
                'itemUnit' => $item->ItemUnit ?? $item->itemUnit ?? null,
                'ItemQty' => $qty,
                'itemQty' => $qty,
                'OrderQty' => $qty,
                'orderQty' => $qty,
                'ActualReceived' => $qty,
                'CurrencyCode' => $item->CurrencyCode ?? $item->currencyCode ?? null,
                'UnitPrice' => $unitPrice,
                'unitPrice' => $unitPrice,
                'Amount' => $amount,
                'amount' => $amount,
            ];
        })->values();

        return response()->json(['items' => $data, 'source' => 'po']);
    }

    private function createOrUpdateHeader(string $poNumber, string $headerRemark, array $lines, string $userId): TrxInventoryGoodReceiveNoteHeader
    {
        $existingHeader = TrxInventoryGoodReceiveNoteHeader::query()
            ->where('trxPROPurchaseOrderNumber', $poNumber)
            ->where('IsActive', true)
            ->first();

        $remark = $headerRemark !== '' ? $headerRemark : $this->extractHeaderRemarkFromLines($lines);

        if ($existingHeader) {
            $this->grnHeaderService->update($existingHeader, [
                'Remark' => $remark !== '' ? $remark : ($existingHeader->Remark ?? null),
            ], $userId);
            return $existingHeader->refresh();
        }

        $companyCode = $this->resolveCompanyCodeFromPO($poNumber);
        $grNumber = $this->documentCounter->generateNumber('GR', [
            'COMPANY' => $companyCode,
        ]);

        /** @var TrxInventoryGoodReceiveNoteHeader $created */
        $created = $this->grnHeaderService->create([
            'GoodReceiveNoteNumber' => $grNumber,
            'trxPROPurchaseOrderNumber' => $poNumber,
            'Remark' => $remark !== '' ? $remark : null,
            'IsActive' => true,
        ], $userId);

        return $created;
    }

    private function extractHeaderRemarkFromLines(array $lines): string
    {
        foreach ($lines as $line) {
            $remark = trim((string) ($line['remark'] ?? $line['Remark'] ?? ''));
            if ($remark !== '') {
                return $remark;
            }
        }

        return '';
    }

    private function resolveCompanyCodeFromPO(string $poNumber): string
    {
        $poTable = 'trxPROPurchaseOrder';
        if (!Schema::hasTable($poTable)) {
            return 'IDI';
        }

        $poNumberColumn = $this->resolveExistingColumn($poTable, [
            'PurchaseOrderNumber',
            'trxPROPurchaseOrderNumber',
            'PurchOrderID',
        ]);
        if ($poNumberColumn === null) {
            return 'IDI';
        }

        $companyColumn = $this->resolveExistingColumn($poTable, [
            'Company',
            'CompanyID',
            'CompanyCode',
            'CompanyId',
        ]);
        if ($companyColumn === null) {
            return 'IDI';
        }

        $poRow = DB::table($poTable)
            ->where($poNumberColumn, $poNumber)
            ->first([$companyColumn]);

        $companyCode = trim((string) ($poRow->{$companyColumn} ?? ''));
        return $companyCode !== '' ? $companyCode : 'IDI';
    }

    private function resolveExistingColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function getPOItems(string $poNumber): array
    {
        $table = 'trxPROPurchaseOrderItem';
        if (!Schema::hasTable($table)) {
            return [];
        }

        $query = DB::table($table)->where('trxPROPurchaseOrderNumber', $poNumber);
        if (Schema::hasColumn($table, 'IsActive')) {
            $query->where('IsActive', true);
        }

        return $query->orderBy('ID')->get()->all();
    }

    private function applyHeaderGridFilters($query, Request $request)
    {
        $grNumber = trim((string) $request->input('grNumber', ''));
        $poNumber = trim((string) $request->input('poNumber', ''));
        $createdBy = trim((string) $request->input('createdBy', ''));
        $createdStartDate = trim((string) $request->input('createdStartDate', ''));
        $createdEndDate = trim((string) $request->input('createdEndDate', ''));

        if ($grNumber !== '') {
            $query->where('h.GoodReceiveNoteNumber', 'like', '%' . $grNumber . '%');
        }
        if ($poNumber !== '') {
            $query->where('h.trxPROPurchaseOrderNumber', 'like', '%' . $poNumber . '%');
        }
        if ($createdBy !== '') {
            $query->where('h.CreatedBy', 'like', '%' . $createdBy . '%');
        }
        if ($createdStartDate !== '') {
            $query->whereDate('h.CreatedDate', '>=', $createdStartDate);
        }
        if ($createdEndDate !== '') {
            $query->whereDate('h.CreatedDate', '<=', $createdEndDate);
        }

        return $query;
    }

    private function mapHeaderOrderColumn(string $columnKey): ?string
    {
        return match ($columnKey) {
            'goodReceiveNoteNumber' => 'h.GoodReceiveNoteNumber',
            'poNumber' => 'h.trxPROPurchaseOrderNumber',
            'remark' => 'h.Remark',
            'createdBy' => 'h.CreatedBy',
            'createdDate' => 'h.CreatedDate',
            'updatedBy' => 'h.UpdatedBy',
            'updatedDate' => 'h.UpdatedDate',
            default => null,
        };
    }

    /**
     * Recalculate amortization (trxPROPurchaseOrderAmortization) for this PO from GRN total.
     * Call when opening Create Invoice for a PO that has GRN so Term/Period amounts reflect 27M not PR 32.4M.
     */
    public function recalcAmortization(string $poNumber)
    {
        $decoded = urldecode($poNumber);
        try {
            PurchaseOrderAmortizationService::recalculateFromGRN($decoded);
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function getCurrentEmployeeId()
    {
        $employee = session('employee');
        if ($employee && isset($employee->ID)) {
            return $employee->ID;
        }
        if ($employee && isset($employee->EmployeeID)) {
            return $employee->EmployeeID;
        }
        return null;
    }
}
