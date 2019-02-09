<?php

return [
    'routes' => [
        'get' => [
            '/api[/]' => [
                'controller' => 'MyApp\Http\Api\Controller\IndexController',
                'action'     => 'index'
            ],
            '[/]' => [
                'controller' => 'MyApp\Http\Web\Controller\IndexController',
                'action'     => 'index'
            ],
        ],
        '*' => [
            '*'    => [
                'controller' => 'MyApp\Http\Controller\IndexController',
                'action'     => 'error'
            ]
        ]
    ],
    'database' => include __DIR__ . '/database.php',
    'http_options_headers' => [
        'Access-Control-Allow-Origin'  => '*',
        'Access-Control-Allow-Headers' => 'Accept, Authorization, Content-Type, X-Resource, X-Permission',
        'Access-Control-Allow-Methods' => 'HEAD, OPTIONS, GET, PUT, POST, PATCH, DELETE',
        'Content-Type'                 => 'application/json'
    ]
];