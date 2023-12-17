<?php

namespace Pop\Kettle\Test\Model;

use Pop\Dir\Dir;
use Pop\Kettle\Model;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{


    public function testInitApi()
    {
        $application = new Model\Application();
        $application->init(__DIR__ . '/../tmp/test', 'MyApp', null, true, null);

        $this->assertFileExists(__DIR__ . '/../tmp/test/app');

        $dir = new Dir(__DIR__ . '/../tmp/test/');
        $dir->emptyDir();
    }

    public function testInitCli()
    {
        $application = new Model\Application();
        $application->init(__DIR__ . '/../tmp/test', 'MyApp', null, null, true);

        $this->assertFileExists(__DIR__ . '/../tmp/test/app');
        $dir = new Dir(__DIR__ . '/../tmp/test/');
        $dir->emptyDir();
    }

    public function testInitWebApi()
    {
        $application = new Model\Application();
        $application->init(__DIR__ . '/../tmp/test', 'MyApp', true, true);

        $this->assertFileExists(__DIR__ . '/../tmp/test/app');
        $dir = new Dir(__DIR__ . '/../tmp/test/');
        $dir->emptyDir();
    }

    public function testInitApiCli()
    {
        $application = new Model\Application();
        $application->init(__DIR__ . '/../tmp/test', 'MyApp', null, true, true);

        $this->assertFileExists(__DIR__ . '/../tmp/test/app');
        $dir = new Dir(__DIR__ . '/../tmp/test/');
        $dir->emptyDir();
    }

    public function testInitWebCli()
    {
        $application = new Model\Application();
        $application->init(__DIR__ . '/../tmp/test', 'MyApp', true, null, true);

        $this->assertFileExists(__DIR__ . '/../tmp/test/app');
        $dir = new Dir(__DIR__ . '/../tmp/test/');
        $dir->emptyDir();
    }

    public function testInitWebApiCli()
    {
        $application = new Model\Application();
        $application->init(__DIR__ . '/../tmp/test', 'MyApp', true, true, true);
        $this->assertFileExists(__DIR__ . '/../tmp/test/app');
    }

    public function testCreateCtrl()
    {
        $application = new Model\Application();
        $application->createController('MyController', __DIR__ . '/../tmp/test', true, true, true);
        $this->assertFileExists(__DIR__ . '/../tmp/test/app/src/Console/Controller/MyController.php');
        $this->assertFileExists(__DIR__ . '/../tmp/test/app/src/Http/Web/Controller/MyController.php');
        $this->assertFileExists(__DIR__ . '/../tmp/test/app/src/Http/Api/Controller/MyController.php');
    }

    public function testCreateModel()
    {
        $application = new Model\Application();
        $application->createModel('User', __DIR__ . '/../tmp/test', true);
        $this->assertFileExists(__DIR__ . '/../tmp/test/app/src/Model/User.php');
        $this->assertFileExists(__DIR__ . '/../tmp/test/app/src/Table/Users.php');
    }

    public function testCreateView()
    {
        $application = new Model\Application();
        $application->createView('view.phtml', __DIR__ . '/../tmp/test');
        $this->assertFileExists(__DIR__ . '/../tmp/test/app/view/view.phtml');
        $dir = new Dir(__DIR__ . '/../tmp/test/');
        $dir->emptyDir();
    }

}
