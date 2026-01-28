<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Arr;

class BaseService
{
    protected Model $model;

    protected static array $columnCache = [];

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function getById(mixed $id): ?Model
    {
        return $this->model->newQuery()->find($id);
    }

    public function query(array $filters = [], array $orderBy = []): Builder
    {
        $query = $this->applyFilters($this->model->newQuery(), $filters);
        return $this->applyOrderBy($query, $orderBy);
    }

    public function getList(array $filters = [], array $orderBy = [], ?int $limit = null, ?int $offset = null): Collection
    {
        $query = $this->query($filters, $orderBy);

        if ($offset !== null) {
            $query->skip($offset);
        }
        if ($limit !== null) {
            $query->take($limit);
        }

        return $query->get();
    }

    public function getCount(array $filters = []): int
    {
        $query = $this->applyFilters($this->model->newQuery(), $filters);
        return $query->count();
    }

    public function getPaged(int $pageIndex, int $pageSize, array $filters = [], array $orderBy = []): array
    {
        $pageIndex = max(1, $pageIndex);
        $pageSize = max(1, $pageSize);

        $query = $this->applyFilters($this->model->newQuery(), $filters);
        $total = $query->count();

        $items = $this->applyOrderBy($query, $orderBy)
            ->skip(($pageIndex - 1) * $pageSize)
            ->take($pageSize)
            ->get();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $pageIndex,
            'pageSize' => $pageSize,
        ];
    }

    public function create(array $data, ?string $userId = null): Model
    {
        $model = $this->model->newInstance();
        $this->setAuditFieldsOnCreate($model, $userId);
        $model->fill($data);
        $model->save();

        return $model;
    }

    public function update(Model $model, array $data, ?string $userId = null): Model
    {
        $this->setAuditFieldsOnUpdate($model, $userId);
        $model->fill($data);
        $model->save();

        return $model;
    }

    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }

    protected function applyFilters($query, array $filters)
    {
        foreach ($filters as $field => $value) {
            if (is_callable($value)) {
                $value($query);
                continue;
            }

            if (is_array($value)) {
                $query->whereIn($field, $value);
                continue;
            }

            if ($value === null) {
                $query->whereNull($field);
                continue;
            }

            $query->where($field, $value);
        }

        return $query;
    }

    protected function applyOrderBy($query, array $orderBy)
    {
        foreach ($orderBy as $field => $direction) {
            $query->orderBy($field, $direction);
        }

        return $query;
    }

    protected function setAuditFieldsOnCreate(Model $model, ?string $userId = null): void
    {
        $userId = $userId ?: (Auth::user()->Username ?? Auth::id() ?? 'System');

        if ($this->hasColumn($model, 'CreatedDate')) {
            $model->setAttribute('CreatedDate', now());
        }
        if ($this->hasColumn($model, 'CreatedBy')) {
            $model->setAttribute('CreatedBy', $userId);
        }
        if ($this->hasColumn($model, 'IsActive') && !Arr::exists($model->getAttributes(), 'IsActive')) {
            $model->setAttribute('IsActive', true);
        }
    }

    protected function setAuditFieldsOnUpdate(Model $model, ?string $userId = null): void
    {
        $userId = $userId ?: (Auth::user()->Username ?? Auth::id() ?? 'System');

        if ($this->hasColumn($model, 'UpdatedDate')) {
            $model->setAttribute('UpdatedDate', now());
        }
        if ($this->hasColumn($model, 'UpdatedBy')) {
            $model->setAttribute('UpdatedBy', $userId);
        }
    }

    protected function hasColumn(Model $model, string $column): bool
    {
        $table = $model->getTable();
        $key = $table . ':' . $column;

        if (!array_key_exists($key, self::$columnCache)) {
            self::$columnCache[$key] = Schema::connection($model->getConnectionName())
                ->hasColumn($table, $column);
        }

        return self::$columnCache[$key];
    }
}
