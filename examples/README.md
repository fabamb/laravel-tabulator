# Examples

Runnable-shaped snippets for each package feature, referenced from the main [README.md](../README.md). Not autoloaded, not a Laravel app — copy what you need into your own `app/`.

| Folder | Feature |
|---|---|
| [`remote-basic/`](remote-basic/) | Minimal remote table: table class, route, view |
| [`local-data/`](local-data/) | Client-side table, no `ajax-url` |
| [`column-filters/`](column-filters/) | Native Tabulator `headerFilter` per column |
| [`relations/`](relations/) | Column on a `belongsTo` relation (`role.name`), filterable via `whereHas` |
| [`scopes/`](scopes/) | `addScope()`, per-request query constraints |
| [`transformers/`](transformers/) | `transformer()`, reshape rows before JSON |
| [`global-search/`](global-search/) | `search` prop + `searchableFields()` |
| [`navbar-search/`](navbar-search/) | `search-value` prop, search from outside the table's page |
| [`options-override/`](options-override/) | `options` prop, raw Tabulator config passthrough |
| [`toolbar/`](toolbar/) | `toolbar` prop, standard buttons + custom `window.tabulatorButtons` registry |
| [`localization/`](localization/) | `locale` config, built-in `en`/`it` strings, adding a locale via `vendor:publish --tag=tabulator-lang` |
