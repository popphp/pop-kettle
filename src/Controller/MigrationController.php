<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/pop-bootstrap
 * @author     Nick Sagona, III <nick@nolainteractive.com>
 * @copyright  Copyright (c) 2012-2021 NOLA Interactive, LLC. (http://www.nolainteractive.com)
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
 * @copyright  Copyright (c) 2012-2021 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    1.6.1
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
        $location  = getcwd();

        if (null === $database) {
            $databases = ['default'];
        } else if ($database == 'all') {
            $databases = array_filter(scandir($location . '/database/migrations'), function($value) {
                return (($value != '.') && ($value != '..'));
            });
        } else {
            $databases = [$database];
        }

        foreach ($databases as $db) {
            if (!file_exists($location . '/database/migrations/' . $db)) {
                mkdir($location . '/database/migrations/' . $db);
            }

            $migrationClass = $class . uniqid();
            Migrator::create($migrationClass, $location . '/database/migrations/' . $db);
            $this->console->write("Migration class '" . $migrationClass . "' created for '" . $db . "'.");
        }
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
        $location = getcwd();
        $dbModel  = new Model\Database();

        if (null === $database) {
            $databases = ['default'];
        } else if ($database == 'all') {
            $databases = array_filter(scandir($location . '/database/migrations'), function($value) {
                return (($value != '.') && ($value != '..'));
            });
        } else {
            $databases = [$database];
        }

        if (null === $steps) {
            $steps = 1;
        }

        if (!file_exists($location . '/app/config/database.php')) {
            $this->console->write($this->console->colorize(
                'The database configuration was not found.', Console::BOLD_RED
            ));
        } else {
            foreach ($databases as $db) {
                if (!file_exists($location . '/database/migrations/' . $db)) {
                    $this->console->write($this->console->colorize(
                        "The database migration folder was not found for '" . $db . "'.", Console::BOLD_RED
                    ));
                } else {
                    $this->console->write("Running database migration for '" . $db . "'...");

                    $dbConfig = include $location . '/app/config/database.php';
                    if (!isset($dbConfig[$db])) {
                        $this->console->write($this->console->colorize(
                            "The database configuration was not found for '" . $db . "'.", Console::BOLD_RED
                        ));
                    } else {
                        $dbAdapter = $dbModel->createAdapter($dbConfig[$db]);
                        $migrator  = new Migrator($dbAdapter, $location . '/database/migrations/' . $db);
                        $migrator->run($steps);
                        $this->console->write();
                        $this->console->write('Done!');
                    }
                }
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
        $location = getcwd();
        $dbModel  = new Model\Database();

        if (null === $database) {
            $databases = ['default'];
        } else if ($database == 'all') {
            $databases = array_filter(scandir($location . '/database/migrations'), function($value) {
                return (($value != '.') && ($value != '..'));
            });
        } else {
            $databases = [$database];
        }

        if (null === $steps) {
            $steps = 1;
        }

        if (!file_exists($location . '/app/config/database.php')) {
            $this->console->write($this->console->colorize(
                'The database configuration was not found.', Console::BOLD_RED
            ));
        } else {
            foreach ($databases as $db) {
                if (!file_exists($location . '/database/migrations/' . $db)) {
                    $this->console->write($this->console->colorize(
                        "The database migration folder was not found for '" . $db . "'.", Console::BOLD_RED
                    ));
                } else {
                    $this->console->write("Rolling back database migration for '" . $db . "'...");

                    $dbConfig = include $location . '/app/config/database.php';
                    if (!isset($dbConfig[$db])) {
                        $this->console->write($this->console->colorize(
                            "The database configuration was not found for '" . $db . "'.", Console::BOLD_RED
                        ));
                    } else {
                        $dbAdapter = $dbModel->createAdapter($dbConfig[$db]);
                        $migrator  = new Migrator($dbAdapter, $location . '/database/migrations/' . $db);
                        $migrator->rollback($steps);
                        $this->console->write();
                        $this->console->write('Done!');
                    }
                }
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
        $location = getcwd();
        $dbModel  = new Model\Database();

        if (null === $database) {
            $databases = ['default'];
        } else if ($database == 'all') {
            $databases = array_filter(scandir($location . '/database/migrations'), function($value) {
                return (($value != '.') && ($value != '..'));
            });
        } else {
            $databases = [$database];
        }

        if (!file_exists($location . '/app/config/database.php')) {
            $this->console->write($this->console->colorize(
                'The database configuration was not found.', Console::BOLD_RED
            ));
        } else {
            foreach ($databases as $db) {
                if (!file_exists($location . '/database/migrations/' . $db)) {
                    $this->console->write($this->console->colorize(
                        "The database migration folder was not found for '" . $db . "'.", Console::BOLD_RED
                    ));
                } else {
                    $this->console->write("Resetting the database for '" . $db . "'...");

                    $dbConfig = include $location . '/app/config/database.php';
                    if (!isset($dbConfig[$db])) {
                        $this->console->write($this->console->colorize(
                            "The database configuration was not found for '" . $db . "'.", Console::BOLD_RED
                        ));
                    } else {
                        $dbAdapter = $dbModel->createAdapter($dbConfig[$db]);
                        $migrator  = new Migrator($dbAdapter, $location . '/database/migrations/' . $db);
                        $migrator->rollbackAll();
                        $this->console->write();
                        $this->console->write('Done!');
                    }
                }
            }
        }
    }

}