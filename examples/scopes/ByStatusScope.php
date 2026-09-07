<?php

namespace App\Tabulator\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Example scope: filters rows by a value not known when the table class
 * is defined (e.g. current tenant, session state), added dynamically
 * from the controller instead of hardcoded in query().
 */
class ByStatusScope implements Scope
{
    public function __construct(private readonly string $status)
    {
    }

    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('status', $this->status);
    }
}
