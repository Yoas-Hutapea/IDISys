<?php

namespace App\Modules\Finance\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class TermOfPaymentsController extends Controller
{
    public function index()
    {
        $tops = DB::table('mstFINInvoiceTOP')
            ->where('IsActive', true)
            ->orderBy('TOPDescription')
            ->get([
                'ID',
                'TOPDescription',
                'TOPRemarks',
            ]);

        return response()->json($tops);
    }
}
