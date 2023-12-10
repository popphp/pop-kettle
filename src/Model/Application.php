<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Kettle\Model;

use Pop\Code\Generator;
use Pop\Dir\Dir;
use Pop\Model\AbstractModel;
use Pop\Kettle\Exception;

/**
 * Application model class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    2.1.0
 */
class Application extends AbstractModel
{

    /**
     * Init application
     *
     * @param  string $location
     * @param  string $namespace
     * @param  ?bool  $web
     * @param  ?bool  $api
     * @param  ?bool  $cli
     * @param  string $name
     * @param  string $env
     * @param  string $url
     * @return void
     */
    public function init(
        string $location, string $namespace, ?bool $web = null, ?bool $api = null, ?bool $cli = null,
        string $name = 'Pop', string $env = 'local', string $url = 'http://localhost'
    ): void
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

        $this->install($install, $location, $namespace, $name, $env, $url);
    }

    /**
     * Install application files
     *
     * @param  string $install
     * @param  string $location
     * @param  string $namespace
     * @param  string $name
     * @param  string $env
     * @param  string $url
     * @return void
     */
    public function install(
        string $install, string $location, string $namespace,
        string $name = 'Pop', string $env = 'local', string $url = 'http://localhost'
    ): void
    {
        $script = strtolower(str_replace('\\', '-', $namespace));
        $path   = realpath(__DIR__ . '/../../config/templates/' . $install);
        $dir    = new Dir($path);
        foreach ($dir as $entry) {
            if ($path . DIRECTORY_SEPARATOR . $entry) {
                $d = new Dir($path . DIRECTORY_SEPARATOR . $entry);
                $d->copyTo($location);
            }
        }

        $dir = new Dir($location . '/app', [
            'filesOnly' => true,
            'recursive' => true,
            'absolute'  => true
        ]);

        foreach ($dir as $file) {
            file_put_contents($file, str_replace(['MyApp', 'myapp'], [$namespace, $script], file_get_contents($file)));
        }

        if (file_exists($location . DIRECTORY_SEPARATOR . 'public')) {
            file_put_contents(
                $location . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'index.php',
                str_replace(
                    ['MyApp', 'myapp'], [$namespace, $script],
                    file_get_contents($location . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'index.php')
                )
            );
        }

        if (file_exists($location . DIRECTORY_SEPARATOR . 'script')) {
            file_put_contents(
                $location . DIRECTORY_SEPARATOR . 'script' . DIRECTORY_SEPARATOR . 'myapp',
                str_replace(
                    ['MyApp', 'myapp'], [$namespace, $script],
                    file_get_contents($location . DIRECTORY_SEPARATOR . 'script' . DIRECTORY_SEPARATOR . 'myapp')
                )
            );
            rename(
                $location . DIRECTORY_SEPARATOR . 'script' . DIRECTORY_SEPARATOR . 'myapp',
                $location . DIRECTORY_SEPARATOR . 'script' . DIRECTORY_SEPARATOR . $script
            );
            chmod($location . DIRECTORY_SEPARATOR . 'script' . DIRECTORY_SEPARATOR . $script, 0755);
        }

        if (!file_exists($location . DIRECTORY_SEPARATOR . '/.env')) {
            copy(
                __DIR__ . '/../../config/templates/orig.env',
                $location . DIRECTORY_SEPARATOR . '/.env'
            );
        }

        $env = str_replace([
            'APP_NAME=Pop',
            'APP_ENV=local',
            'APP_URL=http://localhost',
        ], [
            'APP_NAME=' . $name,
            'APP_ENV=' . $env,
            'APP_URL=' . $url,
        ], file_get_contents($location . DIRECTORY_SEPARATOR . '/.env'));

        file_put_contents($location . DIRECTORY_SEPARATOR . '/.env', $env);
    }

    /**
     * Create controller method
     *
     * @param  string $ctrl
     * @param  string $location
     * @param  ?bool  $web
     * @param  ?bool  $api
     * @param  ?bool  $cli
     * @throws Exception
     * @return array
     */
    public function createController(
        string $ctrl, string $location, ?bool $web = null, ?bool $api = null, ?bool $cli = null
    ): array
    {
        $namespace = $this->getNamespace($location);

        $cliFolder = $location . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR .
            'Console' . DIRECTORY_SEPARATOR . 'Controller';

        $httpFolder = $location . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR .
            'Http' . DIRECTORY_SEPARATOR . 'Controller';

        $webFolder = $location . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR .
            'Http' . DIRECTORY_SEPARATOR . 'Web' . DIRECTORY_SEPARATOR . 'Controller';

        $apiFolder = $location . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR .
            'Http' . DIRECTORY_SEPARATOR . 'Api' . DIRECTORY_SEPARATOR . 'Controller';

        $createdCtrls = [];

        // Create CLI controller
        if ($cli === true) {
            if (!file_exists($cliFolder)) {
                throw new Exception('Error: The console folder and namespace has not been created');
            }
            $cliNamespace = $namespace . "\\Console\\Controller";

            if (strpos($ctrl, DIRECTORY_SEPARATOR)) {
                $folders = explode(DIRECTORY_SEPARATOR, $ctrl);
                $ctrl    = array_pop($folders);

                foreach ($folders as $folder) {
                    $cliNamespace .= "\\" . $folder;
                    $cliFolder    .= DIRECTORY_SEPARATOR . $folder;
                    if (!file_exists($cliFolder)) {
                        mkdir($cliFolder);
                    }
                }
            }
            $cliCtrlClassObject = new Generator\ClassGenerator($ctrl);
            $cliCtrlClassObject->setParent("\\" . $namespace . "\\Console\\Controller\\AbstractController");

            $namespaceObject = new Generator\NamespaceGenerator($cliNamespace);

            $code = new Generator();
            $code->addCodeObjects([$namespaceObject, $cliCtrlClassObject]);
            $code->writeToFile($cliFolder . DIRECTORY_SEPARATOR . $ctrl . '.php');

            $createdCtrls[] = $cliNamespace . "\\" . $ctrl;
        }

        // Create HTTP Web controller
        if ($web === true) {
            if (!file_exists($webFolder)) {
                throw new Exception('Error: The HTTP web folder and namespace has not been created');
            }
            $webNamespace = $namespace . "\\Http\\Web\\Controller";

            if (strpos($ctrl, DIRECTORY_SEPARATOR)) {
                $folders = explode(DIRECTORY_SEPARATOR, $ctrl);
                $ctrl    = array_pop($folders);

                foreach ($folders as $folder) {
                    $webNamespace .= "\\" . $folder;
                    $webFolder    .= DIRECTORY_SEPARATOR . $folder;
                    if (!file_exists($webFolder)) {
                        mkdir($webFolder);
                    }
                }
            }
            $webCtrlClassObject = new Generator\ClassGenerator($ctrl);
            $webCtrlClassObject->setParent("\\" . $namespace . "\\Http\\Web\\Controller\\AbstractController");

            $namespaceObject = new Generator\NamespaceGenerator($webNamespace);

            $code = new Generator();
            $code->addCodeObjects([$namespaceObject, $webCtrlClassObject]);
            $code->writeToFile($webFolder . DIRECTORY_SEPARATOR . $ctrl . '.php');

            $createdCtrls[] = $webNamespace . "\\" . $ctrl;
        }

        // Create HTTP API controller
        if ($api === true) {
            if (!file_exists($apiFolder)) {
                throw new Exception('Error: The HTTP API folder and namespace has not been created');
            }
            $apiNamespace = $namespace . "\\Http\\Api\\Controller";

            if (strpos($ctrl, DIRECTORY_SEPARATOR)) {
                $folders = explode(DIRECTORY_SEPARATOR, $ctrl);
                $ctrl    = array_pop($folders);

                foreach ($folders as $folder) {
                    $apiNamespace .= "\\" . $folder;
                    $apiFolder    .= DIRECTORY_SEPARATOR . $folder;
                    if (!file_exists($apiFolder)) {
                        mkdir($apiFolder);
                    }
                }
            }
            $apiCtrlClassObject = new Generator\ClassGenerator($ctrl);
            $apiCtrlClassObject->setParent("\\" . $namespace . "\\Http\\Api\\Controller\\AbstractController");

            $namespaceObject = new Generator\NamespaceGenerator($apiNamespace);

            $code = new Generator();
            $code->addCodeObjects([$namespaceObject, $apiCtrlClassObject]);
            $code->writeToFile($apiFolder . DIRECTORY_SEPARATOR . $ctrl . '.php');

            $createdCtrls[] = $apiNamespace . "\\" . $ctrl;
        }

        // Create HTTP controller
        if (empty($web) && empty($api)) {
            if (!file_exists($httpFolder)) {
                throw new Exception('Error: The HTTP folder and namespace has not been created');
            }
            $httpNamespace = $namespace . "\\Http\\Controller";
            if (strpos($ctrl, DIRECTORY_SEPARATOR)) {
                $folders = explode(DIRECTORY_SEPARATOR, $ctrl);
                $ctrl    = array_pop($folders);

                foreach ($folders as $folder) {
                    $httpNamespace .= "\\" . $folder;
                    $httpFolder    .= DIRECTORY_SEPARATOR . $folder;
                    if (!file_exists($httpFolder)) {
                        mkdir($httpFolder);
                    }
                }
            }
            $httpCtrlClassObject = new Generator\ClassGenerator($ctrl);
            $httpCtrlClassObject->setParent("\\" . $namespace . "\\Http\\Controller\\AbstractController");

            $namespaceObject = new Generator\NamespaceGenerator($httpNamespace);

            $code = new Generator();
            $code->addCodeObjects([$namespaceObject, $httpCtrlClassObject]);
            $code->writeToFile($httpFolder . DIRECTORY_SEPARATOR . $ctrl . '.php');

            $createdCtrls[] = $httpNamespace . "\\" . $ctrl;
        }

        return $createdCtrls;
    }

    /**
     * Create model method
     *
     * @param  string $model
     * @param  string $location
     * @return string
     */
    public function createModel(string $model, string $location): string
    {
        $namespace   = $this->getNamespace($location) . "\\Model";
        $modelFolder = $location . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Model';
        if (!file_exists($modelFolder)) {
            mkdir($modelFolder);
        }

        if (strpos($model, DIRECTORY_SEPARATOR)) {
            $folders = explode(DIRECTORY_SEPARATOR, $model);
            $model   = array_pop($folders);

            foreach ($folders as $folder) {
                $namespace   .= "\\" . $folder;
                $modelFolder .= DIRECTORY_SEPARATOR . $folder;
                if (!file_exists($modelFolder)) {
                    mkdir($modelFolder);
                }
            }
        }

        $modelClassObject = new Generator\ClassGenerator($model);
        $modelClassObject->setParent('AbstractModel');

        $namespaceObject = new Generator\NamespaceGenerator($namespace);
        $namespaceObject->addUse('Pop\Model\AbstractModel');

        $code = new Generator();
        $code->addCodeObjects([$namespaceObject, $modelClassObject]);
        $code->writeToFile($modelFolder . DIRECTORY_SEPARATOR . $model . '.php');

        return $namespace . "\\" . $model;
    }

    /**
     * Create view method
     *
     * @param  string $view
     * @param  string $location
     * @return string
     */
    public function createView(string $view, string $location): string
    {
        $origView   = $view;
        $viewFolder = $location . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'view';
        if (!file_exists($viewFolder)) {
            mkdir($viewFolder);
        }

        if (strpos($view, DIRECTORY_SEPARATOR)) {
            $folders = explode(DIRECTORY_SEPARATOR, $view);
            $view    = array_pop($folders);

            foreach ($folders as $folder) {
                $viewFolder .= DIRECTORY_SEPARATOR . $folder;
                if (!file_exists($viewFolder)) {
                    mkdir($viewFolder);
                }
            }
        }

        touch($viewFolder . DIRECTORY_SEPARATOR . $view);
        $index = file_get_contents(realpath(__DIR__ . '/../../config/templates/web/app/view/index.phtml'));
        file_put_contents($viewFolder . DIRECTORY_SEPARATOR . $view, $index);

        return $origView;
    }

    /**
     * Get namespace
     *
     * @param  string $location
     * @throws Exception
     * @return string
     */
    public function getNamespace(string $location): string
    {
        if (file_exists($location . DIRECTORY_SEPARATOR . 'app') &&
            file_exists($location . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'src') &&
            file_exists($location . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Application.php')) {
            $fileContents = file_get_contents($location . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Application.php');
            $namespace    = substr($fileContents, (strpos($fileContents, 'namespace ') + 10));
            $namespace    = substr($namespace, 0, strpos($namespace, ';'));

            return $namespace;
        } else {
            throw new Exception('Error: Unable to detect namespace.');
        }
    }

}
