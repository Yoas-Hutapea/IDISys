<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TrxInventoryGoodReceiveNote;
use App\Services\BaseService;
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

    private BaseService $grnService;

    public function __construct()
    {
        $this->grnService = new BaseService(new TrxInventoryGoodReceiveNote);
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

        $poNumber = trim((string) $request->input('poNumber', ''));
        $lines = $request->input('lines', []);

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
        }

        return response()->json(['success' => true, 'message' => 'Good Receive Note saved.']);
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
     * Get GRN items for Invoice Create: returns rows from trxInventoryGoodReceiveNote
     * so invoice can bill based on actual received qty/amount.
     * Shape compatible with PO items: ItemQty = ActualReceived, Amount = GRN Amount.
     */
    public function itemsForInvoice(string $poNumber)
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
        $rows = $query->orderBy('mstPROPurchaseItemInventoryItemID')->get();

        $data = $rows->map(function ($row) {
            return [
                'mstPROPurchaseItemInventoryItemID' => $row->mstPROPurchaseItemInventoryItemID ?? null,
                'ItemName' => $row->ItemName ?? null,
                'itemName' => $row->ItemName ?? null,
                'ItemDescription' => $row->ItemDescription ?? null,
                'itemDescription' => $row->ItemDescription ?? null,
                'ItemUnit' => $row->ItemUnit ?? null,
                'itemUnit' => $row->ItemUnit ?? null,
                'ItemQty' => $row->ActualReceived ?? 0,
                'itemQty' => $row->ActualReceived ?? 0,
                'ActualReceived' => $row->ActualReceived ?? 0,
                'CurrencyCode' => $row->CurrencyCode ?? null,
                'UnitPrice' => $row->UnitPrice ?? 0,
                'unitPrice' => $row->UnitPrice ?? 0,
                'Amount' => $row->Amount ?? 0,
                'amount' => $row->Amount ?? 0,
            ];
        })->values();

        return response()->json(['data' => $data]);
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
