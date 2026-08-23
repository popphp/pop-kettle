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
 * Console asset model class
 *
 * @category   Pop\Kettle
 * @package    Pop\Kettle
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2026 Nick Sagona, III
 * @license    https://www.popphp.org/license     New BSD License
 * @version    3.0.0
 */
class Asset extends AbstractModel
{

    /**
     * npm binary to invoke
     * @var string
     */
    protected string $npmBinary = 'npm';

    /**
     * Constructor
     *
     * @param string $npmBinary
     */
    public function __construct(string $npmBinary = 'npm')
    {
        parent::__construct();
        $this->npmBinary = $npmBinary;
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
        if (PHP_OS_FAMILY === 'Windows') {
            exec('where ' . escapeshellarg($this->npmBinary), $output, $exitCode);
        } else {
            exec('command -v ' . escapeshellarg($this->npmBinary) . ' 2>/dev/null', $output, $exitCode);
        }

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
        $result = $this->run([$this->npmBinary, 'run', 'watch'], $location);
        $this->touchAssetMarkers($location);
        return $result;
    }

    /**
     * Run `npm run build` at the given location
     *
     * @param  string $location
     * @return int
     */
    public function build(string $location): int
    {
        $result = $this->run([$this->npmBinary, 'run', 'build'], $location);
        $this->touchAssetMarkers($location);
        return $result;
    }

    /**
     * Re-create the .empty marker files in the build output folders, which
     * vite's build step wipes out along with the rest of the outDir contents
     * on every rebuild, so the otherwise-empty folders still commit to Git
     *
     * @param  string $location
     * @return void
     */
    protected function touchAssetMarkers(string $location): void
    {
        foreach (['css', 'js'] as $type) {
            $dir = $location . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . $type;
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            touch($dir . DIRECTORY_SEPARATOR . '.empty');
        }
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
