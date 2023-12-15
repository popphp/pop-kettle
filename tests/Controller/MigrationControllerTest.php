<?php

namespace Pop\Kettle\Test\Controller;

use Pop\Application;
use Pop\Console\Console;
use Pop\Kettle;
use PHPUnit\Framework\TestCase;

class MigrationControllerTest extends TestCase
{

    public function testCreate1()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../../');
        $dotEnv->safeLoad();

        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $controller = new Kettle\Controller\MigrationController($app, new Console(120, '    '));

        ob_start();
        $controller->create('MyMigration', null);
        $result = ob_get_clean();

        $this->assertStringContainsString('Migration class', $result);
    }

    public function testCreate2()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../../');
        $dotEnv->safeLoad();

        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $controller = new Kettle\Controller\MigrationController($app, new Console(120, '    '));

        ob_start();
        $controller->create('MyMigration', 'all');
        $result = ob_get_clean();

        $this->assertStringContainsString('Migration class', $result);
    }

    public function testRun()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../../');
        $dotEnv->safeLoad();

        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $controller = new Kettle\Controller\MigrationController($app, new Console(120, '    '));

        ob_start();
        $controller->run();
        $result = ob_get_clean();

        $this->assertStringContainsString('Running database migration for', $result);
    }

    public function testRollback()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../../');
        $dotEnv->safeLoad();

        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $controller = new Kettle\Controller\MigrationController($app, new Console(120, '    '));

        ob_start();
        $controller->rollback();
        $result = ob_get_clean();

        $this->assertStringContainsString('Rolling back database migration for', $result);
    }

    public function testPoint()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../../');
        $dotEnv->safeLoad();

        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        touch(__DIR__ . '/../../database/migrations/default/.current');

        $controller = new Kettle\Controller\MigrationController($app, new Console(120, '    '));

        ob_start();
        $controller->point('latest', null);
        $result = ob_get_clean();

        $this->assertStringContainsString('Done!', $result);
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

        touch(__DIR__ . '/../../database/migrations/default/.current');

        $controller = new Kettle\Controller\MigrationController($app, new Console(120, '    '));

        ob_start();
        $controller->reset(null);
        $result = ob_get_clean();

        $this->assertStringContainsString('Done!', $result);
    }

}
