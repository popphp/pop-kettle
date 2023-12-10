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

use Pop\Kettle\Model;

/**
 * Console application controller class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <nick@nolainteractive.com>
 * @copyright  Copyright (c) 2012-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    2.0.0
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
        } else if (str_contains($name, ' ')) {
            $name = '"' . $name . '"';
        }

        $this->console->write();
        foreach ($envs as $env => $i) {
            $this->console->write($i . ': ' . $env);
        }
        $this->console->write();

        $e   = $this->console->prompt('Please choose an app environments from above: ', $envs);
        $env = array_search($e, $envs);

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

        $this->console->write("Installing files for '" . $namespace ."'...");
        $this->console->write();

        $createDb = $this->console->prompt(
            'Would you like to configure a database? [Y/N] ', ['y', 'n']
        );

        if (strtolower($createDb) == 'y') {
            $dbModel->configure($this->console, $location);
        }

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
     * @return void
     */
    public function createModel(string $model): void
    {
        $appModel   = new Model\Application();
        $modelClass = $appModel->createModel($model, getcwd());

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
