<?php

return [
    // Blade stack name the component pushes its <script> into.
    // AdminLTE's base layout only defines @stack('css')/@stack('js'); other
    // layouts may use a different name (e.g. 'scripts').
    'stack' => 'js',

    'layout' => 'fitColumns',
    'movable_columns' => true,

    //
    'pagination_size' => 10,
    'pagination_size_selector' => [5, 10, 25, 50, 100, true],
    'pagination_counter' => 'rows',

    // Toolbar buttons (<x-tabulator-table :toolbar="[...]" ... />).
    // Per-button override: pass 'class' in the button's own array.
    'toolbar_button_class' => 'btn-secondary',
    'toolbar_button_size_class' => 'btn-sm',

    // Global search box (<x-tabulator-table :search="true" ... />)
    'search_size_class' => 'input-group-sm',
    'search_width' => '300px',
    'search_debounce_ms' => 300,
    'search_min_chars' => 2,
    'search_icon' => 'bi bi-search',

    'selectable_width' => 30,
    'rownum_width' => 50,

    // Tabulator's own UI strings (pagination, header filter placeholder)
    // and the toolbar button tooltips both come from resources/lang/{locale}/tabulator.php
    // (publish with `php artisan vendor:publish --tag=tabulator-lang` to add/edit a locale).
    // Set 'locale' => false to leave Tabulator's built-in default (English) as-is.
    'locale' => config('app.locale'),
];
