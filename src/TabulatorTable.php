<?php

namespace Fabamb\LaravelTabulator;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     * Data source for the table.
     */
    abstract public function query(): Builder;

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
     * Apply filter/sort/pagination from the request and return the
     * Tabulator-compatible JSON response.
     */
    public function toResponse(Request $request): JsonResponse
    {
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
}
