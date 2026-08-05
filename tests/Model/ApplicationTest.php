<?php

namespace Pop\Kettle\Test\Model;

use Pop\Kettle\Model;
use Pop\Kettle\Test\Fixtures\AppTestTrait;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{

    use AppTestTrait;

    protected function setUp(): void
    {
        $this->enterSandbox();
    }

    protected function tearDown(): void
    {
        $this->leaveSandbox();
    }

    public function testInitApiOnly()
    {
        $this->seedKettleIncOrig();
        $application = new Model\Application();
        $application->init(getcwd(), 'MyApp', null, true, null);

        $this->assertFileExists(getcwd() . '/app/src/Http/Controller/AbstractController.php');
        $this->assertFileExists(getcwd() . '/app/src/Http/Event');
    }

    public function testInitWebApi()
    {
        $this->seedKettleIncOrig();
        $application = new Model\Application();
        $application->init(getcwd(), 'MyApp', true, true, null);

        $this->assertFileExists(getcwd() . '/app/src/Http/Web/Controller/AbstractController.php');
        $this->assertFileExists(getcwd() . '/app/src/Http/Api/Controller/AbstractController.php');
    }

    public function testInitApiCli()
    {
        $this->seedKettleIncOrig();
        $application = new Model\Application();
        $application->init(getcwd(), 'MyApp', null, true, true);

        $this->assertFileExists(getcwd() . '/app/src/Console/Controller/AbstractController.php');
    }

    public function testInitWebCli()
    {
        $this->seedKettleIncOrig();
        $application = new Model\Application();
        $application->init(getcwd(), 'MyApp', true, null, true);

        $this->assertFileExists(getcwd() . '/app/src/Console/Controller/AbstractController.php');
        $this->assertFileExists(getcwd() . '/app/src/Http/Controller/AbstractController.php');
    }

    public function testInitCliOnly()
    {
        $this->seedKettleIncOrig();
        $application = new Model\Application();
        $application->init(getcwd(), 'MyApp', null, null, true);

        $this->assertFileExists(getcwd() . '/app/src/Console/Controller/AbstractController.php');
        $this->assertFileExists(getcwd() . '/app/src/Console/Command/Kettle/.empty');
    }

    public function testInitWebApiCli()
    {
        $this->seedKettleIncOrig();
        $application = new Model\Application();
        $application->init(getcwd(), 'MyApp', true, true, true);

        $this->assertFileExists(getcwd() . '/app/src/Console/Controller/AbstractController.php');
        $this->assertFileExists(getcwd() . '/app/src/Http/Web/Controller/AbstractController.php');
        $this->assertFileExists(getcwd() . '/app/src/Http/Api/Controller/AbstractController.php');
    }

    public function testInitDefaultsToWeb()
    {
        $this->seedKettleIncOrig();
        $application = new Model\Application();
        $application->init(getcwd(), 'MyApp');

        $this->assertFileExists(getcwd() . '/app/src/Http/Controller/AbstractController.php');
    }

    public function testCreateCommand()
    {
        $this->scaffoldApp('cli');
        $application = new Model\Application();
        $application->createCommand('send-email', getcwd());

        $this->assertFileExists(getcwd() . '/app/src/Console/Command/Kettle/SendEmail.php');
        $this->assertStringContainsString(
            "'send-email'", file_get_contents(getcwd() . '/app/src/Console/Command/Kettle/SendEmail.php')
        );
    }

    public function testCreateCommandWithNamespacedSignature()
    {
        $this->scaffoldApp('cli');
        $application = new Model\Application();
        $application->createCommand('email:send', getcwd());

        $this->assertFileExists(getcwd() . '/app/src/Console/Command/Kettle/Send.php');
        $this->assertStringContainsString(
            "'email:send'", file_get_contents(getcwd() . '/app/src/Console/Command/Kettle/Send.php')
        );
    }

    public function testCreateCommandIsNoopWhenFileAlreadyExists()
    {
        $this->scaffoldApp('cli');
        $application = new Model\Application();
        $application->createCommand('send-email', getcwd());

        $original = file_get_contents(getcwd() . '/app/src/Console/Command/Kettle/SendEmail.php');

        // Creating the same command again should not touch the existing file
        $application->createCommand('send-email', getcwd());

        $this->assertSame($original, file_get_contents(getcwd() . '/app/src/Console/Command/Kettle/SendEmail.php'));
    }

    public function testCreateCommandMissingFolderThrows()
    {
        $this->scaffoldApp('web');
        $application = new Model\Application();

        $this->expectException('Pop\Kettle\Exception');
        $application->createCommand('send-email', getcwd());
    }

    public function testCreateControllerCli()
    {
        $this->scaffoldApp('cli');
        $application = new Model\Application();
        $result      = $application->createController('MyController', getcwd(), null, null, true);

        $this->assertSame(['MyApp\Console\Controller\MyController'], $result);
        $this->assertFileExists(getcwd() . '/app/src/Console/Controller/MyController.php');
    }

    public function testCreateControllerWeb()
    {
        $this->scaffoldApp('web-api');
        $application = new Model\Application();
        $result      = $application->createController('MyController', getcwd(), true, null, null);

        $this->assertSame(['MyApp\Http\Web\Controller\MyController'], $result);
        $this->assertFileExists(getcwd() . '/app/src/Http/Web/Controller/MyController.php');
    }

    public function testCreateControllerApi()
    {
        $this->scaffoldApp('web-api');
        $application = new Model\Application();
        $result      = $application->createController('MyController', getcwd(), null, true, null);

        $this->assertSame(['MyApp\Http\Api\Controller\MyController'], $result);
        $this->assertFileExists(getcwd() . '/app/src/Http/Api/Controller/MyController.php');
    }

    public function testCreateControllerDefault()
    {
        $this->scaffoldApp('web');
        $application = new Model\Application();
        $result      = $application->createController('MyController', getcwd(), null, null, null);

        $this->assertSame(['MyApp\Http\Controller\MyController'], $result);
        $this->assertFileExists(getcwd() . '/app/src/Http/Controller/MyController.php');
    }

    public function testCreateControllerAllFlavors()
    {
        $this->scaffoldApp('web-api-cli');
        $application = new Model\Application();
        $result      = $application->createController('MyController', getcwd(), true, true, true);

        $this->assertCount(3, $result);
        $this->assertFileExists(getcwd() . '/app/src/Console/Controller/MyController.php');
        $this->assertFileExists(getcwd() . '/app/src/Http/Web/Controller/MyController.php');
        $this->assertFileExists(getcwd() . '/app/src/Http/Api/Controller/MyController.php');
    }

    public function testCreateControllerNestedPath()
    {
        $this->scaffoldApp('web');
        $application = new Model\Application();
        $result      = $application->createController('Admin/Users', getcwd(), null, null, null);

        $this->assertSame(['MyApp\Http\Controller\Admin\Users'], $result);
        $this->assertFileExists(getcwd() . '/app/src/Http/Controller/Admin/Users.php');
    }

    public function testCreateControllerCliNestedPath()
    {
        $this->scaffoldApp('cli');
        $application = new Model\Application();
        $result      = $application->createController('Admin/Tools', getcwd(), null, null, true);

        $this->assertSame(['MyApp\Console\Controller\Admin\Tools'], $result);
        $this->assertFileExists(getcwd() . '/app/src/Console/Controller/Admin/Tools.php');
    }

    public function testCreateControllerWebNestedPath()
    {
        $this->scaffoldApp('web-api');
        $application = new Model\Application();
        $result      = $application->createController('Admin/Tools', getcwd(), true, null, null);

        $this->assertSame(['MyApp\Http\Web\Controller\Admin\Tools'], $result);
        $this->assertFileExists(getcwd() . '/app/src/Http/Web/Controller/Admin/Tools.php');
    }

    public function testCreateControllerApiNestedPath()
    {
        $this->scaffoldApp('web-api');
        $application = new Model\Application();
        $result      = $application->createController('Admin/Tools', getcwd(), null, true, null);

        $this->assertSame(['MyApp\Http\Api\Controller\Admin\Tools'], $result);
        $this->assertFileExists(getcwd() . '/app/src/Http/Api/Controller/Admin/Tools.php');
    }

    public function testCreateControllerCliMissingFolderThrows()
    {
        $this->scaffoldApp('web');
        $application = new Model\Application();

        $this->expectException('Pop\Kettle\Exception');
        $application->createController('MyController', getcwd(), null, null, true);
    }

    public function testCreateControllerWebMissingFolderThrows()
    {
        $this->scaffoldApp('cli');
        $application = new Model\Application();

        $this->expectException('Pop\Kettle\Exception');
        $application->createController('MyController', getcwd(), true, null, null);
    }

    public function testCreateControllerApiMissingFolderThrows()
    {
        $this->scaffoldApp('cli');
        $application = new Model\Application();

        $this->expectException('Pop\Kettle\Exception');
        $application->createController('MyController', getcwd(), null, true, null);
    }

    public function testCreateControllerDefaultMissingFolderThrows()
    {
        $this->scaffoldApp('cli');
        $application = new Model\Application();

        $this->expectException('Pop\Kettle\Exception');
        $application->createController('MyController', getcwd(), null, null, null);
    }

    public function testCreateModel()
    {
        $this->scaffoldApp('web');
        $application = new Model\Application();
        $result      = $application->createModel('User', getcwd());

        $this->assertSame('MyApp\Model\User', $result);
        $this->assertFileExists(getcwd() . '/app/src/Model/User.php');
    }

    public function testCreateModelWithDataPluralizesTable()
    {
        $this->scaffoldApp('web');
        $application = new Model\Application();
        $application->createModel('User', getcwd(), true);

        $this->assertFileExists(getcwd() . '/app/src/Model/User.php');
        $this->assertFileExists(getcwd() . '/app/src/Table/Users.php');
    }

    public function testCreateModelWithDataDoesNotPluralizeWhenAlreadyEndingInS()
    {
        $this->scaffoldApp('web');
        $application = new Model\Application();
        $application->createModel('Status', getcwd(), true);

        $this->assertFileExists(getcwd() . '/app/src/Table/Status.php');
    }

    public function testCreateModelNestedPath()
    {
        $this->scaffoldApp('web');
        $application = new Model\Application();
        $result      = $application->createModel('Admin/Setting', getcwd());

        $this->assertSame('MyApp\Model\Admin\Setting', $result);
        $this->assertFileExists(getcwd() . '/app/src/Model/Admin/Setting.php');
    }

    public function testCreateModelWithDataNestedPath()
    {
        // Note: the generated Table class name is derived from the model's basename
        // (after the nested path is already stripped off), so it lands flat in
        // app/src/Table/ rather than mirroring the model's Admin/ subfolder.
        $this->scaffoldApp('web');
        $application = new Model\Application();
        $application->createModel('Admin/Setting', getcwd(), true);

        $this->assertFileExists(getcwd() . '/app/src/Model/Admin/Setting.php');
        $this->assertFileExists(getcwd() . '/app/src/Table/Settings.php');
    }

    public function testInstallQuotesNameContainingSpaces()
    {
        $this->seedKettleIncOrig();
        $application = new Model\Application();
        $application->install('web', getcwd(), 'MyApp', 'My App Name');

        $this->assertStringContainsString('APP_NAME="My App Name"', file_get_contents(getcwd() . '/.env'));
    }

    public function testCreateViewCreatesMissingBaseFolder()
    {
        mkdir(getcwd() . '/app/src', 0777, true);
        file_put_contents(getcwd() . '/app/src/Application.php', "<?php\n\nnamespace MyApp;\n");

        $application = new Model\Application();
        $application->createView('test.phtml', getcwd());

        $this->assertFileExists(getcwd() . '/app/view/test.phtml');
    }

    public function testCreateView()
    {
        $this->scaffoldApp('web');
        $application = new Model\Application();
        $result      = $application->createView('test.phtml', getcwd());

        $this->assertSame('test.phtml', $result);
        $this->assertFileExists(getcwd() . '/app/view/test.phtml');
    }

    public function testCreateViewNestedPath()
    {
        $this->scaffoldApp('web');
        $application = new Model\Application();
        $application->createView('admin/dashboard.phtml', getcwd());

        $this->assertFileExists(getcwd() . '/app/view/admin/dashboard.phtml');
    }

    public function testGetNamespaceFromModuleFallback()
    {
        mkdir(getcwd() . '/app/src', 0777, true);
        file_put_contents(getcwd() . '/app/src/Module.php', "<?php\n\nnamespace LegacyApp;\n");

        $application = new Model\Application();

        $this->assertSame('LegacyApp', $application->getNamespace(getcwd()));
    }

    public function testGetNamespaceThrowsWhenUndetectable()
    {
        mkdir(getcwd() . '/app/src', 0777, true);

        $application = new Model\Application();

        $this->expectException('Pop\Kettle\Exception');
        $application->getNamespace(getcwd());
    }

}
