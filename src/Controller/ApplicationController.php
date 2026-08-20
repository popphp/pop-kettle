<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/pop-bootstrap
 * @author     Nick Sagona, III <nick@noladev.com>
 * @copyright  Copyright (c) 2012-2025 NOLA Interactive, LLC.
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Kettle\Controller;

use Pop\Console\Color;
use Pop\Kettle\Event;
use Pop\Kettle\Model;
use Pop\App;

/**
 * Console application controller class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <nick@noladev.com>
 * @copyright  Copyright (c) 2012-2025 NOLA Interactive, LLC.
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
class ApplicationController extends AbstractController
{

    /**
     * Init command
     *
     * @return void
     */
    public function init(): void
    {
        $namespace = $this->console->prompt('What is the namespace of your app? [MyApp] ', null, true);
        if (empty($namespace)) {
            $namespace = 'MyApp';
        }

        $parsed    = Model\Application::parseNamespace($namespace);
        $namespace = $parsed['namespace'];
        $fullName  = $parsed['fullName'];

        $this->console->write();
        $name = $this->console->prompt('What is the name of your app? [' . $fullName . '] ', null, true);
        if ($name == '') {
            $name = $fullName;
        } else if (str_contains($name, ' ') && !str_starts_with($name, '"') && !str_ends_with($name, '"')) {
            $name = '"' . $name . '"';
        }

        $this->console->write();
        $cliOnlyAnswer = $this->console->prompt('Is this a CLI-only application? [Y/N] ', null, true);
        $cliOnly       = (strtolower($cliOnlyAnswer) == 'y');

        $url = '';

        if (!$cliOnly) {
            $this->console->write();
            $url = $this->console->prompt('What is the URL of your app? [http://localhost] ', null, true);
            if ($url == '') {
                $url = 'http://localhost';
            }
        }

        $this->console->write();
        $createCliApp = $this->console->prompt(
            'Initialize a stand-alone CLI application? [Y/N] ', ['y', 'n']
        );
        $cliApp = (strtolower($createCliApp) == 'y');

        $appModel = new Model\Application();
        $dbModel  = new Model\Database();
        $location = getcwd();

        $this->console->write();
        $configDb = $this->console->prompt(
            'Would you like to configure a database? [Y/N] ', ['y', 'n']
        );
        $createDb = (strtolower($configDb) == 'y');

        $frontend = null;

        if (!$cliOnly) {
            $this->console->write();
            $installFrontend = $this->console->prompt(
                'Would you like to install a front-end? [Y/N] ', ['y', 'n']
            );

            if (strtolower($installFrontend) == 'y') {
                $frameworks = [
                    'AlpineJS' => 1,
                    'Vue'      => 2,
                    'React'    => 3,
                ];

                $this->console->write();
                foreach ($frameworks as $label => $i) {
                    $this->console->write($i . ': ' . $label);
                }
                $this->console->write();

                $f     = $this->console->prompt('Please select a front-end framework from above: ', $frameworks);
                $label = array_search($f, $frameworks);
                $frontend = match ($label) {
                    'AlpineJS' => 'alpine',
                    'Vue'      => 'vue',
                    'React'    => 'react',
                    default    => null,
                };
            }
        }

        $appModel->init($location, $namespace, $cliOnly, $name, $url, $cliApp, $createDb, $frontend);

        $this->console->write();
        $this->console->write("Installing files for '" . $namespace ."'...");
        $this->console->write();

        $composerModel = new Model\Composer();
        if ($composerModel->isAvailable()) {
            $this->console->write("Registering application autoloader for '" . $namespace . "'...");
            $this->console->write();
            $composerModel->dumpAutoload($location);
            $this->console->write();
        } else {
            $this->console->alertWarning(
                'Composer not found -- run `composer dump-autoload` yourself before using the app.',
                60
            );
            $this->console->write();
        }

        if ($createDb) {
            $this->console->write("Configuring database for '" . $namespace ."'...");
            $this->console->write();
            $dbModel->configure($this->console, $location);
            $this->console->write();
        }

        if ($frontend !== null) {
            $assetModel = new Model\Asset();
            if ($assetModel->isNpmAvailable()) {
                $this->console->write("Installing front-end dependencies for '" . $namespace . "'...");
                $this->console->write();
                $installResult = $assetModel->install($location);

                if ($installResult === 0) {
                    $this->console->write();
                    $this->console->write("Building front-end assets for '" . $namespace . "'...");
                    $this->console->write();
                    $assetModel->build($location);
                }
            } else {
                $this->console->alertWarning(
                    'Node/npm not found -- install Node, then run `npm install` before using web:watch/web:build.',
                    60
                );
            }
            $this->console->write();
        }

        $this->console->write('Done!');
    }

    /**
     * Env command
     *
     * @param  array $options
     * @return void
     */
    public function env(array $options = []): void
    {
        if (isset($options['set'])) {
            $this->setEnv();
            return;
        }

        $this->displayEnv((string)App::environment());
    }

    /**
     * Display the colorized environment alert box for the given environment
     *
     * @param  string $env
     * @return void
     */
    protected function displayEnv(string $env): void
    {
        if (str_starts_with($env, 'prod')) {
            $this->console->alertWarning('Application in Production', 40);
        } else if ($env == 'staging') {
            $this->console->alertPrimary('Application in Staging', 40);
        } else if ($env == 'testing') {
            $this->console->alertSecondary('Application in Testing', 40);
        } else if ($env == 'dev') {
            $this->console->alertDark('Application in Dev', 40);
        } else if ($env == 'local') {
            $this->console->alertLight('Application in Local', 40);
        }
    }

    /**
     * Set application environment
     *
     * @return void
     */
    protected function setEnv(): void
    {
        $location = getcwd();

        if (!file_exists($location . '/.env')) {
            $this->console->write('No .env file found.');
            return;
        }

        $envs = [
            'local'      => 1,
            'dev'        => 2,
            'testing'    => 3,
            'staging'    => 4,
            'production' => 5,
        ];

        $this->console->write();
        foreach ($envs as $env => $i) {
            $this->console->write($i . ': ' . $env);
        }
        $this->console->write();

        $e   = $this->console->prompt('Please select an app environment from above: ', $envs);
        $env = array_search($e, $envs);

        $current = App::environment();

        $contents = str_replace(
            [
                'APP_ENV=' . $current,
                'APP_ENV="' . $current . '"',
            ],
            'APP_ENV=' . $env,
            file_get_contents($location . '/.env')
        );

        file_put_contents($location . '/.env', $contents);

        $this->console->write();
        $this->displayEnv($env);
    }

    /**
     * Status command
     *
     * @return void
     */
    public function status(): void
    {
        if (App::isUp()) {
            $this->console->alertSuccess('Application is Live', 40);
        }
    }

    /**
     * Down command
     *
     * @param  array $options
     * @return void
     */
    public function down(array $options = []): void
    {
        $location = getcwd();
        $secret   = null;

        if (array_key_exists('secret', $options)) {
            $secret = (($options['secret'] === null) || ($options['secret'] == '')) ? sha1((string)time()) : $options['secret'];
        }

        if (file_exists($location . '/.env') && (App::isUp())) {
            $e = file_get_contents($location . '/.env');
            $e = str_replace(
                [
                    'MAINTENANCE_MODE=false',
                    'MAINTENANCE_MODE="false"',
                    'MAINTENANCE_MODE=(false)',
                ],
                'MAINTENANCE_MODE=true',
                $e
            );

            $currentSecret = App::env('MAINTENANCE_MODE_SECRET');
            if (!empty($secret)) {
                $e = str_replace(
                    [
                        'MAINTENANCE_MODE_SECRET=' . $currentSecret,
                        'MAINTENANCE_MODE_SECRET="' . $currentSecret . '"',
                    ],
                    'MAINTENANCE_MODE_SECRET=' . $secret,
                    $e
                );
            } else if (!empty($currentSecret)) {
                $secret = $currentSecret;
            }

            file_put_contents($location . '/.env', $e);

            $this->console->alertInfo('Application in Maintenance', 40);
            $this->console->write('Application has been switched to maintenance mode.');
            if (!empty($secret)) {
                $this->console->write('');
                $this->console->write('The secret is ' . $this->console->colorize($secret, Color::BOLD_GREEN));
            }
        } else if (file_exists($location . '/.env') && (App::isDown())) {
            $this->console->write('Application is currently in maintenance mode. No action to take.');
            $currentSecret = App::env('MAINTENANCE_MODE_SECRET');
            if (!empty($secret)) {
                $e = str_replace(
                    [
                        'MAINTENANCE_MODE_SECRET=' . $currentSecret,
                        'MAINTENANCE_MODE_SECRET="' . $currentSecret . '"',
                    ],
                    'MAINTENANCE_MODE_SECRET=' . $secret,
                    file_get_contents($location . '/.env')
                );

                file_put_contents($location . '/.env', $e);
            } else if (!empty($currentSecret)) {
                $secret = $currentSecret;
            }
            if (!empty($secret)) {
                $this->console->write('');
                $this->console->write('The secret is ' . $this->console->colorize($secret, Color::BOLD_GREEN));
            }
        } else {
            $this->console->write('No .env file found.');
        }
    }

    /**
     * Up command
     *
     * @return void
     */
    public function up(): void
    {
        $location = getcwd();

        if (file_exists($location . '/.env') && (App::isDown())) {
            $e = str_replace(
                [
                    'MAINTENANCE_MODE=true',
                    'MAINTENANCE_MODE="true"',
                    'MAINTENANCE_MODE=(true)',
                ],
                'MAINTENANCE_MODE=false',
                file_get_contents($location . '/.env')
            );

            file_put_contents($location . '/.env', $e);

            $this->console->alertSuccess('Application is Live', 40);
            $this->console->write('Application has been made live.');
        } else if (App::isUp()) {
            $this->console->alertSuccess('Application is Live', 40);
            $this->console->write('Application is currently live. No action to take.');
        } else {
            $this->console->write('No .env file found.');
        }
    }

    /**
     * Create custom command
     *
     * @param  string $command
     * @return void
     */
    public function createCommand(string $command, array $options = []): void
    {
        $appModel = new Model\Application();
        $appModel->createCommand($command, getcwd(), (isset($options['app'])));

        $this->console->write("Command '" . $command ."' created.");

        $this->console->write();
        $this->console->write('Done!');
    }

    /**
     * Create controller command
     *
     * @param  string $ctrl
     * @param  array  $options
     * @return void
     */
    public function createController(string $ctrl, array $options = []): void
    {
        $cli = (isset($options['cli'])) ? true : null;

        $appModel  = new Model\Application();
        $ctrlClass = $appModel->createController($ctrl, getcwd(), $cli);

        $this->console->write("Controller class '" . $ctrlClass ."' created.");

        $this->console->write();
        $this->console->write('Done!');
    }

    /**
     * Create model command
     *
     * @param  string $model
     * @param  array  $options
     * @return void
     */
    public function createModel(string $model, array $options = []): void
    {
        $appModel   = new Model\Application();
        $modelClass = $appModel->createModel($model, getcwd(), (isset($options['data'])));

        $this->console->write("Model class '" . $modelClass ."' created.");
        $this->console->write();
        $this->console->write('Done!');
    }

    /**
     * Create view command
     *
     * @param  string $view
     * @return void
     */
    public function createView(string $view): void
    {
        $appModel = new Model\Application();
        $viewFile = $appModel->createView($view, getcwd());

        $this->console->write("View file '" . $viewFile ."' created.");
        $this->console->write();
        $this->console->write('Done!');
    }

}
