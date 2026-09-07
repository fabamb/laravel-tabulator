<?php

namespace App\Http\Controllers;

use App\Tabulator\Scopes\ByStatusScope;
use App\Tabulator\UserTabulatorTable;
use Illuminate\Http\Request;

/**
 * Example: registering scopes dynamically per-request instead of
 * hardcoding them in UserTabulatorTable::query(). Multiple scopes
 * accumulate and are all applied in TabulatorTable::toResponse().
 */
class UserController
{
    public function data(Request $request, UserTabulatorTable $table)
    {
        $table->addScope(new ByStatusScope($request->get('status', 'active')));

        return $table->toResponse($request);
    }
}
