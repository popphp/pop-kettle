<?php

namespace Pop\Kettle\Test\Model;

use Pop\Kettle\Model;
use Pop\Kettle\Test\Fixtures\AppTestTrait;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{

    use AppTestTrait;

    protected function setUp(): void
    {
        $this->enterSandbox();
    }

    protected function tearDown(): void
    {
        $this->leaveSandbox();
    }

    public function testInitApiOnly()
    {
        $application = new Model\Application();
        $application->init(getcwd(), 'App', null, true, null);

        $this->assertFileExists(getcwd() . '/app/src/Http/Controller/AbstractController.php');
        $this->assertFileExists(getcwd() . '/app/src/Http/Event');
    }

    public function testInitWebApi()
    {
        $application = new Model\Application();
        $application->init(getcwd(), 'App', true, true, null);

        $this->assertFileExists(getcwd() . '/app/src/Http/Web/Controller/AbstractController.php');
        $this->assertFileExists(getcwd() . '/app/src/Http/Api/Controller/AbstractController.php');
    }

    public function testInitApiCli()
    {
        $application = new Model\Application();
        $application->init(getcwd(), 'App', null, true, true, cliApp: true);

        $this->assertFileExists(getcwd() . '/app/src/Console/Controller/AbstractController.php');
    }

    public function testInitWebCli()
    {
        $application = new Model\Application();
        $application->init(getcwd(), 'App', true, null, true, cliApp: true);

        $this->assertFileExists(getcwd() . '/app/src/Console/Controller/AbstractController.php');
        $this->assertFileExists(getcwd() . '/app/src/Http/Controller/AbstractController.php');
    }

    public function testInitCliOnly()
    {
        $application = new Model\Application();
        $application->init(getcwd(), 'App', null, null, true, cliApp: true);

        $this->assertFileExists(getcwd() . '/app/src/Console/Controller/AbstractController.php');
        $this->assertFileExists(getcwd() . '/app/src/Console/Command/Kettle/.empty');
    }

    public function testInitCliOnlyWithoutStandaloneAppRemovesConsoleController()
    {
        $application = new Model\Application();
        $application->init(getcwd(), 'App', null, null, true);

        $this->assertFileDoesNotExist(getcwd() . '/app/src/Console/Controller');
        $this->assertFileExists(getcwd() . '/app/src/Console/Command/Kettle/.empty');
    }

    public function testInitWebApiCli()
    {
        $application = new Model\Application();
        $application->init(getcwd(), 'App', true, true, true, cliApp: true);

        $this->assertFileExists(getcwd() . '/app/src/Console/Controller/AbstractController.php');
        $this->assertFileExists(getcwd() . '/app/src/Http/Web/Controller/AbstractController.php');
        $this->assertFileExists(getcwd() . '/app/src/Http/Api/Controller/AbstractController.php');
    }

    public function testInitDefaultsToWeb()
    {
        $application = new Model\Application();
        $application->init(getcwd(), 'App');

        $this->assertFileExists(getcwd() . '/app/src/Http/Controller/AbstractController.php');
    }

    public function testCreateCommand()
    {
        $this->scaffoldApp('cli');
        $application = new Model\Application();
        $application->createCommand('send-email', getcwd());

        $this->assertFileExists(getcwd() . '/app/src/Console/Command/Kettle/SendEmail.php');
        $this->assertStringContainsString(
            "'send-email'", file_get_contents(getcwd() . '/app/src/Console/Command/Kettle/SendEmail.php')
        );
    }

    public function testCreateCommandWithNamespacedSignature()
    {
        $this->scaffoldApp('cli');
        $application = new Model\Application();
        $application->createCommand('email:send', getcwd());

        $this->assertFileExists(getcwd() . '/app/src/Console/Command/Kettle/Send.php');
        $this->assertStringContainsString(
            "'email:send'", file_get_contents(getcwd() . '/app/src/Console/Command/Kettle/Send.php')
        );
    }

    public function testCreateCommandForStandaloneApp()
    {
        $this->scaffoldApp('cli');
        $application = new Model\Application();
        $application->createCommand('send-email', getcwd(), true);

        $this->assertFileExists(getcwd() . '/app/src/Console/Command/SendEmail.php');
        $this->assertFileDoesNotExist(getcwd() . '/app/src/Console/Command/Kettle/SendEmail.php');

        $contents = file_get_contents(getcwd() . '/app/src/Console/Command/SendEmail.php');
        $this->assertStringContainsString("'send-email'", $contents);
        $this->assertStringContainsString('namespace App\Console\Command;', $contents);
    }

    public function testScaffoldedKettleCommandIsInstantiable()
    {
        // Regression guard for pop-console's AbstractCommand refactor:
        // its constructor now leads with (?Application, Console) via
        // Pop\Dispatch\ConsoleTrait and load()/loadForApplication() were
        // removed, so a scaffolded command must still be constructable
        // with no arguments (as CommandRegistry::loadRoutes() does) and
        // dispatchable via handle().
        $this->scaffoldApp('cli', 'ScaffoldCmdKettle');
        $application = new Model\Application();
        $application->createCommand('greet', getcwd());

        $autoloader = include __DIR__ . '/../../vendor/autoload.php';
        $autoloader->addPsr4('ScaffoldCmdKettle\\', getcwd() . '/app/src');

        $command = new \ScaffoldCmdKettle\Console\Command\Kettle\Greet();

        $this->assertInstanceOf('Pop\Console\Command\AbstractCommand', $command);
        $this->assertInstanceOf('Pop\Dispatch\DispatchableInterface', $command);
        $this->assertSame('greet', $command->getName());
    }

    public function testScaffoldedStandaloneCommandIsInstantiable()
    {
        $this->scaffoldApp('cli', 'ScaffoldCmdApp');
        $application = new Model\Application();
        $application->createCommand('greet', getcwd(), true);

        $autoloader = include __DIR__ . '/../../vendor/autoload.php';
        $autoloader->addPsr4('ScaffoldCmdApp\\', getcwd() . '/app/src');

        $command = new \ScaffoldCmdApp\Console\Command\Greet();

        $this->assertInstanceOf('Pop\Console\Command\AbstractCommand', $command);
        $this->assertInstanceOf('Pop\Dispatch\DispatchableInterface', $command);
        $this->assertSame('greet', $command->getName());
    }

    public function testCreateCommandIsNoopWhenFileAlreadyExists()
    {
        $this->scaffoldApp('cli');
        $application = new Model\Application();
        $application->createCommand('send-email', getcwd());

        $original = file_get_contents(getcwd() . '/app/src/Console/Command/Kettle/SendEmail.php');

        // Creating the same command again should not touch the existing file
        $application->createCommand('send-email', getcwd());

        $this->assertSame($original, file_get_contents(getcwd() . '/app/src/Console/Command/Kettle/SendEmail.php'));
    }

    public function testCreateCommandMissingFolderThrows()
    {
        $this->scaffoldApp('web');
        $application = new Model\Application();

        $this->expectException('Pop\Kettle\Exception');
        $application->createCommand('send-email', getcwd());
    }

    public function testCreateControllerCli()
    {
        $this->scaffoldApp('cli', 'App', true);
        $application = new Model\Application();
        $result      = $application->createController('MyController', getcwd(), null, null, true);

        $this->assertSame(['App\Console\Controller\MyController'], $result);
        $this->assertFileExists(getcwd() . '/app/src/Console/Controller/MyController.php');
    }

    public function testCreateControllerWeb()
    {
        $this->scaffoldApp('web-api');
        $application = new Model\Application();
        $result      = $application->createController('MyController', getcwd(), true, null, null);

        $this->assertSame(['App\Http\Web\Controller\MyController'], $result);
        $this->assertFileExists(getcwd() . '/app/src/Http/Web/Controller/MyController.php');
    }

    public function testCreateControllerApi()
    {
        $this->scaffoldApp('web-api');
        $application = new Model\Application();
        $result      = $application->createController('MyController', getcwd(), null, true, null);

        $this->assertSame(['App\Http\Api\Controller\MyController'], $result);
        $this->assertFileExists(getcwd() . '/app/src/Http/Api/Controller/MyController.php');
    }

    public function testCreateControllerDefault()
    {
        $this->scaffoldApp('web');
        $application = new Model\Application();
        $result      = $application->createController('MyController', getcwd(), null, null, null);

        $this->assertSame(['App\Http\Controller\MyController'], $result);
        $this->assertFileExists(getcwd() . '/app/src/Http/Controller/MyController.php');
    }

    public function testCreateControllerAllFlavors()
    {
        $this->scaffoldApp('web-api-cli', 'App', true);
        $application = new Model\Application();
        $result      = $application->createController('MyController', getcwd(), true, true, true);

        $this->assertCount(3, $result);
        $this->assertFileExists(getcwd() . '/app/src/Console/Controller/MyController.php');
        $this->assertFileExists(getcwd() . '/app/src/Http/Web/Controller/MyController.php');
        $this->assertFileExists(getcwd() . '/app/src/Http/Api/Controller/MyController.php');
    }

    public function testCreateControllerNestedPath()
    {
        $this->scaffoldApp('web');
        $application = new Model\Application();
        $result      = $application->createController('Admin/Users', getcwd(), null, null, null);

        $this->assertSame(['App\Http\Controller\Admin\Users'], $result);
        $this->assertFileExists(getcwd() . '/app/src/Http/Controller/Admin/Users.php');
    }

    public function testCreateControllerCliNestedPath()
    {
        $this->scaffoldApp('cli', 'App', true);
        $application = new Model\Application();
        $result      = $application->createController('Admin/Tools', getcwd(), null, null, true);

        $this->assertSame(['App\Console\Controller\Admin\Tools'], $result);
        $this->assertFileExists(getcwd() . '/app/src/Console/Controller/Admin/Tools.php');
    }

    public function testCreateControllerWebNestedPath()
    {
        $this->scaffoldApp('web-api');
        $application = new Model\Application();
        $result      = $application->createController('Admin/Tools', getcwd(), true, null, null);

        $this->assertSame(['App\Http\Web\Controller\Admin\Tools'], $result);
        $this->assertFileExists(getcwd() . '/app/src/Http/Web/Controller/Admin/Tools.php');
    }

    public function testCreateControllerApiNestedPath()
    {
        $this->scaffoldApp('web-api');
        $application = new Model\Application();
        $result      = $application->createController('Admin/Tools', getcwd(), null, true, null);

        $this->assertSame(['App\Http\Api\Controller\Admin\Tools'], $result);
        $this->assertFileExists(getcwd() . '/app/src/Http/Api/Controller/Admin/Tools.php');
    }

    public function testCreateControllerCliMissingFolderThrows()
    {
        $this->scaffoldApp('web');
        $application = new Model\Application();

        $this->expectException('Pop\Kettle\Exception');
        $application->createController('MyController', getcwd(), null, null, true);
    }

    public function testCreateControllerCliDeniedWithoutStandaloneApp()
    {
        // Scaffolded as 'cli' but without opting into a stand-alone ./script
        // app - Console/Controller is removed in that case (you're expected
        // to piggyback commands through Kettle instead), so create:ctrl --cli
        // should refuse with a specific message rather than the generic
        // "folder not created" error.
        $this->scaffoldApp('cli');
        $application = new Model\Application();

        $this->expectException('Pop\Kettle\Exception');
        $this->expectExceptionMessage('This application was not initialized with a stand-alone console application.');
        $application->createController('MyController', getcwd(), null, null, true);
    }

    public function testScaffoldedControllersAreInstantiable()
    {
        // Regression guard for the popphp Http/Console trait refactor: the
        // scaffolding templates used to reference the now-removed
        // Pop\Controller\HttpControllerTrait / ConsoleControllerTrait
        // classes, which only ever surfaced as a fatal "Trait not found"
        // error once a generated app's controller was actually loaded -
        // none of the other tests here go further than asserting the file
        // exists, so this instantiates each flavor for real.
        $this->scaffoldApp('web-api-cli', 'ScaffoldCtrl', true);
        $application = new Model\Application();
        $application->createController('MyController', getcwd(), true, true, true);

        $autoloader = include __DIR__ . '/../../vendor/autoload.php';
        $autoloader->addPsr4('ScaffoldCtrl\\', getcwd() . '/app/src');

        $webController = new \ScaffoldCtrl\Http\Web\Controller\MyController();
        $this->assertInstanceOf('Pop\Dispatch\DispatchableInterface', $webController);

        $apiController = new \ScaffoldCtrl\Http\Api\Controller\MyController();
        $this->assertInstanceOf('Pop\Dispatch\DispatchableInterface', $apiController);

        $consoleController = new \ScaffoldCtrl\Console\Controller\MyController($this->makeApp());
        $this->assertInstanceOf('Pop\Dispatch\DispatchableInterface', $consoleController);
    }

    public function testScaffoldedDefaultHttpControllerIsInstantiable()
    {
        $this->scaffoldApp('web', 'ScaffoldDefaultCtrl');
        $application = new Model\Application();
        $application->createController('MyController', getcwd(), null, null, null);

        $autoloader = include __DIR__ . '/../../vendor/autoload.php';
        $autoloader->addPsr4('ScaffoldDefaultCtrl\\', getcwd() . '/app/src');

        $controller = new \ScaffoldDefaultCtrl\Http\Controller\MyController();
        $this->assertInstanceOf('Pop\Dispatch\DispatchableInterface', $controller);
    }

    public function testScaffoldedApiOptionsEventClassIsLoadable()
    {
        // Regression guard for the popphp Router 'controller' -> 'dispatchable'
        // rename: the scaffolded Options event class used to call the
        // now-removed Router::hasController()/getController() methods, which
        // only ever surfaced as a fatal "Call to undefined method" error once
        // the event actually fired - no other test loads this class at all.
        $this->scaffoldApp('api', 'ScaffoldOptsApi');

        $autoloader = include __DIR__ . '/../../vendor/autoload.php';
        $autoloader->addPsr4('ScaffoldOptsApi\\', getcwd() . '/app/src');

        $application = new \Pop\Application($autoloader, ['routes' => []]);
        \ScaffoldOptsApi\Http\Event\Options::send($application);

        $this->assertFalse($application->router()->hasDispatchable());
    }

    public function testScaffoldedWebApiOptionsEventClassIsLoadable()
    {
        $this->scaffoldApp('web-api', 'ScaffoldOptsWebApi');

        $autoloader = include __DIR__ . '/../../vendor/autoload.php';
        $autoloader->addPsr4('ScaffoldOptsWebApi\\', getcwd() . '/app/src');

        $application = new \Pop\Application($autoloader, ['routes' => []]);
        \ScaffoldOptsWebApi\Http\Api\Event\Options::send($application);

        $this->assertFalse($application->router()->hasDispatchable());
    }

    public function testCreateControllerWebMissingFolderThrows()
    {
        $this->scaffoldApp('cli');
        $application = new Model\Application();

        $this->expectException('Pop\Kettle\Exception');
        $application->createController('MyController', getcwd(), true, null, null);
    }

    public function testCreateControllerApiMissingFolderThrows()
    {
        $this->scaffoldApp('cli');
        $application = new Model\Application();

        $this->expectException('Pop\Kettle\Exception');
        $application->createController('MyController', getcwd(), null, true, null);
    }

    public function testCreateControllerDefaultMissingFolderThrows()
    {
        $this->scaffoldApp('cli');
        $application = new Model\Application();

        $this->expectException('Pop\Kettle\Exception');
        $application->createController('MyController', getcwd(), null, null, null);
    }

    public function testCreateModel()
    {
        $this->scaffoldApp('web');
        $application = new Model\Application();
        $result      = $application->createModel('User', getcwd());

        $this->assertSame('App\Model\User', $result);
        $this->assertFileExists(getcwd() . '/app/src/Model/User.php');
    }

    public function testCreateModelWithDataPluralizesTable()
    {
        $this->scaffoldApp('web');
        $application = new Model\Application();
        $application->createModel('User', getcwd(), true);

        $this->assertFileExists(getcwd() . '/app/src/Model/User.php');
        $this->assertFileExists(getcwd() . '/app/src/Table/Users.php');
    }

    public function testCreateModelWithDataDoesNotPluralizeWhenAlreadyEndingInS()
    {
        $this->scaffoldApp('web');
        $application = new Model\Application();
        $application->createModel('Status', getcwd(), true);

        $this->assertFileExists(getcwd() . '/app/src/Table/Status.php');
    }

    public function testCreateModelNestedPath()
    {
        $this->scaffoldApp('web');
        $application = new Model\Application();
        $result      = $application->createModel('Admin/Setting', getcwd());

        $this->assertSame('App\Model\Admin\Setting', $result);
        $this->assertFileExists(getcwd() . '/app/src/Model/Admin/Setting.php');
    }

    public function testCreateModelWithDataNestedPath()
    {
        // Note: the generated Table class name is derived from the model's basename
        // (after the nested path is already stripped off), so it lands flat in
        // app/src/Table/ rather than mirroring the model's Admin/ subfolder.
        $this->scaffoldApp('web');
        $application = new Model\Application();
        $application->createModel('Admin/Setting', getcwd(), true);

        $this->assertFileExists(getcwd() . '/app/src/Model/Admin/Setting.php');
        $this->assertFileExists(getcwd() . '/app/src/Table/Settings.php');
    }

    public function testInstallQuotesNameContainingSpaces()
    {
        $application = new Model\Application();
        $application->install('web', getcwd(), 'App', 'My App Name');

        $this->assertStringContainsString('APP_NAME="My App Name"', file_get_contents(getcwd() . '/.env'));
    }

    public function testInstallRegistersComposerAutoload()
    {
        $this->seedComposerJson();

        $application = new Model\Application();
        $application->install('web', getcwd(), 'App');

        $composer = json_decode(file_get_contents(getcwd() . '/composer.json'), true);
        $this->assertSame('app/src/', $composer['autoload']['psr-4']['App\\']);
    }

    public function testInstallPreservesExistingCustomComposerAutoloadEntry()
    {
        file_put_contents(getcwd() . '/composer.json', json_encode([
            'name'     => 'test/app',
            'autoload' => ['psr-4' => ['App\\' => 'custom/path/']],
        ], JSON_PRETTY_PRINT) . PHP_EOL);

        $application = new Model\Application();
        $application->install('web', getcwd(), 'App');

        $composer = json_decode(file_get_contents(getcwd() . '/composer.json'), true);
        $this->assertCount(1, $composer['autoload']['psr-4']);
        $this->assertSame('custom/path/', $composer['autoload']['psr-4']['App\\']);
    }

    public function testInstallSkipsAutoloadRegistrationWithoutComposerJson()
    {
        $application = new Model\Application();
        $application->install('web', getcwd(), 'App');

        $this->assertFileDoesNotExist(getcwd() . '/composer.json');
    }

    public function testCreateViewCreatesMissingBaseFolder()
    {
        mkdir(getcwd() . '/app/src', 0777, true);
        file_put_contents(getcwd() . '/app/src/Application.php', "<?php\n\nnamespace App;\n");

        $application = new Model\Application();
        $application->createView('test.phtml', getcwd());

        $this->assertFileExists(getcwd() . '/app/view/test.phtml');
    }

    public function testCreateView()
    {
        $this->scaffoldApp('web');
        $application = new Model\Application();
        $result      = $application->createView('test.phtml', getcwd());

        $this->assertSame('test.phtml', $result);
        $this->assertFileExists(getcwd() . '/app/view/test.phtml');
    }

    public function testCreateViewNestedPath()
    {
        $this->scaffoldApp('web');
        $application = new Model\Application();
        $application->createView('admin/dashboard.phtml', getcwd());

        $this->assertFileExists(getcwd() . '/app/view/admin/dashboard.phtml');
    }

    public function testGetNamespaceFromModuleFallback()
    {
        mkdir(getcwd() . '/app/src', 0777, true);
        file_put_contents(getcwd() . '/app/src/Module.php', "<?php\n\nnamespace LegacyApp;\n");

        $application = new Model\Application();

        $this->assertSame('LegacyApp', $application->getNamespace(getcwd()));
    }

    public function testGetNamespaceThrowsWhenUndetectable()
    {
        mkdir(getcwd() . '/app/src', 0777, true);

        $application = new Model\Application();

        $this->expectException('Pop\Kettle\Exception');
        $application->getNamespace(getcwd());
    }

    public function testResolveAppInstanceReturnsNullWhenAppClassMissing()
    {
        mkdir(getcwd() . '/app/src', 0777, true);
        file_put_contents(
            getcwd() . '/app/src/Application.php',
            '<?php' . PHP_EOL . 'namespace NoSuchAutoloadedApp;' . PHP_EOL . 'class Application {}' . PHP_EOL
        );

        $autoloader = include __DIR__ . '/../../vendor/autoload.php';
        $result     = (new Model\Application())->resolveAppInstance(getcwd(), $autoloader, []);

        $this->assertNull($result);
    }

    public function testResolveAppInstanceWithoutConsoleConfigDoesNotWarn()
    {
        $this->scaffoldApp('web', 'KettleWebOnlyApp');

        $autoloader = include __DIR__ . '/../../vendor/autoload.php';
        $autoloader->addPsr4('KettleWebOnlyApp\\', getcwd() . '/app/src');

        // A web-only scaffold has no app/config/app.console.php to include
        $this->assertFileDoesNotExist(getcwd() . '/app/config/app.console.php');

        $warnings = [];
        set_error_handler(function($errno, $errstr) use (&$warnings) {
            $warnings[] = $errstr;
            return true;
        }, E_WARNING | E_NOTICE);

        try {
            $result = (new Model\Application())->resolveAppInstance(
                getcwd(), $autoloader, include __DIR__ . '/../../config/routes.php'
            );
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $warnings);
        $this->assertInstanceOf('KettleWebOnlyApp\Application', $result);
    }

    public function testResolveAppInstanceThrowsWhenNothingScaffolded()
    {
        $this->expectException('Pop\Kettle\Exception');

        $autoloader = include __DIR__ . '/../../vendor/autoload.php';
        (new Model\Application())->resolveAppInstance(getcwd(), $autoloader, []);
    }

    public function testResolveAppInstanceReturnsConfiguredAppInstance()
    {
        $this->scaffoldApp('cli', 'KettleResolveApp');
        (new Model\Application())->createCommand('greet', getcwd(), app: true);

        $autoloader = include __DIR__ . '/../../vendor/autoload.php';
        $autoloader->addPsr4('KettleResolveApp\\', getcwd() . '/app/src');

        $baseRoutes = include __DIR__ . '/../../config/routes.php';
        $result     = (new Model\Application())->resolveAppInstance(getcwd(), $autoloader, $baseRoutes);

        $this->assertInstanceOf('KettleResolveApp\Application', $result);
        $this->assertArrayHasKey('greet', $result->config()['routes']);
    }

    public function testResolveInstallTypeApiOnly()
    {
        $this->assertSame('api', Model\Application::resolveInstallType(null, true, null));
    }

    public function testResolveInstallTypeWebApi()
    {
        $this->assertSame('web-api', Model\Application::resolveInstallType(true, true, null));
    }

    public function testResolveInstallTypeApiCli()
    {
        $this->assertSame('api-cli', Model\Application::resolveInstallType(null, true, true));
    }

    public function testResolveInstallTypeWebCli()
    {
        $this->assertSame('web-cli', Model\Application::resolveInstallType(true, null, true));
    }

    public function testResolveInstallTypeCliOnly()
    {
        $this->assertSame('cli', Model\Application::resolveInstallType(null, null, true));
    }

    public function testResolveInstallTypeAll()
    {
        $this->assertSame('web-api-cli', Model\Application::resolveInstallType(true, true, true));
    }

    public function testResolveInstallTypeDefaultsToWeb()
    {
        $this->assertSame('web', Model\Application::resolveInstallType(null, null, null));
    }

    public function testInstallScaffoldsAlpineFrontend()
    {
        $application = new Model\Application();
        $application->install('web', getcwd(), 'App', frontend: 'alpine');

        $this->assertFileExists(getcwd() . '/package.json');
        $this->assertStringContainsString('alpinejs', file_get_contents(getcwd() . '/package.json'));
        $this->assertFileExists(getcwd() . '/vite.config.js');
        $this->assertFileExists(getcwd() . '/app/assets/css/app.css');
        $this->assertFileExists(getcwd() . '/app/assets/js/app.js');
        $this->assertStringContainsString('x-data', file_get_contents(getcwd() . '/app/view/index.phtml'));
        $this->assertStringContainsString('node_modules/', file_get_contents(getcwd() . '/.gitignore'));
    }

    public function testInstallScaffoldsVueFrontend()
    {
        $application = new Model\Application();
        $application->install('web', getcwd(), 'App', frontend: 'vue');

        $this->assertStringContainsString('"vue"', file_get_contents(getcwd() . '/package.json'));
        $this->assertFileExists(getcwd() . '/app/assets/js/components/App.vue');
        $this->assertStringContainsString('id="app"', file_get_contents(getcwd() . '/app/view/index.phtml'));
    }

    public function testInstallScaffoldsReactFrontend()
    {
        $application = new Model\Application();
        $application->install('web', getcwd(), 'App', frontend: 'react');

        $this->assertStringContainsString('"react"', file_get_contents(getcwd() . '/package.json'));
        $this->assertFileExists(getcwd() . '/app/assets/js/app.jsx');
        $this->assertFileExists(getcwd() . '/app/assets/js/components/App.jsx');
        $this->assertStringContainsString('id="app"', file_get_contents(getcwd() . '/app/view/index.phtml'));
    }

    public function testInstallWithoutFrontendLeavesDefaultView()
    {
        $application = new Model\Application();
        $application->install('web', getcwd(), 'App');

        $this->assertFileDoesNotExist(getcwd() . '/package.json');
        $this->assertStringNotContainsString('x-data', file_get_contents(getcwd() . '/app/view/index.phtml'));
    }

}
