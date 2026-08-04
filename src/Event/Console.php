<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/pop-bootstrap
 * @author     Nick Sagona, III <nick@noladev.com>
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
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
class Console
{

    /**
     * Production check omit commands
     * @var array
     */
    protected static array $omitCommands = ['app:env', 'app:status', 'help', 'version'];

    /**
     * Display maintenance alert
     *
     * @param  ?\Pop\Console\Console $console
     * @return void
     */
    public static function maintenanceDisplay(?\Pop\Console\Console $console = null): void
    {
        $console     = $console ?? new \Pop\Console\Console();
        $routeString = App::get()->router()->getRouteMatch()->getRouteString();

        if (App::isDown() && ($routeString != 'app:up')) {
            $console->alertInfo('Application in Maintenance', 40);
        }
    }

    /**
     * Display production alert and prompt
     *
     * @param  ?\Pop\Console\Console $console
     * @return void
     */
    public static function productionDisplay(?\Pop\Console\Console $console = null): void
    {
        $console     = $console ?? new \Pop\Console\Console();
        $routeString = App::get()->router()->getRouteMatch()->getRouteString();

        if ((App::isProduction()) && !in_array($routeString, self::$omitCommands)) {
            $console->alertWarning('Application in Production', 40);
            $console->confirm('Are you sure you want to run this command?');
        }
    }

}
