<?php

return [
    'routes' => [
        'help' => [
            'controller' => 'MyApp\Console\Controller\ConsoleController',
            'action'     => 'help'
        ],
        '*'    => [
            'controller' => 'MyApp\Console\Controller\ConsoleController',
            'action'     => 'error'
        ]
    ]
];