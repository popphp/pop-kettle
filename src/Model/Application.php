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

use Pop\Dir\Dir;
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
        // API-only
        if (($api === true) && empty($web) && empty($cli)) {
            $install = 'api';
        // Web+API
        } else if (($web === true) && ($api === true) && empty($cli)) {
            $install = 'web-api';
        // API+CLI
        } else if (($api === true) && ($cli === true) && empty($web)) {
            $install = 'api-cli';
        // Web+CLI
        } else if (($web === true) && ($cli === true) && empty($api)) {
            $install = 'web-cli';
        // CLI-only
        } else if (($cli === true) && empty($web) && empty($api)) {
            $install = 'cli';
        // Install all
        } else if (($web === true) && ($api === true) && ($cli === true)) {
            $install = 'web-api-cli';
        // Default to web-only
        } else {
            $install = 'web';
        }

        $this->install($install, $location, $namespace);
    }

    /**
     * Install application files
     *
     * @param string  $install
     * @param string  $location
     * @param string  $namespace
     * @param boolean $web
     * @param boolean $api
     * @param boolean $cli
     */
    public function install($install, $location, $namespace)
    {
        $path = __DIR__ . '/../../config/templates/' . $install;
        $dir  = new Dir($path);
        foreach ($dir as $entry) {
            if (is_dir($path . DIRECTORY_SEPARATOR . $entry)) {
                $d = new Dir($path . DIRECTORY_SEPARATOR . $entry);
                $d->copyTo($location);
            }
        }
    }

}