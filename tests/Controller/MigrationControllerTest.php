<?php

namespace Pop\Kettle\Test\Controller;

use Pop\Console\Console;
use Pop\Db\Db;
use Pop\Kettle;
use Pop\Kettle\Test\Fixtures\AppTestTrait;
use Pop\Kettle\Test\Fixtures\Migrations;
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

    /**
     * Write a real migration class file into a database's migrations folder, in the
     * same shape Migrator::create() produces (a 14-digit timestamp prefix, and a class
     * extending AbstractMigration), so Migrator picks it up as a migration.
     */
    private function seedMigration(string $timestamp, string $class, string $db = 'default'): void
    {
        @mkdir(getcwd() . '/database/migrations/' . $db, 0777, true);

        file_put_contents(
            getcwd() . '/database/migrations/' . $db . '/' . $timestamp . '_' . strtolower($class) . '.php',
            '<?php' . PHP_EOL . PHP_EOL .
            'use Pop\Db\Sql\Migration\AbstractMigration;' . PHP_EOL . PHP_EOL .
            'class ' . $class . ' extends AbstractMigration' . PHP_EOL .
            '{' . PHP_EOL . PHP_EOL .
            '    public function up(): void {}' . PHP_EOL . PHP_EOL .
            '    public function down(): void {}' . PHP_EOL . PHP_EOL .
            '}' . PHP_EOL
        );
    }

    /**
     * Switch a database's migration state storage over from the default '.current'
     * file to a migrations table in the database itself, which is the only storage
     * mode that tracks batch numbers.
     */
    private function seedTableStorage(string $db = 'default'): void
    {
        @mkdir(getcwd() . '/database/migrations/' . $db, 0777, true);

        file_put_contents(getcwd() . '/database/migrations/' . $db . '/.table', Migrations::class);
        Migrations::setDb(Db::connect('sqlite', ['database' => getcwd() . '/database/' . $db . '.sqlite']));
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

    public function testStatusNothingApplied()
    {
        $this->seedDatabaseConfig();
        $this->seedMigration('20260101120000', 'StatusNothingAppliedOne');
        $this->seedMigration('20260101130000', 'StatusNothingAppliedTwo');

        ob_start();
        $this->controller()->status(null);
        $result = ob_get_clean();

        $this->assertStringContainsString('Database: default', $result);
        $this->assertStringContainsString('Current: 0', $result);
        $this->assertStringContainsString('Batch: 0', $result);
        $this->assertStringContainsString('Pending: 2', $result);
        $this->assertStringContainsString('Storage: file', $result);
        $this->assertStringContainsString('Path: ' . getcwd() . '/database/migrations/default', $result);
    }

    public function testStatusSomeApplied()
    {
        $this->seedDatabaseConfig();
        $this->seedMigration('20260101120000', 'StatusSomeAppliedOne');
        $this->seedMigration('20260101130000', 'StatusSomeAppliedTwo');

        ob_start();
        $this->controller()->run(1, 'default');
        $this->controller()->status('default');
        $result = ob_get_clean();

        $this->assertStringContainsString('Current: 20260101120000', $result);
        $this->assertStringContainsString('Pending: 1', $result);
    }

    public function testStatusAllApplied()
    {
        $this->seedDatabaseConfig();
        $this->seedMigration('20260101120000', 'StatusAllAppliedOne');
        $this->seedMigration('20260101130000', 'StatusAllAppliedTwo');

        ob_start();
        $this->controller()->run('all', 'default');
        $this->controller()->status('default');
        $result = ob_get_clean();

        $this->assertStringContainsString('Current: 20260101130000', $result);
        $this->assertStringContainsString('Pending: 0', $result);
    }

    public function testStatusWithTableStorage()
    {
        $this->seedDatabaseConfig();
        $this->seedTableStorage();
        $this->seedMigration('20260101120000', 'StatusTableStorageOne');

        ob_start();
        $this->controller()->run(1, 'default');
        $this->controller()->status('default');
        $result = ob_get_clean();

        $this->assertStringContainsString('Storage: table', $result);
        $this->assertStringContainsString('Current: 20260101120000', $result);
        $this->assertStringContainsString('Batch: 1', $result);
        $this->assertStringContainsString('Pending: 0', $result);
    }

    public function testStatusAllDatabases()
    {
        $this->seedDatabaseConfig('default');
        $this->seedDatabaseConfig('secondary');

        ob_start();
        $this->controller()->status('all');
        $result = ob_get_clean();

        $this->assertStringContainsString('Database: default', $result);
        $this->assertStringContainsString('Database: secondary', $result);
    }

    public function testStatusMissingConfig()
    {
        ob_start();
        $this->controller()->status();
        $result = ob_get_clean();

        $this->assertStringContainsString('The database configuration was not found.', $result);
    }

    public function testStatusMissingFolder()
    {
        @mkdir(getcwd() . '/app/config', 0777, true);
        file_put_contents(getcwd() . '/app/config/database.php', '<?php return ' . var_export([
            'default' => ['database' => 'x', 'adapter' => 'sqlite'],
        ], true) . ';');

        ob_start();
        $this->controller()->status();
        $result = ob_get_clean();

        $this->assertStringContainsString("The database migration folder was not found for 'default'.", $result);
    }

    public function testStatusMissingConfigKey()
    {
        $this->seedDatabaseConfig();
        @mkdir(getcwd() . '/database/migrations/other', 0777, true);

        ob_start();
        $this->controller()->status('other');
        $result = ob_get_clean();

        $this->assertStringContainsString("The database configuration was not found for 'other'.", $result);
    }

    public function testRunReportsAppliedAndBatch()
    {
        $this->seedDatabaseConfig();
        $this->seedTableStorage();
        $this->seedMigration('20260101120000', 'RunReportsOne');
        $this->seedMigration('20260101130000', 'RunReportsTwo');

        ob_start();
        $this->controller()->run('all', 'default');
        $runResult = ob_get_clean();

        $this->assertStringContainsString('Applied: 2', $runResult);
        $this->assertStringContainsString('Batch: 1', $runResult);
        $this->assertStringContainsString('Done!', $runResult);

        ob_start();
        $this->controller()->status('default');
        $statusResult = ob_get_clean();

        $this->assertStringContainsString('Batch: 1', $statusResult);
        $this->assertStringContainsString('Pending: 0', $statusResult);
    }

    public function testRunReportsNothingApplied()
    {
        $this->seedDatabaseConfig();

        ob_start();
        $this->controller()->run(1, 'default');
        $result = ob_get_clean();

        $this->assertStringContainsString('Applied: 0', $result);
        $this->assertStringContainsString('Batch: 0', $result);
    }

}
