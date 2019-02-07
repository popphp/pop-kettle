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

use Pop\Application;
use Pop\Console\Console;
use Pop\Console\Command;

/**
 * Console abstract controller class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <nick@nolainteractive.com>
 * @copyright  Copyright (c) 2012-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    0.0.1-alpha
 */
abstract class AbstractController extends \Pop\Controller\AbstractController
{

    /**
     * Application object
     * @var Application
     */
    protected $application = null;

    /**
     * Console object
     * @var \Pop\Console\Console
     */
    protected $console = null;

    /**
     * Constructor for the controller
     *
     * @param  Application $application
     * @param  Console     $console
     */
    public function __construct(Application $application, Console $console)
    {
        $this->application = $application;
        $this->console     = $console;

        $this->console->setHelpColors(Console::BOLD_CYAN, Console::BOLD_GREEN, Console::BOLD_MAGENTA);
        $this->console->addCommands([
            new Command('./kettle app:init', '[--web] [--api] [--cli] <namespace>', "Initialize an application" . PHP_EOL),
            new Command('./kettle db:init', null, "Initialize a database"),
            new Command('./kettle db:seed', null, "Seed a database with data"),
            new Command('./kettle db:reset', null, "Reset a database with original data" . PHP_EOL),
            new Command('./kettle migrate:create', '<class>', "Create new database migration"),
            new Command('./kettle migrate:run', null, "Perform forward database migration"),
            new Command('./kettle migrate:rollback', '[<steps>]', "Perform backward database migration"),
            new Command('./kettle migrate:reset', null, "Perform complete rollback of the database" . PHP_EOL),
            new Command('./kettle serve', '[--host=] [--port=] [--folder=]', "Start the web server"),
            new Command('./kettle help', null, "Show the help screen"),
            new Command('./kettle version', null, "Show the version")
        ]);
    }

    /**
     * Get application object
     *
     * @return Application
     */
    public function application()
    {
        return $this->application;
    }

    /**
     * Get request object
     *
     * @return Console
     */
    public function console()
    {
        return $this->console;
    }

    /**
     * Default error action method
     *
     * @throws \Pop\Kettle\Exception
     * @return void
     */
    public function error()
    {
        throw new \Pop\Kettle\Exception('Invalid Command');
    }

}