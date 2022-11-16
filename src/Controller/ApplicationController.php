<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/pop-bootstrap
 * @author     Nick Sagona, III <nick@nolainteractive.com>
 * @copyright  Copyright (c) 2012-2023 NOLA Interactive, LLC. (http://www.nolainteractive.com)
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
 * @copyright  Copyright (c) 2012-2023 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    1.6.2
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
    public function init($namespace, array $options = [])
    {
        $web = (isset($options['web']));
        $api = (isset($options['api']));
        $cli = (isset($options['cli']));

        $appModel = new Model\Application();
        $dbModel  = new Model\Database();
        $location = getcwd();

        $appModel->init($location, $namespace, $web, $api, $cli);

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
    public function createController($ctrl, array $options = [])
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
    public function createModel($model)
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
    public function createView($view)
    {
        $appModel = new Model\Application();
        $viewFile = $appModel->createView($view, getcwd());

        $this->console->write("View file '" . $viewFile ."' created.");
        $this->console->write();
        $this->console->write('Done!');
    }

}