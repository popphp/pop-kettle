<?php

return [
    'app:init [--web] [--api] [--cli] <namespace>' => [
        'controller' => 'Pop\Kettle\Controller\ApplicationController',
        'action'     => 'init',
        'help'       => 'Initialize an application'
    ],
    'app:env' => [
        'controller' => 'Pop\Kettle\Controller\ApplicationController',
        'action'     => 'env',
        'help'       => 'Display current application environment'
    ],
    'app:status' => [
        'controller' => 'Pop\Kettle\Controller\ApplicationController',
        'action'     => 'status',
        'help'       => 'Display current application status'
    ],
    'app:down [-s|--secret=]' => [
        'controller' => 'Pop\Kettle\Controller\ApplicationController',
        'action'     => 'down',
        'help'       => 'Turn on application maintenance mode'
    ],
    'app:up' => [
        'controller' => 'Pop\Kettle\Controller\ApplicationController',
        'action'     => 'up',
        'help'       => 'Turn off application maintenance mode' . PHP_EOL
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
    'db:export [<database>]' => [
        'controller' => 'Pop\Kettle\Controller\DatabaseController',
        'action'     => 'export',
        'help'       => 'Export the database to a file (MySQL only)'
    ],
    'db:import <file> [<database>]' => [
        'controller' => 'Pop\Kettle\Controller\DatabaseController',
        'action'     => 'import',
        'help'       => 'Import the database from a file (MySQL only)'
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
    'migrate:point [<id>] [<database>]' => [
        'controller' => 'Pop\Kettle\Controller\MigrationController',
        'action'     => 'point',
        'help'       => 'Point to specific migration, w/o running (.current file only)'
    ],
    'migrate:reset [<database>]' => [
        'controller' => 'Pop\Kettle\Controller\MigrationController',
        'action'     => 'reset',
        'help'       => 'Perform complete rollback of the database' . PHP_EOL
    ],
    'create:ctrl [--web] [--api] [--cli] <ctrl>' => [
        'controller' => 'Pop\Kettle\Controller\ApplicationController',
        'action'     => 'createController',
        'help'       => 'Create a new controller class'
    ],
    'create:model <model>' => [
        'controller' => 'Pop\Kettle\Controller\ApplicationController',
        'action'     => 'createModel',
        'help'       => 'Create a new model class'
    ],
    'create:view <view>' => [
        'controller' => 'Pop\Kettle\Controller\ApplicationController',
        'action'     => 'createView',
        'help'       => 'Create a new view file' . PHP_EOL
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
