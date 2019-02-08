<?php

namespace MyApp;

use Pop\Application;
use Pop\Db;
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

        if (isset($this->application->config['database'])) {
            $this->initDb($this->application->config['database']);
        }

        if (null !== $this->application->router()) {
            $this->application->router()->addControllerParams(
                '*', [
                    'application' => $this->application,
                    'request'     => new Request(),
                    'response'    => new Response()
                ]
            );
        }

        $this->application->on('app.dispatch.pre', 'MyApp\Http\Api\Event\Options::send', 1);

        return $this;
    }

    /**
     * Initialize database service
     *
     * @param  array $database
     * @throws \Pop\Db\Adapter\Exception
     * @return void
     */
    protected function initDb($database)
    {
        if (!empty($database['adapter'])) {
            $adapter = $database['adapter'];
            $options = [
                'database' => $database['database'],
                'username' => $database['username'],
                'password' => $database['password'],
                'host'     => $database['host'],
                'type'     => $database['type']
            ];

            $check = Db\Db::check($adapter, $options);

            if (null !== $check) {
                throw new \Pop\Db\Adapter\Exception('Error: ' . $check);
            }

            $this->application->services()->set('database', [
                'call'   => 'Pop\Db\Db::connect',
                'params' => [
                    'adapter' => $adapter,
                    'options' => $options
                ]
            ]);

            if ($this->application->services()->isAvailable('database')) {
                Db\Record::setDb($this->application->services['database']);
            }
        }
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

}