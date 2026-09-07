# Laravel Tabulator

Server-side [Tabulator.js](https://tabulator.info) integration for Laravel: a Blade component that renders the JS table, and a base class that handles remote pagination, sorting and filtering on the server.

See [`examples/`](examples/) for a runnable-shaped snippet of every feature below.

## Installation

Add the package as a composer path repository (until it's published):

```json
{
    "repositories": [
        {"type": "path", "url": "../laravel-tabulator"}
    ],
    "require": {
        "fabamb/laravel-tabulator": "*"
    }
}
```

```sh
composer require fabamb/laravel-tabulator
```

Publish the config (optional):

```sh
php artisan vendor:publish --tag=tabulator-config
```

Tabulator's JS/CSS assets are not bundled by this package — include them yourself (CDN or your own build) before the component's `<script>` runs.

## Basic usage: remote data source

1. Define a table class extending `Fabamb\LaravelTabulator\TabulatorTable`:

```php
namespace App\Tabulator;

use App\Models\User;
use Fabamb\LaravelTabulator\TabulatorTable;
use Illuminate\Database\Eloquent\Builder;

class UserTabulatorTable extends TabulatorTable
{
    public function query(): Builder
    {
        return User::query()->select('id', 'name', 'email', 'created_at');
    }
}
```

2. Expose it behind a route:

```php
Route::get('/users/data', function (App\Tabulator\UserTabulatorTable $table, Illuminate\Http\Request $request) {
    return $table->toResponse($request);
})->name('users.data');
```

3. Render the component in a Blade view:

```blade
<x-tabulator-table
    ajax-url="{{ route('users.data') }}"
    :columns="[
        ['field' => 'id', 'title' => 'ID', 'width' => 80],
        ['field' => 'name', 'title' => 'Name'],
        ['field' => 'email', 'title' => 'Email'],
        ['field' => 'created_at', 'title' => 'Created at'],
    ]"
    rownum
    selectable
/>
```

Full example: [`examples/remote-basic/`](examples/remote-basic/).

## Local data (no server round-trip)

Pass `data` instead of `ajax-url` for a client-side table (pagination/sort/filter all happen in the browser):

```blade
<x-tabulator-table
    :data="$rows"
    :columns="[['field' => 'name', 'title' => 'Name']]"
/>
```

Full example: [`examples/local-data/`](examples/local-data/).

## Component props

| Prop | Type | Default | Description |
|---|---|---|---|
| `id` | string | auto-generated | DOM id of the table container |
| `columns` | array | `[]` | Tabulator column definitions, passed through as-is |
| `ajax-url` | string\|null | `null` | Enables remote mode (pagination/sort/filter sent to the server) |
| `data` | array\|null | `null` | Local dataset, used when `ajax-url` is not set |
| `selectable` | bool | `false` | Adds a row-selection checkbox column |
| `rownum` | bool | `false` | Adds a row-number column (frozen, not sortable) |
| `search` | bool | `false` | Adds a global search box above the table (see below) |
| `search-value` | string\|null | `null` | Pre-fills the search box and applies it as Tabulator's `initialFilter` on load (e.g. from a navbar search redirect). Implies `search`. |
| `options` | array | `[]` | Raw Tabulator options, merged last — overrides anything the component computed |

`options` is an escape hatch for any Tabulator setting not covered by a dedicated prop:

```blade
:options="['layout' => 'fitDataStretch', 'paginationSize' => 25, 'placeholder' => 'No matching rows']"
```

Full example: [`examples/options-override/`](examples/options-override/).

## Column filters

Column-level filters are native Tabulator, nothing package-specific: set `headerFilter` on any column.

```blade
:columns="[
    ['field' => 'name', 'title' => 'Name', 'headerFilter' => 'input'],
    ['field' => 'status', 'title' => 'Status', 'headerFilter' => 'list', 'headerFilterParams' => ['values' => ['active' => 'Active', 'inactive' => 'Inactive']]],
]"
```

A column without `headerFilter` never shows a filter box — there's no separate "enable filters" flag to configure.

In remote mode, filters are sent to the server as `{field, type, value}` tuples and applied by `TabulatorTable::applyFilters()`. Supported types: `=` (default), `like`, `in`, `<`, `<=`, `>`, `>=`.

Full example: [`examples/column-filters/`](examples/column-filters/).

## Scopes

Register extra query constraints per-request (e.g. tenant, session state) without hardcoding them in `query()`. Uses native `Illuminate\Database\Eloquent\Scope`, nothing package-specific:

```php
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ByStatusScope implements Scope
{
    public function __construct(private readonly string $status) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('status', $this->status);
    }
}
```

Add scopes from the controller, they accumulate and are all applied before filter/sort/pagination:

```php
$table = new UserTabulatorTable();
$table->addScope(new ByStatusScope($request->get('status', 'active')));
$table->addScope(new ByTenantScope($request->user()->tenant_id));

return $table->toResponse($request);
```

Full example: [`examples/scopes/`](examples/scopes/).

## Transformers

Reshape each row before it's sent as JSON — e.g. rendering a badge, formatting a date, dropping a column that shouldn't reach the client. Override `transformer()` on your table class to return a callable; it receives the model and returns the array sent to Tabulator (keys map to column `field`s):

```php
class UserTabulatorTable extends TabulatorTable
{
    protected function transformer(): ?callable
    {
        return fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'status' => view('components.badge', ['status' => $user->status])->render(),
        ];
    }
}
```

Applied only to the current page's rows (or all rows when `size` is `All`), after pagination — not the whole dataset. `null` (the default) means no transformation, rows pass through as returned by `query()`. No dependency on a specific transformer library (e.g. Fractal) — any callable works, including `[$instance, 'method']` for transformers with constructor dependencies.

Full example: [`examples/transformers/`](examples/transformers/).

## Global search

Set `search` on the component to render a search box above the table:

```blade
<x-tabulator-table ajax-url="{{ route('users.data') }}" :columns="[...]" search />
```

In remote mode, override `searchableFields()` on your table class to say which columns the search box matches (OR'd together with `like`). Without it, the search box is a no-op:

```php
class UserTabulatorTable extends TabulatorTable
{
    protected function searchableFields(): array
    {
        return ['name', 'email'];
    }
}
```

Full example: [`examples/global-search/`](examples/global-search/).

In local mode, `search` uses Tabulator's own `setFilter` against the in-browser dataset — no server-side wiring needed.

Debounce and minimum character count are configurable in `config/tabulator.php`:

```php
'search_debounce_ms' => 300,
'search_min_chars' => 2,
'search_icon' => 'bi bi-search',
```

### Search from outside the table (e.g. navbar)

Pass `search-value` to prefill the box and filter on first load, sourced from wherever the request came from (typically a query string):

```blade
<x-tabulator-table
    ajax-url="{{ route('users.data') }}"
    :columns="[...]"
    search
    :search-value="request('search')"
/>
```

Pattern for a navbar search box that isn't on the table's page: submit a plain `GET` form to the table's route with a `search` field (AdminLTE's built-in `navbar-search` menu item works — set `method => 'get'`, `input_name => 'search'`, `url => '/your-route'`). The component drops the query string via `history.replaceState` right after applying the initial filter, so a later reload starts unfiltered instead of being stuck on the old search.

This only targets one table/route per navbar search box — for search across multiple unrelated tables, route selection is up to the caller (not handled by this package).

Full example: [`examples/navbar-search/`](examples/navbar-search/).

## Configuration reference (`config/tabulator.php`)

| Key | Description |
|---|---|
| `stack` | Blade `@push` stack the component's `<script>` goes into (must match a `@stack` in your layout) |
| `layout` | Tabulator `layout` option (default `fitColumns`) |
| `pagination_size` | Default page size |
| `pagination_size_selector` | Options in the page-size dropdown |
| `pagination_counter` | Tabulator `paginationCounter` value |
| `locale` / `langs` | Tabulator locale strings; set `locale` to `false` to keep English |
| `search_debounce_ms` / `search_min_chars` | Global search box behavior |
| `search_icon` | CSS class of the icon shown in the search box (default `bi bi-search`, Bootstrap Icons) |

## Testing

```sh
vendor/bin/phpunit tests
```
