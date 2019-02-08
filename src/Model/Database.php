<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2018 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Kettle\Model;

use Pop\Console\Console;
use Pop\Model\AbstractModel;

/**
 * Database model class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2018 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    0.0.1-alpha
 */
class Database extends AbstractModel
{

    /**
     * Configure database
     *
     * @param Console $console
     */
    public function configureDb(Console $console, $location)
    {
        $console->write();
        // Configure application database
        $dbName     = '';
        $dbUser     = '';
        $dbPass     = '';
        $dbHost     = '';
        $realDbName = '';
        $dbAdapters = [];
        $pdoDrivers = (class_exists('Pdo', false)) ? \PDO::getAvailableDrivers() : [];

        if (class_exists('mysqli', false)) {
            $dbAdapters['mysql'] = 'Mysql';
        }
        if (in_array('mysql', $pdoDrivers)) {
            $dbAdapters['pdo_mysql'] = 'PDO Mysql';
        }

        $adapters  = array_keys($dbAdapters);
        $dbChoices = [];
        $i         = 1;

        foreach ($dbAdapters as $a) {
            $console->write($i . ': ' . $a);
            $dbChoices[] = $i;
            $i++;
        }

        $console->write();
        $adapter = $console->prompt('Please select one of the above database adapters: ', $dbChoices);
        $console->write();

        // If PDO
        if (stripos($adapters[$adapter - 1], 'pdo') !== false) {
            $dbInterface = 'Pdo';
            $dbType      = 'mysql';
        } else {
            $dbInterface = ucfirst(strtolower($adapters[$adapter - 1]));
            $dbType      = null;
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
            ], file_get_contents(__DIR__ . '/../../../app/config/database.php')
        );

        file_put_contents(__DIR__ . '/../../../app/config/database.php', $dbConfig);


    }

}