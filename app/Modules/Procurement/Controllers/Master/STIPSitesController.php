<?php

namespace App\Modules\Procurement\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Modules\Procurement\Services\MasterDataService;
use Illuminate\Http\Request;

class STIPSitesController extends Controller
{
    public function __construct(private readonly MasterDataService $service)
    {
    }

    public function index(Request $request)
    {
        return response()->json(
            $this->service->getStipSites(
                $request->query('sonumb'),
                $request->query('siteId'),
                $request->query('siteName'),
                $request->query('operatorName')
            )
        );
    }
}
