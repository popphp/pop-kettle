<?php

namespace Pop\Kettle\Test\Controller;

use Pop\Console\Console;
use Pop\Kettle;
use Pop\Kettle\Model;
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
        $folder = getcwd() . '/data/queue/' . $queue;
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

    public function testWorkWithNonAutoloadableAppPrintsFriendlyError()
    {
        // App class exists on disk, but isn't autoloadable (e.g. a missing kettle.inc.php),
        // so resolveAppInstance() returns null instead of throwing
        mkdir(getcwd() . '/app/src', 0777, true);
        file_put_contents(
            getcwd() . '/app/src/Application.php',
            '<?php' . PHP_EOL . 'namespace NoSuchAutoloadedQueueApp;' . PHP_EOL . 'class Application {}' . PHP_EOL
        );

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

    public function testWorkLoopStopsAfterOnePassViaEvent()
    {
        $this->seedApp();
        $this->seedFileQueueConfig();

        $worker = (new Model\Queue())->buildWorker(getcwd(), $this->makeApp());

        $events = new \Pop\Event\Manager();
        $ran    = false;
        // Pop\Event\Manager dispatches named params positionally (array_values() after
        // addNamedParameter()) - Worker::workLoop() fires this as ['jobs' => ..., 'worker' => $this],
        // so the listener must accept ($jobs, $worker) in that order, not just ($worker).
        $events->on('worker.work_loop.tick', function($jobs, $worker) use (&$ran) {
            $ran = true;
            $worker->stop();
        });
        $worker->setEvents($events);

        $controller = new class($this->makeApp(), new Console(120, '    '), $worker)
            extends Kettle\Controller\QueueController
        {
            private \Pop\Queue\Worker $testWorker;

            public function __construct($application, $console, \Pop\Queue\Worker $testWorker)
            {
                parent::__construct($application, $console);
                $this->testWorker = $testWorker;
            }

            protected function worker(string $queue): ?\Pop\Queue\Worker
            {
                return $this->testWorker;
            }
        };

        ob_start();
        $controller->work('default', []);
        ob_end_clean();

        $this->assertTrue($ran);
    }

    public function testSchedulerLoopStopsAfterOnePassViaEvent()
    {
        $this->seedApp();
        $this->seedFileQueueConfig();

        $worker = (new Model\Queue())->buildWorker(getcwd(), $this->makeApp());

        $events = new \Pop\Event\Manager();
        $ran    = false;
        // Same positional-dispatch note as above - Worker::runLoop() fires this as
        // ['tasks' => ..., 'worker' => $this], so ($tasks, $worker) in that order.
        $events->on('worker.run_loop.tick', function($tasks, $worker) use (&$ran) {
            $ran = true;
            $worker->stop();
        });
        $worker->setEvents($events);

        $controller = new class($this->makeApp(), new Console(120, '    '), $worker)
            extends Kettle\Controller\QueueController
        {
            private \Pop\Queue\Worker $testWorker;

            public function __construct($application, $console, \Pop\Queue\Worker $testWorker)
            {
                parent::__construct($application, $console);
                $this->testWorker = $testWorker;
            }

            protected function worker(string $queue): ?\Pop\Queue\Worker
            {
                return $this->testWorker;
            }
        };

        ob_start();
        $controller->scheduler('default', []);
        ob_end_clean();

        $this->assertTrue($ran);
    }

}
