<?php

namespace App\Tabulator;

use App\Models\User;
use Fabamb\LaravelTabulator\TabulatorTable;
use Illuminate\Database\Eloquent\Builder;

/**
 * transformer() reshapes each row of the current page before it's sent as
 * JSON: format a date, render a badge, drop a column. Applied after
 * pagination, so it never touches rows outside the returned page.
 */
class UserTabulatorTable extends TabulatorTable
{
    public function query(): Builder
    {
        return User::query()->select('id', 'name', 'email', 'status', 'created_at');
    }

    protected function transformer(): ?callable
    {
        // Any callable works, not just a closure — e.g. an existing Fractal
        // transformer with constructor deps: `return [new UserTransformer($request->user()), 'transform'];`
        return fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => view('components.badge', ['status' => $user->status])->render(),
            'created_at' => $user->created_at->format('Y-m-d'),
        ];
    }
}
