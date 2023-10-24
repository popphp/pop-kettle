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
class DatabaseController extends AbstractController
{

    /**
     * Install command
     *
     * @param  string $database
     * @return void
     */
    public function install(string $database = 'default'): void
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
     * @param  string $database
     * @return void
     */
    public function config(string $database = 'default'): void
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
     * @param  string $database
     * @return void
     */
    public function test(string $database = 'default'): void
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
                $dbConfig = include $location . '/app/config/database.php';
                if (!isset($dbConfig[$db])) {
                    $this->console->write($this->console->colorize(
                        "The database configuration was not found for '" . $db . "'.", Console::BOLD_RED
                    ));
                } else {
                    $result = $dbModel->test($dbConfig[$db]);
                    if ($result !== true) {
                        $this->console->write($this->console->colorize($result, Console::BOLD_RED));
                    } else {
                        $this->console->write($this->console->colorize(
                            "Database configuration test for '" . $db . "' passed.", Console::BOLD_GREEN
                        ));
                    }
                }
            }
        }
    }

    /**
     * Create seed command
     *
     * @param  string $class
     * @param  string $database
     * @return void
     */
    public function createSeed(string $class, string $database = 'default'): void
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

            if (str_ends_with(strtolower($class), '.sql')) {
                touch($location . '/database/seeds/' . $db .'/' . $class);
                $this->console->write("Database seed file '" . $class . "' created for '" . $db . "'.");
            } else {
                $classContents = str_replace(
                    'DatabaseSeeder', $class, file_get_contents(__DIR__ . '/../../config/templates/db/DatabaseSeeder.php')
                );

                file_put_contents($location . '/database/seeds/' . $db .'/' . $class . '.php', $classContents);
                $this->console->write("Database seed class '" . $class . "' created for '" . $db . "'.");
            }
        }
    }

    /**
     * Seed command
     *
     * @param  string $database
     * @return void
     */
    public function seed(string $database = 'default'): void
    {
        if ($database === null) {
            $database = 'default';
        }

        $dbModel  = new Model\Database();
        $dbModel->seed($this->console, getcwd(), $database);
    }

    /**
     * Reset command
     *
     * @param  string $database
     * @return void
     */
    public function reset(string $database = 'default'): void
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
     * @param  string $database
     * @return void
     */
    public function clear(string $database = 'default'): void
    {
        if ($database === null) {
            $database = 'default';
        }

        $dbModel  = new Model\Database();
        $dbModel->clear($this->console, getcwd(), $database);
    }

}
