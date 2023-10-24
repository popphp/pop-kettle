<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Kettle\Model;

use Pop\Code\Generator;
use Pop\Console\Console;
use Pop\Db\Db;
use Pop\Db\Adapter;
use Pop\Db\Sql\Seeder\SeederInterface;
use Pop\Dir\Dir;
use Pop\Model\AbstractModel;

/**
 * Database model class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    2.0.0
 */
class Database extends AbstractModel
{

    /**
     * Configure database
     *
     * @param  Console $console
     * @param  string  $location
     * @param  string  $database
     * @return Database
     */
    public function configure(Console $console, string $location, string $database = 'default'): Database
    {
        $console->write();

        $dbUser     = '';
        $dbPass     = '';
        $dbHost     = '';
        $realDbName = '';
        $dbAdapters = Db::getAvailableAdapters();
        $dbChoices  = [];
        $i          = 1;

        if (!file_exists($location . '/database')) {
            mkdir($location . '/database');
        }
        if (!file_exists($location . '/database/migrations')) {
            mkdir($location . '/database/migrations');
        }
        if (!file_exists($location . '/database/seeds')) {
            mkdir($location . '/database/seeds');
        }
        if (!file_exists($location . '/database/migrations/' . $database)) {
            mkdir($location . '/database/migrations/' . $database);
        }
        if (!file_exists($location . '/database/seeds/' . $database)) {
            mkdir($location . '/database/seeds/' . $database);
        }

        foreach ($dbAdapters as $adapter => $result) {
            if ($adapter == 'pdo') {
                foreach ($result as $a => $r) {
                    if ($r) {
                        $console->write($i . ': PDO ' . str_replace(
                            ['sql', 'Pg'], ['SQL', 'Postgre'], ucfirst($a))
                        );
                        $dbChoices[strtolower('pdo_' . $a)] = $i;
                        $i++;
                    }
                }
            } else if ($result) {
                $console->write($i . ': ' . str_replace(
                    ['sqli', 'sql', 'Pg'], ['SQL', 'SQL', 'Postgre'], ucfirst($adapter))
                );
                $dbChoices[strtolower(str_replace('ysqli', 'ysql', $adapter))] = $i;
                $i++;
            }
        }

        $console->write();
        $adapter = $console->prompt('Please select one of the above database adapters: ', $dbChoices);
        $console->write();

        $dbAdapter = array_search($adapter, $dbChoices);
        $sqliteDb  = null;

        // If PDO
        if (str_contains($dbAdapter, 'pdo')) {
            $dbInterface = 'Pdo';
            $dbType      = substr($dbAdapter, (strpos($dbAdapter, '_') + 1));
        } else {
            $dbInterface = ucfirst(strtolower($dbAdapter));
            $dbType      = null;
        }

        if (($dbInterface == 'Sqlite') || ($dbType == 'sqlite')) {
            $dbName     = $console->prompt('DB Name: ', null, true);
            $sqliteFile = $dbName . ((!str_contains($dbName, '.sqlite')) ? '.sqlite' : '');
            $sqliteFile = str_replace(' ', '_', $sqliteFile);

            chmod($location . '/database', 0755);
            touch($location . '/database/' . $sqliteFile);
            chmod($location . '/database/' . $sqliteFile, 0777);

            $sqliteDb   = "__DIR__ . '/../../database/" . $sqliteFile . "'";
            $realDbName = '[{SQLITE_DB}]';

            $console->write();
        } else {
            $dbCheck = false;
            while ($dbCheck !== true) {
                $dbName     = ($database != 'default') ?
                    $console->prompt('DB Name: [' . $database .']') : $console->prompt('DB Name: ', null, true);
                $dbUser     = $console->prompt('DB User: ', null, true);
                $dbPass     = $console->prompt('DB Password: ', null, true);
                $dbHost     = $console->prompt('DB Host: [localhost] ', null, true);

                if (($dbName == '') && ($database != 'default')) {
                    $dbName = $database;
                }
                if ($dbHost == '') {
                    $dbHost = 'localhost';
                }

                $realDbName = $dbName;

                $dbCheck = Db::check($dbInterface, [
                    'database' => $dbName,
                    'username' => $dbUser,
                    'password' => $dbPass,
                    'host'     => $dbHost,
                    'type'     => $dbType,
                ]);

                if ($dbCheck !== true) {
                    $console->write();
                    $console->write($console->colorize(
                        'Database configuration test failed. Please try again. ' . PHP_EOL . PHP_EOL .
                        '    ' . $dbCheck, Console::BOLD_RED
                    ));
                } else {
                    $console->write();
                    $console->write($console->colorize(
                        'Database configuration test passed.', Console::BOLD_GREEN
                    ));
                }
                $console->write();
            }
        }

        $console->write('Writing database configuration file...');

        if (!file_exists($location . '/app')) {
            mkdir($location . '/app');
        }
        if (!file_exists($location . '/app/config')) {
            mkdir($location . '/app/config');
        }

        if (!file_exists($location . DIRECTORY_SEPARATOR . '/app/config/database.php')) {
            copy(
                __DIR__ . '/../../config/templates/api/app/config/database.php',
                $location . DIRECTORY_SEPARATOR . '/app/config/database.php'
            );
        }

        $dbConfig = include $location . DIRECTORY_SEPARATOR . '/app/config/database.php';

        if (!isset($dbConfig[$database])) {
            $dbConfig[$database] = [
                'adapter'  => '',
                'database' => '',
                'username' => '',
                'password' => '',
                'host'     => '',
                'type'     => ''
            ];
        }

        $dbConfig[$database]['adapter']  = strtolower($dbInterface);
        $dbConfig[$database]['database'] = $realDbName;
        $dbConfig[$database]['username'] = $dbUser;
        $dbConfig[$database]['password'] = $dbPass;
        $dbConfig[$database]['host']     = $dbHost;
        $dbConfig[$database]['type']     = $dbType;

        $configBody = new Generator\BodyGenerator();
        $configBody->setIndent(0);
        $configBody->createReturnConfig($dbConfig);

        $code = new Generator($configBody);
        $code->setIndent(0);
        $code->writeToFile($location . DIRECTORY_SEPARATOR . '/app/config/database.php');

        if ($sqliteDb !== null) {
            file_put_contents(
                $location . DIRECTORY_SEPARATOR . '/app/config/database.php',
                str_replace(
                    "'[{SQLITE_DB}]'",
                    $sqliteDb,
                    file_get_contents($location . DIRECTORY_SEPARATOR . '/app/config/database.php')
                )
            );
        }

        return $this;
    }

    /**
     * Test database connection
     *
     * @param  array  $database
     * @return string|bool
     */
    public function test(array $database): string|bool
    {
        return Db::check($database['adapter'], array_diff_key($database, array_flip(['adapter'])));
    }

    /**
     * Seed database
     *
     * @param  Console $console
     * @param  string  $location
     * @param  string  $database
     * @return Database
     */
    public function seed(Console $console, string $location, string $database = 'default'): Database
    {
        if ($database === null) {
            $databases = ['default'];
        } else if ($database == 'all') {
            $databases = array_filter(scandir($location . '/database/migrations'), function($value) {
                return (($value != '.') && ($value != '..'));
            });
        } else {
            $databases = [$database];
        }

        if (!file_exists($location . '/app/config/database.php')) {
            $console->write($console->colorize(
                "The database configuration was not found for '" . $database . "'.", Console::BOLD_RED
            ));
        } else {
            foreach ($databases as $db) {
                if (!file_exists($location . '/database/seeds/' . $db)) {
                    $console->write($console->colorize(
                        "The database seed folder was not found for '" . $db . "'.", Console::BOLD_RED
                    ));
                } else {
                    $dbConfig = include $location . '/app/config/database.php';
                    if (!isset($dbConfig[$db])) {
                        $console->write($console->colorize(
                            "The database configuration was not found for '" . $db . "'.", Console::BOLD_RED
                        ));
                    } else {
                        $dbAdapter = $this->createAdapter($dbConfig[$db]);
                        $dir       = new Dir($location . '/database/seeds/' . $db, ['filesOnly' => true]);
                        $seeds     = $dir->getFiles();

                        sort($seeds);

                        $console->write("Running database seeds for '" . $db . "'...");

                        foreach ($seeds as $seed) {
                            if (stripos($seed, '.sql') !== false) {
                                $this->install($dbConfig[$db], $location . '/database/seeds/' . $db . '/' . $seed);
                            } else if (stripos($seed, '.php') !== false) {
                                include $location . '/database/seeds/' . $db . '/' . $seed;
                                $class  = str_replace('.php', '', $seed);
                                $dbSeed = new $class();
                                if ($dbSeed instanceof SeederInterface) {
                                    $dbSeed->run($dbAdapter);
                                }
                            }
                        }
                        $console->write('Done!');
                        $console->write();
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Create database adapter
     *
     * @param  array $database
     * @return Adapter\AbstractAdapter
     */
    public function createAdapter(array $database): Adapter\AbstractAdapter
    {
        return Db::connect($database['adapter'], array_diff_key($database, array_flip(['adapter'])));
    }

    /**
     * Install SQL
     *
     * @param  array  $database
     * @param  string $sqlFile
     * @return Database
     */
    public function install(array $database, string $sqlFile): Database
    {
        Db::executeSqlFile($sqlFile, $database['adapter'], array_diff_key($database, array_flip(['adapter'])));

        return $this;
    }

    /**
     * Reset database
     *
     * @param  Console $console
     * @param  string  $location
     * @param  string  $database
     * @return Database
     */
    public function reset(Console $console, string $location, string $database = 'default'): Database
    {
        if ($database === null) {
            $databases = ['default'];
        } else if ($database == 'all') {
            $databases = array_filter(scandir($location . '/database/migrations'), function($value) {
                return (($value != '.') && ($value != '..'));
            });
        } else {
            $databases = [$database];
        }

        if (!file_exists($location . '/app/config/database.php')) {
            $console->write($console->colorize(
                'The database configuration was not found.', Console::BOLD_RED
            ));
        } else {
            foreach ($databases as $db) {
                if (!file_exists($location . '/database/seeds/' . $db)) {
                    $console->write($console->colorize(
                        "The database seed folder was not found for '" . $db . "'.", Console::BOLD_RED
                    ));
                } else {
                    $console->write('Resetting database data...');

                    $dbConfig = include $location . '/app/config/database.php';
                    if (!isset($dbConfig[$db])) {
                        $console->write($console->colorize(
                            "The database configuration was not found for '" . $db . "'.", Console::BOLD_RED
                        ));
                    } else {
                        $dbAdapter = $this->createAdapter($dbConfig[$db]);
                        $schema    = $dbAdapter->createSchema();
                        $tables    = $dbAdapter->getTables();

                        if (($dbAdapter instanceof \Pop\Db\Adapter\Mysql) ||
                            (($dbAdapter instanceof \Pop\Db\Adapter\Pdo) && ($dbAdapter->getType() == 'mysql'))) {
                            $dbAdapter->query('SET foreign_key_checks = 0');
                            foreach ($tables as $table) {
                                $schema->truncate($table);
                                $dbAdapter->query($schema);
                            }
                            $dbAdapter->query('SET foreign_key_checks = 1');
                        } else if (($dbAdapter instanceof \Pop\Db\Adapter\Pgsql) ||
                            (($dbAdapter instanceof \Pop\Db\Adapter\Pdo) && ($dbAdapter->getType() == 'pgsql'))) {
                            foreach ($tables as $table) {
                                $schema->truncate($table)->cascade();
                                $dbAdapter->query($schema);
                            }
                        } else {
                            foreach ($tables as $table) {
                                $schema->truncate($table);
                                $dbAdapter->query($schema);
                            }
                        }

                        $this->seed($console, $location, $db);

                        if (file_exists($location . '/database/migrations/' . $db . '/.current')) {
                            unlink($location . '/database/migrations/' . $db . '/.current');
                        }
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Clear database
     *
     * @param  Console $console
     * @param  string  $location
     * @param  string  $database
     * @return Database
     */
    public function clear(Console $console, string $location, string $database = 'default'): Database
    {
        if ($database === null) {
            $databases = ['default'];
        } else if ($database == 'all') {
            $databases = array_filter(scandir($location . '/database/migrations'), function($value) {
                return (($value != '.') && ($value != '..'));
            });
        } else {
            $databases = [$database];
        }

        if (!file_exists($location . '/app/config/database.php')) {
            $console->write($console->colorize(
                'The database configuration was not found.', Console::BOLD_RED
            ));
        } else {
            foreach ($databases as $db) {
                $console->write('Clearing database data...');

                $dbConfig = include $location . '/app/config/database.php';
                if (!isset($dbConfig[$db])) {
                    $console->write($console->colorize(
                        "The database configuration was not found for '" . $db . "'.", Console::BOLD_RED
                    ));
                } else {
                    $dbAdapter = $this->createAdapter($dbConfig[$db]);
                    $schema    = $dbAdapter->createSchema();
                    $tables    = $dbAdapter->getTables();

                    if (($dbAdapter instanceof \Pop\Db\Adapter\Mysql) ||
                        (($dbAdapter instanceof \Pop\Db\Adapter\Pdo) && ($dbAdapter->getType() == 'mysql'))) {
                        $dbAdapter->query('SET foreign_key_checks = 0');
                        foreach ($tables as $table) {
                            $schema->drop($table);
                            $dbAdapter->query($schema);
                        }
                        $dbAdapter->query('SET foreign_key_checks = 1');
                    } else if (($dbAdapter instanceof \Pop\Db\Adapter\Pgsql) ||
                        (($dbAdapter instanceof \Pop\Db\Adapter\Pdo) && ($dbAdapter->getType() == 'pgsql'))) {
                        foreach ($tables as $table) {
                            $schema->drop($table)->cascade();
                            $dbAdapter->query($schema);
                        }
                    } else {
                        foreach ($tables as $table) {
                            $schema->drop($table);
                            $dbAdapter->query($schema);
                        }
                    }

                    if (file_exists($location . '/database/migrations/' . $db . '/.current')) {
                        unlink($location . '/database/migrations/' . $db . '/.current');
                    }

                    $console->write();
                    $console->write('Done!');
                }
            }
        }

        return $this;
    }

}
