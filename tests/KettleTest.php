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

    public function testCliError()
    {
        $app = $this->makeApp();

        ob_start();
        $app->cliError(new Kettle\Exception('This was an error.'), false);
        $result = ob_get_clean();

        $this->assertStringContainsString('This was an error.', $result);
    }

}
