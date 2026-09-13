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

Publish the language files to add a locale or edit strings (optional — `en` and `it` ship built in):

```sh
php artisan vendor:publish --tag=tabulator-lang
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
    selectable="checkbox"
/>
```

Full example: [`examples/remote-basic/`](examples/remote-basic/).

## Server-side column definitions

Columns are normally passed inline via `:columns` (they're just Tabulator's own JS config). When the same table's columns are reused across multiple views (index, export, ...) and duplicating the array risks drift, override `columns()` on the table class instead — same idea as `transformer()`:

```php
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
```

Pass the table instance as `:table` and omit `:columns` to use it:

```blade
<x-tabulator-table ajax-url="{{ route('users.data') }}" :table="$table" rownum selectable="checkbox" />
```

An explicit `:columns` still overrides `:table`'s, per view.

Full example: [`examples/server-columns/`](examples/server-columns/).

## Server-side toolbar definitions

Same idea as `columns()`, for tables reused across multiple views that would otherwise repeat the same `:toolbar` array everywhere. Three ways to define a toolbar, pick whichever fits:

1. **Local, view-only** — the usual `:toolbar="[...]"` array, or bare `toolbar` (uses `Toolbar::default()`). Nothing to override.
2. **Server-side, extending the default** — override `toolbar()` on the table class, starting from `parent::toolbar()`:

   ```php
   class UserTabulatorTable extends TabulatorTable
   {
       public function toolbar(): array
       {
           return parent::toolbar() + [
               'export-pdf' => ['icon' => 'fas fa-file-pdf', 'title' => 'Export PDF', 'url' => route('users.export-pdf')],
           ];
       }
   }
   ```

3. **Server-side, fully custom** — override `toolbar()` without calling `parent::toolbar()`, returning only what that table needs.

Pass `:table` with bare `toolbar` to use whichever `toolbar()` the table defines:

```blade
<x-tabulator-table ajax-url="{{ route('users.data') }}" :table="$table" toolbar />
```

An explicit `:toolbar` array on the view still overrides `:table`'s, same precedence as `:columns`. `Toolbar::default()` also remains callable directly from the view at any time (e.g. `:toolbar="\Fabamb\LaravelTabulator\Toolbar::default() + [...]"`), `:table` doesn't take that away.

Full example: [`examples/server-toolbar/`](examples/server-toolbar/).

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
| `columns` | array | `[]` | Tabulator column definitions, passed through as-is. Overrides `table`'s `columns()` when both are set |
| `table` | `TabulatorTable`\|null | `null` | Table instance to pull `columns()` from when `columns` isn't passed (see [Server-side column definitions](#server-side-column-definitions)), and `toolbar()` from when bare `toolbar` is passed (see [Server-side toolbar definitions](#server-side-toolbar-definitions)) |
| `ajax-url` | string\|null | `null` | Enables remote mode (pagination/sort/filter sent to the server) |
| `data` | array\|null | `null` | Local dataset, used when `ajax-url` is not set |
| `selectable` | bool\|string | `false` | Row selection. Bare `selectable` (`true`) is shorthand for `'both'`. `'checkbox'` — tickbox column only, clicking elsewhere in the row does nothing. `'click'` — clicking anywhere in the row selects it, no column. `'both'` — both at once |
| `rownum` | bool | `false` | Adds a row-number column (frozen, not sortable) |
| `responsive` | bool | `false` | Shorthand for `:options="['layout' => config('tabulator.responsive_fixed_layout'), 'responsiveLayout' => 'collapse']"` (see [Configuration reference](#configuration-reference-configtabulatorphp)); an explicit `layout`/`responsiveLayout` in `options` still wins |
| `search` | bool | `false` | Adds a global search box above the table (see below) |
| `search-value` | string\|null | `null` | Pre-fills the search box and applies it as Tabulator's `initialFilter` on load (e.g. from a navbar search redirect). Implies `search`. |
| `options` | array | `[]` | Raw Tabulator options, merged last — overrides anything the component computed |
| `toolbar` | array\|bool | `[]` | Buttons shown above the table (see [Toolbar buttons](#toolbar-buttons)). Bare `toolbar` (`true`) uses `table`'s `toolbar()` when `table` is set, otherwise the standard `Toolbar::default()` set. Overrides `table`'s `toolbar()` when both are set |

`options` is an escape hatch for any Tabulator setting not covered by a dedicated prop:

```blade
:options="['layout' => 'fitDataStretch', 'paginationSize' => 25, 'placeholder' => 'No matching rows']"
```

Full example: [`examples/options-override/`](examples/options-override/).

## Toolbar buttons

`toolbar` renders a button group above the table. Four standard actions are built in — no JS to write. Their tooltips (`title`) default to `resources/lang/{locale}/tabulator.php` (`toolbar.*` keys) unless a button sets its own `title`:

| key | action |
|---|---|
| `reload` | `table.setData()` |
| `csv` | `table.download('csv', 'export.csv')` |
| `print` | `table.print()` |
| `reset` | `table.clearFilter(true); table.clearHeaderFilter()` |

```blade
<x-tabulator-table
    ajax-url="{{ route('servers.data') }}"
    :toolbar="[
        'reload' => ['icon' => 'fas fa-sync', 'title' => 'Reload'],
        'csv' => ['icon' => 'fas fa-file-csv', 'title' => 'Export CSV'],
    ]"
    :columns="[...]"
/>
```

Buttons are icon-only by default. Add `label` to a button to also show text next to the icon; it combines with `title` (the tooltip) by one rule:

| `label` | `title` | Visible label | Tooltip |
|---|---|---|---|
| set | set | `label` | `title` |
| set | not set | `label` | `label` |
| not set | set | *(none)* | `title` |
| not set | not set | *(none)* | *(none)* |

```blade
:toolbar="[
    'reload' => ['icon' => 'fas fa-sync', 'label' => 'Reload'],
]"
```

For app-specific actions (`create`, `bulk-delete`, ...), define a `window.tabulatorButtons` registry in your own JS — the component looks up any key it doesn't recognize as standard:

```js
// resources/js/app.js
window.tabulatorButtons = {
    'bulk-delete': {
        action: (table, selected, url) => {
            if (!selected.length) return alert('Nothing selected');
            // ... DELETE selected.map(r => r.id) to `url`
        },
    },
};
```

```blade
:toolbar="['bulk-delete' => ['icon' => 'fas fa-trash-alt', 'url' => route('servers.bulk.destroy'), 'title' => 'Delete']]"
```

The click dispatch (button → table instance → `action(table, selectedRows, url)`) is registered once per page via `@pushOnce`, regardless of how many `<x-tabulator-table>` instances are on it.

Add a visual gap between buttons with a `['separator' => true]` entry anywhere in the `:toolbar` array (its key doesn't matter, only that key is unique):

```blade
:toolbar="[
    'reload' => ['icon' => 'fas fa-sync'],
    'sep' => ['separator' => true],
    'csv' => ['icon' => 'fas fa-file-csv'],
]"
```

### Reusable bulk-action factory

`bulk-actions.js` ships `Tabulator.bulkAction(options)`, covering the two common `window.tabulatorButtons` patterns above — confirm-then-fetch and prompt-then-fetch against the current selection — so you don't hand-roll the same fetch/confirm/prompt wiring per button. Publish it the same way as `row-actions.js` (see "Reusable factory" above), then:

```js
window.tabulatorButtons = {
    'bulk-delete': Tabulator.bulkAction({ method: 'DELETE', confirm: 'Delete selected rows?' }),
    'bulk-tags': Tabulator.bulkAction({
        method: 'PUT', prompt: 'Tags, comma separated:', bodyKey: 'tags',
    }),
};
```

`method` is required. `confirm`/`prompt` are optional; a cancelled `prompt()` aborts the action. `bodyKey` (default `'value'`) names the field the prompted value is sent under, alongside `ids` (the selected rows' `id`s). `empty` overrides the alert shown when nothing is selected. Actions that need more than confirm/prompt — a modal site-picker, for instance — are still hand-written against the same `window.tabulatorButtons` registry.

### Standard toolbar across many tables

An admin panel with several tables (users, servers, videos, ...) tends to repeat the same `reload`/`csv`/`print`/`reset` set everywhere, only the `create` (and, for selectable tables, bulk-edit/bulk-delete) URLs changing. `Fabamb\LaravelTabulator\Toolbar::default()` returns that set — no subclassing needed:

```blade
:toolbar="\Fabamb\LaravelTabulator\Toolbar::default(route('users.create'))"
```

When no `create`/bulk URL is needed at all, the bare `toolbar` attribute (Blade resolves the valueless attribute to `true`) is shorthand for the same standard set with no arguments:

```blade
<x-tabulator-table ajax-url="{{ route('users.data') }}" toolbar :columns="[...]" />
```

Omit the URL (or pass `null`) to drop the `create` button, e.g. for a read-only table. Icons (and, optionally, per-button `class` for color) come from `config('tabulator.default_toolbar_icons')` (Bootstrap Icons + Bootstrap colors by default) — override there project-wide, no code change needed. Titles still come from `resources/lang/{locale}/tabulator.php`.

Pass `withLabels: true` to also show each button's translated label next to its icon — a short `toolbar_label.*` string (falling back to the `toolbar.*` tooltip text for any key without one):

```blade
:toolbar="\Fabamb\LaravelTabulator\Toolbar::default(route('users.create'), withLabels: true)"
```

When `create`/`bulk-edit`/`bulk-delete` are present, a spacer (`toolbar_separator_class`) is inserted automatically before the standard `reload`/`csv`/`print`/`reset` set.

For tables with row selection (`selectable`), pass bulk-action URLs too:

```blade
<x-tabulator-table
    selectable="checkbox"
    :toolbar="\Fabamb\LaravelTabulator\Toolbar::default(
        createUrl: route('users.create'),
        bulkDeleteUrl: route('users.bulk.destroy'),
        bulkEditUrl: route('users.bulk.edit'),
    )"
    ...
/>
```

`bulk-edit`/`bulk-delete` are omitted (like `create`) when their URL is `null`. They rely on the same `window.tabulatorButtons` action wiring as any other button with a `url` — see [Toolbar buttons](#toolbar-buttons) above.

`Toolbar::default()` returns a plain array, so adding an app-specific button alongside the standard set needs no helper — just `+` a custom entry onto it (a key colliding with a standard one, e.g. `reload`, loses to the standard button, since `+` keeps the left array's value on conflict):

```blade
:toolbar="\Fabamb\LaravelTabulator\Toolbar::default(route('users.create')) + [
    'export-pdf' => ['icon' => 'fas fa-file-pdf', 'title' => 'Export PDF', 'url' => route('users.export-pdf')],
]"
```

Full example: [`examples/toolbar/`](examples/toolbar/).

## Row actions

A per-row action column (View/Edit/Delete, or anything else) is a plain Tabulator column with no `field` and a custom `formatter`, declared in `:columns` like any other column:

```blade
:columns="[
    ['formatter' => 'rowActionButtons', 'title' => '', 'width' => 100, 'headerSort' => false, 'hozAlign' => 'center'],
    ['field' => 'name', 'title' => 'Name'],
]"
```

`formatter` as a string is a lookup against Tabulator's own formatter registry (built-ins: `plaintext`, `html`, `money`, ...) — not `window[name]` — so it survives the `:columns` array's PHP-array → `@json()` → JS-object trip as-is; no package-specific resolution needed. Register the custom formatter once, before any `new Tabulator(...)` call:

```js
// resources/js/tabulator-formatters.js, imported by resources/js/app.js.
Tabulator.extendModule('format', 'formatters', {
    rowActionButtons: function (cell) {
        const id = cell.getData().id;
        const wrap = document.createElement('div');
        wrap.innerHTML = `<a href="/users/${id}/edit" class="btn btn-sm btn-primary">Edit</a>`;
        return wrap;
    },
});
```

The formatter receives the Tabulator `cell`; `cell.getData()` is the full row, so it needs no server-side transformer — this is a purely client-side column. If a row action instead needs data the row doesn't already carry (a computed field, a related model not otherwise shown), reshape the row with [`transformer()`](#transformers) and read the extra key from `cell.getData()` the same way.

A dropdown-menu formatter (kebab button + Bootstrap 5 `.dropdown-menu`) needs two extra lines: Tabulator rows are `overflow: hidden` and `transform`ed (virtual scroll), which clips a normally-positioned Popper dropdown and traps it in that row's own stacking context. Fix: `strategy: 'fixed'` on the Popper config, and move the menu to `document.body` only while open (back to the cell on close, so redraws don't leak detached nodes) — see `formatters.js` in the full example below.

Full example: [`examples/row-actions/`](examples/row-actions/).

### Reusable factory

Hand-rolling the formatter above per resource repeats the same DOM/URL/dropdown wiring. `resources/js/row-actions.js` ships two configurable factories, `Tabulator.rowActionButtons(actions)` and `Tabulator.rowActionKebab(actions)`, that build the formatter for you from an actions config — no assumed action set, no baked-in labels/icons/colors, no i18n library.

Publish it once:

```bash
php artisan vendor:publish --tag=tabulator-js
```

This copies it to `public/vendor/tabulator/row-actions.js` — a plain global (`Tabulator.rowActionButtons`/`Tabulator.rowActionKebab`), no bundler required. Load it with a `<script>` tag (e.g. via `config('adminlte.plugins')`, or any other global-asset mechanism), or import it from your own `resources/js/app.js` if you do run a bundler:

```js
import '/vendor/tabulator/row-actions.js';
```

Then, per table, define the actions config and register the formatter — same `Tabulator.extendModule` call as any custom formatter:

```blade
@push('js')
<script>
    const userActions = {
        view:   { url: id => `/users/${id}`,      icon: 'bi-eye' },
        edit:   { url: id => `/users/${id}/edit`, icon: 'bi-pencil' },
        delete: {
            url: id => `/users/${id}`, icon: 'bi-trash', method: 'DELETE',
            class: 'text-danger', label: '{{ __('Delete') }}', confirm: '{{ __('Delete this row?') }}',
        },
    };

    Tabulator.extendModule('format', 'formatters', {
        userActionButtons: Tabulator.rowActionButtons(userActions),
        userActionKebab: Tabulator.rowActionKebab(userActions),
    });
</script>
@endpush
```

```blade
:columns="[
    ['formatter' => 'userActionButtons', 'title' => '', 'width' => 120, 'headerSort' => false, 'hozAlign' => 'center'],
    // ...
]"
```

Each action key is free-form (not assumed to be `view`/`edit`/`delete`): `url(id)` (required), `icon` (required, a Bootstrap Icons class), plus optional `class`, `label`, `method` (omitted/`GET` renders an `<a href>`; anything else renders a `<button>` that `fetch`es and refreshes the table), and `confirm`.

Labels are plain strings the config provides — `row-actions.js` has no i18n of its own and never touches this package's own `tabulator.php` lang file, which is reserved for Tabulator's built-in UI strings (toolbar, pagination, ...). Since the actions config is written in your own `@push('js')` block, it's rendered by Blade like any other view — use your own `__()` calls and your own lang file there, same as for any other string in your app.

Full example: [`examples/row-actions-factory/`](examples/row-actions-factory/).

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

## Relation columns

A column can show data from a `belongsTo` (or any) relation using a dotted `field`, e.g. `role.name`. Two things to set up in the `TabulatorTable`:

```php
public function query(): Builder
{
    // Eager-load, or it's an N+1 per row.
    return User::query()->select('id', 'name', 'role_id')->with('role');
}

protected function transformer(): ?callable
{
    return fn (User $user) => [
        'id' => $user->id,
        'name' => $user->name,
        // Nested, not a flat 'role.name' key: Tabulator's dotted `field`
        // reads it as a path into a nested object.
        'role' => ['name' => $user->role?->name],
    ];
}
```

```blade
['field' => 'role.name', 'title' => 'Role', 'headerFilter' => 'input']
```

`headerFilter` on a dotted field works out of the box: `applyFilters()` detects the dot and filters via `whereHas($relation, ...)` instead of a plain `where()`. Sorting on a dotted field is not supported — `orderBy` needs a join, which the package doesn't set up automatically; add one manually in `query()` if you need it.

Full example: [`examples/relations/`](examples/relations/).

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

The search box's placeholder comes from `resources/lang/{locale}/tabulator.php`'s `search_placeholder` key, same as toolbar tooltips.

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

Full example: [`examples/localization/`](examples/localization/).

## Configuration reference (`config/tabulator.php`)

| Key | Description |
|---|---|
| `stack` | Blade `@push` stack the component's `<script>` goes into (must match a `@stack` in your layout) |
| `layout` | Tabulator `layout` option (default `fitColumns`) |
| `movable_columns` | Tabulator `movableColumns` option (default `true`) |
| `responsive_layout` | Tabulator `responsiveLayout` option (default `false`); set to `'collapse'` to hide columns that no longer fit behind a per-row expand arrow (the component adds the `responsiveCollapse` toggle column for it automatically — coexists fine with `selectable`'s own column), or `'hide'` to just drop them. **Requires `layout` to be a fixed-width mode** (`fitData`/`fitDataFill`/`fitDataStretch`) — the default `fitColumns` shrinks every column to always fit instead of ever overflowing, so `collapse`/`hide` never trigger. The `responsive` prop below sets both together automatically |
| `responsive_fixed_layout` | `layout` used when the `responsive` prop is set (default `fitDataFill`) |
| `pagination_size` | Default page size |
| `pagination_size_selector` | Options in the page-size dropdown |
| `pagination_counter` | Tabulator `paginationCounter` value |
| `selectable_width` | Width (px) of the row-selection checkbox column |
| `rownum_width` | Width (px) of the row-number column |
| `locale` | Active locale, used to load `resources/lang/{locale}/tabulator.php` for both Tabulator's own UI strings and toolbar tooltips (`en`/`it` ship built in); set to `false` to keep Tabulator's built-in English |
| `toolbar_button_class` | Default Bootstrap class for toolbar buttons (default `btn-secondary`); override per button with `class` |
| `toolbar_button_size_class` | Bootstrap size class for toolbar buttons |
| `toolbar_separator_class` | Class for the gap rendered by a `['separator' => true]` toolbar entry (default `mx-1`, plain spacing — set to `vr mx-1` for a visible vertical rule instead) |
| `default_toolbar_icons` | Icon, `class` (button color, added on top of `toolbar_button_class`, e.g. `bg-primary`) and `icon_class` (icon color, needed on `bg-body-secondary` buttons for contrast — use theme-adaptive `text-*` utilities so it stays legible when `data-bs-theme` switches to dark) used by `Toolbar::default()` per button, e.g. `['icon' => 'bi bi-plus-lg', 'class' => 'bg-primary']` |
| `search_size_class` | Bootstrap size class for the search box's input group |
| `search_width` | Max width (CSS value) of the search box |
| `search_debounce_ms` / `search_min_chars` | Global search box behavior |
| `search_icon` | CSS class of the icon shown in the search box (default `bi bi-search`, Bootstrap Icons) |

## Testing

```sh
vendor/bin/phpunit tests
```
