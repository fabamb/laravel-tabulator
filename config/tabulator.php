<?php

return [
    // Blade stack name the component pushes its <script> into.
    // AdminLTE's base layout only defines @stack('css')/@stack('js'); other
    // layouts may use a different name (e.g. 'scripts').
    'stack' => 'js',

    'layout' => 'fitColumns',

    'pagination_size' => 10,
    'pagination_size_selector' => [5, 10, 25, 50, 100, true],
    'pagination_counter' => 'rows',

    // Global search box (<x-tabulator-table :search="true" ... />).
    'search_debounce_ms' => 300,
    'search_min_chars' => 2,
    'search_icon' => 'bi bi-search',

    // Tabulator ships English strings only; any other language needs an
    // explicit `langs` entry keyed by the `locale` value below.
    // Set 'locale' => false to leave Tabulator's default (English) as-is.
    'locale' => config('app.locale'),
    'langs' => [
        'it' => [
            'groups' => ['item' => 'elemento', 'items' => 'elementi'],
            'pagination' => [
                'page_size' => 'Righe per pagina',
                'page_title' => 'Vai alla pagina',
                'first' => 'Prima',
                'first_title' => 'Prima pagina',
                'last' => 'Ultima',
                'last_title' => 'Ultima pagina',
                'prev' => 'Precedente',
                'prev_title' => 'Pagina precedente',
                'next' => 'Successivo',
                'next_title' => 'Pagina successiva',
                'all' => 'Tutti',
                'counter' => [
                    'showing' => 'Visualizzazione',
                    'of' => 'di',
                    'rows' => 'righe',
                    'pages' => 'pagine',
                ],
            ],
            'headerFilters' => ['default' => 'filtro colonna...'],
        ],
    ],
];
