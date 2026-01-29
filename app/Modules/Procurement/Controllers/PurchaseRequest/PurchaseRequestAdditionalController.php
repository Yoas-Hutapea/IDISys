<?php

namespace App\Modules\Procurement\Controllers\PurchaseRequest;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseRequestAdditionalController extends Controller
{
    public function batchCheck(Request $request)
    {
        $prNumbers = $request->input();
        if (!is_array($prNumbers)) {
            $prNumbers = [];
        }

        $prNumbers = array_values(array_filter(array_map('strval', $prNumbers)));
        if (empty($prNumbers)) {
            return response()->json(['message' => 'PR Numbers list cannot be empty'], 400);
        }

        $rows = DB::table('trxPROPurchaseRequestAdditional')
            ->whereIn('trxPROPurchaseRequestNumber', $prNumbers)
            ->where('IsActive', true)
            ->get([
                'trxPROPurchaseRequestNumber',
                'StartPeriod',
                'EndPeriod',
            ]);

        $results = [];
        foreach ($prNumbers as $number) {
            $match = $rows->firstWhere('trxPROPurchaseRequestNumber', $number);
            $results[] = [
                'purchReqNumber' => $number,
                'startPeriod' => $match->StartPeriod ?? null,
                'endPeriod' => $match->EndPeriod ?? null,
            ];
        }

        return response()->json($results);
    }
}
