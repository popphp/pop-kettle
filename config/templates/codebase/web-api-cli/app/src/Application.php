<?php

namespace App;

use Pop\Db;
use Pop\Console\Console;
use Pop\Http\Server\Request;
use Pop\Http\Server\Response;
use Pop\View\View;

class Application extends \Pop\Application
{

    /**
     * Application name
     * @var ?string
     */
    const string NAME = 'app';

    /**
     * Application full name
     * @var ?string
     */
    const string FULL_NAME = 'App';

    /**
     * Application version
     * @var string
     */
    const string VERSION = '1.0.0';

    /**
     * Application name
     * @var ?string
     */
    protected ?string $name = self::NAME;

    /**
     * Application full name
     * @var ?string
     */
    protected ?string $fullName = self::FULL_NAME;

    /**
     * Version
     * @var ?string
     */
    protected ?string $version = self::VERSION;

    /**
     * Load application
     *
     * @return Application
     */
    public function load(): Application
    {
        if (isset($this->config['database'])) {
            $this->initDb($this->config['database']);
        }

        if ($this->router() !== null) {
            if ($this->router()->isHttp()) {
                $this->router()->addControllerParams(
                    '*', [
                        'application' => $this,
                        'request'     => new Request(),
                        'response'    => new Response()
                    ]
                );

                $this->on('app.dispatch.pre', 'App\Http\Api\Event\Options::send', 1);
            } else if ($this->router()->isCli()) {
                $console = new Console(120, '    ');
                $this->router()->addControllerParams(
                    '*', [
                        'application' => $this,
                        'console'     => $console
                    ]
                );

                $console->write(PHP_EOL . $console->header(self::FULL_NAME, '=', null, 'left', false, true));

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
        $request  = new Request();
        $response = new Response();
        $message  = $exception->getMessage();
        if (stripos($request->getHeader('Accept')->getValue(), 'text/html') !== false) {
            $view          = new View(__DIR__ . '/../view/exception.phtml');
            $view->title   = 'Exception';
            $view->message = $message;
            $response->addHeader('Content-Type', 'text/html');
            $response->setBody($view->render());
        } else {
            $response->addHeaders($this->config['http_options_headers']);
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
    public function cliError(\Exception $exception): void
    {
        $message = strip_tags($exception->getMessage());

        if (stripos(PHP_OS, 'win') === false) {
            $string  = "    \x1b[1;37m\x1b[41m    " . str_repeat(' ', strlen($message)) . "    \x1b[0m" . PHP_EOL;
            $string .= "    \x1b[1;37m\x1b[41m    " . $message . "    \x1b[0m" . PHP_EOL;
            $string .= "    \x1b[1;37m\x1b[41m    " . str_repeat(' ', strlen($message)) . "    \x1b[0m" . PHP_EOL . PHP_EOL;
            $string .= "    Try \x1b[1;33m./app help\x1b[0m for help" . PHP_EOL . PHP_EOL;
        } else {
            $string = $message . PHP_EOL . PHP_EOL;
            $string .= '    Try \'./app help\' for help' . PHP_EOL . PHP_EOL;
        }

        echo $string;
        echo PHP_EOL;

        exit(127);
    }

}
