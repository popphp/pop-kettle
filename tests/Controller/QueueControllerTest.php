<?php

namespace Pop\Kettle\Test\Controller;

use Pop\Console\Console;
use Pop\Kettle;
use Pop\Kettle\Test\Fixtures\AppTestTrait;
use PHPUnit\Framework\TestCase;

class QueueControllerTest extends TestCase
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

    private function controller(?Console $console = null): Kettle\Controller\QueueController
    {
        return new Kettle\Controller\QueueController($this->makeApp(), $console ?? new Console(120, '    '));
    }

    private function seedApp(): void
    {
        $this->scaffoldApp('cli', 'QueueCtrlApp');

        $autoloader = include __DIR__ . '/../../vendor/autoload.php';
        $autoloader->addPsr4('QueueCtrlApp\\', getcwd() . '/app/src');
    }

    private function seedFileQueueConfig(string $queue = 'default'): void
    {
        $folder = getcwd() . '/database/queue/' . $queue;
        mkdir($folder, 0777, true);

        file_put_contents(getcwd() . '/app/config/queue.php', '<?php return ' . var_export([
            $queue => [
                'adapter'  => 'file',
                'folder'   => $folder,
                'priority' => 'FIFO',
                'lease'    => 60,
                'weight'   => 0,
            ],
        ], true) . ';');
    }

    public function testWorkWithoutScaffoldedAppPrintsFriendlyError()
    {
        ob_start();
        $this->controller()->work('default', []);
        $result = ob_get_clean();

        $this->assertStringContainsString('No application', $result);
    }

    public function testWorkWithMissingQueueConfigPrintsFriendlyError()
    {
        $this->seedApp();

        ob_start();
        $this->controller()->work('default', []);
        $result = ob_get_clean();

        $this->assertStringContainsString('queue configuration was not found', $result);
    }

    public function testWorkOnceRunsSinglePass()
    {
        $this->seedApp();
        $this->seedFileQueueConfig();

        ob_start();
        $this->controller()->work('default', ['once' => true]);
        $result = ob_get_clean();

        $this->assertStringContainsString('Worker pass complete.', $result);
    }

    public function testSchedulerOnceRunsSinglePass()
    {
        $this->seedApp();
        $this->seedFileQueueConfig();

        ob_start();
        $this->controller()->scheduler('default', ['once' => true]);
        $result = ob_get_clean();

        $this->assertStringContainsString('Scheduler pass complete.', $result);
    }

    public function testClearPrintsConfirmation()
    {
        $this->seedApp();
        $this->seedFileQueueConfig();

        ob_start();
        $this->controller()->clear('default', []);
        $result = ob_get_clean();

        $this->assertStringContainsString("Queue 'default' cleared.", $result);
    }

    public function testJobsPrintsPendingCount()
    {
        $this->seedApp();
        $this->seedFileQueueConfig();

        ob_start();
        $this->controller()->jobs('default');
        $result = ob_get_clean();

        $this->assertStringContainsString('Pending: 0', $result);
    }

    public function testTasksPrintsNoScheduledTasksWhenEmpty()
    {
        $this->seedApp();
        $this->seedFileQueueConfig();

        ob_start();
        $this->controller()->tasks('default');
        $result = ob_get_clean();

        $this->assertStringContainsString('No scheduled tasks.', $result);
    }

}
