<?php

namespace App\Tabulator;

use Fabamb\LaravelTabulator\TabulatorTable;

/**
 * Non-Eloquent remote table: data comes from a filesystem scan, not a DB
 * query, so of() supplies the collection instead of overriding query().
 * Filter/sort/pagination and searchableFields() still work exactly as
 * with a query-backed table, just in-memory instead of in SQL.
 */
class LogFileTabulatorTable extends TabulatorTable
{
    protected function searchableFields(): array
    {
        return ['name'];
    }
}
