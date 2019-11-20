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
use Pop\Model\AbstractModel;

/**
 * Database model class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    1.0.2
 */
class Database extends AbstractModel
{

    /**
     * Configure database
     *
     * @param Console $console
     * @param string  $location
     * @return void
     */
    public function configure(Console $console, $location)
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
            $dbCheck = 1;
            while (null !== $dbCheck) {
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

                if (null !== $dbCheck) {
                    $console->write();
                    $console->write($console->colorize(
                        'Database configuration test failed. Please try again.', Console::BOLD_RED
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

        if (!file_exists($location . DIRECTORY_SEPARATOR . '/app/config/database.php')) {
            copy(
                __DIR__ . '/../../config/templates/api/app/config/database.php',
                $location . DIRECTORY_SEPARATOR . '/app/config/database.php'
            );
        }

        // Write web config file
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
            ], file_get_contents($location . DIRECTORY_SEPARATOR . '/app/config/database.php')
        );

        file_put_contents($location . DIRECTORY_SEPARATOR . '/app/config/database.php', $dbConfig);
    }

    /**
     * Test database connection
     *
     * @param array $database
     * @return string
     */
    public function test(array $database)
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
     * @return void
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

        Db::install($sqlFile, $adapter, $options);
    }

}