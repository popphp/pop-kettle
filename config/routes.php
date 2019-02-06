<?php

return [
    'help' => [
        'controller' => 'Pop\Kettle\Controller\ConsoleController',
        'action'     => 'help'
    ],
    '-v|--version' => [
        'controller' => 'Pop\Kettle\Controller\ConsoleController',
        'action'     => 'version'
    ],
    '*'    => [
        'controller' => 'Pop\Kettle\Controller\ConsoleController',
        'action'     => 'error'
    ]
];