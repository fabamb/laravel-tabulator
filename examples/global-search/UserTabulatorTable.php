<?php

namespace App\Tabulator;

use App\Models\User;
use Fabamb\LaravelTabulator\TabulatorTable;
use Illuminate\Database\Eloquent\Builder;

class UserTabulatorTable extends TabulatorTable
{
    public function query(): Builder
    {
        return User::query()->select('id', 'name', 'email');
    }

    // Columns the search box's __global filter ORs together. Without this
    // override the search box renders but matches nothing.
    protected function searchableFields(): array
    {
        return ['name', 'email'];
    }
}
