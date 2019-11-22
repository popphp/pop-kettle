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
 * @version    1.1.0
 */
class MigrationController extends AbstractController
{

    /**
     * Create command
     *
     * @param  string $class
     * @return void
     */
    public function create($class)
    {
        $class .= uniqid();
        Migrator::create($class, getcwd() . '/database/migrations');
        $this->console->write('Migration class created (' . $class . ')');
    }

    /**
     * Run command
     *
     * @param  int  $steps
     * @return void
     */
    public function run($steps = 1)
    {
        if (null === $steps) {
            $steps = 1;
        }

        $location = getcwd();
        $dbModel  = new Model\Database();

        if (!file_exists($location . '/app/config/database.php')) {
            $this->console->write($this->console->colorize(
                'The database configuration was not found.', Console::BOLD_RED
            ));
        } else {
            $this->console->write('Running database migration...');

            $db       = $dbModel->createAdapter(include $location . '/app/config/database.php');
            $migrator = new Migrator($db, $location . '/database/migrations');
            $migrator->run($steps);
            $this->console->write();
            $this->console->write('Done!');
        }
    }

    /**
     * Rollback command
     *
     * @param  int  $steps
     * @return void
     */
    public function rollback($steps = 1)
    {
        if (null === $steps) {
            $steps = 1;
        }

        $location = getcwd();
        $dbModel  = new Model\Database();

        if (!file_exists($location . '/app/config/database.php')) {
            $this->console->write($this->console->colorize(
                'The database configuration was not found.', Console::BOLD_RED
            ));
        } else {
            $this->console->write('Rolling back database migration...');

            $db       = $dbModel->createAdapter(include $location . '/app/config/database.php');
            $migrator = new Migrator($db, $location . '/database/migrations');
            $migrator->rollback($steps);
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
            $this->console->write('Resetting the database...');

            $db       = $dbModel->createAdapter(include $location . '/app/config/database.php');
            $migrator = new Migrator($db, $location . '/database/migrations');
            $migrator->rollbackAll();
            $this->console->write();
            $this->console->write('Done!');
        }
    }

}