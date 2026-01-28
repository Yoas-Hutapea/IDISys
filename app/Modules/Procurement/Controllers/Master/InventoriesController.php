<?php

namespace App\Modules\Procurement\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Modules\Procurement\Services\MasterDataService;
use Illuminate\Http\Request;

class InventoriesController extends Controller
{
    public function __construct(private readonly MasterDataService $service)
    {
    }

    public function index(Request $request)
    {
        $isActiveParam = $request->query('isActive');
        $isActive = $isActiveParam === null ? true : filter_var($isActiveParam, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        $itemName = $request->query('itemName');
        $purchaseRequestType = $request->query('purchaseRequestType');
        $purchaseRequestSubType = $request->query('purchaseRequestSubType');
        $purchaseRequestCategory = $request->query('purchaseRequestTypeCategory');

        $typeId = is_numeric($purchaseRequestType) ? (int) $purchaseRequestType : null;
        $subTypeId = is_numeric($purchaseRequestSubType) ? (int) $purchaseRequestSubType : null;

        return response()->json(
            $this->service->getInventories(
                $isActive,
                $itemName,
                $typeId,
                $purchaseRequestCategory,
                $subTypeId
            )
        );
    }
}
