<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Recalculate trxPROPurchaseOrderAmortization.InvoiceAmount based on actual received amount (GRN).
 * When GRN exists, term amounts (e.g. 40|40) should be based on received total, not full PO amount.
 */
class PurchaseOrderAmortizationService
{
    /**
     * Recalculate InvoiceAmount for Term-type amortization rows for the given PO.
     * Base amount = sum of GRN line amounts for this PO; if no GRN or sum is 0, use PO amount.
     */
    public static function recalculateFromGRN(string $poNumber): void
    {
        $poNumber = trim($poNumber);
        if ($poNumber === '') {
            return;
        }

        if (!Schema::hasTable('trxPROPurchaseOrderAmortization')) {
            return;
        }

        $poColumn = Schema::hasColumn('trxPROPurchaseOrderAmortization', 'trxPROPurchaseOrderNumber')
            ? 'trxPROPurchaseOrderNumber'
            : (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'PurchaseOrderNumber') ? 'PurchaseOrderNumber' : null);

        if (!$poColumn || !Schema::hasColumn('trxPROPurchaseOrderAmortization', 'InvoiceAmount')) {
            return;
        }

        $baseAmount = self::getGRNTotalAmount($poNumber);
        if ($baseAmount <= 0) {
            $baseAmount = self::getPOAmount($poNumber);
        }
        if ($baseAmount <= 0) {
            return;
        }

        $termRows = DB::table('trxPROPurchaseOrderAmortization')
            ->where($poColumn, $poNumber)
            ->where(function ($q) {
                $col = Schema::hasColumn('trxPROPurchaseOrderAmortization', 'AmortizationType')
                    ? 'AmortizationType' : 'amortizationType';
                $q->where($col, 'Term');
            });

        if (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'IsCanceled')) {
            $termRows->where(function ($q) {
                $q->whereNull('IsCanceled')->orWhere('IsCanceled', false);
            });
        }
        if (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'InvoiceNumber')) {
            $termRows->where(function ($q) {
                $q->whereNull('InvoiceNumber')->orWhere('InvoiceNumber', '');
            });
        }

        $termValueCol = Schema::hasColumn('trxPROPurchaseOrderAmortization', 'TermValue')
            ? 'TermValue' : 'termValue';
        $invoiceAmountCol = 'InvoiceAmount';
        $periodCol = Schema::hasColumn('trxPROPurchaseOrderAmortization', 'PeriodNumber')
            ? 'PeriodNumber' : 'Period';

        $rows = $termRows->get();

        foreach ($rows as $row) {
            $termValue = (float) ($row->{$termValueCol} ?? 0);
            $newInvoiceAmount = ($baseAmount * $termValue) / 100;

            $update = [$invoiceAmountCol => $newInvoiceAmount];
            if (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'UpdatedDate')) {
                $update['UpdatedDate'] = now();
            }
            if (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'UpdatedBy')) {
                $emp = session('employee');
                $update['UpdatedBy'] = $emp && is_object($emp) ? ($emp->ID ?? $emp->EmployeeID ?? null) : ($emp['ID'] ?? $emp['EmployeeID'] ?? null);
            }

            $idCol = Schema::hasColumn('trxPROPurchaseOrderAmortization', 'ID') ? 'ID' : 'id';
            $id = $row->{$idCol} ?? null;
            if ($id !== null) {
                DB::table('trxPROPurchaseOrderAmortization')->where($idCol, $id)->update($update);
            }
        }

        // Also recalculate Period-type rows so Invoice Amount (Before VAT) follows GRN/PO total, not PR
        $periodRows = DB::table('trxPROPurchaseOrderAmortization')
            ->where($poColumn, $poNumber)
            ->where(function ($q) {
                $col = Schema::hasColumn('trxPROPurchaseOrderAmortization', 'AmortizationType')
                    ? 'AmortizationType' : 'amortizationType';
                $q->where($col, 'Period');
            });

        if (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'IsCanceled')) {
            $periodRows->where(function ($q) {
                $q->whereNull('IsCanceled')->orWhere('IsCanceled', false);
            });
        }
        if (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'InvoiceNumber')) {
            $periodRows->where(function ($q) {
                $q->whereNull('InvoiceNumber')->orWhere('InvoiceNumber', '');
            });
        }

        $periodList = $periodRows->get();
        foreach ($periodList as $row) {
            // Each period row gets full base amount (same as release: InvoiceAmount = poAmount per period)
            $update = [$invoiceAmountCol => $baseAmount];
            if (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'UpdatedDate')) {
                $update['UpdatedDate'] = now();
            }
            if (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'UpdatedBy')) {
                $emp = session('employee');
                $update['UpdatedBy'] = $emp && is_object($emp) ? ($emp->ID ?? $emp->EmployeeID ?? null) : ($emp['ID'] ?? $emp['EmployeeID'] ?? null);
            }
            $idCol = Schema::hasColumn('trxPROPurchaseOrderAmortization', 'ID') ? 'ID' : 'id';
            $id = $row->{$idCol} ?? null;
            if ($id !== null) {
                DB::table('trxPROPurchaseOrderAmortization')->where($idCol, $id)->update($update);
            }
        }
    }

    private static function getGRNTotalAmount(string $poNumber): float
    {
        $table = 'trxInventoryGoodReceiveNote';
        if (!Schema::hasTable($table)) {
            return 0;
        }

        $query = DB::table($table)
            ->where('trxPROPurchaseOrderNumber', $poNumber);

        if (Schema::hasColumn($table, 'IsActive')) {
            $query->where('IsActive', true);
        }

        $sum = $query->sum('Amount');
        return (float) $sum;
    }

    private static function getPOAmount(string $poNumber): float
    {
        if (Schema::hasTable('trxPROPurchaseOrder')) {
            $poCol = Schema::hasColumn('trxPROPurchaseOrder', 'PurchaseOrderNumber')
                ? 'PurchaseOrderNumber' : 'trxPROPurchaseOrderNumber';
            $po = DB::table('trxPROPurchaseOrder')
                ->where($poCol, $poNumber)
                ->first();
            if ($po) {
                $amount = $po->PurchaseOrderAmount ?? $po->purchaseOrderAmount ?? $po->Amount ?? $po->amount ?? 0;
                if ((float) $amount > 0) {
                    return (float) $amount;
                }
            }
        }

        if (Schema::hasTable('trxPROPurchaseOrderItem')) {
            $query = DB::table('trxPROPurchaseOrderItem')
                ->where('trxPROPurchaseOrderNumber', $poNumber);
            if (Schema::hasColumn('trxPROPurchaseOrderItem', 'IsActive')) {
                $query->where('IsActive', true);
            }
            $sum = $query->sum('Amount');
            return (float) $sum;
        }

        return 0;
    }
}
