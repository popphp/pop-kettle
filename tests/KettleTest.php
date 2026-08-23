<?php

namespace Pop\Kettle\Test;

use Pop\Kettle;
use Pop\Kettle\Test\Fixtures\AppTestTrait;
use PHPUnit\Framework\TestCase;

class KettleTest extends TestCase
{

    use AppTestTrait;

    public function testLoad()
    {
        $app = $this->makeApp();

        ob_start();
        $result = $app->load();
        $output = ob_get_clean();

        $this->assertInstanceOf('Pop\Kettle\Application', $result);
        $this->assertInstanceOf('Pop\Console\Console', $app->getConsole());
        $this->assertStringContainsString('Pop Kettle', $output);
    }

    public function testLoadInitializesDbWhenConfigPresent()
    {
        $this->enterSandbox();

        mkdir(getcwd() . '/app/config', 0777, true);
        mkdir(getcwd() . '/database', 0777, true);
        touch(getcwd() . '/database/load-test.sqlite');

        file_put_contents(getcwd() . '/app/config/database.php', '<?php return ' . var_export([
            'default' => [
                'database' => getcwd() . '/database/load-test.sqlite',
                'adapter'  => 'sqlite',
                'username' => null,
                'password' => null,
                'host'     => null,
                'type'     => null,
            ],
        ], true) . ';');

        $app = $this->makeApp();

        ob_start();
        $app->load();
        ob_end_clean();

        $this->assertInstanceOf('Pop\Db\Adapter\AbstractAdapter', \Pop\Db\Record::getDb());

        $this->leaveSandbox();
    }

    public function testLoadBoundEventsRunWithoutError()
    {
        // Force a non-production, non-maintenance env so productionDisplay()'s
        // confirm() prompt (which would otherwise block on real stdin) is skipped.
        $_ENV['APP_ENV']          = 'local';
        $_ENV['MAINTENANCE_MODE'] = 'false';

        $app = $this->makeApp();

        ob_start();
        $app->load();
        $app->trigger('app.route.pre');
        $app->trigger('app.dispatch.post');
        $output = ob_get_clean();

        $this->assertStringContainsString('Pop Kettle', $output);
    }

    public function testPrepareReturnsSelfForNativeKettleRoute()
    {
        $originalArgv    = $_SERVER['argv'];
        $_SERVER['argv'] = ['kettle', 'help'];

        $app    = $this->makeApp();
        $result = $app->prepare();

        $_SERVER['argv'] = $originalArgv;

        $this->assertSame($app, $result);
    }

    public function testPrepareReturnsSelfForUnmatchedRoute()
    {
        $originalArgv    = $_SERVER['argv'];
        $_SERVER['argv'] = ['kettle', 'this-command-does-not-exist'];

        $app    = $this->makeApp();
        $result = $app->prepare();

        $_SERVER['argv'] = $originalArgv;

        $this->assertSame($app, $result);
    }

    public function testPrepareSwitchesToCustomAppForPiggybackedCommand()
    {
        $this->enterSandbox();
        $this->scaffoldApp('cli', 'KettlePrepareApp');

        (new Kettle\Model\Application())->createCommand('greet', getcwd());

        $autoloader = include __DIR__ . '/../vendor/autoload.php';
        $autoloader->addPsr4('KettlePrepareApp\\', getcwd() . '/app/src');

        // Mirrors the real `kettle` script: only the Console/Command/Kettle
        // subfolder (piggybacked commands) is merged into Kettle's own route
        // table up front, which is what makes the command reachable at all
        // as `kettle greet` and lets it show up in `kettle help`.
        $config           = include __DIR__ . '/../config/app.console.php';
        $config['routes'] = \Pop\Console\CommandRegistry::loadRoutes($config['routes'], getcwd() . '/app/src/Console/Command/Kettle');

        $originalArgv    = $_SERVER['argv'];
        $_SERVER['argv'] = ['kettle', 'greet'];

        $app    = new Kettle\Application($autoloader, $config);
        $result = $app->prepare();

        $_SERVER['argv'] = $originalArgv;

        $this->assertInstanceOf('KettlePrepareApp\Application', $result);
        $this->assertNotSame($app, $result);
        $this->assertArrayHasKey('greet', $result->config()['routes']);

        $this->leaveSandbox();
    }

    public function testPrepareIgnoresStandaloneAppCommand()
    {
        $this->enterSandbox();
        $this->scaffoldApp('cli', 'KettlePrepareStandaloneApp');

        // A command created with the `-a` flag is scaffolded for the
        // separate `./script/myapp` entry point only - it never gets merged
        // into Kettle's own route table, so it stays unreachable as
        // `kettle greet` and shouldn't trigger the app switch.
        (new Kettle\Model\Application())->createCommand('greet', getcwd(), true);

        $autoloader = include __DIR__ . '/../vendor/autoload.php';
        $autoloader->addPsr4('KettlePrepareStandaloneApp\\', getcwd() . '/app/src');

        $config = include __DIR__ . '/../config/app.console.php';

        $originalArgv    = $_SERVER['argv'];
        $_SERVER['argv'] = ['kettle', 'greet'];

        $app    = new Kettle\Application($autoloader, $config);
        $result = $app->prepare();

        $_SERVER['argv'] = $originalArgv;

        $this->assertSame($app, $result);

        $this->leaveSandbox();
    }

    public function testInitDb()
    {
        $app = $this->makeApp();
        $app->initDb(include __DIR__ . '/tmp/app/config/database.php');

        $this->assertInstanceOf('Pop\Db\Adapter\AbstractAdapter', \Pop\Db\Record::getDb());
    }

    public function testInitDbWithNoDefaultConfigured()
    {
        $app = $this->makeApp();
        // No 'default' key and no adapter/database - should just no-op, not throw
        $app->initDb(['default' => []]);

        $this->assertInstanceOf('Pop\Kettle\Application', $app);
    }

    public function testInitDbBadConfigThrows()
    {
        $this->expectException('Pop\Db\Adapter\Exception');

        $app = $this->makeApp();
        $app->initDb(include __DIR__ . '/tmp/app/config/database-bad.php');
    }

    public function testInitDbBadConfigThrowsWithoutDoubledErrorPrefix()
    {
        // Db\Db::check() already returns a message starting with 'Error: ' - initDb() must not
        // prepend another 'Error: ' on top of it (regression test for a doubled prefix).
        $app = $this->makeApp();

        try {
            $app->initDb(include __DIR__ . '/tmp/app/config/database-bad.php');
            $this->fail('Expected a Pop\Db\Adapter\Exception to be thrown.');
        } catch (\Pop\Db\Adapter\Exception $e) {
            $this->assertStringStartsNotWith('Error: Error:', $e->getMessage());
            $this->assertSame(1, substr_count($e->getMessage(), 'Error:'));
        }
    }

    public function testCliError()
    {
        $app = $this->makeApp();

        ob_start();
        $app->cliError(new Kettle\Exception('This was an error.'), false);
        $result = ob_get_clean();

        $this->assertStringContainsString('This was an error.', $result);
    }

}
