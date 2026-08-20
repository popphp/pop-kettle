<?php

namespace Pop\Kettle\Test\Model;

use Pop\Console\Console;
use Pop\Kettle\Model;
use Pop\Kettle\Test\Fixtures\AppTestTrait;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
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

    private function seedDatabaseConfig(string $db = 'default', array $extra = []): array
    {
        @mkdir(getcwd() . '/app/config', 0777, true);
        @mkdir(getcwd() . '/database/migrations/' . $db, 0777, true);
        @mkdir(getcwd() . '/database/seeds/' . $db, 0777, true);
        @mkdir(getcwd() . '/database/snapshots/' . $db, 0777, true);

        touch(getcwd() . '/database/' . $db . '.sqlite');

        $config = array_merge([
            $db => [
                'database' => getcwd() . '/database/' . $db . '.sqlite',
                'adapter'  => 'sqlite',
                'username' => null,
                'password' => null,
                'host'     => null,
                'type'     => null,
            ],
        ], $extra);

        file_put_contents(getcwd() . '/app/config/database.php', '<?php return ' . var_export($config, true) . ';');

        return $config[$db];
    }

    public function testInit()
    {
        $database = new Model\Database();
        $this->assertInstanceOf('Pop\Kettle\Model\Database', $database);
    }

    public function testConfigureSqlite()
    {
        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream((string)$this->sqliteAdapterIndex(), 'mydb'));

        $database = new Model\Database();
        $result   = $database->configure($console, getcwd());

        $this->assertSame($database, $result);
        $this->assertFileExists(getcwd() . '/database/mydb.sqlite');
        $this->assertSame('sqlite', $_ENV['DB_ADAPTER']);
        $this->assertStringContainsString('mydb.sqlite', $_ENV['DB_DATABASE']);
    }

    public function testConfigureSqliteAppendsExtensionOnlyWhenMissing()
    {
        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream((string)$this->sqliteAdapterIndex(), 'already.sqlite'));

        $database = new Model\Database();
        $database->configure($console, getcwd());

        $this->assertFileExists(getcwd() . '/database/already.sqlite');
    }

    public function testConfigureNamedDatabaseWithMysql()
    {
        // Exercises the non-default-$database branch (env/config-file writes
        // for a named connection like "logging", not "default") against a
        // real MySQL connection - also the only test that drives configure()'s
        // generic (non-sqlite) Db::check() retry loop to a real success.
        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream(
            (string)$this->mysqlAdapterIndex(),
            $_ENV['MYSQL_DB'],
            $_ENV['MYSQL_USER'],
            $_ENV['MYSQL_PASS'],
            $_ENV['MYSQL_HOST']
        ));

        $database = new Model\Database();
        $result   = $database->configure($console, getcwd(), 'logging');

        $this->assertSame($database, $result);
        $this->assertSame('mysql', $_ENV['DB_LOGGING_ADAPTER']);
        $this->assertSame($_ENV['MYSQL_DB'], $_ENV['DB_LOGGING_DATABASE']);
        $this->assertSame($_ENV['MYSQL_USER'], $_ENV['DB_LOGGING_USERNAME']);
        $this->assertSame($_ENV['MYSQL_HOST'], $_ENV['DB_LOGGING_HOST']);

        $envContents = file_get_contents(getcwd() . '/.env');
        $this->assertStringContainsString('DB_LOGGING_DATABASE=' . $_ENV['MYSQL_DB'], $envContents);
        $this->assertStringContainsString('DB_LOGGING_ADAPTER=mysql', $envContents);

        $dbConfig = file_get_contents(getcwd() . '/app/config/database.php');
        $this->assertStringContainsString("'logging' => [", $dbConfig);
        $this->assertStringContainsString("\$_ENV['DB_LOGGING_DATABASE']", $dbConfig);
    }

    public function testConfigureNamedDatabaseWithSqlite()
    {
        // Same non-default-$database branch as testConfigureNamedDatabaseWithMysql(),
        // but through the sqlite-specific branch of configure() - proves the
        // named-connection file writes are adapter-agnostic, not incidentally
        // only correct for mysql.
        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream((string)$this->sqliteAdapterIndex(), 'archive'));

        $database = new Model\Database();
        $result   = $database->configure($console, getcwd(), 'archive');

        $this->assertSame($database, $result);
        $this->assertFileExists(getcwd() . '/database/archive.sqlite');
        $this->assertSame('sqlite', $_ENV['DB_ARCHIVE_ADAPTER']);
        $this->assertStringContainsString('archive.sqlite', $_ENV['DB_ARCHIVE_DATABASE']);

        $envContents = file_get_contents(getcwd() . '/.env');
        $this->assertStringContainsString('DB_ARCHIVE_ADAPTER=sqlite', $envContents);

        $dbConfig = file_get_contents(getcwd() . '/app/config/database.php');
        $this->assertStringContainsString("'archive' => [", $dbConfig);
        $this->assertStringContainsString("\$_ENV['DB_ARCHIVE_DATABASE']", $dbConfig);
    }

    public function testTestPasses()
    {
        $config = $this->seedDatabaseConfig();

        $database = new Model\Database();
        $this->assertTrue($database->test($config));
    }

    public function testTestFails()
    {
        $database = new Model\Database();
        $result   = $database->test([
            'adapter'  => 'sqlite',
            'database' => getcwd() . '/does-not-exist/bad.sqlite',
        ]);

        $this->assertNotTrue($result);
    }

    public function testCreateAdapter()
    {
        $config   = $this->seedDatabaseConfig();
        $database = new Model\Database();
        $adapter  = $database->createAdapter($config);

        $this->assertInstanceOf('Pop\Db\Adapter\AbstractAdapter', $adapter);
    }

    public function testInstallSql()
    {
        $config = $this->seedDatabaseConfig();

        $sqlFile = getcwd() . '/create.sql';
        file_put_contents($sqlFile, 'CREATE TABLE widgets (id INTEGER PRIMARY KEY, name TEXT);');

        $database = new Model\Database();
        $result   = $database->install($config, $sqlFile);

        $this->assertSame($database, $result);

        $adapter = $database->createAdapter($config);
        $this->assertContains('widgets', $adapter->getTables());
    }

    public function testSeedAllDatabases()
    {
        $this->seedDatabaseConfig('default');
        $this->seedDatabaseConfig('secondary', [
            'default' => [
                'database' => getcwd() . '/database/default.sqlite',
                'adapter'  => 'sqlite',
                'username' => null,
                'password' => null,
                'host'     => null,
                'type'     => null,
            ],
        ]);

        $console = new Console(120, '    ');
        ob_start();
        (new Model\Database())->seed($console, getcwd(), 'all');
        $result = ob_get_clean();

        $this->assertStringContainsString("Running database seeds for 'default'...", $result);
        $this->assertStringContainsString("Running database seeds for 'secondary'...", $result);
    }

    public function testResetWithRealTableRemovesCurrentPointer()
    {
        $config = $this->seedDatabaseConfig();

        $sqlFile = getcwd() . '/create.sql';
        file_put_contents($sqlFile, 'CREATE TABLE widgets (id INTEGER PRIMARY KEY, name TEXT);');

        $database = new Model\Database();
        $database->install($config, $sqlFile);

        touch(getcwd() . '/database/migrations/default/.current');

        $console = new Console(120, '    ');
        ob_start();
        $result = $database->reset($console, getcwd(), 'default');
        $output = ob_get_clean();

        $this->assertSame($database, $result);
        $this->assertStringContainsString('Resetting database data...', $output);
        $this->assertFileDoesNotExist(getcwd() . '/database/migrations/default/.current');
    }

    public function testClearWithRealTable()
    {
        $config = $this->seedDatabaseConfig();

        $sqlFile = getcwd() . '/create.sql';
        file_put_contents($sqlFile, 'CREATE TABLE widgets (id INTEGER PRIMARY KEY, name TEXT);');

        $database = new Model\Database();
        $database->install($config, $sqlFile);

        $console = new Console(120, '    ');
        ob_start();
        $result = $database->clear($console, getcwd(), 'default');
        $output = ob_get_clean();

        $this->assertSame($database, $result);
        $this->assertStringContainsString('Clearing database data...', $output);
        $this->assertStringContainsString('Done!', $output);
    }

    public function testExportMissingConfigKey()
    {
        $this->seedDatabaseConfig();
        $console = new Console(120, '    ');

        ob_start();
        (new Model\Database())->export($console, getcwd(), 'other');
        $result = ob_get_clean();

        $this->assertStringContainsString("The database configuration was not found for 'other'.", $result);
    }

    public function testImportMissingConfigKey()
    {
        $this->seedDatabaseConfig();
        $console = new Console(120, '    ');

        touch(getcwd() . '/dump.sql');

        ob_start();
        (new Model\Database())->import($console, getcwd(), 'dump.sql', 'other');
        $result = ob_get_clean();

        $this->assertStringContainsString("The database configuration was not found for 'other'.", $result);
    }

    private function pdoSqliteAdapterIndex(): int
    {
        $index = 0;
        $i     = 0;

        foreach (\Pop\Db\Db::getAvailableAdapters() as $adapter => $result) {
            if ($adapter == 'pdo') {
                foreach ($result as $a => $r) {
                    if ($r) {
                        $i++;
                        if (strtolower($a) == 'sqlite') {
                            $index = $i;
                        }
                    }
                }
            } else if ($result) {
                $i++;
            }
        }

        return $index;
    }

    public function testConfigurePdoSqlite()
    {
        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream((string)$this->pdoSqliteAdapterIndex(), 'pdodb'));

        $database = new Model\Database();
        $database->configure($console, getcwd());

        $this->assertFileExists(getcwd() . '/database/pdodb.sqlite');
        $this->assertSame('pdo', $_ENV['DB_ADAPTER']);
        $this->assertSame('sqlite', $_ENV['DB_TYPE']);
    }

    public function testSeedMissingConfigKey()
    {
        $this->seedDatabaseConfig();
        @mkdir(getcwd() . '/database/seeds/other', 0777, true);

        $console = new Console(120, '    ');
        ob_start();
        (new Model\Database())->seed($console, getcwd(), 'other');
        $result = ob_get_clean();

        $this->assertStringContainsString("The database configuration was not found for 'other'.", $result);
    }

    public function testExportAllDatabases()
    {
        $this->seedDatabaseConfig('default');
        $this->seedDatabaseConfig('secondary', [
            'default' => [
                'database' => getcwd() . '/database/default.sqlite',
                'adapter'  => 'sqlite',
                'username' => null,
                'password' => null,
                'host'     => null,
                'type'     => null,
            ],
        ]);

        $console = new Console(120, '    ');
        ob_start();
        (new Model\Database())->export($console, getcwd(), 'all');
        $result = ob_get_clean();

        $this->assertSame(2, substr_count($result, 'The database is not MySQL.'));
    }

    public function testExportMissingConfigFile()
    {
        $console = new Console(120, '    ');

        ob_start();
        (new Model\Database())->export($console, getcwd(), 'default');
        $result = ob_get_clean();

        $this->assertStringContainsString("The database configuration was not found for 'default'.", $result);
    }

    public function testExportAttemptsMysqldumpForMysqlAdapter()
    {
        @mkdir(getcwd() . '/app/config', 0777, true);
        @mkdir(getcwd() . '/database/snapshots/default', 0777, true);
        file_put_contents(getcwd() . '/app/config/database.php', '<?php return ' . var_export([
            'default' => [
                'database' => 'kettle_test',
                'adapter'  => 'mysql',
                'username' => 'nobody',
                'password' => 'nobody',
                'host'     => '127.0.0.1',
                'type'     => null,
            ],
        ], true) . ';');

        $console = new Console(120, '    ');
        ob_start();
        (new Model\Database())->export($console, getcwd(), 'default');
        $result = ob_get_clean();

        $this->assertStringContainsString('Exported!', $result);
    }

    public function testImportAllDatabases()
    {
        $this->seedDatabaseConfig('default');
        $this->seedDatabaseConfig('secondary', [
            'default' => [
                'database' => getcwd() . '/database/default.sqlite',
                'adapter'  => 'sqlite',
                'username' => null,
                'password' => null,
                'host'     => null,
                'type'     => null,
            ],
        ]);
        touch(getcwd() . '/dump.sql');

        $console = new Console(120, '    ');
        ob_start();
        (new Model\Database())->import($console, getcwd(), 'dump.sql', 'all');
        $result = ob_get_clean();

        $this->assertSame(2, substr_count($result, 'The database is not MySQL.'));
    }

    public function testImportMissingConfigFile()
    {
        touch(getcwd() . '/dump.sql');
        $console = new Console(120, '    ');

        ob_start();
        (new Model\Database())->import($console, getcwd(), 'dump.sql', 'default');
        $result = ob_get_clean();

        $this->assertStringContainsString("The database configuration was not found for 'default'.", $result);
    }

    public function testImportAttemptsMysqlRestoreForMysqlAdapter()
    {
        @mkdir(getcwd() . '/app/config', 0777, true);
        file_put_contents(getcwd() . '/app/config/database.php', '<?php return ' . var_export([
            'default' => [
                'database' => 'kettle_test',
                'adapter'  => 'mysql',
                'username' => 'nobody',
                'password' => 'nobody',
                'host'     => '127.0.0.1',
                'type'     => null,
            ],
        ], true) . ';');
        touch(getcwd() . '/dump.sql');

        $console = new Console(120, '    ');
        ob_start();
        (new Model\Database())->import($console, getcwd(), 'dump.sql', 'default');
        $result = ob_get_clean();

        $this->assertStringContainsString('Imported!', $result);
    }

    public function testResetAllDatabases()
    {
        $this->seedDatabaseConfig('default');
        $this->seedDatabaseConfig('secondary', [
            'default' => [
                'database' => getcwd() . '/database/default.sqlite',
                'adapter'  => 'sqlite',
            ],
        ]);

        $console = new Console(120, '    ');
        ob_start();
        (new Model\Database())->reset($console, getcwd(), 'all');
        $result = ob_get_clean();

        $this->assertSame(2, substr_count($result, 'Resetting database data...'));
    }

    public function testResetMissingSeedFolderForDatabase()
    {
        $this->seedDatabaseConfig();

        $console = new Console(120, '    ');
        ob_start();
        (new Model\Database())->reset($console, getcwd(), 'other');
        $result = ob_get_clean();

        $this->assertStringContainsString("The database seed folder was not found for 'other'.", $result);
    }

    public function testResetMissingConfigKeyForDatabase()
    {
        $this->seedDatabaseConfig();
        @mkdir(getcwd() . '/database/seeds/other', 0777, true);

        $console = new Console(120, '    ');
        ob_start();
        (new Model\Database())->reset($console, getcwd(), 'other');
        $result = ob_get_clean();

        $this->assertStringContainsString("The database configuration was not found for 'other'.", $result);
    }

    public function testClearAllDatabases()
    {
        $this->seedDatabaseConfig('default');
        $this->seedDatabaseConfig('secondary', [
            'default' => [
                'database' => getcwd() . '/database/default.sqlite',
                'adapter'  => 'sqlite',
            ],
        ]);

        $console = new Console(120, '    ');
        ob_start();
        (new Model\Database())->clear($console, getcwd(), 'all');
        $result = ob_get_clean();

        $this->assertSame(2, substr_count($result, 'Clearing database data...'));
    }

    public function testClearMissingConfigKey()
    {
        $this->seedDatabaseConfig();

        $console = new Console(120, '    ');
        ob_start();
        (new Model\Database())->clear($console, getcwd(), 'other');
        $result = ob_get_clean();

        $this->assertStringContainsString("The database configuration was not found for 'other'.", $result);
    }

    public function testClearRemovesCurrentPointer()
    {
        $config = $this->seedDatabaseConfig();

        $sqlFile = getcwd() . '/create.sql';
        file_put_contents($sqlFile, 'CREATE TABLE widgets (id INTEGER PRIMARY KEY, name TEXT);');

        $database = new Model\Database();
        $database->install($config, $sqlFile);

        touch(getcwd() . '/database/migrations/default/.current');

        $console = new Console(120, '    ');
        ob_start();
        $database->clear($console, getcwd(), 'default');
        ob_end_clean();

        $this->assertFileDoesNotExist(getcwd() . '/database/migrations/default/.current');
    }

}
