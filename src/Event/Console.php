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
namespace Pop\Kettle\Event;

use Pop\App;

/**
 * Console event class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    2.1.0
 */
class Console
{

    /**
     * Display console header
     *
     * @return void
     */
    public static function header(): void
    {
        echo PHP_EOL . '    Pop Kettle' . PHP_EOL;
        echo '    ==========' . PHP_EOL . PHP_EOL;

        $routeString = App::get()->router()->getRouteMatch()->getRouteString();

        if (App::isDown()) {
            if (stripos(PHP_OS, 'win') === false) {
                $string  = "    \x1b[1;30m\x1b[106m                                  \x1b[0m" . PHP_EOL;
                $string .= "    \x1b[1;30m\x1b[106m    Application in Maintenance    \x1b[0m" . PHP_EOL;
                $string .= "    \x1b[1;30m\x1b[106m                                  \x1b[0m" . PHP_EOL;
            } else {
                $string = '    Application in Maintenance' . PHP_EOL;
            }

            echo $string;
            echo PHP_EOL;
        }

        if ((App::isProduction()) && ($routeString != 'help') && ($routeString != 'version')) {
            if (stripos(PHP_OS, 'win') === false) {
                $string  = "    \x1b[1;30m\x1b[103m                                  \x1b[0m" . PHP_EOL;
                $string .= "    \x1b[1;30m\x1b[103m    Application in Production     \x1b[0m" . PHP_EOL;
                $string .= "    \x1b[1;30m\x1b[103m                                  \x1b[0m" . PHP_EOL;
            } else {
                $string = '    Application in Production' . PHP_EOL;
            }

            echo $string;
            echo PHP_EOL;

            $confirm = (new \Pop\Console\Console())->prompt('Are you sure you want to run this command? [Y/N] ', ['y', 'n']);

            echo PHP_EOL;

            if (strtolower($confirm) == 'n') {
                exit(127);
            }
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
