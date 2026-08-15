<?php
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

        $console->write();
        $selected = $console->prompt('Please select one of the above queue adapters: ', $adapterChoices);
        $console->write();

        $adapter = array_search($selected, $adapterChoices);
        $fields  = [];

        if ($adapter == 'file') {
            $default = 'database/queue/' . $queue;
            $folder  = $console->prompt('Queue Folder: [' . $default . '] ', null, true);
            $folder  = ($folder == '') ? $default : $folder;

            if (!file_exists($location . '/' . $folder)) {
                mkdir($location . '/' . $folder, 0777, true);
            }

            $fields['folder'] = realpath($location . '/' . $folder);
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
        $env     = file_get_contents($envFile);

        foreach ($fields as $key => $value) {
            $envKey = $prefix . strtoupper($key);

            if (($queue == 'default') && preg_match('/^' . preg_quote($envKey, '/') . '=.*$/m', $env)) {
                $env = preg_replace('/^' . preg_quote($envKey, '/') . '=.*$/m', $envKey . '=' . $value, $env);
            } else {
                $env .= PHP_EOL . $envKey . '=' . $value;
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

}
