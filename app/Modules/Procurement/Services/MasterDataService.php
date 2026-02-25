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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MasterDataService
{
    protected function baseFor(Model $model): BaseService
    {
        return new BaseService($model);
    }

    /**
     * Get Applicant list: Superior (= OnBehalf) from idxPROCPurchReqOnBehalfEmployee.
     * Name from mstEmployee JOIN idxPROCPurchReqOnBehalfEmployee ON OnBehalfID = Employ_Id,
     * for the logged-in user (UserID / Employ ID).
     */
    public function getApplicants(?string $userId = null): Collection
    {
        $tableOnBehalf = 'idxPROCPurchReqOnBehalfEmployee';
        $empTable = (new MstEmployee())->getTable();
        if (! Schema::hasTable($tableOnBehalf) || ! Schema::hasColumn($tableOnBehalf, 'OnBehalfID')) {
            return $this->getApplicantsFallback($userId);
        }

        $userIds = $this->getCurrentUserIdentifiersForApplicants($userId);
        if (empty($userIds)) {
            return $this->getApplicantsFallback($userId);
        }

        $nameExpr = "COALESCE(NULLIF(TRIM({$empTable}.name), ''), {$empTable}.nick_name, {$empTable}.Employ_Id)";
        $rows = DB::table($empTable)
            ->join($tableOnBehalf, "{$tableOnBehalf}.OnBehalfID", '=', "{$empTable}.Employ_Id")
            ->where($tableOnBehalf . '.IsActive', true)
            ->whereIn($tableOnBehalf . '.UserID', $userIds)
            ->selectRaw("{$empTable}.Employ_Id as OnBehalfID, {$nameExpr} as Name")
            ->distinct()
            ->get();

        $byEmployId = [];
        foreach ($rows as $row) {
            $id = $row->OnBehalfID ?? null;
            if ($id && ! isset($byEmployId[$id])) {
                $byEmployId[$id] = (object) [
                    'OnBehalfID' => $id,
                    'Name'       => $row->Name ?? $id,
                ];
            }
        }

        // Prepend logged-in user (UserID) as first option: name from mstEmployee for that Employ_Id
        $empTable = (new MstEmployee())->getTable();
        foreach ($userIds as $loginId) {
            $loginId = trim((string) $loginId);
            if ($loginId === '' || isset($byEmployId[$loginId])) {
                continue;
            }
            $emp = DB::table($empTable)
                ->where('Employ_Id', $loginId)
                ->orWhere('Employ_Id_TBGSYS', $loginId)
                ->selectRaw("Employ_Id as OnBehalfID, COALESCE(NULLIF(TRIM(name), ''), nick_name, Employ_Id) as Name")
                ->first();
            if ($emp) {
                $id = $emp->OnBehalfID ?? $loginId;
                $byEmployId = [$id => (object) ['OnBehalfID' => $id, 'Name' => $emp->Name ?? $id]] + $byEmployId;
            }
            break;
        }

        $result = collect(array_values($byEmployId));
        if ($result->isEmpty()) {
            return $this->getApplicantsFallback($userId);
        }
        return $result;
    }

    /**
     * Identifiers to match UserID in idxPROCPurchReqOnBehalfEmployee (Employ ID or User ID of logged-in user).
     */
    private function getCurrentUserIdentifiersForApplicants(?string $userId): array
    {
        $ids = [];
        if ($userId !== null && $userId !== '') {
            $ids[] = trim($userId);
        }
        $employee = session('employee');
        if ($employee && is_object($employee)) {
            if (! empty($employee->Employ_Id)) {
                $ids[] = trim((string) $employee->Employ_Id);
            }
            if (! empty($employee->Employ_Id_TBGSYS)) {
                $ids[] = trim((string) $employee->Employ_Id_TBGSYS);
            }
        }
        $user = auth()->user();
        if ($user && is_object($user)) {
            $loginId = $user->id ?? $user->ID ?? $user->username ?? $user->Username ?? $user->UserID ?? null;
            if ($loginId !== null && $loginId !== '') {
                $ids[] = trim((string) $loginId);
            }
        }
        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * Fallback when OnBehalf table missing or no rows: only single employee by userId, never all.
     */
    private function getApplicantsFallback(?string $userId): Collection
    {
        if ($userId === null || trim($userId) === '') {
            return collect();
        }
        $filters = [
            '__query' => function ($query) use ($userId) {
                $query->selectRaw('Employ_Id as OnBehalfID, name as Name, nick_name as NickName')
                    ->where('Employ_Id', trim($userId));
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
