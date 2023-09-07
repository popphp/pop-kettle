<?php

return [
    'routes' => [
        'help' => [
            'controller' => 'TestApp\Console\Controller\ConsoleController',
            'action'     => 'help',
            'help'       => 'Show the help screen'
        ],
        '*'    => [
            'controller' => 'TestApp\Console\Controller\ConsoleController',
            'action'     => 'error'
        ]
    ],
    'database' => include __DIR__ . '/database.php'
];