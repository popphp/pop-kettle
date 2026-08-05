<?php

namespace Pop\Kettle\Test\Event;

use Pop\Console\Console;
use Pop\Kettle;
use Pop\Kettle\Test\Fixtures\AppTestTrait;
use PHPUnit\Framework\TestCase;

class ConsoleTest extends TestCase
{

    use AppTestTrait;

    private ?array $origArgv = null;

    protected function setUp(): void
    {
        $this->origArgv = $_SERVER['argv'];
    }

    protected function tearDown(): void
    {
        $_SERVER['argv'] = $this->origArgv;
    }

    public function testMaintenanceDisplayShowsAlertWhenDown()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/prod');
        $dotEnv->safeLoad();

        $_SERVER['argv'] = ['kettle', 'db:seed'];
        $this->makeApp();

        ob_start();
        Kettle\Event\Console::maintenanceDisplay();
        $result = ob_get_clean();

        $this->assertStringContainsString('Application in Maintenance', $result);
    }

    public function testMaintenanceDisplaySuppressedOnAppUpRoute()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/prod');
        $dotEnv->safeLoad();

        $_SERVER['argv'] = ['kettle', 'app:up'];
        $this->makeApp();

        ob_start();
        Kettle\Event\Console::maintenanceDisplay();
        $result = ob_get_clean();

        $this->assertStringNotContainsString('Application in Maintenance', $result);
    }

    public function testMaintenanceDisplaySuppressedWhenUp()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/dev');
        $dotEnv->safeLoad();

        $_SERVER['argv'] = ['kettle', 'db:seed'];
        $this->makeApp();

        ob_start();
        Kettle\Event\Console::maintenanceDisplay();
        $result = ob_get_clean();

        $this->assertStringNotContainsString('Application in Maintenance', $result);
    }

    public function testProductionDisplayShowsWarningAndConfirms()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/prod');
        $dotEnv->safeLoad();

        $_SERVER['argv'] = ['kettle', 'db:seed'];
        $this->makeApp();

        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream('y'));

        ob_start();
        Kettle\Event\Console::productionDisplay($console);
        $result = ob_get_clean();

        $this->assertStringContainsString('Application in Production', $result);
        $this->assertStringContainsString('Are you sure you want to run this command?', $result);
    }

    public function testProductionDisplaySuppressedOnOmittedCommand()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/prod');
        $dotEnv->safeLoad();

        $_SERVER['argv'] = ['kettle', 'help'];
        $this->makeApp();

        ob_start();
        Kettle\Event\Console::productionDisplay();
        $result = ob_get_clean();

        $this->assertStringNotContainsString('Application in Production', $result);
    }

    public function testProductionDisplaySuppressedWhenNotProduction()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/dev');
        $dotEnv->safeLoad();

        $_SERVER['argv'] = ['kettle', 'db:seed'];
        $this->makeApp();

        ob_start();
        Kettle\Event\Console::productionDisplay();
        $result = ob_get_clean();

        $this->assertStringNotContainsString('Application in Production', $result);
    }

}
