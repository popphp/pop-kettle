<?php

namespace Pop\Kettle\Test\Controller;

use Pop\Console\Console;
use Pop\Kettle;
use Pop\Kettle\Test\Fixtures\AppTestTrait;
use PHPUnit\Framework\TestCase;

class AbstractControllerTest extends TestCase
{

    use AppTestTrait;

    public function testConstructor()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/dev');
        $dotEnv->safeLoad();

        $app        = $this->makeApp();
        $controller = new Kettle\Controller\ApplicationController($app, new Console(120, '    '));

        $this->assertInstanceOf('Pop\Application', $controller->application());
        $this->assertInstanceOf('Pop\Console\Console', $controller->console());
    }

    public function testError()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/dev');
        $dotEnv->safeLoad();

        $this->expectException('Pop\Kettle\Exception');

        $app        = $this->makeApp();
        $controller = new Kettle\Controller\ApplicationController($app, new Console(120, '    '));
        $controller->error();
    }

}
