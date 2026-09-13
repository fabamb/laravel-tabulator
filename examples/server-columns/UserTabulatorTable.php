<?php

namespace App\Tabulator;

use App\Models\User;
use Fabamb\LaravelTabulator\TabulatorTable;
use Illuminate\Database\Eloquent\Builder;

/**
 * columns() is a single source of truth for this table's columns, reused
 * by every view that renders it — override it instead of repeating the
 * same `:columns` array per view.
 */
class UserTabulatorTable extends TabulatorTable
{
    public function query(): Builder
    {
        return User::query()->select('id', 'name', 'email', 'created_at');
    }

    public function columns(): array
    {
        return [
            ['field' => 'id', 'title' => 'ID', 'width' => 80],
            ['field' => 'name', 'title' => 'Name'],
            ['field' => 'email', 'title' => 'Email'],
            ['field' => 'created_at', 'title' => 'Created at'],
        ];
    }
}
