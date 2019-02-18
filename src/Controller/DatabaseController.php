<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/pop-bootstrap
 * @author     Nick Sagona, III <nick@nolainteractive.com>
 * @copyright  Copyright (c) 2012-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Kettle\Controller;

use Pop\Console\Console;
use Pop\Db\Sql\Seeder\SeederInterface;
use Pop\Dir\Dir;
use Pop\Kettle\Model;

/**
 * Console database controller class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <nick@nolainteractive.com>
 * @copyright  Copyright (c) 2012-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    1.0.0
 */
class DatabaseController extends AbstractController
{

    /**
     * Config command
     *
     * @return void
     */
    public function config()
    {
        $dbModel = new Model\Database();
        $dbModel->configure($this->console, getcwd());
    }

    /**
     * Test command
     *
     * @return void
     */
    public function test()
    {
        $location = getcwd();
        $dbModel  = new Model\Database();

        if (!file_exists($location . '/app/config/database.php')) {
            $this->console->write($this->console->colorize(
                'The database configuration was not found.', Console::BOLD_RED
            ));
        } else {
            $result  = $dbModel->test(include $location . '/app/config/database.php');
            if (null !== $result) {
                $this->console->write($this->console->colorize($result, Console::BOLD_RED));
            } else {
                $this->console->write($this->console->colorize(
                    'Database configuration test passed.', Console::BOLD_GREEN
                ));
            }
        }
    }

    /**
     * Seed command
     *
     * @return void
     */
    public function seed()
    {
        $location = getcwd();
        $dbModel  = new Model\Database();

        if (!file_exists($location . '/app/config/database.php')) {
            $this->console->write($this->console->colorize(
                'The database configuration was not found.', Console::BOLD_RED
            ));
        } else {
            $db    = $dbModel->createAdapter(include $location . '/app/config/database.php');
            $dir   = new Dir($location . '/database/seeds', ['filesOnly' => true]);
            $seeds = $dir->getFiles();

            sort($seeds);

            $this->console->write('Running database seeds...');

            foreach ($seeds as $seed) {
                if (stripos($seed, '.sql') !== false) {
                    $dbModel->install(
                        include $location . '/app/config/database.php',
                        $location . '/database/seeds/' . $seed
                    );
                } else if (stripos('.php', $seed) !== false) {
                    include $location . '/database/seeds/' . $seed;
                    $class  = str_replace('.php', '', $seed);
                    $dbSeed = new $class();
                    if ($dbSeed instanceof SeederInterface) {
                        $dbSeed->run($db);
                    }
                }
            }

            $this->console->write();
            $this->console->write('Done!');
        }
    }

    /**
     * Reset command
     *
     * @return void
     */
    public function reset()
    {
        $location = getcwd();
        $dbModel  = new Model\Database();

        if (!file_exists($location . '/app/config/database.php')) {
            $this->console->write($this->console->colorize(
                'The database configuration was not found.', Console::BOLD_RED
            ));
        } else {
            $this->console->write('Resetting database data...');

            $db     = $dbModel->createAdapter(include $location . '/app/config/database.php');
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

            $this->console->write('Re-running database seeds...');

            foreach ($seeds as $seed) {
                if (stripos($seed, '.sql') !== false) {
                    $dbModel->install(
                        include $location . '/app/config/database.php',
                        $location . '/database/seeds/' . $seed
                    );
                } else if (stripos('.php', $seed) !== false) {
                    include $location . '/database/seeds/' . $seed;
                    $class  = str_replace('.php', '', $seed);
                    $dbSeed = new $class();
                    if ($dbSeed instanceof SeederInterface) {
                        $dbSeed->run($db);
                    }
                }
            }

            $this->console->write();
            $this->console->write('Done!');
        }
    }

    /**
     * Clear command
     *
     * @return void
     */
    public function clear()
    {
        $location = getcwd();
        $dbModel  = new Model\Database();

        if (!file_exists($location . '/app/config/database.php')) {
            $this->console->write($this->console->colorize(
                'The database configuration was not found.', Console::BOLD_RED
            ));
        } else {
            $this->console->write('Clearing database data...');

            $db     = $dbModel->createAdapter(include $location . '/app/config/database.php');
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

            $this->console->write();
            $this->console->write('Done!');
        }
    }

}