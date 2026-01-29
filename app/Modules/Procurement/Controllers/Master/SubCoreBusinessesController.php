<?php

namespace App\Modules\Procurement\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubCoreBusinessesController extends Controller
{
    public function index(Request $request)
    {
        $coreBusinessId = (int) $request->query('coreBusinessId', 0);
        if ($coreBusinessId <= 0) {
            return response()->json(['message' => 'CoreBusinessId is required and must be greater than 0'], 400);
        }

        $rows = DB::table('mstPROCoreBusinessSubCore')
            ->where('mstPROCoreBusinessID', $coreBusinessId)
            ->orderBy('SubCoreBusiness')
            ->get([
                'ID',
                'mstPROCoreBusinessID',
                'SubCoreBusiness',
            ]);

        return response()->json($rows);
    }
}
