<?php

namespace App\Modules\Finance\Controllers\EBilling;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InvoiceDocumentChecklistsController extends Controller
{
    public function index(Request $request)
    {
        $table = $this->resolveTable([
            'mstFINInvoiceDocumentChecklist',
            'mstFINInvoiceDocumentChecklists',
            'mstFINInvoiceDocumentCheckList',
        ]);

        if (!$table) {
            return response()->json([]);
        }

        $query = DB::table($table);
        $isActiveColumn = $this->resolveColumn($table, ['IsActive', 'isActive']);
        if ($request->has('isActive') && $isActiveColumn) {
            $query->where($isActiveColumn, $this->normalizeBool($request->query('isActive')));
        }

        return response()->json($query->get());
    }

    public function show(int $id)
    {
        $table = $this->resolveTable([
            'mstFINInvoiceDocumentChecklist',
            'mstFINInvoiceDocumentChecklists',
            'mstFINInvoiceDocumentCheckList',
        ]);

        if (!$table) {
            return response()->json(null, 404);
        }

        $idColumn = $this->resolveColumn($table, ['ID', 'Id', 'InvoiceDocumentChecklistID', 'mstFINInvoiceDocumentChecklistID']);
        if (!$idColumn) {
            return response()->json(null, 404);
        }

        $row = DB::table($table)->where($idColumn, $id)->first();
        return response()->json($row);
    }

    public function store(Request $request)
    {
        $table = $this->resolveTable([
            'mstFINInvoiceDocumentChecklist',
            'mstFINInvoiceDocumentChecklists',
            'mstFINInvoiceDocumentCheckList',
        ]);

        if (!$table) {
            return response()->json(['message' => 'Document checklist table not found'], 404);
        }

        $data = [];
        $this->setIfColumn($table, $data, ['DocumentName', 'documentName'], $request->input('documentName'));
        $this->setIfColumn($table, $data, ['FileName', 'fileName'], $request->input('fileName'));
        $this->setIfColumn($table, $data, ['FilePath', 'filePath'], $request->input('filePath'));
        if ($request->has('isActive')) {
            $this->setIfColumn($table, $data, ['IsActive', 'isActive'], $this->normalizeBool($request->input('isActive')));
        }

        $this->setAuditColumns($table, $data, true);

        $idColumn = $this->resolveColumn($table, ['ID', 'Id', 'InvoiceDocumentChecklistID', 'mstFINInvoiceDocumentChecklistID']);
        if ($idColumn) {
            $newId = DB::table($table)->insertGetId($data, $idColumn);
            $row = DB::table($table)->where($idColumn, $newId)->first();
            return response()->json($row);
        }

        DB::table($table)->insert($data);
        return response()->json($data);
    }

    public function update(int $id, Request $request)
    {
        $table = $this->resolveTable([
            'mstFINInvoiceDocumentChecklist',
            'mstFINInvoiceDocumentChecklists',
            'mstFINInvoiceDocumentCheckList',
        ]);

        if (!$table) {
            return response()->json(['message' => 'Document checklist table not found'], 404);
        }

        $idColumn = $this->resolveColumn($table, ['ID', 'Id', 'InvoiceDocumentChecklistID', 'mstFINInvoiceDocumentChecklistID']);
        if (!$idColumn) {
            return response()->json(['message' => 'Document checklist ID column not found'], 404);
        }

        $data = [];
        $this->setIfColumn($table, $data, ['DocumentName', 'documentName'], $request->input('documentName'));
        $this->setIfColumn($table, $data, ['FileName', 'fileName'], $request->input('fileName'));
        $this->setIfColumn($table, $data, ['FilePath', 'filePath'], $request->input('filePath'));
        if ($request->has('isActive')) {
            $this->setIfColumn($table, $data, ['IsActive', 'isActive'], $this->normalizeBool($request->input('isActive')));
        }

        $this->setAuditColumns($table, $data, false);

        DB::table($table)->where($idColumn, $id)->update($data);
        $row = DB::table($table)->where($idColumn, $id)->first();
        return response()->json($row);
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
