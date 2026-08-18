<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Kettle\Model;

use Pop\Code\Generator;
use Pop\Dir\Dir;
use Pop\Utils\AbstractModel;
use Pop\Kettle\Exception;
use Pop\Utils\Str;

/**
 * Application model class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
class Application extends AbstractModel
{

    /**
     * Init application
     *
     * @param  string  $location
     * @param  string  $namespace
     * @param  ?bool   $web
     * @param  ?bool   $api
     * @param  ?bool   $cli
     * @param  string  $name
     * @param  string  $env
     * @param  string  $url
     * @param  bool    $cliApp
     * @param  bool    $createDb
     * @param  ?string $frontend
     * @return void
     */
    public function init(
        string $location, string $namespace, ?bool $web = null, ?bool $api = null, ?bool $cli = null,
        string $name = 'MyApp', string $env = 'local', string $url = '', bool $cliApp = false, bool $createDb = false,
        ?string $frontend = null
    ): void
    {
        $install = self::resolveInstallType($web, $api, $cli);

        $this->install($install, $location, $namespace, $name, $env, $url, $cliApp, $createDb, $frontend);
    }

    /**
     * Resolve the install flavor from the web/api/cli flags
     *
     * @param  ?bool $web
     * @param  ?bool $api
     * @param  ?bool $cli
     * @return string
     */
    public static function resolveInstallType(?bool $web, ?bool $api, ?bool $cli): string
    {
        // API-only
        if (($api === true) && empty($web) && empty($cli)) {
            return 'api';
        // Web+API
        } else if (($web === true) && ($api === true) && empty($cli)) {
            return 'web-api';
        // API+CLI
        } else if (($api === true) && ($cli === true) && empty($web)) {
            return 'api-cli';
        // Web+CLI
        } else if (($web === true) && ($cli === true) && empty($api)) {
            return 'web-cli';
        // CLI-only
        } else if (($cli === true) && empty($web) && empty($api)) {
            return 'cli';
        // Install all
        } else if (($web === true) && ($api === true) && ($cli === true)) {
            return 'web-api-cli';
        // Default to web-only
        } else {
            return 'web';
        }
    }

    /**
     * Install application files
     *
     * @param  string  $install
     * @param  string  $location
     * @param  string  $namespace
     * @param  string  $name
     * @param  string  $env
     * @param  string  $url
     * @param  bool    $cliApp
     * @param  bool    $createDb
     * @param  ?string $frontend
     * @return void
     */
    public function install(
        string $install, string $location, string $namespace, string $name = 'MyApp',
        string $env = 'local', string $url = '', bool $cliApp = false, bool $createDb = false,
        ?string $frontend = null
    ): void
    {
        if (!file_exists($location . DIRECTORY_SEPARATOR . 'kettle.inc.php') &&
            file_exists($location . DIRECTORY_SEPARATOR . 'kettle.inc.orig.php')) {
            copy($location . DIRECTORY_SEPARATOR . 'kettle.inc.orig.php', $location . DIRECTORY_SEPARATOR . 'kettle.inc.php');
        }

        $script = strtolower(str_replace('\\', '-', $namespace));
        $path   = realpath(__DIR__ . '/../../config/templates/codebase/' . $install);
        $dir    = new Dir($path);
        foreach ($dir as $entry) {
            if (is_dir($path . DIRECTORY_SEPARATOR . $entry)) {
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

        // Set up web /public folder and files
        if (str_contains($install, 'web')) {
            mkdir($location . DIRECTORY_SEPARATOR . 'public');
            copy(__DIR__ . '/../../config/templates/public/.htaccess', $location . DIRECTORY_SEPARATOR . 'public/.htaccess');
            copy(__DIR__ . '/../../config/templates/public/index.php', $location . DIRECTORY_SEPARATOR . 'public/index.php');

            file_put_contents(
                $location . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'index.php',
                str_replace(
                    ['MyApp', 'myapp'], [$namespace, $script],
                    file_get_contents($location . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'index.php')
                )
            );

            // Copy view files
            mkdir($location . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'view');
            $indexView = ($frontend !== null) ? 'index-' . $frontend . '.phtml' : 'index.phtml';
            copy(__DIR__ . '/../../config/templates/view/' . $indexView, $location . DIRECTORY_SEPARATOR . 'app/view/index.phtml');
            copy(__DIR__ . '/../../config/templates/view/error.phtml', $location . DIRECTORY_SEPARATOR . 'app/view/error.phtml');
            copy(__DIR__ . '/../../config/templates/view/exception.phtml', $location . DIRECTORY_SEPARATOR . 'app/view/exception.phtml');
            copy(__DIR__ . '/../../config/templates/view/maintenance.phtml', $location . DIRECTORY_SEPARATOR . 'app/view/maintenance.phtml');

            if ($frontend !== null) {
                $this->installFrontend($frontend, $location, $namespace, $script);
            }
        }

        // Set up CLI /script folder and application script
        if ($cliApp) {
            mkdir($location . DIRECTORY_SEPARATOR . 'script');
            copy(__DIR__ . '/../../config/templates/script/myapp', $location . DIRECTORY_SEPARATOR . 'script/myapp');

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
        } else if (file_exists($location . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Console' . DIRECTORY_SEPARATOR . 'Controller')) {
            $dir = new Dir($location . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Console' . DIRECTORY_SEPARATOR . 'Controller');
            $dir->emptyDir(true);
        }

        // Add writable /data folder
        mkdir($location . DIRECTORY_SEPARATOR . 'data');
        chmod($location . DIRECTORY_SEPARATOR . 'data', 0777);
        touch($location . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . '.empty');

        // Set up database folder
        if ($createDb) {
            mkdir($location . DIRECTORY_SEPARATOR . 'database');
            $dbPath = realpath(__DIR__ . '/../../config/templates/database');
            $dir    = new Dir($dbPath);
            foreach ($dir as $entry) {
                if (is_dir($dbPath . DIRECTORY_SEPARATOR . $entry)) {
                    $d = new Dir($dbPath . DIRECTORY_SEPARATOR . $entry);
                    $d->copyTo($location . DIRECTORY_SEPARATOR . 'database');
                }
            }

            copy(
                __DIR__ . '/../../config/templates/database.php',
                $location . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php'
            );
        } else {
            if (file_exists($location . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.http.php')) {
                file_put_contents(
                    $location . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.http.php',
                    str_replace(
                        "    'database' => include __DIR__ . '/database.php',", "",
                        file_get_contents($location . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.http.php')
                    )
                );
            }
            if (file_exists($location . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.console.php')) {
                file_put_contents(
                    $location . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.console.php',
                    str_replace(
                        "    'database' => include __DIR__ . '/database.php',", "",
                        file_get_contents($location . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.console.php')
                    )
                );
            }
        }

        // Populate kettle.inc.php file autoloader for application
        if (file_exists($location . DIRECTORY_SEPARATOR . 'kettle.inc.php')) {
            $autoloader = "\$autoloader->addPsr4('{$namespace}\\\\', __DIR__ . '/app/src');" . PHP_EOL;
            if (!str_contains(file_get_contents($location . DIRECTORY_SEPARATOR . 'kettle.inc.php'), $autoloader)) {
                file_put_contents($location . DIRECTORY_SEPARATOR . 'kettle.inc.php', $autoloader, FILE_APPEND);
            }
        }

        // Copy .env file over and populate with values
        if (!file_exists($location . DIRECTORY_SEPARATOR . '/.env')) {
            copy(
                __DIR__ . '/../../config/templates/.env.example',
                $location . DIRECTORY_SEPARATOR . '/.env'
            );
        }

        if (str_contains($name, ' ') && !str_starts_with($name, '"') && !str_ends_with($name, '"')) {
            $name = '"' . $name . '"';
        }

        $env = str_replace([
            'APP_NAME=MyApp',
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
     * Install front-end tooling (package.json, vite config, source assets)
     *
     * @param  string $frontend
     * @param  string $location
     * @param  string $namespace
     * @param  string $script
     * @return void
     */
    protected function installFrontend(string $frontend, string $location, string $namespace, string $script): void
    {
        $frontendPath = realpath(__DIR__ . '/../../config/templates/frontend/' . $frontend);
        $dir          = new Dir($frontendPath);

        foreach ($dir as $entry) {
            $entryPath = $frontendPath . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($entryPath)) {
                if (!file_exists($location . DIRECTORY_SEPARATOR . $entry)) {
                    mkdir($location . DIRECTORY_SEPARATOR . $entry);
                }
                (new Dir($entryPath))->copyTo($location . DIRECTORY_SEPARATOR . $entry, false);
            } else {
                copy($entryPath, $location . DIRECTORY_SEPARATOR . $entry);
            }
        }

        $placeholders = ['MyApp', 'myapp'];
        $replacements = [$namespace, $script];

        $packageJson = $location . DIRECTORY_SEPARATOR . 'package.json';
        file_put_contents($packageJson, str_replace($placeholders, $replacements, file_get_contents($packageJson)));

        $assetsDir = new Dir($location . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'assets', [
            'filesOnly' => true, 'recursive' => true, 'absolute' => true
        ]);
        foreach ($assetsDir as $file) {
            file_put_contents($file, str_replace($placeholders, $replacements, file_get_contents($file)));
        }

        $gitignore = $location . DIRECTORY_SEPARATOR . '.gitignore';
        if (!file_exists($gitignore)) {
            file_put_contents($gitignore, 'node_modules/' . PHP_EOL);
        } else if (!str_contains(file_get_contents($gitignore), 'node_modules/')) {
            file_put_contents($gitignore, 'node_modules/' . PHP_EOL, FILE_APPEND);
        }
    }

    /**
     * Create command method
     *
     * @param  string $command
     * @param  string $location
     * @param  bool   $app
     * @throws Exception
     * @return void
     */
    public function createCommand(string $command, string $location, bool $app = false): void
    {
        $command   = strtolower($command);
        $namespace = $this->getNamespace($location);

        $commandFolder = $location . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR .
            'Console' . DIRECTORY_SEPARATOR . 'Command';

        if (!$app) {
            $commandFolder .= DIRECTORY_SEPARATOR . 'Kettle';
        }

        if (!file_exists($commandFolder)) {
            throw new Exception('Error: The command folder and namespace has not been created');
        }

        $classCommandName = (str_contains($command, ':')) ?
            substr($command, (strrpos($command, ':') + 1)) : $command;

        if (!file_exists($commandFolder . DIRECTORY_SEPARATOR . $classCommandName . '.php')) {
            $classCommandName = ucfirst(Str::convertToCamelCase($classCommandName, '-'));

            $commandNamespace = $namespace . "\\Console\\Command";

            if (!$app) {
                $commandNamespace .= '\\Kettle';
            }

            $namespaceObject  = new Generator\NamespaceGenerator($commandNamespace);

            $commandClassObject = new Generator\ClassGenerator($classCommandName);
            $commandClassObject->setParent("\\Pop\\Console\\Command\\AbstractCommand");

            $nameProperty   = new Generator\PropertyGenerator('name', '?string', $command);
            $paramsProperty = new Generator\PropertyGenerator('params', '?string');
            $helpProperty   = new Generator\PropertyGenerator('help', '?string', "This is the " . $command . " command");
            $handleMethod   = new Generator\MethodGenerator('handle', 'public');
            $handleMethod->setBody('/** Add command code here. */')
                ->setDocblock(new Generator\DocblockGenerator("  \$this->application and \$this->console are both available."));

            $commandClassObject->addProperties([$nameProperty, $paramsProperty, $helpProperty])
                ->addMethod($handleMethod);

            $code = new Generator();
            $code->addCodeObjects([$namespaceObject, $commandClassObject]);
            $code->writeToFile($commandFolder . DIRECTORY_SEPARATOR . $classCommandName . '.php');
        }
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
            $scriptFolder = $location . DIRECTORY_SEPARATOR . 'script';

            if (!file_exists($cliFolder) || !file_exists($scriptFolder)) {
                throw new Exception('Error: This application was not initialized with a stand-alone console application.');
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

        // Default to creating HTTP controller
        if (($web === null) && ($api == null) && ($cli === null)) {
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
     * @param  bool   $data
     * @return string
     */
    public function createModel(string $model, string $location, bool $data = false): string
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

        if ($data) {
            $abstractModel = 'AbstractDataModel';
            $useNamespace  = 'Pop\Db\Model\AbstractDataModel';

        } else {
            $abstractModel = 'AbstractModel';
            $useNamespace  = 'Pop\Utils\AbstractModel';
        }

        $modelClassObject = new Generator\ClassGenerator($model);
        $modelClassObject->setParent($abstractModel);

        $namespaceObject = new Generator\NamespaceGenerator($namespace);
        $namespaceObject->addUse($useNamespace);

        $code = new Generator();
        $code->addCodeObjects([$namespaceObject, $modelClassObject]);
        $code->writeToFile($modelFolder . DIRECTORY_SEPARATOR . $model . '.php');

        if ($data) {
            $table = $model;

            if (!str_ends_with($model, 's')) {
                $table .= 's';
            }

            $tableNamespace = $this->getNamespace($location) . "\\Table";
            $tableFolder    = $location . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Table';
            if (!file_exists($tableFolder)) {
                mkdir($tableFolder);
            }

            if (strpos($table, DIRECTORY_SEPARATOR)) {
                $folders = explode(DIRECTORY_SEPARATOR, $table);
                $table   = array_pop($folders);

                foreach ($folders as $folder) {
                    $namespace   .= "\\" . $folder;
                    $tableFolder .= DIRECTORY_SEPARATOR . $folder;
                    if (!file_exists($tableFolder)) {
                        mkdir($tableFolder);
                    }
                }
            }

            $tableClassObject = new Generator\ClassGenerator($table);
            $tableClassObject->setParent('Record');

            $tableNamespaceObject = new Generator\NamespaceGenerator($tableNamespace);
            $tableNamespaceObject->addUse('Pop\Db\Record');

            $code = new Generator();
            $code->addCodeObjects([$tableNamespaceObject, $tableClassObject]);
            $code->writeToFile($tableFolder . DIRECTORY_SEPARATOR . $table . '.php');
        }

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
        $index = file_get_contents(realpath(__DIR__ . '/../../config/templates/view/index.phtml'));
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
        } else if (file_exists($location . DIRECTORY_SEPARATOR . 'app') &&
            file_exists($location . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'src') &&
            file_exists($location . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Module.php')) {
            $fileContents = file_get_contents($location . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Module.php');
            $namespace    = substr($fileContents, (strpos($fileContents, 'namespace ') + 10));
            $namespace    = substr($namespace, 0, strpos($namespace, ';'));

            return $namespace;
        } else {
            throw new Exception('Error: Unable to detect namespace.');
        }
    }

    /**
     * Resolve a full instance of the consuming application, if one is scaffolded, merging
     * $baseRoutes with its own app/src/Console/Command routes
     *
     * @param  string $dir
     * @param  mixed  $autoloader
     * @param  array  $baseRoutes
     * @throws Exception
     * @return ?\Pop\Application
     */
    public function resolveAppInstance(string $dir, mixed $autoloader, array $baseRoutes): ?\Pop\Application
    {
        $namespace = $this->getNamespace($dir);
        $appClass  = $namespace . '\Application';

        if (!class_exists($appClass)) {
            return null;
        }

        $config = file_exists($dir . '/app/config/app.console.php')
            ? include $dir . '/app/config/app.console.php'
            : [];

        $config['routes'] = \Pop\Console\CommandRegistry::loadRoutes($baseRoutes, $dir . '/app/src/Console/Command');

        return new $appClass($autoloader, $config);
    }

}
