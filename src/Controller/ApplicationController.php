<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/pop-bootstrap
 * @author     Nick Sagona, III <nick@nolainteractive.com>
 * @copyright  Copyright (c) 2012-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Kettle\Controller;

use Pop\Console\Console;
use Pop\Kettle\Model;

/**
 * Console application controller class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <nick@nolainteractive.com>
 * @copyright  Copyright (c) 2012-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    0.0.1-alpha
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
        $appModel->init(getcwd(), $namespace, $web, $api, $cli);

        $this->console->write('App init! ' . $namespace);
    }

}