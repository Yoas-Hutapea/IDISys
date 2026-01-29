<?php

namespace App\Modules\Procurement\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorContractsController extends Controller
{
    public function index(Request $request)
    {
        $subCoreBusinessId = (int) $request->query('subCoreBusinessId', 0);

        if ($subCoreBusinessId > 0) {
            $contractIds = DB::table('idxPROVendorContractSubCore')
                ->where('SubCoreBusinessID', $subCoreBusinessId)
                ->where('IsActive', true)
                ->pluck('trxPROVendorContractID')
                ->unique()
                ->values();

            if ($contractIds->isEmpty()) {
                return response()->json([]);
            }

            $query = DB::table('trxPROVendorContract')
                ->whereIn('ID', $contractIds->all());
        } else {
            $query = DB::table('trxPROVendorContract');
        }

        $rows = $query
            ->orderByDesc(DB::raw('COALESCE(ContractDate, CreatedDate)'))
            ->get([
                'ID',
                'ContractNumber',
                'VendorID',
                'mstCompanyID',
                'CompanyName',
                'mstCompanyGroupID',
                'CompanyGroup',
                'mstPROVendorContractTypeID',
                'mstPROVendorContractRemarkStatusID',
                'ContractDate',
                'StartDate',
                'EndDate',
                'CreatedBy',
                'CreatedDate',
                'UpdatedBy',
                'UpdatedDate',
            ]);

        return response()->json($rows);
    }
}
