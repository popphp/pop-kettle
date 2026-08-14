<?php

namespace MyApp;

use Pop\Db;
use Pop\Http\Server\Request;
use Pop\Http\Server\Response;

class Application extends \Pop\Application
{

    /**
     * Application name
     * @var ?string
     */
    const string NAME = 'myapp';

    /**
     * Application full name
     * @var ?string
     */
    const string FULL_NAME = 'MyApp';

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
        foreach ($database as $db => $dbConfig) {
            if (!empty($dbConfig['adapter']) && !empty($dbConfig['database'])) {
                $adapter = $dbConfig['adapter'];
                $options = [
                    'database' => $dbConfig['database'],
                    'username' => $dbConfig['username'] ?? null,
                    'password' => $dbConfig['password'] ?? null,
                    'host'     => $dbConfig['host'] ?? null,
                    'type'     => $dbConfig['type'] ?? null
                ];

                $check = Db\Db::check($adapter, $options);

                if ($check !== true) {
                    throw new \Pop\Db\Adapter\Exception('Error: ' . $check);
                }

                $dbService = 'database';
                if ($db != 'default') {
                    $dbService .= '_' . $db;
                }

                $this->services()->set($dbService, [
                    'call'   => 'Pop\Db\Db::connect',
                    'params' => [
                        'adapter' => $adapter,
                        'options' => $options
                    ]
                ]);

                if ($db == 'default') {
                    if ($this->services()->isAvailable('database')) {
                        Db\Record::setDb($this->services['database']);
                    }
                }
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
