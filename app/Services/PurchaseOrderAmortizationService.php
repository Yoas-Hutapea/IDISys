<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Recalculate trxPROPurchaseOrderAmortization.InvoiceAmount based on actual received amount (GRN).
 * When GRN exists, term amounts are based on received total. If some terms are already paid (have
 * InvoiceNumber), only unpaid terms are updated: remainingToPay = newTotal - sum(paid); each
 * unpaid term gets remainingToPay * (termValue / sumUnpaidTermValues) so the ratio 50|30 is kept
 * and total remaining = 60_000 (e.g. 80_000 - 20_000) is split 50:30 → 37_500 and 22_500.
 */
class PurchaseOrderAmortizationService
{
    /**
     * Sync Term-type amortization InvoiceAmount to the given PO amount.
     * Call after Confirm PO when PO amount has changed so each term reflects the new total:
     * InvoiceAmount = (TermValue / 100) * poAmount.
     * Example: PO Amount 10,000,000 with 40|50|10 → 4,000,000 | 5,000,000 | 1,000,000.
     */
    public static function syncAmortizationToPOAmount(string $poNumber, float $poAmount): void
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

        $termValueCol = Schema::hasColumn('trxPROPurchaseOrderAmortization', 'TermValue')
            ? 'TermValue' : 'termValue';
        $invoiceAmountCol = 'InvoiceAmount';
        $idCol = Schema::hasColumn('trxPROPurchaseOrderAmortization', 'ID') ? 'ID' : 'id';
        $typeCol = Schema::hasColumn('trxPROPurchaseOrderAmortization', 'AmortizationType')
            ? 'AmortizationType' : 'amortizationType';

        $termRows = DB::table('trxPROPurchaseOrderAmortization')
            ->where($poColumn, $poNumber)
            ->where($typeCol, 'Term')
            ->orderBy(Schema::hasColumn('trxPROPurchaseOrderAmortization', 'PeriodNumber') ? 'PeriodNumber' : $idCol)
            ->get();

        $now = now();
        $emp = session('employee');
        $updatedBy = $emp && is_object($emp) ? ($emp->Employ_Id ?? $emp->EmployId ?? $emp->ID ?? $emp->EmployeeID ?? null) : ($emp['Employ_Id'] ?? $emp['EmployId'] ?? $emp['ID'] ?? $emp['EmployeeID'] ?? null);

        // Split PO amount by unit type to keep Ls/prorate separated from non-Ls.
        // If any Ls item exists:
        // - Term 1 gets total Ls amount
        // - Remaining terms get non-Ls amount proportionally by remaining TermValue
        $splitAmount = self::getPOItemAmountSplitByUnit($poNumber);
        $hasLsItem = ((float) ($splitAmount['ls'] ?? 0)) > 0;
        $lsAmount = (float) ($splitAmount['ls'] ?? 0);
        $nonLsAmount = (float) ($splitAmount['nonLs'] ?? 0);
        if ($nonLsAmount < 0) {
            $nonLsAmount = 0;
        }

        // Build per-term target amount
        $termTargetAmounts = [];
        if ($hasLsItem && $termRows->count() > 0) {
            $rows = $termRows->values();
            $firstRow = $rows->first();
            $firstId = $firstRow ? ($firstRow->{$idCol} ?? null) : null;
            if ($firstId !== null) {
                // If there is only one term row, keep full PO amount to avoid dropping non-Ls value
                $termTargetAmounts[$firstId] = $rows->count() === 1 ? $poAmount : $lsAmount;
            }

            if ($rows->count() > 1) {
                $remainingRows = $rows->slice(1)->values();
                $remainingTermValueTotal = $remainingRows->sum(function ($r) use ($termValueCol) {
                    return (float) ($r->{$termValueCol} ?? $r->termValue ?? 0);
                });

                foreach ($remainingRows as $r) {
                    $rid = $r->{$idCol} ?? null;
                    if ($rid === null) {
                        continue;
                    }
                    $tv = (float) ($r->{$termValueCol} ?? $r->termValue ?? 0);
                    if ($remainingTermValueTotal > 0) {
                        $termTargetAmounts[$rid] = ($nonLsAmount * $tv) / $remainingTermValueTotal;
                    } else {
                        $termTargetAmounts[$rid] = $remainingRows->count() > 0 ? ($nonLsAmount / $remainingRows->count()) : 0;
                    }
                }
            }
        }

        foreach ($termRows as $row) {
            $termValue = (float) ($row->{$termValueCol} ?? $row->termValue ?? 0);
            $rowId = $row->{$idCol} ?? null;
            $newInvoiceAmount = ($poAmount * $termValue) / 100;
            if ($hasLsItem && $rowId !== null && array_key_exists($rowId, $termTargetAmounts)) {
                $newInvoiceAmount = (float) $termTargetAmounts[$rowId];
            }

            $update = [$invoiceAmountCol => $newInvoiceAmount];
            if (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'UpdatedDate')) {
                $update['UpdatedDate'] = $now;
            }
            if (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'UpdatedBy')) {
                $update['UpdatedBy'] = $updatedBy;
            }

            $id = $row->{$idCol} ?? null;
            if ($id !== null) {
                DB::table('trxPROPurchaseOrderAmortization')->where($idCol, $id)->update($update);
            }
        }

        // Period-type: follow release logic:
        // Period 1 = total Ls amount
        // Period 2..N = sum(non-Ls Amount / ItemQty)
        // If no Ls item, fallback to poAmount for all periods
        $periodRows = DB::table('trxPROPurchaseOrderAmortization')
            ->where($poColumn, $poNumber)
            ->where($typeCol, 'Period')
            ->orderBy(Schema::hasColumn('trxPROPurchaseOrderAmortization', 'PeriodNumber') ? 'PeriodNumber' : $idCol)
            ->get();

        $periodAmounts = self::buildPeriodAmountsFromPOItems($poNumber, $periodRows->count(), $poAmount);
        $periodNumberCol = Schema::hasColumn('trxPROPurchaseOrderAmortization', 'PeriodNumber') ? 'PeriodNumber' : null;

        $periodSeq = 0;
        foreach ($periodRows as $row) {
            $rowPeriodNumber = $periodNumberCol ? (int) ($row->{$periodNumberCol} ?? 0) : 0;
            if ($rowPeriodNumber <= 0) {
                // Fallback by row position when PeriodNumber is unavailable
                $periodSeq++;
                $rowPeriodNumber = $periodSeq;
            }
            $periodAmount = $periodAmounts[$rowPeriodNumber] ?? $poAmount;
            $update = [$invoiceAmountCol => $periodAmount];
            if (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'UpdatedDate')) {
                $update['UpdatedDate'] = $now;
            }
            if (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'UpdatedBy')) {
                $update['UpdatedBy'] = $updatedBy;
            }
            $id = $row->{$idCol} ?? null;
            if ($id !== null) {
                DB::table('trxPROPurchaseOrderAmortization')->where($idCol, $id)->update($update);
            }
        }
    }

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

        $termValueCol = Schema::hasColumn('trxPROPurchaseOrderAmortization', 'TermValue')
            ? 'TermValue' : 'termValue';
        $invoiceAmountCol = 'InvoiceAmount';
        $invoiceNumberCol = 'InvoiceNumber';
        $idCol = Schema::hasColumn('trxPROPurchaseOrderAmortization', 'ID') ? 'ID' : 'id';

        // All term rows (including canceled: canceled share stays in denominator so its amount is not redistributed)
        $allTermQuery = DB::table('trxPROPurchaseOrderAmortization')
            ->where($poColumn, $poNumber)
            ->where(function ($q) {
                $col = Schema::hasColumn('trxPROPurchaseOrderAmortization', 'AmortizationType')
                    ? 'AmortizationType' : 'amortizationType';
                $q->where($col, 'Term');
            });
        $allTermRows = $allTermQuery->orderBy(Schema::hasColumn('trxPROPurchaseOrderAmortization', 'PeriodNumber') ? 'PeriodNumber' : 'ID')->get();

        $isCanceledCol = Schema::hasColumn('trxPROPurchaseOrderAmortization', 'IsCanceled') ? 'IsCanceled' : null;

        // Sum already paid: only terms that have InvoiceNumber set (submitted). Cancelled terms do not reduce remaining.
        $sumPaid = 0;
        foreach ($allTermRows as $r) {
            $invNum = $r->{$invoiceNumberCol} ?? $r->invoiceNumber ?? null;
            if ($invNum !== null && trim((string) $invNum) !== '') {
                $sumPaid += (float) ($r->{$invoiceAmountCol} ?? $r->invoiceAmount ?? 0);
            }
        }

        $remainingToPay = max(0, $baseAmount - $sumPaid);

        // sumUnpaidTermValues = sum(termValue) for all NOT submitted (unpaid + cancelled), so cancelled share is not redistributed
        $unpaidRows = [];
        $sumUnpaidTermValues = 0;
        foreach ($allTermRows as $r) {
            $invNum = $r->{$invoiceNumberCol} ?? $r->invoiceNumber ?? null;
            $hasInvoice = $invNum !== null && trim((string) $invNum) !== '';
            if (!$hasInvoice) {
                $sumUnpaidTermValues += (float) ($r->{$termValueCol} ?? $r->termValue ?? 0);
                $isCanceled = $isCanceledCol && ($r->{$isCanceledCol} ?? $r->isCanceled ?? false);
                if (!$isCanceled) {
                    $unpaidRows[] = $r;
                }
            }
        }

        if ($sumUnpaidTermValues <= 0) {
            return;
        }

        foreach ($unpaidRows as $row) {
            $termValue = (float) ($row->{$termValueCol} ?? 0);
            $newInvoiceAmount = ($remainingToPay * $termValue) / $sumUnpaidTermValues;

            $update = [$invoiceAmountCol => $newInvoiceAmount];
            if (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'UpdatedDate')) {
                $update['UpdatedDate'] = now();
            }
            if (Schema::hasColumn('trxPROPurchaseOrderAmortization', 'UpdatedBy')) {
                $emp = session('employee');
                $update['UpdatedBy'] = $emp && is_object($emp) ? ($emp->ID ?? $emp->EmployeeID ?? null) : ($emp['ID'] ?? $emp['EmployeeID'] ?? null);
            }

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

    /**
     * Split PO item amount into:
     * - ls: sum(Amount) where ItemUnit = 'Ls'
     * - nonLs: sum(Amount) where ItemUnit != 'Ls'
     */
    private static function getPOItemAmountSplitByUnit(string $poNumber): array
    {
        $result = ['ls' => 0.0, 'nonLs' => 0.0];
        if (!Schema::hasTable('trxPROPurchaseOrderItem')) {
            return $result;
        }

        $query = DB::table('trxPROPurchaseOrderItem')
            ->where('trxPROPurchaseOrderNumber', $poNumber);
        if (Schema::hasColumn('trxPROPurchaseOrderItem', 'IsActive')) {
            $query->where('IsActive', true);
        }

        $items = $query->get(['ItemUnit', 'Amount']);
        foreach ($items as $item) {
            $unit = strtolower(trim((string) ($item->ItemUnit ?? '')));
            $amount = (float) ($item->Amount ?? 0);
            if ($unit === 'ls') {
                $result['ls'] += $amount;
            } else {
                $result['nonLs'] += $amount;
            }
        }

        return $result;
    }

    /**
     * Build amount map for Period amortization rows based on PO items.
     * Rule:
     * - Period 1 = total Amount where ItemUnit = 'Ls'
     * - Period 2..N = recurring non-Ls = sum(Amount) for non-Ls items
     * - If no Ls item, fallback all periods to defaultAmount
     */
    private static function buildPeriodAmountsFromPOItems(string $poNumber, int $periodCount, float $defaultAmount): array
    {
        $result = [];
        if ($periodCount <= 0) {
            return $result;
        }

        if (!Schema::hasTable('trxPROPurchaseOrderItem')) {
            for ($i = 1; $i <= $periodCount; $i++) {
                $result[$i] = $defaultAmount;
            }
            return $result;
        }

        $query = DB::table('trxPROPurchaseOrderItem')
            ->where('trxPROPurchaseOrderNumber', $poNumber);
        if (Schema::hasColumn('trxPROPurchaseOrderItem', 'IsActive')) {
            $query->where('IsActive', true);
        }
        $items = $query->get(['ItemUnit', 'ItemQty', 'Amount']);

        $lsTotal = 0.0;
        $nonLsRecurring = 0.0;
        foreach ($items as $item) {
            $unit = strtolower(trim((string) ($item->ItemUnit ?? '')));
            $amount = (float) ($item->Amount ?? 0);
            if ($unit === 'ls') {
                $lsTotal += $amount;
            } else {
                $nonLsRecurring += $amount;
            }
        }

        if ($lsTotal <= 0) {
            for ($i = 1; $i <= $periodCount; $i++) {
                $result[$i] = $defaultAmount;
            }
            return $result;
        }

        $result[1] = $lsTotal;
        for ($i = 2; $i <= $periodCount; $i++) {
            $result[$i] = $nonLsRecurring;
        }
        return $result;
    }
}
