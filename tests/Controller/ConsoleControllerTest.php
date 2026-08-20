<?php

namespace Pop\Kettle\Test\Controller;

use Pop\Console\Console;
use Pop\Kettle;
use Pop\Kettle\Test\Fixtures\AppTestTrait;
use PHPUnit\Framework\TestCase;

class ConsoleControllerTest extends TestCase
{

    use AppTestTrait;

    public function testServe()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/dev');
        $dotEnv->safeLoad();

        $app        = $this->makeApp();
        $controller = new Kettle\Controller\ConsoleController($app, new Console(120, '    '));

        ob_start();
        $controller->serve([], true);
        $result = ob_get_clean();

        $this->assertStringContainsString('PHP web server running on the folder', $result);
    }

    public function testServeWithOptions()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/dev');
        $dotEnv->safeLoad();

        $app        = $this->makeApp();
        $controller = new Kettle\Controller\ConsoleController($app, new Console(120, '    '));

        ob_start();
        $controller->serve(['host' => '127.0.0.1', 'port' => 8080, 'folder' => 'web'], true);
        $result = ob_get_clean();

        $this->assertStringContainsString('127.0.0.1:8080', $result);
        $this->assertStringContainsString('web', $result);
    }

    public function testHelp()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/dev');
        $dotEnv->safeLoad();

        $app        = $this->makeApp();
        $controller = new Kettle\Controller\ConsoleController($app, new Console(120, '    '));

        ob_start();
        $controller->help();
        $result = ob_get_clean();

        $this->assertStringContainsString('./kettle', $result);
        $this->assertStringContainsString('pop:init', $result);
    }

    public function testHelpRaw()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/dev');
        $dotEnv->safeLoad();

        $app        = $this->makeApp();
        $controller = new Kettle\Controller\ConsoleController($app, new Console(120, '    '));

        ob_start();
        $controller->help(null, ['raw' => true]);
        $result = ob_get_clean();

        $this->assertStringContainsString('pop:init', $result);
    }

    public function testHelpFiltersBySubCommand()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/dev');
        $dotEnv->safeLoad();

        $app        = $this->makeApp();
        $controller = new Kettle\Controller\ConsoleController($app, new Console(120, '    '));

        ob_start();
        $controller->help('db');
        $result = ob_get_clean();

        $this->assertStringContainsString('db:config', $result);
        $this->assertStringNotContainsString('pop:init', $result);
    }

    public function testVersion()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/dev');
        $dotEnv->safeLoad();

        $app        = $this->makeApp();
        $controller = new Kettle\Controller\ConsoleController($app, new Console(120, '    '));

        ob_start();
        $controller->version();
        $result = ob_get_clean();

        $this->assertStringContainsString('Version:', $result);
        $this->assertStringContainsString(Kettle\Application::VERSION, $result);
    }

}
