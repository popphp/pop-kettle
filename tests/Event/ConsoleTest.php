<?php

namespace Pop\Kettle\Test\Event;

use Pop\Application;
use Pop\Kettle;
use PHPUnit\Framework\TestCase;

class ConsoleTest extends TestCase
{

    private function createInputStream(string ...$lines): mixed
    {
        $stream = fopen('php://memory', 'r+');
        foreach ($lines as $line) {
            fwrite($stream, $line . PHP_EOL);
        }
        rewind($stream);
        return $stream;
    }

    public function testHeader()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/dev');
        $dotEnv->safeLoad();

        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        ob_start();
        Kettle\Event\Console::header();
        $result = ob_get_clean();

        $this->assertStringContainsString('Pop Kettle', $result);
    }


    public function testHeaderWithWarnings()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/prod');
        $dotEnv->safeLoad();

        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }

        $module = new Kettle\Module();
        $app->register($module);
        $module->getConsole()->setInputStream($this->createInputStream('y'));

        ob_start();
        Kettle\Event\Console::header($module->getConsole());
        $result = ob_get_clean();

        $this->assertStringContainsString('Application in Maintenance', $result);
        $this->assertStringContainsString('Application in Production', $result);
    }

    public function testFooter()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/dev');
        $dotEnv->safeLoad();

        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        ob_start();
        Kettle\Event\Console::footer();
        $result = ob_get_clean();

        $this->assertStringContainsString(PHP_EOL, $result);
    }

}
