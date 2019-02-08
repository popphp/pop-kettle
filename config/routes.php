<?php

return [
    'app:init [--web] [--api] [--cli] <namespace>' => [
        'controller' => 'Pop\Kettle\Controller\ApplicationController',
        'action'     => 'init'
    ],
    'db:init' => [
        'controller' => 'Pop\Kettle\Controller\DatabaseController',
        'action'     => 'init'
    ],
    'db:test' => [
        'controller' => 'Pop\Kettle\Controller\DatabaseController',
        'action'     => 'test'
    ],
    'db:seed' => [
        'controller' => 'Pop\Kettle\Controller\DatabaseController',
        'action'     => 'seed'
    ],
    'db:reset' => [
        'controller' => 'Pop\Kettle\Controller\DatabaseController',
        'action'     => 'reset'
    ],
    'migrate:create <class>' => [
        'controller' => 'Pop\Kettle\Controller\MigrationController',
        'action'     => 'create'
    ],
    'migrate:run [<steps>]' => [
        'controller' => 'Pop\Kettle\Controller\MigrationController',
        'action'     => 'run'
    ],
    'migrate:rollback [<steps>]' => [
        'controller' => 'Pop\Kettle\Controller\MigrationController',
        'action'     => 'rollback'
    ],
    'migrate:reset' => [
        'controller' => 'Pop\Kettle\Controller\MigrationController',
        'action'     => 'reset'
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