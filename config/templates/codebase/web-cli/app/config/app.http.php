<?php

return [
    'routes' => [
        '[/]' => [
            'controller' => 'App\Http\Controller\IndexController',
            'action'     => 'index'
        ],
        '*'    => [
            'controller' => 'App\Http\Controller\IndexController',
            'action'     => 'error'
        ]
    ],
    'database' => include __DIR__ . '/database.php'
];
