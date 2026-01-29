<?php

namespace App\Modules\Procurement\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoreBusinessesController extends Controller
{
    public function index(Request $request)
    {
        $vendorCategoryId = (int) $request->query('vendorCategoryId', 0);
        if ($vendorCategoryId <= 0) {
            return response()->json(['message' => 'VendorCategoryId is required and must be greater than 0'], 400);
        }

        $rows = DB::table('mstPROCoreBusiness')
            ->where('mstPROCoreBusinessVendorCategoryID', $vendorCategoryId)
            ->orderBy('CoreBusiness')
            ->get([
                'ID',
                'CoreBusiness',
                'InitialCoreBusiness',
                'mstPROCoreBusinessVendorCategoryID',
            ]);

        return response()->json($rows);
    }
}
