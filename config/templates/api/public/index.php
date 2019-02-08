<?php

$autoloader = include __DIR__ . '/../vendor/autoload.php';
$autoloader->addPsr4('MyApp\\', __DIR__ . '/../app/src');

try {
    $app = new Popcorn\Pop($autoloader, include __DIR__ . '/../app/config/app.http.php');
    $app->register(new MyApp\Module());
    $app->run();
} catch (\Exception $exception) {
    $app = new MyApp\Module();
    $app->httpError($exception);
}


