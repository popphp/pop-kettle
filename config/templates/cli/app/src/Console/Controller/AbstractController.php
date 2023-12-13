<?php

namespace MyApp\Console\Controller;

use Pop\Application;
use Pop\Console\Console;
use Pop\Console\Color;

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

        $this->console->setHelpColors(Color::BOLD_CYAN, Color::BOLD_GREEN, Color::BOLD_MAGENTA);
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
    public function error()
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
