<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Kettle\Model;

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
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    1.1.0
 */
class Database extends AbstractModel
{

    /**
     * Configure database
     *
     * @param Console $console
     * @param string  $location
     * @param string  $database
     * @return Database
     */
    public function configure(Console $console, $location, $database = 'default')
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

        // If PDO
        if (strpos($dbAdapter, 'pdo') !== false) {
            $dbInterface = 'Pdo';
            $dbType      = substr($dbAdapter, (strpos($dbAdapter, '_') + 1));
        } else {
            $dbInterface = ucfirst(strtolower($dbAdapter));
            $dbType      = null;
        }

        if (($dbInterface == 'Sqlite') || ($dbType == 'sqlite')) {
            $dbName     = $console->prompt('DB Name: ');
            $sqliteFile = $dbName . ((strpos($dbName, '.sqlite') === false) ? '.sqlite' : '');
            $sqliteFile = str_replace(' ', '_', $sqliteFile);
            chmod($location . '/database', 0755);
            touch($location . '/database/' . $sqliteFile);
            chmod($location . '/database/' . $sqliteFile, 0777);
            $realDbName = "__DIR__ . '/../../database/" . $sqliteFile . "'";
            $console->write();
        } else {
            $dbCheck = false;
            while ($dbCheck !== true) {
                $dbName     = $console->prompt('DB Name: ');
                $dbUser     = $console->prompt('DB User: ');
                $dbPass     = $console->prompt('DB Password: ');
                $dbHost     = $console->prompt('DB Host: [localhost] ');
                $realDbName = "'" . $dbName . "'";

                if ($dbHost == '') {
                    $dbHost = 'localhost';
                }

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

        /*
        if (!file_exists($location . DIRECTORY_SEPARATOR . '/app/config/database.php')) {
            copy(
                __DIR__ . '/../../config/templates/api/app/config/database.php',
                $location . DIRECTORY_SEPARATOR . '/app/config/database.php'
            );
        }
        */

        // Write web config file
        $dbConfigContents = file_get_contents(__DIR__ . '/../../config/templates/api/app/config/database.php');
        $newDbConfig      = substr($dbConfigContents, strpos($dbConfigContents, "'default'"));
        $newDbConfig      = substr($newDbConfig, 0, (strpos($newDbConfig, ']') + 1));
        $newDbConfig      = str_replace("'default'", "'" . $database . "'", $newDbConfig);

        $newDbConfig = str_replace(
            [
                "'adapter'  => '',",
                "'database' => '',",
                "'username' => '',",
                "'password' => '',",
                "'host'     => '',",
                "'type'     => ''"
            ],
            [
                "'adapter'  => '" . strtolower($dbInterface) . "',",
                "'database' => " . $realDbName . ",",
                "'username' => '" . $dbUser . "',",
                "'password' => '" . $dbPass . "',",
                "'host'     => '" . $dbHost . "',",
                "'type'     => '" . $dbType . "'"
            ], $newDbConfig
        );

        if (!file_exists($location . DIRECTORY_SEPARATOR . '/app/config/database.php')) {

        }

        /*
            $dbConfig = str_replace(
                ']' . PHP_EOL . '];', '],' . PHP_EOL . '    ' . $newDbConfig . PHP_EOL . '];', $dbConfigContents
            );

            $dbConfig = str_replace(
                [
                    "'adapter'  => '',",
                    "'database' => '',",
                    "'username' => '',",
                    "'password' => '',",
                    "'host'     => '',",
                    "'type'     => ''"
                ],
                [
                    "'adapter'  => '" . strtolower($dbInterface) . "',",
                    "'database' => " . $realDbName . ",",
                    "'username' => '" . $dbUser . "',",
                    "'password' => '" . $dbPass . "',",
                    "'host'     => '" . $dbHost . "',",
                    "'type'     => '" . $dbType . "'"
                ], $dbConfigContents
            );


        file_put_contents($location . DIRECTORY_SEPARATOR . '/app/config/database.php', $dbConfig);
*/
        return $this;
    }

    /**
     * Test database connection
     *
     * @param  array  $database
     * @param  string $dbName
     * @return string|boolean
     */
    public function test(array $database, $dbName = 'default')
    {
        return Db::check($database['adapter'], [
            'database' => $database['database'],
            'username' => $database['username'],
            'password' => $database['password'],
            'host'     => $database['host'],
            'type'     => $database['type'],
        ]);
    }

    /**
     * Seed database
     *
     * @param  Console $console
     * @param  string  $location
     * @param  string  $database
     * @return Database
     */
    public function seed(Console $console, $location, $database = 'default')
    {
        if (!file_exists($location . '/app/config/database.php')) {
            $console->write($console->colorize(
                'The database configuration was not found.', Console::BOLD_RED
            ));
        } else {
            $db    = $this->createAdapter(include $location . '/app/config/database.php');
            $dir   = new Dir($location . '/database/seeds', ['filesOnly' => true]);
            $seeds = $dir->getFiles();

            sort($seeds);

            $console->write('Running database seeds...');

            foreach ($seeds as $seed) {
                if (stripos($seed, '.sql') !== false) {
                    $this->install(
                        include $location . '/app/config/database.php',
                        $location . '/database/seeds/' . $seed
                    );
                } else if (stripos($seed, '.php') !== false) {
                    include $location . '/database/seeds/' . $seed;
                    $class  = str_replace('.php', '', $seed);
                    $dbSeed = new $class();
                    if ($dbSeed instanceof SeederInterface) {
                        $dbSeed->run($db);
                    }
                }
            }

            $console->write();
            $console->write('Done!');
        }

        return $this;
    }

    /**
     * Create database adapter
     *
     * @param  array $database
     * @return Adapter\AbstractAdapter
     */
    public function createAdapter(array $database)
    {
        $adapter  = $database['adapter'];
        $options  = [
            'database' => $database['database'],
            'username' => $database['username'],
            'password' => $database['password'],
            'host'     => $database['host'],
            'type'     => $database['type']
        ];

        return Db::connect($adapter, $options);
    }

    /**
     * Install SQL
     *
     * @param  array  $database
     * @param  string $sqlFile
     * @return Database
     */
    public function install(array $database, $sqlFile)
    {
        $adapter  = $database['adapter'];
        $options  = [
            'database' => $database['database'],
            'username' => $database['username'],
            'password' => $database['password'],
            'host'     => $database['host'],
            'type'     => $database['type']
        ];

        Db::executeSqlFile($sqlFile, $adapter, $options);

        return $this;
    }

    /**
     * Reset database
     *
     * @param Console $console
     * @param string  $location
     * @param string  $database
     * @return Database
     */
    public function reset(Console $console, $location, $database = 'default')
    {
        if (!file_exists($location . '/app/config/database.php')) {
            $console->write($console->colorize(
                'The database configuration was not found.', Console::BOLD_RED
            ));
        } else {
            $console->write('Resetting database data...');

            $db     = $this->createAdapter(include $location . '/app/config/database.php');
            $schema = $db->createSchema();
            $tables = $db->getTables();

            if (($db instanceof \Pop\Db\Adapter\Mysql) ||
                (($db instanceof \Pop\Db\Adapter\Pdo) && ($db->getType() == 'mysql'))) {
                $db->query('SET foreign_key_checks = 0');
                foreach ($tables as $table) {
                    $schema->truncate($table);
                    $db->query($schema);
                }
                $db->query('SET foreign_key_checks = 1');
            } else if (($db instanceof \Pop\Db\Adapter\Pgsql) ||
                (($db instanceof \Pop\Db\Adapter\Pdo) && ($db->getType() == 'pgsql'))) {
                foreach ($tables as $table) {
                    $schema->truncate($table)->cascade();
                    $db->query($schema);
                }
            } else {
                foreach ($tables as $table) {
                    $schema->truncate($table);
                    $db->query($schema);
                }
            }

            $dir   = new Dir($location . '/database/seeds', ['filesOnly' => true]);
            $seeds = $dir->getFiles();

            sort($seeds);

            $console->write('Re-running database seeds...');

            foreach ($seeds as $seed) {
                if (stripos($seed, '.sql') !== false) {
                    $this->install(
                        include $location . '/app/config/database.php',
                        $location . '/database/seeds/' . $seed
                    );
                } else if (stripos($seed, '.php') !== false) {
                    include $location . '/database/seeds/' . $seed;
                    $class  = str_replace('.php', '', $seed);
                    $dbSeed = new $class();
                    if ($dbSeed instanceof SeederInterface) {
                        $dbSeed->run($db);
                    }
                }
            }

            $console->write();
            $console->write('Done!');
        }

        return $this;
    }

    /**
     * Clear database
     *
     * @param Console $console
     * @param string  $location
     * @param string  $database
     * @return Database
     */
    public function clear(Console $console, $location, $database = 'default')
    {
        if (!file_exists($location . '/app/config/database.php')) {
            $console->write($console->colorize(
                'The database configuration was not found.', Console::BOLD_RED
            ));
        } else {
            $console->write('Clearing database data...');

            $db     = $this->createAdapter(include $location . '/app/config/database.php');
            $schema = $db->createSchema();
            $tables = $db->getTables();

            if (($db instanceof \Pop\Db\Adapter\Mysql) ||
                (($db instanceof \Pop\Db\Adapter\Pdo) && ($db->getType() == 'mysql'))) {
                $db->query('SET foreign_key_checks = 0');
                foreach ($tables as $table) {
                    $schema->drop($table);
                    $db->query($schema);
                }
                $db->query('SET foreign_key_checks = 1');
            } else if (($db instanceof \Pop\Db\Adapter\Pgsql) ||
                (($db instanceof \Pop\Db\Adapter\Pdo) && ($db->getType() == 'pgsql'))) {
                foreach ($tables as $table) {
                    $schema->drop($table)->cascade();
                    $db->query($schema);
                }
            } else {
                foreach ($tables as $table) {
                    $schema->drop($table);
                    $db->query($schema);
                }
            }

            if (file_exists($location . '/database/migrations/.current')) {
                unlink($location . '/database/migrations/.current');
            }

            $console->write();
            $console->write('Done!');
        }

        return $this;
    }

}