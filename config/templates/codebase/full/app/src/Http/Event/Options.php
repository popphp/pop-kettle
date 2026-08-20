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
        if (($application->router()->hasDispatchable()) && ($application->router()->getDispatchable()->request() !== null) &&
            ($application->router()->getDispatchable()->request()->isOptions())) {
            $application->router()->getDispatchable()->sendOptions();
            exit();
        }
    }

}
