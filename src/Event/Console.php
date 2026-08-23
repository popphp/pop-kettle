<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/pop-bootstrap
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
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
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
class Console
{

    /**
     * Production check omit commands
     * @var array
     */
    protected static array $omitCommands = [
        'pop:env', 'pop:status', 'help', 'version', 'queue:jobs', 'queue:tasks', 'queue:work', 'queue:scheduler'
    ];

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

        if (App::isDown() && ($routeString != 'pop:up')) {
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
        $command     = explode(' ', $routeString, 2)[0];

        if ((App::isProduction()) && !in_array($command, self::$omitCommands)) {
            $console->alertWarning('Application in Production', 40);
            $console->confirm('Are you sure you want to run this command?');
        }
    }

}
