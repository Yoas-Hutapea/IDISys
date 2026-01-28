<?php

namespace App\Modules\Procurement\Services;

use App\Models\TrxProPurchaseOrder;
use App\Models\TrxProPurchaseRequest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use App\Services\BaseService;

class TaskToDoService
{
    protected BaseService $purchaseRequestService;
    protected BaseService $purchaseOrderService;

    public function __construct()
    {
        $this->purchaseRequestService = new BaseService(new TrxProPurchaseRequest());
        $this->purchaseOrderService = new BaseService(new TrxProPurchaseOrder());
    }

    public function getPurchaseRequestsByStatusQuery(array $statusIds): Builder
    {
        return $this->purchaseRequestService->query([
            'mstApprovalStatusID' => $statusIds,
            'IsActive' => true,
        ]);
    }

    public function getPurchaseRequestsByStatus(array $statusIds): Collection
    {
        return $this->purchaseRequestService->getList([
            'mstApprovalStatusID' => $statusIds,
            'IsActive' => true,
        ]);
    }

    public function getPurchaseRequestsByStatusSingle(?int $statusId): Collection
    {
        $filters = ['IsActive' => true];

        if ($statusId !== null) {
            $filters['mstApprovalStatusID'] = $statusId;
        }

        return $this->purchaseRequestService->getList($filters);
    }

    public function getPurchaseOrdersByStatus(array $statusIds): Collection
    {
        return $this->purchaseOrderService->getList([
            'mstApprovalStatusID' => $statusIds,
            'IsActive' => true,
        ]);
    }
}
