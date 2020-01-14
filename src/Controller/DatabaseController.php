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
use Pop\Kettle\Model;

/**
 * Console database controller class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <nick@nolainteractive.com>
 * @copyright  Copyright (c) 2012-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    1.1.0
 */
class DatabaseController extends AbstractController
{

    /**
     * Install command
     *
     * @param  string $database
     * @return void
     */
    public function install($database = 'default')
    {
        if (null === $database) {
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
    public function config($database = 'default')
    {
        if (null === $database) {
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
    public function test($database = 'default')
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
        } else {
            $result = $dbModel->test(include $location . '/app/config/database.php', $database);
            if ($result !== true) {
                $this->console->write($this->console->colorize($result, Console::BOLD_RED));
            } else {
                $this->console->write($this->console->colorize(
                    "Database configuration test for '" . $database . "' passed.", Console::BOLD_GREEN
                ));
            }
        }
    }

    /**
     * Create seed command
     *
     * @param  string $class
     * @return void
     */
    public function createSeed($class)
    {
        $classContents = str_replace(
            'DatabaseSeeder', $class, file_get_contents(__DIR__ . '/../../config/templates/db/DatabaseSeeder.php')
        );
        file_put_contents(getcwd() . '/database/seeds/' . $class . '.php', $classContents);
        $this->console->write('Database seed class created (' . $class . ')');
    }

    /**
     * Seed command
     *
     * @param  string $database
     * @return void
     */
    public function seed($database = 'default')
    {
        if (null === $database) {
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
    public function reset($database = 'default')
    {
        if (null === $database) {
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
    public function clear($database = 'default')
    {
        if (null === $database) {
            $database = 'default';
        }

        $dbModel  = new Model\Database();
        $dbModel->clear($this->console, getcwd(), $database);
    }

}
