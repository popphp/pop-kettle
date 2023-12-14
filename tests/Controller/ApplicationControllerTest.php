<?php

namespace Pop\Kettle\Test\Controller;

use Pop\Application;
use Pop\Dir\Dir;
use Pop\Console\Console;
use Pop\Kettle;
use PHPUnit\Framework\TestCase;

class ApplicationControllerTest extends TestCase
{

    public function testInit()
    {
        $_SERVER['X_POP_CONSOLE_INPUT']   = '';
        $_SERVER['X_POP_CONSOLE_INPUT_2'] = '1';
        $_SERVER['X_POP_CONSOLE_INPUT_3'] = '';
        $_SERVER['X_POP_CONSOLE_INPUT_4'] = 'n';

        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/dev');
        $dotEnv->safeLoad();
        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $controller = new Kettle\Controller\ApplicationController($app, new Console(120, '    '));
        ob_start();
        $controller->init('');
        $results = ob_get_clean();

        $this->assertInstanceOf('Pop\Kettle\Controller\ApplicationController', $controller);
    }

    public function testCreateController()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/dev');
        $dotEnv->safeLoad();
        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $controller = new Kettle\Controller\ApplicationController($app, new Console(120, '    '));
        ob_start();
        $controller->createController('TestController');
        $results = ob_get_clean();

        $this->assertStringContainsString("Controller class 'MyApp\Http\Controller\TestController' created.", $results);
        $this->assertFileExists(__DIR__ . '/../../app/src/Http/Controller/TestController.php');
    }

    public function testCreateModel()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/dev');
        $dotEnv->safeLoad();
        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $controller = new Kettle\Controller\ApplicationController($app, new Console(120, '    '));
        ob_start();
        $controller->createModel('TestModel');
        $results = ob_get_clean();

        $this->assertStringContainsString("Model class 'MyApp\Model\TestModel' created.", $results);
        $this->assertFileExists(__DIR__ . '/../../app/src/Model/TestModel.php');
    }

    public function testCreateView()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/dev');
        $dotEnv->safeLoad();
        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $controller = new Kettle\Controller\ApplicationController($app, new Console(120, '    '));
        ob_start();
        $controller->createView('test.phtml');
        $results = ob_get_clean();

        $this->assertStringContainsString("View file 'test.phtml' created.", $results);
        $this->assertFileExists(__DIR__ . '/../../app/view/test.phtml');

        if (file_exists(__DIR__ . '/../../app')) {
            $dir = new Dir(__DIR__ . '/../../app');
            $dir->emptyDir(true);
        }
    }

    public function testEnvLocal()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/local');
        $dotEnv->safeLoad();
        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $controller = new Kettle\Controller\ApplicationController($app, new Console(120, '    '));
        ob_start();
        $controller->env();
        $results = ob_get_clean();

        $this->assertStringContainsString('Application in Local', $results);
    }

    public function testEnvDev()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/dev');
        $dotEnv->safeLoad();
        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $controller = new Kettle\Controller\ApplicationController($app, new Console(120, '    '));
        ob_start();
        $controller->env();
        $results = ob_get_clean();

        $this->assertStringContainsString('Application in Dev', $results);
    }

    public function testEnvTesting()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/testing');
        $dotEnv->safeLoad();
        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $controller = new Kettle\Controller\ApplicationController($app, new Console(120, '    '));
        ob_start();
        $controller->env();
        $results = ob_get_clean();

        $this->assertStringContainsString('Application in Testing', $results);
    }

    public function testEnvStaging()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/staging');
        $dotEnv->safeLoad();
        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $controller = new Kettle\Controller\ApplicationController($app, new Console(120, '    '));
        ob_start();
        $controller->env();
        $results = ob_get_clean();

        $this->assertStringContainsString('Application in Staging', $results);
    }

    public function testEnvProd()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/prod');
        $dotEnv->safeLoad();
        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $controller = new Kettle\Controller\ApplicationController($app, new Console(120, '    '));
        ob_start();
        $controller->env();
        $results = ob_get_clean();

        $this->assertStringContainsString('Application in Production', $results);
    }

    public function testStatus()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../tmp/dev');
        $dotEnv->safeLoad();
        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $controller = new Kettle\Controller\ApplicationController($app, new Console(120, '    '));
        ob_start();
        $controller->status();
        $results = ob_get_clean();

        $this->assertStringContainsString('Application is Live', $results);
    }

    public function testDown1()
    {
        copy(__DIR__ . '/../tmp/dev/.env', __DIR__ . '/../../.env');

        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../../');
        $dotEnv->safeLoad();
        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $controller = new Kettle\Controller\ApplicationController($app, new Console(120, '    '));
        ob_start();
        $controller->down(['secret' => 123456]);
        $results = ob_get_clean();

        $this->assertStringContainsString('Application has been switched to maintenance mode.', $results);
    }

    public function testDown2()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../../');
        $dotEnv->safeLoad();
        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $controller = new Kettle\Controller\ApplicationController($app, new Console(120, '    '));
        ob_start();
        $controller->down(['secret' => 123456]);
        $results = ob_get_clean();

        $this->assertStringContainsString('Application is currently in maintenance mode. No action to take.', $results);
    }

    public function testUp1()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../../');
        $dotEnv->safeLoad();
        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $controller = new Kettle\Controller\ApplicationController($app, new Console(120, '    '));
        ob_start();
        $controller->up();
        $results = ob_get_clean();

        $this->assertStringContainsString('Application is Live', $results);
    }

    public function testUp2()
    {
        $dotEnv = \Dotenv\Dotenv::createMutable(__DIR__ . '/../../');
        $dotEnv->safeLoad();
        $app = new Application(include __DIR__ . '/../../vendor/autoload.php', include __DIR__ . '/../../config/app.console.php');
        if (file_exists(__DIR__ . '/../../kettle.inc.php')) {
            include __DIR__ . '/../../kettle.inc.php';
        }
        $app->register(new Kettle\Module());

        $controller = new Kettle\Controller\ApplicationController($app, new Console(120, '    '));
        ob_start();
        $controller->up();
        $results = ob_get_clean();

        $this->assertStringContainsString('Application is currently live. No action to take.', $results);

        if (file_exists(__DIR__ . '/../../.env')) {
            unlink(__DIR__ . '/../../.env');
        }
    }

}
