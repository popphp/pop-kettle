<?php

namespace MyApp\Http\Event;

use Pop\Application;

class Options
{

    /**
     * Check for and re-route OPTIONS requests
     *
     * Not every dispatchable is guaranteed to have a request() or sendOptions() method - only
     * controllers built on Pop\Dispatch\HttpTrait (like the scaffolded Http\Controller\AbstractController)
     * do. A controller that only extends Pop\Controller\AbstractController is still a valid dispatchable,
     * so this checks for both methods before calling them and simply lets dispatch continue as normal
     * if either one is missing.
     *
     * @param  Application $application
     * @return void
     */
    public static function send(Application $application): void
    {
        if (!$application->router()->hasDispatchable()) {
            return;
        }

        $dispatchable = $application->router()->getDispatchable();

        if (!method_exists($dispatchable, 'request') || !method_exists($dispatchable, 'sendOptions')) {
            return;
        }

        if (($dispatchable->request() !== null) && ($dispatchable->request()->isOptions())) {
            $dispatchable->sendOptions();
            exit();
        }
    }

}
