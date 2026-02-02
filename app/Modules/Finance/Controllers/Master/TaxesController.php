<?php

namespace App\Modules\Finance\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TaxesController extends Controller
{
    public function index(Request $request)
    {
        $table = $this->resolveTable([
            'mstFINTax',
            'mstFINTaxes',
            'mstFINInvoiceTax',
            'mstFINInvoiceTaxes',
            'mstTax',
            'mstTaxes',
        ]);

        if (!$table) {
            return response()->json([]);
        }

        $query = DB::table($table);
        if ($request->has('isActive')) {
            $isActiveColumn = $this->resolveColumn($table, ['IsActive', 'isActive']);
            if ($isActiveColumn) {
                $query->where($isActiveColumn, $this->normalizeBool($request->query('isActive')));
            }
        }

        $rows = $query->get();

        $idColumn = $this->resolveColumn($table, ['ID', 'Id', 'TaxID', 'mstTaxID']);
        $nameColumn = $this->resolveColumn($table, ['TaxName', 'taxName', 'Name', 'name', 'TaxDescription', 'Description']);
        $valueColumn = $this->resolveColumn($table, ['TaxValue', 'taxValue', 'Value', 'value', 'TaxPercentage', 'Percentage']);

        $data = $rows->map(function ($row) use ($idColumn, $nameColumn, $valueColumn) {
            return [
                'id' => $idColumn ? ($row->{$idColumn} ?? null) : ($row->ID ?? $row->Id ?? null),
                'taxName' => $nameColumn ? ($row->{$nameColumn} ?? '') : ($row->TaxName ?? $row->Name ?? $row->Description ?? ''),
                'TaxName' => $nameColumn ? ($row->{$nameColumn} ?? '') : ($row->TaxName ?? $row->Name ?? $row->Description ?? ''),
                'taxValue' => $valueColumn ? ($row->{$valueColumn} ?? 0) : ($row->TaxValue ?? $row->Value ?? 0),
                'TaxValue' => $valueColumn ? ($row->{$valueColumn} ?? 0) : ($row->TaxValue ?? $row->Value ?? 0),
            ];
        });

        return response()->json($data);
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
