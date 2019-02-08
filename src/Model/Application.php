<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2018 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Kettle\Model;

use Pop\Code;
use Pop\Model\AbstractModel;

/**
 * Application model class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2018 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    0.0.1-alpha
 */
class Application extends AbstractModel
{

    /**
     * Init application
     *
     * @param string  $location
     * @param string  $namespace
     * @param boolean $web
     * @param boolean $api
     * @param boolean $cli
     */
    public function init($location, $namespace, $web = null, $api = null, $cli = null)
    {
        // Web-only or API-only
        if ((empty($web) && empty($api) && empty($cli)) ||
            (($web === true) && empty($api) && empty($cli)) ||
            (($api === true) && empty($web) && empty($cli))) {

        // Web+API
        } else if (($web === true) && ($api === true) && empty($cli)) {

        // API+CLI or Web+CLI
        } else if ((($api === true) && ($cli === true) && empty($web)) ||
            (($web === true) && ($cli === true) && empty($api))) {

        // CLI-only
        } else if (($cli === true) && empty($web) && empty($api)) {

        // Install all
        } else if (($web === true) && ($api === true) && ($cli === true)) {

        }
    }

}