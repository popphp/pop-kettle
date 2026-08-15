<?php

namespace Pop\Kettle\Test\Model;

use Pop\Console\Console;
use Pop\Kettle\Model;
use Pop\Kettle\Test\Fixtures\AppTestTrait;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{

    use AppTestTrait;

    protected function setUp(): void
    {
        $this->enterSandbox();
        mkdir(getcwd() . '/app/config', 0777, true);
        copy(__DIR__ . '/../../config/templates/orig.env', getcwd() . '/.env');
    }

    protected function tearDown(): void
    {
        $this->leaveSandbox();
    }

    public function testInit()
    {
        $queue = new Model\Queue();
        $this->assertInstanceOf('Pop\Kettle\Model\Queue', $queue);
    }

    public function testConfigureFileDefaultQueue()
    {
        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream('1', '', '', '', ''));

        $queue  = new Model\Queue();
        $result = $queue->configure($console, getcwd());

        $this->assertSame($queue, $result);
        $this->assertSame('file', $_ENV['QUEUE_ADAPTER']);
        $this->assertSame('FIFO', $_ENV['QUEUE_PRIORITY']);
        $this->assertSame('60', $_ENV['QUEUE_LEASE']);
        $this->assertDirectoryExists(getcwd() . '/data/queue/default');
        $this->assertFileExists(getcwd() . '/app/config/queue.php');

        $config = include getcwd() . '/app/config/queue.php';
        $this->assertSame('file', $config['default']['adapter']);
        $this->assertSame(0, $config['default']['weight']);
    }

    public function testConfigureFileNamedQueue()
    {
        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream('1', '', '', '', '5'));

        $queue = new Model\Queue();
        $queue->configure($console, getcwd(), 'logging');

        $this->assertSame('file', $_ENV['QUEUE_LOGGING_ADAPTER']);
        $this->assertDirectoryExists(getcwd() . '/data/queue/logging');

        $config = include getcwd() . '/app/config/queue.php';
        $this->assertSame('file', $config['logging']['adapter']);
        $this->assertSame(5, $config['logging']['weight']);
    }

    public function testConfigureDatabaseQueue()
    {
        $console = new Console(120, '    ');
        $console->setInputStream($this->createInputStream('2', '', '', '', '', ''));

        $queue = new Model\Queue();
        $queue->configure($console, getcwd());

        $this->assertSame('database', $_ENV['QUEUE_ADAPTER']);
        $this->assertSame('default', $_ENV['QUEUE_CONNECTION']);
        $this->assertSame('pop_queue', $_ENV['QUEUE_TABLE']);

        $config = include getcwd() . '/app/config/queue.php';
        $this->assertSame('database', $config['default']['adapter']);
        $this->assertSame('pop_queue', $config['default']['table']);
    }

    public function testConfigureRedisQueue()
    {
        if (!class_exists('Redis', false)) {
            $this->markTestSkipped('ext-redis is not installed.');
        }

        $console = new Console(120, '    ');
        // 8 prompts: adapter, host, port, prefix, password, priority, lease, weight
        $console->setInputStream($this->createInputStream('3', '', '', '', '', '', '', ''));

        $queue = new Model\Queue();
        $queue->configure($console, getcwd());

        $this->assertSame('redis', $_ENV['QUEUE_ADAPTER']);
        $this->assertSame('localhost', $_ENV['QUEUE_HOST']);
        $this->assertSame('6379', $_ENV['QUEUE_PORT']);
        $this->assertSame('pop-queue', $_ENV['QUEUE_PREFIX']);

        $config = include getcwd() . '/app/config/queue.php';
        $this->assertSame('redis', $config['default']['adapter']);
    }

    public function testConfigureQuotesEnvValuesWithSpacesAndWritesThemVerbatim()
    {
        $folder = 'data/queue/my $1 queue';

        // Configure twice, so both the append branch and the existing-key replace branch are exercised
        for ($i = 0; $i < 2; $i++) {
            $console = new Console(120, '    ');
            $console->setInputStream($this->createInputStream('1', $folder, '', '', ''));
            (new Model\Queue())->configure($console, getcwd());
        }

        $expected = realpath(getcwd() . '/' . $folder);
        $env      = file_get_contents(getcwd() . '/.env');

        $this->assertStringContainsString('QUEUE_FOLDER="' . $expected . '"', $env);
        $this->assertSame(1, substr_count($env, 'QUEUE_FOLDER='));

        // The .env must still be parseable - an unquoted value with a space throws here
        (\Dotenv\Dotenv::createMutable(getcwd()))->load();

        $this->assertSame($expected, $_ENV['QUEUE_FOLDER']);
        $this->assertStringEndsWith('my $1 queue', $_ENV['QUEUE_FOLDER']);
    }

    private function seedFileQueueConfig(string $queue = 'default', int $weight = 0): void
    {
        $folder = getcwd() . '/data/queue/' . $queue;
        mkdir($folder, 0777, true);

        $this->writeQueueConfig([
            $queue => [
                'adapter'  => 'file',
                'folder'   => $folder,
                'priority' => 'FIFO',
                'lease'    => 60,
                'weight'   => $weight,
            ],
        ]);
    }

    private function writeQueueConfig(array $config): void
    {
        file_put_contents(getcwd() . '/app/config/queue.php', '<?php return ' . var_export($config, true) . ';');
    }

    public function testBuildWorkerFileAdapter()
    {
        $this->seedFileQueueConfig();

        $app    = $this->makeApp();
        $worker = (new Model\Queue())->buildWorker(getcwd(), $app);

        $this->assertInstanceOf('Pop\Queue\Worker', $worker);
        $this->assertTrue($worker->hasQueue('default'));
        $this->assertSame($app, $worker->getApplication());
    }

    public function testBuildWorkerAllQueuesRegistersEveryQueueWeighted()
    {
        $this->seedFileQueueConfig('default', 0);

        $folder = getcwd() . '/data/queue/logging';
        mkdir($folder, 0777, true);

        $config           = include getcwd() . '/app/config/queue.php';
        $config['logging'] = [
            'adapter'  => 'file',
            'folder'   => $folder,
            'priority' => 'FIFO',
            'lease'    => 60,
            'weight'   => 10,
        ];
        $this->writeQueueConfig($config);

        $worker = (new Model\Queue())->buildWorker(getcwd(), $this->makeApp(), 'all');

        $this->assertTrue($worker->hasQueue('default'));
        $this->assertTrue($worker->hasQueue('logging'));
        $this->assertSame(10, $worker->getWeight('logging'));
    }

    public function testBuildWorkerMissingQueueKeyThrows()
    {
        $this->seedFileQueueConfig();

        $this->expectException('Pop\Kettle\Exception');
        (new Model\Queue())->buildWorker(getcwd(), $this->makeApp(), 'does-not-exist');
    }

    public function testBuildWorkerMissingConfigFileThrows()
    {
        $this->expectException('Pop\Kettle\Exception');
        (new Model\Queue())->buildWorker(getcwd(), $this->makeApp());
    }

    public function testBuildWorkerDatabaseAdapter()
    {
        touch(getcwd() . '/database.sqlite');
        file_put_contents(getcwd() . '/app/config/database.php', '<?php return ' . var_export([
            'default' => [
                'database' => getcwd() . '/database.sqlite',
                'adapter'  => 'sqlite',
                'username' => null,
                'password' => null,
                'host'     => null,
                'type'     => null,
            ],
        ], true) . ';');

        $this->writeQueueConfig([
            'default' => [
                'adapter'    => 'database',
                'connection' => 'default',
                'table'      => 'pop_queue',
                'priority'   => 'FIFO',
                'lease'      => 60,
                'weight'     => 0,
            ],
        ]);

        $worker = (new Model\Queue())->buildWorker(getcwd(), $this->makeApp());

        $this->assertTrue($worker->hasQueue('default'));
    }

    public function testBuildWorkerUnknownAdapterThrows()
    {
        $this->writeQueueConfig([
            'default' => ['adapter' => 'bogus', 'weight' => 0],
        ]);

        $this->expectException('Pop\Kettle\Exception');
        (new Model\Queue())->buildWorker(getcwd(), $this->makeApp());
    }

    public function testClearClearsPendingJobsByDefault()
    {
        $this->seedFileQueueConfig();
        $worker = (new Model\Queue())->buildWorker(getcwd(), $this->makeApp());
        $worker->getQueue('default')->addJob(\Pop\Queue\Process\Job::create(function() {}));

        (new Model\Queue())->clear($worker, 'default');

        $summary = (new Model\Queue())->jobsSummary($worker->getQueue('default'));
        $this->assertSame(0, $summary['pending']);
    }

    public function testClearFailedAndTasksCombine()
    {
        $this->seedFileQueueConfig();
        $worker = (new Model\Queue())->buildWorker(getcwd(), $this->makeApp());

        $job = \Pop\Queue\Process\Job::create(function() { throw new \Exception('nope'); });
        $job->setMaxAttempts(1);
        $worker->getQueue('default')->addJob($job);
        $worker->work('default');

        $task = \Pop\Queue\Process\Task::create(function() {})->everyMinute();
        $worker->getQueue('default')->addTask($task);

        (new Model\Queue())->clear($worker, 'default', true, true);

        $jobSummary = (new Model\Queue())->jobsSummary($worker->getQueue('default'));
        $this->assertSame(0, $jobSummary['dead']);
        $this->assertSame([], (new Model\Queue())->tasksSummary($worker->getQueue('default')));
    }

    public function testJobsSummaryListsDeadJobsWithReason()
    {
        $this->seedFileQueueConfig();
        $worker = (new Model\Queue())->buildWorker(getcwd(), $this->makeApp());

        $job = \Pop\Queue\Process\Job::create(function() { throw new \Exception('boom'); });
        $job->setMaxAttempts(1);
        $worker->getQueue('default')->addJob($job);
        $worker->work('default');

        $summary = (new Model\Queue())->jobsSummary($worker->getQueue('default'));

        $this->assertSame(1, $summary['dead']);
        $this->assertCount(1, $summary['deadJobs']);
        $this->assertStringContainsString('boom', reset($summary['deadJobs']));
    }

    public function testTasksSummaryListsScheduleAndGracePeriod()
    {
        $this->seedFileQueueConfig();
        $worker = (new Model\Queue())->buildWorker(getcwd(), $this->makeApp());

        $task = \Pop\Queue\Process\Task::create(function() {}, id: 'nightly-report')->everyMinute();
        $task->setGracePeriod(30);
        $worker->getQueue('default')->addTask($task);

        $summary = (new Model\Queue())->tasksSummary($worker->getQueue('default'));

        $this->assertArrayHasKey('nightly-report', $summary);
        $this->assertSame(30, $summary['nightly-report']['gracePeriod']);
        $this->assertNotNull($summary['nightly-report']['schedule']);
    }

}
