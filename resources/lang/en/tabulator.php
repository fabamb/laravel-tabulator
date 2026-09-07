<?php

return [
    // Global search box placeholder (<x-tabulator-table :search="true" ... />).
    'search_placeholder' => 'Search',

    // Toolbar button tooltips (<x-tabulator-table :toolbar="[...]" ... />),
    // used as fallback when a button doesn't set its own 'title'.
    'toolbar' => [
        'create' => 'New',
        'bulk-edit' => 'Edit selected',
        'bulk-delete' => 'Delete selected',
        'reload' => 'Reload',
        'csv' => 'Export CSV',
        'print' => 'Print',
        'reset' => 'Reset filters',
    ],

    // Tabulator's own `langs` option — passed as-is into the JS config
    // when config('tabulator.locale') matches this file's locale. Tabulator
    // ships English by default, this mirrors it so setting locale => 'en'
    // explicitly still works instead of relying on Tabulator's fallback.
    'js' => [
        'groups' => ['item' => 'item', 'items' => 'items'],
        'pagination' => [
            'page_size' => 'Page Size',
            'page_title' => 'Show Page',
            'first' => 'First',
            'first_title' => 'First Page',
            'last' => 'Last',
            'last_title' => 'Last Page',
            'prev' => 'Prev',
            'prev_title' => 'Prev Page',
            'next' => 'Next',
            'next_title' => 'Next Page',
            'all' => 'All',
            'counter' => [
                'showing' => 'Showing',
                'of' => 'of',
                'rows' => 'rows',
                'pages' => 'pages',
            ],
        ],
        'headerFilters' => ['default' => 'filter column...'],
    ],
];
