<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/pop-bootstrap
 * @author     Nick Sagona, III <nick@nolainteractive.com>
 * @copyright  Copyright (c) 2012-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
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
 * @copyright  Copyright (c) 2012-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    1.1.0
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

}