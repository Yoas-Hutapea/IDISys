<?php

namespace App\Modules\Procurement\Services;

use App\Models\TrxProPurchaseOrder;
use App\Models\TrxProPurchaseRequest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;

class TaskToDoService
{
    public function getPurchaseRequestsByStatusQuery(array $statusIds): Builder
    {
        return TrxProPurchaseRequest::query()
            ->whereIn('mstApprovalStatusID', $statusIds)
            ->where('IsActive', true);
    }

    public function getPurchaseRequestsByStatus(array $statusIds): Collection
    {
        return $this->getPurchaseRequestsByStatusQuery($statusIds)->get();
    }

    public function getPurchaseRequestsByStatusSingle(?int $statusId): Collection
    {
        $query = TrxProPurchaseRequest::query()->where('IsActive', true);

        if ($statusId !== null) {
            $query->where('mstApprovalStatusID', $statusId);
        }

        return $query->get();
    }

    public function getPurchaseOrdersByStatus(array $statusIds): Collection
    {
        return TrxProPurchaseOrder::query()
            ->whereIn('mstApprovalStatusID', $statusIds)
            ->where('IsActive', true)
            ->get();
    }
}
