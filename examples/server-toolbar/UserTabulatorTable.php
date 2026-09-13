<?php

namespace App\Tabulator;

use App\Models\User;
use Fabamb\LaravelTabulator\TabulatorTable;
use Fabamb\LaravelTabulator\Toolbar;
use Illuminate\Database\Eloquent\Builder;

/**
 * toolbar() extends the standard set with a custom button, single source of
 * truth reused by every view that renders this table — override it instead
 * of repeating the same `:toolbar` array per view.
 */
class UserTabulatorTable extends TabulatorTable
{
    public function query(): Builder
    {
        return User::query()->select('id', 'name', 'email', 'created_at');
    }

    public function toolbar(): array
    {
        return parent::toolbar() + [
            'export-pdf' => ['icon' => 'fas fa-file-pdf', 'title' => 'Export PDF', 'url' => route('users.export-pdf')],
        ];
    }
}
