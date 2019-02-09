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
use Pop\Kettle\Model;

/**
 * Console database controller class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <nick@nolainteractive.com>
 * @copyright  Copyright (c) 2012-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    0.0.1-alpha
 */
class DatabaseController extends AbstractController
{

    /**
     * Init command
     *
     * @return void
     */
    public function init()
    {
        $this->console->write('DB init!');
    }

    /**
     * Test command
     *
     * @return void
     */
    public function test()
    {
        $location = getcwd();

        if (file_exists($location . '/app/config/database.php')) {
            $dbModel = new Model\Database();
            $result  = $dbModel->test(include $location . '/app/config/database.php');
            if (null !== $result) {
                $this->console->write($this->console->colorize($result, Console::BOLD_RED));
            } else {
                $this->console->write($this->console->colorize(
                    'Database configuration test passed.', Console::BOLD_GREEN
                ));
            }
        } else {
            $this->console->write($this->console->colorize(
                'The database configuration was not found.', Console::BOLD_RED
            ));
        }
    }

    /**
     * Seed command
     *
     * @return void
     */
    public function seed()
    {
        $this->console->write('DB seed!');
    }

    /**
     * Reset command
     *
     * @return void
     */
    public function reset()
    {
        $this->console->write('DB reset!');
    }

    /**
     * Clear command
     *
     * @return void
     */
    public function clear()
    {
        $this->console->write('DB clear!');
    }

}