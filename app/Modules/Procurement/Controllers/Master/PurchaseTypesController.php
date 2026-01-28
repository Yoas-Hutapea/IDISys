<?php

namespace App\Modules\Procurement\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Modules\Procurement\Services\MasterDataService;
use Illuminate\Http\Request;

class PurchaseTypesController extends Controller
{
    public function __construct(private readonly MasterDataService $service)
    {
    }

    public function list(Request $request)
    {
        $isActiveParam = $request->query('isActive');
        $isActive = $isActiveParam === null ? true : filter_var($isActiveParam, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return response()->json($this->service->getPurchaseTypes($isActive));
    }

    public function subTypes(Request $request)
    {
        $typeId = $request->query('mstPROPurchaseTypeID');
        $typeId = is_numeric($typeId) ? (int) $typeId : null;

        $isActiveParam = $request->query('isActive');
        $isActive = $isActiveParam === null ? true : filter_var($isActiveParam, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return response()->json($this->service->getPurchaseSubTypes($typeId, $isActive));
    }
}
