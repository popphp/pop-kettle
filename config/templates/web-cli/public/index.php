<?php

$autoloader = include __DIR__ . '/../vendor/autoload.php';
$autoloader->addPsr4('MyApp\\', __DIR__ . '/../app/src');

$dotEnv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotEnv->safeLoad();

try {
    $app = new MyApp\Application($autoloader, include __DIR__ . '/../app/config/app.http.php');
    $app->load();
    $app->run();
} catch (\Exception $exception) {
    $app = new MyApp\Application();
    $app->httpError($exception);
}


