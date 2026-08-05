<?php

namespace Pop\Kettle\Test\Controller;

use Pop\Console\Console;
use Pop\Kettle;
use Pop\Kettle\Test\Fixtures\AppTestTrait;
use PHPUnit\Framework\TestCase;

class DatabaseControllerTest extends TestCase
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

    private function controller(?Console $console = null): Kettle\Controller\DatabaseController
    {
        return new Kettle\Controller\DatabaseController($this->makeApp(), $console ?? new Console(120, '    '));
    }

    private function seedDatabaseConfig(string $file = 'test.sqlite', string $db = 'default'): void
    {
        @mkdir(getcwd() . '/app/config', 0777, true);
        @mkdir(getcwd() . '/database/migrations/' . $db, 0777, true);
        @mkdir(getcwd() . '/database/seeds/' . $db, 0777, true);
        @mkdir(getcwd() . '/database/snapshots/' . $db, 0777, true);

        touch(getcwd() . '/database/' . $file);

        $config = [
            $db => [
                'database' => getcwd() . '/database/' . $file,
                'adapter'  => 'sqlite',
                'username' => null,
                'password' => null,
                'host'     => null,
                'type'     => null,
            ],
        ];

        file_put_contents(getcwd() . '/app/config/database.php', '<?php return ' . var_export($config, true) . ';');
    }

    public function testConfig()
    {
        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream((string)$this->sqliteAdapterIndex(), 'testconfig'));

        ob_start();
        $this->controller($console)->config(null);
        $result = ob_get_clean();

        $this->assertStringContainsString('Sqlite', $result);
        $this->assertFileExists(getcwd() . '/app/config/database.php');
        $this->assertFileExists(getcwd() . '/database/testconfig.sqlite');
    }

    public function testInstall()
    {
        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream((string)$this->sqliteAdapterIndex(), 'testinstall'));

        ob_start();
        $this->controller($console)->install(null);
        ob_end_clean();

        $this->assertStringContainsString('DB_ADAPTER=sqlite', file_get_contents(getcwd() . '/.env'));
    }

    public function testTestPasses()
    {
        $this->seedDatabaseConfig();

        ob_start();
        $this->controller()->test(null);
        $result = ob_get_clean();

        $this->assertStringContainsString("Database configuration test for 'default' passed.", $result);
    }

    public function testTestFails()
    {
        @mkdir(getcwd() . '/app/config', 0777, true);
        file_put_contents(getcwd() . '/app/config/database.php', '<?php return ' . var_export([
            'default' => [
                'database' => getcwd() . '/does-not-exist/bad.sqlite',
                'adapter'  => 'sqlite',
                'username' => null,
                'password' => null,
                'host'     => null,
                'type'     => null,
            ],
        ], true) . ';');

        ob_start();
        $this->controller()->test(null);
        $result = ob_get_clean();

        $this->assertStringNotContainsString("passed.", $result);
    }

    public function testTestMissingConfigFile()
    {
        ob_start();
        $this->controller()->test(null);
        $result = ob_get_clean();

        $this->assertStringContainsString('The database configuration was not found.', $result);
    }

    public function testTestMissingConfigKey()
    {
        $this->seedDatabaseConfig();

        ob_start();
        $this->controller()->test('other');
        $result = ob_get_clean();

        $this->assertStringContainsString("The database configuration was not found for 'other'.", $result);
    }

    public function testTestAllDatabases()
    {
        $this->seedDatabaseConfig('test.sqlite', 'default');

        ob_start();
        $this->controller()->test('all');
        $result = ob_get_clean();

        $this->assertStringContainsString("Database configuration test for 'default' passed.", $result);
    }

    public function testCreateSeedSqlFile()
    {
        @mkdir(getcwd() . '/database/seeds', 0777, true);

        ob_start();
        $this->controller()->createSeed('my_data.sql', null);
        $result = ob_get_clean();

        $this->assertStringContainsString("Database seed file 'my_data.sql' created for 'default'.", $result);
        $this->assertFileExists(getcwd() . '/database/seeds/default/my_data.sql');
    }

    public function testCreateSeedClass()
    {
        @mkdir(getcwd() . '/database/seeds', 0777, true);

        ob_start();
        $this->controller()->createSeed('MySeed', null);
        $result = ob_get_clean();

        $this->assertStringContainsString("Database seed class 'MySeed' created for 'default'.", $result);

        $files = scandir(getcwd() . '/database/seeds/default');
        $found = false;
        foreach ($files as $file) {
            if (str_ends_with($file, '_my_seed.php')) {
                $found = true;
            }
        }
        $this->assertTrue($found);
    }

    public function testCreateSeedExplicitDatabase()
    {
        @mkdir(getcwd() . '/database/seeds', 0777, true);

        ob_start();
        $this->controller()->createSeed('MySeed', 'default');
        $result = ob_get_clean();

        $this->assertStringContainsString("created for 'default'.", $result);
    }

    public function testCreateSeedAllDatabases()
    {
        @mkdir(getcwd() . '/database/seeds', 0777, true);
        @mkdir(getcwd() . '/database/migrations/default', 0777, true);
        @mkdir(getcwd() . '/database/migrations/secondary', 0777, true);

        ob_start();
        $this->controller()->createSeed('MySeed', 'all');
        $result = ob_get_clean();

        $this->assertStringContainsString("created for 'default'.", $result);
        $this->assertStringContainsString("created for 'secondary'.", $result);
    }

    public function testExportWithNullDatabaseDefaultsToDefault()
    {
        $this->seedDatabaseConfig();

        ob_start();
        $this->controller()->export(null);
        $result = ob_get_clean();

        $this->assertStringContainsString('The database is not MySQL.', $result);
    }

    public function testImportWithNullDatabaseDefaultsToDefault()
    {
        $this->seedDatabaseConfig();
        touch(getcwd() . '/dump.sql');

        ob_start();
        $this->controller()->import('dump.sql', null);
        $result = ob_get_clean();

        $this->assertStringContainsString('The database is not MySQL.', $result);
    }

    public function testSeed()
    {
        $this->seedDatabaseConfig();

        ob_start();
        $this->controller()->seed(null);
        $result = ob_get_clean();

        $this->assertStringContainsString("Running database seeds for 'default'...", $result);
        $this->assertStringContainsString('Done!', $result);
    }

    public function testSeedMissingConfig()
    {
        ob_start();
        $this->controller()->seed(null);
        $result = ob_get_clean();

        $this->assertStringContainsString("The database configuration was not found for 'default'.", $result);
    }

    public function testSeedMissingFolder()
    {
        @mkdir(getcwd() . '/app/config', 0777, true);
        file_put_contents(getcwd() . '/app/config/database.php', '<?php return ' . var_export([
            'default' => ['database' => 'x', 'adapter' => 'sqlite'],
        ], true) . ';');

        ob_start();
        $this->controller()->seed(null);
        $result = ob_get_clean();

        $this->assertStringContainsString("The database seed folder was not found for 'default'.", $result);
    }

    public function testImportMissingFile()
    {
        ob_start();
        $this->controller()->import('does-not-exist.sql', 'default');
        $result = ob_get_clean();

        $this->assertStringContainsString('The database import file was not found.', $result);
    }

    public function testImportNonMysqlDatabase()
    {
        $this->seedDatabaseConfig();
        touch(getcwd() . '/dump.sql');

        ob_start();
        $this->controller()->import('dump.sql', 'default');
        $result = ob_get_clean();

        $this->assertStringContainsString('The database is not MySQL. It must be MySQL to perform the export', $result);
    }

    public function testExportNonMysqlDatabase()
    {
        $this->seedDatabaseConfig();

        ob_start();
        $this->controller()->export('default');
        $result = ob_get_clean();

        $this->assertStringContainsString('The database is not MySQL. It must be MySQL to perform the export', $result);
    }

    public function testReset()
    {
        $this->seedDatabaseConfig();

        ob_start();
        $this->controller()->reset(null);
        $result = ob_get_clean();

        $this->assertStringContainsString('Resetting database data...', $result);
        $this->assertStringContainsString('Done!', $result);
    }

    public function testResetMissingConfig()
    {
        ob_start();
        $this->controller()->reset(null);
        $result = ob_get_clean();

        $this->assertStringContainsString('The database configuration was not found.', $result);
    }

    public function testClear()
    {
        $this->seedDatabaseConfig();

        ob_start();
        $this->controller()->clear(null);
        $result = ob_get_clean();

        $this->assertStringContainsString('Clearing database data...', $result);
        $this->assertStringContainsString('Done!', $result);
    }

    public function testClearMissingConfig()
    {
        ob_start();
        $this->controller()->clear(null);
        $result = ob_get_clean();

        $this->assertStringContainsString('The database configuration was not found.', $result);
    }

}
