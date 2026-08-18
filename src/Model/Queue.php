<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Kettle\Model;

use Pop\Console\Console;
use Pop\Kettle\Exception;
use Pop\Utils\AbstractModel;

/**
 * Queue model class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
class Queue extends AbstractModel
{

    /**
     * Configure queue
     *
     * @param  Console $console
     * @param  string  $location
     * @param  string  $queue
     * @return Queue
     */
    public function configure(Console $console, string $location, string $queue = 'default'): Queue
    {
        $adapterChoices = [];
        $i              = 1;

        $console->write($i . ': File');
        $adapterChoices['file'] = $i;
        $i++;

        $console->write($i . ': Database');
        $adapterChoices['database'] = $i;
        $i++;

        if (class_exists('Redis', false)) {
            $console->write($i . ': Redis');
            $adapterChoices['redis'] = $i;
            $i++;
        }

        $console->write();
        $selected = $console->prompt('Please select one of the above queue adapters: ', $adapterChoices);
        $console->write();

        $adapter = array_search($selected, $adapterChoices);
        $fields  = [];

        if ($adapter == 'file') {
            $default = 'data/queue/' . $queue;
            $folder  = $console->prompt('Queue Folder: [' . $default . '] ', null, true);
            $folder  = ($folder == '') ? $default : $folder;

            if (!file_exists($location . '/' . $folder)) {
                mkdir($location . '/' . $folder, 0777, true);
            }

            $fields['folder'] = realpath($location . '/' . $folder);
        } else if ($adapter == 'database') {
            $connection = $console->prompt('DB Connection: [default] ', null, true);
            $connection = ($connection == '') ? 'default' : $connection;

            $defaultTable = ($queue == 'default') ? 'pop_queue' : 'pop_queue_' . $queue;
            $table        = $console->prompt('Queue Table: [' . $defaultTable . '] ', null, true);
            $table        = ($table == '') ? $defaultTable : $table;

            $fields['connection'] = $connection;
            $fields['table']      = $table;
        } else if ($adapter == 'redis') {
            $host = $console->prompt('Redis Host: [localhost] ', null, true);
            $host = ($host == '') ? 'localhost' : $host;

            $port = $console->prompt('Redis Port: [6379] ', null, true);
            $port = ($port == '') ? '6379' : $port;

            $defaultPrefix = ($queue == 'default') ? 'pop-queue' : 'pop-queue-' . $queue;
            $prefix        = $console->prompt('Redis Prefix: [' . $defaultPrefix . '] ', null, true);
            $prefix        = ($prefix == '') ? $defaultPrefix : $prefix;

            $password = $console->prompt('Redis Password: [none] ', null, true);

            $fields['host']     = $host;
            $fields['port']     = $port;
            $fields['prefix']   = $prefix;
            $fields['password'] = $password;
        }

        $priority = $console->prompt('Queue Priority (FIFO/FILO): [FIFO] ', null, true);
        $priority = ($priority == '') ? 'FIFO' : strtoupper($priority);

        $lease = $console->prompt('Lease Seconds: [60] ', null, true);
        $lease = ($lease == '') ? '60' : $lease;

        $weight = $console->prompt('Queue Weight: [0] ', null, true);
        $weight = ($weight == '') ? '0' : $weight;

        $fields['priority'] = $priority;
        $fields['lease']    = $lease;
        $fields['adapter']  = $adapter;

        $this->writeEnv($location, $queue, $fields);
        $this->writeConfig($location, $queue, $fields, (int)$weight);

        return $this;
    }

    /**
     * Write queue env vars, upserting into .env - unprefixed QUEUE_* for the
     * 'default' queue, QUEUE_<NAME>_* for any other queue name
     *
     * @param  string $location
     * @param  string $queue
     * @param  array  $fields
     * @return void
     */
    protected function writeEnv(string $location, string $queue, array $fields): void
    {
        $prefix  = ($queue == 'default') ? 'QUEUE_' : 'QUEUE_' . strtoupper($queue) . '_';
        $envFile = $location . '/.env';

        if (!file_exists($envFile)) {
            copy(__DIR__ . '/../../config/templates/.env.example', $envFile);
        }

        $env = file_get_contents($envFile);

        foreach ($fields as $key => $value) {
            $envKey = $prefix . strtoupper($key);
            $value  = (string)$value;

            // Any value containing a space has to be quoted, or the resulting .env is unparseable
            if (str_contains($value, ' ') && !str_starts_with($value, '"') && !str_ends_with($value, '"')) {
                $value = '"' . $value . '"';
            }

            $pattern = '/^' . preg_quote($envKey, '/') . '=.*$/m';
            $line    = $envKey . '=' . $value;

            if (($queue == 'default') && preg_match($pattern, $env)) {
                // Callback form so that any $1/\1 sequences in the value are written verbatim
                $env = preg_replace_callback($pattern, fn() => $line, $env);
            } else {
                $env .= PHP_EOL . $line;
            }
        }

        file_put_contents($envFile, $env);

        (\Dotenv\Dotenv::createMutable($location))->safeLoad();
    }

    /**
     * Append this queue's config block to app/config/queue.php, creating the
     * file first if it doesn't exist yet
     *
     * @param  string $location
     * @param  string $queue
     * @param  array  $fields
     * @param  int    $weight
     * @return void
     */
    protected function writeConfig(string $location, string $queue, array $fields, int $weight): void
    {
        $configFile = $location . '/app/config/queue.php';

        if (!file_exists($configFile)) {
            if (!file_exists($location . '/app/config')) {
                mkdir($location . '/app/config', 0777, true);
            }
            file_put_contents($configFile, '<?php' . PHP_EOL . PHP_EOL . 'return [' . PHP_EOL . '];' . PHP_EOL);
        }

        $prefix = ($queue == 'default') ? 'QUEUE_' : 'QUEUE_' . strtoupper($queue) . '_';

        $block  = '    \'' . $queue . '\' => [' . PHP_EOL;
        $block .= '        \'adapter\'  => $_ENV[\'' . $prefix . 'ADAPTER\'],' . PHP_EOL;

        foreach ($fields as $key => $value) {
            if ($key == 'adapter') {
                continue;
            }
            $envKey = $prefix . strtoupper($key);
            $block .= '        \'' . $key . '\' => $_ENV[\'' . $envKey . '\'],' . PHP_EOL;
        }

        $block .= '        \'weight\'   => ' . $weight . ',' . PHP_EOL;
        $block .= '    ],' . PHP_EOL;

        $contents = file_get_contents($configFile);
        $contents = str_replace('];', $block . '];', $contents);

        file_put_contents($configFile, $contents);
    }

    /**
     * Build a worker for the given queue (or every configured queue when $queue == 'all')
     *
     * @param  string           $location
     * @param  \Pop\Application $app
     * @param  string           $queue
     * @throws Exception
     * @return \Pop\Queue\Worker
     */
    public function buildWorker(string $location, \Pop\Application $app, string $queue = 'default'): \Pop\Queue\Worker
    {
        $configFile = $location . '/app/config/queue.php';

        if (!file_exists($configFile)) {
            throw new Exception('Error: The queue configuration was not found.');
        }

        $queueConfig = include $configFile;

        if ($queue == 'all') {
            $names = array_keys($queueConfig);
        } else {
            if (!isset($queueConfig[$queue])) {
                throw new Exception("Error: The queue configuration was not found for '" . $queue . "'.");
            }
            $names = [$queue];
        }

        $worker = \Pop\Queue\Worker::create(null, $app);

        foreach ($names as $name) {
            $config = $queueConfig[$name];
            $worker->addQueue($this->createQueue($location, $name, $config), (int)($config['weight'] ?? 0));
        }

        return $worker;
    }

    /**
     * Build a single Queue object from its stored config
     *
     * @param  string $location
     * @param  string $name
     * @param  array  $config
     * @throws Exception
     * @return \Pop\Queue\Queue
     */
    protected function createQueue(string $location, string $name, array $config): \Pop\Queue\Queue
    {
        $adapter = match ($config['adapter'] ?? null) {
            'file'     => $this->createFileAdapter($location, $name, $config),
            'database' => $this->createDatabaseAdapter($location, $config),
            'redis'    => $this->createRedisAdapter($config),
            default    => throw new Exception("Error: Unknown queue adapter '" . ($config['adapter'] ?? '') . "'."),
        };

        return \Pop\Queue\Queue::create($name, $adapter, $config['priority'] ?? null);
    }

    /**
     * @param  string $location
     * @param  string $name
     * @param  array  $config
     * @return \Pop\Queue\Adapter\File
     */
    protected function createFileAdapter(string $location, string $name, array $config): \Pop\Queue\Adapter\File
    {
        $folder = $config['folder'] ?? ($location . '/data/queue/' . $name);
        if (!file_exists($folder)) {
            mkdir($folder, 0777, true);
        }

        return new \Pop\Queue\Adapter\File($folder, $config['priority'] ?? null, (int)($config['lease'] ?? 60));
    }

    /**
     * @param  string $location
     * @param  array  $config
     * @throws Exception
     * @return \Pop\Queue\Adapter\Database
     */
    protected function createDatabaseAdapter(string $location, array $config): \Pop\Queue\Adapter\Database
    {
        $connection   = $config['connection'] ?? 'default';
        $dbConfigFile = $location . '/app/config/database.php';

        if (!file_exists($dbConfigFile)) {
            throw new Exception('Error: The database configuration was not found.');
        }

        $dbConfig = include $dbConfigFile;

        if (!isset($dbConfig[$connection])) {
            throw new Exception("Error: The database configuration was not found for '" . $connection . "'.");
        }

        $db = \Pop\Db\Db::connect(
            $dbConfig[$connection]['adapter'], array_diff_key($dbConfig[$connection], array_flip(['adapter']))
        );

        return new \Pop\Queue\Adapter\Database(
            $db, $config['table'] ?? 'pop_queue', $config['priority'] ?? null, (int)($config['lease'] ?? 60)
        );
    }

    /**
     * @param  array $config
     * @return \Pop\Queue\Adapter\Redis
     */
    protected function createRedisAdapter(array $config): \Pop\Queue\Adapter\Redis
    {
        $password = !empty($config['password']) ? $config['password'] : null;

        return new \Pop\Queue\Adapter\Redis(
            $config['host'] ?? 'localhost',
            $config['port'] ?? 6379,
            $config['prefix'] ?? 'pop-queue',
            $config['priority'] ?? null,
            (int)($config['lease'] ?? 60),
            $password
        );
    }

    /**
     * Clear jobs/failed jobs/tasks from the given queue (or every configured queue when $queue == 'all')
     *
     * @param  \Pop\Queue\Worker $worker
     * @param  string            $queue
     * @param  bool              $failed
     * @param  bool              $tasks
     * @return void
     */
    public function clear(\Pop\Queue\Worker $worker, string $queue, bool $failed = false, bool $tasks = false): void
    {
        $names = ($queue == 'all') ? array_keys($worker->getQueues()) : [$queue];

        foreach ($names as $name) {
            if (!$failed && !$tasks) {
                $worker->clear($name);
            }
            if ($failed) {
                $worker->clearFailed($name);
            }
            if ($tasks) {
                $worker->clearTasks($name);
            }
        }
    }

    /**
     * Summarize pending/dead-letter jobs for a queue
     *
     * @param  \Pop\Queue\Queue $queue
     * @return array
     */
    public function jobsSummary(\Pop\Queue\Queue $queue): array
    {
        $adapter = $queue->getAdapter();

        $summary = [
            'pending'  => $adapter->count(),
            'dead'     => $adapter->countDead(),
            'deadJobs' => [],
        ];

        if ($summary['dead'] > 0) {
            foreach ($adapter->getDeadJobs() as $jobId => $job) {
                $reason = null;
                if (($job instanceof \Pop\Queue\Process\AbstractJob) && $job->hasFailedMessages()) {
                    $messages = $job->getFailedMessages();
                    $reason   = end($messages);
                }
                $summary['deadJobs'][$jobId] = $reason;
            }
        }

        return $summary;
    }

    /**
     * Summarize scheduled tasks for a queue
     *
     * @param  \Pop\Queue\Queue $queue
     * @return array
     */
    public function tasksSummary(\Pop\Queue\Queue $queue): array
    {
        $summary = [];

        foreach ($queue->getScheduledTasks() as $taskId => $task) {
            $summary[$taskId] = [
                'schedule'    => $task->cron()?->getSchedule(),
                'gracePeriod' => $task->hasGracePeriod() ? $task->getGracePeriod() : null,
            ];
        }

        return $summary;
    }

}
