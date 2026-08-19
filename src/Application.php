<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Kettle;

use Pop\Console\Console;
use Pop\Db;
use Pop\Kettle\Controller\ApplicationController;

/**
 * Main module class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
class Application extends \Pop\Application
{

    /**
     * Application name
     * @var string
     */
    const string NAME = 'pop-kettle';

    /**
     * Application full name
     * @var string
     */
    const string FULL_NAME = 'Pop Kettle';

    /**
     * Application version
     * @var string
     */
    const string VERSION = '3.0.0';

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
     * Shared console object
     * @var ?Console
     */
    protected ?Console $console = null;

    /**
     * Load application
     *
     * @return Application
     */
    public function load(): Application
    {
        $dir = getcwd();
        if (file_exists($dir . '/app/config/database.php')) {
            $this->initDb(include $dir . '/app/config/database.php');
        }

        $this->console = new Console(120);
        $this->console->write(PHP_EOL . $this->console->header(self::FULL_NAME, '=', null, 'left', true, true));

        $this->on('app.route.pre', function () {
            Event\Console::maintenanceDisplay($this->console);
            Event\Console::productionDisplay($this->console);
        })->on('app.dispatch.post', function() {
            $this->console->write();
        });

        return $this;
    }

    /**
     * Prepare application
     *   - determine if the route is a kettle native route or a custom application route
     *
     * @return \Pop\Application
     */
    public function prepare(): \Pop\Application
    {
        $app = $this;

        if (($this->router !== null)) {
            $this->router->getRouteMatch()->match();
            $dispatchable = $this->router->getRouteMatch()->getDispatchable();

            // If it's a custom command
            if (!str_starts_with($dispatchable, 'Pop\Kettle')) {
                $resolved = (new Model\Application())->resolveAppInstance(getcwd(), $this->autoloader(), $this->config['routes']);
                if ($resolved !== null) {
                    $app = $resolved;
                }
            }
        }

        return $app;
    }

    /**
     * Get the shared console object
     *
     * @return ?Console
     */
    public function getConsole(): ?Console
    {
        return $this->console;
    }

    /**
     * CLI error handler method
     *
     * @param  \Exception $exception
     * @param  bool       $exit
     * @return void
     */
    public function cliError(\Exception $exception, bool $exit = true): void
    {
        (new Console())->alertDanger(strip_tags($exception->getMessage()));

        echo (stripos(PHP_OS, 'win') === false) ?
            "    Try \x1b[1;33m./kettle help\x1b[0m for help" . PHP_EOL . PHP_EOL :
            '    Try \'./kettle help\' for help' . PHP_EOL . PHP_EOL;

        if ($exit) {
            exit(127);
        }
    }

    /**
     * Initialize database service
     *
     * @param  array $database
     * @throws \Pop\Db\Adapter\Exception
     * @return void
     */
    public function initDb(array $database): void
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
     * Install application (post-install-cmd for main framework installation)
     *
     * @return void
     */
    public static function install(): void
    {
        (new ApplicationController(new static(include __DIR__ . '/../config/app.console.php'), new Console(120)))->init();
    }

}
