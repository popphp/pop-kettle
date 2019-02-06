<?php

return [
    'help' => [
        'controller' => 'Pop\Kettle\Controller\ConsoleController',
        'action'     => 'help'
    ],
    '*'    => [
        'controller' => 'Pop\Kettle\Controller\ConsoleController',
        'action'     => 'error'
    ]
];