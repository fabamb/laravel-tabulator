<?php

return [
    // Blade stack name the component pushes its <script> into.
    // AdminLTE's base layout only defines @stack('css')/@stack('js'); other
    // layouts may use a different name (e.g. 'scripts').
    'stack' => 'js',

    'layout' => 'fitColumns',
    'movable_columns' => true,

    // Tabulator's responsiveLayout option. 'collapse' hides columns that no
    // longer fit and lists them under the row behind an expand arrow; 'hide'
    // just drops them. false disables it (default). Per-table override via
    // <x-tabulator-table :options="['responsiveLayout' => ...]" ... />.
    'responsive_layout' => false,

    // Layout used by <x-tabulator-table responsive ... /> (see README).
    // Must be a fixed-width mode — the default 'layout' above ('fitColumns')
    // shrinks every column to always fit instead of ever overflowing, so
    // responsive_layout's 'collapse'/'hide' would never trigger.
    'responsive_fixed_layout' => 'fitDataFill',

    //
    'pagination_size' => 10,
    'pagination_size_selector' => [5, 10, 25, 50, 100, true],
    'pagination_counter' => 'rows',

    // Toolbar buttons (<x-tabulator-table :toolbar="[...]" ... />).
    // Per-button override: pass 'class' in the button's own array.
    'toolbar_button_class' => 'btn-secondary',
    'toolbar_button_size_class' => '',

    // Visual gap between toolbar buttons: add ['separator' => true] as any
    // entry in the :toolbar array to render one at that spot. Plain spacing,
    // no divider line — set to 'vr mx-1' for a visible vertical rule instead.
    'toolbar_separator_class' => 'mx-1',

    // Icons + button class for Toolbar::default() (see below) — override
    // per app, e.g. to switch to Font Awesome or change button colors.
    // 'class' is optional, added on top of 'toolbar_button_class' above
    // (not a replacement) — use a `bg-*` utility, not a `btn-*` variant.
    // 'icon_class' colors the icon itself — needed on `bg-body-secondary`
    // buttons, whose base text color (white, from 'toolbar_button_class')
    // would otherwise be invisible on the light-mode background. Use
    // theme-adaptive text-* utilities here, not text-dark/text-white,
    // so it stays legible when 'data-bs-theme' switches to dark.
    'default_toolbar_icons' => [
        'create' => ['icon' => 'bi bi-plus-lg', 'class' => 'bg-primary'],
        'bulk-edit' => ['icon' => 'bi bi-pencil-square', 'class' => 'bg-body-secondary', 'icon_class' => 'text-body-emphasis'],
        'bulk-delete' => ['icon' => 'bi bi-trash', 'class' => 'bg-danger'],
        'reload' => ['icon' => 'bi bi-arrow-clockwise', 'class' => 'bg-body-secondary', 'icon_class' => 'text-body-emphasis'],
        'csv' => ['icon' => 'bi bi-filetype-csv', 'class' => 'bg-body-secondary', 'icon_class' => 'text-body-emphasis'],
        'print' => ['icon' => 'bi bi-printer', 'class' => 'bg-body-secondary', 'icon_class' => 'text-body-emphasis'],
        'reset' => ['icon' => 'bi bi-arrow-counterclockwise', 'class' => 'bg-body-secondary', 'icon_class' => 'text-body-emphasis'],
    ],

    // Global search box (<x-tabulator-table :search="true" ... />)
    'search_size_class' => '',
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
