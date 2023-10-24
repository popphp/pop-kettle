<?php

namespace MyApp\Http\Event;

use Pop\Application;

class Options
{

    /**
     * Check for and re-route OPTIONS requests
     *
     * @param  Application $application
     * @return void
     */
    public static function send(Application $application): void
    {
        if (($application->router()->hasController()) && (null !== $application->router()->getController()->request()) &&
            ($application->router()->getController()->request()->isOptions())) {
            $application->router()->getController()->sendOptions();
            exit();
        }
    }

}