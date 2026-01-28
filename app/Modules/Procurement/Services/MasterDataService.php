<?php

namespace App\Modules\Procurement\Services;

use App\Models\IdxProPurchaseTypeSubType;
use App\Models\IdxProPurchaseItemPool;
use App\Models\MstCompany;
use App\Models\MstCurrency;
use App\Models\MstEmployee;
use App\Models\MstProPurchaseItemInventory;
use App\Models\MstProPurchaseItemPool;
use App\Models\MstProPurchaseItemUnit;
use App\Models\MstProPurchaseRequestBillingType;
use App\Models\MstProPurchaseSubType;
use App\Models\MstProPurchaseType;
use App\Models\TrxProPurchaseRequestStip;
use Illuminate\Support\Collection;

class MasterDataService
{
    public function getApplicants(?string $userId = null): Collection
    {
        return MstEmployee::query()
            ->selectRaw('Employ_Id as OnBehalfID, name as Name, nick_name as NickName')
            ->when($userId, function ($query, $userId) {
                $query->where('Employ_Id', $userId);
            })
            ->get();
    }

    public function getCompanies(?bool $isActive = true): Collection
    {
        return MstCompany::query()
            ->select(['CompanyID', 'Company', 'IsActive'])
            ->when($isActive !== null, function ($query) use ($isActive) {
                $query->where('IsActive', $isActive);
            })
            ->get();
    }

    public function getPurchaseTypes(?bool $isActive = true): Collection
    {
        return MstProPurchaseType::query()
            ->select(['ID', 'PurchaseRequestType', 'Category', 'IsActive'])
            ->when($isActive !== null, function ($query) use ($isActive) {
                $query->where('IsActive', $isActive);
            })
            ->get();
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

        return MstProPurchaseSubType::query()
            ->select(['ID', 'PurchaseRequestSubType', 'IsActive'])
            ->whereIn('ID', $subTypeIds)
            ->when($isActive !== null, function ($query) use ($isActive) {
                $query->where('IsActive', $isActive);
            })
            ->get();
    }

    public function getUnits(?bool $isActive = true): Collection
    {
        return MstProPurchaseItemUnit::query()
            ->select(['ID', 'UnitId', 'Unit', 'IsActive', 'UnitDecimals', 'UnitSystem'])
            ->when($isActive !== null, function ($query) use ($isActive) {
                $query->where('IsActive', $isActive);
            })
            ->get();
    }

    public function getCurrencies(?bool $isActive = true): Collection
    {
        return MstCurrency::query()
            ->select(['ID', 'CurrencyCode', 'Currency', 'IsActive'])
            ->when($isActive !== null, function ($query) use ($isActive) {
                $query->where('IsActive', $isActive);
            })
            ->get();
    }

    public function getBillingTypes(?bool $isActive = true): Collection
    {
        return MstProPurchaseRequestBillingType::query()
            ->select(['ID', 'Name', 'Description', 'TotalMonthPeriod', 'IsActive', 'mstPROPurchaseItemUnitID'])
            ->when($isActive !== null, function ($query) use ($isActive) {
                $query->where('IsActive', $isActive);
            })
            ->get();
    }

    public function getInventories(
        ?bool $isActive = true,
        ?string $itemName = null,
        ?int $purchaseTypeId = null,
        ?string $purchaseTypeCategory = null,
        ?int $purchaseSubTypeId = null
    ): Collection {
        $query = MstProPurchaseItemInventory::query()
            ->from('mstPROPurchaseItemInventory as inv')
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

            $query->whereIn('inv.mstPROPurchaseItemPoolId', $poolIds);
        }

        return $query->get();
    }

    public function getStipSites(
        ?string $sonumb = null,
        ?string $siteId = null,
        ?string $siteName = null,
        ?string $operatorName = null
    ): Collection {
        $query = TrxProPurchaseRequestStip::query()
            ->from('trxPROPurchaseRequestSTIP as stip')
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

        return $query->get();
    }

    public function getEmployees(?string $searchTerm = null): Collection
    {
        return MstEmployee::query()
            ->select([
                'Employ_Id',
                'name',
                'nick_name',
                'PositionName',
                'JobTitleName',
                'DepartmentName',
            ])
            ->when($searchTerm, function ($query, $searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('Employ_Id', 'like', '%' . $searchTerm . '%')
                        ->orWhere('name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('nick_name', 'like', '%' . $searchTerm . '%');
                });
            })
            ->get();
    }
}
