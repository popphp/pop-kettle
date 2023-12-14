<?php

namespace Pop\Kettle\Test\Controller;

use Pop\Application;
use Pop\Console\Console;
use Pop\Kettle;
use PHPUnit\Framework\TestCase;

class DatabaseControllerTest extends TestCase
{

    public function testConstructor()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/dev');
        $dotEnv->safeLoad();

        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $controller = new Kettle\Controller\DatabaseController($app, new Console(120, '    '));

        $this->assertInstanceOf('Pop\Application', $controller->application());
        $this->assertInstanceOf('Pop\Console\Console', $controller->console());
    }

}
