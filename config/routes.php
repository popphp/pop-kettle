<?php

return [
    'app:init [--web] [--api] [--cli] <namespace>' => [
        'controller' => 'Pop\Kettle\Controller\ApplicationController',
        'action'     => 'init',
        'help'       => 'Initialize an application' . PHP_EOL
    ],
    'db:install [<database>]' => [
        'controller' => 'Pop\Kettle\Controller\DatabaseController',
        'action'     => 'install',
        'help'       => 'Install the database (Runs the config, test and seed commands)'
    ],
    'db:config [<database>]' => [
        'controller' => 'Pop\Kettle\Controller\DatabaseController',
        'action'     => 'config',
        'help'       => 'Configure the database'
    ],
    'db:test [<database>]' => [
        'controller' => 'Pop\Kettle\Controller\DatabaseController',
        'action'     => 'test',
        'help'       => 'Test the database connection'
    ],
    'db:create-seed <seed> [<database>]' => [
        'controller' => 'Pop\Kettle\Controller\DatabaseController',
        'action'     => 'createSeed',
        'help'       => 'Create database seed class'
    ],
    'db:seed [<database>]' => [
        'controller' => 'Pop\Kettle\Controller\DatabaseController',
        'action'     => 'seed',
        'help'       => 'Seed the database with data'
    ],
    'db:reset [<database>]' => [
        'controller' => 'Pop\Kettle\Controller\DatabaseController',
        'action'     => 'reset',
        'help'       => 'Reset the database with original seed data'
    ],
    'db:clear [<database>]' => [
        'controller' => 'Pop\Kettle\Controller\DatabaseController',
        'action'     => 'clear',
        'help'       => 'Clear the database of all data' . PHP_EOL
    ],
    'migrate:create <class> [<database>]' => [
        'controller' => 'Pop\Kettle\Controller\MigrationController',
        'action'     => 'create',
        'help'       => 'Create new database migration class'
    ],
    'migrate:run [<steps>] [<database>]' => [
        'controller' => 'Pop\Kettle\Controller\MigrationController',
        'action'     => 'run',
        'help'       => 'Perform forward database migration'
    ],
    'migrate:rollback [<steps>] [<database>]' => [
        'controller' => 'Pop\Kettle\Controller\MigrationController',
        'action'     => 'rollback',
        'help'       => 'Perform backward database migration'
    ],
    'migrate:reset [<database>]' => [
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