<?php

namespace MyApp;

use Pop\Db;
use Pop\Console\Console;
use Pop\Http\Server\Request;
use Pop\Http\Server\Response;
use Pop\View\View;

class Application extends \Pop\Application
{

    /**
     * Application name
     * @var string
     */
    protected $name = 'myapp';

    /**
     * Application version
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * Load application
     *
     * @return Application
     */
    public function load()
    {
        if (isset($this->config['database'])) {
            $this->initDb($this->config['database']);
        }

        if (null !== $this->router()) {
            if ($this->router()->isHttp()) {
                $this->router()->addControllerParams(
                    '*', [
                        'application' => $this,
                        'request'     => new Request(),
                        'response'    => new Response()
                    ]
                );
            } else if ($this->router()->isCli()) {
                $this->router()->addControllerParams(
                    '*', [
                        'application' => $this,
                        'console'     => new Console(120, '    ')
                    ]
                );

                $this->on('app.route.pre', function() { echo PHP_EOL; })
                     ->on('app.dispatch.post', function() { echo PHP_EOL; });
            }
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
    protected function initDb(array $database): void
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

            $this->services()->set('database', [
                'call'   => 'Pop\Db\Db::connect',
                'params' => [
                    'adapter' => $adapter,
                    'options' => $options
                ]
            ]);

            if ($this->services()->isAvailable('database')) {
                Db\Record::setDb($this->services['database']);
            }
        }
    }

    /**
     * HTTP error handler method
     *
     * @param  \Exception $exception
     * @return void
     */
    public function httpError(\Exception $exception): void
    {
        $response      = new Response();
        $view          = new View(__DIR__ . '/../view/exception.phtml');
        $view->title   = 'Exception';
        $view->message = $exception->getMessage();
        $response->setBody($view->render());
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