<?php

namespace MyApp\Console\Controller;

use Pop\Application;
use Pop\Console\Console;
use Pop\Console\Color;
use Pop\Controller\ConsoleControllerTrait;

abstract class AbstractController extends \Pop\Controller\AbstractController
{

    /**
     * Traits
     */
    use ConsoleControllerTrait {
        ConsoleControllerTrait::__construct as private constructConsoleController;
    }

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
        $this->console->addCommandsFromRoutes($application->router()->getRouteMatch(), './app');
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

    /**
     * Default maintenance action method
     *
     * @return void
     */
    public function maintenance()
    {
        $this->console->alertInfo('Application in Maintenance', 40);
        exit(127);
    }

}
