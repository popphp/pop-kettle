<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/pop-bootstrap
 * @author     Nick Sagona, III <nick@nolainteractive.com>
 * @copyright  Copyright (c) 2012-2025 NOLA Interactive, LLC.
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Kettle\Event;

use Pop\App;
use Pop\Kettle\Model\Application;

/**
 * Console event class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    http://www.popphp.org/license     New BSD License
 * @version    2.3.2
 */
class Console
{

    /**
     * Production check omit commands
     * @var array
     */
    protected static array $omitCommands = ['app:env', 'app:status', 'help', 'version'];

    /**
     * Display console header
     *
     * @return void
     */
    public static function header(): void
    {
        $console     = new \Pop\Console\Console();
        $routeString = App::get()->router()->getRouteMatch()->getRouteString();

        echo PHP_EOL . $console->header('Pop Kettle', '=', null, 'left', true, true) . PHP_EOL;

        if (App::isDown() && ($routeString != 'app:up')) {
            $console->alertInfo('Application in Maintenance', 40);
        }

        if ((App::isProduction()) && !in_array($routeString, self::$omitCommands)) {
            $console->alertWarning('Application in Production', 40);
            $console->confirm('Are you sure you want to run this command?');
        }
    }

    /**
     * Display console footer
     *
     * @return void
     */
    public static function footer(): void
    {
        echo PHP_EOL;
    }

}
