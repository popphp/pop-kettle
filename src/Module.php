<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2018 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Kettle;

use Pop\Application;

/**
 * Main module class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2018 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    1.0.0
 */
class Module extends \Pop\Module\Module
{

    /**
     * Module name
     * @var string
     */
    protected $name = 'pop-kettle';

    /**
     * Application version
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Register module
     *
     * @param  Application $application
     */
    public function register(Application $application)
    {
        parent::register($application);

        if (null !== $this->application->router()) {
            $this->application->router()->addControllerParams(
                '*', [
                    'application' => $this->application,
                    'console'     => new \Pop\Console\Console(120, '    ')
                ]
            );
        }

        $this->application->on('app.route.pre', 'Pop\Kettle\Event\Console::header')
             ->on('app.dispatch.post', 'Pop\Kettle\Event\Console::footer');
    }

    /**
     * CLI error handler method
     *
     * @param  \Exception $exception
     * @return void
     */
    public function cliError(\Exception $exception)
    {
        $message = strip_tags($exception->getMessage());

        if (stripos(PHP_OS, 'win') === false) {
            $string  = "    \x1b[1;37m\x1b[41m    " . str_repeat(' ', strlen($message)) . "    \x1b[0m" . PHP_EOL;
            $string .= "    \x1b[1;37m\x1b[41m    " . $message . "    \x1b[0m" . PHP_EOL;
            $string .= "    \x1b[1;37m\x1b[41m    " . str_repeat(' ', strlen($message)) . "    \x1b[0m" . PHP_EOL . PHP_EOL;
            $string .= "    Try \x1b[1;33m./kettle help\x1b[0m for help" . PHP_EOL . PHP_EOL;
        } else {
            $string = $message . PHP_EOL . PHP_EOL;
            $string .= '    Try \'./kettle help\' for help' . PHP_EOL . PHP_EOL;
        }

        echo $string;
        echo PHP_EOL;

        exit(127);
    }

}