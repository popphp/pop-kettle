<?php

$autoloader = include __DIR__ . '/../vendor/autoload.php';
$autoloader->addPsr4('TestApp\\', __DIR__ . '/../app/src');

try {
    $app = new TestApp\Application($autoloader, include __DIR__ . '/../app/config/app.http.php');
    $app->load();
    $app->run();
} catch (\Exception $exception) {
    $app = new TestApp\Application();
    $app->httpError($exception);
}


