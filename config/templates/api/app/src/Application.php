<?php

namespace MyApp;

use Pop\Db;
use Pop\Http\Server\Request;
use Pop\Http\Server\Response;

class Application extends \Popcorn\Pop
{

    /**
     * Application name
     * @var ?string
     */
    protected ?string $name = 'myapp';

    /**
     * Application version
     * @var ?string
     */
    protected ?string $version = '1.0.0';

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
            $this->router()->addControllerParams(
                '*', [
                    'application' => $this,
                    'request'     => new Request(),
                    'response'    => new Response()
                ]
            );
        }

        $this->on('app.dispatch.pre', 'MyApp\Http\Event\Options::send', 1);

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
        $response = new Response();
        $response->addHeader('Content-Type', 'application/json');
        $response->setBody(json_encode(['error' => $exception->getMessage()], JSON_PRETTY_PRINT) . PHP_EOL);
        $response->send(500);
        exit();
    }

}