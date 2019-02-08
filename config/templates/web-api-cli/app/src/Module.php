<?php

namespace MyApp;

use Pop\Application;
use Pop\Console\Console;
use Pop\Http\Request;
use Pop\Http\Response;
use Pop\View\View;

class Module extends \Pop\Module\Module
{

    /**
     * Module name
     * @var string
     */
    protected $name = 'myapp';

    /**
     * Register module
     *
     * @param  Application $application
     * @return Module
     */
    public function register(Application $application)
    {
        parent::register($application);

        if (null !== $this->application->router()) {
            if ($this->application->router()->isHttp()) {
                $this->application->router()->addControllerParams(
                    '*', [
                        'application' => $this->application,
                        'request'     => new Request(),
                        'response'    => new Response()
                    ]
                );

                $this->application->on('app.dispatch.pre', 'MyApp\Http\Api\Event\Options::send', 1);
            } else if ($this->application->router()->isCli()) {
                $this->application->router()->addControllerParams(
                    '*', [
                        'application' => $this->application,
                        'console'     => new Console(120, '    ')
                    ]
                );

                $this->application->on('app.route.pre', function() { echo PHP_EOL; })
                     ->on('app.dispatch.post', function() { echo PHP_EOL; });
            }
        }

        return $this;
    }

    /**
     * HTTP error handler method
     *
     * @param  \Exception $exception
     * @return void
     */
    public function httpError(\Exception $exception)
    {
        $request  = new Request();
        $response = new Response();
        $message  = $exception->getMessage();
        if (stripos($request->getHeader('Accept'), 'text/html') !== false) {
            $view          = new View(__DIR__ . '/../view/exception.phtml');
            $view->title   = 'Exception';
            $view->message = $message;
            $response->setHeader('Content-Type', 'text/html');
            $response->setBody($view->render());
        } else {
            $response->setHeaders($this->config['http_options_headers']);
            $response->setBody(json_encode(['error' => $exception->getMessage()], JSON_PRETTY_PRINT) . PHP_EOL);
        }
        $response->send(500);
        exit();
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
            $string .= "    Try \x1b[1;33m./myapp help\x1b[0m for help" . PHP_EOL . PHP_EOL;
        } else {
            $string = $message . PHP_EOL . PHP_EOL;
            $string .= '    Try \'./myapp help\' for help' . PHP_EOL . PHP_EOL;
        }

        echo $string;
        echo PHP_EOL;

        exit(127);
    }

}