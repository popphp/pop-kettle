<?php

namespace Pop\Kettle\Test\Controller;

use Pop\Application;
use Pop\Console\Console;
use Pop\Kettle;
use PHPUnit\Framework\TestCase;

class ConsoleControllerTest extends TestCase
{

    public function testServe()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/dev');
        $dotEnv->safeLoad();

        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $controller = new Kettle\Controller\ConsoleController($app, new Console(120, '    '));
        ob_start();
        $controller->serve([], true);
        $result = ob_get_clean();

        $this->assertStringContainsString('PHP web server running on the folder', $result);
    }

    public function testHelp()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/dev');
        $dotEnv->safeLoad();

        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $controller = new Kettle\Controller\ConsoleController($app, new Console(120, '    '));
        ob_start();
        $controller->help();
        $result = ob_get_clean();

        $this->assertStringContainsString('./kettle', $result);
        $this->assertStringContainsString('app:init', $result);
    }

    public function testVersion()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/dev');
        $dotEnv->safeLoad();

        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $controller = new Kettle\Controller\ConsoleController($app, new Console(120, '    '));
        ob_start();
        $controller->version();
        $result = ob_get_clean();

        $this->assertStringContainsString('Version:', $result);
        $this->assertStringContainsString(Kettle\Module::VERSION, $result);
    }

}
