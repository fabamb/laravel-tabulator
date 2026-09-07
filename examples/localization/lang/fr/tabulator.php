<?php

// Adding a locale the package doesn't ship (en/it are built in): publish
// with `php artisan vendor:publish --tag=tabulator-lang`, then drop a file
// like this one at lang/vendor/tabulator/fr/tabulator.php.
return [
    'toolbar' => [
        'reload' => 'Actualiser',
        'csv' => 'Exporter CSV',
        'print' => 'Imprimer',
        'reset' => 'Réinitialiser les filtres',
    ],

    // Tabulator's own `langs` option, passed through as-is.
    'js' => [
        'groups' => ['item' => 'élément', 'items' => 'éléments'],
        'pagination' => [
            'page_size' => 'Taille de page',
            'page_title' => 'Afficher la page',
            'first' => 'Premier',
            'first_title' => 'Première page',
            'last' => 'Dernier',
            'last_title' => 'Dernière page',
            'prev' => 'Précédent',
            'prev_title' => 'Page précédente',
            'next' => 'Suivant',
            'next_title' => 'Page suivante',
            'all' => 'Tous',
            'counter' => [
                'showing' => 'Affichage de',
                'of' => 'sur',
                'rows' => 'lignes',
                'pages' => 'pages',
            ],
        ],
        'headerFilters' => ['default' => 'filtrer la colonne...'],
    ],
];
