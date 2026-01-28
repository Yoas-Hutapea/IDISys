<?php

namespace App\Modules\Procurement\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Modules\Procurement\Services\MasterDataService;
use Illuminate\Http\Request;

class CompaniesController extends Controller
{
    public function __construct(private readonly MasterDataService $service)
    {
    }

    public function index(Request $request)
    {
        $isActiveParam = $request->query('isActive');
        $isActive = $isActiveParam === null ? true : filter_var($isActiveParam, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return response()->json($this->service->getCompanies($isActive));
    }
}
