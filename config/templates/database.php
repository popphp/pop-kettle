<?php

return [
    'default' => [
        'database' => $_ENV['DB_DATABASE'],
        'adapter'  => $_ENV['DB_ADAPTER'],
        'username' => $_ENV['DB_USERNAME'],
        'password' => $_ENV['DB_PASSWORD'],
        'host'     => $_ENV['DB_HOST'],
        'type'     => $_ENV['DB_TYPE'],
    ],
];
