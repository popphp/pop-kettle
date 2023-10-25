<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/pop-bootstrap
 * @author     Nick Sagona, III <nick@nolainteractive.com>
 * @copyright  Copyright (c) 2012-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
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
 * @copyright  Copyright (c) 2012-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    2.0.0
 */
class MigrationController extends AbstractController
{

    /**
     * Create command
     *
     * @param  string  $class
     * @param  ?string $database
     * @return void
     */
    public function create(string $class, ?string $database = 'default'): void
    {
        $location  = getcwd();

        if ($database === null) {
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
     * @param  ?int    $steps
     * @param  ?string $database
     * @return void
     */
    public function run(?int $steps = 1, ?string $database = 'default'): void
    {
        $location = getcwd();
        $dbModel  = new Model\Database();

        if ($database === null) {
            $databases = ['default'];
        } else if ($database == 'all') {
            $databases = array_filter(scandir($location . '/database/migrations'), function($value) {
                return (($value != '.') && ($value != '..'));
            });
        } else {
            $databases = [$database];
        }

        if ($steps === null) {
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
     * @param  ?int    $steps
     * @param  ?string $database
     * @return void
     */
    public function rollback(?int $steps = 1, ?string $database = 'default'): void
    {
        $location = getcwd();
        $dbModel  = new Model\Database();

        if ($database === null) {
            $databases = ['default'];
        } else if ($database == 'all') {
            $databases = array_filter(scandir($location . '/database/migrations'), function($value) {
                return (($value != '.') && ($value != '..'));
            });
        } else {
            $databases = [$database];
        }

        if ($steps === null) {
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
     * Point command
     *
     * @param  mixed   $id
     * @param  ?string $database
     * @return void
     */
    public function point(mixed $id = 'latest', ?string $database = 'default'): void
    {
        $location = getcwd();

        if ($id === null) {
            $id = 'latest';
        }
        if ($database === null) {
            $database = 'default';
        }

        if (!file_exists($location . '/database/migrations/' . $database)) {
            $this->console->write($this->console->colorize(
                "The database '" . $database . "' does not exist in the migration folder.", Console::BOLD_RED
            ));
        } else {
            $ids = array_map(function($value) {
                    return substr($value, 0, strpos($value, '_'));
                },
                array_values(array_filter(scandir($location . '/database/migrations/' . $database), function ($value){
                    if (($value != '.') && ($value != '..') && ($value != '.current') && ($value != '.empty') && (stripos($value, '_') !== false)) {
                        return $value;
                    }
                })
            ));

            if (!empty($ids) && is_array($ids)) {
                sort($ids, SORT_NUMERIC);
            }

            if (empty($ids)) {
                $this->console->write($this->console->colorize(
                    "No migrations for the database '" . $database . "' were found.", Console::BOLD_RED
                ));
            } else if (is_numeric($id)) {
                if (!in_array($id, $ids)) {
                    $this->console->write($this->console->colorize(
                        "The migration '" . $id . "' for the database '" . $database . "' does not exist.", Console::BOLD_RED
                    ));
                } else {
                    file_put_contents($location . '/database/migrations/' . $database . '/.current', $id);
                    $this->console->write();
                    $this->console->write('Done!');
                }
            } else if ($id == 'latest') {
                $id = end($ids);
                file_put_contents($location . '/database/migrations/' . $database . '/.current', $id);
                $this->console->write();
                $this->console->write('Done!');
            }
        }
    }

    /**
     * Reset command
     *
     * @param  ?string $database
     * @return void
     */
    public function reset(?string $database = 'default'): void
    {
        $location = getcwd();
        $dbModel  = new Model\Database();

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