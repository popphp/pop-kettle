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
namespace Pop\Kettle\Controller;

use Pop\Application;
use Pop\Console\Color;
use Pop\Console\Console;
use Pop\Kettle\Exception;

/**
 * Console abstract controller class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <nick@nolainteractive.com>
 * @copyright  Copyright (c) 2012-2025 NOLA Interactive, LLC.
 * @license    http://www.popphp.org/license     New BSD License
 * @version    2.3.2
 */
abstract class AbstractController extends \Pop\Controller\AbstractController
{

    /**
     * Application object
     * @var ?Application
     */
    protected ?Application $application = null;

    /**
     * Console object
     * @var ?Console
     */
    protected ?Console $console = null;

    /**
     * Bypass maintenance false
     * @var bool
     */
    protected bool $bypassMaintenance = true;

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

        $this->console->setHelpColors(Color::BOLD_CYAN, Color::BOLD_GREEN, Color::BOLD_MAGENTA);
        $this->console->addCommandsFromRoutes($application->router()->getRouteMatch(), './kettle');
    }

    /**
     * Get application object
     *
     * @return Application
     */
    public function application(): Application
    {
        return $this->application;
    }

    /**
     * Get request object
     *
     * @return Console
     */
    public function console(): Console
    {
        return $this->console;
    }

    /**
     * Default error action method
     *
     * @throws Exception
     * @return void
     */
    public function error(): void
    {
        throw new Exception('Invalid Command');
    }

}
