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
namespace Pop\Kettle\Event;

/**
 * Console event class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    1.1.0
 */
class Console
{

    /**
     * Display console header
     *
     * @return void
     */
    public static function header()
    {
        echo PHP_EOL . '    Pop Kettle' . PHP_EOL;
        echo '    ==========' . PHP_EOL . PHP_EOL;
    }

    /**
     * Display console footer
     *
     * @return void
     */
    public static function footer()
    {
        echo PHP_EOL;
    }

}