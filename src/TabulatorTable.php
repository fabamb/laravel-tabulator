<?php

namespace Fabamb\LaravelTabulator;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Base class for Tabulator remote data sources.
 *
 * Implementors provide the Eloquent query; this class handles filter,
 * sort and pagination as sent by Tabulator in remote mode and returns
 * the JSON shape Tabulator expects: {"last_page": n, "data": [...]}.
 */
abstract class TabulatorTable
{
    /**
     * Eloquent scopes accumulated via addScope(), applied on top of query()
     * before filter/sort/pagination.
     */
    protected array $scopes = [];

    /**
     * Non-Eloquent data source, set via of(). When present, toResponse()
     * filters/sorts/paginates this collection in-memory instead of calling
     * query(); query() is not invoked at all.
     */
    protected ?Collection $collection = null;

    /**
     * Data source for the table. Not called when of() supplied a collection.
     */
    public function query(): Builder
    {
        throw new \LogicException(static::class.' must override query() or call of() with a Collection.');
    }

    /**
     * Use a plain Collection as the data source instead of an Eloquent
     * query, e.g. for data assembled outside the database (filesystem
     * scans, API results). Mirrors Yajra DataTable's of().
     */
    public function of(Collection $collection): static
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * Register a scope to apply to the query, e.g. from a controller
     * depending on request/session state. Native
     * Illuminate\Database\Eloquent\Scope, no custom interface.
     */
    public function addScope(Scope $scope): static
    {
        $this->scopes[] = $scope;

        return $this;
    }

    /**
     * Row transformer, applied to each row of the current page after
     * pagination, before the JSON response. Receives the model, returns
     * an associative array whose keys match the Tabulator column `field`s.
     * Null (default): rows pass through as-is.
     */
    protected function transformer(): ?callable
    {
        return null;
    }

    /**
     * Tabulator column definitions for the client component. Optional:
     * only worth overriding when the same table's columns are reused
     * across multiple views (index, export, ...) and should have a single
     * source of truth. Pass `<x-tabulator-table :table="$table">` and omit
     * `:columns` to use this; an explicit `:columns` still wins.
     */
    public function columns(): array
    {
        return [];
    }

    /**
     * Toolbar buttons for the client component. Optional: only worth
     * overriding when the same table's toolbar is reused across multiple
     * views and should have a single source of truth. Pass
     * `<x-tabulator-table :table="$table" toolbar>` (bare `toolbar`) to use
     * this; an explicit `:toolbar` array still wins. Defaults to
     * `Toolbar::default()` so bare `toolbar` behaves the same whether or
     * not `:table` is passed, unless overridden.
     */
    public function toolbar(): array
    {
        return Toolbar::default();
    }

    /**
     * Apply filter/sort/pagination from the request and return the
     * Tabulator-compatible JSON response.
     */
    public function toResponse(Request $request): JsonResponse
    {
        if ($this->collection !== null) {
            return $this->collectionResponse($request);
        }

        $query = $this->query();

        foreach ($this->scopes as $scope) {
            $scope->apply($query, $query->getModel());
        }

        $query = $this->applyFilters($query, $request->input('filter', []));
        $query = $this->applySort($query, $request->input('sort', []));

        $page = max(1, (int) $request->input('page', 1));

        // Tabulator's "All" page-size option sends size=true (non-numeric),
        // meaning: no pagination, return every matching row on one page.
        $transformer = $this->transformer();

        $sizeInput = $request->input('size', config('tabulator.pagination_size'));
        if (! is_numeric($sizeInput)) {
            $rows = $query->get();

            return response()->json([
                'last_page' => 1,
                'data' => $transformer ? $rows->map($transformer)->all() : $rows,
            ]);
        }

        $size = max(1, (int) $sizeInput);
        $paginator = $query->paginate($size, ['*'], 'page', $page);
        $rows = $paginator->getCollection();

        return response()->json([
            'last_page' => $paginator->lastPage(),
            'data' => $transformer ? $rows->map($transformer)->all() : $rows,
        ]);
    }

    /**
     * Columns the global search box (field `__global`) searches across.
     * Empty by default: implementors opt in by overriding this.
     */
    protected function searchableFields(): array
    {
        return [];
    }

    /**
     * Apply Tabulator filters (field/type/value tuples) to the query.
     *
     * Supported types (MVP): =, like, <, <=, >, >=, in.
     * Field `__global` is the global search box: OR-`like` across
     * `searchableFields()` instead of AND-ing like a normal filter.
     * Field with a dot (`relation.column`) filters on a related model
     * via `whereHas`, e.g. `role.name`.
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $filter) {
            $field = $filter['field'] ?? null;
            $type = $filter['type'] ?? '=';
            $value = $filter['value'] ?? null;

            if ($field === null) {
                continue;
            }

            if ($field === '__global') {
                $fields = $this->searchableFields();
                if ($fields !== []) {
                    $query->where(function (Builder $query) use ($fields, $value) {
                        foreach ($fields as $field) {
                            $query->orWhere($field, 'like', "%{$value}%");
                        }
                    });
                }

                continue;
            }

            if (str_contains($field, '.')) {
                [$relation, $column] = explode('.', $field, 2);

                $query->whereHas($relation, function (Builder $query) use ($column, $type, $value) {
                    match ($type) {
                        'like' => $query->where($column, 'like', "%{$value}%"),
                        'in' => $query->whereIn($column, (array) $value),
                        '<', '<=', '>', '>=' => $query->where($column, $type, $value),
                        default => $query->where($column, '=', $value),
                    };
                });

                continue;
            }

            match ($type) {
                'like' => $query->where($field, 'like', "%{$value}%"),
                'in' => $query->whereIn($field, (array) $value),
                '<', '<=', '>', '>=' => $query->where($field, $type, $value),
                default => $query->where($field, '=', $value),
            };
        }

        return $query;
    }

    /**
     * Apply Tabulator sorters (field/dir tuples) to the query.
     */
    protected function applySort(Builder $query, array $sorters): Builder
    {
        foreach ($sorters as $sorter) {
            $field = $sorter['field'] ?? null;
            $dir = $sorter['dir'] ?? 'asc';

            if ($field === null) {
                continue;
            }

            $query->orderBy($field, $dir);
        }

        return $query;
    }

    /**
     * Collection counterpart of toResponse()'s query pipeline: filter, sort,
     * paginate in-memory, same request shape and JSON response as the
     * Eloquent path.
     */
    protected function collectionResponse(Request $request): JsonResponse
    {
        $items = $this->applyFiltersToCollection($this->collection, $request->input('filter', []));
        $items = $this->applySortToCollection($items, $request->input('sort', []));

        $transformer = $this->transformer();

        $sizeInput = $request->input('size', config('tabulator.pagination_size'));
        if (! is_numeric($sizeInput)) {
            return response()->json([
                'last_page' => 1,
                'data' => $transformer ? $items->map($transformer)->values()->all() : $items->values()->all(),
            ]);
        }

        $size = max(1, (int) $sizeInput);
        $page = max(1, (int) $request->input('page', 1));
        $lastPage = max(1, (int) ceil($items->count() / $size));
        $pageItems = $items->slice(($page - 1) * $size, $size)->values();

        return response()->json([
            'last_page' => $lastPage,
            'data' => $transformer ? $pageItems->map($transformer)->all() : $pageItems->all(),
        ]);
    }

    /**
     * Collection counterpart of applyFilters(). Same filter shape
     * (field/type/value, __global via searchableFields()); comparisons run
     * in PHP instead of SQL.
     */
    protected function applyFiltersToCollection(Collection $items, array $filters): Collection
    {
        foreach ($filters as $filter) {
            $field = $filter['field'] ?? null;
            $type = $filter['type'] ?? '=';
            $value = $filter['value'] ?? null;

            if ($field === null) {
                continue;
            }

            if ($field === '__global') {
                $fields = $this->searchableFields();
                if ($fields !== []) {
                    $items = $items->filter(function ($item) use ($fields, $value) {
                        foreach ($fields as $f) {
                            if (str_contains(strtolower((string) data_get($item, $f)), strtolower((string) $value))) {
                                return true;
                            }
                        }

                        return false;
                    });
                }

                continue;
            }

            $items = $items->filter(function ($item) use ($field, $type, $value) {
                $itemValue = data_get($item, $field);

                return match ($type) {
                    'like' => str_contains(strtolower((string) $itemValue), strtolower((string) $value)),
                    'in' => in_array($itemValue, (array) $value),
                    '<' => $itemValue < $value,
                    '<=' => $itemValue <= $value,
                    '>' => $itemValue > $value,
                    '>=' => $itemValue >= $value,
                    default => $itemValue == $value,
                };
            });
        }

        return $items;
    }

    /**
     * Collection counterpart of applySort(). Multiple sorters apply in
     * reverse so the first sorter remains primary (PHP's sort is stable).
     */
    protected function applySortToCollection(Collection $items, array $sorters): Collection
    {
        foreach (array_reverse($sorters) as $sorter) {
            $field = $sorter['field'] ?? null;
            $dir = $sorter['dir'] ?? 'asc';

            if ($field === null) {
                continue;
            }

            $items = $dir === 'desc' ? $items->sortByDesc($field) : $items->sortBy($field);
        }

        return $items;
    }
}
