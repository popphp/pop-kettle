<?php

namespace MyApp;

use Pop\Application;
use Pop\Db;
use Pop\Http\Server\Request;
use Pop\Http\Server\Response;
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
        if (isset($database['default']) &&
            !empty($database['default']['adapter']) && !empty($database['default']['database'])) {
            $adapter = $database['default']['adapter'];
            $options = [
                'database' => $database['default']['database'],
                'username' => $database['default']['username'] ?? null,
                'password' => $database['default']['password'] ?? null,
                'host'     => $database['default']['host'] ?? null,
                'type'     => $database['default']['type'] ?? null
            ];

            $check = Db\Db::check($adapter, $options);

            if ($check !== true) {
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
        $response      = new Response();
        $view          = new View(__DIR__ . '/../view/exception.phtml');
        $view->title   = 'Exception';
        $view->message = $exception->getMessage();
        $response->setBody($view->render());
        $response->send(500);
        exit();
    }

}