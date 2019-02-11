<?php

return [
    'app:init [--web] [--api] [--cli] <namespace>' => [
        'controller' => 'Pop\Kettle\Controller\ApplicationController',
        'action'     => 'init',
        'help'       => 'Initialize an application' . PHP_EOL
    ],
    'db:config' => [
        'controller' => 'Pop\Kettle\Controller\DatabaseController',
        'action'     => 'config',
        'help'       => 'Configure the database'
    ],
    'db:test' => [
        'controller' => 'Pop\Kettle\Controller\DatabaseController',
        'action'     => 'test',
        'help'       => 'Test the database connection'
    ],
    'db:seed' => [
        'controller' => 'Pop\Kettle\Controller\DatabaseController',
        'action'     => 'seed',
        'help'       => 'Seed the database with data'
    ],
    'db:reset' => [
        'controller' => 'Pop\Kettle\Controller\DatabaseController',
        'action'     => 'reset',
        'help'       => 'Reset the database with original seed data'
    ],
    'db:clear' => [
        'controller' => 'Pop\Kettle\Controller\DatabaseController',
        'action'     => 'clear',
        'help'       => 'Clear the database of all data' . PHP_EOL
    ],
    'migrate:create <class>' => [
        'controller' => 'Pop\Kettle\Controller\MigrationController',
        'action'     => 'create',
        'help'       => 'Create new database migration'
    ],
    'migrate:run [<steps>]' => [
        'controller' => 'Pop\Kettle\Controller\MigrationController',
        'action'     => 'run',
        'help'       => 'Perform forward database migration'
    ],
    'migrate:rollback [<steps>]' => [
        'controller' => 'Pop\Kettle\Controller\MigrationController',
        'action'     => 'rollback',
        'help'       => 'Perform backward database migration'
    ],
    'migrate:reset' => [
        'controller' => 'Pop\Kettle\Controller\MigrationController',
        'action'     => 'reset',
        'help'       => 'Perform complete rollback of the database' . PHP_EOL
    ],
    'serve [--host=] [--port=] [--folder=]' => [
        'controller' => 'Pop\Kettle\Controller\ConsoleController',
        'action'     => 'serve',
        'help'       => 'Start the web server'
    ],
    'help' => [
        'controller' => 'Pop\Kettle\Controller\ConsoleController',
        'action'     => 'help',
        'help'       => 'Show the help screen'
    ],
    'version' => [
        'controller' => 'Pop\Kettle\Controller\ConsoleController',
        'action'     => 'version',
        'help'       => 'Show the version'
    ],
    '*'    => [
        'controller' => 'Pop\Kettle\Controller\ConsoleController',
        'action'     => 'error'
    ]
];