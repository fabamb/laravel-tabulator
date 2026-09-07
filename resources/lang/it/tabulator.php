<?php

return [
    // Global search box placeholder (<x-tabulator-table :search="true" ... />).
    'search_placeholder' => 'Cerca',

    // Toolbar button tooltips (<x-tabulator-table :toolbar="[...]" ... />),
    // used as fallback when a button doesn't set its own 'title'.
    'toolbar' => [
        'create' => 'Nuovo',
        'bulk-edit' => 'Modifica selezionati',
        'bulk-delete' => 'Elimina selezionati',
        'reload' => 'Ricarica',
        'csv' => 'Esporta CSV',
        'print' => 'Stampa',
        'reset' => 'Reimposta filtri',
    ],

    // Tabulator's own `langs` option — passed as-is into the JS config
    // when config('tabulator.locale') matches this file's locale.
    'js' => [
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
];
