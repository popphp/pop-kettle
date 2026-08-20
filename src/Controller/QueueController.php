<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/pop-bootstrap
 * @author     Nick Sagona, III <nick@noladev.com>
 * @copyright  Copyright (c) 2012-2025 NOLA Interactive, LLC.
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Kettle\Controller;

use Pop\Console\Color;
use Pop\Kettle\Model;

/**
 * Console queue controller class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <nick@noladev.com>
 * @copyright  Copyright (c) 2012-2025 NOLA Interactive, LLC.
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
class QueueController extends AbstractController
{

    /**
     * Resolve the consuming application's own Application instance, printing a friendly
     * error and returning null if nothing is scaffolded
     *
     * @return ?\Pop\Application
     */
    protected function resolveApp(): ?\Pop\Application
    {
        try {
            $app = (new Model\Application())->resolveAppInstance(
                getcwd(), $this->application->autoloader(), $this->application->config()['routes']
            );
        } catch (\Exception $e) {
            $app = null;
        }

        // Null means either nothing is scaffolded, or the app class isn't autoloadable
        // (e.g. a stale composer autoloader - run `composer dump-autoload`) - either way, say so
        if ($app === null) {
            $this->console->write($this->console->colorize(
                "No application was detected. Run 'pop:init' first.", Color::BOLD_RED
            ));
        }

        return $app;
    }

    /**
     * Resolve the app and build a worker for $queue, printing a friendly error and
     * returning null on failure
     *
     * @param  string $queue
     * @return ?\Pop\Queue\Worker
     */
    protected function worker(string $queue): ?\Pop\Queue\Worker
    {
        $app = $this->resolveApp();
        if ($app === null) {
            return null;
        }

        try {
            return (new Model\Queue())->buildWorker(getcwd(), $app, $queue);
        } catch (\Exception $e) {
            $this->console->write($this->console->colorize($e->getMessage(), Color::BOLD_RED));
            return null;
        }
    }

    /**
     * Config command
     *
     * @param  ?string $queue
     * @return void
     */
    public function config(?string $queue = 'default'): void
    {
        if ($queue === null) {
            $queue = 'default';
        }

        (new Model\Queue())->configure($this->console, getcwd(), $queue);
    }

    /**
     * Work command
     *
     * @param  ?string $queue
     * @param  array   $options
     * @return void
     */
    public function work(?string $queue = 'default', array $options = []): void
    {
        if ($queue === null) {
            $queue = 'default';
        }

        $worker = $this->worker($queue);
        if ($worker === null) {
            return;
        }

        if (isset($options['once'])) {
            if ($queue == 'all') {
                $worker->workAll();
            } else {
                $worker->work($queue);
            }
            $this->console->write('Worker pass complete.');
        } else {
            $sleep = isset($options['sleep']) ? (int)$options['sleep'] : 1;
            $this->console->write('Worker started. Press Ctrl+C to stop.');
            $worker->workLoop($sleep);
        }
    }

    /**
     * Scheduler command
     *
     * @param  ?string $queue
     * @param  array   $options
     * @return void
     */
    public function scheduler(?string $queue = 'default', array $options = []): void
    {
        if ($queue === null) {
            $queue = 'default';
        }

        $worker = $this->worker($queue);
        if ($worker === null) {
            return;
        }

        if (isset($options['once'])) {
            if ($queue == 'all') {
                $worker->runAll();
            } else {
                $worker->run($queue);
            }
            $this->console->write('Scheduler pass complete.');
        } else {
            $sleep = isset($options['sleep']) ? (int)$options['sleep'] : 1;
            $this->console->write('Scheduler started. Press Ctrl+C to stop.');
            $worker->runLoop($sleep);
        }
    }

    /**
     * Clear command
     *
     * @param  ?string $queue
     * @param  array   $options
     * @return void
     */
    public function clear(?string $queue = 'default', array $options = []): void
    {
        if ($queue === null) {
            $queue = 'default';
        }

        $worker = $this->worker($queue);
        if ($worker === null) {
            return;
        }

        (new Model\Queue())->clear($worker, $queue, isset($options['failed']), isset($options['tasks']));
        $this->console->write("Queue '" . $queue . "' cleared.");
    }

    /**
     * Jobs command
     *
     * @param  ?string $queue
     * @return void
     */
    public function jobs(?string $queue = 'default'): void
    {
        if ($queue === null) {
            $queue = 'default';
        }

        $worker = $this->worker($queue);
        if ($worker === null) {
            return;
        }

        $model = new Model\Queue();
        $names = ($queue == 'all') ? array_keys($worker->getQueues()) : [$queue];

        foreach ($names as $name) {
            $summary = $model->jobsSummary($worker->getQueue($name));

            $this->console->write($this->console->colorize("Queue '" . $name . "':", Color::BOLD_CYAN));
            $this->console->write('    Pending: ' . $summary['pending']);
            $this->console->write('    Dead:    ' . $summary['dead']);

            foreach ($summary['deadJobs'] as $jobId => $reason) {
                $this->console->write('        - ' . $jobId . (($reason !== null) ? ' (' . $reason . ')' : ''));
            }
        }
    }

    /**
     * Tasks command
     *
     * @param  ?string $queue
     * @return void
     */
    public function tasks(?string $queue = 'default'): void
    {
        if ($queue === null) {
            $queue = 'default';
        }

        $worker = $this->worker($queue);
        if ($worker === null) {
            return;
        }

        $model = new Model\Queue();
        $names = ($queue == 'all') ? array_keys($worker->getQueues()) : [$queue];

        foreach ($names as $name) {
            $summary = $model->tasksSummary($worker->getQueue($name));

            $this->console->write($this->console->colorize("Queue '" . $name . "':", Color::BOLD_CYAN));

            if (empty($summary)) {
                $this->console->write('    No scheduled tasks.');
            } else {
                foreach ($summary as $taskId => $task) {
                    $grace = ($task['gracePeriod'] !== null) ? ' (grace: ' . $task['gracePeriod'] . 's)' : '';
                    $this->console->write('    - ' . $taskId . ': ' . $task['schedule'] . $grace);
                }
            }
        }
    }

}
