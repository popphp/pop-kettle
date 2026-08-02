<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Kettle;

use Pop\Application;
use Pop\Code\Reflection\ClassReflection;
use Pop\Console\Console;
use Pop\Db;

/**
 * Main module class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2026 NOLA Interactive, LLC.
 * @license    http://www.popphp.org/license     New BSD License
 * @version    2.3.4
 */
class Module extends \Pop\Module\Module
{

    /**
     * Application name
     * @var ?string
     */
    protected ?string $name = 'pop-kettle';

    /**
     * Application version
     * @var string
     */
    const VERSION = '2.3.4';

    /**
     * Register module
     *
     * @param Application $application
     * @return static
     */
    public function register(Application $application): static
    {
        parent::register($application);

        $dir = getcwd();
        if (file_exists($dir . '/app/config/database.php')) {
            $this->initDb(include $dir . '/app/config/database.php');
        }

        if ($this->application->router() !== null) {
            $this->application->router()->addControllerParams(
                '*', [
                    'application' => $this->application,
                    'console'     => new Console(120, '    ')
                ]
            );
        }

        $this->application->on('app.route.pre', 'Pop\Kettle\Event\Console::header')
             ->on('app.dispatch.post', 'Pop\Kettle\Event\Console::footer');

        return $this;
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

    /**
     * Load custom application commands
     *
     * @param  array  $routes
     * @return array
     */
    public static function loadCommands(array $routes): array
    {
        $location = getcwd() . '/app/src/Console/Command';

        if (file_exists($location)) {
            $commands = array_values(array_filter(scandir($location), function ($value) {
                return (($value != '.') && ($value != '..') && ($value != '.empty'));
            }));

            $namespace     = null;
            $commandRoutes = [];

            foreach ($commands as $i => $command) {
                if ($namespace === null) {
                    if (file_exists($location . DIRECTORY_SEPARATOR . $command)) {
                        $classContents = file_get_contents($location . DIRECTORY_SEPARATOR . $command);
                        $namespace     = substr($classContents, strpos($classContents, 'namespace ') + 10);
                        $namespace     = substr($namespace, 0, strpos($namespace, ';'));
                    }

                    $commandClass = $namespace . '\\' . substr($command, 0, -4);
                    if (class_exists($commandClass)) {
                        $commandObject = new $commandClass();
                        if ($commandObject->hasController()) {
                            $commandRoute = [
                                'controller' => $commandObject->getController()
                            ];

                            if ($commandObject->hasAction()) {
                                $commandRoute['action'] = $commandObject->getAction();
                            }
                            if ($commandObject->hasHelp()) {
                                $commandRoute['help'] = $commandObject->getHelp();
                                if ($i == (count($commands) - 1)) {
                                    $commandRoute['help'] .= PHP_EOL;
                                }
                            }
                            $commandRoutes[(string)$commandObject] = $commandRoute;
                        }
                    }
                }
            }

            if (!empty($commandRoutes)) {
                $routes = array_merge($commandRoutes, $routes);
            }
        }

        return $routes;
    }

}
