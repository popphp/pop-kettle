<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/pop-bootstrap
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Kettle\Model;

use Pop\Utils\AbstractModel;

/**
 * Console composer model class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
class Composer extends AbstractModel
{

    /**
     * composer binary to invoke
     * @var string
     */
    protected string $composerBinary = 'composer';

    /**
     * Constructor
     *
     * @param string $composerBinary
     */
    public function __construct(string $composerBinary = 'composer')
    {
        parent::__construct();
        $this->composerBinary = $composerBinary;
    }

    /**
     * Whether the configured composer binary is available on the PATH
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            exec('where ' . escapeshellarg($this->composerBinary), $output, $exitCode);
        } else {
            exec('command -v ' . escapeshellarg($this->composerBinary) . ' 2>/dev/null', $output, $exitCode);
        }

        return ($exitCode === 0) && !empty($output);
    }

    /**
     * Run `composer dump-autoload` at the given location
     *
     * @param  string $location
     * @return int
     */
    public function dumpAutoload(string $location): int
    {
        return $this->run([$this->composerBinary, 'dump-autoload'], $location);
    }

    /**
     * Run a command with inherited stdio, returning its exit code
     *
     * @param  array  $command
     * @param  string $cwd
     * @return int
     */
    protected function run(array $command, string $cwd): int
    {
        $process = proc_open($command, [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes, $cwd);
        if (!is_resource($process)) {
            return 1;
        }
        return proc_close($process);
    }

}
