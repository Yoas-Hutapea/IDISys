<?php

namespace App\Modules\Procurement\Services;

use App\Models\IdxProPurchaseTypeSubType;
use App\Models\IdxProPurchaseItemPool;
use App\Models\MstCompany;
use App\Models\MstCurrency;
use App\Models\MstEmployee;
use App\Models\MstApprovalStatus;
use App\Models\MstProPurchaseItemInventory;
use App\Models\MstProPurchaseItemPool;
use App\Models\MstProPurchaseItemUnit;
use App\Models\MstProPurchaseRequestBillingType;
use App\Models\MstProPurchaseSubType;
use App\Models\MstProPurchaseType;
use App\Models\MstRegion;
use App\Models\TrxProPurchaseRequestStip;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class MasterDataService
{
    protected function baseFor(Model $model): BaseService
    {
        return new BaseService($model);
    }

    public function getApplicants(?string $userId = null): Collection
    {
        $filters = [
            '__query' => function ($query) use ($userId) {
                $query->selectRaw('Employ_Id as OnBehalfID, name as Name, nick_name as NickName');
                if ($userId) {
                    $query->where('Employ_Id', $userId);
                }
            },
        ];

        return $this->baseFor(new MstEmployee())->getList($filters);
    }

    public function getCompanies(?bool $isActive = true): Collection
    {
        $filters = [
            '__query' => function ($query) {
                $query->select(['CompanyID', 'Company', 'IsActive']);
            },
        ];

        if ($isActive !== null) {
            $filters['IsActive'] = $isActive;
        }

        return $this->baseFor(new MstCompany())->getList($filters);
    }

    public function getPurchaseTypes(?bool $isActive = true): Collection
    {
        $filters = [
            '__query' => function ($query) {
                $query->select(['ID', 'PurchaseRequestType', 'Category', 'IsActive']);
            },
        ];

        if ($isActive !== null) {
            $filters['IsActive'] = $isActive;
        }

        return $this->baseFor(new MstProPurchaseType())->getList($filters);
    }

    public function getPurchaseSubTypes(?int $purchaseTypeId, ?bool $isActive = true): Collection
    {
        if (!$purchaseTypeId) {
            return collect();
        }

        $subTypeIds = IdxProPurchaseTypeSubType::query()
            ->where('mstPROPurchaseTypeID', $purchaseTypeId)
            ->when($isActive !== null, function ($query) use ($isActive) {
                $query->where('IsActive', $isActive);
            })
            ->pluck('mstPROPurchaseSubTypeID')
            ->all();

        if (empty($subTypeIds)) {
            return collect();
        }

        $filters = [
            '__query' => function ($query) {
                $query->select(['ID', 'PurchaseRequestSubType', 'IsActive']);
            },
            'ID' => $subTypeIds,
        ];

        if ($isActive !== null) {
            $filters['IsActive'] = $isActive;
        }

        return $this->baseFor(new MstProPurchaseSubType())->getList($filters);
    }

    public function getUnits(?bool $isActive = true): Collection
    {
        $filters = [
            '__query' => function ($query) {
                $query->select(['ID', 'UnitId', 'Unit', 'IsActive', 'UnitDecimals', 'UnitSystem']);
            },
        ];

        if ($isActive !== null) {
            $filters['IsActive'] = $isActive;
        }

        return $this->baseFor(new MstProPurchaseItemUnit())->getList($filters);
    }

    public function getCurrencies(?bool $isActive = true): Collection
    {
        $filters = [
            '__query' => function ($query) {
                $query->select(['ID', 'CurrencyCode', 'Currency', 'IsActive']);
            },
        ];

        if ($isActive !== null) {
            $filters['IsActive'] = $isActive;
        }

        return $this->baseFor(new MstCurrency())->getList($filters);
    }

    public function getBillingTypes(?bool $isActive = true): Collection
    {
        $filters = [
            '__query' => function ($query) {
                $query->select([
                    'ID',
                    'Name',
                    'Description',
                    'TotalMonthPeriod',
                    'IsActive',
                    'mstPROPurchaseItemUnitID',
                ]);
            },
        ];

        if ($isActive !== null) {
            $filters['IsActive'] = $isActive;
        }

        return $this->baseFor(new MstProPurchaseRequestBillingType())->getList($filters);
    }

    public function getInventories(
        ?bool $isActive = true,
        ?string $itemName = null,
        ?int $purchaseTypeId = null,
        ?string $purchaseTypeCategory = null,
        ?int $purchaseSubTypeId = null
    ): Collection {
        $poolIds = null;
        if ($purchaseTypeId || $purchaseSubTypeId) {
            $poolIds = IdxProPurchaseItemPool::query()
                ->when($purchaseTypeId, function ($q) use ($purchaseTypeId) {
                    $q->where('mstPROPurchaseTypeID', $purchaseTypeId);
                })
                ->when($purchaseSubTypeId, function ($q) use ($purchaseSubTypeId) {
                    $q->where('mstPROPurchaseSubTypeID', $purchaseSubTypeId);
                })
                ->pluck('mstPROPurchaseItemPoolID')
                ->filter()
                ->all();

            if (empty($poolIds)) {
                return collect();
            }
        }

        $filters = [
            '__query' => function ($query) use ($isActive, $itemName, $purchaseTypeCategory, $poolIds) {
                $query->from('mstPROPurchaseItemInventory as inv')
                    ->leftJoin('mstPROPurchaseItemPool as pool', 'inv.mstPROPurchaseItemPoolId', '=', 'pool.Id')
                    ->select([
                        'inv.ID',
                        'inv.ItemID',
                        'inv.ItemName',
                        'inv.mstPROItemCOAId',
                        'inv.mstPROPurchaseItemPoolId',
                        'inv.mstPROPurchaseItemUnitId',
                        'inv.Category',
                        'pool.PoolName',
                    ]);

                if ($isActive !== null) {
                    $query->where('inv.IsActive', $isActive);
                }
                if ($itemName) {
                    $query->where('inv.ItemName', 'like', '%' . $itemName . '%');
                }
                if ($purchaseTypeCategory) {
                    $query->where('inv.Category', $purchaseTypeCategory);
                }
                if (is_array($poolIds)) {
                    $query->whereIn('inv.mstPROPurchaseItemPoolId', $poolIds);
                }
            },
        ];

        return $this->baseFor(new MstProPurchaseItemInventory())->getList($filters);
    }

    public function getStipSites(
        ?string $sonumb = null,
        ?string $siteId = null,
        ?string $siteName = null,
        ?string $operatorName = null
    ): Collection {
        $filters = [
            '__query' => function ($query) use ($sonumb, $siteId, $siteName, $operatorName) {
                $query->from('trxPROPurchaseRequestSTIP as stip')
                    ->select([
                        'stip.SONumber as Sonumb',
                        'stip.SiteID',
                        'stip.SiteName',
                        'stip.CustomerName as OperatorName',
                        'stip.ID',
                    ]);

                if ($sonumb) {
                    $query->where('stip.SONumber', 'like', '%' . $sonumb . '%');
                }
                if ($siteId) {
                    $query->where('stip.SiteID', 'like', '%' . $siteId . '%');
                }
                if ($siteName) {
                    $query->where('stip.SiteName', 'like', '%' . $siteName . '%');
                }
                if ($operatorName) {
                    $query->where('stip.CustomerName', 'like', '%' . $operatorName . '%');
                }
            },
        ];

        return $this->baseFor(new TrxProPurchaseRequestStip())->getList($filters);
    }

    public function getEmployees(?string $searchTerm = null): Collection
    {
        $filters = [
            '__query' => function ($query) use ($searchTerm) {
                $query->select([
                    'Employ_Id',
                    'name',
                    'nick_name',
                    'PositionName',
                    'JobTitleName',
                    'DepartmentName',
                ]);

                if ($searchTerm) {
                    $query->where(function ($q) use ($searchTerm) {
                        $q->where('Employ_Id', 'like', '%' . $searchTerm . '%')
                            ->orWhere('name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('nick_name', 'like', '%' . $searchTerm . '%');
                    });
                }
            },
        ];

        return $this->baseFor(new MstEmployee())->getList($filters);
    }

    public function getApprovalStatuses(?bool $isActive = true): Collection
    {
        $filters = [
            '__query' => function ($query) {
                $query->select(['ID', 'ApprovalStatus', 'IsActive']);
            },
        ];

        if ($isActive !== null) {
            $filters['IsActive'] = $isActive;
        }

        return $this->baseFor(new MstApprovalStatus())->getList($filters);
    }

    public function getRegions(?bool $isActive = true): Collection
    {
        $filters = [
            '__query' => function ($query) {
                $query->selectRaw('RegionId as RegionID, RegionName, IsActive');
            },
        ];

        if ($isActive !== null) {
            $filters['IsActive'] = $isActive;
        }

        return $this->baseFor(new MstRegion())->getList($filters);
    }
}
