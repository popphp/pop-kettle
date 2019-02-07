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
        $this->console->write('Migration create! ' . $class);
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
        $this->console->write('Migration run! ' . $steps);
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
        $this->console->write('Migration rollback! ' . $steps);
    }

    /**
     * Reset command
     *
     * @return void
     */
    public function reset()
    {
        $this->console->write('Migration reset!');
    }

}