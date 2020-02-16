<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/pop-bootstrap
 * @author     Nick Sagona, III <nick@nolainteractive.com>
 * @copyright  Copyright (c) 2012-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Kettle\Controller;

use Pop\Console\Console;
use Pop\Db\Sql\Migrator;
use Pop\Kettle\Model;

/**
 * Console database controller class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <nick@nolainteractive.com>
 * @copyright  Copyright (c) 2012-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    1.3.0
 */
class MigrationController extends AbstractController
{

    /**
     * Create command
     *
     * @param  string $class
     * @param  string $database
     * @return void
     */
    public function create($class, $database = 'default')
    {
        if (null === $database) {
            $database = 'default';
        }

        $location = getcwd();

        if (!file_exists($location . '/database/migrations/' . $database)) {
            mkdir($location . '/database/migrations/' . $database);
        }

        $class .= uniqid();
        Migrator::create($class, $location . '/database/migrations/' . $database);
        $this->console->write("Migration class '" . $class . "' created for '" . $database . "'.");
    }

    /**
     * Run command
     *
     * @param  int    $steps
     * @param  string $database
     * @return void
     */
    public function run($steps = 1, $database = 'default')
    {
        if (null === $steps) {
            $steps = 1;
        }
        if (null === $database) {
            $database = 'default';
        }

        $location = getcwd();
        $dbModel  = new Model\Database();

        if (!file_exists($location . '/app/config/database.php')) {
            $this->console->write($this->console->colorize(
                'The database configuration was not found.', Console::BOLD_RED
            ));
        } else if (!file_exists($location . '/database/migrations/' . $database)) {
            $this->console->write($this->console->colorize(
                "The database migration folder was not found for '" . $database . "'.", Console::BOLD_RED
            ));
        } else {
            $this->console->write("Running database migration for '" . $database . "'...");

            $dbConfig = include $location . '/app/config/database.php';
            if (!isset($dbConfig[$database])) {
                $this->console->write($this->console->colorize(
                    "The database configuration was not found for '" . $database . "'.", Console::BOLD_RED
                ));
            } else {
                $db = $dbModel->createAdapter($dbConfig[$database]);
                $migrator = new Migrator($db, $location . '/database/migrations/' . $database);
                $migrator->run($steps);
                $this->console->write();
                $this->console->write('Done!');
            }
        }
    }

    /**
     * Rollback command
     *
     * @param  int    $steps
     * @param  string $database
     * @return void
     */
    public function rollback($steps = 1, $database = 'default')
    {
        if (null === $steps) {
            $steps = 1;
        }
        if (null === $database) {
            $database = 'default';
        }

        $location = getcwd();
        $dbModel  = new Model\Database();

        if (!file_exists($location . '/app/config/database.php')) {
            $this->console->write($this->console->colorize(
                'The database configuration was not found.', Console::BOLD_RED
            ));
        } else if (!file_exists($location . '/database/migrations/' . $database)) {
            $this->console->write($this->console->colorize(
                "The database migration folder was not found for '" . $database . "'.", Console::BOLD_RED
            ));
        } else {
            $this->console->write("Rolling back database migration for '" . $database . "'...");

            $dbConfig = include $location . '/app/config/database.php';
            if (!isset($dbConfig[$database])) {
                $this->console->write($this->console->colorize(
                    "The database configuration was not found for '" . $database . "'.", Console::BOLD_RED
                ));
            } else {
                $db       = $dbModel->createAdapter($dbConfig[$database]);
                $migrator = new Migrator($db, $location . '/database/migrations/' . $database);
                $migrator->rollback($steps);
                $this->console->write();
                $this->console->write('Done!');
            }
        }
    }

    /**
     * Reset command
     *
     * @param  string $database
     * @return void
     */
    public function reset($database = 'default')
    {
        if (null === $database) {
            $database = 'default';
        }

        $location = getcwd();
        $dbModel  = new Model\Database();

        if (!file_exists($location . '/app/config/database.php')) {
            $this->console->write($this->console->colorize(
                'The database configuration was not found.', Console::BOLD_RED
            ));
        } else if (!file_exists($location . '/database/migrations/' . $database)) {
            $this->console->write($this->console->colorize(
                "The database migration folder was not found for '" . $database . "'.", Console::BOLD_RED
            ));
        } else {
            $this->console->write("Resetting the database for '" . $database . "'...");

            $dbConfig = include $location . '/app/config/database.php';
            if (!isset($dbConfig[$database])) {
                $this->console->write($this->console->colorize(
                    "The database configuration was not found for '" . $database . "'.", Console::BOLD_RED
                ));
            } else {
                $db       = $dbModel->createAdapter($dbConfig[$database]);
                $migrator = new Migrator($db, $location . '/database/migrations/' . $database);
                $migrator->rollbackAll();
                $this->console->write();
                $this->console->write('Done!');
            }
        }
    }

}