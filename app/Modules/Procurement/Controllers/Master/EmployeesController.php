<?php

namespace App\Modules\Procurement\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Modules\Procurement\Services\MasterDataService;
use Illuminate\Http\Request;

class EmployeesController extends Controller
{
    public function __construct(private readonly MasterDataService $service)
    {
    }

    public function index(Request $request)
    {
        $searchTerm = $request->query('searchTerm');
        $reportCodeForReview = null;
        $reviewedByEmployId = $request->query('reviewedByEmployId');

        if ($request->boolean('filterByReportCodeForReview')) {
            $employee = session('employee');
            if ($employee && is_object($employee) && !empty($employee->Report_Code ?? null)) {
                $reportCodeForReview = trim((string) $employee->Report_Code);
            }
        }

        $list = $this->service->getEmployees($searchTerm, $reportCodeForReview, $reviewedByEmployId);
        $data = $list->values()->all();
        return response()->json(['data' => $data]);
    }
}
