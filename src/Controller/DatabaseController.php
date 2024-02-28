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

use Pop\Console\Color;
use Pop\Kettle\Model;
use Pop\Db\Sql\Seeder;

/**
 * Console database controller class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <nick@nolainteractive.com>
 * @copyright  Copyright (c) 2012-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    2.3.0
 */
class DatabaseController extends AbstractController
{

    /**
     * Install command
     *
     * @param  ?string $database
     * @return void
     */
    public function install(?string $database = 'default'): void
    {
        if ($database === null) {
            $database = 'default';
        }

        $location = getcwd();
        $dbModel  = new Model\Database();
        $dbModel->configure($this->console, $location, $database)
            ->seed($this->console, $location, $database);
    }

    /**
     * Config command
     *
     * @param  ?string $database
     * @return void
     */
    public function config(?string $database = 'default'): void
    {
        if ($database === null) {
            $database = 'default';
        }

        $dbModel = new Model\Database();
        $dbModel->configure($this->console, getcwd(), $database);
    }

    /**
     * Test command
     *
     * @param  ?string $database
     * @return void
     */
    public function test(?string $database = 'default'): void
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
                'The database configuration was not found.', Color::BOLD_RED
            ));
        } else {
            foreach ($databases as $db) {
                $dbConfig = include $location . '/app/config/database.php';
                if (!isset($dbConfig[$db])) {
                    $this->console->write($this->console->colorize(
                        "The database configuration was not found for '" . $db . "'.", Color::BOLD_RED
                    ));
                } else {
                    $result = $dbModel->test($dbConfig[$db]);
                    if ($result !== true) {
                        $this->console->write($this->console->colorize($result, Color::BOLD_RED));
                    } else {
                        $this->console->write($this->console->colorize(
                            "Database configuration test for '" . $db . "' passed.", Color::BOLD_GREEN
                        ));
                    }
                }
            }
        }
    }

    /**
     * Create seed command
     *
     * @param  string  $seed
     * @param  ?string $database
     * @return void
     */
    public function createSeed(string $seed, ?string $database = 'default'): void
    {
        $location = getcwd();

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
            if (!file_exists($location . '/database/seeds/' . $db)) {
                mkdir($location . '/database/seeds/' . $db);
            }

            if (str_ends_with(strtolower($seed), '.sql')) {
                touch($location . '/database/seeds/' . $db .'/' . $seed);
                file_put_contents($location . '/database/seeds/' . $db .'/' . $seed, "-- Seed data for '" . $seed . "'" . PHP_EOL . PHP_EOL);
                $this->console->write("Database seed file '" . $seed . "' created for '" . $db . "'.");
            } else {
                Seeder::create($seed, $location . '/database/seeds/' . $db);
                $this->console->write("Database seed class '" . $seed . "' created for '" . $db . "'.");
            }
        }
    }

    /**
     * Seed command
     *
     * @param  ?string $database
     * @return void
     */
    public function seed(?string $database = 'default'): void
    {
        if ($database === null) {
            $database = 'default';
        }

        $dbModel  = new Model\Database();
        $dbModel->seed($this->console, getcwd(), $database);
    }

    /**
     * Export command
     *
     * @param  ?string $database
     * @return void
     */
    public function export(?string $database = 'default'): void
    {
        if ($database === null) {
            $database = 'default';
        }

        $disabled = explode(',', ini_get('disable_functions'));

        if (in_array('exec', $disabled)) {
            $this->console->write($this->console->colorize(
                "The 'exec' function is not enabled.", Color::BOLD_RED
            ));
        } else {
            $output = null;
            exec('which mysqldump', $output);

            if (empty($output)) {
                $this->console->write($this->console->colorize(
                    "The 'mysqldump' program was not found.", Color::BOLD_RED
                ));
            } else {
                $dbModel  = new Model\Database();
                $dbModel->export($this->console, getcwd(), $database);
            }
        }
    }

    /**
     * Import command
     *
     * @param  string  $importFile
     * @param  ?string $database
     * @return void
     */
    public function import(string $importFile,  ?string $database = 'default'): void
    {
        if ($database === null) {
            $database = 'default';
        }

        $location = getcwd();
        $disabled = explode(',', ini_get('disable_functions'));

        if (in_array('exec', $disabled)) {
            $this->console->write($this->console->colorize(
                "The 'exec' function is not enabled.", Color::BOLD_RED
            ));
        } else if (!file_exists($location . '/' . $importFile)) {
            $this->console->write($this->console->colorize(
                'The database import file was not found.', Color::BOLD_RED
            ));
        } else {
            $output = null;
            exec('which mysql', $output);

            if (empty($output)) {
                $this->console->write($this->console->colorize(
                    "The 'mysql' program was not found.", Color::BOLD_RED
                ));
            } else {
                $dbModel  = new Model\Database();
                $dbModel->import($this->console, getcwd(), $importFile, $database);
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
        if ($database === null) {
            $database = 'default';
        }

        $dbModel  = new Model\Database();
        $dbModel->reset($this->console, getcwd(), $database);
    }

    /**
     * Clear command
     *
     * @param  ?string $database
     * @return void
     */
    public function clear(?string $database = 'default'): void
    {
        if ($database === null) {
            $database = 'default';
        }

        $dbModel  = new Model\Database();
        $dbModel->clear($this->console, getcwd(), $database);
    }

}
