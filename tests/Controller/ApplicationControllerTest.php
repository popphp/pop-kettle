<?php

namespace Pop\Kettle\Test\Controller;

use Pop\Console\Console;
use Pop\Kettle;
use Pop\Kettle\Test\Fixtures\AppTestTrait;
use PHPUnit\Framework\TestCase;

class ApplicationControllerTest extends TestCase
{

    use AppTestTrait;

    protected string $originalPath = '';

    protected function setUp(): void
    {
        $this->enterSandbox();
        $this->originalPath = (string)getenv('PATH');
        putenv('PATH=' . sys_get_temp_dir() . '/pop-kettle-test-no-such-path-' . uniqid());
    }

    protected function tearDown(): void
    {
        putenv('PATH=' . $this->originalPath);
        $this->leaveSandbox();
    }

    private function writeEnv(array $overrides = []): void
    {
        $values = array_merge([
            'APP_NAME'                => 'Pop',
            'APP_ENV'                 => 'local',
            'APP_URL'                 => 'http://localhost',
            'MAINTENANCE_MODE'        => 'false',
            'MAINTENANCE_MODE_SECRET' => '',
            'DB_DATABASE'             => '',
            'DB_ADAPTER'              => '',
            'DB_USERNAME'             => '',
            'DB_PASSWORD'             => '',
            'DB_HOST'                 => '',
            'DB_TYPE'                 => '',
        ], $overrides);

        $lines = [];
        foreach ($values as $key => $value) {
            $lines[] = $key . '=' . $value;
        }

        file_put_contents(getcwd() . '/.env', implode(PHP_EOL, $lines) . PHP_EOL);
        \Dotenv\Dotenv::createMutable(getcwd())->safeLoad();
    }

    private function controller(?Console $console = null): Kettle\Controller\ApplicationController
    {
        return new Kettle\Controller\ApplicationController($this->makeApp(), $console ?? new Console(120, '    '));
    }

    public function testInitDefaultsToWebInstall()
    {
        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream('App', '', '', '', 'n', 'n'));

        ob_start();
        $this->controller($console)->init();
        $result = ob_get_clean();

        $this->assertStringContainsString('Done!', $result);
        $this->assertFileExists(getcwd() . '/app/src/Http/Controller/AbstractController.php');
        $this->assertStringContainsString('APP_ENV=local', file_get_contents(getcwd() . '/.env'));
        $this->assertStringContainsString('APP_URL=http://localhost', file_get_contents(getcwd() . '/.env'));
    }

    public function testInitDefaultsToWebInstallPromptsForUrl()
    {
        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream('App', '', '', 'http://example.com', 'n', 'n'));

        ob_start();
        $this->controller($console)->init();
        ob_end_clean();

        $this->assertStringContainsString('APP_URL=http://example.com', file_get_contents(getcwd() . '/.env'));
    }

    public function testInitPromptDefaultsAppNameToHumanReadableFullName()
    {
        $console = new Console(120, '    ');
        // Blank line for the app-name prompt accepts the shown default
        $console->setInputStream($this->createInputStream('nick-user-app', '', '', '', 'n', 'n'));

        ob_start();
        $result = $this->controller($console)->init();
        $output = ob_get_clean();

        $this->assertStringContainsString('[Nick User App]', $output);
        $this->assertStringContainsString('APP_NAME="Nick User App"', file_get_contents(getcwd() . '/.env'));
    }

    public function testInitWarnsWhenComposerNotFoundForAutoload()
    {
        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream('App', '', '', '', 'n', 'n'));

        ob_start();
        $this->controller($console)->init();
        $result = ob_get_clean();

        $this->assertStringContainsString('Composer not found', $result);
    }

    public function testInitRegistersComposerAutoloadWhenComposerAvailable()
    {
        $this->seedComposerJson();

        $fakeBinDir = getcwd() . '/fake-bin';
        mkdir($fakeBinDir);
        copy(__DIR__ . '/../tmp/fake-composer', $fakeBinDir . '/composer');
        chmod($fakeBinDir . '/composer', 0755);
        putenv('PATH=' . $fakeBinDir . ':' . $this->originalPath);

        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream('App', '', '', '', 'n', 'n'));

        ob_start();
        $this->controller($console)->init();
        $result = ob_get_clean();

        $this->assertStringContainsString("Registering application autoloader for 'App'", $result);

        $composer = json_decode(file_get_contents(getcwd() . '/composer.json'), true);
        $this->assertSame('app/src/', $composer['autoload']['psr-4']['App\\']);
    }

    public function testInitWithCliFlag()
    {
        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream('App', '', '3', 'y', 'n'));

        ob_start();
        $this->controller($console)->init();
        ob_end_clean();

        $this->assertFileExists(getcwd() . '/app/src/Console/Controller/AbstractController.php');
        $this->assertFileExists(getcwd() . '/app/src/Console/Command/Kettle/.empty');
        $this->assertFileExists(getcwd() . '/script/app');
    }

    public function testInitWithCliFlagWithoutStandaloneAppRemovesConsoleController()
    {
        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream('App', '', '3', 'n', 'n'));

        ob_start();
        $this->controller($console)->init();
        ob_end_clean();

        $this->assertFileDoesNotExist(getcwd() . '/app/src/Console/Controller');
        $this->assertFileExists(getcwd() . '/app/src/Console/Command/Kettle/.empty');
    }

    public function testInitWithDatabaseConfiguration()
    {
        $sqliteIndex = $this->sqliteAdapterIndex();

        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream(
            'App', '', '', '', 'y', 'n', (string)$sqliteIndex, 'testdb'
        ));

        ob_start();
        $this->controller($console)->init();
        ob_end_clean();

        $this->assertFileExists(getcwd() . '/database/testdb.sqlite');
        $this->assertStringContainsString('DB_ADAPTER=sqlite', file_get_contents(getcwd() . '/.env'));
    }

    public function testInitQuotesNameContainingSpaces()
    {
        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream('', 'My App', '', '', 'n', 'n'));

        ob_start();
        $this->controller($console)->init();
        ob_end_clean();

        $this->assertStringContainsString('APP_NAME="My App"', file_get_contents(getcwd() . '/.env'));
    }

    public function testInitDefaultsNamespaceWhenEmpty()
    {
        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream('', '', '', '', 'n', 'n'));

        ob_start();
        $this->controller($console)->init();
        ob_end_clean();

        // Falls back to the 'MyApp' namespace, which install() uses to derive class references
        $this->assertStringContainsString('App', file_get_contents(getcwd() . '/app/src/Http/Controller/AbstractController.php'));
    }

    public function testInitSkipsFrontendInstallWhenAnsweredNo()
    {
        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream('App', '', '', '', 'n', 'n'));

        ob_start();
        $this->controller($console)->init();
        ob_end_clean();

        $this->assertFileDoesNotExist(getcwd() . '/package.json');
    }

    public function testInitInstallsAlpineFrontend()
    {
        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream('App', '', '', '', 'n', 'y', '1'));

        ob_start();
        $this->controller($console)->init();
        ob_end_clean();

        $this->assertFileExists(getcwd() . '/package.json');
        $this->assertStringContainsString('alpinejs', file_get_contents(getcwd() . '/package.json'));
    }

    public function testInitInstallsVueFrontend()
    {
        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream('App', '', '', '', 'n', 'y', '2'));

        ob_start();
        $this->controller($console)->init();
        ob_end_clean();

        $this->assertStringContainsString('"vue"', file_get_contents(getcwd() . '/package.json'));
    }

    public function testInitInstallsReactFrontend()
    {
        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream('App', '', '', '', 'n', 'y', '3'));

        ob_start();
        $this->controller($console)->init();
        ob_end_clean();

        $this->assertStringContainsString('"react"', file_get_contents(getcwd() . '/package.json'));
    }

    public function testInitWarnsWhenNpmNotFoundForFrontend()
    {
        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream('App', '', '', '', 'n', 'y', '1'));

        ob_start();
        $this->controller($console)->init();
        $result = ob_get_clean();

        $this->assertStringContainsString('Node/npm not found', $result);
        $this->assertFileDoesNotExist(getcwd() . '/node_modules');
    }

    public function testInitInstallsFrontendDependenciesWhenNpmAvailable()
    {
        $fakeBinDir = getcwd() . '/fake-bin';
        mkdir($fakeBinDir);
        copy(__DIR__ . '/../tmp/fake-npm', $fakeBinDir . '/npm');
        chmod($fakeBinDir . '/npm', 0755);
        copy(__DIR__ . '/../tmp/fake-composer', $fakeBinDir . '/composer');
        chmod($fakeBinDir . '/composer', 0755);
        putenv('FAKE_NPM_LOG=' . getcwd() . '/npm.log');
        putenv('PATH=' . $fakeBinDir . ':' . $this->originalPath);

        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream('App', '', '', '', 'n', 'y', '1'));

        ob_start();
        $this->controller($console)->init();
        $result = ob_get_clean();

        $this->assertStringContainsString('Installing front-end dependencies', $result);
        $this->assertStringContainsString('Building front-end assets', $result);

        $log = file_get_contents(getcwd() . '/npm.log');
        $this->assertStringContainsString('install', $log);
        $this->assertStringContainsString('run build', $log);
        $this->assertTrue(strpos($log, 'install') < strpos($log, 'run build'), 'install should run before build');

        putenv('FAKE_NPM_LOG');
    }

    public function testEnvLocal()
    {
        $this->writeEnv(['APP_ENV' => 'local']);

        ob_start();
        $this->controller()->env();
        $result = ob_get_clean();

        $this->assertStringContainsString('Application in Local', $result);
    }

    public function testEnvDev()
    {
        $this->writeEnv(['APP_ENV' => 'dev']);

        ob_start();
        $this->controller()->env();
        $result = ob_get_clean();

        $this->assertStringContainsString('Application in Dev', $result);
    }

    public function testEnvTesting()
    {
        $this->writeEnv(['APP_ENV' => 'testing']);

        ob_start();
        $this->controller()->env();
        $result = ob_get_clean();

        $this->assertStringContainsString('Application in Testing', $result);
    }

    public function testEnvStaging()
    {
        $this->writeEnv(['APP_ENV' => 'staging']);

        ob_start();
        $this->controller()->env();
        $result = ob_get_clean();

        $this->assertStringContainsString('Application in Staging', $result);
    }

    public function testEnvProd()
    {
        $this->writeEnv(['APP_ENV' => 'production']);

        ob_start();
        $this->controller()->env();
        $result = ob_get_clean();

        $this->assertStringContainsString('Application in Production', $result);
    }

    public function testEnvSetChangesEnvironment()
    {
        $this->writeEnv(['APP_ENV' => 'local']);

        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream('4'));

        ob_start();
        $this->controller($console)->env(['set' => true]);
        $result = ob_get_clean();

        $this->assertStringContainsString('Application in Staging', $result);
        $this->assertStringContainsString('APP_ENV=staging', file_get_contents(getcwd() . '/.env'));
    }

    public function testEnvSetReprompsOnInvalidSelection()
    {
        $this->writeEnv(['APP_ENV' => 'local']);

        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream('9', '2'));

        ob_start();
        $this->controller($console)->env(['set' => true]);
        ob_end_clean();

        $this->assertStringContainsString('APP_ENV=dev', file_get_contents(getcwd() . '/.env'));
    }

    public function testEnvSetWithNoEnvFile()
    {
        ob_start();
        $this->controller()->env(['set' => true]);
        $result = ob_get_clean();

        $this->assertStringContainsString('No .env file found.', $result);
    }

    public function testStatus()
    {
        $this->writeEnv(['MAINTENANCE_MODE' => 'false']);

        ob_start();
        $this->controller()->status();
        $result = ob_get_clean();

        $this->assertStringContainsString('Application is Live', $result);
    }

    public function testDownWithProvidedSecret()
    {
        $this->writeEnv(['MAINTENANCE_MODE' => 'false']);

        ob_start();
        $this->controller()->down(['secret' => '123456']);
        $result = ob_get_clean();

        $this->assertStringContainsString('Application has been switched to maintenance mode.', $result);
        $this->assertStringContainsString('The secret is', $result);
        $this->assertStringContainsString('123456', $result);
        $this->assertStringContainsString('MAINTENANCE_MODE=true', file_get_contents(getcwd() . '/.env'));
    }

    public function testDownAutoGeneratesSecret()
    {
        $this->writeEnv(['MAINTENANCE_MODE' => 'false']);

        ob_start();
        $this->controller()->down(['secret' => null]);
        $result = ob_get_clean();

        $this->assertStringContainsString('The secret is', $result);
        $this->assertMatchesRegularExpression('/[0-9a-f]{40}/', $result);
    }

    public function testDownWithoutSecretOption()
    {
        $this->writeEnv(['MAINTENANCE_MODE' => 'false']);

        ob_start();
        $this->controller()->down([]);
        $result = ob_get_clean();

        $this->assertStringContainsString('Application has been switched to maintenance mode.', $result);
        $this->assertStringNotContainsString('The secret is', $result);
    }

    public function testDownReusesExistingSecretWhenTransitioningToDown()
    {
        $this->writeEnv(['MAINTENANCE_MODE' => 'false', 'MAINTENANCE_MODE_SECRET' => 'leftoversecret']);

        ob_start();
        $this->controller()->down([]);
        $result = ob_get_clean();

        $this->assertStringContainsString('Application has been switched to maintenance mode.', $result);
        $this->assertStringContainsString('leftoversecret', $result);
    }

    public function testDownOverwritesExistingSecretWhenAlreadyDown()
    {
        $this->writeEnv(['MAINTENANCE_MODE' => 'true', 'MAINTENANCE_MODE_SECRET' => 'oldsecret']);

        ob_start();
        $this->controller()->down(['secret' => 'newsecret']);
        $result = ob_get_clean();

        $this->assertStringContainsString('Application is currently in maintenance mode. No action to take.', $result);
        $this->assertStringContainsString('newsecret', $result);
        $this->assertStringContainsString('MAINTENANCE_MODE_SECRET=newsecret', file_get_contents(getcwd() . '/.env'));
    }

    public function testDownWhenAlreadyDownReusesExistingSecret()
    {
        $this->writeEnv(['MAINTENANCE_MODE' => 'true', 'MAINTENANCE_MODE_SECRET' => 'existingsecret']);

        ob_start();
        $this->controller()->down([]);
        $result = ob_get_clean();

        $this->assertStringContainsString('Application is currently in maintenance mode. No action to take.', $result);
        $this->assertStringContainsString('The secret is', $result);
        $this->assertStringContainsString('existingsecret', $result);
    }

    public function testDownWhenAlreadyDownWithNoSecret()
    {
        $this->writeEnv(['MAINTENANCE_MODE' => 'true', 'MAINTENANCE_MODE_SECRET' => '']);

        ob_start();
        $this->controller()->down([]);
        $result = ob_get_clean();

        $this->assertStringContainsString('Application is currently in maintenance mode. No action to take.', $result);
        $this->assertStringNotContainsString('The secret is', $result);
    }

    public function testDownNoEnvFile()
    {
        ob_start();
        $this->controller()->down([]);
        $result = ob_get_clean();

        $this->assertStringContainsString('No .env file found.', $result);
    }

    public function testUpFromDown()
    {
        $this->writeEnv(['MAINTENANCE_MODE' => 'true']);

        ob_start();
        $this->controller()->up();
        $result = ob_get_clean();

        $this->assertStringContainsString('Application has been made live.', $result);
        $this->assertStringContainsString('MAINTENANCE_MODE=false', file_get_contents(getcwd() . '/.env'));
    }

    public function testUpWhenAlreadyUp()
    {
        $this->writeEnv(['MAINTENANCE_MODE' => 'false']);

        ob_start();
        $this->controller()->up();
        $result = ob_get_clean();

        $this->assertStringContainsString('Application is currently live. No action to take.', $result);
    }

    public function testUpNoEnvFile()
    {
        unset($_ENV['MAINTENANCE_MODE'], $_ENV['MAINTENANCE_MODE_SECRET']);

        ob_start();
        $this->controller()->up();
        $result = ob_get_clean();

        $this->assertStringContainsString('No .env file found.', $result);
    }

    public function testCreateControllerDefault()
    {
        $this->scaffoldApp('web');

        ob_start();
        $this->controller()->createController('TestController');
        $result = ob_get_clean();

        $this->assertStringContainsString("Controller class 'App\Http\Controller\TestController' created.", $result);
        $this->assertFileExists(getcwd() . '/app/src/Http/Controller/TestController.php');
    }

    public function testCreateControllerCli()
    {
        $this->scaffoldApp('cli', 'App', true);

        ob_start();
        $this->controller()->createController('TestController', ['cli' => true]);
        $result = ob_get_clean();

        $this->assertStringContainsString("Controller class 'App\Console\Controller\TestController' created.", $result);
        $this->assertFileExists(getcwd() . '/app/src/Console/Controller/TestController.php');
    }

    public function testCreateControllerMissingFolderThrows()
    {
        $this->scaffoldApp('web');

        $this->expectException('Pop\Kettle\Exception');
        $this->controller()->createController('TestController', ['cli' => true]);
    }

    public function testCreateModel()
    {
        $this->scaffoldApp('web');

        ob_start();
        $this->controller()->createModel('TestModel');
        $result = ob_get_clean();

        $this->assertStringContainsString("Model class 'App\Model\TestModel' created.", $result);
        $this->assertFileExists(getcwd() . '/app/src/Model/TestModel.php');
    }

    public function testCreateModelWithData()
    {
        $this->scaffoldApp('web');

        ob_start();
        $this->controller()->createModel('TestModel', ['data' => true]);
        $result = ob_get_clean();

        $this->assertStringContainsString("Model class 'App\Model\TestModel' created.", $result);
        $this->assertFileExists(getcwd() . '/app/src/Model/TestModel.php');
        $this->assertFileExists(getcwd() . '/app/src/Table/TestModels.php');
    }

    public function testCreateView()
    {
        $this->scaffoldApp('web');

        ob_start();
        $this->controller()->createView('test.phtml');
        $result = ob_get_clean();

        $this->assertStringContainsString("View file 'test.phtml' created.", $result);
        $this->assertFileExists(getcwd() . '/app/view/test.phtml');
    }

    public function testCreateCommand()
    {
        $this->scaffoldApp('cli');

        ob_start();
        $this->controller()->createCommand('send-email');
        $result = ob_get_clean();

        $this->assertStringContainsString("Command 'send-email' created.", $result);
        $this->assertFileExists(getcwd() . '/app/src/Console/Command/Kettle/SendEmail.php');
    }

    public function testCreateCommandMissingFolderThrows()
    {
        $this->scaffoldApp('web');

        $this->expectException('Pop\Kettle\Exception');
        $this->controller()->createCommand('send-email');
    }

    public function testCreateCommandWithAppFlag()
    {
        $this->scaffoldApp('cli');

        ob_start();
        $this->controller()->createCommand('send-email', ['app' => true]);
        $result = ob_get_clean();

        $this->assertStringContainsString("Command 'send-email' created.", $result);
        $this->assertFileExists(getcwd() . '/app/src/Console/Command/SendEmail.php');
        $this->assertFileDoesNotExist(getcwd() . '/app/src/Console/Command/Kettle/SendEmail.php');
    }

}
