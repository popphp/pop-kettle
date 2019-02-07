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
        mkdir($location . '/app');
        mkdir($location . '/app/config');
        mkdir($location . '/app/src');
        mkdir($location . '/app/src/Controller');

        // Web-only or API-only
        if ((empty($web) && empty($api) && empty($cli)) ||
            (($web === true) && empty($api) && empty($cli)) ||
            (($api === true) && empty($web) && empty($cli))) {
            $this->createWeb($location, $namespace, ($api === true));
        // Web+API
        } else if (($web === true) && ($api === true) && empty($cli)) {
            $this->createWebAndApi($location, $namespace);
        // API+CLI or Web+CLI
        } else if ((($api === true) && ($cli === true) && empty($web)) ||
            (($web === true) && ($cli === true) && empty($api))) {
            $this->createWeb($location, $namespace, ($api === true));
            $this->createCli($location, $namespace);
        // CLI-only
        } else if (($cli === true) && empty($web) && empty($api)) {
            $this->createCli($location, $namespace);
        // Install all
        } else if (($web === true) && ($api === true) && ($cli === true)) {
            $this->createWebAndApi($location, $namespace);
            $this->createCli($location, $namespace);
        }
    }

    /**
     * Create web
     *
     * @param  string $location
     * @param  string $namespace
     * @param  boolean $isApi
     * @return void
     */
    public function createWeb($location, $namespace, $isApi)
    {
        mkdir($location . '/public');
        mkdir($location . '/app/src/Http');
        mkdir($location . '/app/src/Http/Controller');
        mkdir($location . '/app/view');

        $httpConfig = new Code\Generator($location . '/app/config/app.http.php', Code\Generator::CREATE_EMPTY);

        $httpConfig->appendToBody('return [');
        $httpConfig->appendToBody("    'routes' => [");
        $httpConfig->appendToBody("        '[/]' => [");
        $httpConfig->appendToBody("            'controller' => '" . $namespace . "\\Http\\Controller\\IndexController',");
        $httpConfig->appendToBody("            'action'     => 'index'");
        $httpConfig->appendToBody('        ],');
        $httpConfig->appendToBody("        '*' => [");
        $httpConfig->appendToBody("            'controller' => '" . $namespace . "\\Http\\Controller\\IndexController',");
        $httpConfig->appendToBody("            'action'     => 'error'");
        $httpConfig->appendToBody('        ]');
        $httpConfig->appendToBody('    ]');
        $httpConfig->appendToBody('];');

        $httpConfig->save();

        $this->createHttpFrontController($location, $namespace);
        $this->createHttpController($location, $namespace, $isApi);
    }

    /**
     * Create web+API
     *
     * @param  string $location
     * @param  string $namespace
     * @return void
     */
    public function createWebAndApi($location, $namespace)
    {
        mkdir($location . '/public');
        mkdir($location . '/app/src/Http');
        mkdir($location . '/app/src/Http/Api');
        mkdir($location . '/app/src/Http/Web');
        mkdir($location . '/app/src/Http/Api/Controller');
        mkdir($location . '/app/src/Http/Web/Controller');
        mkdir($location . '/app/view');

        $httpConfig = new Code\Generator($location . '/app/config/app.http.php', Code\Generator::CREATE_EMPTY);

        $httpConfig->appendToBody('return [');
        $httpConfig->appendToBody("    'routes' => [");
        $httpConfig->appendToBody("        '[/]' => [");
        $httpConfig->appendToBody("            'controller' => '" . $namespace . "\\Http\\Web\\Controller\\IndexController',");
        $httpConfig->appendToBody("            'action'     => 'index'");
        $httpConfig->appendToBody('        ],');
        $httpConfig->appendToBody("        '/api[/]' => [");
        $httpConfig->appendToBody("            'controller' => '" . $namespace . "\\Http\\Api\\Controller\\IndexController',");
        $httpConfig->appendToBody("            'action'     => 'index'");
        $httpConfig->appendToBody('        ],');
        $httpConfig->appendToBody("        '*' => [");
        $httpConfig->appendToBody("            'controller' => '" . $namespace . "\\Http\\Controller\\IndexController',");
        $httpConfig->appendToBody("            'action'     => 'error'");
        $httpConfig->appendToBody('        ]');
        $httpConfig->appendToBody('    ]');
        $httpConfig->appendToBody('];');

        $httpConfig->save();

        $this->createHttpFrontController($location, $namespace);
    }

    /**
     * Create HTTP front controller method
     *
     * @param  string $location
     * @param  string $namespace
     * @return void
     */
    public function createHttpFrontController($location, $namespace)
    {
        $index = new Code\Generator($location . '/public/index.php', Code\Generator::CREATE_EMPTY);

        $index->appendToBody("\$autoloader = include __DIR__ . '/../vendor/autoload.php';");
        $index->appendToBody("\$autoloader->addPsr4('" . $namespace . "\\\\', __DIR__ . '/../app/src');");
        $index->appendToBody("");
        $index->appendToBody("try {");
        $index->appendToBody("    \$app = new Pop\\Application(\$autoloader, include __DIR__ . '/../app/config/app.http.php');");
        $index->appendToBody("    \$app->register(new " .  $namespace . "\\Module());");
        $index->appendToBody("    \$app->run();");
        $index->appendToBody("} catch (\\Exception \$exception) {");
        $index->appendToBody("    \$app = new " .  $namespace . "\\Module();");
        $index->appendToBody("    \$app->httpError(\$exception);");
        $index->appendToBody("}");

        $index->save();

        copy(__DIR__ . '/../../config/resources/.htaccess', $location . '/public/.htaccess');
    }

    /**
     * Create HTTP controller method
     *
     * @param  string  $location
     * @param  string  $namespace
     * @param  boolean $isApi
     * @return void
     */
    protected function createHttpController($location, $namespace, $isApi = false)
    {
        $ctrl = new Code\Generator($location . '/app/src/Controller/IndexController.php', Code\Generator::CREATE_CLASS);

        $ctrl->code()->setName('IndexController')
            ->setParent('\Pop\Controller\AbstractController');

        $namespaceObject = new Code\Generator\NamespaceGenerator($namespace . '\Controller');
        $namespaceObject->setUse('Pop\Application')
            ->setUse('Pop\Http\Request')
            ->setUse('Pop\Http\Response');

        if (!$isApi) {
            $namespaceObject->setUse('Pop\View\View');
        }

        $ctrl->code()->setNamespace($namespaceObject);

        $ctrl->code()->addProperty(new Code\Generator\PropertyGenerator('application', 'Application'));
        $ctrl->code()->addProperty(new Code\Generator\PropertyGenerator('request', 'Request'));
        $ctrl->code()->addProperty(new Code\Generator\PropertyGenerator('response', 'Response'));

        $constructMethod = new Code\Generator\MethodGenerator('__construct');
        $constructMethod->addArgument('application', null, 'Application')
            ->addArgument('request', null, 'Request')
            ->addArgument('response', null, 'Response');

        $constructMethod->appendToBody('$this->application = $application;');
        $constructMethod->appendToBody('$this->request     = $request;');
        $constructMethod->appendToBody('$this->response    = $response;');

        $applicationMethod = new Code\Generator\MethodGenerator('application');
        $applicationMethod->appendToBody('return $this->application;');

        $requestMethod = new Code\Generator\MethodGenerator('request');
        $requestMethod->appendToBody('return $this->request;');

        $responseMethod = new Code\Generator\MethodGenerator('response');
        $responseMethod->appendToBody('return $this->response;');

        $indexMethod = new Code\Generator\MethodGenerator('index');

        if ($isApi) {

        } else {
            $indexMethod->appendToBody("\$view        = new View(__DIR__ . '/../../view/index.phtml');");
            $indexMethod->appendToBody("\$view->title = '" . $namespace . "';");
            $indexMethod->appendToBody("\$this->response->setBody(\$view->render());");
            $indexMethod->appendToBody("\$this->response->send();");
        }

        if (strpos($namespace, '\\Http') !== false) {
            $indexMethod->appendToBody("\$view        = new View(__DIR__ . '/../../../view/index.phtml');");
        } else {
            $indexMethod->appendToBody("\$view        = new View(__DIR__ . '/../../view/index.phtml');");
        }
        $indexMethod->appendToBody("\$view->title = '" . str_replace('\\Http', '', $namespace) . "';");
        $indexMethod->appendToBody("\$this->response->setBody(\$view->render());");
        $indexMethod->appendToBody("\$this->response->send();");

        $errorMethod = new Code\Generator\MethodGenerator('error');
        if (strpos($namespace, '\\Http') !== false) {
            $errorMethod->appendToBody("\$view        = new View(__DIR__ . '/../../../view/error.phtml');");
        } else {
            $errorMethod->appendToBody("\$view        = new View(__DIR__ . '/../../view/error.phtml');");
        }
        $errorMethod->appendToBody("\$view->title = 'Error';");
        $errorMethod->appendToBody("\$this->response->setBody(\$view->render());");
        $errorMethod->appendToBody("\$this->response->send(404);");

        $ctrl->code()->addMethod($constructMethod);
        $ctrl->code()->addMethod($applicationMethod);
        $ctrl->code()->addMethod($requestMethod);
        $ctrl->code()->addMethod($responseMethod);
        $ctrl->code()->addMethod($indexMethod);
        $ctrl->code()->addMethod($errorMethod);

        $ctrl->save();
    }

    /**
     * Create CLI
     *
     * @param  string $location
     * @param  string $namespace
     * @return void
     */
    public function createCli($location, $namespace)
    {
        mkdir($location . '/script');
        mkdir($location . '/app/src/Console');
        mkdir($location . '/app/src/Console/Controller');

        $consoleConfig = new Code\Generator($location . '/app/config/app.console.php', Code\Generator::CREATE_EMPTY);

        $consoleConfig->appendToBody('return [');
        $consoleConfig->appendToBody("    'routes' => [");
        $consoleConfig->appendToBody("        'help' => [");
        $consoleConfig->appendToBody("            'controller' => '" . $namespace . "\\Console\\Controller\\ConsoleController',");
        $consoleConfig->appendToBody("            'action'     => 'help'");
        $consoleConfig->appendToBody('        ],');
        $consoleConfig->appendToBody("        '*' => [");
        $consoleConfig->appendToBody("            'controller' => '" . $namespace . "\\Console\\Controller\\ConsoleController',");
        $consoleConfig->appendToBody("            'action'     => 'error'");
        $consoleConfig->appendToBody('        ]');
        $consoleConfig->appendToBody('    ]');
        $consoleConfig->appendToBody('];');

        $consoleConfig->save();

        $app = new Code\Generator($location . '/script/' . strtolower($namespace), Code\Generator::CREATE_EMPTY);
        $app->setEnv('#!/usr/bin/php');
        $app->appendToBody("\$autoloader = include __DIR__ . '/../vendor/autoload.php';");
        $app->appendToBody("\$autoloader->addPsr4('" . $namespace . "\\\\', __DIR__ . '/../app/src');");
        $app->appendToBody("");
        $app->appendToBody("try {");
        $app->appendToBody("    \$app = new Pop\\Application(\$autoloader, include __DIR__ . '/../app/config/app.console.php');");
        $app->appendToBody("    \$app->register(new " .  $namespace . "\\Module());");
        $app->appendToBody("    \$app->run();");
        $app->appendToBody("} catch (\\Exception \$exception) {");
        $app->appendToBody("    \$app = new " .  $namespace . "\\Module();");
        $app->appendToBody("    \$app->cliError(\$exception);");
        $app->appendToBody("}");

        $app->save();

        chmod($location . '/script/' . strtolower($namespace), 0755);

        $ctrl = new Code\Generator($location . '/app/src/Console/Controller/ConsoleController.php', Code\Generator::CREATE_CLASS);
        $ctrl->code()->setName('ConsoleController')
            ->setParent('\Pop\Controller\AbstractController');

        $namespaceObject = new Code\Generator\NamespaceGenerator($namespace . '\Console\Controller');
        $namespaceObject->setUse('Pop\Application')
            ->setUse('Pop\Console\Console');

        $ctrl->code()->setNamespace($namespaceObject);

        $ctrl->code()->addProperty(new Code\Generator\PropertyGenerator('application', 'Application'));
        $ctrl->code()->addProperty(new Code\Generator\PropertyGenerator('console', 'Console'));

        $constructMethod = new Code\Generator\MethodGenerator('__construct');
        $constructMethod->addArgument('application', null, 'Application')
            ->addArgument('console', null, 'Console');

        $constructMethod->appendToBody('$this->application = $application;');
        $constructMethod->appendToBody('$this->console     = $console;');

        $applicationMethod = new Code\Generator\MethodGenerator('application');
        $applicationMethod->appendToBody('return $this->application;');

        $consoleMethod = new Code\Generator\MethodGenerator('console');
        $consoleMethod->appendToBody('return $this->console;');

        $helpMethod = new Code\Generator\MethodGenerator('help');
        $helpMethod->appendToBody("\$this->console->help();");

        $errorMethod = new Code\Generator\MethodGenerator('error');
        $errorMethod->appendToBody("throw new \\" . $namespace . "\\Exception('Invalid Command');");

        $ctrl->code()->addMethod($constructMethod);
        $ctrl->code()->addMethod($applicationMethod);
        $ctrl->code()->addMethod($consoleMethod);
        $ctrl->code()->addMethod($helpMethod);
        $ctrl->code()->addMethod($errorMethod);

        $ctrl->save();
    }

}