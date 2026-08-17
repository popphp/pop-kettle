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
namespace Pop\Kettle\Model;

use Pop\Utils\AbstractModel;

/**
 * Console asset model class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <nick@noladev.com>
 * @copyright  Copyright (c) 2012-2025 NOLA Interactive, LLC.
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
class Asset extends AbstractModel
{

    /**
     * Constructor
     *
     * @param string $npmBinary
     */
    public function __construct(protected string $npmBinary = 'npm')
    {
    }

    /**
     * Whether a package.json exists at the given location
     *
     * @param  string $location
     * @return bool
     */
    public function isInstalled(string $location): bool
    {
        return file_exists($location . DIRECTORY_SEPARATOR . 'package.json');
    }

    /**
     * Whether the configured npm binary is available on the PATH
     *
     * @return bool
     */
    public function isNpmAvailable(): bool
    {
        exec('command -v ' . escapeshellarg($this->npmBinary) . ' 2>/dev/null', $output, $exitCode);
        return ($exitCode === 0) && !empty($output);
    }

    /**
     * Run `npm install` at the given location
     *
     * @param  string $location
     * @return int
     */
    public function install(string $location): int
    {
        return $this->run([$this->npmBinary, 'install'], $location);
    }

    /**
     * Run `npm run watch` at the given location
     *
     * @param  string $location
     * @return int
     */
    public function watch(string $location): int
    {
        return $this->run([$this->npmBinary, 'run', 'watch'], $location);
    }

    /**
     * Run `npm run build` at the given location
     *
     * @param  string $location
     * @return int
     */
    public function build(string $location): int
    {
        return $this->run([$this->npmBinary, 'run', 'build'], $location);
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
