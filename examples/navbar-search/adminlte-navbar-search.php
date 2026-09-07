<?php

// config/adminlte.php — navbar-search menu item, GET to the table's page.
return [
    'navbar-search' => true,
    'navbar-search-item' => [
        'method' => 'get',
        'input_name' => 'search',
        'url' => '/users',
    ],
];
