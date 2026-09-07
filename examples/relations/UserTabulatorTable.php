<?php

namespace App\Tabulator;

use App\Models\User;
use Fabamb\LaravelTabulator\TabulatorTable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Column driven by a `belongsTo` relation (`role.name`).
 *
 * Two things a plain column doesn't need:
 * - eager-load the relation in query(), or it's an N+1 per row.
 * - transformer() must nest it as `['role' => ['name' => ...]]`, not a
 *   flat `'role.name'` key — Tabulator's dotted `field` reads it as a
 *   path into a nested object, not a literal key with a dot in it.
 *
 * Filtering/sorting on `role.name` is handled by the package itself
 * (dotted field -> whereHas), nothing extra needed here.
 */
class UserTabulatorTable extends TabulatorTable
{
    public function query(): Builder
    {
        return User::query()->select('id', 'name', 'email', 'role_id')->with('role');
    }

    protected function transformer(): ?callable
    {
        return fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => ['name' => $user->role?->name],
        ];
    }
}
