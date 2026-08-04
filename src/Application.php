<?php
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
     * @var ?string
     */
    const string NAME = 'pop-kettle';

    /**
     * Application full name
     * @var ?string
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

        $this->console = new Console(120, '    ');

        if ($this->router() !== null) {
            $this->router()->addControllerParams(
                '*', [
                    'application' => $this,
                    'console'     => $this->console
                ]
            );
        }

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

            Db\Record::setDb(Db\Db::connect($adapter, $options));
        }
    }

}
