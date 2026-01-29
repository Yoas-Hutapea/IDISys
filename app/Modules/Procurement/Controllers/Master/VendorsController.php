<?php

namespace App\Modules\Procurement\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorsController extends Controller
{
    public function index(Request $request)
    {
        $isActive = $request->query('isActive', true);
        $isActive = filter_var($isActive, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        $query = DB::table('mstVendor')
            ->select([
                'ID',
                'VendorID',
                'VendorName',
                'VendorCategory',
                'IsActive',
                'CreatedBy',
                'CreatedDate',
                'UpdatedBy',
                'UpdatedDate',
            ]);

        if ($isActive !== null) {
            $query->where('IsActive', $isActive);
        }

        return response()->json($query->orderBy('VendorName')->get());
    }
}
