<?php

namespace MyApp;

use Pop\Application;
use Pop\Db;
use Pop\Console\Console;
use Pop\Http\Server\Request;
use Pop\Http\Server\Response;

class Module extends \Pop\Module\Module
{

    /**
     * Module name
     * @var ?string
     */
    protected ?string $name = 'myapp';

    /**
     * Register module
     *
     * @param  Application $application
     * @return static
     */
    public function register(Application $application): static
    {
        parent::register($application);

        if (isset($this->application->config['database'])) {
            $this->initDb($this->application->config['database']);
        }

        if ($this->application->router() !== null) {
            if ($this->application->router()->isHttp()) {
                $this->application->router()->addControllerParams(
                    '*', [
                        'application' => $this->application,
                        'request'     => new Request(),
                        'response'    => new Response()
                    ]
                );

                $this->application->on('app.dispatch.pre', 'MyApp\Http\Event\Options::send', 1);
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
    public function httpError(\Exception $exception): void
    {
        $response = new Response();
        $response->addHeader('Content-Type', 'application/json');
        $response->setBody(json_encode(['error' => $exception->getMessage()], JSON_PRETTY_PRINT) . PHP_EOL);
        $response->send(500);
        exit();
    }

    /**
     * CLI error handler method
     *
     * @param  \Exception $exception
     * @return void
     */
    public function cliError(\Exception $exception): void
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