<?php

namespace Pop\Kettle\Test\Controller;

use Pop\Console\Console;
use Pop\Kettle;
use Pop\Kettle\Test\Fixtures\AppTestTrait;
use PHPUnit\Framework\TestCase;

class MigrationControllerTest extends TestCase
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

    private function controller(): Kettle\Controller\MigrationController
    {
        return new Kettle\Controller\MigrationController($this->makeApp(), new Console(120, '    '));
    }

    private function seedDatabaseConfig(string $db = 'default'): void
    {
        @mkdir(getcwd() . '/app/config', 0777, true);
        @mkdir(getcwd() . '/database/migrations/' . $db, 0777, true);

        touch(getcwd() . '/database/' . $db . '.sqlite');

        $configFile = getcwd() . '/app/config/database.php';
        $config     = file_exists($configFile) ? include $configFile : [];

        $config[$db] = [
            'database' => getcwd() . '/database/' . $db . '.sqlite',
            'adapter'  => 'sqlite',
            'username' => null,
            'password' => null,
            'host'     => null,
            'type'     => null,
        ];

        file_put_contents(getcwd() . '/app/config/database.php', '<?php return ' . var_export($config, true) . ';');
    }

    public function testCreate()
    {
        @mkdir(getcwd() . '/database/migrations', 0777, true);

        ob_start();
        $this->controller()->create('MyMigration', null);
        $result = ob_get_clean();

        $this->assertStringContainsString("Migration class 'MyMigration", $result);
        $this->assertStringContainsString("created for 'default'.", $result);
    }

    public function testCreateExplicitDatabase()
    {
        @mkdir(getcwd() . '/database/migrations', 0777, true);

        ob_start();
        $this->controller()->create('MyMigration', 'default');
        $result = ob_get_clean();

        $this->assertStringContainsString("created for 'default'.", $result);
    }

    public function testCreateAll()
    {
        @mkdir(getcwd() . '/database/migrations/default', 0777, true);
        @mkdir(getcwd() . '/database/migrations/secondary', 0777, true);

        ob_start();
        $this->controller()->create('MyMigration', 'all');
        $result = ob_get_clean();

        $this->assertStringContainsString("created for 'default'.", $result);
        $this->assertStringContainsString("created for 'secondary'.", $result);
    }

    public function testRun()
    {
        $this->seedDatabaseConfig();

        ob_start();
        $this->controller()->run();
        $result = ob_get_clean();

        $this->assertStringContainsString("Running database migration for 'default'...", $result);
        $this->assertStringContainsString('Done!', $result);
    }

    public function testRunMissingConfig()
    {
        ob_start();
        $this->controller()->run();
        $result = ob_get_clean();

        $this->assertStringContainsString('The database configuration was not found.', $result);
    }

    public function testRunMissingFolder()
    {
        @mkdir(getcwd() . '/app/config', 0777, true);
        file_put_contents(getcwd() . '/app/config/database.php', '<?php return ' . var_export([
            'default' => ['database' => 'x', 'adapter' => 'sqlite'],
        ], true) . ';');

        ob_start();
        $this->controller()->run();
        $result = ob_get_clean();

        $this->assertStringContainsString("The database migration folder was not found for 'default'.", $result);
    }

    public function testRunMissingConfigKey()
    {
        $this->seedDatabaseConfig();
        // request a database that isn't in the config
        @mkdir(getcwd() . '/database/migrations/other', 0777, true);

        ob_start();
        $this->controller()->run(1, 'other');
        $result = ob_get_clean();

        $this->assertStringContainsString("The database configuration was not found for 'other'.", $result);
    }

    public function testRunWithNullDatabase()
    {
        $this->seedDatabaseConfig();

        ob_start();
        $this->controller()->run(1, null);
        $result = ob_get_clean();

        $this->assertStringContainsString("Running database migration for 'default'...", $result);
    }

    public function testRunWithNullSteps()
    {
        $this->seedDatabaseConfig();

        ob_start();
        $this->controller()->run(null, 'default');
        $result = ob_get_clean();

        $this->assertStringContainsString("Running database migration for 'default'...", $result);
    }

    public function testRunAllDatabases()
    {
        $this->seedDatabaseConfig('default');
        $this->seedDatabaseConfig('secondary');

        ob_start();
        $this->controller()->run(1, 'all');
        $result = ob_get_clean();

        $this->assertStringContainsString("Running database migration for 'default'...", $result);
        $this->assertStringContainsString("Running database migration for 'secondary'...", $result);
    }

    public function testRollback()
    {
        $this->seedDatabaseConfig();

        ob_start();
        $this->controller()->rollback();
        $result = ob_get_clean();

        $this->assertStringContainsString("Rolling back database migration for 'default'...", $result);
        $this->assertStringContainsString('Done!', $result);
    }

    public function testRollbackWithNullDatabase()
    {
        $this->seedDatabaseConfig();

        ob_start();
        $this->controller()->rollback(1, null);
        $result = ob_get_clean();

        $this->assertStringContainsString("Rolling back database migration for 'default'...", $result);
    }

    public function testRollbackWithNullSteps()
    {
        $this->seedDatabaseConfig();

        ob_start();
        $this->controller()->rollback(null, 'default');
        $result = ob_get_clean();

        $this->assertStringContainsString("Rolling back database migration for 'default'...", $result);
    }

    public function testRollbackAllDatabases()
    {
        $this->seedDatabaseConfig('default');
        $this->seedDatabaseConfig('secondary');

        ob_start();
        $this->controller()->rollback(1, 'all');
        $result = ob_get_clean();

        $this->assertStringContainsString("Rolling back database migration for 'default'...", $result);
        $this->assertStringContainsString("Rolling back database migration for 'secondary'...", $result);
    }

    public function testRollbackMissingConfig()
    {
        ob_start();
        $this->controller()->rollback();
        $result = ob_get_clean();

        $this->assertStringContainsString('The database configuration was not found.', $result);
    }

    public function testRollbackMissingFolder()
    {
        @mkdir(getcwd() . '/app/config', 0777, true);
        file_put_contents(getcwd() . '/app/config/database.php', '<?php return ' . var_export([
            'default' => ['database' => 'x', 'adapter' => 'sqlite'],
        ], true) . ';');

        ob_start();
        $this->controller()->rollback();
        $result = ob_get_clean();

        $this->assertStringContainsString("The database migration folder was not found for 'default'.", $result);
    }

    public function testRollbackMissingConfigKey()
    {
        $this->seedDatabaseConfig();
        @mkdir(getcwd() . '/database/migrations/other', 0777, true);

        ob_start();
        $this->controller()->rollback(1, 'other');
        $result = ob_get_clean();

        $this->assertStringContainsString("The database configuration was not found for 'other'.", $result);
    }

    public function testReset()
    {
        $this->seedDatabaseConfig();

        ob_start();
        $this->controller()->reset(null);
        $result = ob_get_clean();

        $this->assertStringContainsString("Resetting the database for 'default'...", $result);
        $this->assertStringContainsString('Done!', $result);
    }

    public function testResetMissingMigrationFolderForDatabase()
    {
        $this->seedDatabaseConfig();
        // 'other' has no migrations folder, but is present in the config file's keys? No -
        // reset() only checks the migrations folder per requested db, independent of config keys.
        ob_start();
        $this->controller()->reset('other');
        $result = ob_get_clean();

        $this->assertStringContainsString("The database migration folder was not found for 'other'.", $result);
    }

    public function testResetMissingConfigKeyForDatabase()
    {
        $this->seedDatabaseConfig();
        @mkdir(getcwd() . '/database/migrations/other', 0777, true);

        ob_start();
        $this->controller()->reset('other');
        $result = ob_get_clean();

        $this->assertStringContainsString("The database configuration was not found for 'other'.", $result);
    }

    public function testResetAllDatabases()
    {
        $this->seedDatabaseConfig('default');
        $this->seedDatabaseConfig('secondary');

        ob_start();
        $this->controller()->reset('all');
        $result = ob_get_clean();

        $this->assertStringContainsString("Resetting the database for 'default'...", $result);
        $this->assertStringContainsString("Resetting the database for 'secondary'...", $result);
    }

    public function testResetMissingConfig()
    {
        ob_start();
        $this->controller()->reset(null);
        $result = ob_get_clean();

        $this->assertStringContainsString('The database configuration was not found.', $result);
    }

    public function testPointDatabaseNotFound()
    {
        ob_start();
        $this->controller()->point('latest', 'default');
        $result = ob_get_clean();

        $this->assertStringContainsString("does not exist in the migration folder.", $result);
    }

    public function testPointNoCurrentFileIsNoop()
    {
        @mkdir(getcwd() . '/database/migrations/default', 0777, true);

        ob_start();
        $this->controller()->point('latest', 'default');
        $result = ob_get_clean();

        $this->assertSame('', trim($result));
    }

    public function testPointNoMigrationsFound()
    {
        @mkdir(getcwd() . '/database/migrations/default', 0777, true);
        touch(getcwd() . '/database/migrations/default/.current');

        ob_start();
        $this->controller()->point('latest', 'default');
        $result = ob_get_clean();

        $this->assertStringContainsString('No migrations for the', $result);
    }

    public function testPointLatest()
    {
        @mkdir(getcwd() . '/database/migrations/default', 0777, true);
        touch(getcwd() . '/database/migrations/default/.current');
        touch(getcwd() . '/database/migrations/default/100_first.php');
        touch(getcwd() . '/database/migrations/default/200_second.php');

        ob_start();
        $this->controller()->point('latest', 'default');
        $result = ob_get_clean();

        $this->assertStringContainsString('Done!', $result);
        $this->assertSame('200', file_get_contents(getcwd() . '/database/migrations/default/.current'));
    }

    public function testPointValidNumericId()
    {
        @mkdir(getcwd() . '/database/migrations/default', 0777, true);
        touch(getcwd() . '/database/migrations/default/.current');
        touch(getcwd() . '/database/migrations/default/100_first.php');
        touch(getcwd() . '/database/migrations/default/200_second.php');

        ob_start();
        $this->controller()->point('100', 'default');
        $result = ob_get_clean();

        $this->assertStringContainsString('Done!', $result);
        $this->assertSame('100', file_get_contents(getcwd() . '/database/migrations/default/.current'));
    }

    public function testPointInvalidNumericId()
    {
        @mkdir(getcwd() . '/database/migrations/default', 0777, true);
        touch(getcwd() . '/database/migrations/default/.current');
        touch(getcwd() . '/database/migrations/default/100_first.php');

        ob_start();
        $this->controller()->point('999', 'default');
        $result = ob_get_clean();

        $this->assertStringContainsString('does not exist.', $result);
    }

    public function testPointDefaultsWhenNullArgs()
    {
        @mkdir(getcwd() . '/database/migrations/default', 0777, true);
        touch(getcwd() . '/database/migrations/default/.current');
        touch(getcwd() . '/database/migrations/default/100_first.php');

        ob_start();
        $this->controller()->point(null, null);
        $result = ob_get_clean();

        $this->assertStringContainsString('Done!', $result);
        $this->assertSame('100', file_get_contents(getcwd() . '/database/migrations/default/.current'));
    }

}
