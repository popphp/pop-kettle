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

    public function testInitFull()
    {
        $application = new Model\Application();
        $application->init(getcwd(), 'App', false);

        $this->assertFileExists(getcwd() . '/app/src/Http/Controller/AbstractController.php');
        $this->assertFileExists(getcwd() . '/app/src/Http/Controller/IndexController.php');
        $this->assertFileExists(getcwd() . '/app/src/Http/Event');
        $this->assertFileDoesNotExist(getcwd() . '/app/src/Http/Web');
        $this->assertFileDoesNotExist(getcwd() . '/app/src/Http/Api');
    }

    public function testInitCliOnly()
    {
        $application = new Model\Application();
        $application->init(getcwd(), 'App', true, cliApp: true);

        $this->assertFileExists(getcwd() . '/app/src/Console/Controller/AbstractController.php');
        $this->assertFileExists(getcwd() . '/app/src/Console/Command/Kettle/.empty');
        $this->assertFileDoesNotExist(getcwd() . '/app/src/Http');
        $this->assertFileDoesNotExist(getcwd() . '/public');
    }

    public function testInitCliOnlyWithoutStandaloneAppRemovesConsoleController()
    {
        $application = new Model\Application();
        $application->init(getcwd(), 'App', true);

        $this->assertFileDoesNotExist(getcwd() . '/app/src/Console/Controller');
        $this->assertFileExists(getcwd() . '/app/src/Console/Command/Kettle/.empty');
    }

    public function testInitFullWithCliApp()
    {
        $application = new Model\Application();
        $application->init(getcwd(), 'App', false, cliApp: true);

        $this->assertFileExists(getcwd() . '/app/src/Console/Controller/AbstractController.php');
        $this->assertFileExists(getcwd() . '/app/src/Http/Controller/AbstractController.php');
        $this->assertFileExists(getcwd() . '/app/src/Http/Controller/IndexController.php');
        $this->assertFileDoesNotExist(getcwd() . '/app/src/Http/Web');
        $this->assertFileDoesNotExist(getcwd() . '/app/src/Http/Api');
    }

    public function testInitDefaultsToFull()
    {
        $application = new Model\Application();
        $application->init(getcwd(), 'App');

        $this->assertFileExists(getcwd() . '/app/src/Http/Controller/AbstractController.php');
        $this->assertFileExists(getcwd() . '/app/src/Http/Controller/IndexController.php');
        $this->assertFileDoesNotExist(getcwd() . '/app/src/Http/Web');
        $this->assertFileDoesNotExist(getcwd() . '/app/src/Http/Api');
    }

    public function testCreateCommand()
    {
        $this->scaffoldApp(true);
        $application = new Model\Application();
        $application->createCommand('send-email', getcwd());

        $this->assertFileExists(getcwd() . '/app/src/Console/Command/Kettle/SendEmail.php');
        $this->assertStringContainsString(
            "'send-email'", file_get_contents(getcwd() . '/app/src/Console/Command/Kettle/SendEmail.php')
        );
    }

    public function testCreateCommandWithNamespacedSignature()
    {
        $this->scaffoldApp(true);
        $application = new Model\Application();
        $application->createCommand('email:send', getcwd());

        $this->assertFileExists(getcwd() . '/app/src/Console/Command/Kettle/Send.php');
        $this->assertStringContainsString(
            "'email:send'", file_get_contents(getcwd() . '/app/src/Console/Command/Kettle/Send.php')
        );
    }

    public function testCreateCommandForStandaloneApp()
    {
        $this->scaffoldApp(true);
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
        $this->scaffoldApp(true, 'ScaffoldCmdKettle');
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
        $this->scaffoldApp(true, 'ScaffoldCmdApp');
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
        $this->scaffoldApp(true);
        $application = new Model\Application();
        $application->createCommand('send-email', getcwd());

        $original = file_get_contents(getcwd() . '/app/src/Console/Command/Kettle/SendEmail.php');

        // Creating the same command again should not touch the existing file
        $application->createCommand('send-email', getcwd());

        $this->assertSame($original, file_get_contents(getcwd() . '/app/src/Console/Command/Kettle/SendEmail.php'));
    }

    public function testCreateCommandMissingFolderThrows()
    {
        // Every install now ships Console/Command/Kettle by default (there's
        // no more flavor that omits it), so the only way to reproduce a
        // missing command folder is to construct a namespace-only app by
        // hand, the same way testCreateViewCreatesMissingBaseFolder does.
        mkdir(getcwd() . '/app/src', 0777, true);
        file_put_contents(getcwd() . '/app/src/Application.php', "<?php\n\nnamespace App;\n");

        $application = new Model\Application();

        $this->expectException('Pop\Kettle\Exception');
        $application->createCommand('send-email', getcwd());
    }

    public function testCreateControllerCli()
    {
        $this->scaffoldApp(true, 'App', true);
        $application = new Model\Application();
        $result      = $application->createController('MyController', getcwd(), true);

        $this->assertSame('App\Console\Controller\MyController', $result);
        $this->assertFileExists(getcwd() . '/app/src/Console/Controller/MyController.php');
    }

    public function testCreateControllerDefault()
    {
        $this->scaffoldApp();
        $application = new Model\Application();
        $result      = $application->createController('MyController', getcwd());

        $this->assertSame('App\Http\Controller\MyController', $result);
        $this->assertFileExists(getcwd() . '/app/src/Http/Controller/MyController.php');
    }

    public function testCreateControllerNestedPath()
    {
        $this->scaffoldApp();
        $application = new Model\Application();
        $result      = $application->createController('Admin/Users', getcwd());

        $this->assertSame('App\Http\Controller\Admin\Users', $result);
        $this->assertFileExists(getcwd() . '/app/src/Http/Controller/Admin/Users.php');
    }

    public function testCreateControllerCliNestedPath()
    {
        $this->scaffoldApp(true, 'App', true);
        $application = new Model\Application();
        $result      = $application->createController('Admin/Tools', getcwd(), true);

        $this->assertSame('App\Console\Controller\Admin\Tools', $result);
        $this->assertFileExists(getcwd() . '/app/src/Console/Controller/Admin/Tools.php');
    }

    public function testCreateControllerCliDeniedWithoutStandaloneApp()
    {
        // Scaffolded without opting into a stand-alone ./script app -
        // Console/Controller is removed in that case (you're expected to
        // piggyback commands through Kettle instead), so create:ctrl --cli
        // should refuse with a specific message rather than the generic
        // "folder not created" error.
        $this->scaffoldApp(true);
        $application = new Model\Application();

        $this->expectException('Pop\Kettle\Exception');
        $this->expectExceptionMessage('This application was not initialized with a stand-alone console application.');
        $application->createController('MyController', getcwd(), true);
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
        $this->scaffoldApp(false, 'ScaffoldCtrl', true);
        $application = new Model\Application();
        $application->createController('MyController', getcwd());
        $application->createController('MyController', getcwd(), true);

        $autoloader = include __DIR__ . '/../../vendor/autoload.php';
        $autoloader->addPsr4('ScaffoldCtrl\\', getcwd() . '/app/src');

        $httpController = new \ScaffoldCtrl\Http\Controller\MyController();
        $this->assertInstanceOf('Pop\Dispatch\DispatchableInterface', $httpController);

        $consoleController = new \ScaffoldCtrl\Console\Controller\MyController($this->makeApp());
        $this->assertInstanceOf('Pop\Dispatch\DispatchableInterface', $consoleController);
    }

    public function testScaffoldedIndexControllerIsInstantiable()
    {
        // Regression guard: the merged Http\Controller\AbstractController gained
        // error()/maintenance() with (int $code, ?string $message) signatures,
        // and IndexController must stay compatible with them. PHP treats a
        // narrower override signature as a compile-time fatal (not a runtime
        // one), so merely loading and instantiating the class - not calling
        // error()/maintenance() - is enough to reproduce that class of bug.
        $this->scaffoldApp(false, 'ScaffoldIdx');

        $autoloader = include __DIR__ . '/../../vendor/autoload.php';
        $autoloader->addPsr4('ScaffoldIdx\\', getcwd() . '/app/src');

        $controller = new \ScaffoldIdx\Http\Controller\IndexController();
        $this->assertInstanceOf('Pop\Dispatch\DispatchableInterface', $controller);
    }

    public function testScaffoldedIndexControllerNegotiatesHtmlAccept()
    {
        // Regression guard: IndexController::index() used to call
        // $this->request->getHeader('Accept')->getValue(), but the current
        // pop-http Request::getHeader() returns a plain array of string
        // values, not an object with getValue() - so this fataled with
        // "Call to a member function getValue() on array" on every request.
        // Only index() is exercised here (not error()'s non-HTML branch),
        // since AbstractController::error() ends by calling
        // Response::sendAndExit(), which calls PHP's exit() and would kill
        // the PHPUnit process.
        $this->scaffoldApp(false, 'ScaffoldIdxNegotiate');

        $autoloader = include __DIR__ . '/../../vendor/autoload.php';
        $autoloader->addPsr4('ScaffoldIdxNegotiate\\', getcwd() . '/app/src');

        $htmlRequest = new \Pop\Http\Server\Request(new \Pop\Http\Uri(), populateFromGlobals: false);
        $htmlRequest->addHeader('Accept', 'text/html');

        $htmlController = new \ScaffoldIdxNegotiate\Http\Controller\IndexController(
            null, $htmlRequest, new \Pop\Http\Server\Response()
        );

        ob_start();
        $htmlController->index();
        $htmlOutput = ob_get_clean();

        $this->assertStringContainsString('Welcome', $htmlOutput);

        $jsonRequest = new \Pop\Http\Server\Request(new \Pop\Http\Uri(), populateFromGlobals: false);
        $jsonRequest->addHeader('Accept', 'application/json');

        // Mirrors the 'http_options_headers' config app.http.php defines
        // (including the Content-Type: application/json header sendJson()
        // relies on to decide whether to json_encode the body).
        $jsonApplication = new \Pop\Application($autoloader, [
            'http_options_headers' => ['Content-Type' => 'application/json']
        ]);

        $jsonController = new \ScaffoldIdxNegotiate\Http\Controller\IndexController(
            $jsonApplication, $jsonRequest, new \Pop\Http\Server\Response()
        );

        ob_start();
        $jsonController->index();
        $jsonOutput = ob_get_clean();

        $this->assertStringContainsString('Index page', $jsonOutput);
    }

    public function testScaffoldedOptionsEventClassIsLoadable()
    {
        // Regression guard for the popphp Router 'controller' -> 'dispatchable'
        // rename: the scaffolded Options event class used to call the
        // now-removed Router::hasController()/getController() methods, which
        // only ever surfaced as a fatal "Call to undefined method" error once
        // the event actually fired - no other test loads this class at all.
        $this->scaffoldApp(false, 'ScaffoldOptsEvent');

        $autoloader = include __DIR__ . '/../../vendor/autoload.php';
        $autoloader->addPsr4('ScaffoldOptsEvent\\', getcwd() . '/app/src');

        $application = new \Pop\Application($autoloader, ['routes' => []]);
        \ScaffoldOptsEvent\Http\Event\Options::send($application);

        $this->assertFalse($application->router()->hasDispatchable());
    }

    public function testCreateControllerDefaultMissingFolderThrows()
    {
        $this->scaffoldApp(true);
        $application = new Model\Application();

        $this->expectException('Pop\Kettle\Exception');
        $application->createController('MyController', getcwd());
    }

    public function testCreateModel()
    {
        $this->scaffoldApp();
        $application = new Model\Application();
        $result      = $application->createModel('User', getcwd());

        $this->assertSame('App\Model\User', $result);
        $this->assertFileExists(getcwd() . '/app/src/Model/User.php');
    }

    public function testCreateModelWithDataPluralizesTable()
    {
        $this->scaffoldApp();
        $application = new Model\Application();
        $application->createModel('User', getcwd(), true);

        $this->assertFileExists(getcwd() . '/app/src/Model/User.php');
        $this->assertFileExists(getcwd() . '/app/src/Table/Users.php');
    }

    public function testCreateModelWithDataDoesNotPluralizeWhenAlreadyEndingInS()
    {
        $this->scaffoldApp();
        $application = new Model\Application();
        $application->createModel('Status', getcwd(), true);

        $this->assertFileExists(getcwd() . '/app/src/Table/Status.php');
    }

    public function testCreateModelNestedPath()
    {
        $this->scaffoldApp();
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
        $this->scaffoldApp();
        $application = new Model\Application();
        $application->createModel('Admin/Setting', getcwd(), true);

        $this->assertFileExists(getcwd() . '/app/src/Model/Admin/Setting.php');
        $this->assertFileExists(getcwd() . '/app/src/Table/Settings.php');
    }

    public function testInstallQuotesNameContainingSpaces()
    {
        $application = new Model\Application();
        $application->install(false, getcwd(), 'App', 'My App Name');

        $this->assertStringContainsString('APP_NAME="My App Name"', file_get_contents(getcwd() . '/.env'));
    }

    public function testInstallRegistersComposerAutoload()
    {
        $this->seedComposerJson();

        $application = new Model\Application();
        $application->install(false, getcwd(), 'App');

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
        $application->install(false, getcwd(), 'App');

        $composer = json_decode(file_get_contents(getcwd() . '/composer.json'), true);
        $this->assertCount(1, $composer['autoload']['psr-4']);
        $this->assertSame('custom/path/', $composer['autoload']['psr-4']['App\\']);
    }

    public function testInstallSkipsAutoloadRegistrationWithoutComposerJson()
    {
        $application = new Model\Application();
        $application->install(false, getcwd(), 'App');

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
        $this->scaffoldApp();
        $application = new Model\Application();
        $result      = $application->createView('test.phtml', getcwd());

        $this->assertSame('test.phtml', $result);
        $this->assertFileExists(getcwd() . '/app/view/test.phtml');
    }

    public function testCreateViewNestedPath()
    {
        $this->scaffoldApp();
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
        // Every install now ships app.console.php and Console/Command by
        // default (there's no more flavor that omits them), so a scaffold
        // lacking console config has to be constructed by stripping them out
        // afterward - simulating an app whose CLI scaffolding was removed.
        $this->scaffoldApp(false, 'KettleWebOnlyApp');
        unlink(getcwd() . '/app/config/app.console.php');
        (new \Pop\Dir\Dir(getcwd() . '/app/src/Console/Command'))->emptyDir(true);

        $autoloader = include __DIR__ . '/../../vendor/autoload.php';
        $autoloader->addPsr4('KettleWebOnlyApp\\', getcwd() . '/app/src');

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
        $this->scaffoldApp(true, 'KettleResolveApp');
        (new Model\Application())->createCommand('greet', getcwd(), app: true);

        $autoloader = include __DIR__ . '/../../vendor/autoload.php';
        $autoloader->addPsr4('KettleResolveApp\\', getcwd() . '/app/src');

        $baseRoutes = include __DIR__ . '/../../config/routes.php';
        $result     = (new Model\Application())->resolveAppInstance(getcwd(), $autoloader, $baseRoutes);

        $this->assertInstanceOf('KettleResolveApp\Application', $result);
        $this->assertArrayHasKey('greet', $result->config()['routes']);
    }

    public function testInstallScaffoldsAlpineFrontend()
    {
        $application = new Model\Application();
        $application->install(false, getcwd(), 'App', frontend: 'alpine');

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
        $application->install(false, getcwd(), 'App', frontend: 'vue');

        $this->assertStringContainsString('"vue"', file_get_contents(getcwd() . '/package.json'));
        $this->assertFileExists(getcwd() . '/app/assets/js/components/App.vue');
        $this->assertStringContainsString('id="app"', file_get_contents(getcwd() . '/app/view/index.phtml'));
    }

    public function testInstallScaffoldsReactFrontend()
    {
        $application = new Model\Application();
        $application->install(false, getcwd(), 'App', frontend: 'react');

        $this->assertStringContainsString('"react"', file_get_contents(getcwd() . '/package.json'));
        $this->assertFileExists(getcwd() . '/app/assets/js/app.jsx');
        $this->assertFileExists(getcwd() . '/app/assets/js/components/App.jsx');
        $this->assertStringContainsString('id="app"', file_get_contents(getcwd() . '/app/view/index.phtml'));
    }

    public function testInstallWithoutFrontendLeavesDefaultView()
    {
        $application = new Model\Application();
        $application->install(false, getcwd(), 'App');

        $this->assertFileDoesNotExist(getcwd() . '/package.json');
        $this->assertStringNotContainsString('x-data', file_get_contents(getcwd() . '/app/view/index.phtml'));
    }

    public function testParseNamespaceSingleWord()
    {
        $parsed = Model\Application::parseNamespace('App');

        $this->assertSame('App', $parsed['namespace']);
        $this->assertSame('app', $parsed['slug']);
        $this->assertSame('App', $parsed['fullName']);
    }

    public function testParseNamespaceCamelCase()
    {
        $parsed = Model\Application::parseNamespace('NicksApp');

        $this->assertSame('NicksApp', $parsed['namespace']);
        $this->assertSame('nicks-app', $parsed['slug']);
        $this->assertSame('Nicks App', $parsed['fullName']);
    }

    public function testParseNamespaceKebabInput()
    {
        $parsed = Model\Application::parseNamespace('nick-user-app');

        $this->assertSame('NickUserApp', $parsed['namespace']);
        $this->assertSame('nick-user-app', $parsed['slug']);
        $this->assertSame('Nick User App', $parsed['fullName']);
    }

    public function testParseNamespaceMultiSegmentBackslash()
    {
        $parsed = Model\Application::parseNamespace('Nick\\Users\\App');

        $this->assertSame('Nick\\Users\\App', $parsed['namespace']);
        $this->assertSame('nick-users-app', $parsed['slug']);
        $this->assertSame('Nick Users App', $parsed['fullName']);
    }

    public function testParseNamespaceDoubledBackslash()
    {
        $parsed = Model\Application::parseNamespace('Nick\\\\Users\\\\App');

        $this->assertSame('Nick\\Users\\App', $parsed['namespace']);
        $this->assertSame('nick-users-app', $parsed['slug']);
        $this->assertSame('Nick Users App', $parsed['fullName']);
    }

    public function testParseNamespaceStripsPathTraversalSegments()
    {
        $parsed = Model\Application::parseNamespace('../../etc/App');

        $this->assertSame('Etc\\App', $parsed['namespace']);
        $this->assertSame('etc-app', $parsed['slug']);
        $this->assertStringNotContainsString('/', $parsed['slug']);
        $this->assertStringNotContainsString('.', $parsed['slug']);
    }

    public function testParseNamespaceLeadingDigitSegmentIsPrefixed()
    {
        $parsed = Model\Application::parseNamespace('123App');

        $this->assertSame('_123App', $parsed['namespace']);
    }

    public function testParseNamespaceThrowsOnNoValidCharacters()
    {
        $this->expectException('Pop\Kettle\Exception');
        Model\Application::parseNamespace('---');
    }

    public function testInstallWritesSlugBasedNameConstAndHumanFullNameConst()
    {
        $application = new Model\Application();
        $application->install(false, getcwd(), 'nick-user-app');

        $contents = file_get_contents(getcwd() . '/app/src/Application.php');
        $this->assertStringContainsString("namespace NickUserApp;", $contents);
        $this->assertStringContainsString("const string NAME = 'nick-user-app';", $contents);
        $this->assertStringContainsString("const string FULL_NAME = 'Nick User App';", $contents);
    }

    public function testInstallScriptFileNameUsesSlug()
    {
        $application = new Model\Application();
        $application->install(true, getcwd(), 'nick-user-app', cliApp: true);

        $this->assertFileExists(getcwd() . '/script/nick-user-app');
    }

}
