<?php

namespace MyApp\Console\Controller;

use Pop\Application;
use Pop\Console\Console;

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
        $this->console->addCommandsFromRoutes($application->router()->getRouteMatch(), './myapp');
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
     * Get console object
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
     * @throws \MyApp\Exception
     * @return void
     */
    public function error(): void
    {
        throw new \MyApp\Exception('Invalid Command');
    }

}