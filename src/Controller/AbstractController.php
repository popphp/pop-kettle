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
namespace Pop\Kettle\Controller;

use Pop\Application;
use Pop\Dispatch\ConsoleTrait;
use Pop\Console\Color;
use Pop\Console\Console;
use Pop\Kettle\Exception;
use Pop\Router\Match\Cli;

/**
 * Console abstract controller class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
abstract class AbstractController extends \Pop\Controller\AbstractController
{

    /**
     * Traits
     */
    use ConsoleTrait {
        ConsoleTrait::__construct as private constructConsoleController;
    }

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
    public function __construct(Application $application, Console $console = new Console(120))
    {
        $this->constructConsoleController($application, $console);

        $this->console->setHelpColors(Color::BOLD_CYAN, Color::BOLD_GREEN, Color::BOLD_MAGENTA);

        $routeMatch = $application->router()->getRouteMatch();
        if ($routeMatch instanceof Cli) {
            $this->console->addCommandsFromRoutes($routeMatch, './kettle');
        }
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
