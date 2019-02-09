<?php

return [
    'routes' => [
        '[/]' => [
            'controller' => 'MyApp\Http\Controller\IndexController',
            'action'     => 'index'
        ],
        '*'    => [
            'controller' => 'MyApp\Http\Controller\IndexController',
            'action'     => 'error'
        ]
    ],
    'database' => include __DIR__ . '/database.php'
];