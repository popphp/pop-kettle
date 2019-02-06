<?php

return [
    'app:init' => [
        'controller' => 'Pop\Kettle\Controller\ApplicationController',
        'action'     => 'init'
    ],
    'db:create' => [
        'controller' => 'Pop\Kettle\Controller\DatabaseController',
        'action'     => 'create'
    ],
    'db:init' => [
        'controller' => 'Pop\Kettle\Controller\DatabaseController',
        'action'     => 'init'
    ],
    'db:seed' => [
        'controller' => 'Pop\Kettle\Controller\DatabaseController',
        'action'     => 'seed'
    ],
    'db:clear' => [
        'controller' => 'Pop\Kettle\Controller\DatabaseController',
        'action'     => 'clear'
    ],
    'db:reset' => [
        'controller' => 'Pop\Kettle\Controller\DatabaseController',
        'action'     => 'reset'
    ],
    'db:migrate' => [
        'controller' => 'Pop\Kettle\Controller\DatabaseController',
        'action'     => 'migrate'
    ],
    'serve [--host=] [--port=] [--folder=]' => [
        'controller' => 'Pop\Kettle\Controller\ConsoleController',
        'action'     => 'serve'
    ],
    'help' => [
        'controller' => 'Pop\Kettle\Controller\ConsoleController',
        'action'     => 'help'
    ],
    'version' => [
        'controller' => 'Pop\Kettle\Controller\ConsoleController',
        'action'     => 'version'
    ],
    '*'    => [
        'controller' => 'Pop\Kettle\Controller\ConsoleController',
        'action'     => 'error'
    ]
];