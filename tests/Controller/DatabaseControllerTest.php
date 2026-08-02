<?php

namespace Pop\Kettle\Test\Controller;

use Pop\Application;
use Pop\Console\Console;
use Pop\Db\Db;
use Pop\Kettle;
use PHPUnit\Framework\TestCase;

class DatabaseControllerTest extends TestCase
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

    public function testConfig()
    {
        $dbAdapters  = Db::getAvailableAdapters();
        $sqliteIndex = 0;

        $i = 0;
        foreach ($dbAdapters as $adapter => $result) {
            if ($adapter == 'pdo') {
                foreach ($result as $a => $r) {
                    if ($r) {
                        $i++;
                    }
                }
            } else if ($result) {
                $i++;
                if (strtolower($adapter) == 'sqlite') {
                    $sqliteIndex = $i;
                    break;
                }
            }
        }

        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../../');
        $dotEnv->safeLoad();

        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream((string)$sqliteIndex, '.htpopdb.sqlite'));

        $controller = new Kettle\Controller\DatabaseController($app, $console);

        ob_start();
        $controller->config(null);
        $result = ob_get_clean();

        $this->assertStringContainsString('DB', $result);
    }

    public function testSeed()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../../');
        $dotEnv->safeLoad();

        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $controller = new Kettle\Controller\DatabaseController($app, new Console(120, '    '));

        ob_start();
        $controller->seed(null);
        $result = ob_get_clean();

        $this->assertStringContainsString('Running database seeds for', $result);
    }

    public function testTest()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../../');
        $dotEnv->safeLoad();

        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $controller = new Kettle\Controller\DatabaseController($app, new Console(120, '    '));

        ob_start();
        $controller->test(null);
        $result = ob_get_clean();

        $this->assertStringContainsString('Database configuration test for', $result);
    }

    public function testCreateSeed()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../../');
        $dotEnv->safeLoad();

        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $controller = new Kettle\Controller\DatabaseController($app, new Console(120, '    '));

        ob_start();
        $controller->createSeed('MySeed', null);
        $result = ob_get_clean();

        $files       = scandir(__DIR__ . '/../../database/seeds/default');
        $hasSeedFile = false;
        $seedFile    = null;

        foreach ($files as $file) {
            if (str_ends_with($file, '_my_seed.php')) {
                $hasSeedFile = true;
                $seedFile    = $file;
                break;
            }
        }

        $this->assertStringContainsString("Database seed class 'MySeed' created for", $result);
        $this->assertTrue($hasSeedFile);
        $this->fileExists(__DIR__ . '/../../database/seeds/default/' . $seedFile);

        unlink(__DIR__ . '/../../database/seeds/default/' . $seedFile);
    }

    public function testReset()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../../');
        $dotEnv->safeLoad();

        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $controller = new Kettle\Controller\DatabaseController($app, new Console(120, '    '));

        ob_start();
        $controller->reset(null);
        $result = ob_get_clean();

        $this->assertStringContainsString('Resetting database data', $result);
    }

    public function testClear()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../../');
        $dotEnv->safeLoad();

        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $controller = new Kettle\Controller\DatabaseController($app, new Console(120, '    '));

        ob_start();
        $controller->clear(null);
        $result = ob_get_clean();

        $this->assertStringContainsString('Clearing database data', $result);
    }


    public function testInstall()
    {
        $dbAdapters  = Db::getAvailableAdapters();
        $sqliteIndex = 0;

        $i = 0;
        foreach ($dbAdapters as $adapter => $result) {
            if ($adapter == 'pdo') {
                foreach ($result as $a => $r) {
                    if ($r) {
                        $i++;
                    }
                }
            } else if ($result) {
                $i++;
                if (strtolower($adapter) == 'sqlite') {
                    $sqliteIndex = $i;
                    break;
                }
            }
        }

        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../../');
        $dotEnv->safeLoad();

        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream((string)$sqliteIndex, '.htpopdb.sqlite'));

        $controller = new Kettle\Controller\DatabaseController($app, $console);

        unlink(__DIR__ . '/../../.env');

        ob_start();
        $controller->install(null);
        $result = ob_get_clean();

        $this->assertStringContainsString('DB', $result);
    }

}
