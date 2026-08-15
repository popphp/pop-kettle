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
        $this->assertDirectoryExists(getcwd() . '/database/queue/default');
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
        $this->assertDirectoryExists(getcwd() . '/database/queue/logging');

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

}
