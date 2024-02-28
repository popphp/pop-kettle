<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/pop-bootstrap
 * @author     Nick Sagona, III <nick@nolainteractive.com>
 * @copyright  Copyright (c) 2012-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
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
 * @author     Nick Sagona, III <nick@nolainteractive.com>
 * @copyright  Copyright (c) 2012-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    2.3.0
 */
class ApplicationController extends AbstractController
{

    /**
     * Init command
     *
     * @param  string $namespace
     * @param  array  $options
     * @return void
     */
    public function init(string $namespace, array $options = []): void
    {
        $web  = (isset($options['web']));
        $api  = (isset($options['api']));
        $cli  = (isset($options['cli']));
        $envs = [
            'local'      => 1,
            'dev'        => 2,
            'testing'    => 3,
            'staging'    => 4,
            'production' => 5,
        ];

        $name = $this->console->prompt('What is the name of your app? [Pop] ', null, true);
        if ($name == '') {
            $name = 'Pop';
        } else if (str_contains($name, ' ') && !str_starts_with($name, '"') && !str_ends_with($name, '"')) {
            $name = '"' . $name . '"';
        }

        $this->console->write();
        foreach ($envs as $env => $i) {
            $this->console->write($i . ': ' . $env);
        }
        $this->console->write();

        // For testing purposes
        if (isset($_SERVER['X_POP_CONSOLE_INPUT_2'])) {
            $_SERVER['X_POP_CONSOLE_INPUT'] = $_SERVER['X_POP_CONSOLE_INPUT_2'];
        }

        $e   = $this->console->prompt('Please select an app environment from above: ', $envs);
        $env = array_search($e, $envs);

        // For testing purposes
        if (isset($_SERVER['X_POP_CONSOLE_INPUT_3'])) {
            $_SERVER['X_POP_CONSOLE_INPUT'] = $_SERVER['X_POP_CONSOLE_INPUT_3'];
        }

        $url = $this->console->prompt('What is the URL of your app? [http://localhost] ', null, true);
        if ($url == '') {
            $url = 'http://localhost';
        }

        $appModel = new Model\Application();
        $dbModel  = new Model\Database();
        $location = getcwd();

        if (empty($namespace)) {
            $namespace = 'MyApp';
        }

        $appModel->init($location, $namespace, $web, $api, $cli, $name, $env, $url);

        $this->console->write();
        $this->console->write("Installing files for '" . $namespace ."'...");
        $this->console->write();

        // For testing purposes
        if (isset($_SERVER['X_POP_CONSOLE_INPUT_4'])) {
            $_SERVER['X_POP_CONSOLE_INPUT'] = $_SERVER['X_POP_CONSOLE_INPUT_4'];
        }

        $createDb = $this->console->prompt(
            'Would you like to configure a database? [Y/N] ', ['y', 'n']
        );

        if (strtolower($createDb) == 'y') {
            $this->console->write();
            $dbModel->configure($this->console, $location);
        }

        $this->console->write();
        $this->console->write('Done!');
    }

    /**
     * Env command
     *
     * @return void
     */
    public function env(): void
    {
        if (App::isProduction()) {
            $this->console->alertWarning('Application in Production', 40);
        } else if (App::isStaging()) {
            $this->console->alertPrimary('Application in Staging', 40);
        } else if (App::isTesting()) {
            $this->console->alertSecondary('Application in Testing', 40);
        } else if (App::isDev()) {
            $this->console->alertDark('Application in Dev', 40);
        } else if (App::isLocal()) {
            $this->console->alertLight('Application in Local', 40);
        }
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
            $secret = (($options['secret'] === null) || ($options['secret'] == '')) ? sha1(time()) : $options['secret'];
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
     * Create controller command
     *
     * @param  string $ctrl
     * @param  array  $options
     * @return void
     */
    public function createController(string $ctrl, array $options = []): void
    {
        $web = (isset($options['web']));
        $api = (isset($options['api']));
        $cli = (isset($options['cli']));

        $appModel    = new Model\Application();
        $ctrlClasses = $appModel->createController($ctrl, getcwd(), $web, $api, $cli);

        foreach ($ctrlClasses as $ctrlClass) {
            $this->console->write("Controller class '" . $ctrlClass ."' created.");
        }

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
