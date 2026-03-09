<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MstEmployee;
use App\Models\TrxInventoryGoodReceiveNote;
use App\Models\TrxInventoryGoodReceiveNoteHeader;
use App\Services\BaseService;
use App\Services\DocumentCounterService;
use App\Services\PurchaseOrderAmortizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Good Receive Notes: simpan data penerimaan barang per PO item.
 * Menggunakan BaseService agar CreatedBy, CreatedDate, UpdatedBy, UpdatedDate, IsActive terisi otomatis (sama seperti PR).
 */
class GoodReceiveNotesController extends Controller
{
    private const TABLE_NAME = 'trxInventoryGoodReceiveNote';
    private const HEADER_TABLE_NAME = 'trxInventoryGoodReceiveNoteHeader';
    private const DOCUMENT_TABLE_NAME = 'trxInventoryGoodReceiveNoteDocument';

    private BaseService $grnService;
    private BaseService $grnHeaderService;
    private array $employeeNameCache = [];

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
        $linesInput = $request->input('lines', []);
        $lines = $this->normalizeLinesPayload($linesInput);
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
        if (!Schema::hasTable(self::DOCUMENT_TABLE_NAME)) {
            return response()->json(['success' => false, 'message' => 'Table ' . self::DOCUMENT_TABLE_NAME . ' not found.'], 500);
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

        foreach ($lines as $line) {
            $itemId = trim((string) ($line['mstPROPurchaseItemInventoryItemID'] ?? $line['itemId'] ?? ''));
            if ($itemId === '' || !isset($itemMap[$itemId])) {
                continue;
            }

            $item = $itemMap[$itemId];
            $itemQty = (float) ($item->ItemQty ?? $item->itemQty ?? 0);
            $actualReceived = isset($line['actualReceived']) ? (float) $line['actualReceived'] : $itemQty;
            if ($actualReceived > $itemQty) {
                return response()->json([
                    'success' => false,
                    'message' => "Actual Received for item {$itemId} cannot exceed Item Qty.",
                ], 422);
            }

            $remark = trim((string) ($line['remark'] ?? $line['Remark'] ?? ''));
            $qtyRemain = max(0, $itemQty - $actualReceived);
            if ($qtyRemain > 0 && $remark === '') {
                return response()->json([
                    'success' => false,
                    'message' => "Remark is required for item {$itemId} when Qty Remain is not zero.",
                ], 422);
            }
        }

        try {
            [$header, $savedLineCount, $savedDocuments] = DB::transaction(function () use ($request, $poNumber, $lines, $itemMap, $userId, $headerRemark) {
                $header = $this->createHeader($poNumber, $headerRemark, $lines, $userId);
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
                $savedDocuments = $this->saveDocumentFiles($request, $poNumber, (string) ($header->GoodReceiveNoteNumber ?? ''), $userId);
                return [$header, $savedLineCount, $savedDocuments];
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
            'savedDocuments' => $savedDocuments ?? 0,
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
            ->get()
            ->map(function ($row) {
                $row->createdBy = $this->resolveEmployeeDisplayName($row->createdBy ?? null);
                $row->updatedBy = $this->resolveEmployeeDisplayName($row->updatedBy ?? null);
                return $row;
            })->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    public function approvalGrid(Request $request)
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
                'h.IsApproved as isApproved',
                'h.CreatedBy as createdBy',
                'h.CreatedDate as createdDate',
                'h.UpdatedBy as updatedBy',
                'h.UpdatedDate as updatedDate',
            ]);

        if (Schema::hasColumn(self::HEADER_TABLE_NAME, 'IsActive')) {
            $baseQuery->where('h.IsActive', true);
        }

        $recordsTotal = (clone $baseQuery)->count('h.ID');
        $filteredQuery = $this->applyApprovalGridFilters($baseQuery, $request);
        $recordsFiltered = (clone $filteredQuery)->count('h.ID');

        $order = $request->input('order.0', []);
        $columns = $request->input('columns', []);
        $orderColumnIndex = $order['column'] ?? null;
        $orderDir = strtolower((string) ($order['dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($orderColumnIndex !== null && isset($columns[$orderColumnIndex]['data'])) {
            $orderColumn = $this->mapApprovalOrderColumn((string) $columns[$orderColumnIndex]['data']);
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
            ->get()
            ->map(function ($row) {
                $isApproved = (int) ($row->isApproved ?? 0) === 1;
                $hasDecision = !empty($row->updatedDate);
                $status = $isApproved ? 'Approved' : ($hasDecision ? 'Rejected' : 'Waiting Approval');
                return [
                    'id' => $row->id ?? null,
                    'goodReceiveNoteNumber' => $row->goodReceiveNoteNumber ?? null,
                    'poNumber' => $row->poNumber ?? null,
                    'remark' => $row->remark ?? null,
                    'isApproved' => $isApproved ? 1 : 0,
                    'approvalStatus' => $status,
                    'createdBy' => $this->resolveEmployeeDisplayName($row->createdBy ?? null),
                    'createdDate' => $row->createdDate ?? null,
                    'updatedBy' => $this->resolveEmployeeDisplayName($row->updatedBy ?? null),
                    'updatedDate' => $row->updatedDate ?? null,
                ];
            })->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    public function approvalDecision(string $grNumber, Request $request)
    {
        $decodedNumber = urldecode($grNumber);
        $decision = strtolower(trim((string) ($request->input('decision') ?? '')));
        if (!in_array($decision, ['approve', 'reject'], true)) {
            return response()->json(['success' => false, 'message' => 'Invalid decision. Use approve or reject.'], 422);
        }

        $header = TrxInventoryGoodReceiveNoteHeader::query()
            ->where('GoodReceiveNoteNumber', $decodedNumber)
            ->where('IsActive', true)
            ->first();

        if (!$header) {
            return response()->json(['success' => false, 'message' => 'GR header not found.'], 404);
        }

        $userId = (string) (optional(Auth::user())->Username ?? Auth::id() ?? $this->getCurrentEmployeeId() ?? 'System');
        $remark = trim((string) ($request->input('remark') ?? ''));
        $updateData = [
            'IsApproved' => $decision === 'approve' ? true : false,
        ];
        if ($remark !== '') {
            $updateData['Remark'] = $remark;
        }

        $this->grnHeaderService->update($header, $updateData, $userId);

        return response()->json([
            'success' => true,
            'message' => $decision === 'approve' ? 'GR approved.' : 'GR rejected.',
        ]);
    }

    public function documents(string $grNumber)
    {
        $decodedNumber = urldecode($grNumber);
        if (!Schema::hasTable(self::DOCUMENT_TABLE_NAME)) {
            return response()->json([]);
        }

        $query = DB::table(self::DOCUMENT_TABLE_NAME)
            ->where('trxInventoryGoodReceiveNoteNumber', $decodedNumber);
        if (Schema::hasColumn(self::DOCUMENT_TABLE_NAME, 'IsActive')) {
            $query->where('IsActive', true);
        }

        $documents = $query
            ->orderByDesc('ID')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->ID ?? null,
                    'ID' => $row->ID ?? null,
                    'fileName' => $row->FileName ?? null,
                    'FileName' => $row->FileName ?? null,
                    'fileSize' => $row->FileSize ?? null,
                    'FileSize' => $row->FileSize ?? null,
                    'filePath' => $row->FilePath ?? null,
                    'FilePath' => $row->FilePath ?? null,
                ];
            })->values();

        return response()->json($documents);
    }

    public function downloadDocument(string $documentId)
    {
        if (!Schema::hasTable(self::DOCUMENT_TABLE_NAME)) {
            return response()->json(['message' => 'Document table not found'], 404);
        }

        $query = DB::table(self::DOCUMENT_TABLE_NAME)
            ->where('ID', $documentId);
        if (Schema::hasColumn(self::DOCUMENT_TABLE_NAME, 'IsActive')) {
            $query->where('IsActive', true);
        }
        $document = $query->first();

        if (!$document) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        $fileName = (string) ($document->FileName ?? 'document');
        $filePath = ltrim((string) ($document->FilePath ?? ''), '/');
        $resolvedPath = null;

        if ($filePath !== '') {
            if (Storage::disk('public')->exists($filePath)) {
                $resolvedPath = Storage::disk('public')->path($filePath);
            } else {
                $candidate = public_path('storage' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath));
                if (is_file($candidate)) {
                    $resolvedPath = $candidate;
                }
            }
        }

        if ($resolvedPath === null || !is_file($resolvedPath)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $preview = request()->boolean('preview');
        if ($preview) {
            return response()->file($resolvedPath, [
                'Content-Disposition' => 'inline; filename="' . addslashes($fileName) . '"'
            ]);
        }

        return response()->download($resolvedPath, $fileName);
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

    private function createHeader(string $poNumber, string $headerRemark, array $lines, string $userId): TrxInventoryGoodReceiveNoteHeader
    {
        $remark = $headerRemark !== '' ? $headerRemark : $this->extractHeaderRemarkFromLines($lines);

        $companyCode = $this->resolveCompanyCodeFromPO($poNumber);
        $grNumber = $this->documentCounter->generateNumber('GR', [
            'COMPANY' => $companyCode,
        ]);

        /** @var TrxInventoryGoodReceiveNoteHeader $created */
        $created = $this->grnHeaderService->create([
            'GoodReceiveNoteNumber' => $grNumber,
            'trxPROPurchaseOrderNumber' => $poNumber,
            'Remark' => $remark !== '' ? $remark : null,
            'IsApproved' => false,
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

    private function applyApprovalGridFilters($query, Request $request)
    {
        $grNumber = trim((string) $request->input('grNumber', ''));
        $poNumber = trim((string) $request->input('poNumber', ''));
        $approvalStatus = trim((string) $request->input('approvalStatus', ''));
        $createdStartDate = trim((string) $request->input('createdStartDate', ''));
        $createdEndDate = trim((string) $request->input('createdEndDate', ''));

        if ($grNumber !== '') {
            $query->where('h.GoodReceiveNoteNumber', 'like', '%' . $grNumber . '%');
        }
        if ($poNumber !== '') {
            $query->where('h.trxPROPurchaseOrderNumber', 'like', '%' . $poNumber . '%');
        }
        if ($approvalStatus !== '') {
            if (strtolower($approvalStatus) === 'approved') {
                $query->where('h.IsApproved', true);
            } elseif (strtolower($approvalStatus) === 'rejected') {
                $query->where('h.IsApproved', false)->whereNotNull('h.UpdatedDate');
            } elseif (strtolower($approvalStatus) === 'waiting') {
                $query->where('h.IsApproved', false)->whereNull('h.UpdatedDate');
            }
        } else {
            $query->where('h.IsApproved', false)->whereNull('h.UpdatedDate');
        }
        if ($createdStartDate !== '') {
            $query->whereDate('h.CreatedDate', '>=', $createdStartDate);
        }
        if ($createdEndDate !== '') {
            $query->whereDate('h.CreatedDate', '<=', $createdEndDate);
        }

        return $query;
    }

    private function mapApprovalOrderColumn(string $columnKey): ?string
    {
        return match ($columnKey) {
            'goodReceiveNoteNumber' => 'h.GoodReceiveNoteNumber',
            'poNumber' => 'h.trxPROPurchaseOrderNumber',
            'approvalStatus' => 'h.IsApproved',
            'createdBy' => 'h.CreatedBy',
            'createdDate' => 'h.CreatedDate',
            'updatedBy' => 'h.UpdatedBy',
            'updatedDate' => 'h.UpdatedDate',
            default => null,
        };
    }

    private function normalizeLinesPayload($linesInput): array
    {
        if (is_array($linesInput)) {
            return $linesInput;
        }

        if (is_string($linesInput) && trim($linesInput) !== '') {
            $decoded = json_decode($linesInput, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    private function saveDocumentFiles(Request $request, string $poNumber, string $grNumber, string $userId): int
    {
        if ($grNumber === '') {
            return 0;
        }

        $files = $request->file('documents', []);
        if (!$request->hasFile('documents')) {
            return 0;
        }
        if (!is_array($files)) {
            $files = [$files];
        }

        $savedCount = 0;
        $now = now();

        foreach ($files as $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }

            $timestamp = now()->format('YmdHis');
            $originalName = $file->getClientOriginalName();
            $fileSizeKb = (string) round($file->getSize() / 1024);
            $filename = $timestamp . '_' . $originalName;
            $relativeDir = "Inventory/GoodReceiveNotes/{$grNumber}";
            $relativePath = "{$relativeDir}/{$filename}";

            $publicStorageRoot = public_path('storage');
            if (!is_dir($publicStorageRoot)) {
                @mkdir($publicStorageRoot, 0775, true);
            }

            if (is_link($publicStorageRoot)) {
                Storage::disk('public')->putFileAs($relativeDir, $file, $filename);
            } else {
                $targetDir = $publicStorageRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0775, true);
                }
                $file->move($targetDir, $filename);
            }

            DB::table(self::DOCUMENT_TABLE_NAME)->insert([
                'trxPROPurchaseOrderNumber' => $poNumber,
                'trxInventoryGoodReceiveNoteNumber' => $grNumber,
                'FileName' => $originalName,
                'FileSize' => $fileSizeKb,
                'FilePath' => '/' . $relativePath,
                'CreatedBy' => $userId,
                'CreatedDate' => $now,
                'UpdatedBy' => $userId,
                'UpdatedDate' => $now,
                'IsActive' => true,
            ]);

            $savedCount++;
        }

        return $savedCount;
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

    private function resolveEmployeeDisplayName(?string $employeeId): ?string
    {
        $employeeId = trim((string) $employeeId);
        if ($employeeId === '' || $employeeId === '-') {
            return $employeeId !== '' ? $employeeId : null;
        }

        if (str_contains($employeeId, ' ')) {
            return $employeeId;
        }

        $cacheKey = strtolower($employeeId);
        if (array_key_exists($cacheKey, $this->employeeNameCache)) {
            return $this->employeeNameCache[$cacheKey] ?: $employeeId;
        }

        $employee = MstEmployee::query()
            ->where('Employ_Id', $employeeId)
            ->orWhere('Employ_Id_TBGSYS', $employeeId)
            ->first();

        $name = trim((string) ($employee->Employ_Name ?? $employee->EmployeeName ?? $employee->name ?? ''));
        $this->employeeNameCache[$cacheKey] = $name;

        return $name !== '' ? $name : $employeeId;
    }
}
