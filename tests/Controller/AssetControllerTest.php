<?php

namespace Pop\Kettle\Test\Controller;

use Pop\Console\Console;
use Pop\Kettle;
use Pop\Kettle\Test\Fixtures\AppTestTrait;
use PHPUnit\Framework\TestCase;

class AssetControllerTest extends TestCase
{

    use AppTestTrait;

    protected string $originalPath = '';

    protected function setUp(): void
    {
        $this->enterSandbox();
        $this->originalPath = (string)getenv('PATH');
        putenv('PATH=' . sys_get_temp_dir() . '/pop-kettle-test-no-such-path-' . uniqid());
    }

    protected function tearDown(): void
    {
        putenv('PATH=' . $this->originalPath);
        $this->leaveSandbox();
    }

    private function controller(?Console $console = null): Kettle\Controller\AssetController
    {
        return new Kettle\Controller\AssetController($this->makeApp(), $console ?? new Console(120, '    '));
    }

    public function testWatchWithNoPackageJsonPrintsMessageAndReturns()
    {
        ob_start();
        $this->controller()->watch();
        $result = ob_get_clean();

        $this->assertStringContainsString('No front-end has been installed', $result);
    }

    public function testBuildWithNoPackageJsonPrintsMessageAndReturns()
    {
        ob_start();
        $this->controller()->build();
        $result = ob_get_clean();

        $this->assertStringContainsString('No front-end has been installed', $result);
    }

    public function testWatchWithNoNpmOnPathPrintsMessageAndReturns()
    {
        touch(getcwd() . '/package.json');

        ob_start();
        $this->controller()->watch();
        $result = ob_get_clean();

        $this->assertStringContainsString('Node/npm was not found', $result);
    }

    public function testBuildWithNoNpmOnPathPrintsMessageAndReturns()
    {
        touch(getcwd() . '/package.json');

        ob_start();
        $this->controller()->build();
        $result = ob_get_clean();

        $this->assertStringContainsString('Node/npm was not found', $result);
    }
}
