<?php

namespace Pop\Kettle\Test\Model;

use Pop\Kettle\Model;
use Pop\Kettle\Test\Fixtures\AppTestTrait;
use PHPUnit\Framework\TestCase;

class AssetTest extends TestCase
{

    use AppTestTrait;

    protected function setUp(): void
    {
        $this->enterSandbox();
    }

    protected function tearDown(): void
    {
        $this->leaveSandbox();
        putenv('FAKE_NPM_LOG');
    }

    public function testIsInstalledFalseWithoutPackageJson()
    {
        $asset = new Model\Asset();
        $this->assertFalse($asset->isInstalled(getcwd()));
    }

    public function testIsInstalledTrueWithPackageJson()
    {
        touch(getcwd() . '/package.json');
        $asset = new Model\Asset();
        $this->assertTrue($asset->isInstalled(getcwd()));
    }

    public function testIsNpmAvailableFalseForBogusBinary()
    {
        $asset = new Model\Asset('definitely-not-a-real-binary-xyz');
        $this->assertFalse($asset->isNpmAvailable());
    }

    public function testInstallInvokesConfiguredBinary()
    {
        $logPath = getcwd() . '/npm.log';
        putenv('FAKE_NPM_LOG=' . $logPath);

        $asset = new Model\Asset(__DIR__ . '/../tmp/fake-npm');
        $asset->install(getcwd());

        $log = file_get_contents($logPath);
        $this->assertStringContainsString('install', $log);
        $this->assertStringContainsString(getcwd(), $log);
    }

    public function testWatchInvokesConfiguredBinary()
    {
        $logPath = getcwd() . '/npm.log';
        putenv('FAKE_NPM_LOG=' . $logPath);

        $asset = new Model\Asset(__DIR__ . '/../tmp/fake-npm');
        $asset->watch(getcwd());

        $this->assertStringContainsString('run watch', file_get_contents($logPath));
    }

    public function testBuildInvokesConfiguredBinary()
    {
        $logPath = getcwd() . '/npm.log';
        putenv('FAKE_NPM_LOG=' . $logPath);

        $asset = new Model\Asset(__DIR__ . '/../tmp/fake-npm');
        $asset->build(getcwd());

        $this->assertStringContainsString('run build', file_get_contents($logPath));
    }
}
