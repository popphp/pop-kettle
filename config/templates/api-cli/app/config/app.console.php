<?php

return [
    'routes' => [
        'help' => [
            'controller' => 'MyApp\Console\Controller\ConsoleController',
            'action'     => 'help',
            'help'       => 'Show the help screen'
        ],
        '*'    => [
            'controller' => 'MyApp\Console\Controller\ConsoleController',
            'action'     => 'error'
        ]
    ],
    'database' => include __DIR__ . '/database.php'
];